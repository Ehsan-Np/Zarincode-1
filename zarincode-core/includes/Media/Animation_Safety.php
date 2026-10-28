<?php
/** Fail closed when an upload may contain animation or multiple pages. */
namespace Zarincode\Runtime260\Media;

defined( 'ABSPATH' ) || exit;

final class Animation_Safety {
    public static function single_frame( string $file, string $mime ): bool {
        if ( in_array( $mime, [ 'image/bmp', 'image/x-ms-bmp' ], true ) ) {
            return true;
        }
        if ( in_array( $mime, [ 'image/jpeg', 'image/gif', 'image/png' ], true ) ) {
            $stream = @fopen( $file, 'rb' );
            if ( ! $stream ) { return false; }
            try {
                if ( 'image/jpeg' === $mime ) { return self::jpeg( $stream ); }
                return 'image/gif' === $mime ? self::gif( $stream ) : self::png( $stream );
            } catch ( \Throwable $error ) {
                return false;
            } finally {
                fclose( $stream );
            }
        }
        // TIFF/HEIF/AVIF can contain several pages/frames. GD alone cannot prove
        // that converting them will not silently flatten the source.
        if ( ! class_exists( '\Imagick' ) ) { return false; }
        $probe = null;
        try {
            $probe = new \Imagick();
            $probe->pingImage( $file );
            return 1 === $probe->getNumberImages();
        } catch ( \Throwable $error ) {
            return false;
        } finally {
            if ( $probe ) { $probe->clear(); }
        }
    }

    private static function skip( $stream, int $length ): bool {
        $size = fstat( $stream );
        $position = ftell( $stream );
        return $length >= 0 && false !== $position && is_array( $size )
            && $length <= $size['size'] - $position && 0 === fseek( $stream, $length, SEEK_CUR );
    }

    private static function subblocks( $stream ): bool {
        for ( $blocks = 0; $blocks < 100000; ++$blocks ) {
            $byte = fread( $stream, 1 );
            if ( '' === $byte || false === $byte ) { return false; }
            $length = ord( $byte );
            if ( 0 === $length ) { return true; }
            if ( ! self::skip( $stream, $length ) ) { return false; }
        }
        return false;
    }

    private static function gif( $stream ): bool {
        $header = fread( $stream, 13 );
        if ( false === $header || 13 !== strlen( $header ) || ! in_array( substr( $header, 0, 6 ), [ 'GIF87a', 'GIF89a' ], true ) ) { return false; }
        $packed = ord( $header[10] );
        if ( ( $packed & 128 ) && ! self::skip( $stream, 3 * ( 1 << ( ( $packed & 7 ) + 1 ) ) ) ) { return false; }
        $frames = 0;
        for ( $blocks = 0; $blocks < 100000; ++$blocks ) {
            $byte = fread( $stream, 1 );
            if ( ';' === $byte ) { return 1 === $frames; }
            if ( "\x21" === $byte ) {
                if ( ! self::skip( $stream, 1 ) || ! self::subblocks( $stream ) ) { return false; }
            } elseif ( "\x2c" === $byte ) {
                if ( ++$frames > 1 ) { return false; }
                $descriptor = fread( $stream, 9 );
                if ( false === $descriptor || 9 !== strlen( $descriptor ) ) { return false; }
                $packed = ord( $descriptor[8] );
                if ( ( $packed & 128 ) && ! self::skip( $stream, 3 * ( 1 << ( ( $packed & 7 ) + 1 ) ) ) ) { return false; }
                if ( ! self::skip( $stream, 1 ) || ! self::subblocks( $stream ) ) { return false; }
            } else {
                return false;
            }
        }
        return false;
    }

    /** MPO stores a multi-picture index in JPEG APP2; do not flatten it. */
    private static function jpeg( $stream ): bool {
        if ( "\xff\xd8" !== fread( $stream, 2 ) ) { return false; }
        for ( $segments = 0; $segments < 4096; ++$segments ) {
            if ( "\xff" !== fread( $stream, 1 ) ) { return false; }
            for ( $fill = 0; $fill < 4096; ++$fill ) {
                $marker = fread( $stream, 1 );
                if ( "\xff" !== $marker ) { break; }
            }
            if ( ! is_string( $marker ) || 1 !== strlen( $marker ) || "\xff" === $marker ) { return false; }
            $marker = ord( $marker );
            if ( 1 === $marker ) { continue; }
            if ( 0 === $marker || ( $marker >= 0xd0 && $marker <= 0xd9 ) ) { return false; }
            $length = fread( $stream, 2 );
            if ( ! is_string( $length ) || 2 !== strlen( $length ) ) { return false; }
            $length = unpack( 'nlength', $length )['length'] - 2;
            if ( $length < 0 ) { return false; }
            if ( 0xe2 === $marker && $length >= 4 ) {
                if ( "MPF\x00" === fread( $stream, 4 ) ) { return false; }
                $length -= 4;
            }
            if ( ! self::skip( $stream, $length ) ) { return false; }
            if ( 0xda === $marker ) { return true; }
        }
        return false;
    }

    private static function png( $stream ): bool {
        if ( "\x89PNG\r\n\x1a\n" !== fread( $stream, 8 ) ) { return false; }
        $has_data = false;
        for ( $chunks = 0; $chunks < 100000; ++$chunks ) {
            $header = fread( $stream, 8 );
            if ( false === $header || 8 !== strlen( $header ) ) { return false; }
            $length = unpack( 'Nlength', substr( $header, 0, 4 ) )['length'];
            $type = substr( $header, 4, 4 );
            if ( 'acTL' === $type ) { return false; }
            if ( ! self::skip( $stream, $length + 4 ) ) { return false; }
            if ( 'IDAT' === $type ) { $has_data = true; }
            if ( 'IEND' === $type ) { return $has_data && 0 === $length; }
        }
        return false;
    }
}
