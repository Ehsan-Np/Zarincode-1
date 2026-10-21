<?php
/**
 * Elementor Widgets & Custom Category Loader
 *
 * Registers the "زرین کد (Crextio + Webinex)" Elementor category and all 14
 * fully customizable Zarincode Elementor widgets.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Loader {

    private static ?Elementor_Loader $instance = null;

    public static function instance(): Elementor_Loader {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_assets' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
    }

    public function register_assets(): void {
        $base = strpos( wp_normalize_path( __DIR__ ), wp_normalize_path( get_template_directory() ) . '/' ) === 0
            ? get_template_directory_uri() . '/inc/EmbeddedCore/Widgets/assets/'
            : plugin_dir_url( __FILE__ ) . 'assets/';
        wp_register_style( 'zarincode-editable-widgets', $base . 'widgets.css', [], '2.6.0' );
        wp_register_script( 'zarincode-editable-widgets', $base . 'widgets.js', [], '2.6.0', true );
    }

    public function register_category( $elements_manager ): void {
        $elements_manager->add_category(
            'zarincode-elements',
            [
                'title' => '✨ زرین کد (Crextio + Webinex — ۱۴ ویجت اختصاصی)',
                'icon'  => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets( $widgets_manager ): void {
        require_once __DIR__ . '/Elementor_Widgets_Collection.php';

        $widget_classes = [
            Widget_Header_Crextio::class,
            Widget_Footer_Crextio::class,
            Widget_Hero_Crextio::class,
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
