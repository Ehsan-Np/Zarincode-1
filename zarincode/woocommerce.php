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
$phone = zarincode_get_option( 'contact_phone', '09024561001' );
?>

<main id="primary" style="padding-top:2rem;padding-bottom:3.5rem;" role="main">
    <div class="zc-container">
        <!-- Store Hero Header -->
        <div class="zc-dark-card" style="padding:2.5rem 2.75rem;margin-bottom:0.5rem;">
            <div style="display:grid;grid-template-columns:1fr;gap:1.25rem;align-items:center;">
                <div>
                    <span class="zc-badge-pill"><i class="fa-solid fa-code-branch"></i> ZARINCODE DIGITAL MARKETPLACE</span>
                    <h1 style="font-size:clamp(1.6rem,2.6vw,2.25rem);font-weight:900;margin:0.4rem 0 0.75rem;color:#FFFFFF;line-height:1.4;">
                        <?php echo esc_html( zarincode_get_option( 'shop_hero_title', 'فروشگاه اختصاصی قالب وردپرس، افزونه و سورس‌کدهای آماده نرم‌افزار' ) ); ?>
                    </h1>
                    <p style="color:#D1D5DB;font-size:0.98rem;max-width:920px;margin:0 0 1.35rem;line-height:1.9;">
                        تمامی محصولات این فروشگاه به صورت ۱۰۰٪ اختصاصی توسط <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> کدنویسی و بهینه‌سازی شده‌اند و دارای <strong>پیش‌نمایش آنلاین دمو، ضمانت بازگشت وجه، آپدیت دائمی، کدهای تمیز و استاندارد ژاکت و تم‌فارست و پشتیبانی مستقیم (09024561001)</strong> هستند.
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                        <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-bolt" style="color:var(--color-accent);"></i> دانلود آنی بلافاصله پس از خرید</span>
                        <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-desktop" style="color:var(--color-accent);"></i> دارای پیش‌نمایش آنلاین دمو (Live Demo)</span>
                        <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-shield-halved" style="color:var(--color-accent);"></i> ۶ ماه گارانتی و پشتیبانی فنی رایگان</span>
                        <span class="zc-trust-pill" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.15);"><i class="fa-solid fa-code" style="color:var(--color-accent);"></i> سورس‌کد باز و کاملاً مستندسازی شده</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if ( class_exists( '\Zarincode\Runtime260\Widgets\Renderer' ) ) {
        echo \Zarincode\Runtime260\Widgets\Renderer::render_products_marketplace( [ 'count' => 8, 'show_filters' => true ] );
    } else {
        echo '<div class="zc-container">';
        woocommerce_content();
        echo '</div>';
    }
    ?>

    <!-- Store Guarantee, Refund Policy & Buyer FAQ Accordion Section -->
    <section style="padding:1rem 0 2rem;">
        <div class="zc-container">
            <div class="zc-glass-panel" style="padding:2.5rem;">
                <div class="zc-hero-grid" style="align-items:start;">
                    <!-- Right Column: Accordion Header & SLA Summary Card -->
                    <div>
                        <span class="zc-badge-pill"><i class="fa-solid fa-shield-halved"></i> WARRANTY, REFUND &amp; BUYER FAQ</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.65rem;">
                            شرایط گارانتی، بازگشت وجه و <span class="zc-highlight">سوالات متداول خریداران</span>
                        </h2>
                        <p style="color:var(--zc-text-secondary);font-size:0.95rem;line-height:1.95;margin-bottom:1.5rem;">
                            خرید از مارکت‌پلیس زرین کد با تضمین مستقیم <strong>احسان نادری پناه</strong> همراه است. پیش از خرید می‌توانید دمو آنلاین هر محصول را بررسی کنید و پس از خرید نیز از پشتیبانی مستقیم، آپدیت مادام‌العمر و ضمانت بازگشت وجه برخوردار شوید.
                        </p>

                        <div class="zc-dark-card" style="padding:1.65rem;margin-bottom:1.25rem;border:1.5px solid rgba(253,208,91,0.4);">
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.85rem;margin-bottom:1.25rem;">
                                <div style="background:rgba(255,255,255,0.06);padding:0.9rem;border-radius:1rem;">
                                    <div style="color:var(--color-accent);font-weight:900;font-size:0.95rem;margin-bottom:0.2rem;"><i class="fa-solid fa-code"></i> ۱۰۰٪ سورس باز</div>
                                    <div style="font-size:0.76rem;color:#D1D5DB;">بدون قفل دامنه یا IonCube</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.06);padding:0.9rem;border-radius:1rem;">
                                    <div style="color:#34D399;font-weight:900;font-size:0.95rem;margin-bottom:0.2rem;"><i class="fa-solid fa-rotate-left"></i> ضمانت بازگشت وجه</div>
                                    <div style="font-size:0.76rem;color:#D1D5DB;">تضمین ۱۰۰٪ تطابق با دمو</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.06);padding:0.9rem;border-radius:1rem;">
                                    <div style="color:#fff;font-weight:900;font-size:0.95rem;margin-bottom:0.2rem;"><i class="fa-solid fa-headset" style="color:var(--color-accent);"></i> ۶ ماه پشتیبانی</div>
                                    <div style="font-size:0.76rem;color:#D1D5DB;">پاسخگویی مستقیم فنی</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.06);padding:0.9rem;border-radius:1rem;">
                                    <div style="color:var(--color-accent);font-weight:900;font-size:0.95rem;margin-bottom:0.2rem;"><i class="fa-solid fa-arrows-rotate"></i> آپدیت دائمی</div>
                                    <div style="font-size:0.76rem;color:#D1D5DB;">دسترسی رایگان مادام‌العمر</div>
                                </div>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:0.65rem;">
                                <a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>" class="zc-btn zc-btn-yellow" style="padding:0.55rem 1.15rem;font-size:0.82rem;">
                                    <i class="fa-solid fa-file-shield"></i> شرایط گارانتی
                                </a>
                                <a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>" class="zc-btn" style="padding:0.55rem 1.15rem;font-size:0.82rem;background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);">
                                    <i class="fa-solid fa-hand-holding-dollar"></i> ضوابط بازگشت وجه
                                </a>
                                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn" style="padding:0.55rem 1.15rem;font-size:0.82rem;background:rgba(253,208,91,0.15);color:var(--color-accent);">
                                    <i class="fa-solid fa-phone"></i> <span dir="ltr"><?php echo esc_html( $phone ); ?></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Left Column: Redesigned Professional Interactive Accordion -->
                    <div class="zc-faq-list">
                        <div class="zc-faq-item is-open">
                            <button type="button" class="zc-faq-btn">
                                <span class="zc-faq-q-wrap">
                                    <span class="zc-faq-num">۰۱</span>
                                    <span>آیا سورس‌کد محصولات کاملاً باز است یا روی دامنه قفل می‌شود؟</span>
                                </span>
                                <span class="zc-faq-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="zc-faq-body">
                                تمامی قالب‌ها، افزونه‌ها و سورس‌کدهای عرضه شده در فروشگاه زرین کد دارای <strong>سورس‌کد ۱۰۰٪ باز (Open Source)</strong> و بدون هیچ‌گونه قفل دامنه یا کدهای رمزنگاری شده (IonCube) هستند تا بتوانید با خیال راحت روی دامنه‌های خود نصب و سفارشی‌سازی کنید.
                            </div>
                        </div>

                        <div class="zc-faq-item">
                            <button type="button" class="zc-faq-btn">
                                <span class="zc-faq-q-wrap">
                                    <span class="zc-faq-num">۰۲</span>
                                    <span>چگونه می‌توانم پیش‌نمایش آنلاین (دمو) هر محصول را قبل از خرید ببینم؟</span>
                                </span>
                                <span class="zc-faq-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="zc-faq-body">
                                روی هر کارت محصول و همچنین در صفحه اختصاصی هر محصول، دکمه <strong>«پیش‌نمایش آنلاین دمو (دمو)»</strong> قرار داده شده است که با کلیک روی آن می‌توانید محیط زنده و سرعت واقعی محصول را پیش از خرید بررسی نمایید.
                            </div>
                        </div>

                        <div class="zc-faq-item">
                            <button type="button" class="zc-faq-btn">
                                <span class="zc-faq-q-wrap">
                                    <span class="zc-faq-num">۰۳</span>
                                    <span>نحوه دریافت فایل‌ها و پشتیبانی فنی پس از خرید چگونه است؟</span>
                                </span>
                                <span class="zc-faq-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="zc-faq-body">
                                بلافاصله پس از پرداخت، لینک دانلود پکیج کامل (شامل فایل اصلی محصول، افزونه‌های همراه، بسته درون‌ریز خودکار دمو و مستندات فارسی) فعال می‌شود. همچنین به مدت <strong>۶ ماه از پشتیبانی مستقیم احسان نادری پناه (09024561001)</strong> برخوردار خواهید بود.
                            </div>
                        </div>

                        <div class="zc-faq-item">
                            <button type="button" class="zc-faq-btn">
                                <span class="zc-faq-q-wrap">
                                    <span class="zc-faq-num">۰۴</span>
                                    <span>آیا امکان نصب و راه‌اندازی رایگان توسط تیم زرین کد وجود دارد؟</span>
                                </span>
                                <span class="zc-faq-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="zc-faq-body">
                                بله! در صورت تمایل، پس از خرید می‌توانید اطلاعات پیشخوان یا هاست خود را برای واحد پشتیبانی ارسال کنید تا نصب اولیه و درون‌ریزی کامل دمو به صورت <strong>رایگان</strong> برای شما انجام شود.
                            </div>
                        </div>

                        <div class="zc-faq-item">
                            <button type="button" class="zc-faq-btn">
                                <span class="zc-faq-q-wrap">
                                    <span class="zc-faq-num">۰۵</span>
                                    <span>شرایط گارانتی ۱۰۰٪ بازگشت وجه در چه مواردی اعمال می‌شود؟</span>
                                </span>
                                <span class="zc-faq-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="zc-faq-body">
                                در صورتی که محصول خریداری‌شده دارای مغایرت فنی با امکانات معرفی‌شده یا دمو آنلاین باشد و تیم فنی ظرف ۴۸ ساعت کاری مشکل را برطرف نکند، <strong>۱۰۰٪ وجه پرداختی بدون قید و شرط</strong> به خریدار بازگردانده می‌شود.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
