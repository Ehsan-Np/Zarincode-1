<?php
/**
 * Elementor editor performance — applies official best-practice experiments.
 *
 * Research (developers.elementor.com):
 * - "Optimized Control Loading" (default inactive) shrinks what the editor panel
 *   receives per widget → biggest lever for widget-heavy sites.
 * - "Improved Asset Loading" defers non-critical CSS/JS to interaction.
 * - "Optimized DOM Output" (Optimized Markup) lightens the preview document.
 * - "Inline Font Icons" removes a render-blocking font request.
 *
 * Only features that exist in the installed Elementor version are touched;
 * unknown keys are ignored, so this stays safe across upgrades.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Elementor;

defined( 'ABSPATH' ) || exit;

final class Editor_Performance {

	/** feature slug => desired default state */
	private const WANTED = array(
		'e_optimized_markup'           => 'active', // Optimized DOM Output
		'e_optimized_assets_loading'   => 'active', // Improved Asset Loading
		'e_optimized_control_loading'  => 'active', // Optimized Control Loading (editor panel)
		'inline_font_icons'            => 'active', // Inline Font Icons
		'e_css_optimized'              => 'active', // Optimized CSS loading
	);

	public static function boot(): void {
		add_filter( 'elementor/experiments/feature-states/default-feature-state', array( __CLASS__, 'feature_state' ), 10, 2 );
	}

	/**
	 * @param string           $state   Default state Elementor computed.
	 * @param array|object     $feature Feature definition (array in older versions, object in newer).
	 */
	public static function feature_state( $state, $feature ) {
		$name = is_object( $feature ) ? ( $feature->name ?? '' ) : ( $feature['name'] ?? '' );
		if ( isset( self::WANTED[ $name ] ) ) {
			return self::WANTED[ $name ];
		}
		return $state;
	}

	/** Human-readable status for the admin guidance box. */
	public static function report(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return array();
		}
		$exp = \Elementor\Plugin::$instance->experiments ?? null;
		if ( ! $exp || ! method_exists( $exp, 'get_features' ) ) {
			return array();
		}
		$rows = array();
		foreach ( $exp->get_features() as $slug => $feature ) {
			if ( ! isset( self::WANTED[ $slug ] ) ) {
				continue;
			}
			$state = is_object( $feature ) ? ( $feature->state ?? '' ) : ( $feature['state'] ?? '' );
			$rows[] = array(
				'slug'  => (string) $slug,
				'label' => (string) ( ( is_object( $feature ) ? ( $feature->title ?? '' ) : ( $feature['title'] ?? '' ) ) ?: $slug ),
				'active' => 'active' === $state,
			);
		}
		return $rows;
	}
}
