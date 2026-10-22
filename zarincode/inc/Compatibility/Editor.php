<?php
namespace Zarincode\Theme\Compatibility;
defined( 'ABSPATH' ) || exit;
/** Public editor APIs only; no editor chrome or private Elementor template overrides. */
final class Editor {
    public static function boot(): void {
        add_action( 'after_setup_theme', [ self::class, 'setup' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ], 25 );
        add_action( 'elementor/theme/register_locations', static function ( $manager ) {
            $manager->register_all_core_location();
        } );
    }
    public static function setup(): void {
        add_theme_support( 'editor-styles' );
        add_theme_support( 'wp-block-styles' );
        add_editor_style( [ 'assets/css/vazirmatn.css', 'assets/css/blocks/editor.css' ] );
    }
    public static function assets(): void {
        if ( is_singular() && has_blocks( get_queried_object_id() ) ) {
            wp_enqueue_style( 'zarincode-blocks', get_template_directory_uri() . '/assets/css/blocks/content.css', [], ZARINCODE_VERSION );
        }
    }
}
