<?php
/**
 * Homepage section widgets — "cheerful + formal" homepage (v4.7.0).
 *
 * Eleven widgets compose the homepage. Each one behaves like a stock Elementor
 * widget, so it can be edited without touching code:
 *
 *   • Content tab  — every string, link, icon, repeater row and data source.
 *   • Style tab    — background, typography, colors, spacing, border, radius and
 *                    shadow, all wired through Elementor `selectors` so the live
 *                    preview updates instantly and Elementor generates the CSS.
 *   • Advanced tab — the standard Elementor controls (margin, padding, motion…).
 *
 * Control defaults are read from the theme content model
 * (zarincode/inc/Home/home-content.json) through Home_Templates::defaults(), so a
 * freshly dropped widget already shows the approved copy instead of empty fields,
 * and clearing a field really clears it on the front end.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Zarincode\Theme\Home\Home_Templates;

defined( 'ABSPATH' ) || exit;

/* ===================================================================== BASE */

abstract class Abstract_Home_Widget extends Widget_Base {

	/** Elementor panel category registered by Elementor_Loader::register_category(). */
	public const CATEGORY = 'zarincode-home';

	/**
	 * Hidden from the panel since 4.16.0.
	 *
	 * Every one of these widgets renders a whole homepage section in a single
	 * element (hero, services, pricing …). The homepage itself has been built
	 * from native Elementor widgets since 4.15.0 — heading, text-editor, button,
	 * image, icon-box, icon-list and dividers inside containers — precisely so
	 * that each title, paragraph, card and button can be selected on its own.
	 * They stay registered only so a document written by an older release keeps
	 * rendering; nobody can create a new «one widget per section» block.
	 */
	public function show_in_panel(): bool {
		return false;
	}

	/** Section heading selectors reused by the style controls. */
	private const SEL_BADGE = '{{WRAPPER}} .zc-hp .sec-head .k, {{WRAPPER}} .zc-hp .head-row .k';
	private const SEL_TITLE = '{{WRAPPER}} .zc-hp h2.t';
	private const SEL_HILITE = '{{WRAPPER}} .zc-hp h2.t .g';
	private const SEL_DESC = '{{WRAPPER}} .zc-hp .sec-head p, {{WRAPPER}} .zc-hp .head-row p';
	private const SEL_LINK = '{{WRAPPER}} .zc-hp .head-row .link';

	/** Cached content model for this section. */
	private ?array $content_defaults = null;

	/* ------------------------------------------------------------ Elementor */

	public function get_categories(): array {
		return array( self::CATEGORY );
	}

	public function get_keywords(): array {
		return array( 'zarincode', 'زرین کد', 'صفحه اصلی', 'home', $this->section_slug() );
	}

	public function get_style_depends(): array {
		return array( 'zarincode-home-cheerful' );
	}

	/** Sections read posts/products, so Elementor must not cache the output. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/** Theme template method that renders this section (hero, services, …). */
	abstract protected function section_slug(): string;

	/** Elementor section ids that hold editable content (not style/advanced). */
	private array $content_sections = array();

	/** Opens a content-tab section and remembers it for Home_Defaults. */
	protected function content_section( string $id, string $label, array $condition = array() ): void {
		$this->content_sections[] = $id;
		$args = array( 'label' => $label, 'tab' => C::TAB_CONTENT );
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->start_controls_section( $id, $args );
	}

	/** Section ids registered through content_section(). */
	public function get_content_section_ids(): array {
		return $this->content_sections;
	}

	/** Turns Elementor settings into the array the template expects. */
	protected function prepare( array $settings ): array {
		return $settings;
	}

	/* -------------------------------------------------- content model access */

	protected function defaults(): array {
		if ( null === $this->content_defaults ) {
			$this->content_defaults = class_exists( Home_Templates::class )
				? Home_Templates::defaults( $this->section_slug() )
				: array();
		}
		return $this->content_defaults;
	}

	/** Raw default for a content key ('' when missing). */
	protected function d( string $key, $fallback = '' ) {
		$d = $this->defaults();
		return array_key_exists( $key, $d ) ? $d[ $key ] : $fallback;
	}

	/** String default — safe for TEXT/TEXTAREA controls. */
	protected function ds( string $key, string $fallback = '' ): string {
		$value = $this->d( $key, $fallback );
		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/** Array default — safe for repeaters. */
	protected function da( string $key ): array {
		$value = $this->d( $key, array() );
		return is_array( $value ) ? array_values( $value ) : array();
	}

	/** Nested array default (featured, matrix, newsletter…). */
	protected function dn( string $key ): array {
		$value = $this->d( $key, array() );
		return is_array( $value ) ? $value : array();
	}

	/* -------------------------------------------------------- field helpers */

	/** Adds a control to the widget or to a repeater instance. */
	protected function field( $target, string $id, array $args ): void {
		$target->add_control( $id, $args );
	}

	protected function f_text( $target, string $id, string $label, string $default = '', array $extra = array() ): void {
		$this->field( $target, $id, array_merge( array(
			'label'       => $label,
			'type'        => C::TEXT,
			'default'     => $default,
			'label_block' => true,
			'dynamic'     => array( 'active' => true ),
		), $extra ) );
	}

	protected function f_area( $target, string $id, string $label, string $default = '', array $extra = array() ): void {
		$this->field( $target, $id, array_merge( array(
			'label'       => $label,
			'type'        => C::TEXTAREA,
			'default'     => $default,
			'rows'        => 3,
			'label_block' => true,
			'dynamic'     => array( 'active' => true ),
		), $extra ) );
	}

	protected function f_url( $target, string $id, string $label, string $default = '' ): void {
		$this->field( $target, $id, array(
			'label'       => $label,
			'type'        => C::URL,
			'default'     => array( 'url' => $default ),
			'label_block' => true,
			'placeholder' => '/contact-us/',
			'dynamic'     => array( 'active' => true ),
		) );
	}

	protected function f_number( $target, string $id, string $label, $default, int $min, int $max ): void {
		$this->field( $target, $id, array(
			'label'   => $label,
			'type'    => C::NUMBER,
			'default' => $default,
			'min'     => $min,
			'max'     => $max,
		) );
	}

	protected function f_select( $target, string $id, string $label, array $options, string $default = '', string $type = C::SELECT ): void {
		$this->field( $target, $id, array(
			'label'       => $label,
			'type'        => $type,
			'options'     => $options,
			'default'     => $default,
			'label_block' => true,
		) );
	}

	protected function f_switch( $target, string $id, string $label, string $default = 'yes' ): void {
		$this->field( $target, $id, array(
			'label'        => $label,
			'type'         => C::SWITCHER,
			'default'      => $default,
			'return_value' => 'yes',
		) );
	}

	/** Icon picker: a searchable list of the Font Awesome glyphs the design ships. */
	protected function f_icon( $target, string $id = 'icon', string $default = 'display' ): void {
		$options = class_exists( Home_Templates::class ) ? Home_Templates::icon_choices() : array();
		$this->field( $target, $id, array(
			'label'       => 'آیکون',
			'type'        => C::SELECT2,
			'options'     => $options,
			'default'     => $default,
			'label_block' => true,
		) );
	}

	/**
	 * Registers a repeater with stable row identities and real defaults.
	 *
	 * @param string $id          Control id.
	 * @param string $label       Panel label.
	 * @param array  $fields      field id => control args.
	 * @param array  $rows        default rows (already in template shape).
	 * @param string $title_field Field used as the collapsed row title.
	 */
	protected function f_repeater( string $id, string $label, array $fields, array $rows, string $title_field ): void {
		// $fields is Repeater::get_controls(), which already carries Elementor's hidden
		// `_id` field. Building a second Repeater around it would redeclare `_id`.
		$this->add_control( $id, array(
			'label'         => $label,
			'type'          => C::REPEATER,
			'fields'        => $fields,
			'default'       => Repeater_Identity::rows( $rows, $this->get_name() . '.' . $id, $fields ),
			'title_field'   => '{{{ ' . $title_field . ' }}}',
			'prevent_empty' => false,
			'label_block'   => true,
		) );
	}

	/* --------------------------------------------------- normalisation utils */

	/** URL controls return arrays; the templates expect plain strings. */
	protected function urls_to_string( array $settings, array $keys ): array {
		foreach ( $keys as $key ) {
			$value = $settings[ $key ] ?? '';
			if ( is_array( $value ) ) {
				$settings[ $key ] = (string) ( $value['url'] ?? '' );
			}
		}
		return $settings;
	}

	/**
	 * URL control value → plain string.
	 *
	 * Elementor stores URLs as `['url' => …]`, but seeded or programmatically
	 * built settings may already be strings; reading `['url']` off a string used
	 * to raise a PHP warning.
	 */
	protected function link_of( $value ): string {
		if ( is_array( $value ) ) {
			return (string) ( $value['url'] ?? '' );
		}
		return is_string( $value ) ? $value : '';
	}

	/** "a, b , c" or an array → clean list. */
	protected function to_list( $value ): array {
		if ( is_array( $value ) ) {
			$items = $value;
		} else {
			$items = preg_split( '/[,،]+/u', (string) $value );
		}
		$items = array_map( 'trim', (array) $items );
		return array_values( array_filter( $items, static fn( $v ) => '' !== $v && null !== $v ) );
	}

	/** Textarea (one item per line) → clean list. */
	protected function to_lines( $value ): array {
		$items = preg_split( '/[\r\n]+/u', (string) $value );
		$items = array_map( 'trim', (array) $items );
		$items = array_filter( $items, static fn( $v ) => '' !== $v );
		return array_values( $items );
	}

	/** Only the given keys, keeping repeater rows in template shape. */
	protected function pick_rows( $rows, array $keys ): array {
		$out = array();
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$item = array();
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $row ) ) {
					$item[ $key ] = $row[ $key ];
				}
			}
			$out[] = $item;
		}
		return $out;
	}

	/** Repeater rows are always arrays, never "not set". */
	protected function rows_of( array $settings, string $key ): array {
		$rows = $settings[ $key ] ?? array();
		return is_array( $rows ) ? array_values( array_filter( $rows, 'is_array' ) ) : array();
	}

	/**
	 * Stores the prepared rows, and drops the key entirely when there are none
	 * so the template falls back to its own demo/backup content instead of
	 * rendering an empty grid.
	 *
	 * @param array $settings Widget settings, updated in place.
	 * @param array $items    Prepared rows.
	 */
	protected function set_items( array &$settings, array $items ): void {
		if ( $items ) {
			$settings['items'] = array_values( $items );
		} else {
			unset( $settings['items'] );
		}
	}

	/* ----------------------------------------------------- shared: content */

	/**
	 * Standard section heading controls.
	 *
	 * @param bool $with_link Show the "مشاهده همه" link controls.
	 */
	protected function heading_controls( bool $with_link = false ): void {
		$this->content_section( 'zc_heading', 'عنوان و توضیح بخش' );
		$this->f_text( $this, 'badge', 'برچسب بالای عنوان', $this->ds( 'badge' ), array(
			'description' => 'چیپ کوچک بالای عنوان. برای حذف، فیلد را خالی کنید.',
		) );
		$this->f_text( $this, 'title', 'عنوان', $this->ds( 'title' ) );
		$this->f_text( $this, 'title_highlight', 'بخش هایلایت‌شدهٔ عنوان', $this->ds( 'title_highlight' ), array(
			'description' => 'این عبارت با هایلایتر طلایی مشخص می‌شود.',
		) );
		$this->f_text( $this, 'title_after', 'ادامهٔ عنوان', $this->ds( 'title_after' ) );
		$this->f_area( $this, 'desc', 'توضیح زیر عنوان', $this->ds( 'desc' ) );
		if ( $with_link ) {
			$this->f_text( $this, 'link_text', 'متن پیوند «مشاهده همه»', $this->ds( 'link_text' ) );
			$this->f_url( $this, 'link_url', 'نشانی پیوند «مشاهده همه»', $this->ds( 'link_url' ) );
		}
		$this->end_controls_section();
	}

	/** Data-source selector shared by products / portfolio / blog. */
	protected function source_controls( string $id, string $count_id, string $label, string $manual_label, string $query_label, int $count, int $min, int $max, string $default = 'query' ): void {
		$this->f_select( $this, $id, 'منبع داده', array(
			'query'  => $query_label,
			'manual' => $manual_label,
		), $default );
		$this->f_number( $this, $count_id, 'تعداد آیتم‌های نمایشی', $count, $min, $max );
		$this->add_control( $count_id . '_note', array(
			'type'            => C::RAW_HTML,
			'raw'             => 'حالت پویا محتوا را از سایت می‌خواند و اگر هنوز محتوایی منتشر نشده باشد، برای جلوگیری از بخش خالی، همان کارت‌های دستی زیر نمایش داده می‌شود.',
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( $id => 'query' ),
		) );
	}

	/* --------------------------------------------------- shared: style tabs */

	/** Section shell: background, padding, margin, radius. */
	protected function style_shell(): void {
		$this->start_controls_section( 'zc_style_shell', array( 'label' => 'پوستهٔ بخش', 'tab' => C::TAB_STYLE ) );
		// The shipped design paints a soft gradient on the <section> of some blocks.
		// A user colour must be able to win over it, so the tint can be switched off
		// first — otherwise picking a colour would appear to do nothing.
		// Deliberately not a `selectors` switcher: Elementor emits a switcher's CSS
		// rule even when the switch is on, which would kill the tint by default.
		// The value is applied as a wrapper class in render() instead.
		$this->add_control( 'sec_tint', array(
			'label'        => 'تینت گرادیانی ملایم',
			'type'         => C::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'no',
			'description'  => 'روشن بگذارید تا تینت ملایم طراحی نمایش داده شود. اگر رنگ پس‌زمینه انتخاب می‌کنید، خاموشش کنید.',
		) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'sec_bg',
			'label'    => 'پس‌زمینهٔ بخش',
			'types'    => array( 'classic', 'gradient' ),
			'exclude'  => Editor_Controls::BACKGROUND_EXCLUDE,
			'selector' => '{{WRAPPER}} .zc-hp section, {{WRAPPER}} .zc-hp .hero',
		) );
		$this->add_responsive_control( 'sec_padding', array(
			'label'      => 'فاصلهٔ داخلی',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( 'sec_margin', array(
			'label'      => 'فاصلهٔ بیرونی',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( 'sec_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
			),
		) );
		$this->end_controls_section();
	}

	/** Section heading styles: badge, title, highlight, description, link. */
	protected function style_heading( bool $with_link = false ): void {
		$this->start_controls_section( 'zc_style_head', array( 'label' => 'عنوان بخش', 'tab' => C::TAB_STYLE ) );

		$this->add_control( 'head_badge_heading', array( 'label' => 'برچسب بالای عنوان', 'type' => C::HEADING ) );
		$this->add_control( 'badge_color', array(
			'label'     => 'رنگ متن',
			'type'      => C::COLOR,
			'selectors' => array( self::SEL_BADGE => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'badge_bg', array(
			'label'     => 'رنگ پس‌زمینه',
			'type'      => C::COLOR,
			'selectors' => array( self::SEL_BADGE => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'badge_type',
			'label'    => 'تایپوگرافی',
			'selector' => self::SEL_BADGE,
		) );

		$this->add_control( 'head_title_heading', array( 'label' => 'عنوان', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_type',
			'label'    => 'تایپوگرافی',
			'selector' => self::SEL_TITLE,
		) );
		$this->add_control( 'title_color', array(
			'label'     => 'رنگ عنوان',
			'type'      => C::COLOR,
			'selectors' => array( self::SEL_TITLE => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'highlight_color', array(
			'label'       => 'رنگ عبارت هایلایت',
			'type'        => C::COLOR,
			'description' => 'هایلایتر طلایی را با «رنگ پس‌زمینهٔ هایلایت» تغییر دهید.',
			'selectors'   => array( self::SEL_HILITE => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'highlight_bg', array(
			'label'     => 'رنگ هایلایتر',
			'type'      => C::COLOR,
			'selectors' => array( self::SEL_HILITE => 'background-image: linear-gradient({{VALUE}}, {{VALUE}});' ),
		) );

		$this->add_control( 'head_desc_heading', array( 'label' => 'توضیح', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'desc_type',
			'label'    => 'تایپوگرافی',
			'selector' => self::SEL_DESC,
		) );
		$this->add_control( 'desc_color', array(
			'label'     => 'رنگ متن',
			'type'      => C::COLOR,
			'selectors' => array( self::SEL_DESC => 'color: {{VALUE}};' ),
		) );

		if ( $with_link ) {
			$this->add_control( 'head_link_heading', array( 'label' => 'پیوند «مشاهده همه»', 'type' => C::HEADING, 'separator' => 'before' ) );
			$this->add_group_control( Group_Control_Typography::get_type(), array(
				'name'     => 'more_type',
				'label'    => 'تایپوگرافی',
				'selector' => self::SEL_LINK,
			) );
			$this->add_control( 'more_color', array(
				'label'     => 'رنگ',
				'type'      => C::COLOR,
				'selectors' => array( self::SEL_LINK => 'color: {{VALUE}};' ),
			) );
		}

		$this->add_control( 'head_spacing_heading', array( 'label' => 'چیدمان', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_responsive_control( 'head_align', array(
			'label'     => 'ترازبندی',
			'type'      => C::CHOOSE,
			'options'   => array(
				'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array(
				'{{WRAPPER}} .zc-hp .sec-head' => 'text-align: {{VALUE}};',
			),
		) );
		$this->add_responsive_control( 'head_gap', array(
			'label'      => 'فاصله تا محتوا',
			'type'       => C::SLIDER,
			'size_units' => array( 'px', 'em' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .sec-head, {{WRAPPER}} .zc-hp .head-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			),
		) );
		$this->end_controls_section();
	}

	/**
	 * Card styles (normal + hover) plus card title / text / icon typography.
	 *
	 * @param string $label     Style section label.
	 * @param string $card      Inner selector of one card, e.g. '.svc'.
	 * @param string $title     Card title selector, e.g. '.sbody h3'.
	 * @param string $text      Card body selector.
	 * @param string $icon      Icon selector (optional).
	 */
	protected function style_cards( string $label, string $card, string $title, string $text, string $icon = '' ): void {
		$card_sel  = '{{WRAPPER}} .zc-hp ' . $card;
		$title_sel = '{{WRAPPER}} .zc-hp ' . $title;
		$text_sel  = '{{WRAPPER}} .zc-hp ' . $text;

		$this->start_controls_section( 'zc_style_cards', array( 'label' => $label, 'tab' => C::TAB_STYLE ) );

		$this->start_controls_tabs( 'card_tabs' );

		$this->start_controls_tab( 'card_tab_normal', array( 'label' => 'عادی' ) );
		$this->add_control( 'card_bg', array(
			'label'     => 'رنگ پس‌زمینه',
			'type'      => C::COLOR,
			'selectors' => array( $card_sel => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'label'    => 'حاشیه',
			'selector' => $card_sel,
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'label'    => 'سایه',
			'selector' => $card_sel,
		) );
		$this->add_responsive_control( 'card_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', '%', 'em' ),
			'selectors'  => array(
				$card_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( 'card_padding', array(
			'label'      => 'فاصلهٔ داخلی',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array(
				$card_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'card_tab_hover', array( 'label' => 'هاور' ) );
		$this->add_control( 'card_bg_hover', array(
			'label'     => 'رنگ پس‌زمینه',
			'type'      => C::COLOR,
			'selectors' => array( $card_sel . ':hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow_hover',
			'label'    => 'سایه',
			'selector' => $card_sel . ':hover',
		) );
		$this->add_control( 'card_lift', array(
			'label'     => 'بالا آمدن کارت',
			'type'      => C::SLIDER,
			'range'     => array( 'px' => array( 'min' => -20, 'max' => 0 ) ),
			'selectors' => array( $card_sel . ':hover' => 'transform: translateY({{SIZE}}px);' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control( 'card_title_heading', array( 'label' => 'عنوان کارت', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'card_title_type',
			'label'    => 'تایپوگرافی',
			'selector' => $title_sel,
		) );
		$this->add_control( 'card_title_color', array(
			'label'     => 'رنگ عنوان',
			'type'      => C::COLOR,
			'selectors' => array( $title_sel => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_text_heading', array( 'label' => 'متن کارت', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'card_text_type',
			'label'    => 'تایپوگرافی',
			'selector' => $text_sel,
		) );
		$this->add_control( 'card_text_color', array(
			'label'     => 'رنگ متن',
			'type'      => C::COLOR,
			'selectors' => array( $text_sel => 'color: {{VALUE}};' ),
		) );

		if ( '' !== $icon ) {
			$icon_sel = '{{WRAPPER}} .zc-hp ' . $icon . ' .fa';
			$this->add_control( 'card_icon_heading', array( 'label' => 'آیکون', 'type' => C::HEADING, 'separator' => 'before' ) );
			$this->add_control( 'card_icon_color', array(
				'label'     => 'رنگ آیکون',
				'type'      => C::COLOR,
				'selectors' => array( $icon_sel => 'color: {{VALUE}};' ),
			) );
			$this->add_control( 'card_icon_bg', array(
				'label'     => 'پس‌زمینهٔ آیکون',
				'type'      => C::COLOR,
				'selectors' => array( '{{WRAPPER}} .zc-hp ' . $icon => 'background-color: {{VALUE}};' ),
			) );
			$this->add_responsive_control( 'card_icon_size', array(
				'label'      => 'اندازهٔ آیکون',
				'type'       => C::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 64 ) ),
				'selectors'  => array( $icon_sel => 'font-size: {{SIZE}}{{UNIT}};' ),
			) );
		}

		$this->end_controls_section();
	}

	/* -------------------------------------------------------------- render */

	protected function render(): void {
		$slug = $this->section_slug();
		if ( '' === $slug || ! class_exists( Home_Templates::class ) || ! method_exists( Home_Templates::class, $slug ) ) {
			return;
		}

		$settings = $this->prepare( $this->get_settings_for_display() );
		$html     = Home_Templates::$slug( $settings );
		if ( '' === $html ) {
			return;
		}

		echo Home_Templates::wrap_open( $this->is_flat( $settings ) ) . $html . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput -- templates escape internally.
	}

	/** True when the user asked for a flat (non-tinted) section background. */
	protected function is_flat( array $settings ): bool {
		return ( $settings['sec_tint'] ?? 'yes' ) === 'no';
	}
}

/* ==================================================================== HERO */

class Home_Hero extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_hero';
	}

	public function get_title(): string {
		return 'صفحه اصلی — هیرو';
	}

	public function get_icon(): string {
		return 'eicon-heading';
	}

	protected function section_slug(): string {
		return 'hero';
	}

	protected function register_controls(): void {
		$this->content_section( 'zc_hero_title', 'عنوان اصلی' );
		$this->f_text( $this, 'title_line1', 'خط اول عنوان', $this->ds( 'title_line1' ) );
		$this->f_text( $this, 'title_highlight', 'بخش هایلایت‌شدهٔ عنوان', $this->ds( 'title_highlight' ), array(
			'description' => 'این عبارت با هایلایتر طلایی نمایش داده می‌شود.',
		) );
		$this->f_text( $this, 'title_line2', 'خط دوم عنوان', $this->ds( 'title_line2' ) );
		$this->f_text( $this, 'title_pop', 'جملهٔ برجستهٔ پایانی', $this->ds( 'title_pop' ), array(
			'description' => 'این جمله داخل قرص تیرهٔ زیر عنوان می‌آید.',
		) );
		$this->f_area( $this, 'lead', 'متن معرفی', $this->ds( 'lead' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_hero_buttons', 'دکمه‌ها' );
		$this->f_text( $this, 'primary_text', 'دکمهٔ اصلی — متن', $this->ds( 'primary_text' ) );
		$this->f_url( $this, 'primary_url', 'دکمهٔ اصلی — پیوند', $this->ds( 'primary_url' ) );
		$this->f_text( $this, 'secondary_text', 'دکمهٔ دوم — متن', $this->ds( 'secondary_text' ) );
		$this->f_url( $this, 'secondary_url', 'دکمهٔ دوم — پیوند', $this->ds( 'secondary_url' ) );
		$this->f_text( $this, 'tertiary_text', 'دکمهٔ سوم — متن', $this->ds( 'tertiary_text' ) );
		$this->f_url( $this, 'tertiary_url', 'دکمهٔ سوم — پیوند', $this->ds( 'tertiary_url' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_hero_badges', 'نشان‌های بالای عنوان' );
		$badge = new Repeater();
		$this->f_icon( $badge, 'icon', 'circle-check' );
		$this->f_text( $badge, 'text', 'متن نشان' );
		$this->f_repeater(
			'badges',
			'نشان‌ها',
			$badge->get_controls(),
			$this->pick_rows( $this->da( 'badges' ), array( 'icon', 'text' ) ),
			'text'
		);
		$this->end_controls_section();

		$this->content_section( 'zc_hero_trust', 'شاخص‌های اعتماد' );
		$this->f_switch( $this, 'real_stats', 'آمار واقعی سایت (تعداد پروژه‌ها و میانگین امتیاز)', 'yes' );
		$this->add_control( 'real_stats_note', array(
			'type'            => C::RAW_HTML,
			'raw'             => 'با روشن بودن این گزینه، «مقدار» کارت اول از تعداد نمونه‌کارهای منتشرشده و کارت دوم از میانگین امتیاز دیدگاه‌های محصولات ساخته می‌شود؛ اگر داده‌ای نباشد، همان مقداری که نوشته‌اید نمایش داده می‌شود.',
			'content_classes' => 'elementor-descriptor',
		) );
		$trust = new Repeater();
		$this->f_icon( $trust, 'icon', 'chart-line' );
		$this->f_text( $trust, 'value', 'مقدار', '', array( 'description' => 'مثال: +۱۴۰ پروژه' ) );
		$this->f_text( $trust, 'label', 'زیرنویس' );
		$this->f_repeater(
			'trust',
			'شاخص‌ها',
			$trust->get_controls(),
			$this->pick_rows( $this->da( 'trust' ), array( 'icon', 'value', 'label' ) ),
			'value'
		);
		$this->end_controls_section();

		$this->content_section( 'zc_hero_float', 'کارت شناور و امتیاز' );
		$this->f_text( $this, 'code_fn', 'کارت کد — نام تابع', $this->ds( 'code_fn', 'grow' ) );
		$this->f_text( $this, 'code_arg', 'کارت کد — پارامتر ورودی', $this->ds( 'code_arg', 'business' ) );
		$this->f_text( $this, 'score_value', 'امتیاز (کارت شناور)', $this->ds( 'score_value' ) );
		$this->f_text( $this, 'score_label', 'زیرنویس امتیاز', $this->ds( 'score_label' ) );
		$floats = new Repeater();
		$this->f_icon( $floats, 'icon', 'cart-shopping' );
		$this->f_text( $floats, 'text', 'متن' );
		$this->f_repeater(
			'floats',
			'نشان‌های شناور',
			$floats->get_controls(),
			$this->pick_rows( $this->da( 'floats' ), array( 'icon', 'text' ) ),
			'text'
		);

		$this->end_controls_section();

		$this->content_section( 'zc_hero_code', 'کارت کد (نمایشی)' );
		$code = new Repeater();
		$this->f_text( $code, 'left', 'سمت چپ خط', '' );
		$this->f_text( $code, 'right', 'سمت راست خط' );
		$this->f_repeater(
			'code_rows',
			'خطوط کد',
			$code->get_controls(),
			$this->pick_rows( $this->da( 'code_rows' ), array( 'left', 'right' ) ),
			'right'
		);
		$this->end_controls_section();

		$this->content_section( 'zc_hero_ticker', 'نوار متحرک فناوری‌ها' );
		$this->f_switch( $this, 'ticker_show', 'نمایش نوار متحرک' );
		$this->f_area( $this, 'ticker_items', 'عبارت‌ها (هر خط یک مورد)', implode( "\n", (array) $this->d( 'ticker', array() ) ) );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->style_shell();

		$this->start_controls_section( 'zc_style_hero', array( 'label' => 'عنوان و متن هیرو', 'tab' => C::TAB_STYLE ) );
		$this->add_control( 'hero_title_heading', array( 'label' => 'عنوان', 'type' => C::HEADING ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'hero_title_type',
			'label'    => 'تایپوگرافی',
			'selector' => '{{WRAPPER}} .zc-hp h1.big',
		) );
		$this->add_control( 'hero_title_color', array(
			'label'     => 'رنگ عنوان',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp h1.big' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hero_hl_color', array(
			'label'     => 'رنگ عبارت هایلایت',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp h1.big .hl' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hero_pop_color', array(
			'label'     => 'رنگ متن قرص پایانی',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp h1.big .pop' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hero_pop_bg', array(
			'label'     => 'پس‌زمینهٔ قرص پایانی',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp h1.big .pop' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'hero_lead_heading', array( 'label' => 'متن معرفی', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'hero_lead_type',
			'label'    => 'تایپوگرافی',
			'selector' => '{{WRAPPER}} .zc-hp .hero .lead',
		) );
		$this->add_control( 'hero_lead_color', array(
			'label'     => 'رنگ متن',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .hero .lead' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'hero_btn_heading', array( 'label' => 'دکمه‌ها', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'hero_btn_type',
			'label'    => 'تایپوگرافی',
			'selector' => '{{WRAPPER}} .zc-hp .hero-actions .btn',
		) );
		$this->add_responsive_control( 'hero_btn_radius', array(
			'label'      => 'گردی دکمه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .hero-actions .btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( 'hero_btn_padding', array(
			'label'      => 'فاصلهٔ داخلی دکمه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .hero-actions .btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_control( 'hero_trust_heading', array( 'label' => 'کارت‌های اعتماد', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'trust_bg', array(
			'label'     => 'رنگ پس‌زمینه',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .tcard' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'trust_shadow',
			'label'    => 'سایه',
			'selector' => '{{WRAPPER}} .zc-hp .tcard',
		) );
		$this->add_responsive_control( 'trust_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .tcard' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->end_controls_section();
	}

	protected function prepare( array $settings ): array {
		$settings = $this->urls_to_string( $settings, array( 'primary_url', 'secondary_url', 'tertiary_url' ) );

		foreach ( array( 'badges' => 'badges', 'trust' => 'trust', 'floats' => 'floats' ) as $key => $template_key ) {
			$settings[ $template_key ] = $this->rows_of( $settings, $key );
		}

		if ( 'yes' === ( $settings['real_stats'] ?? 'yes' ) ) {
			$settings['trust'] = $this->real_stats( (array) $settings['trust'] );
		}

		$show_ticker = ( $settings['ticker_show'] ?? 'yes' ) !== 'yes';
		if ( $show_ticker ) {
			$settings['ticker_items'] = array();
			return $settings;
		}
		$items = $this->to_lines( $settings['ticker_items'] ?? '' );
		$settings['ticker_items'] = $items;
		return $settings;
	}

	/**
	 * Replaces the first two trust values with numbers the site can prove:
	 * published projects and the average product rating. Values without data
	 * stay exactly as the user typed them.
	 *
	 * @param array $rows Trust rows (icon / value / label).
	 */
	private function real_stats( array $rows ): array {
		if ( ! $rows ) {
			return $rows;
		}

		$projects = post_type_exists( 'zc_project' ) ? (int) ( wp_count_posts( 'zc_project' )->publish ?? 0 ) : 0;
		if ( $projects > 0 && isset( $rows[0] ) ) {
			$rows[0]['value'] = sprintf( '+%s پروژه', number_format_i18n( $projects ) );
		}

		$rating = $this->average_product_rating();
		if ( $rating > 0 && isset( $rows[1] ) ) {
			$rows[1]['value'] = sprintf( '%s٪ رضایت', number_format_i18n( (int) round( $rating / 5 * 100 ) ) );
		}

		return $rows;
	}

	/**
	 * Site-wide average of WooCommerce product ratings (0 when nothing is
	 * rated). Bounded query: at most 60 rated products.
	 */
	private function average_product_rating(): float {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		if ( ! post_type_exists( 'product' ) ) {
			return $cached = 0.0;
		}

		if ( class_exists( '\\Zarincode\\Theme\\Home\\Home_Cache' ) ) {
			return $cached = (float) \Zarincode\Theme\Home\Home_Cache::remember( 'avg_rating', array( $this, 'compute_average_rating' ) );
		}

		return $cached = $this->compute_average_rating();
	}

	/** Uncached average rating (runs on a cold cache only). */
	public function compute_average_rating(): float {
		$ids = get_posts( array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'numberposts'      => 60,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'meta_key'         => '_wc_average_rating',
			'suppress_filters' => false,
		) );

		$sum = 0.0;
		$n   = 0;
		foreach ( (array) $ids as $id ) {
			$value = (float) get_post_meta( (int) $id, '_wc_average_rating', true );
			if ( $value > 0 ) {
				$sum += $value;
				++$n;
			}
		}

		if ( $n > 0 ) {
			return $sum / $n;
		}

		// Fresh stores may not have the cached lookup meta yet: fall back to the
		// approved review ratings themselves (one query + primed meta cache).
		$reviews = get_comments( array(
			'post_type' => 'product',
			'status'    => 'approve',
			'type'      => 'review',
			'meta_key'  => 'rating',
			'number'    => 200,
		) );

		$sum = 0.0;
		$n   = 0;
		foreach ( (array) $reviews as $review ) {
			$value = (float) get_comment_meta( (int) $review->comment_ID, 'rating', true );
			if ( $value > 0 ) {
				$sum += $value;
				++$n;
			}
		}

		return $n > 0 ? $sum / $n : 0.0;
	}

	protected function render(): void {
		$slug = $this->section_slug();
		if ( ! class_exists( Home_Templates::class ) ) {
			return;
		}
		$settings = $this->prepare( $this->get_settings_for_display() );
		$html     = Home_Templates::hero( $settings );
		$ticker   = ! empty( $settings['ticker_items'] ) ? Home_Templates::ticker( array( 'items' => $settings['ticker_items'] ) ) : '';
		echo Home_Templates::wrap_open( $this->is_flat( $settings ) ) . $html . $ticker . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ================================================================ PARTNERS */

class Home_Partners extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_partners';
	}

	public function get_title(): string {
		return 'صفحه اصلی — کاروسل برندها';
	}

	public function get_icon(): string {
		return 'eicon-carousel';
	}

	protected function section_slug(): string {
		return 'partners';
	}

	protected function register_controls(): void {
		$this->content_section( 'zc_partners', 'متن‌های بخش' );
		$this->source_controls( 'source', 'count', 'برندها', 'فهرست دستی برندها (دلخواه)', 'نام کارفرمایان پروژه‌های واقعی سایت', 12, 1, 40 );
		$this->f_text( $this, 'label', 'برچسب بخش', $this->ds( 'label' ) );
		// NOTE: `count` is already the number-of-items control added by
		// source_controls(). Registering a second control with the same id made
		// Elementor log «Cannot redeclare control with same name "count"» on
		// every panel load. The counter caption lives in `count_label`.
		$this->f_text( $this, 'count_label', 'شمارنده', $this->ds( 'count' ), array( 'description' => 'مثال: +۲۴ سازمان — با گزینهٔ زیر می‌توانید خودکار شود.' ) );
		$this->f_switch( $this, 'auto_count', 'شمارندهٔ خودکار از تعداد برندها', 'yes' );
		$this->f_text( $this, 'note', 'یادداشت پایین', $this->ds( 'note' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_partners_items', 'برندها', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$this->f_text( $row, 'name', 'نام برند' );
		$this->f_icon( $row, 'icon', 'building' );
		$this->f_url( $row, 'link', 'پیوند' );
		$this->f_repeater(
			'items',
			'برندها',
			$row->get_controls(),
			$this->pick_rows( $this->da( 'items' ), array( 'name', 'icon', 'link' ) ),
			'name'
		);
		$this->end_controls_section();

		$this->style_shell();
		$this->style_cards( 'کارت برندها', '.cbox2.partners', '.plogo', '.note2', '.plogo' );
	}

	protected function prepare( array $settings ): array {
		// The caption control is `count_label`; the template prints `count`.
		if ( isset( $settings['count_label'] ) && '' !== trim( (string) $settings['count_label'] ) ) {
			$settings['count'] = (string) $settings['count_label'];
		}
		if ( 'manual' !== ( $settings['source'] ?? 'query' ) ) {
			$this->fill_from_projects( $settings );
		} else {
			$this->set_items( $settings, $this->rows_of( $settings, 'items' ) );
		}
		return $settings;
	}

	/**
	 * Lists the clients written in the «کارفرما» field of real published
	 * projects — the brand bar then shows work that actually exists on the site,
	 * each logo linking to its project.
	 *
	 * @param array $settings Widget settings, updated in place.
	 */
	private function fill_from_projects( array &$settings ): void {
		if ( ! post_type_exists( 'zc_project' ) ) {
			$this->set_items( $settings, $this->rows_of( $settings, 'items' ) );
			return;
		}

		$limit = max( 1, (int) ( $settings['count'] ?? 12 ) );
		if ( class_exists( '\\Zarincode\\Theme\\Home\\Home_Cache' ) ) {
			$items = (array) \Zarincode\Theme\Home\Home_Cache::remember( 'brands', array( $this, 'brands_from_projects' ) );
			$items = array_slice( $items, 0, $limit );
			$this->set_items( $settings, $items );
			if ( 'yes' === ( $settings['auto_count'] ?? 'yes' ) && $items ) {
				$settings['count'] = sprintf( '+%s برند', number_format_i18n( count( $items ) ) );
			}
			return;
		}

		$posts = get_posts( array(
			'post_type'        => 'zc_project',
			'post_status'      => 'publish',
			'numberposts'      => 60,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		) );

		$items = array();
		$seen  = array();
		foreach ( $posts as $post ) {
			$client = trim( (string) get_post_meta( (int) $post->ID, '_zc_prj_client', true ) );
			$key    = mb_strtolower( $client );
			if ( '' === $client || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$items[]      = array(
				'name' => $client,
				'icon' => 'building',
				'link' => get_permalink( $post ),
			);
			if ( count( $items ) >= $limit ) {
				break;
			}
		}

		$this->set_items( $settings, $items );

		// The counter can follow the real number of brands.
		if ( 'yes' === ( $settings['auto_count'] ?? 'yes' ) && $items ) {
			$settings['count'] = sprintf( '+%s برند', number_format_i18n( count( $items ) ) );
		}
	}

	/**
	 * Every unique client name written on a published project, newest first.
	 * Cached; used when the cache is warm.
	 */
	public function brands_from_projects(): array {
		if ( ! post_type_exists( 'zc_project' ) ) {
			return array();
		}

		$posts = get_posts( array(
			'post_type'        => 'zc_project',
			'post_status'      => 'publish',
			'numberposts'      => 60,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		) );

		$items = array();
		$seen  = array();
		foreach ( $posts as $post ) {
			$client = trim( (string) get_post_meta( (int) $post->ID, '_zc_prj_client', true ) );
			$key    = mb_strtolower( $client );
			if ( '' === $client || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$items[]      = array(
				'name' => $client,
				'icon' => 'building',
				'link' => get_permalink( $post ),
			);
		}

		return $items;
	}
}

/* ================================================================ SERVICES */

class Home_Services extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_services';
	}

	public function get_title(): string {
		return 'صفحه اصلی — خدمات';
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	protected function section_slug(): string {
		return 'services';
	}

	protected function register_controls(): void {
		$this->heading_controls();

		$this->content_section( 'zc_services_source', 'منبع خدمات' );
		$this->source_controls( 'source', 'count', 'خدمات', 'کارت‌های دستی (دلخواه)', 'خدمات واقعی سایت (نوشته‌های «خدمات»)', 6, 1, 12 );
		$this->end_controls_section();

		$this->content_section( 'zc_services_items', 'کارت‌های خدمات', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'display' );
		$this->f_text( $row, 'no', 'شمارهٔ کارت', '' );
		$this->f_text( $row, 'en', 'برچسب انگلیسی', '' );
		$this->f_text( $row, 'title', 'عنوان خدمت' );
		$this->f_area( $row, 'desc', 'توضیح', '', array( 'rows' => 2 ) );
		$this->f_text( $row, 'chip1_text', 'چیپ ۱ — متن', '' );
		$this->f_icon( $row, 'chip1_icon', 'clock' );
		$this->f_text( $row, 'chip2_text', 'چیپ ۲ — متن', '' );
		$this->f_icon( $row, 'chip2_icon', 'shield-halved' );
		$this->f_text( $row, 'price', 'برچسب قیمت', '' );
		$this->f_url( $row, 'link', 'پیوند کارت' );
		$this->f_text( $row, 'link_text', 'متن پیوند کارت', 'جزئیات' );

		$defaults = array();
		foreach ( $this->da( 'items' ) as $item ) {
			$meta           = is_array( $item['meta'] ?? null ) ? $item['meta'] : array();
			$defaults[]     = array(
				'icon'        => $item['icon'] ?? 'display',
				'no'          => $item['no'] ?? '',
				'en'          => $item['en'] ?? '',
				'title'       => $item['title'] ?? '',
				'desc'        => $item['desc'] ?? '',
				'chip1_text'  => $meta[0]['text'] ?? '',
				'chip1_icon'  => $meta[0]['icon'] ?? 'clock',
				'chip2_text'  => $meta[1]['text'] ?? '',
				'chip2_icon'  => $meta[1]['icon'] ?? 'shield-halved',
				'price'       => $item['price'] ?? '',
				'link'        => array( 'url' => (string) ( $item['link'] ?? '' ) ),
				'link_text'   => $item['link_text'] ?? 'جزئیات',
			);
		}

		$this->f_repeater( 'items', 'خدمات', $row->get_controls(), $defaults, 'title' );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading();
		$this->style_cards( 'کارت خدمات', '.svc', '.sbody h3', '.sbody p', '.ico-tile' );
	}

	protected function prepare( array $settings ): array {
		if ( 'manual' !== ( $settings['source'] ?? 'query' ) ) {
			$this->fill_from_services( $settings );
			return $settings;
		}

		$items = array();
		foreach ( $this->rows_of( $settings, 'items' ) as $index => $row ) {
			$meta = array();
			foreach ( array( 1, 2 ) as $n ) {
				$text = trim( (string) ( $row[ 'chip' . $n . '_text' ] ?? '' ) );
				if ( '' !== $text ) {
					$meta[] = array(
						'icon' => (string) ( $row[ 'chip' . $n . '_icon' ] ?? 'clock' ),
						'text' => $text,
					);
				}
			}
			$items[] = array(
				'no'        => (string) ( $row['no'] ?? '' ) ?: sprintf( '%02d', $index + 1 ),
				'en'        => (string) ( $row['en'] ?? '' ),
				'icon'      => (string) ( $row['icon'] ?? 'display' ),
				'title'     => (string) ( $row['title'] ?? '' ),
				'desc'      => (string) ( $row['desc'] ?? '' ),
				'meta'      => $meta,
				'price'     => (string) ( $row['price'] ?? '' ),
				'link'      => $this->link_of( $row['link'] ?? '' ),
				'link_text' => (string) ( $row['link_text'] ?? '' ) ?: 'جزئیات',
			);
		}
		$this->set_items( $settings, $items );
		return $settings;
	}

	/**
	 * Builds the service cards from published «خدمات» (zc_service) posts so the
	 * homepage shows the same content the archive and single templates use.
	 *
	 * @param array $settings Widget settings, updated in place.
	 */
	private function fill_from_services( array &$settings ): void {
		$limit = max( 1, (int) ( $settings['count'] ?? 6 ) );
		$posts = get_posts( array(
			'post_type'             => 'zc_service',
			'post_status'           => 'publish',
			'numberposts'           => $limit,
			'orderby'               => 'menu_order date',
			'order'                 => 'ASC',
			'update_post_term_cache' => false, // Services never print terms.
			'suppress_filters'      => false,
		) );

		if ( ! $posts ) {
			return; // Falls back to the widget's own manual/default rows.
		}

		// Font Awesome class => the short icon names this template understands.
		$icon_alias = array(
			'laptop-code'       => 'display',
			'desktop'           => 'display',
			'code'              => 'gears',
			'microchip'         => 'server',
			'chart-line'        => 'chart-line',
			'arrow-trend-up'    => 'arrow-trend-up',
			'hashtag'           => 'hashtag',
			'pen-nib'           => 'pen-nib',
			'pen-ruler'         => 'pen-ruler',
			'box-open'          => 'cubes',
			'cubes'             => 'cubes',
			'cart-shopping'     => 'cart-shopping',
			'magnifying-glass'  => 'magnifying-glass',
			'shield-halved'     => 'shield-halved',
			'rocket'            => 'rocket',
			'wordpress'         => 'wordpress',
			'palette'           => 'palette',
			'robot'             => 'robot',
			'comments'          => 'comments',
		);

		$tones = array( 'sc-a', 'sc-b', 'sc-c', 'sc-d', 'sc-e', 'sc-f' );
		$items = array();
		foreach ( $posts as $index => $post ) {
			$id       = (int) $post->ID;
			$fa_class = (string) get_post_meta( $id, '_zc_srv_icon', true );
			$short    = 'display';
			if ( '' !== $fa_class ) {
				foreach ( $icon_alias as $needle => $icon ) {
					if ( false !== strpos( $fa_class, $needle ) ) {
						$short = $icon;
						break;
					}
				}
			}

			$meta = array();
			$duration = trim( (string) get_post_meta( $id, '_zc_srv_duration', true ) );
			if ( '' !== $duration ) {
				$meta[] = array( 'icon' => 'clock', 'text' => $duration );
			}
			$delivery = trim( (string) get_post_meta( $id, '_zc_srv_final_delivery', true ) );
			if ( '' === $delivery ) {
				$delivery = trim( (string) get_post_meta( $id, '_zc_srv_delivery_mode', true ) );
			}
			if ( '' !== $delivery ) {
				$meta[] = array( 'icon' => 'shield-halved', 'text' => $delivery );
			}

			$description = $post->post_excerpt;
			if ( '' === trim( (string) $description ) ) {
				$description = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24 );
			}

			$items[] = array(
				'no'        => sprintf( '%02d', $index + 1 ),
				'en'        => (string) get_post_meta( $id, '_zc_srv_en_title', true ),
				'icon'      => $short,
				'title'     => get_the_title( $post ),
				'desc'      => $description,
				'meta'      => $meta,
				'price'     => (string) get_post_meta( $id, '_zc_srv_price_range', true ),
				'link'      => get_permalink( $id ),
				'image'     => (string) ( get_the_post_thumbnail_url( $id, 'medium_large' ) ?: '' ),
				'link_text' => 'جزئیات خدمت',
				'tone'      => $tones[ $index % 6 ],
			);
		}
		$this->set_items( $settings, $items );
	}
}

/* ================================================================ PRODUCTS */

class Home_Products extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_products';
	}

	public function get_title(): string {
		return 'صفحه اصلی — محصولات دیجیتال';
	}

	public function get_icon(): string {
		return 'eicon-cart-medium';
	}

	protected function section_slug(): string {
		return 'products';
	}

	protected function register_controls(): void {
		$this->heading_controls( true );

		$this->content_section( 'zc_products_source', 'منبع محصولات' );
		$this->source_controls( 'source', 'count', 'محصولات', 'کارت‌های دستی (دلخواه)', 'محصولات واقعی ووکامرس', 4, 1, 12 );
		$this->f_text( $this, 'currency', 'واحد قیمت', $this->ds( 'currency', 'تومان' ) );
		$this->f_text( $this, 'cta_text', 'متن دکمهٔ کارت', $this->ds( 'cta_text', 'مشاهده' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_products_items', 'کارت‌های محصول', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'cubes' );
		$this->f_text( $row, 'kind', 'نوع محصول', '', array( 'description' => 'مثال: سورس‌کد / قالب' ) );
		$this->f_text( $row, 'title', 'نام محصول' );
		$this->f_text( $row, 'tags', 'برچسب‌ها (با کاما)', '' );
		$this->f_text( $row, 'discount', 'نشان تخفیف', '' );
		$this->f_text( $row, 'old', 'قیمت قبلی', '' );
		$this->f_text( $row, 'now', 'قیمت فعلی', '' );
		$this->f_switch( $row, 'featured', 'برجسته', 'no' );
		$this->f_url( $row, 'link', 'پیوند محصول' );

		$defaults = array();
		foreach ( $this->da( 'items' ) as $item ) {
			$defaults[] = array(
				'icon'     => $item['icon'] ?? 'cubes',
				'kind'     => $item['kind'] ?? '',
				'title'    => $item['title'] ?? '',
				'tags'     => implode( ', ', (array) ( $item['tags'] ?? array() ) ),
				'discount' => $item['discount'] ?? '',
				'old'      => $item['old'] ?? '',
				'now'      => $item['now'] ?? '',
				'featured' => empty( $item['featured'] ) ? 'no' : 'yes',
				'link'     => array( 'url' => (string) ( $item['link'] ?? '' ) ),
			);
		}

		$this->f_repeater( 'items', 'محصولات', $row->get_controls(), $defaults, 'title' );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading( true );
		$this->style_cards( 'کارت محصول', '.pcard', '.pbody h4', '.pchips span', '.ico-tile' );
	}

	protected function prepare( array $settings ): array {
		$settings = $this->urls_to_string( $settings, array( 'link_url' ) );

		if ( 'manual' !== ( $settings['source'] ?? 'query' ) && function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products( array(
				'status'  => 'publish',
				'limit'   => max( 1, (int) ( $settings['count'] ?? 4 ) ),
				'orderby' => 'popularity',
			) );

			if ( $products ) {
				$items = array();
				foreach ( $products as $product ) {
					$regular = (float) $product->get_regular_price();
					$price   = (float) $product->get_price();
					$items[] = array(
						'icon'     => 'cubes',
						'discount' => '',
						'kind'     => 'محصول دیجیتال',
						'title'    => $product->get_name(),
						'tags'     => array_slice( wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ) ?: array(), 0, 3 ),
						'old'      => $regular > $price ? number_format_i18n( $regular, 0 ) : '',
						'now'      => number_format_i18n( $price, 0 ),
						'link'     => get_permalink( $product->get_id() ),
						'image'    => (string) ( wp_get_attachment_image_url( (int) $product->get_image_id(), 'medium_large' ) ?: '' ),
						'featured' => $product->is_featured() ? 'yes' : '',
					);
				}
				if ( $items ) {
					$this->set_items( $settings, $items );
					return $settings;
				}
			}
			// No published products (yet): fall through to the configured cards so
			// the section never renders an empty grid.
		}

		$items = array();
		foreach ( $this->rows_of( $settings, 'items' ) as $row ) {
			$items[] = array(
				'icon'     => (string) ( $row['icon'] ?? 'cubes' ),
				'kind'     => (string) ( $row['kind'] ?? '' ),
				'title'    => (string) ( $row['title'] ?? '' ),
				'image'    => $this->media_url( $row['image'] ?? '' ),
				'tags'     => $this->to_list( $row['tags'] ?? '' ),
				'discount' => (string) ( $row['discount'] ?? '' ),
				'old'      => (string) ( $row['old'] ?? '' ),
				'now'      => (string) ( $row['now'] ?? '' ),
				'featured' => ( $row['featured'] ?? '' ) === 'yes' ? 'yes' : '',
				'link'     => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$this->set_items( $settings, $items );
		return $settings;
	}
}

/* =============================================================== PORTFOLIO */

class Home_Portfolio extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_portfolio';
	}

	public function get_title(): string {
		return 'صفحه اصلی — نمونه‌کارها';
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	protected function section_slug(): string {
		return 'portfolio';
	}

	protected function register_controls(): void {
		$this->heading_controls( true );

		$this->content_section( 'zc_portfolio_source', 'منبع پروژه‌ها' );
		$this->source_controls( 'source', 'count', 'پروژه‌ها', 'کارت‌های دستی (دلخواه)', 'پروژه‌های واقعی (نمونه‌کار)', 3, 1, 9 );
		$this->f_text( $this, 'cta_text', 'متن پیوند کارت', $this->ds( 'cta_text', 'اطلاعات پروژه' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_portfolio_items', 'کارت‌های پروژه', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'display' );
		$this->f_text( $row, 'title', 'عنوان پروژه' );
		$this->f_text( $row, 'year', 'سال / فصل' );
		$this->f_text( $row, 'cat', 'دسته‌بندی' );
		$this->f_text( $row, 'kpi', 'دستاورد', '', array( 'description' => 'مثال: +۳۵٪ نرخ تبدیل' ) );
		$this->f_select( $row, 'tone', 'رنگ برچسب', array( 'c1' => 'طلایی', 'c2' => 'نعنایی', 'c3' => 'بنفش' ), 'c1' );
		$this->f_url( $row, 'link', 'پیوند پروژه' );

		$defaults = array();
		foreach ( $this->da( 'items' ) as $item ) {
			$defaults[] = array(
				'icon'  => $item['icon'] ?? 'display',
				'title' => $item['title'] ?? '',
				'year'  => $item['year'] ?? '',
				'cat'   => $item['cat'] ?? '',
				'kpi'   => $item['kpi'] ?? '',
				'tone'  => $item['tone'] ?? 'c1',
				'link'  => array( 'url' => (string) ( $item['link'] ?? '' ) ),
			);
		}

		$this->f_repeater( 'items', 'پروژه‌ها', $row->get_controls(), $defaults, 'title' );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading( true );
		$this->style_cards( 'کارت پروژه', '.jcard', '.jbody h4', '.jrow .cat', '.ico-tile' );
	}

	protected function prepare( array $settings ): array {
		$settings = $this->urls_to_string( $settings, array( 'link_url' ) );
		$items    = array();

		if ( 'manual' !== ( $settings['source'] ?? 'query' ) ) {
			$posts = get_posts( array(
				'post_type'      => 'zc_project',
				'numberposts'    => max( 1, (int) ( $settings['count'] ?? 3 ) ),
				'post_status'    => 'publish',
				'ignore_sticky_posts' => true,
			) );
			foreach ( $posts as $index => $post ) {
				$items[] = array(
					'year'  => (string) get_post_meta( $post->ID, '_zc_prj_date', true ),
					'icon'  => 'display',
					'kpi'   => (string) get_post_meta( $post->ID, '_zc_prj_metrics', true ),
					'title' => get_the_title( $post ),
					'cat'   => implode( '، ', wp_get_post_terms( $post->ID, 'zc_project_cat', array( 'fields' => 'names' ) ) ?: array() ),
					'tone'  => array( 'c1', 'c2', 'c3' )[ $index % 3 ],
					'image' => (string) ( get_the_post_thumbnail_url( $post, 'large' ) ?: '' ),
					'link'  => get_permalink( $post ),
				);
			}
			$this->set_items( $settings, $items );
			return $settings; // No projects yet — keep the configured cards.
		}

		foreach ( $this->rows_of( $settings, 'items' ) as $row ) {
			$items[] = array(
				'icon'  => (string) ( $row['icon'] ?? 'display' ),
				'title' => (string) ( $row['title'] ?? '' ),
				'year'  => (string) ( $row['year'] ?? '' ),
				'cat'   => (string) ( $row['cat'] ?? '' ),
				'kpi'   => (string) ( $row['kpi'] ?? '' ),
				'tone'  => (string) ( $row['tone'] ?? 'c1' ),
				'link'  => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$this->set_items( $settings, $items );
		return $settings;
	}
}

/* ===================================================================== WHY */

class Home_Why extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_why';
	}

	public function get_title(): string {
		return 'صفحه اصلی — چرا زرین کد';
	}

	public function get_icon(): string {
		return 'eicon-info-circle';
	}

	protected function section_slug(): string {
		return 'why';
	}

	protected function register_controls(): void {
		$this->heading_controls();

		$this->content_section( 'zc_why_items', 'کارت‌ها (بنتو)' );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'compass' );
		$this->f_text( $row, 'title', 'عنوان' );
		$this->f_area( $row, 'text', 'متن', '', array( 'rows' => 2 ) );
		$this->f_repeater(
			'items',
			'کارت‌ها',
			$row->get_controls(),
			$this->pick_rows( $this->da( 'items' ), array( 'icon', 'title', 'text' ) ),
			'title'
		);
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading();
		$this->style_cards( 'کارت‌ها', '.bcard', '.bcard h4', '.bcard p', '.bcard .em' );
	}

	protected function prepare( array $settings ): array {
		$settings['items'] = $this->rows_of( $settings, 'items' );
		return $settings;
	}
}

/* =================================================================== STEPS */

class Home_Steps extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_steps';
	}

	public function get_title(): string {
		return 'صفحه اصلی — مراحل همکاری';
	}

	public function get_icon(): string {
		return 'eicon-time-line';
	}

	protected function section_slug(): string {
		return 'steps';
	}

	protected function register_controls(): void {
		$this->heading_controls();

		$this->content_section( 'zc_steps_items', 'قدم‌ها' );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'bullseye' );
		$this->f_text( $row, 'title', 'عنوان قدم' );
		$this->f_area( $row, 'text', 'توضیح', '', array( 'rows' => 2 ) );
		$this->f_repeater(
			'items',
			'قدم‌ها',
			$row->get_controls(),
			$this->pick_rows( $this->da( 'items' ), array( 'icon', 'title', 'text' ) ),
			'title'
		);
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading();
		$this->style_cards( 'کارت مرحله', '.step', '.step b', '.step p', '.step .sic' );
	}

	protected function prepare( array $settings ): array {
		$settings['items'] = $this->rows_of( $settings, 'items' );
		return $settings;
	}
}

/* ================================================================= PRICING */

class Home_Pricing extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_pricing';
	}

	public function get_title(): string {
		return 'صفحه اصلی — تعرفه‌ها';
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	protected function section_slug(): string {
		return 'pricing';
	}

	protected function register_controls(): void {
		$this->heading_controls();

		$this->content_section( 'zc_pricing_plans', 'پلن‌ها' );
		$this->f_text( $this, 'tabs', 'تب‌های بالای پلن‌ها (با کاما)', implode( ', ', (array) $this->d( 'tabs', array() ) ) );
		$this->f_text( $this, 'price_label', 'برچسب بالای قیمت', $this->ds( 'price_label', 'بازهٔ سرمایه‌گذاری' ) );
		$this->f_text( $this, 'price_unit', 'واحد قیمت', $this->ds( 'price_unit', 'میلیون تومان' ) );

		$row = new Repeater();
		$this->f_text( $row, 'title', 'نام پلن' );
		$this->f_text( $row, 'track', 'مسیر (تب) این پلن', '', array( 'description' => 'دقیقاً یکی از عنوان‌های تب‌ها؛ تا کارت زیر همان تب نمایش داده شود.' ) );
		$this->f_text( $row, 'delivery', 'مدت تحویل' );
		$this->f_text( $row, 'price', 'بازهٔ قیمت' );
		$this->f_text( $row, 'flag', 'نشان پلن' );
		$this->f_select( $row, 'tone', 'رنگ پلن', array( 'p1' => 'آسمانی', 'p2' => 'طلایی (ویژه)', 'p3' => 'بنفش' ), 'p1' );
		$this->f_area( $row, 'features', 'مزایا (هر خط یک مورد)', '', array( 'rows' => 4 ) );
		$this->f_text( $row, 'cta', 'متن دکمه', 'مشاوره و ثبت سفارش' );
		$this->f_url( $row, 'link', 'پیوند دکمه' );

		$defaults = array();
		foreach ( $this->da( 'plans' ) as $plan ) {
			$defaults[] = array(
				'title'    => $plan['title'] ?? '',
				'track'    => $plan['track'] ?? '',
				'delivery' => $plan['delivery'] ?? '',
				'price'    => $plan['price'] ?? '',
				'flag'     => $plan['flag'] ?? '',
				'tone'     => $plan['tone'] ?? 'p1',
				'features' => implode( "\n", (array) ( $plan['features'] ?? array() ) ),
				'cta'      => $plan['cta'] ?? 'مشاوره و ثبت سفارش',
				'link'     => array( 'url' => (string) ( $plan['link'] ?? '' ) ),
			);
		}

		$this->f_repeater( 'plans', 'پلن‌ها', $row->get_controls(), $defaults, 'title' );
		$this->end_controls_section();

		$this->content_section( 'zc_pricing_matrix', 'جدول مقایسه' );
		$matrix = $this->dn( 'matrix' );
		$this->f_text( $this, 'matrix_head', 'سرستون‌ها (با کاما)', implode( ', ', (array) ( $matrix['head'] ?? array() ) ) );
		$this->f_text( $this, 'matrix_foot', 'پاورقی جدول (با کاما)', implode( ', ', (array) ( $matrix['foot'] ?? array() ) ) );
		$mrow = new Repeater();
		$this->f_text( $mrow, 'feature', 'ویژگی' );
		$this->f_text( $mrow, 'col_1', 'ستون ۱' );
		$this->f_text( $mrow, 'col_2', 'ستون ۲' );
		$this->f_text( $mrow, 'col_3', 'ستون ۳' );
		$mrows = array();
		foreach ( (array) ( $matrix['rows'] ?? array() ) as $cells ) {
			$mrows[] = array(
				'feature' => $cells[0] ?? '',
				'col_1'   => $cells[1] ?? '',
				'col_2'   => $cells[2] ?? '',
				'col_3'   => $cells[3] ?? '',
			);
		}
		$this->f_repeater( 'matrix_rows', 'ردیف‌های جدول', $mrow->get_controls(), $mrows, 'feature' );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading();
		$this->style_cards( 'کارت پلن', '.plan', '.plan h4', '.plan .sub', '.plan .flag' );

		$this->start_controls_section( 'zc_style_price', array( 'label' => 'قیمت و جدول', 'tab' => C::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'price_type',
			'label'    => 'تایپوگرافی قیمت',
			'selector' => '{{WRAPPER}} .zc-hp .pprice b',
		) );
		$this->add_control( 'price_color', array(
			'label'     => 'رنگ قیمت',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .pprice b' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'matrix_heading', array( 'label' => 'جدول مقایسه', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'matrix_bg', array(
			'label'     => 'پس‌زمینهٔ جدول',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .matrix' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'matrix_type',
			'label'    => 'تایپوگرافی جدول',
			'selector' => '{{WRAPPER}} .zc-hp .matrix table',
		) );
		$this->add_control( 'matrix_color', array(
			'label'     => 'رنگ متن جدول',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .matrix td, {{WRAPPER}} .zc-hp .matrix th' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function prepare( array $settings ): array {
		$plans = array();
		foreach ( $this->rows_of( $settings, 'plans' ) as $row ) {
			$plans[] = array(
				'title'    => (string) ( $row['title'] ?? '' ),
				'track'    => (string) ( $row['track'] ?? '' ),
				'delivery' => (string) ( $row['delivery'] ?? '' ),
				'price'    => (string) ( $row['price'] ?? '' ),
				'flag'     => (string) ( $row['flag'] ?? '' ),
				'tone'     => (string) ( $row['tone'] ?? 'p1' ),
				'features' => $this->to_lines( $row['features'] ?? '' ),
				'cta'      => (string) ( $row['cta'] ?? '' ) ?: 'مشاوره و ثبت سفارش',
				'link'     => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$settings['plans'] = $plans;

		$settings['tabs'] = $this->to_list( $settings['tabs'] ?? '' );

		$rows = array();
		foreach ( $this->rows_of( $settings, 'matrix_rows' ) as $row ) {
			$rows[] = array(
				(string) ( $row['feature'] ?? '' ),
				(string) ( $row['col_1'] ?? '' ),
				(string) ( $row['col_2'] ?? '' ),
				(string) ( $row['col_3'] ?? '' ),
			);
		}
		$settings['matrix'] = array(
			'head' => $this->to_list( $settings['matrix_head'] ?? '' ),
			'rows' => $rows,
			'foot' => $this->to_list( $settings['matrix_foot'] ?? '' ),
		);

		return $settings;
	}
}

/* ============================================================ TESTIMONIALS */

class Home_Testimonials extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_testimonials';
	}

	public function get_title(): string {
		return 'صفحه اصلی — نظرات مشتریان';
	}

	public function get_icon(): string {
		return 'eicon-testimonial';
	}

	protected function section_slug(): string {
		return 'testimonials';
	}

	protected function register_controls(): void {
		$this->heading_controls();

		$this->content_section( 'zc_testimonials_source', 'منبع نظرات' );
		$this->source_controls( 'source', 'count', 'نظرات', 'نظرات دستی (دلخواه)', 'دیدگاه‌های واقعی محصولات (ووکامرس)', 3, 1, 12 );
		$this->f_switch( $this, 'use_gravatars', 'استفاده از تصویر پروفایل نویسنده در حالت پویا', 'yes' );
		$this->end_controls_section();

		$this->content_section( 'zc_testimonials_items', 'نظرات', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$row->add_control( 'photo', array(
			'label'       => 'عکس مشتری',
			'type'        => C::MEDIA,
			'label_block' => true,
			'description' => 'تصویر واقعی مشتری؛ اگر خالی بماند حرف اول نام نمایش داده می‌شود.',
		) );
		$this->f_area( $row, 'text', 'متن نظر', '', array( 'rows' => 3 ) );
		$this->f_text( $row, 'name', 'نام' );
		$this->f_text( $row, 'role', 'سمت / سازمان' );
		$this->f_text( $row, 'initial', 'حرف اول آواتار', '', array( 'description' => 'یک حرف یا نویسه؛ خالی بگذارید تا از نام ساخته شود.' ) );
		$this->f_number( $row, 'stars', 'امتیاز (۰ تا ۵ — صفر: بدون ستاره)', 5, 0, 5 );
		$this->f_url( $row, 'link', 'پیوند (نمونه‌کار یا صفحهٔ مشتری) — اختیاری' );
		$testimonial_rows = array();
		foreach ( $this->da( 'items' ) as $item ) {
			$testimonial_rows[] = array(
				'photo'   => array( 'url' => (string) ( $item['photo'] ?? '' ) ),
				'text'    => (string) ( $item['text'] ?? '' ),
				'name'    => (string) ( $item['name'] ?? '' ),
				'role'    => (string) ( $item['role'] ?? '' ),
				'initial' => (string) ( $item['initial'] ?? '' ),
				'stars'   => (int) ( $item['stars'] ?? 5 ),
				'link'    => array( 'url' => (string) ( $item['link'] ?? '' ) ),
			);
		}
		$this->f_repeater( 'items', 'نظرات', $row->get_controls(), $testimonial_rows, 'name' );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading();
		$this->style_cards( 'کارت نظر', '.tst', '.tst .who b', '.tst p', '.tst .ava' );

		$this->start_controls_section( 'zc_style_stars', array( 'label' => 'ستاره‌ها', 'tab' => C::TAB_STYLE ) );
		$this->add_control( 'stars_color', array(
			'label'     => 'رنگ ستاره‌ها',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .tst .stars' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'quote_color', array(
			'label'     => 'رنگ علامت نقل‌قول',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .tst .qm' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function prepare( array $settings ): array {
		if ( 'manual' !== ( $settings['source'] ?? 'query' ) ) {
			$this->fill_from_reviews( $settings );
			return $settings;
		}

		$items = array();
		foreach ( $this->rows_of( $settings, 'items' ) as $row ) {
			$name    = (string) ( $row['name'] ?? '' );
			$initial = trim( (string) ( $row['initial'] ?? '' ) );
			$items[] = array(
				'photo'   => $this->media_url( $row['photo'] ?? '' ),
				'initial' => '' !== $initial ? $initial : mb_substr( $name, 0, 1 ),
				'stars'   => max( 0, min( 5, (int) ( $row['stars'] ?? 5 ) ) ),
				'text'    => (string) ( $row['text'] ?? '' ),
				'name'    => $name,
				'role'    => (string) ( $row['role'] ?? '' ),
				'link'    => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$this->set_items( $settings, $items );
		return $settings;
	}

	/**
	 * Normalises a MEDIA control value (array|url|id) to an image URL.
	 *
	 * @param mixed $value Raw control value.
	 */
	protected function media_url( $value ): string {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['url'] ) ) {
				return (string) $value['url'];
			}
			$value = $value['id'] ?? '';
		}
		if ( is_numeric( $value ) ) {
			return (string) ( wp_get_attachment_image_url( (int) $value, 'thumbnail' ) ?: '' );
		}
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Fills the cards from real WooCommerce product reviews so the section is
	 * fed by customer feedback that already lives on the site.
	 *
	 * @param array $settings Widget settings, updated in place.
	 */
	private function fill_from_reviews( array &$settings ): void {
		if ( ! post_type_exists( 'product' ) ) {
			return;
		}

		$limit   = max( 1, (int) ( $settings['count'] ?? 3 ) );
		$reviews = get_comments( array(
			'status'    => 'approve',
			'post_type' => 'product',
			'number'    => $limit,
			'orderby'   => 'comment_date_gmt',
			'order'     => 'DESC',
			'type'      => 'review',
		) );

		if ( ! $reviews ) {
			return;
		}

		$use_gravatar = 'yes' === ( $settings['use_gravatars'] ?? 'yes' );
		$items        = array();
		foreach ( $reviews as $review ) {
			$rating = (int) get_comment_meta( (int) $review->comment_ID, 'rating', true );
			$name   = (string) $review->comment_author;
			$items[] = array(
				'photo'   => $use_gravatar ? (string) get_avatar_url( (string) $review->comment_author_email, array( 'size' => 96 ) ) : '',
				'initial' => mb_substr( $name, 0, 1 ),
				'stars'   => max( 0, min( 5, $rating > 0 ? $rating : 5 ) ),
				'text'    => wp_trim_words( wp_strip_all_tags( (string) $review->comment_content ), 34 ),
				'name'    => $name,
				'role'    => sprintf( 'خریدار %s', get_the_title( (int) $review->comment_post_ID ) ),
				'link'    => get_permalink( (int) $review->comment_post_ID ),
			);
		}
		$this->set_items( $settings, $items );
	}
}

/* ==================================================================== BLOG */

class Home_Blog extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_blog';
	}

	public function get_title(): string {
		return 'صفحه اصلی — مجله';
	}

	public function get_icon(): string {
		return 'eicon-posts-masonry';
	}

	protected function section_slug(): string {
		return 'blog';
	}

	protected function register_controls(): void {
		$this->heading_controls( true );

		$this->content_section( 'zc_blog_source', 'منبع مقالات' );
		$this->source_controls( 'source', 'count', 'مقالات', 'کارت‌های دستی (دلخواه)', 'آخرین نوشته‌های واقعی سایت', 3, 3, 7 );
		$this->f_text( $this, 'read_more', 'متن پیوند «ادامه»', $this->ds( 'read_more', 'ادامهٔ مطلب' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_blog_featured', 'مقالهٔ شاخص', array( 'source' => 'manual' ) );
		$featured = $this->dn( 'featured' );
		$this->f_icon( $this, 'f_icon', 'newspaper' );
		$this->f_text( $this, 'f_cat', 'دسته‌بندی', (string) ( $featured['cat'] ?? '' ) );
		$this->f_text( $this, 'f_seal', 'نشان ویژه', (string) ( $featured['seal'] ?? '' ) );
		$this->f_text( $this, 'f_title', 'عنوان', (string) ( $featured['title'] ?? '' ) );
		$this->f_area( $this, 'f_excerpt', 'خلاصه', (string) ( $featured['excerpt'] ?? '' ) );
		$this->f_text( $this, 'f_author', 'نویسنده', (string) ( $featured['author'] ?? '' ) );
		$this->f_text( $this, 'f_date', 'تاریخ', (string) ( $featured['date'] ?? '' ) );
		$this->f_text( $this, 'f_read', 'زمان مطالعه', (string) ( $featured['read'] ?? '' ) );
		$this->f_url( $this, 'f_link', 'پیوند مقاله', (string) ( $featured['link'] ?? '' ) );
		$this->end_controls_section();

		$this->content_section( 'zc_blog_side', 'مقالات کنار', array( 'source' => 'manual' ) );
		$row = new Repeater();
		$this->f_icon( $row, 'icon', 'newspaper' );
		$this->f_text( $row, 'pill', 'برچسب روی کاور' );
		$this->f_select( $row, 'tone', 'رنگ کارت', array( 'sc1' => 'آسمانی', 'sc2' => 'نعنایی', 'sc3' => 'هلویی' ), 'sc1' );
		$this->f_text( $row, 'title', 'عنوان' );
		$this->f_area( $row, 'excerpt', 'خلاصه', '', array( 'rows' => 2 ) );
		$this->f_text( $row, 'tags', 'برچسب‌ها (با کاما)' );
		$this->f_text( $row, 'author', 'نویسنده' );
		$this->f_text( $row, 'date', 'تاریخ' );
		$this->f_text( $row, 'read', 'زمان مطالعه' );
		$this->f_url( $row, 'link', 'پیوند' );

		$defaults = array();
		foreach ( $this->da( 'side' ) as $item ) {
			$defaults[] = array(
				'icon'    => $item['icon'] ?? 'newspaper',
				'pill'    => $item['pill'] ?? '',
				'tone'    => $item['tone'] ?? 'sc1',
				'title'   => $item['title'] ?? '',
				'excerpt' => $item['excerpt'] ?? '',
				'tags'    => implode( ', ', (array) ( $item['tags'] ?? array() ) ),
				'author'  => $item['author'] ?? '',
				'date'    => $item['date'] ?? '',
				'read'    => $item['read'] ?? '',
				'link'    => array( 'url' => (string) ( $item['link'] ?? '' ) ),
			);
		}
		$this->f_repeater( 'side_items', 'مقالات کنار', $row->get_controls(), $defaults, 'title' );
		$this->end_controls_section();

		$this->content_section( 'zc_blog_popular', 'پربازدیدترین‌ها و خبرنامه' );
		$this->f_text( $this, 'popular_title', 'عنوان باکس پربازدید', $this->ds( 'popular_title', 'پربازدیدترین‌ها' ) );
		$this->f_text( $this, 'popular_sub', 'زیرنویس باکس', $this->ds( 'popular_sub', '' ) );
		$prow = new Repeater();
		$this->f_text( $prow, 'title', 'عنوان مقاله' );
		$this->f_text( $prow, 'meta', 'زیرنویس' );
		$this->f_url( $prow, 'link', 'پیوند مقاله' );
		$this->f_repeater(
			'popular_items',
			'پربازدیدترین‌ها',
			$prow->get_controls(),
			$this->pick_rows( $this->da( 'popular' ), array( 'title', 'meta', 'link' ) ),
			'title'
		);
		$newsletter = $this->dn( 'newsletter' );
		$this->f_text( $this, 'news_title', 'خبرنامه — عنوان', (string) ( $newsletter['title'] ?? '' ) );
		$this->f_text( $this, 'news_desc', 'خبرنامه — توضیح', (string) ( $newsletter['desc'] ?? '' ) );
		$this->f_text( $this, 'news_placeholder', 'خبرنامه — متن جای ورودی', (string) ( $newsletter['placeholder'] ?? '' ) );
		$this->f_text( $this, 'news_button', 'خبرنامه — متن دکمه', (string) ( $newsletter['button'] ?? '' ) );
		$this->end_controls_section();

		$this->style_shell();
		$this->style_heading( true );
		$this->style_cards( 'کارت مقاله', '.scard', '.scard h3', '.scard p', '.scover2 .g' );
	}

	protected function prepare( array $settings ): array {
		$settings = $this->urls_to_string( $settings, array( 'link_url', 'f_link' ) );

		if ( 'manual' !== ( $settings['source'] ?? 'query' ) ) {
			$this->fill_from_posts( $settings );
			return $settings;
		}

		$settings['featured'] = array(
			'icon'    => (string) ( $settings['f_icon'] ?? 'newspaper' ),
			'cat'     => (string) ( $settings['f_cat'] ?? '' ),
			'seal'    => (string) ( $settings['f_seal'] ?? '' ),
			'title'   => (string) ( $settings['f_title'] ?? '' ),
			'excerpt' => (string) ( $settings['f_excerpt'] ?? '' ),
			'author'  => (string) ( $settings['f_author'] ?? '' ),
			'date'    => (string) ( $settings['f_date'] ?? '' ),
			'read'    => (string) ( $settings['f_read'] ?? '' ),
			'link'    => (string) ( $settings['f_link'] ?? '' ),
		);

		$side = array();
		foreach ( $this->rows_of( $settings, 'side_items' ) as $row ) {
			$side[] = array(
				'icon'    => (string) ( $row['icon'] ?? 'newspaper' ),
				'pill'    => (string) ( $row['pill'] ?? '' ),
				'tone'    => (string) ( $row['tone'] ?? 'sc1' ),
				'title'   => (string) ( $row['title'] ?? '' ),
				'excerpt' => (string) ( $row['excerpt'] ?? '' ),
				'tags'    => $this->to_list( $row['tags'] ?? '' ),
				'author'  => (string) ( $row['author'] ?? '' ),
				'date'    => (string) ( $row['date'] ?? '' ),
				'read'    => (string) ( $row['read'] ?? '' ),
				'link'    => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$settings['side'] = $side;

		$popular = array();
		foreach ( $this->rows_of( $settings, 'popular_items' ) as $row ) {
			$popular[] = array(
				'title' => (string) ( $row['title'] ?? '' ),
				'meta'  => (string) ( $row['meta'] ?? '' ),
				'link'  => $this->link_of( $row['link'] ?? '' ),
			);
		}
		$settings['popular'] = $popular;

		$settings['newsletter'] = array(
			'title'       => (string) ( $settings['news_title'] ?? '' ),
			'desc'        => (string) ( $settings['news_desc'] ?? '' ),
			'placeholder' => (string) ( $settings['news_placeholder'] ?? '' ),
			'button'      => (string) ( $settings['news_button'] ?? '' ),
		);

		return $settings;
	}

	/** Builds the three blog slots from the newest published posts. */
	private function fill_from_posts( array &$settings ): void {
		$posts = get_posts( array(
			'numberposts' => max( 3, (int) ( $settings['count'] ?? 3 ) ),
			'post_status' => 'publish',
		) );
		if ( ! $posts ) {
			return;
		}

		// One query for every category of the listed posts instead of one per post.
		$cat_map = array();
		if ( $posts ) {
			$ids     = array_map( static function ( $p ) { return (int) $p->ID; }, $posts );
			$all     = wp_get_object_terms( $ids, 'category', array( 'fields' => 'all_with_object_id' ) );
			if ( ! is_wp_error( $all ) ) {
				foreach ( $all as $term ) {
					if ( ! isset( $cat_map[ (int) $term->object_id ] ) ) {
						$cat_map[ (int) $term->object_id ] = (string) $term->name;
					}
				}
			}
		}
		$first_cat = static function ( int $id ) use ( &$cat_map ): string {
			return $cat_map[ $id ] ?? 'مجلهٔ زرین کد';
		};
		$date_of = static function ( int $id ): string {
			return class_exists( '\Zarincode\Theme\Core\Setup' )
				? \Zarincode\Theme\Core\Setup::jalali_date( (int) get_post_time( 'U', true, $id ) )
				: (string) get_the_date( '', $id );
		};
		$reading = static function ( $post ): string {
			$words = str_word_count( wp_strip_all_tags( (string) $post->post_content ) );
			return number_format_i18n( max( 1, (int) ceil( $words / 200 ) ) ) . ' دقیقه مطالعه';
		};

		$featured = array_shift( $posts );
		$settings['featured'] = array(
			'icon'    => 'newspaper',
			'cat'     => $first_cat( (int) $featured->ID ),
			'seal'    => 'تازه‌ترین مقاله',
			'title'   => get_the_title( $featured ),
			'image'   => (string) ( get_the_post_thumbnail_url( $featured, 'large' ) ?: '' ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( $featured->post_excerpt ?: $featured->post_content ), 22 ),
			'author'  => get_the_author_meta( 'display_name', (int) $featured->post_author ),
			'date'    => $date_of( (int) $featured->ID ),
			'read'    => $reading( $featured ),
			'link'    => get_permalink( $featured ),
		);

		$side = array();
		foreach ( array_slice( $posts, 0, 2 ) as $index => $post ) {
			$side[] = array(
				'icon'    => array( 'arrow-trend-up', 'robot' )[ $index % 2 ],
				'pill'    => $first_cat( (int) $post->ID ),
				'tone'    => array( 'sc1', 'sc2' )[ $index % 2 ],
				'title'   => get_the_title( $post ),
				'image'   => (string) ( get_the_post_thumbnail_url( $post, 'medium_large' ) ?: '' ),
				'excerpt' => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 16 ),
				'tags'    => array( $first_cat( (int) $post->ID ), 'زرین کد' ),
				'author'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'date'    => $date_of( (int) $post->ID ),
				'read'    => $reading( $post ),
				'link'    => get_permalink( $post ),
			);
		}
		$settings['side'] = $side;

		$popular = array();
		foreach ( get_posts( array( 'numberposts' => 3, 'orderby' => 'comment_count', 'post_status' => 'publish' ) ) as $post ) {
			$popular[] = array(
				'title' => get_the_title( $post ),
				'meta'  => $first_cat( (int) $post->ID ) . ' · ' . $reading( $post ),
				'link'  => get_permalink( $post ),
			);
		}
		if ( $popular ) {
			$settings['popular'] = $popular;
		}
	}
}

/* =================================================================== PROMO */

class Home_Promo extends Abstract_Home_Widget {

	public function get_name(): string {
		return 'zarincode_home_promo';
	}

	public function get_title(): string {
		return 'صفحه اصلی — فراخوان پایانی';
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	protected function section_slug(): string {
		return 'promo';
	}

	protected function register_controls(): void {
		$this->content_section( 'zc_promo_content', 'محتوای فراخوان' );
		$this->f_text( $this, 'badge', 'برچسب', $this->ds( 'badge' ) );
		$this->f_text( $this, 'title', 'عنوان', $this->ds( 'title' ) );
		$this->f_area( $this, 'text', 'توضیح', $this->ds( 'text' ) );
		$this->f_text( $this, 'primary_text', 'دکمهٔ اصلی — متن', $this->ds( 'primary_text' ) );
		$this->f_url( $this, 'primary_url', 'دکمهٔ اصلی — پیوند', $this->ds( 'primary_url' ) );
		$this->f_text( $this, 'secondary_text', 'دکمهٔ دوم — متن', $this->ds( 'secondary_text' ) );
		$this->f_url( $this, 'secondary_url', 'دکمهٔ دوم — پیوند', $this->ds( 'secondary_url' ) );
		$this->end_controls_section();

		$this->style_shell();

		$this->start_controls_section( 'zc_style_promo', array( 'label' => 'کارت فراخوان', 'tab' => C::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'promo_bg',
			'label'    => 'پس‌زمینهٔ کارت',
			'types'    => array( 'classic', 'gradient' ),
			'exclude'  => Editor_Controls::BACKGROUND_EXCLUDE,
			'selector' => '{{WRAPPER}} .zc-hp .promo',
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'promo_shadow',
			'label'    => 'سایه',
			'selector' => '{{WRAPPER}} .zc-hp .promo',
		) );
		$this->add_responsive_control( 'promo_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .promo' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( 'promo_padding', array(
			'label'      => 'فاصلهٔ داخلی',
			'type'       => C::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .zc-hp .promo' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_control( 'promo_title_heading', array( 'label' => 'عنوان', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'promo_title_type',
			'label'    => 'تایپوگرافی',
			'selector' => '{{WRAPPER}} .zc-hp .promo h2',
		) );
		$this->add_control( 'promo_title_color', array(
			'label'     => 'رنگ عنوان',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .promo h2' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'promo_text_heading', array( 'label' => 'توضیح', 'type' => C::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'promo_text_type',
			'label'    => 'تایپوگرافی',
			'selector' => '{{WRAPPER}} .zc-hp .promo p',
		) );
		$this->add_control( 'promo_text_color', array(
			'label'     => 'رنگ متن',
			'type'      => C::COLOR,
			'selectors' => array( '{{WRAPPER}} .zc-hp .promo p' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function prepare( array $settings ): array {
		return $this->urls_to_string( $settings, array( 'primary_url', 'secondary_url' ) );
	}
}
