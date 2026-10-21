<?php
/**
 * Enterprise Theme Settings Panel & Redux Framework Configuration (16 Sections, 120+ Options)
 *
 * Provides both:
 * 1. Full Redux Framework integration (\Redux::set_args & \Redux::set_section)
 * 2. Built-in Crextio-Styled Advanced Theme Options Control Center (works even without Redux)
 * 3. Live frontend execution of all customization options (CSS variables, Preloader, Floating Contact,
 *    Scroll Progress, Back-to-Top, GDPR Cookie Bar, Maintenance Mode, Schema JSON-LD, Performance, Custom Code).
 *
 * @package Zarincode\Theme\Redux
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Theme\Redux;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OptionsConfig {

    private static ?OptionsConfig $instance = null;
    private string $opt_name = 'zarincode_opt';

    public static function instance(): OptionsConfig {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize defaults if option does not exist yet
        add_action( 'after_setup_theme', [ $this, 'ensure_default_options' ], 5 );
        // Register Redux Framework sections if Redux plugin is active
        add_action( 'after_setup_theme', [ $this, 'register_redux_panel' ], 15 );
        // Register Native Advanced Control Center Menu + Submenus + Admin Bar
        add_action( 'admin_menu', [ $this, 'register_native_options_menu' ], 9 );
        add_action( 'admin_bar_menu', [ $this, 'register_admin_bar_nodes' ], 99 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Apply runtime frontend customizations from settings
        add_action( 'init', [ $this, 'apply_performance_optimizations' ] );
        add_action( 'template_redirect', [ $this, 'maybe_render_maintenance_mode' ] );
        add_action( 'wp_head', [ $this, 'output_dynamic_styles_and_schema' ], 99 );
        add_action( 'wp_body_open', [ $this, 'output_preloader_and_progress_bar' ], 5 );
        add_action( 'wp_footer', [ $this, 'output_footer_utilities' ], 99 );

        // WooCommerce dynamic filters
        add_filter( 'loop_shop_columns', [ $this, 'filter_shop_columns' ], 20 );
        add_filter( 'loop_shop_per_page', [ $this, 'filter_shop_per_page' ], 20 );
    }

    /**
     * Complete 16-Section Schema with 120+ Professional Settings
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_sections_schema(): array {
        return [
            'general_branding' => [
                'title' => '۱. هویت برند، لوگو و عمومی',
                'icon'  => 'dashicons-admin-home',
                'redux' => 'el el-home',
                'desc'  => 'مدیریت نام برند، مشخصات توسعه‌دهنده، لوگو، ابعاد کانتینر، پریلودر و تنظیمات پایه قالب زرین کد.',
                'fields' => [
                    [ 'id' => 'brand_name',            'type' => 'text',   'title' => 'نام برند فارسی', 'subtitle' => 'نمایش در هدر، فوتر و عنوان‌های اصلی', 'default' => 'زرین کد' ],
                    [ 'id' => 'brand_en_name',         'type' => 'text',   'title' => 'نام برند انگلیسی / زیرعنوان لوگو', 'default' => 'Zarincode Studio' ],
                    [ 'id' => 'brand_slogan',          'type' => 'text',   'title' => 'شعار رسمی برند', 'default' => 'آژانس مهندسی نرم‌افزار، طراحی سایت، سئو و مارکت‌پلیس محصولات آماده' ],
                    [ 'id' => 'developer_name',        'type' => 'text',   'title' => 'نام توسعه‌دهنده و مدیر فنی', 'default' => 'احسان نادری پناه (Ehsan NaderiPanah)' ],
                    [ 'id' => 'official_website',      'type' => 'text',   'title' => 'آدرس وب‌سایت رسمی برند', 'default' => 'https://zarincode.com' ],
                    [ 'id' => 'logo_monogram',         'type' => 'text',   'title' => 'حرف مونوگرام دایره‌ای لوگو', 'subtitle' => 'در صورت عدم انتخاب تصویر لوگو نمایش داده می‌شود', 'default' => 'Z' ],
                    [ 'id' => 'logo_light',            'type' => 'media',  'title' => 'تصویر لوگو (حالت روشن)', 'subtitle' => 'فرمت PNG یا SVG شفاف پیشنهاد می‌شود', 'default' => '' ],
                    [ 'id' => 'logo_dark',             'type' => 'media',  'title' => 'تصویر لوگو (حالت تیره / فوتر)', 'default' => '' ],
                    [ 'id' => 'favicon_url',           'type' => 'media',  'title' => 'فاوآیکون اختصاصی سایت (Favicon)', 'default' => '' ],
                    [ 'id' => 'logo_width',            'type' => 'number', 'title' => 'حداکثر عرض لوگو (پیکسل)', 'default' => 155 ],
                    [ 'id' => 'container_max_width',   'type' => 'select', 'title' => 'حداکثر عرض کانتینر اصلی صفحات', 'options' => [ '1280px' => '۱۲۸۰ پیکسل (کلاسیک)', '1360px' => '۱۳۶۰ پیکسل (استاندارد Crextio)', '1440px' => '۱۴۴۰ پیکسل (عریض مدرن)', '1600px' => '۱۶۰۰ پیکسل (فول‌ویدث استودیویی)' ], 'default' => '1360px' ],
                    [ 'id' => 'bento_border_radius',   'type' => 'select', 'title' => 'میزان گردی گوشه‌های کارت‌های بنتو (Border Radius)', 'options' => [ '16px' => '۱۶ پیکسل (ملایم)', '24px' => '۲۴ پیکسل (مدرن)', '28px' => '۲۸ پیکسل (استاندارد Crextio)', '36px' => '۳۶ پیکسل (کپسولی فوق‌گرد)' ], 'default' => '28px' ],
                    [ 'id' => 'enable_preloader',      'type' => 'switch', 'title' => 'فعال‌سازی پیش‌بارگذار صفحه (Preloader)', 'default' => false ],
                    [ 'id' => 'preloader_text',        'type' => 'text',   'title' => 'متن نمایشی پیش‌بارگذار', 'default' => 'زرین کد | Zarincode.com' ],
                    [ 'id' => 'enable_scroll_progress','type' => 'switch', 'title' => 'نوار پیشرفت اسکرول در بالای صفحه (Scroll Progress Bar)', 'default' => true ],
                    [ 'id' => 'enable_back_to_top',    'type' => 'switch', 'title' => 'دکمه شناور بازگشت به بالای صفحه (Back to Top)', 'default' => true ],
                ],
            ],

            'crextio_colors' => [
                'title' => '۲. رنگ‌بندی زرد سانشاین و پالت Crextio',
                'icon'  => 'dashicons-art',
                'redux' => 'el el-brush',
                'desc'  => 'شخصی‌سازی کامل رنگ زرد سانشاین Crextio (#FDD05B)، کارت‌های تیره بنتو (#2A2C31)، گرادینت پس‌زمینه، شفافیت پنل‌های شیشه‌ای و حالت شب/روز.',
                'fields' => [
                    [ 'id' => 'default_color_preset',  'type' => 'select', 'title' => 'پالت رنگی پیش‌فرض قالب (Crextio Presets)', 'options' => [
                        'sunshine' => 'Sunshine Yellow — زرد سانشاین اصلی Crextio (#FDD05B)',
                        'emerald'  => 'Emerald Mint — سبز زمردی فین‌تک (#34D399)',
                        'royal'    => 'Royal Sky — آبی رویال استارتاپی (#60A5FA)',
                        'sunset'   => 'Warm Coral — نارنجی مرجانی خلاقانه (#FB923C)',
                        'amethyst' => 'Amethyst — بنفش نئونی هوش مصنوعی (#C084FC)',
                    ], 'default' => 'sunshine' ],
                    [ 'id' => 'accent_color',          'type' => 'color',  'title' => 'رنگ اصلی برند (Primary Accent Color)', 'subtitle' => 'کد استاندارد زرد سانشاین Crextio: #FDD05B', 'default' => '#FDD05B' ],
                    [ 'id' => 'accent_hover_color',    'type' => 'color',  'title' => 'رنگ هاور دکمه‌های اصلی', 'default' => '#F5C038' ],
                    [ 'id' => 'accent_contrast_color', 'type' => 'color',  'title' => 'رنگ متن روی دکمه‌های زرد (Contrast Text)', 'default' => '#2A2C31' ],
                    [ 'id' => 'dark_surface_color',    'type' => 'color',  'title' => 'رنگ کارت‌های تیره بنتو (Crextio Dark Surface)', 'subtitle' => 'کد استاندارد: #2A2C31', 'default' => '#2A2C31' ],
                    [ 'id' => 'bg_gradient_from',      'type' => 'color',  'title' => 'شروع گرادینت پس‌زمینه حالت روشن (Warm Cream)', 'default' => '#F8F6F0' ],
                    [ 'id' => 'bg_gradient_to',        'type' => 'color',  'title' => 'پایان گرادینت پس‌زمینه حالت روشن (Sunshine Tint)', 'default' => '#FFF5D1' ],
                    [ 'id' => 'dark_bg_from',          'type' => 'color',  'title' => 'شروع گرادینت پس‌زمینه حالت تیره (Dark Mode)', 'default' => '#15171C' ],
                    [ 'id' => 'dark_bg_to',            'type' => 'color',  'title' => 'پایان گرادینت پس‌زمینه حالت تیره (Dark Mode)', 'default' => '#1F1B14' ],
                    [ 'id' => 'default_theme_mode',    'type' => 'select', 'title' => 'حالت نمایش پیش‌فرض سایت برای کاربران جدید', 'options' => [ 'light' => 'حالت روشن گرم (Crextio Warm Light)', 'dark' => 'حالت تیره بنتو (Crextio Dark Mode)' ], 'default' => 'light' ],
                    [ 'id' => 'glass_blur_px',         'type' => 'number', 'title' => 'شدت ماتی پنل‌های شیشه‌ای (Backdrop Blur بر حسب پیکسل)', 'default' => 14 ],
                    [ 'id' => 'enable_color_switcher', 'type' => 'switch', 'title' => 'نمایش ابزارک شناور تغییر رنگ و حالت شب/روز در فرانت‌اند', 'default' => true ],
                ],
            ],

            'typography_settings' => [
                'title' => '۳. تایپوگرافی و فونت وزیرمتن',
                'icon'  => 'dashicons-editor-spellcheck',
                'redux' => 'el el-font',
                'desc'  => 'تنظیمات فونت فارسی وزیرمتن (Vazirmatn)، سایز و وزن تیترها، فاصله خطوط و تبدیل خودکار اعداد به فارسی.',
                'fields' => [
                    [ 'id' => 'primary_font',          'type' => 'select', 'title' => 'فونت اصلی متون و رابط کاربری', 'options' => [ 'Vazirmatn' => 'فونت وزیرمتن (Vazirmatn — نسخه کامل WOFF2 داخلی)' ], 'default' => 'Vazirmatn' ],
                    [ 'id' => 'heading_font',          'type' => 'select', 'title' => 'فونت تیترها و عناوین (H1 تا H6)', 'options' => [ 'Vazirmatn' => 'فونت وزیرمتن ضخیم (Vazirmatn ExtraBold/Black)' ], 'default' => 'Vazirmatn' ],
                    [ 'id' => 'body_font_size',        'type' => 'number', 'title' => 'اندازه فونت پایه متون سایت (پیکسل)', 'default' => 15 ],
                    [ 'id' => 'body_line_height',      'type' => 'text',   'title' => 'ارتفاع خط متون (Line Height)', 'default' => '1.9' ],
                    [ 'id' => 'heading_weight',        'type' => 'select', 'title' => 'وزن فونت تیترهای اصلی', 'options' => [ '700' => '۷۰۰ (Bold)', '800' => '۸۰۰ (ExtraBold)', '900' => '۹۰۰ (Black — استاندارد زرین کد)' ], 'default' => '900' ],
                    [ 'id' => 'persian_digits',        'type' => 'switch', 'title' => 'تبدیل خودکار اعداد انگلیسی به فارسی در قیمت‌ها و آمارها', 'default' => true ],
                ],
            ],

            'topbar_settings' => [
                'title' => '۴. نوار بالایی هدر (Topbar)',
                'icon'  => 'dashicons-megaphone',
                'redux' => 'el el-bullhorn',
                'desc'  => 'مدیریت نوار اعلان بالای سایت، نمایش نام توسعه‌دهنده، شماره تماس مستقیم و لینک‌های سریع صفحات قوانین.',
                'fields' => [
                    [ 'id' => 'enable_topbar',            'type' => 'switch', 'title' => 'نمایش نوار بالایی هدر (Topbar)', 'default' => true ],
                    [ 'id' => 'topbar_show_dev',          'type' => 'switch', 'title' => 'نمایش نام توسعه‌دهنده در نوار بالایی', 'default' => true ],
                    [ 'id' => 'topbar_show_phone',        'type' => 'switch', 'title' => 'نمایش شماره تماس مستقیم در نوار بالایی', 'default' => true ],
                    [ 'id' => 'topbar_show_legal_links',  'type' => 'switch', 'title' => 'نمایش لینک‌های سریع قوانین (قوانین ایران، گارانتی، بازگشت وجه، GDPR، DMCA)', 'default' => true ],
                    [ 'id' => 'topbar_custom_badge',      'type' => 'text',   'title' => 'متن اعلان سفارشی در نوار بالایی (اختیاری)', 'default' => '' ],
                    [ 'id' => 'topbar_bg_color',          'type' => 'color',  'title' => 'رنگ پس‌زمینه نوار بالایی', 'default' => '#2A2C31' ],
                ],
            ],

            'header_navigation' => [
                'title' => '۵. هدر کپسولی، مگامنو و ناوبری',
                'icon'  => 'dashicons-menu-alt3',
                'redux' => 'el el-lines',
                'desc'  => 'انتخاب هدر پیش‌فرض یا هدر المنتوری، تنظیمات چسبان بودن هدر، مگامنو، دکمه جستجو، سبد خرید و دکمه مشاوره.',
                'fields' => [
                    [ 'id' => 'header_source',            'type' => 'select', 'title' => 'منبع رندر هدر سایت', 'options' => [ 'default_pill' => 'هدر کپسولی استاندارد زرین کد (Crextio Pill Header)', 'elementor_hf' => 'استفاده از قالب هدر ساخته شده در المنتور (zc_hf_template)' ], 'default' => 'default_pill' ],
                    [ 'id' => 'enable_sticky_header',     'type' => 'switch', 'title' => 'چسبان بودن هدر هنگام اسکرول (Sticky Header)', 'default' => true ],
                    [ 'id' => 'header_show_search',       'type' => 'switch', 'title' => 'نمایش دکمه مودال جستجوی سریع (Ctrl + K)', 'default' => true ],
                    [ 'id' => 'header_show_dark_toggle',  'type' => 'switch', 'title' => 'نمایش دکمه تغییر حالت روشن / تیره در هدر', 'default' => true ],
                    [ 'id' => 'header_show_cart',         'type' => 'switch', 'title' => 'نمایش آیکون سبد خرید و شمارنده محصولات در هدر', 'default' => true ],
                    [ 'id' => 'header_show_cta',          'type' => 'switch', 'title' => 'نمایش دکمه زرد فراخوان مشاوره در هدر', 'default' => true ],
                    [ 'id' => 'header_cta_text',          'type' => 'text',   'title' => 'متن دکمه فراخوان هدر', 'default' => 'درخواست مشاوره' ],
                    [ 'id' => 'header_cta_action',        'type' => 'select', 'title' => 'عملکرد دکمه فراخوان هدر', 'options' => [ 'modal' => 'باز کردن پاپ‌آپ درخواست مشاوره رایگان', 'link' => 'انتقال به لینک سفارشی' ], 'default' => 'modal' ],
                    [ 'id' => 'header_cta_custom_url',    'type' => 'text',   'title' => 'لینک سفارشی دکمه هدر (در صورت انتخاب حالت لینک)', 'default' => '/contact-us/' ],
                ],
            ],

            'hero_homepage' => [
                'title' => '۶. هیرو سکشن و صفحه اصلی',
                'icon'  => 'dashicons-welcome-view-site',
                'redux' => 'el el-screen',
                'desc'  => 'مدیریت متون، بج‌ها، تیکر چرخشی، دکمه‌های فراخوان و آمارهای کارت بنتو در هیرو سکشن صفحه اصلی.',
                'fields' => [
                    [ 'id' => 'hero_badge',               'type' => 'text',     'title' => 'بج کپسولی بالای هیرو سکشن', 'default' => 'ZARINCODE DIGITAL AGENCY & SOFTWARE STUDIO' ],
                    [ 'id' => 'hero_title_part1',         'type' => 'text',     'title' => 'بخش اول تیتر اصلی هیرو', 'default' => 'مهندسی وب‌سایت، نرم‌افزار و' ],
                    [ 'id' => 'hero_title_highlight',     'type' => 'text',     'title' => 'بخش هایلایت زرد تیتر هیرو', 'default' => 'رشد تضمینی در گوگل و شبکه‌های اجتماعی' ],
                    [ 'id' => 'hero_subtitle',            'type' => 'textarea', 'title' => 'توضیحات زیرعنوان هیرو سکشن', 'default' => 'استودیو زرین کد با مدیریت احسان نادری پناه؛ ترکیب کدنویسی استاندارد جهانی، طراحی رابط کاربری به سبک Crextio، سئو تخصصی سایت و شبکه‌های اجتماعی و فروشگاه محصولات آماده.' ],
                    [ 'id' => 'hero_btn_primary_text',    'type' => 'text',     'title' => 'متن دکمه اول هیرو (زرد سانشاین)', 'default' => 'مشاوره رایگان و ثبت سفارش پروژه' ],
                    [ 'id' => 'hero_btn_secondary_text',  'type' => 'text',     'title' => 'متن دکمه دوم هیرو (تیره)', 'default' => 'ورود به فروشگاه سورس و افزونه' ],
                    [ 'id' => 'hero_stat_projects',       'type' => 'text',     'title' => 'عدد آمار پروژه‌های موفق در هیرو', 'default' => '+۴۸۰' ],
                    [ 'id' => 'hero_stat_satisfaction',   'type' => 'text',     'title' => 'درصد رضایت کارفرمایان در هیرو', 'default' => '۹۹.۸٪' ],
                ],
            ],

            'services_department' => [
                'title' => '۷. دپارتمان ۶ خدمت تخصصی و SLA',
                'icon'  => 'dashicons-awards',
                'redux' => 'el el-briefcase',
                'desc'  => 'تنظیمات نمایش ۶ خدمت اصلی (طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت، سئو شبکه اجتماعی، تولید محتوا، محصولات آماده) و جدول ۱۲ شرط انجام پروژه.',
                'fields' => [
                    [ 'id' => 'services_sec_badge',          'type' => 'text',   'title' => 'بج بالای بخش خدمات', 'default' => 'ZARINCODE 6 CORE DEPARTMENTS' ],
                    [ 'id' => 'services_sec_title',          'type' => 'text',   'title' => 'عنوان بخش خدمات تخصصی', 'default' => '۶ دپارتمان تخصصی زرین کد برای رشد ۳۶۰ درجه کسب‌وکار شما' ],
                    [ 'id' => 'services_grid_columns',       'type' => 'select', 'title' => 'تعداد ستون کارت‌های خدمات در دسکتاپ', 'options' => [ '3' => '۳ ستونه (استاندارد بنتو)', '2' => '۲ ستونه (عریض)' ], 'default' => '3' ],
                    [ 'id' => 'service_single_show_sla',     'type' => 'switch', 'title' => 'نمایش جدول ۱۲گانه شرایط انجام پروژه در تک‌صفحه هر خدمت', 'default' => true ],
                    [ 'id' => 'service_single_show_form',    'type' => 'switch', 'title' => 'نمایش فرم ثبت سفارش سریع در سایدبار تک‌صفحه خدمت', 'default' => true ],
                    [ 'id' => 'service_single_show_related', 'type' => 'switch', 'title' => 'نمایش سایر خدمات مرتبط در پایین تک‌صفحه خدمت', 'default' => true ],
                    [ 'id' => 'service_default_prepayment',  'type' => 'text',   'title' => 'درصد پیش‌پرداخت پیش‌فرض پروژه‌ها', 'default' => '۵۰٪ پیش‌پرداخت و ۵۰٪ پس از تحویل' ],
                    [ 'id' => 'service_default_warranty',    'type' => 'text',   'title' => 'مدت گارانتی و پشتیبانی فنی رایگان پروژه‌ها', 'default' => '۱۲ ماه گارانتی و پشتیبانی فنی رایگان' ],
                ],
            ],

            'portfolio_projects' => [
                'title' => '۸. نمونه‌کارها و پروژه‌ها',
                'icon'  => 'dashicons-portfolio',
                'redux' => 'el el-picture',
                'desc'  => 'مدیریت بخش نمونه‌کارها (Portfolio CPT)، تب‌های فیلتر دسته‌بندی و جزئیات نمایشی هر پروژه.',
                'fields' => [
                    [ 'id' => 'portfolio_sec_title',      'type' => 'text',   'title' => 'عنوان بخش نمونه‌کارها', 'default' => 'چشم‌اندازی از پروژه‌ها و نمونه‌کارهای موفق زرین کد' ],
                    [ 'id' => 'portfolio_enable_filter',  'type' => 'switch', 'title' => 'فعال‌سازی تب‌های فیلتر دسته‌بندی نمونه‌کارها بدون رفرش صفحه', 'default' => true ],
                    [ 'id' => 'portfolio_per_page',       'type' => 'number', 'title' => 'تعداد نمونه‌کارهای نمایشی در صفحه اصلی', 'default' => 6 ],
                    [ 'id' => 'portfolio_show_client',    'type' => 'switch', 'title' => 'نمایش نام کارفرما و مدت اجرای پروژه روی کارت‌ها', 'default' => true ],
                    [ 'id' => 'portfolio_show_metrics',   'type' => 'switch', 'title' => 'نمایش بج دستاورد و آمار رشد پروژه روی کارت‌ها', 'default' => true ],
                ],
            ],

            'pricing_plans' => [
                'title' => '۹. جداول تعرفه و پلن‌های قیمت‌گذاری',
                'icon'  => 'dashicons-money-alt',
                'redux' => 'el el-usd',
                'desc'  => 'مدیریت جداول تعرفه تب‌دار (طراحی سایت، سئو، نرم‌افزار، تولید محتوا)، بج‌های تخفیف و مودال مقایسه امکانات.',
                'fields' => [
                    [ 'id' => 'pricing_sec_title',        'type' => 'text',   'title' => 'عنوان بخش تعرفه‌ها', 'default' => 'تعرفه شفاف خدمات طراحی سایت، سئو، نرم‌افزار و تولید محتوا' ],
                    [ 'id' => 'pricing_discount_badge',   'type' => 'text',   'title' => 'متن بج تخفیف جشنواره روی پلن‌های ویژه', 'default' => '۵۰٪ تخفیف جشنواره زرین کد' ],
                    [ 'id' => 'pricing_enable_modal',     'type' => 'switch', 'title' => 'فعال‌سازی دکمه پاپ‌آپ «مشاهده تمام امکانات پلن» (مشابه Webinex)', 'default' => true ],
                    [ 'id' => 'pricing_currency_label',   'type' => 'text',   'title' => 'واحد پول نمایشی در جداول تعرفه', 'default' => 'میلیون تومان' ],
                ],
            ],

            'woocommerce_store' => [
                'title' => '۱۰. فروشگاه محصولات آماده (ووکامرس)',
                'icon'  => 'dashicons-cart',
                'redux' => 'el el-shopping-cart',
                'desc'  => 'تنظیمات مارکت‌پلیس فروش قالب‌های وردپرس، افزونه‌ها و سورس‌کدهای نرم‌افزار، تعداد ستون‌ها و بج‌های ضمانت.',
                'fields' => [
                    [ 'id' => 'shop_hero_title',          'type' => 'text',   'title' => 'عنوان هدر فروشگاه محصولات آماده', 'default' => 'مارکت‌پلیس اختصاصی افزونه، قالب وردپرس و سورس‌کدهای آماده زرین کد' ],
                    [ 'id' => 'shop_hero_subtitle',       'type' => 'textarea', 'title' => 'زیرعنوان فروشگاه محصولات آماده', 'default' => 'تمامی قالب‌ها، افزونه‌ها و سورس‌کدهای موجود در فروشگاه زرین کد حاصل کدنویسی مستقیم احسان نادری پناه بوده و دارای سورس باز، مستندات فارسی و آپدیت مادام‌العمر هستند.' ],
                    [ 'id' => 'product_warranty_badge',   'type' => 'text',   'title' => 'متن بج ضمانت اصالت و پشتیبانی محصولات', 'default' => 'کدنویسی اختصاصی توسط احسان نادری پناه + آپدیت مادام‌العمر و ۶ ماه پشتیبانی رایگان' ],
                    [ 'id' => 'shop_columns',             'type' => 'select', 'title' => 'تعداد ستون محصولات در صفحه فروشگاه', 'options' => [ '3' => '۳ ستون در هر ردیف', '4' => '۴ ستون در هر ردیف (استاندارد مارکت‌پلیس)' ], 'default' => '4' ],
                    [ 'id' => 'shop_per_page',            'type' => 'number', 'title' => 'تعداد محصولات در هر صفحه فروشگاه', 'default' => 8 ],
                    [ 'id' => 'shop_show_instant_badge',  'type' => 'switch', 'title' => 'نمایش بج «تحویل و دانلود آنی پس از پرداخت» روی کارت محصولات', 'default' => true ],
                    [ 'id' => 'shop_show_source_badge',   'type' => 'switch', 'title' => 'نمایش بج «سورس باز و بدون قفل (Open Source)»', 'default' => true ],
                ],
            ],

            'blog_settings' => [
                'title' => '۱۱. وبلاگ و مقالات آموزشی',
                'icon'  => 'dashicons-welcome-write-blog',
                'redux' => 'el el-pencil',
                'desc'  => 'مدیریت چیدمان آرشیو وبلاگ، کارت‌های مقالات، زمان مطالعه و باکس نویسنده در تک‌نوشته.',
                'fields' => [
                    [ 'id' => 'blog_sec_title',           'type' => 'text',   'title' => 'عنوان بخش آخرین مقالات وبلاگ', 'default' => 'آخرین مقالات تخصصی آموزش طراحی سایت، سئو و مهندسی نرم‌افزار' ],
                    [ 'id' => 'blog_grid_columns',        'type' => 'select', 'title' => 'چیدمان کارت‌های مقالات', 'options' => [ '3' => 'گرید ۳ ستونه بنتو', '2' => 'گرید ۲ ستونه' ], 'default' => '3' ],
                    [ 'id' => 'blog_show_reading_time',   'type' => 'switch', 'title' => 'نمایش زمان تقریبی مطالعه مقاله', 'default' => true ],
                    [ 'id' => 'blog_show_author_box',     'type' => 'switch', 'title' => 'نمایش باکس معرفی نویسنده (احسان نادری پناه) در پایان مقاله', 'default' => true ],
                ],
            ],

            'footer_bento' => [
                'title' => '۱۲. فوتر بنتو دارک، نمادها و کپی‌رایت',
                'icon'  => 'dashicons-table-row-after',
                'redux' => 'el el-website',
                'desc'  => 'شخصی‌سازی فوتر بنتو تیره Crextio، بنر فراخوان بالای فوتر، کد ای‌نماد و ساماندهی و متن کپی‌رایت.',
                'fields' => [
                    [ 'id' => 'footer_source',            'type' => 'select',   'title' => 'منبع رندر فوتر سایت', 'options' => [ 'default_bento' => 'فوتر بنتو دارک استاندارد زرین کد (Crextio Dark Bento)', 'elementor_hf' => 'استفاده از قالب فوتر ساخته شده در المنتور (zc_hf_template)' ], 'default' => 'default_bento' ],
                    [ 'id' => 'footer_show_cta_strip',    'type' => 'switch',   'title' => 'نمایش نوار فراخوان عمل (CTA Strip) در بالای فوتر', 'default' => true ],
                    [ 'id' => 'footer_cta_heading',       'type' => 'text',     'title' => 'عنوان نوار فراخوان بالای فوتر', 'default' => 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟' ],
                    [ 'id' => 'footer_about_text',        'type' => 'textarea', 'title' => 'متن معرفی آژانس زرین کد در ستون اول فوتر', 'default' => 'زرین کد با مدیریت و کدنویسی مستقیم احسان نادری پناه، ارائه‌دهنده خدمات فوق‌تخصصی طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت و شبکه‌های اجتماعی، تولید محتوا و مارکت‌پلیس افزونه‌ها، قالب‌های وردپرس و سورس‌کدهای آماده تجاری است.' ],
                    [ 'id' => 'footer_enamad_html',       'type' => 'textarea', 'title' => 'کد HTML نماد اعتماد الکترونیکی (ای‌نماد)', 'subtitle' => 'در صورت خالی بودن، نشان گرافیکی پیش‌فرض نمایش داده می‌شود', 'default' => '' ],
                    [ 'id' => 'footer_samandehi_html',    'type' => 'textarea', 'title' => 'کد HTML نشان ملی ثبت (ساماندهی)', 'default' => '' ],
                    [ 'id' => 'footer_copyright_text',    'type' => 'textarea', 'title' => 'متن کپی‌رایت پایین فوتر', 'default' => 'تمامی حقوق مادی و معنوی این قالب و محصولات متعلق به زرین کد (Zarincode.com) و احسان نادری پناه (Ehsan NaderiPanah) می‌باشد.' ],
                ],
            ],

            'contact_socials' => [
                'title' => '۱۳. اطلاعات تماس، شبکه‌های اجتماعی و دکمه شناور',
                'icon'  => 'dashicons-phone',
                'redux' => 'el el-phone',
                'desc'  => 'مدیریت شماره تماس، آدرس، لینک شبکه‌های اجتماعی و دکمه شناور تماس سریع / مشاوره در گوشه پایین صفحه.',
                'fields' => [
                    [ 'id' => 'contact_phone',            'type' => 'text',   'title' => 'شماره تماس مستقیم و پشتیبانی', 'default' => '09024561001' ],
                    [ 'id' => 'contact_email',            'type' => 'text',   'title' => 'ایمیل رسمی پشتیبانی و فروش', 'default' => 'info@zarincode.com' ],
                    [ 'id' => 'contact_address',          'type' => 'text',   'title' => 'آدرس دفتر مرکزی', 'default' => 'تهران، مرکز نوآوری و مهندسی نرم‌افزار زرین کد (پشتیبانی آنلاین و تلفنی ۷ روز هفته)' ],
                    [ 'id' => 'contact_working_hours',    'type' => 'text',   'title' => 'ساعات پاسخگویی و پشتیبانی', 'default' => 'شنبه تا جمعه — ۹ صبح الی ۱۱ شب (پشتیبانی تیکت ۲۴ ساعته)' ],
                    [ 'id' => 'social_github',            'type' => 'text',   'title' => 'آدرس گیت‌هاب رسمی (GitHub)', 'default' => 'https://github.com/Ehsan-Np' ],
                    [ 'id' => 'social_telegram',          'type' => 'text',   'title' => 'آدرس تلگرام پشتیبانی', 'default' => 'https://t.me/zarincode' ],
                    [ 'id' => 'social_whatsapp',          'type' => 'text',   'title' => 'لینک واتساپ ارتباط مستقیم', 'default' => 'https://wa.me/989024561001' ],
                    [ 'id' => 'social_instagram',         'type' => 'text',   'title' => 'آدرس پیج اینستاگرام', 'default' => 'https://instagram.com/zarincode' ],
                    [ 'id' => 'social_linkedin',          'type' => 'text',   'title' => 'آدرس لینکدین رسمی', 'default' => 'https://linkedin.com/company/zarincode' ],
                    [ 'id' => 'enable_floating_contact',  'type' => 'switch', 'title' => 'نمایش دکمه شناور مشاوره و تماس مستقیم (09024561001) در پایین صفحات', 'default' => true ],
                    [ 'id' => 'floating_contact_label',   'type' => 'text',   'title' => 'متن دکمه شناور مشاوره سریع', 'default' => 'مشاوره فوری: 09024561001' ],
                ],
            ],

            'modals_forms' => [
                'title' => '۱۴. پاپ‌آپ مشاوره و فرم‌های ثبت سفارش',
                'icon'  => 'dashicons-email-alt',
                'redux' => 'el el-envelope',
                'desc'  => 'تنظیمات مودال درخواست مشاوره رایگان، پیام‌های تایید فرم و ایمیل دریافت‌کننده سفارش‌ها.',
                'fields' => [
                    [ 'id' => 'consult_modal_title',      'type' => 'text',     'title' => 'عنوان مودال درخواست مشاوره رایگان', 'default' => 'درخواست مشاوره رایگان و ثبت سفارش پروژه' ],
                    [ 'id' => 'consult_modal_desc',       'type' => 'textarea', 'title' => 'توضیحات بالای فرم پاپ‌آپ مشاوره', 'default' => 'فرم زیر را تکمیل کنید یا مستقیماً با شماره 09024561001 (احسان نادری پناه) تماس بگیرید. کارشناسان ما در کمتر از ۱۵ دقیقه با شما تماس خواهند گرفت.' ],
                    [ 'id' => 'forms_recipient_email',    'type' => 'text',     'title' => 'ایمیل دریافت‌کننده درخواست‌های مشاوره و سفارش', 'default' => 'info@zarincode.com' ],
                    [ 'id' => 'forms_success_message',    'type' => 'text',     'title' => 'پیام موفقیت پس از ارسال فرم توسط کاربر', 'default' => 'درخواست شما با موفقیت ثبت شد! احسان نادری پناه و تیم زرین کد به زودی با شما تماس می‌گیرند.' ],
                ],
            ],

            'legal_compliance' => [
                'title' => '۱۵. قوانین ایران، گارانتی، GDPR و DMCA',
                'icon'  => 'dashicons-shield-alt',
                'redux' => 'el el-lock',
                'desc'  => 'مدیریت متغیرهای حقوقی در ۶ برگه قانونی (قوانین تجارت الکترونیک ایران، گارانتی، بازگشت وجه، حریم خصوصی، GDPR و DMCA انگلیسی) و نوار کوکی.',
                'fields' => [
                    [ 'id' => 'legal_dpo_name',           'type' => 'text',   'title' => 'نام مدیر مسئول، نماینده حقوقی DMCA و افسر حفاظت داده (DPO)', 'default' => 'احسان نادری پناه (Ehsan NaderiPanah)' ],
                    [ 'id' => 'legal_phone',              'type' => 'text',   'title' => 'شماره تماس واحد حقوقی و قراردادها', 'default' => '09024561001' ],
                    [ 'id' => 'legal_jurisdiction',       'type' => 'text',   'title' => 'مرجع قانونی حل اختلاف قراردادها', 'default' => 'قوانین تجارت الکترونیک و جرایم رایانه‌ای جمهوری اسلامی ایران و شورای انتظامی نظام صنفی رایانه‌ای' ],
                    [ 'id' => 'legal_warranty_days',      'type' => 'text',   'title' => 'مدت گارانتی رسمی پروژه‌ها و محصولات', 'default' => '۳۶۵ روز گارانتی پروژه‌های اختصاصی و ۶ ماه پشتیبانی رایگان محصولات آماده' ],
                    [ 'id' => 'enable_gdpr_cookie_bar',   'type' => 'switch', 'title' => 'نمایش نوار اطلاع‌رسانی کوکی و حریم خصوصی (GDPR Consent Bar)', 'default' => false ],
                    [ 'id' => 'gdpr_cookie_text',         'type' => 'text',   'title' => 'متن نوار کوکی و حریم خصوصی', 'default' => 'وب‌سایت زرین کد برای ارائه تجربه کاربری بهتر و مطابق قوانین حریم خصوصی و GDPR از کوکی‌ها استفاده می‌کند.' ],
                ],
            ],

            'performance_advanced' => [
                'title' => '۱۶. بهینه‌سازی سرعت، سئو، حالت تعمیر و کدهای سفارشی',
                'icon'  => 'dashicons-performance',
                'redux' => 'el el-cogs',
                'desc'  => 'تنظیمات اسکیما JSON-LD، بهینه‌سازی سرعت وردپرس، حالت تعمیر (Maintenance Mode) و درج کدهای سفارشی CSS و JS.',
                'fields' => [
                    [ 'id' => 'enable_schema_markup',     'type' => 'switch',   'title' => 'تولید خودکار اسکیما JSON-LD سازمان و خدمات برای گوگل', 'default' => true ],
                    [ 'id' => 'optimize_wp_head',         'type' => 'switch',   'title' => 'حذف اسکریپت‌های ایموجی و متاتگ‌های اضافی وردپرس جهت افزایش سرعت', 'default' => true ],
                    [ 'id' => 'enable_maintenance_mode',  'type' => 'switch',   'title' => 'فعال‌سازی حالت بروزرسانی / به زودی (Maintenance Mode برای کاربران مهمان)', 'default' => false ],
                    [ 'id' => 'maintenance_title',        'type' => 'text',     'title' => 'عنوان صفحه حالت بروزرسانی', 'default' => 'وب‌سایت زرین کد در حال بروزرسانی است' ],
                    [ 'id' => 'maintenance_message',      'type' => 'textarea', 'title' => 'توضیحات صفحه حالت بروزرسانی', 'default' => 'در حال ارتقای زیرساخت و افزودن امکانات جدید هستیم. برای سفارش فوری با شماره 09024561001 (احسان نادری پناه) تماس بگیرید.' ],
                    [ 'id' => 'custom_css',               'type' => 'code',     'title' => 'کدهای CSS سفارشی (Custom CSS)', 'subtitle' => 'در انتهای wp_head بارگذاری می‌شود', 'default' => '' ],
                    [ 'id' => 'custom_head_js',           'type' => 'code',     'title' => 'کدهای اسکریپت یا متاتگ در هدر (قبل از </head>)', 'default' => '' ],
                    [ 'id' => 'custom_footer_js',         'type' => 'code',     'title' => 'کدهای جاوااسکریپت یا آنالیتیکس در فوتر (قبل از </body>)', 'default' => '' ],
                ],
            ],
        ];
    }

    /**
     * Get flat associative array of all default values across all 16 sections
     *
     * @return array<string, mixed>
     */
    public static function get_all_defaults(): array {
        $defaults = [];
        foreach ( self::get_sections_schema() as $section ) {
            foreach ( $section['fields'] as $field ) {
                $defaults[ $field['id'] ] = $field['default'];
            }
        }
        return $defaults;
    }

    /**
     * Ensure zarincode_opt is initialized with all default values
     */
    public function ensure_default_options(): void {
        $current  = get_option( $this->opt_name, [] );
        $defaults = self::get_all_defaults();
        if ( empty( $current ) || ! is_array( $current ) ) {
            update_option( $this->opt_name, $defaults );
        } else {
            $merged = array_merge( $defaults, $current );
            if ( count( $merged ) !== count( $current ) ) {
                update_option( $this->opt_name, $merged );
            }
        }
    }

    /**
     * Register all 16 sections with Redux Framework when Redux is active
     */
    public function register_redux_panel(): void {
        if ( ! class_exists( '\Redux' ) ) {
            return;
        }

        $args = [
            'opt_name'           => $this->opt_name,
            'display_name'       => 'پنل تنظیمات پیشرفته قالب زرین کد (Zarincode Pro)',
            'display_version'    => ZARINCODE_VERSION . ' — توسعه‌دهنده: احسان نادری پناه (09024561001)',
            'menu_type'          => 'submenu',
            'page_parent'        => 'zarincode-theme-options',
            'allow_sub_menu'     => true,
            'menu_title'         => 'تنظیمات با موتور Redux',
            'page_title'         => 'تنظیمات قالب زرین کد (موتور Redux Framework)',
            'admin_bar'          => false,
            'page_priority'      => 59,
            'save_defaults'      => true,
            'show_import_export' => true,
            'footer_credit'      => 'طراحی و کدنویسی توسط احسان نادری پناه (Ehsan NaderiPanah) | شماره تماس: 09024561001 | وب‌سایت: Zarincode.com',
        ];

        \Redux::set_args( $this->opt_name, $args );

        foreach ( self::get_sections_schema() as $sec_id => $sec ) {
            $redux_fields = [];
            foreach ( $sec['fields'] as $f ) {
                $rf = [
                    'id'       => $f['id'],
                    'title'    => $f['title'],
                    'subtitle' => $f['subtitle'] ?? '',
                    'default'  => $f['default'],
                ];
                switch ( $f['type'] ) {
                    case 'color':
                        $rf['type']        = 'color';
                        $rf['transparent'] = false;
                        break;
                    case 'switch':
                        $rf['type']    = 'switch';
                        $rf['Default'] = (bool) $f['default'];
                        break;
                    case 'select':
                        $rf['type']    = 'select';
                        $rf['options'] = $f['options'] ?? [];
                        break;
                    case 'textarea':
                    case 'code':
                        $rf['type'] = 'textarea';
                        break;
                    case 'media':
                        $rf['type'] = 'text';
                        break;
                    default:
                        $rf['type'] = 'text';
                        break;
                }
                $redux_fields[] = $rf;
            }

            \Redux::set_section(
                $this->opt_name,
                [
                    'title'  => $sec['title'],
                    'id'     => $sec_id,
                    'desc'   => $sec['desc'],
                    'icon'   => $sec['redux'] ?? 'el el-cog',
                    'fields' => $redux_fields,
                ]
            );
        }
    }

    /**
     * Register Native Standalone Control Center Menu & Submenus in WP Admin
     */
    public function register_native_options_menu(): void {
        add_menu_page(
            'پنل تنظیمات پیشرفته قالب زرین کد',
            'تنظیمات زرین کد',
            'manage_options',
            'zarincode-theme-options',
            [ $this, 'render_native_options_panel' ],
            'dashicons-superhero',
            2
        );

        add_submenu_page(
            'zarincode-theme-options',
            'پنل تنظیمات پیشرفته قالب زرین کد',
            '⚙️ تنظیمات پیشرفته قالب',
            'manage_options',
            'zarincode-theme-options',
            [ $this, 'render_native_options_panel' ]
        );

        add_submenu_page(
            'zarincode-theme-options',
            'نصب افزونه‌های مورد نیاز قالب',
            '🔌 نصب افزونه‌های قالب',
            'manage_options',
            'themes.php?page=zarincode-install-plugins'
        );

        add_submenu_page(
            'zarincode-theme-options',
            'درون‌ریز هوشمند دموی زرین کد',
            '🚀 درون‌ریز دمو با یک کلیک',
            'manage_options',
            'admin.php?page=zarincode-demo-importer'
        );

        add_submenu_page(
            'zarincode-theme-options',
            'مدیریت هدر و فوتر المنتوری',
            '🎨 هدر و فوتر المنتور',
            'manage_options',
            'edit.php?post_type=zc_hf_template'
        );
    }

    /**
     * Add Quick Links in WordPress Top Admin Bar
     */
    public function register_admin_bar_nodes( \WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $wp_admin_bar->add_node( [
            'id'    => 'zarincode-hub',
            'title' => '⚡ تنظیمات و مدیریت زرین کد',
            'href'  => admin_url( 'admin.php?page=zarincode-theme-options' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'zarincode-hub-options',
            'parent' => 'zarincode-hub',
            'title'  => '⚙️ پنل تنظیمات پیشرفته قالب (۱۶ بخش)',
            'href'   => admin_url( 'admin.php?page=zarincode-theme-options' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'zarincode-hub-plugins',
            'parent' => 'zarincode-hub',
            'title'  => '🔌 نصب و مدیریت افزونه‌های قالب',
            'href'   => admin_url( 'themes.php?page=zarincode-install-plugins' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'zarincode-hub-demo',
            'parent' => 'zarincode-hub',
            'title'  => '🚀 درون‌ریز هوشمند دمو با یک کلیک',
            'href'   => admin_url( 'admin.php?page=zarincode-demo-importer' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'zarincode-hub-hf',
            'parent' => 'zarincode-hub',
            'title'  => '🎨 مدیریت هدر و فوتر المنتوری',
            'href'   => admin_url( 'edit.php?post_type=zc_hf_template' ),
        ] );
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( strpos( $hook, 'zarincode' ) !== false ) {
            wp_enqueue_media();
        }
    }

    /**
     * Render the Native Crextio-Styled Advanced Control Center (16 Tabs, Search, Presets, Import/Export)
     */
    public function render_native_options_panel(): void {
        $schema   = self::get_sections_schema();
        $defaults = self::get_all_defaults();
        $saved    = get_option( $this->opt_name, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        $options  = array_merge( $defaults, $saved );
        $feedback = '';

        // Handle Save / Reset / Import Settings
        if ( isset( $_POST['zarincode_options_action'] ) && check_admin_referer( 'zarincode_save_options_nonce' ) ) {
            $act = sanitize_text_field( wp_unslash( $_POST['zarincode_options_action'] ) );

            if ( 'reset_defaults' === $act ) {
                $options = $defaults;
                update_option( $this->opt_name, $options );
                $feedback = '🔄 تمامی تنظیمات قالب زرین کد به مقادیر استاندارد اولیه (Crextio Sunshine) بازنشانی شدند.';
            } elseif ( 'import_json' === $act && ! empty( $_POST['zarincode_import_json_raw'] ) ) {
                $raw_json = wp_unslash( $_POST['zarincode_import_json_raw'] );
                $decoded  = json_decode( $raw_json, true );
                if ( is_array( $decoded ) ) {
                    $options = array_merge( $defaults, $decoded );
                    update_option( $this->opt_name, $options );
                    $feedback = '📥 فایل پشتیبان تنظیمات JSON با موفقیت درون‌ریزی و اعمال شد!';
                } else {
                    $feedback = '❌ خطا: ساختار کد JSON وارد شده معتبر نیست.';
                }
            } else {
                $posted = isset( $_POST['zc_opt'] ) && is_array( $_POST['zc_opt'] ) ? wp_unslash( $_POST['zc_opt'] ) : [];
                $new_options = [];
                foreach ( $schema as $sec ) {
                    foreach ( $sec['fields'] as $field ) {
                        $fid   = $field['id'];
                        $ftype = $field['type'];
                        if ( 'switch' === $ftype ) {
                            $new_options[ $fid ] = ! empty( $posted[ $fid ] );
                        } elseif ( 'number' === $ftype ) {
                            $new_options[ $fid ] = isset( $posted[ $fid ] ) ? (int) $posted[ $fid ] : (int) $field['default'];
                        } elseif ( 'code' === $ftype || 'textarea' === $ftype ) {
                            $new_options[ $fid ] = isset( $posted[ $fid ] ) ? trim( (string) $posted[ $fid ] ) : '';
                        } else {
                            $new_options[ $fid ] = isset( $posted[ $fid ] ) ? sanitize_text_field( (string) $posted[ $fid ] ) : $field['default'];
                        }
                    }
                }

                // Sync color preset if user switched preset
                if ( isset( $new_options['default_color_preset'] ) && $new_options['default_color_preset'] !== ( $options['default_color_preset'] ?? 'sunshine' ) ) {
                    $preset_map = [
                        'sunshine' => '#FDD05B',
                        'emerald'  => '#34D399',
                        'royal'    => '#60A5FA',
                        'sunset'   => '#FB923C',
                        'amethyst' => '#C084FC',
                    ];
                    if ( isset( $preset_map[ $new_options['default_color_preset'] ] ) ) {
                        $new_options['accent_color'] = $preset_map[ $new_options['default_color_preset'] ];
                    }
                }

                $options = $new_options;
                update_option( $this->opt_name, $options );
                $feedback = '✅ تمامی تغییرات شما در پنل تنظیمات پیشرفته قالب زرین کد با موفقیت ذخیره و در سایت اعمال شدند!';
            }
        }

        $first_tab = array_key_first( $schema );
        ?>
        <div class="wrap" style="direction:rtl;font-family:Tahoma,system-ui,sans-serif;max-width:1320px;margin-top:18px;">
            <style>
                .zc-opt-shell { display:grid; grid-template-columns: 290px 1fr; gap: 22px; align-items: start; }
                @media (max-width: 960px) { .zc-opt-shell { grid-template-columns: 1fr; } }
                .zc-opt-sidebar { background: #1E2025; border-radius: 22px; padding: 16px; border: 2px solid #FDD05B; position: sticky; top: 46px; }
                .zc-opt-nav-btn { display:flex; align-items:center; gap:10px; width:100%; text-align:right; background:transparent; border:none; color:#D1D5DB; padding:11px 14px; border-radius:12px; cursor:pointer; font-size:13px; font-weight:600; transition:all 0.2s ease; margin-bottom:4px; }
                .zc-opt-nav-btn:hover { background:rgba(255,255,255,0.08); color:#fff; }
                .zc-opt-nav-btn.is-active { background:#FDD05B; color:#2A2C31; font-weight:900; box-shadow:0 6px 16px rgba(253,208,91,0.3); }
                .zc-opt-panel { display:none; background:#fff; border-radius:24px; padding:28px 32px; border:1px solid #E5E7EB; box-shadow:0 10px 30px rgba(0,0,0,0.04); }
                .zc-opt-panel.is-active { display:block; }
                .zc-field-row { display:grid; grid-template-columns: 320px 1fr; gap:20px; padding:18px 0; border-bottom:1px solid #F3F4F6; align-items:center; }
                @media (max-width: 780px) { .zc-field-row { grid-template-columns: 1fr; } }
                .zc-field-row:last-child { border-bottom:none; }
                .zc-input-text, .zc-input-select, .zc-input-textarea { width:100%; max-width:560px; padding:9px 14px; border-radius:12px; border:1px solid #D1D5DB; font-size:13.5px; background:#F9FAFB; }
                .zc-input-text:focus, .zc-input-select:focus, .zc-input-textarea:focus { border-color:#FDD05B; outline:none; background:#fff; box-shadow:0 0 0 3px rgba(253,208,91,0.25); }
                .zc-switch { position:relative; display:inline-block; width:54px; height:28px; }
                .zc-switch input { opacity:0; width:0; height:0; }
                .zc-slider { position:absolute; cursor:pointer; inset:0; background-color:#D1D5DB; transition:.25s; border-radius:999px; }
                .zc-slider:before { position:absolute; content:""; height:22px; width:22px; right:3px; bottom:3px; background-color:white; transition:.25s; border-radius:50%; }
                .zc-switch input:checked + .zc-slider { background-color:#2A2C31; border:1px solid #FDD05B; }
                .zc-switch input:checked + .zc-slider:before { transform:translateX(-25px); background-color:#FDD05B; }
            </style>

            <form method="post" id="zc-theme-options-form">
                <?php wp_nonce_field( 'zarincode_save_options_nonce' ); ?>
                <input type="hidden" name="zarincode_options_action" id="zc-options-action-input" value="save">

                <!-- Top Control Header -->
                <div style="background:linear-gradient(135deg,#2A2C31 0%,#15171C 100%);color:#fff;padding:26px 32px;border-radius:24px;border:2px solid #FDD05B;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:18px;box-shadow:0 16px 40px rgba(0,0,0,0.12);">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <span style="background:#FDD05B;color:#2A2C31;padding:3px 12px;border-radius:999px;font-weight:900;font-size:11.5px;">ZARINCODE CONTROL CENTER v<?php echo esc_html( ZARINCODE_VERSION ); ?></span>
                            <span style="color:#9CA3AF;font-size:12px;">• ۱۶ دپارتمان مدیریتی و بیش از ۱۲۰ قابلیت شخصی‌سازی زنده</span>
                        </div>
                        <h1 style="color:#fff;font-size:22px;margin:0 0 4px;font-weight:900;">پنل تنظیمات حرفه‌ای و جامع قالب «زرین کد (Zarincode)»</h1>
                        <div style="color:#D1D5DB;font-size:12.5px;">
                            طراحی و توسعه: <strong>احسان نادری پناه (Ehsan NaderiPanah)</strong> | پشتیبانی مستقیم: <strong dir="ltr" style="color:#FDD05B;">09024561001</strong> | <a href="https://zarincode.com" target="_blank" style="color:#FDD05B;">Zarincode.com</a>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <input type="search" id="zc-options-live-search" placeholder="🔍 جستجوی سریع در بین ۱۲۰ تنظیم..." style="padding:8px 16px;border-radius:999px;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.1);color:#fff;min-width:230px;font-size:12.5px;">
                        <button type="submit" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:900;border-radius:999px;padding:4px 26px;box-shadow:0 8px 20px rgba(253,208,91,0.35);">
                            💾 ذخیره تمام تنظیمات
                        </button>
                        <button type="submit" onclick="if(confirm('آیا از بازنشانی تمامی تنظیمات به حالت پیش‌فرض اطمینان دارید؟')){document.getElementById('zc-options-action-input').value='reset_defaults';}else{return false;}" class="button button-hero" style="border-radius:999px;background:rgba(255,255,255,0.08);color:#FCA5A5;border-color:rgba(252,165,165,0.3);">
                            🔄 بازنشانی پیش‌فرض
                        </button>
                    </div>
                </div>

                <!-- Quick Access Strip -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
                    <a href="<?php echo esc_url( admin_url( 'themes.php?page=zarincode-install-plugins' ) ); ?>" class="button" style="border-radius:10px;font-weight:bold;">🔌 نصب و مدیریت افزونه‌های قالب</a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zarincode-demo-importer' ) ); ?>" class="button" style="border-radius:10px;font-weight:bold;">🚀 درون‌ریز دمو با یک کلیک</a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_hf_template' ) ); ?>" class="button" style="border-radius:10px;font-weight:bold;">🎨 مدیریت هدر و فوتر المنتوری</a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_service' ) ); ?>" class="button" style="border-radius:10px;font-weight:bold;">🏆 مدیریت ۶ خدمت تخصصی</a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zc_project' ) ); ?>" class="button" style="border-radius:10px;font-weight:bold;">💼 مدیریت نمونه‌کارها</a>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button" style="border-radius:10px;font-weight:bold;">🌐 مشاهده زنده وب‌سایت ↗</a>
                </div>

                <?php if ( $feedback ) : ?>
                    <div style="background:#ECFDF5;border:2px solid #10B981;color:#065F46;padding:16px 22px;border-radius:16px;margin-bottom:20px;font-weight:bold;font-size:14px;">
                        <?php echo esc_html( $feedback ); ?>
                    </div>
                <?php endif; ?>

                <div class="zc-opt-shell">
                    <!-- Vertical Tabs Navigation -->
                    <aside class="zc-opt-sidebar">
                        <?php foreach ( $schema as $sec_id => $sec ) : ?>
                            <button type="button" class="zc-opt-nav-btn <?php echo $sec_id === $first_tab ? 'is-active' : ''; ?>" data-target="zc-sec-<?php echo esc_attr( $sec_id ); ?>">
                                <span class="dashicons <?php echo esc_attr( $sec['icon'] ); ?>"></span>
                                <span><?php echo esc_html( $sec['title'] ); ?></span>
                            </button>
                        <?php endforeach; ?>
                        <button type="button" class="zc-opt-nav-btn" data-target="zc-sec-backup">
                            <span class="dashicons dashicons-database-export"></span>
                            <span>۱۷. پشتیبان‌گیری و انتقال (JSON)</span>
                        </button>
                    </aside>

                    <!-- Tab Content Panels -->
                    <div>
                        <?php foreach ( $schema as $sec_id => $sec ) : ?>
                            <section id="zc-sec-<?php echo esc_attr( $sec_id ); ?>" class="zc-opt-panel <?php echo $sec_id === $first_tab ? 'is-active' : ''; ?>">
                                <div style="border-bottom:2px solid #FEF3C7;padding-bottom:14px;margin-bottom:16px;">
                                    <h2 style="margin:0 0 6px;font-size:19px;color:#111827;font-weight:900;">
                                        <span class="dashicons <?php echo esc_attr( $sec['icon'] ); ?>" style="color:#D97706;margin-left:6px;"></span>
                                        <?php echo esc_html( $sec['title'] ); ?>
                                    </h2>
                                    <p style="margin:0;color:#6B7280;font-size:13px;"><?php echo esc_html( $sec['desc'] ); ?></p>
                                </div>

                                <?php foreach ( $sec['fields'] as $field ) :
                                    $fid   = $field['id'];
                                    $val   = $options[ $fid ] ?? $field['default'];
                                    $ftype = $field['type'];
                                    ?>
                                    <div class="zc-field-row" data-search-label="<?php echo esc_attr( $field['title'] . ' ' . ( $field['subtitle'] ?? '' ) . ' ' . $fid ); ?>">
                                        <div>
                                            <label for="zc_field_<?php echo esc_attr( $fid ); ?>" style="font-weight:800;font-size:13.5px;color:#1F2937;display:block;margin-bottom:4px;">
                                                <?php echo esc_html( $field['title'] ); ?>
                                            </label>
                                            <?php if ( ! empty( $field['subtitle'] ) ) : ?>
                                                <span style="font-size:11.5px;color:#6B7280;display:block;"><?php echo esc_html( $field['subtitle'] ); ?></span>
                                            <?php endif; ?>
                                            <code style="font-size:10.5px;color:#9CA3AF;background:#F3F4F6;padding:1px 6px;border-radius:4px;display:inline-block;margin-top:4px;"><?php echo esc_html( $fid ); ?></code>
                                        </div>

                                        <div>
                                            <?php if ( 'text' === $ftype ) : ?>
                                                <input type="text" id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" value="<?php echo esc_attr( (string) $val ); ?>" class="zc-input-text">

                                            <?php elseif ( 'number' === $ftype ) : ?>
                                                <input type="number" id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" value="<?php echo esc_attr( (string) $val ); ?>" class="zc-input-text" style="max-width:180px;">

                                            <?php elseif ( 'color' === $ftype ) : ?>
                                                <div style="display:flex;align-items:center;gap:10px;">
                                                    <input type="color" id="zc_color_picker_<?php echo esc_attr( $fid ); ?>" value="<?php echo esc_attr( (string) $val ); ?>" onchange="document.getElementById('zc_field_<?php echo esc_attr( $fid ); ?>').value=this.value;" style="width:48px;height:40px;padding:2px;border-radius:10px;border:1px solid #D1D5DB;cursor:pointer;">
                                                    <input type="text" id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" value="<?php echo esc_attr( (string) $val ); ?>" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('zc_color_picker_<?php echo esc_attr( $fid ); ?>').value=this.value;}" class="zc-input-text" style="max-width:160px;direction:ltr;text-align:left;font-family:monospace;">
                                                </div>

                                            <?php elseif ( 'switch' === $ftype ) : ?>
                                                <label class="zc-switch">
                                                    <input type="checkbox" id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" value="1" <?php checked( ! empty( $val ) ); ?>>
                                                    <span class="zc-slider"></span>
                                                </label>

                                            <?php elseif ( 'select' === $ftype ) : ?>
                                                <select id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" class="zc-input-select">
                                                    <?php foreach ( ( $field['options'] ?? [] ) as $ok => $olabel ) : ?>
                                                        <option value="<?php echo esc_attr( (string) $ok ); ?>" <?php selected( (string) $val, (string) $ok ); ?>>
                                                            <?php echo esc_html( $olabel ); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                            <?php elseif ( 'media' === $ftype ) : ?>
                                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                                    <input type="text" id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" value="<?php echo esc_attr( (string) $val ); ?>" class="zc-input-text" placeholder="آدرس تصویر یا انتخاب از کتابخانه رسانه..." style="max-width:400px;" dir="ltr">
                                                    <button type="button" class="button zc-media-upload-btn" data-target="zc_field_<?php echo esc_attr( $fid ); ?>" style="border-radius:10px;font-weight:bold;">
                                                        🖼️ انتخاب تصویر
                                                    </button>
                                                </div>

                                            <?php elseif ( 'textarea' === $ftype || 'code' === $ftype ) : ?>
                                                <textarea id="zc_field_<?php echo esc_attr( $fid ); ?>" name="zc_opt[<?php echo esc_attr( $fid ); ?>]" rows="<?php echo 'code' === $ftype ? '5' : '3'; ?>" class="zc-input-textarea" style="<?php echo 'code' === $ftype ? 'direction:ltr;text-align:left;font-family:monospace;background:#1E2025;color:#FDE68A;' : ''; ?>"><?php echo esc_textarea( (string) $val ); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div style="margin-top:22px;padding-top:16px;border-top:1px solid #E5E7EB;display:flex;justify-content:flex-end;">
                                    <button type="submit" class="button button-primary button-hero" style="background:#2A2C31;border-color:#2A2C31;color:#FDD05B;font-weight:bold;border-radius:999px;padding:4px 28px;">
                                        💾 ذخیره تنظیمات این بخش
                                    </button>
                                </div>
                            </section>
                        <?php endforeach; ?>

                        <!-- Section 17: Backup & JSON Import/Export -->
                        <section id="zc-sec-backup" class="zc-opt-panel">
                            <h2 style="margin:0 0 8px;font-size:19px;font-weight:900;">۱۷. پشتیبان‌گیری، برون‌ریزی (Export) و درون‌ریزی (Import) تنظیمات قالب</h2>
                            <p style="color:#6B7280;font-size:13px;margin-bottom:20px;">
                                می‌توانید از تمامی ۱۲۰+ تنظیم فعلی قالب کپی پشتیبان (JSON) تهیه کنید یا تنظیمات ذخیره شده قبلی را درون‌ریزی نمایید.
                            </p>
                            <div style="margin-bottom:22px;">
                                <label style="font-weight:bold;display:block;margin-bottom:8px;">کد پشتیبان تنظیمات فعلی (Export JSON — کپی کنید):</label>
                                <textarea readonly rows="7" class="zc-input-textarea" style="max-width:100%;direction:ltr;font-family:monospace;font-size:12px;background:#F3F4F6;"><?php echo esc_textarea( wp_json_encode( $options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
                            </div>
                            <div>
                                <label style="font-weight:bold;display:block;margin-bottom:8px;">درون‌ریزی تنظیمات از کد JSON (Import JSON):</label>
                                <textarea name="zarincode_import_json_raw" rows="6" class="zc-input-textarea" placeholder="کد JSON پشتیبان را اینجا قرار دهید..." style="max-width:100%;direction:ltr;font-family:monospace;font-size:12px;"></textarea>
                                <div style="margin-top:12px;">
                                    <button type="submit" onclick="document.getElementById('zc-options-action-input').value='import_json';" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:bold;border-radius:999px;">
                                        📥 درون‌ریزی و اعمال فایل JSON
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>
        </div>

        <script>
        (function(){
            // Tab switching
            const navBtns = document.querySelectorAll('.zc-opt-nav-btn');
            const panels  = document.querySelectorAll('.zc-opt-panel');

            navBtns.forEach(function(btn){
                btn.addEventListener('click', function(){
                    const target = btn.getAttribute('data-target');
                    navBtns.forEach(function(b){ b.classList.remove('is-active'); });
                    panels.forEach(function(p){ p.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    const panel = document.getElementById(target);
                    if (panel) panel.classList.add('is-active');
                    document.querySelectorAll('.zc-field-row').forEach(function(r){ r.style.display = ''; });
                });
            });

            // Instant Live Search across all 120+ settings
            const searchInput = document.getElementById('zc-options-live-search');
            if (searchInput) {
                searchInput.addEventListener('input', function(){
                    const q = searchInput.value.trim().toLowerCase();
                    if (!q) {
                        panels.forEach(function(p){ p.classList.remove('is-active'); });
                        const activeBtn = document.querySelector('.zc-opt-nav-btn.is-active');
                        if (activeBtn) {
                            const t = document.getElementById(activeBtn.getAttribute('data-target'));
                            if (t) t.classList.add('is-active');
                        }
                        document.querySelectorAll('.zc-field-row').forEach(function(r){ r.style.display = ''; });
                        return;
                    }
                    panels.forEach(function(p){
                        let hasMatch = false;
                        p.querySelectorAll('.zc-field-row').forEach(function(row){
                            const txt = (row.getAttribute('data-search-label') || '').toLowerCase();
                            if (txt.indexOf(q) !== -1) {
                                row.style.display = '';
                                hasMatch = true;
                            } else {
                                row.style.display = 'none';
                            }
                        });
                        if (hasMatch) {
                            p.classList.add('is-active');
                        } else {
                            p.classList.remove('is-active');
                        }
                    });
                });
            }

            // WordPress Media Uploader
            document.querySelectorAll('.zc-media-upload-btn').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    const targetId = btn.getAttribute('data-target');
                    const input    = document.getElementById(targetId);
                    if (!window.wp || !wp.media) return;
                    const frame = wp.media({
                        title: 'انتخاب تصویر از رسانه وردپرس',
                        button: { text: 'استفاده از این تصویر' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        const attachment = frame.state().get('selection').first().toJSON();
                        if (input && attachment.url) {
                            input.value = attachment.url;
                        }
                    });
                    frame.open();
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Apply WordPress Head & Performance Optimizations when enabled
     */
    public function apply_performance_optimizations(): void {
        if ( zarincode_get_option( 'optimize_wp_head', true ) ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_action( 'wp_head', 'wp_generator' );
            remove_action( 'wp_head', 'wlwmanifest_link' );
            remove_action( 'wp_head', 'rsd_link' );
        }
    }

    /**
     * Render Maintenance / Coming Soon Mode for non-admin visitors when enabled
     */
    public function maybe_render_maintenance_mode(): void {
        if ( ! zarincode_get_option( 'enable_maintenance_mode', false ) ) {
            return;
        }
        if ( current_user_can( 'manage_options' ) || is_user_logged_in() ) {
            return;
        }
        $title = zarincode_get_option( 'maintenance_title', 'وب‌سایت زرین کد در حال بروزرسانی است' );
        $msg   = zarincode_get_option( 'maintenance_message', 'در حال ارتقای زیرساخت هستیم. تماس مستقیم: 09024561001' );
        $phone = zarincode_get_option( 'contact_phone', '09024561001' );
        status_header( 503 );
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html( $title ); ?></title>
            <style>
                body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#15171C 0%,#2A2C31 100%); color:#fff; font-family:Tahoma,system-ui,sans-serif; padding:20px; text-align:center; }
                .box { max-width:600px; background:rgba(255,255,255,0.06); border:2px solid #FDD05B; border-radius:28px; padding:42px 32px; backdrop-filter:blur(14px); }
                .badge { display:inline-block; background:#FDD05B; color:#2A2C31; padding:6px 18px; border-radius:999px; font-weight:900; font-size:12px; margin-bottom:16px; }
                h1 { font-size:26px; margin:0 0 14px; color:#FDD05B; }
                p { font-size:15px; line-height:1.9; color:#E5E7EB; margin-bottom:24px; }
                .btn { display:inline-block; background:#FDD05B; color:#2A2C31; text-decoration:none; font-weight:900; padding:12px 28px; border-radius:999px; }
            </style>
        </head>
        <body>
            <div class="box">
                <span class="badge">ZARINCODE MAINTENANCE MODE</span>
                <h1><?php echo esc_html( $title ); ?></h1>
                <p><?php echo esc_html( $msg ); ?></p>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="btn">تماس فوری: <?php echo esc_html( $phone ); ?></a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Output Dynamic CSS Variables, Favicon, Custom CSS/JS & Schema.org JSON-LD in wp_head
     */
    public function output_dynamic_styles_and_schema(): void {
        $accent       = zarincode_get_option( 'accent_color', '#FDD05B' );
        $accent_hover = zarincode_get_option( 'accent_hover_color', '#F5C038' );
        $contrast     = zarincode_get_option( 'accent_contrast_color', '#2A2C31' );
        $dark_surf    = zarincode_get_option( 'dark_surface_color', '#2A2C31' );
        $bg_from      = zarincode_get_option( 'bg_gradient_from', '#F8F6F0' );
        $bg_to        = zarincode_get_option( 'bg_gradient_to', '#FFF5D1' );
        $dark_from    = zarincode_get_option( 'dark_bg_from', '#15171C' );
        $dark_to      = zarincode_get_option( 'dark_bg_to', '#1F1B14' );
        $container_w  = zarincode_get_option( 'container_max_width', '1360px' );
        $radius       = zarincode_get_option( 'bento_border_radius', '28px' );
        $blur         = (int) zarincode_get_option( 'glass_blur_px', 14 );
        $font_size    = (int) zarincode_get_option( 'body_font_size', 15 );
        $line_height  = zarincode_get_option( 'body_line_height', '1.9' );
        $h_weight     = zarincode_get_option( 'heading_weight', '900' );
        $topbar_bg    = zarincode_get_option( 'topbar_bg_color', '#2A2C31' );
        $favicon      = zarincode_get_option( 'favicon_url', '' );

        if ( ! empty( $favicon ) ) {
            echo '<link rel="icon" href="' . esc_url( $favicon ) . '" />' . "\n";
        }
        ?>
        <style id="zarincode-dynamic-options-css">
            :root {
                --color-accent: <?php echo esc_attr( $accent ); ?>;
                --color-accent-hover: <?php echo esc_attr( $accent_hover ); ?>;
                --color-accent-contrast: <?php echo esc_attr( $contrast ); ?>;
                --zc-dark-card: <?php echo esc_attr( $dark_surf ); ?>;
                --zc-bg-from: <?php echo esc_attr( $bg_from ); ?>;
                --zc-bg-to: <?php echo esc_attr( $bg_to ); ?>;
                --zc-container-width: <?php echo esc_attr( $container_w ); ?>;
                --zc-radius-bento: <?php echo esc_attr( $radius ); ?>;
                --zc-glass-blur: <?php echo esc_attr( $blur . 'px' ); ?>;
            }
            body {
                font-size: <?php echo esc_attr( $font_size . 'px' ); ?>;
                line-height: <?php echo esc_attr( $line_height ); ?>;
                background: linear-gradient(135deg, var(--zc-bg-from) 0%, var(--zc-bg-to) 100%);
            }
            html.dark body {
                background: linear-gradient(135deg, <?php echo esc_attr( $dark_from ); ?> 0%, <?php echo esc_attr( $dark_to ); ?> 100%);
            }
            .zc-container { max-width: var(--zc-container-width); }
            .zc-glass-panel, .zc-dark-card, .zc-service-card, .zc-product-card { border-radius: var(--zc-radius-bento); }
            .zc-glass-panel { backdrop-filter: blur(var(--zc-glass-blur)); -webkit-backdrop-filter: blur(var(--zc-glass-blur)); }
            h1, h2, h3, .zc-section-title, .zc-hero-title { font-weight: <?php echo esc_attr( $h_weight ); ?>; }
            .zc-topbar { background-color: <?php echo esc_attr( $topbar_bg ); ?>; }
            <?php if ( ! zarincode_get_option( 'enable_sticky_header', true ) ) : ?>
            .zc-header-wrap { position: relative !important; top: 0 !important; }
            <?php endif; ?>
        </style>
        <?php

        $custom_css = zarincode_get_option( 'custom_css', '' );
        if ( ! empty( $custom_css ) ) {
            echo '<style id="zarincode-user-custom-css">' . "\n" . $custom_css . "\n" . '</style>' . "\n";
        }

        $custom_head_js = zarincode_get_option( 'custom_head_js', '' );
        if ( ! empty( $custom_head_js ) ) {
            echo $custom_head_js . "\n";
        }

        // Schema.org JSON-LD Structured Data
        if ( zarincode_get_option( 'enable_schema_markup', true ) ) {
            $schema_data = [
                '@context'    => 'https://schema.org',
                '@type'       => 'ProfessionalService',
                'name'        => zarincode_get_option( 'brand_name', 'زرین کد' ) . ' | ' . zarincode_get_option( 'brand_en_name', 'Zarincode' ),
                'url'         => home_url( '/' ),
                'telephone'   => zarincode_get_option( 'contact_phone', '09024561001' ),
                'email'       => zarincode_get_option( 'contact_email', 'info@zarincode.com' ),
                'founder'     => [
                    '@type' => 'Person',
                    'name'  => zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' ),
                ],
                'description' => zarincode_get_option( 'brand_slogan', 'طراحی سایت، برنامه‌نویسی نرم‌افزار، سئو سایت و شبکه‌های اجتماعی، تولید محتوا و فروش محصولات آماده' ),
                'sameAs'      => array_values( array_filter( [
                    zarincode_get_option( 'official_website', 'https://zarincode.com' ),
                    zarincode_get_option( 'social_github', 'https://github.com/Ehsan-Np' ),
                ] ) ),
            ];
            echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        }
    }

    /**
     * Output Top Scroll Progress Bar & Optional Preloader
     */
    public function output_preloader_and_progress_bar(): void {
        if ( zarincode_get_option( 'enable_scroll_progress', true ) ) : ?>
            <div id="zc-scroll-progress-bar" style="position:fixed;top:0;right:0;height:4px;width:0%;background:linear-gradient(90deg,#2A2C31,var(--color-accent));z-index:99999;transition:width 0.1s linear;"></div>
        <?php endif;

        if ( zarincode_get_option( 'enable_preloader', false ) ) :
            $p_text = zarincode_get_option( 'preloader_text', 'زرین کد | Zarincode.com' );
            ?>
            <div id="zc-site-preloader" style="position:fixed;inset:0;background:#18191D;z-index:999999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;transition:opacity 0.4s ease;">
                <div style="width:58px;height:58px;border-radius:50%;background:var(--color-accent);color:#2A2C31;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:26px;box-shadow:0 0 30px rgba(253,208,91,0.5);">Z</div>
                <div style="color:#FDD05B;font-weight:800;font-size:14px;"><?php echo esc_html( $p_text ); ?></div>
            </div>
            <script>
            window.addEventListener('load', function(){
                var pl = document.getElementById('zc-site-preloader');
                if (pl) { pl.style.opacity = '0'; setTimeout(function(){ pl.remove(); }, 400); }
            });
            </script>
        <?php endif;
    }

    /**
     * Output Floating Quick Contact Widget, Back-to-Top Button, GDPR Cookie Bar & Custom Footer JS
     */
    public function output_footer_utilities(): void {
        $phone = zarincode_get_option( 'contact_phone', '09024561001' );

        // 1. Floating Quick Contact & Consultation Button
        if ( zarincode_get_option( 'enable_floating_contact', true ) ) :
            $label = zarincode_get_option( 'floating_contact_label', 'مشاوره فوری: 09024561001' );
            ?>
            <div class="zc-floating-quick-contact" style="position:fixed;bottom:22px;right:22px;z-index:9980;display:flex;align-items:center;gap:8px;">
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-yellow" style="padding:10px 18px;border-radius:999px;font-size:13px;box-shadow:0 12px 28px rgba(42,44,49,0.22);border:2px solid #2A2C31;">
                    <i class="fa-solid fa-phone-volume"></i>
                    <span><?php echo esc_html( $label ); ?></span>
                </a>
                <?php if ( zarincode_get_option( 'enable_back_to_top', true ) ) : ?>
                    <button type="button" id="zc-back-to-top-btn" onclick="window.scrollTo({top:0,behavior:'smooth'});" title="بازگشت به بالای صفحه" style="width:42px;height:42px;border-radius:50%;background:#2A2C31;color:#FDD05B;border:2px solid #FDD05B;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,0.2);">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif;

        // 2. GDPR Cookie Consent Bar
        if ( zarincode_get_option( 'enable_gdpr_cookie_bar', false ) ) :
            $cookie_text = zarincode_get_option( 'gdpr_cookie_text', 'وب‌سایت زرین کد برای ارائه تجربه کاربری بهتر و مطابق قوانین حریم خصوصی و GDPR از کوکی‌ها استفاده می‌کند.' );
            ?>
            <div id="zc-gdpr-cookie-bar" style="display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:9995;max-width:780px;width:92%;background:#2A2C31;color:#fff;padding:16px 24px;border-radius:20px;border:2px solid #FDD05B;box-shadow:0 18px 40px rgba(0,0,0,0.3);align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div style="font-size:13px;color:#E5E7EB;flex:1;">
                    <i class="fa-solid fa-cookie-bite" style="color:#FDD05B;margin-left:6px;"></i>
                    <?php echo esc_html( $cookie_text ); ?>
                    <a href="<?php echo esc_url( home_url( '/gdpr-compliance/' ) ); ?>" style="color:#FDD05B;text-decoration:underline;margin-right:6px;">مطالعه قوانین GDPR</a>
                </div>
                <button type="button" onclick="localStorage.setItem('zc_gdpr_accepted','1');document.getElementById('zc-gdpr-cookie-bar').style.display='none';" class="zc-btn zc-btn-yellow" style="padding:6px 18px;font-size:12.5px;">
                    متوجه شدم و موافقم
                </button>
            </div>
            <script>
            if (!localStorage.getItem('zc_gdpr_accepted')) {
                var cb = document.getElementById('zc-gdpr-cookie-bar');
                if (cb) cb.style.display = 'flex';
            }
            </script>
        <?php endif;

        // 3. Scroll progress listener
        if ( zarincode_get_option( 'enable_scroll_progress', true ) ) : ?>
            <script>
            window.addEventListener('scroll', function(){
                var sb = document.getElementById('zc-scroll-progress-bar');
                if (!sb) return;
                var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var scrolled = height > 0 ? (winScroll / height) * 100 : 0;
                sb.style.width = scrolled + '%';
            }, { passive: true });
            </script>
        <?php endif;

        // 4. Custom Footer JS
        $custom_footer_js = zarincode_get_option( 'custom_footer_js', '' );
        if ( ! empty( $custom_footer_js ) ) {
            echo $custom_footer_js . "\n";
        }
    }

    public function filter_shop_columns(): int {
        return (int) zarincode_get_option( 'shop_columns', 4 );
    }

    public function filter_shop_per_page(): int {
        return (int) zarincode_get_option( 'shop_per_page', 8 );
    }
}
