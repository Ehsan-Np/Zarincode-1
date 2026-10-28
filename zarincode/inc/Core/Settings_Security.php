<?php
/** Security boundaries for every writer of the shared options store, including JSON and Redux. */
namespace Zarincode\Theme\Core;
defined( 'ABSPATH' ) || exit;
final class Settings_Security {
    public static function boot(): void {
        add_filter( 'pre_update_option_zarincode_opt', [ self::class, 'sanitize' ], 20, 2 );
        add_action( 'updated_option', [ self::class, 'invalidate' ], 10, 3 );
    }
    public static function sanitize( $value, $old ) {
        if ( ! is_array( $value ) ) { return is_array( $old ) ? $old : []; }
        $old = is_array( $old ) ? $old : [];
        // Other plugins' unknown keys are preserved. Only this theme's declared scalar fields are normalized.
        foreach ( \Zarincode\Theme\Redux\OptionsConfig::get_sections_schema() as $section ) {
            foreach ( $section['fields'] as $field ) {
                $key = $field['id'];
                if ( ! array_key_exists( $key, $value ) ) { continue; }
                if ( ! is_scalar( $value[$key] ) && null !== $value[$key] ) {
                    $previous = $old[$key] ?? $field['default'];
                    $value[$key] = is_scalar( $previous ) ? $previous : $field['default'];
                }
                if ( 'select' === $field['type'] && ! array_key_exists( (string) $value[$key], $field['options'] ?? [] ) ) {
                    $value[$key] = $field['default'];
                }
                $fallback = $field['default'];
                if ( 'color' === $field['type'] ) {
                    $value[$key] = sanitize_hex_color( (string) $value[$key] ) ?: $fallback;
                } elseif ( 'media' === $field['type'] ) {
                    $value[$key] = esc_url_raw( (string) $value[$key], [ 'http', 'https' ] );
                } elseif ( 'number' === $field['type'] && ! isset( Layout::RANGES[$key] ) ) {
                    $ranges = [ 'logo_width' => [32, 400], 'body_font_size' => [12, 24], 'glass_blur_px' => [0, 30], 'portfolio_per_page' => [1, 48], 'shop_per_page' => [1, 96] ];
                    [ $min, $max ] = $ranges[$key] ?? [ (int) ($field['min'] ?? 0), (int) ($field['max'] ?? 1000) ];
                    $value[$key] = is_numeric( $value[$key] ) ? max( $min, min( $max, (int) $value[$key] ) ) : $fallback;
                } elseif ( 'body_line_height' === $key ) {
                    $value[$key] = is_numeric( $value[$key] ) ? (string) max( 1.2, min( 2.8, (float) $value[$key] ) ) : $fallback;
                } elseif ( 'text' === $field['type'] ) {
                    $value[$key] = sanitize_text_field( (string) $value[$key] );
                } elseif ( 'textarea' === $field['type'] && ! in_array( $key, [ 'footer_enamad_html', 'footer_samandehi_html', 'footer_zarinpal_html', 'footer_gateway_html' ], true ) ) {
                    $value[$key] = sanitize_textarea_field( (string) $value[$key] );
                }
                if ( 'switch' === $field['type'] ) {
                    $value[$key] = in_array( $value[$key], [ true, 1, '1', 'yes', 'on' ], true );
                }
            }
        }
        foreach ( [ 'custom_head_js', 'custom_footer_js' ] as $key ) {
            if ( array_key_exists( $key, $value ) && ! current_user_can( 'unfiltered_html' ) ) {
                // Preserve trusted existing snippets; a restricted writer cannot replace them through import/API.
                $value[$key] = is_string( $old[$key] ?? null ) ? $old[$key] : '';
            }
        }
        if ( isset( $value['custom_css'] ) ) { $value['custom_css'] = self::css( (string) $value['custom_css'] ); }
        return Layout::normalize( $value );
    }
    public static function css( string $css ): string {
        // This is an HTML style element, not a CSS-security sandbox. Remove all literal markup delimiters.
        return str_replace( [ '<', '>' ], '', $css );
    }
    public static function invalidate( string $key, $old, $new ): void {
        if ( 'zarincode_opt' !== $key ) { return; }
        $GLOBALS['zarincode_opt'] = is_array( $new ) ? $new : [];
        do_action( 'zarincode_settings_changed', $new, $old );
    }
}
