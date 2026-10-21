<?php
/**
 * Central Elementor Widget & Shortcode Rendering Engine for Zarincode
 *
 * Combines Webinex capabilities with Crextio Sunshine Yellow (#FDD05B) + Dark Bento (#2A2C31) design methodology.
 *
 * @package Zarincode\Core\Widgets
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Renderer {

    private static ?Renderer $instance = null;

    public static function instance(): Renderer {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'zarincode_widget', [ $this, 'handle_shortcode' ] );
    }

    public function handle_shortcode( $atts ): string {
        $atts = shortcode_atts(
            [
                'type'    => 'hero',
                'section' => '',
                'count'   => 8,
            ],
            $atts,
            'zarincode_widget'
        );

        switch ( $atts['type'] ) {
            case 'header':
                return self::render_header_block( $atts );
            case 'footer':
                return self::render_footer_block( $atts );
            case 'hero':
                return self::render_hero( $atts );
            case 'brands':
                return self::render_brands_marquee( $atts );
            case 'services':
                return self::render_services_bento( $atts );
            case 'products':
                return self::render_products_marketplace( $atts );
            case 'portfolio':
                return self::render_portfolio_showcase( $atts );
            case 'why_us':
                return self::render_why_us_stats( $atts );
            case 'pricing':
                return self::render_pricing_tabs( $atts );
            case 'promo':
                return self::render_promo_banner( $atts );
            case 'steps':
                return self::render_project_steps( $atts );
            case 'testimonials':
                return self::render_testimonials( $atts );
            case 'blog':
                return self::render_blog_grid( $atts );
            case 'page_section':
                return self::render_page_section( [ 'section_key' => $atts['section'] ] );
            default:
                return '';
        }
    }

    /**
     * 1. HERO WIDGET (Webinex Agency Headline + Crextio Sunshine Bento Dashboard)
     */
    public static function render_hero( array $settings = [] ): string {
        $badge = $settings['badge'] ?? 'ZARINCODE - Your Trusted Digital Expert & Software Studio';
        $phone = '09024561001';
        ob_start();
        ?>
        <section class="zc-hero-section">
            <div class="zc-container">
                <div class="zc-hero-grid">
                    <!-- Main Agency Hero Card (Webinex Structure + Crextio Glass Panel) -->
                    <div class="zc-glass-panel zc-hero-main-card">
                        <div>
                            <span class="zc-badge-pill">
                                <i class="fa-solid fa-sparkles"></i> <?php echo esc_html( $badge ); ?>
                            </span>
                            <h1 class="zc-hero-title">
                                آژانـس طراحی سایـت، نرم‌افزار و سئو
                                <span class="zc-hero-inline-pill">
                                    <i class="fa-solid fa-code" style="color:var(--color-accent);"></i> زریــن کـد
                                </span>
                            </h1>
                            <p style="font-size:1.03rem;color:var(--zc-text-secondary);line-height:1.95;margin-bottom:1.25rem;">
                                در دنیای امروز، حضور آنلاین دیگر یک انتخاب نیست؛ یک ضرورت اساسی است. تیم مهندسی <strong>زرین کد (Zarincode.com)</strong> با مدیریت و کدنویسی مستقیم <strong>احسان نادری پناه</strong>، در حوزه‌های <strong>طراحی سایت اختصاصی، برنامه‌نویسی نرم‌افزار، سئو سایت، سئو شبکه‌های اجتماعی، تولید محتوا</strong> و ارائه <strong>افزونه‌ها، قالب‌های وردپرس و سورس‌کدهای آماده نرم‌افزار</strong> همراه شماست تا برندتان دیده شود، ماندگار شود و به فروش پایدار برسد.
                            </p>

                            <!-- Webinex 3 Signature Guarantee Pills -->
                            <div class="zc-hero-trust-list">
                                <span class="zc-trust-pill">
                                    <i class="fa-solid fa-circle-check"></i> مشاوره تخصصی رایگان
                                </span>
                                <span class="zc-trust-pill">
                                    <i class="fa-solid fa-circle-check"></i> ضمانت ۱۰۰٪ بازگشت وجه
                                </span>
                                <span class="zc-trust-pill">
                                    <i class="fa-solid fa-circle-check"></i> پشتیبانی و گارانتی یک‌ساله
                                </span>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display:flex;flex-wrap:wrap;gap:0.9rem;margin-bottom:2rem;">
                                <button type="button" class="zc-btn zc-btn-yellow" data-zc-modal-open="zc-consult-modal">
                                    <i class="fa-solid fa-headset"></i> درخواست مشاوره رایگان
                                </button>
                                <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="zc-btn zc-btn-dark">
                                    <i class="fa-solid fa-layer-group"></i> مشاهده خدمات زرین کد
                                </a>
                                <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="zc-btn zc-btn-glass">
                                    <i class="fa-solid fa-store"></i> فروشگاه سورس و افزونه آماده
                                </a>
                            </div>
                        </div>

                        <!-- Crextio Signature Segmented Progress Bar (from Crextio Dashboard) -->
                        <div class="zc-crextio-progress-wrap" style="padding-top:1.25rem;border-top:1px solid var(--zc-border-subtle);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                                <span style="font-size:0.84rem;font-weight:800;">توزیع پروژه‌ها و محصولات فعال استودیو زرین کد</span>
                                <span style="font-size:0.8rem;font-weight:700;color:var(--zc-text-muted);">تحویل ۱۰۰٪ تضمینی</span>
                            </div>
                            <div class="zc-crextio-progress-labels">
                                <div style="width:30%;">طراحی سایت ۳۰٪</div>
                                <div style="width:25%;">نرم‌افزار ۲۵٪</div>
                                <div style="width:25%;">سئو و محتوا ۲۵٪</div>
                                <div style="width:20%;">سورس آماده ۲۰٪</div>
                            </div>
                            <div class="zc-crextio-progress-bars">
                                <div class="zc-prog-seg seg-yellow" style="width:30%;">Web Design</div>
                                <div class="zc-prog-seg seg-dark" style="width:25%;">Software</div>
                                <div class="zc-prog-seg seg-striped progress-dashed" style="width:25%;">SEO &amp; Social</div>
                                <div class="zc-prog-seg seg-end" style="width:20%;">Store</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Crextio Interactive Bento Dashboard Card -->
                    <div style="display:flex;flex-direction:column;gap:1.25rem;">
                        <!-- Developer Profile & Live Tracker Card (Crextio Dark Card) -->
                        <div class="zc-dark-card" style="padding:2rem;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;position:relative;z-index:2;">
                                <div style="display:flex;align-items:center;gap:0.9rem;">
                                    <div style="width:3.6rem;height:3.6rem;border-radius:1.25rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;box-shadow:0 8px 20px rgba(253,208,91,0.35);">
                                        EN
                                    </div>
                                    <div>
                                        <div style="font-weight:900;font-size:1.12rem;color:#FFFFFF;">احسان نادری پناه</div>
                                        <div style="font-size:0.8rem;color:var(--color-accent);">Ehsan NaderiPanah • Lead Architect</div>
                                    </div>
                                </div>
                                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-prod-badge" dir="ltr" style="font-size:0.82rem;padding:0.4rem 0.85rem;">
                                    <i class="fa-solid fa-phone"></i> <?php echo esc_html( $phone ); ?>
                                </a>
                            </div>

                            <!-- Crextio Weekly Progress & Time Tracker Simulation -->
                            <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:1.35rem;padding:1.25rem;margin:1.35rem 0;position:relative;z-index:2;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.85rem;">
                                    <div>
                                        <div style="font-size:0.75rem;color:#9CA3AF;">پایش زنده پشتیبانی و توسعه (Crextio Tracker)</div>
                                        <div id="zc-live-timer" dir="ltr" style="font-size:1.65rem;font-weight:900;color:var(--color-accent);letter-spacing:0.04em;">02:35:00</div>
                                    </div>
                                    <span style="padding:0.35rem 0.85rem;border-radius:999px;background:rgba(16,185,129,0.2);color:#34D399;font-size:0.76rem;font-weight:800;">
                                        ● آنلاین و آماده پاسخگویی
                                    </span>
                                </div>
                                <!-- 7-Day Activity Bars -->
                                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:0.5rem;align-items:end;height:56px;padding-top:0.5rem;">
                                    <div style="text-align:center;"><div style="height:28px;background:rgba(255,255,255,0.2);border-radius:999px;"></div><small style="font-size:0.65rem;color:#9CA3AF;">ش</small></div>
                                    <div style="text-align:center;"><div style="height:40px;background:rgba(255,255,255,0.25);border-radius:999px;"></div><small style="font-size:0.65rem;color:#9CA3AF;">ی</small></div>
                                    <div style="text-align:center;"><div style="height:36px;background:rgba(255,255,255,0.25);border-radius:999px;"></div><small style="font-size:0.65rem;color:#9CA3AF;">د</small></div>
                                    <div style="text-align:center;"><div style="height:46px;background:var(--color-accent);border-radius:999px;"></div><small style="font-size:0.65rem;color:var(--color-accent);font-weight:800;">س</small></div>
                                    <div style="text-align:center;"><div style="height:42px;background:rgba(255,255,255,0.25);border-radius:999px;"></div><small style="font-size:0.65rem;color:#9CA3AF;">چ</small></div>
                                    <div style="text-align:center;"><div style="height:50px;background:var(--color-accent);border-radius:999px;"></div><small style="font-size:0.65rem;color:var(--color-accent);font-weight:800;">پ</small></div>
                                    <div style="text-align:center;"><div style="height:32px;background:rgba(255,255,255,0.2);border-radius:999px;"></div><small style="font-size:0.65rem;color:#9CA3AF;">ج</small></div>
                                </div>
                            </div>

                            <!-- 3 Quick KPI Stat Boxes -->
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem;position:relative;z-index:2;">
                                <div style="background:rgba(255,255,255,0.06);padding:0.85rem;border-radius:1.1rem;text-align:center;">
                                    <div style="font-size:1.35rem;font-weight:900;color:var(--color-accent);">+۴۲۰</div>
                                    <div style="font-size:0.73rem;color:#D1D5DB;">پروژه موفق</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.06);padding:0.85rem;border-radius:1.1rem;text-align:center;">
                                    <div style="font-size:1.35rem;font-weight:900;color:#FFFFFF;">۹۹.۸٪</div>
                                    <div style="font-size:0.73rem;color:#D1D5DB;">رضایت کارفرما</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.06);padding:0.85rem;border-radius:1.1rem;text-align:center;">
                                    <div style="font-size:1.35rem;font-weight:900;color:var(--color-accent);">+۳۵</div>
                                    <div style="font-size:0.73rem;color:#D1D5DB;">قالب و افزونه آماده</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Quick Service Pill Card -->
                        <div class="zc-yellow-card" style="padding:1.35rem 1.75rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                            <div>
                                <div style="font-weight:900;font-size:1.05rem;">برای یک طراحی، نرم‌افزار یا سئوی حرفه‌ای آماده‌اید؟</div>
                                <div style="font-size:0.82rem;opacity:0.85;">مشاوره مستقیم با احسان نادری پناه — Zarincode.com</div>
                            </div>
                            <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" class="zc-btn zc-btn-dark" style="padding:0.55rem 1.2rem;font-size:0.84rem;">
                                شروع همکاری <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 2. PARTNERS & TECH STACK MARQUEE WIDGET
     */
    public static function render_brands_marquee( array $settings = [] ): string {
        $brands = [
            [ 'icon' => 'fa-brands fa-wordpress', 'name' => 'WordPress & Elementor' ],
            [ 'icon' => 'fa-brands fa-php', 'name' => 'PHP 8.4 & Laravel' ],
            [ 'icon' => 'fa-brands fa-react', 'name' => 'React & Next.js' ],
            [ 'icon' => 'fa-brands fa-node-js', 'name' => 'Node.js & Microservices' ],
            [ 'icon' => 'fa-brands fa-google', 'name' => 'Google Technical SEO' ],
            [ 'icon' => 'fa-brands fa-instagram', 'name' => 'Social Media SEO' ],
            [ 'icon' => 'fa-solid fa-cart-shopping', 'name' => 'WooCommerce Pro' ],
            [ 'icon' => 'fa-solid fa-shield-halved', 'name' => 'ThemeForest & Zhaket Standard' ],
        ];
        ob_start();
        ?>
        <section class="zc-marquee-section">
            <div class="zc-container">
                <div class="zc-glass-panel zc-marquee-track-wrap">
                    <div style="text-align:center;margin-bottom:1rem;">
                        <span class="zc-badge-pill" style="margin-bottom:0.35rem;">PARTNERS &amp; TECHNOLOGIES</span>
                        <h2 style="font-size:1.25rem;font-weight:800;margin:0;">برندها و تکنولوژی‌هایی که به زرین کد اعتماد کردند!</h2>
                    </div>
                    <div class="zc-marquee-track">
                        <?php foreach ( $brands as $b ) : ?>
                            <div class="zc-partner-pill">
                                <i class="<?php echo esc_attr( $b['icon'] ); ?>" style="color:var(--zc-dark);font-size:1.15rem;"></i>
                                <span><?php echo esc_html( $b['name'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * ELEMENTOR HEADER BLOCK WIDGET
     */
    public static function render_header_block( array $settings = [] ): string {
        $phone = $settings['phone'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'contact_phone', '09024561001' ) : '09024561001' );
        $brand = $settings['brand_title'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'brand_name', 'زریــن کـد' ) : 'زریــن کـد' );
        $sub   = $settings['brand_subtitle'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'brand_en_name', 'Zarincode Studio' ) : 'Zarincode Studio' );
        $cta   = $settings['cta_text'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'header_cta_text', 'درخواست مشاوره' ) : 'درخواست مشاوره' );
        ob_start();
        ?>
        <div class="zc-container" style="padding:1rem 0;">
            <div class="zc-header-pill">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="zc-brand">
                    <span class="zc-brand-logo-box">Z</span>
                    <span class="zc-brand-text">
                        <span class="zc-brand-name"><?php echo esc_html( $brand ); ?></span>
                        <span class="zc-brand-sub"><?php echo esc_html( $sub ); ?></span>
                    </span>
                </a>
                <ul class="zc-nav-pill">
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">خدمات ما</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">فروشگاه سورس و افزونه</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>">نمونه‌کارها</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>">قوانین و گارانتی</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">درباره ما</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">تماس با ما</a></li>
                </ul>
                <div class="zc-header-actions">
                    <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-yellow"><?php echo esc_html( $cta ); ?></a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * ELEMENTOR FOOTER BLOCK WIDGET
     */
    public static function render_footer_block( array $settings = [] ): string {
        $heading = $settings['cta_heading'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'footer_cta_heading', 'برای تحول دیجیتال، طراحی سایت اختصاصی یا خرید سورس‌کدهای آماده آماده‌اید؟' ) : 'برای تحول دیجیتال آماده هستید؟' );
        $phone   = $settings['phone'] ?? ( function_exists( 'zarincode_get_option' ) ? zarincode_get_option( 'contact_phone', '09024561001' ) : '09024561001' );
        ob_start();
        ?>
        <div class="zc-container" style="padding:1.5rem 0;">
            <div class="zc-footer-bento">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <h3 style="margin:0;color:#fff;"><?php echo esc_html( $heading ); ?></h3>
                    <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-yellow">تماس مستقیم: <?php echo esc_html( $phone ); ?></a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 3. 6 CORE SERVICES BENTO GRID WIDGET
     */
    public static function render_services_bento( array $settings = [] ): string {
        $services = [
            [
                'num'      => '01 • DESIGN',
                'icon'     => 'fa-solid fa-laptop-code',
                'title'    => '۱. طراحی سایت اختصاصی',
                'en_title' => 'Website Design',
                'desc'     => 'طراحی و کدنویسی وب‌سایت‌های مدرن، فروشگاهی، شرکتی و استارتاپی با المنتور و کدنویسی اختصاصی، رابط کاربری Crextio، سرعت فوق‌العاده و ریسپانسیو کامل.',
                'price'    => '۱۰ تا ۵۵ میلیون تومان',
                'link'     => home_url( '/service/website-design/' ),
                'featured' => false,
            ],
            [
                'num'      => '02 • SOFTWARE',
                'icon'     => 'fa-solid fa-microchip',
                'title'    => '۲. برنامه‌نویسی نرم‌افزار',
                'en_title' => 'Software Development',
                'desc'     => 'توسعه و معماری نرم‌افزارهای اختصاصی تحت وب، سامانه‌های سازمانی (CRM/ERP)، وب‌اپلیکیشن‌های پیشرفته، پنل‌های مدیریتی و APIهای مقیاس‌پذیر.',
                'price'    => '۲۰ تا ۱۱۰ میلیون تومان',
                'link'     => home_url( '/service/software-development/' ),
                'featured' => true,
            ],
            [
                'num'      => '03 • GOOGLE SEO',
                'icon'     => 'fa-solid fa-chart-line',
                'title'    => '۳. سئو سایت تضمینی',
                'en_title' => 'Website SEO Service',
                'desc'     => 'بهبود رتبه وب‌سایت شما در صفحه اول گوگل با تکنیک‌های سئو تکنیکال، استراتژی کلمات کلیدی، لینک‌سازی اصولی و افزایش ترافیک و فروش ارگانیک.',
                'price'    => '۱۵ تا ۶۵ میلیون تومان',
                'link'     => home_url( '/service/website-seo/' ),
                'featured' => false,
            ],
            [
                'num'      => '04 • SOCIAL SEO',
                'icon'     => 'fa-solid fa-hashtag',
                'title'    => '۴. سئو شبکه‌های اجتماعی',
                'en_title' => 'Social Media SEO',
                'desc'     => 'بهینه‌سازی الگوریتمی پیج‌های اینستاگرام، لینکدین، یوتیوب و تلگرام؛ ورود تضمینی به اکسپلور و نتایج جستجوی شبکه‌های اجتماعی برای جذب مشتری.',
                'price'    => '۸ تا ۲۵ میلیون تومان',
                'link'     => home_url( '/service/social-media-seo/' ),
                'featured' => false,
            ],
            [
                'num'      => '05 • CONTENT',
                'icon'     => 'fa-solid fa-pen-nib',
                'title'    => '۵. تولید محتوا تخصصی',
                'en_title' => 'Content Creation',
                'desc'     => 'تولید محتوای متنی سئو شده، تقویم محتوایی، سناریونویسی ویدئو و ریلز، طراحی گرافیکی و مدیریت محتوایی مستمر برای رشد اعتبار برند شما.',
                'price'    => '۷ تا ۳۰ میلیون تومان',
                'link'     => home_url( '/service/content-creation/' ),
                'featured' => false,
            ],
            [
                'num'      => '06 • MARKETPLACE',
                'icon'     => 'fa-solid fa-box-open',
                'title'    => '۶. فروش محصولات آماده',
                'en_title' => 'Ready-Made Themes, Plugins & Source Codes',
                'desc'     => 'دسترسی و خرید آنی قالب‌های حرفه‌ای وردپرس، افزونه‌های اختصاصی و سورس‌کدهای آماده نرم‌افزار کدنویسی‌شده توسط احسان نادری پناه با گارانتی کامل.',
                'price'    => 'از ۸۹۰ هزار تا ۱۲ میلیون تومان',
                'link'     => home_url( '/service/ready-made-products/' ),
                'featured' => true,
            ],
        ];

        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:2.5rem;">
                    <div>
                        <span class="zc-badge-pill"><i class="fa-solid fa-bolt"></i> ZARINCODE SERVICES</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.4rem;">خدمات تخصصی آژانس <span class="zc-highlight">زرین کد</span></h2>
                        <p class="zc-section-desc">ترکیب خلاقیت، مهندسی نرم‌افزار، سئو داده‌محور و محصولات آماده برای رشد پایدار کسب‌وکار شما</p>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="zc-btn zc-btn-dark">
                        مشاهده همه خدمات <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>

                <div class="zc-services-grid">
                    <?php foreach ( $services as $srv ) : ?>
                        <div class="zc-white-card zc-service-card <?php echo $srv['featured'] ? 'is-featured' : ''; ?>">
                            <div>
                                <div class="zc-service-top">
                                    <div class="zc-service-icon">
                                        <i class="<?php echo esc_attr( $srv['icon'] ); ?>"></i>
                                    </div>
                                    <span class="zc-service-num" dir="ltr"><?php echo esc_html( $srv['num'] ); ?></span>
                                </div>
                                <h3 class="zc-service-title" style="margin-top:1.25rem;"><?php echo esc_html( $srv['title'] ); ?></h3>
                                <div style="font-size:0.78rem;opacity:0.65;margin-bottom:0.65rem;" dir="ltr"><?php echo esc_html( $srv['en_title'] ); ?></div>
                                <p class="zc-service-desc"><?php echo esc_html( $srv['desc'] ); ?></p>
                            </div>
                            <div class="zc-service-footer">
                                <span style="font-size:0.82rem;font-weight:700;">
                                    <i class="fa-solid fa-tag" style="color:var(--color-accent);"></i> <?php echo esc_html( $srv['price'] ); ?>
                                </span>
                                <a href="<?php echo esc_url( $srv['link'] ); ?>" class="zc-btn <?php echo $srv['featured'] ? 'zc-btn-yellow' : 'zc-btn-dark'; ?>" style="padding:0.45rem 1.1rem;font-size:0.82rem;">
                                    مشاهده و سفارش <i class="fa-solid fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 4. READY-MADE DIGITAL PRODUCTS MARKETPLACE WIDGET (Themes, Plugins & Software Source Codes)
     */
    public static function render_products_marketplace( array $settings = [] ): string {
        $products = [
            [
                'cat'     => 'wp-theme',
                'cat_lbl' => 'قالب وردپرس',
                'ver'     => 'v2.5.0',
                'icon'    => 'fa-brands fa-wordpress',
                'title'   => 'قالب وردپرس آژانسی و فروشگاهی زرین کد (Zarincode Pro)',
                'desc'    => 'قالب المنتور بیس با ۱۲ ویجت اختصاصی، پنل Redux و رنگ‌بندی زرد سانشاین Crextio.',
                'tags'    => [ 'Elementor', 'Redux', 'WooCommerce', 'PHP 8.4' ],
                'price'   => '۲,۴۹۰,۰۰۰',
                'slug'    => 'zarincode-pro-theme',
            ],
            [
                'cat'     => 'wp-plugin',
                'cat_lbl' => 'افزونه وردپرس',
                'ver'     => 'v3.2.1',
                'icon'    => 'fa-solid fa-plug-circle-bolt',
                'title'   => 'افزونه سئو هوشمند و ایندکس آنی گوگل (ZarinSEO Booster)',
                'desc'    => 'افزونه اختصاصی بهینه‌سازی اسکیما، اتصال به Indexing API گوگل و آنالیز لحظه‌ای کلمات کلیدی.',
                'tags'    => [ 'SEO API', 'Schema Pro', 'Auto-Index' ],
                'price'   => '۱,۲۹۰,۰۰۰',
                'slug'    => 'zarinseo-booster-plugin',
            ],
            [
                'cat'     => 'source-code',
                'cat_lbl' => 'سورس‌کد نرم‌افزار',
                'ver'     => 'v4.0.0',
                'icon'    => 'fa-solid fa-boxes-stacked',
                'title'   => 'سورس‌کد سامانه جامع CRM، فاکتور و مدیریت مشتریان زرین‌یار',
                'desc'    => 'سورس‌کد کامل PHP/Laravel + React با پنل حسابداری، تیکتینگ، پیامک و گزارش‌گیری پیشرفته.',
                'tags'    => [ 'Laravel 11', 'React', 'REST API', 'Full Source' ],
                'price'   => '۶,۹۰۰,۰۰۰',
                'slug'    => 'zarinyar-crm-source',
            ],
            [
                'cat'     => 'wp-plugin',
                'cat_lbl' => 'افزونه وردپرس',
                'ver'     => 'v2.1.0',
                'icon'    => 'fa-solid fa-mobile-screen-button',
                'title'   => 'افزونه ورود و ثبت‌نام پیامکی و پنل کاربری (ZarinDigits OTP)',
                'desc'    => 'سیستم احراز هویت دو مرحله‌ای پیامکی با پشتیبانی از ۲۰ پنل پیامک ایرانی و داشبورد مدرن ووکامرس.',
                'tags'    => [ 'OTP SMS', 'WooCommerce', 'AJAX Auth' ],
                'price'   => '۹۸۰,۰۰۰',
                'slug'    => 'zarindigits-otp-plugin',
            ],
            [
                'cat'     => 'wp-theme',
                'cat_lbl' => 'قالب وردپرس',
                'ver'     => 'v1.9.4',
                'icon'    => 'fa-solid fa-bag-shopping',
                'title'   => 'قالب فروشگاهی سوپرمارکت و کالای دیجیتال (ZarinMart)',
                'desc'    => 'قالب فروشگاهی ووکامرس با فیلتر ایجکس، پیشنهاد شگفت‌انگیز تایمردار و مقایسه پیشرفته محصولات.',
                'tags'    => [ 'WooCommerce', 'Elementor', 'AJAX Filter' ],
                'price'   => '۲,۱۹۰,۰۰۰',
                'slug'    => 'zarinmart-shop-theme',
            ],
            [
                'cat'     => 'source-code',
                'cat_lbl' => 'سورس‌کد نرم‌افزار',
                'ver'     => 'v2.4.0',
                'icon'    => 'fa-brands fa-instagram',
                'title'   => 'سورس‌کد پلتفرم آنالیز و سئو شبکه‌های اجتماعی (ZarinSocial AI)',
                'desc'    => 'نرم‌افزار تحت وب تحلیل هشتگ، زمان‌بندی انتشار محتوا و بهینه‌سازی سئو اینستاگرام و لینکدین.',
                'tags'    => [ 'Node.js', 'AI Content', 'Social SEO' ],
                'price'   => '۵,۴۰۰,۰۰۰',
                'slug'    => 'zarinsocial-ai-source',
            ],
            [
                'cat'     => 'wp-plugin',
                'cat_lbl' => 'افزونه وردپرس',
                'ver'     => 'v1.8.0',
                'icon'    => 'fa-solid fa-wand-magic-sparkles',
                'title'   => 'افزونه تولید محتوای خودکار با هوش مصنوعی (ZarinContent AI)',
                'desc'    => 'تولید مقالات سئو شده فارسی با ساختار هدینگ استاندارد، جدول محتوا و تصویر شاخص خودکار در وردپرس.',
                'tags'    => [ 'AI Writer', 'Auto SEO', 'WordPress' ],
                'price'   => '۱,۴۵۰,۰۰۰',
                'slug'    => 'zarincontent-ai-plugin',
            ],
            [
                'cat'     => 'source-code',
                'cat_lbl' => 'سورس‌کد نرم‌افزار',
                'ver'     => 'v3.0.2',
                'icon'    => 'fa-solid fa-graduation-cap',
                'title'   => 'سورس‌کد سامانه آموزش آنلاین و برگزاری وبینار (ZarinLMS Pro)',
                'desc'    => 'پلتفرم جامع فروش دوره، آزمون آنلاین، صدور گواهینامه PDF و کیف پول دیجیتال با سورس باز.',
                'tags'    => [ 'Full-Stack', 'LMS', 'Payment Gateway' ],
                'price'   => '۷,۸۰۰,۰۰۰',
                'slug'    => 'zarinlms-pro-source',
            ],
        ];

        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:1.75rem;">
                    <div>
                        <span class="zc-badge-pill"><i class="fa-solid fa-code"></i> READY-MADE DIGITAL PRODUCTS</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.4rem;">فروشگاه محصولات آماده <span class="zc-highlight">زرین کد</span></h2>
                        <p class="zc-section-desc">قالب‌های وردپرس، افزونه‌های حرفه‌ای و سورس‌کدهای آماده نرم‌افزار — کدنویسی‌شده توسط احسان نادری پناه با دانلود آنی</p>
                    </div>
                    <div class="zc-filter-tabs" data-zc-tabs="#zc-products-grid-target" style="margin-bottom:0;">
                        <button type="button" class="zc-filter-tab is-active" data-filter="all">همه محصولات (۸)</button>
                        <button type="button" class="zc-filter-tab" data-filter="wp-theme">قالب وردپرس</button>
                        <button type="button" class="zc-filter-tab" data-filter="wp-plugin">افزونه وردپرس</button>
                        <button type="button" class="zc-filter-tab" data-filter="source-code">سورس‌کد نرم‌افزار</button>
                    </div>
                </div>

                <div id="zc-products-grid-target" class="zc-products-grid">
                    <?php foreach ( $products as $prod ) : ?>
                        <div class="zc-white-card zc-product-card" data-category="<?php echo esc_attr( $prod['cat'] ); ?>">
                            <div>
                                <div class="zc-product-thumb">
                                    <div class="zc-product-badges">
                                        <span class="zc-prod-badge"><?php echo esc_html( $prod['cat_lbl'] ); ?></span>
                                        <span class="zc-prod-ver" dir="ltr"><?php echo esc_html( $prod['ver'] ); ?></span>
                                    </div>
                                    <div style="text-align:center;margin-top:1.25rem;">
                                        <div style="width:3.5rem;height:3.5rem;border-radius:1rem;background:rgba(253,208,91,0.18);color:var(--color-accent);display:inline-flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:0.5rem;">
                                            <i class="<?php echo esc_attr( $prod['icon'] ); ?>"></i>
                                        </div>
                                        <div style="font-size:0.75rem;color:#9CA3AF;">By Ehsan NaderiPanah</div>
                                    </div>
                                </div>

                                <h3 style="font-size:1.03rem;font-weight:800;margin:1rem 0 0.45rem;line-height:1.5;">
                                    <?php echo esc_html( $prod['title'] ); ?>
                                </h3>
                                <p style="font-size:0.84rem;color:var(--zc-text-secondary);margin:0 0 0.85rem;">
                                    <?php echo esc_html( $prod['desc'] ); ?>
                                </p>
                                <div class="zc-product-meta-tags">
                                    <?php foreach ( $prod['tags'] as $t ) : ?>
                                        <span class="zc-meta-tag" dir="ltr"><?php echo esc_html( $t ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="zc-product-price-row">
                                <div>
                                    <span class="zc-price-amount"><?php echo esc_html( $prod['price'] ); ?></span>
                                    <span class="zc-price-unit">تومان</span>
                                </div>
                                <div style="display:flex;gap:0.4rem;">
                                    <button type="button" class="zc-btn zc-btn-yellow" style="padding:0.45rem 0.95rem;font-size:0.8rem;" data-zc-modal-open="zc-consult-modal">
                                        <i class="fa-solid fa-cart-plus"></i> خرید آنی
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 5. PORTFOLIO / PROJECTS SHOWCASE WIDGET (Webinex Structure + Crextio Sunshine Cards)
     */
    public static function render_portfolio_showcase( array $settings = [] ): string {
        $projects = [
            [
                'cat'      => 'ecommerce web',
                'cat_lbl'  => '* فروشگاهی و مارکت‌پلیس *',
                'title'    => 'طراحی و کدنویسی مارکت‌پلیس تکنولوژی و دیجیتال پلاس',
                'agency'   => 'زرین کد',
                'date'     => 'فروردین ۱۴۰۵',
                'duration' => '۲۵ روز کاری',
                'icon'     => 'fa-solid fa-cart-flatbed',
                'metrics'  => '+۳۲۰٪ رشد فروش آنلاین',
            ],
            [
                'cat'      => 'software',
                'cat_lbl'  => '* نرم‌افزار اختصاصی *',
                'title'    => 'توسعه سامانه ابری مدیریت منابع انسانی و حقوق (مشابه Crextio)',
                'agency'   => 'زرین کد',
                'date'     => 'اردیبهشت ۱۴۰۵',
                'duration' => '۴۰ روز کاری',
                'icon'     => 'fa-solid fa-network-wired',
                'metrics'  => '۱۲,۰۰۰ کاربر فعال روزانه',
            ],
            [
                'cat'      => 'seo',
                'cat_lbl'  => '* سئو سایت و شبکه‌های اجتماعی *',
                'title'    => 'سئو جامع و رشد اکسپلور برند تجهیزات پزشکی طب‌گستر',
                'agency'   => 'زرین کد',
                'date'     => 'خرداد ۱۴۰۵',
                'duration' => '۹۰ روز کاری',
                'icon'     => 'fa-solid fa-magnifying-glass-chart',
                'metrics'  => 'رتبه ۱ گوگل در ۴۵ کلیدواژه',
            ],
            [
                'cat'      => 'web',
                'cat_lbl'  => '* شرکتی و استارتاپی *',
                'title'    => 'طراحی سایت پلتفرم فروش هاست ابری و سرور اختصاصی',
                'agency'   => 'زرین کد',
                'date'     => 'تیر ۱۴۰۵',
                'duration' => '۳۰ روز کاری',
                'icon'     => 'fa-solid fa-server',
                'metrics'  => 'سرعت لود ۰.۸ ثانیه (GTmetrix A)',
            ],
            [
                'cat'      => 'ecommerce web',
                'cat_lbl'  => '* فروشگاهی مد و پوشاک *',
                'title'    => 'طراحی فروشگاه اینترنتی استایل‌مد با سیستم ست‌ساز هوشمند',
                'agency'   => 'زرین کد',
                'date'     => 'مرداد ۱۴۰۵',
                'duration' => '۲۰ روز کاری',
                'icon'     => 'fa-solid fa-shirt',
                'metrics'  => 'نرخ تبدیل ۴.۸٪ فروش',
            ],
            [
                'cat'      => 'content seo',
                'cat_lbl'  => '* تولید محتوا و مجله‌ای *',
                'title'    => 'طراحی پرتال خبری، مجله دیجیتال و استراتژی محتوای فین‌تک',
                'agency'   => 'زرین کد',
                'date'     => 'شهریور ۱۴۰۵',
                'duration' => '۱۸ روز کاری',
                'icon'     => 'fa-solid fa-newspaper',
                'metrics'  => '+۱۸۰ هزار بازدید ماهانه',
            ],
        ];

        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:2rem;">
                    <div>
                        <span class="zc-badge-pill"><i class="fa-solid fa-briefcase"></i> PORTFOLIO &amp; CASE STUDIES</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.4rem;">برخی از <span class="zc-highlight">نمونه‌کارهای موفق</span> زرین کد</h2>
                        <p class="zc-section-desc">نگاهی به پروژه‌های طراحی سایت، نرم‌افزار، سئو و تولید محتوا اجرا شده توسط تیم احسان نادری پناه</p>
                    </div>
                    <div class="zc-filter-tabs" data-zc-tabs="#zc-portfolio-grid-target" style="margin-bottom:0;">
                        <button type="button" class="zc-filter-tab is-active" data-filter="all">همه نمونه‌کارها</button>
                        <button type="button" class="zc-filter-tab" data-filter="web">طراحی سایت و فروشگاهی</button>
                        <button type="button" class="zc-filter-tab" data-filter="software">نرم‌افزار اختصاصی</button>
                        <button type="button" class="zc-filter-tab" data-filter="seo">سئو و محتوا</button>
                    </div>
                </div>

                <div id="zc-portfolio-grid-target" class="zc-portfolio-grid">
                    <?php foreach ( $projects as $prj ) : ?>
                        <div class="zc-white-card zc-project-card" data-category="<?php echo esc_attr( $prj['cat'] ); ?>">
                            <div class="zc-project-visual">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span class="zc-prod-badge"><?php echo esc_html( $prj['cat_lbl'] ); ?></span>
                                    <span class="zc-prod-ver"><i class="fa-solid fa-chart-simple"></i> <?php echo esc_html( $prj['metrics'] ); ?></span>
                                </div>
                                <div style="margin-top:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                                    <h3 style="font-size:1.1rem;font-weight:800;margin:0;color:#FFFFFF;line-height:1.55;">
                                        <?php echo esc_html( $prj['title'] ); ?>
                                    </h3>
                                    <div style="width:3rem;height:3rem;border-radius:0.9rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.25rem;">
                                        <i class="<?php echo esc_attr( $prj['icon'] ); ?>"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="zc-project-info-pills">
                                <span><i class="fa-solid fa-building" style="color:var(--color-accent);"></i> مجری: <?php echo esc_html( $prj['agency'] ); ?></span>
                                <span><i class="fa-regular fa-calendar" style="color:var(--color-accent);"></i> <?php echo esc_html( $prj['date'] ); ?></span>
                                <span><i class="fa-regular fa-clock" style="color:var(--color-accent);"></i> <?php echo esc_html( $prj['duration'] ); ?></span>
                            </div>
                            <div class="zc-service-footer" style="padding-top:0.75rem;">
                                <span style="font-size:0.8rem;color:var(--zc-text-muted);font-weight:600;">تضمین کیفیت و پشتیبانی فعال</span>
                                <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>" class="zc-btn zc-btn-glass" style="padding:0.4rem 1rem;font-size:0.8rem;">
                                    مشاهده و بررسی پروژه <i class="fa-solid fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 6. WHY US & STATS COUNTERS WIDGET (Webinex ? WHY US + Crextio Bento Layout)
     */
    public static function render_why_us_stats( array $settings = [] ): string {
        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div class="zc-glass-panel" style="padding:2.75rem;">
                    <div class="zc-hero-grid" style="align-items:center;">
                        <div>
                            <span class="zc-badge-pill">? WHY ZARINCODE</span>
                            <h2 class="zc-section-title">
                                چرا <span class="zc-hero-inline-pill" style="font-size:0.78em;">زرین کد</span> ؟ چه چیزی ما را متمایز می‌کند؟
                            </h2>
                            <p style="color:var(--zc-text-secondary);font-size:0.98rem;line-height:1.95;margin-bottom:1.5rem;">
                                ما فراتر از یک تیم طراحی سایت هستیم؛ ما هم‌مسیر مهندسی و رشد کسب‌وکار شما در دنیای دیجیتالیم. تمرکز ما در <strong>زرین کد (با مدیریت احسان نادری پناه)</strong> نه فقط بر زیبایی ظاهری، بلکه بر معماری کدنویسی تمیز، امنیت، سرعت لود، سئوی تکنیکال و نرخ تبدیل واقعی فروش است. از طراحی سایت و نرم‌افزار اختصاصی تا سئو شبکه‌های اجتماعی و ارائه سورس‌کدهای آماده، همه‌چیز در خدمت موفقیت شماست!
                            </p>
                            <div style="display:flex;flex-wrap:wrap;gap:0.9rem;">
                                <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="zc-btn zc-btn-yellow">
                                    <i class="fa-solid fa-award"></i> درباره استودیو زرین کد
                                </a>
                                <button type="button" class="zc-btn zc-btn-dark" data-zc-modal-open="zc-consult-modal">
                                    <i class="fa-solid fa-phone-volume"></i> تماس مستقیم: 09024561001
                                </button>
                            </div>
                        </div>

                        <!-- 4 Differentiator Bento Boxes + 3 Webinex Counters -->
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;">
                            <div class="zc-white-card" style="padding:1.35rem;">
                                <span class="zc-badge-pill" style="margin-bottom:0.5rem;">01</span>
                                <h4 style="margin:0 0 0.35rem;font-weight:800;">کدنویسی استاندارد جهانی</h4>
                                <p style="margin:0;font-size:0.82rem;color:var(--zc-text-secondary);">معماری شیءگرا PHP، استانداردهای ژاکت و تم‌فارست و سرعت لود زیر ۱ ثانیه</p>
                            </div>
                            <div class="zc-dark-card" style="padding:1.35rem;">
                                <span class="zc-badge-pill" style="margin-bottom:0.5rem;">02</span>
                                <h4 style="margin:0 0 0.35rem;font-weight:800;color:#fff;">تسلط بر سئو و الگوریتم‌ها</h4>
                                <p style="margin:0;font-size:0.82rem;color:#D1D5DB;">سئو تکنیکال گوگل + سئو شبکه‌های اجتماعی (اینستاگرام و لینکدین)</p>
                            </div>
                            <div class="zc-white-card" style="padding:1.35rem;">
                                <span class="zc-badge-pill" style="margin-bottom:0.5rem;">03</span>
                                <h4 style="margin:0 0 0.35rem;font-weight:800;">ضمانت رسمی در قرارداد</h4>
                                <p style="margin:0;font-size:0.82rem;color:var(--zc-text-secondary);">گارانتی ۱۰۰٪ بازگشت وجه، تحویل به‌موقع و ۱ سال پشتیبانی فنی رایگان</p>
                            </div>
                            <div class="zc-yellow-card" style="padding:1.35rem;">
                                <span class="zc-badge-pill zc-badge-dark" style="margin-bottom:0.5rem;">04</span>
                                <h4 style="margin:0 0 0.35rem;font-weight:900;">۹۹.۸٪ رضایت مشتریان</h4>
                                <p style="margin:0;font-size:0.82rem;font-weight:600;">بیش از ۱۸۵۰ روز همراهی مستمر و ۴۲۰+ پروژه و محصول موفق</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 7. TABBED PRICING PLANS WIDGET (Webinex Pricing + Crextio Sunshine Yellow Featured Card)
     */
    public static function render_pricing_tabs( array $settings = [] ): string {
        $pricing_groups = [
            'web-design' => [
                [
                    'name'     => 'پلن نقره‌ای (طراحی سایت)',
                    'delivery' => 'تحویل از ۱۵ تا ۲۵ روز کاری',
                    'price'    => '۱۰ تا ۱۵ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'طراحی المنتوری مدرن با استایل Crextio',
                        'شامل ۱۰ صفحه کاربردی و استاندارد',
                        '۳ بار ویرایش و بازبینی رایگان',
                        'هاست پرسرعت ۵ گیگ یک‌ساله رایگان',
                        'ثبت دامنه .ir رایگان به نام کارفرما',
                        'امنیت کامل و سئو تکنیکال پایه',
                        '۶ ماه گارانتی و پشتیبانی فنی رایگان',
                    ],
                ],
                [
                    'name'     => 'پلن طلایی (فروشگاهی و شرکتی)',
                    'delivery' => 'تحویل از ۲۵ تا ۳۵ روز کاری',
                    'price'    => '۲۰ تا ۳۰ میلیون تومان',
                    'popular'  => true,
                    'features' => [
                        'طراحی اختصاصی UI/UX و ویجت‌های المنتوری',
                        'شامل ۱۵ صفحه + فروشگاه کامل ووکامرس',
                        'اتصال به درگاه بانکی، پیامک و فاکتور رسمی',
                        'هاست VIP هشت گیگ + دامنه .com و .ir رایگان',
                        'بهینه‌سازی سرعت (نمره بالای ۹۵ در گوگل)',
                        'سئو داخلی و ساختار اسکیما پیشرفته',
                        '۱ سال گارانتی و پشتیبانی VIP رایگان',
                    ],
                ],
                [
                    'name'     => 'پلن الماسی (فول اختصاصی)',
                    'delivery' => 'تحویل از ۳۵ تا ۵۰ روز کاری',
                    'price'    => '۳۵ تا ۶۵ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'کدنویسی ۱۰۰٪ اختصاصی قالب و افزونه‌های مورد نیاز',
                        'طراحی تمامی صفحات بدون محدودیت',
                        'مگامنوساز، فیلتر ایجکس و پنل کاربری اختصاصی',
                        'سرور ابری اختصاصی یک‌ساله + دامنه بین‌المللی',
                        'پکیج امنیت سازمانی و ضد حملات DDoS',
                        'استراتژی کامل سئو و ۵ مقاله تخصصی رایگان',
                        '۱ سال گارانتی طلایی + آموزش کامل ویدئویی',
                    ],
                ],
            ],
            'software-dev' => [
                [
                    'name'     => 'پلن MVP استارتاپی',
                    'delivery' => 'تحویل از ۳۰ تا ۴۵ روز کاری',
                    'price'    => '۲۵ تا ۴۰ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'تحلیل سیستم و طراحی دیتابیس استاندارد',
                        'توسعه بک‌اند با PHP/Laravel یا Node.js',
                        'داشبورد مدیریتی مدرن با رابط Crextio',
                        'احراز هویت پیامکی و سطوح دسترسی کاربران',
                        'مستندسازی کامل API و سورس‌کد باز',
                        '۶ ماه گارانتی رفع باگ رایگان',
                    ],
                ],
                [
                    'name'     => 'پلن سامانه سازمانی و پلتفرم',
                    'delivery' => 'تحویل از ۴۵ تا ۶۰ روز کاری',
                    'price'    => '۴۵ تا ۸۰ میلیون تومان',
                    'popular'  => true,
                    'features' => [
                        'معماری ماژولار و مقیاس‌پذیر (Micro-ready)',
                        'رابط کاربری SPA با React / Next.js',
                        'سیستم مالی، کیف پول، فاکتور و گزارش‌گیری',
                        'پنل مدیریت پیشرفته + وب‌سرویس موبایل',
                        'تست‌های امنیتی (PenTest) و بهینه‌سازی کوئری‌ها',
                        '۱ سال گارانتی و پشتیبانی مستقیم احسان نادری پناه',
                    ],
                ],
                [
                    'name'     => 'پلن سوپراپ و اینترپرایز',
                    'delivery' => 'تحویل از ۶۰ تا ۹۰ روز کاری',
                    'price'    => '۸۵ تا ۱۵۰ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'توسعه وب‌اپلیکیشن + اپلیکیشن موبایل (iOS/Android)',
                        'زیرساخت ابری Docker و CI/CD خودکار',
                        'هوش مصنوعی اختصاصی و پردازش بلادرنگ',
                        'قرارداد رسمی NDA و انتقال ۱۰۰٪ مالکیت سورس‌کد',
                        'پشتیبانی فنی اختصاصی ۲۴/۷ یک‌ساله',
                        'آموزش حضوری یا آنلاین تیم فنی کارفرما',
                    ],
                ],
            ],
            'seo-social' => [
                [
                    'name'     => 'پلن اقتصادی (سئو و شبکه اجتماعی)',
                    'delivery' => 'دوره تمدید: ۳۰ روز کاری',
                    'price'    => '۱۲ تا ۱۸ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'کیورد ریسرچ و تدوین استراتژی سئو سایت',
                        'رفع خطاهای تکنیکال Search Console',
                        'بهینه‌سازی سرعت لود و Core Web Vitals',
                        'بهینه‌سازی بایو، هایلایت و سئو پیج اینستاگرام',
                        'تولید ۸ محتوای سئو شده در ماه',
                        'گزارش‌دهی شفاف ماهانه',
                    ],
                ],
                [
                    'name'     => 'پلن استاندارد (رشد گوگل + اکسپلور)',
                    'delivery' => 'دوره تمدید: ۳۰ روز کاری',
                    'price'    => '۲۲ تا ۳۵ میلیون تومان',
                    'popular'  => true,
                    'features' => [
                        'سئو جامع سایت برای کلمات کلیدی پول‌ساز',
                        'سئو الگوریتمی اینستاگرام، لینکدین و یوتیوب',
                        'لینک‌سازی خارجی و رپورتاژ آگهی هدفمند',
                        'تولید ۱۶ مقاله تخصصی + سناریوی ریلز اکسپلوری',
                        'بهینه‌سازی نرخ تبدیل (CRO) صفحات فرود',
                        'ضمانت رشد ورودی ارگانیک در قرارداد',
                    ],
                ],
                [
                    'name'     => 'پلن اختصاصی (تسلط کامل بر بازار)',
                    'delivery' => 'دوره تمدید: ۶۰ روز کاری',
                    'price'    => '۴۵ تا ۷۵ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'رقابت روی سخت‌ترین کلمات کلیدی صنعتی و فروشگاهی',
                        'مدیریت کامل سئو سایت + تمامی شبکه‌های اجتماعی',
                        'کمپین‌های روابط عمومی و رپورتاژ در رسانه‌های برتر',
                        'تولید محتوای روزانه متنی، گرافیکی و ویدئویی',
                        'داشبورد اختصاصی رصد لحظه‌ای رتبه‌ها',
                        'جلسات استراتژی هفتگی با احسان نادری پناه',
                    ],
                ],
            ],
            'content' => [
                [
                    'name'     => 'پکیج محتوای متنی و سئو وبلاگ',
                    'delivery' => 'تحویل ماهانه (۲۰ مقاله)',
                    'price'    => '۸ تا ۱۲ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        '۲۰ مقاله تخصصی ۱۵۰۰ تا ۲۵۰۰ کلمه‌ای',
                        'رعایت کامل اصول سئو (Yoast / RankMath سبز)',
                        'طراحی تصویر شاخص اختصاصی برای هر مقاله',
                        'لینک‌سازی داخلی هوشمند و اسکیما FAQ',
                        'تضمین ۱۰۰٪ یونیک بودن و عدم کپی',
                    ],
                ],
                [
                    'name'     => 'پکیج جامع محتوای سایت و اینستاگرام',
                    'delivery' => 'تحویل ماهانه کامل',
                    'price'    => '۱۶ تا ۲۵ میلیون تومان',
                    'popular'  => true,
                    'features' => [
                        'تقویم محتوایی ۳۰ روزه هدفمند',
                        '۱۲ مقاله تخصصی سئو شده برای سایت',
                        '۱۲ پست و ریلز حرفه‌ای اینستاگرام + کاور اختصاصی',
                        'کپشن‌نویسی سئو شده با هشتگ‌های ترند',
                        'طراحی ۴ بنر اسلایدر صفحه اصلی سایت',
                    ],
                ],
                [
                    'name'     => 'پکیج برندینگ و تولید محتوای VIP',
                    'delivery' => 'مدیریت کامل محتوایی برند',
                    'price'    => '۳۰ تا ۴۸ میلیون تومان',
                    'popular'  => false,
                    'features' => [
                        'تولید محتوای کامل سایت، اینستاگرام، لینکدین و یوتیوب',
                        'نگارش صفحات محصول و لندینگ‌پیج‌های فروشنده‌ساز',
                        'تدوین ویدئوهای معرفی محصول و موشن‌گرافیک',
                        'مدیریت و بارگذاری کامل در وردپرس و شبکه‌ها',
                        'پشتیبانی و ویرایش نامحدود محتوایی',
                    ],
                ],
            ],
        ];

        ob_start();
        ?>
        <section class="zc-pricing-section" style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div class="zc-section-head text-center">
                    <span class="zc-badge-pill"><i class="fa-solid fa-tags"></i> TRANSPARENT PRICING</span>
                    <h2 class="zc-section-title">تعرفه خدمات تخصصی <span class="zc-highlight">زرین کد</span></h2>
                    <p class="zc-section-desc">پلن‌های شفاف، منعطف و همراه با ضمانت رسمی بازگشت وجه و پشتیبانی یک‌ساله</p>

                    <div class="zc-filter-tabs" data-zc-pricing-tabs style="margin:1.75rem auto 0;">
                        <button type="button" class="zc-filter-tab is-active" data-pricing-target="web-design">طراحی سایت</button>
                        <button type="button" class="zc-filter-tab" data-pricing-target="software-dev">برنامه‌نویسی نرم‌افزار</button>
                        <button type="button" class="zc-filter-tab" data-pricing-target="seo-social">سئو سایت و شبکه‌های اجتماعی</button>
                        <button type="button" class="zc-filter-tab" data-pricing-target="content">تولید محتوا</button>
                    </div>
                </div>

                <?php foreach ( $pricing_groups as $group_key => $plans ) : ?>
                    <div class="zc-pricing-grid" data-pricing-panel="<?php echo esc_attr( $group_key ); ?>" style="display:<?php echo 'web-design' === $group_key ? 'grid' : 'none'; ?>;">
                        <?php foreach ( $plans as $plan ) : ?>
                            <div class="zc-white-card zc-pricing-card <?php echo $plan['popular'] ? 'is-popular' : ''; ?>">
                                <?php if ( $plan['popular'] ) : ?>
                                    <span class="zc-popular-ribbon"><i class="fa-solid fa-crown"></i> پیشنهادی زرین کد</span>
                                <?php endif; ?>
                                <div>
                                    <h3 style="font-size:1.25rem;font-weight:900;margin:0 0 0.4rem;"><?php echo esc_html( $plan['name'] ); ?></h3>
                                    <div style="font-size:0.82rem;opacity:0.75;margin-bottom:1.25rem;">
                                        <i class="fa-regular fa-clock" style="color:var(--color-accent);"></i> <?php echo esc_html( $plan['delivery'] ); ?>
                                    </div>
                                    <div style="padding:1rem 1.25rem;border-radius:1.15rem;background:<?php echo $plan['popular'] ? 'rgba(255,255,255,0.08)' : 'var(--color-accent-soft)'; ?>;margin-bottom:1.25rem;">
                                        <div style="font-size:0.75rem;opacity:0.8;">بازه سرمایه‌گذاری:</div>
                                        <div style="font-size:1.3rem;font-weight:900;color:<?php echo $plan['popular'] ? 'var(--color-accent)' : 'var(--zc-text-main)'; ?>;">
                                            <?php echo esc_html( $plan['price'] ); ?>
                                        </div>
                                    </div>
                                    <ul class="zc-pricing-features">
                                        <?php foreach ( $plan['features'] as $feat ) : ?>
                                            <li><i class="fa-solid fa-check"></i> <span><?php echo esc_html( $feat ); ?></span></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <button type="button" class="zc-btn <?php echo $plan['popular'] ? 'zc-btn-yellow' : 'zc-btn-dark'; ?>" style="width:100%;" data-zc-modal-open="zc-consult-modal">
                                    مشاوره رایگان و ثبت سفارش <i class="fa-solid fa-arrow-left"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 8. SPECIAL PROMO BANNER WIDGET (Webinex 50% Promo + Crextio Sunshine Design)
     */
    public static function render_promo_banner( array $settings = [] ): string {
        ob_start();
        ?>
        <section style="padding:1.5rem 0 3.5rem;">
            <div class="zc-container">
                <div class="zc-dark-card" style="padding:2.75rem;background:linear-gradient(135deg, #2A2C31 0%, #17181C 100%);border:2px solid var(--color-accent);">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:2rem;position:relative;z-index:2;">
                        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                            <div style="width:4.8rem;height:4.8rem;border-radius:1.5rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:2.2rem;box-shadow:0 12px 30px rgba(253,208,91,0.4);">
                                <i class="fa-solid fa-gift"></i>
                            </div>
                            <div>
                                <span class="zc-badge-pill" style="margin-bottom:0.4rem;">ZARINCODE PLUS CAMPAIGN</span>
                                <h3 style="font-size:clamp(1.35rem,2.4vw,2rem);font-weight:900;margin:0 0 0.35rem;color:#FFFFFF;">
                                    جشنواره ویژه خدمات سئو، تولید محتوا و پکیج سورس‌کدهای آماده زرین کد پلاس
                                </h3>
                                <p style="margin:0;color:#D1D5DB;font-size:0.95rem;">
                                    با سفارش همزمان طراحی سایت و سئو یا خرید باندل افزونه‌ها، تا <strong style="color:var(--color-accent);">۵۰٪ تخفیف ویژه</strong> روی پکیج سئو شبکه‌های اجتماعی و تولید محتوا دریافت کنید!
                                </p>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.85rem;flex-wrap:wrap;">
                            <button type="button" class="zc-btn zc-btn-yellow" data-zc-modal-open="zc-consult-modal">
                                <i class="fa-solid fa-fire"></i> دریافت کد تخفیف و مشاوره
                            </button>
                            <a href="tel:09024561001" class="zc-btn" style="background:rgba(255,255,255,0.1);color:#fff;">
                                <i class="fa-solid fa-phone"></i> 09024561001
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 9. PROJECT STEPS TIMELINE WIDGET (Webinex 4 Steps + Crextio Bento Cards)
     */
    public static function render_project_steps( array $settings = [] ): string {
        $steps = [
            [
                'num'   => '۰۱',
                'title' => 'مشاوره رایگان و نیازسنجی دقیق',
                'desc'  => 'بررسی دقیق اهداف کسب‌وکار شما توسط احسان نادری پناه، آنالیز رقبا و ارائه بهترین راهکار فنی و سئو.',
            ],
            [
                'num'   => '۰۲',
                'title' => 'برنامه‌ریزی و عقد قرارداد شفاف',
                'desc'  => 'تدوین معماری پروژه، زمان‌بندی دقیق، تعیین تعرفه شفاف و عقد قرارداد رسمی همراه با ضمانت بازگشت وجه.',
            ],
            [
                'num'   => '۰۳',
                'title' => 'طراحی UI/UX، کدنویسی و اجرا',
                'desc'  => 'طراحی اختصاصی با متد Crextio، کدنویسی استاندارد PHP/JS، بهینه‌سازی سرعت و اجرای سئو تکنیکال و محتوایی.',
            ],
            [
                'num'   => '۰۴',
                'title' => 'تحویل نهایی، آموزش و گارانتی',
                'desc'  => 'ارائه کامل فایل‌ها و دسترسی‌ها، آموزش ویدئویی مدیریت سایت و شروع ۱ سال گارانتی و پشتیبانی فنی رایگان.',
            ],
        ];
        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div class="zc-section-head text-center">
                    <span class="zc-badge-pill">PROJECT STEPS</span>
                    <h2 class="zc-section-title">مراحل انجام پروژه در <span class="zc-highlight">زرین کد</span></h2>
                    <p class="zc-section-desc">مسیر شفاف، مهندسی‌شده و بدون ریسک از ایده تا تحویل نهایی و پشتیبانی مستمر</p>
                </div>
                <div class="zc-steps-grid">
                    <?php foreach ( $steps as $step ) : ?>
                        <div class="zc-glass-panel zc-step-card">
                            <div class="zc-step-number"><?php echo esc_html( $step['num'] ); ?></div>
                            <h3 style="font-size:1.15rem;font-weight:800;margin:0 0 0.6rem;"><?php echo esc_html( $step['title'] ); ?></h3>
                            <p style="margin:0;font-size:0.9rem;color:var(--zc-text-secondary);line-height:1.85;"><?php echo esc_html( $step['desc'] ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 10. CUSTOMER TESTIMONIALS WIDGET
     */
    public static function render_testimonials( array $settings = [] ): string {
        $reviews = [
            [
                'name'    => 'مهندس مهدی ابراهیمی',
                'role'    => 'بنیان‌گذار پلتفرم پیامک‌پرو',
                'comment' => 'طراحی سایت و پنل نرم‌افزاری که جناب احسان نادری پناه در زرین کد برای مجموعه ما انجام دادند، سرعت بارگذاری و نرخ تبدیل فروش ما را ۳ برابر کرد. پشتیبانی واقعاً بی‌نظیر است.',
            ],
            [
                'name'    => 'سرکار خانم مونا عبدالهی',
                'role'    => 'مدیرعامل شرکت بازرگانی معدن‌گستران',
                'comment' => 'سئو سایت و سئو پیج اینستاگرام خودمان را به تیم زرین کد سپردیم. در کمتر از ۳ ماه روی کلیدواژه‌های اصلی به رتبه ۱ گوگل رسیدیم و ورودی اکسپلور ما فوق‌العاده شد.',
            ],
            [
                'name'    => 'جناب آقای علی محمودی',
                'role'    => 'مدیر فنی فروشگاه دیجیتال‌مارکت',
                'comment' => 'چندین افزونه وردپرس و سورس‌کد آماده را از فروشگاه زرین کد خریداری کردیم. کیفیت کدنویسی، رعایت استانداردهای امنیتی و مستندات فنی کاملاً در تراز تم‌فارست است.',
            ],
        ];
        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div class="zc-section-head text-center">
                    <span class="zc-badge-pill"><i class="fa-solid fa-star"></i> CUSTOMERS REVIEWS</span>
                    <h2 class="zc-section-title">نظرات مشتریان <span class="zc-highlight">زرین کد</span></h2>
                    <p class="zc-section-desc">تجربه واقعی مدیران کسب‌وکارهایی که طراحی سایت، سئو و تامین سورس‌کد خود را به ما سپردند</p>
                </div>
                <div class="zc-services-grid">
                    <?php foreach ( $reviews as $rev ) : ?>
                        <div class="zc-white-card" style="padding:2rem;display:flex;flex-direction:column;justify-content:space-between;gap:1.25rem;">
                            <div>
                                <div style="color:var(--color-accent);margin-bottom:0.85rem;font-size:0.95rem;">
                                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                </div>
                                <p style="font-size:0.95rem;color:var(--zc-text-secondary);line-height:1.9;margin:0;">
                                    «<?php echo esc_html( $rev['comment'] ); ?>»
                                </p>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.85rem;padding-top:1rem;border-top:1px solid var(--zc-border-subtle);">
                                <div style="width:2.8rem;height:2.8rem;border-radius:50%;background:var(--zc-dark);color:var(--color-accent);display:flex;align-items:center;justify-content:center;font-weight:800;">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;font-size:0.95rem;"><?php echo esc_html( $rev['name'] ); ?></div>
                                    <div style="font-size:0.78rem;color:var(--zc-text-muted);"><?php echo esc_html( $rev['role'] ); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 11. LATEST BLOG ARTICLES WIDGET
     */
    public static function render_blog_grid( array $settings = [] ): string {
        $posts = [
            [
                'cat'   => 'آموزش طراحی سایت و المنتور',
                'title' => 'معماری قالب‌های وردپرس المنتور بیس مطابق استاندارد ژاکت و تم‌فارست',
                'desc'  => 'چگونه با ترکیب PHP شیءگرا، Redux Framework و ویجت‌های اختصاصی المنتور یک قالب تجاری بسازیم؟',
                'date'  => '۲۹ مهر ۱۴۰۵',
            ],
            [
                'cat'   => 'سئو سایت و شبکه‌های اجتماعی',
                'title' => 'راهنمای جامع سئو سایت و سئو الگوریتمی اینستاگرام در سال ۲۰۲۶',
                'desc'  => 'تکنیک‌های کلیدی برای تسخیر همزمان صفحه اول گوگل و اکسپلور اینستاگرام با استراتژی محتوای یکپارچه.',
                'date'  => '۲۵ مهر ۱۴۰۵',
            ],
            [
                'cat'   => 'مهندسی نرم‌افزار و سورس‌کد',
                'title' => 'چرا خرید سورس‌کد و افزونه‌های استاندارد زمان راه‌اندازی استارتاپ را ۷۰٪ کاهش می‌دهد؟',
                'desc'  => 'بررسی مزایای استفاده از محصولات نرم‌افزاری تست‌شده با کدهای باز و پشتیبانی مستقیم توسعه‌دهنده.',
                'date'  => '۲۰ مهر ۱۴۰۵',
            ],
        ];
        ob_start();
        ?>
        <section style="padding:3rem 0 4rem;">
            <div class="zc-container">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:2.25rem;">
                    <div>
                        <span class="zc-badge-pill">ZARINCODE BLOG</span>
                        <h2 class="zc-section-title" style="margin-bottom:0.35rem;">آخرین مقالات آموزشی <span class="zc-highlight">زرین کد</span></h2>
                        <p class="zc-section-desc">مطالب تخصصی برنامه‌نویسی، طراحی وب، سئو و بازاریابی محتوایی</p>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="zc-btn zc-btn-dark">
                        مشاهده وبلاگ <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
                <div class="zc-services-grid">
                    <?php foreach ( $posts as $p ) : ?>
                        <article class="zc-white-card zc-project-card">
                            <div class="zc-project-visual" style="min-height:165px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span class="zc-prod-badge"><?php echo esc_html( $p['cat'] ); ?></span>
                                    <span class="zc-prod-ver"><?php echo esc_html( $p['date'] ); ?></span>
                                </div>
                                <h3 style="font-size:1.08rem;font-weight:800;margin:1rem 0 0;color:#FFFFFF;line-height:1.6;">
                                    <?php echo esc_html( $p['title'] ); ?>
                                </h3>
                            </div>
                            <p style="font-size:0.88rem;color:var(--zc-text-secondary);margin:0;">
                                <?php echo esc_html( $p['desc'] ); ?>
                            </p>
                            <div class="zc-service-footer" style="padding-top:0.75rem;">
                                <span style="font-size:0.78rem;color:var(--zc-text-muted);"><i class="fa-solid fa-user-pen" style="color:var(--color-accent);"></i> احسان نادری پناه</span>
                                <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="zc-btn zc-btn-yellow" style="padding:0.4rem 0.95rem;font-size:0.8rem;">مطالعه مقاله</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * 12. DEDICATED INNER PAGES & LEGAL/COMPLIANCE SUITE ELEMENTOR WIDGET
     */
    public static function render_page_section( array $settings = [] ): string {
        $key = $settings['section_key'] ?? 'about';
        return Legal_And_Inner_Pages::render( $key );
    }
}
