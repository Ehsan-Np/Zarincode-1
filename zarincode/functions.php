<?php
/**
 * Zarincode Theme Functions and Bootstrapping (OOP + PSR-4 Architecture)
 *
 * Includes embedded fallback for Zarincode\Core (CPTs, 14 Elementor Widgets,
 * Header/Footer Builder, and 1-Click + Automatic Demo Importer) so that
 * installing zarincode.zip works 100% standalone out-of-the-box.
 *
 * @package    Zarincode
 * @author     Ehsan NaderiPanah <info@zarincode.com>
 * @copyright  2026 Zarincode (https://zarincode.com)
 * @license    GPL-2.0-or-later
 * @version    2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ZARINCODE_VERSION', '2.5.0' );
define( 'ZARINCODE_DIR', get_template_directory() );
define( 'ZARINCODE_URI', get_template_directory_uri() );
define( 'ZARINCODE_DEV_NAME', 'احسان نادری پناه (Ehsan NaderiPanah)' );
define( 'ZARINCODE_DEV_PHONE', '09024561001' );
define( 'ZARINCODE_DEV_SITE', 'https://zarincode.com' );

/**
 * PSR-4 Autoloader for Zarincode\Theme and Embedded Zarincode\Core namespaces
 */
spl_autoload_register( function ( $class ) {
    $theme_prefix = 'Zarincode\\Theme\\';
    if ( strncmp( $theme_prefix, $class, strlen( $theme_prefix ) ) === 0 ) {
        $relative_class = substr( $class, strlen( $theme_prefix ) );
        $file           = ZARINCODE_DIR . '/inc/' . str_replace( '\\', '/', $relative_class ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
        return;
    }

    $core_prefix = 'Zarincode\\Core\\';
    if ( strncmp( $core_prefix, $class, strlen( $core_prefix ) ) === 0 ) {
        $relative_class = substr( $class, strlen( $core_prefix ) );
        $file           = ZARINCODE_DIR . '/inc/EmbeddedCore/' . str_replace( '\\', '/', $relative_class ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

/**
 * Helper to fetch Redux Framework option safely with default fallback
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function zarincode_get_option( string $key, $default = '' ) {
    global $zarincode_opt;
    if ( empty( $zarincode_opt ) ) {
        $zarincode_opt = get_option( 'zarincode_opt', [] );
    }
    return isset( $zarincode_opt[ $key ] ) && '' !== $zarincode_opt[ $key ] ? $zarincode_opt[ $key ] : $default;
}

/**
 * Convert Latin digits to Persian digits when RTL/Farsi digit option is enabled
 *
 * @param string|int $input Text or number.
 * @return string
 */
function zarincode_fa_digits( $input ): string {
    if ( ! zarincode_get_option( 'persian_digits', true ) ) {
        return (string) $input;
    }
    $en = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
    $fa = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
    return str_replace( $en, $fa, (string) $input );
}

// Initialize Theme Core Classes, Plugin Installer & Advanced Settings Panel
\Zarincode\Theme\Core\Setup::instance();
\Zarincode\Theme\Core\Assets::instance();
\Zarincode\Theme\Plugins\Plugin_Manager::instance();
\Zarincode\Theme\Redux\OptionsConfig::instance();

// Initialize Embedded Core Engine (CPTs, 14 Elementor Widgets, Header/Footer & Demo Importer)
// Works seamlessly whether zarincode-core plugin is active or theme is installed standalone.
add_action( 'after_setup_theme', function () {
    if ( class_exists( '\Zarincode\Core\PostTypes\CPT_Manager' ) ) {
        \Zarincode\Core\PostTypes\CPT_Manager::instance();
    }
    if ( class_exists( '\Zarincode\Core\Widgets\Elementor_Loader' ) ) {
        \Zarincode\Core\Widgets\Elementor_Loader::instance();
    }
    if ( class_exists( '\Zarincode\Core\Widgets\Renderer' ) ) {
        \Zarincode\Core\Widgets\Renderer::instance();
    }
    if ( class_exists( '\Zarincode\Core\Demo\Demo_Seeder' ) ) {
        \Zarincode\Core\Demo\Demo_Seeder::instance();
    }
}, 20 );
