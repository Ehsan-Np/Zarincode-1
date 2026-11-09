<?php
/**
 * Indexing + AI readiness settings (v4.8.0).
 *
 * One autoloaded option keeps the whole feature free of extra queries on the
 * front end: WordPress already loads `zarincode_seo` with the other options, so
 * reading a setting costs nothing. Defaults are merged in memory and cached in a
 * static for the request.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Store {

	public const OPTION = 'zarincode_seo';

	/** Request-level cache: the option is read once, no matter how often it is used. */
	private static ?array $cache = null;

	/** Fields that are URLs. */
	private const URL_FIELDS = array( 'org_logo', 'ai_license_url' );

	/** Verification tokens: accept the bare value or the whole <meta> tag. */
	private const VERIFY_FIELDS = array( 'search_console', 'bing', 'yandex', 'baidu', 'pinterest' );

	/**
	 * Defaults. `ai_bot_policy` defaults to the 2026 recommendation: keep the
	 * crawlers that produce citations, leave training crawlers to the site owner.
	 */
	public static function defaults(): array {
		return array(
			// --- indexing ---
			'enabled'            => 'yes',
			'search_console'     => '',
			'bing'               => '',
			'yandex'             => '',
			'baidu'              => '',
			'pinterest'          => '',
			'extra_head'         => '',
			'robots_extra'       => '',
			'max_image_preview'  => 'large',
			'max_snippet'        => '-1',
			'noindex_search'     => 'yes',
			'noindex_author'     => 'yes',
			'noindex_date'       => 'yes',
			'noindex_tags'       => 'no',
			'noindex_media'      => 'yes',
			'noindex_paged_404'  => 'yes',
			'sitemap'            => 'yes',
			'indexnow'           => 'no',
			'indexnow_key'       => '',
			'org_schema'         => 'yes',
			'org_logo'           => '',
			'org_same_as'        => '',
			'breadcrumb_schema'  => 'yes',
			'article_schema'     => 'yes',
			// --- AI ---
			'llms'               => 'yes',
			'ai_bot_policy'      => 'all',
			'ai_summary'         => '',
			'ai_contact'         => '',
			'ai_license_url'     => '',
			'ai_extra'           => '',
			'ai_sections'        => 'yes',
			// --- diagnostics ---
			'last_scan'          => 0,
			'last_score'         => 0,
		);
	}

	/** Full settings array (defaults + saved values). */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		self::$cache = array_merge( self::defaults(), $saved );
		return self::$cache;
	}

	/** One setting. */
	public static function get( string $key, $fallback = '' ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/** Boolean helper for 'yes'/'no' switches. */
	public static function on( string $key ): bool {
		return 'yes' === self::get( $key, 'no' );
	}

	/** Persists a partial update and refreshes the cache. */
	public static function save( array $values ): void {
		$clean = array();
		foreach ( $values as $key => $value ) {
			$clean[ $key ] = self::sanitize( (string) $key, $value );
		}
		$merged = array_merge( self::all(), $clean );
		update_option( self::OPTION, $merged, true );
		self::$cache = $merged;
		self::flush_caches();
	}

	/**
	 * Writes a single internal value into the same autoloaded option.
	 *
	 * Used for bookkeeping such as the rewrite-rules hash: a separate option
	 * (`add_option(..., autoload: false)`) would cost one extra query on every
	 * front-end request, which is exactly what this feature must not do.
	 */
	public static function remember( string $key, $value ): void {
		$all         = self::all();
		$all[ $key ] = $value;
		update_option( self::OPTION, $all, true );
		self::$cache = $all;
	}

	/** Clears the caches the feature owns (llms.txt, scan results). */
	public static function flush_caches(): void {
		delete_transient( Llms::CACHE_KEY );
		delete_transient( Scanner::CACHE_KEY );
	}

	/**
	 * Field-level sanitising. Unknown keys are accepted but reduced to text so a
	 * future field never becomes a stored XSS vector.
	 */
	public static function sanitize( string $key, $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', array_map( 'strval', $value ) );
		}
		$value = (string) $value;

		if ( in_array( $key, self::VERIFY_FIELDS, true ) ) {
			return sanitize_text_field( Indexing::verification_value( $value ) );
		}

		switch ( $key ) {
			case 'extra_head':
				return self::sanitize_head_code( $value );
			case 'robots_extra':
				return self::sanitize_robots( $value );
			case 'ai_summary':
			case 'ai_extra':
				return sanitize_textarea_field( $value );
			case 'max_snippet':
				return in_array( $value, array( '-1', '0', '20', '50', '100', '150', '200' ), true ) ? $value : '-1';
			case 'max_image_preview':
				return in_array( $value, array( 'large', 'standard', 'none' ), true ) ? $value : 'large';
			case 'ai_bot_policy':
				return in_array( $value, array( 'all', 'nofeed', 'block' ), true ) ? $value : 'all';
			case 'indexnow_key':
				$value = preg_replace( '/[^a-zA-Z0-9-]/', '', $value ) ?? '';
				return substr( $value, 0, 128 );
			case 'org_same_as':
				return implode( "\n", array_filter( array_map( 'esc_url_raw', self::to_lines( $value ) ) ) );
			case 'enabled':
			case 'noindex_search':
			case 'noindex_author':
			case 'noindex_date':
			case 'noindex_tags':
			case 'noindex_media':
			case 'noindex_paged_404':
			case 'sitemap':
			case 'indexnow':
			case 'org_schema':
			case 'breadcrumb_schema':
			case 'article_schema':
			case 'llms':
			case 'ai_sections':
				return 'yes' === $value ? 'yes' : 'no';
			case 'last_scan':
			case 'last_score':
				return absint( $value );
		}

		if ( in_array( $key, self::URL_FIELDS, true ) ) {
			return esc_url_raw( trim( $value ) );
		}
		return sanitize_text_field( $value );
	}

	/** Textarea → lines. */
	private static function to_lines( string $value ): array {
		return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $value ) ?: array() ) ) );
	}

	/** robots.txt additions: keep only the directives the protocol understands. */
	private static function sanitize_robots( string $value ): string {
		$allowed = array( 'user-agent', 'allow', 'disallow', 'crawl-delay', 'clean-param', 'sitemap', 'host' );
		$out     = array();
		foreach ( self::to_lines( $value ) as $line ) {
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				$out[] = sanitize_text_field( $line );
				continue;
			}
			if ( ! str_contains( $line, ':' ) ) {
				continue;
			}
			list( $directive, $path ) = array_map( 'trim', explode( ':', $line, 2 ) );
			if ( ! in_array( strtolower( $directive ), $allowed, true ) ) {
				continue;
			}
			$out[] = $directive . ': ' . sanitize_text_field( $path );
		}
		return implode( "\n", $out );
	}

	/**
	 * Extra indexing code.
	 *
	 * Admins may paste verification `<meta>` tags, `<link rel="canonical|alternate">`
	 * and JSON-LD `<script type="application/ld+json">` blocks. Anything else
	 * (inline JS, iframes, event handlers) is stripped, so a compromised editor
	 * account cannot turn this field into a script injection point.
	 */
	private static function sanitize_head_code( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		$allowed = array(
			'meta' => array(
				'name'       => true,
				'content'    => true,
				'property'   => true,
				'charset'    => true,
				'http-equiv' => true,
			),
			'link' => array(
				'rel'   => true,
				'href'  => true,
				'sizes' => true,
				'type'  => true,
				'hreflang' => true,
			),
		);

		// Keep JSON-LD blocks aside, they are stripped by wp_kses but are legitimate.
		$scripts = array();
		$value   = preg_replace_callback(
			'#<script\b[^>]*type\s*=\s*["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
			static function ( $m ) use ( &$scripts ) {
				$json = trim( $m[1] );
				if ( '' === $json || null === json_decode( $json ) ) {
					return '';
				}
				$scripts[] = $json;
				return '';
			},
			$value
		) ?? '';

		// Drop every other executable/embedded element entirely — leaving the inner
		// text behind would print stray code into <head>.
		$value = preg_replace( '#<(script|style|noscript|iframe|object|embed)\b.*?</\1>#is', '', $value ) ?? '';
		$value = preg_replace( '/<!--.*?-->/s', '', $value ) ?? '';

		$html = wp_kses( $value, $allowed );

		$out = '';
		foreach ( $scripts as $json ) {
			$out .= '<script type="application/ld+json">' . $json . "</script>\n";
		}
		return trim( $out . "\n" . $html );
	}
}
