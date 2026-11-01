<?php
/**
 * Consultation inbox: every «درخواست مشاوره رایگان» submission is stored in wp-admin
 * as a private CPT with an exact field-by-field record (plus IP, UA, page, time)
 * and presented through a dedicated inbox screen (stats, search, filters, detail,
 * statuses).
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Features;

defined( 'ABSPATH' ) || exit;

final class Consultation_Inbox {
	public const SLUG = 'zc_consultation';
	private const NONCE = 'zc_consult';
	private static ?self $instance = null;

	public static function boot(): void {
		self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'wp_ajax_zc_consult_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_zc_consult_submit', array( $this, 'ajax_submit' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 6 );
	}

	/* ------------------------------------------------------------------ data */

	public function register_cpt(): void {
		register_post_type(
			self::SLUG,
			array(
				'labels'              => array(
					'name'          => 'درخواست‌های مشاوره',
					'singular_name' => 'درخواست مشاوره',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/** Normalise Iranian mobile numbers to 09xxxxxxxxx. */
	public static function phone( string $raw ): string {
		$raw = preg_replace( '/[\s\-\(\)]+/', '', trim( $raw ) ) ?? '';
		if ( preg_match( '/^(?:\+98|0098|98|0)(9\d{9})$/', $raw, $m ) ) {
			return '0' . $m[1];
		}
		return '';
	}

	public static function ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$first = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$cand   = trim( $first[0] );
			if ( filter_var( $cand, FILTER_VALIDATE_IP ) ) {
				$ip = $cand;
			}
		}
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	public static function service_label( string $value ): string {
		return array(
			'web-design'      => 'طراحی سایت اختصاصی و المنتوری',
			'software'        => 'برنامه‌نویسی نرم‌افزار و سامانه تحت وب',
			'seo'             => 'سئو سایت تضمینی و رشد گوگل',
			'social-seo'      => 'سئو شبکه‌های اجتماعی (اینستاگرام/لینکدین)',
			'content'         => 'تولید محتوای تخصصی و سئو شده',
			'ready-products'  => 'خرید قالب، افزونه یا سورس‌کد آماده',
		)[ $value ] ?? $value;
	}

	public static function priority_label( string $value ): string {
		return array(
			'normal'   => 'عادی',
			'urgent'   => 'فوریتی',
			'relaxed'  => 'غیرفوریتی',
		)[ $value ] ?? $value;
	}

	/** Count of submissions that still have status=new (menu badge). */
	public static function unread(): int {
		$q = new \WP_Query(
			array(
				'post_type'      => self::SLUG,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_zc_status', 'value' => 'new' ) ),
			)
		);
		return (int) $q->found_posts; // real total, not the capped post_count.
	}

	/* -------------------------------------------------------------- front ajax */

	public function ajax_submit(): void {
		check_ajax_referer( self::NONCE, 'zc_consult_nonce', false ) || wp_send_json_error( array( 'message' => 'نشانی امنیتی نامعتبر است؛ صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ), 403 );

		// Honeypot: real visitors never fill the invisible field.
		if ( ! empty( $_POST['zc_hp'] ) ) {
			wp_send_json_success( array( 'message' => 'درخواست شما با موفقیت ثبت شد!' ) );
		}

		$ip = self::ip();
		if ( $ip ) {
			$key  = 'zc_cf_' . md5( $ip );
			$hits = (int) get_transient( $key );
			if ( $hits >= 4 ) {
				wp_send_json_error( array( 'message' => 'چند درخواست اخیر ثبت شده است؛ لطفاً کمی بعد دوباره تلاش کنید.' ), 429 );
			}
			set_transient( $key, $hits + 1, 15 * MINUTE_IN_SECONDS );
		}

		$name  = sanitize_text_field( (string) ( $_POST['zc-consult-name'] ?? '' ) );
		$phone = self::phone( (string) ( $_POST['zc-consult-phone'] ?? '' ) );

		if ( mb_strlen( $name ) < 2 ) {
			wp_send_json_error( array( 'message' => 'نام و نام خانوادگی الزامی است.' ), 422 );
		}
		if ( '' === $phone ) {
			wp_send_json_error( array( 'message' => 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۳۴۵۶۷۸۹).' ), 422 );
		}

		// Persist EVERY submitted field exactly (minus protocol keys).
		$skip   = array( 'action', 'zc_consult_nonce', '_wp_http_referer', 'zc_hp', 'zc_page' );
		$fields = array();
		foreach ( $_POST as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, $skip, true ) || str_starts_with( $key, '_' ) ) {
				continue;
			}
			$fields[ $key ] = is_array( $value )
				? array_map( array( __CLASS__, 'clean_scalar' ), wp_unslash( $value ) )
				: self::clean_field( $key, (string) wp_unslash( $value ) );
		}
		$fields['zc-consult-name']  = $name;
		$fields['zc-consult-phone'] = $phone;

		$id = wp_insert_post(
			array(
				'post_type'   => self::SLUG,
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s — %s', $name, $phone ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => 'ثبت درخواست ناموفق بود؛ لطفاً بعداً تلاش کنید یا تماس بگیرید.' ), 500 );
		}

		foreach ( $fields as $key => $value ) {
			update_post_meta( $id, '_zc_field_' . sanitize_key( $key ), maybe_serialize( $value ) );
		}
		update_post_meta( $id, '_zc_status', 'new' );
		update_post_meta( $id, '_zc_ip', $ip );
		update_post_meta( $id, '_zc_ua', mb_substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 300 ) );
		update_post_meta( $id, '_zc_page', esc_url_raw( (string) ( $_POST['zc_page'] ?? '' ) ?: ( wp_get_referer() ?: '' ) ) );
		update_post_meta( $id, '_zc_referrer', esc_url_raw( (string) ( $_SERVER['HTTP_REFERER'] ?? '' ) ) );
		update_post_meta( $id, '_zc_submitted', current_time( 'mysql' ) );

		$this->notify_admin( $id, $name, $phone, $fields );

		wp_send_json_success(
			array(
				'message' => 'درخواست شما با موفقیت ثبت شد! احسان نادری پناه و تیم زرین کد (۰۹۰۲۴۵۶۱۰۰۱) در سریع‌ترین زمان با شما تماس می‌گیرند.',
				'id'      => $id,
			)
		);
	}

	public static function clean_scalar( $value ) {
		return is_scalar( $value ) ? self::clean_field( 'x', (string) $value ) : '';
	}

	private static function clean_field( string $key, string $value ): string {
		return ( false !== strpos( $key, 'details' ) || false !== strpos( $key, 'message' ) || false !== strpos( $key, 'description' ) )
			? sanitize_textarea_field( $value )
			: sanitize_text_field( $value );
	}

	private function notify_admin( int $id, string $name, string $phone, array $fields ): void {
		$lines = array(
			'درخواست مشاوره رایگان جدید در سایت زرین کد ثبت شد.',
			'',
			'نام: ' . $name,
			'شماره موبایل: ' . $phone,
		);
		foreach ( $fields as $key => $value ) {
			if ( in_array( $key, array( 'zc-consult-name', 'zc-consult-phone' ), true ) || ! is_scalar( $value ) || '' === $value ) {
				continue;
			}
			$lines[] = $key . ': ' . $value;
		}
		$lines[] = 'مشاهده در پیشخوان: ' . admin_url( 'admin.php?page=zc-inbox&zc_view=' . $id );
		wp_mail(
			get_option( 'admin_email' ),
			'درخواست مشاوره جدید — ' . $name . ' (' . $phone . ')',
			implode( "\n", $lines ),
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	/* ------------------------------------------------------------------ admin */

	public function admin_menu(): void {
		$badge = self::unread();
		$title = 'صندوق ورودی' . ( $badge ? ' ' . $badge : '' );
		add_menu_page(
			'صندوق ورودی',
			$title,
			'manage_options',
			'zc-inbox',
			array( $this, 'render_list' ),
			'dashicons-email-alt',
			26
		);
		add_submenu_page( 'zc-inbox', 'درخواست‌های مشاوره', 'مشاوره رایگان', 'manage_options', 'zc-inbox', array( $this, 'render_list' ) );
	}

	private function query_args(): array {
		$status  = sanitize_key( (string) ( $_GET['zc_status'] ?? 'all' ) );
		$paged   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$zq      = sanitize_text_field( (string) ( $_GET['zc_q'] ?? '' ) );
		$args    = array(
			'post_type'      => self::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$mq = array();
		if ( in_array( $status, array( 'new', 'read', 'replied', 'spam' ), true ) ) {
			$mq[] = array( 'key' => '_zc_status', 'value' => $status );
		}
		if ( '' !== $zq ) {
			// Search across the three fields visitors actually type into.
			$mq[] = array(
				'relation' => 'OR',
				array( 'key' => '_zc_field_zc-consult-name', 'value' => $zq, 'compare' => 'LIKE' ),
				array( 'key' => '_zc_field_zc-consult-phone', 'value' => $zq, 'compare' => 'LIKE' ),
				array( 'key' => '_zc_field_zc-consult-details', 'value' => $zq, 'compare' => 'LIKE' ),
				array( 'key' => '_zc_field_message', 'value' => $zq, 'compare' => 'LIKE' ),
			);
		}
		if ( $mq ) {
			$args['meta_query'] = $mq; // AND between status and (OR search-group).
		}
		return $args;
	}

	/** Lightweight per-status counters for the stats cards. */
	private function stats(): array {
		$count = static fn( string $status ) => ( new \WP_Query(
			array(
				'post_type'      => self::SLUG,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_zc_status', 'value' => $status ) ),
			)
		) )->found_posts; // ppp=1 ⇒ post_count would cap at 1; found_posts is the real total.
		$total = ( new \WP_Query(
			array(
				'post_type'      => self::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		) )->found_posts;
		return array(
			'total'    => (int) $total,
			'new'      => (int) $count( 'new' ),
			'read'     => (int) $count( 'read' ),
			'replied'  => (int) $count( 'replied' ),
			'spam'     => (int) $count( 'spam' ),
		);
	}

	private function status_badge( string $status ): string {
		$map = array(
			'new'     => array( 'جدید', '#d1fae5', '#065f46' ),
			'read'    => array( 'خوانده‌شده', '#e0e7ff', '#3730a3' ),
			'replied' => array( 'پاسخ‌داده‌شده', '#fef3c7', '#92400e' ),
			'spam'    => array( 'هرزنامه', '#fee2e2', '#991b1b' ),
		);
		[ $label, $bg, $fg ] = $map[ $status ] ?? array( $status, '#f3f4f6', '#374151' );
		return '<span style="display:inline-block;padding:2px 9px;border-radius:999px;background:' . esc_attr( $bg ) . ';color:' . esc_attr( $fg ) . ';font-size:11px;font-weight:700;">' . esc_html( $label ) . '</span>';
	}

	private function priority_chip( string $value ): string {
		$map = array(
			'urgent'  => array( 'فوریتی', '#fee2e2', '#991b1b' ),
			'normal'  => array( 'عادی', '#f1f5f9', '#334155' ),
			'relaxed' => array( 'غیرفوریتی', '#ecfdf5', '#065f46' ),
		);
		[ $label, $bg, $fg ] = $map[ $value ] ?? array( self::priority_label( $value ), '#f8fafc', '#475569' );
		return '<span style="display:inline-block;padding:2px 9px;border-radius:999px;background:' . esc_attr( $bg ) . ';color:' . esc_attr( $fg ) . ';font-size:11px;font-weight:700;">' . esc_html( $label ) . '</span>';
	}

	/** zc_msg → dismissible admin notice. */
	private function flash_notice(): void {
		$code = sanitize_key( (string) ( $_GET['zc_msg'] ?? '' ) );
		if ( '' === $code ) {
			return;
		}
		$map = array(
			'deleted'   => 'درخواست برای همیشه حذف شد.',
			'bulk-done' => 'عملیات گروهی روی درخواست‌های انتخاب‌شده اعمال شد.',
			'updated'   => 'وضعیت درخواست به‌روزرسانی شد.',
		);
		if ( ! isset( $map[ $code ] ) ) {
			return;
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $map[ $code ] ) . '</p></div>';
	}

	/** Admin-screen stylesheet (cards, toolbar, table polish). */
	private function print_styles(): void {
		echo '<style>
		.zc-inbox-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin:14px 0 16px;}
		.zc-inbox-stat{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px #0f172a08;}
		.zc-inbox-stat i{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;font-size:1rem;flex-shrink:0;}
		.zc-inbox-stat b{display:block;font-size:1.35rem;line-height:1.2;color:#0f172a;}
		.zc-inbox-stat span{font-size:.75rem;color:#64748b;font-weight:700;}
		.zc-inbox-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:10px 12px;margin:0 0 14px;}
		.zc-inbox-search{display:flex;gap:6px;align-items:center;}
		.zc-inbox-search input[type=search]{border:1px solid #cbd5e1;border-radius:10px;padding:8px 12px;min-width:220px;font-size:.85rem;}
		.zc-inbox-tabs{display:flex;gap:6px;flex-wrap:wrap;}
		.zc-inbox-tabs a{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:.8rem;font-weight:700;text-decoration:none;border:1px solid transparent;transition:background .15s,border-color .15s;}
		.zc-inbox-tabs a:hover{background:#e2e8f0;}
		.zc-inbox-tabs a.is-active{background:#292c32;color:#fdd05b;border-color:#292c32;}
		.zc-inbox-tabs a .cnt{background:#ffffffcc;color:#292c32;border-radius:999px;padding:0 7px;font-size:.7rem;font-weight:900;}
		.zc-inbox-table{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px #0f172a08;}
		.zc-inbox-table table{border-collapse:collapse;width:100%;}
		.zc-inbox-table th{background:#f8fafc;color:#334155;font-size:.75rem;font-weight:800;text-align:right;padding:10px 12px;border-bottom:1px solid #e2e8f0;}
		.zc-inbox-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:.83rem;}
		.zc-inbox-table tr:last-child td{border-bottom:0;}
		.zc-inbox-table tr:hover td{background:#fffbeb55;}
		.zc-inbox-actions{display:flex;gap:6px;flex-wrap:wrap;}
		.zc-inbox-actions a.button-link{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:8px;background:#f1f5f9;color:#0f172a;font-size:.72rem;font-weight:700;text-decoration:none;border:1px solid #e2e8f0;}
		.zc-inbox-actions a.button-link:hover{background:#fef3c7;border-color:#fdd05b;}
		.zc-inbox-empty{padding:2.2rem;text-align:center;color:#64748b;}
		.zc-inbox-pagination{display:flex;gap:6px;align-items:center;margin-top:14px;}
		.zc-inbox-pagination a,.zc-inbox-pagination span{min-width:32px;height:32px;display:inline-grid;place-items:center;padding:0 8px;border-radius:9px;background:#fff;border:1px solid #e2e8f0;font-size:.8rem;font-weight:700;text-decoration:none;color:#0f172a;}
		.zc-inbox-pagination span.current{background:#292c32;color:#fdd05b;border-color:#292c32;}
		.zc-inbox-detail dl{display:grid;grid-template-columns:200px 1fr;gap:6px 14px;margin:0;}
		.zc-inbox-detail dt{font-weight:700;color:#334155;background:#f8fafc;padding:8px 10px;border-radius:8px;}
		.zc-inbox-detail dd{margin:0;padding:8px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;white-space:pre-wrap;word-break:break-word;}
		.zc-inbox-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start;}
		.zc-inbox-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 3px #0f172a08;}
		.zc-inbox-card h2{margin:0 0 10px;font-size:.95rem;border-bottom:1px solid #f1f5f9;padding-bottom:8px;}
		@media(max-width:960px){.zc-inbox-grid{grid-template-columns:1fr;}.zc-inbox-detail dl{grid-template-columns:1fr;}}
		</style>';
	}

	public function render_list(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Single entry point for POST actions (list AND detail paths).
		$this->handle_actions();
		if ( isset( $_GET['zc_view'] ) ) {
			$this->render_detail( absint( $_GET['zc_view'] ) );
			return;
		}

		$this->print_styles();
		$this->flash_notice();

		$args   = $this->query_args();
		$q      = new \WP_Query( $args );
		$stats  = $this->stats();
		$zq     = sanitize_text_field( (string) ( $_GET['zc_q'] ?? '' ) );
		$cur_st = sanitize_key( (string) ( $_GET['zc_status'] ?? 'all' ) );
		$base   = admin_url( 'admin.php?page=zc-inbox' );

		$tabs = array(
			'all'     => array( 'همه', $stats['total'] ),
			'new'     => array( 'جدید', $stats['new'] ),
			'read'    => array( 'خوانده‌شده', $stats['read'] ),
			'replied' => array( 'پاسخ‌داده‌شده', $stats['replied'] ),
			'spam'    => array( 'هرزنامه', $stats['spam'] ),
		);

		echo '<div class="wrap" dir="rtl"><h1>صندوق ورودی درخواست‌های مشاوره <span style="font-size:.75em;font-weight:400;color:#64748b;">— همهٔ فرم‌های «درخواست مشاوره رایگان» با جزئیات کامل</span></h1>';

		// Stats cards.
		$cards = array(
			array( 'کل درخواست‌ها', $stats['total'], 'fa-solid fa-inbox', '#eef2ff', '#3730a3' ),
			array( 'جدید (خوانده‌نشده)', $stats['new'], 'fa-solid fa-envelope-open-text', '#d1fae5', '#065f46' ),
			array( 'پاسخ‌داده‌شده', $stats['replied'], 'fa-solid fa-circle-check', '#fef3c7', '#92400e' ),
			array( 'هرزنامه', $stats['spam'], 'fa-solid fa-shield-halved', '#fee2e2', '#991b1b' ),
		);
		echo '<div class="zc-inbox-stats">';
		foreach ( $cards as [ $label, $num, $icon, $bg, $fg ] ) {
			echo '<div class="zc-inbox-stat"><i class="' . esc_attr( $icon ) . '" style="background:' . esc_attr( $bg ) . ';color:' . esc_attr( $fg ) . ';"></i><div><b>' . esc_html( number_format_i18n( (int) $num ) ) . '</b><span>' . esc_html( $label ) . '</span></div></div>';
		}
		echo '</div>';

		// Toolbar: search + status tabs.
		echo '<div class="zc-inbox-toolbar">';
		echo '<form class="zc-inbox-search" method="get" action="' . esc_url( admin_url( 'admin.php' ) . '' ) . '">';
		echo '<input type="hidden" name="page" value="zc-inbox">';
		if ( 'all' !== $cur_st ) {
			echo '<input type="hidden" name="zc_status" value="' . esc_attr( $cur_st ) . '">';
		}
		echo '<input type="search" name="zc_q" value="' . esc_attr( $zq ) . '" placeholder="جستجو در نام، شماره یا شرح درخواست…">';
		echo '<button class="button button-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> جستجو</button>';
		if ( '' !== $zq ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( array( 'zc_status' => $cur_st ), $base ) ) . '">پاک کردن</a>';
		}
		echo '</form>';
		echo '<nav class="zc-inbox-tabs">';
		foreach ( $tabs as $key => [ $label, $cnt ] ) {
			$active = ( $cur_st === $key );
			$url    = add_query_arg( array_filter( array( 'zc_status' => $key, 'zc_q' => $zq ) ), $base );
			echo '<a class="' . ( $active ? 'is-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '<span class="cnt">' . esc_html( number_format_i18n( (int) $cnt ) ) . '</span></a>';
		}
		echo '</nav>';
		echo '</div>';

		echo '<form method="post" action="' . esc_url( $base ) . '">';
		wp_nonce_field( 'zc_inbox_bulk' );
		echo '<div class="zc-inbox-table"><table><thead><tr>';
		echo '<td style="width:26px;"><input type="checkbox" onclick="document.querySelectorAll(\'input[name^=zc_ids[]]\').forEach(c=>c.checked=this.checked)"></td>';
		echo '<th>درخواست‌کننده</th><th>شماره موبایل</th><th>سرویس</th><th>اولویت</th><th>شرح درخواست</th><th>صفحه</th><th>وضعیت</th><th>تاریخ ثبت</th><th>عملیات</th>';
		echo '</tr></thead><tbody>';
		if ( ! $q->posts ) {
			echo '<tr><td colspan="9" class="zc-inbox-empty">درخواستی با این فیلترها پیدا نشد' . ( '' !== $zq ? ' — عبارت جستجو را ساده‌تر کنید' : '' ) . '.</td></tr>';
		}
		foreach ( $q->posts as $post ) {
			$meta    = get_post_meta( $post->ID );
			$get     = static fn( string $k, string $d = '' ) => isset( $meta[ '_zc_field_' . $k ] ) ? (string) maybe_unserialize( $meta['_zc_field_' . $k ][0] ) : $d;
			$status  = (string) ( $meta['_zc_status'][0] ?? 'new' );
			$name    = $get( 'zc-consult-name', get_the_title( $post ) );
			$phone   = $get( 'zc-consult-phone' );
			$service = self::service_label( $get( 'zc-consult-service' ) );
			$prio    = $get( 'zc-consult-priority' );
			$details = $get( 'zc-consult-details', $get( 'message' ) );
			$page    = (string) ( $meta['_zc_page'][0] ?? '' );
			$view    = add_query_arg( 'zc_view', $post->ID, $base );
			echo '<tr>';
			echo '<td><input type="checkbox" name="zc_ids[]" value="' . (int) $post->ID . '"></td>';
			echo '<td><strong><a href="' . esc_url( $view ) . '">' . esc_html( $name ) . '</a></strong><br><code style="color:#64748b;">#' . (int) $post->ID . '</code></td>';
			echo '<td dir="ltr" style="text-align:right;">' . ( $phone ? '<a href="tel:' . esc_attr( $phone ) . '" style="text-decoration:none;font-weight:700;">' . esc_html( $phone ) . '</a>' : '—' ) . '</td>';
			echo '<td>' . esc_html( $service ) . '</td>';
			echo '<td>' . $this->priority_chip( $prio ) . '</td>';
			echo '<td style="max-width:260px;">' . esc_html( mb_strimwidth( $details, 0, 120, '…' ) ) . '</td>';
			echo '<td style="max-width:150px;">' . ( $page ? '<a href="' . esc_url( $page ) . '" target="_blank" rel="noopener">صفحه</a>' : '—' ) . '</td>';
			echo '<td>' . $this->status_badge( $status ) . '</td>';
			echo '<td style="white-space:nowrap;">' . esc_html( mysql2date( 'Y/m/d H:i', (string) $post->post_date ) ) . '</td>';
			echo '<td><div class="zc-inbox-actions">';
			echo '<a class="button-link" href="' . esc_url( $view ) . '"><i class="fa-regular fa-eye"></i> نمایش</a>';
			if ( $phone ) {
				echo '<a class="button-link" href="tel:' . esc_attr( $phone ) . '"><i class="fa-solid fa-phone"></i> تماس</a>';
			}
			if ( 'read' !== $status && 'new' !== $status ) {
				echo '<a class="button-link" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'zc_quick' => 'read', 'zc_id' => (int) $post->ID, 'zc_status' => $cur_st, 'paged' => (int) ( $_GET['paged'] ?? 1 ), 'zc_q' => $zq ), $base ), 'zc_inbox_quick' ) ) . '"><i class="fa-regular fa-envelope-open"></i> خوانده‌شد</a>';
			} elseif ( 'new' === $status ) {
				echo '<a class="button-link" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'zc_quick' => 'read', 'zc_id' => (int) $post->ID, 'zc_status' => $cur_st, 'paged' => (int) ( $_GET['paged'] ?? 1 ), 'zc_q' => $zq ), $base ), 'zc_inbox_quick' ) ) . '"><i class="fa-regular fa-check"></i> علامت‌خوانده</a>';
			}
			if ( 'replied' !== $status ) {
				echo '<a class="button-link" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'zc_quick' => 'replied', 'zc_id' => (int) $post->ID, 'zc_status' => $cur_st, 'paged' => (int) ( $_GET['paged'] ?? 1 ), 'zc_q' => $zq ), $base ), 'zc_inbox_quick' ) ) . '"><i class="fa-solid fa-reply"></i> پاسخ‌داده</a>';
			}
			echo '</div></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';

		echo '<p style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
		echo '<select name="zc_bulk"><option value="">عملیات گروهی…</option><option value="read">خوانده‌شده</option><option value="replied">پاسخ‌داده‌شده</option><option value="spam">هرزنامه</option><option value="delete">حذف دائمی</option></select>';
		echo '<button class="button button-primary" name="zc_bulk_run" value="1">اعمال</button>';
		echo '</p></form>';

		// Pagination (preserves status filter + search query).
		if ( $q->max_num_pages > 1 ) {
			$pages = paginate_links(
				array(
					'base'      => add_query_arg( 'paged', '%#%', $base ) . ( '' !== $zq ? '&zc_q=' . rawurlencode( $zq ) : '' ) . '&zc_status=' . rawurlencode( $cur_st ),
					'format'    => '',
					'current'   => max( 1, (int) ( $_GET['paged'] ?? 1 ) ),
					'total'     => (int) $q->max_num_pages,
					'type'      => 'array',
					'prev_text' => '→',
					'next_text' => '←',
				)
			);
			if ( is_array( $pages ) ) {
				echo '<nav class="zc-inbox-pagination">';
				foreach ( $pages as $p ) {
					echo wp_kses_post( $p );
				}
				echo '</nav>';
			}
		}
		echo '</div>';
	}

	/** Handle list+detail POST actions and quick (nonce'd GET) status links. */
	private function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$base = admin_url( 'admin.php?page=zc-inbox' );

		// Quick status links from the list rows (GET + nonce).
		if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_GET['zc_quick'] ) ) {
			check_admin_referer( 'zc_inbox_quick' );
			$quick = sanitize_key( (string) $_GET['zc_quick'] );
			$id    = absint( $_GET['zc_id'] ?? 0 );
			if ( $id && in_array( $quick, array( 'read', 'replied', 'spam' ), true ) ) {
				update_post_meta( $id, '_zc_status', $quick );
			}
			wp_safe_redirect( add_query_arg( 'zc_msg', 'updated', remove_query_arg( array( 'zc_quick', 'zc_id', '_wpnonce' ), wp_get_referer() ?: $base ) ) );
			exit;
		}

		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}
		check_admin_referer( 'zc_inbox_bulk' );

		if ( isset( $_POST['zc_status_set'] ) ) {
			$id     = absint( $_POST['zc_id'] ?? 0 );
			$status = sanitize_key( (string) ( $_POST['zc_status_set'] ?? '' ) );
			if ( $id && in_array( $status, array( 'new', 'read', 'replied', 'spam' ), true ) ) {
				update_post_meta( $id, '_zc_status', $status );
			}
			// Redirect: no re-submit on refresh + visible confirmation.
			wp_safe_redirect( add_query_arg( array( 'zc_view' => $id, 'zc_msg' => 'updated' ), $base ) );
			exit;
		}
		if ( isset( $_POST['zc_delete'] ) ) {
			$id = absint( $_POST['zc_id'] ?? 0 );
			if ( $id ) {
				wp_delete_post( $id, true );
				wp_safe_redirect( add_query_arg( 'zc_msg', 'deleted', $base ) );
				exit;
			}
		}
		if ( isset( $_POST['zc_bulk_run'] ) ) {
			$action = sanitize_key( (string) ( $_POST['zc_bulk'] ?? '' ) );
			$ids    = array_map( 'absint', (array) ( $_POST['zc_ids'] ?? array() ) );
			foreach ( $ids as $id ) {
				if ( ! $id ) {
					continue;
				}
				if ( 'delete' === $action ) {
					wp_delete_post( $id, true );
				} elseif ( in_array( $action, array( 'read', 'replied', 'spam' ), true ) ) {
					update_post_meta( $id, '_zc_status', $action );
				}
			}
			wp_safe_redirect( add_query_arg( 'zc_msg', 'bulk-done', $base ) );
			exit;
		}
	}

	private function render_detail( int $id ): void {
		$post = get_post( $id );
		if ( ! $post || self::SLUG !== $post->post_type ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>درخواست پیدا نشد.</p></div></div>';
			return;
		}
		$this->print_styles();
		$this->flash_notice();
		$meta   = get_post_meta( $id );
		$status = (string) ( $meta['_zc_status'][0] ?? 'new' );
		$base   = admin_url( 'admin.php?page=zc-inbox' );
		$phone  = (string) ( $meta['_zc_field_zc-consult-phone'][0] ?? '' );

		$labels = array(
			'zc-consult-name'     => 'نام و نام خانوادگی',
			'zc-consult-phone'    => 'شماره موبایل',
			'zc-consult-service'  => 'سرویس یا محصول مد نظر',
			'zc-consult-priority' => 'اولویت زمانی',
			'zc-consult-details'  => 'شرح پروژه یا درخواست',
			'name'                => 'نام',
			'phone'               => 'شماره تماس',
			'message'             => 'پیام',
		);

		echo '<div class="wrap zc-inbox-detail" dir="rtl"><h1>' . esc_html( get_the_title( $post ) ) . ' ' . $this->status_badge( $status ) . '</h1>';
		echo '<p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
		echo '<a class="button" href="' . esc_url( $base ) . '"><i class="fa-solid fa-arrow-right"></i> بازگشت به صندوق ورودی</a>';
		if ( $phone ) {
			echo '<a class="button button-primary" href="tel:' . esc_attr( $phone ) . '" dir="ltr"><i class="fa-solid fa-phone"></i> تماس با ' . esc_html( $phone ) . '</a>';
		}
		echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=zc-live-chat' ) ) . '"><i class="fa-regular fa-comments"></i> گفتگوهای زنده</a>';
		echo '</p>';

		echo '<div class="zc-inbox-grid">';

		// Main column: submitted fields + technical facts.
		echo '<div>';
		echo '<div class="zc-inbox-card" style="margin-bottom:16px;"><h2><i class="fa-regular fa-clipboard" style="color:#b45309;"></i> اطلاعات ثبت‌شده توسط بازدیدکننده</h2><dl>';
		foreach ( $meta as $key => $value ) {
			if ( ! str_starts_with( $key, '_zc_field_' ) ) {
				continue;
			}
			$fkey  = substr( $key, strlen( '_zc_field_' ) );
			$label = $labels[ $fkey ] ?? $fkey;
			$raw   = maybe_unserialize( $value[0] );
			if ( 'zc-consult-service' === $fkey ) {
				$raw = self::service_label( (string) $raw );
			}
			if ( 'zc-consult-priority' === $fkey ) {
				$raw = self::priority_label( (string) $raw );
			}
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( is_scalar( $raw ) ? (string) $raw : wp_json_encode( $raw, JSON_UNESCAPED_UNICODE ) ) . '</dd>';
		}
		echo '</dl></div>';

		echo '<div class="zc-inbox-card"><h2><i class="fa-solid fa-microchip" style="color:#0369a1;"></i> جزئیات فنی و زمان ثبت</h2><dl>';
		$facts = array(
			'شناسه درخواست'           => '#' . $id,
			'زمان ثبت'                => (string) ( $meta['_zc_submitted'][0] ?? $post->post_date ),
			'آدرس IP'                 => (string) ( $meta['_zc_ip'][0] ?? '' ),
			'مرورگر (User-Agent)'     => (string) ( $meta['_zc_ua'][0] ?? '' ),
			'صفحه‌ای که فرم در آن ارسال شد' => (string) ( $meta['_zc_page'][0] ?? '' ),
			'ارجاع‌دهنده (Referer)'   => (string) ( $meta['_zc_referrer'][0] ?? '' ),
		);
		foreach ( $facts as $label => $value ) {
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		echo '</dl></div>';
		echo '</div>';

		// Sidebar: status management + quick actions.
		echo '<aside>';
		echo '<div class="zc-inbox-card" style="margin-bottom:16px;"><h2><i class="fa-solid fa-toggle-on" style="color:#7c3aed;"></i> تغییر وضعیت</h2>';
		echo '<form method="post" action="' . esc_url( $base . '&zc_view=' . $id ) . '">';
		wp_nonce_field( 'zc_inbox_bulk' );
		echo '<input type="hidden" name="zc_id" value="' . (int) $id . '">';
		echo '<select name="zc_status_set" style="width:100%;margin-bottom:10px;">';
		foreach ( array( 'new' => 'جدید', 'read' => 'خوانده‌شده', 'replied' => 'پاسخ‌داده‌شده', 'spam' => 'هرزنامه' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
		echo '<button class="button button-primary" name="zc_status_go" value="1">ثبت وضعیت</button>';
		echo '<button class="button" name="zc_delete" value="1" onclick="return confirm(\'این درخواست برای همیشه حذف شود؟\')" style="color:#b91c1c;">حذف درخواست</button>';
		echo '</div></form></div>';

		echo '<div class="zc-inbox-card"><h2><i class="fa-solid fa-bolt" style="color:#b45309;"></i> اقدامات سریع</h2><div style="display:flex;flex-direction:column;gap:8px;">';
		if ( $phone ) {
			echo '<a class="button button-primary" style="width:100%;" href="tel:' . esc_attr( $phone ) . '" dir="ltr"><i class="fa-solid fa-phone"></i> تماس تلفنی</a>';
			echo '<a class="button" style="width:100%;" href="sms:' . esc_attr( $phone ) . '" dir="ltr"><i class="fa-solid fa-comment-sms"></i> پیامک</a>';
		}
		$page = (string) ( $meta['_zc_page'][0] ?? '' );
		if ( $page ) {
			echo '<a class="button" style="width:100%;" href="' . esc_url( $page ) . '" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> صفحهٔ ارسال فرم</a>';
		}
		echo '<a class="button" style="width:100%;" href="' . esc_url( add_query_arg( 'zc_status', 'new', $base ) ) . '"><i class="fa-solid fa-filter"></i> نمایش درخواست‌های جدید</a>';
		echo '</div></div>';
		echo '</aside>';

		echo '</div></div>';
	}
}
