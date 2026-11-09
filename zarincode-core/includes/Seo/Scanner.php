<?php
/**
 * Site scanner: how the site looks to search engines and to AI (v4.8.0).
 *
 * Runs entirely in the admin, on demand, and caches its verdict in a transient.
 * It crawls the *public* site (exactly what Googlebot / OAI-SearchBot sees)
 * rather than trusting internal state, then grades ~45 checks in five groups
 * and returns a pass / warn / fail line with a concrete fix for each.
 *
 * Nothing in this class is loaded on a front-end request: the admin page is the
 * only caller, so the scanner cannot cost a visitor a single byte.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Scanner {

	public const CACHE_KEY = 'zarincode_seo_scan';
	private const TTL     = HOUR_IN_SECONDS;
	private const UA      = 'Zarincode-Scanner/1.0 (+site audit)';

	/** Cached report, or a fresh one when forced / stale. */
	public static function report( bool $force = false ): array {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['checks'] ) ) {
				return $cached;
			}
		}

		$report = self::build();

		// Persist the verdict first: writing the option fires the module's cache
		// flush, which would otherwise delete the transient we just created.
		$store               = get_option( Store::OPTION, array() );
		$store               = is_array( $store ) ? $store : array();
		$store['last_scan']  = time();
		$store['last_score'] = (int) $report['score'];
		update_option( Store::OPTION, array_merge( Store::defaults(), $store ), true );

		set_transient( self::CACHE_KEY, $report, self::TTL );
		return $report;
	}

	/** Runs every probe and grades the result. */
	private static function build(): array {
		$home     = home_url( '/' );
		$page     = self::fetch( $home );
		$robots   = self::fetch( home_url( '/robots.txt' ) );
		$llms     = self::fetch( home_url( '/llms.txt' ) );
		$sitemap  = self::fetch( home_url( '/wp-sitemap.xml' ) );
		$html     = (string) ( $page['body'] ?? '' );

		$facts = self::extract( $html );

		// Schema checks that only exist on inner pages (breadcrumb, article) are
		// graded on a real published post instead of being marked "missing" on
		// the home page.
		$sample = self::sample_post();

		$checks = array();
		$add    = static function ( string $group, string $id, string $title, string $status, int $weight, string $detail, string $fix = '' ) use ( &$checks ) {
			$checks[] = array(
				'group'  => $group,
				'id'     => $id,
				'title'  => $title,
				'status' => $status, // pass | warn | fail | skip
				'weight' => $weight,
				'detail' => $detail,
				'fix'    => $fix,
			);
		};

		// ------------------------------------------------------------------
		// 1. Indexability
		// ------------------------------------------------------------------
		$public = (int) get_option( 'blog_public' ) === 1;
		$add( 'index', 'blog_public', 'اجازهٔ ایندکس شدن سایت در وردپرس', $public ? 'pass' : 'fail', 6,
			$public ? 'گزینهٔ «تشویق موتورهای جستجو» فعال است.' : 'گزینهٔ «جلوگیری از ایندکس شدن توسط موتورهای جستجو» روشن است؛ هیچ صفحه‌ای ایندکس نمی‌شود.',
			$public ? '' : 'تنظیمات ← خواندن ← تیک «جلوگیری از ایندکس…» را بردارید.' );

		$https = str_starts_with( $home, 'https://' );
		$add( 'index', 'https', 'HTTPS روی دامنهٔ اصلی', $https ? 'pass' : 'warn', 4,
			$https ? 'دامنه با HTTPS سرو می‌شود.' : 'دامنه با HTTP سرو می‌شود (در محیط آزمایشی طبیعی است).',
			$https ? '' : 'SSL را فعال و آدرس سایت را به https تغییر دهید.' );

		$add( 'index', 'home_status', 'پاسخ صفحهٔ اصلی', self::status_of( $page, 200 ), 5,
			( $page['error'] ?? '' ) ?: ( 'کد پاسخ: ' . ( $page['code'] ?? 0 ) ),
			( $page['code'] ?? 0 ) === 200 ? '' : 'خطای سرور یا مسدودی لوپ‌بک را بررسی کنید.' );

		$robots_ok = ( $robots['code'] ?? 0 ) === 200 && '' !== trim( (string) ( $robots['body'] ?? '' ) );
		$add( 'index', 'robots_txt', 'فایل robots.txt در دسترس است', $robots_ok ? 'pass' : 'fail', 4,
			$robots_ok ? 'robots.txt با کد ۲۰۰ دریافت شد.' : 'robots.txt دریافت نشد یا خالی است (کد ' . ( $robots['code'] ?? 0 ) . ').',
			$robots_ok ? '' : 'در افزونه بخش «ایندکس» را فعال کنید یا فایل robots.txt را در ریشهٔ سایت قرار دهید.' );

		$robots_body = (string) ( $robots['body'] ?? '' );
		$blocked_all = self::star_group_blocks_all( $robots_body );
		$add( 'index', 'robots_root', 'robots.txt مسیر اصلی را نبسته باشد', $robots_body === '' ? 'skip' : ( $blocked_all ? 'fail' : 'pass' ), 5,
			$robots_body === '' ? 'برای بررسی، robots.txt لازم است.' : ( $blocked_all ? 'گروه «User-agent: *» با «Disallow: /» کل سایت را می‌بندد.' : 'مسیر / برای خزنده‌ها باز است.' ),
			$blocked_all ? 'خط Disallow: / را از گروه * حذف کنید.' : '' );

		$sitemap_in_robots = $robots_body !== '' && (bool) preg_match( '/^\s*Sitemap:\s*\S+/mi', $robots_body );
		$add( 'index', 'sitemap_declared', 'نقشهٔ سایت در robots.txt معرفی شده', $sitemap_in_robots ? 'pass' : ( $robots_body === '' ? 'skip' : 'fail' ), 3,
			$sitemap_in_robots ? 'خط Sitemap در robots.txt موجود است.' : 'خط Sitemap یافت نشد.',
			$sitemap_in_robots ? '' : 'در تنظیمات افزونه، گزینهٔ «انتشار نقشهٔ سایت» را روشن کنید.' );

		$sm_body   = (string) ( $sitemap['body'] ?? '' );
		$is_index  = false !== strpos( $sm_body, '<sitemapindex' );
		$locs      = preg_match_all( '/<loc>/i', $sm_body );
		$urls      = $is_index ? 0 : substr_count( $sm_body, '<url>' );
		$sub_count = 0;
		if ( $is_index && preg_match_all( '#<loc>([^<]+)</loc>#i', $sm_body, $m ) ) {
			$sub_count = count( $m[1] );
			$first     = self::fetch( html_entity_decode( $m[1][0], ENT_QUOTES, 'UTF-8' ) );
			$urls      = substr_count( (string) ( $first['body'] ?? '' ), '<url>' );
		}
		$sitemap_total = $is_index ? $sub_count . ' زیرنقشه / ' . $urls . ' URL در نمونهٔ اول' : $urls . ' URL';
		$add( 'index', 'sitemap_ok', 'نقشهٔ سایت قابل خواندن است', ( $sitemap['code'] ?? 0 ) === 200 && $locs > 0 ? 'pass' : 'fail', 4,
			( $sitemap['code'] ?? 0 ) === 200 ? $sitemap_total : 'دریافت نقشهٔ سایت ناموفق (کد ' . ( $sitemap['code'] ?? 0 ) . ').',
			( $sitemap['code'] ?? 0 ) === 200 ? '' : 'تنظیمات ← پیوندهای یکتا را یک‌بار ذخیره کنید تا نقشهٔ وردپرس بازسازی شود.' );

		$lastmod = preg_match_all( '/<lastmod>/i', $sm_body );
		$add( 'index', 'sitemap_lastmod', 'نقشهٔ سایت تاریخ به‌روزرسانی دارد', $lastmod > 0 ? 'pass' : 'warn', 1,
			$lastmod > 0 ? 'تعداد lastmod: ' . $lastmod : 'بدون lastmod، خزنده اولویت به‌روزرسانی را نمی‌فهمد.',
			$lastmod > 0 ? '' : 'هنگام انتشار، تاریخ ویرایش را به‌روز نگه دارید.' );

		$noindex_home = (bool) preg_match( '/<meta[^>]+name=["\']robots["\'][^>]*content=["\'][^"\']*noindex/i', $html );
		$add( 'index', 'home_indexable', 'صفحهٔ اصلی noindex نیست', $html === '' ? 'skip' : ( $noindex_home ? 'fail' : 'pass' ), 6,
			$html === '' ? 'صفحهٔ اصلی دریافت نشد.' : ( $noindex_home ? 'روی صفحهٔ اصلی تگ noindex وجود دارد.' : 'صفحهٔ اصلی قابل ایندکس است.' ),
			$noindex_home ? 'افزونهٔ سئو یا تنظیم «noindex» را بررسی کنید.' : '' );

		$canonical = $facts['canonical'] ?? '';
		$add( 'index', 'canonical', 'تگ canonical در صفحهٔ اصلی', '' !== $canonical ? 'pass' : 'warn', 3,
			'' !== $canonical ? 'canonical: ' . $canonical : 'canonical یافت نشد.',
			'' !== $canonical ? '' : 'با فعال کردن بخش ایندکس افزونه، canonical خودکار اضافه می‌شود.' );

		$permalink = (string) get_option( 'permalink_structure' );
		$add( 'index', 'permalink', 'ساختار پیوند یکتا (خوانا)', '' !== $permalink ? 'pass' : 'fail', 3,
			'' !== $permalink ? 'ساختار: ' . $permalink : 'پیوندهای ساده (‎?p=123) برای سئو و AI مناسب نیستند.',
			'' !== $permalink ? '' : 'تنظیمات ← پیوندهای یکتا ← «نام نوشته» را انتخاب کنید.' );

		$key  = (string) Store::get( 'indexnow_key' );
		$inow = Store::on( 'indexnow' ) && '' !== $key;
		if ( $inow ) {
			$keyfile = self::fetch( home_url( '/' . $key . '.txt' ) );
			$exact   = trim( (string) ( $keyfile['body'] ?? '' ) ) === $key && ( $keyfile['code'] ?? 0 ) === 200;
			$add( 'index', 'indexnow', 'کلید IndexNow روی دامنه سرو می‌شود', $exact ? 'pass' : 'fail', 3,
				$exact ? 'فایل کلید با کد ۲۰۰ و مقدار درست در دسترس است.' : 'فایل /' . $key . '.txt درست پاسخ نمی‌دهد (کد ' . ( $keyfile['code'] ?? 0 ) . ').',
				$exact ? '' : 'یک‌بار تنظیمات ← پیوندهای یکتا را ذخیره کنید تا قواعد بازنویسی بازسازی شوند.' );
		} else {
			$add( 'index', 'indexnow', 'ارسال سریع به Bing/Yandex (IndexNow)', 'warn', 3,
				'IndexNow خاموش است؛ صفحات تازه با تأخیر بیشتری کشف می‌شوند.',
				'در بخش «ایندکس»، IndexNow را روشن کنید (تولید کلید خودکار است).' );
		}

		// ------------------------------------------------------------------
		// 2. Content extractability
		// ------------------------------------------------------------------
		$title_len = mb_strlen( $facts['title'] ?? '' );
		$add( 'content', 'title', 'طول عنوان صفحه', $title_len >= 25 && $title_len <= 70 ? 'pass' : ( $title_len ? 'warn' : 'fail' ), 3,
			'طول عنوان: ' . $title_len . ' کاراکتر — «' . mb_substr( (string) ( $facts['title'] ?? '' ), 0, 70 ) . '»',
			$title_len >= 25 && $title_len <= 70 ? '' : 'عنوان ۳۰ تا ۶۰ کاراکتری بنویسید و کلمهٔ کلیدی را ابتدا بیاورید.' );

		$desc_len = mb_strlen( $facts['description'] ?? '' );
		$desc_ok  = $desc_len >= 60 && $desc_len <= 170;
		$add( 'content', 'description', 'طول توضیح متا', $desc_ok ? 'pass' : ( $desc_len ? 'warn' : 'fail' ), 3,
			'طول توضیح: ' . $desc_len . ' کاراکتر.',
			$desc_ok ? '' : 'توضیحی ۱۲۰ تا ۱۶۰ کاراکتری با ارزش پیشنهادی و دعوت به اقدام بنویسید.' );

		$h1 = (int) ( $facts['h1'] ?? 0 );
		$add( 'content', 'h1', 'یک H1 در صفحهٔ اصلی', 1 === $h1 ? 'pass' : ( $h1 ? 'warn' : 'fail' ), 3,
			'تعداد H1: ' . $h1, 1 === $h1 ? '' : 'ساختار عنوان‌ها را اصلاح کنید تا فقط یک H1 وجود داشته باشد.' );

		$h2 = (int) ( $facts['h2'] ?? 0 );
		$add( 'content', 'headings', 'سلسله‌مراتب عنوان‌ها (H2)', $h2 >= 3 ? 'pass' : 'warn', 2,
			'تعداد H2: ' . $h2, $h2 >= 3 ? '' : 'بخش‌های صفحه را با H2 معنادار تقسیم کنید؛ مدل‌های زبانی از آن‌ها برای تقسیم پاسخ استفاده می‌کنند.' );

		$words = (int) ( $facts['words'] ?? 0 );
		$add( 'content', 'words', 'مقدار متن قابل استخراج', $words >= 400 ? 'pass' : ( $words >= 150 ? 'warn' : 'fail' ), 3,
			'تعداد کلمات متن قابل مشاهده: ' . $words,
			$words >= 400 ? '' : 'حداقل ۴۰۰ کلمه توضیح روشن، مزیت‌ها، محدودهٔ خدمات و پاسخ پرسش‌های رایج اضافه کنید.' );

		$alt_ratio = (int) ( $facts['alt_ratio'] ?? 0 );
		$add( 'content', 'alt', 'متن جایگزین تصاویر', $alt_ratio >= 90 ? 'pass' : ( $alt_ratio >= 60 ? 'warn' : 'fail' ), 2,
			'درصد تصاویر دارای alt: ' . $alt_ratio . '٪', $alt_ratio >= 90 ? '' : 'برای تصاویر معنادار alt توصیفی بنویسید.' );

		$links = (int) ( $facts['links'] ?? 0 );
		$add( 'content', 'links', 'پیوندهای داخلی در صفحهٔ اصلی', $links >= 8 ? 'pass' : ( $links >= 4 ? 'warn' : 'fail' ), 2,
			'تعداد پیوند داخلی: ' . $links, $links >= 8 ? '' : 'به خدمات، نمونه‌کار و مقالات مرتبط پیوند بدهید تا روابط موضوعی ساخته شود.' );

		$fresh_days = self::freshness_days();
		$add( 'content', 'freshness', 'تازگی محتوا', $fresh_days <= 90 ? 'pass' : ( $fresh_days <= 365 ? 'warn' : 'fail' ), 2,
			$fresh_days < 0 ? 'مقالهٔ منتشرشده‌ای یافت نشد.' : 'آخرین به‌روزرسانی محتوا: ' . $fresh_days . ' روز پیش.',
			$fresh_days <= 90 ? '' : 'یک مقاله به‌روز یا مطالعهٔ موردی تازه منتشر کنید؛ AI به تازگی و تاریخ ارجاع حساس است.' );

		$og = ( '' !== ( $facts['og_image'] ?? '' ) ) && ( '' !== ( $facts['og_title'] ?? '' ) );
		$add( 'content', 'og', 'تگ‌های اشتراک‌گذاری (Open Graph)', $og ? 'pass' : 'warn', 2,
			$og ? 'og:title و og:image موجود است.' : 'og:title/og:image کامل نیست.',
			$og ? '' : 'با فعال بودن بخش ایندکس، این تگ‌ها خودکار منتشر می‌شوند.' );

		// ------------------------------------------------------------------
		// 3. Structured data
		// ------------------------------------------------------------------
		$types = $facts['types'] ?? array();
		$add( 'schema', 'jsonld', 'داده‌ساختاریافته JSON-LD', $types ? 'pass' : 'fail', 5,
			$types ? 'تایپ‌های یافت‌شده: ' . implode( ', ', array_slice( $types, 0, 8 ) ) : 'هیچ بلوک JSON-LD معتبری یافت نشد.',
			$types ? '' : 'در بخش «ایندکس»، اسکیمای سازمان/نشانه‌گذاری ساختاریافته را روشن کنید.' );

		$bad = (int) ( $facts['bad_jsonld'] ?? 0 );
		$add( 'schema', 'jsonld_valid', 'اعتبار JSON-LD', 0 === $bad ? 'pass' : 'fail', 3,
			0 === $bad ? 'همهٔ بلوک‌ها با json_decode معتبرند.' : $bad . ' بلوک JSON-LD نامعتبر است.',
			0 === $bad ? '' : 'اسکیمای افزونه‌های دیگر را بررسی کنید؛ JSON نامعتبر برای خزنده بی‌فایده است.' );

		$has_entity = (bool) array_intersect( $types, array( 'Organization', 'ProfessionalService', 'LocalBusiness', 'Corporation', 'Person' ) );
		$add( 'schema', 'entity', 'موجودیت برند (Organization)', $has_entity ? 'pass' : 'fail', 4,
			$has_entity ? 'موجودیت برند تعریف شده است.' : 'موجودیت برند تعریف نشده؛ AI نمی‌داند «چه کسی» پاسخ می‌دهد.',
			$has_entity ? '' : 'در بخش «ایندکس» اسکیمای سازمان را روشن کنید و لوگو/شبکه‌های اجتماعی را کامل کنید.' );

		$has_website = in_array( 'WebSite', $types, true );
		$add( 'schema', 'website', 'WebSite + جستجوی داخلی', $has_website ? 'pass' : 'warn', 2,
			$has_website ? 'WebSite/SearchAction موجود است.' : 'اسکیمای WebSite یافت نشد.',
			$has_website ? '' : 'با فعال بودن اسکیمای قالب، این مورد خودکار اضافه می‌شود.' );

		$post_types = (array) ( $sample['facts']['types'] ?? array() );
		$has_crumb  = in_array( 'BreadcrumbList', $types, true ) || in_array( 'BreadcrumbList', $post_types, true );
		$crumb_where = in_array( 'BreadcrumbList', $types, true ) ? 'صفحهٔ اصلی' : ( $sample['url'] ?? '' );
		$add( 'schema', 'breadcrumb', 'مسیر راهنما (BreadcrumbList)', $has_crumb ? 'pass' : ( $sample['url'] ? 'fail' : 'skip' ), 2,
			$has_crumb ? 'BreadcrumbList روی ' . $crumb_where . ' موجود است.' : 'BreadcrumbList در صفحهٔ نمونه یافت نشد.',
			$has_crumb ? '' : 'در بخش «ایندکس»، «اسکیمای مسیر راهنما» را روشن کنید.' );

		$has_article = (bool) array_intersect( $post_types, array( 'BlogPosting', 'Article', 'NewsArticle' ) );
		$add( 'schema', 'article', 'اسکیمای مقاله برای نوشته‌ها', $has_article ? 'pass' : ( $sample['url'] ? 'fail' : 'skip' ), 2,
			$has_article ? 'BlogPosting/Article روی ' . ( $sample['url'] ?? '' ) . ' موجود است.' : 'مقالهٔ ساختاریافته‌ای در صفحهٔ نمونه یافت نشد.',
			$has_article ? '' : 'در بخش «ایندکس»، «اسکیمای مقاله» را روشن کنید.' );

		$has_faq = (bool) array_intersect( array_merge( $types, $post_types ), array( 'FAQPage', 'Question' ) );
		$add( 'schema', 'faq', 'بخش پرسش‌های متداول ساختاریافته', $has_faq ? 'pass' : 'warn', 2,
			$has_faq ? 'FAQPage/Question موجود است.' : 'FAQPage یافت نشد.',
			$has_faq ? '' : 'در صفحهٔ اصلی یک بخش «پرسش‌های متداول» با اسکیمای FAQPage اضافه کنید؛ بیشترین شانس نقل‌قول در AI را دارد.' );

		if ( class_exists( 'WooCommerce' ) ) {
			$has_product = in_array( 'Product', $types, true );
			$add( 'schema', 'product', 'اسکیمای محصول (ووکامرس)', $has_product ? 'pass' : 'warn', 1,
				$has_product ? 'Product موجود است.' : 'روی صفحهٔ اصلی محصولی فهرست نشده؛ در صفحهٔ محصول بررسی کنید.', '' );
		}

		// ------------------------------------------------------------------
		// 4. AI visibility
		// ------------------------------------------------------------------
		$blocked_bots = array();
		foreach ( array_merge( Indexing::RETRIEVAL_BOTS, array( 'Googlebot', 'Bingbot' ) ) as $bot ) {
			if ( self::bot_blocked( $robots_body, $bot ) ) {
				$blocked_bots[] = $bot;
			}
		}
		$add( 'ai', 'ai_access', 'دسترسی خزنده‌های AI (خزش/استناد)', $robots_body === '' ? 'skip' : ( $blocked_bots ? 'fail' : 'pass' ), 6,
			$robots_body === '' ? 'برای بررسی، robots.txt لازم است.' : ( $blocked_bots ? 'مسدود: ' . implode( ', ', $blocked_bots ) : 'OAI-SearchBot، PerplexityBot، Claude-SearchBot و Google-Extended مسدود نیستند.' ),
			$blocked_bots ? 'اگر می‌خواهید در پاسخ‌های ChatGPT/Perplexity/Claude دیده شوید، این خزنده‌ها را باز کنید.' : '' );

		$gm_blocked = self::bot_blocked( $robots_body, 'Google-Extended' );
		$add( 'ai', 'gemini', 'Google-Extended (استناد در Gemini)', $robots_body === '' ? 'skip' : ( $gm_blocked ? 'warn' : 'pass' ), 2,
			$gm_blocked ? 'Google-Extended بسته است؛ سایت در Gemini و Vertex AI به‌عنوان منبع استفاده نمی‌شود.' : 'Google-Extended باز است.',
			$gm_blocked ? 'توجه: بستن Google-Extended روی AI Overviews اثر ندارد، اما شانس استناد در Gemini را صفر می‌کند.' : '' );

		$llms_body = trim( (string) ( $llms['body'] ?? '' ) );
		$llms_ok   = ( $llms['code'] ?? 0 ) === 200 && str_starts_with( $llms_body, '#' );
		$llms_links = preg_match_all( '/\]\((https?:[^)]+)\)/', $llms_body );
		$add( 'ai', 'llms_txt', 'فایل llms.txt برای عامل‌های AI', $llms_ok ? 'pass' : 'fail', 4,
			$llms_ok ? 'llms.txt فعال است؛ تعداد پیوند: ' . (int) $llms_links : 'llms.txt یافت نشد یا با کد ' . ( $llms['code'] ?? 0 ) . ' پاسخ داد.',
			$llms_ok ? '' : 'در بخش «AI» سوئیچ llms.txt را روشن کنید (ساخت و سرو خودکار است).' );

		$add( 'ai', 'llms_curated', 'گزیده بودن llms.txt (نه فهرست انبوه)', (int) $llms_links >= 8 && (int) $llms_links <= 60 ? 'pass' : 'warn', 1,
			'تعداد پیوند: ' . (int) $llms_links . ' (بازهٔ پیشنهادی ۸ تا ۶۰)',
			'' );

		$add( 'ai', 'plain_text', 'متن اصلی بدون نیاز به رندر جاوااسکریپت', $words >= 300 ? 'pass' : 'fail', 4,
			'کلمات متن خام HTML: ' . $words,
			$words >= 300 ? '' : 'محتوای کلیدی باید در HTML اولیه باشد؛ محتوایی که فقط با JS ساخته می‌شود برای اکثر خزنده‌های AI نامرئی است.' );

		$brand_ok = (bool) ( $facts['brand_mentioned'] ?? false );
		$add( 'ai', 'entity_mention', 'نام برند در ابتدای متن', $brand_ok ? 'pass' : 'warn', 2,
			$brand_ok ? 'نام برند در ۳۰۰ کلمهٔ اول آمده است.' : 'نام برند در ابتدای متن دیده نشد.',
			$brand_ok ? '' : 'در پاراگراف اول به روشنی بنویسید چه کسی، چه کاری، برای چه کسی انجام می‌دهد.' );

		$contact_ok = (bool) ( $facts['contact'] ?? false );
		$add( 'ai', 'contact', 'اطلاعات تماس قابل استخراج', $contact_ok ? 'pass' : 'warn', 2,
			$contact_ok ? 'تلفن/ایمیل در متن یا اسکیما موجود است.' : 'تلفن یا ایمیل قابل استخراج یافت نشد.',
			$contact_ok ? '' : 'شماره تماس و ایمیل را در فوتر و اسکیمای سازمان اضافه کنید.' );

		// Author is a site-wide signal: look at both the home page and the sampled post.
		$author_types = array_merge( $types, $post_types );
		$author_ok    = (bool) array_intersect( $author_types, array( 'Person', 'BlogPosting', 'Article', 'NewsArticle' ) );
		$add( 'ai', 'author', 'نویسنده/مسئول محتوا مشخص است', $author_ok ? 'pass' : 'warn', 2,
			$author_ok ? 'نویسنده در اسکیمای صفحه یا نوشته تعریف شده است.' : 'نویسندهٔ محتوا در اسکیما مشخص نیست.',
			$author_ok ? '' : 'اسکیمای مقاله و نام نویسنده را کامل کنید؛ E-E-A-T و استناد AI به آن وابسته است.' );

		$lang = (string) ( $facts['lang'] ?? '' );
		$add( 'ai', 'lang', 'زبان صفحه اعلام شده', '' !== $lang ? 'pass' : 'warn', 2,
			'' !== $lang ? 'lang="' . $lang . '"' : 'صفت lang روی <html> نیست.', '' );

		$sitemap_allow = ! $blocked_bots;
		$add( 'ai', 'ai_sitemap', 'دسترسی خزنده‌های AI به نقشهٔ سایت', $locs > 0 && $sitemap_allow ? 'pass' : 'warn', 1,
			$locs > 0 ? 'نقشهٔ سایت (' . $sitemap_total . ') بدون مانع در دسترس است.' : 'نقشهٔ سایت در دسترس نیست.', '' );

		// ------------------------------------------------------------------
		// 5. Performance guard
		// ------------------------------------------------------------------
		$bytes = strlen( $html );
		$kb    = (int) round( $bytes / 1024 );
		$add( 'speed', 'html_size', 'حجم HTML صفحهٔ اصلی', $kb > 0 && $kb < 200 ? 'pass' : ( $kb < 350 ? 'warn' : 'fail' ), 3,
			'حجم: ' . $kb . ' کیلوبایت', $kb < 200 ? '' : 'محتوا و مارک‌آپ اضافی را کم کنید؛ HTML سنگین زمان پاسخ را بالا می‌برد.' );

		$ttfb = (int) ( $page['ttfb'] ?? 0 );
		$add( 'speed', 'ttfb', 'زمان اولین بایت (TTFB)', $ttfb > 0 && $ttfb < 800 ? 'pass' : ( $ttfb < 1500 ? 'warn' : 'fail' ), 3,
			'TTFB: ' . $ttfb . ' میلی‌ثانیه', $ttfb < 800 ? '' : 'کش صفحه، آبجکت‌کش و کوئری‌های سنگین را بررسی کنید.' );

		$encoding = strtolower( (string) ( $page['encoding'] ?? '' ) );
		$add( 'speed', 'compression', 'فشرده‌سازی پاسخ (gzip/brotli)', '' !== $encoding ? 'pass' : 'warn', 2,
			'' !== $encoding ? 'Content-Encoding: ' . $encoding : 'هدر Content-Encoding دیده نشد.',
			'' !== $encoding ? '' : 'فشرده‌سازی gzip/brotli را در وب‌سرور یا CDN فعال کنید.' );

		$cache = (string) ( $page['cache'] ?? '' );
		$add( 'speed', 'cache', 'هدرهای کش صفحه', '' !== $cache ? 'pass' : 'warn', 2,
			'' !== $cache ? 'Cache-Control: ' . $cache : 'هدر کش اختصاصی دیده نشد.',
			'' !== $cache ? '' : 'کش صفحه (CDN یا کش افزونه) را فعال کنید؛ سریع‌ترین راه برای حفظ TTFB.' );

		$seo_owner = Indexing::owned() ? 'افزونهٔ سئوی فعال' : null;
		$add( 'speed', 'no_extra_work', 'هزینهٔ افزونه روی بارگذاری صفحه', 'pass', 3,
			$seo_owner
				? 'تگ‌های متا توسط ' . $seo_owner . ' مدیریت می‌شود؛ افزونه هیچ تگ تکراری چاپ نمی‌کند.'
				: 'افزونه فقط چند مقایسهٔ رشته‌ای انجام می‌دهد: بدون کوئری اضافه، بدون فایل، بدون CSS/JS در فرانت‌اند.',
			'' );

		// ------------------------------------------------------------------
		// Grade
		// ------------------------------------------------------------------
		$score   = self::score( $checks );
		$groups  = self::group_scores( $checks );
		$counts  = array( 'pass' => 0, 'warn' => 0, 'fail' => 0, 'skip' => 0 );
		foreach ( $checks as $check ) {
			$counts[ $check['status'] ] = ( $counts[ $check['status'] ] ?? 0 ) + 1;
		}

		return array(
			'time'     => time(),
			'score'    => $score,
			'grade'    => self::grade( $score ),
			'groups'   => $groups,
			'counts'   => $counts,
			'checks'   => $checks,
			'preview'  => self::ai_preview( $facts ),
			'bots'     => array(
				'retrieval' => Indexing::RETRIEVAL_BOTS,
				'training'  => Indexing::TRAINING_BOTS,
				'blocked'   => self::scan_blocked( $robots_body ),
			),
			'fetched'  => array(
				'home'    => (bool) $html,
				'robots'  => $robots_body !== '',
				'llms'    => $llms_body !== '',
				'sitemap' => $locs > 0,
			),
		);
	}

	/** Weighted score: pass = 1, warn = 0.5, skip = ignored, fail = 0. */
	private static function score( array $checks ): int {
		$earned = 0.0;
		$total  = 0.0;
		foreach ( $checks as $check ) {
			if ( 'skip' === $check['status'] ) {
				continue;
			}
			$total += $check['weight'];
			if ( 'pass' === $check['status'] ) {
				$earned += $check['weight'];
			} elseif ( 'warn' === $check['status'] ) {
				$earned += $check['weight'] * 0.5;
			}
		}
		return $total > 0 ? (int) round( 100 * $earned / $total ) : 0;
	}

	/** Per-group score so the UI can show five dials. */
	private static function group_scores( array $checks ): array {
		$labels = array(
			'index'   => 'ایندکس‌پذیری',
			'content' => 'کیفیت محتوا',
			'schema'  => 'داده‌ساختاریافته',
			'ai'      => 'دیده‌شدن در AI',
			'speed'   => 'سرعت و محافظت',
		);
		$out = array();
		foreach ( $labels as $key => $label ) {
			$subset = array_values( array_filter( $checks, static fn( $c ) => $c['group'] === $key ) );
			$out[ $key ] = array(
				'label' => $label,
				'score' => self::score( $subset ),
				'items' => count( $subset ),
			);
		}
		return $out;
	}

	/** Letter grade + Persian verdict. */
	private static function grade( int $score ): array {
		if ( $score >= 90 ) {
			return array( 'A', 'عالی — آماده برای ایندکس و استناد در AI' );
		}
		if ( $score >= 75 ) {
			return array( 'B', 'خوب — چند اصلاح کوچک مانده' );
		}
		if ( $score >= 60 ) {
			return array( 'C', 'متوسط — موارد قرمز را برطرف کنید' );
		}
		if ( $score >= 40 ) {
			return array( 'D', 'ضعیف — دیده‌شدن در جستجو و AI محدود است' );
		}
		return array( 'E', 'بحرانی — پیش از هر کاری این موارد را درست کنید' );
	}

	/** Is a bot blocked by a Disallow rule in robots.txt? */
	public static function bot_blocked( string $robots, string $bot ): bool {
		if ( '' === trim( $robots ) ) {
			return false;
		}
		$lines    = preg_split( '/\r\n|\n|\r/', $robots ) ?: array();
		$relevant = false;
		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/#.*$/', '', $line ) ?? '' );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^user-agent:\s*(.+)$/i', $line, $m ) ) {
				$agent    = trim( $m[1] );
				$relevant = ( '*' === $agent ) || ( strcasecmp( $agent, $bot ) === 0 );
				continue;
			}
			if ( ! $relevant ) {
				continue;
			}
			if ( preg_match( '/^disallow:\s*(.*)$/i', $line, $m ) ) {
				$path = trim( $m[1] );
				if ( '/' === $path ) {
					return true;
				}
			}
			if ( preg_match( '/^allow:\s*\/\s*$/i', $line ) ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Does the `User-agent: *` group disallow the whole site?
	 *
	 * Parsed group by group on purpose: a naive regex that looks for
	 * "User-agent: * … Disallow: /" also matches a *later* group and produced a
	 * false "your site is closed" verdict on a perfectly healthy robots.txt.
	 */
	public static function star_group_blocks_all( string $robots ): bool {
		if ( '' === trim( $robots ) ) {
			return false;
		}
		$lines   = preg_split( '/\r\n|\n|\r/', $robots ) ?: array();
		$in_star = false;
		$rules   = false;
		$blocked = false;

		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/#.*$/', '', $line ) ?? '' );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^user-agent:\s*(.+)$/i', $line, $m ) ) {
				if ( $rules ) {          // a user-agent line after rules opens a new group
					$in_star = false;
					$rules   = false;
					$blocked = false;
				}
				if ( '*' === trim( $m[1] ) ) {
					$in_star = true;
				}
				continue;
			}
			if ( preg_match( '/^(allow|disallow):\s*(.*)$/i', $line, $m ) ) {
				$rules = true;
				if ( ! $in_star ) {
					continue;
				}
				$path = trim( $m[2] );
				if ( 'disallow' === strtolower( $m[1] ) && '/' === $path ) {
					$blocked = true;
				} elseif ( 'allow' === strtolower( $m[1] ) && '/' === $path ) {
					$blocked = false;
				}
			}
		}
		return $blocked;
	}

	/** Names of every AI bot blocked by the current robots.txt. */
	private static function scan_blocked( string $robots ): array {
		$out = array();
		foreach ( array_merge( Indexing::RETRIEVAL_BOTS, Indexing::TRAINING_BOTS ) as $bot ) {
			if ( self::bot_blocked( $robots, $bot ) ) {
				$out[] = $bot;
			}
		}
		return $out;
	}

	/** HTTP GET with timing, capped and never fatal. */
	private static function fetch( string $url ): array {
		$start = microtime( true );
		$res   = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 3,
				'user-agent'  => self::UA,
				'headers'     => array( 'Accept' => 'text/html,text/plain;q=0.9,*/*;q=0.8' ),
				'sslverify'   => ! ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE ),
			)
		);
		$ms = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $res ) ) {
			return array( 'code' => 0, 'body' => '', 'error' => $res->get_error_message(), 'ttfb' => $ms );
		}
		$headers = wp_remote_retrieve_headers( $res );
		return array(
			'code'     => (int) wp_remote_retrieve_response_code( $res ),
			'body'     => (string) wp_remote_retrieve_body( $res ),
			'ttfb'     => $ms,
			'encoding' => (string) ( $headers['content-encoding'] ?? '' ),
			'cache'    => (string) ( $headers['cache-control'] ?? $headers['cf-cache-status'] ?? '' ),
			'type'     => (string) ( $headers['content-type'] ?? '' ),
		);
	}

	/** status helper */
	private static function status_of( array $res, int $expected ): string {
		if ( ( $res['code'] ?? 0 ) === $expected ) {
			return 'pass';
		}
		return ( $res['code'] ?? 0 ) === 0 ? 'fail' : 'warn';
	}

	/** Parses the public HTML the way a crawler would. */
	private static function extract( string $html ): array {
		$out = array( 'types' => array(), 'bad_jsonld' => 0, 'words' => 0, 'h1' => 0, 'h2' => 0 );

		if ( '' === $html ) {
			return $out;
		}

		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
			$out['title'] = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
		}
		if ( preg_match( '/<html[^>]*lang=["\']([^"\']+)["\']/i', $html, $m ) ) {
			$out['lang'] = $m[1];
		}
		if ( preg_match( '/<meta[^>]+name=["\']description["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $m ) ) {
			$out['description'] = trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		}
		if ( preg_match( '/<link[^>]+rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']/i', $html, $m ) ) {
			$out['canonical'] = $m[1];
		}
		if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
			$out['og_image'] = $m[1];
		}
		if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
			$out['og_title'] = $m[1];
		}

		$out['h1'] = preg_match_all( '/<h1[\s>]/i', $html );
		$out['h2'] = preg_match_all( '/<h2[\s>]/i', $html );

		$imgs = preg_match_all( '/<img\s[^>]*>/i', $html );
		$alts = preg_match_all( '/<img\s[^>]*alt=["\'][^"\']+["\'][^>]*>/i', $html );
		$out['alt_ratio'] = $imgs > 0 ? (int) round( 100 * $alts / $imgs ) : 100;

		$out['links'] = preg_match_all( '/<a\s[^>]*href=["\'](?!https?:\/\/(?:www\.)?' . preg_quote( (string) wp_parse_url( home_url(), PHP_URL_HOST ), '/' ) . ')[^"\']*["\']/i', $html );

		// JSON-LD blocks.
		if ( preg_match_all( '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $blocks ) ) {
			foreach ( $blocks[1] as $json ) {
				$data = json_decode( trim( $json ), true );
				if ( null === $data ) {
					$out['bad_jsonld']++;
					continue;
				}
				foreach ( (array) ( $data['@graph'] ?? array( $data ) ) as $node ) {
					if ( ! is_array( $node ) ) {
						continue;
					}
					$type = $node['@type'] ?? '';
					foreach ( (array) $type as $t ) {
						if ( is_string( $t ) && ! in_array( $t, $out['types'], true ) ) {
							$out['types'][] = $t;
						}
					}
				}
			}
		}

		// Visible text: strip scripts/styles/nav chrome, then count words.
		$text = preg_replace( '#<(script|style|noscript|svg)\b.*?</\1>#is', ' ', $html ) ?? '';
		$text = wp_strip_all_tags( $text );
		// Shortcodes are markup, not readable prose: an AI model must never see
		// `[zarincode_widget type="…"]` as the first words of the site.
		$text = strip_shortcodes( $text );
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );
		// str_word_count() only understands ASCII, so a Persian page scored ~200
		// words. Count Unicode letter/number runs instead.
		$out['words']       = (int) preg_match_all( '/[\p{L}\p{N}]+/u', $text );
		$out['text_sample'] = mb_substr( $text, 0, 600 );

		$brand = (string) get_bloginfo( 'name' );
		$first = mb_substr( $text, 0, 2000 );
		$out['brand_mentioned'] = '' !== $brand && false !== mb_stripos( $first, $brand );

		$phone = (string) ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'contact_phone', '' ) : '' );
		$out['contact'] = (bool) (
			( $phone && str_contains( $html, $phone ) )
			|| false !== strpos( $html, 'tel:' )
			|| false !== strpos( $html, 'mailto:' )
			|| preg_match( '/[\w.+-]+@[\w-]+\.[\w.]+/', $html )
		);

		return $out;
	}

	/** Newest published post: the cheapest page that must carry article schema. */
	private static function sample_post(): array {
		$posts = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'post_type' => 'post' ) );
		if ( ! $posts ) {
			return array( 'url' => '', 'facts' => array() );
		}
		$url  = (string) get_permalink( $posts[0] );
		$page = self::fetch( $url );
		return array(
			'url'   => $url,
			'facts' => self::extract( (string) ( $page['body'] ?? '' ) ),
		);
	}

	/** Days since the newest published/updated post. */
	private static function freshness_days(): int {
		$posts = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'orderby' => 'modified' ) );
		if ( ! $posts ) {
			return -1;
		}
		return max( 0, (int) floor( ( time() - (int) get_post_modified_time( 'U', true, $posts[0] ) ) / DAY_IN_SECONDS ) );
	}

	/** What an AI assistant would most likely quote from the site. */
	private static function ai_preview( array $facts ): array {
		// Put the entity type first: that is what an answer engine quotes.
		$rank  = array( 'Organization' => 0, 'ProfessionalService' => 0, 'LocalBusiness' => 0, 'Corporation' => 0, 'WebSite' => 2, 'WebPage' => 3, 'BreadcrumbList' => 4 );
		$types = (array) ( $facts['types'] ?? array() );
		usort( $types, static fn( $a, $b ) => ( $rank[ $a ] ?? 2 ) <=> ( $rank[ $b ] ?? 2 ) );

		return array(
			'name'        => get_bloginfo( 'name' ),
			'tagline'     => get_bloginfo( 'description' ),
			'type'        => (string) ( $types[0] ?? '—' ),
			'title'       => (string) ( $facts['title'] ?? '' ),
			'description' => (string) ( $facts['description'] ?? '' ),
			'canonical'   => (string) ( $facts['canonical'] ?? '' ),
			'words'       => (int) ( $facts['words'] ?? 0 ),
			'sample'      => (string) ( $facts['text_sample'] ?? '' ),
			'types'       => array_slice( $types, 0, 10 ),
		);
	}
}
