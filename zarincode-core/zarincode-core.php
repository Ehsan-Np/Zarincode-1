<?php
/**
 * Plugin Name: Zarincode Core (هسته و ویجت‌های المنتوری قالب زرین کد)
 * Plugin URI:  https://zarincode.com
 * Description: افزونه هسته و ضروری قالب وردپرس «زرین کد (Zarincode)» شامل ۲۶ ویجت المنتور با ویرایش محتوای متصل به پیش‌نمایش، پست‌تایپ‌های خدمات و نمونه‌کارها، هدر و فوترساز المنتوری، اتصال به ووکامرس و درون‌ریز هوشمند دمو و صفحات حقوقی (قوانین ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA).
 * Version:     4.0.2
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

defined( 'ZARINCODE_CORE_VERSION' ) || define( 'ZARINCODE_CORE_VERSION', '4.0.2' );
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
        \Zarincode\Runtime260\Demo\Bundled_Images::boot();
        \Zarincode\Runtime260\Media\WebP_Uploads::boot();
        \Zarincode\Runtime260\Media\Lazy_Images::boot();
        \Zarincode\Runtime260\PWA\App::boot();
        \Zarincode\Runtime260\Integrations\Multilingual::boot();
        add_action('elementor/element/after_section_end',[\Zarincode\Runtime260\Elementor\Shared_Controls::class,'register'],10,2);
        add_action('elementor/dynamic_tags/register',static function($manager){
            if(!\Zarincode\Runtime260\Features\Manager::enabled('public_dynamic_tags'))return;
            $manager->register_group('zarincode-public',['title'=>'ZarinCode']);
            $manager->register(new \Zarincode\Runtime260\Elementor\Public_Data_Tag());
        });
        add_action('init',static function(){if(\Zarincode\Runtime260\Features\Manager::enabled('article_ratings'))\Zarincode\Runtime260\Content\Article_Rating_Service::boot();});
        add_action('init',static function(){if(\Zarincode\Runtime260\Features\Manager::enabled('product_policies'))\Zarincode\Runtime260\Admin\Product_Policy_Admin::boot();});
        add_action('init',static function(){if(\Zarincode\Runtime260\Features\Manager::enabled('threaded_discussion'))\Zarincode\Runtime260\Content\Discussion_Service::boot();});
        add_action('init',static function(){if(\Zarincode\Runtime260\Features\Manager::enabled('social_profiles'))\Zarincode\Runtime260\Content\Social_Profile_Store::boot();});
        add_filter('debug_information', [\Zarincode\Runtime260\Support\Diagnostics::class,'site_health']);
        add_action('init',static function(){load_plugin_textdomain('zarincode-core',false,dirname(plugin_basename(__FILE__)).'/languages');});
        \Zarincode\Runtime260\PostTypes\CPT_Manager::instance();
        \Zarincode\Runtime260\Widgets\Renderer::instance();
        \Zarincode\Runtime260\Widgets\Elementor_Loader::instance();
        \Zarincode\Runtime260\Demo\Demo_Seeder::instance();
    }
}

add_action('before_woocommerce_init',static function(){
    if(class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables',__FILE__,true);
    }
});
Zarincode_Core_Plugin_260::instance();
