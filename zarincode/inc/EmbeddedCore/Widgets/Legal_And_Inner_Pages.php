<?php
/**
 * Complete Inner Pages & Legal Compliance Suite Renderer for Zarincode
 *
 * Includes: Services, Portfolio, About Us, Contact Us, Terms & Iranian Laws,
 * Warranty Policy, Privacy Policy, Refund Policy, GDPR Compliance, and English DMCA Policy.
 *
 * @package Zarincode\Core\Widgets
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Legal_And_Inner_Pages {

    public static function render_legal_sidebar( string $active ): string {
        $links = [
            'terms'    => [ 'url' => home_url( '/terms-and-conditions/' ), 'icon' => 'fa-solid fa-scale-balanced', 'label' => 'قوانین و شرایط + قوانین ایران' ],
            'warranty' => [ 'url' => home_url( '/warranty-policy/' ),      'icon' => 'fa-solid fa-shield-halved',  'label' => 'شرایط گارانتی و پشتیبانی' ],
            'privacy'  => [ 'url' => home_url( '/privacy-policy/' ),       'icon' => 'fa-solid fa-user-shield',    'label' => 'سیاست حفظ حریم خصوصی' ],
            'refund'   => [ 'url' => home_url( '/refund-policy/' ),        'icon' => 'fa-solid fa-rotate-left',    'label' => 'شرایط ضمانت بازگشت وجه' ],
            'gdpr'     => [ 'url' => home_url( '/gdpr-compliance/' ),      'icon' => 'fa-solid fa-earth-europe',   'label' => 'قوانین حفاظت داده (GDPR)' ],
            'dmca'     => [ 'url' => home_url( '/dmca-policy/' ),          'icon' => 'fa-solid fa-copyright',      'label' => 'DMCA Policy (English)' ],
        ];
        ob_start();
        ?>
        <aside class="zc-glass-panel zc-legal-sidebar">
            <div style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid var(--zc-border-subtle);">
                <span class="zc-badge-pill" style="margin-bottom:0.35rem;">LEGAL CENTER</span>
                <h4 style="margin:0;font-size:1.05rem;font-weight:900;">مرکز قوانین و اسناد حقوقی زرین کد</h4>
            </div>
            <ul class="zc-legal-nav">
                <?php foreach ( $links as $k => $item ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo $active === $k ? 'is-active' : ''; ?>">
                            <span><i class="<?php echo esc_attr( $item['icon'] ); ?>" style="margin-left:0.45rem;"></i> <?php echo esc_html( $item['label'] ); ?></span>
                            <i class="fa-solid fa-angle-left" style="font-size:0.75rem;"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="zc-dark-card" style="padding:1.25rem;margin-top:1.25rem;">
                <div style="font-size:0.78rem;color:var(--color-accent);font-weight:700;margin-bottom:0.35rem;">واحد حقوقی و پشتیبانی قراردادها</div>
                <div style="font-size:0.9rem;font-weight:800;color:#fff;margin-bottom:0.25rem;">احسان نادری پناه</div>
                <div style="font-size:0.82rem;color:#D1D5DB;margin-bottom:0.75rem;">شماره تماس مستقیم: <strong dir="ltr" style="color:var(--color-accent);">09024561001</strong></div>
                <a href="tel:09024561001" class="zc-btn zc-btn-yellow" style="width:100%;padding:0.5rem;font-size:0.82rem;">تماس با واحد حقوقی</a>
            </div>
        </aside>
        <?php
        return ob_get_clean();
    }

    public static function render( string $key ): string {
        switch ( $key ) {
            case 'services-page':
                return self::render_services_full_page();
            case 'projects-page':
                return self::render_projects_full_page();
            case 'about-page':
                return self::render_about_full_page();
            case 'contact-page':
                return self::render_contact_full_page();
            case 'terms':
                return self::render_terms_iran_laws();
            case 'warranty':
                return self::render_warranty_policy();
            case 'privacy':
                return self::render_privacy_policy();
            case 'refund':
                return self::render_refund_policy();
            case 'gdpr':
                return self::render_gdpr_policy();
            case 'dmca':
                return self::render_dmca_english();
            default:
                return self::render_about_full_page();
        }
    }

    /**
     * SERVICES FULL PAGE (Includes Webinex Single Service Conditions Table + All 6 Services + FAQ)
     */
    private static function render_services_full_page(): string {
        ob_start();
        ?>
        <div class="zc-container" style="padding-top:2rem;padding-bottom:3rem;">
            <!-- Hero Header -->
            <div class="zc-glass-panel" style="padding:2.75rem;margin-bottom:2.5rem;">
                <span class="zc-badge-pill">!HERE'S WHAT WE OFFER TO HELP GROW YOUR BUSINESS</span>
                <h1 class="zc-section-title">آنچه در استودیو <span class="zc-highlight">زرین کد</span> برای رشد کسب‌وکار شما ارائه می‌دهیم!</h1>
                <p style="font-size:1rem;color:var(--zc-text-secondary);line-height:1.95;max-width:960px;">
                    ما در آژانس طراحی سایت، مهندسی نرم‌افزار و سئو <strong>زرین کد (Zarincode.com)</strong> به مدیریت <strong>احسان نادری پناه (09024561001)</strong>، با ترکیب دانش برنامه‌نویسی سطح بالا، متد طراحی Crextio و الگوریتم‌های روز گوگل و شبکه‌های اجتماعی، ۶ دپارتمان تخصصی را برای موفقیت آنلاین شما فراهم کرده‌ایم:
                </p>
                <div class="zc-hero-trust-list">
                    <span class="zc-trust-pill"><i class="fa-solid fa-circle-check"></i> ارائه مشاوره تخصصی رایگان برای تمامی سرویس‌ها</span>
                    <span class="zc-trust-pill"><i class="fa-solid fa-circle-check"></i> ضمانت بازگشت وجه در صورت عدم تحقق توافقات</span>
                    <span class="zc-trust-pill"><i class="fa-solid fa-circle-check"></i> پشتیبانی فنی یک‌ساله رایگان برای پروژه‌ها</span>
                </div>
            </div>

            <?php echo Renderer::render_services_bento(); ?>

            <!-- Webinex Single Service Conditions & SLA Table -->
            <div class="zc-glass-panel" style="padding:2.5rem;margin:2rem 0 3rem;">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:2rem;">
                    <div>
                        <span class="zc-badge-pill">SERVICE LEVEL AGREEMENT (SLA)</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.25rem;">شرایط استاندارد ارائه خدمات در زرین کد</h2>
                        <p class="zc-section-desc">شفافیت کامل در نحوه اجرای پروژه‌های طراحی سایت، نرم‌افزار، سئو و تولید محتوا (مطابق الگوی استاندارد Webinex)</p>
                    </div>
                    <div class="zc-yellow-card" style="padding:1rem 1.5rem;text-align:center;">
                        <div style="font-size:0.78rem;font-weight:700;">امتیاز رضایت کارفرمایان</div>
                        <div style="font-size:1.5rem;font-weight:900;">۵.۰ از ۵ (۸۴۰+ نظر ثبت شده)</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">نحوه انجام خدمت:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-laptop-house" style="color:var(--color-accent);"></i> دورکاری و حضوری (قرارداد رسمی)</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">گارانتی بازگشت وجه:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;color:#10B981;"><i class="fa-solid fa-circle-check"></i> دارد (۱۰۰٪ تضمینی)</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">ضمانت کیفیت کدنویسی:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-medal" style="color:var(--color-accent);"></i> دارد (استاندارد ژاکت و تم‌فارست)</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">شرایط پرداخت:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-credit-card" style="color:var(--color-accent);"></i> ۵۰٪ پیش‌پرداخت + ۵۰٪ تحویل نهایی</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">مدت زمان اجرا:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-regular fa-clock" style="color:var(--color-accent);"></i> ۱۵ تا ۴۵ روز کاری</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">ارائه گزارش از روند کار:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-chart-pie" style="color:var(--color-accent);"></i> ۵ مرحله در طول اجرای پروژه</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">اصلاح و بازبینی رایگان:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--color-accent);"></i> تا ۳ بار پس از تحویل اولیه</div>
                    </div>
                    <div class="zc-white-card" style="padding:1.25rem;">
                        <div style="font-size:0.78rem;color:var(--zc-text-muted);">نحوه تحویل نهایی:</div>
                        <div style="font-size:1.05rem;font-weight:800;margin-top:0.25rem;"><i class="fa-solid fa-file-zipper" style="color:var(--color-accent);"></i> سورس کامل + مستندات و ویدئو آموزشی</div>
                    </div>
                </div>
            </div>

            <?php echo Renderer::render_project_steps(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * PROJECTS / PORTFOLIO FULL PAGE
     */
    private static function render_projects_full_page(): string {
        ob_start();
        ?>
        <div class="zc-container" style="padding-top:2rem;padding-bottom:3rem;">
            <div class="zc-glass-panel" style="padding:2.75rem;margin-bottom:1.5rem;">
                <span class="zc-badge-pill">AN OVERVIEW OF OUR SUCCESSFUL PROJECTS</span>
                <h1 class="zc-section-title">چشم‌اندازی از <span class="zc-highlight">پروژه‌های موفق</span> زرین کد</h1>
                <p style="font-size:1rem;color:var(--zc-text-secondary);line-height:1.95;">
                    هر پروژه برای تیم <strong>احسان نادری پناه در زرین کد (Zarincode.com)</strong> فرصتی برای خلق یک شاهکار مهندسی و تجاری بوده است. در ادامه می‌توانید نمونه‌کارهای طراحی سایت، نرم‌افزارهای اختصاصی، پروژه‌های سئو و تولید محتوا را بررسی نمایید.
                </p>
            </div>
            <?php echo Renderer::render_portfolio_showcase(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * ABOUT US FULL PAGE (Featuring Ehsan NaderiPanah & Zarincode Team + Real Executive Photography)
     */
    private static function render_about_full_page(): string {
        $founder_img = get_template_directory_uri() . '/assets/images/ehsan-about-founder.jpg';
        $office_img  = get_template_directory_uri() . '/assets/images/zarincode-luxury-office.jpg';
        $studio_img  = get_template_directory_uri() . '/assets/images/ehsan-portrait.png';
        ob_start();
        ?>
        <div class="zc-container" style="padding-top:2rem;padding-bottom:3rem;">
            <div class="zc-glass-panel" style="padding:2.75rem;margin-bottom:2.5rem;">
                <div class="zc-hero-grid" style="align-items:center;">
                    <div>
                        <span class="zc-badge-pill">GET TO KNOW ZARINCODE BETTER</span>
                        <h1 class="zc-section-title">درباره آژانس و استودیو مهندسی <span class="zc-highlight">زرین کد</span></h1>
                        <p style="color:var(--zc-text-secondary);line-height:1.95;font-size:1rem;">
                            استودیو نرم‌افزاری و دیجیتال مارکتینگ <strong>زرین کد (Zarincode.com)</strong> به مدیریت و راهبری فنی <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> با هدف ارتقای استانداردهای طراحی وب، برنامه‌نویسی نرم‌افزار، سئو داده‌محور و تولید محصولات آماده وردپرسی و نرم‌افزاری شکل گرفته است.
                        </p>
                        <p style="color:var(--zc-text-secondary);line-height:1.95;font-size:1rem;">
                            ما معتقدیم یک وب‌سایت یا نرم‌افزار موفق، نقطه‌ی تلاقی <strong>کدنویسی تمیز و شیءگرا (PHP/JS)</strong>، <strong>رابط کاربری چشم‌نواز (به سبک Crextio)</strong> و <strong>استراتژی سئو و محتوای هدفمند</strong> است.
                        </p>
                        <div class="zc-hero-trust-list">
                            <span class="zc-trust-pill"><i class="fa-solid fa-certificate"></i> کدنویسی منطبق با استانداردهای ژاکت و تم‌فارست</span>
                            <span class="zc-trust-pill"><i class="fa-solid fa-face-smile"></i> رضایت ۹۹.۸٪ از ۴۲۰+ کارفرما و خریدار</span>
                            <span class="zc-trust-pill"><i class="fa-solid fa-bolt"></i> تحویل سریع و پشتیبانی مستقیم: 09024561001</span>
                        </div>

                        <!-- Studio Architecture Banner Inside About -->
                        <div style="margin-top:1.75rem;position:relative;border-radius:1.35rem;overflow:hidden;height:210px;border:1px solid var(--zc-border-subtle);box-shadow:var(--zc-shadow-card);">
                            <img src="<?php echo esc_url( $office_img ); ?>" alt="دفتر و استودیو مهندسی نرم‌افزار زرین کد" style="width:100%;height:100%;object-fit:cover;display:block;">
                            <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(15,17,22,0.88) 0%,transparent 65%);display:flex;align-items:flex-end;justify-content:space-between;padding:1.15rem 1.35rem;">
                                <div>
                                    <div style="color:var(--color-accent);font-size:0.75rem;font-weight:800;">ZARINCODE SOFTWARE &amp; DESIGN STUDIO</div>
                                    <div style="color:#fff;font-size:0.95rem;font-weight:900;">محیط مهندسی و توسعه محصولات دیجیتال زرین کد</div>
                                </div>
                                <span class="zc-badge-pill" style="margin:0;">ISO / Zhaket / ThemeForest</span>
                            </div>
                        </div>
                    </div>

                    <div class="zc-dark-card" style="padding:1.85rem;overflow:hidden;">
                        <!-- Real Executive Standing Portrait of Ehsan NaderiPanah -->
                        <div style="position:relative;border-radius:1.35rem;overflow:hidden;margin-bottom:1.35rem;border:2px solid rgba(253,208,91,0.45);height:310px;">
                            <img src="<?php echo esc_url( $founder_img ); ?>" alt="احسان نادری پناه - موسس و مدیر فنی زرین کد" style="width:100%;height:100%;object-fit:cover;object-position:center top;display:block;">
                            <div style="position:absolute;top:0.85rem;right:0.85rem;">
                                <span class="zc-badge-pill" style="margin:0;box-shadow:0 8px 20px rgba(0,0,0,0.35);"><i class="fa-solid fa-crown"></i> FOUNDER &amp; ARCHITECT</span>
                            </div>
                            <div style="position:absolute;bottom:0;left:0;right:0;padding:1.1rem 1.25rem;background:linear-gradient(to top,rgba(15,17,22,0.95) 0%,rgba(15,17,22,0.55) 65%,transparent 100%);display:flex;align-items:center;justify-content:space-between;">
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <img src="<?php echo esc_url( $studio_img ); ?>" alt="احسان نادری پناه" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--color-accent);">
                                    <div>
                                        <h3 style="font-size:1.25rem;font-weight:900;color:#fff;margin:0;">احسان نادری پناه</h3>
                                        <div style="color:var(--color-accent);font-size:0.78rem;font-weight:700;">Ehsan NaderiPanah — Full-Stack Architect</div>
                                    </div>
                                </div>
                                <span style="background:rgba(16,185,129,0.22);color:#34D399;padding:0.25rem 0.65rem;border-radius:999px;font-size:0.72rem;font-weight:800;">● Verified</span>
                            </div>
                        </div>

                        <p style="color:#D1D5DB;font-size:0.88rem;line-height:1.85;margin-bottom:1.2rem;">
                            متخصص معماری قالب‌ها و افزونه‌های حرفه‌ای وردپرس (Elementor &amp; Redux Framework)، توسعه‌دهنده فول‌استک نرم‌افزارهای تحت وب و استراتژیست ارشد سئو سایت و شبکه‌های اجتماعی.
                        </p>
                        <div style="display:flex;flex-direction:column;gap:0.55rem;font-size:0.86rem;color:#fff;background:rgba(255,255,255,0.06);padding:1rem 1.15rem;border-radius:1.15rem;">
                            <div><i class="fa-solid fa-phone" style="color:var(--color-accent);width:1.5rem;"></i> شماره تماس مستقیم: <strong dir="ltr">09024561001</strong></div>
                            <div><i class="fa-solid fa-globe" style="color:var(--color-accent);width:1.5rem;"></i> وب‌سایت رسمی: <strong>Zarincode.com</strong></div>
                            <div><i class="fa-brands fa-github" style="color:var(--color-accent);width:1.5rem;"></i> مخزن گیت‌هاب: <strong>github.com/Ehsan-Np</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo Renderer::render_why_us_stats(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * CONTACT US FULL PAGE (Featuring Real Consultation Portrait of Ehsan NaderiPanah)
     */
    private static function render_contact_full_page(): string {
        $consult_img = get_template_directory_uri() . '/assets/images/ehsan-contact-consultation.jpg';
        ob_start();
        ?>
        <div class="zc-container" style="padding-top:2rem;padding-bottom:3rem;">
            <div class="zc-hero-grid">
                <div class="zc-glass-panel" style="padding:2.5rem;">
                    <span class="zc-badge-pill">CONTACT ZARINCODE TEAM</span>
                    <h1 class="zc-section-title">با تیم <span class="zc-highlight">زرین کد</span> در ارتباط باشید...</h1>
                    <p style="color:var(--zc-text-secondary);line-height:1.95;margin-bottom:1.35rem;">
                        ۷ روز هفته آماده پاسخگویی به سوالات شما در زمینه سفارش طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت، سئو شبکه‌های اجتماعی، تولید محتوا و خرید محصولات آماده (قالب، افزونه و سورس‌کد) هستیم.
                    </p>

                    <!-- Real Executive Consultation Photo -->
                    <div style="position:relative;border-radius:1.35rem;overflow:hidden;height:215px;margin-bottom:1.35rem;border:2px solid rgba(253,208,91,0.45);box-shadow:var(--zc-shadow-card);">
                        <img src="<?php echo esc_url( $consult_img ); ?>" alt="مشاوره مستقیم با احسان نادری پناه در زرین کد" style="width:100%;height:100%;object-fit:cover;object-position:center top;display:block;">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(15,17,22,0.92) 0%,rgba(15,17,22,0.25) 60%,transparent 100%);display:flex;align-items:flex-end;justify-content:space-between;padding:1rem 1.25rem;">
                            <div>
                                <div style="font-size:0.75rem;color:var(--color-accent);font-weight:800;">DIRECT TECHNICAL CONSULTATION</div>
                                <div style="font-size:0.98rem;color:#fff;font-weight:900;">پاسخگویی و مشاوره مستقیم با احسان نادری پناه</div>
                            </div>
                            <a href="tel:09024561001" class="zc-btn zc-btn-yellow" style="padding:0.4rem 0.9rem;font-size:0.78rem;" dir="ltr">
                                <i class="fa-solid fa-phone-volume"></i> 09024561001
                            </a>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr;gap:0.9rem;margin-bottom:0.5rem;">
                        <div class="zc-white-card" style="padding:1.15rem;display:flex;align-items:center;gap:1rem;">
                            <div class="zc-service-icon" style="width:3.2rem;height:3.2rem;font-size:1.2rem;"><i class="fa-solid fa-user-tie"></i></div>
                            <div>
                                <div style="font-size:0.78rem;color:var(--zc-text-muted);">مدیریت و توسعه‌دهنده ارشد:</div>
                                <div style="font-size:1.05rem;font-weight:900;">احسان نادری پناه (Ehsan NaderiPanah)</div>
                            </div>
                        </div>
                        <div class="zc-white-card" style="padding:1.15rem;display:flex;align-items:center;gap:1rem;">
                            <div class="zc-service-icon" style="width:3.2rem;height:3.2rem;font-size:1.2rem;"><i class="fa-solid fa-phone-volume"></i></div>
                            <div>
                                <div style="font-size:0.78rem;color:var(--zc-text-muted);">شماره تماس مستقیم، واتساپ و تلگرام:</div>
                                <a href="tel:09024561001" dir="ltr" style="font-size:1.2rem;font-weight:900;color:var(--zc-text-main);">09024561001</a>
                            </div>
                        </div>
                        <div class="zc-white-card" style="padding:1.15rem;display:flex;align-items:center;gap:1rem;">
                            <div class="zc-service-icon" style="width:3.2rem;height:3.2rem;font-size:1.2rem;"><i class="fa-solid fa-globe"></i></div>
                            <div>
                                <div style="font-size:0.78rem;color:var(--zc-text-muted);">وب‌سایت رسمی و پست الکترونیک:</div>
                                <div style="font-size:1rem;font-weight:800;">Zarincode.com • info@zarincode.com</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Contact & Order Form -->
                <div class="zc-white-card" style="padding:2.5rem;">
                    <span class="zc-badge-pill">DIRECT MESSAGE</span>
                    <h2 style="font-size:1.45rem;font-weight:900;margin:0.25rem 0 1.25rem;">سوال، درخواست مشاوره یا سفارش پروژه دارید؟ بنویسید!</h2>
                    <form class="zc-Ajax-form">
                        <div class="zc-form-group">
                            <label class="zc-form-label">نام و نام خانوادگی *</label>
                            <input type="text" class="zc-form-control" required placeholder="نام کامل خود را وارد نمایید">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="zc-form-group">
                                <label class="zc-form-label">شماره تماس *</label>
                                <input type="tel" class="zc-form-control" required placeholder="0912XXXXXXX" dir="ltr">
                            </div>
                            <div class="zc-form-group">
                                <label class="zc-form-label">آدرس ایمیل</label>
                                <input type="email" class="zc-form-control" placeholder="you@domain.com" dir="ltr">
                            </div>
                        </div>
                        <div class="zc-form-group">
                            <label class="zc-form-label">موضوع درخواست</label>
                            <select class="zc-form-control">
                                <option>سفارش طراحی سایت اختصاصی</option>
                                <option>سفارش برنامه‌نویسی نرم‌افزار و سامانه</option>
                                <option>سفارش سئو سایت یا سئو شبکه‌های اجتماعی</option>
                                <option>سفارش تولید محتوا</option>
                                <option>پشتیبانی و خرید قالب، افزونه و سورس‌کد آماده</option>
                            </select>
                        </div>
                        <div class="zc-form-group">
                            <label class="zc-form-label">توضیحات پیام شما *</label>
                            <textarea class="zc-form-control" rows="4" required placeholder="جزئیات درخواست یا سوال خود را بنویسید..."></textarea>
                        </div>
                        <div class="zc-form-feedback" style="display:none;padding:0.85rem 1rem;border-radius:1rem;background:var(--color-accent);color:var(--color-accent-contrast);font-weight:700;font-size:0.88rem;margin-bottom:1rem;"></div>
                        <button type="submit" class="zc-btn zc-btn-yellow" style="width:100%;">
                            <i class="fa-solid fa-paper-plane"></i> ثبت و ارسال مستقیم پیغام به احسان نادری پناه
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * TERMS & CONDITIONS + ALL IRANIAN CYBER & E-COMMERCE LAWS
     */
    private static function render_terms_iran_laws(): string {
        ob_start();
        ?>
        <div class="zc-container">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'terms' ); ?>
                <div class="zc-glass-panel zc-legal-content">
                    <span class="zc-badge-pill"><i class="fa-solid fa-scale-balanced"></i> IRANIAN CYBER &amp; E-COMMERCE LAWS</span>
                    <h1 class="zc-section-title">قوانین و شرایط استفاده و مقررات حاکم بر کشور ایران</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        کلیه فعالیت‌ها، خدمات و محصولات ارائه شده در وب‌سایت <strong>زرین کد (Zarincode.com)</strong> به مدیریت و مالکیت <strong>احسان نادری پناه (شماره تماس: 09024561001)</strong> منطبق با قوانین موضوعه جمهوری اسلامی ایران، قانون تجارت الکترونیکی، قانون جرایم رایانه‌ای و قانون حمایت از حقوق پدیدآورندگان نرم‌افزارهای رایانه‌ای می‌باشد.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-landmark"></i> ماده ۱: انطباق با قانون تجارت الکترونیکی جمهوری اسلامی ایران (مصوب ۱۳۸۲)</h3>
                        <p>تمامی قراردادهای آنلاین، فاکتورهای الکترونیکی، پیش‌فاکتورها و تراکنش‌های ثبت‌شده در وب‌سایت زرین کد بر اساس مواد ۲، ۶، ۱۲ و ۳۳ الی ۴۹ «قانون تجارت الکترونیکی جمهوری اسلامی ایران» در حکم اسناد معتبر و لازم‌الاجرا بوده و میان کارفرما/خریدار و مجموعه زرین کد (احسان نادری پناه) ایجاد تعهد قانونی می‌نماید.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-shield-virus"></i> ماده ۲: رعایت قانون جرایم رایانه‌ای و مصوبات کارگروه تعیین مصادیق محتوای مجرمانه</h3>
                        <p>زرین کد صرفاً به طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو، تولید محتوا و فروش سورس‌کد برای کسب‌وکارهای مجاز و قانونی کشور می‌پردازد. پذیرش هرگونه پروژه یا ارائه سورس‌کد به وب‌سایت‌های دارای محتوای خلاف قوانین جمهوری اسلامی ایران، قمار، شرط‌بندی، فیشینگ، نقض عفت عمومی یا موارد فهرست مصادیق محتوای مجرمانه (موضوع ماده ۲۱ قانون جرایم رایانه‌ای مصوب ۱۳۸۸) اکیداً ممنوع بوده و در صورت تخلف خریدار، سرویس بدون بازگشت وجه مسدود می‌گردد.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-code"></i> ماده ۳: قانون حمایت از حقوق پدیدآورندگان نرم‌افزارهای رایانه‌ای (مصوب ۱۳۷۹)</h3>
                        <p>کلیه قالب‌های وردپرس، افزونه‌ها و سورس‌کدهای آماده عرضه شده در بخش فروشگاه زرین کد، حاصل کدنویسی اختصاصی <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> بوده و تحت حمایت «قانون حمایت از حقوق پدیدآورندگان نرم‌افزارهای رایانه‌ای» و ماده ۶۲ قانون تجارت الکترونیکی قرار دارند. خرید هر محصول از فروشگاه، به معنای دریافت «حق بهره‌برداری (لایسنس)» برای دامنه یا پروژه خریدار بوده و هرگونه بازفروش، انتشار رایگان، نال کردن یا واگذاری سورس‌کد به اشخاص ثالث پیگرد قانونی و قضایی در مراجع صالحه ایران خواهد داشت.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-file-signature"></i> ماده ۴: شرایط اختصاصی خدمات طراحی سایت، نرم‌افزار، سئو و تولید محتوا</h3>
                        <ul>
                            <li><strong>نحوه پرداخت پروژه‌های سفارشی:</strong> ۵۰٪ مبلغ قرارداد در زمان شروع پروژه به عنوان پیش‌پرداخت و ۵۰٪ الباقی پس از تکمیل، تست کامل روی سرور آزمایشی و تایید کارفرما تسویه می‌گردد.</li>
                            <li><strong>تعهدات کارفرما:</strong> تحویل به‌موقع اطلاعات، لوگو، متون اولیه و دسترسی‌های لازم بر عهده کارفرماست. تاخیر در تحویل مدارک از سوی کارفرما به مدت زمان اجرای پروژه افزوده می‌شود.</li>
                            <li><strong>تعهدات سئو سایت و شبکه‌های اجتماعی:</strong> کلیه خدمات سئو در زرین کد به روش کلاه‌سفید (White-Hat SEO) و مطابق دستورالعمل‌های رسمی گوگل و متا انجام می‌شود تا امنیت دامنه و پیج کارفرما ۱۰۰٪ حفظ گردد.</li>
                        </ul>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-gavel"></i> ماده ۵: مرجع حل اختلاف و قوانین حاکم</h3>
                        <p>در صورت بروز هرگونه ابهام یا اختلاف در تفسیر یا اجرای قراردادهای خدماتی یا خرید محصولات آماده، موضوع ابتدا از طریق مذاکره مستقیم با مدیریت زرین کد (احسان نادری پناه - تلفن: 09024561001) حل‌وفصل می‌گردد و در صورت عدم حصول توافق، شورای انتظامی نظام صنفی رایانه‌ای و دادگاه‌های صالحه دادگستری جمهوری اسلامی ایران مرجع رسمی رسیدگی خواهند بود.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * WARRANTY & TECHNICAL SUPPORT POLICY
     */
    private static function render_warranty_policy(): string {
        ob_start();
        ?>
        <div class="zc-container">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'warranty' ); ?>
                <div class="zc-glass-panel zc-legal-content">
                    <span class="zc-badge-pill"><i class="fa-solid fa-shield-halved"></i> OFFICIAL WARRANTY &amp; SLA</span>
                    <h1 class="zc-section-title">شرایط گارانتی و پشتیبانی فنی یک‌ساله زرین کد</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        تمامی پروژه‌های اختصاصی و محصولات آماده نرم‌افزاری، قالب‌ها و افزونه‌های وردپرسی ارائه شده توسط <strong>احسان نادری پناه در زرین کد (Zarincode.com)</strong> دارای گارانتی معتبر فنی و پشتیبانی تضمین‌شده هستند.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-award"></i> ۱. گارانتی ۳۶۵ روزه پروژه‌های طراحی سایت و برنامه‌نویسی نرم‌افزار</h3>
                        <p>کلیه وب‌سایت‌ها و نرم‌افزارهای اختصاصی کدنویسی‌شده توسط تیم زرین کد، از تاریخ تحویل نهایی به مدت <strong>یک سال کامل (۱۲ ماه)</strong> دارای گارانتی رایگان رفع هرگونه باگ احتمالی، خطاهای کدنویسی، ناسازگاری با نسخه‌های استاندارد PHP و وردپرس و پایش امنیتی هستند.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-box-open"></i> ۲. گارانتی محصولات آماده فروشگاهی (قالب، افزونه و سورس‌کد)</h3>
                        <ul>
                            <li><strong>آپدیت مادام‌العمر رایگان:</strong> خریداران محصولات آماده در پنل کاربری خود به تمامی نسخه‌های بروزرسانی آینده بدون پرداخت هزینه اضافی دسترسی دارند.</li>
                            <li><strong>۶ ماه پشتیبانی فنی رایگان:</strong> شامل پاسخگویی به سوالات فنی، راهنمایی نصب و رفع هرگونه تداخل با قالب یا افزونه‌های استاندارد وردپرس و ووکامرس.</li>
                            <li><strong>تضمین سلامت و اصالت کد:</strong> کلیه کدها عاری از هرگونه کد مخرب، بک‌دور یا وابستگی غیرمجاز بوده و مستقیماً توسط احسان نادری پناه توسعه یافته‌اند.</li>
                        </ul>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-clock"></i> ۳. توافق‌نامه سطح خدمات و زمان پاسخگویی (SLA)</h3>
                        <p>پشتیبانی زرین کد از طریق تیکت پنل کاربری و خط مستقیم <strong>09024561001</strong> در ۷ روز هفته برقرار است: زمان پاسخگویی به مشکلات فوری (اورژانسی) حداکثر ۱ تا ۳ ساعت و تیکت‌های عادی حداکثر ۱۲ ساعت می‌باشد.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * PRIVACY POLICY
     */
    private static function render_privacy_policy(): string {
        ob_start();
        ?>
        <div class="zc-container">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'privacy' ); ?>
                <div class="zc-glass-panel zc-legal-content">
                    <span class="zc-badge-pill"><i class="fa-solid fa-user-shield"></i> PRIVACY &amp; DATA SECURITY</span>
                    <h1 class="zc-section-title">سیاست حفظ حریم خصوصی و محرمانگی اطلاعات</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        استودیو <strong>زرین کد (Zarincode.com)</strong> به مدیریت <strong>احسان نادری پناه</strong> متعهد به حفاظت حداکثری از اطلاعات شخصی، تجاری، سورس‌کدها و دسترسی‌های سرور مشتریان خود است.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-lock"></i> ۱. محرمانگی کامل پروژه‌ها و ایده‌های استارتاپی (NDA)</h3>
                        <p>تمامی اطلاعات تجاری، طرح‌های کسب‌وکار (Business Plan)، دیتابیس مشتریان، دسترسی‌های هاست/سرور و کلیدهای API که کارفرما جهت اجرای پروژه‌های طراحی سایت، برنامه‌نویسی نرم‌افزار یا سئو در اختیار زرین کد قرار می‌دهد، کاملاً محرمانه تلقی شده و در صورت درخواست کارفرما، قرارداد رسمی عدم افشای اطلاعات (NDA) منعقد می‌گردد.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-shield-halved"></i> ۲. امنیت تراکنش‌های بانکی و اطلاعات هویتی</h3>
                        <p>تمامی پرداخت‌های آنلاین در وب‌سایت Zarincode.com از طریق درگاه‌های رسمی شبکه شاپرک و با پروتکل رمزنگاری امن SSL (256-bit) انجام می‌شود و هیچ‌گونه اطلاعات محرمانه کارت بانکی در سرورهای زرین کد ذخیره نمی‌گردد.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-ban"></i> ۳. عدم اشتراک‌گذاری اطلاعات با اشخاص ثالث</h3>
                        <p>شماره تماس، آدرس ایمیل و مشخصات خریداران محصولات آماده و کارفرمایان پروژه‌ها صرفاً جهت ارسال فاکتور، اطلاع‌رسانی آپدیت محصولات و پشتیبانی فنی استفاده شده و هرگز در اختیار اشخاص یا شرکت‌های تبلیغاتی ثالث قرار نخواهد گرفت.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * REFUND POLICY (MONEY-BACK GUARANTEE)
     */
    private static function render_refund_policy(): string {
        ob_start();
        ?>
        <div class="zc-container">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'refund' ); ?>
                <div class="zc-glass-panel zc-legal-content">
                    <span class="zc-badge-pill"><i class="fa-solid fa-rotate-left"></i> 100% MONEY-BACK GUARANTEE</span>
                    <h1 class="zc-section-title">شرایط و ضوابط ضمانت بازگشت وجه در زرین کد</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        برای حذف هرگونه ریسک از مسیر همکاری شما با <strong>زرین کد (احسان نادری پناه - 09024561001)</strong>، شرایط بازگشت وجه برای هر دو بخش «خدمات پروژه‌ای» و «فروشگاه محصولات آماده» به صورت کاملاً شفاف تدوین شده است.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-laptop-code"></i> ۱. ضمانت بازگشت وجه در خدمات طراحی سایت، نرم‌افزار و سئو</h3>
                        <ul>
                            <li>در صورتی که طرح اولیه UI/UX یا معماری نرم‌افزار ارائه شده پس از ۳ مرحله بازبینی رایگان مطابق با نیازمندی‌های مکتوب قرارداد نباشد، کارفرما حق فسخ قرارداد و دریافت ۱۰۰٪ پیش‌پرداخت واریزی را دارد.</li>
                            <li>در صورت تاخیر غیرموجه بیش از ۱۵ روز کاری از سوی مجری در تحویل پروژه (خارج از موارد فورس‌ماژور یا تاخیر کارفرما)، کارفرما می‌تواند درخواست استرداد کامل وجه نماید.</li>
                            <li>در پروژه‌های سئو تضمینی، در صورت عدم تحقق شاخص‌های کلیدی عملکرد (KPI) توافق‌شده در پایان دوره قرارداد، وجه مطابق مفاد قرارداد به کارفرما مسترد می‌گردد.</li>
                        </ul>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-box-open"></i> ۲. شرایط بازگشت وجه در خرید قالب، افزونه و سورس‌کد آماده</h3>
                        <p>با توجه به ماهیت فایل‌های دیجیتال و سورس‌کد باز، در صورتی که محصول خریداری‌شده دارای نقص فنی اساسی یا مغایرت با امکانات اعلام‌شده در صفحه دمو باشد و تیم پشتیبانی فنی زرین کد (احسان نادری پناه) ظرف حداکثر ۴۸ ساعت کاری قادر به رفع مشکل نباشد، ۱۰۰٪ مبلغ پرداختی بدون کسر هیچ‌گونه کارمزدی به حساب خریدار عودت داده می‌شود.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-money-bill-transfer"></i> ۳. نحوه ثبت درخواست و زمان واریز</h3>
                        <p>جهت ثبت درخواست بازگشت وجه کافیست از طریق پنل کاربری یا تماس مستقیم با شماره <strong>09024561001</strong> درخواست خود را به همراه شماره شبا اعلام فرمایید. مبالغ تاییدشده ظرف حداکثر ۲۴ الی ۷۲ ساعت کاری از طریق حواله پایا/ساتنا به حساب بانکی کارفرما واریز می‌گردد.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * GDPR COMPLIANCE POLICY
     */
    private static function render_gdpr_policy(): string {
        ob_start();
        ?>
        <div class="zc-container">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'gdpr' ); ?>
                <div class="zc-glass-panel zc-legal-content">
                    <span class="zc-badge-pill"><i class="fa-solid fa-earth-europe"></i> EU GDPR COMPLIANCE (2016/679)</span>
                    <h1 class="zc-section-title">قوانین حفاظت از داده‌های عمومی اتحادیه اروپا (GDPR)</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        وب‌سایت و پلتفرم <strong>زرین کد (Zarincode.com)</strong> به منظور رعایت استانداردهای بین‌المللی مارکت‌های جهانی نظیر <strong>ThemeForest (Envato)</strong> و حفظ حقوق کاربران مقیم اتحادیه اروپا و سراسر جهان، کاملاً منطبق با مقررات عمومی حفاظت از داده اتحادیه اروپا (General Data Protection Regulation - EU 2016/679) طراحی و پیاده‌سازی شده است.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-database"></i> ۱. اصول پردازش قانونی، منصفانه و شفاف داده‌ها (Article 5 GDPR)</h3>
                        <p>زرین کد تنها حداقل داده‌های ضروری (Data Minimization) را با رضایت صریح کاربر (Explicit Consent) جهت ارائه خدمات طراحی سایت، توسعه نرم‌افزار و تحویل لایسنس محصولات دیجیتال جمع‌آوری و پردازش می‌کند.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-user-check"></i> ۲. حقوق هشت‌گانه کاربران تحت قوانین GDPR (Articles 12–23)</h3>
                        <ul>
                            <li><strong>حق دسترسی (Right of Access - Art. 15):</strong> هر کاربر حق دارد کپی کاملی از داده‌های ثبت‌شده خود در Zarincode.com را دریافت نماید.</li>
                            <li><strong>حق اصلاح (Right to Rectification - Art. 16):</strong> امکان ویرایش و تصحیح آنی اطلاعات هویتی در حساب کاربری.</li>
                            <li><strong>حق فراموش شدن و حذف کامل (Right to Erasure / Be Forgotten - Art. 17):</strong> کاربر در هر زمان می‌تواند درخواست حذف دائمی حساب کاربری و کلیه سوابق خود را از سرورهای زرین کد ثبت نماید.</li>
                            <li><strong>حق انتقال‌پذیری داده‌ها (Right to Data Portability - Art. 20):</strong> دریافت خروجی استاندارد JSON/CSV از اطلاعات حساب و سفارش‌ها.</li>
                        </ul>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-id-badge"></i> ۳. افسر حفاظت از داده‌ها (Data Protection Officer - DPO)</h3>
                        <p>مسئول رسمی حفاظت از داده‌ها و رسیدگی به درخواست‌های GDPR در مجموعه زرین کد:</p>
                        <ul>
                            <li><strong>نام افسر حفاظت داده (DPO):</strong> احسان نادری پناه (Ehsan NaderiPanah)</li>
                            <li><strong>شماره تماس مستقیم:</strong> <span dir="ltr">+98 902 456 1001 (09024561001)</span></li>
                            <li><strong>وب‌سایت و ایمیل رسمی:</strong> Zarincode.com • dpo@zarincode.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * DMCA COPYRIGHT POLICY (FULL AUTHORITATIVE ENGLISH AS REQUESTED BY USER)
     */
    private static function render_dmca_english(): string {
        ob_start();
        ?>
        <div class="zc-container zc-dmca-wrapper" dir="ltr" style="text-align:left;">
            <div class="zc-legal-layout">
                <?php echo self::render_legal_sidebar( 'dmca' ); ?>
                <div class="zc-glass-panel zc-legal-content" dir="ltr" style="text-align:left;">
                    <span class="zc-badge-pill"><i class="fa-solid fa-copyright"></i> 17 U.S.C. § 512 • DIGITAL MILLENNIUM COPYRIGHT ACT</span>
                    <h1 class="zc-section-title" style="font-size:2.1rem;">DMCA Copyright Notice &amp; Takedown Policy</h1>
                    <p style="color:var(--zc-text-secondary);margin-bottom:2rem;">
                        <strong>Zarincode (<a href="https://zarincode.com" style="text-decoration:underline;">https://zarincode.com</a>)</strong>, founded and operated by <strong>Ehsan NaderiPanah</strong>, respects the intellectual property rights of software developers, digital creators, and third-party copyright owners worldwide and expects all users and customers to do the same. In accordance with the <strong>Digital Millennium Copyright Act of 1998 ("DMCA"), 17 U.S.C. § 512</strong>, and international copyright treaties (Berne Convention &amp; WIPO Copyright Treaty), we respond expeditiously to valid claims of copyright infringement.
                    </p>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-shield-halved"></i> 1. Intellectual Property Ownership of Zarincode Products</h3>
                        <p>All proprietary WordPress themes (including the <strong>Zarincode Elementor-Based Theme</strong>), WordPress plugins, custom Elementor widgets, UI kits, and ready-made software source codes published on <strong>Zarincode.com</strong> are original works authored by <strong>Ehsan NaderiPanah</strong>. Unauthorized redistribution, nulled/cracked distribution, scraping, reverse engineering, or resale without an official commercial license from Zarincode.com is strictly prohibited and subject to immediate DMCA takedown notices sent to hosting providers, Cloudflare, Google Search, and GitHub.</p>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-file-Lines"></i> 2. Filing a DMCA Infringement Notification</h3>
                        <p>If you believe in good faith that any material, code snippet, graphic asset, or content hosted on or linked through <strong>Zarincode.com</strong> infringes your copyright, please send a written DMCA Takedown Notice containing all of the following statutory elements (pursuant to 17 U.S.C. § 512(c)(3)):</p>
                        <ol style="line-height:1.9;">
                            <li>A physical or electronic signature of the copyright owner or a person authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.</li>
                            <li>Identification of the copyrighted work claimed to have been infringed (or a representative list if multiple works are covered by a single notification).</li>
                            <li>Identification of the material that is claimed to be infringing and exact URLs on <strong>Zarincode.com</strong> reasonably sufficient to permit us to locate the material.</li>
                            <li>Your full legal name, mailing address, telephone number, and valid email address.</li>
                            <li>A statement that you have a good-faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.</li>
                            <li>A statement that the information in the notification is accurate, and <strong>under penalty of perjury</strong>, that you are authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.</li>
                        </ol>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-reply"></i> 3. Counter-Notification Procedure (17 U.S.C. § 512(g))</h3>
                        <p>If your material was removed or disabled as a result of a DMCA notice and you believe such removal was the result of mistake or misidentification, you may submit a written Counter-Notification to our Designated Copyright Agent including:</p>
                        <ul style="line-height:1.9;">
                            <li>Your physical or electronic signature;</li>
                            <li>Identification of the material that has been removed and the location at which the material appeared before it was removed;</li>
                            <li>A statement under penalty of perjury that you have a good-faith belief that the material was removed or disabled as a result of mistake or misidentification;</li>
                            <li>Your name, address, telephone number, and a statement consenting to the jurisdiction of the competent court.</li>
                        </ul>
                    </div>

                    <div class="zc-legal-clause">
                        <h3><i class="fa-solid fa-user-tie"></i> 4. Designated DMCA Copyright Agent Contact Information</h3>
                        <p>All DMCA Takedown Notices and Counter-Notifications for <strong>Zarincode.com</strong> must be directed to our Designated Copyright Agent:</p>
                        <div style="background:var(--zc-dark);color:#FFFFFF;padding:1.5rem;border-radius:1.25rem;margin-top:1rem;">
                            <div><strong>Designated Copyright Agent:</strong> Ehsan NaderiPanah</div>
                            <div><strong>Organization / Brand:</strong> Zarincode Digital Engineering &amp; Software Studio</div>
                            <div><strong>Official Website:</strong> <a href="https://zarincode.com" style="color:var(--color-accent);">https://zarincode.com</a></div>
                            <div><strong>Direct Phone / WhatsApp / Telegram:</strong> <a href="tel:09024561001" style="color:var(--color-accent);">+98 902 456 1001 (09024561001)</a></div>
                            <div><strong>DMCA &amp; Legal Email:</strong> <span style="color:var(--color-accent);">dmca@zarincode.com • info@zarincode.com</span></div>
                            <div><strong>Response SLA:</strong> Within 24 to 48 business hours</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
