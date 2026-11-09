<?php
/**
 * Cache helpers for the homepage content lookups (v4.9.0).
 *
 * The homepage reads real site content in three places that would otherwise
 * repeat identical queries on every page view:
 *
 *  - the title index the ticker uses to point a term at a real page,
 *  - the average product rating shown in the hero trust cards,
 *  - the brand list built from project clients.
 *
 * Each value is cached for 12 hours in a non-autoloaded transient and flushed
 * the moment content changes, so an editor never sees stale data and normal
 * visitors pay one lookup instead of ~25 queries.
 *
 * @package Zarincode
 */

namespace Zarincode\Theme\Home;

defined( 'ABSPATH' ) || exit;

final class Home_Cache {

	/** Transient prefix shared by every cached homepage lookup. */
	private const PREFIX = 'zarincode_home_cache_';

	/** How long a lookup stays warm. */
	private const TTL = 12 * HOUR_IN_SECONDS;

	/** Registers the invalidation hooks. */
	public static function boot(): void {
		add_action( 'save_post', [ self::class, 'flush' ] );
		add_action( 'deleted_post', [ self::class, 'flush' ] );
		add_action( 'trashed_post', [ self::class, 'flush' ] );
		add_action( 'edited_term', [ self::class, 'flush' ] );
		add_action( 'switch_theme', [ self::class, 'flush' ] );
		// WooCommerce keeps its own rating meta; refresh on any product change.
		add_action( 'woocommerce_update_product', [ self::class, 'flush' ] );
		add_action( 'woocommerce_new_product', [ self::class, 'flush' ] );
		add_action( 'comment_post', [ self::class, 'flush' ] );
		add_action( 'transition_comment_status', [ self::class, 'flush' ] );
	}

	/**
	 * Returns a cached value, or builds and stores it.
	 *
	 * @param string   $key     Short cache key (no prefix).
	 * @param callable $builder Producer for a cold cache.
	 * @return mixed
	 */
	public static function remember( string $key, callable $builder ) {
		$name  = self::PREFIX . $key;
		$found = false;
		$value = wp_cache_get( $name, 'zarincode_home' );
		if ( false !== $value ) {
			return $value;
		}

		$stored = get_transient( $name );
		if ( is_array( $stored ) && array_key_exists( 'v', $stored ) ) {
			wp_cache_set( $name, $stored['v'], 'zarincode_home', self::TTL );
			return $stored['v'];
		}

		$value = $builder();
		set_transient( $name, [ 'v' => $value ], self::TTL );
		wp_cache_set( $name, $value, 'zarincode_home', self::TTL );

		unset( $found );
		return $value;
	}

	/** Drops every cached homepage lookup (content or settings changed). */
	public static function flush(): void {
		foreach ( [ 'title_index', 'avg_rating', 'brands' ] as $key ) {
			delete_transient( self::PREFIX . $key );
			wp_cache_delete( self::PREFIX . $key, 'zarincode_home' );
		}
	}
}
