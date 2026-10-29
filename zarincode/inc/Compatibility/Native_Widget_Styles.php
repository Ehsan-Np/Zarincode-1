<?php
/** Presentation only: native APIs, no copied plugin templates or widget behaviours. */
namespace Zarincode\Theme\Compatibility;
defined('ABSPATH') || exit;
final class Native_Widget_Styles {
    public static function boot(): void {
        add_filter('body_class', [self::class, 'body_classes']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue'], 40);
    }
    public static function body_classes(array $classes): array {
        foreach (['wordpress'=>'zc-wp-widget-skin','woocommerce'=>'zc-wc-widget-skin'] as $family=>$class) {
            if (zarincode_get_option('feature_'.$family.'_widget_styles', true)) $classes[]=$class;
        }
        if (zarincode_get_option('feature_official_widget_styles', true)) $classes[]='zc-elementor-widget-skin';
        return $classes;
    }
    public static function shared(): void {
        $file='/assets/css/widgets/tokens.css';
        wp_enqueue_style('zarincode-widget-tokens', get_template_directory_uri().$file, ['zarincode-main'], (string)filemtime(get_template_directory().$file));
    }
    public static function enqueue(): void {
        // Widgets can appear in sidebars, blocks, shortcodes and builder templates, not only singular pages.
        if (zarincode_get_option('feature_wordpress_widget_styles', true)) {
            self::shared();
            $file='/assets/css/widgets/wordpress.css';
            wp_enqueue_style('zarincode-wordpress-widgets', get_template_directory_uri().$file, ['zarincode-widget-tokens'], (string)filemtime(get_template_directory().$file));
        }
        // WC blocks and mini-cart may occur outside shop/account routes.
        if (class_exists('WooCommerce') && zarincode_get_option('feature_woocommerce_widget_styles', true)) {
            self::shared();
            $dependencies=['zarincode-widget-tokens'];
            if (wp_style_is('woocommerce-general','registered')) $dependencies[]='woocommerce-general';
            $file='/assets/css/widgets/woocommerce.css';
            wp_enqueue_style('zarincode-woocommerce-widgets', get_template_directory_uri().$file, $dependencies, (string)filemtime(get_template_directory().$file));
        }
    }
}
