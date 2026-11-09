<?php
/**
 * Admin panel: «امنیت سایت» — state of the hardening layer.
 *
 * Everything here is read-only and guarded by `manage_options`. It exists so the
 * claim "absolute paths can no longer appear in the site" is verifiable in the
 * dashboard instead of being taken on trust: the panel reports the PHP error
 * configuration, the state of the error shield and a scan of the server log for
 * any remaining message that still carries a filesystem path.
 *
 * @package Zarincode
 */

namespace Zarincode\Runtime260\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the security panel.
 */
final class Hardening_Page {

	/**
	 * Print the panel.
	 */
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rows = Hardening::audit();
		$scan = self::scan_log();

		echo '<div class="wrap zc-security" dir="rtl">';
		echo '<h1>امنیت سایت — نمایش خطا و مسیر فایل‌های سرور</h1>';

		echo '<div class="notice notice-info" style="padding:14px 18px;max-width:960px">';
		echo '<p style="margin:0 0 8px;font-weight:700">چرا آدرس <code dir="ltr">/home/greenfis/public_html/…</code> در سایت دیده می‌شد؟</p>';
		echo '<p style="margin:0">آن رشته، در وردپرس ذخیره نمی‌شود؛ خودِ PHP آن را وقتی یک خطا/اخطار را نمایش می‌دهد می‌سازد. یعنی دو شرط هم‌زمان برقرار بود: (۱) یک خط کد در قالب یک اخطار PHP تولید می‌کرد (در نسخه‌های قبلی، <code dir="ltr">header.php</code> مقدار تهی را به <code dir="ltr">strpos()</code> می‌داد) و (۲) در php.ini هاست <code dir="ltr">display_errors = On</code> بود، پس PHP متن اخطار همراه مسیر کامل فایل را داخل خود صفحه چاپ می‌کرد.</p>';
		echo '<p style="margin:8px 0 0">رفع دائم: (الف) خطای کد در قالب اصلاح شد؛ (ب) لایهٔ مهار خطا در هستهٔ زرین‌کد، نمایش هر خطای غیرکشنده را می‌گیرد حتی اگر هاست <code dir="ltr">display_errors</code> را اجباری روشن کرده باشد (چون هندلر سفارشی، هندلر چاپ‌کنندهٔ PHP را اجرا نمی‌کند) و همهٔ پیام‌هایی که به مرورگر برمی‌گردند با <code dir="ltr">Hardening::scrub()</code> از مسیرهای مطلق پاک می‌شوند.</p>';
		echo '</div>';

		echo '<table class="widefat striped" style="max-width:960px;background:#fff;margin-top:14px"><thead><tr><th style="width:34%">مورد</th><th style="width:90px">وضعیت</th><th>توضیح</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$badge = 'ok' === $row['state']
				? '<span style="color:#0a7c3f;font-weight:700">🟢 سالم</span>'
				: '<span style="color:#a15c00;font-weight:700">🟠 نیازمند توجه</span>';
			echo '<tr><td><strong>' . esc_html( $row['label'] ) . '</strong></td><td>' . $badge . '</td><td>' . esc_html( $row['detail'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static badge markup built above.
		}
		echo '</tbody></table>';

		echo '<h2 style="margin-top:24px">پایش لاگ برای مسیر فایل</h2>';
		if ( ! $scan['readable'] ) {
			echo '<p class="description">فایل لاگ قابل خواندن نیست یا وجود ندارد؛ این وضعیت طبیعی است و به معنی نبودِ خطا نیست.</p>';
		} else {
			printf(
				'<p>در <code dir="ltr">%1$s</code> تعداد <strong>%2$d</strong> خط بررسی شد؛ <strong>%3$d</strong> خط شامل مسیر مطلق فایل بود.</p>',
				esc_html( $scan['file'] ),
				(int) $scan['lines'],
				(int) $scan['with_path']
			);
			if ( $scan['with_path'] > 0 ) {
				echo '<p class="description">هر خط با <code dir="ltr">Hardening::scrub()</code> پاک‌سازی شده و تنها نسخهٔ بدون مسیر نمایش داده می‌شود:</p>';
				echo '<pre dir="ltr" style="max-width:960px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:12px;font-size:12px">';
				foreach ( $scan['samples'] as $line ) {
					echo esc_html( $line ) . "\n";
				}
				echo '</pre>';
				echo '<p class="description">اگر مسیر در لاگ هست ولی در هیچ صفحه‌ای دیده نمی‌شود، یعنی لایهٔ مهار درست کار می‌کند. برای پاک‌سازی کامل، فایل لاگ را خالی کنید و <code dir="ltr">WP_DEBUG_DISPLAY</code> را در wp-config.php برابر false بگذارید.</p>';
			} else {
				echo '<p style="color:#0a7c3f">هیچ خطی از لاگ شامل مسیر مطلق فایل نیست. 🟢</p>';
			}
		}

		echo '<h2 style="margin-top:24px">توصیه‌های wp-config.php</h2>';
		echo '<pre dir="ltr" style="max-width:960px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:12px;font-size:12px">';
		echo esc_html(
			"// هیچ خطایی در مرورگر چاپ نشود، همه در لاگ بماند\n"
			. "define( 'WP_DEBUG', true );\n"
			. "define( 'WP_DEBUG_DISPLAY', false );\n"
			. "define( 'WP_DEBUG_LOG', true );\n"
			. "// ویرایش فایل از پیشخوان خاموش شود\n"
			. "define( 'DISALLOW_FILE_EDIT', true );\n"
			. "// نصب/حذف افزونه و قالب از پیشخوان (اگر لازم نیست)\n"
			. "define( 'DISALLOW_FILE_MODS', false );"
		);
		echo '</pre>';

		echo '</div>';
	}

	/**
	 * Scan the debug log for lines that contain an absolute server path.
	 *
	 * @return array{readable:bool,file:string,lines:int,with_path:int,samples:array<int,string>}
	 */
	private static function scan_log(): array {
		$file = defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG )
			? WP_DEBUG_LOG
			: ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/debug.log' : '' );

		$out = array(
			'readable'  => false,
			'file'      => Hardening::scrub( $file ),
			'lines'     => 0,
			'with_path' => 0,
			'samples'   => array(),
		);

		if ( '' === $file || ! @is_readable( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- probing a log file that may not exist.
			return $out;
		}

		$handle = @fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.PHP.NoSilencedErrors.Discouraged -- streaming read of a possibly large log.
		if ( ! $handle ) {
			return $out;
		}

		$out['readable'] = true;

		// Read at most the last 512 KB: a production debug.log can be hundreds
		// of megabytes and this panel must stay instant.
		$cap  = 524288;
		$size = (int) fstat( $handle )['size'];
		if ( $size > $cap ) {
			fseek( $handle, -$cap, SEEK_END );
			fgets( $handle ); // Drop the partial first line.
		}

		$tail = array();
		// Count a line when it repeats this install's own absolute path (the real
		// risk is /home/<user>/public_html/…) or another server root.
		$roots = array();
		foreach ( array( 'ABSPATH', 'WP_CONTENT_DIR', 'WP_PLUGIN_DIR' ) as $constant ) {
			if ( defined( $constant ) ) {
				$roots[] = untrailingslashit( (string) constant( $constant ) );
			}
		}
		$roots   = array_values( array_filter( array_unique( $roots ) ) );
		$pattern = $roots
			? '#(?:' . implode( '|', array_map( static fn( $r ) => preg_quote( $r, '#' ), $roots ) ) . ')#'
			: '#(?:/home/|/var/www|/srv/|/opt/)#';
		$seen    = 0;

		while ( false !== ( $line = fgets( $handle ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$seen;
			if ( preg_match( $pattern, $line ) ) {
				++$out['with_path'];
				$tail[] = Hardening::scrub( $line );
				if ( count( $tail ) > 15 ) {
					array_shift( $tail );
				}
			}
		}
		$out['lines'] = $seen;
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$out['samples'] = $tail;
		return $out;
	}
}
