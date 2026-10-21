<?php
/**
 * 12 Custom Elementor Widgets extending \Elementor\Widget_Base for Zarincode Theme
 *
 * Each widget provides Elementor Controls_Manager fields and renders via \Zarincode\Core\Widgets\Renderer.
 *
 * @package Zarincode\Core\Widgets
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Abstract_Zarincode_Widget extends Widget_Base {
    public function get_categories(): array {
        return [ 'zarincode-elements' ];
    }
    public function get_keywords(): array {
        return [ 'zarincode', 'webinex', 'crextio', 'زرین کد', 'احسان نادری پناه' ];
    }
}

/** 1. Hero Section Widget */
class Widget_Hero_Crextio extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_hero'; }
    public function get_title(): string { return '۱. هیرو سکشن زرین کد (Crextio + Webinex)'; }
    public function get_icon(): string { return 'eicon-banner'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات هیرو سکشن', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'badge', [ 'label' => 'متن بج بالای تیتر', 'type' => Controls_Manager::TEXT, 'default' => 'ZARINCODE - Your Trusted Digital Expert & Software Studio' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_hero( $this->get_settings_for_display() );
    }
}

/** 2. Brands & Tech Marquee Widget */
class Widget_Brands_Marquee extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_brands'; }
    public function get_title(): string { return '۲. اسلایدر برندها و تکنولوژی‌ها ( زرین کد )'; }
    public function get_icon(): string { return 'eicon-carousel'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات برندها', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'برندها و تکنولوژی‌هایی که به زرین کد اعتماد کردند!' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_brands_marquee( $this->get_settings_for_display() );
    }
}

/** 3. 6 Core Services Bento Grid Widget */
class Widget_Services_Bento extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_services'; }
    public function get_title(): string { return '۳. شبکه ۶ خدمت تخصصی زرین کد (Bento Grid)'; }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات خدمات', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان خدمات', 'type' => Controls_Manager::TEXT, 'default' => 'خدمات تخصصی آژانس زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_services_bento( $this->get_settings_for_display() );
    }
}

/** 4. Digital Products Marketplace Widget */
class Widget_Products_Marketplace extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_products'; }
    public function get_title(): string { return '۴. فروشگاه محصولات آماده (قالب، افزونه و سورس‌کد)'; }
    public function get_icon(): string { return 'eicon-products'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات ویترین فروشگاه', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'count', [ 'label' => 'تعداد محصولات', 'type' => Controls_Manager::NUMBER, 'default' => 8 ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_products_marketplace( $this->get_settings_for_display() );
    }
}

/** 5. Portfolio / Projects Showcase Widget */
class Widget_Portfolio_Showcase extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_portfolio'; }
    public function get_title(): string { return '۵. نمونه‌کارها و پروژه‌های موفق (فیلتر تب‌دار)'; }
    public function get_icon(): string { return 'eicon-portfolio-grid'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات نمونه‌کارها', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'برخی از نمونه‌کارهای موفق زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_portfolio_showcase( $this->get_settings_for_display() );
    }
}

/** 6. Why Us & Counters Widget */
class Widget_Why_Us_Stats extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_why_us'; }
    public function get_title(): string { return '۶. چرا زرین کد؟ + آمار و ارقام (Why Us)'; }
    public function get_icon(): string { return 'eicon-counter'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات چرا ما', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'چرا زرین کد؟ چه چیزی ما را متمایز می‌کند؟' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_why_us_stats( $this->get_settings_for_display() );
    }
}

/** 7. Tabbed Pricing Plans Widget */
class Widget_Pricing_Tabs extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_pricing'; }
    public function get_title(): string { return '۷. جداول تعرفه تب‌دار (طراحی سایت، نرم‌افزار، سئو، محتوا)'; }
    public function get_icon(): string { return 'eicon-price-table'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات تعرفه‌ها', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان تعرفه‌ها', 'type' => Controls_Manager::TEXT, 'default' => 'تعرفه خدمات تخصصی زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_pricing_tabs( $this->get_settings_for_display() );
    }
}

/** 8. Promo Campaign Banner Widget */
class Widget_Promo_Banner extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_promo'; }
    public function get_title(): string { return '۸. بنر جشنواره تخفیف ۵۰٪ زرین کد پلاس'; }
    public function get_icon(): string { return 'eicon-call-to-action'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات بنر تبلیغاتی', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان بنر', 'type' => Controls_Manager::TEXT, 'default' => 'جشنواره ویژه خدمات و محصولات زرین کد پلاس' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_promo_banner( $this->get_settings_for_display() );
    }
}

/** 9. Project Steps Timeline Widget */
class Widget_Project_Steps extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_steps'; }
    public function get_title(): string { return '۹. مراحل ۴گانه انجام پروژه در زرین کد'; }
    public function get_icon(): string { return 'eicon-time-line'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات مراحل پروژه', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'مراحل انجام پروژه در زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_project_steps( $this->get_settings_for_display() );
    }
}

/** 10. Customer Testimonials Widget */
class Widget_Testimonials extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_testimonials'; }
    public function get_title(): string { return '۱۰. نظرات مشتریان و کارفرمایان زرین کد'; }
    public function get_icon(): string { return 'eicon-testimonial-carousel'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات نظرات مشتریان', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'نظرات مشتریان زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_testimonials( $this->get_settings_for_display() );
    }
}

/** 11. Blog Articles Grid Widget */
class Widget_Blog_Grid extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_blog'; }
    public function get_title(): string { return '۱۱. آخرین مقالات وبلاگ زرین کد'; }
    public function get_icon(): string { return 'eicon-posts-grid'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'تنظیمات وبلاگ', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control( 'title', [ 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'آخرین مقالات آموزشی زرین کد' ] );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_blog_grid( $this->get_settings_for_display() );
    }
}

/** 12. Inner Pages & Legal Compliance Section Widget */
class Widget_Page_Content_Section extends Abstract_Zarincode_Widget {
    public function get_name(): string { return 'zarincode_page_section'; }
    public function get_title(): string { return '۱۲. بخش‌بندی صفحات داخلی و حقوقی زرین کد'; }
    public function get_icon(): string { return 'eicon-document-file'; }
    protected function register_controls(): void {
        $this->start_controls_section( 'content_section', [ 'label' => 'انتخاب صفحه دمو / حقوقی', 'tab' => Controls_Manager::TAB_CONTENT ] );
        $this->add_control(
            'section_key',
            [
                'label'   => 'نوع محتوای صفحه المنتوری',
                'type'    => Controls_Manager::SELECT,
                'default' => 'about-page',
                'options' => [
                    'services-page' => 'صفحه جامع خدمات + جدول شرایط SLA',
                    'projects-page' => 'صفحه جامع نمونه‌کارها و پروژه‌ها',
                    'about-page'    => 'صفحه درباره ما (احسان نادری پناه)',
                    'contact-page'  => 'صفحه تماس با ما (09024561001)',
                    'terms'         => 'قوانین و شرایط + قوانین حاکم بر ایران',
                    'warranty'      => 'شرایط گارانتی و پشتیبانی فنی یک‌ساله',
                    'privacy'       => 'سیاست حفظ حریم خصوصی',
                    'refund'        => 'شرایط ضمانت ۱۰۰٪ بازگشت وجه',
                    'gdpr'          => 'قوانین حفاظت از داده‌ها (GDPR)',
                    'dmca'          => 'DMCA Copyright Policy (English)',
                ],
            ]
        );
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Renderer::render_page_section( $this->get_settings_for_display() );
    }
}
