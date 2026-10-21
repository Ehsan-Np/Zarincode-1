<?php
/**
 * The Footer for Zarincode Theme (Crextio Dark Bento Footer + Modals + Full Theme Options Integration)
 *
 * @package Zarincode
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$phone           = zarincode_get_option( 'contact_phone', '09024561001' );
$dev_name        = zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' );
$brand_name      = zarincode_get_option( 'brand_name', 'آژانس و استودیو زرین کد' );
$logo_monogram   = zarincode_get_option( 'logo_monogram', 'Z' );
$logo_dark       = zarincode_get_option( 'logo_dark', '' );
$show_cta_strip  = (bool) zarincode_get_option( 'footer_show_cta_strip', true );
$cta_heading     = zarincode_get_option( 'footer_cta_heading', 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟' );
$about_text      = zarincode_get_option( 'footer_about_text', 'زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهنده خدمات فوق‌تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت و شبکه‌های اجتماعی، تولید محتوا و مارکت‌پلیس افزونه‌ها، قالب‌های وردپرس و سورس‌کدهای آماده تجاری است.' );
$enamad_html     = zarincode_get_option( 'footer_enamad_html', '' );
$samandehi_html  = zarincode_get_option( 'footer_samandehi_html', '' );
$copyright_text  = zarincode_get_option( 'footer_copyright_text', 'تمامی حقوق مادی و معنوی این قالب و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه (Ehsan NaderiPanah) می‌باشد.' );
$modal_title     = zarincode_get_option( 'consult_modal_title', 'درخواست مشاوره رایگان و ثبت سفارش پروژه' );
$modal_desc      = zarincode_get_option( 'consult_modal_desc', 'فرم زیر را تکمیل کنید یا مستقیماً با شماره 09024561001 (احسان نادری پناه) تماس بگیرید. کارشناسان ما در کمتر از ۱۵ دقیقه با شما تماس خواهند گرفت.' );
?>

<?php $zc_custom_footer = zarincode_builder_part( 'footer' ); ?>
<?php if ( $zc_custom_footer !== '' ) : ?>
<footer role="contentinfo"><?php echo $zc_custom_footer; ?></footer>
<?php else : ?>
<footer class="zc-footer" role="contentinfo">
    <div class="zc-container">
        <div class="zc-footer-bento">
            <?php if ( $show_cta_strip ) : ?>
            <!-- Top Callout Strip inside Bento Footer -->
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1.5rem;padding:1.75rem 2rem;background:rgba(255,255,255,0.05);border-radius:1.5rem;margin-bottom:2.5rem;border:1px solid rgba(255,255,255,0.08);">
                <div>
                    <span class="zc-badge-pill" style="margin-bottom:0.4rem;">ZARINCODE DIGITAL AGENCY &amp; MARKETPLACE</span>
                    <h3 style="margin:0;font-size:1.45rem;font-weight:900;color:#FFFFFF;"><?php echo esc_html( $cta_heading ); ?></h3>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.85rem;">
                    <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-yellow">
                        <i class="fa-solid fa-phone-volume"></i> تماس مستقیم: <?php echo esc_html( $phone ); ?>
                    </a>
                    <button type="button" class="zc-btn" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);" data-zc-modal-open="zc-consult-modal">
                        <i class="fa-solid fa-paper-plane"></i> ثبت سفارش آنلاین پروژه
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="zc-footer-grid">
                <!-- Col 1: Brand & Developer Identity -->
                <div>
                    <div style="display:flex;align-items:center;gap:0.85rem;margin-bottom:1rem;">
                        <?php if ( ! empty( $logo_dark ) ) : ?>
                            <img src="<?php echo esc_url( $logo_dark ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" style="max-height:46px;width:auto;">
                        <?php else : ?>
                            <span style="width:2.8rem;height:2.8rem;border-radius:50%;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.3rem;"><?php echo esc_html( $logo_monogram ); ?></span>
                            <div>
                                <div style="font-size:1.25rem;font-weight:900;color:#FFFFFF;"><?php echo esc_html( $brand_name ); ?></div>
                                <div style="font-size:0.78rem;color:var(--color-accent);">Zarincode.com — Digital Engineering</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="color:#9CA3AF;font-size:0.9rem;line-height:1.85;margin-bottom:1.25rem;">
                        <?php echo esc_html( $about_text ); ?>
                    </p>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.88rem;color:#E5E7EB;">
                        <div><i class="fa-solid fa-user-tie" style="color:var(--color-accent);width:1.4rem;"></i> توسعه‌دهنده: <strong><?php echo esc_html( $dev_name ); ?></strong></div>
                        <div><i class="fa-solid fa-phone" style="color:var(--color-accent);width:1.4rem;"></i> خط ویژه مشاوره: <a href="tel:<?php echo esc_attr( $phone ); ?>" dir="ltr" style="color:var(--color-accent);font-weight:800;"><?php echo esc_html( $phone ); ?></a></div>
                        <div><i class="fa-solid fa-globe" style="color:var(--color-accent);width:1.4rem;"></i> وب‌سایت رسمی: <a href="<?php echo esc_url( zarincode_get_option( 'official_website', 'https://zarincode.com' ) ); ?>" style="color:#fff;">Zarincode.com</a></div>
                    </div>
                </div>

                <!-- Col 2: Core Services -->
                <div>
                    <h4 class="zc-footer-title">خدمات تخصصی زرین کد</h4>
                    <ul class="zc-footer-links">
                        <li><a href="<?php echo esc_url( home_url( '/service/website-design/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> طراحی سایت حرفه‌ای و المنتوری</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/software-development/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> برنامه‌نویسی نرم‌افزار و وب‌اپلیکیشن</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/website-seo/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> سئو سایت و بهینه‌سازی موتور جستجو</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/social-media-seo/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> سئو شبکه‌های اجتماعی (اینستاگرام و لینکدین)</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/content-creation/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> تولید محتوای تخصصی و استراتژی محتوا</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/ready-made-products/' ) ); ?>"><i class="fa-solid fa-angle-left"></i> فروشگاه قالب، افزونه و سورس‌کد آماده</a></li>
                    </ul>
                </div>

                <!-- Col 3: Legal, Compliance & Policies -->
                <div>
                    <h4 class="zc-footer-title">قوانین و اسناد حقوقی</h4>
                    <ul class="zc-footer-links">
                        <li><a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>"><i class="fa-solid fa-scale-balanced"></i> قوانین و شرایط و مقررات کشور ایران</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>"><i class="fa-solid fa-shield-halved"></i> شرایط گارانتی و پشتیبانی فنی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><i class="fa-solid fa-user-shield"></i> سیاست حفظ حریم خصوصی کاربران</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><i class="fa-solid fa-rotate-left"></i> شرایط و ضوابط بازگشت وجه</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/gdpr-compliance/' ) ); ?>"><i class="fa-solid fa-earth-europe"></i> قوانین حفاظت داده اتحادیه اروپا (GDPR)</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/dmca-policy/' ) ); ?>"><i class="fa-solid fa-copyright"></i> DMCA Takedown Policy (English)</a></li>
                    </ul>
                </div>

                <!-- Col 4: Trust Badges & Quick Links -->
                <div>
                    <h4 class="zc-footer-title">مجوزها و ضمانت‌های رسمی</h4>
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;margin-bottom:1.25rem;">
                        <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:0.85rem;text-align:center;">
                            <?php if ( ! empty( $enamad_html ) ) : ?>
                                <?php echo wp_kses_post( $enamad_html ); ?>
                            <?php else : ?>
                                <i class="fa-solid fa-award" style="font-size:1.6rem;color:var(--color-accent);margin-bottom:0.35rem;"></i>
                                <div style="font-size:0.76rem;font-weight:700;color:#fff;">نماد اعتماد و ساماندهی</div>
                                <div style="font-size:0.68rem;color:#9CA3AF;">مطابق قوانین ج.ا.ایران</div>
                            <?php endif; ?>
                        </div>
                        <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:0.85rem;text-align:center;">
                            <?php if ( ! empty( $samandehi_html ) ) : ?>
                                <?php echo wp_kses_post( $samandehi_html ); ?>
                            <?php else : ?>
                                <i class="fa-solid fa-medal" style="font-size:1.6rem;color:var(--color-accent);margin-bottom:0.35rem;"></i>
                                <div style="font-size:0.76rem;font-weight:700;color:#fff;">ضمانت ۱۰۰٪ بازگشت وجه</div>
                                <div style="font-size:0.68rem;color:#9CA3AF;">گارانتی یک‌ساله زرین کد</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                        <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="zc-btn" style="padding:0.45rem 0.95rem;font-size:0.8rem;background:rgba(255,255,255,0.08);color:#fff;">درباره زرین کد</a>
                        <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>" class="zc-btn" style="padding:0.45rem 0.95rem;font-size:0.8rem;background:rgba(255,255,255,0.08);color:#fff;">نمونه‌کارها</a>
                        <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" class="zc-btn" style="padding:0.45rem 0.95rem;font-size:0.8rem;background:rgba(255,255,255,0.08);color:#fff;">تماس با ما</a>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright & Standards Note -->
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding-top:1.5rem;font-size:0.84rem;color:#9CA3AF;">
                <div>
                    <?php echo esc_html( $copyright_text ); ?>
                </div>
                <div style="display:flex;align-items:center;gap:1rem;">
                    <span>پشتیبانی و فروش: <strong dir="ltr" style="color:var(--color-accent);"><?php echo esc_html( $phone ); ?></strong></span>
                    <span>•</span>
                    <span>طراحی بر پایه استانداردهای Zhaket &amp; ThemeForest</span>
                </div>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Global Consultation Request Modal (Webinex Popup + Crextio UI) -->
<div id="zc-consult-modal" class="zc-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="zc-consult-title">
    <div class="zc-modal-dialog">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <div>
                <span class="zc-badge-pill" style="margin-bottom:0.25rem;">FREE CONSULTATION</span>
                <h3 id="zc-consult-title" style="margin:0;font-size:1.3rem;font-weight:900;"><?php echo esc_html( $modal_title ); ?></h3>
            </div>
            <button type="button" data-zc-modal-close style="width:36px;height:36px;border-radius:50%;border:none;background:rgba(0,0,0,0.07);cursor:pointer;font-size:1.2rem;">×</button>
        </div>
        <p style="font-size:0.9rem;color:var(--zc-text-secondary);margin-top:0;margin-bottom:1.25rem;">
            <?php echo esc_html( $modal_desc ); ?>
        </p>
        <form class="zc-Ajax-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;">
                <div class="zc-form-group">
                    <label class="zc-form-label">نام و نام خانوادگی *</label>
                    <input type="text" class="zc-form-control" required placeholder="مثلاً: علی محمدی">
                </div>
                <div class="zc-form-group">
                    <label class="zc-form-label">شماره موبایل *</label>
                    <input type="tel" class="zc-form-control" required placeholder="0912XXXXXXX" dir="ltr">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;">
                <div class="zc-form-group">
                    <label class="zc-form-label">سرویس یا محصول مد نظر</label>
                    <select class="zc-form-control">
                        <option value="web-design">طراحی سایت اختصاصی و المنتوری</option>
                        <option value="software">برنامه‌نویسی نرم‌افزار و سامانه تحت وب</option>
                        <option value="seo">سئو سایت تضمینی و رشد گوگل</option>
                        <option value="social-seo">سئو شبکه‌های اجتماعی (اینستاگرام/لینکدین)</option>
                        <option value="content">تولید محتوای تخصصی و سئو شده</option>
                        <option value="ready-products">خرید قالب، افزونه یا سورس‌کد آماده</option>
                    </select>
                </div>
                <div class="zc-form-group">
                    <label class="zc-form-label">اولویت زمانی پروژه</label>
                    <select class="zc-form-control">
                        <option value="normal">عادی (کیفیت و زمان‌بندی استاندارد)</option>
                        <option value="urgent">فوریتی (نیاز به تحویل سریع دارم)</option>
                        <option value="relaxed">غیرفوریتی (تمرکز حداکثری بر جزئیات)</option>
                    </select>
                </div>
            </div>
            <div class="zc-form-group">
                <label class="zc-form-label">توضیحات پروژه یا محصول مورد نیاز</label>
                <textarea class="zc-form-control" rows="3" placeholder="کوتاه در مورد اهداف کسب‌وکار یا پروژه خود بنویسید..."></textarea>
            </div>
            <div class="zc-form-feedback" style="display:none;padding:0.85rem 1rem;border-radius:1rem;background:var(--color-accent);color:var(--color-accent-contrast);font-weight:700;font-size:0.88rem;margin-bottom:1rem;"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <button type="submit" class="zc-btn zc-btn-yellow" style="flex:1;">
                    <i class="fa-solid fa-paper-plane"></i> ثبت درخواست مشاوره رایگان
                </button>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-dark">
                    <i class="fa-solid fa-phone"></i> <?php echo esc_html( $phone ); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Quick Search Modal (Ctrl + K) -->
<div id="zc-search-modal" class="zc-modal-backdrop" role="dialog" aria-modal="true" aria-label="جستجو در زرین کد">
    <div class="zc-modal-dialog">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h4 style="margin:0;font-weight:800;"><i class="fa-solid fa-magnifying-glass" style="color:var(--color-accent);"></i> جستجوی سریع خدمات، افزونه‌ها و سورس‌کدهای زرین کد</h4>
            <button type="button" data-zc-modal-close style="width:32px;height:32px;border-radius:50%;border:none;background:rgba(0,0,0,0.07);cursor:pointer;">×</button>
        </div>
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div style="display:flex;gap:0.65rem;">
                <input type="search" name="s" class="zc-form-control" placeholder="نام سرویس، افزونه وردپرس، قالب یا سورس‌کد را وارد کنید..." value="<?php echo get_search_query(); ?>">
                <button type="submit" class="zc-btn zc-btn-yellow">جستجو</button>
            </div>
        </form>
        <div style="margin-top:1.15rem;display:flex;flex-wrap:wrap;gap:0.5rem;font-size:0.8rem;">
            <span style="color:var(--zc-text-muted);font-weight:700;">دسترسی سریع:</span>
            <a href="<?php echo esc_url( home_url( '/service/website-design/' ) ); ?>" class="zc-meta-tag">طراحی سایت</a>
            <a href="<?php echo esc_url( home_url( '/service/software-development/' ) ); ?>" class="zc-meta-tag">برنامه‌نویسی نرم‌افزار</a>
            <a href="<?php echo esc_url( home_url( '/service/website-seo/' ) ); ?>" class="zc-meta-tag">سئو سایت</a>
            <a href="<?php echo esc_url( home_url( '/service/social-media-seo/' ) ); ?>" class="zc-meta-tag">سئو شبکه‌های اجتماعی</a>
            <a href="<?php echo esc_url( home_url( '/service/content-creation/' ) ); ?>" class="zc-meta-tag">تولید محتوا</a>
            <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="zc-meta-tag">فروشگاه سورس و افزونه</a>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
