<?php
/**
 * Primary navigation — one native element inside the Elementor header.
 *
 * The seven links are repeater rows, so the owner adds, removes, renames,
 * reorders (drag) or relinks every item in the panel. The «current page»
 * state is not stored: it is resolved at render time by
 * `Native_Design::current_state()`, the same oracle the rest of the site uses,
 * so a link always knows whether it is the current page, the branch that
 * contains it, or idle — and the three states are styled separately.
 *
 * The list keeps the `.zc-nav-pill > li > a` shape because the mobile drawer
 * (assets/js/zarincode-main.js) clones this list on phones: change the shape
 * and the hamburger opens an empty drawer.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Controls_Manager as C;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Widget_Header_Nav extends Studio_Widget {

    public function get_name(): string {
        return 'zarincode_header_nav';
    }

    public function get_title(): string {
        return 'منوی اصلی هدر (قابل ویرایش)';
    }

    public function get_icon(): string {
        return 'eicon-nav-menu';
    }

    /** `zarincode-hf-header` carries the shell rules the panel cannot express. */
    public function get_style_depends(): array {
        return array( 'zarincode-studio', 'zarincode-hf-header' );
    }

    public function get_script_depends(): array {
        return array();
    }

    public function get_keywords(): array {
        return array( 'nav', 'menu', 'منو', 'ناوبری', 'هدر' );
    }

    /** One row of the default navigation — the approved 4.15.0 header menu. */
    public static function default_items(): array {
        return array(
            array( 'text' => 'صفحه اصلی', 'link' => array( 'url' => home_url( '/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
            array(
                'text'  => 'خدمات ما',
                'link'  => array( 'url' => home_url( '/services/' ) ),
                'icon'  => array( 'value' => '', 'library' => 'fa-solid' ),
                'paths' => "/pricing/\n/technologies/\n/seo-performance/\n/trust-and-credentials/",
            ),
            array( 'text' => 'فروشگاه محصولات', 'link' => array( 'url' => home_url( '/shop/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
            array( 'text' => 'نمونه‌کارها', 'link' => array( 'url' => home_url( '/projects-portfolio/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
            array( 'text' => 'قوانین و گارانتی', 'link' => array( 'url' => home_url( '/terms-and-conditions/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
            array( 'text' => 'درباره ما', 'link' => array( 'url' => home_url( '/about-us/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
            array( 'text' => 'تماس با ما', 'link' => array( 'url' => home_url( '/contact-us/' ) ), 'icon' => array( 'value' => '', 'library' => 'fa-solid' ) ),
        );
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'items_section', array( 'label' => 'ردیف‌های منو' ) );

        $rep = new Repeater();
        $rep->add_control( 'text', array( 'label' => 'متن', 'type' => C::TEXT, 'label_block' => true ) );
        $rep->add_control( 'link', array( 'label' => 'پیوند', 'type' => C::URL, 'label_block' => true ) );
        $rep->add_control( 'icon', array( 'label' => 'آیکون (اختیاری)', 'type' => C::ICONS, 'default' => array( 'value' => '', 'library' => 'fa-solid' ) ) );
        $rep->add_control( 'highlight', array( 'label' => 'برجسته‌سازی این ردیف', 'type' => C::SWITCHER, 'default' => '' ) );
        $rep->add_control(
            'paths',
            array(
                'label'       => 'صفحه‌های وابسته به این ردیف',
                'type'        => C::TEXTAREA,
                'rows'        => 3,
                'label_block' => true,
                'description' => 'هر مسیر در یک خط (مثلاً /pricing/). وقتی بازدیدکننده روی یکی از این صفحه‌ها باشد، همین ردیف به‌عنوان «شاخهٔ جاری» نشان داده می‌شود.',
                'default'     => '',
            )
        );

        $this->field(
            'items',
            'ردیف‌ها',
            C::REPEATER,
            self::default_items(),
            array(
                'fields'        => $rep->get_controls(),
                'title_field'   => '{{{ text }}}',
                'prevent_empty' => false,
            )
        );

        $this->field( 'aria_label', 'برچسب منو برای صفحه‌خوان', C::TEXT, 'منوی اصلی' );

        $this->end_controls_section();

        $this->start_controls_section( 'track', array( 'label' => 'نوار منو', 'tab' => C::TAB_STYLE ) );

        $this->field(
            'track_background',
            'پس‌زمینهٔ نوار',
            C::COLOR,
            'rgba(255,255,255,0.92)',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill' => 'background:{{VALUE}};' ) )
        );

        $this->field(
            'track_border',
            'رنگ خط دور نوار',
            C::COLOR,
            'rgba(42,44,49,0.08)',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill' => 'border:1px solid {{VALUE}};' ) )
        );

        $this->add_responsive_control(
            'track_radius',
            array(
                'label'     => 'گردی گوشهٔ نوار',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
                'default'   => array( 'size' => 999, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-nav-pill' => 'border-radius:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'track_padding',
            array(
                'label'      => 'فاصله داخلی نوار',
                'type'       => C::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array( 'unit' => 'px', 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'isLinked' => true ),
                'selectors'  => array( '{{WRAPPER}} .zc-nav-pill' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'item_gap',
            array(
                'label'     => 'فاصلهٔ ردیف‌ها',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
                'default'   => array( 'size' => 3, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-nav-pill' => 'gap:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section( 'link_style', array( 'label' => 'ردیف‌ها: اندازه، حالت‌ها و رنگ', 'tab' => C::TAB_STYLE ) );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array( 'name' => 'link_typography', 'label' => 'تایپوگرافی ردیف‌ها', 'selector' => '{{WRAPPER}} .zc-nav-pill > li > a' )
        );

        $this->add_responsive_control(
            'link_padding',
            array(
                'label'      => 'فاصله داخلی هر ردیف',
                'type'       => C::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array( 'unit' => 'px', 'top' => '7', 'right' => '12', 'bottom' => '7', 'left' => '12', 'isLinked' => false ),
                'selectors'  => array( '{{WRAPPER}} .zc-nav-pill > li > a' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
            )
        );

        $this->add_responsive_control(
            'link_radius',
            array(
                'label'     => 'گردی گوشهٔ ردیف',
                'type'      => C::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
                'default'   => array( 'size' => 999, 'unit' => 'px' ),
                'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a' => 'border-radius:{{SIZE}}{{UNIT}};' ),
            )
        );

        $this->start_controls_tabs( 'link_states' );

        $this->start_controls_tab( 'state_idle', array( 'label' => 'عادی' ) );
        $this->field(
            'idle_color',
            'رنگ متن',
            C::COLOR,
            '#4B5563',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a' => 'color:{{VALUE}};' ) )
        );
        $this->field(
            'idle_icon',
            'رنگ آیکون',
            C::COLOR,
            '',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a .zc-nav-ico' => 'color:{{VALUE}};' ) )
        );
        $this->end_controls_tab();

        $this->start_controls_tab( 'state_hover', array( 'label' => 'هاور' ) );
        $this->field(
            'hover_color',
            'رنگ متن',
            C::COLOR,
            '#FFFFFF',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a:hover, {{WRAPPER}} .zc-nav-pill > li > a:focus-visible' => 'color:{{VALUE}};' ) )
        );
        $this->field(
            'hover_bg',
            'پس‌زمینه',
            C::COLOR,
            '#2A2C31',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a:hover, {{WRAPPER}} .zc-nav-pill > li > a:focus-visible' => 'background:{{VALUE}};' ) )
        );
        $this->field(
            'hover_icon',
            'رنگ آیکون',
            C::COLOR,
            '#FDD05B',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a:hover .zc-nav-ico, {{WRAPPER}} .zc-nav-pill > li > a:focus-visible .zc-nav-ico' => 'color:{{VALUE}};' ) )
        );
        $this->end_controls_tab();

        $this->start_controls_tab( 'state_current', array( 'label' => 'صفحهٔ جاری' ) );
        $this->field(
            'current_color',
            'رنگ متن',
            C::COLOR,
            '#FFFFFF',
            array(
                'selectors' => array(
                    '{{WRAPPER}} .zc-nav-pill > li > a.is-current, {{WRAPPER}} .zc-nav-pill > li > a[aria-current="page"]' => 'color:{{VALUE}};',
                ),
            )
        );
        $this->field(
            'current_bg',
            'پس‌زمینه',
            C::COLOR,
            '#2A2C31',
            array(
                'selectors' => array(
                    '{{WRAPPER}} .zc-nav-pill > li > a.is-current, {{WRAPPER}} .zc-nav-pill > li > a[aria-current="page"]' => 'background:{{VALUE}};',
                ),
            )
        );
        $this->field(
            'current_indicator',
            'رنگ خطِ نشانگر',
            C::COLOR,
            '#FDD05B',
            array(
                'selectors' => array(
                    '{{WRAPPER}}' => '--zc-hdr-indicator:{{VALUE}};',
                    '{{WRAPPER}} .zc-nav-pill > li > a.is-current .zc-nav-ico' => 'color:{{VALUE}};',
                ),
            )
        );
        $this->end_controls_tab();

        $this->start_controls_tab( 'state_ancestor', array( 'label' => 'شاخهٔ جاری' ) );
        $this->field(
            'ancestor_color',
            'رنگ متن',
            C::COLOR,
            '#1F2937',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a.is-current-ancestor' => 'color:{{VALUE}};' ) )
        );
        $this->field(
            'ancestor_bg',
            'پس‌زمینه',
            C::COLOR,
            'rgba(253,208,91,0.22)',
            array( 'selectors' => array( '{{WRAPPER}} .zc-nav-pill > li > a.is-current-ancestor' => 'background:{{VALUE}};' ) )
        );
        $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function render(): void {
        $s     = $this->get_settings_for_display();
        $items = is_array( $s['items'] ?? null ) ? $s['items'] : self::default_items();
        if ( ! $items ) {
            if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
                echo '<p class="zc-hdr-empty">ردیفی برای منو اضافه نشده است.</p>';
            }
            return;
        }

        $aria = trim( (string) ( $s['aria_label'] ?? '' ) );
        echo '<ul class="zc-nav-pill" role="list"' . ( '' !== $aria ? ' aria-label="' . esc_attr( $aria ) . '"' : '' ) . '>';

        foreach ( $items as $index => $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $text  = trim( (string) ( $row['text'] ?? '' ) );
            $link  = is_array( $row['link'] ?? null ) ? $row['link'] : array();
            $url   = (string) ( $link['url'] ?? '' );
            $state = Native_Design::current_state( $url );

            // «من کجای سایت هستم؟» — a page that belongs to this row without
            // living under its URL (تعرفه، فناوری‌ها، سئو …) marks the row as
            // the branch the visitor is in, not as the page itself.
            if ( '' === $state && ! empty( $row['paths'] ) ) {
                foreach ( preg_split( '/[\r\n,]+/', (string) $row['paths'] ) as $extra ) {
                    $extra = trim( $extra );
                    if ( '' === $extra ) {
                        continue;
                    }
                    if ( 'current' === Native_Design::current_state( home_url( $extra ) ) ) {
                        $state = 'ancestor';
                        break;
                    }
                }
            }

            $classes = array( 'zc-nav-link' );
            if ( 'current' === $state ) {
                $classes[] = 'is-current';
            } elseif ( 'ancestor' === $state ) {
                $classes[] = 'is-current-ancestor';
            }
            if ( 'yes' === (string) ( $row['highlight'] ?? '' ) ) {
                $classes[] = 'is-highlight';
            }

            echo '<li>';
            printf(
                '<a class="%1$s"%2$s%3$s>',
                esc_attr( implode( ' ', $classes ) ),
                'current' === $state ? ' aria-current="page"' : '',
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
                '' !== $url ? ' ' . $this->link( $link, 'item' . $index ) : ''
            );

            if ( ! empty( $row['icon']['value'] ) ) {
                echo '<span class="zc-nav-ico" aria-hidden="true">' . Icon_Compatibility::render( $row['icon'] ) . '</span>';
            }

            echo '<span class="zc-nav-text">' . esc_html( $text ) . '</span></a></li>';
        }

        echo '</ul>';
    }
}
