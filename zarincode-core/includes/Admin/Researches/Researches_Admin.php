<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/**
 * Single admin surface for every third-party research entry.
 *
 * @package ZarincodeCore
 */
final class Researches_Admin {

	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register' ), 12 );
	}

	public static function register(): void {
		add_menu_page( 'بانک تحقیقات', 'تحقیقات بازار', 'manage_options', 'zc-rocket', array( __CLASS__, 'render' ), 'dashicons-rocket', 29 );
	}

	private static function entries(): array {
		return array(
			array( 'group' => 'ZarinPal', 'slug' => 'zarinpal', 'title' => 'ZarinPal — درگاه پرداخت', 'summary' => 'درگاه پرداخت اصلی زرین‌کد (فروشگاه/قالب).', 'source' => 'https://docs.zarinpal.com/paymentGateway/', 'doc' => __DIR__ . '/../research/zarinpal-research.md', 'class' => ZarinPal_Research::class ),
			array( 'group' => 'ZarinPal', 'slug' => 'zarinpal-sandbox', 'title' => 'ZarinPal Sandbox — محیط تست', 'summary' => 'تست بدون تراکنش واقعی؛ پاسخ‌های mock شده.', 'source' => 'https://docs.zarinpal.com/paymentGateway/sandbox.html', 'doc' => __DIR__ . '/../research/zarinpal-research.md', 'class' => ZarinPal_Research::class ),
			array( 'group' => 'ZarinPal', 'slug' => 'zarinpal-rest', 'title' => 'ZarinPal REST API v1', 'summary' => 'Web Service (درخواست/تأیید/استعلام).', 'source' => 'https://docs.zarinpal.com/paymentGateway/rest/', 'doc' => __DIR__ . '/../research/zarinpal-research.md', 'class' => ZarinPal_Research::class ),
			array( 'group' => 'شبکه‌های اجتماعی', 'slug' => 'telegram', 'title' => 'Telegram — اشتراک‌گذاری و بات', 'summary' => 'اشتراک‌گذاری لینک + ساخت بات پشتیبانی.', 'source' => 'https://core.telegram.org/bots/api', 'doc' => '', 'class' => __CLASS__ ),
			array( 'group' => 'شبکه‌های اجتماعی', 'slug' => 'whatsapp', 'title' => 'WhatsApp — Click to Chat', 'summary' => 'پیام مستقیم با شماره ثبت‌شده.', 'source' => 'https://faq.whatsapp.com/591339899867293', 'doc' => '', 'class' => __CLASS__ ),
			array( 'group' => 'شبکه‌های اجتماعی', 'slug' => 'x', 'title' => 'X (توییتر) — intent', 'summary' => 'اشتراک‌گذاری intent endpoint.', 'source' => 'https://developer.x.com/en/docs/twitter-for-websites/integrate-with-x', 'doc' => '', 'class' => __CLASS__ ),
			array( 'group' => 'شبکه‌های اجتماعی', 'slug' => 'bale', 'title' => 'Bale — پیام‌رسان', 'summary' => 'اشتراک‌گذاری + سرویس Bale برای پشتیبانی.', 'source' => 'https://web.bale.ai/', 'doc' => '', 'class' => Bale_Research::class ),
			array( 'group' => 'شبکه‌های اجتماعی', 'slug' => 'eitaa', 'title' => 'Eitaa — پیام‌رسان', 'summary' => 'نسخهٔ ایرانی؛ اشتراک‌گذاری لینک.', 'source' => 'https://eitaa.com/', 'doc' => '', 'class' => __CLASS__ ),
			array( 'group' => 'منابع', 'slug' => 'icons', 'title' => 'persianlabs/icons — آیکون‌ها', 'summary' => 'MIT؛ آیکون‌های رنگی شبکه‌های اجتماعی.', 'source' => 'https://github.com/persianlabs/icons', 'doc' => '', 'class' => Persianlabs_Icons::class ),
			array( 'group' => 'منابع', 'slug' => 'ideaa', 'title' => 'آیکون‌های IDEAA', 'summary' => 'آیکون‌های متن‌باز برای بار شبکه‌های اجتماعی.', 'source' => 'https://ideaa.org/', 'doc' => '', 'class' => Social_IDEAA::class ),
			array( 'group' => 'منابع', 'slug' => 'signal', 'title' => 'Signal — آیکون رسمی', 'summary' => 'استایل رسمی برای بار.', 'source' => 'https://signal.org/', 'doc' => '', 'class' => Social_Signal::class ),
			array( 'group' => 'منابع', 'slug' => 'theme', 'title' => 'زیرساخت قالب', 'summary' => 'Crextio + Webinex؛ مودال مشاوره، اکشن‌های هدر.', 'source' => 'https://github.com/afzoneha', 'doc' => '', 'class' => Zarincode_Theme::class ),
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$items = self::entries();
		?>
		<div class="wrap" dir="rtl">
			<h1>تحقیقات بازار <span style="font-size:.72em;font-weight:400;color:#64748b;">— جمع‌آوری و مرور منابع برای هر سرویس قبل از کدنویسی (دستور دائمی کاربر)</span></h1>
			<p>برای هر سرویس، تحقیق، نیازسنجی و سپس کدنویسی انجام شده است؛ این صفحه نتایج را یکجا نگه می‌دارد.</p>
			<table class="widefat striped" style="background:#fff;max-width:1100px">
				<thead><tr><th style="width:110px">گروه</th><th>سرویس</th><th>خلاصه</th><th style="width:90px">منبع</th><th style="width:110px">مرجع داخلی</th></tr></thead>
				<tbody>
				<?php foreach ( $items as $it ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $it['group'] ); ?></strong></td>
						<td><a href="<?php echo esc_url( $it['source'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $it['title'] ); ?></a></td>
						<td><?php echo esc_html( $it['summary'] ); ?></td>
						<td><a href="<?php echo esc_url( $it['source'] ); ?>" target="_blank" rel="noopener">مشاهده</a></td>
						<td>
							<?php if ( $it['doc'] && is_file( $it['doc'] ) ) : ?>
								<a href="<?php echo esc_url( content_url( 'zarincode-core/' . wp_make_link_relative( $it['doc'] ) ) ); ?>" target="_blank" rel="noopener">داک فنی</a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=zc-edit-requests' ) ); ?>">درخواست‌های طراحی سایت (پنل اختصاصی)</a></p>
		</div>
		<?php
	}
}
