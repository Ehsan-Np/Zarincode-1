<?php
/**
 * Apply accessible factory palette values to the active Elementor kit.
 *
 * Elementor's stock global colors fail WCAG when used as backgrounds behind
 * white text (for example accent #61CE70 renders 1.98:1). Only entries that
 * still hold the exact factory value are migrated, once per kit, through the
 * official kit document API; any author customisation is left untouched.
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

	/** @var array<string,string> Accessible replacements with luminance low enough for white text and for use on white. */
	private const MAP = array(
		'accent'   => '#202227',
		'primary'  => '#1F6B8F',
		'text'     => '#5F6368',
	);

	/** @var array<string,string> Exact Elementor factory values being replaced. */
	private const FACTORY = array(
		'accent'   => '#61CE70',
		'primary'  => '#6EC1E4',
		'text'     => '#7A7A7A',
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
	 * Migrate still-default kit colours, then remember the processed kit ID.
	 *
	 * @return void
	 */
	public static function apply(): void {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return;
		}
		$kit_id = (int) get_option( 'elementor_active_kit' );
		if ( $kit_id <= 0 || get_option( self::FLAG ) === $kit_id ) {
			return;
		}
		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! is_object( $kit ) || ! method_exists( $kit, 'update_settings' ) || ! method_exists( $kit, 'get_settings' ) ) {
			return;
		}
		$settings = $kit->get_settings();
		$colors   = is_array( $settings['system_colors'] ?? null ) ? $settings['system_colors'] : array();
		$changed  = false;
		foreach ( $colors as $index => $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['_id'] ) || ! isset( self::MAP[ $entry['_id'] ] ) ) {
				continue;
			}
			$current = is_string( $entry['color'] ?? null ) ? strtoupper( $entry['color'] ) : '';
			if ( $current === strtoupper( self::FACTORY[ $entry['_id'] ] ) ) {
				$colors[ $index ]['color'] = self::MAP[ $entry['_id'] ];
				$changed                   = true;
			}
		}
		if ( $changed ) {
			$kit->update_settings( array( 'system_colors' => $colors ) );
		}
		update_option( self::FLAG, $kit_id, false );
	}
}
