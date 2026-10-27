<?php
/**
 * Fail-closed feature resolution and narrowly scoped cache invalidation.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Features;

defined( 'ABSPATH' ) || exit;
/**
 * Resolve filtered feature definitions without trusting their shape.
 *
 * @phpstan-type Definition array{settings:string,default:bool,dependencies:list<string>,plugins:list<string>,conflicts:list<string>,cache_groups:list<string>}
 */
final class Manager {
	/**
	 * Normalize only the resolver's fields. Unknown metadata remains owned by the registry.
	 *
	 * @param string $id Feature ID.
	 * @param mixed  $raw Filtered definition.
	 * @return Definition|null
	 */
	private static function definition( string $id, $raw ): ?array {
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$key     = array_key_exists( 'settings', $raw ) ? $raw['settings'] : 'feature_' . $id;
		$default = array_key_exists( 'default', $raw ) ? $raw['default'] : false;
		if ( ! is_string( $key ) || '' === $key || ! is_bool( $default ) ) {
			return null;
		}
		$lists = array();
		foreach ( array( 'dependencies', 'plugins', 'conflicts', 'cache_groups' ) as $field ) {
			$value = array_key_exists( $field, $raw ) ? $raw[ $field ] : array();
			if ( ! is_array( $value ) ) {
				return null;
			}
			$list = array();
			foreach ( $value as $entry ) {
				if ( ! is_string( $entry ) || '' === $entry ) {
					return null;
				}$list[] = $entry;}
			$lists[ $field ] = $list;
		}
		return array(
			'settings'     => $key,
			'default'      => $default,
			'dependencies' => $lists['dependencies'],
			'plugins'      => $lists['plugins'],
			'conflicts'    => $lists['conflicts'],
			'cache_groups' => $lists['cache_groups'],
		);
	}
	/**
	 * Resolve missing, malformed, circular or unavailable dependencies without enabling them.
	 *
	 * @param string                 $id Requested feature.
	 * @param array<array-key,mixed> $features Filtered registry.
	 * @param array<array-key,mixed> $options Existing shared options.
	 * @param array<array-key,mixed> $available Available plugin capabilities.
	 * @param array                  $trail Ancestor feature IDs.
	 * @phpstan-param list<string> $trail
	 * @return string Machine-readable state.
	 */
	public static function resolve( string $id, array $features, array $options, array $available, array $trail = array() ): string {
		if ( ! array_key_exists( $id, $features ) ) {
			return 'unknown';
		}
		if ( in_array( $id, $trail, true ) ) {
			return 'cycle';
		}
		$feature = self::definition( $id, $features[ $id ] );
		if ( null === $feature ) {
			return 'invalid_definition';
		}
		if ( ! self::requested( $feature, $options ) ) {
			return 'disabled';
		}
		foreach ( $feature['conflicts'] as $other ) {
			if ( ! array_key_exists( $other, $features ) ) {
				return 'invalid_definition';
			}
			$peer = self::definition( $other, $features[ $other ] );
			if ( null === $peer ) {
				return 'invalid_definition';
			}
			if ( self::requested( $peer, $options ) ) {
				return 'conflict';
			}
		}
		foreach ( $feature['plugins'] as $plugin ) {
			if ( empty( $available[ $plugin ] ) ) {
				return 'dependency_missing';
			}
		}
		$trail[] = $id;
		foreach ( $feature['dependencies'] as $dependency ) {
			$status = self::resolve( $dependency, $features, $options, $available, $trail );
			if ( 'enabled' !== $status ) {
				return 'cycle' === $status ? 'cycle' : 'dependency_disabled';
			}
		}
		return 'enabled';
	}
	/**
	 * Compare option values semantically, not by PHP scalar representation.
	 *
	 * @param array<string,mixed>    $feature Normalized definition.
	 * @param array<array-key,mixed> $options Shared settings.
	 * @phpstan-param Definition $feature
	 * @return bool Whether requested.
	 */
	private static function requested( array $feature, array $options ): bool {
		return array_key_exists( $feature['settings'], $options ) ? in_array( $options[ $feature['settings'] ], array( true, 1, '1', 'yes', 'on' ), true ) : $feature['default'];
	}
	/**
	 * Get the effective state from the shared store.
	 *
	 * @param string $id Feature ID.
	 * @return string Effective state.
	 */
	public static function status( string $id ): string {
		return self::resolve( $id, Registry::all(), (array) get_option( 'zarincode_opt', array() ), array( 'elementor' => defined( 'ELEMENTOR_VERSION' ) ) );
	}
	/**
	 * Expose a fail-closed boolean to service bootstraps.
	 *
	 * @param string $id Feature ID.
	 * @return bool Whether enabled.
	 */
	public static function enabled( string $id ): bool {
		return 'enabled' === self::status( $id );}
	/**
	 * Clear only cache groups whose effective feature state changed.
	 *
	 * @param mixed $old Previous shared option value.
	 * @param mixed $new_options New shared option value.
	 * @return void
	 */
	public static function invalidate( $old, $new_options ): void {
		$old         = is_array( $old ) ? $old : array();
		$new_options = is_array( $new_options ) ? $new_options : array();
		$registry    = Registry::all();
		$available   = array( 'elementor' => defined( 'ELEMENTOR_VERSION' ) );
		$changed     = array();
		$css         = false;
		foreach ( $registry as $id => $raw ) {
			if ( ! is_string( $id ) ) {
				continue;
			}
			if ( self::resolve( $id, $registry, $old, $available ) === self::resolve( $id, $registry, $new_options, $available ) ) {
				continue;
			}
			$changed[] = $id;
			$feature   = self::definition( $id, $raw );
			if ( null !== $feature && in_array( 'elementor-css', $feature['cache_groups'], true ) ) {
				$css = true;
			}
		}
		if ( $css && class_exists( '\\Elementor\\Plugin' ) ) {
			$plugin = \Elementor\Plugin::instance();
			if ( isset( $plugin->files_manager ) ) {
				$plugin->files_manager->clear_cache();
			}
		}
		foreach ( $changed as $id ) {
			do_action( 'zarincode_feature_cache_invalidated', $id );
		}
	}
}
