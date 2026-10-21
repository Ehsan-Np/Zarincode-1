<?php
/**
 * Single Digital Product Template (Zhaket & ThemeForest Marketplace Layout + Crextio Sunshine UI)
 *
 * Renders the dedicated inner page for Ready-Made Digital Products (WordPress Themes,
 * Plugins, and Software Source Codes) with sticky license/purchase box, interactive
 * technical tabs, developer profile card, and related products.
 *
 * @package Zarincode
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();
    $post_id      = get_the_ID();
    $slug         = get_post_field( 'post_name', $post_id );
    $title        = get_the_title();
    $phone        = zarincode_get_option( 'contact_phone', '09024561001' );
    $dev_name     = zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' );
    $dev_img      = get_template_directory_uri() . '/assets/images/ehsan-contact-consultation.jpg';

    // Product metadata with rich defaults
    $raw_price    = get_post_meta( $post_id, '_zc_prod_price_display', true );
    if ( ! $raw_price ) {
        $wc_price  = get_post_meta( $post_id, '_regular_price', true ) ?: '2490000';
        $raw_price = zarincode_fa_digits( number_format( (int) $wc_price ) );
    }
    $old_price    = get_post_meta( $post_id, '_zc_prod_old_price', true ) ?: '۳,۹۰۰,۰۰۰';
    $version      = get_post_meta( $post_id, '_zc_prod_version', true ) ?: 'v2.5.0';
    $cat_badge    = get_post_meta( $post_id, '_zc_prod_cat_badge', true ) ?: 'محصول دیجیتال آماده';
    $sales_cnt    = get_post_meta( $post_id, '_zc_prod_sales_count', true ) ?: '+۳۸۰ فروش موفق';
    $compat       = get_post_meta( $post_id, '_zc_prod_compat', true ) ?: 'WordPress 6.x / 7.x • PHP 8.1 - 8.4 • Elementor & WooCommerce';
    $icon_class   = get_post_meta( $post_id, '_zc_prod_icon', true ) ?: 'fa-solid fa-Layer-group';
    $tags_str     = get_post_meta( $post_id, '_zc_prod_tags', true ) ?: 'PHP 8.4,Elementor,WooCommerce,Open Source,RTL Ready';
    $tags         = array_map( 'trim', explode( ',', $tags_str ) );
    $updated_date = get_post_meta( $post_id, '_zc_prod_updated', true ) ?: 'مهر ۱۴۰۵';

    $add_to_cart_url = function_exists( 'wc_get_cart_url' ) ? add_query_arg( 'add-to-cart', $post_id, wc_get_cart_url() ) : '#zc-consult-modal';
    ?>

    <main id="primary" class="zc-container zc-single-product-wrap" style="padding-top:2rem;padding-bottom:4.5rem;">
        <!-- Breadcrumb Bar -->
        <nav class="zc-breadcrumb-bar" aria-label="Breadcrumb" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;padding:0.85rem 1.35rem;background:rgba(255,255,255,0.65);border:1px solid var(--zc-border-subtle);border-radius:1rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.55rem;font-size:0.84rem;color:var(--zc-text-secondary);">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;"><i class="fa-solid fa-house"></i> صفحه اصلی</a>
                <span>/</span>
                <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;">فروشگاه محصولات آماده</a>
                <span>/</span>
                <span style="color:var(--color-accent-contrast);background:var(--color-accent);padding:0.15rem 0.65rem;border-radius:999px;font-weight:800;font-size:0.76rem;"><?php echo esc_html( $cat_badge ); ?></span>
                <span>/</span>
                <span style="font-weight:800;color:var(--zc-text-primary);"><?php echo esc_html( $title ); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;font-size:0.8rem;">
                <span style="color:#059669;font-weight:800;"><i class="fa-solid fa-circle-check"></i> اورجینال و تست‌شده در آزمایشگاه فنی زرین کد</span>
                <span class="zc-meta-tag" dir="ltr"><?php echo esc_html( $version ); ?></span>
            </div>
        </nav>

        <!-- Main 2-Column Marketplace Grid -->
        <div class="zc-single-product-grid">
            <!-- Right Column: Product Showcase, Preview & Interactive Technical Tabs -->
            <div class="zc-single-product-main">
                <!-- Product Hero Card -->
                <div class="zc-glass-panel" style="padding:2.25rem;margin-bottom:1.75rem;">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1rem;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;">
                            <span class="zc-badge-pill" style="margin-bottom:0;"><i class="fa-solid fa-award"></i> <?php echo esc_html( $cat_badge ); ?></span>
                            <span class="zc-trust-pill" style="padding:0.25rem 0.75rem;font-size:0.76rem;"><i class="fa-solid fa-star" style="color:#F59E0B;"></i> امتیاز ۵.۰ از ۵.۰ (رضایت ۱۰۰٪ خریداران)</span>
                        </div>
                        <span style="font-size:0.8rem;color:var(--zc-text-muted);font-weight:700;">
                            <i class="fa-regular fa-calendar-check"></i> آخرین بروزرسانی: <?php echo esc_html( $updated_date ); ?>
                        </span>
                    </div>

                    <h1 style="font-size:clamp(1.3rem,1.95vw,1.65rem);font-weight:900;margin:0 0 1.15rem;line-height:1.5;color:var(--zc-text-primary);">
                        <?php echo esc_html( $title ); ?>
                    </h1>

                    <!-- Visual Showcase Box (Crextio Dark & Sunshine Presentation) -->
                    <div class="zc-dark-card" style="padding:2.25rem;margin-bottom:1.5rem;background:linear-gradient(135deg,#2A2C31 0%,#16181D 100%);border:2px solid rgba(253,208,91,0.35);">
                        <div style="display:grid;grid-template-columns:1fr auto;gap:1.5rem;align-items:center;">
                            <div>
                                <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(253,208,91,0.15);color:var(--color-accent);padding:0.35rem 0.9rem;border-radius:999px;font-size:0.78rem;font-weight:800;margin-bottom:0.85rem;">
                                    <i class="fa-solid fa-code"></i> ZARINCODE OFFICIAL COMMERCIAL RELEASE • <?php echo esc_html( $version ); ?>
                                </div>
                                <h2 style="font-size:1.12rem;font-weight:900;color:#fff;margin:0 0 0.65rem;line-height:1.55;">
                                    معماری استاندارد شیءگرا (OOP)، سرعت فوق‌العاده و سورس‌کد ۱۰۰٪ باز
                                </h2>
                                <p style="color:#D1D5DB;font-size:0.9rem;line-height:1.85;margin:0 0 1.25rem;">
                                    <?php echo wp_kses_post( get_the_excerpt() ?: get_the_content() ); ?>
                                </p>
                                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                                    <?php foreach ( $tags as $t ) : ?>
                                        <span style="background:rgba(255,255,255,0.08);color:#FDE68A;border:1px solid rgba(253,208,91,0.25);padding:0.25rem 0.75rem;border-radius:999px;font-size:0.76rem;font-weight:700;" dir="ltr">
                                            #<?php echo esc_html( $t ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div style="text-align:center;">
                                <div style="width:5.5rem;height:5.5rem;border-radius:1.6rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:2.4rem;box-shadow:0 14px 32px rgba(253,208,91,0.35);margin:0 auto 0.6rem;">
                                    <i class="<?php echo esc_attr( $icon_class ); ?>"></i>
                                </div>
                                <div style="font-size:0.74rem;color:#9CA3AF;font-weight:700;" dir="ltr">SKU: <?php echo esc_html( get_post_meta( $post_id, '_sku', true ) ?: 'ZC-PRO' ); ?></div>
                            </div>
                        </div>

                        <!-- 4 Quick Product KPIs inside Visual Banner -->
                        <div class="zc-prod-kpi-strip" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.85rem;margin-top:1.75rem;padding-top:1.35rem;border-top:1px solid rgba(255,255,255,0.1);">
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:var(--color-accent);"><?php echo esc_html( $sales_cnt ); ?></div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">تعداد فروش و نصب فعال</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:#34D399;">۱۰۰٪ سورس باز</div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">بدون IonCube یا قفل</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:#fff;" dir="ltr"><?php echo esc_html( $version ); ?></div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">نسخه پایدار و نهایی</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:var(--color-accent);">آپدیت دائمی</div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">+ ۶ ماه پشتیبانی رایگان</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Bar under Preview -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;background:rgba(253,208,91,0.14);border:1px solid rgba(253,208,91,0.45);border-radius:1.25rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <i class="fa-solid fa-bolt" style="font-size:1.35rem;color:var(--color-accent-contrast);background:var(--color-accent);width:2.4rem;height:2.4rem;border-radius:50%;display:flex;align-items:center;justify-content:center;"></i>
                            <div>
                                <div style="font-weight:900;font-size:0.92rem;">فایل کامل محصول + مستندات فارسی + درون‌ریز خودکار آماده دانلود است</div>
                                <div style="font-size:0.78rem;color:var(--zc-text-secondary);">بلافاصله پس از تکمیل سفارش، لینک دانلود مستقیم در پنل کاربری و ایمیل شما فعال می‌شود.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                            <button type="button" class="zc-btn zc-btn-dark" style="padding:0.55rem 1.15rem;font-size:0.82rem;" data-zc-modal-open="zc-consult-modal">
                                <i class="fa-solid fa-desktop"></i> درخواست دمو / مشاوره فنی
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Product Details Tabs (Overview, Tech Specs, Changelog, FAQ & Guarantee) -->
                <div class="zc-glass-panel" style="padding:2.25rem;">
                    <div class="zc-prod-tabs-nav" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;border-bottom:2px solid var(--zc-border-subtle);padding-bottom:1.1rem;margin-bottom:1.75rem;">
                        <button type="button" class="zc-btn zc-btn-yellow zc-prod-tab-btn is-active" data-prod-tab="tab-overview" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-file-lines"></i> معرفی و امکانات
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-specs" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-sliders"></i> مشخصات فنی
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-changelog" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-code-commit"></i> لیست تغییرات
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-faq" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-shield-halved"></i> گارانتی و سوالات
                        </button>
                    </div>

                    <!-- TAB 1: Overview & Key Features -->
                    <div id="tab-overview" class="zc-prod-tab-pane" style="display:block;">
                        <div style="font-size:0.96rem;line-height:2;color:var(--zc-text-primary);margin-bottom:2rem;">
                            <?php the_content(); ?>
                            <p>
                                این محصول در واحد مهندسی نرم‌افزار <strong>زرین کد (Zarincode.com)</strong> توسط <strong>احسان نادری پناه</strong> با رعایت دقیق‌ترین استانداردهای امنیتی، بهینه‌سازی کوئری‌ها و معماری ماژولار کدنویسی شده است. تمامی بخش‌ها به صورت فارسی و راست‌چین استاندارد (RTL) با فونت وزیرمتن طراحی شده‌اند و هیچ‌گونه وابستگی به سرورهای خارجی یا لایسنس‌های قفل‌شده ندارند.
                            </p>
                        </div>

                        <h3 style="font-size:1.2rem;font-weight:900;margin:0 0 1.25rem;">
                            <i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> قابلیت‌ها و مزیت‌های رقابتی این محصول
                        </h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;margin-bottom:1.5rem;">
                            <?php
                            $highlights = [
                                [ 'icon' => 'fa-solid fa-code', 'title' => 'سورس‌کد ۱۰۰٪ باز و تمیز (Clean OOP Code)', 'desc' => 'کدنویسی شیءگرا و استاندارد بدون هیچ‌گونه رمزنگاری IonCube جهت توسعه آسان.' ],
                                [ 'icon' => 'fa-solid fa-gauge-high', 'title' => 'بهینه‌سازی شده برای سرعت زیر ۱ ثانیه', 'desc' => 'کسب نمره بالای ۹۵ در GTmetrix و Google PageSpeed Insights.' ],
                                [ 'icon' => 'fa-solid fa-mobile-screen', 'title' => 'واکنش‌گرایی ۱۰۰٪ (فول ریسپانسیو)', 'desc' => 'نمایش بی‌نقص در موبایل، تبلت، لپ‌تاپ و مانیتورهای عریض.' ],
                                [ 'icon' => 'fa-solid fa-wand-magic-sparkles', 'title' => 'نصب و درون‌ریزی دمو با یک کلیک', 'desc' => 'راه‌اندازی کامل محصول دقیقاً مشابه پیش‌نمایش در کمتر از ۳ دقیقه.' ],
                                [ 'icon' => 'fa-solid fa-shield-halved', 'title' => 'امنیت چندلایه و استاندارد', 'desc' => 'ایمن‌سازی شده در برابر حملات XSS، CSRF و SQL Injection با Nonceهای استاندارد.' ],
                                [ 'icon' => 'fa-solid fa-headset', 'title' => 'پشتیبانی مستقیم احسان نادری پناه', 'desc' => '۶ ماه پشتیبانی فنی رایگان و پاسخگویی مستقیم تلفنی و تیکتی (09024561001).' ],
                            ];
                            foreach ( $highlights as $hl ) :
                                ?>
                                <div class="zc-white-card" style="padding:1.25rem;">
                                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                                        <span style="width:2.4rem;height:2.4rem;border-radius:0.75rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                                            <i class="<?php echo esc_attr( $hl['icon'] ); ?>"></i>
                                        </span>
                                        <h4 style="margin:0;font-size:0.95rem;font-weight:800;"><?php echo esc_html( $hl['title'] ); ?></h4>
                                    </div>
                                    <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;"><?php echo esc_html( $hl['desc'] ); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 2: Technical Specifications -->
                    <div id="tab-specs" class="zc-prod-tab-pane" style="display:none;">
                        <h3 style="font-size:1.15rem;font-weight:900;margin:0 0 1.25rem;">جدول مشخصات فنی و پیش‌نیازهای سرور</h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0.85rem;">
                            <?php
                            $specs = [
                                'نام محصول'                => $title,
                                'نسخه فعلی منتشر شده'      => $version . ' (نسخه نهایی و پایدار)',
                                'توسعه‌دهنده و مالک سورس'  => 'احسان نادری پناه (Zarincode.com)',
                                'سازگاری فنی'              => $compat,
                                'نسخه PHP مورد نیاز'       => 'PHP 8.0 / 8.1 / 8.2 / 8.3 / 8.4',
                                'تایپوگرافی و زبان'        => 'فارسی (RTL استاندارد) + فونت وزیرمتن (Vazirmatn)',
                                'نوع لایسنس عرضه شده'      => 'اورجینال، سورس باز (Open Source) و بدون محدودیت دامنه',
                                'نحوه تحویل فایل‌ها'       => 'تحویل و دانلود آنی بلافاصله پس از پرداخت',
                                'مستندات و فایل راهنما'    => 'دارای مستندات کامل فارسی گام‌به‌گام',
                                'پشتیبانی و بروزرسانی'     => '۶ ماه پشتیبانی رایگان + آپدیت مادام‌العمر',
                            ];
                            foreach ( $specs as $sk => $sv ) :
                                ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:0.95rem 1.15rem;background:rgba(255,255,255,0.8);border:1px solid var(--zc-border-subtle);border-radius:0.95rem;font-size:0.86rem;">
                                    <span style="color:var(--zc-text-secondary);font-weight:700;"><?php echo esc_html( $sk ); ?>:</span>
                                    <strong style="color:var(--zc-text-primary);text-align:left;"><?php echo esc_html( $sv ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 3: Changelog -->
                    <div id="tab-changelog" class="zc-prod-tab-pane" style="display:none;">
                        <h3 style="font-size:1.15rem;font-weight:900;margin:0 0 1.25rem;">تاریخچه بروزرسانی‌ها و تغییرات نسخه (Changelog)</h3>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <div class="zc-white-card" style="padding:1.35rem;border-right:4px solid var(--color-accent);">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                                    <strong style="font-size:0.98rem;">نسخه <?php echo esc_html( $version ); ?> — بروزرسانی بزرگ هسته و رابط کاربری Crextio</strong>
                                    <span class="zc-badge-pill" style="margin:0;">مهر ۱۴۰۵</span>
                                </div>
                                <ul style="margin:0;padding-right:1.2rem;font-size:0.86rem;color:var(--zc-text-secondary);line-height:1.9;">
                                    <li>سازگاری کامل با آخرین نسخه PHP 8.4 و وردپرس 7.x</li>
                                    <li>ارتقای پنل تنظیمات پیشرفته ۱۶ بخشی و نصب خودکار ایجکس افزونه‌ها</li>
                                    <li>بهینه‌سازی کامل استایل‌های واکنش‌گرا (موبایل و تبلت) و سرعت بارگذاری</li>
                                </ul>
                            </div>
                            <div class="zc-white-card" style="padding:1.35rem;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                                    <strong style="font-size:0.98rem;">نسخه v2.0.0 — افزودن ماژول‌های پیشرفته و درون‌ریز خودکار</strong>
                                    <span class="zc-meta-tag">تیر ۱۴۰۵</span>
                                </div>
                                <ul style="margin:0;padding-right:1.2rem;font-size:0.86rem;color:var(--zc-text-secondary);line-height:1.9;">
                                    <li>افزودن درون‌ریز یک‌کلیکی دمو و قالب‌های آماده المنتوری</li>
                                    <li>بهبود امنیت فرم‌های ایجکس و یکپارچگی با درگاه‌های پرداخت شاپرک</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: Guarantee & FAQ -->
                    <div id="tab-faq" class="zc-prod-tab-pane" style="display:none;">
                        <h3 style="font-size:1.15rem;font-weight:900;margin:0 0 1.25rem;">شرایط گارانتی، بازگشت وجه و سوالات متداول خریداران</h3>
                        <div class="zc-faq-list">
                            <div class="zc-faq-item is-open">
                                <button type="button" class="zc-faq-trigger">
                                    <span>۱. آیا سورس‌کد محصول کاملاً باز است یا روی دامنه قفل می‌شود؟</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="zc-faq-body">
                                    تمامی محصولات عرضه شده در مارکت‌پلیس زرین کد دارای <strong>سورس‌کد ۱۰۰٪ باز (Open Source)</strong> و بدون هیچ‌گونه قفل دامنه یا کدهای رمزنگاری شده (IonCube) هستند تا بتوانید با خیال راحت روی دامنه‌های خود نصب و سفارشی‌سازی کنید.
                                </div>
                            </div>
                            <div class="zc-faq-item">
                                <button type="button" class="zc-faq-trigger">
                                    <span>۲. نحوه دریافت فایل‌ها و پشتیبانی فنی پس از خرید چگونه است؟</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="zc-faq-body">
                                    بلافاصله پس از پرداخت، لینک دانلود پکیج کامل (شامل فایل محصول، افزونه‌های همراه، فایل درون‌ریز دمو و مستندات فارسی) فعال می‌شود. همچنین به مدت ۶ ماه از پشتیبانی مستقیم احسان نادری پناه (09024561001) برخوردار خواهید بود.
                                </div>
                            </div>
                            <div class="zc-faq-item">
                                <button type="button" class="zc-faq-trigger">
                                    <span>۳. آیا امکان نصب و راه‌اندازی رایگان توسط تیم زرین کد وجود دارد؟</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="zc-faq-body">
                                    بله! در صورت تمایل، پس از خرید می‌توانید اطلاعات هاست یا پیشخوان خود را برای واحد پشتیبانی ارسال کنید تا نصب اولیه و درون‌ریزی کامل دمو به صورت رایگان برای شما انجام شود.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left Column: Sticky Purchase & Developer Sidebar (Zhaket / ThemeForest Style) -->
            <aside class="zc-single-product-sidebar">
                <!-- Purchase & License Bento Card -->
                <div class="zc-dark-card" style="padding:2rem;margin-bottom:1.5rem;border:2px solid var(--color-accent);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <span class="zc-prod-badge"><i class="fa-solid fa-bolt"></i> تحویل و دانلود آنی</span>
                        <span style="font-size:0.78rem;color:#9CA3AF;text-decoration:line-through;"><?php echo esc_html( $old_price ); ?> تومان</span>
                    </div>

                    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:1.25rem;padding:1.15rem;text-align:center;margin-bottom:1.35rem;">
                        <div style="font-size:0.78rem;color:#D1D5DB;margin-bottom:0.25rem;">سرمایه‌گذاری خرید محصول (لایسنس دائمی):</div>
                        <div style="font-size:2rem;font-weight:900;color:var(--color-accent);line-height:1.2;">
                            <?php echo esc_html( $raw_price ); ?>
                            <span style="font-size:0.9rem;font-weight:700;color:#fff;">تومان</span>
                        </div>
                    </div>

                    <!-- License Options -->
                    <div style="margin-bottom:1.25rem;display:flex;flex-direction:column;gap:0.6rem;">
                        <label style="display:flex;align-items:center;gap:0.6rem;padding:0.75rem 0.95rem;background:rgba(253,208,91,0.14);border:1px solid var(--color-accent);border-radius:0.9rem;cursor:pointer;font-size:0.82rem;font-weight:700;color:#fff;">
                            <input type="radio" name="zc_license_type" checked style="accent-color:var(--color-accent);">
                            <span>لایسنس استاندارد نامحدود + آپدیت دائمی</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.6rem;padding:0.75rem 0.95rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:0.9rem;cursor:pointer;font-size:0.82rem;color:#D1D5DB;">
                            <input type="radio" name="zc_license_type" style="accent-color:var(--color-accent);">
                            <span>همراه با نصب و راه‌اندازی کامل دمو (رایگان)</span>
                        </label>
                    </div>

                    <!-- 6 SLA & Guarantee Checkmarks -->
                    <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:0.65rem;font-size:0.83rem;color:#E5E7EB;">
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> کدنویسی اختصاصی احسان نادری پناه</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> سورس ۱۰۰٪ باز (بدون قفل دامنه)</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> ۶ ماه پشتیبانی فنی رایگان و مستقیم</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> بروزرسانی رایگان مادام‌العمر</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> شامل مستندات فارسی و فایل درون‌ریز دمو</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:#34D399;"></i> ضمانت ۱۰۰٪ بازگشت وجه طبق قوانین سایت</li>
                    </ul>

                    <!-- Primary Purchase CTA Buttons -->
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <?php if ( function_exists( 'WC' ) ) : ?>
                            <a href="<?php echo esc_url( $add_to_cart_url ); ?>" class="zc-btn zc-btn-yellow" style="width:100%;padding:0.95rem;font-size:0.95rem;">
                                <i class="fa-solid fa-cart-shopping"></i> افزودن به سبد خرید و دانلود آنی
                            </a>
                        <?php endif; ?>
                        <button type="button" class="zc-btn <?php echo function_exists( 'WC' ) ? 'zc-btn-glass' : 'zc-btn-yellow'; ?>" style="width:100%;padding:0.85rem;font-size:0.9rem;<?php echo function_exists( 'WC' ) ? 'background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);' : ''; ?>" data-zc-modal-open="zc-consult-modal">
                            <i class="fa-solid fa-bolt"></i> خرید سریع / سفارش سفارشی‌سازی
                        </button>
                        <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn" style="width:100%;padding:0.75rem;font-size:0.84rem;background:rgba(255,255,255,0.06);color:#FDD05B;border:1px solid rgba(253,208,91,0.3);">
                            <i class="fa-solid fa-phone-volume"></i> مشاوره تلفنی پیش از خرید: <span dir="ltr"><?php echo esc_html( $phone ); ?></span>
                        </a>
                    </div>
                </div>

                <!-- Developer / Author Real Portrait Card -->
                <div class="zc-glass-panel" style="padding:1.5rem;">
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <img src="<?php echo esc_url( $dev_img ); ?>" alt="<?php echo esc_attr( $dev_name ); ?>" style="width:68px;height:68px;border-radius:1.1rem;object-fit:cover;border:2px solid var(--color-accent);flex-shrink:0;">
                        <div>
                            <span style="display:inline-block;background:var(--color-accent);color:var(--color-accent-contrast);padding:0.15rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:900;margin-bottom:0.25rem;">OFFICIAL AUTHOR</span>
                            <h4 style="margin:0;font-size:1rem;font-weight:900;"><?php echo esc_html( $dev_name ); ?></h4>
                            <div style="font-size:0.78rem;color:var(--zc-text-secondary);">معمار نرم‌افزار و مدیر فنی زرین کد</div>
                        </div>
                    </div>
                    <p style="font-size:0.82rem;color:var(--zc-text-secondary);line-height:1.8;margin:0 0 1rem;">
                        تمامی سورس‌کدها، قالب‌ها و افزونه‌های زرین کد مستقیماً توسط احسان نادری پناه کدنویسی، تست امنیتی و پشتیبانی می‌شوند.
                    </p>
                    <div style="display:flex;gap:0.5rem;">
                        <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="zc-btn zc-btn-dark" style="flex:1;padding:0.5rem;font-size:0.78rem;">رزومه توسعه‌دهنده</a>
                        <a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>" class="zc-btn zc-btn-glass" style="flex:1;padding:0.5rem;font-size:0.78rem;">شرایط گارانتی</a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Related Digital Products -->
        <section style="margin-top:3.5rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;">
                <h2 style="font-size:1.4rem;font-weight:900;margin:0;">سایر محصولات آماده و سورس‌کدهای <span class="zc-highlight">زرین کد</span></h2>
                <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="zc-btn zc-btn-dark" style="padding:0.5rem 1.2rem;font-size:0.82rem;">
                    مشاهده همه محصولات فروشگاه <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
            <?php
            if ( class_exists( '\Zarincode\Core\Widgets\Renderer' ) ) {
                echo \Zarincode\Core\Widgets\Renderer::render_products_marketplace( [ 'count' => 4, 'hide_header' => true ] );
            }
            ?>
        </section>
    </main>

    <script>
    (function(){
        const tabBtns = document.querySelectorAll('.zc-prod-tab-btn');
        const tabPanes = document.querySelectorAll('.zc-prod-tab-pane');
        tabBtns.forEach(function(btn){
            btn.addEventListener('click', function(){
                const target = btn.getAttribute('data-prod-tab');
                tabBtns.forEach(function(b){
                    b.classList.remove('zc-btn-yellow', 'is-active');
                    b.classList.add('zc-btn-glass');
                });
                tabPanes.forEach(function(p){ p.style.display = 'none'; });
                btn.classList.remove('zc-btn-glass');
                btn.classList.add('zc-btn-yellow', 'is-active');
                const pane = document.getElementById(target);
                if (pane) pane.style.display = 'block';
            });
        });
    })();
    </script>

<?php
endwhile;
get_footer();
