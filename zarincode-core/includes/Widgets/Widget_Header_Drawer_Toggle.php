<?php
/**
 * Hamburger — one native element inside the Elementor header.
 *
 * It is a real `<button>` carrying `data-zc-drawer-open`; the theme's script
 * (`assets/js/zarincode-main.js`) finds it, builds the drawer from the header's
 * own `.zc-nav-pill` list and clones the brand and the CTA into it, so the
 * drawer is never a second copy of the menu that can drift out of date.
 *
 * The button is hidden on desktop and shown on tablet/mobile through the
 * Elementor «Responsive» switches written by the header tree, not through a
 * hard-coded media query — the owner can change that in the panel.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Controls_Manager as C;

defined( 'ABSPATH' ) || exit;

final class Widget_Header_Drawer_Toggle extends Studio_Widget {

    public function get_name(): string {
        return 'zarincode_header_drawer';
    }

    public function get_title(): string {
        return 'دکمهٔ منوی موبایل (هدر)';
    }

    public function get_icon(): string {
        return 'eicon-menu-bar';
    }

    /** `zarincode-hf-header` carries the shell rules the panel cannot express. */
    public function get_style_depends(): array {
        return array( 'zarincode-studio', 'zarincode-hf-header' );
    }

    public function get_script_depends(): array {
        return array();
    }

    public function get_keywords(): array {
        return array( 'menu', 'hamburger', 'drawer', 'منو', 'موبایل', 'همبرگر' );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'دکمه و منوی کشویی' ) );

        $this->field(
            'icon',
            'آیکون',
            C::ICONS,
            array( 'value' => 'fa-solid fa-bars', 'library' => 'fa-solid' )
        );

        $this->field( 'label', 'برچسب دسترس‌پذیری', C::TEXT, 'باز کردن منو' );
        $this->field( 'target', 'شناسهٔ منوی کشویی', C::TEXT, 'zc-mobile-drawer', array( 'description' => 'به همین شناسه در اسکریپت قالب وصل می‌شود؛ تغییر ندهید مگر آنکه اسکریپت را هم هماهنگ کنید.' ) );

        $this->end_controls_section();

        $this->start_controls_section( 'look', array( 'label' => 'اندازه و رنگ', 'tab' => C::TAB_STYLE ) );

        $this->add_responsive_control(
            'size',
            array(
                'label'     => 'اندازهٔ دکمه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 28, 'max' => 60 ) ),
                'default'   => array( 'size' => 40, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-mobile-menu-toggle' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->field(
            'icon_color',
            'رنگ آیکون',
            C::COLOR,
            '#FDD05B',
            array( 'selectors' => array( '{{WRAPPER}} .zc-mobile-menu-toggle' => 'color:{{VALUE}};' ) )
        );

        $this->field(
            'background_color',
            'رنگ پس‌زمینه',
            C::COLOR,
            '#2A2C31',
            array( 'selectors' => array( '{{WRAPPER}} .zc-mobile-menu-toggle' => 'background:{{VALUE}};border-color:{{VALUE}};' ) )
        );

        $this->add_responsive_control(
            'radius',
            array(
                'label'     => 'گردی گوشه',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
                'default'   => array( 'size' => 50, 'unit' => '%' ),
                'selectors' => array( '{{WRAPPER}} .zc-mobile-menu-toggle' => 'border-radius:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        $s      = $this->get_settings_for_display();
        $id     = trim( (string) ( $s['target'] ?? '' ) ) ?: 'zc-mobile-drawer';
        $label  = trim( (string) ( $s['label'] ?? '' ) ) ?: 'باز کردن منو';

        printf(
            '<button type="button" class="zc-icon-btn zc-mobile-menu-toggle" data-zc-drawer-open="%1$s" aria-controls="%1$s" aria-expanded="false" aria-label="%2$s">%3$s</button>',
            esc_attr( $id ),
            esc_attr( $label ),
            Icon_Compatibility::render( $s['icon'] ?? array() )
        );
    }
}
