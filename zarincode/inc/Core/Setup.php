<?php
/**
 * Theme Core Setup, Supports, Menus, Sidebars & RTL/WooCommerce Configuration
 *
 * @package Zarincode\Theme\Core
 */

namespace Zarincode\Theme\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Setup {

    private static ?Setup $instance = null;

    public static function instance(): Setup {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
        add_action( 'widgets_init', [ $this, 'register_sidebars' ] );
        add_filter('woocommerce_single_product_image_gallery_classes',static fn(array $classes):array=>array_merge($classes,[is_rtl()?'zc-gallery-rtl':'zc-gallery-ltr']));
        add_filter( 'body_class', [ $this, 'body_classes' ] );
        add_filter( 'language_attributes', [ $this, 'force_rtl_attributes' ], 20 );
        add_filter( 'template_include', [ $this, 'enforce_custom_single_templates' ], 99 );
        add_action( 'init', [ $this, 'woo_toman_currency' ], 5 );
    }

    /**
     * Ensure Single Product, Single Service, and Single Project always use Zarincode's dedicated templates
     * instead of WooCommerce's unstyled default single-product.php
     */
    public function enforce_custom_single_templates( string $template ): string {
        if ( is_singular( 'product' ) ) {
            $custom = get_template_directory() . '/single-product.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( is_singular( 'zc_service' ) ) {
            $custom = get_template_directory() . '/single-zc_service.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( is_singular( 'zc_project' ) ) {
            $custom = get_template_directory() . '/single-zc_project.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
            $custom = get_template_directory() . '/woocommerce.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public function setup_theme(): void {
        load_theme_textdomain( 'zarincode', ZARINCODE_DIR . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_image_size('zarincode-card',960,600,true);
        add_image_size('zarincode-avatar',160,160,true);
        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'align-wide' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support(
            'html5',
            [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ]
        );
        add_theme_support(
            'custom-logo',
            [
                'height'      => 80,
                'width'       => 240,
                'flex-height' => true,
                'flex-width'  => true,
            ]
        );

        // WooCommerce Marketplace Support
        add_theme_support( 'woocommerce' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );

        // Register Navigation Menus
        register_nav_menus(
            [
                'primary'   => esc_html__( 'منوی اصلی هدر (Pill Navigation)', 'zarincode' ),
                'legal_nav' => esc_html__( 'منوی قوانین و مقررات (فوتر و سایدبار حقوقی)', 'zarincode' ),
                'footer_1'  => esc_html__( 'دسترسی سریع فوتر', 'zarincode' ),
            ]
        );

        // Set global content width (ThemeForest standard)
        if ( ! isset( $GLOBALS['content_width'] ) ) {
            $GLOBALS['content_width'] = 1440;
        }
    }

    public function register_sidebars(): void {
        register_sidebar(
            [
                'name'          => esc_html__( 'سایدبار وبلاگ زرین کد', 'zarincode' ),
                'id'            => 'sidebar-blog',
                'description'   => esc_html__( 'ابزارک‌های ستون کناری وبلاگ', 'zarincode' ),
                'before_widget' => '<div id="%1$s" class="zc-glass-panel widget %2$s" style="padding:1.5rem;margin-bottom:1.5rem;">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="widget-title" style="margin-top:0;font-weight:800;font-size:1.05rem;">',
                'after_title'   => '</h4>',
            ]
        );

        register_sidebar(
            [
                'name'          => esc_html__( 'سایدبار فروشگاه محصولات دیجیتال', 'zarincode' ),
                'id'            => 'sidebar-shop',
                'description'   => esc_html__( 'فیلترها و ابزارک‌های فروشگاه قالب، افزونه و سورس‌کد', 'zarincode' ),
                'before_widget' => '<div id="%1$s" class="zc-glass-panel widget %2$s" style="padding:1.5rem;margin-bottom:1.5rem;">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="widget-title" style="margin-top:0;font-weight:800;font-size:1.05rem;">',
                'after_title'   => '</h4>',
            ]
        );
    }

    public function body_classes( array $classes ): array {
        $classes[] = 'zarincode-theme';
        $classes[] = 'crextio-sunshine-ui';
        if ( is_rtl() ) { $classes[] = 'rtl'; }
        return $classes;
    }

    public function force_rtl_attributes( string $output ): string {
        if ( is_admin() ) {
            return $output;
        }
        // Persian-first theme: frontend is always RTL regardless of site locale,
        // matching the unconditionally-enqueued rtl.css. Keeps layouts correct even
        // on installs that never activated the fa_IR language pack.
        if ( strpos( $output, 'dir=' ) !== false ) {
            $output = preg_replace( '/dir="[^"]*"/', 'dir="rtl"', $output );
        } else {
            $output .= ' dir="rtl"';
        }
        if ( strpos( $output, 'lang=' ) === false ) {
            $output = 'lang="fa-IR" ' . $output;
        }
        return $output;
    }

    /** WooCommerce: Persian Toman currency (symbol + zero decimals). */
    public function woo_toman_currency(): void {
        add_filter( 'woocommerce_currency_options', static function ( $currencies ) {
            $currencies['TOMAN'] = 'تومان';
            return $currencies;
        } );
        add_filter( 'woocommerce_currency_symbol', static function ( $symbol, $currency ) {
            return 'TOMAN' === $currency ? 'تومان' : $symbol;
        }, 10, 2 );
        add_filter( 'wc_get_price_decimals', static function ( $decimals ) {
            return 'TOMAN' === (string) get_option( 'woocommerce_currency', '' ) ? 0 : $decimals;
        } );
    }

    /** Gregorian timestamp -> Persian Jalali date ("۲ آبان ۱۴۰۵"). Port of jalaali-js (MIT), verified vs reference. */
    public static function jalali_date( int $timestamp, string $format = 'j F Y' ): string {
        static $months = [ 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' ];
        $utc = ( new \DateTime( '@' . $timestamp ) )->setTimezone( new \DateTimeZone( 'UTC' ) );
        $gy  = (int) $utc->format( 'Y' );
        $gm  = (int) $utc->format( 'n' );
        $gd  = (int) $utc->format( 'j' );

        $div = static fn( int $a, int $b ): int => intdiv( $a, $b );
        $mod = static fn( int $a, int $b ): int => $a - intdiv( $a, $b ) * $b;
        $g2d = static function ( int $gy, int $gm, int $gd ) use ( $div, $mod ): int {
            $d  = $div( ( $gy + $div( $gm - 8, 6 ) + 100100 ) * 1461, 4 ) + $div( 153 * $mod( $gm + 9, 12 ) + 2, 5 ) + $gd - 34840408;
            $d -= $div( $div( $gy + 100100 + $div( $gm - 8, 6 ), 100 ) * 3, 4 ) - 752;
            return $d;
        };
        $d2g = static function ( int $jdn ) use ( $div, $mod ): array {
            $j  = 4 * $jdn + 139361631;
            $j  = $j + $div( $div( 4 * $jdn + 183187720, 146097 ) * 3, 4 ) * 4 - 3908;
            $i  = $div( $mod( $j, 1461 ), 4 ) * 5 + 308;
            $gd = $div( $mod( $i, 153 ), 5 ) + 1;
            $gm = $mod( $div( $i, 153 ), 12 ) + 1;
            $gy = $div( $j, 1461 ) - 100100 + $div( 8 - $gm, 6 );
            return [ $gy, $gm, $gd ];
        };
        $jal_cal = static function ( int $jy, bool $without_leap ) use ( $div, $mod ): ?array {
            $breaks = [ -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178 ];
            $bl     = count( $breaks );
            $gy     = $jy + 621;
            $leap_j = -14;
            $jp     = $breaks[0];
            $jump   = 0;
            if ( $jy < $jp || $jy >= $breaks[ $bl - 1 ] ) {
                return null;
            }
            for ( $i = 1; $i < $bl; $i++ ) {
                $jm   = $breaks[ $i ];
                $jump = $jm - $jp;
                if ( $jy < $jm ) {
                    break;
                }
                $leap_j = $leap_j + $div( $jump, 33 ) * 8 + $div( $mod( $jump, 33 ), 4 );
                $jp     = $jm;
            }
            $n       = $jy - $jp;
            $leap_j  = $leap_j + $div( $n, 33 ) * 8 + $div( $mod( $n, 33 ) + 3, 4 );
            if ( 4 === $mod( $jump, 33 ) && 4 === $jump - $n ) {
                $leap_j++;
            }
            $leap_g = $div( $gy, 4 ) - $div( ( $div( $gy, 100 ) + 1 ) * 3, 4 ) - 150;
            $march = 20 + $leap_j - $leap_g;
            if ( $without_leap ) {
                return [ 'gy' => $gy, 'march' => $march ];
            }
            if ( $jump - $n < 6 ) {
                $n = $n - $jump + $div( $jump + 4, 33 ) * 33;
            }
            $leap = $mod( $mod( $n + 1, 33 ) - 1, 4 );
            if ( -1 === $leap ) {
                $leap = 4;
            }
            return [ 'leap' => $leap, 'gy' => $gy, 'march' => $march ];
        };

        $jdn  = $g2d( $gy, $gm, $gd );
        $gy   = $d2g( $jdn )[0];
        $jy   = $gy - 621;
        $r    = $jal_cal( $jy, false );
        if ( null === $r ) {
            return gmdate( 'Y/m/d', $timestamp );
        }
        $k = $jdn - $g2d( $gy, 3, $r['march'] );
        if ( $k >= 0 ) {
            if ( $k <= 185 ) {
                $jm = 1 + $div( $k, 31 );
                $jd = $mod( $k, 31 ) + 1;
            } else {
                $k -= 186;
                $jm = 7 + $div( $k, 30 );
                $jd = $mod( $k, 30 ) + 1;
            }
        } else {
            $jy -= 1;
            $k  += 179;
            if ( 1 === $r['leap'] ) {
                $k++;
            }
            $jm = 7 + $div( $k, 30 );
            $jd = $mod( $k, 30 ) + 1;
        }
        $fa = static function ( $n ): string {
            return strtr( (string) $n, array_combine( range( 0, 9 ), [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ] ) );
        };
        return strtr( $format, [ 'Y' => $fa( $jy ), 'F' => $months[ $jm - 1 ], 'j' => $fa( $jd ), 'n' => $fa( $jm ) ] );
    }
}
