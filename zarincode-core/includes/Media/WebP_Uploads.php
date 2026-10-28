<?php
/** Convert successful local WordPress uploads before attachment insertion. */
namespace Zarincode\Runtime260\Media;

use Zarincode\Runtime260\Features\Manager;

defined( 'ABSPATH' ) || exit;

final class WebP_Uploads {
    public const INPUT_MIMES = [ 'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/tiff', 'image/avif', 'image/heic', 'image/heif' ];

    public static function boot(): void {
        // Core runs this hook for both wp_handle_upload and wp_handle_sideload.
        add_filter( 'wp_handle_upload', [ self::class, 'convert' ], 20, 2 );
        add_filter( 'wp_editor_set_quality', [ self::class, 'editor_quality' ], 20, 2 );
    }

    public static function settings(): array {
        $raw = get_option( 'zarincode_opt', [] );
        $raw = is_array( $raw ) ? $raw : [];
        $number = static function ( string $key, int $default, int $min, int $max ) use ( $raw ): int {
            return isset( $raw[$key] ) && is_scalar( $raw[$key] ) && is_numeric( $raw[$key] )
                ? max( $min, min( $max, (int) $raw[$key] ) ) : $default;
        };
        return [
            'enabled' => Manager::enabled( 'webp_uploads' ),
            'quality' => $number( 'webp_quality', 82, 50, 100 ),
            'max_bytes' => $number( 'webp_max_megabytes', 25, 1, 100 ) * 1048576,
            'max_pixels' => $number( 'webp_max_megapixels', 40, 1, 80 ) * 1000000,
            'only_smaller' => ! array_key_exists( 'webp_only_smaller', $raw ) || ! in_array( $raw['webp_only_smaller'], [ false, 0, '0', 'no', 'off' ], true ),
        ];
    }

    public static function editor_quality( $quality, $mime ) {
        $settings = self::settings();
        return $settings['enabled'] && 'image/webp' === $mime ? $settings['quality'] : $quality;
    }

    /** Read-only, path-free data for diagnostics. No upload/user data is stored. */
    public static function status(): array {
        return array_merge( self::settings(), [
            'webp_editor' => wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] ),
            'allowed_webp' => in_array( 'image/webp', get_allowed_mime_types(), true ),
            'multiframe_probe' => class_exists( '\Imagick' ),
            'scope' => 'New local WordPress uploads/sideloads; no retroactive conversion.',
        ] );
    }

    /** Report bounded reason codes, never paths or customer data. */
    private static function result( array $upload, string $reason ): array {
        try { do_action( 'zarincode_webp_result', $reason ); } catch ( \Throwable $ignored ) { /* Observability must not break an upload. */ }
        return $upload;
    }

    public static function convert( array $upload, string $context = 'upload' ): array {
        try { return self::convert_local( $upload, $context ); }
        catch ( \Throwable $error ) { return self::result( $upload, 'conversion_failed' ); }
    }

    private static function convert_local( array $upload, string $context ): array {
        $settings = self::settings();
        if ( ! $settings['enabled'] || ! empty( $upload['error'] ) || ! isset( $upload['file'], $upload['url'], $upload['type'] )
            || ! is_string( $upload['file'] ) || ! is_string( $upload['url'] ) || ! is_string( $upload['type'] ) ) { return $upload; }
        if ( ! apply_filters( 'zarincode_webp_upload_allowed', true, $upload, $context ) ) { return self::result( $upload, 'excluded' ); }
        // Streams, remote URLs and symlinks are not writable local upload candidates.
        if ( ! stream_is_local( $upload['file'] ) || is_link( $upload['file'] ) ) { return self::result( $upload, 'not_local' ); }
        $source = realpath( $upload['file'] );
        $directory = wp_get_upload_dir();
        $base = empty( $directory['error'] ) && isset( $directory['basedir'] ) ? realpath( $directory['basedir'] ) : false;
        if ( ! $source || ! $base || ! is_file( $source ) || ! is_readable( $source )
            || 0 !== strpos( wp_normalize_path( $source ), trailingslashit( wp_normalize_path( $base ) ) )
            || ! is_writable( dirname( $source ) ) ) { return self::result( $upload, 'outside_uploads' ); }
        $relative = substr( wp_normalize_path( $source ), strlen( trailingslashit( wp_normalize_path( $base ) ) ) );
        if ( ! isset( $directory['baseurl'] ) || ! is_string( $directory['baseurl'] )
            || rawurldecode( $upload['url'] ) !== trailingslashit( $directory['baseurl'] ) . $relative ) { return self::result( $upload, 'remote_storage' ); }
        $bytes = filesize( $source );
        if ( ! $bytes || $bytes > $settings['max_bytes'] ) { return self::result( $upload, 'byte_limit' ); }
        $mime = wp_get_image_mime( $source );
        if ( 'image/webp' === $mime ) { return self::result( $upload, 'already_webp' ); }
        if ( ! in_array( $mime, self::INPUT_MIMES, true ) || $upload['type'] !== $mime ) { return self::result( $upload, 'unsupported_input' ); }
        if ( ! in_array( 'image/webp', get_allowed_mime_types(), true )
            || ! wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] ) ) { return self::result( $upload, 'webp_unavailable' ); }
        $size = wp_getimagesize( $source );
        if ( ! $size || empty( $size[0] ) || empty( $size[1] ) || $size[0] > $settings['max_pixels'] / $size[1] ) { return self::result( $upload, 'pixel_limit' ); }
        // Conservative PHP-heap estimate before a decoder allocates a pixel buffer.
        wp_raise_memory_limit( 'image' );
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        if ( $memory_limit > 0 && ( $size[0] * $size[1] * 8 + $bytes * 2 + 16777216 ) > $memory_limit - memory_get_usage( true ) ) { return self::result( $upload, 'memory_limit' ); }
        if ( ! Animation_Safety::single_frame( $source, $mime ) ) { return self::result( $upload, 'animated_or_unverified' ); }

        $target = null;
        try {
            $editor = wp_get_image_editor( $source );
            if ( is_wp_error( $editor ) ) { return self::result( $upload, 'decoder_unavailable' ); }
            if ( 'image/jpeg' === $mime && is_callable( [ $editor, 'maybe_exif_rotate' ] ) && is_wp_error( $editor->maybe_exif_rotate() ) ) { return self::result( $upload, 'orientation_failed' ); }
            $expected = $editor->get_size();
            if ( ! is_array( $expected ) || empty( $expected['width'] ) || empty( $expected['height'] ) ) { return self::result( $upload, 'invalid_dimensions' ); }
            // GD cannot encode indexed PNG/GIF directly as WebP. A full-frame,
            // same-size copy through WP's public API normalizes the pixel buffer
            // to truecolor without changing dimensions or cutting content.
            if ( $editor instanceof \WP_Image_Editor_GD && in_array( $mime, [ 'image/png', 'image/gif' ], true )
                && is_wp_error( $editor->crop( 0, 0, $expected['width'], $expected['height'], $expected['width'], $expected['height'] ) ) ) { return self::result( $upload, 'pixel_buffer_failed' ); }
            if ( is_wp_error( $editor->set_quality( $settings['quality'] ) ) ) { return self::result( $upload, 'quality_failed' ); }
            // Reserve exclusively: same-named JPEG/PNG uploads must not overwrite
            // each other, existing WebP files or another request's reservation.
            $name = pathinfo( $source, PATHINFO_FILENAME ) . '.webp';
            for ( $attempt = 0; $attempt < 5; ++$attempt ) {
                $unique = wp_unique_filename( dirname( $source ), $name );
                if ( ! is_string( $unique ) || false !== strpbrk( $unique, "/\\" ) || 'webp' !== strtolower( pathinfo( $unique, PATHINFO_EXTENSION ) ) ) { return self::result( $upload, 'invalid_filename' ); }
                $candidate = dirname( $source ) . '/' . $unique;
                $reservation = @fopen( $candidate, 'x' );
                if ( $reservation ) { fclose( $reservation ); $target = $candidate; break; }
            }
            if ( ! $target ) { return self::result( $upload, 'reservation_failed' ); }
            $saved = $editor->save( $target, 'image/webp' );
            clearstatcache( true, $target );
            if ( is_wp_error( $saved ) || ! is_array( $saved ) || ! isset( $saved['path'] ) || ! is_string( $saved['path'] )
                || realpath( $saved['path'] ) !== realpath( $target ) || ! is_file( $target ) || ! filesize( $target )
                || 'image/webp' !== wp_get_image_mime( $target ) ) { return self::result( $upload, 'invalid_output' ); }
            $actual = wp_getimagesize( $target );
            if ( ! $actual || (int) $actual[0] !== (int) $expected['width'] || (int) $actual[1] !== (int) $expected['height'] ) { return self::result( $upload, 'dimension_mismatch' ); }
            // Header checks alone do not prove that all pixel data can be read.
            // Release the source buffer before decoding the finished output.
            $editor = null;
            $verification = wp_get_image_editor( $target );
            if ( is_wp_error( $verification ) ) { return self::result( $upload, 'output_decode_failed' ); }
            $verified_size = $verification->get_size();
            $verification = null;
            if ( ! is_array( $verified_size ) || (int) ( $verified_size['width'] ?? 0 ) !== (int) $expected['width']
                || (int) ( $verified_size['height'] ?? 0 ) !== (int) $expected['height'] ) { return self::result( $upload, 'dimension_mismatch' ); }
            if ( $settings['only_smaller'] && filesize( $target ) >= $bytes ) { return self::result( $upload, 'not_smaller' ); }
            // Commit last. If WP's deletion hook preserves the original, leave
            // the attachment untouched and discard our new output instead.
            try { wp_delete_file( $source ); } catch ( \Throwable $ignored ) { /* Check the actual outcome before committing. */ }
            clearstatcache( true, $source );
            if ( file_exists( $source ) ) { return self::result( $upload, 'source_retained' ); }
            $upload['file'] = $target;
            $upload['url'] = substr( $upload['url'], 0, strrpos( $upload['url'], '/' ) + 1 ) . wp_basename( $target );
            $upload['type'] = 'image/webp';
            $target = null; // Ownership transfers to WordPress attachment lifecycle.
            return self::result( $upload, 'converted' );
        } catch ( \Throwable $error ) {
            return self::result( $upload, 'conversion_failed' );
        } finally {
            // This exact path was exclusively reserved by this invocation. Do not
            // let a global delete filter leave failed reservations behind.
            if ( $target && is_file( $target ) ) { @unlink( $target ); }
        }
    }
}
