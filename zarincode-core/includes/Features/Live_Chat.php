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
		add_action( 'wp_ajax_zc_chat_admin_send', array( $this, 'ajax_admin_send' ) );
		add_action( 'wp_ajax_zc_chat_admin_fetch', array( $this, 'ajax_admin_fetch' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 8 );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
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
			)
		);
		return (int) $q->found_posts; // real total; post_count is capped by posts_per_page.
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
			'chat_sound'         => 1,
			'chat_notify_email'  => 1,
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
		$out['chat_sound']         = isset( $out['chat_sound'] ) ? ( $out['chat_sound'] ? 1 : 0 ) : 1;
		$out['chat_notify_email']  = isset( $out['chat_notify_email'] ) ? ( $out['chat_notify_email'] ? 1 : 0 ) : 1;
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
			)
		);
		return (int) $q->found_posts; // real total; post_count is capped by posts_per_page.
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
				wp_send_json_error( array( 'message' => \Zarincode\Runtime260\Security\Hardening::scrub( $file->get_error_message() ) ), 422 );
			}
		}

		$id = $this->store_message( $contact, 'in', in_array( $type, array( 'text', 'photo', 'voice', 'video' ), true ) ? $type : 'text', $text, is_string( $file ) ? $file : '' );
		$this->notify_admin_email( $contact );
		wp_send_json_success( array( 'message' => self::message_row( $id ) ) );
	}

	/**
	 * Email the site admin when a visitor sends a chat message.
	 * Throttled to one email per contact per 5 minutes to avoid spam floods.
	 */
	private function notify_admin_email( int $contact ): void {
		if ( ! (int) self::settings()['chat_notify_email'] ) {
			return;
		}
		$all = get_option( 'zc_chat_email_last', array() );
		$all = is_array( $all ) ? $all : array();
		$now = time();
		if ( isset( $all[ $contact ] ) && ( $now - (int) $all[ $contact ] ) < 300 ) {
			return;
		}
		$all[ $contact ] = $now;
		// Keep the throttle option small: drop entries older than a day.
		foreach ( $all as $cid => $ts ) {
			if ( ( $now - (int) $ts ) > DAY_IN_SECONDS ) {
				unset( $all[ $cid ] );
			}
		}
		update_option( 'zc_chat_email_last', $all, false );

		$phone = (string) get_post_meta( $contact, '_zc_phone', true );
		$name  = (string) get_the_title( $contact );
		$last  = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array( 'key' => '_zc_contact', 'value' => $contact ),
					array( 'key' => '_zc_dir', 'value' => 'in' ),
				),
			)
		);
		$excerpt = $last ? mb_strimwidth( (string) $last[0]->post_content, 0, 160, '…' ) : '—';
		$thread  = admin_url( 'admin.php?page=zc-live-chat&zc_thread=' . $contact );
		wp_mail(
			get_option( 'admin_email' ),
			'پیام جدید گفتگوی زنده — ' . $name . ' (' . $phone . ')',
			implode(
				"\n",
				array(
					'بازدیدکننده یک پیام جدید در گفتگوی زنده ارسال کرده است.',
					'',
					'نام: ' . $name,
					'شماره موبایل: ' . $phone,
					'آخرین پیام: ' . $excerpt,
					'مشاهده گفتگو: ' . $thread,
				)
			),
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
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
			return new \WP_Error( 'upload', 'فرمت فایل مجاز نیست (تصویر، ویدیو یا صدا). ' . \Zarincode\Runtime260\Security\Hardening::scrub( (string) $result['error'] ) );
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
			'sound'     => (bool) $st['chat_sound'],
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

	/* ------------------------------------------------------- admin assets */

	/** Enqueue the admin chat CSS/JS only on the two chat screens. */
	public function admin_assets( string $hook ): void {
		if ( false === strpos( (string) $hook, 'zc-live-chat' ) ) {
			return;
		}
		$plugin_base = plugin_dir_url( dirname( __DIR__, 2 ) . '/zarincode-core.php' );
		wp_enqueue_style( 'zc-fa-admin', $plugin_base . 'assets/vendor/fontawesome/css/all.min.css', array(), '6.5.2' );
		wp_enqueue_style( 'zc-live-chat-admin', $plugin_base . 'assets/css/live-chat-admin.css', array( 'zc-fa-admin' ), '1.1.0' );
		wp_enqueue_script( 'zc-live-chat-admin', $plugin_base . 'assets/js/live-chat-admin.js', array(), '1.1.0', true );

		$thread_id = ( isset( $_GET['page'] ) && 'zc-live-chat' === $_GET['page'] && isset( $_GET['zc_thread'] ) ) ? absint( $_GET['zc_thread'] ) : 0;
		$mode      = 'zc-live-chat-settings' === ( $_GET['page'] ?? '' ) ? 'settings' : ( $thread_id ? 'thread' : 'list' );
		$since     = 0;
		if ( $thread_id ) {
			$last = get_posts(
				array(
					'post_type'      => self::MESSAGE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'DESC',
					'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $thread_id ) ),
				)
			);
			$since = $last ? (int) $last[0] : 0;
		}
		wp_localize_script(
			'zc-live-chat-admin',
			'ZarincodeChatAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::ADMIN_NONCE ),
				'mode'     => $mode,
				'contact'  => $thread_id,
				'since'    => $since,
				'pollMs'   => 8000,
				'threadMs' => 5000,
				'strings'  => array(
					'title'         => 'پیام جدید گفتگوی زنده',
					'bodyNew'       => 'یک بازدیدکننده پیام جدیدی ارسال کرده است؛ برای پاسخ روی اعلان یا منوی «گفتگوهای زنده» کلیک کنید.',
					'error'         => 'خطا در ارتباط؛ دوباره تلاش کنید.',
					'confirmDelete' => 'کل گفتگو برای همیشه حذف شود؟',
				),
			)
		);
	}

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
		$zq     = (string) ( $_GET['zc_chat_q'] ?? '' );
		$tab    = in_array( ( $_GET['zc_tab'] ?? 'all' ), array( 'all', 'unread', 'online' ), true ) ? (string) $_GET['zc_tab'] : 'all';
		$paged  = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$per    = 20;

		// All contacts (capped) + last-activity map from recent messages.
		$contact_ids = get_posts(
			array(
				'post_type'      => self::CONTACT,
				'post_status'    => 'any',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$recent = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 300,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$last_msg = array();
		$activity = array();
		foreach ( $recent as $mid ) {
			$mcid = (int) get_post_meta( $mid, '_zc_contact', true );
			if ( $mcid && ! isset( $last_msg[ $mcid ] ) ) {
				$last_msg[ $mcid ] = $mid;
				$activity[ $mcid ] = (int) strtotime( (string) get_post_field( 'post_date', $mid ) );
			}
		}

		// Unread counts for ALL contacts in one query (real totals via found_posts).
		$unread_total = 0;
		$unread_map   = array();
		if ( $contact_ids ) {
			$uq = new \WP_Query(
				array(
					'post_type'      => self::MESSAGE,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array( 'key' => '_zc_contact', 'value' => $contact_ids, 'compare' => 'IN' ),
						array( 'key' => '_zc_dir', 'value' => 'in' ),
						array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ),
					),
					'no_found_rows'  => false,
				)
			);
			$unread_total = (int) $uq->found_posts;
			foreach ( $uq->posts as $uid ) {
				$ucid = (int) get_post_meta( $uid, '_zc_contact', true );
				$unread_map[ $ucid ] = ( $unread_map[ $ucid ] ?? 0 ) + 1;
			}
		}

		// Build row models: filter (search + tab), then sort by activity.
		$rows = array();
		$online_count = 0;
		$today        = 0;
		foreach ( $contact_ids as $cid ) {
			$c     = get_post( $cid );
			if ( ! $c ) {
				continue;
			}
			$phone = (string) get_post_meta( $cid, '_zc_phone', true );
			$site  = (string) get_post_meta( $cid, '_zc_website', true );
			$ip    = (string) get_post_meta( $cid, '_zc_ip', true );
			$seen  = (int) get_post_meta( $cid, '_zc_last_seen', true );
			$is_on = $seen && ( time() - $seen ) < 180;
			if ( $is_on ) {
				$online_count++;
			}
			$unread = (int) ( $unread_map[ $cid ] ?? 0 );
			$mid    = (int) ( $last_msg[ $cid ] ?? 0 );
			$prev   = $mid ? (string) get_post_field( 'post_content', $mid ) : '';
			$prev_t = $mid ? (string) get_post_meta( $mid, '_zc_type', true ) : '';
			$prev_d = $mid ? (string) get_post_meta( $mid, '_zc_dir', true ) : '';

			if ( '' !== $zq ) {
				$hay = mb_strtolower( $c->post_title . ' ' . $phone . ' ' . $site . ' ' . $ip . ' ' . $prev );
				if ( false === mb_strpos( $hay, mb_strtolower( $zq ) ) ) {
					continue;
				}
			}
			if ( 'unread' === $tab && $unread < 1 ) {
				continue;
			}
			if ( 'online' === $tab && ! $is_on ) {
				continue;
			}
			$rows[] = array(
				'id' => $cid, 'name' => $c->post_title, 'phone' => $phone, 'site' => $site, 'ip' => $ip,
				'seen' => $seen, 'online' => $is_on, 'unread' => $unread, 'last' => $mid,
				'preview' => $prev, 'ptype' => $prev_t, 'pdir' => $prev_d,
				'activity' => (int) ( $activity[ $cid ] ?? strtotime( (string) $c->post_date ) ),
			);
		}
		usort( $rows, static fn( $a, $b ) => $b['activity'] <=> $a['activity'] );
		$total_rows = count( $rows );
		$pages      = max( 1, (int) ceil( $total_rows / $per ) );
		$paged      = min( $paged, $pages );
		$page_rows  = array_slice( $rows, ( $paged - 1 ) * $per, $per );

		$tq = new \WP_Query(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => array( array( 'after' => 'today 00:00', 'inclusive' => true ) ),
			)
		);
		$today = (int) $tq->found_posts;

		$this->print_admin_styles_marker();
		echo '<div class="wrap zc-ladm" dir="rtl">';
		echo '<div class="zc-ladm-top"><h1>گفتگوهای زنده<span class="zc-ladm-sub">تاریخچهٔ کامل بر اساس شماره موبایل یا IP هر بازدیدکننده — پاسخ‌ها همین‌جا ثبت می‌شود</span></h1>';
		echo '<span class="zc-ladm-me' . ( $online ? ' is-online' : '' ) . '" title="' . esc_attr( $online ? 'بازدیدکننده‌ها نشان سبز و «پشتیبانی آنلاین است» را می‌بینند.' : 'پیام‌های جدید همچنان ثبت و اینجا خوانده می‌شوند.' ) . '"><i class="zc-ladm-dot"></i>' . ( $online ? 'شما آنلاین هستید' : 'شما آفلاین هستید' ) . '</span></div>';

		// Tab bar (shared nav).
		$cur_list = 'is-active';
		echo '<div class="zc-ladm-tabs">';
		echo '<a class="' . $cur_list . '" href="' . esc_url( $base ) . '"><i class="fa-regular fa-comments"></i> گفتگوها</a>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=zc-live-chat-settings' ) ) . '"><i class="fa-solid fa-sliders"></i> تنظیمات گفتگو</a>';
		echo '<span class="zc-ladm-live' . ( $online ? '' : ' is-off' ) . '" id="zc-chat-live-dot" title="به‌روزرسانی خودکار هر ۸ ثانیه"><i></i> زنده</span>';
		echo '</div>';

		// Stats cards.
		echo '<div class="zc-ladm-stats">';
		echo '<div class="zc-ladm-stat is-slate"><i class="zc-ladm-stat-ic fa-solid fa-inbox"></i><div><b>' . esc_html( number_format_i18n( count( $contact_ids ) ) ) . '</b><span>کل گفتگوها</span></div></div>';
		echo '<div class="zc-ladm-stat is-gold"><i class="zc-ladm-stat-ic fa-solid fa-envelope-open-text"></i><div><b>' . esc_html( number_format_i18n( $unread_total ) ) . '</b><span>پیام خوانده‌نشده</span></div></div>';
		echo '<div class="zc-ladm-stat is-green"><i class="zc-ladm-stat-ic fa-solid fa-signal"></i><div><b>' . esc_html( number_format_i18n( $online_count ) ) . '</b><span>بازدیدکننده آنلاین (۳ دقیقه اخیر)</span></div></div>';
		echo '<div class="zc-ladm-stat is-red"><i class="zc-ladm-stat-ic fa-regular fa-paper-plane"></i><div><b>' . esc_html( number_format_i18n( $today ) ) . '</b><span>پیام امروز</span></div></div>';
		echo '</div>';

		// Toolbar: search + tab filters.
		$counts = array( 'all' => $total_rows, 'unread' => 0, 'online' => 0 );
		foreach ( $rows as $r ) {
			if ( $r['unread'] > 0 ) {
				$counts['unread']++;
			}
			if ( $r['online'] ) {
				$counts['online']++;
			}
		}
		echo '<div class="zc-ladm-toolbar">';
		echo '<form class="zc-ladm-search" method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="zc-live-chat">';
		if ( 'all' !== $tab ) {
			echo '<input type="hidden" name="zc_tab" value="' . esc_attr( $tab ) . '">';
		}
		echo '<input type="search" name="zc_chat_q" value="' . esc_attr( $zq ) . '" placeholder="جستجو در نام، شماره، سایت، IP یا متن پیام…">';
		echo '<button class="button button-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> جستجو</button>';
		if ( '' !== $zq ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( 'zc_tab', $tab, $base ) ) . '">پاک کردن</a>';
		}
		echo '</form>';
		echo '<nav class="zc-ladm-filters">';
		foreach ( array( 'all' => 'همه', 'unread' => 'خوانده‌نشده', 'online' => 'آنلاین' ) as $k => $label ) {
			$url    = add_query_arg( array_filter( array( 'zc_tab' => $k, 'zc_chat_q' => $zq ) ), $base );
			$active = ( $tab === $k );
			echo '<a class="' . ( $active ? 'is-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '<span class="cnt">' . esc_html( number_format_i18n( $counts[ $k ] ) ) . '</span></a>';
		}
		echo '</nav>';
		echo '</div>';

		// Conversations table.
		echo '<div class="zc-ladm-panel"><table class="zc-ladm-table"><thead><tr>';
		echo '<th>مخاطب</th><th>آخرین پیام</th><th>وضعیت</th><th>خوانده‌نشده</th><th>آخرین فعالیت</th><th>عملیات</th>';
		echo '</tr></thead><tbody>';
		if ( ! $page_rows ) {
			if ( '' !== $zq || 'all' !== $tab ) {
				echo '<tr><td colspan="6"><div class="zc-ladm-empty"><i class="fa-solid fa-magnifying-glass"></i><b>نتیجه‌ای پیدا نشد</b>عبارت جستجو یا فیلتر را ساده‌تر کنید.</div></td></tr>';
			} else {
				echo '<tr><td colspan="6"><div class="zc-ladm-empty"><i class="fa-regular fa-comments"></i><b>هنوز گفتگویی ثبت نشده است</b>با دکمهٔ شناور در سایت، یک گفتگو را امتحان کنید.</div></td></tr>';
			}
		}
		foreach ( $page_rows as $r ) {
			$thread_url = add_query_arg( 'zc_thread', $r['id'], $base );
			$initial    = esc_html( mb_strimwidth( (string) $r['name'], 0, 1, '' ) ?: '؟' );
			$dir_icon   = 'out' === $r['pdir'] ? '<span class="zc-ladm-dir is-out" title="پاسخ شما">↗</span>' : '<span class="zc-ladm-dir is-in" title="پیام بازدیدکننده">↙</span>';
			$prev_text  = '' !== trim( (string) $r['preview'] ) ? esc_html( mb_strimwidth( $r['preview'], 0, 90, '…' ) ) : '<span class="zc-ladm-empty-txt">بدون پیام — فقط ثبت‌شده</span>';
			if ( $r['last'] && 'text' !== $r['ptype'] && '' !== $r['ptype'] ) {
				$type_fa   = 'photo' === $r['ptype'] ? 'fa-regular fa-image' : ( 'voice' === $r['ptype'] ? 'fa-solid fa-microphone' : 'fa-solid fa-video' );
				$prev_text = '<i class="' . esc_attr( $type_fa ) . '"></i> ' . ( 'text' !== $r['ptype'] && '' === trim( (string) $r['preview'] ) ? 'ارسال فایل' : $prev_text );
			}
			echo '<tr>';
			echo '<td data-ladm-label="مخاطب"><div class="zc-ladm-who"><span class="zc-ladm-ava' . ( $r['online'] ? ' is-online' : '' ) . '">' . $initial . '<span class="zc-ladm-ava-on"></span></span><span><strong><a href="' . esc_url( $thread_url ) . '">' . esc_html( $r['name'] ) . '</a></strong><small>' . ( $r['phone'] ? '<b>' . esc_html( $r['phone'] ) . '</b>' : 'بدون شماره' ) . ( $r['ip'] ? ' · <b>' . esc_html( $r['ip'] ) . '</b>' : '' ) . '</small></span></div></td>';
			echo '<td data-ladm-label="آخرین پیام"><div class="zc-ladm-preview">' . ( $r['last'] ? $dir_icon . $prev_text : $prev_text ) . '</div></td>';
			echo '<td data-ladm-label="وضعیت">' . ( $r['online'] ? '<span class="zc-ladm-pill is-online">آنلاین</span>' : '<span class="zc-ladm-pill is-offline">آفلاین' . ( $r['seen'] ? ' · ' . esc_html( self::human_time_fa( $r['seen'] ) ) : '' ) . '</span>' ) . '</td>';
			echo '<td data-ladm-label="خوانده‌نشده"><span class="zc-ladm-unread' . ( $r['unread'] ? '' : ' is-zero' ) . '">' . esc_html( $r['unread'] ? number_format_i18n( $r['unread'] ) : '—' ) . '</span></td>';
			echo '<td data-ladm-label="آخرین فعالیت"><span class="zc-ladm-when" title="' . esc_attr( gmdate( 'Y/m/d H:i', $r['activity'] ) . ' UTC' ) . '">' . esc_html( $r['activity'] ? self::human_time_fa( $r['activity'] ) : '—' ) . '</span></td>';
			echo '<td data-ladm-label="عملیات"><div class="zc-ladm-row-actions">';
			echo '<a class="zc-ladm-btn is-primary" href="' . esc_url( $thread_url ) . '"><i class="fa-regular fa-comment-dots"></i> باز کردن</a>';
			if ( $r['phone'] ) {
				echo '<a class="zc-ladm-btn is-icon" href="tel:' . esc_attr( $r['phone'] ) . '" title="تماس"><i class="fa-solid fa-phone"></i></a>';
			}
			echo '</div></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		if ( $pages > 1 ) {
			echo '<div class="zc-ladm-pager">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				$url = add_query_arg( array_filter( array( 'paged' => $i, 'zc_tab' => $tab, 'zc_chat_q' => $zq ) ), $base );
				echo ( $i === $paged ) ? '<span class="current">' . $i . '</span>' : '<a href="' . esc_url( $url ) . '">' . $i . '</a>';
			}
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	/** No-op hook kept for markup symmetry (styles ship via live-chat-admin.css). */
	private function print_admin_styles_marker(): void {
	}

	/** Persian relative time ("۵ دقیقه پیش") — admin UI only. */
	private static function human_time_fa( int $ts ): string {
		if ( $ts <= 0 ) {
			return '';
		}
		$d = time() - $ts;
		if ( $d < 60 ) {
			return 'چند لحظه پیش';
		}
		if ( $d < 3600 ) {
			return (int) floor( $d / 60 ) . ' دقیقه پیش';
		}
		if ( $d < 86400 ) {
			return (int) floor( $d / 3600 ) . ' ساعت پیش';
		}
		if ( $d < 86400 * 7 ) {
			return (int) floor( $d / 86400 ) . ' روز پیش';
		}
		return gmdate( 'Y/m/d', $ts );
	}

	/** Shared bubble renderer (initial render + AJAX send/fetch responses). */
	private function message_html( array $row, string $visitor, int $seen_ts ): string {
		$mine   = 'out' === $row['dir'];
		$stamp  = (int) strtotime( (string) ( $row['date'] ?? '' ) );
		$initial = esc_html( mb_strimwidth( $visitor, 0, 1, '' ) ?: '؟' );
		$ava    = $mine
			? '<span class="zc-ladm-msg-ava" aria-hidden="true"><i class="fa-solid fa-headset" style="font-size:.7rem;"></i></span>'
			: '<span class="zc-ladm-msg-ava" aria-hidden="true">' . $initial . '</span>';
		$media  = '';
		if ( 'text' !== $row['type'] && '' !== $row['file'] ) {
			if ( 'photo' === $row['type'] ) {
				$media = '<a href="' . esc_url( $row['file'] ) . '" target="_blank" rel="noopener"><img class="zc-ladm-photo" src="' . esc_url( $row['file'] ) . '" alt="" loading="lazy"></a>';
			} elseif ( 'voice' === $row['type'] ) {
				$media = '<audio controls preload="none" src="' . esc_url( $row['file'] ) . '"></audio>';
			} else {
				$media = '<video controls preload="metadata" src="' . esc_url( $row['file'] ) . '"></video>';
			}
		}
		$text = '' !== $row['text'] ? '<div class="zc-ladm-b-text">' . nl2br( esc_html( $row['text'] ) ) . '</div>' : '';
		$ticks = '';
		if ( $mine && $stamp && $seen_ts >= $stamp ) {
			$ticks = '<span class="zc-ladm-ticks" title="خوانده شده">✓✓</span>';
		} elseif ( $mine ) {
			$ticks = '<span class="zc-ladm-ticks" title="ارسال شد">✓</span>';
		}
		$who = $mine ? 'شما' : $visitor;
		$meta = '<div class="zc-ladm-b-meta"><span>' . esc_html( $who ) . '</span><span>·</span><span>' . esc_html( (string) ( $row['at'] ?? '' ) ) . '</span>' . $ticks . '</div>';
		return '<div class="zc-ladm-msg ' . ( $mine ? 'is-admin' : 'is-visitor' ) . '" data-mid="' . (int) ( $row['id'] ?? 0 ) . '">' . $ava . '<div class="zc-ladm-bubble">' . $media . $text . $meta . '</div></div>';
	}

	private function day_label( int $ts ): string {
		$today = ( new \DateTime( 'today' ) )->getTimestamp();
		if ( $ts >= $today ) {
			return 'امروز';
		}
		if ( $ts >= $today - DAY_IN_SECONDS ) {
			return 'دیروز';
		}
		return gmdate( 'Y/m/d', $ts );
	}

	private function render_thread( int $cid, string $base ): void {
		$contact = get_post( $cid );
		if ( ! $contact || self::CONTACT !== $contact->post_type ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>گفتگو پیدا نشد.</p></div></div>';
			return;
		}
		$this->handle_admin_actions( $base );

		// Opening the thread marks visitor messages as read.
		foreach ( get_posts(
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
		) as $mid ) {
			update_post_meta( $mid, '_zc_read', '1' );
		}

		$phone   = (string) get_post_meta( $cid, '_zc_phone', true );
		$site    = (string) get_post_meta( $cid, '_zc_website', true );
		$ip      = (string) get_post_meta( $cid, '_zc_ip', true );
		$seen    = (int) get_post_meta( $cid, '_zc_last_seen', true );
		$is_on   = $seen && ( time() - $seen ) < 180;
		$visitor = (string) $contact->post_title;
		$msgs    = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 500,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $cid ) ),
			)
		);

		echo '<div class="wrap zc-ladm" dir="rtl">';
		echo '<a class="zc-ladm-back" href="' . esc_url( $base ) . '"><i class="fa-solid fa-arrow-right"></i> بازگشت به فهرست گفتگوها</a>';

		// Contact header card.
		$initial = esc_html( mb_strimwidth( $visitor, 0, 1, '' ) ?: '؟' );
		echo '<div class="zc-ladm-chead"><div class="zc-ladm-chead-id">';
		echo '<span class="zc-ladm-ava' . ( $is_on ? ' is-online' : '' ) . '">' . $initial . '<span class="zc-ladm-ava-on"></span></span>';
		echo '<div><h2>' . esc_html( $visitor ) . ( $is_on ? ' <span class="zc-ladm-pill is-online">آنلاین</span>' : ' <span class="zc-ladm-pill is-offline">آفلاین</span>' ) . '</h2>';
		echo '<div class="zc-ladm-chips">';
		if ( $phone ) {
			echo '<a class="zc-ladm-chip" href="tel:' . esc_attr( $phone ) . '"><i class="fa-solid fa-phone"></i> <b>' . esc_html( $phone ) . '</b></a>';
		}
		if ( $site ) {
			echo '<a class="zc-ladm-chip" href="' . esc_url( $site ) . '" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i> ' . esc_html( mb_strimwidth( $site, 0, 34, '…' ) ) . '</a>';
		}
		if ( $ip ) {
			echo '<button type="button" class="zc-ladm-chip" data-zc-copy="' . esc_attr( $ip ) . '" title="کپی IP"><i class="fa-regular fa-copy"></i> <b>' . esc_html( $ip ) . '</b></button>';
		}
		echo '<span class="zc-ladm-chip' . ( $is_on ? ' is-green' : '' ) . '"><i class="fa-regular fa-clock"></i> آخرین حضور: ' . esc_html( $seen ? self::human_time_fa( $seen ) : 'هرگز' ) . '</span>';
		$msg_count_q = new \WP_Query(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $cid ) ),
			)
		);
		$msg_count = (int) $msg_count_q->found_posts;
		echo '<span class="zc-ladm-chip is-gold"><i class="fa-regular fa-envelope"></i> ' . esc_html( number_format_i18n( $msg_count ) ) . ' پیام</span>';
		echo '</div></div></div>';
		echo '<div class="zc-ladm-chead-actions">';
		echo '<form method="post" id="zc-ladm-delete-form" action="' . esc_url( $base . '&zc_thread=' . $cid ) . '">';
		wp_nonce_field( 'zc_chat_admin' );
		echo '<input type="hidden" name="zc_id" value="' . (int) $cid . '">';
		echo '<button class="zc-ladm-btn is-danger" name="zc_contact_delete" value="1"><i class="fa-regular fa-trash-can"></i> حذف گفتگو</button>';
		echo '</form></div></div>';

		// Thread + composer.
		echo '<div class="zc-ladm-thread">';
		echo '<div class="zc-ladm-msgs" id="zc-ladm-msgs">';
		if ( ! $msgs ) {
			echo '<div class="zc-ladm-empty"><i class="fa-regular fa-comment-dots"></i><b>هنوز پیامی در این گفتگو نیست</b>پاسخ شما از پایین ارسال می‌شود؛ پیام بازدیدکننده همین‌جا ظاهر می‌شود.</div>';
		}
		$last_day = '';
		foreach ( $msgs as $m ) {
			$row  = self::message_row( (int) $m->ID );
			if ( ! $row ) {
				continue;
			}
			$ts  = (int) strtotime( (string) $row['date'] );
			$day = $this->day_label( $ts );
			if ( $day !== $last_day ) {
				echo '<div class="zc-ladm-daysep">' . esc_html( $day ) . '</div>';
				$last_day = $day;
			}
			echo $this->message_html( $row, $visitor, $seen ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside helper.
		}
		echo '</div>';
		echo '<button type="button" class="zc-ladm-jump" id="zc-ladm-jump" title="رفتن به انتها" aria-label="رفتن به انتها"><i class="fa-solid fa-arrow-down"></i></button>';

		echo '<form class="zc-ladm-composer" id="zc-ladm-composer-form" method="post" action="' . esc_url( $base . '&zc_thread=' . $cid ) . '">';
		wp_nonce_field( 'zc_chat_admin' );
		echo '<input type="hidden" name="zc_id" value="' . (int) $cid . '">';
		echo '<div class="zc-ladm-filechip" id="zc-ladm-filechip"><i class="fa-solid fa-paperclip"></i> <span class="zc-ladm-filechip-name" id="zc-ladm-filechip-name"></span><button type="button" id="zc-ladm-filechip-x" aria-label="حذف پیوست">&times;</button></div>';
		echo '<div class="zc-ladm-composer-row">';
		echo '<button type="button" class="zc-ladm-attach" id="zc-ladm-attach" title="ارسال فایل (تصویر/ویدیو/صدا)" aria-label="ارسال فایل"><i class="fa-solid fa-paperclip"></i></button>';
		echo '<input type="file" id="zc-ladm-file" name="zc_admin_file" accept="image/*,video/*,audio/*" hidden>';
		echo '<textarea id="zc-ladm-text" name="zc_admin_text" rows="1" placeholder="پاسخ خود را بنویسید… (Enter برای ارسال، Shift+Enter خط جدید)"></textarea>';
		echo '<button type="submit" class="zc-ladm-send" id="zc-ladm-send" name="zc_admin_send" value="1"><i class="fa-solid fa-paper-plane"></i> ارسال</button>';
		echo '</div>';
		echo '<div class="zc-ladm-hint"><span><kbd>Enter</kbd> ارسال · <kbd>Shift</kbd>+<kbd>Enter</kbd> خط جدید — پاسخ فوری بدون رفرش ثبت می‌شود</span><span>حداکثر حجم فایل: ' . esc_html( number_format_i18n( (int) self::settings()['chat_max_mb'] ) ) . ' مگابایت</span></div>';
		echo '</form></div>';
		echo '</div>';
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
					$fail = \Zarincode\Runtime260\Security\Hardening::scrub( $received->get_error_message() );
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
			return new \WP_Error( 'upload', 'فرمت فایل مجاز نیست. ' . \Zarincode\Runtime260\Security\Hardening::scrub( (string) $result['error'] ) );
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

	/** AJAX admin reply (text and/or file) — returns rendered bubble HTML. */
	public function ajax_admin_send(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( self::ADMIN_NONCE, 'nonce', false ) || wp_send_json_error( array( 'message' => 'bad nonce' ), 403 );
		$cid     = absint( $_POST['contact'] ?? 0 );
		$contact = $cid ? get_post( $cid ) : null;
		if ( ! $contact || self::CONTACT !== $contact->post_type ) {
			wp_send_json_error( array( 'message' => 'گفتگو یافت نشد.' ), 404 );
		}
		$text = sanitize_textarea_field( (string) ( $_POST['text'] ?? '' ) );
		$file = '';
		if ( ! empty( $_FILES['hc_file']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$received = $this->normalize_admin_upload( 'hc_file' );
			if ( is_wp_error( $received ) ) {
				wp_send_json_error( array( 'message' => \Zarincode\Runtime260\Security\Hardening::scrub( $received->get_error_message() ) ), 422 );
			}
			$file = $received;
		}
		if ( '' === trim( $text ) && '' === $file ) {
			wp_send_json_error( array( 'message' => 'متن پیام یا فایلی ارسال نکنید.' ), 422 );
		}
		$type = 'text';
		if ( '' !== $file ) {
			$ext  = strtolower( (string) pathinfo( (string) wp_parse_url( $file, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
			$type = in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ), true ) ? 'photo' : ( in_array( $ext, array( 'mp3', 'ogg', 'oga', 'wav', 'm4a', 'weba' ), true ) ? 'voice' : 'video' );
		}
		$id = $this->store_message( $cid, 'out', $type, $text, $file );
		// Sending a reply marks the visitor side as fully read.
		foreach ( get_posts( array( 'post_type' => self::MESSAGE, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_zc_contact', 'value' => $cid ), array( 'key' => '_zc_dir', 'value' => 'in' ), array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ) ) ) ) as $mid ) {
			update_post_meta( $mid, '_zc_read', '1' );
		}
		$row  = self::message_row( $id );
		$seen = (int) get_post_meta( $cid, '_zc_last_seen', true );
		wp_send_json_success(
			array(
				'id'   => $id,
				'dir'  => 'out',
				'html' => $row ? $this->message_html( $row, (string) $contact->post_title, $seen ) : '',
			)
		);
	}

	/** AJAX: new messages in a thread after a given ID (append-only live view). */
	public function ajax_admin_fetch(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( self::ADMIN_NONCE, 'nonce', false ) || wp_send_json_error( array( 'message' => 'bad nonce' ), 403 );
		$cid     = absint( $_POST['contact'] ?? 0 );
		$contact = $cid ? get_post( $cid ) : null;
		if ( ! $contact || self::CONTACT !== $contact->post_type ) {
			wp_send_json_error( array( 'message' => 'گفتگو یافت نشد.' ), 404 );
		}
		$since = absint( $_POST['since'] ?? 0 );
		$rows  = array();
		$msgs  = get_posts(
			array(
				'post_type'      => self::MESSAGE,
				'post_status'    => 'any',
				'posts_per_page' => 60,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => array( array( 'key' => '_zc_contact', 'value' => $cid ) ),
			)
		);
		$seen  = (int) get_post_meta( $cid, '_zc_last_seen', true );
		$found = false;
		foreach ( $msgs as $m ) {
			if ( $since && (int) $m->ID <= $since ) {
				continue;
			}
			$row = self::message_row( (int) $m->ID );
			if ( ! $row ) {
				continue;
			}
			$found = true;
			$rows[] = array(
				'id'   => (int) $m->ID,
				'dir'  => $row['dir'],
				'html' => $this->message_html( $row, (string) $contact->post_title, $seen ),
			);
		}
		// Fresh visitor messages fetched while the thread is open count as read.
		if ( $found ) {
			foreach ( get_posts( array( 'post_type' => self::MESSAGE, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_zc_contact', 'value' => $cid ), array( 'key' => '_zc_dir', 'value' => 'in' ), array( 'key' => '_zc_read', 'value' => '1', 'compare' => '!=' ) ) ) ) as $mid ) {
				update_post_meta( $mid, '_zc_read', '1' );
			}
		}
		wp_send_json_success( array( 'rows' => $rows ) );
	}

	/* ------------------------------------------------- admin nav + live poll */

	/** Compact pill tab bar (live dot is fed by live-chat-admin.js). */
	private static function admin_nav( string $view ): void {
		$base     = admin_url( 'admin.php?page=zc-live-chat' );
		$cur_list = 'list' === $view || 'thread' === $view ? ' is-active' : '';
		$cur_set  = 'settings' === $view ? ' is-active' : '';
		echo '<div class="zc-ladm-tabs">';
		echo '<a class="zc-ladm-tab' . $cur_list . '" href="' . esc_url( $base ) . '"><i class="fa-regular fa-comments"></i> گفتگوها</a>';
		echo '<a class="zc-ladm-tab' . $cur_set . '" href="' . esc_url( admin_url( 'admin.php?page=zc-live-chat-settings' ) ) . '"><i class="fa-solid fa-sliders"></i> تنظیمات گفتگو</a>';
		echo '<span class="zc-ladm-live' . ( self::admin_online() ? '' : ' is-off' ) . '" id="zc-chat-live-dot" title="به‌روزرسانی خودکار"><i></i> زنده</span>';
		echo '</div>';
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
		foreach ( array( 'enable_live_chat', 'chat_field_name', 'chat_field_phone', 'chat_field_website', 'chat_allow_photo', 'chat_allow_video', 'chat_allow_voice', 'chat_sound', 'chat_notify_email' ) as $k ) {
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

		echo '<div class="wrap zc-ladm zc-ladm-set" dir="rtl">';
		echo '<div class="zc-ladm-top"><h1>تنظیمات گفتگوی زنده<span class="zc-ladm-sub">متن‌ها، فیلدهای فرم، امکانات ارسال فایل و اعلان‌ها — تغییرات بلافاصله روی پنل بازدیدکننده اعمال می‌شود</span></h1></div>';
		self::admin_nav( 'settings' );
		if ( $upd ) {
			echo '<div class="notice notice-success is-dismissible"><p>تنظیمات گفتگوی زنده ذخیره شد.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=zc-live-chat-settings' ) ) . '">';
		wp_nonce_field( 'zc_chat_settings' );

		echo '<div class="zc-ladm-set-card"><h2><i class="fa-solid fa-toggle-on"></i> فعال‌سازی</h2>';
		echo '<div class="zc-ladm-set-row"><label><input type="checkbox" name="enable_live_chat" value="1" ' . checked( 1, $on, false ) . '> چت زنده فعال باشد (دکمهٔ شناور و پنل روی همهٔ صفحات)</label></div></div>';

		echo '<div class="zc-ladm-set-card"><h2><i class="fa-solid fa-font"></i> متن‌ها</h2>';
		echo '<div class="zc-ladm-field"><span>عنوان پنل چت</span><input type="text" name="chat_title" value="' . esc_attr( (string) $st['chat_title'] ) . '"></div>';
		echo '<div class="zc-ladm-field"><span>خوش‌آمدگویی (متن دروازهٔ ورود)</span><textarea name="chat_greeting" rows="2">' . esc_textarea( (string) $st['chat_greeting'] ) . '</textarea></div>';
		echo '<div class="zc-ladm-field"><span>پیام حالت آفلاین</span><textarea name="chat_offline_msg" rows="2">' . esc_textarea( (string) $st['chat_offline_msg'] ) . '</textarea></div>';
		echo '<div class="zc-ladm-field"><span>متن جعبهٔ نوشتن</span><input type="text" name="chat_placeholder" value="' . esc_attr( (string) $st['chat_placeholder'] ) . '"></div>';
		echo '<div class="zc-ladm-field"><span>رنگ تم چت (CSS)</span><input type="color" name="chat_accent" value="' . esc_attr( (string) $st['chat_accent'] ) . '"> <span class="description">دکمه‌ها و حباب‌های گفتگو</span></div></div>';

		echo '<div class="zc-ladm-set-card"><h2><i class="fa-solid fa-id-card"></i> فیلدهای فرم شروع گفتگو</h2><div class="zc-ladm-set-row">';
		echo '<label><input type="checkbox" name="chat_field_name" value="1" ' . checked( 1, $st['chat_field_name'], false ) . '> نام (در صورت غیرفعال، «مهمان» ثبت می‌شود)</label>';
		echo '<label><input type="checkbox" name="chat_field_phone" value="1" ' . checked( 1, $st['chat_field_phone'], false ) . '> شماره موبایل (تاریخچه یکپارچه می‌شود)</label>';
		echo '<label><input type="checkbox" name="chat_field_website" value="1" ' . checked( 1, $st['chat_field_website'], false ) . '> وبسایت (اختیاری)</label>';
		echo '</div></div>';

		echo '<div class="zc-ladm-set-card"><h2><i class="fa-solid fa-paperclip"></i> ارسال فایل</h2><div class="zc-ladm-set-row">';
		echo '<label><input type="checkbox" name="chat_allow_photo" value="1" ' . checked( 1, $st['chat_allow_photo'], false ) . '> عکس</label>';
		echo '<label><input type="checkbox" name="chat_allow_video" value="1" ' . checked( 1, $st['chat_allow_video'], false ) . '> ویدیو</label>';
		echo '<label><input type="checkbox" name="chat_allow_voice" value="1" ' . checked( 1, $st['chat_allow_voice'], false ) . '> ویس (ضبط صدا)</label>';
		echo '</div>';
		echo '<div class="zc-ladm-field" style="max-width:220px;"><span>حداکثر حجم هر فایل (۱ تا ۶۴ مگابایت)</span><input type="number" name="chat_max_mb" min="1" max="64" value="' . (int) $st['chat_max_mb'] . '"></div></div>';

		echo '<div class="zc-ladm-set-card"><h2><i class="fa-regular fa-bell"></i> اعلان‌ها</h2>';
		echo '<div class="zc-ladm-set-row" style="flex-direction:column;align-items:flex-start;gap:8px;">';
		echo '<label><input type="checkbox" name="chat_sound" value="1" ' . checked( 1, $st['chat_sound'], false ) . '> صدای اعلان برای بازدیدکننده هنگام دریافت پاسخ (با دکمهٔ زنگ در سربرگ پنل بی‌صدا می‌شود)</label>';
		echo '<label><input type="checkbox" name="chat_notify_email" value="1" ' . checked( 1, $st['chat_notify_email'], false ) . '> ایمیل به مدیر برای هر پیام جدید (حداکثر هر ۵ دقیقه برای هر بازدیدکننده)</label>';
		echo '</div>';
		echo '<p class="description">در پیشخوان، روی پیام جدید زنگ پخش می‌شود و اعلان مرورگر نمایش داده می‌شود؛ در تب‌های بسته عنوان تب با شمارنده چشمک می‌زند. حضور شما (آنلاین/آفلاین) با ۹۰ ثانیه آخرین فعالیت در پیشخوان تشخیص داده می‌شود.</p></div>';

		echo '<div class="zc-ladm-set-foot"><button type="submit" name="zc_chat_settings_save" value="1" class="button button-primary"><i class="fa-solid fa-check"></i> ذخیرهٔ تنظیمات</button><span class="description">پیام‌ها در پیشخوان ← زرین‌پرس ← گفتگوهای زنده پاسخ داده می‌شوند.</span></div>';
		echo '</form></div>';
	}
}

