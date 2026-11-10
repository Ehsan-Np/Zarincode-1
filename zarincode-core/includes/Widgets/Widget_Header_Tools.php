<?php
/**
 * Header tools — search, cart and the light/dark switch, as native elements.
 *
 * These three controls used to exist only in the theme's coded header
 * (`header.php`), which is the last-resort fallback: an Elementor-built header
 * could not contain them at all. They are widgets now, so an owner who wants
 * them in the Elementor header drags them into the header container and can
 * then move, restyle or delete them like any other element:
 *
 *   zarincode_header_search  → opens the site search modal (Ctrl/⌘ + K)
 *   zarincode_header_cart    → WooCommerce cart link + live item count
 *   zarincode_header_theme   → light/dark switch (html.dark)
 *
 * The defaults of the shipped header (see `Elementor\HF_Builder::header_tree`)
 * do not include them: the approved 4.15.0 pill is sized for brand + menu +
 * account + CTA + hamburger, and adding three more buttons at 1440px would
 * push the menu out of the pill. They are one drag away, fully styled.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Controls_Manager as C;

defined( 'ABSPATH' ) || exit;

/**
 * Shared look for the three round buttons of the header.
 */
abstract class Header_Tool_Widget extends Studio_Widget {

    public function get_style_depends(): array {
        return array( 'zarincode-studio', 'zarincode-hf-header' );
    }

    public function get_script_depends(): array {
        return array();
    }

    /** The size/colour block every tool button shares. */
    protected function skin_controls( string $selector ): void {
        $this->start_controls_section( 'look', array( 'label' => 'اندازه و رنگ', 'tab' => C::TAB_STYLE ) );

        $this->add_responsive_control(
            'circle_size',
            array(
                'label'     => 'اندازهٔ دکمه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 28, 'max' => 60 ) ),
                'default'   => array( 'size' => 40, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} ' . $selector => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'icon_size',
            array(
                'label'     => 'اندازهٔ آیکون',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 10, 'max' => 30 ) ),
                'selectors' => array( '{{WRAPPER}} ' . $selector . ' > :is(i, svg)' => 'font-size:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->field(
            'icon_color',
            'رنگ آیکون',
            C::COLOR,
            '#2A2C31',
            array( 'selectors' => array( '{{WRAPPER}} ' . $selector => 'color:{{VALUE}};' ) )
        );

        $this->field(
            'background_color',
            'پس‌زمینه',
            C::COLOR,
            '#FFFFFF',
            array( 'selectors' => array( '{{WRAPPER}} ' . $selector => 'background:{{VALUE}};border-color:{{VALUE}};' ) )
        );

        $this->end_controls_section();
    }
}

final class Widget_Header_Search extends Header_Tool_Widget {

    public function get_name(): string {
        return 'zarincode_header_search';
    }

    public function get_title(): string {
        return 'جستجوی زنده (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-search';
    }

    public function get_keywords(): array {
        return array( 'search', 'جستجو', 'هدر' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'جستجو' ) );
        $this->field( 'icon', 'آیکون', C::ICONS, array( 'value' => 'fa-solid fa-magnifying-glass', 'library' => 'fa-solid' ) );
        $this->field( 'label', 'برچسب دسترس‌پذیری', C::TEXT, 'جستجوی سریع خدمات و محصولات' );
        $this->field(
            'modal_id',
            'شناسهٔ پنجرهٔ جستجو',
            C::TEXT,
            'zc-search-modal',
            array( 'description' => 'قالب پنجرهٔ جستجو را با این شناسه چاپ می‌کند؛ فقط اگر آن را جابه‌جا کرده‌اید تغییر دهید.' )
        );
        $this->end_controls_section();
        $this->skin_controls( '.zc-header-search' );
    }

    protected function render(): void {
        $s     = $this->get_settings_for_display();
        $modal = trim( (string) ( $s['modal_id'] ?? '' ) ) ?: 'zc-search-modal';
        printf(
            '<button type="button" class="zc-icon-btn zc-header-search" data-zc-modal-open="%1$s" aria-haspopup="dialog" aria-controls="%1$s" title="%2$s (Ctrl + K)" aria-label="%2$s">%3$s</button>',
            esc_attr( $modal ),
            esc_attr( trim( (string) ( $s['label'] ?? '' ) ) ?: 'جستجو' ),
            Icon_Compatibility::render( $s['icon'] ?? array() )
        );
    }
}

final class Widget_Header_Cart extends Header_Tool_Widget {

    public function get_name(): string {
        return 'zarincode_header_cart';
    }

    public function get_title(): string {
        return 'سبد خرید با شمارشگر (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-cart-light';
    }

    public function get_keywords(): array {
        return array( 'cart', 'woocommerce', 'سبد', 'خرید', 'هدر' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'سبد خرید' ) );
        $this->field( 'icon', 'آیکون', C::ICONS, array( 'value' => 'fa-solid fa-bag-shopping', 'library' => 'fa-solid' ) );
        $this->field( 'label', 'برچسب دسترس‌پذیری', C::TEXT, 'سبد خرید محصولات آماده' );
        $this->field( 'show_count', 'نمایش شمارشگر', C::SWITCHER, 'yes' );
        $this->field( 'show_zero', 'نمایش «۰» وقتی سبد خالی است', C::SWITCHER, '' );
        $this->end_controls_section();
        $this->skin_controls( '.zc-header-cart' );
    }

    protected function render(): void {
        $s    = $this->get_settings_for_display();
        $url  = function_exists( 'wc_get_cart_url' ) ? (string) wc_get_cart_url() : home_url( '/cart/' );
        $qty  = 0;
        if ( function_exists( 'WC' ) && WC()->cart ) {
            $qty = (int) WC()->cart->get_cart_contents_count();
        }
        $show = 'yes' === (string) ( $s['show_count'] ?? 'yes' );
        $zero = 'yes' === (string) ( $s['show_zero'] ?? '' );

        printf(
            '<a class="zc-icon-btn zc-header-cart" href="%1$s" aria-label="%2$s"%3$s>%4$s%5$s</a>',
            esc_url( $url ),
            esc_attr( trim( (string) ( $s['label'] ?? '' ) ) ?: 'سبد خرید' ),
            $qty > 0 ? ' aria-describedby="zc-cart-qty"' : '',
            Icon_Compatibility::render( $s['icon'] ?? array() ),
            ( $show && ( $qty > 0 || $zero ) )
                ? '<span class="zc-cart-count" id="zc-cart-qty">' . esc_html( function_exists( 'zarincode_fa_digits' ) ? zarincode_fa_digits( (string) $qty ) : (string) $qty ) . '</span>'
                : ''
        );
    }
}

final class Widget_Header_Theme_Toggle extends Header_Tool_Widget {

    public function get_name(): string {
        return 'zarincode_header_theme';
    }

    public function get_title(): string {
        return 'کلید حالت روشن / تیره (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-adjust';
    }

    public function get_keywords(): array {
        return array( 'dark', 'theme', 'mode', 'تیره', 'شب', 'هدر' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'حالت نمایش' ) );
        $this->field(
            'icon',
            'آیکون',
            C::ICONS,
            array( 'value' => 'fa-solid fa-moon', 'library' => 'fa-solid' ),
            array( 'description' => 'بعد از تغییر حالت، اسکریپت قالب همین آیکون را به خورشید/ماه تبدیل می‌کند تا وضعیت معلوم باشد.' )
        );
        $this->field( 'label', 'برچسب دسترس‌پذیری', C::TEXT, 'تغییر حالت روشن یا تیره' );
        $this->end_controls_section();
        $this->skin_controls( '.zc-header-theme' );
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        printf(
            '<button type="button" class="zc-icon-btn zc-header-theme" data-zc-toggle-dark aria-pressed="false" title="%2$s" aria-label="%2$s">%1$s</button>',
            Icon_Compatibility::render( $s['icon'] ?? array() ),
            esc_attr( trim( (string) ( $s['label'] ?? '' ) ) ?: 'تغییر حالت روشن یا تیره' )
        );
    }
}
