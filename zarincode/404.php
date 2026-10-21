<?php
/**
 * 404 Error Page Template (Crextio Sunshine Style)
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-container" style="padding:4rem 1.5rem;" role="main">
    <div class="zc-glass-panel" style="max-width:680px;margin:0 auto;padding:3.5rem 2rem;text-align:center;">
        <span class="zc-badge-pill">ERROR 404</span>
        <div style="font-size:5.5rem;font-weight:900;line-height:1;color:var(--zc-dark);margin:0.5rem 0;">۴۰۴</div>
        <h1 class="zc-section-title">صفحه مورد نظر شما در زرین کد یافت نشد!</h1>
        <p class="zc-section-desc" style="margin-bottom:2rem;">
            ممکن است آدرس صفحه تغییر کرده باشد یا حذف شده باشد. می‌توانید از طریق دکمه‌های زیر به صفحه اصلی یا فروشگاه سورس‌کد و افزونه‌های آماده بروید.
        </p>
        <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="zc-btn zc-btn-yellow">
                <i class="fa-solid fa-house"></i> بازگشت به صفحه اصلی
            </a>
            <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="zc-btn zc-btn-dark">
                <i class="fa-solid fa-store"></i> فروشگاه محصولات آماده
            </a>
        </div>
    </div>
</main>

<?php
get_footer();
