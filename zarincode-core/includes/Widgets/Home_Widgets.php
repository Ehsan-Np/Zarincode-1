<?php
/**
 * Homepage Elementor widgets — "cheerful + formal" homepage (v4.6.0).
 *
 * Each widget renders one homepage section through Home_Templates and exposes
 * every heading plus item repeaters in the Elementor panel, so the page stays
 * 100% editable without touching code.
 *
 * @package Zarincode\Runtime260\Widgets
 */

namespace Zarincode\Runtime260\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Zarincode\Theme\Home\Home_Templates;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_Home_Widget extends Widget_Base {

	public function get_categories(): array {
		return array( 'zarincode' );
	}

	public function get_keywords(): array {
		return array( 'zarincode', 'home', 'صفحه اصلی' );
	}

	public function get_icon(): string {
		return 'eicon-post-content';
	}

	public function get_style_depends(): array {
		return array( 'zarincode-home-cheerful' );
	}

	protected function section_slug(): string {
		return '';
	}

	/** Section heading controls shared by the content sections. */
	protected function heading_controls( bool $with_after = false ): void {
		$this->start_controls_section( 'zc_head', array( 'label' => 'عنوان و توضیح بخش' ) );
		$this->add_control( 's_badge', array( 'label' => 'برچسب بالای عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 's_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 's_highlight', array( 'label' => 'بخش هایلایت‌شدهٔ عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		if ( $with_after ) {
			$this->add_control( 's_after', array( 'label' => 'ادامهٔ عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		}
		$this->add_control( 's_desc', array( 'label' => 'توضیح زیر عنوان', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ) );
		$this->add_control( 's_more_text', array( 'label' => 'متن پیوند «مشاهده همه»', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 's_more_url', array( 'label' => 'نشانی پیوند «مشاهده همه»', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->end_controls_section();
	}

	protected function settings_with_head(): array {
		$s = $this->get_settings_for_display();
		$map = array(
			'badge'           => 's_badge',
			'title'           => 's_title',
			'title_highlight' => 's_highlight',
			'title_after'     => 's_after',
			'desc'            => 's_desc',
			'link_text'       => 's_more_text',
		);
		foreach ( $map as $target => $key ) {
			if ( isset( $s[ $key ] ) && '' !== $s[ $key ] ) {
				$s[ $target ] = $s[ $key ];
			}
		}
		if ( ! empty( $s['s_more_url']['url'] ) ) {
			$s['more_url'] = $s['s_more_url']['url'];
		}
		return is_array( $s ) ? $s : array();
	}

	protected function render(): void {
		$slug = $this->section_slug();
		if ( '' === $slug || ! class_exists( Home_Templates::class ) ) {
			return;
		}
		$settings = $this->settings_with_head();
		echo Home_Templates::wrap_open() . Home_Templates::$slug( $settings ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput -- templates escape internally.
	}
}

/* ------------------------------------------------------------------ HERO */

class Home_Hero extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_hero';
	}
	public function get_title(): string {
		return 'صفحه اصلی — هیرو';
	}
	protected function section_slug(): string {
		return 'hero';
	}
	protected function register_controls(): void {
		$this->start_controls_section( 'zc_hero', array( 'label' => 'محتوای هیرو' ) );
		$this->add_control( 'h_line1', array( 'label' => 'خط اول عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_hl', array( 'label' => 'هایلایت طلایی', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_line2', array( 'label' => 'خط دوم عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_pop', array( 'label' => 'نشان تیرهٔ عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_lead', array( 'label' => 'متن معرفی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'default' => '' ) );
		$this->add_control( 'h_p1t', array( 'label' => 'دکمهٔ اصلی — متن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_p1u', array( 'label' => 'دکمهٔ اصلی — پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'h_p2t', array( 'label' => 'دکمهٔ دوم — متن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_p2u', array( 'label' => 'دکمهٔ دوم — پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'h_p3t', array( 'label' => 'دکمهٔ سوم — متن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_p3u', array( 'label' => 'دکمهٔ سوم — پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'h_score', array( 'label' => 'امتیاز (کارت تیره)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'h_score_l', array( 'label' => 'زیرنویس امتیاز', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'zc_hero_triple', array( 'label' => 'سه شاخص اعتماد' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->add_control( "t{$i}_v", array( 'label' => "شاخص {$i} — مقدار", 'type' => Controls_Manager::TEXT, 'default' => '' ) );
			$this->add_control( "t{$i}_l", array( 'label' => "شاخص {$i} — برچسب", 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		}
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->get_settings_for_display();
		$map = array(
			'title_line1' => 'h_line1', 'title_highlight' => 'h_hl', 'title_line2' => 'h_line2', 'title_pop' => 'h_pop',
			'lead' => 'h_lead', 'primary_text' => 'h_p1t', 'secondary_text' => 'h_p2t', 'tertiary_text' => 'h_p3t',
			'score_value' => 'h_score', 'score_label' => 'h_score_l',
		);
		$out = array();
		foreach ( $map as $k => $key ) {
			if ( isset( $s[ $key ] ) && '' !== $s[ $key ] ) {
				$out[ $k ] = $s[ $key ];
			}
		}
		foreach ( array( 'primary' => 'h_p1u', 'secondary' => 'h_p2u', 'tertiary' => 'h_p3u' ) as $k => $key ) {
			if ( ! empty( $s[ $key ]['url'] ) ) {
				$out[ $k . '_url' ] = $s[ $key ]['url'];
			}
		}
		$trust = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			if ( ! empty( $s[ "t{$i}_v" ] ) || ! empty( $s[ "t{$i}_l" ] ) ) {
				$trust[] = array( 'icon' => array( 'chart-line', 'handshake', 'bolt' )[ $i - 1 ], 'value' => $s[ "t{$i}_v" ] ?? '', 'label' => $s[ "t{$i}_l" ] ?? '' );
			}
		}
		if ( $trust ) {
			$out['trust'] = $trust;
		}
		echo Home_Templates::wrap_open() . Home_Templates::hero( $out ) . Home_Templates::ticker( array() ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ------------------------------------------------------------- PARTNERS */

class Home_Partners extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_partners';
	}
	public function get_title(): string {
		return 'صفحه اصلی — کاروسل برندها';
	}
	protected function section_slug(): string {
		return 'partners';
	}
	protected function register_controls(): void {
		$this->start_controls_section( 'zc_partners', array( 'label' => 'کاروسل شرکای همکار' ) );
		$this->add_control( 'p_label', array( 'label' => 'برچسب بخش', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'p_count', array( 'label' => 'متن شمارنده (مثلاً +۲۴ سازمان)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'p_note', array( 'label' => 'یادداشت پایین', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r = new Repeater();
		$r->add_control( 'name', array( 'label' => 'نام برند', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'logo', array( 'label' => 'لوگو', 'type' => Controls_Manager::MEDIA ) );
		$r->add_control( 'icon', array( 'label' => 'آیکون جایگزین لوگو', 'type' => Controls_Manager::TEXT, 'default' => 'building', 'description' => 'نام آیکون Font Awesome بدون پیشوند؛ مثل building یا wordpress' ) );
		$r->add_control( 'link', array( 'label' => 'پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'p_items', array( 'label' => 'برندها', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ name }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s   = $this->get_settings_for_display();
		$out = array();
		foreach ( array( 'label' => 'p_label', 'count' => 'p_count', 'note' => 'p_note' ) as $k => $key ) {
			if ( ! empty( $s[ $key ] ) ) {
				$out[ $k ] = $s[ $key ];
			}
		}
		if ( ! empty( $s['p_items'] ) && is_array( $s['p_items'] ) ) {
			$items = array();
			foreach ( $s['p_items'] as $row ) {
				$items[] = array(
					'name' => $row['name'] ?? '',
					'icon' => $row['icon'] ?? 'building',
					'logo' => $row['logo']['url'] ?? '',
				);
			}
			if ( $items ) {
				$out['items'] = $items;
			}
		}
		echo Home_Templates::wrap_open() . Home_Templates::partners( $out ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ------------------------------------------------------------- SERVICES */

class Home_Services extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_services';
	}
	public function get_title(): string {
		return 'صفحه اصلی — خدمات رنگین';
	}
	protected function section_slug(): string {
		return 'services';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_items', array( 'label' => 'کارت‌های خدمات' ) );
		$r = new Repeater();
		$r->add_control( 'icon', array( 'label' => 'آیکون', 'type' => Controls_Manager::TEXT, 'default' => 'display' ) );
		$r->add_control( 'en', array( 'label' => 'برچسب انگلیسی', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'title', array( 'label' => 'عنوان خدمت', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'desc', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ) );
		$r->add_control( 'meta', array( 'label' => 'چیپ‌های اطلاعاتی (با کاما جدا کنید)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'price', array( 'label' => 'برچسب قیمت', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'link', array( 'label' => 'پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'srv_items', array( 'label' => 'خدمات', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ title }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s    = $this->settings_with_head();
		$rows = $s['srv_items'] ?? array();
		if ( is_array( $rows ) && $rows ) {
			$items = array();
			foreach ( $rows as $i => $row ) {
				$meta = array();
				foreach ( array_filter( array_map( 'trim', explode( ',', (string) ( $row['meta'] ?? '' ) ) ) ) as $k => $m ) {
					$meta[] = array( 'icon' => 0 === $k ? 'clock' : 'shield-halved', 'text' => $m );
				}
				$items[] = array(
					'no'        => sprintf( '%02d', $i + 1 ),
					'en'        => $row['en'] ?? '',
					'icon'      => $row['icon'] ?? 'display',
					'title'     => $row['title'] ?? '',
					'desc'      => $row['desc'] ?? '',
					'meta'      => $meta,
					'price'     => $row['price'] ?? '',
					'link'      => $row['link']['url'] ?? '',
					'link_text' => 'جزئیات',
				);
			}
			$s['items'] = $items;
		}
		echo Home_Templates::wrap_open() . Home_Templates::services( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ------------------------------------------------------------- PRODUCTS */

class Home_Products extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_products';
	}
	public function get_title(): string {
		return 'صفحه اصلی — محصولات دیجیتال';
	}
	protected function section_slug(): string {
		return 'products';
	}
	protected function register_controls(): void {
		$this->heading_controls( true );
		$this->start_controls_section( 'zc_prod', array( 'label' => 'منبع محصولات' ) );
		$this->add_control( 'p_source', array(
			'label'   => 'منبع داده',
			'type'    => Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => array( 'manual' => 'کارت‌های دستی (پیش‌فرض دمو)', 'query' => 'محصولات واقعی ووکامرس' ),
		) );
		$this->add_control( 'p_count', array( 'label' => 'تعداد محصولات', 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 12, 'condition' => array( 'p_source' => 'query' ) ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		$s['source'] = $s['p_source'] ?? 'manual';
		if ( 'query' === $s['source'] && function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products( array( 'status' => 'publish', 'limit' => max( 1, (int) ( $s['p_count'] ?? 4 ) ), 'orderby' => 'popularity' ) );
			$items    = array();
			foreach ( $products as $p ) {
				$items[] = array(
					'icon'     => 'cubes',
					'discount' => '',
					'kind'     => 'محصول دیجیتال',
					'title'    => $p->get_name(),
					'tags'     => array_slice( wp_get_post_terms( $p->get_id(), 'product_tag', array( 'fields' => 'names' ) ) ?: array(), 0, 3 ),
					'old'      => $p->get_regular_price() !== $p->get_price() ? number_format_i18n( (float) $p->get_regular_price(), 0 ) : '',
					'now'      => number_format_i18n( (float) $p->get_price(), 0 ),
					'link'     => get_permalink( $p->get_id() ),
					'featured' => $p->is_featured() ? 'yes' : '',
				);
			}
			if ( $items ) {
				$s['items'] = $items;
			}
		}
		echo Home_Templates::wrap_open() . Home_Templates::products( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ------------------------------------------------------------ PORTFOLIO */

class Home_Portfolio extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_portfolio';
	}
	public function get_title(): string {
		return 'صفحه اصلی — نمونه‌کارها';
	}
	protected function section_slug(): string {
		return 'portfolio';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_pf', array( 'label' => 'منبع پروژه‌ها' ) );
		$this->add_control( 'pf_source', array(
			'label'   => 'منبع داده',
			'type'    => Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => array( 'manual' => 'کارت‌های دستی (پیش‌فرض دمو)', 'query' => 'پروژه‌های واقعی (CPT نمونه‌کار)' ),
		) );
		$this->add_control( 'pf_count', array( 'label' => 'تعداد پروژه', 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 9, 'condition' => array( 'pf_source' => 'query' ) ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( 'query' === ( $s['pf_source'] ?? 'manual' ) ) {
			$posts = get_posts( array( 'post_type' => 'zc_project', 'numberposts' => max( 1, (int) ( $s['pf_count'] ?? 3 ) ) ) );
			$items = array();
			foreach ( $posts as $p ) {
				$items[] = array(
					'year'  => (string) get_post_meta( $p->ID, '_zc_prj_date', true ),
					'icon'  => 'display',
					'kpi'   => (string) get_post_meta( $p->ID, '_zc_prj_metrics', true ),
					'title' => get_the_title( $p ),
					'cat'   => implode( '، ', wp_get_post_terms( $p->ID, 'zc_project_cat', array( 'fields' => 'names' ) ) ?: array() ),
					'tone'  => 'c1',
					'link'  => get_permalink( $p ),
				);
			}
			if ( $items ) {
				$s['items'] = $items;
			}
		}
		echo Home_Templates::wrap_open() . Home_Templates::portfolio( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ----------------------------------------------------------------- WHY */

class Home_Why extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_why';
	}
	public function get_title(): string {
		return 'صفحه اصلی — چرا زرین کد';
	}
	protected function section_slug(): string {
		return 'why';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_why', array( 'label' => 'کارت‌ها (بنتو)' ) );
		$r = new Repeater();
		$r->add_control( 'icon', array( 'label' => 'آیکون', 'type' => Controls_Manager::TEXT, 'default' => 'compass' ) );
		$r->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'text', array( 'label' => 'متن', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ) );
		$this->add_control( 'why_items', array( 'label' => 'کارت‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ title }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( ! empty( $s['why_items'] ) && is_array( $s['why_items'] ) ) {
			$s['items'] = array_map(
				static fn( $r ) => array( 'icon' => $r['icon'] ?? 'compass', 'title' => $r['title'] ?? '', 'text' => $r['text'] ?? '' ),
				$s['why_items']
			);
		}
		echo Home_Templates::wrap_open() . Home_Templates::why( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* --------------------------------------------------------------- STEPS */

class Home_Steps extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_steps';
	}
	public function get_title(): string {
		return 'صفحه اصلی — مراحل همکاری';
	}
	protected function section_slug(): string {
		return 'steps';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_steps', array( 'label' => 'قدم‌ها' ) );
		$r = new Repeater();
		$r->add_control( 'icon', array( 'label' => 'آیکون', 'type' => Controls_Manager::TEXT, 'default' => 'bullseye' ) );
		$r->add_control( 'title', array( 'label' => 'عنوان قدم', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => '' ) );
		$this->add_control( 'step_items', array( 'label' => 'قدم‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ title }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( ! empty( $s['step_items'] ) && is_array( $s['step_items'] ) ) {
			$s['items'] = array_map(
				static fn( $r ) => array( 'icon' => $r['icon'] ?? 'bullseye', 'title' => $r['title'] ?? '', 'text' => $r['text'] ?? '' ),
				$s['step_items']
			);
		}
		echo Home_Templates::wrap_open() . Home_Templates::steps( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* -------------------------------------------------------------- PRICING */

class Home_Pricing extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_pricing';
	}
	public function get_title(): string {
		return 'صفحه اصلی — تعرفه‌ها';
	}
	protected function section_slug(): string {
		return 'pricing';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_plans', array( 'label' => 'پلن‌ها' ) );
		$r = new Repeater();
		$r->add_control( 'title', array( 'label' => 'نام پلن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'delivery', array( 'label' => 'مدت تحویل', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'price', array( 'label' => 'بازه قیمت (میلیون تومان)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'flag', array( 'label' => 'نشان بالای پلن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'tone', array( 'label' => 'رنگ پلن', 'type' => Controls_Manager::SELECT, 'default' => 'p1', 'options' => array( 'p1' => 'آسمانی', 'p2' => 'طلایی (ویژه)', 'p3' => 'بنفش' ) ) );
		$r->add_control( 'features', array( 'label' => 'مزایا (هر خط یک مورد)', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'default' => '' ) );
		$r->add_control( 'link', array( 'label' => 'پیوند دکمه', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'plan_items', array( 'label' => 'پلن‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ title }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( ! empty( $s['plan_items'] ) && is_array( $s['plan_items'] ) ) {
			$plans = array();
			foreach ( $s['plan_items'] as $row ) {
				$plans[] = array(
					'title'    => $row['title'] ?? '',
					'delivery' => $row['delivery'] ?? '',
					'price'    => $row['price'] ?? '',
					'flag'     => $row['flag'] ?? '',
					'tone'     => $row['tone'] ?? 'p1',
					'features' => array_filter( array_map( 'trim', explode( "\n", (string) ( $row['features'] ?? '' ) ) ) ),
					'cta'      => 'مشاوره و ثبت سفارش',
					'link'     => $row['link']['url'] ?? '',
				);
			}
			$s['plans'] = $plans;
		}
		echo Home_Templates::wrap_open() . Home_Templates::pricing( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* --------------------------------------------------------- TESTIMONIALS */

class Home_Testimonials extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_testimonials';
	}
	public function get_title(): string {
		return 'صفحه اصلی — نظرات';
	}
	protected function section_slug(): string {
		return 'testimonials';
	}
	protected function register_controls(): void {
		$this->heading_controls( true );
		$this->start_controls_section( 'zc_tst', array( 'label' => 'نظرات' ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => 'متن نظر', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ) );
		$r->add_control( 'name', array( 'label' => 'نام', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'role', array( 'label' => 'سمت / سازمان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'stars', array( 'label' => 'امتیاز (۱ تا ۵)', 'type' => Controls_Manager::NUMBER, 'default' => 5, 'min' => 1, 'max' => 5 ) );
		$this->add_control( 'tst_items', array( 'label' => 'نظرات', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ name }}}', 'prevent_empty' => false ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( ! empty( $s['tst_items'] ) && is_array( $s['tst_items'] ) ) {
			$items = array();
			foreach ( $s['tst_items'] as $i => $row ) {
				$name    = (string) ( $row['name'] ?? '' );
				$items[] = array(
					'initial' => mb_substr( $name, 0, 1 ),
					'stars'   => (int) ( $row['stars'] ?? 5 ),
					'text'    => $row['text'] ?? '',
					'name'    => $name,
					'role'    => $row['role'] ?? '',
				);
			}
			$s['items'] = $items;
		}
		echo Home_Templates::wrap_open() . Home_Templates::testimonials( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ---------------------------------------------------------------- BLOG */

class Home_Blog extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_blog';
	}
	public function get_title(): string {
		return 'صفحه اصلی — مجله (شاخص + کناری)';
	}
	protected function section_slug(): string {
		return 'blog';
	}
	protected function register_controls(): void {
		$this->heading_controls();
		$this->start_controls_section( 'zc_blog', array( 'label' => 'منبع مقالات' ) );
		$this->add_control( 'b_source', array(
			'label'   => 'منبع داده',
			'type'    => Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => array( 'manual' => 'کارت‌های دستی (پیش‌فرض دمو)', 'query' => 'آخرین نوشته‌های واقعی' ),
		) );
		$this->add_control( 'b_count', array( 'label' => 'تعداد نوشته‌ها', 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 3, 'max' => 7, 'condition' => array( 'b_source' => 'query' ) ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->settings_with_head();
		if ( 'query' === ( $s['b_source'] ?? 'manual' ) ) {
			$posts = get_posts( array( 'numberposts' => max( 3, (int) ( $s['b_count'] ?? 3 ) ) ) );
			if ( $posts ) {
				$cats = static function ( $id ) {
					$t = wp_get_post_categories( $id, array( 'fields' => 'names' ) );
					return $t ? $t[0] : 'مجلهٔ زرین کد';
				};
				$date = static function ( $id ) {
					return class_exists( '\Zarincode\Theme\Core\Setup' )
						? \Zarincode\Theme\Core\Setup::jalali_date( (int) get_post_time( 'U', true, $id ) )
						: get_the_date( '', $id );
				};
				$first              = array_shift( $posts );
				$s['featured']      = array(
					'cat'     => $cats( $first->ID ),
					'seal'    => 'تازه‌ترین مقاله',
					'icon'    => 'newspaper',
					'title'   => get_the_title( $first ),
					'excerpt' => wp_trim_words( wp_strip_all_tags( $first->post_excerpt ?: $first->post_content ), 22 ),
					'author'  => get_the_author_meta( 'display_name', (int) $first->post_author ),
					'date'    => $date( $first->ID ),
					'read'    => max( 1, (int) ceil( str_word_count( wp_strip_all_tags( $first->post_content ) ) / 200 ) ) . ' دقیقه مطالعه',
					'link'    => get_permalink( $first ),
				);
				$side = array();
				foreach ( array_slice( $posts, 0, 2 ) as $i => $p ) {
					$side[] = array(
						'tags'    => array( $cats( $p->ID ), 'زرین کد' ),
						'pill'    => $cats( $p->ID ),
						'icon'    => array( 'arrow-trend-up', 'robot' )[ $i % 2 ],
						'title'   => get_the_title( $p ),
						'excerpt' => wp_trim_words( wp_strip_all_tags( $p->post_excerpt ?: $p->post_content ), 16 ),
						'author'  => get_the_author_meta( 'display_name', (int) $p->post_author ),
						'date'    => $date( $p->ID ),
						'read'    => '۵ دقیقه',
						'link'    => get_permalink( $p ),
						'tone'    => 0 === $i ? 'sc1' : 'sc2',
					);
				}
				$s['side'] = $side;
			}
		}
		echo Home_Templates::wrap_open() . Home_Templates::blog( $s ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* --------------------------------------------------------------- PROMO */

class Home_Promo extends Abstract_Home_Widget {
	public function get_name(): string {
		return 'zarincode_home_promo';
	}
	public function get_title(): string {
		return 'صفحه اصلی — فراخوان پایانی';
	}
	protected function section_slug(): string {
		return 'promo';
	}
	protected function register_controls(): void {
		$this->start_controls_section( 'zc_promo', array( 'label' => 'فراخوان پایانی' ) );
		$this->add_control( 'pr_badge', array( 'label' => 'برچسب', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'pr_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'pr_text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ) );
		$this->add_control( 'pr_p1t', array( 'label' => 'دکمه اصلی — متن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'pr_p1u', array( 'label' => 'دکمه اصلی — پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'pr_p2t', array( 'label' => 'دکمه دوم — متن', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'pr_p2u', array( 'label' => 'دکمه دوم — پیوند', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s   = $this->get_settings_for_display();
		$out = array();
		$map = array( 'badge' => 'pr_badge', 'title' => 'pr_title', 'text' => 'pr_text', 'primary_text' => 'pr_p1t', 'secondary_text' => 'pr_p2t' );
		foreach ( $map as $k => $key ) {
			if ( ! empty( $s[ $key ] ) ) {
				$out[ $k ] = $s[ $key ];
			}
		}
		foreach ( array( 'primary' => 'pr_p1u', 'secondary' => 'pr_p2u' ) as $k => $key ) {
			if ( ! empty( $s[ $key ]['url'] ) ) {
				$out[ $k . '_url' ] = $s[ $key ]['url'];
			}
		}
		echo Home_Templates::wrap_open() . Home_Templates::promo( $out ) . Home_Templates::wrap_close(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
