<?php
/**
 * Zarincode Theme Functions and Bootstrapping (OOP + PSR-4 Architecture)
 *
 * Includes an embedded, version-isolated fallback runtime for Zarincode\Core
 * (CPTs, 14 Fully Customizable Elementor Widgets, Header/Footer Builder, and
 * 1-Click + Automatic Demo Importer) so that installing or updating zarincode.zip
 * loads the matching core engine without rewriting an installed plugin.
 *
 * @package    Zarincode
 * @author     Ehsan NaderiPanah <info@zarincode.com>
 * @copyright  2026 Zarincode (https://zarincode.com)
 * @license    GPL-2.0-or-later
 * @version    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ZARINCODE_VERSION', '2.6.0' );
define( 'ZARINCODE_DIR', get_template_directory() );
define( 'ZARINCODE_URI', get_template_directory_uri() );
define( 'ZARINCODE_DEV_NAME', 'احسان نادری پناه (Ehsan NaderiPanah)' );
define( 'ZARINCODE_DEV_PHONE', '09024561001' );
define( 'ZARINCODE_DEV_SITE', 'https://zarincode.com' );

/** Disable callbacks from the legacy core without modifying third-party/plugin files.
 * PHP cannot replace a class already loaded by an old active plugin. The new
 * runtime uses its own namespace and removes ONLY callbacks owned by the old one.
 */
function zarincode_detach_legacy_core(): void {
    global $wp_filter;
    foreach ( $wp_filter as $hook => $object ) {
        if ( ! $object instanceof WP_Hook ) { continue; }
        foreach ( $object->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                $fn = $callback['function'];
                $owner = is_array( $fn ) ? ( is_object( $fn[0] ) ? get_class( $fn[0] ) : $fn[0] ) : ( is_object( $fn ) ? get_class( $fn ) : $fn );
                if ( is_string( $owner ) && strpos( ltrim( $owner, '\\' ), 'Zarincode\\Core\\' ) === 0 ) {
                    remove_filter( $hook, $fn, $priority );
                }
            }
        }
    }
}
zarincode_detach_legacy_core();

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

    $core_prefix = 'Zarincode\\Runtime260\\';
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
    if ( class_exists( '\Zarincode\Runtime260\PostTypes\CPT_Manager' ) ) {
        \Zarincode\Runtime260\PostTypes\CPT_Manager::instance();
    }
    if ( class_exists( '\Zarincode\Runtime260\Widgets\Renderer' ) ) {
        \Zarincode\Runtime260\Widgets\Renderer::instance();
    }
    if ( class_exists( '\Zarincode\Runtime260\Widgets\Elementor_Loader' ) ) {
        \Zarincode\Runtime260\Widgets\Elementor_Loader::instance();
    }
    if ( class_exists( '\Zarincode\Runtime260\Demo\Demo_Seeder' ) ) {
        \Zarincode\Runtime260\Demo\Demo_Seeder::instance();
    }
}, 5 );

/** Use available admin content width; retain the native WP sidebar and toolbar. */
add_action( 'admin_head', function () {
    $screen = get_current_screen();
    if ( ! $screen || ! preg_match( '/zarincode-(theme-options|demo-importer|install-plugins)/', $screen->id ) ) { return; }
    echo '<style id="zc-admin-edge-to-edge">
    #wpcontent{padding-left:0!important;padding-right:0!important}
    #wpbody,#wpbody-content{width:100%!important;max-width:none!important;box-sizing:border-box}
    #wpbody-content>.wrap,.zc-options-fullwidth-wrap,.zc-demo-fullwidth-wrap,.zc-plugins-fullwidth-wrap{width:100%!important;max-width:none!important;margin:0!important;box-sizing:border-box!important}
    #wpbody-content>.wrap{padding:20px!important}
    .redux-container,.redux-main{max-width:none!important;box-sizing:border-box}
    .redux-container{width:100%!important;margin:0!important}
    @media(max-width:782px){#wpbody-content>.wrap{padding:12px!important}.zc-demo-main-grid{grid-template-columns:minmax(0,1fr)!important}}
    </style>';
}, 999 );

/** Render the active Elementor header/footer template; empty output keeps a safe fallback. */
function zarincode_builder_part( string $type ): string {
    if ( ! in_array( $type, [ 'header', 'footer' ], true ) || 'elementor_hf' !== zarincode_get_option( $type . '_source', 'elementor_hf' ) || ! class_exists( '\Elementor\Plugin' ) ) { return ''; }
    static $rendering = false;
    if ( $rendering ) { return ''; }
    $ids = get_posts( [ 'post_type' => 'zc_hf_template', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => [ [ 'key' => '_zc_hf_type', 'value' => $type ], [ 'key' => '_zc_hf_active', 'value' => 'yes' ] ] ] );
    if ( ! $ids ) { return ''; }
    $rendering = true;
    try {
        return (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( (int) $ids[0], true );
    } finally {
        $rendering = false;
    }
}
