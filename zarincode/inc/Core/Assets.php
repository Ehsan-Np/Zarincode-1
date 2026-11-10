<?php
/**
 * Theme Assets Manager (Fonts, Styles, Scripts & Redux Dynamic CSS)
 *
 * @package Zarincode\Theme\Core
 */

namespace Zarincode\Theme\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets {

    private static ?Assets $instance = null;

    public static function instance(): Assets {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_styles' ], 5 );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_frontend_styles' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ], 20 );
        // Elementor prints its document stylesheet at priority 20 too, and the
        // plugin's styles land after the theme's. The composed-page design must
        // be able to restyle stock widgets (a Heading's colour, a Button's
        // padding) at equal specificity, so it is enqueued last on purpose.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_page_design' ], 999 );
        add_action( 'wp_enqueue_scripts', [ $this, 'drop_core_block_styles_on_plain_pages' ], 99 );
        add_action( 'wp_head', [ $this, 'preload_critical_fonts' ], 1 );
        add_filter( 'style_loader_tag', [ $this, 'make_fontawesome_non_blocking' ], 10, 4 );
        add_filter( 'script_loader_tag', [ $this, 'defer_theme_scripts' ], 10, 3 );
        // Vazirmatn is the project typeface — including wp-admin, the login
        // screen and the Elementor editor panel/preview.
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_font' ] );
        add_action( 'login_enqueue_scripts', [ $this, 'enqueue_admin_font' ] );
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_admin_font' ] );
        add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_editor_preview_font' ] );
        // Standing typography order: Vazirmatn is the only typeface of the
        // project. Elementor's kit still requests Roboto / Roboto Slab from
        // Google Fonts on every page (measured: 2 external stylesheets), which
        // both breaks the order and adds render-blocking third-party requests.
        add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
        add_filter( 'body_class', [ $this, 'font_body_class' ] );
        add_filter( 'admin_body_class', [ $this, 'font_admin_body_class' ] );
    }

    /**
     * Preload the two Vazirmatn faces used above the fold so text paints with
     * the real font instead of the fallback (font requests are otherwise
     * discovered only after the stylesheet is parsed).
     */
    public function preload_critical_fonts(): void {
        if ( is_admin() || is_feed() || wp_doing_ajax() ) {
            return;
        }
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
            esc_url( ZARINCODE_URI . '/assets/fonts/Vazirmatn-Variable.woff2' )
        );
        // Elementor caches its own font preloads; nothing else is needed.
    }

    /**
     * Font Awesome 7 ships a large render-blocking stylesheet; load it as an
     * asynchronous style swap while keeping the original element id, with a
     * noscript fallback for no-JS environments.
     */
    public function make_fontawesome_non_blocking( string $tag, string $handle, string $href, string $media ): string {
        if ( 'zarincode-fontawesome' !== $handle || is_admin() ) {
            return $tag;
        }
        $swap = "this.onload=null;this.rel='stylesheet'";
        return '<link rel="preload" as="style" href="' . esc_url( $href ) . '" id="zarincode-fontawesome-css" onload="' . esc_attr( $swap ) . '">' . "\n"
            . '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>' . "\n";
    }

    /**
     * Defer the theme's own interaction scripts. Both bind delegated listeners
     * and localize data as separate inline tags, so deferred execution before
     * DOMContentLoaded stays behaviour-compatible.
     */
    public function defer_theme_scripts( string $tag, string $handle, string $src ): string {
        if ( ! in_array( $handle, [ 'zarincode-main', 'zarincode-dialogs' ], true ) || is_admin() || false !== strpos( $tag, 'defer' ) ) {
            return $tag;
        }
        return str_replace( '<script ', '<script defer ', $tag );
    }

    /**
     * Core block-library CSS is only meaningful when the queried singular
     * page actually contains block markup; WordPress already skips it for
     * blockless content, and this keeps the guarantee explicit and resilient
     * to plugins re-registering the handles.
     */
    public function drop_core_block_styles_on_plain_pages(): void {
        if ( is_admin() || ! is_singular() || ! function_exists( 'has_blocks' ) || has_blocks() ) {
            return;
        }
        foreach ( [ 'wp-block-library', 'wp-block-styles' ] as $style ) {
            wp_dequeue_style( $style );
            wp_deregister_style( $style );
        }
        wp_dequeue_script( 'wp-block-library' );
    }

    /**
     * Registers the homepage design system once, independently of the current
     * screen. Registering (not just enqueueing) is what lets Elementor resolve
     * `get_style_depends()` for the homepage widgets — in the editor preview and
     * on any non-front page that embeds one of those widgets or its shortcode.
     */
    /**
     * Load the variable Vazirmatn face wherever WordPress or Elementor paints
     * its own type (admin, login, Elementor editor chrome).
     */
    public function enqueue_admin_font(): void {
        wp_enqueue_style(
            'zarincode-admin-font',
            ZARINCODE_URI . '/assets/css/admin-font.css',
            array(),
            $this->asset_version( '/assets/css/admin-font.css' )
        );
    }

    /**
     * The Elementor preview iframe is a front-end document. Ship the face and
     * the widget skin so what the editor shows is what the visitor gets — and
     * so an Elementor-built page never looks "undesigned" while editing.
     */
    public function enqueue_editor_preview_font(): void {
        $this->enqueue_admin_font();
        wp_enqueue_style(
            'zarincode-vazirmatn',
            ZARINCODE_URI . '/assets/css/vazirmatn.css',
            array(),
            $this->asset_version( '/assets/css/vazirmatn.css' )
        );
        // The preview must show what the visitor gets, so the widget skin is
        // loaded here too — it only paints WordPress/WooCommerce widgets and
        // Elementor's stock widgets, never the header/footer document.
        wp_enqueue_style(
            'zarincode-widgets',
            ZARINCODE_URI . '/assets/css/widgets.css',
            array( 'zarincode-vazirmatn' ),
            $this->asset_version( '/assets/css/widgets.css' )
        );
        wp_enqueue_style(
            'zarincode-hf-footer',
            ZARINCODE_URI . '/assets/css/hf-footer.css',
            array( 'zarincode-vazirmatn' ),
            $this->asset_version( '/assets/css/hf-footer.css' )
        );
        wp_enqueue_style(
            'zarincode-hf-header',
            ZARINCODE_URI . '/assets/css/hf-header.css',
            array( 'zarincode-vazirmatn' ),
            $this->asset_version( '/assets/css/hf-header.css' )
        );
    }

    /** Option «اعداد فارسی» → html.zc-fa-digits (ss01 of Vazirmatn). */
    public function font_body_class( array $classes ): array {
        if ( zarincode_get_option( 'persian_digits', true ) ) {
            $classes[] = 'zc-fa-digits';
        }
        $classes[] = 'zc-font-vazirmatn';
        return $classes;
    }

    /** Same switch for wp-admin. */
    public function font_admin_body_class( $classes ): string {
        $classes = is_string( $classes ) ? $classes : '';
        if ( zarincode_get_option( 'persian_digits', true ) ) {
            $classes .= ' zc-fa-digits';
        }
        return $classes . ' zc-font-vazirmatn';
    }

    /** Version an asset by its mtime so an edited file is never served from cache. */
    private function asset_version( string $relative ): string {
        $path = get_template_directory() . $relative;
        return file_exists( $path ) ? (string) filemtime( $path ) : ZARINCODE_VERSION;
    }

    public function register_frontend_styles(): void {
        if ( wp_style_is( 'zarincode-home-cheerful', 'registered' ) ) {
            return;
        }
        // Depend on the theme stylesheet only when it is actually registered,
        // otherwise WordPress silently drops a style with an unknown dependency.
        $deps = [];
        foreach ( [ 'zarincode-main', 'zarincode-fontawesome' ] as $handle ) {
            if ( wp_style_is( $handle, 'registered' ) ) {
                $deps[] = $handle;
                break;
            }
        }
        wp_register_style(
            'zarincode-home-cheerful',
            ZARINCODE_URI . '/assets/css/home-cheerful.css',
            $deps,
            ZARINCODE_VERSION
        );
    }

    /**
     * Design layer for the Elementor-native demo pages (`.zc-eb-*`).
     *
     * Every demo page is a plain Container tree of stock Elementor widgets, so
     * the look comes from this stylesheet instead of a custom renderer: it
     * restyles a Heading, a Text Editor, a Button, an Icon Box and the
     * container geometry. It is scoped to `zc-eb-*` classes only, so it can
     * never reach a widget the owner dropped in himself, and it is enqueued at
     * priority 999 so it is printed *after* Elementor's own document CSS —
     * otherwise Elementor's equal-specificity rules would win.
     */
    public function enqueue_page_design(): void {
        wp_enqueue_style(
            'zarincode-eb',
            ZARINCODE_URI . '/assets/css/eb.css',
            array( 'zarincode-main' ),
            $this->asset_version( '/assets/css/eb.css' )
        );
    }

    public function enqueue_frontend_assets(): void {
        wp_enqueue_script('zarincode-dialogs',ZARINCODE_URI.'/assets/js/dialogs.js',[],ZARINCODE_VERSION,true);
        // 1. Local Vazirmatn Font
        wp_enqueue_style(
            'zarincode-vazirmatn',
            ZARINCODE_URI . '/assets/css/vazirmatn.css',
            [],
            ZARINCODE_VERSION
        );

        // 2. Font Awesome Free 7.0.0 (icons CC BY 4.0, fonts SIL OFL 1.1, code
        //    MIT — redistributable with a public theme; the Pro build this theme
        //    used to ship is a per-seat commercial licence and cannot be bundled).
        wp_enqueue_style(
            'zarincode-fontawesome',
            ZARINCODE_URI . '/assets/fontawesome/css/all.min.css',
            [],
            \Zarincode\Theme\Core\Icons::VERSION
        );

        // 2b. Icon layer: family pinning, geometry and the size scale. Loaded
        //     right after Font Awesome and before the theme stylesheet so a
        //     component can size an icon with a token instead of a raw font-size.
        wp_enqueue_style(
            'zarincode-icons',
            ZARINCODE_URI . '/assets/css/icons.css',
            [ 'zarincode-fontawesome' ],
            $this->asset_version( '/assets/css/icons.css' )
        );

        // 2c. Old Font Awesome name aliases. Content saved by an earlier version
        //     (Elementor documents, header/footer, third-party widgets) still
        //     uses FA5/6 names; this maps them to the code points the shipped
        //     font has instead of rewriting the database.
        wp_enqueue_style(
            'zarincode-icon-aliases',
            ZARINCODE_URI . '/assets/css/icon-aliases.css',
            [ 'zarincode-icons' ],
            $this->asset_version( '/assets/css/icon-aliases.css' )
        );

        // 3. Theme Main Stylesheet (Crextio Sunshine Yellow + Webinex Design System)
        wp_enqueue_style(
            'zarincode-main',
            ZARINCODE_URI . '/assets/css/zarincode-main.css',
            [ 'zarincode-vazirmatn', 'zarincode-fontawesome', 'zarincode-icons', 'zarincode-icon-aliases' ],
            ZARINCODE_VERSION
        );

        // 4. Widget skin: WordPress core widgets, WooCommerce and Elementor's
        //    stock widgets. Loaded everywhere because an Elementor page, a
        //    widget area or a WooCommerce template can appear on any route.
        //    It deliberately contains no header/footer rules any more — those
        //    documents are authored in Elementor only (see HF_Builder).
        wp_enqueue_style(
            'zarincode-widgets',
            ZARINCODE_URI . '/assets/css/widgets.css',
            array( 'zarincode-main' ),
            $this->asset_version( '/assets/css/widgets.css' )
        );

        // 5. The approved 4.9.0 footer design, applied to the Elementor footer
        //    document (plate, CTA card, 4 columns, chips, bottom bar). Loaded
        //    everywhere so the Elementor preview shows exactly the front end.
        wp_enqueue_style(
            'zarincode-hf-footer',
            ZARINCODE_URI . '/assets/css/hf-footer.css',
            array( 'zarincode-main' ),
            $this->asset_version( '/assets/css/hf-footer.css' )
        );

        // 5b. The Elementor header shell (v4.16.0): sticky band, glass blur,
        //     list resets and the dark-mode mirror. Structure, spacing and
        //     colour of the header itself are Elementor settings.
        wp_enqueue_style(
            'zarincode-hf-header',
            ZARINCODE_URI . '/assets/css/hf-header.css',
            array( 'zarincode-main' ),
            $this->asset_version( '/assets/css/hf-header.css' )
        );

        // Product-page reference layout (hero row, KPI strip, tabs) is shared
        // with portfolio case-study singles so both inner pages look identical.
        if ( is_singular('product') || is_singular('zc_project') ) {
            wp_enqueue_style('zarincode-product-reference', ZARINCODE_URI . '/assets/css/product-reference.css', ['zarincode-main'], ZARINCODE_VERSION);
        }

        // Homepage "cheerful + formal" design system (scoped to .zc-hp, front page only).
        // Other pages that use the section widgets get the file from
        // Elementor's get_style_depends() via the registered handle below.
        if ( is_front_page() ) {
            wp_enqueue_style( 'zarincode-home-cheerful' );
        }

        wp_enqueue_style('zarincode-responsive', ZARINCODE_URI . '/assets/css/responsive.css', ['zarincode-main','zarincode-rtl'], ZARINCODE_VERSION);

        // 4. RTL Native Stylesheet
        wp_enqueue_style(
            'zarincode-rtl',
            ZARINCODE_URI . '/rtl.css',
            [ 'zarincode-main' ],
            ZARINCODE_VERSION
        );

        // 5. Dynamic CSS from Redux Framework Options
        $accent_color = sanitize_hex_color( zarincode_get_option( 'accent_color', '#FDD05B' ) ) ?: '#FDD05B';
        $dark_color   = sanitize_hex_color( zarincode_get_option( 'dark_surface_color', '#2A2C31' ) ) ?: '#2A2C31';
        $bg_from      = sanitize_hex_color( zarincode_get_option( 'bg_gradient_from', '#F8F6F0' ) ) ?: '#F8F6F0';
        $bg_to        = sanitize_hex_color( zarincode_get_option( 'bg_gradient_to', '#FFF5D1' ) ) ?: '#FFF5D1';

        $dynamic_css = ":root {
            --color-yellow: {$accent_color};
            --color-accent: {$accent_color};
            --zc-dark: {$dark_color};
            --zc-bg-from: {$bg_from};
            --zc-bg-to: {$bg_to};
        }";
        wp_add_inline_style( 'zarincode-main', $dynamic_css );

        // 6. Main Frontend Interactive JS + Crextio Switcher
        wp_enqueue_script(
            'zarincode-main',
            ZARINCODE_URI . '/assets/js/zarincode-main.js',
            [],
            ZARINCODE_VERSION,
            true
        );

        wp_localize_script(
            'zarincode-main',
            'ZarincodeData',
            [
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'siteUrl'   => home_url( '/' ),
                'devName'   => ZARINCODE_DEV_NAME,
                'devPhone'  => ZARINCODE_DEV_PHONE,
                'devSite'   => ZARINCODE_DEV_SITE,
                'consultNonce' => wp_create_nonce( 'zc_consult' ),
            ]
        );

        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
}
