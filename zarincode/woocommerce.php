<?php
/**
 * WooCommerce Digital Marketplace Template (Routes Single Product vs Shop Archive)
 *
 * @package Zarincode
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Route Single Digital Product pages directly to our dedicated single-product.php template!
if ( is_singular( 'product' ) ) {
    include get_template_directory() . '/single-product.php';
    return;
}

get_header();
?>

<main id="primary" class="zc-container" style="padding-top:2.5rem;padding-bottom:4rem;" role="main">
    <!-- Store Hero Header -->
    <div class="zc-dark-card" style="padding:2.75rem;margin-bottom:2.5rem;">
        <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;align-items:center;">
            <div>
                <span class="zc-badge-pill"><i class="fa-solid fa-code-branch"></i> ZARINCODE DIGITAL MARKETPLACE</span>
                <h1 style="font-size:clamp(1.75rem,3vw,2.6rem);font-weight:900;margin:0.4rem 0 0.85rem;color:#FFFFFF;">
                    <?php echo esc_html( zarincode_get_option( 'shop_hero_title', 'فروشگاه اختصاصی قالب وردپرس، افزونه و سورس‌کدهای آماده نرم‌افزار' ) ); ?>
                </h1>
                <p style="color:#D1D5DB;font-size:1rem;max-width:860px;margin:0 0 1.5rem;">
                    تمامی محصولات این فروشگاه به صورت ۱۰۰٪ اختصاصی توسط <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> کدنویسی و بهینه‌سازی شده‌اند و دارای <strong>ضمانت بازگشت وجه، آپدیت دائمی، کدهای تمیز و استاندارد ژاکت و تم‌فارست و پشتیبانی مستقیم (09024561001)</strong> هستند.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                    <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-bolt" style="color:var(--color-accent);"></i> دانلود آنی بلافاصله پس از خرید</span>
                    <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-shield-halved" style="color:var(--color-accent);"></i> ۶ ماه گارانتی و پشتیبانی فنی رایگان</span>
                    <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-code" style="color:var(--color-accent);"></i> سورس‌کد باز و کاملاً مستندسازی شده</span>
                </div>
            </div>
        </div>
    </div>

    <?php
    if ( class_exists( '\Zarincode\Core\Widgets\Renderer' ) ) {
        echo \Zarincode\Core\Widgets\Renderer::render_products_marketplace( [ 'count' => 8, 'show_filters' => true ] );
    } else {
        woocommerce_content();
    }
    ?>
</main>

<?php
get_footer();
