<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/**
 * ZarinPal research + sandbox page (Rocket admin surface).
 */
final class ZarinPal_Research {

	public const SANDBOX_MERCHANT = 'zarin.example';
	public const SANDBOX_SUCCESS  = 'https://example.com/ok';
	public const SANDBOX_FAIL     = 'https://example.com/fail';
	public const SANDBOX_REQUEST  = 'https://sandbox.zarinpal.com/pg/StartPay/';
	public const SANDBOX_VERIFY   = 'https://sandbox.zarinpal.com/pg/Verify';
	public const API_ROOT         = 'https://api.zarinpal.com';
	public const V4_GATEWAY       = '/payment/request.json';
	public const V4_START         = '/payment/start/';
	public const V4_VERIFY        = '/payment/verify.json';
	public const REST_ROOT        = 'https://api.zarinpal.com/v1';
	public const REST_REQUEST     = '/request';
	public const REST_VERIFY      = '/verify';
	public const REST_START       = '/PaymentRequest.json';
	public const REST_CONFIRM     = '/PaymentVerification.json';
	public const REST_UNIPAY      = '/Unverified.json';

	public static function service(): array {
		return array(
			'name'        => 'ZarinPal',
			'group'       => 'درگاه پرداخت',
			'doc'         => 'https://docs.zarinpal.com/paymentGateway/',
			'sandbox_doc' => 'https://docs.zarinpal.com/paymentGateway/sandbox.html',
			'rest_doc'    => 'https://docs.zarinpal.com/paymentGateway/rest/',
			'callout'     => 'زیرساخت پرداخت برای فروشگاه و قالب (درخواست/تأیید/استعلام).',
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap" dir="rtl">
			<h1>ZarinPal — درگاه پرداخت</h1>
			<p>تحقیق و نیازسنجی زیرساخت پرداخت زرین‌کد؛ استفادهٔ عملیاتی در فروشگاه و قالب انجام شده است.</p>
			<table class="widefat striped" style="background:#fff;max-width:900px">
				<tbody>
					<tr><td style="width:220px"><strong>مرجع رسمی</strong></td><td><a target="_blank" rel="noopener" href="https://docs.zarinpal.com/paymentGateway/">docs.zarinpal.com/paymentGateway</a></td></tr>
					<tr><td><strong>مرچنت تاب‌عده (sandbox)</strong></td><td><code dir="ltr"><?php echo esc_html( self::SANDBOX_MERCHANT ); ?></code> — از مستندات رسمی ZarinPal</td></tr>
					<tr><td><strong>نشانی Start Pay</strong></td><td><code dir="ltr"><?php echo esc_html( self::SANDBOX_REQUEST ); ?></code></td></tr>
					<tr><td><strong>نشانی Verify</strong></td><td><code dir="ltr"><?php echo esc_html( self::SANDBOX_VERIFY ); ?></code></td></tr>
					<tr><td><strong>API Root</strong></td><td><code dir="ltr"><?php echo esc_html( self::API_ROOT ); ?></code></td></tr>
					<tr><td><strong>مسیرهای v4</strong></td><td><code dir="ltr"><?php echo esc_html( self::V4_GATEWAY ); ?> · <?php echo esc_html( self::V4_START ); ?> · <?php echo esc_html( self::V4_VERIFY ); ?></code></td></tr>
					<tr><td><strong>REST v1</strong></td><td><code dir="ltr"><?php echo esc_html( self::REST_ROOT . self::REST_REQUEST ); ?> · <?php echo esc_html( self::REST_ROOT . self::REST_VERIFY ); ?></code></td></tr>
				</tbody>
			</table>
			<p>نشانی‌های مربوط به پرداخت در همین صفحه ماندگار می‌شوند تا کدنویسی فروشگاه و قالب روی تحقیق قابل استناد انجام شود.</p>

			<?php
			$rows = \Zarincode\Runtime260\Elementor\Editor_Performance::report();
			if ( $rows ) {
				echo '<h2>بهینه‌سازی ویرایش المنتور (به‌صورت خودکار اعمال می‌شود)</h2>';
				echo '<table class="widefat striped" style="background:#fff;max-width:900px"><tbody>';
				foreach ( $rows as $r ) {
					echo '<tr><td style="width:220px"><strong>' . esc_html( $r['label'] ) . '</strong></td><td><code dir="ltr">' . esc_html( $r['slug'] ) . '</code> — ' . ( $r['active'] ? '🟢 فعال' : '⚪ پیش‌فرض' ) . '</td></tr>';
				}
				echo '</tbody></table>';
				echo '<p class="description">برای سرعت بیشتر ویرایش: در المنتور ← مدیریت ابزارک‌ها (Element Manager) ابزارک‌های بلااستفاده را غیرفعال کنید تا پنل ویرایش سبک‌تر شود. ابزارک‌های زرین‌کد فقط وقتی CSS/JS خود را بارگذاری می‌کنند که روی صفحه‌ای استفاده شده باشند (وابستگی شرطی Elementor).</p>';
			}
			?>
		</div>
		<?php
	}
}
