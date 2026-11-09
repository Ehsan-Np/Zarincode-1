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

		// No template with our slug? Adopt the one that already carries this
		// location (a demo import, an older release or an owner-created one)
		// instead of adding a second footer to the site.
		if ( ! $existing ) {
			$found = get_posts(
				array(
					'post_type'      => 'zc_hf_template',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_zc_hf_type',
					'meta_value'     => $type,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			if ( $found ) {
				$existing = get_post( (int) $found[0] );
			}
		}

		if ( $existing ) {
			$id   = (int) $existing->ID;
			$data = (string) get_post_meta( $id, '_elementor_data', true );

			if ( ! $force ) {
				$edited = (int) get_post_meta( $id, '_zc_hf_user_edited', true );
				// The owner's own document — and every header — is never touched,
				// only repaired. (The approved header design ships as a single
				// theme widget and stays exactly as it is.)
				if ( $edited || 'footer' !== $type || ! self::is_legacy_tree( $data ) ) {
					self::mark_editable( $id, $type );
					return $id;
				}
				// A tree from an older release: one monolithic widget
				// (`zarincode_footer` / `[zarincode_widget type="footer"]`) that
				// could not be edited in the panel. Upgrade it in place, keeping
				// a backup, so the owner finally gets the editable version.
				if ( '' !== $data && '[]' !== $data ) {
					update_post_meta( $id, self::BACKUP, wp_slash( $data ) );
				}
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
	/**
	 * A tree generated by an older release: it wraps the whole footer in one
	 * native theme widget (or its shortcode), so nothing inside it could be
	 * edited in the panel. Those trees are the ones this release upgrades.
	 */
	private static function is_legacy_tree( string $raw ): bool {
		if ( '' === trim( $raw ) ) {
			return false;
		}
		return (bool) preg_match( '/zarincode_(footer|header)\b|zarincode_widget[^\]]*type=\\"?(footer|header)/i', $raw );
	}

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
	private static function widget( string $type, array $settings = array(), string $name = '', string $css_class = '' ): array {
		if ( '' !== $name ) {
			$settings['_title'] = $name;
		}
		if ( '' !== $css_class ) {
			$settings['_css_classes'] = $css_class;
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
				if ( 0 === strpos( (string) $names[ $index ], 'zc-' ) ) {
					$col_settings['css_classes'] = $names[ $index ];
				} else {
					$col_settings['_title'] = $names[ $index ];
				}
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

	/**
	 * Header: the approved 4.9.0 header, unchanged.
	 *
	 * The design ships as the theme's own `zarincode_header` widget (top bar,
	 * pill navigation with mega menu, search, account, cart, dark-mode switch
	 * and the consult CTA) — one Elementor widget inside a real Elementor
	 * document, exactly as 4.9.0 shipped it. Nothing in this release re-draws
	 * it: the header is left alone, only the footer becomes fully editable.
	 */
	public static function header_tree(): array {
		return array(
			self::row(
				array(
					array(
						'size'    => 100,
						'widgets' => array(
							self::widget(
								'zarincode_header',
								array(
									'brand_title'    => 'زریــن کـد',
									'brand_subtitle' => 'Zarincode Studio',
									'dev_name'       => 'احسان نادری پناه',
									'phone'          => '09024561001',
									'cta_text'       => 'درخواست مشاوره',
								),
								'هدر اصلی سایت'
							),
						),
					),
				),
				array( 'structure' => '10', '_title' => 'هدر سایت' )
			),
		);
	}

	/**
	 * Footer: the approved 4.9.0 bento footer, rebuilt from native widgets.
	 *
	 * Structure and copy are the 4.9.0 design (`.zc-footer-bento`): CTA card,
	 * four columns (brand + services + legal + credentials) and the bottom bar.
	 * The look comes from `assets/css/hf-footer.css`, which carries the exact
	 * 4.9.0 values; here every piece of text, every link, every icon, every
	 * button and every social entry is an Elementor widget the owner can edit
	 * in the panel — nothing is hard-coded in PHP any more.
	 */
	public static function footer_tree(): array {
		/* ---------------------------------------------------------- CTA card */
		$cta = self::row(
			array(
				array(
					'size'    => 62,
					'widgets' => array(
						self::widget(
							'heading',
							array(
								'title'       => 'ZARINCODE DIGITAL AGENCY &amp; MARKETPLACE',
								'header_size' => 'span',
								'align'       => 'right',
							),
							'نشان بالای کارت',
							'zc-ft-badge'
						),
						self::widget(
							'heading',
							array(
								'title'       => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟',
								'header_size' => 'h2',
								'align'       => 'right',
							),
							'تیتر دعوت به اقدام'
						),
					),
				),
				array(
					'size'    => 38,
					'widgets' => array(
						self::widget(
							'button',
							array_merge(
								array(
									'text'        => 'تماس مستقیم: 09024561001',
									'link'        => array( 'url' => 'tel:09024561001', 'is_external' => '', 'nofollow' => '' ),
									'align'       => 'left',
									'size'        => 'md',
									'selected_icon' => array( 'value' => 'fas fa-phone-volume', 'library' => 'fa-solid' ),
									'icon_align'  => 'right',
								),
								self::pill_button( 'gold' )
							),
							'دکمهٔ تماس مستقیم',
							'zc-ft-gold'
						),
						self::widget(
							'button',
							array_merge(
								array(
									'text'        => 'ثبت سفارش آنلاین پروژه',
									'link'        => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
									'align'       => 'left',
									'size'        => 'md',
									'selected_icon' => array( 'value' => 'fas fa-paper-plane', 'library' => 'fa-solid' ),
									'icon_align'  => 'right',
								),
								self::pill_button( 'glass' )
							),
							'دکمهٔ ثبت سفارش آنلاین',
							'zc-ft-glass'
						),
					),
				),
			),
			array( 'css_classes' => 'zc-ft-cta', 'structure' => '62+38', '_title' => 'کارت دعوت به اقدام' ),
			array( 'zc-ft-ctatext', 'zc-ft-buttons' )
		);

		/* ------------------------------------------------------- brand column */
		$brand = array(
			self::widget( 'heading', array( 'title' => 'ZC', 'header_size' => 'span', 'align' => 'right' ), 'مونوگرام برند', 'zc-ft-mark' ),
			self::widget( 'heading', array( 'title' => 'زرین کد', 'header_size' => 'h3', 'align' => 'right' ), 'نام برند', 'zc-ft-brand' ),
			self::widget(
				'text-editor',
				array( 'editor' => '<p>Zarincode.com — Digital Engineering</p>' ),
				'شعار برند',
				'zc-ft-tagline'
			),
			self::widget(
				'text-editor',
				array( 'editor' => '<p>زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهنده خدمات فوق‌تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت و شبکه‌های اجتماعی، تولید محتوا و مارکت‌پلیس افزونه‌ها، قالب‌های وردپرس و سورس‌کدهای آماده تجاری است.</p>' ),
				'متن معرفی زرین کد',
				'zc-ft-about'
			),
			self::links(
				array(
					array( 'توسعه‌دهنده: احسان نادری پناه (Ehsan NaderiPanah)', '#' , 'fas fa-user-tie' ),
					array( 'خط ویژه مشاوره: 09024561001', 'tel:09024561001', 'fas fa-phone' ),
					array( 'وب‌سایت رسمی: Zarincode.com', 'https://zarincode.com', 'fas fa-globe' ),
				),
				array( '_css_classes' => 'zc-ft-contact' ),
				'اطلاعات تماس'
			),
			self::socials( 'شبکه‌های اجتماعی فوتر' ),
		);

		/* ---------------------------------------------------- services column */
		$services = array(
			self::widget( 'heading', array( 'title' => 'خدمات تخصصی زرین کد', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون خدمات', 'zc-ft-title' ),
			self::links(
				array(
					array( 'طراحی سایت حرفه‌ای و المنتوری', home_url( '/service/website-design/' ), 'fas fa-angle-left' ),
					array( 'برنامه‌نویسی نرم‌افزار و وب‌اپلیکیشن', home_url( '/service/software-development/' ), 'fas fa-angle-left' ),
					array( 'سئو سایت و بهینه‌سازی موتور جستجو', home_url( '/service/website-seo/' ), 'fas fa-angle-left' ),
					array( 'سئو شبکه‌های اجتماعی (اینستاگرام و لینکدین)', home_url( '/service/social-media-seo/' ), 'fas fa-angle-left' ),
					array( 'تولید محتوای تخصصی و استراتژی محتوا', home_url( '/service/content-creation/' ), 'fas fa-angle-left' ),
					array( 'فروشگاه قالب، افزونه و سورس‌کد آماده', home_url( '/service/ready-made-products/' ), 'fas fa-angle-left' ),
				),
				array( '_css_classes' => 'zc-ft-links' ),
				'پیوندهای خدمات'
			),
		);

		/* ------------------------------------------------------- legal column */
		$legal = array(
			self::widget( 'heading', array( 'title' => 'قوانین و اسناد حقوقی', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون قوانین', 'zc-ft-title' ),
			self::links(
				array(
					array( 'قوانین و شرایط و مقررات کشور ایران', home_url( '/terms-and-conditions/' ), 'fas fa-balance-scale' ),
					array( 'شرایط گارانتی و پشتیبانی فنی', home_url( '/warranty-policy/' ), 'fas fa-shield-alt' ),
					array( 'سیاست حفظ حریم خصوصی کاربران', home_url( '/privacy-policy/' ), 'fas fa-user-shield' ),
					array( 'شرایط و ضوابط بازگشت وجه', home_url( '/refund-policy/' ), 'fas fa-undo' ),
					array( 'قوانین حفاظت داده اتحادیه اروپا (GDPR)', home_url( '/gdpr-compliance/' ), 'fas fa-globe' ),
					array( 'DMCA Takedown Policy (English)', home_url( '/dmca-policy/' ), 'fas fa-copyright' ),
				),
				array( '_css_classes' => 'zc-ft-links' ),
				'پیوندهای قوانین'
			),
		);

		/* -------------------------------------------- credentials/quck links */
		$credentials = array(
			self::widget( 'heading', array( 'title' => 'اعتبارنامه‌ها و راه‌های ارتباطی', 'header_size' => 'h3', 'align' => 'right' ), 'عنوان ستون اعتبارنامه‌ها', 'zc-ft-title' ),
			self::chip( 'درباره زرین کد', home_url( '/about-us/' ), 'چیپ درباره' ),
			self::chip( 'نمونه‌کارها', home_url( '/projects-portfolio/' ), 'چیپ نمونه‌کارها' ),
			self::chip( 'تماس با ما', home_url( '/contact-us/' ), 'چیپ تماس' ),
		);

		$columns = self::row(
			array(
				array( 'size' => 40, 'widgets' => $brand ),
				array( 'size' => 25, 'widgets' => $services ),
				array( 'size' => 25, 'widgets' => $legal ),
				array( 'size' => 30, 'widgets' => $credentials ),
			),
			array( 'css_classes' => 'zc-ft-grid', 'structure' => '40+25+25+30', '_title' => 'ستون‌های فوتر' ),
			array( 'zc-ft-col-brand', 'zc-ft-col-services', 'zc-ft-col-legal', 'zc-ft-col-cred' )
		);

		/* --------------------------------------------------------- bottom bar */
		$bottom = self::row(
			array(
				array(
					'size'    => 55,
					'widgets' => array(
						self::widget(
							'text-editor',
							array( 'editor' => '<p>تمامی حقوق مادی و معنوی این قالب و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه (Ehsan NaderiPanah) می‌باشد.</p>' ),
							'متن کپی‌رایت'
						),
					),
				),
				array(
					'size'    => 45,
					'widgets' => array(
						self::widget(
							'text-editor',
							array( 'editor' => '<p>پشتیبانی و فروش: <strong dir="ltr">09024561001</strong> &nbsp;•&nbsp; طراحی بر پایه استانداردهای Zhaket &amp; ThemeForest</p>' ),
							'خط پشتیبانی و استانداردها'
						),
					),
				),
			),
			array( 'css_classes' => 'zc-ft-bottom', 'structure' => '55+45', '_title' => 'نوار پایانی فوتر' ),
			array( 'ستون کپی‌رایت', 'ستون پشتیبانی' )
		);

		return array( $cta, $columns, $bottom );
	}

	/** A pill-styled Elementor button (`gold` = brand, `glass` = translucent). */
	private static function pill_button( string $skin ): array {
		$radius = array( 'unit' => 'px', 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'isLinked' => true );
		$base   = array( 'border_radius' => $radius, 'align' => 'left' );
		if ( 'gold' === $skin ) {
			return $base + array(
				'background_color'              => '#FDD05B',
				'button_text_color'             => '#2A2C31',
				'button_background_hover_color' => '#FCC22D',
				'button_hover_color'            => '#2A2C31',
			);
		}
		return $base + array(
			'background_color'              => 'rgba(255,255,255,0.10)',
			'button_text_color'             => '#FFFFFF',
			'border_border'                 => 'solid',
			'border_width'                  => array( 'unit' => 'px', 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true ),
			'border_color'                  => 'rgba(255,255,255,0.20)',
			'button_background_hover_color' => 'rgba(255,255,255,0.16)',
			'button_hover_color'            => '#FFFFFF',
		);
	}

	/** A credential chip (the 4.9.0 `.zc-btn` light fill). */
	private static function chip( string $text, string $url, string $name = '' ): array {
		return self::widget(
			'button',
			array(
				'text'        => $text,
				'link'        => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
				'align'       => 'left',
				'size'        => 'sm',
				'button_text_color' => '#FFFFFF',
				'background_color'  => 'rgba(255,255,255,0.08)',
				'border_radius'     => array( 'unit' => 'px', 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'isLinked' => true ),
				'button_background_hover_color' => 'rgba(255,255,255,0.14)',
				'button_hover_color' => '#FFFFFF',
			),
			$name,
			'zc-ft-cred'
		);
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
