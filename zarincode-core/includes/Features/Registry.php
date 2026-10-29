<?php
/**
 * Central feature definitions.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Features;

defined( 'ABSPATH' ) || exit;
/** First registered feature family: optional Elementor widgets. Other legacy services remain migration backlog. */
final class Registry {
	/**
	 * Return filtered definitions; Manager validates resolver fields.
	 *
	 * @return array<array-key,mixed> Feature metadata.
	 */
	public static function all(): array {
		$features = array();
		foreach ( array(
			'native_widgets'       => 'Native widgets',
			'studio_widgets'       => 'Studio widgets',
			'professional_widgets' => 'Professional widgets',
		) as $id => $name ) {
			$features[ $id ] = array(
				'id'           => $id,
				'name'         => $name,
				'description'  => 'Optional Elementor widget family.',
				'category'     => 'elementor',
				'default'      => true,
				'dependencies' => 'native_widgets' === $id ? array() : array( 'native_widgets' ),
				'plugins'      => array( 'elementor' ),
				'conflicts'    => array(),
				'capability'   => 'manage_options',
				'css'          => array(),
				'js'           => array(),
				'services'     => array(),
				'widgets'      => array(),
				'settings'     => 'feature_' . $id,
				'translation'  => 'zarincode-core',
				'cache_groups' => array( 'elementor-css' ),
				'version'      => '4.0.1',
				'migration'    => 'No destructive migration. Missing option preserves enabled state.',
				'tests'        => array( 'feature-resolver', 'disabled-widget-output', 'disabled-widget-assets' ),
			);
		}
		$features['native_widgets']['widgets']       = array_map( static fn( $id ) => 'zarincode_' . $id, array( 'header', 'footer', 'hero', 'hero_intro', 'hero_cta', 'brands', 'services', 'products', 'portfolio', 'why_us', 'pricing', 'promo', 'steps', 'testimonials', 'blog', 'page_section' ) );
		$features['native_widgets']['css']           = array( 'zarincode-editable-widgets', 'zarincode-native', 'zarincode-studio' );
		$features['native_widgets']['js']            = array( 'zarincode-editable-widgets', 'zarincode-native' );
		$features['studio_widgets']['widgets']       = array( 'zarincode_trust_badges', 'zarincode_client_logos', 'zarincode_pricing_plan' );
		$features['studio_widgets']['css']           = array( 'zarincode-studio', 'zarincode-trust' );
		$features['studio_widgets']['js']            = array( 'zarincode-studio' );
		$features['professional_widgets']['widgets'] = array_map( static fn( $id ) => 'zarincode_' . $id, array( 'service_spotlight', 'project_roadmap', 'seo_audit', 'before_after', 'team_experts', 'service_matrix', 'products_slider' ) );
		$features['professional_widgets']['css']     = array( 'zarincode-professional' );
		$features['professional_widgets']['js']      = array( 'zarincode-professional' );
		$features['official_widget_styles']          = array_replace(
			$features['native_widgets'],
			array(
				'id'           => 'official_widget_styles',
				'name'         => 'Official widget theme styles',
				'settings'     => 'feature_official_widget_styles',
				'dependencies' => array(),
				'widgets'      => array(),
				'css'          => array( 'zarincode-elementor-official' ),
				'js'           => array(),
				'tests'        => array( 'official-style-controls', 'official-style-assets' ),
			)
		);
		$features['public_dynamic_tags']             = array_replace(
			$features['native_widgets'],
			array(
				'id'           => 'public_dynamic_tags',
				'name'         => 'Public content dynamic tags',
				'settings'     => 'feature_public_dynamic_tags',
				'dependencies' => array(),
				'widgets'      => array(),
				'css'          => array(),
				'js'           => array(),
				'services'     => array( 'elementor_public_content_tag' ),
				'tests'        => array( 'dynamic-tag-public-content', 'dynamic-tag-private-content' ),
			)
		);
		$features['article_ratings']                 = array_replace(
			$features['native_widgets'],
			array(
				'id'           => 'article_ratings',
				'name'         => 'Moderated article ratings',
				'settings'     => 'blog_article_ratings',
				'description'  => 'Optional moderated article-comment ratings.',
				'category'     => 'content',
				'cache_groups' => array(),
				'dependencies' => array(),
				'plugins'      => array(),
				'widgets'      => array(),
				'css'          => array(),
				'js'           => array(),
				'services'     => array( 'comment_rating_validation', 'comment_rating_persistence' ),
				'tests'        => array( 'rating-nonce', 'rating-validation', 'rating-duplicates', 'rating-moderation', 'theme-switch-rating' ),
			)
		);
		$features['product_policies']                = array_replace(
			$features['article_ratings'],
			array(
				'id'       => 'product_policies',
				'name'     => 'Product policies and changelog',
				'settings' => 'feature_product_policies',
				'services' => array( 'product_policy_persistence', 'product_policy_admin' ),
				'tests'    => array( 'policy-permissions', 'policy-nonce', 'policy-schema', 'policy-theme-switch' ),
			)
		);
		$features['threaded_discussion']             = array_replace(
			$features['article_ratings'],
			array(
				'id'       => 'threaded_discussion',
				'name'     => 'Native threaded discussion validation',
				'settings' => 'feature_threaded_discussion',
				'services' => array( 'comment_parent_validation', 'review_reply_integrity' ),
				'tests'    => array( 'parent-post-binding', 'reply-depth', 'reply-vote-removal', 'verified-buyer' ),
			)
		);
		$features['social_profiles']                 = array_replace(
			$features['article_ratings'],
			array(
				'id'       => 'social_profiles',
				'name'     => 'Social profile persistence and sanitization',
				'settings' => 'feature_social_profiles',
				'services' => array( 'social_profile_store', 'bounded_svg_sanitizer' ),
				'tests'    => array( 'social-xss', 'social-nonce', 'social-theme-switch', 'social-schema' ),
			)
		);
        $features['webp_uploads'] = array_replace(
            $features['article_ratings'],
            [
                'id' => 'webp_uploads', 'name' => 'Automatic WebP uploads',
                'description' => 'Safe conversion of supported new local raster uploads; originals replaced only after validation.',
                'migration' => 'No bulk migration. Successful future uploads replace their source; failures preserve it.',
                'settings' => 'feature_webp_uploads', 'category' => 'media',
                'services' => [ 'safe_local_webp_upload_conversion' ],
                'tests' => [ 'webp-real-codec', 'webp-animation', 'webp-failover', 'webp-collision', 'webp-resource-limits' ],
            ]
        );
		return (array) apply_filters( 'zarincode_feature_registry', $features );
	}
}
