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
 * @package Zarincode\Core\Demo
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\Demo;

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
        add_action( 'after_switch_theme', [ $this, 'auto_seed_on_activation' ] );
        add_action( 'init', [ $this, 'maybe_auto_seed_first_time' ], 99 );
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
        // Top-Level Menu in WP Admin Sidebar so it is impossible to miss
        add_menu_page(
            'درون‌ریز دمو زرین کد',
            'درون‌ریز دمو زرین کد',
            'manage_options',
            'zarincode-demo-importer',
            [ $this, 'render_admin_page' ],
            'dashicons-cloud-Download',
            3
        );

        // Also under Appearance > درون‌ریز دموی زرین کد
        add_theme_page(
            'درون‌ریز هوشمند دموی زرین کد',
            'درون‌ریز دموی زرین کد',
            'manage_options',
            'zarincode-demo-importer-sub',
            [ $this, 'render_admin_page' ]
        );
    }

    public function render_admin_notice_banner(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && strpos( $screen->id, 'zarincode-demo-importer' ) !== false ) {
            return;
        }
        $import_url = admin_url( 'admin.php?page=zarincode-demo-importer' );
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
                    $message = '۸ محصول دیجیتال فروشگاه ووکامرس و ۶ مقاله تخصصی وبلاگ با موفقیت درون‌ریزی شدند!';
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
        <div class="wrap" style="direction:rtl;font-family:Tahoma,system-ui,sans-serif;max-width:1100px;margin-top:20px;">
            <div style="background:linear-gradient(135deg,#2A2C31 0%,#18191D 100%);color:#fff;padding:30px;border-radius:24px;margin-bottom:24px;border:2px solid #FDD05B;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
                <span style="background:#FDD05B;color:#2A2C31;padding:4px 14px;border-radius:999px;font-weight:bold;font-size:12px;">ZARINCODE ONE-CLICK DEMO IMPORTER v2.5</span>
                <h1 style="color:#fff;font-size:24px;margin:12px 0 8px;">پنل درون‌ریز هوشمند و یک‌کلیکی قالب «زرین کد (Zarincode)»</h1>
                <p style="color:#D1D5DB;font-size:14px;margin:0;">
                    طراحی و توسعه توسط <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> | شماره تماس: <strong dir="ltr" style="color:#FDD05B;">09024561001</strong> | وب‌سایت: <a href="https://zarincode.com" target="_blank" style="color:#FDD05B;">Zarincode.com</a>
                </p>
            </div>

            <?php if ( $message ) : ?>
                <div style="background:#ECFDF5;border:2px solid #10B981;color:#065F46;padding:18px 22px;border-radius:16px;margin-bottom:24px;font-weight:bold;font-size:14px;">
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

            <!-- Live Status Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:14px;margin-bottom:24px;">
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_hf; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">هدر، فوتر و مگامنوی المنتور</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_hf_template' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت هدر و فوتر ←</a>
                </div>
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_services; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">خدمات تخصصی (پست‌تایپ)</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_service' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت خدمات ←</a>
                </div>
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_projects; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">نمونه‌کارها و پروژه‌ها</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_project' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت نمونه‌کارها ←</a>
                </div>
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_pages; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">برگه‌های المنتوری و قوانین</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت برگه‌ها ←</a>
                </div>
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_products; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">محصولات آماده ووکامرس</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت محصولات ←</a>
                </div>
                <div style="background:#fff;padding:18px;border-radius:18px;border:1px solid #E5E7EB;text-align:center;">
                    <div style="font-size:26px;font-weight:900;color:#2A2C31;"><?php echo (int) $cnt_posts; ?></div>
                    <div style="font-size:13px;color:#6B7280;margin-top:4px;">مقالات آموزشی وبلاگ</div>
                    <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" style="font-size:12px;display:inline-block;margin-top:6px;">مدیریت نوشته‌ها ←</a>
                </div>
            </div>

            <!-- Import Form -->
            <form method="post" style="background:#fff;padding:28px;border-radius:24px;border:1px solid #E5E7EB;">
                <?php wp_nonce_field( 'zarincode_seed_nonce' ); ?>
                <h2 style="margin-top:0;font-size:18px;">انتخاب نحوه درون‌ریزی دمو (تلفیق Webinex + رنگ‌بندی زرد سانشاین Crextio)</h2>
                <p style="color:#4B5563;font-size:13.5px;line-height:1.8;">
                    با کلیک روی دکمه زیر، تمامی محتوای دمو بدون حذف مطالب شخصی شما ایجاد و بروزرسانی می‌شوند و صفحه اصلی، هدر، فوتر، منوها و صفحات قوانین به صورت خودکار تنظیم می‌گردند:
                </p>

                <div style="margin:18px 0;">
                    <label style="display:block;padding:12px 16px;background:#FFFBEB;border:2px solid #FDD05B;border-radius:12px;margin-bottom:10px;cursor:pointer;font-weight:bold;">
                        <input type="radio" name="zarincode_seed_scope" value="all" checked>
                        🌟 درون‌ریزی کامل همه بخش‌ها با یک کلیک (پیشنهادی: هدر و فوتر + ۶ خدمت + ۸ نمونه‌کار + ۸ محصول فروشگاه + ۱۳ برگه المنتوری و قوانین + منوها + تنظیمات Redux)
                    </label>
                    <label style="display:block;padding:10px 16px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="zarincode_seed_scope" value="header_footer">
                        فقط درون‌ریزی هدر، فوتر، مگامنوی المنتوری و فهرست‌های منو (Header, Footer &amp; Menus)
                    </label>
                    <label style="display:block;padding:10px 16px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="zarincode_seed_scope" value="services_projects">
                        فقط درون‌ریزی ۶ خدمت تخصصی و ۸ نمونه‌کار (Services &amp; Portfolio CPTs)
                    </label>
                    <label style="display:block;padding:10px 16px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="zarincode_seed_scope" value="pages_legal">
                        فقط درون‌ریزی ۱۳ برگه المنتوری (صفحه اصلی، درباره ما، تماس با ما و ۶ صفحه قوانین ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA)
                    </label>
                    <label style="display:block;padding:10px 16px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;cursor:pointer;">
                        <input type="radio" name="zarincode_seed_scope" value="shop_blog">
                        فقط درون‌ریزی محصولات فروشگاه ووکامرس و مقالات وبلاگ
                    </label>
                </div>

                <button type="submit" name="zarincode_run_seeder" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:900;font-size:15px;padding:8px 32px;border-radius:999px;box-shadow:0 8px 20px rgba(253,208,91,0.45);">
                    🚀 شروع درون‌ریزی خودکار دموی زرین کد
                </button>
            </form>
        </div>
        <?php
    }

    private static function gen_id(): string {
        return substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
    }

    private static function make_elementor_section( string $widget_type, array $settings = [] ): array {
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
                            'settings'   => $settings,
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
        $hf_items = [
            [
                'slug'  => 'zarincode-main-header',
                'title' => 'هدر اصلی المنتوری زرین کد (Crextio Sunshine Pill Header)',
                'type'  => 'header',
                'tree'  => [ self::make_elementor_section( 'zarincode_header', [
                    'brand_title'    => 'زریــن کـد',
                    'brand_subtitle' => 'Zarincode Studio',
                    'dev_name'       => 'احسان نادری پناه',
                    'phone'          => '09024561001',
                    'cta_text'       => 'درخواست مشاوره',
                ] ) ],
                'sc'    => '[zarincode_widget type="header"]',
            ],
            [
                'slug'  => 'zarincode-main-footer',
                'title' => 'فوتر بنتو دارک المنتوری زرین کد (Crextio Dark Bento Footer)',
                'type'  => 'footer',
                'tree'  => [ self::make_elementor_section( 'zarincode_footer', [
                    'cta_heading' => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟',
                    'phone'       => '09024561001',
                    'dev_name'    => 'احسان نادری پناه (Ehsan NaderiPanah)',
                ] ) ],
                'sc'    => '[zarincode_widget type="footer"]',
            ],
            [
                'slug'  => 'zarincode-services-megamenu',
                'title' => 'مگامنوی ۶ خدمت تخصصی و محصولات زرین کد',
                'type'  => 'megamenu',
                'tree'  => [ self::make_elementor_section( 'zarincode_services', [ 'count' => 6 ] ) ],
                'sc'    => '[zarincode_widget type="services"]',
            ],
            [
                'slug'  => 'zarincode-consultation-popup',
                'title' => 'پاپ‌آپ درخواست مشاوره رایگان و سفارش پروژه (09024561001)',
                'type'  => 'popup',
                'tree'  => [ self::make_elementor_section( 'zarincode_promo', [] ) ],
                'sc'    => '[zarincode_widget type="promo"]',
            ],
        ];

        foreach ( $hf_items as $hf ) {
            self::upsert_post_with_elementor(
                'zc_hf_template',
                $hf['slug'],
                $hf['title'],
                $hf['tree'],
                $hf['sc'],
                '',
                [
                    '_zc_hf_type'   => $hf['type'],
                    '_zc_hf_active' => 'yes',
                ]
            );

            // Also save in elementor_library so it appears in Elementor > Saved Templates as well!
            if ( post_type_exists( 'elementor_library' ) ) {
                self::upsert_post_with_elementor(
                    'elementor_library',
                    $hf['slug'] . '-el',
                    $hf['title'],
                    $hf['tree'],
                    $hf['sc'],
                    '',
                    [ '_elementor_template_type' => 'section' ]
                );
            }
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
                'excerpt'  => 'طراحی سایت حرفه‌ای، مدرن و المنتور بیس با رنگ‌بندی زرد سانشاین Crextio، سرعت بارگذاری زیر ۱ ثانیه، نمایش کامل روی موبایل و پنل مدیریتی آسان برای رشد فروش شما.',
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
                'excerpt'  => 'توسعه و معماری نرم‌افزارهای اختصاصی تحت وب، سامانه‌های سازمانی CRM/ERP، وب‌اپلیکیشن‌های پیشرفته، API و افزونه‌های اختصاصی توسط احسان نادری پناه.',
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
                'excerpt'  => 'بهبود تضمینی رتبه وب‌سایت شما در صفحه اول گوگل با سئو تکنیکال، بهینه‌سازی Core Web Vitals، استراتژی کلمات کلیدی و لینک‌سازی کلاه‌سفید.',
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
                'excerpt'  => 'بهینه‌سازی الگوریتمی پیج‌های اینستاگرام، لینکدین، یوتیوب و تلگرام؛ ورود هدفمند به اکسپلور و صدر نتایج جستجوی شبکه‌های اجتماعی.',
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
                'excerpt'  => 'تولید محتوای متنی سئو شده، تقویم محتوایی، سناریونویسی ویدئو و ریلز، طراحی بنر و مدیریت جامع محتوای وب‌سایت و شبکه‌های اجتماعی.',
                'content'  => '<h2>خدمات تولید محتوای تخصصی و استراتژی محتوا در زرین کد</h2><p>محتوا پادشاه دنیای دیجیتال است؛ اما تنها محتوایی که بر اساس نیاز کاربر و الگوریتم‌های سئو نگارش و طراحی شده باشد منجر به فروش می‌شود. تیم تولید محتوای زرین کد مقالات تخصصی، لندینگ‌پیج‌ها، بنرهای گرافیکی و ویدئوهای معرفی برند شما را با بالاترین کیفیت تولید می‌کند.</p>',
            ],
            [
                'slug'     => 'ready-made-products',
                'title'    => 'فروش محصولات آماده',
                'en_title' => 'Ready-Made Themes, Plugins & Source Codes',
                'icon'     => 'fa-solid fa-box-open',
                'price'    => 'از ۸۹۰ هزار تا ۱۲ میلیون تومان',
                'old_p'    => 'تحویل و دانلود آنی',
                'duration' => 'تحویل آنی پس از خرید',
                'cat'      => 'محصولات دیجیتال آماده',
                'excerpt'  => 'فروشگاه اختصاصی قالب‌های حرفه‌ای وردپرس، افزونه‌های کاربردی و سورس‌کدهای آماده نرم‌افزار کدنویسی‌شده توسط احسان نادری پناه با آپدیت دائمی.',
                'content'  => '<h2>مارکت‌پلیس محصولات آماده زرین کد (قالب، افزونه و سورس‌کد)</h2><p>تمامی قالب‌ها، افزونه‌های وردپرس و سورس‌کدهای نرم‌افزاری موجود در فروشگاه زرین کد حاصل کدنویسی مستقیم <strong>احسان نادری پناه</strong> بوده و دارای مستندات کامل، سورس باز، ۶ ماه پشتیبانی رایگان و آپدیت مادام‌العمر هستند.</p>',
            ],
        ];

        foreach ( $services_data as $srv ) {
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
                    '_zc_srv_discount'          => '50%',
                    '_zc_srv_delivery_mode'     => 'دورکاری و قرارداد رسمی',
                    '_zc_srv_refund_guarantee'  => 'دارد (۱۰۰٪ تضمینی)',
                    '_zc_srv_quality_guarantee' => 'دارد (استاندارد ژاکت و تم‌فارست)',
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

        foreach ( $projects as $prj ) {
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
        if ( ! post_type_exists( 'product' ) ) {
            return;
        }

        $items = [
            [
                'slug'  => 'zarincode-pro-wordpress-theme',
                'name'  => 'قالب وردپرس آژانسی و فروشگاهی زرین کد (Zarincode Pro)',
                'price' => '2490000',
                'sku'   => 'ZC-THEME-01',
                'desc'  => 'قالب وردپرس المنتور بیس با ۱۴ ویجت اختصاصی، هدر و فوترساز المنتوری، پنل تنظیمات Redux Framework و رنگ‌بندی زرد سانشاین Crextio — کدنویسی شده توسط احسان نادری پناه.',
            ],
            [
                'slug'  => 'zarinseo-booster-plugin',
                'name'  => 'افزونه سئو هوشمند و ایندکس آنی گوگل (ZarinSEO Booster)',
                'price' => '1290000',
                'sku'   => 'ZC-PLUG-01',
                'desc'  => 'افزونه اختصاصی وردپرس برای اتصال مستقیم به Google Indexing API، تولید خودکار اسکیماهای پیشرفته و آنالیز لحظه‌ای چگالی کلمات کلیدی.',
            ],
            [
                'slug'  => 'zarinyar-crm-full-source-code',
                'name'  => 'سورس‌کد سامانه جامع CRM، فاکتور و مدیریت مشتریان زرین‌یار',
                'price' => '6900000',
                'sku'   => 'ZC-SRC-01',
                'desc'  => 'سورس‌کد کامل و متن‌باز PHP/Laravel + React شامل ماژول‌های مدیریت سرنخ، صدور پیش‌فاکتور و فاکتور رسمی، تیکتینگ و اطلاع‌رسانی پیامکی.',
            ],
            [
                'slug'  => 'zarindigits-otp-auth-plugin',
                'name'  => 'افزونه ورود و ثبت‌نام پیامکی و پنل کاربری (ZarinDigits OTP)',
                'price' => '980000',
                'sku'   => 'ZC-PLUG-02',
                'desc'  => 'سیستم احراز هویت دو مرحله‌ای پیامکی (OTP) برای وردپرس و ووکامرس با پشتیبانی از ۲۰ سامانه پیامکی ایران و پنل کاربری مدرن.',
            ],
            [
                'slug'  => 'zarinmart-woocommerce-theme',
                'name'  => 'قالب فروشگاهی سوپرمارکت و کالای دیجیتال (ZarinMart)',
                'price' => '2190000',
                'sku'   => 'ZC-THEME-02',
                'desc'  => 'قالب فروشگاهی حرفه‌ای ووکامرس با فیلتر ایجکس، پیشنهاد شگفت‌انگیز تایمردار، مینی‌کارت کشویی و جدول مقایسه پیشرفته محصولات.',
            ],
            [
                'slug'  => 'zarinsocial-ai-platform-source',
                'name'  => 'سورس‌کد پلتفرم آنالیز و سئو شبکه‌های اجتماعی (ZarinSocial AI)',
                'price' => '5400000',
                'sku'   => 'ZC-SRC-02',
                'desc'  => 'نرم‌افزار تحت وب تحلیل هشتگ، زمان‌بندی انتشار پست و بهینه‌سازی سئو الگوریتمی اینستاگرام و لینکدین.',
            ],
            [
                'slug'  => 'zarincontent-ai-writer-plugin',
                'name'  => 'افزونه تولید محتوای خودکار با هوش مصنوعی (ZarinContent AI)',
                'price' => '1450000',
                'sku'   => 'ZC-PLUG-03',
                'desc'  => 'افزونه وردپرس برای تولید مقالات سئو شده فارسی با ساختار هدینگ استاندارد، جدول محتوا و متادیسکریپشن خودکار.',
            ],
            [
                'slug'  => 'zarinlms-pro-online-academy-source',
                'name'  => 'سورس‌کد سامانه آموزش آنلاین و برگزاری وبینار (ZarinLMS Pro)',
                'price' => '7800000',
                'sku'   => 'ZC-SRC-03',
                'desc'  => 'پلتفرم جامع فروش دوره آموزشی، برگزاری آزمون آنلاین، صدور گواهینامه PDF و کیف پول دیجیتال با سورس باز.',
            ],
        ];

        foreach ( $items as $it ) {
            if ( class_exists( '\WC_Product_Simple' ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
                $existing_id = wc_get_product_id_by_sku( $it['sku'] );
                $p = $existing_id ? wc_get_product( $existing_id ) : new \WC_Product_Simple();
                if ( ! $p ) {
                    $p = new \WC_Product_Simple();
                }
                $p->set_name( $it['name'] );
                $p->set_slug( $it['slug'] );
                $p->set_regular_price( $it['price'] );
                $p->set_description( $it['desc'] );
                $p->set_short_description( $it['desc'] );
                $p->set_sku( $it['sku'] );
                $p->set_virtual( true );
                $p->set_downloadable( true );
                $p->set_status( 'publish' );
                $p->save();
            } else {
                self::upsert_post_with_elementor(
                    'product',
                    $it['slug'],
                    $it['name'],
                    [],
                    $it['desc'],
                    $it['desc'],
                    [
                        '_regular_price' => $it['price'],
                        '_price'         => $it['price'],
                        '_sku'           => $it['sku'],
                        '_virtual'       => 'yes',
                        '_downloadable'  => 'yes',
                    ]
                );
            }
        }
    }

    /**
     * 5. SEED ALL 13 ELEMENTOR PAGES (HOMEPAGE, SERVICES, PORTFOLIO, SHOP, ABOUT, CONTACT, BLOG + 6 LEGAL PAGES)
     */
    public static function seed_elementor_pages(): void {
        $home_widgets = [
            [ 'widget' => 'zarincode_hero',         'sc' => '[zarincode_widget type="hero"]' ],
            [ 'widget' => 'zarincode_brands',       'sc' => '[zarincode_widget type="brands"]' ],
            [ 'widget' => 'zarincode_services',     'sc' => '[zarincode_widget type="services"]' ],
            [ 'widget' => 'zarincode_products',     'sc' => '[zarincode_widget type="products"]' ],
            [ 'widget' => 'zarincode_portfolio',    'sc' => '[zarincode_widget type="portfolio"]' ],
            [ 'widget' => 'zarincode_why_us',       'sc' => '[zarincode_widget type="why_us"]' ],
            [ 'widget' => 'zarincode_pricing',      'sc' => '[zarincode_widget type="pricing"]' ],
            [ 'widget' => 'zarincode_promo',        'sc' => '[zarincode_widget type="promo"]' ],
            [ 'widget' => 'zarincode_steps',        'sc' => '[zarincode_widget type="steps"]' ],
            [ 'widget' => 'zarincode_testimonials', 'sc' => '[zarincode_widget type="testimonials"]' ],
            [ 'widget' => 'zarincode_blog',         'sc' => '[zarincode_widget type="blog"]' ],
        ];

        $home_tree = [];
        $home_shortcodes = [];
        foreach ( $home_widgets as $hw ) {
            $home_tree[]       = self::make_elementor_section( $hw['widget'], [] );
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

        $inner_pages = [
            'services'             => [ 'title' => 'خدمات تخصصی زرین کد',                       'key' => 'services-page' ],
            'projects-portfolio'   => [ 'title' => 'نمونه‌کارها و پروژه‌های موفق زرین کد',       'key' => 'projects-page' ],
            'about-us'             => [ 'title' => 'درباره آژانس زرین کد و احسان نادری پناه',    'key' => 'about-page' ],
            'contact-us'           => [ 'title' => 'تماس با ما و ثبت سفارش (09024561001)',       'key' => 'contact-page' ],
            'terms-and-conditions' => [ 'title' => 'قوانین و شرایط و مقررات حاکم بر کشور ایران', 'key' => 'terms' ],
            'warranty-policy'      => [ 'title' => 'شرایط گارانتی و پشتیبانی فنی یک‌ساله',       'key' => 'warranty' ],
            'privacy-policy'       => [ 'title' => 'سیاست حفظ حریم خصوصی کاربران',               'key' => 'privacy' ],
            'refund-policy'        => [ 'title' => 'شرایط و ضوابط ضمانت بازگشت وجه',             'key' => 'refund' ],
            'gdpr-compliance'      => [ 'title' => 'قوانین حفاظت از داده‌های عمومی (GDPR)',      'key' => 'gdpr' ],
            'dmca-policy'          => [ 'title' => 'DMCA Copyright & Takedown Policy (English)', 'key' => 'dmca' ],
        ];

        foreach ( $inner_pages as $slug => $cfg ) {
            $tree = [ self::make_elementor_section( 'zarincode_page_section', [ 'section_key' => $cfg['key'] ] ) ];
            $sc   = '[zarincode_widget type="page_section" section="' . esc_attr( $cfg['key'] ) . '"]';
            $pid  = self::upsert_post_with_elementor( 'page', $slug, $cfg['title'], $tree, $sc );
            if ( 'privacy-policy' === $slug ) {
                update_option( 'wp_page_for_privacy_policy', $pid );
            }
        }

        // Shop Page
        $shop_tree = [
            self::make_elementor_section( 'zarincode_products', [ 'count' => 8 ] ),
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
        $blog_tree = [ self::make_elementor_section( 'zarincode_blog', [] ) ];
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
        $blog_posts = [
            [
                'slug'    => 'elementor-based-wordpress-theme-architecture',
                'title'   => 'معماری قالب‌های وردپرس المنتور بیس مطابق استاندارد ژاکت و تم‌فارست',
                'content' => '<p>در این مقاله تخصصی به قلم <strong>احسان نادری پناه (Zarincode.com - 09024561001)</strong> بررسی می‌کنیم که چگونه با تفکیک وظایف قالب و افزونه هسته (Plugin Territory)، استفاده از پنل Redux Framework و کدنویسی ویجت‌های اختصاصی المنتور می‌توان یک قالب فروشگاهی و شرکتی فوق‌حرفه‌ای با رنگ‌بندی زرد سانشاین Crextio طراحی کرد.</p>',
            ],
            [
                'slug'    => 'website-and-social-media-seo-strategy-2026',
                'title'   => 'راهنمای جامع سئو سایت و سئو شبکه‌های اجتماعی برای افزایش ۳ برابری فروش',
                'content' => '<p>ترکیب سئوی تکنیکال گوگل و سئوی الگوریتمی شبکه‌های اجتماعی (اینستاگرام، لینکدین و یوتیوب) رمز موفقیت برندهای پیشرو در سال ۲۰۲۶ است. تیم زرین کد با استراتژی داده‌محور، مسیر رسیدن به رتبه اول گوگل و اکسپلور را هموار می‌کند.</p>',
            ],
            [
                'slug'    => 'ready-made-software-source-codes-benefits',
                'title'   => 'مزایای استفاده از افزونه‌ها و سورس‌کدهای آماده استاندارد در پروژه‌های نرم‌افزاری',
                'content' => '<p>سورس‌کدها، قالب‌ها و افزونه‌های آماده کدنویسی‌شده توسط احسان نادری پناه در فروشگاه زرین کد با داشتن مستندات کامل، کدهای تمیز PHP/JS و ۶ ماه گارانتی رایگان، زمان و هزینه راه‌اندازی کسب‌وکار آنلاین شما را تا ۷۰ درصد کاهش می‌دهند.</p>',
            ],
        ];

        foreach ( $blog_posts as $bp ) {
            self::upsert_post_with_elementor(
                'post',
                $bp['slug'],
                $bp['title'],
                [],
                $bp['content'],
                wp_strip_all_tags( $bp['content'] )
            );
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
            'shop_hero_title'        => 'مارکت‌پلیس اختصاصی افزونه، قالب وردپرس و سورس‌کدهای آماده زرین کد',
            'product_warranty_badge' => 'کدنویسی اختصاصی توسط احسان نادری پناه + آپدیت مادام‌العمر و ۶ ماه پشتیبانی رایگان',
        ];
        update_option( 'zarincode_opt', $defaults );
    }

    /**
     * SEED EVERYTHING IN ONE CALL
     */
    public static function seed_all(): void {
        self::seed_redux_options();
        self::seed_header_footer_templates();
        self::seed_services_cpt();
        self::seed_projects_cpt();
        self::seed_woocommerce_products();
        self::seed_elementor_pages();
        self::seed_blog_posts();
        self::seed_navigation_menus();
        flush_rewrite_rules();
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
        ];
    }

    public function ocdi_after_import_setup(): void {
        self::seed_all();
    }
}
