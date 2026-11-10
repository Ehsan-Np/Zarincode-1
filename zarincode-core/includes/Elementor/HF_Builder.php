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
	 * Version of the element tree this class generates.
	 *
	 * Stored on the document (`_zc_hf_builder`). When a site is updated from a
	 * release that generated an older tree, `ensure()` rebuilds that document
	 * **only** if the owner never edited it — so an upgrade always lands on the
	 * current design, while a single edit made in the panel protects it forever.
	 */
	private const TREE_VERSION = '4.16.0';

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
				// A document the owner saved in the panel is never touched, and a
				// document already on the current tree is left exactly as it is.
				// Everything else — a monolithic tree from an older release, or a
				// tree generated by an older builder version — is upgraded in
				// place (with a backup), header included: 4.16.0 turned the header
				// into native Elementor elements, so a header still holding the
				// single `zarincode_header` widget has to be rebuilt once.
				$stale = self::is_stale_tree( $id, $data );
				if ( $edited || ( ! self::is_legacy_tree( $data ) && ! $stale ) ) {
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
		update_post_meta( $id, '_zc_hf_builder', self::TREE_VERSION );
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

	/**
	 * A tree this class generated in an *older* release.
	 *
	 * Only documents we own (`_zc_hf_generated`, no `_zc_hf_user_edited`) qualify,
	 * so a footer the owner has touched in the panel is never rewritten. An empty
	 * version means the document predates the marker — the 4.11–4.13 trees.
	 */
	private static function is_stale_tree( int $id, string $data ): bool {
		if ( '' === trim( $data ) || '1' !== (string) get_post_meta( $id, '_zc_hf_generated', true ) ) {
			return false;
		}
		if ( (int) get_post_meta( $id, '_zc_hf_user_edited', true ) ) {
			return false;
		}
		return (string) get_post_meta( $id, '_zc_hf_builder', true ) !== self::TREE_VERSION;
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
			return self::widget(
				'zarincode_social_profiles',
				array(
					'align'        => 'flex-start',
					'text_color'   => '#E5E7EB',
					'border_color' => 'rgba(136,136,136,0.27)',
					'hover_color'  => '#FDD05B',
					'icon_size'    => array( 'unit' => 'px', 'size' => 18 ),
				),
				$name
			);
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
	 * Header: a real Elementor layout, one native element at a time.
	 *
	 * Structure — every level is an element the owner can select in the
	 * Navigator, move, duplicate, restyle or delete on its own:
	 *
	 *   Container  zc-header-bar      the sticky band (hf-header.css supplies
	 *                                 only the sticky positioning and the
	 *                                 glass blur, which the panel has no
	 *                                 control for)
	 *   └─ Container zc-header-pill   the pill: background, border, radius,
	 *                                 shadow, padding, gap — all Elementor
	 *                                 settings, all editable
	 *      ├─ Widget  برند           monogram + name + tagline + link
	 *      ├─ Widget  منوی اصلی      the seven links (repeater rows), with
	 *      │                        idle / hover / current / ancestor states
	 *      └─ Container zc-header-actions
	 *         ├─ Widget  حساب کاربری the account button
	 *         ├─ Button  درخواست مشاوره (native Elementor button)
	 *         └─ Widget  منوی موبایل  the hamburger (tablet + mobile only)
	 *
	 * The list still prints `.zc-nav-pill > li > a`, the brand still prints
	 * `.zc-brand`, and the CTA still sits in `.zc-header-actions`, because the
	 * mobile drawer builds itself from those three roles.
	 */
	public static function header_tree(): array {
		$gold = '#FDD05B';
		$ink  = '#2A2C31';

		self::load_widgets();

		$brand = self::widget(
			'zarincode_header_brand',
			array(
				'link'             => array( 'url' => home_url( '/' ), 'is_external' => '', 'nofollow' => '' ),
				'monogram'         => 'ZC',
				'name'             => 'زرین کد',
				'tagline'          => 'Zarincode',
				'show_name'        => 'yes',
				'show_tagline'     => 'yes',
				'frame_background' => 'rgba(255,255,255,0.92)',
				'frame_border'     => 'rgba(42,44,49,0.08)',
				'monogram_color'   => $gold,
				'monogram_bg'      => $ink,
				'name_color'       => '#1F2937',
				'tagline_color'    => '#4B5563',
			),
			'برند زرین کد'
		);

		$nav = self::widget(
			'zarincode_header_nav',
			array(
				'items'       => class_exists( '\Zarincode\Runtime260\Widgets\Widget_Header_Nav' )
					? \Zarincode\Runtime260\Widgets\Widget_Header_Nav::default_items()
					: array(),
				'aria_label'  => 'منوی اصلی',
			),
			'منوی اصلی'
		);

		$account = self::widget(
			'zarincode_header_account',
			array(
				'icon'             => array( 'value' => 'fa-regular fa-user', 'library' => 'fa-regular' ),
				'label_display'    => 'auto',
				'icon_color'       => '#2A2C31',
				'background_color' => '#FFFFFF',
			),
			'حساب کاربری'
		);

		$cta = self::widget(
			'button',
			array_merge(
				array(
					'text'   => 'درخواست مشاوره',
					'link'   => array( 'url' => 'tel:09024561001', 'is_external' => '', 'nofollow' => '' ),
					'size'   => 'sm',
					'align'  => 'center',
				),
				self::gold_button()
			),
			'دکمهٔ درخواست مشاوره',
			'zc-header-cta-btn'
		);
		$cta['settings']['typography_typography'] = 'custom';
		$cta['settings']['typography_font_size']  = array( 'unit' => 'px', 'size' => 13, 'sizes' => array() );
		$cta['settings']['typography_font_weight'] = '800';
		$cta['settings']['padding'] = array( 'unit' => 'px', 'top' => '10', 'right' => '18', 'bottom' => '10', 'left' => '18', 'isLinked' => false );

		$toggle = self::widget(
			'zarincode_header_drawer',
			array(
				'icon'             => array( 'value' => 'fa-solid fa-bars', 'library' => 'fa-solid' ),
				'label'            => 'باز کردن منو',
				'target'           => 'zc-mobile-drawer',
				'icon_color'       => $gold,
				'background_color' => $ink,
			),
			'دکمهٔ منوی موبایل'
		);

		$actions = self::container(
			array( $account, $cta, $toggle ),
			array(
				'title'  => 'دکمه‌های هدر',
				'css'    => 'zc-header-actions',
				'gap'    => array( 'column' => '8', 'row' => '8' ),
				'align'  => 'center',
				'nowrap' => true,
			),
			true
		);

		$pill = self::container(
			array( $brand, $nav, $actions ),
			array(
				'title'    => 'نوار شیشه‌ای هدر',
				'css'      => 'zc-header-pill',
				'gap'      => array( 'column' => '10', 'row' => '10' ),
				'justify'  => 'space-between',
				'align'    => 'center',
				'nowrap'   => true,
				'padding'  => array( 'top' => '7', 'right' => '10', 'bottom' => '7', 'left' => '10' ),
				// Nearly opaque: the blur behind it is decoration, not a
				// window — a see-through pill turns grey when the page goes
				// dark, and the owner's colour must survive both site modes.
				'background' => 'rgba(255,255,255,0.94)',
				'border'   => 'rgba(255,255,255,0.98)',
				'radius'   => 999,
				'shadow'   => array( 'horizontal' => 0, 'vertical' => 12, 'blur' => 36, 'spread' => 0, 'color' => 'rgba(42,44,49,0.08)' ),
			),
			true
		);

		$bar = self::container(
			array( $pill ),
			array(
				'title'   => 'هدر سایت',
				'css'     => 'zc-header-bar',
				'padding' => array( 'top' => '10', 'right' => '0', 'bottom' => '10', 'left' => '0' ),
			)
		);
		$bar['settings']['html_tag'] = 'header';

		return array( $bar );
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
	/**
	 * Footer: the approved 4.9.0 design, expressed **entirely in Elementor**.
	 *
	 * Structure — every level is a native element, nothing is one "chunk":
	 *
	 *   Container  zc-ft-plate        the dark bento plate (gradient, radius,
	 *                                 border, shadow, padding and row gap are
	 *                                 Elementor settings, so the box itself is
	 *                                 editable in the panel)
	 *   ├─ Container zc-ft-cta          the CTA card (fill, border, radius, gap)
	 *   │  ├─ Heading   zc-ft-badge        the gold pill above the H2
	 *   │  ├─ Heading   zc-ft-cta-title    the H2
	 *   │  └─ Container zc-ft-buttons      direct-call button + order button
	 *   ├─ Container zc-ft-grid         the four columns (1.6 / 1 / 1 / 1.2)
	 *   │  ├─ zc-ft-col-brand             monogram, name, tagline, about,
	 *   │  │                              contact icon-list, social profiles
	 *   │  ├─ zc-ft-col-services          dot + title, link icon-list
	 *   │  ├─ zc-ft-col-legal             dot + title, link icon-list
	 *   │  └─ zc-ft-col-cred              dot + title, chip container
	 *   └─ Container zc-ft-bottom       copyright + support/standards lines
	 *
	 * Every value the 4.9.0 stylesheet used is written as the matching
	 * Elementor control (typography, colours, padding, gaps, borders, radius,
	 * shadows, per-device widths), so the owner edits the footer in the panel
	 * and the front end follows. The theme adds only the page-continuity veil
	 * above the plate and the two decorative glows (see hf-footer.css).
	 */
	public static function footer_tree(): array {
		/* ------------------------------------------------------------ tokens */
		$gold    = '#FDD05B';
		$gold_hi = '#FCC22D';
		$ink     = '#2A2C31';
		$white   = '#FFFFFF';
		$body    = '#E5E7EB';
		$muted   = '#9CA3AF';
		$line    = 'rgba(255,255,255,0.10)';
		$pill    = array( 'unit' => 'px', 'top' => '999', 'right' => '999', 'bottom' => '999', 'left' => '999', 'isLinked' => true );

		/* CTA card ─────────────────────────────────────────────────────────── */
		$badge = self::widget(
			'heading',
			array_merge(
				array(
					'title'          => 'ZARINCODE DIGITAL AGENCY &amp; MARKETPLACE',
					'header_size'    => 'span',
					'align'          => 'start',
					'title_color'    => $gold,
					'_flex_align_self' => 'flex-start',
				),
				self::typo( '12.5', '700', array( 'letter_spacing' => '0.04', 'text_transform' => 'uppercase', 'line_height' => '1.6', 'size_m' => '10.5' ) ),
				array(
					'_background_background'     => 'gradient',
					'_background_color'          => '#2F3238',
					'_background_color_b'        => '#1F2126',
					'_background_gradient_type'  => 'linear',
					'_background_gradient_angle' => array( 'unit' => 'deg', 'size' => 140 ),
					'_border_border'             => 'solid',
					'_border_width'              => array( 'unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true ),
					'_border_color'              => 'rgba(253,208,91,0.24)',
					'_border_radius'             => $pill,
					'_padding'                   => array( 'unit' => 'px', 'top' => '6', 'right' => '16', 'bottom' => '6', 'left' => '16', 'isLinked' => false ),
				)
			),
			'نشان بالای کارت',
			'zc-ft-badge'
		);

		$cta_title = self::widget(
			'heading',
			array_merge(
				array(
					'title'       => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟',
					'header_size' => 'h2',
					'align'       => 'start',
					'title_color' => $white,
				),
				self::typo( '23.2', '900', array( 'line_height' => '1.7', 'size_t' => '21', 'size_m' => '17.5' ) )
			),
			'تیتر دعوت به اقدام',
			'zc-ft-cta-title'
		);

		$btn_call = self::widget(
			'button',
			array_merge(
				array(
					'text'             => 'تماس مستقیم: 09024561001',
					'link'             => array( 'url' => 'tel:09024561001', 'is_external' => '', 'nofollow' => '' ),
					'align'            => 'start',
					'size'             => 'md',
					'selected_icon'    => array( 'value' => 'fas fa-phone-volume', 'library' => 'fa-solid' ),
					'icon_align'       => 'right',
					'_flex_align_self' => 'center',
				),
				self::typo( '15.2', '600', array( 'line_height' => '1.4' ) ),
				array(
					'background_color'                  => $gold,
					'button_text_color'                 => $ink,
					'button_background_hover_color'     => $gold_hi,
					'button_hover_color'                => $ink,
					'border_radius'                     => $pill,
					'text_padding'                      => array( 'unit' => 'px', 'top' => '12.8', 'right' => '26.4', 'bottom' => '12.8', 'left' => '26.4', 'isLinked' => false ),
					'button_box_shadow_box_shadow_type' => 'yes',
					'button_box_shadow_box_shadow'      => array( 'horizontal' => 0, 'vertical' => 8, 'blur' => 24, 'spread' => 0, 'color' => 'rgba(253,208,91,0.40)' ),
				)
			),
			'دکمهٔ تماس مستقیم',
			'zc-ft-gold'
		);

		$btn_order = self::widget(
			'button',
			array_merge(
				array(
					'text'             => 'ثبت سفارش آنلاین پروژه',
					'link'             => array( 'url' => home_url( '/contact-us/' ), 'is_external' => '', 'nofollow' => '' ),
					'align'            => 'start',
					'size'             => 'md',
					'selected_icon'    => array( 'value' => 'fas fa-paper-plane', 'library' => 'fa-solid' ),
					'icon_align'       => 'right',
					'_flex_align_self' => 'center',
				),
				self::typo( '15.2', '600', array( 'line_height' => '1.4' ) ),
				array(
					'background_color'              => 'rgba(255,255,255,0.10)',
					'button_text_color'             => $white,
					'button_background_hover_color' => 'rgba(255,255,255,0.16)',
					'button_hover_color'            => $white,
					'border_border'                 => 'solid',
					'border_width'                  => array( 'unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true ),
					'border_color'                  => 'rgba(255,255,255,0.20)',
					'border_radius'                 => $pill,
					'text_padding'                  => array( 'unit' => 'px', 'top' => '12.8', 'right' => '26.4', 'bottom' => '12.8', 'left' => '26.4', 'isLinked' => false ),
				)
			),
			'دکمهٔ ثبت سفارش آنلاین',
			'zc-ft-glass'
		);

		$cta_btns = self::container(
			array( $btn_call, $btn_order ),
			array_merge(
				self::line( '14' ),
				array( 'css' => 'zc-ft-buttons', 'title' => 'دکمه‌های دعوت به اقدام', 'justify' => 'flex-start', 'nowrap' => true, 'wrap_m' => true )
			),
			true
		);

		$cta = self::container(
			array( $badge, $cta_title, $cta_btns ),
			array_merge(
				self::stack( '14' ),
				array(
					'css'        => 'zc-ft-cta',
					'title'      => 'کارت دعوت به اقدام',
					'content_width' => 'full',
					'justify'    => 'center',
					'align'      => 'flex-start',
					'padding'    => array( 'top' => '26', 'right' => '36', 'bottom' => '26', 'left' => '36' ),
					'padding_t'  => array( 'top' => '22', 'right' => '20', 'bottom' => '22', 'left' => '20' ),
					'background' => 'rgba(255,255,255,0.05)',
					'border'     => 'rgba(255,255,255,0.08)',
					'radius'     => '24',
				)
			),
			true
		);

		/* brand column ─────────────────────────────────────────────────────── */
		$monogram = self::widget(
			'heading',
			array_merge(
				array(
					'title'            => 'ZC',
					'header_size'      => 'span',
					'align'            => 'center',
					'title_color'      => $ink,
					'_flex_align_self' => 'center',
					'_flex_size'       => 'none',
					'_element_width'   => 'auto',
				),
				self::typo( '20.8', '900', array( 'line_height' => '44.8', 'line_height_unit' => 'px' ) ),
				array(
					'_background_background' => 'classic',
					'_background_color'      => $gold,
					'_border_radius'         => $pill,
					'_padding'               => array( 'unit' => 'px', 'top' => '0', 'right' => '8', 'bottom' => '0', 'left' => '8', 'isLinked' => false ),
				)
			),
			'مونوگرام برند',
			'zc-ft-mark'
		);

		$brand_name = self::widget(
			'heading',
			array_merge(
				array(
					'title'       => 'زرین کد',
					'header_size' => 'h3',
					'align'       => 'start',
					'title_color' => $white,
				),
				self::typo( '20', '900', array( 'line_height' => '1.6', 'size_m' => '18' ) )
			),
			'نام برند',
			'zc-ft-brand'
		);

		$tagline = self::widget(
			'text-editor',
			array_merge(
				array(
					'editor'     => '<p>Zarincode.com — Digital Engineering</p>',
					'text_color' => $gold,
					'align'      => 'start',
				),
				self::typo( '12.5', '400', array( 'line_height' => '1.8' ) )
			),
			'شعار برند',
			'zc-ft-tagline'
		);

		$brand_text = self::container(
			array( $brand_name, $tagline ),
			array_merge( self::stack( '2' ), array( 'title' => 'نام و شعار برند', 'grow' => true ) ),
			true
		);

		$brand_head = self::container(
			array( $monogram, $brand_text ),
			array_merge( self::line( '14' ), array( 'css' => 'zc-ft-brandhead', 'title' => 'نشان و نام برند', 'nowrap' => true ) ),
			true
		);

		$about = self::widget(
			'text-editor',
			array_merge(
				array(
					'editor'     => '<p>زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهنده خدمات فوق‌تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت و شبکه‌های اجتماعی، تولید محتوا و مارکت‌پلیس افزونه‌ها، قالب‌های وردپرس و سورس‌کدهای آماده تجاری است.</p>',
					'text_color' => $muted,
					'align'      => 'start',
				),
				self::typo( '14.4', '400', array( 'line_height' => '1.85' ) )
			),
			'متن معرفی زرین کد',
			'zc-ft-about'
		);

		$contact = self::icons(
			array(
				array( 'توسعه‌دهنده: احسان نادری پناه (Ehsan NaderiPanah)', '', 'fas fa-user-tie' ),
				array( 'خط ویژه مشاوره: 09024561001', 'tel:09024561001', 'fas fa-phone' ),
				array( 'وب‌سایت رسمی: Zarincode.com', 'https://zarincode.com', 'fas fa-globe' ),
			),
			array(
				'icon_color'                  => $gold,
				'text_color'                  => $body,
				'icon_size'                   => array( 'unit' => 'px', 'size' => 14 ),
				'space_between'               => array( 'unit' => 'px', 'size' => 11, 'sizes' => array() ),
				'view'                        => 'traditional',
				'icon_align'                  => 'right',
				'icon_typography_typography'  => 'custom',
				'icon_typography_font_size'   => array( 'unit' => 'px', 'size' => 14.08 ),
				'icon_typography_line_height' => array( 'unit' => 'em', 'size' => 1.7 ),
			),
			'اطلاعات تماس',
			'zc-ft-contact'
		);

		$brand_col = self::container(
			array( $brand_head, $about, $contact, self::socials( 'شبکه‌های اجتماعی فوتر' ) ),
			array_merge(
				self::stack( '22' ),
				array(
					'css'          => 'zc-ft-col-brand',
					'title'        => 'ستون برند و پشتیبانی',
					'ratio'        => 29.6,
					'ratio_tablet' => 100,
					'ratio_mobile' => 100,
				)
			),
			true
		);

		/* services column ──────────────────────────────────────────────────── */
		$services_col = self::container(
			array(
				self::column_title( 'خدمات تخصصی زرین کد', 'عنوان ستون خدمات' ),
				self::links(
					array(
						array( 'طراحی سایت حرفه‌ای و المنتوری', home_url( '/service/website-design/' ), 'fas fa-angle-left' ),
						array( 'برنامه‌نویسی نرم‌افزار و وب‌اپلیکیشن', home_url( '/service/software-development/' ), 'fas fa-angle-left' ),
						array( 'سئو سایت و بهینه‌سازی موتور جستجو', home_url( '/service/website-seo/' ), 'fas fa-angle-left' ),
						array( 'سئو شبکه‌های اجتماعی (اینستاگرام و لینکدین)', home_url( '/service/social-media-seo/' ), 'fas fa-angle-left' ),
						array( 'تولید محتوای تخصصی و استراتژی محتوا', home_url( '/service/content-creation/' ), 'fas fa-angle-left' ),
						array( 'فروشگاه قالب، افزونه و سورس‌کد آماده', home_url( '/service/ready-made-products/' ), 'fas fa-angle-left' ),
					),
					self::footer_link_styles(),
					'پیوندهای خدمات'
				),
			),
			array_merge(
				self::stack( '0' ),
				array( 'css' => 'zc-ft-col-services', 'title' => 'ستون خدمات', 'ratio' => 20.2, 'ratio_tablet' => 45, 'ratio_mobile' => 100 )
			),
			true
		);

		/* legal column ─────────────────────────────────────────────────────── */
		$legal_col = self::container(
			array(
				self::column_title( 'قوانین و اسناد حقوقی', 'عنوان ستون قوانین' ),
				self::links(
					array(
						array( 'قوانین و شرایط و مقررات کشور ایران', home_url( '/terms-and-conditions/' ), 'fas fa-balance-scale' ),
						array( 'شرایط گارانتی و پشتیبانی فنی', home_url( '/warranty-policy/' ), 'fas fa-shield-alt' ),
						array( 'سیاست حفظ حریم خصوصی کاربران', home_url( '/privacy-policy/' ), 'fas fa-user-shield' ),
						array( 'شرایط و ضوابط بازگشت وجه', home_url( '/refund-policy/' ), 'fas fa-undo' ),
						array( 'قوانین حفاظت داده اتحادیه اروپا (GDPR)', home_url( '/gdpr-compliance/' ), 'fas fa-globe' ),
						array( 'DMCA Takedown Policy (English)', home_url( '/dmca-policy/' ), 'fas fa-copyright' ),
					),
					self::footer_link_styles(),
					'پیوندهای قوانین'
				),
			),
			array_merge(
				self::stack( '0' ),
				array( 'css' => 'zc-ft-col-legal', 'title' => 'ستون قوانین', 'ratio' => 20.2, 'ratio_tablet' => 45, 'ratio_mobile' => 100 )
			),
			true
		);

		/* credentials column ───────────────────────────────────────────────── */
		$chips = self::container(
			array(
				self::chip( 'درباره زرین کد', home_url( '/about-us/' ), 'چیپ درباره' ),
				self::chip( 'نمونه‌کارها', home_url( '/projects-portfolio/' ), 'چیپ نمونه‌کارها' ),
				self::chip( 'تماس با ما', home_url( '/contact-us/' ), 'چیپ تماس' ),
			),
			array_merge( self::line( '6' ), array( 'css' => 'zc-ft-chips', 'title' => 'چیپ‌های اعتبارنامه', 'justify' => 'flex-start' ) ),
			true
		);

		$cred_col = self::container(
			array( self::column_title( 'اعتبارنامه‌ها و راه‌های ارتباطی', 'عنوان ستون اعتبارنامه‌ها' ), $chips ),
			array_merge(
				self::stack( '22' ),
				array( 'css' => 'zc-ft-col-cred', 'title' => 'ستون اعتبارنامه‌ها', 'ratio' => 21.6, 'ratio_tablet' => 45, 'ratio_mobile' => 100 )
			),
			true
		);

		$grid = self::container(
			array( $brand_col, $services_col, $legal_col, $cred_col ),
			array_merge(
				self::line( '36' ),
				array(
					'css'         => 'zc-ft-grid',
					'title'       => 'ستون‌های فوتر',
					'content_width' => 'full',
					'align'       => 'flex-start',
					'nowrap'      => true,
					'wrap_t'      => true,
					'wrap_m'      => true,
					'padding'     => array( 'top' => '0', 'right' => '0', 'bottom' => '68', 'left' => '0' ),
					'border'      => $line,
					'border_side' => 'bottom',
				)
			),
			true
		);

		/* bottom bar ───────────────────────────────────────────────────────── */
		$copyright = self::widget(
			'text-editor',
			array_merge(
				array(
					'editor'     => '<p>تمامی حقوق مادی و معنوی این قالب و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه (Ehsan NaderiPanah) می‌باشد.</p>',
					'text_color' => $muted,
					'align'      => 'start',
				),
				self::typo( '13.5', '400', array( 'line_height' => '1.9' ) )
			),
			'متن کپی‌رایت',
			'zc-ft-copy'
		);

		$support = self::widget(
			'text-editor',
			array_merge(
				array(
					'editor'     => '<p>پشتیبانی و فروش: <strong dir="ltr">09024561001</strong> &nbsp;•&nbsp; طراحی بر پایه استانداردهای Zhaket &amp; ThemeForest</p>',
					'text_color' => $muted,
					'align'      => 'start',
				),
				self::typo( '13.5', '400', array( 'line_height' => '1.9' ) )
			),
			'خط پشتیبانی و استانداردها',
			'zc-ft-support'
		);

		$bottom = self::container(
			array(
				self::container( array( $copyright ), array_merge( self::stack( '0' ), array( 'css' => 'zc-ft-copy-cell', 'title' => 'ستون کپی‌رایت', 'ratio' => 55, 'ratio_tablet' => 100, 'ratio_mobile' => 100 ) ), true ),
				self::container( array( $support ), array_merge( self::stack( '0' ), array( 'css' => 'zc-ft-support-cell', 'title' => 'ستون پشتیبانی', 'ratio' => 45, 'ratio_tablet' => 100, 'ratio_mobile' => 100 ) ), true ),
			),
			array_merge(
				self::line( '16' ),
				array(
					'css'     => 'zc-ft-bottom',
					'title'   => 'نوار پایانی فوتر',
					'content_width' => 'full',
					'nowrap'  => true,
					'wrap_m'  => true,
					'justify' => 'space-between',
					'align'   => 'center',
					'padding' => array( 'top' => '40', 'right' => '0', 'bottom' => '0', 'left' => '0' ),
				)
			),
			true
		);

		/* the plate ────────────────────────────────────────────────────────── */
		$plate = self::container(
			array( $cta, $grid, $bottom ),
			array(
				'css'           => 'zc-ft-plate',
				'title'         => 'فوتر زرین کد',
				'direction'     => 'column',
				'gap'           => array( 'column' => '40', 'row' => '40' ),
				'padding'       => array( 'top' => '48', 'right' => '64', 'bottom' => '56', 'left' => '64' ),
				'padding_t'     => array( 'top' => '40', 'right' => '32', 'bottom' => '32', 'left' => '32' ),
				'padding_m'     => array( 'top' => '32', 'right' => '20', 'bottom' => '24', 'left' => '20' ),
				'gradient'      => array( '#2E3138', '#22242A', '180' ),
				'border'        => 'rgba(253,208,91,0.16)',
				'border_side'   => 'no-bottom',
				'radius'        => array( 'top' => '32', 'right' => '32', 'bottom' => '0', 'left' => '0' ),
				'shadow'        => array( 'horizontal' => 0, 'vertical' => 24, 'blur' => 60, 'spread' => 0, 'color' => 'rgba(42,44,49,0.13)' ),
				'content_width' => 'full',
			)
		);

		return array( $plate );
	}

	/** A dot + title row, exactly the 4.9.0 `.zc-footer-title` block. */
	private static function column_title( string $text, string $name ): array {
		$dot = self::widget(
			'icon',
			array(
				'selected_icon'    => array( 'value' => 'fas fa-circle', 'library' => 'fa-solid' ),
				'primary_color'    => '#FDD05B',
				'size'             => array( 'unit' => 'px', 'size' => 8.8 ),
				'align'            => 'start',
				'_flex_align_self' => 'center',
			),
			'نقطهٔ طلایی عنوان'
		);
		$title = self::widget(
			'heading',
			array_merge(
				array( 'title' => $text, 'header_size' => 'h3', 'align' => 'start', 'title_color' => '#FFFFFF' ),
				self::typo( '16.8', '800', array( 'line_height' => '1.6', 'size_m' => '15.4' ) )
			),
			'متن عنوان ستون'
		);
		return self::container(
			array( $dot, $title ),
			array_merge( self::line( '8' ), array( 'css' => 'zc-ft-title', 'title' => $name, 'align' => 'center' ) ),
			true
		);
	}

	/** Shared colours/typography for the two footer link lists. */
	private static function footer_link_styles(): array {
		return array(
			'icon_color'                  => '#9CA3AF',
			'icon_color_hover'            => '#FDD05B',
			'text_color'                  => '#9CA3AF',
			'text_color_hover'            => '#FDD05B',
			'icon_size'                   => array( 'unit' => 'px', 'size' => 12 ),
			'space_between'               => array( 'unit' => 'px', 'size' => 8, 'sizes' => array() ),
			'view'                        => 'traditional',
			'icon_align'                  => 'right',
			'icon_typography_typography'  => 'custom',
			'icon_typography_font_size'   => array( 'unit' => 'px', 'size' => 14.4 ),
			'icon_typography_line_height' => array( 'unit' => 'em', 'size' => 1.7 ),
		);
	}

	/* =====================================================================
	 * Small builders — one per Elementor control group, so a value is written
	 * once and shows up in the panel under the label the owner expects.
	 * ===================================================================== */

	/**
	 * Typography for a widget.
	 *
	 * @param string $size   Font size in px (string keeps the decimal).
	 * @param string $weight Font weight.
	 * @param array  $extra  Extra typography sub-controls: line_height,
	 *                       line_height_unit, letter_spacing, text_transform.
	 */
	private static function typo( string $size, string $weight, array $extra = array() ): array {
		$out = array(
			'typography_typography'  => 'custom',
			'typography_font_size'   => array( 'unit' => 'px', 'size' => (float) $size ),
			'typography_font_weight' => $weight,
		);
		if ( isset( $extra['line_height'] ) ) {
			$out['typography_line_height'] = array(
				'unit' => $extra['line_height_unit'] ?? 'em',
				'size' => (float) $extra['line_height'],
			);
		}
		if ( isset( $extra['size_m'] ) ) {
			$out['typography_font_size_mobile'] = array( 'unit' => 'px', 'size' => (float) $extra['size_m'] );
		}
		if ( isset( $extra['size_t'] ) ) {
			$out['typography_font_size_tablet'] = array( 'unit' => 'px', 'size' => (float) $extra['size_t'] );
		}
		if ( isset( $extra['letter_spacing'] ) ) {
			$out['typography_letter_spacing'] = array( 'unit' => 'em', 'size' => (float) $extra['letter_spacing'] );
		}
		if ( isset( $extra['text_transform'] ) ) {
			$out['typography_text_transform'] = $extra['text_transform'];
		}
		return $out;
	}

	/** Defaults for a container that stacks its children vertically. */
	private static function stack( string $gap ): array {
		return array(
			'direction' => 'column',
			'gap'       => array( 'column' => $gap, 'row' => $gap ),
		);
	}

	/** Defaults for a container that lays its children out in one line. */
	private static function line( string $gap ): array {
		return array(
			'direction' => 'row',
			'gap'       => array( 'column' => $gap, 'row' => $gap ),
			'align'     => 'center',
		);
	}

	/**
	 * A Flexbox Container with the design written as Elementor controls.
	 *
	 * Supported keys: css, title, direction, nowrap, gap, justify, align,
	 * width/t_width/m_width (percent columns), ratio/ratio_tablet/
	 * ratio_mobile (flex-basis 0 + grow, i.e. `1.6fr 1fr …`), grow,
	 * padding/padding_t/padding_m, background, gradient (from/to/angle),
	 * border + border_side, radius, shadow, content_width.
	 *
	 * @param array $children Child elements.
	 * @param array $args     Control values (see above).
	 * @param bool  $inner    True for a container nested inside another one.
	 */
	private static function container( array $children, array $args = array(), bool $inner = false ): array {
		$settings = array(
			'content_width'        => $args['content_width'] ?? 'boxed',
			'flex_direction'       => $args['direction'] ?? 'row',
			'flex_wrap'            => ! empty( $args['nowrap'] ) ? 'nowrap' : 'wrap',
			'flex_justify_content' => $args['justify'] ?? 'flex-start',
			'flex_align_items'     => $args['align'] ?? 'stretch',
		);

		$gap = $args['gap'] ?? array( 'column' => '20', 'row' => '20' );
		$settings['flex_gap'] = array(
			'column'   => (string) $gap['column'],
			'row'      => (string) $gap['row'],
			'unit'     => 'px',
			'isLinked' => false,
		);

		if ( ! empty( $args['wrap_t'] ) ) {
			$settings['flex_wrap_tablet'] = 'wrap';
		}
		if ( ! empty( $args['wrap_m'] ) ) {
			$settings['flex_wrap_mobile'] = 'wrap';
		}

		if ( ! empty( $args['ratio'] ) ) {
			// Proportional column, exactly like CSS `1.6fr 1fr 1fr 1.2fr`: the
			// ratio is written as a percentage of the row, which is the value
			// the Width control stores (a 0 flex-basis reads as “empty” to
			// Elementor, so it would never be saved).
			$settings['content_width'] = 'full';
			$settings['width']         = array( 'unit' => '%', 'size' => (float) $args['ratio'] );
			$settings['_flex_size']    = 'custom';
			$settings['_flex_grow']    = 0;
			$settings['_flex_shrink']  = 1;
			if ( isset( $args['ratio_tablet'] ) ) {
				$settings['width_tablet']  = array( 'unit' => '%', 'size' => (float) $args['ratio_tablet'] );
				$settings['_flex_size_tablet'] = 'custom';
				$settings['_flex_grow_tablet'] = 0;
				$settings['_flex_shrink_tablet'] = 1;
			}
			if ( isset( $args['ratio_mobile'] ) ) {
				$settings['width_mobile']  = array( 'unit' => '%', 'size' => (float) $args['ratio_mobile'] );
				$settings['_flex_size_mobile'] = 'custom';
				$settings['_flex_grow_mobile'] = 0;
				$settings['_flex_shrink_mobile'] = 1;
			}
		} elseif ( ! empty( $args['width'] ) && 'auto' !== $args['width'] && '100' !== (string) $args['width'] ) {
			$settings['content_width'] = 'full';
			$settings['width']         = array( 'unit' => '%', 'size' => (float) $args['width'] );
			if ( ! empty( $args['t_width'] ) ) {
				$settings['width_tablet'] = array( 'unit' => '%', 'size' => (float) $args['t_width'] );
			}
			if ( ! empty( $args['m_width'] ) ) {
				$settings['width_mobile'] = array( 'unit' => '%', 'size' => (float) $args['m_width'] );
			}
		} elseif ( ! empty( $args['grow'] ) ) {
			$settings['_flex_size'] = 'grow';
		} elseif ( ! empty( $args['shrink'] ) ) {
			$settings['content_width'] = 'full';
			$settings['_flex_size']    = 'none';
		}

		/*
		 * Elementor gives every nested Container a 10px padding by default.
		 * That default silently eats 20px of every column (and 20px of height
		 * per nested box), so the tree writes the padding explicitly — zero
		 * unless the design asks for more.
		 */
		$settings['padding'] = self::box( $args['padding'] ?? array() );
		if ( ! empty( $args['padding_t'] ) ) {
			$settings['padding_tablet'] = self::box( $args['padding_t'] );
		}
		if ( ! empty( $args['padding_m'] ) ) {
			$settings['padding_mobile'] = self::box( $args['padding_m'] );
		}

		if ( ! empty( $args['gradient'] ) ) {
			$settings['background_background']     = 'gradient';
			$settings['background_color']          = $args['gradient'][0];
			$settings['background_color_b']        = $args['gradient'][1];
			$settings['background_gradient_type']  = 'linear';
			$settings['background_gradient_angle'] = array( 'unit' => 'deg', 'size' => (float) $args['gradient'][2] );
		} elseif ( ! empty( $args['background'] ) ) {
			$settings['background_background'] = 'classic';
			$settings['background_color']      = $args['background'];
		}

		if ( ! empty( $args['border'] ) ) {
			$side = $args['border_side'] ?? 'all';
			$w    = array( 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1' );
			if ( 'bottom' === $side ) {
				$w = array( 'top' => '0', 'right' => '0', 'bottom' => '1', 'left' => '0' );
			} elseif ( 'no-bottom' === $side ) {
				$w['bottom'] = '0';
			}
			$settings['border_border'] = 'solid';
			$settings['border_width']  = array( 'unit' => 'px', 'isLinked' => false ) + $w;
			$settings['border_color']  = $args['border'];
		}

		if ( ! empty( $args['radius'] ) ) {
			if ( is_array( $args['radius'] ) ) {
				$settings['border_radius'] = array( 'unit' => 'px', 'isLinked' => false ) + $args['radius'];
			} else {
				$settings['border_radius'] = array( 'unit' => 'px', 'top' => $args['radius'], 'right' => $args['radius'], 'bottom' => $args['radius'], 'left' => $args['radius'], 'isLinked' => true );
			}
		}

		if ( ! empty( $args['shadow'] ) ) {
			$settings['box_shadow_box_shadow_type'] = 'yes';
			$settings['box_shadow_box_shadow']      = $args['shadow'];
		}

		if ( ! empty( $args['title'] ) ) {
			$settings['_title'] = $args['title'];
		}
		if ( ! empty( $args['css'] ) ) {
			$settings['css_classes'] = $args['css'];
		}

		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => $inner,
			'settings' => $settings,
			'elements' => $children,
		);
	}

	/** Four-sided box value (`padding`, `margin`). */
	private static function box( array $v ): array {
		return array(
			'unit'     => 'px',
			'top'      => (string) ( $v['top'] ?? '0' ),
			'right'    => (string) ( $v['right'] ?? '0' ),
			'bottom'   => (string) ( $v['bottom'] ?? '0' ),
			'left'     => (string) ( $v['left'] ?? '0' ),
			'isLinked' => false,
		);
	}

	/** An icon list with per-row icon, label and link, styled from the panel. */
	private static function icons( array $rows, array $settings = array(), string $name = '', string $css = '' ): array {
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
			array_merge( array( 'icon_list' => $items ), $settings ),
			$name,
			$css
		);
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
				'align'       => 'start',
				'size'        => 'sm',
				'button_text_color' => '#FFFFFF',
				'background_color'  => 'rgba(255,255,255,0.08)',
				'border_radius'     => array( 'unit' => 'px', 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'isLinked' => true ),
				'button_background_hover_color' => 'rgba(255,255,255,0.14)',
				'button_hover_color' => '#FFFFFF',
				'text_padding'       => array( 'unit' => 'px', 'top' => '7', 'right' => '10', 'bottom' => '7', 'left' => '10', 'isLinked' => false ),
				'typography_typography'   => 'custom',
				'typography_font_size'    => array( 'unit' => 'px', 'size' => 12.6 ),
				'typography_font_weight'  => '500',
				'typography_line_height'  => array( 'unit' => 'em', 'size' => 1.4 ),
				'_element_width'     => 'auto',
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
