<?php
/**
 * Elementor-native page composition.
 *
 * The demo pages used to be one composite widget per section
 * (`[zarincode_widget type="home_hero"]`): every word was editable in the
 * Elementor panel, but the Navigator showed a single item, the section could
 * only be moved or restyled as a whole, and nothing inside it behaved like an
 * Elementor element. That is not an Elementor-based site — it is a shortcode
 * with extra steps.
 *
 * This composer writes the other thing: a plain **Container** tree in which
 * every visible piece is its own **native Elementor widget** —
 *
 *   Section (Container)
 *     ├─ Heading      «چه کاری برای شما انجام می‌دهیم؟»
 *     ├─ Text Editor  توضیح بخش
 *     └─ Grid (Container)
 *          ├─ Card (Container) ├─ Icon Box (icon + title + text)
 *          └─ Card (Container) ├─ Icon Box …
 *
 * so anything in the page can be selected, duplicated, re-ordered, deleted or
 * re-styled in the panel exactly like a widget a customer dropped in himself.
 * The approved design is preserved by `assets/css/eb.css`, which styles those
 * native widgets through the `zc-eb-*` classes the composer sets — never by
 * wrapping them in a custom renderer.
 *
 * Nothing here registers a widget, a shortcode or a template: it only builds
 * arrays. Re-running it is safe — `Demo_Seeder` writes the result through the
 * same upsert path as every other document.
 *
 * @package Zarincode\Runtime260\Elementor
 */

namespace Zarincode\Runtime260\Elementor;

defined( 'ABSPATH' ) || exit;

final class Page_Composer {

	/** Elementor container ids are 7 characters, like Elementor's own. */
	public static function id(): string {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/**
	 * Normalised Elementor icon value.
	 *
	 * Every icon in a composed page goes through `Icon_Compatibility`, so the
	 * value Elementor stores is a Font Awesome 5.15.3 name it can resolve in its
	 * own JSON (this is what keeps the log clean), while the theme renders the
	 * 7.x drawing through `assets/css/icon-aliases.css`.
	 */
	public static function icon( string $name, string $library = 'fa-solid' ): array {
		return \Zarincode\Runtime260\Widgets\Icon_Compatibility::value( array( 'value' => 'fas ' . $name, 'library' => $library ) );
	}

	/* ---------------------------------------------------------------------
	 * Primitives
	 * ------------------------------------------------------------------ */

	/**
	 * One native widget.
	 *
	 * @param string $type     Widget type (`heading`, `text-editor`, `button`, …).
	 * @param array  $settings Widget settings, written as the editor stores them.
	 * @param string $name     Navigator label (Persian, tells the client what it is).
	 * @param string $css      Extra CSS classes.
	 */
	public static function widget( string $type, array $settings = array(), string $name = '', string $css = '' ): array {
		if ( '' !== $name ) {
			$settings['_title'] = $name;
		}
		if ( '' !== $css ) {
			$settings['_css_classes'] = trim( ( $settings['_css_classes'] ?? '' ) . ' ' . $css );
		}
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings ? $settings : (object) array(),
		);
	}

	/**
	 * One Container.
	 *
	 * @param array $children Child nodes.
	 * @param array $args     direction (row|column), gap [col,row], width (%), align,
	 *                        justify, boxed (bool), css (classes), name (label),
	 *                        inner (bool), pad [t,r,b,l], mobile_width.
	 */
	public static function container( array $children, array $args = array(), bool $inner = true ): array {
		$gap   = $args['gap'] ?? array( 'column' => 24, 'row' => 24 );
		$width = $args['width'] ?? null;

		$settings = array(
			'content_width'        => empty( $args['boxed'] ) ? 'full' : 'boxed',
			'flex_direction'       => $args['direction'] ?? 'column',
			'flex_wrap'            => $args['wrap'] ?? 'wrap',
			'flex_justify_content' => $args['justify'] ?? 'flex-start',
			'flex_align_items'     => $args['align'] ?? 'stretch',
			'flex_gap'             => array(
				'column'   => (string) ( $gap['column'] ?? 24 ),
				'row'      => (string) ( $gap['row'] ?? 24 ),
				'unit'     => 'px',
				'isLinked' => false,
			),
		);
		$pad = array_map( 'floatval', (array) ( $args['pad'] ?? array( 0, 0, 0, 0 ) ) );
		if ( array_sum( $pad ) > 0 ) {
			// A zero padding is never written: Elementor would emit its own
			// `padding: 0` rule for the element, which outranks the layout rules
			// in eb.css and silently flattens the section rhythm.
			$settings['padding'] = array(
				'unit'     => 'px',
				'top'      => (string) ( $pad[0] ?? 0 ),
				'right'    => (string) ( $pad[1] ?? 0 ),
				'bottom'   => (string) ( $pad[2] ?? 0 ),
				'left'     => (string) ( $pad[3] ?? 0 ),
				'isLinked' => false,
			);
		}

		if ( null !== $width ) {
			$settings['content_width'] = 'full';
			$settings['width']         = array( 'unit' => '%', 'size' => (float) $width );
			$settings['_flex_size']    = 'custom';
			$settings['_flex_grow']    = 0;
			$settings['_flex_shrink']  = 1;
			if ( isset( $args['mobile_width'] ) ) {
				$settings['width_mobile']      = array( 'unit' => '%', 'size' => (float) $args['mobile_width'] );
				$settings['_flex_size_mobile'] = 'custom';
				$settings['_flex_grow_mobile'] = 0;
				$settings['_flex_shrink_mobile'] = 1;
			}
		}

		if ( ! empty( $args['name'] ) ) {
			$settings['_title'] = (string) $args['name'];
		}
		if ( ! empty( $args['css'] ) ) {
			/*
			 * Containers take their custom classes from `css_classes`; older
			 * Elementor builds read `_css_classes`. Both are written so the same
			 * document looks identical on either. (`_css_classes` is the key the
			 * *widget* controls use — see `widget()`.)
			 */
			$settings['css_classes']  = (string) $args['css'];
			$settings['_css_classes'] = (string) $args['css'];
		}

		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => $inner,
			'settings' => $settings,
			'elements' => array_values( $children ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Content widgets — thin wrappers with the project's defaults
	 * ------------------------------------------------------------------ */

	/**
	 * A native Heading.
	 *
	 * `$color` is written as the widget's own `title_color` setting rather than
	 * left to the stylesheet: Elementor prints its Kit rules after the theme CSS,
	 * so only a widget-level colour reliably wins — and the owner sees it in the
	 * panel instead of having to look for it in a file.
	 */
	public static function heading( string $text, string $tag = 'h2', string $css = '', string $name = 'عنوان', string $color = '' ): array {
		$settings = array(
			'title'       => $text,
			'header_size' => $tag,
			'align'       => 'right',
		);
		if ( '' !== $color ) {
			$settings['title_color'] = $color;
		}
		return self::widget( 'heading', $settings, $name, 'zc-eb-h ' . $css );
	}

	public static function text( string $html, string $css = '', string $name = 'متن', string $color = '' ): array {
		$settings = array( 'editor' => wpautop( $html ) );
		if ( '' !== $color ) {
			$settings['text_color'] = $color;
		}
		return self::widget( 'text-editor', $settings, $name, 'zc-eb-p ' . $css );
	}

	public static function button( string $label, string $url, string $css = '', string $name = 'دکمه', string $icon = '' ): array {
		$settings = array(
			'text'  => $label,
			'link'  => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
			'align' => 'right',
			'size'  => 'md',
		);
		if ( '' !== $icon ) {
			$settings['selected_icon'] = self::icon( $icon );
			$settings['icon_align']    = 'right';
		}
		return self::widget( 'button', $settings, $name, 'zc-eb-btn ' . $css );
	}

	public static function image( int $id, string $url, string $alt, string $css = '', string $name = 'تصویر' ): array {
		return self::widget(
			'image',
			array(
				'image'      => array( 'url' => $url, 'id' => $id ),
				'image_size' => 'large',
				'caption_source' => 'none',
				'alt'        => $alt,
			),
			$name,
			'zc-eb-img ' . $css
		);
	}

	/** Icon box: the native widget for «icon + title + text» cards. */
	public static function icon_box( string $icon, string $title, string $text, string $css = '', string $tag = 'h3', string $name = 'کارت' ): array {
		return self::widget(
			'icon-box',
			array(
				'selected_icon'   => self::icon( $icon ),
				'view'            => 'default',
				'title_text'      => $title,
				'description_text'=> $text,
				'title_size'      => $tag,
				'position'        => 'top',
				'text_align'      => 'right',
			),
			$name,
			'zc-eb-card ' . $css
		);
	}

	/**
	 * Native icon list.
	 *
	 * @param array $rows [ ['text' => …, 'icon' => 'fa-check', 'url' => ''], … ]
	 */
	public static function icon_list( array $rows, string $css = '', string $name = 'فهرست' ): array {
		$items = array();
		foreach ( $rows as $row ) {
			$item = array(
				'text'          => (string) ( $row['text'] ?? '' ),
				'selected_icon' => self::icon( (string) ( $row['icon'] ?? 'fa-check' ) ),
				'_id'           => self::id(),
			);
			if ( ! empty( $row['url'] ) ) {
				$item['link'] = array( 'url' => (string) $row['url'] );
			}
			$items[] = $item;
		}
		return self::widget(
			'icon-list',
			array(
				'icon_list'  => $items,
				'space_between'=> array( 'unit' => 'px', 'size' => 8 ),
				'view'       => 'traditional',
			),
			$name,
			'zc-eb-list ' . $css
		);
	}

	/** Small eyebrow pill above a heading (native Text Editor, coloured per widget). */
	public static function badge( string $text, string $css = '', string $name = 'برچسب بخش', string $color = '#8A6B10' ): array {
		return self::widget(
			'text-editor',
			array(
				'editor'     => '<p>' . esc_html( $text ) . '</p>',
				'text_color' => $color,
			),
			$name,
			'zc-eb-badge ' . $css
		);
	}

	public static function divider( string $css = '', string $name = 'جداکننده' ): array {
		return self::widget( 'divider', array( 'color' => 'rgba(41,44,49,.10)' ), $name, 'zc-eb-rule ' . $css );
	}

	/* ---------------------------------------------------------------------
	 * Composed blocks (still plain containers of the widgets above)
	 * ------------------------------------------------------------------ */

	/**
	 * Page section: a boxed column container with an optional tinted background.
	 *
	 * @param array $children Widgets/containers.
	 * @param array $args     ['css' => '…', 'name' => '…', 'id' => '…']
	 */
	public static function section( array $children, array $args = array() ): array {
		$css = 'zc-eb-sec ' . ( $args['css'] ?? '' );
		$settings = array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'_title'         => (string) ( $args['name'] ?? 'بخش صفحه' ),
			'css_classes'    => trim( $css ),
			'_css_classes'  => trim( $css ),
		);
		if ( ! empty( $args['anchor'] ) ) {
			$settings['_element_id'] = (string) $args['anchor'];
		}
		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => $settings,
			'elements' => array( self::container( $children, array( 'gap' => array( 'column' => 26, 'row' => 26 ), 'pad' => array( 0, 0, 0, 0 ), 'css' => 'zc-eb-wrap' ), true ) ),
		);
	}

	/**
	 * Section header: badge + title (with a highlighted fragment) + lead.
	 *
	 * The title is one native Heading widget whose text may contain
	 * `<span class="zc-eb-hl">…</span>`, so the highlight stays a typographic
	 * option rather than a second widget the client has to keep in sync.
	 */
	public static function head( string $badge, string $title, string $highlight, string $lead = '', array $args = array() ): array {
		$children = array();
		if ( '' !== $badge ) {
			$children[] = self::badge( $badge, '', 'برچسب بخش' );
		}
		$title_html = esc_html( $title );
		if ( '' !== $highlight ) {
			$title_html .= ' <span class="zc-eb-hl">' . esc_html( $highlight ) . '</span>';
		}
		$children[] = self::heading( $title_html, 'h2', 'zc-eb-title', 'عنوان بخش' );
		if ( '' !== $lead ) {
			$children[] = self::text( $lead, 'zc-eb-lead', 'توضیح بخش' );
		}
		if ( ! empty( $args['link_text'] ) && ! empty( $args['link_url'] ) ) {
			$children[] = self::button( (string) $args['link_text'], (string) $args['link_url'], 'is-ghost', 'دکمه بخش', 'fa-arrow-left' );
		}
		return self::container(
			$children,
			array(
				'direction' => 'column',
				'gap'       => array( 'column' => 10, 'row' => 10 ),
				'align'     => 'flex-start',
				'css'       => 'zc-eb-head',
				'name'      => 'سرصفحه بخش',
			)
		);
	}

	/** A responsive grid of cards. */
	public static function grid( array $cards, array $args = array() ): array {
		return self::container(
			$cards,
			array(
				'direction' => 'row',
				'gap'       => array( 'column' => 22, 'row' => 22 ),
				'align'     => (string) ( $args['align'] ?? 'stretch' ),
				'css'       => 'zc-eb-grid ' . ( $args['css'] ?? '' ),
				'name'      => (string) ( $args['name'] ?? 'شبکه کارت‌ها' ),
				'width'     => 100,
			)
		);
	}

	/**
	 * One card = a container holding the given widgets.
	 *
	 * `$push_action` distributes the free vertical space, which is how a row of
	 * cards keeps its buttons on one line even when the copy above them has
	 * different lengths.
	 */
	public static function card( array $children, string $css = '', string $name = 'کارت', bool $push_action = false ): array {
		return self::container(
			$children,
			array(
				'direction' => 'column',
				'gap'       => array( 'column' => 12, 'row' => 12 ),
				'align'     => 'stretch',
				'justify'   => $push_action ? 'space-between' : 'flex-start',
				'css'       => 'zc-eb-tile ' . $css,
				'name'      => $name,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Card factories for real site content
	 *
	 * Product, project and post grids appear on more than one page (home,
	 * shop, portfolio, blog). They are produced here once, from plain arrays
	 * the caller fills with live WordPress/WooCommerce data, so a grid always
	 * consists of native Image + Heading + Text + Button widgets.
	 * ------------------------------------------------------------------ */

	/**
	 * @param array  $products Items: title, excerpt, price, url, image, image_id.
	 * @param string $cta      Button label.
	 * @return array<int, array> Cards (containers).
	 */
	public static function product_cards( array $products, string $cta = 'مشاهده محصول' ): array {
		$cards = array();
		foreach ( $products as $product ) {
			$children = array();
			if ( ! empty( $product['image'] ) ) {
				$children[] = self::image( (int) ( $product['image_id'] ?? 0 ), (string) $product['image'], (string) ( $product['title'] ?? '' ), 'is-cover', 'تصویر محصول' );
			}
			$children[] = self::heading( (string) ( $product['title'] ?? '' ), 'h3', 'zc-eb-card-title', 'عنوان محصول' );
			if ( ! empty( $product['excerpt'] ) ) {
				$children[] = self::text( (string) $product['excerpt'], 'zc-eb-card-text', 'توضیح محصول' );
			}
			if ( ! empty( $product['price'] ) ) {
				$children[] = self::heading( (string) $product['price'], 'p', 'zc-eb-price', 'قیمت محصول' );
			}
			$children[] = self::button( $cta, (string) ( $product['url'] ?? '' ), 'is-gold is-sm', 'دکمه محصول', 'fa-cart-shopping' );
			$cards[]    = self::card( $children, 'is-product', 'محصول: ' . (string) ( $product['title'] ?? '' ), true );
		}
		return $cards;
	}

	/**
	 * @param array  $projects Items: title, excerpt, url, image, image_id.
	 * @param string $cta      Button label.
	 */
	public static function project_cards( array $projects, string $cta = 'اطلاعات پروژه' ): array {
		$cards = array();
		foreach ( $projects as $project ) {
			$children = array();
			if ( ! empty( $project['image'] ) ) {
				$children[] = self::image( (int) ( $project['image_id'] ?? 0 ), (string) $project['image'], (string) ( $project['title'] ?? '' ), 'is-cover', 'تصویر پروژه' );
			}
			$children[] = self::heading( (string) ( $project['title'] ?? '' ), 'h3', 'zc-eb-card-title', 'عنوان پروژه' );
			if ( ! empty( $project['excerpt'] ) ) {
				$children[] = self::text( (string) $project['excerpt'], 'zc-eb-card-text', 'توضیح پروژه' );
			}
			$children[] = self::button( $cta, (string) ( $project['url'] ?? '' ), 'is-ghost is-sm', 'دکمه پروژه', 'fa-arrow-left' );
			$cards[]    = self::card( $children, 'is-project', 'پروژه: ' . (string) ( $project['title'] ?? '' ), true );
		}
		return $cards;
	}

	/**
	 * @param array  $posts Items: title, excerpt, url, image, image_id.
	 * @param string $cta   Button label.
	 */
	public static function post_cards( array $posts, string $cta = 'ادامهٔ مطلب' ): array {
		$cards = array();
		foreach ( $posts as $post ) {
			$children = array();
			if ( ! empty( $post['image'] ) ) {
				$children[] = self::image( (int) ( $post['image_id'] ?? 0 ), (string) $post['image'], (string) ( $post['title'] ?? '' ), 'is-cover', 'تصویر مقاله' );
			}
			$children[] = self::heading( (string) ( $post['title'] ?? '' ), 'h3', 'zc-eb-card-title', 'عنوان مقاله' );
			if ( ! empty( $post['excerpt'] ) ) {
				$children[] = self::text( (string) $post['excerpt'], 'zc-eb-card-text', 'توضیح مقاله' );
			}
			$children[] = self::button( $cta, (string) ( $post['url'] ?? '' ), 'is-ghost is-sm', 'دکمه مقاله', 'fa-arrow-left' );
			$cards[]    = self::card( $children, 'is-post', 'مقاله: ' . (string) ( $post['title'] ?? '' ), true );
		}
		return $cards;
	}

	/* ---------------------------------------------------------------------
	 * Home page
	 * ------------------------------------------------------------------ */

	/**
	 * The homepage, section by section, as native Elementor widgets.
	 *
	 * @param array $c    home-content.json (hero, services, why, steps, pricing, …).
	 * @param array $data ['hero_image' => [id,url], 'products' => [...], 'projects' => [...], 'posts' => [...], 'clients' => [...]]
	 */
	public static function home( array $c, array $data = array() ): array {
		$tree = array();
		$hero = $c['hero'] ?? array();

		/* ── 1. Hero: badges, H1, lead, two CTAs, trust list, visual ───── */
		$badges = self::icon_list(
			array_map(
				static fn( $b ) => array( 'text' => (string) ( $b['text'] ?? '' ), 'icon' => 'fa-check' ),
				(array) ( $hero['badges'] ?? array() )
			),
			'is-badges',
			'نشان‌های اعتماد'
		);
		$hero_left = array(
			$badges,
			self::heading( esc_html( $hero['title_line1'] ?? '' ) . ' <span class="zc-eb-hl">' . esc_html( $hero['title_highlight'] ?? '' ) . '</span><br>' . esc_html( $hero['title_line2'] ?? '' ) . '<br><span class="zc-eb-pop">' . esc_html( $hero['title_pop'] ?? '' ) . '</span>', 'h1', 'zc-eb-hero-title', 'عنوان اصلی (H1)' ),
			self::text( (string) ( $hero['lead'] ?? '' ), 'zc-eb-hero-lead', 'متن معرفی' ),
			self::container(
				array(
					self::button( (string) ( $hero['primary_text'] ?? '' ), (string) ( $hero['primary_url'] ?? '/contact-us/' ), 'is-gold', 'دکمه اصلی', 'fa-paper-plane' ),
					self::button( (string) ( $hero['secondary_text'] ?? '' ), (string) ( $hero['secondary_url'] ?? '/projects-portfolio/' ), 'is-ink', 'دکمه دوم', 'fa-arrow-left' ),
					self::button( (string) ( $hero['tertiary_text'] ?? '' ), (string) ( $hero['tertiary_url'] ?? '/contact-us/' ), 'is-ghost', 'دکمه سوم', 'fa-phone' ),
				),
				array( 'direction' => 'row', 'gap' => array( 'column' => 12, 'row' => 12 ), 'css' => 'zc-eb-actions', 'name' => 'دکمه‌های اقدام' )
			),
			self::grid(
				array_map(
					static function ( $t ) {
						return self::card(
							array(
								self::heading( (string) ( $t['value'] ?? '' ), 'h3', 'zc-eb-stat-v', 'عدد' ),
								self::text( (string) ( $t['label'] ?? '' ), 'zc-eb-stat-l', 'توضیح عدد' ),
							),
							'is-stat',
							'کارت آمار'
						);
					},
					(array) ( $hero['trust'] ?? array() )
				),
				array( 'css' => 'is-stats', 'name' => 'آمار اعتماد', 'align' => 'flex-start' )
			),
		);
		$hero_right = array();
		if ( ! empty( $data['hero_image']['url'] ) ) {
			$hero_right[] = self::image( (int) ( $data['hero_image']['id'] ?? 0 ), (string) $data['hero_image']['url'], 'تیم مهندسی زرین کد در حال کار روی یک پروژهٔ نرم‌افزاری', 'is-hero', 'تصویر بخش قهرمان' );
		}
		$hero_right[] = self::card(
			array(
				self::heading( (string) ( $hero['score_value'] ?? '' ), 'h3', 'zc-eb-score-v', 'امتیاز', '#FDD05B' ),
				self::text( (string) ( $hero['score_label'] ?? '' ), 'zc-eb-score-l', 'برچسب امتیاز', '#FFFFFF' ),
			),
			'is-score',
			'کارت امتیاز مشتریان'
		);

		$tree[] = self::section(
			array(
				self::container(
					array(
						self::container( $hero_left, array( 'direction' => 'column', 'gap' => array( 'column' => 22, 'row' => 18 ), 'width' => 52, 'mobile_width' => 100, 'css' => 'zc-eb-hero-copy', 'name' => 'ستون متن قهرمان' ) ),
						self::container( $hero_right, array( 'direction' => 'column', 'gap' => array( 'column' => 16, 'row' => 16 ), 'width' => 44, 'mobile_width' => 100, 'css' => 'zc-eb-hero-art', 'name' => 'ستون تصویر قهرمان' ) ),
					),
					array( 'direction' => 'row', 'gap' => array( 'column' => 40, 'row' => 32 ), 'align' => 'center', 'css' => 'zc-eb-hero', 'name' => 'ردیف قهرمان' )
				),
			),
			array( 'css' => 'is-hero', 'name' => 'قهرمان صفحه' )
		);

		/* ── 2. Ticker / technology strip ─────────────────────────────── */
		if ( ! empty( $hero['ticker'] ) ) {
			$tree[] = self::section(
				array(
					self::icon_list(
						array_map(
							static fn( $t ) => array( 'text' => (string) $t, 'icon' => 'fa-bolt' ),
							(array) $hero['ticker']
						),
						'is-ticker',
						'نوار تکنولوژی‌ها'
					),
				),
				array( 'css' => 'is-strip', 'name' => 'نوار تکنولوژی' )
			);
		}

		/* ── 3. Partners ──────────────────────────────────────────────── */
		$partners = $c['partners'] ?? array();
		if ( ! empty( $data['clients'] ) ) {
			$cards = array();
			foreach ( array_slice( (array) $data['clients'], 0, 6 ) as $client ) {
				$cards[] = self::card(
					array(
						self::image( (int) ( $client['id'] ?? 0 ), (string) ( $client['url'] ?? '' ), (string) ( $client['alt'] ?? 'لوگوی مشتری' ), 'is-client', 'لوگوی مشتری' ),
					),
					'is-client',
					'کارت لوگوی مشتری'
				);
			}
			$tree[] = self::section(
				array(
					self::head( '', (string) ( $partners['label'] ?? 'شرکای فناوری' ), '', (string) ( $partners['note'] ?? '' ) ),
					self::grid( $cards, array( 'css' => 'is-clients', 'name' => 'شبکه لوگوها' ) ),
				),
				array( 'css' => 'is-partners', 'name' => 'شرکای فناوری' )
			);
		}

		/* ── 4. Services ──────────────────────────────────────────────── */
		$services = $c['services'] ?? array();
		$cards    = array();
		foreach ( (array) ( $services['items'] ?? array() ) as $i => $item ) {
			$meta = array();
			foreach ( (array) ( $item['meta'] ?? array() ) as $m ) {
				$meta[] = array( 'text' => (string) ( $m['text'] ?? '' ), 'icon' => 'fa-circle-check' );
			}
			$children = array(
				self::heading( (string) ( $item['no'] ?? sprintf( '%02d', $i + 1 ) ), 'span', 'zc-eb-no', 'شماره خدمت' ),
				self::icon_box( 'fa-' . ( $item['icon'] ?? 'display' ), (string) ( $item['title'] ?? '' ), (string) ( $item['desc'] ?? '' ), '', 'h3', 'کارت خدمت: ' . (string) ( $item['title'] ?? '' ) ),
			);
			if ( $meta ) {
				$children[] = self::icon_list( $meta, 'is-meta', 'متای خدمت' );
			}
			$cards[] = self::card( $children, 'is-service', 'خدمت: ' . (string) ( $item['title'] ?? '' ) );
		}
		$tree[] = self::section(
			array(
				self::head( (string) ( $services['badge'] ?? '' ), (string) ( $services['title'] ?? '' ), (string) ( $services['title_highlight'] ?? '' ), (string) ( $services['desc'] ?? '' ) ),
				self::grid( $cards, array( 'name' => 'شبکه خدمات' ) ),
			),
			array( 'css' => 'is-services', 'name' => 'خدمات', 'anchor' => 'services' )
		);

		/* ── 5. Products (real shop entries) ──────────────────────────── */
		$products = $c['products'] ?? array();
		if ( ! empty( $data['products'] ) ) {
			$cards = self::product_cards( (array) $data['products'], (string) ( $products['cta_text'] ?? 'مشاهده' ) );
			$tree[] = self::section(
				array(
					self::head( (string) ( $products['badge'] ?? '' ), (string) ( $products['title'] ?? '' ), (string) ( $products['title_highlight'] ?? '' ), (string) ( $products['desc'] ?? '' ), array( 'link_text' => (string) ( $products['link_text'] ?? '' ), 'link_url' => (string) ( $products['link_url'] ?? '' ) ) ),
					self::grid( $cards, array( 'css' => 'is-4', 'name' => 'شبکه محصولات' ) ),
				),
				array( 'css' => 'is-products is-tint', 'name' => 'محصولات آماده' )
			);
		}

		/* ── 6. Portfolio ─────────────────────────────────────────────── */
		$portfolio = $c['portfolio'] ?? array();
		if ( ! empty( $data['projects'] ) ) {
			$cards = self::project_cards( (array) $data['projects'], (string) ( $portfolio['cta_text'] ?? 'اطلاعات پروژه' ) );
			$tree[] = self::section(
				array(
					self::head( (string) ( $portfolio['badge'] ?? '' ), (string) ( $portfolio['title'] ?? '' ), (string) ( $portfolio['title_highlight'] ?? '' ), (string) ( $portfolio['desc'] ?? '' ), array( 'link_text' => (string) ( $portfolio['link_text'] ?? '' ), 'link_url' => (string) ( $portfolio['link_url'] ?? '' ) ) ),
					self::grid( $cards, array( 'name' => 'شبکه پروژه‌ها' ) ),
				),
				array( 'css' => 'is-projects', 'name' => 'نمونه‌کارها', 'anchor' => 'portfolio' )
			);
		}

		/* ── 7. Why us ────────────────────────────────────────────────── */
		$why   = $c['why'] ?? array();
		$cards = array();
		foreach ( (array) ( $why['items'] ?? array() ) as $item ) {
			$cards[] = self::card(
				array( self::icon_box( 'fa-' . ( $item['icon'] ?? 'circle-check' ), (string) ( $item['title'] ?? '' ), (string) ( $item['text'] ?? '' ) ) ),
				'is-why',
				'دلیل: ' . (string) ( $item['title'] ?? '' )
			);
		}
		$tree[] = self::section(
			array(
				self::head( (string) ( $why['badge'] ?? '' ), (string) ( $why['title'] ?? '' ), (string) ( $why['title_highlight'] ?? '' ), (string) ( $why['desc'] ?? '' ) ),
				self::grid( $cards, array( 'css' => 'is-4', 'name' => 'شبکه دلایل' ) ),
			),
			array( 'css' => 'is-why is-tint', 'name' => 'چرا زرین کد' )
		);

		/* ── 8. Steps ─────────────────────────────────────────────────── */
		$steps = $c['steps'] ?? array();
		$cards = array();
		foreach ( (array) ( $steps['items'] ?? array() ) as $i => $step ) {
			$cards[] = self::card(
				array(
					self::heading( (string) ( $step['no'] ?? sprintf( '%02d', $i + 1 ) ), 'span', 'zc-eb-step-no', 'شماره مرحله' ),
					self::heading( (string) ( $step['title'] ?? '' ), 'h3', 'zc-eb-card-title', 'عنوان مرحله' ),
					self::text( (string) ( $step['text'] ?? '' ), 'zc-eb-card-text', 'توضیح مرحله' ),
				),
				'is-step',
				'مرحله: ' . (string) ( $step['title'] ?? '' )
			);
		}
		$tree[] = self::section(
			array(
				self::head( (string) ( $steps['badge'] ?? '' ), (string) ( $steps['title'] ?? '' ), (string) ( $steps['title_highlight'] ?? '' ), (string) ( $steps['desc'] ?? '' ) ),
				self::grid( $cards, array( 'css' => 'is-steps', 'name' => 'شبکه مراحل' ) ),
			),
			array( 'css' => 'is-steps', 'name' => 'مراحل همکاری' )
		);

		/* ── 9. Pricing ───────────────────────────────────────────────── */
		$tree[] = self::pricing( (array) ( $c['pricing'] ?? array() ) );

		/* ── 10. Testimonials ─────────────────────────────────────────── */
		$tst   = $c['testimonials'] ?? array();
		$cards = array();
		foreach ( (array) ( $tst['items'] ?? array() ) as $item ) {
			$cards[] = self::card(
				array(
					self::text( (string) ( $item['text'] ?? '' ), 'zc-eb-quote', 'متن نظر' ),
					self::heading( (string) ( $item['name'] ?? '' ), 'h3', 'zc-eb-who', 'نام مشتری' ),
					self::text( (string) ( $item['role'] ?? '' ), 'zc-eb-role', 'عنوان مشتری' ),
				),
				'is-quote',
				'نظر: ' . (string) ( $item['name'] ?? '' )
			);
		}
		if ( $cards ) {
			$tree[] = self::section(
				array(
					self::head( (string) ( $tst['badge'] ?? '' ), (string) ( $tst['title'] ?? '' ), (string) ( $tst['title_highlight'] ?? '' ), (string) ( $tst['desc'] ?? '' ) ),
					self::grid( $cards, array( 'name' => 'شبکه نظرات' ) ),
				),
				array( 'css' => 'is-quotes', 'name' => 'نظرات مشتریان' )
			);
		}

		/* ── 11. Magazine (real posts) ────────────────────────────────── */
		$blog = $c['blog'] ?? array();
		if ( ! empty( $data['posts'] ) ) {
			$cards = self::post_cards( (array) $data['posts'], (string) ( $blog['read_more'] ?? 'ادامهٔ مطلب' ) );
			$tree[] = self::section(
				array(
					self::head( (string) ( $blog['badge'] ?? '' ), (string) ( $blog['title'] ?? '' ), (string) ( $blog['title_highlight'] ?? '' ), (string) ( $blog['desc'] ?? '' ), array( 'link_text' => (string) ( $blog['link_text'] ?? '' ), 'link_url' => (string) ( $blog['link_url'] ?? '' ) ) ),
					self::grid( $cards, array( 'name' => 'شبکه مقالات' ) ),
				),
				array( 'css' => 'is-magazine', 'name' => 'مجله زرین کد', 'anchor' => 'blog' )
			);
		}

		/* ── 12. Closing CTA ──────────────────────────────────────────── */
		$promo = $c['promo'] ?? array();
		$tree[] = self::section(
			array(
				self::container(
					array(
						self::container(
							array(
								self::badge( (string) ( $promo['badge'] ?? '' ), 'is-light', 'برچسب پایانی', '#FDD05B' ),
								self::heading( (string) ( $promo['title'] ?? '' ), 'h2', 'zc-eb-cta-title', 'عنوان پایانی', '#FFFFFF' ),
								self::text( (string) ( $promo['text'] ?? '' ), 'zc-eb-cta-text', 'متن پایانی', '#E6E7E9' ),
							),
							array( 'direction' => 'column', 'gap' => array( 'column' => 12, 'row' => 12 ), 'width' => 100, 'name' => 'متن پایانی' )
						),
						self::container(
							array(
								self::button( (string) ( $promo['primary_text'] ?? '' ), (string) ( $promo['primary_url'] ?? '/contact-us/' ), 'is-gold', 'دکمه پایانی', 'fa-paper-plane' ),
								self::button( (string) ( $promo['secondary_text'] ?? '' ), (string) ( $promo['secondary_url'] ?? '/pricing/' ), 'is-outline', 'دکمه دوم پایانی', 'fa-arrow-left' ),
							),
							array( 'direction' => 'row', 'gap' => array( 'column' => 12, 'row' => 12 ), 'width' => 100, 'css' => 'zc-eb-actions', 'name' => 'دکمه‌های پایانی' )
						),
					),
					array( 'direction' => 'column', 'gap' => array( 'column' => 22, 'row' => 18 ), 'css' => 'zc-eb-cta', 'name' => 'کارت پایانی' )
				),
			),
			array( 'css' => 'is-cta', 'name' => 'فراخوان پایانی' )
		);

		return $tree;
	}

	/**
	 * The pricing block: one section, one group per service track.
	 *
	 * The content model groups the plans under tabs (`tabs` + `plans[].track`).
	 * A tab control is not a native Elementor widget, so each track becomes a
	 * plain heading followed by a grid of plan cards — the same information,
	 * built only from widgets the client can edit, copy or delete. The same
	 * method feeds section 9 of the homepage and the pricing page, so the two
	 * can never disagree.
	 *
	 * @param array $pricing The `pricing` object of home-content.json.
	 * @param array $args    ['css' => extra classes, 'name' => section label].
	 */
	public static function pricing( array $pricing, array $args = array() ): array {
		$plans = (array) ( $pricing['plans'] ?? array() );
		$tabs  = (array) ( $pricing['tabs'] ?? array() );

		$by_track = array();
		foreach ( $plans as $plan ) {
			$by_track[ (string) ( $plan['track'] ?? '' ) ][] = (array) $plan;
		}
		/* Keep the tab order from the content model; unknown tracks go last. */
		$order = array();
		foreach ( $tabs as $tab ) {
			if ( isset( $by_track[ (string) $tab ] ) ) {
				$order[] = (string) $tab;
			}
		}
		foreach ( array_keys( $by_track ) as $track ) {
			if ( ! in_array( $track, $order, true ) && '' !== $track ) {
				$order[] = $track;
			}
		}

		$blocks = array();
		foreach ( $order as $track ) {
			$cards = array();
			foreach ( $by_track[ $track ] as $plan ) {
				$features = array();
				foreach ( (array) ( $plan['features'] ?? array() ) as $feature ) {
					$features[] = array(
						'text' => is_array( $feature ) ? (string) ( $feature['text'] ?? '' ) : (string) $feature,
						'icon' => 'fa-circle-check',
					);
				}
				$children = array();
				if ( ! empty( $plan['flag'] ) ) {
					$children[] = self::badge( (string) $plan['flag'], '', 'برچسب پیشنهاد', '#FDD05B' );
				}
				$children[] = self::heading( (string) ( $plan['title'] ?? '' ), 'h3', 'zc-eb-card-title', 'نام پلن' );
				if ( ! empty( $plan['delivery'] ) ) {
					$children[] = self::text( (string) $plan['delivery'], 'zc-eb-card-text', 'زمان تحویل' );
				}
				$children[] = self::heading(
					( '' !== (string) ( $pricing['price_label'] ?? '' ) ? '<span class="zc-eb-price-note">' . esc_html( (string) $pricing['price_label'] ) . '</span> ' : '' )
					. esc_html( (string) ( $plan['price'] ?? '' ) )
					. ' <span class="zc-eb-price-unit">' . esc_html( (string) ( $pricing['price_unit'] ?? '' ) ) . '</span>',
					'p',
					'zc-eb-price',
					'قیمت پلن',
					'#8A6B10'
				);
				if ( $features ) {
					$children[] = self::icon_list( $features, 'is-checks', 'امکانات پلن' );
				}
				$children[] = self::button( (string) ( $plan['cta'] ?? 'مشاوره و ثبت سفارش' ), (string) ( ( $plan['link'] ?? '' ) ?: '/contact-us/' ), 'is-ink is-sm', 'دکمه پلن', 'fa-arrow-left' );

				$cards[] = self::card( $children, 'is-plan' . ( '' !== (string) ( $plan['flag'] ?? '' ) ? ' is-popular' : '' ), 'پلن: ' . (string) ( $plan['title'] ?? '' ), true );
			}
			if ( ! $cards ) {
				continue;
			}
			$blocks[] = self::heading( $track, 'h3', 'zc-eb-track', 'گروه تعرفه' );
			$blocks[] = self::grid( $cards, array( 'css' => 'is-plans', 'name' => 'تعرفه‌های ' . $track ) );
		}

		return self::section(
			array_merge(
				array(
					self::head(
						(string) ( $pricing['badge'] ?? '' ),
						(string) ( $pricing['title'] ?? '' ),
						(string) ( $pricing['title_highlight'] ?? '' ),
						(string) ( $pricing['desc'] ?? '' )
					),
				),
				$blocks
			),
			array(
				'css'    => trim( 'is-pricing ' . ( $args['css'] ?? '' ) ),
				'name'   => (string) ( $args['name'] ?? 'تعرفهٔ خدمات' ),
				'anchor' => 'pricing',
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Inner pages
	 * ------------------------------------------------------------------ */

	/**
	 * One inner page: hero + N content blocks + closing CTA.
	 *
	 * @param array $page [
	 *   'badge','title','lead','primary' => [text,url], 'secondary' => [text,url],
	 *   'sections' => [ ['title','lead','cards'=>[['icon','title','text']]] ],
	 *   'list'     => ['title','lead','items'=>[['text','url'|'meta']]],
	 *   'clauses'  => [['title','html']],   (legal documents)
	 *   'cta'      => ['title','text','primary'=>[text,url]]
	 * ]
	 */
	public static function inner( array $page ): array {
		$tree  = array();
		$badge = (string) ( $page['badge'] ?? '' );
		$hero  = array();
		if ( '' !== $badge ) {
			$hero[] = self::badge( $badge, '', 'برچسب صفحه', '#8A6B10' );
		}
		$hero[] = self::heading( (string) ( $page['title'] ?? '' ), 'h1', 'zc-eb-page-title', 'عنوان صفحه (H1)' );
		if ( ! empty( $page['lead'] ) ) {
			$hero[] = self::text( (string) $page['lead'], 'zc-eb-page-lead', 'متن معرفی صفحه' );
		}
		$actions = array();
		foreach ( array( 'primary' => 'is-gold', 'secondary' => 'is-ghost' ) as $key => $class ) {
			if ( empty( $page[ $key ]['text'] ) ) {
				continue;
			}
			$name     = 'primary' === $key ? 'دکمه اصلی صفحه' : 'دکمه دوم صفحه';
			$actions[] = self::button( (string) $page[ $key ]['text'], (string) ( $page[ $key ]['url'] ?? '/contact-us/' ), $class, $name, 'fa-arrow-left' );
		}
		if ( $actions ) {
			$hero[] = self::container( $actions, array( 'direction' => 'row', 'gap' => array( 'column' => 12, 'row' => 12 ), 'css' => 'zc-eb-actions', 'name' => 'دکمه‌های صفحه' ) );
		}
		$tree[] = self::section( array( self::container( $hero, array( 'direction' => 'column', 'gap' => array( 'column' => 16, 'row' => 14 ), 'css' => 'zc-eb-page-hero', 'name' => 'قهرمان صفحه' ) ) ), array( 'css' => 'is-page-hero', 'name' => 'قهرمان صفحه' ) );

		/*
		 * Pre-built blocks: the caller composes a grid of real products,
		 * projects or posts (`Page_Composer::section( [ grid( […] ) ] )`) and
		 * passes it in, so nothing has to be duplicated here.
		 */
		foreach ( (array) ( $page['nodes'] ?? array() ) as $node ) {
			$tree[] = $node;
		}

		foreach ( (array) ( $page['sections'] ?? array() ) as $section ) {
			$children = array();
			if ( ! empty( $section['title'] ) ) {
				$children[] = self::head( (string) ( $section['badge'] ?? '' ), (string) $section['title'], '', (string) ( $section['lead'] ?? '' ) );
			}
			if ( ! empty( $section['cards'] ) ) {
				$cards = array();
				foreach ( (array) $section['cards'] as $card ) {
					$cards[] = self::card(
						array( self::icon_box( 'fa-' . ( $card['icon'] ?? 'circle-check' ), (string) ( $card['title'] ?? '' ), (string) ( $card['text'] ?? '' ) ) ),
						'is-why',
						'کارت: ' . (string) ( $card['title'] ?? '' )
					);
				}
				$children[] = self::grid( $cards, array( 'css' => (string) ( $section['grid'] ?? 'is-4' ), 'name' => (string) ( $section['grid_name'] ?? 'شبکه کارت‌ها' ) ) );
			}
			if ( ! empty( $section['html'] ) ) {
				$children[] = self::text( (string) $section['html'], 'zc-eb-prose', 'متن بخش' );
			}
			if ( ! empty( $section['list'] ) ) {
				$children[] = self::icon_list( (array) $section['list'], 'is-links', 'فهرست پیوندها' );
			}
			$tree[] = self::section( $children, array( 'css' => 'is-' . sanitize_html_class( (string) ( $section['css'] ?? 'block' ) ), 'name' => (string) ( $section['title'] ?? 'بخش' ) ) );
		}

		/* Table of contents for documents. */
		if ( ! empty( $page['clauses'] ) ) {
			$toc = array();
			foreach ( (array) $page['clauses'] as $i => $clause ) {
				$toc[] = array( 'text' => (string) ( $clause['title'] ?? '' ), 'icon' => 'fa-angle-left', 'url' => '#clause-' . ( $i + 1 ) );
			}
			$tree[] = self::section(
				array(
					self::head( '', 'فهرست این سند', '', 'برای رفتن به هر بند روی عنوان آن بزنید.' ),
					self::icon_list( $toc, 'is-toc', 'فهرست بندها' ),
				),
				array( 'css' => 'is-toc', 'name' => 'فهرست سند' )
			);
			foreach ( (array) $page['clauses'] as $i => $clause ) {
				$tree[] = self::section(
					array(
						self::container(
							array(
								self::heading( (string) ( $clause['title'] ?? '' ), 'h2', 'zc-eb-clause-title', 'عنوان بند' ),
								self::text( (string) ( $clause['html'] ?? '' ), 'zc-eb-prose', 'متن بند' ),
							),
							array( 'direction' => 'column', 'gap' => array( 'column' => 14, 'row' => 12 ), 'css' => 'zc-eb-clause', 'name' => 'بند ' . ( $i + 1 ) )
						),
					),
					array( 'css' => 'is-clause', 'name' => 'بند: ' . (string) ( $clause['title'] ?? '' ), 'anchor' => 'clause-' . ( $i + 1 ) )
				);
			}
		}

		if ( ! empty( $page['cta']['title'] ) ) {
			$tree[] = self::section(
				array(
					self::container(
						array(
							self::heading( (string) $page['cta']['title'], 'h2', 'zc-eb-cta-title', 'عنوان پایانی', '#FFFFFF' ),
							self::text( (string) ( $page['cta']['text'] ?? '' ), 'zc-eb-cta-text', 'متن پایانی', '#E6E7E9' ),
							self::container(
								array( self::button( (string) ( $page['cta']['primary']['text'] ?? 'شروع پروژه' ), (string) ( $page['cta']['primary']['url'] ?? '/contact-us/' ), 'is-gold', 'دکمه پایانی', 'fa-paper-plane' ) ),
								array( 'direction' => 'row', 'gap' => array( 'column' => 12, 'row' => 12 ), 'css' => 'zc-eb-actions', 'name' => 'دکمه پایانی' )
							),
						),
						array( 'direction' => 'column', 'gap' => array( 'column' => 20, 'row' => 16 ), 'css' => 'zc-eb-cta', 'name' => 'کارت پایانی' )
					),
				),
				array( 'css' => 'is-cta', 'name' => 'فراخوان پایانی' )
			);
		}

		return $tree;
	}
}
