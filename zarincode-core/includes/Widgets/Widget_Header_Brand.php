<?php
/**
 * Brand block — one native element inside the Elementor header.
 *
 * Markup is the header's own brand pill (`.zc-brand`), so the mobile drawer,
 * which clones the first `.zc-brand` it finds, keeps working. Every value the
 * old single-widget header owned — monogram, name, tagline, link — is a
 * control here, and so is every colour and size, so the brand can be edited,
 * moved or duplicated like any other Elementor element.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Controls_Manager as C;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

defined( 'ABSPATH' ) || exit;

final class Widget_Header_Brand extends Studio_Widget {

    public function get_name(): string {
        return 'zarincode_header_brand';
    }

    public function get_title(): string {
        return 'برند و لوگوتایپ (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-site-logo';
    }

    /** `zarincode-hf-header` carries the shell rules the panel cannot express. */
    public function get_style_depends(): array {
        return array( 'zarincode-studio', 'zarincode-hf-header' );
    }

    public function get_script_depends(): array {
        return array();
    }

    public function get_keywords(): array {
        return array( 'brand', 'logo', 'برند', 'لوگو', 'هدر', 'zarincode' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'لوگو، نام و شعار' ) );

        $this->field(
            'link',
            'پیوند برند',
            C::URL,
            array( 'url' => home_url( '/' ) ),
            array( 'label_block' => true, 'description' => 'معمولاً صفحهٔ اصلی سایت.' )
        );

        $this->field( 'monogram', 'نشانهٔ متنی (داخل دایرهٔ لوگو)', C::TEXT, 'ZC', array( 'maxlength' => 4 ) );
        $this->field( 'monogram_icon', 'یا آیکون به‌جای نشانهٔ متنی', C::ICONS, array( 'value' => '', 'library' => 'fa-solid' ) );
        $this->field( 'name', 'نام برند', C::TEXT, 'زرین کد' );
        $this->field( 'tagline', 'شعار / نام لاتین', C::TEXT, 'Zarincode' );
        $this->field( 'show_name', 'نمایش نام برند', C::SWITCHER, 'yes' );
        $this->field( 'show_tagline', 'نمایش شعار', C::SWITCHER, 'yes' );
        $this->field( 'aria_extra', 'توضیح تکمیلی برای صفحه‌خوان (اختیاری)', C::TEXT, '', array( 'label_block' => true ) );

        $this->end_controls_section();

        $this->start_controls_section( 'shape', array( 'label' => 'قاب برند', 'tab' => C::TAB_STYLE ) );

        $this->field(
            'frame_padding',
            'فاصله داخلی',
            C::DIMENSIONS,
            array( 'unit' => 'px', 'top' => '5', 'right' => '12', 'bottom' => '5', 'left' => '6', 'isLinked' => false ),
            array(
                'size_units' => array( 'px', 'em' ),
                'selectors'  => array( '{{WRAPPER}} .zc-brand' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
            )
        );

        $this->field(
            'frame_background',
            'پس‌زمینه',
            C::COLOR,
            'rgba(255,255,255,0.9)',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand' => 'background:{{VALUE}};' ) )
        );

        $this->field(
            'frame_border',
            'رنگ خط دور',
            C::COLOR,
            'rgba(42,44,49,0.08)',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand' => 'border:1px solid {{VALUE}};' ) )
        );

        $this->add_responsive_control(
            'frame_radius',
            array(
                'label'     => 'گردی گوشه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
                'default'   => array( 'size' => 999, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-brand' => 'border-radius:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'gap',
            array(
                'label'     => 'فاصله نشانه و متن',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
                'default'   => array( 'size' => 10, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-brand' => 'gap:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array( 'name' => 'frame_shadow', 'label' => 'سایه', 'selector' => '{{WRAPPER}} .zc-brand' )
        );

        $this->end_controls_section();

        $this->start_controls_section( 'monogram_style', array( 'label' => 'دایرهٔ لوگو', 'tab' => C::TAB_STYLE ) );

        $this->add_responsive_control(
            'monogram_size',
            array(
                'label'     => 'اندازه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 24, 'max' => 64 ) ),
                'default'   => array( 'size' => 38, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-brand-logo-box' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'monogram_font',
            array(
                'label'     => 'اندازهٔ حروف نشانه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 10, 'max' => 32 ) ),
                'selectors' => array( '{{WRAPPER}} .zc-brand-logo-box' => 'font-size:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->field(
            'monogram_color',
            'رنگ نشانه و آیکون',
            C::COLOR,
            '#FDD05B',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand-logo-box' => 'color:{{VALUE}};' ) )
        );

        $this->field(
            'monogram_bg',
            'پس‌زمینهٔ دایره',
            C::COLOR,
            '#2A2C31',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand-logo-box' => 'background:{{VALUE}};' ) )
        );

        $this->add_responsive_control(
            'monogram_radius',
            array(
                'label'     => 'گردی گوشه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 50, 'step' => 1 ) ),
                'default'   => array( 'size' => 50, 'unit' => '%' ),
                'selectors' => array( '{{WRAPPER}} .zc-brand-logo-box' => 'border-radius:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section( 'text_style', array( 'label' => 'نام و شعار', 'tab' => C::TAB_STYLE ) );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array( 'name' => 'name_typography', 'label' => 'تایپوگرافی نام', 'selector' => '{{WRAPPER}} .zc-brand-name' )
        );

        $this->field(
            'name_color',
            'رنگ نام',
            C::COLOR,
            '#1F2937',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand-name' => 'color:{{VALUE}};' ) )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array( 'name' => 'tagline_typography', 'label' => 'تایپوگرافی شعار', 'selector' => '{{WRAPPER}} .zc-brand-sub' )
        );

        $this->field(
            'tagline_color',
            'رنگ شعار',
            C::COLOR,
            '#4B5563',
            array( 'selectors' => array( '{{WRAPPER}} .zc-brand-sub' => 'color:{{VALUE}};' ) )
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        $s    = $this->get_settings_for_display();
        $link = is_array( $s['link'] ?? null ) ? $s['link'] : array();
        if ( empty( $link['url'] ) ) {
            $link['url'] = home_url( '/' );
        }

        $name    = trim( (string) ( $s['name'] ?? '' ) );
        $tagline = trim( (string) ( $s['tagline'] ?? '' ) );
        $mono    = trim( (string) ( $s['monogram'] ?? '' ) );
        $icon    = ! empty( $s['monogram_icon']['value'] ) ? Icon_Compatibility::render( $s['monogram_icon'] ) : '';

        $aria = trim( (string) ( $s['aria_extra'] ?? '' ) );
        if ( '' === $aria ) {
            $aria = trim( $name . ' — ' . $tagline, ' —' );
        }

        echo '<a class="zc-brand" ' . $this->link( $link, 'brand' ) . ' aria-label="' . esc_attr( $aria ) . '">';

        if ( '' !== $icon || '' !== $mono ) {
            echo '<span class="zc-brand-logo-box" aria-hidden="true">' . ( '' !== $icon ? $icon : esc_html( $mono ) ) . '</span>';
        }

        if ( ( 'yes' === (string) ( $s['show_name'] ?? 'yes' ) && '' !== $name ) || ( 'yes' === (string) ( $s['show_tagline'] ?? 'yes' ) && '' !== $tagline ) ) {
            echo '<span class="zc-brand-text">';
            if ( 'yes' === (string) ( $s['show_name'] ?? 'yes' ) && '' !== $name ) {
                echo '<span class="zc-brand-name">' . esc_html( $name ) . '</span>';
            }
            if ( 'yes' === (string) ( $s['show_tagline'] ?? 'yes' ) && '' !== $tagline ) {
                echo '<span class="zc-brand-sub">' . esc_html( $tagline ) . '</span>';
            }
            echo '</span>';
        }

        echo '</a>';
    }
}
