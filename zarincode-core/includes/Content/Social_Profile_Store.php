<?php
/**
 * Bounded social-profile storage and safe icon sanitization.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Content;

defined( 'ABSPATH' ) || exit;
/**
 * Durable bounded social profiles.
 *
 * @phpstan-type SocialRow array{id:string,name:string,url:string,share_template:string,type:string,font:string,svg:string,image:int,enabled:bool}
 */
final class Social_Profile_Store {
	/** Register the single native admin submission handler. */
	public static function boot(): void {
		add_action( 'admin_init', array( self::class, 'save' ) );}
	/** Save only nonce-protected administrator submissions, preserving unknown stored keys. */
	public static function save(): void {
		if ( ! isset( $_POST['zc_social_save'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_socials' );
		if ( ! isset( $_POST['zc_socials'] ) || ( ! is_array( $_POST['zc_socials'] ) && '' !== $_POST['zc_socials'] ) ) {
			return;
		}
		$old   = get_option( 'zarincode_custom_socials', array() );
		$by_id = array();
		foreach ( is_array( $old ) ? $old : array() as $row ) {
			if ( is_array( $row ) && array_key_exists( '_zc_profile_schema', $row ) && 1 !== $row['_zc_profile_schema'] ) {
					do_action( 'zarincode_policy_write_rejected', 0, 'unsupported_schema' );
				return;
			}
			if ( is_array( $row ) && is_string( $row['id'] ?? null ) ) {
				$by_id[ $row['id'] ] = $row;
			}
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize validates the entire bounded profile schema, including URLs and SVG.
		$clean = self::sanitize( wp_unslash( $_POST['zc_socials'] ) );
		foreach ( $clean as &$row ) {
			$row = array_replace( $by_id[ $row['id'] ] ?? array(), $row, array( '_zc_profile_schema' => 1 ) );
		}unset( $row );
		if ( update_option( 'zarincode_custom_socials', $clean, false ) || get_option( 'zarincode_custom_socials' ) === $clean ) {
			do_action( 'zarincode_social_profiles_saved' );}
	}
	/**
	 * Sanitize a bounded public profile value.
	 *
	 * @param mixed $raw Untrusted SVG markup.
	 */
	public static function svg( $raw ): string {
		if ( ! is_string( $raw ) || trim( $raw ) === '' || strlen( $raw ) > 50000 || stripos( $raw, '<!DOCTYPE' ) !== false || stripos( $raw, '<!ENTITY' ) !== false || ! class_exists( 'DOMDocument' ) ) {
			return '';
		}
		$dom      = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		try {
			$valid = $dom->loadXML( $raw, LIBXML_NONET );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}if ( ! $valid || ! $dom->documentElement || 'svg' !== $dom->documentElement->localName ) {
			return '';
		}
		self::clean_svg( $dom->documentElement );
		$dom->documentElement->setAttribute( 'width', '24' );
		$dom->documentElement->setAttribute( 'height', '24' );
		$dom->documentElement->setAttribute( 'aria-hidden', 'true' );
		$dom->documentElement->setAttribute( 'focusable', 'false' );
		$xml = $dom->saveXML( $dom->documentElement );
		return false === $xml ? '' : $xml;
	}
	/**
	 * Clean only allowed geometry; never permits URLs, scripts or external entities.
	 *
	 * @param \DOMElement $node Sanitized document subtree.
	 */
	private static function clean_svg( \DOMElement $node ): void {
		$tags  = array( 'svg', 'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse' );
		$attrs = array( 'viewBox', 'd', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'opacity', 'fill-opacity', 'stroke-opacity', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'points', 'transform' );
		foreach ( iterator_to_array( $node->childNodes ) as $child ) {
			if ( ! $child instanceof \DOMElement || ! in_array( $child->localName, $tags, true ) ) {
				$node->removeChild( $child );
				continue;}
			self::clean_svg( $child );
		}
		foreach ( iterator_to_array( $node->attributes ) as $a ) {
			$v       = $a->value;
			$allowed = in_array( $a->name, $attrs, true ) && ! preg_match( '/url\s*\(|javascript|data:|[<>"\']/i', $v );
			if ( in_array( $a->name, array( 'fill', 'stroke' ), true ) ) {
				$allowed = $allowed && (bool) preg_match( '/^(?:#[a-f0-9]{3,8}|none|currentColor|transparent|black|white)$/i', $v );
			}
			if ( ! $allowed ) {
				$node->removeAttributeNode( $a );
			}
		}
	}
	/**
	 * Sanitize a bounded public profile value.
	 *
	 * @param mixed $raw Untrusted URL.
	 */
	public static function url( $raw ): string {
		if ( ! is_string( $raw ) ) {
			return '';
		}return in_array( wp_parse_url( $raw, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ? esc_url_raw( $raw ) : '';}
	/**
	 * Sanitize a bounded public profile value.
	 *
	 * @param mixed $raw Untrusted official share template.
	 */
	public static function template( $raw ): string {
		if ( ! is_string( $raw ) || strlen( $raw ) > 2048 ) {
			return '';
		}$tokens = array( '{url}', '{title}', '{text}' );
		$safe    = array( 'ZCSHAREURL', 'ZCSHARETITLE', 'ZCSHARETEXT' );
		$v       = self::url( str_replace( $tokens, $safe, $raw ) );
		return str_replace( $safe, $tokens, $v );
	}
	/**
	 * Validate the complete bounded social profile schema.
	 *
	 * @param mixed $rows Untrusted profile list.
	 * @return list<SocialRow>
	 */
	public static function sanitize( $rows ): array {
		$out  = array();
		$seen = array();
		if ( ! is_array( $rows ) ) {
			return array();
		}foreach ( array_slice( $rows, 0, 30 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}$name = sanitize_text_field( is_scalar( $row['name'] ?? null ) ? (string) $row['name'] : '' );
			if ( '' === $name ) {
				continue;
			}
			$key = sanitize_key( is_scalar( $row['id'] ?? null ) ? (string) $row['id'] : '' );
			if ( ! $key || isset( $seen[ $key ] ) ) {
				$key = wp_generate_uuid4();
			}$seen[ $key ] = true;
			$font          = is_string( $row['font'] ?? null ) ? $row['font'] : '';
			$out[]         = array(
				'id'             => $key,
				'name'           => $name,
				'url'            => self::url( $row['url'] ?? '' ),
				'share_template' => self::template( $row['share_template'] ?? '' ),
				'type'           => in_array( $row['type'] ?? '', array( 'font', 'svg', 'image' ), true ) ? $row['type'] : 'font',
				'font'           => preg_match( '/^(?:fa[sbrldt]?|fa-(?:solid|regular|brands|light|thin|duotone))\s+fa-[a-z0-9-]+$/D', $font ) ? $font : 'fa-solid fa-link',
				'svg'            => self::svg( $row['svg'] ?? '' ),
				'image'          => is_numeric( $row['image'] ?? null ) ? absint( $row['image'] ) : 0,
				'enabled'        => ! empty( $row['enabled'] ),
			);
		}return $out;
	}
	/**
	 * Return enabled, sanitized rows without a stale per-request option snapshot.
	 *
	 * @return list<SocialRow>
	 */
	public static function rows(): array {
		return array_values( array_filter( self::sanitize( get_option( 'zarincode_custom_socials', array() ) ), static fn( $row )=>$row['enabled'] ) );
	}
}
