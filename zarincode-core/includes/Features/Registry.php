<?php
namespace Zarincode\Runtime260\Features;
defined( 'ABSPATH' ) || exit;
/** First registered feature family: optional Elementor widgets. Other legacy services remain migration backlog. */
final class Registry {
    public static function all(): array {
        $features = [];
        foreach ( [ 'native_widgets' => 'Native widgets', 'studio_widgets' => 'Studio widgets', 'professional_widgets' => 'Professional widgets' ] as $id => $name ) {
            $features[$id] = [
                'id' => $id, 'name' => $name, 'description' => 'Optional Elementor widget family.',
                'category' => 'elementor', 'default' => true,
                'dependencies' => 'native_widgets' === $id ? [] : [ 'native_widgets' ],
                'plugins' => [ 'elementor' ], 'conflicts' => [], 'capability' => 'manage_options',
                'css' => [], 'js' => [], 'services' => [], 'widgets' => [],
                'settings' => 'feature_' . $id, 'translation' => 'zarincode-core',
                'cache_groups' => [ 'elementor-css' ], 'version' => '4.0.0-rc.2',
                'migration' => 'No destructive migration. Missing option preserves enabled state.',
                'tests' => [ 'feature-resolver', 'disabled-widget-output', 'disabled-widget-assets' ],
            ];
        }
        $features['native_widgets']['widgets'] = array_map(static fn($id) => 'zarincode_'.$id, ['header','footer','hero','hero_intro','hero_profile','hero_cta','brands','services','products','portfolio','why_us','pricing','promo','steps','testimonials','blog','page_section']);
        $features['native_widgets']['css'] = ['zarincode-editable-widgets','zarincode-native','zarincode-studio'];
        $features['native_widgets']['js'] = ['zarincode-editable-widgets','zarincode-native'];
        $features['studio_widgets']['widgets'] = ['zarincode_trust_badges','zarincode_client_logos','zarincode_pricing_plan'];
        $features['studio_widgets']['css'] = ['zarincode-studio','zarincode-trust'];
        $features['studio_widgets']['js'] = ['zarincode-studio'];
        $features['professional_widgets']['widgets'] = array_map(static fn($id) => 'zarincode_'.$id, ['service_spotlight','project_roadmap','seo_audit','before_after','team_experts','service_matrix']);
        $features['professional_widgets']['css'] = ['zarincode-professional'];
        $features['professional_widgets']['js'] = ['zarincode-professional'];
        $features['official_widget_styles'] = array_replace($features['native_widgets'],[
            'id'=>'official_widget_styles','name'=>'Official widget theme styles','settings'=>'feature_official_widget_styles',
            'dependencies'=>[],'widgets'=>[],'css'=>['zarincode-elementor-official'],'js'=>[],
            'tests'=>['official-style-controls','official-style-assets'],
        ]);
        $features['public_dynamic_tags'] = array_replace($features['native_widgets'],[
            'id'=>'public_dynamic_tags','name'=>'Public content dynamic tags','settings'=>'feature_public_dynamic_tags',
            'dependencies'=>[],'widgets'=>[],'css'=>[],'js'=>[],'services'=>['elementor_public_content_tag'],
            'tests'=>['dynamic-tag-public-content','dynamic-tag-private-content'],
        ]);
        $features['article_ratings'] = array_replace($features['native_widgets'],[
            'id'=>'article_ratings','name'=>'Moderated article ratings','settings'=>'blog_article_ratings',
            'description'=>'Optional moderated article-comment ratings.','category'=>'content','cache_groups'=>[],
            'dependencies'=>[],'plugins'=>[],'widgets'=>[],'css'=>[],'js'=>[],
            'services'=>['comment_rating_validation','comment_rating_persistence'],
            'tests'=>['rating-nonce','rating-validation','rating-duplicates','rating-moderation','theme-switch-rating'],
        ]);
        return (array) apply_filters( 'zarincode_feature_registry', $features );
    }
}
