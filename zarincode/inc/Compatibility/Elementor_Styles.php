<?php
namespace Zarincode\Theme\Compatibility;
defined('ABSPATH') || exit;
/** Theme-only styling. Explicit Elementor-generated control styles retain higher specificity. */
final class Elementor_Styles {
    public static function boot(): void {
        add_action('elementor/frontend/after_enqueue_styles',[self::class,'enqueue']);
    }
    public static function enqueue(): void {
        if (!zarincode_get_option('feature_official_widget_styles',true)) {return;}
        Native_Widget_Styles::shared();
        wp_enqueue_style('zarincode-elementor-official',get_template_directory_uri().'/assets/css/elementor/official.css',['zarincode-widget-tokens'],(string)filemtime(get_template_directory().'/assets/css/elementor/official.css'));
    }
}
