<?php
/**
 * Elementor-native header & footer templates.
 *
 * Every header and footer is a real Elementor document: one `zc_hf_template`
 * post whose content is a tree of native widgets (heading, text editor,
 * button, icon box, icon list, social profiles). Nothing in the header or
 * footer is hard-coded HTML any more, so the owner can open it with
 * «ویرایش با المنتور» and change every word, link, icon and colour — the
 * copyright line included.
 *
 * Three rules keep the promise honest:
 *
 *  1. The theme never re-paints the header/footer. The document is the single
 *     source of truth for structure, spacing and colour; the theme only wraps
 *     it in a landmark.
 *  2. Generation never overwrites an existing document. `ensure()` fills an
 *     empty template and stops; a deliberate rebuild is a separate, confirmed
 *     admin action that keeps a backup first.
 *  3. The document stays editable: `_elementor_edit_mode=builder`, a document
 *     type Elementor itself registers, and the CPT is registered in
 *     `elementor_cpt_support`, so the editor always opens.
 *
 * The coded header/footer in the theme remains only as the last-resort
 * fallback so an install that never ran this builder never renders nothing.
 *
 * @package Zarincode\Runtime260\Elementor
 */

namespace Zarincode\Runtime260\Elementor;

defined( 'ABSPATH' ) || exit;

final class HF_Builder {

	/** Option that remembers whether the Elementor header/footer were generated. */
	private const FLAG = 'zc_hf_elementor_generated';

	/** Backup meta written before a forced rebuild. */
	private const BACKUP = '_zc_hf_backup_data';

	/**
	 * Create the header and footer documents when they do not exist yet.
	 *
	 * Existing documents are never rewritten — an owner's edit (made in the
	 * Elementor editor, through the REST API or with WP-CLI) always survives a
	 * re-import, an update or a theme switch.
	 *
	 * @param bool $force Rebuild even when a template exists (admin action only).
	 * @return array{header:int,footer:int}
	 */
	public static function ensure( bool $force = false ): array {
		$out = array( 'header' => 0, 'footer' => 0 );
		if ( ! post_type_exists( 'zc_hf_template' ) || ! did_action( 'elementor/loaded' ) ) {
			return $out;
		}

		self::load_widgets();
		$out['header'] = self::upsert( 'header', 'zarincode-elementor-header', 'هدر سایت — قابل ویرایش با المنتور', self::header_tree(), $force );
		$out['footer'] = self::upsert( 'footer', 'zarincode-elementor-footer', 'فوتر سایت — قابل ویرایش با المنتور', self::footer_tree(), $force );

		$options = get_option( 'zarincode_opt', array() );
		$options = is_array( $options ) ? $options : array();
		if ( $out['header'] ) {
			$options['header_source'] = 'elementor_hf';
		}
		if ( $out['footer'] ) {
			$options['footer_source'] = 'elementor_hf';
		}
		if ( $out['header'] || $out['footer'] ) {
			$options['demo_design'] = 'custom';
			update_option( 'zarincode_opt', $options );
			update_option( self::FLAG, gmdate( 'c' ), false );
		}

		return $out;
	}

	/**
	 * Insert a template, or leave an existing one exactly as the owner left it.
	 *
	 * @param string $type  header|footer
	 * @param string $slug  Template slug (stable, used for lookup).
	 * @param string $title Post title.
	 * @param array  $tree  Elementor element tree used only for a new document.
	 * @param bool   $force Rebuild: back up and re-seed.
	 */
	private static function upsert( string $type, string $slug, string $title, array $tree, bool $force ): int {
		$existing = get_page_by_path( $slug, OBJECT, 'zc_hf_template' );

		if ( $existing ) {
			$id   = (int) $existing->ID;
			$data = (string) get_post_meta( $id, '_elementor_data', true );

			if ( ! $force ) {
				// Existing content = the owner's (or a previous seed's) document.
				// Only repair the flags Elementor needs to open the editor.
				self::mark_editable( $id, $type );
				return $id;
			}

			if ( '' !== $data && '[]' !== $data ) {
				update_post_meta( $id, self::BACKUP, wp_slash( $data ) );
			}
		} else {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'zc_hf_template',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_content' => '',
				)
			);
		}

		if ( ! $id ) {
			return 0;
		}

		self::mark_editable( $id, $type );

		update_post_meta( $id, '_zc_hf_generated', '1' );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );

		// Elementor caches the rendered CSS per post; a new tree needs a fresh one.
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $id;
	}

	/**
	 * Flags that make a `zc_hf_template` post a first-class Elementor document.
	 *
	 * `_elementor_template_type` is deliberately `wp-post`: that is the document
	 * type Elementor registers for every post type listed in
	 * `elementor_cpt_support`. A made-up type (`wp-footer`) is only known to
	 * Elementor Pro, and on a Pro-less site the editor fell back to a document
	 * it could not save — which is why the footer used to look "not editable".
	 */
	private static function mark_editable( int $id, string $type ): void {
		update_post_meta( $id, '_zc_hf_type', $type );
		if ( '' === (string) get_post_meta( $id, '_zc_hf_active', true ) ) {
			update_post_meta( $id, '_zc_hf_active', 'yes' );
		}
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-post' );
		update_post_meta(
			$id,
			'_elementor_version',
			defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.25.0'
		);
	}

	/**
	 * Hooks: edit shortcuts, the confirmed rebuild action and the «این برگه در
	 * المنتور ویرایش نشده» guard.
	 */
	public static function boot(): void {
		// Saving in the Elementor editor is the owner's work from now on.
		add_action(
			'elementor/document/after_save',
			static function ( $document ) {
				$id = (int) $document->get_main_id();
				if ( $id && 'zc_hf_template' === get_post_type( $id ) ) {
					update_post_meta( $id, '_zc_hf_user_edited', time() );
					delete_post_meta( $id, '_zc_hf_generated' );
				}
			}
		);

		// Front-end: a one-click way into the editor for administrators.
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );

		// Template list: an obvious edit link plus the type of each template.
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'manage_zc_hf_template_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_zc_hf_template_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );

		add_action( 'admin_post_zarincode_build_hf', array( __CLASS__, 'handle_rebuild' ) );

		if ( function_exists( 'zarincode_get_option' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'manage_options' ) || ! post_type_exists( 'zc_hf_template' ) ) {
						return;
					}
					$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
					if ( ! $screen || 'dashboard' !== $screen->id ) {
						return;
					}
					$id = function_exists( 'zarincode_hf_template_id' ) ? zarincode_hf_template_id( 'footer' ) : 0;
					if ( ! $id ) {
						return;
					}
					echo '<div class="notice notice-info is-dismissible"><p>';
					printf(
						/* translators: %s: edit link */
						esc_html__( 'فوتر سایت یک برگهٔ المنتوری است: همهٔ متن‌ها (از جمله خط کپی‌رایت) را از %s تغییر دهید.', 'zarincode-core' ),
						'<a href="' . esc_url( admin_url( 'post.php?post=' . $id . '&action=elementor' ) ) . '"><strong>' . esc_html__( 'ویرایش فوتر با المنتور', 'zarincode-core' ) . '</strong></a>'
					);
					echo '</p></div>';
				}
			);
		}
	}

	/** «ویرایش هدر/فوتر با المنتور» in the front-end admin bar. */
	public static function admin_bar( $bar ): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'edit_posts' ) || ! function_exists( 'zarincode_hf_template_id' ) ) {
			return;
		}
		foreach ( array( 'header' => 'هدر', 'footer' => 'فوتر' ) as $type => $label ) {
			$id = zarincode_hf_template_id( $type );
			if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			$bar->add_node(
				array(
					'id'    => 'zc-hf-edit-' . $type,
					'title' => '✏️ ' . sprintf( /* translators: %s: header|footer */ esc_html__( 'ویرایش %s با المنتور', 'zarincode-core' ), $label ),
					'href'  => admin_url( 'post.php?post=' . $id . '&action=elementor' ),
					'meta'  => array( 'title' => esc_attr__( 'همهٔ متن‌ها و المان‌ها در المنتور قابل مدیریت است', 'zarincode-core' ) ),
				)
			);
		}
	}

	/** «ویرایش با المنتور» as the first row action of every template. */
	public static function row_actions( array $actions, $post ): array {
		if ( ! $post || 'zc_hf_template' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$type       = (string) get_post_meta( $post->ID, '_zc_hf_type', true );
		$actions    = array_merge(
			array(
				'zc_elementor' => '<a href="' . esc_url( admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ) ) . '"><strong>' . esc_html__( 'ویرایش با المنتور', 'zarincode-core' ) . '</strong></a>',
			),
			$actions
		);
		if ( $type ) {
			$actions['zc_type'] = esc_html( 'header' === $type ? __( 'هدر سایت', 'zarincode-core' ) : __( 'فوتر سایت', 'zarincode-core' ) );
		}
		return $actions;
	}

	public static function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['zc_hf_type'] = __( 'جایگاه', 'zarincode-core' );
				$out['zc_hf_live'] = __( 'وضعیت نمایش', 'zarincode-core' );
			}
		}
		return $out;
	}

	public static function column_value( string $column, int $post_id ): void {
		if ( 'zc_hf_type' === $column ) {
			$type = (string) get_post_meta( $post_id, '_zc_hf_type', true );
			echo esc_html( 'header' === $type ? __( 'هدر', 'zarincode-core' ) : ( 'footer' === $type ? __( 'فوتر', 'zarincode-core' ) : '—' ) );
			return;
		}
		if ( 'zc_hf_live' === $column ) {
			$type = (string) get_post_meta( $post_id, '_zc_hf_type', true );
			$live = function_exists( 'zarincode_hf_template_id' ) && $type ? zarincode_hf_template_id( $type ) === $post_id : false;
			echo $live
				? '<span style="color:#1a7f37;font-weight:700">' . esc_html__( 'در حال نمایش', 'zarincode-core' ) . '</span>'
				: '<span style="color:#8a6b10">' . esc_html__( 'غیرفعال', 'zarincode-core' ) . '</span>';
		}
	}

	/** Confirmed rebuild: back up, re-seed, report back. */
	public static function handle_rebuild(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'zarincode-core' ) );
		}
		check_admin_referer( 'zarincode_build_hf' );
		$force = isset( $_GET['force'] ) && '1' === $_GET['force'];
		$ids   = self::ensure( $force );
		$back  = wp_get_referer() ?: admin_url( 'admin.php?page=zarincode-theme-options' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'zc_hf_built'  => (int) ( $ids['header'] && $ids['footer'] ),
					'zc_hf_rebuilt'=> $force ? 1 : 0,
				),
				$back
			)
		);
		exit;
	}

	/* ------------------------------------------------------------------ tree */

	private static function id(): string {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/**
	 * One native Elementor widget.
	 *
	 * `_title` is what the Navigator and the panel header show, so every block
	 * in the footer is named in Persian and can be found without guessing.
	 */
	private static function widget( string $type, array $settings = array(), string $name = '' ): array {
		if ( '' !== $name ) {
			$settings['_title'] = $name;
		}
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
	private static function row( array $columns, array $settings = array(), array $names = array() ): array {
		$defaults = array(
			'layout'    => 'boxed',
			'gap'       => 'default',
			'structure' => '10' === (string) count( $columns ) ? '10' : (string) count( $columns ),
		);
		$elements = array();
		foreach ( $columns as $index => $column ) {
			$col_settings = array(
				'_column_size' => isset( $column['size'] ) ? (int) $column['size'] : (int) floor( 100 / max( 1, count( $columns ) ) ),
				'_inline_size' => null,
			);
			if ( isset( $names[ $index ] ) ) {
				$col_settings['_title'] = $names[ $index ];
			}
			$elements[] = array(
				'id'       => self::id(),
				'elType'   => 'column',
				'settings' => $col_settings,
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
	private static function links( array $rows, array $settings = array(), string $name = '' ): array {
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
			),
			$name
		);
	}

	/**
	 * Brand button colours, set on the widget itself so what the editor shows is
	 * what the visitor gets (the Kit's global accent is a dark ink tone, which is
	 * right for body buttons but wrong for a gold CTA).
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

	/**
	 * The social block: our own widget when it is available (each network is a
	 * repeater row the owner edits in Elementor), otherwise the theme shortcode
	 * so an older install never renders an empty column.
	 */
	private static function socials( string $name = 'شبکه‌های اجتماعی' ): array {
		self::load_widgets();
		if ( class_exists( '\Zarincode\Runtime260\Widgets\Widget_Social_Profiles' ) ) {
			return self::widget( 'zarincode_social_profiles', array(), $name );
		}
		return self::widget( 'shortcode', array( 'shortcode' => '[zarincode_social_links]' ), $name );
	}

	/**
	 * Make sure Zarincode's own Elementor widgets are registered before a tree
	 * is built. Without this, a tree generated from WP-CLI or a cron context
	 * (where `elementor/widgets/register` has not run yet) fell back to the
	 * shortcode widget and the owner lost the per-network controls in the panel.
	 */
	private static function load_widgets(): void {
		if ( class_exists( '\Zarincode\Runtime260\Widgets\Widget_Social_Profiles' ) ) {
			return;
		}
		$manager_file = dirname( __DIR__ ) . '/Widgets/Elementor_Loader.php';
		if ( is_readable( $manager_file ) ) {
			require_once $manager_file;
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			// Triggers `elementor/widgets/register` exactly once.
			\Elementor\Plugin::$instance->widgets_manager->get_widget_types();
		}
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
			self::widget(
				'heading',
				array(
					'title'                 => 'زریــن کـد',
					'header_size'           => 'div',
					'align'                 => 'right',
					'title_color'           => '#2A2C31',
					'typography_typography' => 'custom',
					'typography_font_size'  => array( 'unit' => 'px', 'size' => 26, 'sizes' => array() ),
					'typography_font_weight'=> '900',
				),
				'نام برند (لوگوی متنی)'
			),
			self::widget( 'text-editor', array( 'editor' => '<p>Zarincode Studio — آژانس طراحی سایت، نرم‌افزار و سئو</p>' ), 'زیرنویس برند' ),
		);

		$cta_col = array(
			self::widget(
				'button',
				array_merge(
					array(
						'text'  => 'درخواست مشاوره',
						'link'  => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
						'align' => 'left',
						'size'  => 'md',
					),
					self::gold_button()
				),
				'دکمهٔ اصلی هدر'
			),
			self::widget( 'text-editor', array( 'editor' => '<p class="zc-hf-phone">۰۹۰۲۴۵۶۱۰۰۱</p>' ), 'شمارهٔ تماس هدر' ),
		);

		return array(
			// Utility strip — deletable in one click.
			self::row(
				array(
					array(
						'size'    => 100,
						'widgets' => array(
							self::widget( 'text-editor', array( 'editor' => '<p>مشاوره رایگان پروژه: ۰۹۰۲۴۵۶۱۰۰۱ &nbsp;|&nbsp; پاسخ‌گویی شنبه تا پنجشنبه ۹ تا ۱۸</p>' ), 'نوار اطلاعیهٔ بالای هدر' ),
						),
					),
				),
				array( 'css_classes' => 'zc-hf-strip', 'structure' => '10', '_title' => 'نوار بالایی هدر' )
			),
			// Main bar.
			self::row(
				array(
					array( 'size' => 26, 'widgets' => $brand_col ),
					array( 'size' => 54, 'widgets' => array( self::links( $nav, array( '_css_classes' => 'zc-hf-nav' ), 'منوی اصلی' ) ) ),
					array( 'size' => 20, 'widgets' => $cta_col ),
				),
				array( 'css_classes' => 'zc-hf-main', 'structure' => '26+54+20', '_title' => 'نوار اصلی هدر' ),
				array( 'ستون برند', 'ستون منو', 'ستون اقدام' )
			),
		);
	}

	/**
	 * Footer: CTA strip + 4 editable columns + copyright.
	 *
	 * Everything the visitor reads on the footer is a widget here — the CTA
	 * heading, the supporting line, both buttons, the four column titles, every
	 * link (each one is a repeater row with its own icon and URL), the support
	 * block, every social network and the copyright line.
	 */
	public static function footer_tree(): array {
		$cta = self::row(
			array(
				array(
					'size'    => 70,
					'widgets' => array(
						self::widget(
							'heading',
							array(
								'title'       => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟',
								'header_size' => 'h2',
								'align'       => 'right',
							),
							'تیتر دعوت به اقدام'
						),
						self::widget( 'text-editor', array( 'editor' => '<p>مشاورهٔ تلفنی رایگان با احسان نادری پناه — پاسخ در کمتر از ۱۵ دقیقه در ساعات کاری.</p>' ), 'توضیح دعوت به اقدام' ),
					),
				),
				array(
					'size'    => 30,
					'widgets' => array(
						self::widget(
							'button',
							array_merge(
								array(
									'text'  => '۰۹۰۲۴۵۶۱۰۰۱ — تماس مستقیم',
									'link'  => array( 'url' => 'tel:09024561001', 'is_external' => '', 'nofollow' => '' ),
									'align' => 'left',
									'size'  => 'md',
								),
								self::gold_button()
							),
							'دکمهٔ تماس مستقیم'
						),
						self::widget(
							'button',
							array_merge(
								array(
									'text'  => 'ثبت سفارش آنلاین',
									'link'  => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
									'align' => 'left',
									'size'  => 'md',
								),
								self::gold_button( true )
							),
							'دکمهٔ ثبت سفارش'
						),
					),
				),
			),
			array( 'css_classes' => 'zc-hf-cta', 'structure' => '70+30', '_title' => 'نوار دعوت به اقدام' ),
			array( 'ستون پیام', 'ستون دکمه‌ها' )
		);

		$about = array(
			self::widget( 'heading', array( 'title' => 'زرین کد', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون درباره' ),
			self::widget(
				'text-editor',
				array( 'editor' => '<p>زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهندهٔ خدمات تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو و مارکت‌پلیس محصولات دیجیتال است.</p>' ),
				'متن معرفی زرین کد'
			),
			self::widget(
				'icon-box',
				array(
					'title_text'       => 'پشتیبانی مستقیم',
					'description_text' => '۰۹۰۲۴۵۶۱۰۰۱ — info@zarincode.com',
					'selected_icon'    => array( 'value' => 'fas fa-headset', 'library' => 'fa-solid' ),
				),
				'بلوک پشتیبانی مستقیم'
			),
		);

		$services = array(
			self::widget( 'heading', array( 'title' => 'خدمات ما', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون خدمات' ),
			self::links(
				array(
					array( 'طراحی سایت اختصاصی', home_url( '/services/website-design/' ) ),
					array( 'برنامه‌نویسی نرم‌افزار', home_url( '/services/software-development/' ) ),
					array( 'سئو سایت', home_url( '/services/website-seo/' ) ),
					array( 'سئو شبکه‌های اجتماعی', home_url( '/services/social-media-seo/' ) ),
					array( 'تولید محتوا', home_url( '/services/' ) ),
					array( 'همهٔ خدمات', home_url( '/services/' ) ),
				),
				array( '_css_classes' => 'zc-hf-links' ),
				'پیوندهای خدمات'
			),
		);

		$shop = array(
			self::widget( 'heading', array( 'title' => 'فروشگاه و مجله', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون فروشگاه' ),
			self::links(
				array(
					array( 'همهٔ محصولات', home_url( '/shop/' ) ),
					array( 'قالب‌های وردپرس', home_url( '/shop/' ) ),
					array( 'افزونه‌ها', home_url( '/shop/' ) ),
					array( 'سورس‌کدهای آماده', home_url( '/shop/' ) ),
					array( 'مقالات و راهنماها', home_url( '/blog/' ) ),
				),
				array( '_css_classes' => 'zc-hf-links' ),
				'پیوندهای فروشگاه'
			),
		);

		$legal = array(
			self::widget( 'heading', array( 'title' => 'قوانین و اعتماد', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون قوانین' ),
			self::links(
				array(
					array( 'قوانین و مقررات', home_url( '/terms-and-conditions/' ) ),
					array( 'حریم خصوصی', home_url( '/privacy-policy/' ) ),
					array( 'گارانتی و پشتیبانی', home_url( '/warranty-policy/' ) ),
					array( 'بازگشت وجه', home_url( '/refund-policy/' ) ),
					array( 'GDPR', home_url( '/gdpr-compliance/' ) ),
					array( 'DMCA', home_url( '/dmca-policy/' ) ),
				),
				array( '_css_classes' => 'zc-hf-links' ),
				'پیوندهای قوانین'
			),
			self::socials( 'شبکه‌های اجتماعی فوتر' ),
		);

		$columns = self::row(
			array(
				array( 'size' => 30, 'widgets' => $about ),
				array( 'size' => 24, 'widgets' => $services ),
				array( 'size' => 22, 'widgets' => $shop ),
				array( 'size' => 24, 'widgets' => $legal ),
			),
			array( 'css_classes' => 'zc-hf-cols', 'structure' => '30+24+22+24', '_title' => 'ستون‌های فوتر' ),
			array( 'ستون درباره و پشتیبانی', 'ستون خدمات', 'ستون فروشگاه', 'ستون قوانین و شبکه‌ها' )
		);

		$bottom = self::row(
			array(
				array(
					'size'    => 100,
					'widgets' => array(
						self::widget(
							'text-editor',
							array( 'editor' => '<p>تمامی حقوق مادی و معنوی این سایت و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه است. © ۱۴۰۵</p>' ),
							'متن کپی‌رایت'
						),
					),
				),
			),
			array( 'css_classes' => 'zc-hf-bottom', 'structure' => '10', '_title' => 'نوار کپی‌رایت' )
		);

		return array_merge( array( $cta, $columns ), array( $bottom ) );
	}

	/** Status rows for the admin guidance box. */
	public static function report(): array {
		$rows = array();
		foreach ( array( 'header' => 'هدر', 'footer' => 'فوتر' ) as $type => $label ) {
			$id = function_exists( 'zarincode_hf_template_id' ) ? zarincode_hf_template_id( $type ) : 0;
			$rows[] = array(
				'type'    => $type,
				'label'   => $label,
				'id'      => $id,
				'edit'    => $id ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '',
				'source'  => (string) ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( $type . '_source', 'elementor_hf' ) : '' ),
				'widgets' => $id ? self::count_widgets( $id ) : 0,
			);
		}
		return $rows;
	}

	/** How many widgets the document holds — a quick "is it really Elementor?" check. */
	public static function count_widgets( int $id ): int {
		$raw = (string) get_post_meta( $id, '_elementor_data', true );
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return 0;
		}
		$tree = json_decode( $raw, true );
		if ( ! is_array( $tree ) ) {
			return 0;
		}
		$count = 0;
		$walk  = static function ( array $nodes ) use ( &$walk, &$count ) {
			foreach ( $nodes as $node ) {
				if ( ! is_array( $node ) ) {
					continue;
				}
				if ( isset( $node['elType'] ) && 'widget' === $node['elType'] ) {
					$count++;
				}
				if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
					$walk( $node['elements'] );
				}
			}
		};
		$walk( $tree );
		return $count;
	}
}
