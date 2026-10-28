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
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ], 20 );
        add_action( 'wp_enqueue_scripts', [ $this, 'drop_core_block_styles_on_plain_pages' ], 99 );
        add_action( 'wp_head', [ $this, 'preload_critical_fonts' ], 1 );
        add_filter( 'style_loader_tag', [ $this, 'make_fontawesome_non_blocking' ], 10, 4 );
        add_filter( 'script_loader_tag', [ $this, 'defer_theme_scripts' ], 10, 3 );
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
        foreach ( [ 'Vazirmatn-Regular.woff2', 'Vazirmatn-Bold.woff2' ] as $font ) {
            printf(
                '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
                esc_url( ZARINCODE_URI . '/assets/fonts/vazirmatn/' . $font )
            );
        }
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

    public function enqueue_frontend_assets(): void {
        wp_enqueue_script('zarincode-dialogs',ZARINCODE_URI.'/assets/js/dialogs.js',[],ZARINCODE_VERSION,true);
        // 1. Local Vazirmatn Font
        wp_enqueue_style(
            'zarincode-vazirmatn',
            ZARINCODE_URI . '/assets/css/vazirmatn.css',
            [],
            ZARINCODE_VERSION
        );

        // 2. FontAwesome Pro 7 (Bundled from Ehsan-Np/afzoneha)
        wp_enqueue_style(
            'zarincode-fontawesome',
            ZARINCODE_URI . '/assets/fontawesome/css/all.min.css',
            [],
            '7.0.0'
        );

        // 3. Theme Main Stylesheet (Crextio Sunshine Yellow + Webinex Design System)
        wp_enqueue_style(
            'zarincode-main',
            ZARINCODE_URI . '/assets/css/zarincode-main.css',
            [ 'zarincode-vazirmatn', 'zarincode-fontawesome' ],
            ZARINCODE_VERSION
        );

        if ( is_singular('product') ) {
            wp_enqueue_style('zarincode-product-reference', ZARINCODE_URI . '/assets/css/product-reference.css', ['zarincode-main'], ZARINCODE_VERSION);
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
            ]
        );

        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
}
