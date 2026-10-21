<?php
/**
 * Plugin Name: Zarincode Core (هسته و ویجت‌های المنتوری قالب زرین کد)
 * Plugin URI:  https://zarincode.com
 * Description: افزونه هسته و ضروری قالب وردپرس «زرین کد (Zarincode)» شامل ۲۰ ویجت المنتور با ویرایش محتوای متصل به پیش‌نمایش، پست‌تایپ‌های خدمات و نمونه‌کارها، هدر و فوترساز المنتوری، اتصال به ووکامرس و درون‌ریز هوشمند دمو و صفحات حقوقی (قوانین ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA).
 * Version:     2.7.0
 * Author:      Ehsan NaderiPanah (احسان نادری پناه)
 * Author URI:  https://zarincode.com
 * Text Domain: zarincode-core
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

defined( 'ZARINCODE_CORE_VERSION' ) || define( 'ZARINCODE_CORE_VERSION', '2.7.0' );
defined( 'ZARINCODE_CORE_PATH' ) || define( 'ZARINCODE_CORE_PATH', plugin_dir_path( __FILE__ ) );
defined( 'ZARINCODE_CORE_URL' ) || define( 'ZARINCODE_CORE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
    $prefix = 'Zarincode\\Runtime260\\';
    $len    = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $rel_file       = str_replace( '\\', '/', $relative_class ) . '.php';

    // Always prefer the theme's EmbeddedCore if present so theme updates immediately take effect
    if ( defined( 'WP_CONTENT_DIR' ) ) {
        $theme_embedded = WP_CONTENT_DIR . '/themes/zarincode/inc/EmbeddedCore/' . $rel_file;
        if ( file_exists( $theme_embedded ) ) {
            require_once $theme_embedded;
            return;
        }
    }

    $file = ZARINCODE_CORE_PATH . 'includes/' . $rel_file;
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

final class Zarincode_Core_Plugin_260 {

    private static ?Zarincode_Core_Plugin_260 $instance = null;

    public static function instance(): Zarincode_Core_Plugin_260 {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        \Zarincode\Runtime260\PostTypes\CPT_Manager::instance();
        \Zarincode\Runtime260\Widgets\Renderer::instance();
        \Zarincode\Runtime260\Widgets\Elementor_Loader::instance();
        \Zarincode\Runtime260\Demo\Demo_Seeder::instance();
    }
}

Zarincode_Core_Plugin_260::instance();
