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

class Elementor_Loader {

    private static ?Elementor_Loader $instance = null;

    public static function instance(): Elementor_Loader {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_notices', [ $this, 'required_extensions_notice' ] );
        add_action( 'admin_init', [ $this, 'refresh_generated_css' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_assets' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
    }

    public function required_extensions_notice(): void {
        if(class_exists('DOMDocument') || !current_user_can('manage_options'))return;
        echo '<div class="notice notice-warning"><p>'.esc_html__('برای ویرایش مستقیم و دقیق متن‌های طراحی اصلی زرین کد، افزونه DOM در PHP هاست را فعال کنید. تا آن زمان نمایش سایت و کنترل‌های قبلی حفظ می‌شوند.', 'zarincode-core').'</p></div>';
    }

    public function refresh_generated_css(): void {
        if(!current_user_can('manage_options') || get_option('zarincode_css_identity_release')==='2.7.0') return;
        if(class_exists('Elementor\\Plugin') && isset(\Elementor\Plugin::instance()->files_manager)) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
            update_option('zarincode_css_identity_release','2.7.0',false);
        }
    }

    public function register_assets(): void {
        $base = strpos( wp_normalize_path( __DIR__ ), wp_normalize_path( get_template_directory() ) . '/' ) === 0
            ? get_template_directory_uri() . '/inc/EmbeddedCore/Widgets/assets/'
            : plugin_dir_url( __FILE__ ) . 'assets/';
        wp_register_style('zarincode-professional',$base.'professional.css',[],'2.7.0');
        wp_register_script('zarincode-professional',$base.'professional.js',[],'2.7.0',true);
        wp_register_style( 'zarincode-editable-widgets', $base . 'widgets.css', [], '2.7.0' );
        wp_register_script( 'zarincode-editable-widgets', $base . 'widgets.js', [], '2.7.0', true );
    }

    public function register_category( $elements_manager ): void {
        $elements_manager->add_category('zarincode-professional',['title'=>'زرین کد — ابزارهای حرفه‌ای آژانس','icon'=>'fa fa-star']);
        $elements_manager->add_category(
            'zarincode-elements',
            [
                'title' => '✨ زرین کد (Crextio + Webinex — ۱۴ ویجت طراحی اصلی)',
                'icon'  => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets( $widgets_manager ): void {
        require_once __DIR__ . '/Elementor_Widgets_Collection.php';
        require_once __DIR__ . '/Professional_Widgets.php';

        $widget_classes = [
            Widget_Service_Spotlight::class,
            Widget_Project_Roadmap::class,
            Widget_SEO_Audit::class,
            Widget_Before_After::class,
            Widget_Team_Experts::class,
            Widget_Service_Matrix::class,
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
