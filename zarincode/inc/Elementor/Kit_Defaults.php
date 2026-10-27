<?php
/**
 * Apply accessible factory palette values to the active Elementor kit.
 *
 * Elementor's stock global colors fail WCAG when used as backgrounds behind
 * white text (for example accent #61CE70 renders 1.98:1). Only entries that
 * still hold the exact factory value are migrated, once per kit.
 *
 * Persistence uses Elementor's own page-settings meta storage
 * (`_elementor_page_settings` via Document::get_meta()/update_meta()). The
 * higher-level Document::update_settings() pipeline is intentionally NOT
 * used: it funnels into ajax_before_save_settings(), which throws
 * "Access denied" for every non-CLI request without edit_post capability on
 * the kit — a fatal on public page views after activation.
 *
 * @package Zarincode
 */

namespace Zarincode\Theme\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * One-time, idempotent Elementor kit default-colour migration.
 */
final class Kit_Defaults {
	private const FLAG = 'zc_elementor_kit_defaults_kit_id';

	/**
	 * Accessible replacements with luminance low enough for white text on
	 * backgrounds and for dark text on white.
	 *
	 * @var array<string,string>
	 */
	private const MAP = array(
		'accent'  => '#202227',
		'primary' => '#1F6B8F',
		'text'    => '#5F6368',
	);

	/**
	 * Exact Elementor factory values being replaced.
	 *
	 * @var array<string,string>
	 */
	private const FACTORY = array(
		'accent'  => '#61CE70',
		'primary' => '#6EC1E4',
		'text'    => '#7A7A7A',
	);

	/**
	 * Hook the migration after Elementor has registered the kit manager.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'elementor/init', array( self::class, 'apply' ) );
	}

	/**
	 * Pure factory-only colour migration decision (unit-testable).
	 *
	 * @param array<array-key,mixed> $colors Current system_colors rows.
	 * @return array{0:array<array-key,mixed>,1:bool} Colors to store and whether they changed.
	 */
	public static function migrate_colors( array $colors ): array {
		$changed = false;
		foreach ( $colors as $index => $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['_id'] ) || ! is_string( $entry['_id'] ) || ! isset( self::MAP[ $entry['_id'] ] ) ) {
				continue;
			}
			$row     = $entry;
			$current = is_string( $row['color'] ?? null ) ? strtoupper( (string) $row['color'] ) : '';
			if ( strtoupper( self::FACTORY[ $entry['_id'] ] ) === $current ) {
				$row['color']     = self::MAP[ $entry['_id'] ];
				$colors[ $index ] = $row;
				$changed          = true;
			}
		}
		return array( $colors, $changed );
	}

	/**
	 * Migrate still-default kit colours, then remember the processed kit ID.
	 *
	 * Never throws: a palette default must not be able to take a site down.
	 *
	 * @return void
	 */
	public static function apply(): void {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! class_exists( '\\Elementor\\Core\\Settings\\Page\\Manager' ) ) {
			return;
		}
		$active = get_option( 'elementor_active_kit' );
		$kit_id = is_numeric( $active ) ? (int) $active : 0;
		$flag   = get_option( self::FLAG );
		if ( $kit_id <= 0 || ( is_numeric( $flag ) && (int) $flag === $kit_id ) ) {
			return;
		}
		try {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
			// get_active_kit() resolves the kit document and may fail while
			// Elementor is mid-setup; every method below is base-Document API.
			if ( ! $kit instanceof \Elementor\Core\Base\Document ) {
				return;
			}
			$meta_key = \Elementor\Core\Settings\Page\Manager::META_KEY;
			$settings = $kit->get_meta( $meta_key );
			$settings = is_array( $settings ) ? $settings : array();
			// Fresh kits may have no stored system_colors at all; the factory
			// values then come from Elementor's control defaults (read-only,
			// capability-free). Prefer stored rows whenever they exist so an
			// author's customisation always wins over any migration.
			$colors = is_array( $settings['system_colors'] ?? null ) ? $settings['system_colors'] : null;
			if ( null === $colors ) {
				$merged = $kit->get_settings();
				$merged = is_array( $merged ) && is_array( $merged['system_colors'] ?? null ) ? $merged['system_colors'] : array();
				$colors = $merged;
			}
			list( $colors, $changed ) = self::migrate_colors( $colors );
			if ( ! $changed ) {
				update_option( self::FLAG, $kit_id, false );
				return;
			}
			$settings['system_colors'] = $colors;
			// Same storage layer Elementor's page-settings manager writes to
			// (save_settings_to_db()), without its authenticated-AJAX guard.
			$written = $kit->update_meta( $meta_key, wp_slash( $settings ) );
			if ( false === $written || '' === $written ) {
				return; // Retry on a later request; never break the current one.
			}
			$kit->refresh_post();
			if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
			update_option( self::FLAG, $kit_id, false );
		} catch ( \Throwable $e ) {
			// A default-palette migration must never fatal public requests.
			return;
		}
	}
}
