<?php
/** Installable, privacy-first app shell. No customer HTML is persisted. */
namespace Zarincode\Runtime260\PWA;

defined( 'ABSPATH' ) || exit;

final class App {
    public static function boot(): void {
        add_action( 'template_redirect', [ self::class, 'endpoint' ], 0 );
        add_action( 'wp_head', [ self::class, 'metadata' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
    }

    private static function enabled(): bool {
        return (bool) apply_filters( 'zarincode_pwa_enabled', true );
    }

    private static function url( string $resource ): string {
        return add_query_arg( 'zc_pwa', $resource, home_url( '/' ) );
    }

    public static function metadata(): void {
        if ( ! self::enabled() ) { return; }
        printf( '<link rel="manifest" href="%s">' . "\n", esc_url( self::url( 'manifest' ) ) );
        printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( ZARINCODE_CORE_URL . 'assets/pwa/icon-180.png' ) );
        echo '<meta name="theme-color" content="#2a2c31"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    }

    public static function assets(): void {
        if ( ! self::enabled() || is_customize_preview() || isset( $_GET['elementor-preview'] ) ) { return; }
        $version = ZARINCODE_CORE_VERSION . '-' . filemtime( ZARINCODE_CORE_PATH . 'assets/pwa/app.js' );
        wp_enqueue_style( 'zarincode-pwa', ZARINCODE_CORE_URL . 'assets/pwa/app.css', [], $version );
        wp_enqueue_script( 'zarincode-pwa', ZARINCODE_CORE_URL . 'assets/pwa/app.js', [], $version, true );
        wp_localize_script( 'zarincode-pwa', 'ZarincodePWA', [
            'worker' => self::url( 'worker' ),
            'scope' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/',
            'iosHelp' => __( 'برای نصب، در منوی اشتراک‌گذاری Safari گزینه «افزودن به صفحه اصلی» را انتخاب کنید.', 'zarincode-core' ),
            'installLabel' => __( 'نصب برنامه', 'zarincode-core' ),
            'updateLabel' => __( 'نسخه جدید آماده است؛ به‌روزرسانی', 'zarincode-core' ),
        ] );
    }

    public static function endpoint(): void {
        // Deliberately not a rewrite: works with plain permalinks and subdirectories.
        $resource = isset( $_GET['zc_pwa'] ) && is_string( $_GET['zc_pwa'] ) ? sanitize_key( wp_unslash( $_GET['zc_pwa'] ) ) : '';
        if ( ! in_array( $resource, [ 'manifest', 'worker', 'offline' ], true ) ) { return; }
        nocache_headers();
        header( 'X-Content-Type-Options: nosniff' );
        if ( 'worker' === $resource ) {
            header( 'Content-Type: application/javascript; charset=UTF-8' );
            header( 'Service-Worker-Allowed: ' . ( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' ) );
            $base = ZARINCODE_CORE_URL . 'assets/pwa/';
            $config = [
                'enabled' => self::enabled(),
                'prefix' => 'zarincode-pwa-' . substr( md5( home_url( '/' ) ), 0, 12 ) . '-',
                'version' => ZARINCODE_CORE_VERSION . '-' . md5_file( ZARINCODE_CORE_PATH . 'assets/pwa/worker.js' ) . '-' . (string)get_option('zarincode_cache_epoch','0'),
                'offline' => self::url( 'offline' ),
                'assets' => [ $base . 'icon-192.png', $base . 'icon-512.png', $base . 'icon-maskable-512.png' ],
            ];
            echo 'const CONFIG = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ";\n";
            readfile( ZARINCODE_CORE_PATH . 'assets/pwa/worker.js' );
            exit;
        }
        if ( ! self::enabled() ) { status_header( 404 ); exit; }
        if ( 'offline' === $resource ) {
            header( 'Content-Type: text/html; charset=UTF-8' );
            readfile( ZARINCODE_CORE_PATH . 'assets/pwa/offline.html' );
            exit;
        }
        header( 'Content-Type: application/manifest+json; charset=UTF-8' );
        $name = wp_strip_all_tags( get_bloginfo( 'name' ) );
        echo wp_json_encode( [
            'id' => home_url( '/' ), 'name' => $name, 'short_name' => $name,
            'lang' => get_bloginfo( 'language' ), 'dir' => is_rtl() ? 'rtl' : 'ltr',
            'start_url' => home_url( '/' ), 'scope' => home_url( '/' ),
            'display' => 'standalone', 'background_color' => '#f8f6f0', 'theme_color' => '#2a2c31',
            'icons' => [
                [ 'src' => ZARINCODE_CORE_URL . 'assets/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ],
                [ 'src' => ZARINCODE_CORE_URL . 'assets/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ],
                [ 'src' => ZARINCODE_CORE_URL . 'assets/pwa/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        exit;
    }
}
