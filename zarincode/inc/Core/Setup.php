<?php
/**
 * Theme Core Setup, Supports, Menus, Sidebars & RTL/WooCommerce Configuration
 *
 * @package Zarincode\Theme\Core
 */

namespace Zarincode\Theme\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Setup {

    private static ?Setup $instance = null;

    public static function instance(): Setup {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
        add_action( 'widgets_init', [ $this, 'register_sidebars' ] );
        add_filter('woocommerce_single_product_image_gallery_classes',static fn(array $classes):array=>array_merge($classes,[is_rtl()?'zc-gallery-rtl':'zc-gallery-ltr']));
        add_filter( 'body_class', [ $this, 'body_classes' ] );
        add_filter( 'language_attributes', [ $this, 'force_rtl_attributes' ], 20 );
        add_filter( 'template_include', [ $this, 'enforce_custom_single_templates' ], 99 );
    }

    /**
     * Ensure Single Product, Single Service, and Single Project always use Zarincode's dedicated templates
     * instead of WooCommerce's unstyled default single-product.php
     */
    public function enforce_custom_single_templates( string $template ): string {
        if ( is_singular( 'product' ) ) {
            $custom = get_template_directory() . '/single-product.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( is_singular( 'zc_service' ) ) {
            $custom = get_template_directory() . '/single-zc_service.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( is_singular( 'zc_project' ) ) {
            $custom = get_template_directory() . '/single-zc_project.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
            $custom = get_template_directory() . '/woocommerce.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public function setup_theme(): void {
        load_theme_textdomain( 'zarincode', ZARINCODE_DIR . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_image_size('zarincode-card',960,600,true);
        add_image_size('zarincode-avatar',160,160,true);
        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'align-wide' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support(
            'html5',
            [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ]
        );
        add_theme_support(
            'custom-logo',
            [
                'height'      => 80,
                'width'       => 240,
                'flex-height' => true,
                'flex-width'  => true,
            ]
        );

        // WooCommerce Marketplace Support
        add_theme_support( 'woocommerce' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );

        // Register Navigation Menus
        register_nav_menus(
            [
                'primary'   => esc_html__( 'منوی اصلی هدر (Pill Navigation)', 'zarincode' ),
                'legal_nav' => esc_html__( 'منوی قوانین و مقررات (فوتر و سایدبار حقوقی)', 'zarincode' ),
                'footer_1'  => esc_html__( 'دسترسی سریع فوتر', 'zarincode' ),
            ]
        );

        // Set global content width (ThemeForest standard)
        if ( ! isset( $GLOBALS['content_width'] ) ) {
            $GLOBALS['content_width'] = 1440;
        }
    }

    public function register_sidebars(): void {
        register_sidebar(
            [
                'name'          => esc_html__( 'سایدبار وبلاگ زرین کد', 'zarincode' ),
                'id'            => 'sidebar-blog',
                'description'   => esc_html__( 'ابزارک‌های ستون کناری وبلاگ', 'zarincode' ),
                'before_widget' => '<div id="%1$s" class="zc-glass-panel widget %2$s" style="padding:1.5rem;margin-bottom:1.5rem;">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="widget-title" style="margin-top:0;font-weight:800;font-size:1.05rem;">',
                'after_title'   => '</h4>',
            ]
        );

        register_sidebar(
            [
                'name'          => esc_html__( 'سایدبار فروشگاه محصولات دیجیتال', 'zarincode' ),
                'id'            => 'sidebar-shop',
                'description'   => esc_html__( 'فیلترها و ابزارک‌های فروشگاه قالب، افزونه و سورس‌کد', 'zarincode' ),
                'before_widget' => '<div id="%1$s" class="zc-glass-panel widget %2$s" style="padding:1.5rem;margin-bottom:1.5rem;">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="widget-title" style="margin-top:0;font-weight:800;font-size:1.05rem;">',
                'after_title'   => '</h4>',
            ]
        );
    }

    public function body_classes( array $classes ): array {
        $classes[] = 'zarincode-theme';
        $classes[] = 'crextio-sunshine-ui';
        if ( is_rtl() ) { $classes[] = 'rtl'; }
        return $classes;
    }

    public function force_rtl_attributes( string $output ): string {
        if ( is_admin() ) {
            return $output;
        }
        if ( strpos( $output, 'dir=' ) === false ) {
            $output .= is_rtl() ? ' dir="rtl"' : ' dir="ltr"';
        }
        return $output;
    }
}
