<?php
namespace Zarincode\Theme\Compatibility;
defined('ABSPATH') || exit;
/** Theme-only styling. Explicit Elementor-generated control styles retain higher specificity. */
final class Elementor_Styles {
    public static function boot(): void {
        add_action('elementor/frontend/after_enqueue_styles',[self::class,'enqueue']);
    }
    public static function enqueue(): void {
        if (!is_singular() || get_post_meta(get_queried_object_id(),'_elementor_edit_mode',true)!=='builder') {return;}
        if (!zarincode_get_option('feature_official_widget_styles',true)) {return;}
        wp_enqueue_style('zarincode-elementor-official',get_template_directory_uri().'/assets/css/elementor/official.css',[],ZARINCODE_VERSION);
    }
}
