<?php
/**
 * Elementor Widgets & Custom Category Loader
 *
 * Registers the "زرین کد (Crextio + Webinex)" Elementor category and the 14 existing widgets
 * plus six optional professional agency widgets.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__.'/Hero_Layout.php';
require_once __DIR__.'/Demo_Composition.php';

class Elementor_Loader {

    private static ?Elementor_Loader $instance = null;

    public static function instance(): Elementor_Loader {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {

        add_action('admin_menu',static function(){add_theme_page('ساماندهی دمو','ساماندهی دمو','edit_theme_options','zarincode-demo-layout',[Demo_Composition::class,'page']);add_theme_page('تفکیک هیرو','تفکیک هیرو','edit_theme_options','zarincode-split-hero',[Hero_Layout::class,'page']);});
        add_action('admin_notices',[Icon_Compatibility::class,'notice']);
        add_action('elementor/editor/after_enqueue_scripts',[$this,'editor_assets']);
        add_action( 'admin_init', [ $this, 'refresh_generated_css' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_assets' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
    }

    public function refresh_generated_css(): void {
        if(!current_user_can('manage_options') || get_option('zarincode_css_identity_release')==='3.4.0') return;
        if(class_exists('Elementor\\Plugin') && isset(\Elementor\Plugin::instance()->files_manager)) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
            update_option('zarincode_css_identity_release','3.4.0',false);
        }
    }

    public function register_assets(): void {
        Trust_Badges::register_style();
        $base = strpos( wp_normalize_path( __DIR__ ), wp_normalize_path( get_template_directory() ) . '/' ) === 0
            ? get_template_directory_uri() . '/inc/EmbeddedCore/Widgets/assets/'
            : plugin_dir_url( __FILE__ ) . 'assets/';
        wp_register_style('zarincode-studio',$base.'studio.css',[],'3.4.0');
        wp_register_script('zarincode-studio',$base.'studio.js',['elementor-frontend'],'3.4.0',true);
        wp_register_style('zarincode-native',$base.'native.css',[],'3.4.0');
        wp_register_script('zarincode-native',$base.'native.js',['elementor-frontend'],'3.4.0',true);
        wp_register_style('zarincode-professional',$base.'professional.css',[],'3.4.0');
        wp_register_script('zarincode-professional',$base.'professional.js',['elementor-frontend'],'3.4.0',true);
        wp_register_style( 'zarincode-editable-widgets', $base . 'widgets.css', [], '3.4.0' );
        wp_register_script( 'zarincode-editable-widgets', $base . 'widgets.js', ['elementor-frontend'], '3.4.0', true );
    }

    public function editor_assets():void {
        $base=strpos(wp_normalize_path(__DIR__),wp_normalize_path(get_template_directory()).'/')===0?get_template_directory_uri().'/inc/EmbeddedCore/Widgets/assets/':plugin_dir_url(__FILE__).'assets/';
        wp_enqueue_script('zarincode-editor',$base.'editor.js',['elementor-editor'],'3.4.0',true);
        wp_enqueue_style('zarincode-editor',$base.'editor.css',[],'3.4.0');
    }

    public function register_category( $elements_manager ): void {
        $elements_manager->add_category('zarincode-professional',['title'=>'زرین کد — ابزارهای حرفه‌ای آژانس','icon'=>'fa fa-star']);
        $elements_manager->add_category(
            'zarincode-elements',
            [
                'title' => 'زرین کد — ویجت‌های اختصاصی',
                'icon'  => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets( $widgets_manager ): void {
        require_once __DIR__ . '/Elementor_Widgets_Collection.php';
        require_once __DIR__ . '/Professional_Widgets.php';
        require_once __DIR__.'/Studio_Widgets.php';
        require_once __DIR__.'/Widget_Trust_Badges.php';

        $widget_classes = [
            Widget_Trust_Badges::class,
            Widget_Client_Logos::class,
            Widget_Pricing_Plan::class,
            Widget_Service_Spotlight::class,
            Widget_Project_Roadmap::class,
            Widget_SEO_Audit::class,
            Widget_Before_After::class,
            Widget_Team_Experts::class,
            Widget_Service_Matrix::class,
            Widget_Header_Crextio::class,
            Widget_Footer_Crextio::class,
            Widget_Hero_Crextio::class,
            Widget_Hero_Intro::class,
            Widget_Hero_Profile::class,
            Widget_Hero_CTA::class,
            Widget_Brands_Marquee::class,
            Widget_Services_Bento::class,
            Widget_Products_Marketplace::class,
            Widget_Portfolio_Showcase::class,
            Widget_Why_Us_Stats::class,
            Widget_Pricing_Tabs::class,
            Widget_Promo_Banner::class,
            Widget_Project_Steps::class,
            Widget_Testimonials::class,
            Widget_Blog_Grid::class,
            Widget_Page_Content_Section::class,
        ];

        foreach ( $widget_classes as $w_class ) {
            if ( class_exists( $w_class ) ) {
                $widgets_manager->register( new $w_class() );
            }
        }
    }
}
