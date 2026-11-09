<?php
/**
 * Complete One-Click & Automatic Demo Importer for Zarincode Theme
 *
 * Imports & configures:
 * 1. Elementor Header, Footer, MegaMenu & Consultation Popup (zc_hf_template + elementor_library)
 * 2. 6 Core Services (zc_service CPT + zc_service_cat taxonomy + SLA Meta Fields)
 * 3. 8 Portfolio Case Studies (zc_project CPT + zc_project_cat taxonomy + Project Meta Fields)
 * 4. 8 Ready-Made Digital Products in WooCommerce (Themes, Plugins, Software Source Codes)
 * 5. 13 Complete Elementor Pages (Homepage, Services, Portfolio, Shop, About, Contact, Blog + 6 Legal Pages)
 * 6. 6 Educational Blog Posts + Categories
 * 7. 3 WordPress Navigation Menus (Header Pill Menu, Legal Menu, Footer Quick Links)
 * 8. Redux Framework Options (zarincode_opt) + Reading Settings (Front Page & Blog Page)
 * 9. OCDI (One Click Demo Import) plugin integration filters
 *
 * @package Zarincode\Runtime260\Demo
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Runtime260\Demo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Demo_Seeder {

    private static ?Demo_Seeder $instance = null;

    public static function instance(): Demo_Seeder {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Top-level admin menu + Appearance submenu
        add_action( 'admin_menu', [ $this, 'register_demo_admin_menus' ] );
        // Prominent Admin Notice Banner with 1-Click Import button
        add_action( 'admin_notices', [ $this, 'render_admin_notice_banner' ] );
        // Auto-import on theme switch or first run so the user never sees an empty site
        // Demo import is explicit; never overwrite an existing site on activation.

        // OCDI (One Click Demo Import) plugin hooks
        add_filter( 'ocdi/import_files', [ $this, 'ocdi_import_files' ] );
        add_action( 'ocdi/after_import', [ $this, 'ocdi_after_import_setup' ] );
    }

    public function auto_seed_on_activation(): void {
        self::seed_all();
        update_option( 'zarincode_demo_imported_v25', 'yes' );
    }

    public function maybe_auto_seed_first_time(): void {
        if ( 'yes' !== get_option( 'zarincode_demo_imported_v25' ) ) {
            self::seed_all();
            update_option( 'zarincode_demo_imported_v25', 'yes' );
        }
    }

    public function register_demo_admin_menus(): void {
        // Hidden compatibility routes; the sole visible entry is the theme control center.
        foreach(['zarincode-demo-importer','zarincode-demo-importer-sub'] as $slug) {
            add_submenu_page(null,'درون‌ریزی دمو','درون‌ریزی دمو','manage_options',$slug,[$this,'render_admin_page']);
        }

    }

    public function render_admin_notice_banner(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && strpos( $screen->id, 'zarincode' ) !== false ) {
            return;
        }
        $import_url = admin_url( 'admin.php?page=zarincode-theme-options&zc_tool=demo' );
        ?>
        <div class="notice notice-warning is-dismissible" style="border-right:5px solid #FDD05B;background:#FFFDF5;padding:16px 20px;direction:rtl;font-family:Tahoma,system-ui,sans-serif;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <h3 style="margin:0 0 6px;font-size:15px;color:#2A2C31;">
                        ✨ قالب حرفه‌ای «زرین کد (Zarincode)» فعال است — درون‌ریزی هوشمند هدر، فوتر، خدمات، نمونه‌کارها، فروشگاه و صفحات قوانین
                    </h3>
                    <p style="margin:0;color:#4B5563;font-size:13px;">
                        توسعه‌دهنده: <strong>احسان نادری پناه (09024561001 | Zarincode.com)</strong> — برای درون‌ریزی یا بازسازی یک‌کلیکی هدر و فوتر المنتوری، ۶ خدمت تخصصی، ۸ نمونه‌کار، ۸ محصول فروشگاه و ۱۳ برگه المنتوری کلیک کنید:
                    </p>
                </div>
                <a href="<?php echo esc_url( $import_url ); ?>" class="button button-primary button-hero" style="background:#2A2C31;border-color:#2A2C31;color:#FDD05B;font-weight:bold;">
                    🚀 ورود به پنل درون‌ریز دمو زرین کد
                </a>
            </div>
        </div>
        <?php
    }

    public function render_admin_page(): void {
        if(!current_user_can('manage_options'))return;
        $message = '';
        if ( isset( $_POST['zarincode_run_seeder'] ) && check_admin_referer( 'zarincode_seed_nonce' ) ) {
            $scope = sanitize_text_field( wp_unslash( $_POST['zarincode_seed_scope'] ?? 'all' ) );
            switch ( $scope ) {
                case 'header_footer':
                    self::seed_header_footer_templates();
                    self::seed_navigation_menus();
                    $message = 'هدر، فوتر، مگامنو، پاپ‌آپ المنتوری و فهرست‌های منو با موفقیت درون‌ریزی شدند!';
                    break;
                case 'services_projects':
                    self::seed_services_cpt();
                    self::seed_projects_cpt();
                    $message = '۶ خدمت تخصصی و ۸ نمونه‌کار به همراه دسته‌بندی‌ها و متاباکس‌های SLA با موفقیت درون‌ریزی شدند!';
                    break;
                case 'pages_legal':
                    self::seed_elementor_pages();
                    $message = 'تمامی ۱۳ برگه المنتوری (صفحه اصلی، خدمات، نمونه‌کارها، درباره ما، تماس با ما و ۶ صفحه قوانین) با موفقیت درون‌ریزی شدند!';
                    break;
                case 'shop_blog':
                    self::seed_woocommerce_products();
                    self::seed_blog_posts();
                    $message = '۸ محصول دیجیتال فروشگاه ووکامرس و ۶ راهنمای کاربردی وبلاگ با موفقیت درون‌ریزی شدند!';
                    break;
                default:
                    self::seed_all();
                    update_option( 'zarincode_demo_imported_v25', 'yes' );
                    $message = 'تمامی بخش‌های دموی زرین کد (هدر، فوتر، ۶ خدمت، ۸ نمونه‌کار، ۸ محصول فروشگاه، ۱۳ برگه المنتوری و قوانین، فهرست‌ها و تنظیمات Redux) به طور کامل درون‌ریزی و فعال شدند!';
                    break;
            }
        }

        $cnt_hf       = wp_count_posts( 'zc_hf_template' )->publish ?? 0;
        $cnt_services = wp_count_posts( 'zc_service' )->publish ?? 0;
        $cnt_projects = wp_count_posts( 'zc_project' )->publish ?? 0;
        $cnt_pages    = wp_count_posts( 'page' )->publish ?? 0;
        $cnt_posts    = wp_count_posts( 'post' )->publish ?? 0;
        $cnt_products = post_type_exists( 'product' ) ? ( wp_count_posts( 'product' )->publish ?? 0 ) : 0;
        ?>
        <style>
            #wpcontent {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            #wpbody,
            #wpbody-content {
                width: 100% !important;
                max-width: 100% !important;
                float: none !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            #wpbody-content > .wrap.zc-demo-fullwidth-wrap,
            .wrap.zc-demo-fullwidth-wrap {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 18px 24px 36px 24px !important;
                box-sizing: border-box !important;
                direction: rtl !important;
                text-align: right !important;
            }
            .zc-demo-main-grid {
                display: grid;
                grid-template-columns: 1.55fr 1fr;
                gap: 22px;
                width: 100%;
                box-sizing: border-box;
                align-items: start;
            }
            @media (max-width: 1100px) {
                .zc-demo-main-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <div class="wrap zc-demo-fullwidth-wrap" style="direction:rtl;text-align:right;font-family:Tahoma,system-ui,sans-serif;width:100%;max-width:100%;box-sizing:border-box;">
            <h1 class="screen-reader-text">پنل درون‌ریز هوشمند قالب زرین کد</h1>
            <hr class="wp-header-end"><div class="notice notice-warning inline"><p><strong>بازنویسی صریح دمو:</strong> درون‌ریزی، نوشته‌های هم‌نام دمو را بازسازی می‌کند اما <strong>تنظیمات ذخیره‌شدهٔ شما را هرگز بازنویسی نمی‌کند</strong> (فقط کلیدهای خالی با مقدار پیش‌فرض پر می‌شوند). روی سایت عملیاتی ابتدا پشتیبان بگیرید و در محیط آزمایشی اجرا کنید. به‌روزرسانی قالب به‌تنهایی دمو را وارد نمی‌کند.</p></div>
            <div style="background:linear-gradient(135deg,#2A2C31 0%,#18191D 100%);color:#fff;padding:30px 34px;border-radius:24px;margin-bottom:24px;border:2px solid #FDD05B;box-shadow:0 15px 40px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:18px;width:100%;box-sizing:border-box;">
                <div>
                    <span style="background:#FDD05B;color:#2A2C31;padding:4px 14px;border-radius:999px;font-weight:bold;font-size:12px;">ZARINCODE SERVICE DEMO 3.5</span>
                    <h1 style="color:#fff;font-size:24px;margin:12px 0 8px;">پنل درون‌ریز هوشمند و یک‌کلیکی قالب «زرین کد (Zarincode)»</h1>
                    <p style="color:#D1D5DB;font-size:14px;margin:0;">
                        طراحی و توسعه توسط <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> | شماره تماس: <strong dir="ltr" style="color:#FDD05B;">09024561001</strong> | وب‌سایت: <a href="https://zarincode.com" target="_blank" style="color:#FDD05B;">Zarincode.com</a>
                    </p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zarincode-theme-options&zc_tool=plugins' ) ); ?>" class="button button-hero" style="border-radius:999px;background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.25);">
                        🔌 مدیریت افزونه‌های قالب
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zarincode-theme-options' ) ); ?>" class="button button-hero" style="border-radius:999px;background:#FDD05B;color:#2A2C31;border-color:#FDD05B;font-weight:bold;">
                        ⚙️ پنل تنظیمات پیشرفته قالب
                    </a>
                </div>
            </div>

            <?php if ( $message ) : ?>
                <div style="background:#ECFDF5;border:2px solid #10B981;color:#065F46;padding:18px 22px;border-radius:16px;margin-bottom:24px;font-weight:bold;font-size:14px;width:100%;box-sizing:border-box;">
                    ✅ <?php echo esc_html( $message ); ?>
                    <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button button-primary">مشاهده صفحه اصلی سایت</a>
                        <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" target="_blank" class="button">مشاهده صفحه خدمات</a>
                        <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>" target="_blank" class="button">مشاهده نمونه‌کارها</a>
                        <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank" class="button">مشاهده فروشگاه</a>
                        <a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>" target="_blank" class="button">مشاهده صفحات قوانین</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Live Status Cards (Full-Width 6-Column Strip) -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:24px;width:100%;box-sizing:border-box;">
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_hf; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">هدر، فوتر و مگامنوی المنتور</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_hf_template' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت هدر و فوتر ←</a>
                </div>
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_services; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">خدمات تخصصی (پست‌تایپ)</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_service' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت خدمات ←</a>
                </div>
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_projects; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">نمونه‌کارها و پروژه‌ها</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_project' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت نمونه‌کارها ←</a>
                </div>
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_pages; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">برگه‌های المنتوری و قوانین</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت برگه‌ها ←</a>
                </div>
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_products; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">محصولات آماده ووکامرس</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت محصولات ←</a>
                </div>
                <div style="background:#fff;padding:20px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.03);">
                    <div style="font-size:28px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_posts; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;font-weight:bold;">مقالات آموزشی وبلاگ</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت نوشته‌ها ←</a>
                </div>
            </div>

            <!-- Full-Width 2-Column Main Grid -->
            <div class="zc-demo-main-grid">
                <!-- Right Column: Import Form -->
                <form method="post" style="background:#fff;padding:30px 32px;border-radius:24px;border:1px solid #E5E7EB;box-shadow:0 10px 30px rgba(0,0,0,0.04);box-sizing:border-box;">
                    <?php wp_nonce_field( 'zarincode_seed_nonce' ); ?>
                    <h2 style="margin-top:0;font-size:19px;font-weight:900;color:#111827;">انتخاب نحوه درون‌ریزی دمو (تلفیق Webinex + رنگ‌بندی زرد سانشاین Crextio)</h2>
                    <p style="color:#4B5563;font-size:13.5px;line-height:1.8;">
                        پیش از درون‌ریزی نسخه پشتیبان تهیه کنید. محتوای برگه‌ها و تنظیمات دمو با اجرای مجدد جایگزین می‌شوند؛ سایر نوشته‌ها حذف نمی‌شوند و صفحه اصلی، هدر، فوتر، منوها و صفحات قوانین به صورت خودکار با ۱۴ ویجت قابل ویرایش المنتور تنظیم می‌گردند:
                    </p>

                    <div style="margin:18px 0;">
                        <label style="display:block;padding:14px 18px;background:#FFFBEB;border:2px solid #FDD05B;border-radius:14px;margin-bottom:10px;cursor:pointer;font-weight:bold;color:#1F2937;">
                            <input type="radio" name="zarincode_seed_scope" value="all" checked>
                            🌟 درون‌ریزی کامل همه بخش‌ها با یک کلیک (پیشنهادی: هدر و فوتر + ۶ خدمت + ۸ نمونه‌کار + ۸ محصول فروشگاه + ۱۳ برگه المنتوری و قوانین + منوها + تنظیمات Redux)
                        </label>
                        <label style="display:block;padding:12px 18px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:14px;margin-bottom:8px;cursor:pointer;">
                            <input type="radio" name="zarincode_seed_scope" value="header_footer">
                            فقط درون‌ریزی هدر، فوتر، مگامنوی المنتوری و فهرست‌های منو (Header, Footer &amp; Menus)
                        </label>
                        <label style="display:block;padding:12px 18px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:14px;margin-bottom:8px;cursor:pointer;">
                            <input type="radio" name="zarincode_seed_scope" value="services_projects">
                            فقط درون‌ریزی ۶ خدمت تخصصی و ۸ نمونه‌کار (Services &amp; Portfolio CPTs)
                        </label>
                        <label style="display:block;padding:12px 18px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:14px;margin-bottom:8px;cursor:pointer;">
                            <input type="radio" name="zarincode_seed_scope" value="pages_legal">
                            فقط درون‌ریزی ۱۳ برگه المنتوری (صفحه اصلی، درباره ما، تماس با ما و ۶ صفحه قوانین ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA)
                        </label>
                        <label style="display:block;padding:12px 18px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:14px;cursor:pointer;">
                            <input type="radio" name="zarincode_seed_scope" value="shop_blog">
                            فقط درون‌ریزی محصولات فروشگاه ووکامرس (با دکمه پیش‌نمایش زنده دمو) و مقالات وبلاگ
                        </label>
                    </div>

                    <button type="submit" name="zarincode_run_seeder" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:900;font-size:15px;padding:8px 34px;border-radius:999px;box-shadow:0 8px 20px rgba(253,208,91,0.45);">
                        🚀 شروع درون‌ریزی خودکار دموی زرین کد
                    </button>
                </form>

                <!-- Left Column: Detailed Content & Elementor Widget Manifest -->
                <div style="background:#1E2025;color:#fff;padding:28px 30px;border-radius:24px;border:2px solid #FDD05B;box-shadow:0 12px 32px rgba(0,0,0,0.12);box-sizing:border-box;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                        <span style="background:#FDD05B;color:#2A2C31;padding:3px 12px;border-radius:999px;font-size:11.5px;font-weight:900;">100% ELEMENTOR READY</span>
                        <span style="font-size:12px;color:#9CA3AF;">نسخه 2.6.0</span>
                    </div>
                    <h3 style="margin:0 0 12px;font-size:17px;color:#FDD05B;font-weight:900;">محتویات پکیج دمو و ۱۴ ویجت اختصاصی المنتور</h3>
                    <ul style="margin:0 0 18px;padding:0 18px 0 0;font-size:13px;line-height:1.95;color:#E5E7EB;">
                        <li><strong>هدر و فوترساز المنتوری:</strong> ویجت‌های `zarincode_header` و `zarincode_footer` با قابلیت ویرایش کامل لوگو، منو، دکمه‌ها و بج‌های اعتماد.</li>
                        <li><strong>صفحه اصلی بنتو (Crextio + Webinex):</strong> چیده شده با ۱۱ ویجت اختصاصی المنتور (هیرو، اسلایدر تکنولوژی‌ها، ۶ خدمت، مارکت‌پلیس محصولات، نمونه‌کارها، چرا زرین کد، جدول تعرفه ۴ تبه، دعوت به شرح پروژه، مراحل اجرا، نظرات مشتریان و وبلاگ).</li>
                        <li><strong>قابل ویرایش در المنتور:</strong> تمامی متن‌ها، کارت‌ها (Repeaters)، آیکون‌ها، تصاویر، لینک‌ها، بج‌ها، رنگ‌ها و تایپوگرافی هر ۱۴ ویجت مستقیماً از پنل المنتور قابل تغییرند.</li>
                        <li><strong>مارکت‌پلیس محصولات ووکامرس:</strong> ۸ محصول آماده به همراه فیلد اختصاصی <strong>«لینک پیش‌نمایش زنده دمو»</strong> و آکاردئون حرفه‌ای گارانتی و سوالات متداول.</li>
                        <li><strong>۶ برگه حقوقی کامل:</strong> قوانین و مقررات، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA (انگلیسی).</li>
                    </ul>
                    <div style="background:rgba(253,208,91,0.1);border:1px solid rgba(253,208,91,0.35);padding:12px 16px;border-radius:14px;font-size:12.5px;color:#FDE68A;line-height:1.7;">
                        💡 <strong>نکته:</strong> پس از درون‌ریزی دمو، می‌توانید هر برگه را با «ویرایش با المنتور» باز کرده و روی هر بخش کلیک کنید تا تنظیمات محتوا و استایل آن را ویرایش نمایید.
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function gen_id(): string {
        return substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
    }

    /** Elementor must be loaded for widget classes and their defaults to exist. */
    private static function elementor_ready(): bool {
        return class_exists( '\Elementor\Widget_Base' );
    }

    private static function make_elementor_section( string $widget_type, array $settings = [] ): array {
        $settings=Service_Demo::settings($widget_type,$settings);
        return [
            'id'       => self::gen_id(),
            'elType'   => 'section',
            'settings' => [
                'layout' => 'full_width',
                'gap'    => 'no',
            ],
            'elements' => [
                [
                    'id'       => self::gen_id(),
                    'elType'   => 'column',
                    'settings' => [ '_column_size' => 100 ],
                    'elements' => [
                        [
                            'id'         => self::gen_id(),
                            'elType'     => 'widget',
                            'widgetType' => $widget_type,
                            // Empty settings must serialise as a JSON object ({}), not an
                            // array ([]) — Elementor's editor reads settings as a map.
                            'settings'   => $settings ? $settings : (object) array(),
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function upsert_post_with_elementor( string $post_type, string $slug, string $title, array $elementor_tree, string $content, string $excerpt = '', array $meta = [] ): int {
        global $wpdb;
        $prev_suppress = $wpdb->suppress_errors( true );

        $existing = get_page_by_path( $slug, OBJECT, $post_type );
        $post_data = [
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => $post_type,
            'post_author'  => 1,
        ];

        if ( $existing ) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta($post_id,'_zarincode_seeded','1');
            if ( 'page' === $post_type ) {
                update_post_meta( $post_id, '_wp_page_template', 'template-elementor-canvas.php' );
            }
            if ( ! empty( $elementor_tree ) ) {
                update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
                update_post_meta( $post_id, '_elementor_template_type', 'wp-' . $post_type );
                update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.3.2' );
                update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_tree ) ) );
            }
            foreach ( $meta as $mk => $mv ) {
                update_post_meta( $post_id, $mk, $mv );
            }
        }

        $wpdb->suppress_errors( $prev_suppress );
        return (int) $post_id;
    }

    /**
     * 1. SEED HEADER, FOOTER, MEGAMENU & POPUP ELEMENTOR TEMPLATES
     */
    public static function seed_header_footer_templates(): void {
        /*
         * Header and footer are built from *native* Elementor widgets so the
         * owner can edit every column, link, colour and spacing value in the
         * editor (see Elementor\HF_Builder). The two extra templates
         * (megamenu + consultation popup) stay on the theme widgets because
         * they are dynamic lists, and those widgets are themselves editable.
         */
        if ( class_exists( \Zarincode\Runtime260\Elementor\HF_Builder::class ) ) {
            \Zarincode\Runtime260\Elementor\HF_Builder::ensure();
        }

        $hf_items = array(
            array(
                'slug'  => 'zarincode-services-megamenu',
                'title' => 'مگامنوی ۶ خدمت تخصصی و محصولات زرین کد',
                'type'  => 'megamenu',
                'tree'  => array( self::make_elementor_section( 'zarincode_services', array( 'count' => 6 ) ) ),
                'sc'    => '[zarincode_widget type="services"]',
            ),
            array(
                'slug'  => 'zarincode-consultation-popup',
                'title' => 'پاپ‌آپ درخواست مشاوره رایگان و سفارش پروژه (09024561001)',
                'type'  => 'popup',
                'tree'  => array( self::make_elementor_section( 'zarincode_promo', array() ) ),
                'sc'    => '[zarincode_widget type="promo"]',
            ),
        );

        foreach ( $hf_items as $hf ) {
            self::upsert_post_with_elementor(
                'zc_hf_template',
                $hf['slug'],
                $hf['title'],
                $hf['tree'],
                $hf['sc'],
                '',
                array(
                    '_zc_hf_type'   => $hf['type'],
                    '_zc_hf_active' => 'yes',
                )
            );
        }
    }

    /**
     * 2. SEED 6 CORE SERVICES IN zc_service CPT
     */
    public static function seed_services_cpt(): void {
        $services_data = [
            [
                'slug'     => 'website-design',
                'title'    => 'طراحی سایت اختصاصی',
                'en_title' => 'Website Design',
                'icon'     => 'fa-solid fa-laptop-code',
                'price'    => '۱۰ تا ۵۵ میلیون تومان',
                'old_p'    => '۲۰ تا ۱۱۰ میلیون',
                'duration' => '۲۰ تا ۴۵ روز کاری',
                'cat'      => 'طراحی وب و رابط کاربری',
                'excerpt'  => 'معماری محتوا، مسیر کاربر و رابط واکنش‌گرای فارسی؛ وب‌سایتی که سرعت، سئوی فنی و تجربهٔ موبایل را هم‌زمان رعایت می‌کند.',
                'content'  => '<h2>هر آنچه باید در مورد خدمات طراحی سایت در زرین کد بدانید</h2><p>در دنیای امروز که فضای دیجیتال بخش جدایی‌ناپذیری از زندگی افراد شده، داشتن یک وب‌سایت حرفه‌ای ضرورتی انکارناپذیر برای هر کسب‌وکاری است. تیم <strong>زرین کد (به مدیریت احسان نادری پناه - 09024561001)</strong> با طراحی اختصاصی UI/UX، پیاده‌سازی استاندارد با وردپرس و المنتور یا کدنویسی اختصاصی، وب‌سایتی سریع، امن و فروشنده‌ساز برای شما خلق می‌کند.</p><h3>ویژگی‌های کلیدی خدمات طراحی سایت زرین کد</h3><ul><li>انجام پروژه به‌صورت دورکاری و حضوری با قرارداد رسمی</li><li>گارانتی ۱۰۰٪ بازگشت وجه در صورت عدم رضایت</li><li>کدنویسی استاندارد منطبق با معیارهای ژاکت و تم‌فارست</li><li>پرداخت دو مرحله‌ای (۵۰٪ پیش‌پرداخت و ۵۰٪ پس از تحویل نهایی)</li><li>یک سال پشتیبانی فنی و گارانتی رایگان</li></ul>',
            ],
            [
                'slug'     => 'software-development',
                'title'    => 'برنامه‌نویسی نرم‌افزار',
                'en_title' => 'Software Development',
                'icon'     => 'fa-solid fa-microchip',
                'price'    => '۲۰ تا ۱۱۰ میلیون تومان',
                'old_p'    => '۴۰ تا ۲۲۰ میلیون',
                'duration' => '۳۰ تا ۶۰ روز کاری',
                'cat'      => 'مهندسی نرم‌افزار و سامانه',
                'excerpt'  => 'تحلیل گردش‌کار، معماری داده و پیاده‌سازی سامانه‌های اختصاصی تحت وب، CRM/ERP، API و افزونه — با مستندات و آزمون پذیرش.',
                'content'  => '<h2>مهندسی و برنامه‌نویسی نرم‌افزارهای اختصاصی در زرین کد</h2><p>اگر کسب‌وکار شما نیازمند فرآیندهای اختصاصی فراتر از سیستم‌های آماده است، واحد مهندسی نرم‌افزار <strong>زرین کد (Zarincode.com)</strong> با بهره‌گیری از PHP 8.x، Laravel، Node.js و React سامانه‌های ابری، مقیاس‌پذیر و امن را از صفر تا صد برای شما تحلیل، طراحی و کدنویسی می‌کند.</p>',
            ],
            [
                'slug'     => 'website-seo',
                'title'    => 'سئو سایت تضمینی',
                'en_title' => 'Website SEO Service',
                'icon'     => 'fa-solid fa-chart-line',
                'price'    => '۱۵ تا ۶۵ میلیون تومان',
                'old_p'    => '۳۰ تا ۱۳۰ میلیون',
                'duration' => 'دوره ۳ تا ۶ ماهه',
                'cat'      => 'سئو و بهینه‌سازی موتور جستجو',
                'excerpt'  => 'ممیزی فنی، رفع مشکلات ایندکس، بهینه‌سازی Core Web Vitals و نقشهٔ محتوایی مبتنی بر نیت جست‌وجو — بدون وعدهٔ رتبهٔ تضمینی.',
                'content'  => '<h2>خدمات سئو سایت تضمینی و حضور در رتبه اول گوگل</h2><p>در فضای رقابتی امروز، اگر در صفحه اول گوگل دیده نشوید، سهم بازار خود را به رقبا واگذار کرده‌اید. تیم سئو زرین کد با تدوین استراتژی دقیق کلمات کلیدی پول‌ساز، رفع خطاهای تکنیکال و لینک‌سازی هدفمند، ترافیک ارگانیک و فروش واقعی سایت شما را چند برابر می‌کند.</p>',
            ],
            [
                'slug'     => 'social-media-seo',
                'title'    => 'سئو شبکه‌های اجتماعی',
                'en_title' => 'Social Media SEO',
                'icon'     => 'fa-solid fa-hashtag',
                'price'    => '۸ تا ۲۵ میلیون تومان',
                'old_p'    => '۱۶ تا ۵۰ میلیون',
                'duration' => 'دوره ماهانه مستمر',
                'cat'      => 'سئو و رشد شبکه‌های اجتماعی',
                'excerpt'  => 'ساختار پروفایل، پیام برند و تقویم محتوا با هدف جذب مخاطب مرتبط و افزایش نرخ تبدیل در شبکه‌های اجتماعی.',
                'content'  => '<h2>سئو شبکه‌های اجتماعی (Instagram, LinkedIn & YouTube SEO)</h2><p>موتورهای جستجوی داخلی اینستاگرام، لینکدین و یوتیوب امروزه یکی از اصلی‌ترین کانال‌های جذب مشتری هستند. ما در زرین کد با بهینه‌سازی بایو، کلیدواژه‌های نام کاربری، کپشن‌نویسی الگوریتمی، Alt Text و استراتژی ریلزهای اکسپلوری، پیج شما را به آهنربای جذب مشتری تبدیل می‌کنیم.</p>',
            ],
            [
                'slug'     => 'content-creation',
                'title'    => 'تولید محتوا تخصصی',
                'en_title' => 'Content Creation',
                'icon'     => 'fa-solid fa-pen-nib',
                'price'    => '۷ تا ۳۰ میلیون تومان',
                'old_p'    => '۱۴ تا ۶۰ میلیون',
                'duration' => 'تقویم ۳۰ روزه ماهانه',
                'cat'      => 'تولید محتوا و برندینگ',
                'excerpt'  => 'محتوای تخصصی سئوشده، تقویم انتشار، سناریوی ویدئو و صفحهٔ فرود — نوشته‌شده برای پاسخ به پرسش واقعی مخاطب.',
                'content'  => '<h2>خدمات تولید محتوای تخصصی و استراتژی محتوا در زرین کد</h2><p>محتوا پادشاه دنیای دیجیتال است؛ اما تنها محتوایی که بر اساس نیاز کاربر و الگوریتم‌های سئو نگارش و طراحی شده باشد منجر به فروش می‌شود. تیم تولید محتوای زرین کد مقالات تخصصی، لندینگ‌پیج‌ها، بنرهای گرافیکی و ویدئوهای معرفی برند شما را با بالاترین کیفیت تولید می‌کند.</p>',
            ],
            [
                'slug'     => 'ready-made-products',
                'title'    => 'فروش محصولات آماده',
                // Matches the demo copy override «توسعه و یکپارچه‌سازی وردپرس» (Service_Demo record()).
                'en_title' => 'WordPress Integration',
                'icon'     => 'fa-solid fa-box-open',
                'price'    => 'از ۸۹۰ هزار تا ۱۲ میلیون تومان',
                'old_p'    => 'تحویل و دانلود آنی',
                'duration' => 'تحویل آنی پس از خرید',
                'cat'      => 'محصولات دیجیتال آماده',
                'excerpt'  => 'قالب، افزونه و سورس‌کدهای تولید زرین کد؛ با مستندات فنی، آپدیت رسمی و لایسنس شفاف برای شروع سریع‌تر.',
                'content'  => '<h2>مارکت‌پلیس محصولات آماده زرین کد (قالب، افزونه و سورس‌کد)</h2><p>تمامی قالب‌ها، افزونه‌های وردپرس و سورس‌کدهای نرم‌افزاری موجود در فروشگاه زرین کد حاصل کدنویسی مستقیم <strong>احسان نادری پناه</strong> بوده و دارای مستندات کامل، سورس باز، ۶ ماه پشتیبانی رایگان و آپدیت مادام‌العمر هستند.</p>',
            ],
        ];

        foreach ( $services_data as $i=>$srv ) {
            $srv=Service_Demo::record($srv,'service',$i);
            $term = term_exists( $srv['cat'], 'zc_service_cat' );
            if ( ! $term ) {
                $term = wp_insert_term( $srv['cat'], 'zc_service_cat' );
            }
            $post_id = self::upsert_post_with_elementor(
                'zc_service',
                $srv['slug'],
                $srv['title'],
                [],
                $srv['content'],
                $srv['excerpt'],
                [
                    '_zc_srv_en_title'          => $srv['en_title'],
                    '_zc_srv_icon'              => $srv['icon'],
                    '_zc_srv_price_range'       => $srv['price'],
                    '_zc_srv_old_price'         => $srv['old_p'],
                    '_zc_srv_discount'          => '',
                    '_zc_srv_delivery_mode'     => 'دورکاری و قرارداد رسمی',
                    '_zc_srv_refund_guarantee'  => 'طبق توافق و سیاست منتشرشده',
                    '_zc_srv_quality_guarantee' => 'معیار پذیرش مندرج در توافق',
                    '_zc_srv_prepayment'        => '50%',
                    '_zc_srv_duration'          => $srv['duration'],
                    '_zc_srv_reports_count'     => '۵ بار در روند پروژه',
                    '_zc_srv_revisions'         => '۳ بار پس از تحویل',
                    '_zc_srv_final_delivery'    => 'ارائه فایل سورس و اطلاعات کامل سایت',
                ]
            );
            if ( $post_id && ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
                wp_set_object_terms( $post_id, [ (int) $term['term_id'] ], 'zc_service_cat' );
            }
        }
    }

    /**
     * 3. SEED 8 PORTFOLIO CASE STUDIES IN zc_project CPT
     */
    public static function seed_projects_cpt(): void {
        $cats = [
            'web'       => 'طراحی سایت و فروشگاهی',
            'ecommerce' => 'فروشگاهی',
            'software'  => 'نرم‌افزار اختصاصی',
            'seo'       => 'سئو و محتوا',
        ];
        $term_ids = [];
        foreach ( $cats as $slug => $name ) {
            $t = term_exists( $slug, 'zc_project_cat' );
            if ( ! $t ) {
                $t = wp_insert_term( $name, 'zc_project_cat', [ 'slug' => $slug ] );
            }
            if ( ! is_wp_error( $t ) && isset( $t['term_id'] ) ) {
                $term_ids[ $slug ] = (int) $t['term_id'];
            }
        }

        $projects = [
            [
                'slug'     => 'tech-marketplace-design',
                'title'    => 'طراحی و کدنویسی مارکت‌پلیس تکنولوژی و دیجیتال پلاس',
                'client'   => 'گروه تجاری دیجیتال پلاس',
                'date'     => 'فروردین ۱۴۰۵',
                'duration' => '۲۵ روز کاری',
                'cms'      => 'وردپرس + ووکامرس + المنتور اختصاصی',
                'plan'     => 'پلن الماسی + خدمات سئو',
                'metrics'  => '+۳۲۰٪ رشد فروش آنلاین',
                'cats'     => [ 'web', 'ecommerce' ],
                'content'  => '<p>پروژه طراحی و پیاده‌سازی مارکت‌پلیس چندفروشندگی کالای دیجیتال با سیستم مقایسه پیشرفته، فیلتر ایجکس، پنل فروشندگان و بهینه‌سازی سرعت لود زیر ۱ ثانیه توسط تیم زرین کد (احسان نادری پناه).</p>',
            ],
            [
                'slug'     => 'crextio-cloud-hrm-software',
                'title'    => 'توسعه سامانه ابری مدیریت منابع انسانی و حقوق (مشابه Crextio)',
                'client'   => 'هلدینگ فناوری پارس',
                'date'     => 'اردیبهشت ۱۴۰۵',
                'duration' => '۴۰ روز کاری',
                'cms'      => 'Laravel 11 + React + REST API',
                'plan'     => 'پلن سامانه سازمانی',
                'metrics'  => '۱۲,۰۰۰ کاربر فعال روزانه',
                'cats'     => [ 'software' ],
                'content'  => '<p>طراحی و برنامه‌نویسی کامل سامانه ابری مدیریت منابع انسانی، تایم‌ترکر پروژه‌ها، صدور فیش حقوقی و داشبورد تحلیلی با رنگ‌بندی زرد سانشاین Crextio.</p>',
            ],
            [
                'slug'     => 'tebgostar-medical-seo',
                'title'    => 'سئو جامع سایت و رشد اکسپلور برند تجهیزات پزشکی طب‌گستر',
                'client'   => 'شرکت تجهیزات پزشکی طب‌گستر',
                'date'     => 'خرداد ۱۴۰۵',
                'duration' => '۹۰ روز کاری',
                'cms'      => 'Google Technical SEO + Instagram SEO',
                'plan'     => 'پلن اختصاصی سئو و شبکه اجتماعی',
                'metrics'  => 'رتبه ۱ گوگل در ۴۵ کلیدواژه',
                'cats'     => [ 'seo' ],
                'content'  => '<p>اجرای استراتژی یکپارچه سئو تکنیکال، تولید محتوای تخصصی پزشکی و سئوی الگوریتمی پیج اینستاگرام که منجر به کسب رتبه اول گوگل در ۴۵ کلمه کلیدی اصلی شد.</p>',
            ],
            [
                'slug'     => 'cloud-hosting-portal-design',
                'title'    => 'طراحی سایت پلتفرم فروش هاست ابری و سرور اختصاصی میهن‌کلود',
                'client'   => 'دیتاسنتر میهن‌کلود',
                'date'     => 'تیر ۱۴۰۵',
                'duration' => '۳۰ روز کاری',
                'cms'      => 'وردپرس + اتصال به WHMCS API',
                'plan'     => 'پلن الماسی طراحی سایت',
                'metrics'  => 'سرعت لود ۰.۸ ثانیه (GTmetrix A)',
                'cats'     => [ 'web', 'software' ],
                'content'  => '<p>طراحی رابط کاربری مدرن و اتصال کامل وب‌سایت وردپرسی به سیستم صورتحساب و خرید آنی سرور و دامنه WHMCS با امنیت چندلایه.</p>',
            ],
            [
                'slug'     => 'stylemode-fashion-ecommerce',
                'title'    => 'طراحی فروشگاه اینترنتی مد و پوشاک استایل‌مد با ست‌ساز هوشمند',
                'client'   => 'برند پوشاک استایل‌مد',
                'date'     => 'مرداد ۱۴۰۵',
                'duration' => '۲۰ روز کاری',
                'cms'      => 'ووکامرس + ویجت‌های اختصاصی المنتور',
                'plan'     => 'پلن طلایی فروشگاهی',
                'metrics'  => 'نرخ تبدیل ۴.۸٪ فروش',
                'cats'     => [ 'web', 'ecommerce' ],
                'content'  => '<p>پیاده‌سازی فروشگاه آنلاین پوشاک با قابلیت انتخاب سایز هوشمند، گالری ویدئویی محصول و تسویه‌حساب تک‌صفحه‌ای سریع.</p>',
            ],
            [
                'slug'     => 'fintech-magazine-content-seo',
                'title'    => 'طراحی پرتال خبری، مجله دیجیتال و استراتژی محتوای فین‌تک‌پلاس',
                'client'   => 'رسانه مالی فین‌تک‌پلاس',
                'date'     => 'شهریور ۱۴۰۵',
                'duration' => '۱۸ روز کاری',
                'cms'      => 'وردپرس + اسکیما خبری Google News',
                'plan'     => 'پلن طلایی + پکیج تولید محتوا',
                'metrics'  => '+۱۸۰ هزار بازدید ارگانیک ماهانه',
                'cats'     => [ 'web', 'seo' ],
                'content'  => '<p>طراحی قالب مجله‌ای فوق سریع به همراه تدوین تقویم محتوایی سئو شده و نگارش مقالات تحلیلی بازارهای مالی.</p>',
            ],
            [
                'slug'     => 'bartar-clinic-booking-software',
                'title'    => 'برنامه‌نویسی سامانه نوبت‌دهی آنلاین و پرونده الکترونیک کلینیک برتر',
                'client'   => 'کلینیک تخصصی پزشکی برتر',
                'date'     => 'مهر ۱۴۰۵',
                'duration' => '۳۵ روز کاری',
                'cms'      => 'PHP 8.4 + Vue.js + پنل پیامکی نوبت‌دهی',
                'plan'     => 'پلن سامانه اختصاصی نرم‌افزار',
                'metrics'  => '+۸,۵۰۰ نوبت آنلاین ماهانه',
                'cats'     => [ 'software', 'web' ],
                'content'  => '<p>طراحی و برنامه‌نویسی سامانه جامع نوبت‌دهی اینترنتی پزشکان، پرونده الکترونیک بیماران و یادآور خودکار پیامکی توسط احسان نادری پناه.</p>',
            ],
            [
                'slug'     => 'arad-holding-social-seo-campaign',
                'title'    => 'سئو شبکه‌های اجتماعی و تولید محتوای ویدئویی هلدینگ سرمایه‌گذاری آراد',
                'client'   => 'هلدینگ تجاری آراد',
                'date'     => 'آبان ۱۴۰۵',
                'duration' => '۶۰ روز کاری',
                'cms'      => 'Instagram & LinkedIn SEO + Content Studio',
                'plan'     => 'پلن الماسی سئو شبکه اجتماعی و محتوا',
                'metrics'  => '+۴۱۰٪ رشد تعامل و ورودی اکسپلور',
                'cats'     => [ 'seo' ],
                'content'  => '<p>بهینه‌سازی کامل پروفایل‌های لینکدین و اینستاگرام، تدوین تقویم محتوایی و تولید ریلزهای تخصصی با رشد ۴۱۰ درصدی ورودی اکسپلور.</p>',
            ],
        ];

        foreach ( $projects as $i=>$prj ) {
            $prj=Service_Demo::record($prj,'project',$i);
            $post_id = self::upsert_post_with_elementor(
                'zc_project',
                $prj['slug'],
                $prj['title'],
                [],
                $prj['content'],
                wp_strip_all_tags( $prj['content'] ),
                [
                    '_zc_prj_client'   => $prj['client'],
                    '_zc_prj_agency'   => 'زرین کد (احسان نادری پناه)',
                    '_zc_prj_date'     => $prj['date'],
                    '_zc_prj_duration' => $prj['duration'],
                    '_zc_prj_cms'      => $prj['cms'],
                    '_zc_prj_plan'     => $prj['plan'],
                    '_zc_prj_metrics'  => $prj['metrics'],
                    '_zc_prj_url'      => 'https://zarincode.com',
                ]
            );
            if ( $post_id ) {
                $assign_terms = [];
                foreach ( $prj['cats'] as $c ) {
                    if ( isset( $term_ids[ $c ] ) ) {
                        $assign_terms[] = $term_ids[ $c ];
                    }
                }
                if ( ! empty( $assign_terms ) ) {
                    wp_set_object_terms( $post_id, $assign_terms, 'zc_project_cat' );
                }
            }
        }
    }

    /**
     * 4. SEED 8 WOOCOMMERCE READY-MADE DIGITAL PRODUCTS
     */
    public static function seed_woocommerce_products(): void {
        if ( ! post_type_exists( 'product' ) && class_exists( '\Zarincode\Runtime260\PostTypes\CPT_Manager' ) ) {
            $cpt_mgr = \Zarincode\Runtime260\PostTypes\CPT_Manager::instance();
            if ( is_callable( [ $cpt_mgr, 'register_post_types_and_taxonomies' ] ) ) {
                $cpt_mgr->register_post_types_and_taxonomies();
            }
        }

        $items = [
            [
                'slug'        => 'zarincode-pro-wordpress-theme',
                'name'        => 'قالب وردپرس آژانسی و فروشگاهی زرین کد (Zarincode Pro)',
                'price'       => '2490000',
                'price_fa'    => '۲,۴۹۰,۰۰۰',
                'old_price'   => '۳,۹۰۰,۰۰۰',
                'ver'         => 'v2.5.0',
                'cat_badge'   => 'قالب وردپرس',
                'sales'       => '+۴۲۰ فروش موفق',
                'icon'        => 'fa-brands fa-wordpress',
                'tags'        => 'Elementor,Redux,WooCommerce,PHP 8.4,RTL Ready',
                'sku'         => 'ZC-THEME-01',
                'desc'        => 'قالب وردپرس المنتور بیس با ۱۴ ویجت اختصاصی، هدر و فوترساز المنتوری، پنل تنظیمات ۱۶ بخشی Redux Framework و رنگ‌بندی زرد سانشاین Crextio — کدنویسی شده توسط احسان نادری پناه.',
            ],
            [
                'slug'        => 'zarinseo-booster-plugin',
                'name'        => 'افزونه سئو هوشمند و ایندکس آنی گوگل (ZarinSEO Booster)',
                'price'       => '1290000',
                'price_fa'    => '۱,۲۹۰,۰۰۰',
                'old_price'   => '۲,۱۰۰,۰۰۰',
                'ver'         => 'v3.2.1',
                'cat_badge'   => 'افزونه وردپرس',
                'sales'       => '+۶۱۰ فروش موفق',
                'icon'        => 'fa-solid fa-plug-circle-bolt',
                'tags'        => 'Google Indexing API,Schema Pro,Auto-Index,WordPress',
                'sku'         => 'ZC-PLUG-01',
                'desc'        => 'افزونه اختصاصی وردپرس برای اتصال مستقیم به Google Indexing API، تولید خودکار اسکیماهای پیشرفته JSON-LD و آنالیز لحظه‌ای چگالی کلمات کلیدی.',
            ],
            [
                'slug'        => 'zarinyar-crm-full-source-code',
                'name'        => 'سورس‌کد سامانه جامع CRM، فاکتور و مدیریت مشتریان زرین‌یار',
                'price'       => '6900000',
                'price_fa'    => '۶,۹۰۰,۰۰۰',
                'old_price'   => '۹,۸۰۰,۰۰۰',
                'ver'         => 'v4.0.0',
                'cat_badge'   => 'سورس‌کد نرم‌افزار',
                'sales'       => '+۱۹۵ فروش موفق',
                'icon'        => 'fa-solid fa-boxes-stacked',
                'tags'        => 'Laravel 11,React,REST API,Full Source,Accounting',
                'sku'         => 'ZC-SRC-01',
                'desc'        => 'سورس‌کد کامل و متن‌باز PHP/Laravel + React شامل ماژول‌های مدیریت سرنخ، صدور پیش‌فاکتور و فاکتور رسمی، تیکتینگ، پنل حسابداری و اطلاع‌رسانی پیامکی.',
            ],
            [
                'slug'        => 'zarindigits-otp-auth-plugin',
                'name'        => 'افزونه ورود و ثبت‌نام پیامکی و پنل کاربری (ZarinDigits OTP)',
                'price'       => '980000',
                'price_fa'    => '۹۸۰,۰۰۰',
                'old_price'   => '۱,۶۵۰,۰۰۰',
                'ver'         => 'v2.1.0',
                'cat_badge'   => 'افزونه وردپرس',
                'sales'       => '+۸۴۰ فروش موفق',
                'icon'        => 'fa-solid fa-mobile-screen-button',
                'tags'        => 'OTP SMS,WooCommerce,AJAX Auth,20 Iranian Gateways',
                'sku'         => 'ZC-PLUG-02',
                'desc'        => 'سیستم احراز هویت دو مرحله‌ای پیامکی (OTP) برای وردپرس و ووکامرس با پشتیبانی از ۲۰ سامانه پیامکی ایران و پنل کاربری مدرن.',
            ],
            [
                'slug'        => 'zarinmart-woocommerce-theme',
                'name'        => 'قالب فروشگاهی سوپرمارکت و کالای دیجیتال (ZarinMart)',
                'price'       => '2190000',
                'price_fa'    => '۲,۱۹۰,۰۰۰',
                'old_price'   => '۳,۴۰۰,۰۰۰',
                'ver'         => 'v1.9.4',
                'cat_badge'   => 'قالب وردپرس',
                'sales'       => '+۳۱۰ فروش موفق',
                'icon'        => 'fa-solid fa-bag-shopping',
                'tags'        => 'WooCommerce,Elementor,AJAX Filter,Multi-Vendor',
                'sku'         => 'ZC-THEME-02',
                'desc'        => 'قالب فروشگاهی حرفه‌ای ووکامرس با فیلتر ایجکس، پیشنهاد شگفت‌انگیز تایمردار، مینی‌کارت کشویی و جدول مقایسه پیشرفته محصولات.',
            ],
            [
                'slug'        => 'zarinsocial-ai-platform-source',
                'name'        => 'سورس‌کد پلتفرم آنالیز و سئو شبکه‌های اجتماعی (ZarinSocial AI)',
                'price'       => '5400000',
                'price_fa'    => '۵,۴۰۰,۰۰۰',
                'old_price'   => '۷,۹۰۰,۰۰۰',
                'ver'         => 'v2.4.0',
                'cat_badge'   => 'سورس‌کد نرم‌افزار',
                'sales'       => '+۱۵۰ فروش موفق',
                'icon'        => 'fa-brands fa-instagram',
                'tags'        => 'Node.js,AI Analytics,Instagram SEO,LinkedIn Growth',
                'sku'         => 'ZC-SRC-02',
                'desc'        => 'نرم‌افزار تحت وب تحلیل هشتگ، زمان‌بندی انتشار پست و بهینه‌سازی سئو الگوریتمی اینستاگرام و لینکدین.',
            ],
            [
                'slug'        => 'zarincontent-ai-writer-plugin',
                'name'        => 'افزونه تولید محتوای خودکار با هوش مصنوعی (ZarinContent AI)',
                'price'       => '1450000',
                'price_fa'    => '۱,۴۵۰,۰۰۰',
                'old_price'   => '۲,۳۰۰,۰۰۰',
                'ver'         => 'v1.8.0',
                'cat_badge'   => 'افزونه وردپرس',
                'sales'       => '+۴۹۰ فروش موفق',
                'icon'        => 'fa-solid fa-wand-magic-sparkles',
                'tags'        => 'AI Content,Auto SEO,WordPress,Table of Contents',
                'sku'         => 'ZC-PLUG-03',
                'desc'        => 'افزونه وردپرس برای تولید مقالات سئو شده فارسی با ساختار هدینگ استاندارد، جدول محتوا و متادیسکریپشن خودکار.',
            ],
            [
                'slug'        => 'zarinlms-pro-online-academy-source',
                'name'        => 'سورس‌کد سامانه آموزش آنلاین و برگزاری وبینار (ZarinLMS Pro)',
                'price'       => '7800000',
                'price_fa'    => '۷,۸۰۰,۰۰۰',
                'old_price'   => '۱۱,۵۰۰,۰۰۰',
                'ver'         => 'v3.0.2',
                'cat_badge'   => 'سورس‌کد نرم‌افزار',
                'sales'       => '+۲۲۰ فروش موفق',
                'icon'        => 'fa-solid fa-graduation-cap',
                'tags'        => 'Full-Stack LMS,Quiz Engine,PDF Certificate,Shaparak',
                'sku'         => 'ZC-SRC-03',
                'desc'        => 'پلتفرم جامع فروش دوره آموزشی، برگزاری آزمون آنلاین، صدور گواهینامه PDF و کیف پول دیجیتال با سورس باز.',
            ],
        ];

        // Two more products so the shop grid shows a full 10-item catalogue.
        $items[] = array(
            'slug'      => 'zarinpanel-admin-dashboard-kit',
            'name'      => 'کیت داشبورد مدیریت و پنل کاربری زرین پنل (ZarinPanel UI Kit)',
            'price'     => '890000',
            'price_fa'  => '۸۹۰,۰۰۰',
            'old_price' => '',
            'ver'       => 'نسخه نمایشی',
            'cat_badge' => 'کیت رابط کاربری',
            'sales'     => 'محصول نمایشی',
            'icon'      => 'fa-solid fa-table-columns',
            'tags'      => 'Dashboard,Figma,HTML,RTL Panels',
            'sku'       => 'ZC-KIT-01',
            'desc'      => 'مجموعه کارت، نمودار و جدول آمادهٔ پنل مدیریت فارسی با پشتیبانی راست‌به‌چپ و رنگ‌بندی زرین کد.',
        );
        $items[] = array(
            'slug'      => 'zarincare-support-ticketing-source',
            'name'      => 'سورس‌کد سامانه تیکت و پشتیبانی مشتریان (ZarinCare Desk)',
            'price'     => '3450000',
            'price_fa'  => '۳,۴۵۰,۰۰۰',
            'old_price' => '',
            'ver'       => 'نسخه نمایشی',
            'cat_badge' => 'سورس‌کد نرم‌افزار',
            'sales'     => 'محصول نمایشی',
            'icon'      => 'fa-solid fa-headset',
            'tags'      => 'Ticketing,SLA,Knowledge Base,Laravel',
            'sku'       => 'ZC-SRC-04',
            'desc'      => 'سامانهٔ تیکت با صف‌بندی، سطح خدمت (SLA)، پایگاه دانش و گزارش رضایت مشتری — سورس‌کد کامل و مستند.',
        );

        $covers = array(
            'demo-product-cover.jpg',
            'demo-service-cover.jpg',
            'demo-project-cover.jpg',
            'demo-post-cover.jpg',
        );
        $idx = 0;

        foreach ( $items as $it ) {
            $it = Service_Demo::record( $it, 'product', $idx );
            $idx++;

            $product_id = self::upsert_post_with_elementor(
                'product',
                $it['slug'],
                $it['name'],
                array(),
                '<p>' . $it['desc'] . '</p>',
                $it['desc'],
                array(
                    '_regular_price'         => $it['price'],
                    '_price'                 => $it['price'],
                    '_sku'                   => $it['sku'],
                    '_virtual'               => 'yes',
                    '_downloadable'          => 'yes',
                    '_stock_status'          => 'instock',
                    '_manage_stock'          => 'no',
                    '_visibility'            => 'visible',
                    '_zc_prod_price_display' => $it['price_fa'],
                    '_zc_prod_old_price'     => $it['old_price'],
                    '_zc_prod_version'       => $it['ver'],
                    '_zc_prod_ver'           => $it['ver'],
                    '_zc_prod_cat_badge'     => $it['cat_badge'],
                    '_zc_prod_sales_count'   => $it['sales'],
                    '_zc_prod_sales'         => $it['sales'],
                    '_zc_prod_icon'          => $it['icon'],
                    '_zc_prod_tags'          => $it['tags'],
                    '_zc_prod_demo_url'      => home_url( '/?demo_preview=' . $it['slug'] ),
                    '_zc_prod_demo_label'    => 'پیش‌نمایش آنلاین دمو',
                )
            );

            if ( ! $product_id ) {
                continue;
            }

            // A featured image makes the shop grid look like a real store.
            self::attach_demo_image( $product_id, $covers[ ( $idx - 1 ) % count( $covers ) ], $it['name'] );

            self::sync_product( $product_id, $it );
        }

        /*
         * WooCommerce 9.x+ ships new stores in "Coming soon" mode: the shop URL
         * answers with a placeholder page for every visitor, so imported
         * products look like they were never created. Turning it off is part of
         * a complete demo import (the owner can switch it back on in
         * WooCommerce → Settings → Site visibility).
         */
        if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
            update_option( 'woocommerce_coming_soon', 'no' );
        }
        update_option( 'woocommerce_store_pages_only', 'no' );

        // Point WooCommerce at the demo shop page so /shop/ is the catalogue.
        $shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
        if ( $shop_page ) {
            update_option( 'woocommerce_shop_page_id', (int) $shop_page->ID );
        }

        /*
         * Finally, repair every demo product that WooCommerce may have skipped
         * (typical when the demo is imported before WooCommerce is active: the
         * post exists but has no product_type term, so the shop shows nothing).
         */
        self::repair_products();
    }

    /**
     * Make one post behave as a real WooCommerce product.
     *
     * WooCommerce decides what to list from the `product_type` taxonomy, not
     * from the post type alone. A product created through wp_insert_post()
     * without WooCommerce loaded has no `product_type` term, no price lookup
     * row and no visibility meta — it is invisible in the shop even though it
     * exists. This function fixes all of that idempotently.
     */
    public static function sync_product( int $product_id, array $data = array() ): void {
        if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
            return;
        }

        $price = (string) ( $data['price'] ?? get_post_meta( $product_id, '_regular_price', true ) );

        // 1. product_type = simple (the term WooCommerce reads).
        if ( taxonomy_exists( 'product_type' ) ) {
            if ( ! has_term( 'simple', 'product_type', $product_id ) ) {
                wp_set_object_terms( $product_id, 'simple', 'product_type' );
            }
        }

        // 2. WooCommerce price lookup keys + visibility meta.
        $meta = array(
            '_price'            => $price,
            '_regular_price'    => $price,
            '_stock_status'     => 'instock',
            '_manage_stock'     => 'no',
            '_virtual'          => 'yes',
            '_downloadable'     => 'yes',
            '_visibility'       => 'visible',
            '_sold_individually'=> 'no',
        );
        foreach ( $meta as $key => $value ) {
            if ( '' === $value || null === $value ) {
                continue;
            }
            update_post_meta( $product_id, $key, $value );
        }

        // 3. Let WooCommerce recalculate the lookup table + caches itself.
        if ( class_exists( 'WC_Product_Simple' ) && '' !== $price ) {
            $product = new \WC_Product_Simple( $product_id );
            $product->set_status( 'publish' );
            $product->set_regular_price( $price );
            $product->set_virtual( true );
            $product->set_downloadable( true );
            $product->set_catalog_visibility( 'visible' );
            if ( ! empty( $data['cat_badge'] ) && taxonomy_exists( 'product_cat' ) ) {
                $term = term_exists( $data['cat_badge'], 'product_cat' );
                if ( ! $term ) {
                    $term = wp_insert_term( $data['cat_badge'], 'product_cat' );
                }
                if ( ! is_wp_error( $term ) ) {
                    $product->set_category_ids( array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ) );
                }
            }
            $product->save();
        } elseif ( ! empty( $data['cat_badge'] ) && taxonomy_exists( 'product_cat' ) ) {
            $term = term_exists( $data['cat_badge'], 'product_cat' );
            if ( ! $term ) {
                $term = wp_insert_term( $data['cat_badge'], 'product_cat' );
            }
            if ( ! is_wp_error( $term ) ) {
                wp_set_object_terms( $product_id, array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ), 'product_cat' );
            }
        }

        clean_post_cache( $product_id );
    }

    /** Re-sync every demo product (also runs after WooCommerce is activated). */
    public static function repair_products(): int {
        if ( ! post_type_exists( 'product' ) ) {
            return 0;
        }
        $ids = get_posts( array(
            'post_type'        => 'product',
            'post_status'      => 'publish',
            'posts_per_page'   => 100,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => false,
        ) );
        $fixed = 0;
        foreach ( $ids as $id ) {
            self::sync_product( (int) $id );
            $fixed++;
        }
        if ( $fixed && function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
        }
        return $fixed;
    }

    /** Copy a bundled demo image into the media library and set it as the thumbnail. */
    private static function attach_demo_image( int $post_id, string $file, string $alt ): void {
        if ( has_post_thumbnail( $post_id ) ) {
            return;
        }
        $path = get_template_directory() . '/assets/images/' . $file;
        if ( ! file_exists( $path ) ) {
            return;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_upload_bits( $file, null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        if ( ! empty( $upload['error'] ) ) {
            return;
        }
        $attachment = array(
            'post_mime_type' => (string) ( $upload['type'] ?? 'image/jpeg' ),
            'post_title'     => sanitize_text_field( $alt ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
        if ( is_wp_error( $attach_id ) || ! $attach_id ) {
            return;
        }
        $meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $meta );
        update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        set_post_thumbnail( $post_id, $attach_id );
    }

    /**
     * 5. SEED ALL 13 ELEMENTOR PAGES (HOMEPAGE, SERVICES, PORTFOLIO, SHOP, ABOUT, CONTACT, BLOG + 6 LEGAL PAGES)
     */
    public static function seed_elementor_pages(): void {
        // ---------------------------------------------------------------
        // Homepage composition (v4.6.0) — "cheerful + formal" design.
        // Built exclusively from Elementor widgets so every string and every
        // repeater row is editable in the editor. Order follows the approved
        // design reference: hero → partners → services → products → portfolio
        // → why → steps → pricing → testimonials → magazine → final CTA.
        // ---------------------------------------------------------------
        $home_widgets = [
            [ 'widget' => 'zarincode_home_hero',         'sc' => '[zarincode_widget type="home_hero"]' ],
            [ 'widget' => 'zarincode_home_partners',     'sc' => '[zarincode_widget type="home_partners"]' ],
            [ 'widget' => 'zarincode_home_services',     'sc' => '[zarincode_widget type="home_services"]' ],
            [ 'widget' => 'zarincode_home_products',     'sc' => '[zarincode_widget type="home_products"]' ],
            [ 'widget' => 'zarincode_home_portfolio',    'sc' => '[zarincode_widget type="home_portfolio"]' ],
            [ 'widget' => 'zarincode_home_why',          'sc' => '[zarincode_widget type="home_why"]' ],
            [ 'widget' => 'zarincode_home_steps',        'sc' => '[zarincode_widget type="home_steps"]' ],
            [ 'widget' => 'zarincode_home_pricing',      'sc' => '[zarincode_widget type="home_pricing"]' ],
            [ 'widget' => 'zarincode_home_testimonials', 'sc' => '[zarincode_widget type="home_testimonials"]' ],
            [ 'widget' => 'zarincode_home_blog',         'sc' => '[zarincode_widget type="home_blog"]' ],
            [ 'widget' => 'zarincode_home_promo',        'sc' => '[zarincode_widget type="home_promo"]' ],
        ];

        // Widget classes extend Elementor\Widget_Base: loading them without
        // Elementor is a fatal error (it used to abort the whole demo import on
        // sites where Elementor was missing/inactive). The pages are still
        // created below — their content falls back to the widget shortcodes.
        $elementor_ready = class_exists( '\Elementor\Widget_Base' );
        if ( $elementor_ready ) {
            require_once dirname(__DIR__).'/Widgets/Elementor_Widgets_Collection.php';
        }
        $home_tree       = [];
        $home_shortcodes = [];
        foreach ( $home_widgets as $hw ) {
            // Seed the content defaults so the Elementor panel opens with real copy.
            $widget_settings = class_exists( \Zarincode\Runtime260\Widgets\Home_Defaults::class )
                ? \Zarincode\Runtime260\Widgets\Home_Defaults::settings( $hw['widget'] )
                : array();
            if ( $elementor_ready ) {
                $home_tree[] = self::make_elementor_section( $hw['widget'], $widget_settings );
            }
            $home_shortcodes[] = $hw['sc'];
        }

        $home_id = self::upsert_post_with_elementor(
            'page',
            'home',
            'صفحه اصلی - آژانس طراحی سایت، نرم‌افزار، سئو و فروشگاه محصولات آماده زرین کد',
            $home_tree,
            implode( "\n", $home_shortcodes )
        );

        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $home_id );

        self::seed_studio_pages();

        $inner_pages = [
            'services'             => [ 'title' => 'خدمات تخصصی زرین کد',                       'key' => 'services-page' ],
            'projects-portfolio'   => [ 'title' => 'نمونه‌کارها و پروژه‌های موفق زرین کد',       'key' => 'projects-page' ],
            'about-us'             => [ 'title' => 'درباره آژانس زرین کد و احسان نادری پناه',    'key' => 'about-page' ],
            'contact-us'           => [ 'title' => 'شرح پروژه و ارتباط با زرین کد',       'key' => 'contact-page' ],
            'terms-and-conditions' => [ 'title' => 'قوانین و شرایط و مقررات حاکم بر کشور ایران', 'key' => 'terms' ],
            'warranty-policy'      => [ 'title' => 'شرایط پشتیبانی و گارانتی',       'key' => 'warranty' ],
            'privacy-policy'       => [ 'title' => 'سیاست حفظ حریم خصوصی کاربران',               'key' => 'privacy' ],
            'refund-policy'        => [ 'title' => 'شرایط و ضوابط ضمانت بازگشت وجه',             'key' => 'refund' ],
            'gdpr-compliance'      => [ 'title' => 'قوانین حفاظت از داده‌های عمومی (GDPR)',      'key' => 'gdpr' ],
            'dmca-policy'          => [ 'title' => 'DMCA Copyright & Takedown Policy (English)', 'key' => 'dmca' ],
        ];

        foreach ( $inner_pages as $slug => $cfg ) {
            $page_settings = array( 'section_key' => $cfg['key'] );
            $legal_rows    = Service_Demo::legal_rows( $slug );
            if ( $legal_rows ) {
                // Feed the designed legal-document widget with the demo clauses
                // so the page has a real structure (hero, TOC, numbered cards)
                // instead of loose heading/text widgets.
                $page_settings['n_legal_content_items_1'] = $legal_rows;
                $nav_rows = Service_Demo::legal_nav( $slug );
                if ( $nav_rows ) {
                    $page_settings['n_legal_nav_items_1'] = $nav_rows;
                }
                $legal_data = Service_Demo::legal_data( $slug );
                if ( ! empty( $legal_data['title'] ) ) {
                    $page_settings['n_section_title_text_1'] = (string) $legal_data['title'];
                    $page_settings['n_heading_text_1']      = (string) $legal_data['title'];
                }
            }
            // Scoped widget per page model (item 10: the all-in-one widget put
            // 2.4 MB of controls into every editor load).
            $widget = 'zarincode_page_' . str_replace( '-', '_', (string) $cfg['key'] );
            // Without Elementor the page body is the widget shortcode (§ below),
            // so no (empty) builder data is written.
            $tree   = self::elementor_ready() ? [ self::make_elementor_section( $widget, $page_settings ) ] : [];
            $extras=['services'=>['service_spotlight','project_roadmap'],'projects-portfolio'=>['before_after'],'about-us'=>['team_experts','client_logos','testimonials']][$slug]??[];
            foreach(self::elementor_ready() ? $extras : [] as $extra){
                $extraSettings=[];
                if($extra==='before_after')$extraSettings=['before_image'=>['url'=>get_template_directory_uri().'/assets/images/demo-service-cover.jpg'],'after_image'=>['url'=>get_template_directory_uri().'/assets/images/demo-project-cover.jpg'],'caption'=>'نمونه نمایشی مقایسه؛ تصاویر پروژه واقعی را جایگزین کنید.'];
                if($extra==='client_logos')$extraSettings=self::demo_clients();
                $tree[]=self::make_elementor_section('zarincode_'.$extra,$extraSettings);
            }

            // The page body is the designed document section (hero, table of
            // contents, numbered clause cards, sidebar, CTA) — not a raw list of
            // <h2>/<p>. It is also the fallback shown when Elementor is not
            // rendering the page, so both paths look finished.
            $sc = '[zarincode_widget type="page_section" section="' . esc_attr( $cfg['key'] ) . '"]';
            $pid  = self::upsert_post_with_elementor( 'page', $slug, $cfg['title'], $tree, $sc );
            if ( 'privacy-policy' === $slug ) {
                update_option( 'wp_page_for_privacy_policy', $pid );
            }
        }

        // Shop Page
        $shop_tree = [
            self::make_elementor_section( 'zarincode_products', [ 'n_products_source'=>'query','n_products_count'=>8 ] ),
            self::make_elementor_section( 'zarincode_promo', [] ),
            self::make_elementor_section( 'zarincode_steps', [] ),
        ];
        self::upsert_post_with_elementor(
            'page',
            'shop',
            'فروشگاه قالب وردپرس، افزونه و سورس‌کدهای آماده نرم‌افزار زرین کد',
            $shop_tree,
            '[zarincode_widget type="products"][zarincode_widget type="promo"][zarincode_widget type="steps"]'
        );

        // Blog Page
        $blog_tree = [ self::make_elementor_section( 'zarincode_blog', ['n_blog_source'=>'query','n_blog_count'=>8] ) ];
        self::upsert_post_with_elementor(
            'page',
            'blog',
            'وبلاگ و مقالات آموزشی زرین کد',
            $blog_tree,
            '[zarincode_widget type="blog"]'
        );
    }

    /**
     * 6. SEED BLOG ARTICLES
     */
    public static function seed_blog_posts(): void {
        $blog_posts = Service_Demo::blog();

        foreach ( $blog_posts as $bp ) {
            $post_id=self::upsert_post_with_elementor(
                'post',
                $bp['slug'],
                $bp['title'],
                [],
                $bp['content'],
                wp_trim_words(wp_strip_all_tags( $bp['content'] ),34)
            );
            wp_update_post( array( 'ID' => $post_id, 'comment_status' => 'open' ) );
            $term = term_exists( 'راهنمای تصمیم و اجرا', 'category' );
            if ( ! $term ) {
                $term = wp_insert_term( 'راهنمای تصمیم و اجرا', 'category' );
            }
            if ( ! is_wp_error( $term ) ) {
                wp_set_post_terms( $post_id, array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ), 'category' );
            }
            // A cover makes the magazine grid look finished instead of a wall of text.
            self::attach_demo_image( $post_id, 'demo-post-cover.jpg', (string) $bp['title'] );
        }
    }

    /**
     * 7. SEED WORDPRESS NAVIGATION MENUS & ASSIGN LOCATIONS
     */
    public static function seed_navigation_menus(): void {
        $locations = get_theme_mod( 'nav_menu_locations', [] );
        if ( ! is_array( $locations ) ) {
            $locations = [];
        }

        // Primary Menu
        $primary_name = 'منوی اصلی هدر زرین کد';
        $primary_menu = wp_get_nav_menu_object( $primary_name );
        if ( ! $primary_menu ) {
            $menu_id = wp_create_nav_menu( $primary_name );
            if ( ! is_wp_error( $menu_id ) ) {
                $items = [
                    [ 'title' => 'صفحه اصلی',            'url' => home_url( '/' ) ],
                    [ 'title' => 'خدمات ما',             'url' => home_url( '/services/' ) ],
                    [ 'title' => 'فروشگاه سورس و افزونه', 'url' => home_url( '/shop/' ) ],
                    [ 'title' => 'نمونه‌کارها',           'url' => home_url( '/projects-portfolio/' ) ],
                    [ 'title' => 'قوانین و گارانتی',      'url' => home_url( '/terms-and-conditions/' ) ],
                    [ 'title' => 'درباره ما',             'url' => home_url( '/about-us/' ) ],
                    [ 'title' => 'تماس با ما',            'url' => home_url( '/contact-us/' ) ],
                ];
                foreach ( $items as $it ) {
                    wp_update_nav_menu_item( $menu_id, 0, [
                        'menu-item-title'  => $it['title'],
                        'menu-item-url'    => $it['url'],
                        'menu-item-status' => 'publish',
                        'menu-item-type'   => 'custom',
                    ] );
                }
                $locations['primary'] = $menu_id;
            }
        } else {
            $locations['primary'] = $primary_menu->term_id;
        }

        // Legal Menu
        $legal_name = 'منوی قوانین و اسناد حقوقی زرین کد';
        $legal_menu = wp_get_nav_menu_object( $legal_name );
        if ( ! $legal_menu ) {
            $l_id = wp_create_nav_menu( $legal_name );
            if ( ! is_wp_error( $l_id ) ) {
                $l_items = [
                    [ 'title' => 'قوانین و شرایط + قوانین ایران', 'url' => home_url( '/terms-and-conditions/' ) ],
                    [ 'title' => 'شرایط گارانتی و پشتیبانی',       'url' => home_url( '/warranty-policy/' ) ],
                    [ 'title' => 'سیاست حفظ حریم خصوصی',          'url' => home_url( '/privacy-policy/' ) ],
                    [ 'title' => 'شرایط ضمانت بازگشت وجه',        'url' => home_url( '/refund-policy/' ) ],
                    [ 'title' => 'قوانین حفاظت داده (GDPR)',      'url' => home_url( '/gdpr-compliance/' ) ],
                    [ 'title' => 'DMCA Policy (English)',         'url' => home_url( '/dmca-policy/' ) ],
                ];
                foreach ( $l_items as $it ) {
                    wp_update_nav_menu_item( $l_id, 0, [
                        'menu-item-title'  => $it['title'],
                        'menu-item-url'    => $it['url'],
                        'menu-item-status' => 'publish',
                        'menu-item-type'   => 'custom',
                    ] );
                }
                $locations['legal_nav'] = $l_id;
            }
        } else {
            $locations['legal_nav'] = $legal_menu->term_id;
        }

        set_theme_mod( 'nav_menu_locations', $locations );
    }

    /**
     * 8. SEED REDUX FRAMEWORK OPTIONS
     */
    public static function seed_redux_options(): void {
        $defaults = [
            'brand_name'             => 'زرین کد',
            'brand_en_name'          => 'Zarincode',
            'developer_name'         => 'احسان نادری پناه (Ehsan NaderiPanah)',
            'contact_phone'          => '09024561001',
            'contact_email'          => 'info@zarincode.com',
            'official_website'       => 'https://zarincode.com',
            'accent_color'           => '#FDD05B',
            'dark_surface_color'     => '#2A2C31',
            'bg_gradient_from'       => '#F8F6F0',
            'bg_gradient_to'         => '#FFF5D1',
            'enable_color_switcher'  => true,
            'primary_font'           => 'Vazirmatn',
            'persian_digits'         => true,
            'shop_hero_title'        => 'ابزارهای مکمل اجرای پروژه — کاتالوگ نمایشی',
            'product_warranty_badge' => 'پیش‌نیازها و شرایط پشتیبانی هر محصول را پیش از خرید بررسی کنید',
            'social_whatsapp'=>'https://wa.me/989024561001','social_github'=>'https://github.com/Ehsan-Np','logo_monogram'=>'ZC','floating_contact_action'=>'socials','floating_contact_label'=>'راه‌های ارتباطی','topbar_show_phone'=>false,'topbar_show_dev'=>false,'enable_topbar'=>false,'blog_sidebar_layout'=>'both','blog_article_ratings'=>true,
        ];
        /*
         * MERGE, never overwrite.
         *
         * This used to be `update_option( 'zarincode_opt', $defaults )`, which
         * silently threw away every setting the site owner had saved (brand
         * name, phone, colours, chat, footer…). A demo import must only fill
         * the keys the site does not have yet; anything already saved — by the
         * options panel, Redux, or a JSON import — always wins.
         */
        $saved = get_option( 'zarincode_opt', array() );
        $saved = is_array( $saved ) ? $saved : array();
        $merged = $defaults;
        foreach ( $saved as $key => $value ) {
            if ( null !== $value && '' !== $value ) {
                $merged[ $key ] = $value;
            }
        }
        update_option( 'zarincode_opt', $merged );
        // Tell the next admin page load to keep the owner's values.
        update_option( 'zarincode_options_preserved_on_import', gmdate( 'c' ), false );
    }

    /**
     * SEED EVERYTHING IN ONE CALL
     */
    private static function demo_clients():array {
        $clients=[];for($i=1;$i<=6;$i++)$clients[]=['name'=>'لوگوی مشتری شما — نمونه '.$i,'logo'=>['url'=>get_template_directory_uri().'/assets/images/demo-client-'.$i.'.png'],'alt'=>'لوگوی نمایشی قابل جایگزینی','service'=>'نمونه چیدمان همکاری','link'=>['url'=>home_url('/projects-portfolio/')]];
        return ['title'=>'نمونه چیدمان همکاری‌های شما','description'=>'لوگوها نمایشی‌اند؛ نام و نشان مشتریان واقعی خود را جایگزین کنید.','clients'=>$clients];
    }
    private static function demo_technologies(int $limit=8):array {
        $m=\Zarincode\Runtime260\Widgets\Native_Design::model('brands');$rows=$m['repeaters']['marquee_track_items_1']['default'];
        foreach($rows as &$row){$slug='technology-'.sanitize_title($row['span_text_1']);$row['brand_link']=['url'=>home_url('/'.$slug.'/')];$row['brand_description']='معرفی تجربه، کاربردها و خدمات تخصصی زرین کد با این فناوری.';
            $tree=[self::make_elementor_section('heading',['title'=>$row['span_text_1'],'header_size'=>'h1']),self::make_elementor_section('text-editor',['editor'=>'<p>در این صفحه می‌توانید تجربه واقعی تیم در استفاده از '.esc_html($row['span_text_1']).'، خدمات مرتبط، نمونه‌کارها و پرسش‌های تخصصی را معرفی کنید. این متن نمایشی را با محتوای اختصاصی و مستند خود جایگزین کنید.</p>']),self::make_elementor_section('zarincode_service_spotlight',['title'=>'راهکار متناسب با پروژه شما'])];
            self::upsert_post_with_elementor('page',$slug,$row['span_text_1'].' — تخصص زرین کد',$tree,'');
        }unset($row);
        return ['n_brands_marquee_track_items_1'=>array_slice($rows,0,$limit)];
    }
    /** Canonical panel settings of the pricing list widget («شکل قبلی»): the exact 41 keys of the real pricing page. */
    private static function pricing_widget_settings(): array {
        static $cache=null;
        if($cache===null)$cache=(array)json_decode((string)file_get_contents(__DIR__.'/fixtures/pricing-widget-settings.json'),true);
        return $cache;
    }
    /**
     * Exact body of the pricing page («شکل قبلی» جدول لیست قیمت): heading + list + plan cards + matrix.
     * Shared verbatim by the pricing page (h1) and the homepage (h2) so both stay identical forever.
     */
    private static function pricing_body_sections(string $heading_tag): array {
        // Single pricing composition: badge + heading + tabbed plan cards + comparison matrix.
        // (A second plain heading and a duplicate static plan-card grid used to be stacked
        // here, making the section read as a repeated block on the homepage and pricing page.)
        $widget = self::pricing_widget_settings();
        $widget['n_pricing_section_title_1_tag'] = $heading_tag;
        return [
            self::make_elementor_section('zarincode_pricing',$widget),
            self::make_elementor_section('zarincode_service_matrix',[]),
        ];
    }
    private static function seed_studio_pages():void {
        self::upsert_post_with_elementor('page','trust-and-credentials','اعتبارنامه‌ها و شفافیت کسب‌وکار',[self::make_elementor_section('heading',['title'=>'اعتبار، با امکان استعلام','header_size'=>'h1']),self::make_elementor_section('text-editor',['editor'=>'<p>نشان‌های این بخش تنها پس از صدور برای دامنه شما و ثبت کد رسمی در ویجت اعتبارنامه‌ها نمایش داده می‌شوند. خالی بودن بخش به معنی دریافت مجوز نیست.</p>']),self::make_elementor_section('zarincode_trust_badges',[])],'');
        self::upsert_post_with_elementor('page','pricing','تعرفه خدمات و انتخاب پلن',self::pricing_body_sections('h1'),'');
        self::upsert_post_with_elementor('page','seo-performance','سئو و بهبود عملکرد',[self::make_elementor_section('heading',['title'=>'تخصص سئو؛ از تحلیل تا رشد پایدار','header_size'=>'h1']),self::make_elementor_section('zarincode_seo_audit',[])],'');
        self::upsert_post_with_elementor('page','technologies','فناوری‌ها و تخصص تیم زرین کد',[self::make_elementor_section('zarincode_brands',self::demo_technologies())],'');
    }

    /** Only explicit demo import calls this; existing custom thumbnails are never replaced. */
    public static function seed_featured_images():void {
        require_once ABSPATH.'wp-admin/includes/image.php';
        foreach(['product'=>'product','zc_service'=>'service','zc_project'=>'project','post'=>'post'] as $type=>$asset){
            $file=get_template_directory().'/assets/images/demo-'.$asset.'-cover.jpg';
            if(!is_file($file))continue;
            $option='zarincode_demo_cover_'.$asset;$attachment=absint(get_option($option));
            if(!$attachment||get_post_type($attachment)!=='attachment'){
                $upload=wp_upload_bits('zarincode-demo-'.$asset.'.jpg',null,file_get_contents($file));
                if(!empty($upload['error']))continue;
                $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>'تصویر نمایشی زرین کد — '.$asset,'post_status'=>'inherit'],$upload['file']);
                if(is_wp_error($attachment)||!$attachment)continue;
                wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$upload['file']));
                update_post_meta($attachment,'_wp_attachment_image_alt','تصویر نمایشی خدمات دیجیتال زرین کد');update_option($option,$attachment,false);
            }
            $ids=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_zarincode_seeded','meta_value'=>'1']);
            // Match only explicit demo records using their seeder marker (not customer content).
            foreach($ids as $id)if(!has_post_thumbnail($id))set_post_thumbnail($id,$attachment);
        }
    }

    public static function seed_all(): void {
        // Persian locale: admin + front in fa_IR (install the pack when possible; set the
        // option regardless so is_rtl()/date_i18n pick Persian up after any language sync).
        if ( function_exists( 'wp_download_language_pack' ) ) {
            @wp_download_language_pack( 'fa_IR' );
        }
        update_option( 'WPLANG', 'fa_IR' );
        // Sell in Toman (Setup registers symbol + zero-decimals filters).
        if ( class_exists( '\WooCommerce' ) ) {
            update_option( 'woocommerce_currency', 'TOMAN' );
            // Number first, symbol after ("7,800,000 تومان") — correct reading order in RTL.
            update_option( 'woocommerce_currency_pos', 'right_space' );
        }
        // Branded demo author instead of the default "admin".
        wp_update_user( [ 'ID' => 1, 'display_name' => 'احسان نادری پناه' ] );
        // Remove the stock "Hello world!" post so it never leaks into demo blog grids.
        $hello = get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'title' => 'Hello world!', 'posts_per_page' => 1, 'fields' => 'ids' ] );
        foreach ( $hello as $hid ) {
            wp_delete_post( $hid, true );
        }
        if ( class_exists( '\Zarincode\Runtime260\PostTypes\CPT_Manager' ) ) {
            $cpt_mgr = \Zarincode\Runtime260\PostTypes\CPT_Manager::instance();
            if ( is_callable( [ $cpt_mgr, 'register_post_types_and_taxonomies' ] ) ) {
                $cpt_mgr->register_post_types_and_taxonomies();
            }
        }
        self::seed_redux_options();
        self::seed_header_footer_templates();
        self::seed_services_cpt();
        self::seed_projects_cpt();
        self::seed_woocommerce_products();
        self::seed_elementor_pages();
        self::seed_blog_posts();
        self::seed_featured_images();
        self::seed_navigation_menus();
        Service_Demo::sidebars();
        if(class_exists('Elementor\\Plugin'))\Elementor\Plugin::instance()->files_manager->clear_cache();
        flush_rewrite_rules( false );
    }

    /**
     * OCDI (One Click Demo Import) Plugin Compatibility
     */
    public function ocdi_import_files(): array {
        return [
            [
                'import_file_name'           => 'دموی اصلی زرین کد (تلفیق Webinex + رنگ‌بندی زرد سانشاین Crextio)',
                'categories'                 => [ 'فروشگاهی', 'شرکتی و آژانسی', 'نرم‌افزار و سئو' ],
                'local_import_file'          => get_template_directory() . '/demo-data/zarincode-demo-content.xml',
                'local_import_redux'         => [
                    [
                        'file_path'   => get_template_directory() . '/demo-data/zarincode-redux-options.json',
                        'option_name' => 'zarincode_opt',
                    ],
                ],
                'import_preview_image_url'   => get_template_directory_uri() . '/screenshot.png',
                'preview_url'                => 'https://zarincode.com',
                'import_notice'              => 'با کلیک روی دکمه درون‌ریزی، تمامی صفحات المنتوری، هدر، فوتر، ۶ خدمت، ۸ نمونه‌کار، ۸ محصول آماده، منوها و صفحات قوانین ایران، گارانتی، بازگشت وجه، GDPR و DMCA به صورت کامل ساخته می‌شوند.',
            ],
            [
                'import_file_name'           => 'مقالات تخصصی زرین کد (سئو، امنیت، وردپرس، سرعت و طراحی سایت)',
                'categories'                 => [ 'مقالات آموزشی' ],
                'local_import_file'          => get_template_directory() . '/demo-data/zarincode-articles.xml',
                'import_preview_image_url'   => get_template_directory_uri() . '/screenshot.png',
                'preview_url'                => 'https://zarincode.com/blog/',
                'import_notice'              => 'با کلیک روی دکمه درون‌ریزی، ۷۱ مقاله فارسی در ۶ دسته تخصصی (طراحی سایت، سئو، تولید محتوا، آموزش وردپرس، امنیت وب‌سایت و سرعت و بهینه‌سازی) همراه با دسته‌بندی‌ها، خلاصه‌ها و تصاویر شاخص ساخته می‌شوند؛ تنظیمات دمو تغییری نمی‌کند.',
            ],
        ];
    }

    public function ocdi_after_import_setup(): void {
        self::seed_all();
        self::assign_demo_article_covers();
    }

    /**
     * Attach rotating theme demo covers to imported magazine articles
     * (posts marked with the _zc_demo_article meta by the WXR package)
     * so blog cards render with imagery right after demo import.
     */
    public static function assign_demo_article_covers(): void {
        $ids = get_posts( [
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => '_zc_demo_article',
            'meta_value'     => '1',
        ] );
        if ( ! $ids ) {
            return;
        }
        $covers = array_values( array_filter( [
            get_template_directory() . '/assets/images/demo-post-cover.jpg',
            get_template_directory() . '/assets/images/demo-project-cover.jpg',
            get_template_directory() . '/assets/images/demo-service-cover.jpg',
            get_template_directory() . '/assets/images/demo-product-cover.jpg',
        ], 'file_exists' ) );
        if ( ! $covers ) {
            return;
        }
        $made = [];
        foreach ( $ids as $i => $post_id ) {
            if ( get_post_thumbnail_id( $post_id ) ) {
                continue;
            }
            $src = $covers[ $i % count( $covers ) ];
            if ( ! isset( $made[ $src ] ) ) {
                $made[ $src ] = self::sideload_demo_image( $src );
            }
            if ( $made[ $src ] ) {
                set_post_thumbnail( $post_id, (int) $made[ $src ] );
            }
        }
    }

    /**
     * Copy a local theme image into the uploads library once and return
     * the new attachment id (0 on failure).
     */
    private static function sideload_demo_image( string $src ): int {
        static $done = [];
        if ( isset( $done[ $src ] ) ) {
            return $done[ $src ];
        }
        $done[ $src ] = 0;
        $uploads      = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) || ! is_readable( $src ) ) {
            return 0;
        }
        $dest = trailingslashit( $uploads['path'] ) . 'zc-' . basename( $src );
        if ( ! @copy( $src, $dest ) ) {
            return 0;
        }
        $attach_id = wp_insert_attachment(
            [
                'post_mime_type' => 'image/jpeg',
                'post_title'     => sanitize_file_name( basename( $src ) ),
                'post_status'    => 'inherit',
            ],
            $dest,
            0
        );
        if ( ! $attach_id || is_wp_error( $attach_id ) ) {
            return 0;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata( (int) $attach_id, $dest );
        if ( $meta ) {
            wp_update_attachment_metadata( (int) $attach_id, $meta );
        }
        $done[ $src ] = (int) $attach_id;
        return $done[ $src ];
    }
}
