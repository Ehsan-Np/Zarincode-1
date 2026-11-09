<?php
/**
 * Content defaults for the homepage widgets (v4.7.0).
 *
 * The demo importer writes `_elementor_data` for the homepage. Seeding the widgets
 * with their *content* defaults means the Elementor panel opens with the real copy
 * in every field instead of a blank panel that only looks populated on the front
 * end — the "why is my widget editing empty?" problem.
 *
 * Only content-tab controls are exported. Style controls (colors, typography,
 * spacing…) stay empty so the design keeps coming from the stylesheet until the
 * user overrides something.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

defined( 'ABSPATH' ) || exit;

final class Home_Defaults {

	/** Control types that carry editable content (strings, links, media, rows). */
	private const CONTENT_TYPES = array(
		'text',
		'textarea',
		'wysiwyg',
		'url',
		'media',
		'number',
		'select',
		'select2',
		'switcher',
		'repeater',
	);

	/** Widget names of the homepage sections, in display order. */
	public static function widgets(): array {
		return array(
			'zarincode_home_hero',
			'zarincode_home_partners',
			'zarincode_home_services',
			'zarincode_home_products',
			'zarincode_home_portfolio',
			'zarincode_home_why',
			'zarincode_home_steps',
			'zarincode_home_pricing',
			'zarincode_home_testimonials',
			'zarincode_home_blog',
			'zarincode_home_promo',
		);
	}

	/**
	 * Content settings for one widget, ready to be stored in `_elementor_data`.
	 *
	 * @param string $widget Widget name, e.g. zarincode_home_hero.
	 */
	public static function settings( string $widget ): array {
		$instance = self::instance( $widget );
		if ( ! $instance ) {
			return array();
		}

		// get_controls() runs register_controls(); the content section ids are only
		// known afterwards, so read them once the control stack exists.
		$controls         = $instance->get_controls();
		$content_sections = $instance instanceof Abstract_Home_Widget
			? array_flip( $instance->get_content_section_ids() )
			: array();
		$inside_content   = false;

		$settings = array();
		foreach ( $controls as $id => $control ) {
			$type = (string) ( $control['type'] ?? '' );

			// Section markers switch the scope: only content sections are exported.
			if ( in_array( $type, array( 'section', 'tab', 'tabs' ), true ) ) {
				$inside_content = isset( $content_sections[ $id ] );
				continue;
			}

			if ( ! $inside_content || ! in_array( $type, self::CONTENT_TYPES, true ) || ! array_key_exists( 'default', $control ) ) {
				continue;
			}

			$default = $control['default'];
			if ( 'repeater' === $type && is_array( $default ) && class_exists( Repeater_Identity::class ) ) {
				$default = Repeater_Identity::rows( $default, $widget . '.' . $id, (array) ( $control['fields'] ?? array() ) );
			}
			$settings[ $id ] = $default;
		}

		return $settings;
	}

	/** Content settings for every homepage widget, keyed by widget name. */
	public static function all(): array {
		$out = array();
		foreach ( self::widgets() as $widget ) {
			$out[ $widget ] = self::settings( $widget );
		}
		return $out;
	}

	/**
	 * Fills in the homepage widgets that were imported before 4.7.0.
	 *
	 * The 4.6.0 importer wrote `settings: []` for every homepage widget, so an
	 * updated site keeps an Elementor panel full of empty fields even though the
	 * front end looks complete. This runs once per plugin version and only
	 * touches widgets whose settings are still completely empty — anything the
	 * user has edited is left alone.
	 *
	 * @return int Number of widgets that received their defaults.
	 */
	public static function backfill_homepage(): int {
		$front = (int) get_option( 'page_on_front' );
		if ( ! $front ) {
			return 0;
		}

		$raw = get_post_meta( $front, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return 0;
		}

		$tree = json_decode( $raw, true );
		if ( ! is_array( $tree ) ) {
			return 0;
		}

		$filled  = 0;
		$changed = false;

		$walk = static function ( array &$elements ) use ( &$walk, &$filled, &$changed ) {
			foreach ( $elements as &$element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}
				$type = (string) ( $element['widgetType'] ?? '' );
				if ( in_array( $type, self::widgets(), true ) ) {
					$settings = $element['settings'] ?? array();
					if ( is_array( $settings ) && array() === $settings ) {
						$defaults = self::settings( $type );
						if ( $defaults ) {
							$element['settings'] = $defaults;
							$filled++;
							$changed = true;
						}
					}
				}
				if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
					$walk( $element['elements'] );
				}
			}
			unset( $element );
		};

		$walk( $tree );

		if ( $changed ) {
			update_post_meta( $front, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::instance()->files_manager ) ) {
				\Elementor\Plugin::instance()->files_manager->clear_cache();
			}
		}

		return $filled;
	}

	/** Resolves a registered widget instance (Elementor must be loaded). */
	private static function instance( string $widget ): ?\Elementor\Widget_Base {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return null;
		}
		$manager = \Elementor\Plugin::instance()->widgets_manager;
		if ( ! $manager ) {
			return null;
		}
		$instance = $manager->get_widget_types( $widget );
		return $instance instanceof \Elementor\Widget_Base ? $instance : null;
	}
}
