<?php
/**
 * Admin screens: «ایندکس و سئو» + «اسکن هوش مصنوعی» (v4.8.0).
 *
 * Follows the WordPress settings API (options.php + nonce + capability), stores
 * everything in one option, and ships its CSS inline so the admin loads no extra
 * request. Front-end visitors never touch this file.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Settings_Page {

	public const SLUG      = 'zarincode-seo';
	public const SLUG_SCAN = 'zarincode-seo-scan';
	public const GROUP     = 'zarincode_seo_group';

	/** Registers the menu, the option and the admin actions. */
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/** Top-level entry with two screens. */
	public static function menu(): void {
		$cap   = 'manage_options';
		$score = (int) Store::get( 'last_score', 0 );
		$badge = $score > 0 ? ' <span class="awaiting-mod zc-score-pill">' . $score . '</span>' : '';

		add_menu_page(
			'سئو و هوش مصنوعی',
			'سئو و هوش مصنوعی' . $badge,
			$cap,
			self::SLUG,
			array( __CLASS__, 'render_index' ),
			'dashicons-search',
			30
		);
		add_submenu_page( self::SLUG, 'ایندکس و سئو', 'ایندکس و سئو', $cap, self::SLUG, array( __CLASS__, 'render_index' ) );
		add_submenu_page( self::SLUG, 'اسکن هوش مصنوعی', 'اسکن هوش مصنوعی', $cap, self::SLUG_SCAN, array( __CLASS__, 'render_scan' ) );
	}

	/** Registers the option with a merge-aware sanitiser. */
	public static function register(): void {
		register_setting(
			self::GROUP,
			Store::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_option' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Merges the submitted tab over the stored values, so saving one screen never
	 * wipes the other. Checkboxes post a hidden `no` first, so `yes`/`no` always
	 * arrive and unchecking works.
	 */
	public static function sanitize_option( $input ): array {
		$existing = get_option( Store::OPTION, array() );
		$existing = is_array( $existing ) ? $existing : array();
		$input    = is_array( $input ) ? $input : array();

		$clean = array();
		foreach ( $input as $key => $value ) {
			if ( in_array( $key, array( 'last_scan', 'last_score' ), true ) ) {
				continue; // never settable from a form
			}
			$clean[ $key ] = Store::sanitize( (string) $key, $value );
		}
		$merged = array_merge( Store::defaults(), $existing, $clean );

		// IndexNow: give every site a compliant key without asking the owner.
		if ( 'yes' === ( $merged['indexnow'] ?? 'no' ) && '' === (string) ( $merged['indexnow_key'] ?? '' ) ) {
			$merged['indexnow_key'] = Indexing::generate_key();
		}
		return $merged;
	}

	/** One-click actions: rebuild llms.txt / rescan / new key / manual ping. */
	public static function handle_actions(): void {
		$action = isset( $_GET['zc_action'] ) ? sanitize_key( (string) $_GET['zc_action'] ) : '';
		if ( '' === $action || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'zc_' . $action );

		switch ( $action ) {
			case 'rescan':
				Scanner::report( true );
				self::redirect( self::SLUG_SCAN, 'scan' );
			case 'llms':
				delete_transient( Llms::CACHE_KEY );
				Llms::body();
				self::redirect( self::SLUG, 'llms' );
			case 'newkey':
				Store::save( array( 'indexnow_key' => Indexing::generate_key(), 'indexnow' => 'yes' ) );
				self::redirect( self::SLUG, 'key' );
			case 'ping':
				Indexing::submit_now( array( home_url( '/' ), home_url( '/wp-sitemap.xml' ) ) );
				self::redirect( self::SLUG, 'ping' );
		}
	}

	/** Safe redirect back to an admin screen. */
	private static function redirect( string $page, string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				array( 'page' => $page, 'zc_notice' => $notice ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// =====================================================================
	// Shared chrome
	// =====================================================================

	/** Page shell with the design system. */
	private static function open( string $active, string $title, string $subtitle ): void {
		$tabs = array(
			self::SLUG      => array( 'ایندکس و سئو', 'dashicons-admin-site-alt3' ),
			self::SLUG_SCAN => array( 'اسکن هوش مصنوعی', 'dashicons-superhero' ),
		);
		?>
		<div class="wrap zc-seo">
			<?php self::styles(); ?>
			<div class="zc-seo-hero">
				<div class="zc-seo-hero-txt">
					<span class="zc-seo-kicker">زرین‌کد · نسخهٔ <?php echo esc_html( ZARINCODE_CORE_VERSION ); ?></span>
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</div>
				<div class="zc-seo-hero-meta">
					<?php if ( Indexing::owned() ) : ?>
						<span class="zc-chip zc-chip-warn">مدیریت متا: افزونهٔ سئوی دیگر</span>
					<?php else : ?>
						<span class="zc-chip zc-chip-ok">مدیریت متا: زرین‌کد</span>
					<?php endif; ?>
					<span class="zc-chip"><?php echo esc_html( home_url( '/' ) ); ?></span>
				</div>
			</div>

			<nav class="zc-seo-tabs">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
					<a class="<?php echo $slug === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
						<span class="dashicons <?php echo esc_attr( $tab[1] ); ?>"></span><?php echo esc_html( $tab[0] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<?php
			self::notices();
	}

	/** Closes the shell. */
	private static function close(): void {
		echo '<div class="zc-seo-foot">ساخته‌شده توسط تیم زرین‌کد — همهٔ پردازش‌ها سمت مدیر انجام می‌شود و هیچ هزینه‌ای به بازدیدکننده تحمیل نمی‌کند.</div></div>';
	}

	/** Small toast driven by ?zc_notice=. */
	private static function notices(): void {
		$map = array(
			'scan'  => array( 'ok', 'اسکن کامل شد؛ نتایج به‌روز شد.' ),
			'llms'  => array( 'ok', 'فایل llms.txt بازسازی شد.' ),
			'key'   => array( 'ok', 'کلید IndexNow تولید و فعال شد.' ),
			'ping'  => array( 'ok', 'درخواست IndexNow ارسال شد؛ نتیجه در جدول «تاریخچهٔ ارسال» ثبت شد.' ),
			'saved' => array( 'ok', 'تنظیمات ذخیره شد.' ),
		);
		if ( isset( $_GET['settings-updated'] ) ) {
			echo '<div class="zc-toast zc-toast-ok">تنظیمات ذخیره شد؛ قواعد robots.txt، llms.txt و فایل کلید IndexNow به‌روز شدند.</div>';
		}
		$key = isset( $_GET['zc_notice'] ) ? sanitize_key( (string) $_GET['zc_notice'] ) : '';
		if ( isset( $map[ $key ] ) ) {
			printf( '<div class="zc-toast zc-toast-%s">%s</div>', esc_attr( $map[ $key ][0] ), esc_html( $map[ $key ][1] ) );
		}
	}

	// =====================================================================
	// Screen 1 — indexing
	// =====================================================================

	/** Indexing settings screen. */
	public static function render_index(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o = Store::all();
		self::open( self::SLUG, 'ایندکس در موتورهای جستجو', 'robots، متا، نقشهٔ سایت، تأیید مالکیت و ارسال سریع — پیکربندی‌شده برای بیشترین دیده‌شدن با کمترین هزینه.' );

		$robots_url  = home_url( '/robots.txt' );
		$sitemap_url = home_url( '/wp-sitemap.xml' );
		$llms_url    = home_url( '/llms.txt' );
		$key         = (string) $o['indexnow_key'];
		$key_url     = $key ? home_url( '/' . $key . '.txt' ) : '';
		?>
		<div class="zc-seo-grid">
			<a class="zc-card zc-stat" href="<?php echo esc_url( $robots_url ); ?>" target="_blank" rel="noopener">
				<span class="zc-stat-k">robots.txt</span><span class="zc-stat-v"><?php echo $o['enabled'] === 'yes' ? 'فعال' : 'خاموش'; ?></span>
			</a>
			<a class="zc-card zc-stat" href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener">
				<span class="zc-stat-k">نقشهٔ سایت</span><span class="zc-stat-v"><?php echo $o['sitemap'] === 'yes' ? 'منتشرشده' : 'منتشرنشده'; ?></span>
			</a>
			<a class="zc-card zc-stat" href="<?php echo esc_url( $llms_url ); ?>" target="_blank" rel="noopener">
				<span class="zc-stat-k">llms.txt</span><span class="zc-stat-v"><?php echo $o['llms'] === 'yes' ? 'فعال' : 'خاموش'; ?></span>
			</a>
			<a class="zc-card zc-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_SCAN ) ); ?>">
				<span class="zc-stat-k">امتیاز آخرین اسکن</span><span class="zc-stat-v"><bdi dir="ltr"><?php echo (int) $o['last_score']; ?>/100</bdi></span>
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="zc-seo-form">
			<?php settings_fields( self::GROUP ); ?>
			<input type="hidden" name="<?php echo esc_attr( Store::OPTION ); ?>[__tab]" value="index" />

			<div class="zc-card">
				<h2><span class="dashicons dashicons-shield-alt"></span> تنظیمات پایه</h2>
				<p class="zc-card-sub">با روشن بودن این بخش، تگ‌های ایندکس فقط زمانی چاپ می‌شوند که افزونهٔ سئوی دیگری مدیریت صفحه را در دست نداشته باشد.</p>
				<?php
				self::switch_row( 'enabled', 'فعال‌سازی بخش ایندکس', 'کل تگ‌های این صفحه (تأیید مالکیت، فلگ‌های robots، اسکیما) را کنترل می‌کند.', $o );
				self::switch_row( 'sitemap', 'معرفی نقشهٔ سایت در robots.txt', 'خط Sitemap به robots.txt و llms.txt اضافه می‌شود.', $o );
				?>
			</div>

			<div class="zc-card">
				<h2><span class="dashicons dashicons-awards"></span> تأیید مالکیت (Search Console / Bing / Yandex)</h2>
				<p class="zc-card-sub">کل توکن یا کل تگ HTML را بچسبانید؛ افزونه خودش مقدار <code>content</code> را استخراج می‌کند.</p>
				<div class="zc-field-grid">
					<?php
					self::text_row( 'search_console', 'Google Search Console', 'google-site-verification', $o, 'مثال: AbC123…' );
					self::text_row( 'bing', 'Bing Webmaster Tools', 'msvalidate.01', $o, 'مثال: 9F2A…' );
					self::text_row( 'yandex', 'Yandex Webmaster', 'yandex-verification', $o, 'مثال: 7d3f…' );
					self::text_row( 'baidu', 'Baidu', 'baidu-site-verification', $o, 'اختیاری' );
					self::text_row( 'pinterest', 'Pinterest', 'p:domain_verify', $o, 'اختیاری' );
					?>
				</div>
			</div>

			<div class="zc-card">
				<h2><span class="dashicons dashicons-visibility"></span> کنترل اسنیپت و پیش‌نمایش تصویر</h2>
				<p class="zc-card-sub">این مقادیر با <code>max-image-preview</code> و <code>max-snippet</code> به تگ robots اضافه می‌شوند و مستقیماً روی بزرگی کارت نتایج جستجو اثر دارند.</p>
				<div class="zc-field-grid">
					<label class="zc-field">
						<span class="zc-label">max-image-preview</span>
						<select name="<?php echo esc_attr( Store::OPTION ); ?>[max_image_preview]">
							<?php foreach ( array( 'large' => 'large — تصویر بزرگ (پیشنهاد)', 'standard' => 'standard — تصویر معمولی', 'none' => 'none — بدون تصویر' ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $o['max_image_preview'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="zc-field">
						<span class="zc-label">max-snippet</span>
						<select name="<?php echo esc_attr( Store::OPTION ); ?>[max_snippet]">
							<?php foreach ( array( '-1' => 'بی‌محدود (پیشنهاد)', '0' => '۰ کاراکتر', '50' => '۵۰', '100' => '۱۰۰', '150' => '۱۵۰', '200' => '۲۰۰' ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $o['max_snippet'], (string) $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			</div>

			<div class="zc-card">
				<h2><span class="dashicons dashicons-hidden"></span> حذف صفحات کم‌ارزش از ایندکس</h2>
				<p class="zc-card-sub">جلوگیری از ایندکس صفحات تکراری، اعتبار صفحات اصلی را بالا می‌برد. پیوندها همچنان دنبال می‌شوند (<code>follow</code>).</p>
				<div class="zc-switch-grid">
					<?php
					self::switch_row( 'noindex_search', 'نتایج جستجوی داخلی', '', $o );
					self::switch_row( 'noindex_author', 'آرشیو نویسنده', '', $o );
					self::switch_row( 'noindex_date', 'آرشیو تاریخ', '', $o );
					self::switch_row( 'noindex_tags', 'آرشیو برچسب', 'به‌طور پیش‌فرض باز است؛ اگر برچسب‌های زیادی دارید روشن کنید.', $o );
					self::switch_row( 'noindex_media', 'صفحهٔ پیوست رسانه', '', $o );
					self::switch_row( 'noindex_paged_404', 'صفحهٔ دوم به بعد بلاگ', '', $o );
					?>
				</div>
			</div>

			<div class="zc-card">
				<h2><span class="dashicons dashicons-search"></span> IndexNow — ارسال فوری به Bing، Yandex، Naver، Seznam</h2>
				<p class="zc-card-sub">گوگل از IndexNow پشتیبانی نمی‌کند (از Sitemap و لینک‌ها استفاده می‌کند). درخواست‌ها با WP-Cron و ۶۰ ثانیه بعد از انتشار ارسال می‌شوند تا هرگز انتشار نوشته را کند نکنند.</p>
				<?php self::switch_row( 'indexnow', 'ارسال خودکار IndexNow', 'هنگام انتشار یا ویرایش نوشته/برگه/خدمت/محصول.', $o ); ?>
				<div class="zc-field-grid">
					<label class="zc-field zc-field-wide">
						<span class="zc-label">کلید IndexNow (۸ تا ۱۲۸ کاراکتر)</span>
						<input type="text" name="<?php echo esc_attr( Store::OPTION ); ?>[indexnow_key]" value="<?php echo esc_attr( $key ); ?>" placeholder="با دکمهٔ «کلید جدید» تولید کنید" />
						<?php if ( $key_url ) : ?>
							<span class="zc-hint">آدرس فایل کلید: <a href="<?php echo esc_url( $key_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $key_url ); ?></a></span>
						<?php endif; ?>
					</label>
				</div>
				<div class="zc-actions">
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => self::SLUG, 'zc_action' => 'newkey' ), admin_url( 'admin.php' ) ), 'zc_newkey' ) ); ?>">تولید کلید جدید</a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => self::SLUG, 'zc_action' => 'ping' ), admin_url( 'admin.php' ) ), 'zc_ping' ) ); ?>">ارسال آزمایشی صفحهٔ اصلی</a>
				</div>
				<?php self::indexnow_log(); ?>
			</div>

			<div class="zc-card">
				<h2><span class="dashicons dashicons-editor-code"></span> کدهای پیشرفته</h2>
				<p class="zc-card-sub">فقط تگ‌های <code>&lt;meta&gt;</code>، <code>&lt;link&gt;</code> و JSON-LD مجاز است؛ اسکریپت، iframe و رویدادهای درون‌خطی حذف می‌شوند.</p>
				<label class="zc-field zc-field-wide">
					<span class="zc-label">کد اضافی در &lt;head&gt;</span>
					<textarea rows="4" name="<?php echo esc_attr( Store::OPTION ); ?>[extra_head]" placeholder='&lt;meta name="…" content="…" /&gt;'><?php echo esc_textarea( (string) $o['extra_head'] ); ?></textarea>
				</label>
				<label class="zc-field zc-field-wide">
					<span class="zc-label">قواعد اضافی robots.txt</span>
					<textarea rows="4" name="<?php echo esc_attr( Store::OPTION ); ?>[robots_extra]" placeholder="<?php echo esc_attr( "Disallow: /wp-content/uploads/private/\nAllow: /wp-content/uploads/" ); ?>"><?php echo esc_textarea( (string) $o['robots_extra'] ); ?></textarea>
					<span class="zc-hint">فقط دستورهای استاندارد (User-agent، Allow، Disallow، Crawl-delay، Sitemap) ذخیره می‌شوند.</span>
				</label>
			</div>

			<div class="zc-card zc-savebar">
				<?php submit_button( 'ذخیرهٔ تنظیمات ایندکس', 'primary', 'submit', false ); ?>
				<span class="zc-hint">پس از ذخیره، قواعد بازنویسی برای llms.txt و فایل کلید IndexNow به‌صورت خودکار تازه می‌شوند.</span>
			</div>
		</form>

		<div class="zc-card zc-card-soft">
			<h2><span class="dashicons dashicons-info-outline"></span> راهنمای سریع</h2>
			<ol class="zc-guide">
				<li>توکن Search Console را از بخش «HTML tag» بردارید و در فیلد مربوطه بچسبانید؛ سپس در همان صفحه دکمهٔ Verify را بزنید.</li>
				<li>نقشهٔ سایت و robots.txt بلافاصله بعد از ذخیره قابل استفاده‌اند؛ در Search Console بخش Sitemaps آدرس <code>/wp-sitemap.xml</code> را ثبت کنید.</li>
				<li>IndexNow را روشن کنید تا هر انتشار به Bing و Yandex اعلام شود؛ وضعیت در «تاریخچهٔ ارسال» دیده می‌شود.</li>
				<li>برای بررسی دیده‌شدن در ChatGPT/Perplexity/Gemini، تب «اسکن هوش مصنوعی» را اجرا کنید.</li>
			</ol>
		</div>
		<?php
		self::close();
	}

	/** IndexNow history table. */
	private static function indexnow_log(): void {
		$log = (array) get_option( 'zarincode_indexnow_log', array() );
		echo '<h3 class="zc-h3">تاریخچهٔ ارسال</h3>';
		if ( ! $log ) {
			echo '<p class="zc-hint">هنوز درخواستی ارسال نشده است.</p>';
			return;
		}
		echo '<table class="widefat striped zc-table"><thead><tr><th>زمان</th><th>تعداد URL</th><th>نتیجه</th></tr></thead><tbody>';
		foreach ( array_slice( $log, 0, 8 ) as $row ) {
			$code = (int) ( $row['code'] ?? 0 );
			$ok   = in_array( $code, array( 200, 202 ), true );
			printf(
				'<tr><td>%s</td><td>%d</td><td><span class="zc-badge %s">%s</span> %s</td></tr>',
				esc_html( wp_date( 'Y-m-d H:i', (int) ( $row['time'] ?? 0 ) ) ),
				(int) ( $row['count'] ?? 0 ),
				$ok ? 'is-pass' : 'is-warn',
				esc_html( (string) $code ),
				esc_html( Indexing::code_note( $code ) )
			);
		}
		echo '</tbody></table>';
	}

	// =====================================================================
	// Screen 2 — AI scan
	// =====================================================================

	/** Scanner screen. */
	public static function render_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o      = Store::all();
		$report = Scanner::report();
		$rescan = wp_nonce_url( add_query_arg( array( 'page' => self::SLUG_SCAN, 'zc_action' => 'rescan' ), admin_url( 'admin.php' ) ), 'zc_rescan' );

		self::open( self::SLUG_SCAN, 'سایت در نگاه هوش مصنوعی', 'اسکنر، همان چیزی را می‌بیند که خزنده‌های Google و ChatGPT می‌بینند: دسترسی، ساختار، اسکیما و آمادگی نقل‌قول.' );
		?>
		<div class="zc-scan-top">
			<div class="zc-score-card">
				<div class="zc-ring" style="--p:<?php echo (int) $report['score']; ?>">
					<div class="zc-ring-in">
						<strong><bdi dir="ltr"><?php echo (int) $report['score']; ?></bdi></strong>
						<span>از ۱۰۰</span>
					</div>
				</div>
				<div class="zc-score-txt">
					<span class="zc-grade">امتیاز <?php echo esc_html( $report['grade'][0] ); ?></span>
					<h2><?php echo esc_html( $report['grade'][1] ); ?></h2>
					<p class="zc-hint">آخرین اسکن: <?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $report['time'] ) ); ?> — نتیجه ۱ ساعت کش می‌شود.</p>
					<div class="zc-counts">
						<span class="zc-badge is-pass">قبول <?php echo (int) $report['counts']['pass']; ?></span>
						<span class="zc-badge is-warn">هشدار <?php echo (int) $report['counts']['warn']; ?></span>
						<span class="zc-badge is-fail">رد <?php echo (int) $report['counts']['fail']; ?></span>
					</div>
					<div class="zc-actions">
						<a class="button button-primary" href="<?php echo esc_url( $rescan ); ?>">اسکن مجدد سایت</a>
						<a class="button" href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener">مشاهدهٔ llms.txt</a>
					</div>
				</div>
			</div>

			</div>

			<div class="zc-group-grid">
				<?php foreach ( $report['groups'] as $key => $group ) : ?>
					<div class="zc-card zc-mini">
						<span class="zc-mini-label"><?php echo esc_html( $group['label'] ); ?></span>
						<span class="zc-mini-score <?php echo self::score_class( (int) $group['score'] ); ?>"><?php echo (int) $group['score']; ?></span>
						<div class="zc-bar"><i style="width:<?php echo (int) $group['score']; ?>%"></i></div>
						<span class="zc-hint"><?php echo (int) $group['items']; ?> بررسی</span>
					</div>
				<?php endforeach; ?>
			</div>

		<div class="zc-card">
			<h2><span class="dashicons dashicons-format-quote"></span> سایت در پاسخ هوش مصنوعی چه شکلی است؟</h2>
			<p class="zc-card-sub">این پیش‌نمایش از HTML عمومی استخراج شده — همان چیزی که یک مدل زبانی می‌تواند بخواند و نقل کند.</p>
			<div class="zc-preview">
				<div class="zc-preview-head">
					<div class="zc-preview-name"><?php echo esc_html( $report['preview']['name'] ); ?></div>
					<div class="zc-preview-url"><?php echo esc_html( $report['preview']['canonical'] ?: home_url( '/' ) ); ?></div>
				</div>
				<div class="zc-preview-title"><?php echo esc_html( $report['preview']['title'] ); ?></div>
				<div class="zc-preview-desc"><?php echo esc_html( $report['preview']['description'] ); ?></div>
				<div class="zc-preview-sample"><?php echo esc_html( mb_substr( $report['preview']['sample'], 0, 320 ) ); ?>…</div>
				<div class="zc-preview-meta">
					<span class="zc-badge"><?php echo (int) $report['preview']['words']; ?> کلمهٔ قابل استخراج</span>
					<?php foreach ( $report['preview']['types'] as $type ) : ?>
						<span class="zc-badge is-pass"><?php echo esc_html( $type ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="zc-card">
			<h2><span class="dashicons dashicons-share-alt2"></span> دسترسی خزنده‌های AI</h2>
			<p class="zc-card-sub">سیاست فعلی: <strong><?php echo esc_html( self::policy_label( (string) $o['ai_bot_policy'] ) ); ?></strong> — این فهرست را می‌توانید در تب «ایندکس» و بخش AI خشک‌کننده‌ها تغییر دهید.</p>
			<div class="zc-bot-grid">
				<?php foreach ( $report['bots']['retrieval'] as $bot ) : ?>
					<span class="zc-bot <?php echo in_array( $bot, $report['bots']['blocked'], true ) ? 'is-blocked' : 'is-open'; ?>">
						<em><?php echo in_array( $bot, $report['bots']['blocked'], true ) ? 'بسته' : 'باز'; ?></em><?php echo esc_html( $bot ); ?>
					</span>
				<?php endforeach; ?>
				<?php foreach ( $report['bots']['training'] as $bot ) : ?>
					<span class="zc-bot zc-bot-train <?php echo in_array( $bot, $report['bots']['blocked'], true ) ? 'is-blocked' : 'is-open'; ?>">
						<em><?php echo in_array( $bot, $report['bots']['blocked'], true ) ? 'بسته' : 'باز'; ?></em><?php echo esc_html( $bot ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		</div>

		<?php foreach ( $report['groups'] as $key => $group ) : ?>
			<div class="zc-card">
				<h2><?php echo esc_html( $group['label'] ); ?> <span class="zc-badge <?php echo self::score_class( (int) $group['score'] ); ?>"><bdi dir="ltr"><?php echo (int) $group['score']; ?>/100</bdi></span></h2>
				<table class="widefat zc-table zc-checks">
					<tbody>
					<?php foreach ( $report['checks'] as $check ) : ?>
						<?php if ( $check['group'] !== $key ) { continue; } ?>
						<tr class="zc-row-<?php echo esc_attr( $check['status'] ); ?>">
							<td class="zc-status-cell"><span class="zc-dot is-<?php echo esc_attr( $check['status'] ); ?>"></span></td>
							<td class="zc-check-main">
								<strong><?php echo esc_html( $check['title'] ); ?></strong>
								<span class="zc-detail"><?php echo esc_html( $check['detail'] ); ?></span>
								<?php if ( '' !== $check['fix'] && 'pass' !== $check['status'] ) : ?>
									<span class="zc-fix"><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html( $check['fix'] ); ?></span>
								<?php endif; ?>
							</td>
							<td class="zc-weight">وزن <?php echo (int) $check['weight']; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="zc-seo-form">
			<?php settings_fields( self::GROUP ); ?>
			<input type="hidden" name="<?php echo esc_attr( Store::OPTION ); ?>[__tab]" value="ai" />
			<div class="zc-card">
				<h2><span class="dashicons dashicons-robot"></span> سیاست خزنده‌های AI و llms.txt</h2>
				<div class="zc-field-grid">
					<label class="zc-field">
						<span class="zc-label">سیاست خزنده‌ها</span>
						<select name="<?php echo esc_attr( Store::OPTION ); ?>[ai_bot_policy]">
							<?php foreach ( array( 'all' => 'همه مجاز (بیشترین دیده‌شدن)', 'nofeed' => 'خزش استناد مجاز، آموزش مسدود', 'block' => 'همه خزنده‌های AI مسدود' ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $o['ai_bot_policy'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="zc-hint">بستن Google-Extended استناد در Gemini را قطع می‌کند، اما روی AI Overviews اثری ندارد.</span>
					</label>
					<?php self::switch_row( 'llms', 'ساخت /llms.txt', 'فهرست گزیده و کش‌شده برای عامل‌های AI.', $o ); ?>
					<?php self::switch_row( 'ai_sections', 'اسکیماهای تکمیلی', 'BreadcrumbList، BlogPosting و Organization.', $o ); ?>
					<?php self::switch_row( 'org_schema', 'اسکیمای سازمان (پشتیبان)', 'فقط وقتی اسکیمای قالب خاموش باشد چاپ می‌شود؛ هرگز تکراری نیست.', $o ); ?>
					<?php self::switch_row( 'breadcrumb_schema', 'اسکیمای مسیر راهنما', '', $o ); ?>
					<?php self::switch_row( 'article_schema', 'اسکیمای مقاله (پشتیبان)', 'اگر قالب خودش BlogPosting چاپ کند، این مورد غیرفعال می‌ماند.', $o ); ?>
				</div>
				<div class="zc-field-grid">
					<label class="zc-field">
						<span class="zc-label">آدرس لوگوی سازمان</span>
						<input type="url" name="<?php echo esc_attr( Store::OPTION ); ?>[org_logo]" value="<?php echo esc_attr( (string) $o['org_logo'] ); ?>" placeholder="https://…/logo.png" />
					</label>
					<label class="zc-field">
						<span class="zc-label">ایمیل یا تلفن برای AI</span>
						<input type="text" name="<?php echo esc_attr( Store::OPTION ); ?>[ai_contact]" value="<?php echo esc_attr( (string) $o['ai_contact'] ); ?>" placeholder="info@example.com" />
					</label>
					<label class="zc-field">
						<span class="zc-label">نشانی شرایط استفاده از محتوا</span>
						<input type="url" name="<?php echo esc_attr( Store::OPTION ); ?>[ai_license_url]" value="<?php echo esc_attr( (string) $o['ai_license_url'] ); ?>" placeholder="https://…/terms" />
					</label>
					<label class="zc-field zc-field-wide">
						<span class="zc-label">شبکه‌های اجتماعی برای sameAs (هر خط یک آدرس)</span>
						<textarea rows="3" name="<?php echo esc_attr( Store::OPTION ); ?>[org_same_as]"><?php echo esc_textarea( (string) $o['org_same_as'] ); ?></textarea>
					</label>
					<label class="zc-field zc-field-wide">
						<span class="zc-label">توضیح یک‌خطی سایت برای AI (در llms.txt و اسکیما)</span>
						<textarea rows="2" name="<?php echo esc_attr( Store::OPTION ); ?>[ai_summary]" placeholder="مثال: زرین‌کد استودیوی طراحی سایت و توسعهٔ نرم‌افزار در تهران…"><?php echo esc_textarea( (string) $o['ai_summary'] ); ?></textarea>
					</label>
					<label class="zc-field zc-field-wide">
						<span class="zc-label">یادداشت‌های سایت (Markdown، پایان llms.txt)</span>
						<textarea rows="3" name="<?php echo esc_attr( Store::OPTION ); ?>[ai_extra]" placeholder="- محدودهٔ خدمات: …"><?php echo esc_textarea( (string) $o['ai_extra'] ); ?></textarea>
					</label>
				</div>
				<div class="zc-actions">
					<?php submit_button( 'ذخیره', 'primary', 'submit', false ); ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => self::SLUG, 'zc_action' => 'llms' ), admin_url( 'admin.php' ) ), 'zc_llms' ) ); ?>">بازسازی llms.txt</a>
				</div>
			</div>
		</form>

		<div class="zc-card zc-card-soft">
			<h2><span class="dashicons dashicons-editor-help"></span> نتیجه چطور خوانده می‌شود؟</h2>
			<ul class="zc-guide">
				<li><strong>ایندکس‌پذیری</strong>: اگر اینجا قرمز باشد، هیچ اصلاح محتوایی فایده ندارد. اول Search Console و robots.</li>
				<li><strong>داده‌ساختاریافته</strong>: موجودیت برند و مقاله، ستون فقرات استناد در ChatGPT و Gemini است.</li>
				<li><strong>دیده‌شدن در AI</strong>: دسترسی خزنده + متن خام کافی + llms.txt. محتوایی که فقط با جاوااسکریپت ساخته شود برای بیشتر خزنده‌ها نامرئی است.</li>
				<li><strong>سرعت</strong>: هیچ‌کدام از این تنظیمات به بازدیدکننده فایل یا کوئری اضافه نمی‌کند؛ حجم HTML و TTFB را هم می‌سنجیم تا مطمئن شویم همان‌طور مانده است.</li>
			</ul>
		</div>
		<?php
		self::close();
	}

	// =====================================================================
	// Field helpers
	// =====================================================================

	/** Text input row. */
	private static function text_row( string $key, string $label, string $name, array $o, string $placeholder = '' ): void {
		printf(
			'<label class="zc-field"><span class="zc-label">%s</span><input type="text" name="%s[%s]" value="%s" placeholder="%s" /><span class="zc-hint">&lt;meta name="%s"&gt;</span></label>',
			esc_html( $label ),
			esc_attr( Store::OPTION ),
			esc_attr( $key ),
			esc_attr( (string) ( $o[ $key ] ?? '' ) ),
			esc_attr( $placeholder ),
			esc_html( $name )
		);
	}

	/** Switch row with a hidden `no` so unchecking is saved. */
	private static function switch_row( string $key, string $label, string $help, array $o ): void {
		$id = 'zc-' . $key;
		printf(
			'<div class="zc-switch"><label for="%1$s"><input type="hidden" name="%2$s[%1$s]" value="no" /><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="yes" %3$s /><span class="zc-switch-track"><i></i></span><span class="zc-switch-label">%4$s%5$s</span></label></div>',
			esc_attr( $key ),
			esc_attr( Store::OPTION ),
			checked( 'yes', (string) ( $o[ $key ] ?? 'no' ), false ),
			esc_html( $label ),
			'' !== $help ? '<em>' . esc_html( $help ) . '</em>' : ''
		);
	}

	/** Class for a score. */
	private static function score_class( int $score ): string {
		if ( $score >= 80 ) {
			return 'is-pass';
		}
		return $score >= 55 ? 'is-warn' : 'is-fail';
	}

	/** Human label for the bot policy. */
	private static function policy_label( string $policy ): string {
		$map = array(
			'all'    => 'همه خزنده‌های AI مجاز',
			'nofeed' => 'خزنده‌های استناد مجاز، خزنده‌های آموزش مسدود',
			'block'  => 'همه خزنده‌های AI مسدود',
		);
		return $map[ $policy ] ?? 'همه خزنده‌های AI مجاز';
	}

	/** Design system for both screens (inline: no extra HTTP request). */
	private static function styles(): void {
		?>
		<style>
		.zc-seo{--zc-ink:#16181d;--zc-soft:#5b6270;--zc-line:#e6e8ee;--zc-bg:#f6f7fb;--zc-glass:#fff;--zc-accent:#1f6feb;--zc-accent2:#6f42c1;--zc-ok:#12805c;--zc-ok-bg:#e6f6ef;--zc-warn:#9a6700;--zc-warn-bg:#fff7e0;--zc-fail:#b42318;--zc-fail-bg:#fdecea;font-family:Vazirmatn,"Segoe UI",Tahoma,sans-serif;direction:rtl;margin:0 20px 40px 0;max-width:1240px;color:var(--zc-ink)}
		.zc-seo *{box-sizing:border-box}
		.zc-seo h1,.zc-seo h2,.zc-seo h3{margin:0;line-height:1.5}
		.zc-seo-hero{position:relative;overflow:hidden;border-radius:22px;padding:26px 28px;margin:18px 0 16px;color:#fff;background:radial-gradient(120% 140% at 85% 0%,#3b2fd1 0%,#1a1a2e 45%,#0d0d15 100%);box-shadow:0 18px 40px -22px rgba(20,20,50,.6);display:flex;justify-content:space-between;gap:24px;align-items:center;flex-wrap:wrap}
		.zc-seo-hero:after{content:"";position:absolute;inset:auto -80px -140px auto;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,#fdd05b55,transparent 65%)}
		.zc-seo-kicker{font-size:11px;letter-spacing:.08em;text-transform:uppercase;opacity:.72}
		.zc-seo-hero h1{font-size:24px;font-weight:800;margin:6px 0 6px;color:#fff}
		.zc-seo-hero p{margin:0;font-size:13px;max-width:640px;opacity:.82;line-height:1.9}
		.zc-seo-hero-meta{display:flex;flex-direction:column;gap:8px;align-items:flex-end;position:relative;z-index:1}
		.zc-chip{display:inline-block;font-size:11px;padding:5px 11px;border-radius:999px;background:#ffffff1f;color:#fff;border:1px solid #ffffff2e;direction:ltr}
		.zc-chip-ok{background:#1d9e6f33;border-color:#1d9e6f66}
		.zc-chip-warn{background:#f0b42933;border-color:#f0b42966}
		.zc-seo-tabs{display:flex;gap:8px;margin:0 0 16px;flex-wrap:wrap}
		.zc-seo-tabs a{display:inline-flex;align-items:center;gap:7px;padding:10px 16px;border-radius:12px;background:#fff;border:1px solid var(--zc-line);color:var(--zc-soft);text-decoration:none;font-size:13px;font-weight:600;transition:.18s}
		.zc-seo-tabs a:hover{color:var(--zc-accent);border-color:#c9d7f5}
		.zc-seo-tabs a.is-active{background:linear-gradient(135deg,#1f2a44,#0f1424);color:#fff;border-color:transparent;box-shadow:0 10px 22px -14px #0f1424}
		.zc-seo-tabs .dashicons{font-size:16px;width:16px;height:16px}
		.zc-card{background:var(--zc-glass);border:1px solid var(--zc-line);border-radius:18px;padding:20px 22px;margin:0 0 16px;box-shadow:0 1px 2px #1018280a}
		.zc-card-soft{background:linear-gradient(180deg,#fbfcff,#f4f6fb)}
		.zc-card h2{font-size:15px;font-weight:800;display:flex;align-items:center;gap:9px;margin-bottom:10px}
		.zc-card h2 .dashicons{color:var(--zc-accent);font-size:18px;width:18px;height:18px}
		.zc-card-sub{font-size:12.5px;color:var(--zc-soft);margin:0 0 14px;line-height:1.9}
		.zc-seo-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
		.zc-stat{display:flex;flex-direction:column;gap:6px;text-decoration:none;color:inherit;transition:.18s;background:#fff}
		.zc-stat:hover{transform:translateY(-2px);box-shadow:0 14px 26px -20px #101828}
		.zc-stat-k{font-size:11.5px;color:var(--zc-soft)}
		.zc-stat-v{font-size:17px;font-weight:800;unicode-bidi:plaintext}
		.zc-field-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
		.zc-field{display:flex;flex-direction:column;gap:6px}
		.zc-field-wide{grid-column:1/-1}
		.zc-label{font-size:12px;font-weight:700;color:#3c4350}
		.zc-field input[type=text],.zc-field input[type=url],.zc-field select,.zc-field textarea{border:1px solid var(--zc-line);border-radius:10px;padding:9px 12px;font-size:13px;background:#fbfcfe;width:100%;font-family:inherit;transition:.15s;unicode-bidi:plaintext}
		.zc-field input:focus,.zc-field select:focus,.zc-field textarea:focus{outline:none;border-color:#9db8f0;background:#fff;box-shadow:0 0 0 3px #1f6feb1a}
		.zc-field textarea{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;line-height:1.8;direction:ltr;text-align:left}
		.zc-hint{font-size:11px;color:var(--zc-soft);line-height:1.8}
		.zc-hint a{color:var(--zc-accent);word-break:break-all}
		.zc-switch-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px}
		.zc-switch label{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid var(--zc-line);border-radius:12px;cursor:pointer;background:#fbfcfe;transition:.15s}
		.zc-switch label:hover{border-color:#c9d7f5;background:#fff}
		.zc-switch input[type=checkbox]{position:absolute;opacity:0;pointer-events:none}
		.zc-switch-track{flex:0 0 auto;width:38px;height:21px;border-radius:999px;background:#d3d8e2;position:relative;transition:.18s;margin-top:1px}
		.zc-switch-track i{position:absolute;top:2px;right:2px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 1px 3px #0003;transition:.18s}
		.zc-switch input:checked + .zc-switch-track{background:linear-gradient(135deg,#1f9d6f,#12805c)}
		.zc-switch input:checked + .zc-switch-track i{transform:translateX(-17px)}
		.zc-switch-label{font-size:12.5px;font-weight:700;line-height:1.7}
		.zc-switch-label em{display:block;font-style:normal;font-weight:400;font-size:11px;color:var(--zc-soft);margin-top:2px}
		.zc-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px}
		.zc-savebar{display:flex;justify-content:space-between;align-items:center;gap:16px;position:sticky;bottom:12px;z-index:5;background:#fffffff2;backdrop-filter:blur(8px)}
		.zc-toast{border-radius:12px;padding:11px 15px;margin:0 0 14px;font-size:13px;border:1px solid;font-weight:600}
		.zc-toast-ok{background:var(--zc-ok-bg);border-color:#bfe6d5;color:var(--zc-ok)}
		.zc-guide{margin:0;padding-inline-start:20px;font-size:12.5px;color:#3c4350;line-height:2}
		.zc-guide li{margin-bottom:4px}
		.zc-table{border-radius:12px;overflow:hidden;margin-top:6px}
		.zc-table th{font-size:11.5px;color:var(--zc-soft);font-weight:700}
		.zc-table td{font-size:12.5px;vertical-align:top}
		/* scan */
		.zc-scan-top{display:flex;flex-direction:column;gap:14px;margin-bottom:16px}
		.zc-score-card{display:flex;gap:26px;align-items:center;background:linear-gradient(120deg,#fff,#fafbff);border:1px solid var(--zc-line);border-radius:18px;padding:22px 24px;box-shadow:0 1px 2px #1018280a}
		.zc-ring{--p:0;width:118px;height:118px;border-radius:50%;flex:0 0 auto;background:conic-gradient(#12805c calc(var(--p)*1%),#e8ebf2 0);display:grid;place-items:center;position:relative}
		.zc-ring:before{content:"";position:absolute;inset:9px;border-radius:50%;background:#fff}
		.zc-ring-in{position:relative;text-align:center;line-height:1.1}
		.zc-ring-in strong{font-size:30px;font-weight:800;display:block}
		.zc-ring-in span{font-size:10px;color:var(--zc-soft)}
		.zc-grade{font-size:11px;font-weight:800;color:var(--zc-accent);letter-spacing:.04em}
		.zc-score-txt h2{font-size:15px;margin:4px 0 6px}
		.zc-counts{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
		.zc-badge{display:inline-block;font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#eef1f7;color:#3c4350}
		.zc-badge.is-pass{background:var(--zc-ok-bg);color:var(--zc-ok)}
		.zc-badge.is-warn{background:var(--zc-warn-bg);color:var(--zc-warn)}
		.zc-badge.is-fail{background:var(--zc-fail-bg);color:var(--zc-fail)}
		.zc-group-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
		@media(max-width:1100px){.zc-group-grid{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}}
		.zc-mini{display:flex;flex-direction:column;gap:6px;margin:0;padding:14px 15px}
		.zc-mini-label{font-size:11.5px;color:var(--zc-soft);font-weight:700}
		.zc-mini-score{font-size:22px;font-weight:800;unicode-bidi:plaintext}
		.zc-mini-score.is-pass{color:var(--zc-ok)}.zc-mini-score.is-warn{color:var(--zc-warn)}.zc-mini-score.is-fail{color:var(--zc-fail)}
		.zc-bar{height:5px;border-radius:99px;background:#eef1f7;overflow:hidden}
		.zc-bar i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#1f9d6f,#12805c)}
		.zc-preview{border:1px solid var(--zc-line);border-radius:14px;padding:16px;background:linear-gradient(180deg,#fcfdff,#f6f8fc)}
		.zc-preview-name{font-weight:800;font-size:13px}
		.zc-preview-url{font-size:11px;color:var(--zc-ok);direction:ltr;text-align:left;overflow-wrap:anywhere}
		.zc-preview-title{font-size:16px;font-weight:700;color:#1a0dab;margin:8px 0 4px}
		.zc-preview-desc{font-size:12.5px;color:#3c4350;line-height:1.9}
		.zc-preview-sample{font-size:12px;color:var(--zc-soft);line-height:1.9;margin-top:10px;padding:10px 12px;border-radius:10px;background:#fff;border:1px dashed #dfe4ee}
		.zc-preview-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
		.zc-bot-grid{display:flex;gap:7px;flex-wrap:wrap}
		.zc-bot{display:inline-flex;align-items:center;gap:7px;font-size:11.5px;font-family:ui-monospace,monospace;padding:5px 10px;border-radius:999px;border:1px solid var(--zc-line);background:#fbfcfe;direction:ltr}
		.zc-bot em{font-style:normal;font-family:Vazirmatn,sans-serif;font-size:10px;padding:1px 6px;border-radius:999px}
		.zc-bot.is-open em{background:var(--zc-ok-bg);color:var(--zc-ok)}
		.zc-bot.is-blocked em{background:var(--zc-fail-bg);color:var(--zc-fail)}
		.zc-bot-train{opacity:.82}
		.zc-checks tr{border-bottom:1px solid #f0f2f7}
		.zc-checks tr:last-child{border-bottom:none}
		.zc-status-cell{width:26px;padding-top:14px}
		.zc-dot{display:block;width:9px;height:9px;border-radius:50%}
		.zc-dot.is-pass{background:#12805c;box-shadow:0 0 0 4px #12805c1a}
		.zc-dot.is-warn{background:#e0a800;box-shadow:0 0 0 4px #e0a8001a}
		.zc-dot.is-fail{background:#b42318;box-shadow:0 0 0 4px #b423181a}
		.zc-dot.is-skip{background:#98a2b3}
		.zc-check-main strong{display:block;font-size:13px;margin-bottom:2px}
		.zc-detail{display:block;font-size:11.5px;color:var(--zc-soft);line-height:1.85}
		.zc-fix{display:flex;gap:6px;align-items:flex-start;margin-top:5px;font-size:11.5px;color:#7a4b00;background:var(--zc-warn-bg);border-radius:8px;padding:6px 9px;line-height:1.8}
		.zc-fix .dashicons{font-size:14px;width:14px;height:14px;margin-top:2px;color:#b07d00}
		.zc-weight{width:64px;font-size:11px;color:#98a2b3;text-align:left}
		.zc-h3{font-size:12px;font-weight:800;margin:16px 0 6px;color:#3c4350}
		.zc-seo-foot{margin-top:18px;font-size:11px;color:#98a2b3;text-align:center}
		.zc-score-pill{vertical-align:middle}
		.zc-seo .button{border-radius:10px;font-size:12.5px;min-height:34px;line-height:2.2;padding:0 14px;font-family:inherit}
		.zc-seo .button-primary{background:linear-gradient(135deg,#2b6cf6,#1a49c4);border:none;box-shadow:0 10px 20px -12px #1a49c4}
		</style>
		<?php
	}
}
