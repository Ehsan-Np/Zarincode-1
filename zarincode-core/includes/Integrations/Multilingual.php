<?php
namespace Zarincode\Runtime260\Integrations;
defined( 'ABSPATH' ) || exit;
/** Thin adapters to the active translation engine. Never stores translations itself. */
final class Multilingual {
    public static function engine(): string {
        $wpml = defined('ICL_SITEPRESS_VERSION');
        $polylang = function_exists('pll_get_post');
        return $wpml && $polylang ? 'conflict' : ($wpml ? 'wpml' : ($polylang ? 'polylang' : 'none'));
    }
    public static function post_id(int $id, ?string $language = null): int {
        if ($id < 1) { return 0; }
        $type = get_post_type($id);
        if (!$type || !is_post_type_viewable($type)) { return $id; }
        if (self::engine()==='wpml') {
            return (int) apply_filters('wpml_object_id',$id,$type,true,$language);
        }
        if (self::engine()==='polylang') {
            $translated=pll_get_post($id,$language ?? pll_current_language('slug'));
            return $translated ? (int)$translated : $id;
        }
        return $id;
    }
    public static function term_id(int $id,string $taxonomy,?string $language=null): int {
        if ($id<1 || !taxonomy_exists($taxonomy)) { return $id; }
        if (self::engine()==='wpml') {return (int)apply_filters('wpml_object_id',$id,$taxonomy,true,$language);}
        if (self::engine()==='polylang' && function_exists('pll_get_term')) {
            return (int)(pll_get_term($id,$language ?? pll_current_language('slug')) ?: $id);
        }
        return $id;
    }
    public static function post_types(array $types): array {
        foreach(['zc_service','zc_project','zc_hf_template'] as $type){$types[$type]=$type;}
        return $types;
    }
    public static function taxonomies(array $types): array {
        foreach(['zc_service_cat','zc_project_cat'] as $type){$types[$type]=$type;}
        return $types;
    }
    public static function notice(): void {
        if (self::engine()!=='conflict' || !current_user_can('manage_options')) {return;}
        echo '<div class="notice notice-error"><p>'.esc_html__('WPML and Polylang are both active. ZarinCode translation adapters are paused; keep only one translation engine active.','zarincode-core').'</p></div>';
    }
    public static function boot(): void {
        add_filter('pll_get_post_types',[self::class,'post_types']);
        add_filter('pll_get_taxonomies',[self::class,'taxonomies']);
        add_action('admin_notices',[self::class,'notice']);
    }
}
