<?php
/**
 * Elementor editor performance — applies official best-practice experiments.
 *
 * Research (developers.elementor.com + elementor source):
 * - "Optimized Control Loading" (4.x) shrinks what the editor panel receives
 *   per widget → biggest lever for widget-heavy sites.
 * - "Improved Asset Loading" (4.x) defers non-critical CSS/JS to interaction.
 * - "Optimized Markup" (Optimized DOM Output, 3.x+) lightens the preview DOM.
 * - "Inline Font Icons" (`e_font_icon_svg` in 3.x) removes a font request.
 *
 * Elementor stores each experiment's state in an option named
 * `elementor_experiment-{slug}` (Experiments_Manager::get_feature_option_key).
 * We only seed the default for features the admin has not decided yet, so an
 * explicit choice in Elementor → Experiments always wins. Unknown keys are
 * ignored, which keeps this safe across Elementor upgrades.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Elementor;

defined( 'ABSPATH' ) || exit;

final class Editor_Performance {

	/** feature slug => desired state ('active' = Experiments_Manager::STATE_ACTIVE) */
	private const WANTED = array(
		'e_optimized_markup'           => 'active', // Optimized Markup / DOM Output (3.x+)
		'e_font_icon_svg'              => 'active', // Inline Font Icons (3.x slug)
		'inline_font_icons'            => 'active', // Inline Font Icons (newer slug, if present)
		'e_optimized_assets_loading'   => 'active', // Improved Asset Loading (4.x)
		'e_optimized_control_loading'  => 'active', // Optimized Control Loading (4.x, editor panel)
		'e_css_optimized'              => 'active', // Optimized CSS loading (if present)
	);

	public static function boot(): void {
		// Experiments are registered during elementor/init; seed defaults right after.
		add_action( 'elementor/init', array( __CLASS__, 'apply' ), 20 );
	}

	/** Seed wanted experiment states once (admin's explicit choice always wins). */
	public static function apply(): void {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		$exp = \Elementor\Plugin::$instance->experiments ?? null;
		if ( ! $exp || ! method_exists( $exp, 'get_features' ) ) {
			return;
		}
		$features = $exp->get_features();
		foreach ( self::WANTED as $slug => $state ) {
			if ( ! isset( $features[ $slug ] ) || ! method_exists( $exp, 'get_feature_option_key' ) ) {
				continue;
			}
			$key = $exp->get_feature_option_key( $slug );
			if ( false === get_option( $key, false ) ) {
				update_option( $key, $state, false );
			}
		}
	}

	/** Human-readable status for the admin guidance box (rocket page). */
	public static function report(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return array();
		}
		// Plugin::$instance may still be null on some admin screens; boot it so
		// experiments metadata exists when this page renders.
		$exp = \Elementor\Plugin::instance()->experiments ?? null;
		if ( ! $exp || ! method_exists( $exp, 'get_features' ) ) {
			return array();
		}
		$rows = array();
		foreach ( $exp->get_features() as $slug => $feature ) {
			if ( ! isset( self::WANTED[ $slug ] ) ) {
				continue;
			}
			// Features are arrays in 3.x and objects in some 4.x builds.
			if ( is_array( $feature ) ) {
				$state  = $feature['state'] ?? '';
				$label  = $feature['title'] ?? $slug;
			} else {
				$state  = $feature->state ?? '';
				$label  = $feature->title ?? $slug;
			}
			$rows[] = array(
				'slug'   => (string) $slug,
				'label'  => (string) ( $label ?: $slug ),
				'active' => 'active' === $state,
			);
		}
		return $rows;
	}
}
