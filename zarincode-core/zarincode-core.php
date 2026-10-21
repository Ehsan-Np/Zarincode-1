<?php
/**
 * Plugin Name: Zarincode Core (هسته و ویجت‌های المنتوری قالب زرین کد)
 * Plugin URI:  https://zarincode.com
 * Description: افزونه هسته و ضروری قالب وردپرس «زرین کد (Zarincode)» شامل ۱۲ ویجت اختصاصی المنتور، پست‌تایپ‌های خدمات و نمونه‌کارها، هدر و فوترساز المنتوری، اتصال به ووکامرس و درون‌ریز هوشمند دمو و صفحات حقوقی (قوانین ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA).
 * Version:     2.5.0
 * Author:      Ehsan NaderiPanah (احسان نادری پناه)
 * Author URI:  https://zarincode.com
 * Text Domain: zarincode-core
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ZARINCODE_CORE_VERSION', '2.5.0' );
define( 'ZARINCODE_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZARINCODE_CORE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
    $prefix   = 'Zarincode\\Core\\';
    $base_dir = ZARINCODE_CORE_PATH . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

final class Zarincode_Core_Plugin {

    private static ?Zarincode_Core_Plugin $instance = null;

    public static function instance(): Zarincode_Core_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        \Zarincode\Core\PostTypes\CPT_Manager::instance();
        \Zarincode\Core\Widgets\Renderer::instance();
        \Zarincode\Core\Widgets\Elementor_Loader::instance();
        \Zarincode\Core\Demo\Demo_Seeder::instance();
    }
}

Zarincode_Core_Plugin::instance();
