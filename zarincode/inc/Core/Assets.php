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
    }

    public function enqueue_frontend_assets(): void {
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
