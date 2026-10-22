<?php
namespace Zarincode\Runtime260\Features;
defined( 'ABSPATH' ) || exit;
final class Manager {
    /** Pure resolver: unknown/missing/circular/conflicting dependencies fail closed. */
    public static function resolve( string $id, array $features, array $options, array $available, array $trail = [] ): string {
        if ( ! isset( $features[$id] ) || ! is_array( $features[$id] ) ) { return 'unknown'; }
        if ( in_array( $id, $trail, true ) ) { return 'cycle'; }
        $f = $features[$id];
        $key = $f['settings'] ?? 'feature_' . $id;
        $requested = array_key_exists( $key, $options ) ? in_array( $options[$key], [ true, 1, '1', 'yes', 'on' ], true ) : (bool) ( $f['default'] ?? false );
        if ( ! $requested ) { return 'disabled'; }
        foreach ( $f['conflicts'] ?? [] as $other ) {
            if ( ! isset( $features[$other] ) ) { continue; }
            $peer = $features[$other]; $peer_key = $peer['settings'] ?? 'feature_' . $other;
            if ( array_key_exists( $peer_key, $options ) ? in_array( $options[$peer_key], [ true, 1, '1', 'yes', 'on' ], true ) : ! empty( $peer['default'] ) ) { return 'conflict'; }
        }
        foreach ( $f['plugins'] ?? [] as $plugin ) { if ( empty( $available[$plugin] ) ) { return 'dependency_missing'; } }
        $trail[] = $id;
        foreach ( $f['dependencies'] ?? [] as $dependency ) {
            $status = self::resolve( $dependency, $features, $options, $available, $trail );
            if ( 'enabled' !== $status ) { return 'cycle' === $status ? 'cycle' : 'dependency_disabled'; }
        }
        return 'enabled';
    }
    public static function status( string $id ): string {
        return self::resolve( $id, Registry::all(), (array) get_option( 'zarincode_opt', [] ), [ 'elementor' => defined( 'ELEMENTOR_VERSION' ) ] );
    }
    public static function enabled( string $id ): bool { return 'enabled' === self::status( $id ); }
    public static function invalidate( $old, $new ): void {
        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];
        $registry = Registry::all();
        $available = ['elementor'=>defined('ELEMENTOR_VERSION')];
        $changed = [];
        foreach ($registry as $id=>$feature) {
            $key=$feature['settings'];
            if (($old[$key]??$feature['default']) !== ($new[$key]??$feature['default']) || self::resolve($id,$registry,$old,$available)!==self::resolve($id,$registry,$new,$available)) {$changed[]=$id;}
        }
        if (!$changed) {return;}
        if (class_exists('\\Elementor\\Plugin')) {
            $plugin=\Elementor\Plugin::instance();
            if (isset($plugin->files_manager)) {$plugin->files_manager->clear_cache();}
        }
        foreach ($changed as $id) {do_action('zarincode_feature_cache_invalidated',$id);}
    }
}
