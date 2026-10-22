<?php
/**
 * Product metadata editor remains available independently of the theme.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Admin;

use Zarincode\Runtime260\Content\Product_Policy_Store;
defined( 'ABSPATH' ) || exit;
/** Owns the administrative editing surface for durable product policy data. */
final class Product_Policy_Admin {
	/** Register the independent metadata editor without adding a new top-level menu. */
	public static function boot(): void {
		add_action(
			'add_meta_boxes_product',
			static function () {
				add_meta_box( 'zc-product-policies', __( 'ZarinCode — Product policies and FAQ', 'zarincode-core' ), array( self::class, 'box' ), 'product', 'normal', 'default' );
			}
		);
		add_action( 'save_post_product', array( Product_Policy_Store::class, 'save_product' ) );
		add_action( 'admin_init', array( self::class, 'save_shared' ) );
		add_action(
			'admin_enqueue_scripts',
			static function ( string $hook ) {
				$screen = get_current_screen();
				if ( ( $screen && 'product' === $screen->post_type ) || str_contains( $hook, 'zarincode-product-policies' ) || str_contains( $hook, 'zarincode-theme-options' ) ) {
					$version = defined( 'ZARINCODE_CORE_VERSION' ) ? constant( 'ZARINCODE_CORE_VERSION' ) : false;
					wp_enqueue_script( 'zc-policy-manager', plugins_url( 'assets/admin/policy-manager.js', dirname( __DIR__, 2 ) . '/zarincode-core.php' ), array(), is_string( $version ) ? $version : false, true );
				}
			}
		);
		add_action( 'zarincode_policy_write_rejected', array( self::class, 'rejected' ), 10, 2 );
		add_action(
			'admin_notices',
			static function () {
				$key = 'zc_policy_error_' . get_current_user_id();
				if ( get_transient( $key ) ) {
					delete_transient( $key );
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Saved content uses a newer schema. No data was changed. Use a compatible release.', 'zarincode-core' ) . '</p></div>';}
			}
		);
	}
	/**
	 * Queue a private administrator notice for an unsupported record.
	 *
	 * @param int    $id Target record ID, zero for shared options.
	 * @param string $reason Machine-readable rejection reason.
	 */
	public static function rejected( int $id, string $reason ): void {
		set_transient(
			'zc_policy_error_' . get_current_user_id(),
			array(
				'id'     => $id,
				'reason' => $reason,
			),
			60
		);
	}
	/** Save shared policy fields only after capability and nonce verification. */
	public static function save_shared(): void {
		if ( ! isset( $_POST['zc_policy_save'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_shared_policy' );
		$stored = get_option( 'zarincode_product_policies', array() );
		if ( ! Product_Policy_Store::supported( $stored ) ) {
			self::rejected( 0, 'unsupported_schema' );
			return;}
		if ( isset( $_POST['zc_policy'] ) && is_array( $_POST['zc_policy'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The store validates and sanitizes every submitted policy field before merging.
			$next = Product_Policy_Store::merge( $stored, wp_unslash( $_POST['zc_policy'] ) );
			if ( update_option( 'zarincode_product_policies', $next, false ) || get_option( 'zarincode_product_policies' ) === $next ) {
				do_action( 'zarincode_product_policies_saved' );}
		}
	}
	/**
	 * Render one escaped editable FAQ row.
	 *
	 * @param array<array-key,mixed> $row Existing row.
	 * @param int|string             $index Unique form index.
	 */
	public static function row( array $row, $index ): void {
		$name     = 'zc_policy[faqs][' . $index . ']';
		$id       = is_string( $row['id'] ?? null ) ? $row['id'] : wp_generate_uuid4();
		$question = is_string( $row['question'] ?? null ) ? $row['question'] : '';
		$answer   = is_string( $row['answer'] ?? null ) ? $row['answer'] : '';
		echo '<fieldset class="zc-policy-row" style="margin:12px 0;padding:12px;border:1px solid #ccd0d4"><input type="hidden" name="' . esc_attr( $name ) . '[id]" value="' . esc_attr( $id ) . '"><p><label>پرسش <input class="widefat" name="' . esc_attr( $name ) . '[question]" value="' . esc_attr( $question ) . '"></label></p><p><label>پاسخ <textarea class="widefat" rows="3" name="' . esc_attr( $name ) . '[answer]">' . esc_textarea( $answer ) . '</textarea></label></p><button class="button zc-policy-up" type="button">↑ جابه‌جایی بالا</button> <button class="button zc-policy-remove" type="button">حذف پرسش</button></fieldset>';
	}
	/**
	 * Render escaped, normalized policy controls.
	 *
	 * @param array<array-key,mixed> $data Existing policy fields.
	 */
	public static function fields( array $data ): void {
		$data = Product_Policy_Store::sanitize( $data );
		echo '<div class="zc-policy-editor"><p><label>توضیح گارانتی و پشتیبانی <textarea class="widefat" rows="4" name="zc_policy[summary]">' . esc_textarea( $data['summary'] ) . '</textarea></label></p><p><label>پیوند سیاست کامل <input dir="ltr" class="widefat" name="zc_policy[url]" value="' . esc_attr( $data['url'] ) . '"></label></p><input type="hidden" name="zc_policy[faqs]" value=""><div class="zc-policy-list">';
		foreach ( $data['faqs'] as $i => $row ) {
			self::row( $row, $i );
		}echo '</div><template class="zc-policy-template">';
		self::row( array(), '__INDEX__' );
		echo '</template><button class="button zc-policy-add" type="button">افزودن پرسش</button></div>';
	}
	/**
	 * Render the native product metabox.
	 *
	 * @param \WP_Post $post Product post.
	 */
	public static function box( \WP_Post $post ): void {
		wp_nonce_field( 'zc_product_policies_' . $post->ID, 'zc_product_policies_nonce' );
		$raw_mode      = get_post_meta( $post->ID, '_zc_policy_mode', true );
		$mode          = is_string( $raw_mode ) && in_array( $raw_mode, array( 'inherit', 'custom', 'hide' ), true ) ? $raw_mode : 'inherit';
		$raw_changelog = get_post_meta( $post->ID, '_zc_product_changelog', true );
		$changelog     = is_string( $raw_changelog ) ? $raw_changelog : '';
		echo '<p><label>محتوا <select name="zc_policy_mode">';
		foreach ( array(
			'inherit' => 'پیش‌فرض مشترک فروشگاه',
			'custom'  => 'محتوای اختصاصی این محصول',
			'hide'    => 'پنهان در این محصول',
		) as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $mode, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}echo '</select></label></p><p>پیش‌فرض مشترک از نمایش ← گارانتی و پرسش‌های محصول مدیریت می‌شود. تغییر حالت، محتوای اختصاصی قبلی را حذف نمی‌کند.</p>';
		self::fields( Product_Policy_Store::sanitize( get_post_meta( $post->ID, '_zc_product_policies', true ) ) );
		echo '<p><label>تاریخچه تغییرات (خالی = پنهان)<textarea class="widefat" rows="5" name="zc_product_changelog">' . esc_textarea( $changelog ) . '</textarea></label></p>';}
	/** Display the shared policy tool in the existing settings hub. */
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( did_action( 'zarincode_product_policies_saved' ) ) {
			echo '<div class="notice notice-success" role="status"><p>' . esc_html__( 'Policy settings saved.', 'zarincode-core' ) . '</p></div>';
		}
		echo '<div class="wrap"><h1>گارانتی و پرسش‌های مشترک محصولات</h1><p>این متن فقط روی محصولات در حالت «پیش‌فرض مشترک» نمایش داده می‌شود. سیاست واقعی خود را درج کنید؛ ضمانت یا مدت پشتیبانی ساختگی تولید نمی‌شود.</p><form method="post">';
		wp_nonce_field( 'zc_shared_policy' );
		self::fields( Product_Policy_Store::sanitize( get_option( 'zarincode_product_policies', Product_Policy_Store::defaults() ) ) );
		submit_button( 'ذخیره سیاست مشترک', 'primary', 'zc_policy_save' );
		echo '</form></div>';}
}
