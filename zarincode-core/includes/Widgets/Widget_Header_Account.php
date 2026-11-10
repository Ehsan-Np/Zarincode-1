<?php
/**
 * Account button — one native element inside the Elementor header.
 *
 * The header is a normal Elementor document (see `Elementor/HF_Builder`), so
 * the account link cannot be a hard-coded string any more: it has to be a
 * widget the owner can select, move, duplicate, restyle or delete like every
 * other element in the header. This widget is exactly one `<a>` — the icon
 * circle plus its optional label — and nothing else.
 *
 * Its defaults still come from the theme settings (`header_account_enabled`,
 * `header_account_url`, `header_account_text`, `header_account_logged_text`
 * and `header_account_label_display`), so an owner who configured the account
 * button in the panel before this release keeps that configuration after the
 * upgrade. Anything set on the widget itself wins.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Controls_Manager as C;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

final class Widget_Header_Account extends Studio_Widget {

    public function get_name(): string {
        return 'zarincode_header_account';
    }

    public function get_title(): string {
        return 'دکمه حساب کاربری (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-user-circle-o';
    }

    /** `zarincode-hf-header` carries the shell rules the panel cannot express. */
    public function get_style_depends(): array {
        return array( 'zarincode-studio', 'zarincode-hf-header' );
    }

    public function get_script_depends(): array {
        return array();
    }

    public function get_keywords(): array {
        return array( 'account', 'user', 'login', 'حساب', 'ورود', 'هدر' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'account', array( 'label' => 'پیوند و برچسب حساب' ) );

        $this->field(
            'icon',
            'آیکون',
            C::ICONS,
            array( 'value' => 'fa-regular fa-user', 'library' => 'fa-regular' )
        );

        $this->field(
            'label',
            'متن دکمه',
            C::TEXT,
            '',
            array(
                'label_block'   => true,
                'description'   => 'خالی بگذارید تا متن تنظیمات قالب استفاده شود (پیش‌فرض: «ورود / حساب کاربری»؛ برای کاربر واردشده «حساب من»).',
            )
        );

        $this->field(
            'link',
            'پیوند',
            C::URL,
            array(),
            array(
                'label_block' => true,
                'description' => 'خالی بگذارید تا نشانی خودکار ووکامرس (صفحهٔ حساب کاربری) استفاده شود.',
            )
        );

        $this->field(
            'label_display',
            'نمایش متن در کنار آیکون',
            C::SELECT,
            'auto',
            array(
                'options' => array(
                    'auto'   => 'خودکار (از تنظیمات قالب)',
                    'always' => 'همیشه',
                    'never'  => 'هرگز — فقط آیکون',
                ),
            )
        );

        $this->field( 'aria_label', 'برچسب دسترس‌پذیری (aria-label)', C::TEXT, '', array( 'label_block' => true ) );
        $this->field( 'open_in_new_tab', 'باز شدن در زبانهٔ تازه', C::SWITCHER, '' );

        $this->end_controls_section();

        $this->start_controls_section( 'look', array( 'label' => 'اندازه و رنگ', 'tab' => C::TAB_STYLE ) );

        $this->add_responsive_control(
            'circle_size',
            array(
                'label'       => 'اندازهٔ دایره',
                'type'        => C::SLIDER,
                'range'       => array( 'px' => array( 'min' => 28, 'max' => 64 ) ),
                'description' => 'اگر خالی بماند، اندازهٔ خودکار قالب استفاده می‌شود (دایرهٔ ۴۰ پیکسلی؛ با متن، عرض خودکار).',
                'selectors'   => array(
                    '{{WRAPPER}} .zc-header-account' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_size',
            array(
                'label'     => 'اندازهٔ آیکون',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 10, 'max' => 32 ) ),
                'selectors' => array(
                    '{{WRAPPER}} .zc-header-account > :is(i,svg)' => 'font-size:{{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->field(
            'icon_color',
            'رنگ آیکون و متن',
            C::COLOR,
            '',
            array( 'selectors' => array( '{{WRAPPER}} .zc-header-account' => 'color:{{VALUE}};' ) )
        );

        $this->field(
            'background_color',
            'رنگ پس‌زمینه',
            C::COLOR,
            '',
            array( 'selectors' => array( '{{WRAPPER}} .zc-header-account' => 'background-color:{{VALUE}};border-color:{{VALUE}};' ) )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array( 'name' => 'label_typography', 'label' => 'تایپوگرافی متن', 'selector' => '{{WRAPPER}} .zc-header-account > span' )
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        // The theme switch is the site-wide “no account button” switch; an
        // owner who disabled it in the panel keeps that decision.
        if ( function_exists( 'zarincode_get_option' ) && ! zarincode_get_option( 'header_account_enabled', true ) ) {
            return;
        }

        $s = $this->get_settings_for_display();

        $url = '';
        if ( ! empty( $s['link']['url'] ) ) {
            $url = (string) $s['link']['url'];
        } elseif ( function_exists( 'zarincode_get_option' ) ) {
            $raw = zarincode_get_option( 'header_account_url', '' );
            if ( is_string( $raw ) && '' !== $raw ) {
                $url = ( str_starts_with( $raw, '/' ) && ! str_starts_with( $raw, '//' ) ) ? home_url( $raw ) : $raw;
            }
        }
        if ( '' === $url ) {
            $url = ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) > 0 )
                ? (string) wc_get_page_permalink( 'myaccount' )
                : wp_login_url();
        }

        $logged = is_user_logged_in();
        $label  = trim( (string) ( $s['label'] ?? '' ) );
        if ( '' === $label && function_exists( 'zarincode_get_option' ) ) {
            $label = (string) zarincode_get_option( $logged ? 'header_account_logged_text' : 'header_account_text', $logged ? 'حساب من' : 'ورود / حساب کاربری' );
        }
        if ( '' === $label ) {
            $label = $logged ? 'حساب من' : 'ورود / حساب کاربری';
        }

        $mode = (string) ( $s['label_display'] ?? 'auto' );
        if ( 'auto' === $mode ) {
            $mode = function_exists( 'zarincode_get_option' ) ? (string) zarincode_get_option( 'header_account_label_display', 'desktop' ) : 'desktop';
        }
        if ( ! in_array( $mode, array( 'always', 'desktop', 'never' ), true ) ) {
            $mode = 'desktop';
        }

        $aria = trim( (string) ( $s['aria_label'] ?? '' ) );
        if ( '' === $aria ) {
            $aria = $label;
        }

        $target = 'yes' === (string) ( $s['open_in_new_tab'] ?? '' ) ? ' target="_blank" rel="noopener"' : '';

        printf(
            '<a class="zc-header-account zc-icon-btn zc-account-label-%1$s" href="%2$s"%3$s aria-label="%4$s" title="%4$s">%5$s%6$s</a>',
            esc_attr( $mode ),
            esc_url( $url ),
            $target, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal.
            esc_attr( $aria ),
            Icon_Compatibility::render( $s['icon'] ?? array() ),
            'never' === $mode ? '' : '<span>' . esc_html( $label ) . '</span>'
        );
    }
}
