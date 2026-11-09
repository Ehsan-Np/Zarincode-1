<?php
/** Theme presentation bootstrap and backwards-compatible public helper functions. */
defined( 'ABSPATH' ) || exit;
define( 'ZARINCODE_VERSION', '4.10.1' );
define( 'ZARINCODE_DIR', get_template_directory() );
define( 'ZARINCODE_URI', get_template_directory_uri() );
define( 'ZARINCODE_DEV_NAME', 'احسان نادری پناه (Ehsan NaderiPanah)' );
define( 'ZARINCODE_DEV_PHONE', '09024561001' );
define( 'ZARINCODE_DEV_SITE', 'https://zarincode.com' );

/*
 * Fallback error shield.
 *
 * The full shield lives in zarincode-core (Zarincode\Runtime260\Security\Hardening):
 * it also installs an error handler that works when the host pins
 * `display_errors On` in php.ini. If the companion plugin is disabled we still
 * must never let PHP print a warning — those messages carry the absolute
 * server path (e.g. /home/greenfis/public_html/...). See the «امنیت» panel.
 */
if ( ! class_exists( 'Zarincode\Runtime260\Security\Hardening' ) ) {
    $zc_host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
    if ( ! preg_match( '/(^|\\.)(localhost|local|test)(:|$)/', $zc_host ) && ! str_contains( $zc_host, '127.0.0.1' ) ) {
        @ini_set( 'display_errors', '0' );          // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
        @ini_set( 'display_startup_errors', '0' );  // phpcs:ignore WordPress.PHP.IniSet.Risky
        @ini_set( 'html_errors', '0' );             // phpcs:ignore WordPress.PHP.IniSet.Risky
    }
    unset( $zc_host );
}

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
\Zarincode\Theme\Home\Home_Cache::boot();
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

/**
 * Render the Elementor-built header/footer template.
 *
 * Design decision (4.9.2): the site owner asked for a header and a footer that
 * are 100% editable in Elementor, not a fixed coded look. The template CPT
 * (zc_hf_template) already exists; what stopped it from rendering was the
 * `demo_design === original` gate and the two `*_source` options defaulting to
 * the coded shell. Now:
 *
 *  - if a published, active template exists for the location it is used, and
 *  - the coded header/footer stays as the fallback so a fresh install (or a
 *    deleted template) never renders an empty page,
 *  - `zarincode_hf_template_id()` is the single lookup used by both the renderer
 *    and the admin bar shortcut, so «ویرایش با المنتور» always points at the
 *    template that is actually being displayed.
 *
 * @param string $type header|footer
 * @return string Rendered markup, or '' when the coded shell should be used.
 */
function zarincode_hf_template_id( string $type ): int {
    static $cache = array();
    if ( isset( $cache[ $type ] ) ) {
        return $cache[ $type ];
    }
    $cache[ $type ] = 0;
    if ( ! in_array( $type, array( 'header', 'footer' ), true ) || ! post_type_exists( 'zc_hf_template' ) ) {
        return 0;
    }
    $ids = get_posts(
        array(
            'post_type'              => 'zc_hf_template',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'suppress_filters'       => false,
            'meta_query'             => array(
                array( 'key' => '_zc_hf_type', 'value' => $type ),
                array( 'key' => '_zc_hf_active', 'value' => 'yes' ),
            ),
            'orderby'                => 'menu_order date',
            'order'                  => 'ASC',
        )
    );
    $cache[ $type ] = $ids ? (int) $ids[0] : 0;
    return $cache[ $type ];
}

/** Render the active Elementor header/footer template; empty output keeps a safe fallback. */
function zarincode_builder_part( string $type ): string {
    if ( 'elementor_hf' !== zarincode_get_option( $type . '_source', 'elementor_hf' ) ) {
        return '';
    }
    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return '';
    }
    $id = zarincode_hf_template_id( $type );
    if ( ! $id ) {
        return '';
    }
    // Never recurse when the template itself contains a header/footer shortcode.
    static $rendering = false;
    if ( $rendering ) {
        return '';
    }
    $rendering = true;
    try {
        $html = (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, true );
        if ( '' === trim( $html ) ) {
            return '';
        }
        return '<div class="zc-hf zc-hf-' . esc_attr( $type ) . '" data-zc-hf="' . (int) $id . '">' . $html . '</div>';
    } catch ( \Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Zarincode: header/footer template #' . $id . ' failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
        }
        return '';
    } finally {
        $rendering = false;
    }
}

/** Handy admin-bar shortcut: jump straight into the Elementor template in use. */
add_action( 'admin_bar_menu', static function ( $bar ) {
    if ( ! is_admin_bar_showing() || ! current_user_can( 'edit_posts' ) || ! class_exists( '\Elementor\Plugin' ) ) {
        return;
    }
    foreach ( array( 'header' => 'ویرایش هدر در المنتور', 'footer' => 'ویرایش فوتر در المنتور' ) as $type => $label ) {
        $id = zarincode_hf_template_id( $type );
        if ( ! $id ) {
            continue;
        }
        $bar->add_node(
            array(
                'id'    => 'zc-edit-' . $type,
                'title' => $label,
                'href'  => admin_url( 'post.php?post=' . $id . '&action=elementor' ),
            )
        );
    }
}, 90 );

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
