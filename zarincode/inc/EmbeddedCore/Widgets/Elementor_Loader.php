<?php
/**
 * Elementor Custom Category & 12 Custom Widgets Registration Loader
 *
 * @package Zarincode\Core\Widgets
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\Widgets;

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
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
    }

    public function register_category( $elements_manager ): void {
        $elements_manager->add_category(
            'zarincode-elements',
            [
                'title' => 'ویجت‌های اختصاصی زرین کد (Zarincode)',
                'icon'  => 'fa fa-code',
            ]
        );
    }

    public function register_widgets( $widgets_manager ): void {
        if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
            return;
        }

        require_once ZARINCODE_CORE_PATH . 'includes/Widgets/Elementor_Widgets_Collection.php';

        $widget_classes = [
            '\Zarincode\Core\Widgets\Widget_Hero_Crextio',
            '\Zarincode\Core\Widgets\Widget_Brands_Marquee',
            '\Zarincode\Core\Widgets\Widget_Services_Bento',
            '\Zarincode\Core\Widgets\Widget_Products_Marketplace',
            '\Zarincode\Core\Widgets\Widget_Portfolio_Showcase',
            '\Zarincode\Core\Widgets\Widget_Why_Us_Stats',
            '\Zarincode\Core\Widgets\Widget_Pricing_Tabs',
            '\Zarincode\Core\Widgets\Widget_Promo_Banner',
            '\Zarincode\Core\Widgets\Widget_Project_Steps',
            '\Zarincode\Core\Widgets\Widget_Testimonials',
            '\Zarincode\Core\Widgets\Widget_Blog_Grid',
            '\Zarincode\Core\Widgets\Widget_Page_Content_Section',
        ];

        foreach ( $widget_classes as $class_name ) {
            if ( class_exists( $class_name ) ) {
                $widgets_manager->register( new $class_name() );
            }
        }
    }
}
