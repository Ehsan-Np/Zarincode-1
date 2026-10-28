<?php
/** Theme presentation bootstrap and backwards-compatible public helper functions. */
defined( 'ABSPATH' ) || exit;
define( 'ZARINCODE_VERSION', '4.0.0-rc.4' );
define( 'ZARINCODE_DIR', get_template_directory() );
define( 'ZARINCODE_URI', get_template_directory_uri() );
define( 'ZARINCODE_DEV_NAME', 'احسان نادری پناه (Ehsan NaderiPanah)' );
define( 'ZARINCODE_DEV_PHONE', '09024561001' );
define( 'ZARINCODE_DEV_SITE', 'https://zarincode.com' );

/** Theme namespace only: functional classes are owned and loaded by the companion. */
spl_autoload_register( static function ( $class ) {
    $prefix = 'Zarincode\\Theme\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) { return; }
    $file = ZARINCODE_DIR . '/inc/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
    if ( is_file( $file ) ) { require_once $file; }
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
    if ( ! is_rtl() || ! zarincode_get_option( 'persian_digits', true ) ) {
        return (string) $input;
    }
    $en = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
    $fa = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
    return str_replace( $en, $fa, (string) $input );
}

// Initialize Theme Core Classes, Plugin Installer & Advanced Settings Panel
\Zarincode\Theme\Elementor\Kit_Defaults::boot();
\Zarincode\Theme\Core\Setup::instance();
\Zarincode\Theme\Core\Assets::instance();
\Zarincode\Theme\Core\Single_Experience::boot();
\Zarincode\Theme\Core\Article_Toc::boot();
\Zarincode\Theme\Core\Article_Layout::boot();
\Zarincode\Theme\Core\Article_Ratings::boot();
\Zarincode\Theme\Core\Seo_Meta::boot();
\Zarincode\Theme\Core\Discussion::boot();
\Zarincode\Theme\Core\Account::boot();
\Zarincode\Theme\Core\Social_Manager::boot();
\Zarincode\Theme\Core\Product_Policies::boot();
\Zarincode\Theme\Plugins\Plugin_Manager::instance();
\Zarincode\Theme\Redux\OptionsConfig::instance();

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
    if ( 'original' === zarincode_get_option( 'demo_design', 'original' ) ) { return ''; }
    if ( ! in_array( $type, [ 'header', 'footer' ], true ) || 'elementor_hf' !== zarincode_get_option( $type . '_source', 'elementor_hf' ) || ! class_exists( '\Elementor\Plugin' ) ) { return ''; }
    static $rendering = false;
    if ( $rendering ) { return ''; }
    $ids = get_posts( [ 'post_type' => 'zc_hf_template', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters'=>false, 'meta_query' => [ [ 'key' => '_zc_hf_type', 'value' => $type ], [ 'key' => '_zc_hf_active', 'value' => 'yes' ] ] ] );
    if ( ! $ids ) { return ''; }
    $rendering = true;
    try {
        return (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( (int) $ids[0], true );
    } finally {
        $rendering = false;
    }
}

// Register early; load badge CSS only when a configured footer needs it.
add_action('wp_enqueue_scripts',static function(){
    if(!class_exists('\Zarincode\Runtime260\Widgets\Trust_Badges'))return;
    foreach(['footer_enamad_html','footer_samandehi_html','footer_zarinpal_html','footer_gateway_html'] as $key)if(zarincode_get_option($key,'')){
        \Zarincode\Runtime260\Widgets\Trust_Badges::register_style();wp_enqueue_style('zarincode-trust');break;
    }
},20);
/** For custom Elementor headers/footers: use the native Shortcode widget. */
add_shortcode('zarincode_social_links',static fn()=>\Zarincode\Theme\Core\Social_Links::profiles());

// Replace only the historical default monogram, not a custom customer logo.
add_action('after_setup_theme',static function(){
    $options=get_option('zarincode_opt',[]);if(is_array($options)&&($options['logo_monogram']??'')==='Z'){$options['logo_monogram']='ZC';update_option('zarincode_opt',$options);global $zarincode_opt;$zarincode_opt=$options;}
},30);

\Zarincode\Theme\Core\Admin_Hub::boot();

\Zarincode\Theme\Core\Settings_Security::boot();
\Zarincode\Theme\Compatibility\Editor::boot();

\Zarincode\Theme\Compatibility\Elementor_Styles::boot();
