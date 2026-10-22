<?php
/** Product policy data and validation, independent of the active theme.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Content;

defined( 'ABSPATH' ) || exit;
/** Owns persistent content independently of the active theme. */
final class Product_Policy_Store {
	/**
	 * Read normalized policy content.
	 *
	 * @return array{summary:string,url:string,faqs:list<array{id:string,question:string,answer:string}>}
	 */
	public static function defaults(): array {
		return array(
			'summary' => 'شرایط پشتیبانی و بازگشت وجه را پیش از خرید بررسی کنید. تعهد هر محصول بر اساس مشخصات همان محصول و سیاست منتشرشده فروشگاه است.',
			'url'     => home_url( '/warranty-policy/' ),
			'faqs'    => array(
				array(
					'id'       => 'scope',
					'question' => 'دامنه پشتیبانی چگونه مشخص می‌شود؟',
					'answer'   => 'مدت، کانال و محدوده پشتیبانی باید در مشخصات محصول یا توافق خرید درج شود. اگر موردی روشن نیست، پیش از سفارش از فروشنده بپرسید.',
				),
				array(
					'id'       => 'compatibility',
					'question' => 'چگونه از سازگاری محصول مطمئن شوم؟',
					'answer'   => 'پیش‌نیازها و نسخه‌های پشتیبانی‌شده را با محیط خود مقایسه کنید و موارد اختصاصی را پیش از خرید مطرح کنید.',
				),
			),
		);}
	/**
	 * Sanitize all supported policy fields.
	 *
	 * @param mixed $raw Untrusted policy fields.
	 * @return array{summary:string,url:string,faqs:list<array{id:string,question:string,answer:string}>}
	 */
	public static function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}$seen = array();
		$out   = array(
			'summary' => wp_kses_post( is_string( $raw['summary'] ?? null ) ? $raw['summary'] : '' ),
			'url'     => self::url( $raw['url'] ?? '' ),
			'faqs'    => array(),
		);
		foreach ( array_slice( is_array( $raw['faqs'] ?? null ) ? $raw['faqs'] : array(), 0, 40 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}$q = sanitize_text_field( is_scalar( $row['question'] ?? null ) ? (string) $row['question'] : '' );
			$a  = wp_kses_post( is_string( $row['answer'] ?? null ) ? $row['answer'] : '' );
			if ( '' !== $q && '' !== $a ) {
				$key = sanitize_key( is_scalar( $row['id'] ?? null ) ? (string) $row['id'] : '' );
				if ( ! $key || isset( $seen[ $key ] ) ) {
					$key = wp_generate_uuid4();
				}$seen[ $key ] = true;
				$out['faqs'][] = array(
					'id'       => $key,
					'question' => $q,
					'answer'   => $a,
				);
			}
		}return $out;
	}
	/**
	 * Read normalized policy content.
	 *
	 * @param int $id Product ID.
	 * @return array{summary:string,url:string,faqs:list<array{id:string,question:string,answer:string}>}|array{}
	 */
	public static function get( int $id ): array {
		$mode = get_post_meta( $id, '_zc_policy_mode', true );
		if ( 'hide' === $mode ) {
			return array();
		}return self::sanitize( 'custom' === $mode ? get_post_meta( $id, '_zc_product_policies', true ) : get_option( 'zarincode_product_policies', self::defaults() ) );}

	/**
	 * Allow only explicit HTTP and HTTPS policy links.
	 *
	 * @param mixed $raw Untrusted URL.
	 */
	private static function url( $raw ): string {
		return is_string( $raw ) && in_array( wp_parse_url( $raw, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ? esc_url_raw( $raw ) : '';
	}
	/**
	 * Preserve unknown keys and stable FAQ IDs across partial form updates.
	 *
	 * @param mixed                  $stored Original policy metadata.
	 * @param array<array-key,mixed> $incoming Submitted known fields.
	 * @return array<array-key,mixed>
	 */
	public static function merge( $stored, array $incoming ): array {
		$stored = is_array( $stored ) ? $stored : array();
		$clean  = self::sanitize( array_replace( $stored, $incoming ) );
		$by_id  = array();
		foreach ( is_array( $stored['faqs'] ?? null ) ? $stored['faqs'] : array() as $row ) {
			if ( is_array( $row ) && is_string( $row['id'] ?? null ) ) {
				$by_id[ $row['id'] ] = $row;
			}
		}
		foreach ( $clean['faqs'] as &$row ) {
			$row = array_replace( $by_id[ $row['id'] ] ?? array(), $row );
		}
		unset( $row );
		return array_replace( $stored, $clean, array( '_zc_policy_schema' => 1 ) );
	}
	/**
	 * Reject unsupported versions before any write.
	 *
	 * @param mixed $stored Original schema-bearing record.
	 */
	public static function supported( $stored ): bool {
		if ( ! is_array( $stored ) || ! array_key_exists( '_zc_policy_schema', $stored ) ) {
			return true;
		}
		return is_int( $stored['_zc_policy_schema'] ) && $stored['_zc_policy_schema'] >= 0 && $stored['_zc_policy_schema'] <= 1;
	}
	/**
	 * Persist submitted product fields only after capability and nonce verification.
	 *
	 * @param int $id Product ID.
	 */
	public static function save_product( int $id ): void {
		if ( $id < 1 || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$nonce = isset( $_POST['zc_product_policies_nonce'] ) && is_string( $_POST['zc_product_policies_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zc_product_policies_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'zc_product_policies_' . $id ) ) {
			return;
		}
		$stored = get_post_meta( $id, '_zc_product_policies', true );
		if ( ! self::supported( $stored ) ) {
			do_action( 'zarincode_policy_write_rejected', $id, 'unsupported_schema' );
			return;}
		if ( isset( $_POST['zc_product_changelog'] ) && is_string( $_POST['zc_product_changelog'] ) ) {
			update_post_meta( $id, '_zc_product_changelog', wp_kses_post( wp_unslash( $_POST['zc_product_changelog'] ) ) );
		}
		if ( isset( $_POST['zc_policy_mode'] ) && is_string( $_POST['zc_policy_mode'] ) ) {
			$mode = sanitize_key( wp_unslash( $_POST['zc_policy_mode'] ) );
			if ( in_array( $mode, array( 'inherit', 'custom', 'hide' ), true ) ) {
				update_post_meta( $id, '_zc_policy_mode', $mode );
			}
		}
		if ( isset( $_POST['zc_policy'] ) && is_array( $_POST['zc_policy'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- merge sanitizes all submitted fields; preserved unknown fields come only from the stored record.
			update_post_meta( $id, '_zc_product_policies', self::merge( $stored, wp_unslash( $_POST['zc_policy'] ) ) );
		}
	}
}
