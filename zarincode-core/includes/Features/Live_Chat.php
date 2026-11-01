<?php
/**
 * Live chat attached to the floating dock (zc-floating-socials).
 *
 * - Pre-chat gate: name + mobile (required), website (optional).
 * - Visitor may send text, photo, video and voice notes; admin replies from wp-admin.
 * - Threads are keyed by phone, falling back to IP (full history per visitor).
 * - Admin presence (online/offline) is shown next to the launcher icon and the
 *   visitor is actively notified (ribbon, toast, in-panel system line) when online.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Features;

defined( 'ABSPATH' ) || exit;

final class Live_Chat {
	public const CONTACT = 'zc_chat_contact';
	public const MESSAGE = 'zc_chat_message';
	private const NONCE  = 'zc_chat';
	private const ADMIN_NONCE = 'zc_chat_admin';
	private const ONLINE_WINDOW = 90;   // seconds without admin activity ⇒ offline
	private static ?self $instance = null;

	public static function boot(): void {
		self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_cpts' ) );
		add_action( 'init', array( $this, 'bump_presence' ), 20 );
		add_action( 'admin_init', array( $this, 'bump_presence' ) );

		add_action( 'wp_ajax_zc_chat_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_nopriv_zc_chat_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_zc_chat_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_nopriv_zc_chat_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_zc_chat_poll', array( $this, 'ajax_poll' ) );
		add_action( 'wp_ajax_nopriv_zc_chat_poll', array( $this, 'ajax_poll' ) );
		add_action( 'wp_ajax_zc_chat_send', array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_nopriv_zc_chat_send', array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_zc_chat_admin_read', array( $this, 'ajax_admin_read' ) );
		add_action( 'wp_ajax_zc_chat_admin_poll', array( $this, 'ajax_admin_poll' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 8 );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 30 );
	}

	/* ------------------------------------------------------------- structure */

	public function register_cpts(): void {
		$hidden = array(
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'rewrite'             => false,
			'query_var'           => false,
		);
		register_post_type( self::CONTACT, array_merge( $hidden, array( 'label' => 'مخاطبان گفتگوی زنده', 'supports' => array( 'title' ) ) ) );
		register_post_type( self::MESSAGE, array_merge( $hidden, array( 'label' => 'پیام‌های گفتگوی زنده', 'supports' => array( 'title', 'editor' ) ) ) );
	}

	/* -------------------------------------------------------------- presence */

	public function bump_presence(): void {
		if ( is_admin() && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( is_admin() ) {
			$last = (int) get_option( 'zc_chat_admin_seen', 0 );
			if ( time() - $last > 30 ) {
				update_option( 'zc_chat_admin_seen', time(), false );
			}
			return;
		}
		// Logged-in manager browsing the front-end also counts as online.
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			$last = (int) get_option( 'zc_chat_admin_seen', 0 );
			if ( time() - $last > 30 ) {
				update_option( 'zc_chat_admin_seen', time(), false );
			}
		}
	}

	public static function admin_online(): bool {
		return ( time() - (int) get_option( 'zc_chat_admin_seen', 0 ) ) < self::ONLINE_WINDOW;
	}

	/* -------------------------------------------------------------- helpers */

	private static function token( int $contact, string $phone ): string {
		return substr( md5( wp_hash( 'zc-chat|' . $contact . '|' . $phone, 'auth' ) ), 0, 40 );
	}

	private function contact_from_token(): int {
		$contact = absint( $_POST['contact'] ?? 0 );
		$token   = sanitize_text_field( (string) ( $_POST['token'] ?? '' ) );
		if ( ! $contact || ! $token ) {
			return 0;
		}
		$phone = (string) get_post_meta( $contact, '_zc_phone', true );
		return hash_equals( self::token( $contact, $phone ), $token ) ? $contact : 0;
	}

	private static function message_row( int $id ): ?array {
		$post = get_post( $id );
		if ( ! $post || self::MESSAGE !== $post->post_type ) {
			return null;
		}
		return array(
			'id'   => $id,
			'dir'  => (string) get_post_meta( $id, '_zc_dir', true ),
			'type' => (string) ( get_post_meta( $id, '_zc_type', true ) ?: 'text' ),
			'text' => $post->post_content,
			'file' => (string) get_post_meta( $id, '_zc_file', true ),
			'at'   => mysql2date( 'H:i', $post->post_date ),
			'date' => mysql2date( 'Y/m/d H:i', $post->post_date ),
		);
	}

	private static function unread_admin_count(): int {
		$q = new \WP_Query(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_zc_dir', 'value' => 'in' ),
					array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ),
				),
				'no_found_rows'  => true,
			)
		);
		return (int) $q->post_count;
	}

	/** Chat settings (stored in the single zarincode_opt array). */
	public static function settings(): array {
		// Read the whole option once so defaults merge cleanly.
		$all = get_option( 'zarincode_opt', array() );
		$all = is_array( $all ) ? $all : array();
		$d   = array(
			'chat_title'        => 'گفتگوی آنلاین زرین کد',
			'chat_greeting'     => 'برای شروع گفتگو، نام و شماره موبایل خود را وارد کنید (وبسایت اختیاری است).',
			'chat_offline_msg'  => 'پشتیبانی آفلاین است؛ پیام بگذارید تا در اولین فرصت پاسخ دهیم.',
			'chat_placeholder'  => 'پیام خود را بنویسید…',
			'chat_field_name'    => 1,
			'chat_field_phone'   => 1,
			'chat_field_website' => 0,
			'chat_allow_photo'   => 1,
			'chat_allow_video'   => 1,
			'chat_allow_voice'   => 1,
			'chat_max_mb'        => 24,
			'chat_accent'        => '#fdd05b',
		);
		$out = array();
		foreach ( $d as $k => $v ) {
			$stored          = $all[ $k ] ?? null;
			$out[ $k ]       = null === $stored ? $v : $stored;
		}
		$out['chat_max_mb']   = max( 1, min( 64, (int) $out['chat_max_mb'] ) );
		$out['chat_accent']   = (string) ( $out['chat_accent'] ?: $d['chat_accent'] );
		$out['chat_field_name']    = $out['chat_field_name'] ? 1 : 0;
		$out['chat_field_phone']   = $out['chat_field_phone'] ? 1 : 0;
		$out['chat_field_website'] = $out['chat_field_website'] ? 1 : 0;
		$out['chat_allow_photo']   = $out['chat_allow_photo'] ? 1 : 0;
		$out['chat_allow_video']   = $out['chat_allow_video'] ? 1 : 0;
		$out['chat_allow_voice']   = $out['chat_allow_voice'] ? 1 : 0;
		return $out;
	}

	private static function allowed_mimes(): array {
		$st  = self::settings();
		$out = array();
		if ( $st['chat_allow_photo'] ) {
			$out += array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png'          => 'image/png',
				'gif'          => 'image/gif',
				'webp'         => 'image/webp',
			);
		}
		if ( $st['chat_allow_video'] ) {
			$out += array(
				'mp4'    => 'video/mp4',
				'm4v'    => 'video/mp4',
				'mov|qt' => 'video/quicktime',
				'webm'   => 'video/webm',
			);
		}
		if ( $st['chat_allow_voice'] ) {
			$out += array(
				'mp3'     => 'audio/mpeg',
				'm4a'     => 'audio/m4a',
				'ogg|oga' => 'audio/ogg',
				'wav'     => 'audio/wav',
				'weba'    => 'audio/webm',
			);
		}
		return $out;
	}

	private function store_message( int $contact, string $dir, string $type, string $text, string $file = '' ): int {
		return (int) wp_insert_post(
			array(
				'post_type'    => self::MESSAGE,
				'post_status'  => 'publish',
				'post_title'   => mb_substr( '' !== $text ? $text : basename( (string) wp_parse_url( $file, PHP_URL_PATH ) ), 0, 60 ),
				'post_content' => $text,
				'meta_input'   => array(
					'_zc_contact' => $contact,
					'_zc_dir'     => $dir,
					'_zc_type'    => $type,
					'_zc_file'    => $file,
					'_zc_read'    => 'out' === $dir ? '1' : '0',
				),
			)
		);
	}

	/* ------------------------------------------------------------ front ajax */

	public function ajax_status(): void {
		$contact = $this->contact_from_token();
		$st = self::settings();
		wp_send_json_success(
			array(
				'adminOnline' => self::admin_online(),
				'unread'      => $contact ? $this->unread_for( $contact ) : 0,
				'phone'       => function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'contact_phone', '09024561001' ) : '09024561001',
				'settings'    => $st,
			)
		);
	}

	private function unread_for( int $contact ): int {
		$q = new \WP_Query(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_zc_contact', 'value' => $contact ),
					array( 'key' => '_zc_dir', 'value' => 'out' ),
				),
				'no_found_rows'  => true,
			)
		);
		return (int) $q->post_count;
	}

	public function ajax_start(): void {
		check_ajax_referer( self::NONCE, 'zc_chat_nonce', false ) || wp_send_json_error( array( 'message' => 'نشانی امنیتی نامعتبر است؛ صفحه را تازه‌سازی کنید.' ), 403 );

		$name    = sanitize_text_field( (string) ( $_POST['name'] ?? '' ) );
		$phone   = Consultation_Inbox::phone( (string) ( $_POST['phone'] ?? '' ) );
		$website = sanitize_text_field( (string) ( $_POST['website'] ?? '' ) );
		$ip      = Consultation_Inbox::ip();

		$st = self::settings();
		if ( $st['chat_field_name'] && mb_strlen( $name ) < 2 ) {
			wp_send_json_error( array( 'message' => 'برای شروع گفتگو، نام شما الزامی است.' ), 422 );
		}
		if ( ! $st['chat_field_name'] && '' === $name ) {
			$name = 'مهمان';
		}
		if ( $st['chat_field_phone'] && '' === $phone ) {
			wp_send_json_error( array( 'message' => 'شماره موبایل معتبر الزامی است (مثال: ۰۹۱۲۳۴۵۶۷۸۹).' ), 422 );
		}

		$contact = 0;

		// 1) same phone ⇒ same history
		$by_phone = get_posts(
			array(
				'post_type'      => self::CONTACT,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_zc_phone', 'value' => $phone ) ),
			)
		);
		if ( $by_phone ) {
			$contact = (int) $by_phone[0];
		}

		// 2) else same IP in the last 30 days ⇒ keep the history under this visitor
		if ( ! $contact && $ip ) {
			$by_ip = get_posts(
				array(
					'post_type'         => self::CONTACT,
					'posts_per_page'    => 1,
					'fields'            => 'ids',
					'date_query'        => array( array( 'after' => '30 days ago' ) ),
					'meta_query'        => array( array( 'key' => '_zc_ip', 'value' => $ip ) ),
					'orderby'           => 'date',
					'order'             => 'DESC',
				)
			);
			if ( $by_ip ) {
				$contact = (int) $by_ip[0];
			}
		}

		if ( $contact ) {
			wp_update_post( array( 'ID' => $contact, 'post_title' => $name ) );
			update_post_meta( $contact, '_zc_phone', $phone );
			if ( '' !== $website ) {
				update_post_meta( $contact, '_zc_website', $website );
			}
			if ( $ip ) {
				update_post_meta( $contact, '_zc_ip', $ip );
			}
		} else {
			$contact = (int) wp_insert_post(
				array(
					'post_type'   => self::CONTACT,
					'post_status' => 'publish',
					'post_title'  => $name,
					'meta_input'  => array(
						'_zc_phone'   => $phone,
						'_zc_website' => $website,
						'_zc_ip'      => $ip,
					),
				)
			);
		}
		if ( ! $contact ) {
			wp_send_json_error( array( 'message' => 'خطا در ایجاد گفتگو؛ دوباره تلاش کنید.' ), 500 );
		}
		update_post_meta( $contact, '_zc_last_seen', time() );

		$history = array();
		$msgs    = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $contact ) ),
			)
		);
		foreach ( $msgs as $m ) {
			$history[] = self::message_row( (int) $m->ID );
		}

		wp_send_json_success(
			array(
				'contact'     => $contact,
				'token'       => self::token( $contact, $phone ),
				'name'        => $name,
				'history'     => $history,
				'adminOnline' => self::admin_online(),
				'settings'    => $st,
			)
		);
	}

	public function ajax_poll(): void {
		$contact = $this->contact_from_token();
		if ( ! $contact ) {
			wp_send_json_error( array( 'message' => 'گفتگو معتبر نیست.' ), 403 );
		}
		update_post_meta( $contact, '_zc_last_seen', time() );
		$since = absint( $_POST['since'] ?? 0 );
		$args  = array(
			'post_type'      => self::MESSAGE,
			'post_status'    => 'any',
			'posts_per_page' => 60,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'meta_query'     => array(
				array( 'key' => '_zc_contact', 'value' => $contact ),
			),
		);
		$rows = array();
		foreach ( get_posts( $args ) as $m ) {
			if ( $since && (int) $m->ID <= $since ) {
				continue;
			}
			$row = self::message_row( (int) $m->ID );
			if ( $row ) {
				$rows[] = $row;
			}
		}
		$rows = array_reverse( $rows );
		wp_send_json_success(
			array(
				'messages'    => $rows,
				'adminOnline' => self::admin_online(),
			)
		);
	}

	public function ajax_send(): void {
		$contact = $this->contact_from_token();
		if ( ! $contact ) {
			wp_send_json_error( array( 'message' => 'گفتگو معتبر نیست؛ دوباره از ابتدا شروع کنید.' ), 403 );
		}
		check_ajax_referer( self::NONCE, 'zc_chat_nonce', false ) || wp_send_json_error( array( 'message' => 'نشانی امنیتی نامعتبر است.' ), 403 );
		update_post_meta( $contact, '_zc_last_seen', time() );

		$type = sanitize_key( (string) ( $_POST['type'] ?? 'text' ) );
		$text = sanitize_textarea_field( (string) ( $_POST['text'] ?? '' ) );
		$file = '';

		if ( 'text' === $type ) {
			if ( '' === trim( $text ) ) {
				wp_send_json_error( array( 'message' => 'متن پیام خالی است.' ), 422 );
			}
		} else {
			$file = $this->receive_upload();
			if ( is_wp_error( $file ) ) {
				wp_send_json_error( array( 'message' => $file->get_error_message() ), 422 );
			}
		}

		$id = $this->store_message( $contact, 'in', in_array( $type, array( 'text', 'photo', 'voice', 'video' ), true ) ? $type : 'text', $text, is_string( $file ) ? $file : '' );
		wp_send_json_success( array( 'message' => self::message_row( $id ) ) );
	}

	/** Accept photo/video/voice uploads from the visitor chat. */
	private function receive_upload(): string|\WP_Error {
		if ( empty( $_FILES['hc_file'] ) || ! is_array( $_FILES['hc_file'] ) ) {
			return new \WP_Error( 'no_file', 'فایلی ارسال نشده است.' );
		}
		$file = $_FILES['hc_file'];
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'upload', 'آپلود فایل ناموفق بود؛ حجم را کم و دوباره تلاش کنید.' );
		}
		$max = (int) self::settings()['chat_max_mb'];
		if ( (int) $file['size'] > $max * MB_IN_BYTES ) {
			return new \WP_Error( 'size', sprintf( 'حجم فایل باید کمتر از %d مگابایت باشد.', $max ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$overrides = array(
			'test_form' => false,
			'test_size' => false,
			'mimes'     => self::allowed_mimes(),
		);
		$result = wp_handle_upload( $file, $overrides );
		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'upload', 'فرمت فایل مجاز نیست (تصویر، ویدیو یا صدا). ' . $result['error'] );
		}
		return (string) $result['url'];
	}

	/* ------------------------------------------------------------- front assets */

	public function assets(): void {
		if ( is_admin() || ! function_exists( 'zarincode_get_option' ) || ! zarincode_get_option( 'enable_live_chat', true ) ) {
			return;
		}
		$plugin_base = plugin_dir_url( dirname( __DIR__, 2 ) . '/zarincode-core.php' );
		wp_register_style( 'zc-live-chat', $plugin_base . 'assets/css/live-chat.css', array(), '1.0.0' );
		wp_enqueue_style( 'zc-live-chat' );
		wp_register_script( 'zc-live-chat', $plugin_base . 'assets/js/live-chat.js', array(), '1.0.0', true );
		wp_enqueue_script( 'zc-live-chat' );
		$st = self::settings();
		wp_add_inline_style( 'zc-live-chat', ':root{--zc-chat-accent:' . esc_attr( $st['chat_accent'] ) . ';}' );
		wp_localize_script(
			'zc-live-chat',
			'ZarincodeChat',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE ),
				'pollMs'    => 5000,
				'statusMs'  => 20000,
				'phone'     => (string) ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'contact_phone', '09024561001' ) : '09024561001' ),
				'maxMb'     => (int) $st['chat_max_mb'],
				'strings'   => array(
					'title'        => (string) $st['chat_title'],
					'online'       => 'پشتیبانی آنلاین است',
					'offline'      => (string) $st['chat_offline_msg'],
					'welcome'      => (string) $st['chat_greeting'],
					'placeholder'  => (string) $st['chat_placeholder'],
					'name'         => 'نام و نام خانوادگی' . ( $st['chat_field_name'] ? ' *' : '' ),
					'phone'        => 'شماره موبایل' . ( $st['chat_field_phone'] ? ' *' : '' ),
					'website'      => 'وبسایت (اختیاری)',
					'start'        => 'شروع گفتگو',
					'sending'      => 'در حال ارسال…',
					'onlineToast'  => 'کارشناس زرین کد اکنون آنلاین است — همین حالا گفتگو را شروع کنید.',
					'cameOnline'   => 'کارشناس آنلاین شد.',
					'error'        => 'خطا در ارتباط؛ دوباره تلاش کنید.',
					'disabled'     => 'چت زنده در حال حاضر غیرفعال است.',
					'fileLimit'    => sprintf( 'حداکثر حجم فایل: %d مگابایت', (int) $st['chat_max_mb'] ),
					// field/file toggles consumed by live-chat.js
					'fields'       => array(
						'name'    => (bool) $st['chat_field_name'],
						'phone'   => (bool) $st['chat_field_phone'],
						'website' => (bool) $st['chat_field_website'],
					),
					'allows'       => array(
						'photo' => (bool) $st['chat_allow_photo'],
						'video' => (bool) $st['chat_allow_video'],
						'voice' => (bool) $st['chat_allow_voice'],
					),
				),
			)
		);
	}

	/** Launcher markup for the floating dock (theme prints it next to zc-floating-socials). */
	public static function launcher(): string {
		if ( ! class_exists( __CLASS__ ) ) {
			return '';
		}
		return '<button type="button" class="zc-chat-launcher" aria-haspopup="dialog" aria-label="گفتگوی آنلاین زرین کد">'
			. '<span class="zc-chat-launcher-dot" aria-hidden="true"></span>'
			. '<i class="fa-regular fa-comment-dots" aria-hidden="true"></i>'
			. '<span class="zc-chat-launcher-ribbon" hidden></span>'
			. '</button>';
	}

	/* ----------------------------------------------------------------- admin */

	public function admin_menu(): void {
		// WP 7+: menu_page_exists() was removed; menu_exists() checks top-level items.
		if ( function_exists( '\\menu_exists' ) && ! \menu_exists( 'zc-inbox' ) ) {
			return;
		}
		$badge = self::unread_admin_count();
		add_submenu_page(
			'zc-inbox',
			'گفتگوهای زنده',
			'گفتگوهای زنده' . ( $badge ? ' ' . $badge : '' ),
			'manage_options',
			'zc-live-chat',
			array( $this, 'render_contacts' )
		);
		add_submenu_page(
			'zc-inbox',
			'تنظیمات گفتگوی زنده',
			'تنظیمات گفتگو',
			'manage_options',
			'zc-live-chat-settings',
			array( $this, 'render_settings' )
		);
	}

	public function render_contacts(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$base = admin_url( 'admin.php?page=zc-live-chat' );
		if ( isset( $_GET['zc_thread'] ) ) {
			$this->render_thread( absint( $_GET['zc_thread'] ), $base );
			return;
		}
		$this->handle_admin_actions( $base );

		$online = self::admin_online();
		echo '<div class="wrap" dir="rtl"><h1>گفتگوهای زنده <span style="font-size:.72em;font-weight:400;color:#64748b;">— تاریخچهٔ کامل بر اساس شماره موبایل یا IP هر بازدیدکننده</span></h1>';
		self::admin_nav( 'list' );
		echo '<div class="notice notice-info"><p><strong>وضعیت شما:</strong> ' . ( $online ? '🟢 آنلاین — بازدیدکننده‌ها نشان سبز و اطلاع‌رسانی «پشتیبانی آنلاین است» را می‌بینند.' : '🔴 آفلاین — نشان قرمز نمایش داده می‌شود؛ پیام‌های جدید همچنان ثبت و اینجا خوانده می‌شوند.' ) . '</p></div>';

		// Recent conversations = recent messages (one query), then their contacts.
		$recent = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 60,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$order    = array();
		$last_msg = array();
		foreach ( $recent as $mid ) {
			$cid = (int) get_post_meta( $mid, '_zc_contact', true );
			if ( $cid && ! isset( $last_msg[ $cid ] ) ) {
				$last_msg[ $cid ] = $mid;
				$order[]           = $cid;
			}
		}
		$ids = array_slice( $order, 0, 30 );
		// Contacts that never received a message still show up.
		$fresh = get_posts(
			array(
				'post_type'      => self::CONTACT,
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		foreach ( $fresh as $cid ) {
			if ( ! in_array( $cid, $order, true ) ) {
				$ids[] = $cid;
			}
		}

		echo '<table class="widefat striped" style="background:#fff;"><thead><tr><th>مخاطب</th><th>شماره موبایل</th><th>وبسایت</th><th>IP</th><th>وضعیت بازدیدکننده</th><th>آخرین پیام</th><th>خوانده‌نشده</th><th>آخرین فعالیت</th></tr></thead><tbody>';
		if ( ! $ids ) {
			echo '<tr><td colspan="8" style="padding:2rem;text-align:center;color:#64748b;">هنوز گفتگویی ثبت نشده است. با دکمهٔ شناور در سایت، یک گفتگو را امتحان کنید.</td></tr>';
		}
		foreach ( $ids as $cid ) {
			$c       = get_post( $cid );
			if ( ! $c ) {
				continue;
			}
			$phone   = (string) get_post_meta( $cid, '_zc_phone', true );
			$site    = (string) get_post_meta( $cid, '_zc_website', true );
			$ip      = (string) get_post_meta( $cid, '_zc_ip', true );
			$seen    = (int) get_post_meta( $cid, '_zc_last_seen', true );
			$is_on   = $seen && ( time() - $seen ) < 180;
			$last_id = $last_msg[ $cid ] ?? 0;
			$preview = $last_id ? mb_strimwidth( (string) get_post_field( 'post_content', $last_id ), 0, 70, '…' ) : '—';
			$unq     = new \WP_Query(
				array(
					'post_type'      => self::MESSAGE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array( 'key' => '_zc_contact', 'value' => $cid ),
						array( 'key' => '_zc_dir', 'value' => 'in' ),
						array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ),
					),
					'no_found_rows'  => true,
				)
			);
			$unread = (int) $unq->post_count;
			echo '<tr>';
			echo '<td><strong><a href="' . esc_url( add_query_arg( 'zc_thread', $cid, $base ) ) . '">' . esc_html( $c->post_title ) . '</a></strong></td>';
			echo '<td dir="ltr" style="text-align:right;">' . esc_html( $phone ) . '</td>';
			echo '<td>' . ( $site ? '<a href="' . esc_url( $site ) . '" target="_blank" rel="noopener">' . esc_html( mb_strimwidth( $site, 0, 40, '…' ) ) . '</a>' : '—' ) . '</td>';
			echo '<td dir="ltr" style="text-align:right;color:#64748b;">' . esc_html( $ip ) . '</td>';
			echo '<td>' . ( $is_on ? '🟢 آنلاین' : '⚪ آفلاین' ) . '</td>';
			echo '<td style="max-width:240px;">' . esc_html( $preview ) . '</td>';
			echo '<td>' . ( $unread ? '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:999px;font-weight:700;font-size:11px;">' . $unread . ' خوانده‌نشده</span>' : '—' ) . '</td>';
			echo '<td>' . ( $seen ? esc_html( human_time_diff( $seen ) . ' پیش' ) : '—' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	private function render_thread( int $cid, string $base ): void {
		$contact = get_post( $cid );
		if ( ! $contact || self::CONTACT !== $contact->post_type ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>گفتگو پیدا نشد.</p></div></div>';
			return;
		}
		$this->handle_admin_actions( $base );

		// Opening the thread marks visitor messages as read.
		$in_ids = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_zc_contact', 'value' => $cid ),
					array( 'key' => '_zc_dir', 'value' => 'in' ),
					array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ),
				),
			)
		);
		foreach ( $in_ids as $mid ) {
			update_post_meta( $mid, '_zc_read', '1' );
		}

		$phone = (string) get_post_meta( $cid, '_zc_phone', true );
		$ip    = (string) get_post_meta( $cid, '_zc_ip', true );
		$seen  = (int) get_post_meta( $cid, '_zc_last_seen', true );
		$msgs  = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 500,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $cid ) ),
			)
		);

		echo '<div class="wrap zc-chat-admin" dir="rtl"><h1>گفتگوی ' . esc_html( $contact->post_title ) . ' ' . ( $seen && ( time() - $seen ) < 180 ? '🟢' : '' ) . '</h1>';
		self::admin_nav( 'thread' );
		echo '<p><a href="' . esc_url( $base ) . '">← بازگشت به فهرست</a> — <a href="tel:' . esc_attr( $phone ) . '" dir="ltr">' . esc_html( $phone ) . '</a> — IP: <code dir="ltr">' . esc_html( $ip ) . '</code> — آخرین حضور: ' . ( $seen ? esc_html( human_time_diff( $seen ) . ' پیش' ) : '—' ) . '</p>';

		echo '<div class="zc-admin-thread" style="max-width:820px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:10px;">';
		if ( ! $msgs ) {
			echo '<p style="text-align:center;color:#64748b;">هنوز پیامی در این گفتگو نیست.</p>';
		}
		foreach ( $msgs as $m ) {
			$row  = self::message_row( (int) $m->ID );
			$mine = 'out' === $row['dir'];
			$align = $mine ? 'margin-inline-start:auto;' : 'margin-inline-end:auto;';
			$bg    = $mine ? 'background:#111827;color:#fff;' : 'background:#fff;color:#111827;border:1px solid #e2e8f0;';
			echo '<div style="max-width:78%;' . esc_attr( $align ) . $bg . 'padding:10px 14px;border-radius:14px;font-size:.9rem;line-height:1.9;">';
			$meta = '<div style="font-size:.65rem;opacity:.65;margin-top:4px;">' . esc_html( ( $mine ? 'شما' : $contact->post_title ) . ' — ' . $row['date'] ) . '</div>';
			if ( 'text' !== $row['type'] && $row['file'] ) {
				if ( 'photo' === $row['type'] ) {
					echo '<img src="' . esc_url( $row['file'] ) . '" alt="" style="max-width:100%;border-radius:10px;display:block;margin-bottom:6px;">';
				} elseif ( 'voice' === $row['type'] ) {
					echo '<audio controls preload="none" src="' . esc_url( $row['file'] ) . '" style="max-width:100%;display:block;margin-bottom:6px;"></audio>';
				} else {
					echo '<video controls preload="metadata" src="' . esc_url( $row['file'] ) . '" style="max-width:100%;border-radius:10px;display:block;margin-bottom:6px;"></video>';
				}
			}
			if ( '' !== $row['text'] ) {
				echo nl2br( esc_html( $row['text'] ) );
			}
			echo $meta . '</div>';
		}
		echo '</div>';

		echo '<form method="post" action="' . esc_url( $base . '&zc_thread=' . $cid ) . '" style="max-width:820px;margin-top:14px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:14px;">';
		wp_nonce_field( 'zc_chat_admin' );
		echo '<input type="hidden" name="zc_id" value="' . (int) $cid . '">';
		echo '<label style="font-weight:700;display:block;margin-bottom:6px;">پاسخ مدیر</label>';
		echo '<textarea name="zc_admin_text" class="large-text" rows="3" placeholder="پاسخ خود را بنویسید…"></textarea>';
		echo '<div style="display:flex;gap:10px;align-items:center;margin-top:8px;flex-wrap:wrap;">';
		echo '<button class="button button-primary" name="zc_admin_send" value="1">ارسال پاسخ</button>';
		echo '<label style="font-size:.8rem;">یا ارسال فایل (تصویر/ویدیو/صدا): <input type="file" name="zc_admin_file" accept="image/*,video/*,audio/*"></label>';
		echo '<button class="button" name="zc_contact_delete" value="1" onclick="return confirm(\'کل گفتگو حذف شود؟\')" style="margin-inline-start:auto;color:#b91c1c;">حذف گفتگو</button>';
		echo '</div></form></div>';
	}

	private function handle_admin_actions( string $base ): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_chat_admin' );
		$cid = absint( $_POST['zc_id'] ?? 0 );
		if ( ! $cid ) {
			return;
		}

		if ( isset( $_POST['zc_contact_delete'] ) ) {
			$msgs = get_posts(
				array(
					'post_type'      => self::MESSAGE,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $cid ) ),
				)
			);
			foreach ( $msgs as $mid ) {
				wp_delete_post( $mid, true );
			}
			wp_delete_post( $cid, true );
			wp_safe_redirect( $base );
			exit;
		}

		if ( isset( $_POST['zc_admin_send'] ) ) {
			$text = sanitize_textarea_field( (string) ( $_POST['zc_admin_text'] ?? '' ) );
			$file = '';
			if ( ! empty( $_FILES['zc_admin_file']['name'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$received = $this->normalize_admin_upload( 'zc_admin_file' );
				if ( is_wp_error( $received ) ) {
					$fail = $received->get_error_message();
					echo '<div class="notice notice-error"><p>' . esc_html( $fail ) . '</p></div>';
					return;
				}
				$file = $received;
			}
			if ( '' === trim( $text ) && '' === $file ) {
				return;
			}
			$type = 'text';
			if ( '' !== $file ) {
				$ext = strtolower( (string) pathinfo( (string) wp_parse_url( $file, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
				$type = in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ), true ) ? 'photo' : ( in_array( $ext, array( 'mp3', 'ogg', 'oga', 'wav', 'm4a', 'weba' ), true ) ? 'voice' : 'video' );
			}
			$this->store_message( $cid, 'out', $type, $text, $file );
			// Sending a reply marks the visitor side as fully read.
			foreach ( get_posts( array( 'post_type' => self::MESSAGE, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_zc_contact', 'value' => $cid ), array( 'key' => '_zc_dir', 'value' => 'in' ), array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ) ) ) ) as $mid ) {
				update_post_meta( $mid, '_zc_read', '1' );
			}
			wp_safe_redirect( add_query_arg( 'zc_thread', $cid, $base ) );
			exit;
		}
	}

	private function normalize_admin_upload( string $field ): string|\WP_Error {
		if ( empty( $_FILES[ $field ] ) ) {
			return new \WP_Error( 'no_file', 'فایلی انتخاب نشده است.' );
		}
		$file = $_FILES[ $field ];
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'upload', 'آپلود فایل ناموفق بود.' );
		}
		$max = (int) self::settings()['chat_max_mb'];
		if ( (int) $file['size'] > $max * MB_IN_BYTES ) {
			return new \WP_Error( 'size', sprintf( 'حجم فایل باید کمتر از %d مگابایت باشد.', $max ) );
		}
		$result = wp_handle_upload( $file, array( 'test_form' => false, 'test_size' => false, 'mimes' => self::allowed_mimes() ) );
		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'upload', 'فرمت فایل مجاز نیست. ' . $result['error'] );
		}
		return (string) $result['url'];
	}

	/* ------------------------------------------------------------- admin ajax */


	public function ajax_admin_read(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( self::ADMIN_NONCE, 'nonce', false ) || wp_send_json_error( array( 'message' => 'bad nonce' ), 403 );
		$cid = absint( $_POST['contact'] ?? 0 );
		foreach ( get_posts( array( 'post_type' => self::MESSAGE, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_zc_contact', 'value' => $cid ), array( 'key' => '_zc_dir', 'value' => 'in' ), array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ) ) ) ) as $mid ) {
			update_post_meta( $mid, '_zc_read', '1' );
		}
		wp_send_json_success( array( 'ok' => true ) );
	}

	/* ------------------------------------------------- admin nav + live poll */

	/** Tab bar + auto-refresh (live) for list/thread screens. */
	private static function admin_nav( string $view ): void {
		$base   = admin_url( 'admin.php?page=zc-live-chat' );
		$cur    = 'list' === $view ? '#292c32' : 'transparent';
		$curSet = 'settings' === $view ? '#292c32' : 'transparent';
		echo '<div style="display:flex;gap:8px;align-items:center;margin:0 0 14px;">';
		echo '<a class="button" style="background:' . $cur . ';color:#fff;" href="' . esc_url( $base ) . '">گفتگوها</a>';
		echo '<a class="button" style="background:' . $curSet . ';color:#fff;" href="' . esc_url( admin_url( 'admin.php?page=zc-live-chat-settings' ) ) . '">تنظیمات گفتگو</a>';
		echo '<span id="zc-chat-live-dot" title="به‌روزرسانی خودکار" style="width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px #22c55e33;"></span>';
		echo '<span style="font-size:.75rem;color:#64748b;">تازه‌سازی خودکار هر ۸ ثانیه</span>';
		echo '</div>';
		// Tiny live poller: reload the screen when new activity appears.
		$nonce = wp_create_nonce( self::ADMIN_NONCE );
		$mode  = 'thread' === $view ? 'thread' : 'list';
		echo '<script>(function(){var m=' . wp_json_encode( $mode ) . ',n=' . wp_json_encode( $nonce ) . ',last=null,dot=document.getElementById("zc-chat-live-dot");'
			. 'function tick(){fetch(adminurl,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/x-www-form-urlencoded"},'
			. 'body:"action=zc_chat_admin_poll&nonce="+n}).then(function(r){return r.json()}).then(function(j){'
			. 'if(!j||!j.success||!j.data)return;var d=j.data;if(dot){dot.style.background=j.data.active?"#22c55e":"#94a3b8";}'
			. 'var sig=d.unread+"|"+d.lastId+"|"+d.lastContact;if(last===null){last=sig;return;}'
			. 'if(sig!==last){location.reload();}}).catch(function(){})}'
			. 'setInterval(tick,8000);})();var adminurl=' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';</script>';
	}

	/** Lightweight activity feed for the admin poller. */
	public function ajax_admin_poll(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( self::ADMIN_NONCE, 'nonce', false ) || wp_send_json_error( array( 'message' => 'bad nonce' ), 403 );
		$last = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
		$last_id = $last ? (int) $last[0] : 0;
		wp_send_json_success(
			array(
				'unread'       => self::unread_admin_count(),
				'lastId'       => $last_id,
				'lastContact'  => $last_id ? (int) get_post_meta( $last_id, '_zc_contact', true ) : 0,
				'active'       => self::admin_online(),
			)
		);
	}

	/* ----------------------------------------------------------- settings UI */

	public function maybe_save_settings(): void {
		if ( ! isset( $_POST['zc_chat_settings_save'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_chat_settings' );
		$all = get_option( 'zarincode_opt', array() );
		$all = is_array( $all ) ? $all : array();

		// Text fields.
		foreach ( array( 'chat_title', 'chat_greeting', 'chat_offline_msg', 'chat_placeholder', 'chat_accent' ) as $k ) {
			$all[ $k ] = sanitize_text_field( (string) ( $_POST[ $k ] ?? '' ) );
		}
		// Toggles (absent ⇒ 0).
		foreach ( array( 'enable_live_chat', 'chat_field_name', 'chat_field_phone', 'chat_field_website', 'chat_allow_photo', 'chat_allow_video', 'chat_allow_voice' ) as $k ) {
			$all[ $k ] = isset( $_POST[ $k ] ) ? 1 : 0;
		}
		$all['chat_max_mb'] = max( 1, min( 64, (int) ( $_POST['chat_max_mb'] ?? 24 ) ) );

		update_option( 'zarincode_opt', $all );
		wp_safe_redirect( admin_url( 'admin.php?page=zc-live-chat-settings&zc-updated=1' ) );
		exit;
	}

	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$st  = self::settings();
		$on  = function_exists( 'zarincode_get_option' ) ? (int) zarincode_get_option( 'enable_live_chat', true ) : 1;
		$upd = isset( $_GET['zc-updated'] );

		echo '<div class="wrap" dir="rtl"><h1>تنظیمات گفتگوی زنده</h1>';
		self::admin_nav( 'settings' );
		if ( $upd ) {
			echo '<div class="notice notice-success is-dismissible"><p>تنظیمات گفتگوی زنده ذخیره شد.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=zc-live-chat-settings' ) ) . '" style="max-width:760px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;">';
		wp_nonce_field( 'zc_chat_settings' );

		echo '<h2 style="margin-top:0;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">فعال‌سازی</h2>';
		echo '<label style="display:flex;gap:8px;align-items:center;font-weight:700;"><input type="checkbox" name="enable_live_chat" value="1" ' . checked( 1, $on, false ) . '> چت زنده فعال باشد (دکمهٔ شناور و پنل روی همهٔ صفحات)</label>';

		echo '<h2 style="border-bottom:1px solid #f1f5f9;padding-bottom:8px;">متن‌ها</h2>';
		echo '<p><label>عنوان پنل چت<br><input type="text" name="chat_title" class="large-text" value="' . esc_attr( (string) $st['chat_title'] ) . '"></label></p>';
		echo '<p><label>خوش‌آمدگویی (متن دروازهٔ ورود)<br><textarea name="chat_greeting" class="large-text" rows="2">' . esc_textarea( (string) $st['chat_greeting'] ) . '</textarea></label></p>';
		echo '<p><label>پیام حالت آفلاین<br><textarea name="chat_offline_msg" class="large-text" rows="2">' . esc_textarea( (string) $st['chat_offline_msg'] ) . '</textarea></label></p>';
		echo '<p><label>متن جعبهٔ نوشتن<br><input type="text" name="chat_placeholder" class="large-text" value="' . esc_attr( (string) $st['chat_placeholder'] ) . '"></label></p>';
		echo '<p><label>رنگ تم چت (CSS)<br><input type="color" name="chat_accent" value="' . esc_attr( (string) $st['chat_accent'] ) . '" style="width:60px;height:36px;vertical-align:middle"> <span style="color:#64748b;font-size:.8rem;">دکمه‌ها و حباب‌های گفتگو</span></label></p>';

		echo '<h2 style="border-bottom:1px solid #f1f5f9;padding-bottom:8px;">فیلدهای فرم شروع گفتگو</h2>';
		echo '<label style="display:block;margin:4px 0;"><input type="checkbox" name="chat_field_name" value="1" ' . checked( 1, $st['chat_field_name'], false ) . '> نام (در صورت غیرفعال، بازدیدکننده «مهمان» ثبت می‌شود)</label>';
		echo '<label style="display:block;margin:4px 0;"><input type="checkbox" name="chat_field_phone" value="1" ' . checked( 1, $st['chat_field_phone'], false ) . '> شماره موبایل (تاریخچهٔ گفتگوها بر اساس آن یکپارچه می‌شود)</label>';
		echo '<label style="display:block;margin:4px 0;"><input type="checkbox" name="chat_field_website" value="1" ' . checked( 1, $st['chat_field_website'], false ) . '> وبسایت (اختیاری)</label>';

		echo '<h2 style="border-bottom:1px solid #f1f5f9;padding-bottom:8px;">ارسال فایل</h2>';
		echo '<label style="display:inline-block;margin:4px 12px 4px 0;"><input type="checkbox" name="chat_allow_photo" value="1" ' . checked( 1, $st['chat_allow_photo'], false ) . '> عکس</label>';
		echo '<label style="display:inline-block;margin:4px 12px 4px 0;"><input type="checkbox" name="chat_allow_video" value="1" ' . checked( 1, $st['chat_allow_video'], false ) . '> ویدیو</label>';
		echo '<label style="display:inline-block;margin:4px 12px 4px 0;"><input type="checkbox" name="chat_allow_voice" value="1" ' . checked( 1, $st['chat_allow_voice'], false ) . '> ویس (ضبط صدا)</label>';
		echo '<p><label>حداکثر حجم هر فایل (مگابایت، ۱ تا ۶۴)<br><input type="number" name="chat_max_mb" min="1" max="64" value="' . (int) $st['chat_max_mb'] . '" style="width:90px;"></label></p>';

		echo '<p class="description" style="margin-top:14px;">پیام‌های گفتگو در پیشخوان ← زرین‌پرس ← گفتگوهای زنده دریافت و پاسخ داده می‌شوند. حضور شما (آنلاین/آفلاین) با ۹۰ ثانیه آخرین فعالیت در پیشخوان تشخیص داده می‌شود.</p>';
		echo '<p><button type="submit" name="zc_chat_settings_save" value="1" class="button button-primary">ذخیرهٔ تنظیمات</button></p>';
		echo '</form></div>';
	}
}

