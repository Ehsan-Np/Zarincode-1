<?php
/**
 * Consultation inbox: every «درخواست مشاوره رایگان» submission is stored in wp-admin
 * as a private CPT with an exact field-by-field record (plus IP, UA, page, time)
 * and presented through a dedicated inbox screen (list, filters, detail, statuses).
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
				'no_found_rows'  => true,
			)
		);
		return (int) $q->post_count;
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
		update_post_meta( $id, '_zc_page', esc_url_raw( (string) ( $_POST['zc_page'] ?? '' ) ?: (wp_get_referer() ?: '' ) ) );
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

	private function rows(): array {
		$status = sanitize_key( (string) ( $_GET['zc_status'] ?? 'all' ) );
		$paged  = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$args   = array(
			'post_type'      => self::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( in_array( $status, array( 'new', 'read', 'replied', 'spam' ), true ) ) {
			$args['meta_query'] = array( array( 'key' => '_zc_status', 'value' => $status ) );
		}
		$q = new \WP_Query( $args );
		return array( 'posts' => $q->posts, 'pages' => (int) $q->max_num_pages, 'paged' => $paged, 'status' => $status );
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

	public function render_list(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['zc_view'] ) ) {
			$this->render_detail( absint( $_GET['zc_view'] ) );
			return;
		}
		$this->handle_actions();

		$rows   = $this->rows();
		$tabs   = array(
			'all'   => 'همه',
			'new'   => 'جدید',
			'read'  => 'خوانده‌شده',
			'replied' => 'پاسخ‌داده‌شده',
			'spam'  => 'هرزنامه',
		);
		$base   = admin_url( 'admin.php?page=zc-inbox' );
		echo '<div class="wrap" dir="rtl"><h1>صندوق ورودی درخواست‌های مشاوره <span style="font-size:.75em;font-weight:400;color:#64748b;">— همهٔ فرم‌های «درخواست مشاوره رایگان» با جزئیات کامل</span></h1>';
		echo '<p class="nav-tab-wrapper">';
		foreach ( $tabs as $key => $label ) {
			$class = ( $rows['status'] === $key ) ? ' nav-tab nav-tab-active' : ' nav-tab';
			echo '<a class="' . esc_attr( trim( $class ) ) . '" href="' . esc_url( add_query_arg( 'zc_status', $key, $base ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</p>';

		echo '<form method="post" action="' . esc_url( $base ) . '">';
		wp_nonce_field( 'zc_inbox_bulk' );
		echo '<table class="widefat striped" style="background:#fff;"><thead><tr>';
		echo '<td style="width:26px;"><input type="checkbox" onclick="document.querySelectorAll(\'input[name^=zc_ids[]]\').forEach(c=>c.checked=this.checked)"></td>';
		echo '<th>درخواست‌کننده</th><th>شماره موبایل</th><th>سرویس</th><th>اولویت</th><th>شرح درخواست</th><th>صفحه</th><th>وضعیت</th><th>تاریخ ثبت</th><th>عملیات</th>';
		echo '</tr></thead><tbody>';
		if ( ! $rows['posts'] ) {
			echo '<tr><td colspan="9" style="padding:2rem;text-align:center;color:#64748b;">هنوز درخواستی ثبت نشده است.</td></tr>';
		}
		foreach ( $rows['posts'] as $post ) {
			$meta    = get_post_meta( $post->ID );
			$get     = static fn( string $k, string $d = '' ) => isset( $meta[ '_zc_field_' . $k ] ) ? (string) maybe_unserialize( $meta['_zc_field_' . $k ][0] ) : $d;
			$status  = (string) ( $meta['_zc_status'][0] ?? 'new' );
			$name    = $get( 'zc-consult-name', get_the_title( $post ) );
			$phone   = $get( 'zc-consult-phone' );
			$service = self::service_label( $get( 'zc-consult-service' ) );
			$prio    = self::priority_label( $get( 'zc-consult-priority' ) );
			$details = $get( 'zc-consult-details', $get( 'message' ) );
			$page    = (string) ( $meta['_zc_page'][0] ?? '' );
			echo '<tr>';
			echo '<td><input type="checkbox" name="zc_ids[]" value="' . (int) $post->ID . '"></td>';
			echo '<td><strong><a href="' . esc_url( add_query_arg( 'zc_view', $post->ID, $base ) ) . '">' . esc_html( $name ) . '</a></strong><br><code style="color:#64748b;">#' . (int) $post->ID . '</code></td>';
			echo '<td dir="ltr" style="text-align:right;">' . esc_html( $phone ) . '</td>';
			echo '<td>' . esc_html( $service ) . '</td>';
			echo '<td>' . esc_html( $prio ) . '</td>';
			echo '<td style="max-width:260px;">' . esc_html( mb_strimwidth( $details, 0, 120, '…' ) ) . '</td>';
			echo '<td style="max-width:150px;">' . ( $page ? '<a href="' . esc_url( $page ) . '" target="_blank" rel="noopener">صفحه</a>' : '—' ) . '</td>';
			echo '<td>' . $this->status_badge( $status ) . '</td>';
			echo '<td>' . esc_html( mysql2date( 'Y/m/d H:i', (string) $post->post_date ) ) . '</td>';
			echo '<td><a href="' . esc_url( add_query_arg( 'zc_view', $post->ID, $base ) ) . '">نمایش</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<p style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
		echo '<select name="zc_bulk"><option value="">عملیات گروهی…</option><option value="read">خوانده‌شده</option><option value="replied">پاسخ‌داده‌شده</option><option value="spam">هرزنامه</option><option value="delete">حذف دائمی</option></select>';
		echo '<button class="button button-primary" name="zc_bulk_run" value="1">اعمال</button>';
		echo '</p></form>';

		if ( $rows['pages'] > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			for ( $i = 1; $i <= $rows['pages']; $i++ ) {
				$url = add_query_arg( array( 'paged' => $i, 'zc_status' => $rows['status'] ), $base );
				echo ( $i === $rows['paged'] ) ? '<span class="paging-input current">' . $i . '</span> ' : '<a href="' . esc_url( $url ) . '">' . $i . '</a> ';
			}
			echo '</div></div>';
		}
		echo '<style>.zc-inbox-detail dl{display:grid;grid-template-columns:200px 1fr;gap:6px 14px;margin:0}.zc-inbox-detail dt{font-weight:700;color:#334155;background:#f8fafc;padding:8px 10px;border-radius:8px}.zc-inbox-detail dd{margin:0;padding:8px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;white-space:pre-wrap;word-break:break-word}</style></div>';
	}

	private function handle_actions(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_inbox_bulk' );

		if ( isset( $_POST['zc_status_set'] ) ) {
			$id     = absint( $_POST['zc_id'] ?? 0 );
			$status = sanitize_key( (string) ( $_POST['zc_status_set'] ?? '' ) );
			if ( $id && in_array( $status, array( 'new', 'read', 'replied', 'spam' ), true ) ) {
				update_post_meta( $id, '_zc_status', $status );
			}
		}
		if ( isset( $_POST['zc_delete'] ) ) {
			$id = absint( $_POST['zc_id'] ?? 0 );
			if ( $id ) {
				wp_delete_post( $id, true );
				wp_safe_redirect( admin_url( 'admin.php?page=zc-inbox&zc_msg=deleted' ) );
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
			wp_safe_redirect( admin_url( 'admin.php?page=zc-inbox&zc_msg=bulk-done' ) );
			exit;
		}
	}

	private function render_detail( int $id ): void {
		$post = get_post( $id );
		if ( ! $post || self::SLUG !== $post->post_type ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>درخواست پیدا نشد.</p></div></div>';
			return;
		}
		$this->handle_actions();
		$meta   = get_post_meta( $id );
		$status = (string) ( $meta['_zc_status'][0] ?? 'new' );
		$base   = admin_url( 'admin.php?page=zc-inbox' );

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
		echo '<p><a href="' . esc_url( $base ) . '">← بازگشت به صندوق ورودی</a></p>';

		echo '<div class="notice notice-info"><p><strong>تماس سریع:</strong> <a href="tel:' . esc_attr( (string) ( $meta['_zc_field_zc-consult-phone'][0] ?? '' ) ) . '" dir="ltr">' . esc_html( (string) ( $meta['_zc_field_zc-consult-phone'][0] ?? '' ) ) . '</a> — <a href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '">ایمیل پشتیبانی</a></p></div>';

		echo '<h2>اطلاعات ثبت‌شده</h2><dl>';
		foreach ( $meta as $key => $value ) {
			if ( ! str_starts_with( $key, '_zc_field_' ) ) {
				continue;
			}
			$fkey   = substr( $key, strlen( '_zc_field_' ) );
			$label  = $labels[ $fkey ] ?? $fkey;
			$raw    = maybe_unserialize( $value[0] );
			if ( 'zc-consult-service' === $fkey ) {
				$raw = self::service_label( (string) $raw );
			}
			if ( 'zc-consult-priority' === $fkey ) {
				$raw = self::priority_label( (string) $raw );
			}
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( is_scalar( $raw ) ? (string) $raw : wp_json_encode( $raw, JSON_UNESCAPED_UNICODE ) ) . '</dd>';
		}
		echo '</dl>';

		echo '<h2 style="margin-top:1.6rem;">جزئیات فنی و زمان ثبت</h2><dl>';
		$facts = array(
			'شناسه درخواست' => '#' . $id,
			'زمان ثبت'      => (string) ( $meta['_zc_submitted'][0] ?? $post->post_date ),
			'آدرس IP'        => (string) ( $meta['_zc_ip'][0] ?? '' ),
			'مرورگر (User-Agent)' => (string) ( $meta['_zc_ua'][0] ?? '' ),
			'صفحه‌ای که فرم در آن ارسال شد' => (string) ( $meta['_zc_page'][0] ?? '' ),
			'ارجاع‌دهنده (Referer)' => (string) ( $meta['_zc_referrer'][0] ?? '' ),
		);
		foreach ( $facts as $label => $value ) {
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		echo '</dl>';

		echo '<h2 style="margin-top:1.6rem;">تغییر وضعیت</h2><form method="post" action="' . esc_url( $base . '&zc_view=' . $id ) . '">';
		wp_nonce_field( 'zc_inbox_bulk' );
		echo '<input type="hidden" name="zc_id" value="' . (int) $id . '">';
		echo '<select name="zc_status_set">';
		foreach ( array( 'new' => 'جدید', 'read' => 'خوانده‌شده', 'replied' => 'پاسخ‌داده‌شده', 'spam' => 'هرزنامه' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> <button class="button button-primary" name="zc_status_go" value="1">ثبت وضعیت</button> ';
		echo '<button class="button" name="zc_delete" value="1" onclick="return confirm(\'این درخواست برای همیشه حذف شود؟\')">حذف درخواست</button>';
		echo '</form></div>';
	}
}
