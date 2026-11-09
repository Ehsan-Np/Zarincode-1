<?php
/**
 * Elementor-native header & footer templates.
 *
 * The site owner asked for a header and footer that are editable in Elementor
 * down to the last element — not a coded shell with a few settings. This
 * builder creates both templates out of *native* Elementor widgets only
 * (heading, text-editor, icon-list, icon-box, button, divider, spacer,
 * shortcode), so every column, link, colour and spacing value can be moved,
 * deleted or restyled inside the editor.
 *
 * The coded header/footer stays in the theme as the fallback for installs that
 * never ran this builder, so nothing can render an empty page.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Elementor;

defined( 'ABSPATH' ) || exit;

final class HF_Builder {

	/** Option that remembers whether the Elementor header/footer were generated. */
	private const FLAG = 'zc_hf_elementor_generated';

	/**
	 * Create (or refresh) the header and footer templates and point the theme
	 * options at them.
	 *
	 * @param bool $force Rebuild even when a template already exists.
	 * @return array{header:int,footer:int}
	 */
	public static function ensure( bool $force = false ): array {
		$out = array( 'header' => 0, 'footer' => 0 );
		if ( ! post_type_exists( 'zc_hf_template' ) || ! did_action( 'elementor/loaded' ) ) {
			return $out;
		}

		$out['header'] = self::upsert( 'header', 'zarincode-elementor-header', 'هدر سایت — ساخته‌شده با ویجت‌های المنتور', self::header_tree(), $force );
		$out['footer'] = self::upsert( 'footer', 'zarincode-elementor-footer', 'فوتر سایت — ساخته‌شده با ویجت‌های المنتور', self::footer_tree(), $force );

		// Point the theme at the templates we just built.
		$options = get_option( 'zarincode_opt', array() );
		$options = is_array( $options ) ? $options : array();
		if ( $out['header'] ) {
			$options['header_source'] = 'elementor_hf';
		}
		if ( $out['footer'] ) {
			$options['footer_source'] = 'elementor_hf';
		}
		$options['demo_design'] = 'custom';
		update_option( 'zarincode_opt', $options );
		update_option( self::FLAG, gmdate( 'c' ), false );

		return $out;
	}

	/**
	 * Insert or update one template, keeping the same post ID so an owner's
	 * edits are never duplicated into a second template.
	 */
	private static function upsert( string $type, string $slug, string $title, array $tree, bool $force ): int {
		$existing = get_page_by_path( $slug, OBJECT, 'zc_hf_template' );
		$postarr  = array(
			'post_type'    => 'zc_hf_template',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_content' => '',
		);

		if ( $existing ) {
			$id = (int) $existing->ID;
			// Never overwrite a template the owner has edited: only fill an empty one.
			$has_data = (string) get_post_meta( $id, '_elementor_data', true );
			$edited   = (int) get_post_meta( $id, '_zc_hf_user_edited', true );
			if ( ! $force && $edited ) {
				return $id;
			}
			if ( ! $force && '' !== $has_data && ! $edited ) {
				// A previous generated tree — refresh it so new defaults land.
				$has_data = '';
			}
			if ( '' !== $has_data && ! $force ) {
				return $id;
			}
			$postarr['ID'] = $id;
			$id            = (int) wp_update_post( $postarr );
		} else {
			$id = (int) wp_insert_post( $postarr );
		}

		if ( ! $id ) {
			return 0;
		}

		update_post_meta( $id, '_zc_hf_type', $type );
		update_post_meta( $id, '_zc_hf_active', 'yes' );
		update_post_meta( $id, '_zc_hf_generated', '1' );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-' . $type );
		update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.25.0' );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );

		// A library copy so it also appears under Elementor → Templates.
		if ( post_type_exists( 'elementor_library' ) ) {
			$lib = get_page_by_path( $slug . '-library', OBJECT, 'elementor_library' );
			$libarr = array(
				'post_type'   => 'elementor_library',
				'post_title'  => $title,
				'post_name'   => $slug . '-library',
				'post_status' => 'publish',
			);
			if ( $lib ) {
				$libarr['ID'] = (int) $lib->ID;
				$lib_id       = (int) wp_update_post( $libarr );
			} else {
				$lib_id = (int) wp_insert_post( $libarr );
			}
			if ( $lib_id ) {
				update_post_meta( $lib_id, '_elementor_edit_mode', 'builder' );
				update_post_meta( $lib_id, '_elementor_template_type', $type );
				update_post_meta( $lib_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.25.0' );
				update_post_meta( $lib_id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );
			}
		}

		return $id;
	}

	/**
	 * Hooks: mark a template as owner-edited (so a re-import never overwrites
	 * it) and expose a safe admin-post handler for the rebuild button.
	 */
	public static function boot(): void {
		// Elementor saves documents through this action; a saved template is the
		// owner's work from that moment on.
		add_action( 'elementor/document/after_save', static function ( $document ) {
			$id = (int) $document->get_main_id();
			if ( $id && 'zc_hf_template' === get_post_type( $id ) ) {
				update_post_meta( $id, '_zc_hf_user_edited', time() );
			}
		} );

		add_action( 'admin_post_zarincode_build_hf', static function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'دسترسی مجاز نیست.', 'zarincode-core' ) );
			}
			check_admin_referer( 'zarincode_build_hf' );
			$force = isset( $_GET['force'] ) && '1' === $_GET['force'];
			$ids   = self::ensure( $force );
			$back  = wp_get_referer() ?: admin_url( 'admin.php?page=zarincode-theme-options' );
			wp_safe_redirect( add_query_arg( array( 'zc_hf_built' => (int) ( $ids['header'] && $ids['footer'] ) ), $back ) );
			exit;
		} );
	}

	/* ------------------------------------------------------------------ tree */

	private static function id(): string {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/** One native Elementor widget. */
	private static function widget( string $type, array $settings = array() ): array {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings ? $settings : (object) array(),
		);
	}

	/**
	 * One section with N columns.
	 *
	 * @param array $columns List of [ 'size' => int, 'widgets' => array[] ].
	 */
	private static function row( array $columns, array $settings = array() ): array {
		$defaults = array(
			'layout'    => 'boxed',
			'gap'       => 'default',
			'structure' => '10' === (string) count( $columns ) ? '10' : (string) count( $columns ),
		);
		$elements = array();
		foreach ( $columns as $column ) {
			$elements[] = array(
				'id'       => self::id(),
				'elType'   => 'column',
				'settings' => array(
					'_column_size' => isset( $column['size'] ) ? (int) $column['size'] : (int) floor( 100 / max( 1, count( $columns ) ) ),
					'_inline_size' => null,
				),
				'elements' => $column['widgets'],
			);
		}
		return array(
			'id'       => self::id(),
			'elType'   => 'section',
			'settings' => array_merge( $defaults, $settings ),
			'elements' => $elements,
		);
	}

	/**
	 * A nav-style icon list; icons are optional per row.
	 *
	 * Elementor stores a *widget's* extra CSS class in `_css_classes` (sections
	 * and columns use `css_classes` without the underscore) — using the wrong
	 * key silently drops the class and the nav renders as a vertical list.
	 */
	private static function links( array $rows, array $settings = array() ): array {
		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'_id'           => self::id(),
				'text'          => $row[0],
				'selected_icon' => array( 'value' => $row[2] ?? '', 'library' => 'fa-solid' ),
				'link'          => array( 'url' => $row[1], 'is_external' => '', 'nofollow' => '' ),
			);
		}
		return self::widget(
			'icon-list',
			array_merge(
				array(
					'icon_list'     => $items,
					'view'          => 'inline',
					'space_between' => array( 'unit' => 'px', 'size' => 6, 'sizes' => array() ),
					'icon_align'    => 'right',
				),
				$settings
			)
		);
	}

	/**
	 * Brand button colours, set on the widget itself so what the editor shows is
	 * what the visitor gets (the Kit's global accent is a dark ink tone, which is
	 * right for body buttons but wrong for a gold header CTA).
	 */
	private static function gold_button( bool $outline = false ): array {
		$radius = array( 'unit' => 'px', 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'isLinked' => true );
		if ( $outline ) {
			return array(
				'background_color'              => 'rgba(255,255,255,0.06)',
				'button_text_color'             => '#FDD05B',
				'border_border'                 => 'solid',
				'border_width'                  => array( 'unit' => 'px', 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true ),
				'border_color'                  => 'rgba(253,208,91,0.55)',
				'border_radius'                 => $radius,
				'button_background_hover_color' => 'rgba(253,208,91,0.16)',
				'button_hover_border_color'     => '#FDD05B',
				'button_hover_color'            => '#FDD05B',
			);
		}
		return array(
			'background_color'              => '#FDD05B',
			'button_text_color'             => '#2A2C31',
			'border_radius'                 => $radius,
			'button_background_hover_color' => '#FCC22D',
			'button_hover_color'            => '#2A2C31',
		);
	}

	/** Header: contact strip + brand + inline navigation + CTA. */
	public static function header_tree(): array {
		$nav = array(
			array( 'خانه', home_url( '/' ) ),
			array( 'خدمات', home_url( '/services/' ) ),
			array( 'نمونه‌کارها', home_url( '/projects-portfolio/' ) ),
			array( 'فروشگاه', home_url( '/shop/' ) ),
			array( 'مقالات', home_url( '/blog/' ) ),
			array( 'درباره ما', home_url( '/about-us/' ) ),
			array( 'تماس با ما', home_url( '/contact-us/' ) ),
		);

		$brand_col = array(
			self::widget( 'heading', array(
				'title'       => 'زریــن کـد',
				'header_size' => 'div',
				'align'       => 'right',
				'title_color' => '#2A2C31',
				'typography_typography' => 'custom',
				'typography_font_size'  => array( 'unit' => 'px', 'size' => 26, 'sizes' => array() ),
				'typography_font_weight'=> '900',
			) ),
			self::widget( 'text-editor', array(
				'editor' => '<p>Zarincode Studio — آژانس طراحی سایت، نرم‌افزار و سئو</p>',
			) ),
		);

		$cta_col = array(
			self::widget( 'button', array_merge(
				array(
					'text'  => 'درخواست مشاوره',
					'link'  => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
					'align' => 'left',
					'size'  => 'md',
				),
				self::gold_button()
			) ),
			self::widget( 'text-editor', array(
				'editor' => '<p class="zc-hf-phone">۰۹۰۲۴۵۶۱۰۰۱</p>',
			) ),
		);

		return array(
			// Utility strip — deletable in one click.
			self::row(
				array(
					array(
						'size'    => 100,
						'widgets' => array(
							self::widget( 'text-editor', array(
								'editor' => '<p>مشاوره رایگان پروژه: ۰۹۰۲۴۵۶۱۰۰۱ &nbsp;|&nbsp; پاسخ‌گویی شنبه تا پنجشنبه ۹ تا ۱۸</p>',
							) ),
						),
					),
				),
				array( 'css_classes' => 'zc-hf-strip', 'structure' => '10' )
			),
			// Main bar.
			self::row(
				array(
					array( 'size' => 26, 'widgets' => $brand_col ),
					array( 'size' => 54, 'widgets' => array( self::links( $nav, array( '_css_classes' => 'zc-hf-nav' ) ) ) ),
					array( 'size' => 20, 'widgets' => $cta_col ),
				),
				array( 'css_classes' => 'zc-hf-main', 'structure' => '26+54+20' )
			),
		);
	}

	/** Footer: CTA strip + 4 editable columns + legal line. */
	public static function footer_tree(): array {
		$cta = array(
			self::row(
				array(
					array(
						'size'    => 70,
						'widgets' => array(
							self::widget( 'heading', array(
								'title'       => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟',
								'header_size' => 'h2',
								'align'       => 'right',
							) ),
							self::widget( 'text-editor', array(
								'editor' => '<p>مشاورهٔ تلفنی رایگان با احسان نادری پناه — پاسخ در کمتر از ۱۵ دقیقه در ساعات کاری.</p>',
							) ),
						),
					),
					array(
						'size'    => 30,
						'widgets' => array(
							self::widget( 'button', array_merge(
								array(
									'text'  => '۰۹۰۲۴۵۶۱۰۰۱ — تماس مستقیم',
									'link'  => array( 'url' => 'tel:09024561001', 'is_external' => '', 'nofollow' => '' ),
									'align' => 'left',
									'size'  => 'md',
								),
								self::gold_button()
							) ),
							self::widget( 'button', array_merge(
								array(
									'text'  => 'ثبت سفارش آنلاین',
									'link'  => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
									'align' => 'left',
									'size'  => 'md',
								),
								self::gold_button( true )
							) ),
						),
					),
				),
				array( 'css_classes' => 'zc-hf-cta', 'structure' => '70+30' )
			),
		);

		$about = array(
			self::widget( 'heading', array( 'title' => 'زرین کد', 'header_size' => 'h3', 'align' => 'right' ) ),
			self::widget( 'text-editor', array(
				'editor' => '<p>زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهندهٔ خدمات تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو و مارکت‌پلیس محصولات دیجیتال است.</p>',
			) ),
			self::widget( 'icon-box', array(
				'title_text'       => 'پشتیبانی مستقیم',
				'description_text' => '۰۹۰۲۴۵۶۱۰۰۱ — info@zarincode.com',
				'selected_icon'    => array( 'value' => 'fas fa-headset', 'library' => 'fa-solid' ),
			) ),
		);

		$services = array(
			self::widget( 'heading', array( 'title' => 'خدمات ما', 'header_size' => 'h3', 'align' => 'right' ) ),
			self::links( array(
				array( 'طراحی سایت اختصاصی', home_url( '/services/website-design/' ) ),
				array( 'برنامه‌نویسی نرم‌افزار', home_url( '/services/software-development/' ) ),
				array( 'سئو سایت', home_url( '/services/website-seo/' ) ),
				array( 'سئو شبکه‌های اجتماعی', home_url( '/services/social-media-seo/' ) ),
				array( 'تولید محتوا', home_url( '/services/' ) ),
				array( 'همهٔ خدمات', home_url( '/services/' ) ),
			), array( '_css_classes' => 'zc-hf-links' ) ),
		);

		$shop = array(
			self::widget( 'heading', array( 'title' => 'فروشگاه و مجله', 'header_size' => 'h3', 'align' => 'right' ) ),
			self::links( array(
				array( 'همهٔ محصولات', home_url( '/shop/' ) ),
				array( 'قالب‌های وردپرس', home_url( '/shop/' ) ),
				array( 'افزونه‌ها', home_url( '/shop/' ) ),
				array( 'سورس‌کدهای آماده', home_url( '/shop/' ) ),
				array( 'مقالات و راهنماها', home_url( '/blog/' ) ),
			), array( '_css_classes' => 'zc-hf-links' ) ),
		);

		$legal = array(
			self::widget( 'heading', array( 'title' => 'قوانین و اعتماد', 'header_size' => 'h3', 'align' => 'right' ) ),
			self::links( array(
				array( 'قوانین و مقررات', home_url( '/terms-and-conditions/' ) ),
				array( 'حریم خصوصی', home_url( '/privacy-policy/' ) ),
				array( 'گارانتی و پشتیبانی', home_url( '/warranty-policy/' ) ),
				array( 'بازگشت وجه', home_url( '/refund-policy/' ) ),
				array( 'GDPR', home_url( '/gdpr-compliance/' ) ),
				array( 'DMCA', home_url( '/dmca-policy/' ) ),
			), array( '_css_classes' => 'zc-hf-links' ) ),
			self::widget( 'shortcode', array(
				'shortcode' => '[zarincode_social_links]',
			) ),
		);

		$columns = array(
			self::row(
				array(
					array( 'size' => 30, 'widgets' => $about ),
					array( 'size' => 24, 'widgets' => $services ),
					array( 'size' => 22, 'widgets' => $shop ),
					array( 'size' => 24, 'widgets' => $legal ),
				),
				array( 'css_classes' => 'zc-hf-cols', 'structure' => '30+24+22+24' )
			),
		);

		$bottom = array(
			self::row(
				array(
					array(
						'size'    => 100,
						'widgets' => array(
							self::widget( 'text-editor', array(
								'editor' => '<p>تمامی حقوق مادی و معنوی این سایت و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه است. © ۱۴۰۵</p>',
							) ),
						),
					),
				),
				array( 'css_classes' => 'zc-hf-bottom', 'structure' => '10' )
			),
		);

		return array_merge( $cta, $columns, $bottom );
	}

	/** Status rows for the admin guidance box. */
	public static function report(): array {
		$rows = array();
		foreach ( array( 'header' => 'هدر', 'footer' => 'فوتر' ) as $type => $label ) {
			$id = function_exists( 'zarincode_hf_template_id' ) ? zarincode_hf_template_id( $type ) : 0;
			$rows[] = array(
				'type'   => $type,
				'label'  => $label,
				'id'     => $id,
				'edit'   => $id ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '',
				'source' => (string) ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( $type . '_source', 'elementor_hf' ) : '' ),
			);
		}
		return $rows;
	}
}
