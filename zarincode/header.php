<?php
/**
 * The Header for Zarincode Theme (Crextio Sunshine Pill Header + Responsive Mobile Drawer + Full Theme Options Integration)
 *
 * @package Zarincode
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$zc_custom_header = '';
$phone            = zarincode_get_option( 'contact_phone', '09024561001' );
$dev_name         = zarincode_get_option( 'developer_name', 'احسان نادری پناه' );
$brand_name       = zarincode_get_option( 'brand_name', 'زریــن کـد' );
$brand_sub        = zarincode_get_option( 'brand_en_name', 'Zarincode Studio' );
$logo_monogram    = zarincode_get_option( 'logo_monogram', 'ZC' );
$logo_light       = zarincode_get_option( 'logo_light', '' );
$logo_width       = (int) zarincode_get_option( 'logo_width', 145 );
$enable_topbar    = (bool) zarincode_get_option( 'enable_topbar', true );
$show_dev         = (bool) zarincode_get_option( 'topbar_show_dev', true );
$show_phone       = (bool) zarincode_get_option( 'topbar_show_phone', true );
$show_legal       = (bool) zarincode_get_option( 'topbar_show_legal_links', true );
$topbar_badge     = zarincode_get_option( 'topbar_custom_badge', '' );
$show_search      = (bool) zarincode_get_option( 'header_show_search', true );
$show_dark_toggle = (bool) zarincode_get_option( 'header_show_dark_toggle', true );
$show_cart        = (bool) zarincode_get_option( 'header_show_cart', true );
$show_cta         = (bool) zarincode_get_option( 'header_show_cta', true );
$cta_text         = zarincode_get_option( 'header_cta_text', 'درخواست مشاوره' );
$cta_action       = zarincode_get_option( 'header_cta_action', 'modal' );
$cta_custom_url   = zarincode_get_option( 'header_cta_custom_url', '/contact-us/' );
$cart_url         = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/shop/' );
$cart_cnt         = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$ehsan_avatar     = get_template_directory_uri() . '/assets/images/ehsan-portrait.png';
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo 'dark' === zarincode_get_option( 'default_theme_mode', 'light' ) ? 'dark' : ''; ?>">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="author" content="Ehsan NaderiPanah - Zarincode.com">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="zc-skip-link" href="#primary"><?php echo esc_html__( 'Skip to content', 'zarincode' ); ?></a>
<?php if ( function_exists('elementor_theme_do_location') && elementor_theme_do_location('header') ) : ?>
<?php elseif ( ( $zc_custom_header = zarincode_builder_part( 'header' ) ) !== '' ) : ?>
<header role="banner"><?php echo $zc_custom_header; ?></header>
<?php else : ?>

<?php if ( $enable_topbar ) : ?>
<!-- Top Announcement & Legal Bar -->
<div class="zc-topbar">
    <div class="zc-container zc-topbar-inner">
        <div class="zc-topbar-items">
            <?php if ( ! empty( $topbar_badge ) ) : ?>
                <span class="zc-topbar-item zc-hide-sm" style="background:var(--color-accent);color:var(--color-accent-contrast);padding:2px 10px;border-radius:999px;font-weight:800;">
                    <?php echo esc_html( $topbar_badge ); ?>
                </span>
            <?php endif; ?>
            <?php if ( $show_dev ) : ?>
                <span class="zc-topbar-item zc-hide-sm">
                    <i class="fa-solid fa-code"></i>
                    <span>توسعه‌دهنده و مدیر فنی: <strong><?php echo esc_html( $dev_name ); ?></strong></span>
                </span>
            <?php endif; ?>
            <?php if ( $show_phone ) : ?>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-topbar-item">
                    <i class="fa-solid fa-phone-volume"></i>
                    <span>مشاوره و سفارش مستقیم: <strong dir="ltr"><?php echo esc_html( $phone ); ?></strong></span>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url( zarincode_get_option( 'official_website', 'https://zarincode.com' ) ); ?>" target="_blank" rel="noopener" class="zc-topbar-item zc-hide-sm">
                <i class="fa-solid fa-globe"></i>
                <span>Zarincode.com</span>
            </a>
        </div>
        <?php if ( $show_legal ) : ?>
        <div class="zc-topbar-items zc-topbar-legal">
            <a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>" class="zc-topbar-item">
                <i class="fa-solid fa-scale-balanced"></i> قوانین ایران
            </a>
            <a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>" class="zc-topbar-item">
                <i class="fa-solid fa-shield-halved"></i> گارانتی
            </a>
            <a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>" class="zc-topbar-item">
                <i class="fa-solid fa-rotate-left"></i> بازگشت وجه
            </a>
            <a href="<?php echo esc_url( home_url( '/gdpr-compliance/' ) ); ?>" class="zc-topbar-item">
                <i class="fa-solid fa-user-lock"></i> GDPR
            </a>
            <a href="<?php echo esc_url( home_url( '/dmca-policy/' ) ); ?>" class="zc-topbar-item">
                <i class="fa-solid fa-copyright"></i> DMCA (EN)
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Main Sticky Pill Header (Crextio Design System) -->
<header class="zc-header-wrap" role="banner">
    <div class="zc-container">
        <div class="zc-header-pill">
            <!-- Brand Identity -->
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="zc-brand" title="<?php echo esc_attr( $brand_name . ' | ' . $brand_sub ); ?>">
                <?php if ( ! empty( $logo_light ) ) : ?>
                    <img data-zc-eager src="<?php echo esc_url( $logo_light ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" style="max-width:<?php echo esc_attr( $logo_width . 'px' ); ?>;height:auto;">
                <?php else : ?>
                    <span class="zc-brand-logo-box"><?php echo esc_html( $logo_monogram ); ?></span>
                    <span class="zc-brand-text">
                        <span class="zc-brand-name"><?php echo esc_html( $brand_name ); ?></span>
                        <span class="zc-brand-sub"><?php echo esc_html( $brand_sub ); ?></span>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Main Pill Navigation with Mega Dropdowns -->
            <ul class="zc-nav-pill" aria-label="منوی اصلی">
                <li class="<?php echo is_front_page() ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <i class="fa-solid fa-house"></i> صفحه اصلی
                    </a>
                </li>
                <li class="has-megamenu <?php echo ( is_page( 'services' ) || is_singular( 'zc_service' ) || is_post_type_archive( 'zc_service' ) ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">
                        <i class="fa-solid fa-layer-group"></i> خدمات ما <i class="fa-solid fa-chevron-down zc-nav-caret"></i>
                    </a>
                    <ul class="zc-dropdown-menu">
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/website-design/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-laptop-code"></i></span>
                                <div>
                                    <div>۱. طراحی سایت اختصاصی</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">طراحی مدرن، المنتوری و فول‌اختصاصی</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/software-development/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-microchip"></i></span>
                                <div>
                                    <div>۲. برنامه‌نویسی نرم‌افزار</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">سامانه‌های تحت وب، اپلیکیشن و API</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/website-seo/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-chart-line"></i></span>
                                <div>
                                    <div>۳. سئو سایت تضمینی</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">رتبه اول گوگل و رشد ارگانیک فروش</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/social-media-seo/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-hashtag"></i></span>
                                <div>
                                    <div>۴. سئو شبکه‌های اجتماعی</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">رشد اکسپلور اینستاگرام، لینکدین و یوتیوب</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/content-creation/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-pen-nib"></i></span>
                                <div>
                                    <div>۵. تولید محتوا تخصصی</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">محتوای سئو شده، ویدئویی و گرافیکی</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/service/ready-made-products/' ) ); ?>">
                                <span class="zc-dropdown-icon"><i class="fa-solid fa-box-open"></i></span>
                                <div>
                                    <div>۶. فروش محصولات آماده</div>
                                    <small style="color:var(--zc-text-muted);font-weight:400;">قالب، افزونه وردپرس و سورس‌کد آماده</small>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="<?php echo ( is_page( 'shop' ) || ( function_exists( 'is_shop' ) && is_shop() ) || is_singular( 'product' ) || is_post_type_archive( 'product' ) ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">
                        <i class="fa-solid fa-store"></i> فروشگاه محصولات
                    </a>
                </li>
                <li class="<?php echo ( is_page( 'projects-portfolio' ) || is_singular( 'zc_project' ) || is_post_type_archive( 'zc_project' ) ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>">
                        <i class="fa-solid fa-briefcase"></i> نمونه‌کارها
                    </a>
                </li>
                <li class="has-megamenu <?php echo ( is_page( [ 'terms-and-conditions', 'warranty-policy', 'privacy-policy', 'refund-policy', 'gdpr-compliance', 'dmca-policy' ] ) ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>">
                        <i class="fa-solid fa-gavel"></i> قوانین و گارانتی <i class="fa-solid fa-chevron-down zc-nav-caret"></i>
                    </a>
                    <ul class="zc-dropdown-menu">
                        <li><a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-scale-balanced"></i></span> قوانین و شرایط + قوانین ایران</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-shield-halved"></i></span> گارانتی و پشتیبانی فنی یک‌ساله</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-lock"></i></span> سیاست حفظ حریم خصوصی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-money-bill-transfer"></i></span> شرایط ضمانت بازگشت وجه</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/gdpr-compliance/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-earth-europe"></i></span> قوانین حفاظت داده (GDPR)</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/dmca-policy/' ) ); ?>"><span class="zc-dropdown-icon"><i class="fa-solid fa-copyright"></i></span> DMCA Policy (English)</a></li>
                    </ul>
                </li>
                <li class="<?php echo is_page( 'about-us' ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">درباره ما</a>
                </li>
                <li class="<?php echo is_page( 'contact-us' ) ? 'current-menu-item' : ''; ?>">
                    <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">تماس با ما</a>
                </li>
            </ul>

            <!-- Header Actions (Search Ctrl+K, Dark Mode, Mini Cart, Consultation CTA, Mobile Drawer Toggle) -->
            <div class="zc-header-actions">
                <?php echo \Zarincode\Theme\Core\Account::button(); ?>
                <?php if ( $show_search ) : ?>
                    <button type="button" class="zc-icon-btn" data-zc-modal-open="zc-search-modal" title="جستجوی سریع خدمات و محصولات (Ctrl + K)" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                <?php endif; ?>
                <?php if ( $show_dark_toggle ) : ?>
                    <button type="button" class="zc-icon-btn" data-zc-toggle-dark title="تغییر حالت روشن / تیره (Crextio Mode)" aria-label="Toggle Dark Mode">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                <?php endif; ?>
                <?php if ( $show_cart ) : ?>
                    <a href="<?php echo esc_url( $cart_url ); ?>" class="zc-icon-btn" title="سبد خرید محصولات آماده">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span class="zc-cart-count"><?php echo esc_html( zarincode_fa_digits( $cart_cnt ) ); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ( $show_cta ) : ?>
                    <?php if ( 'link' === $cta_action ) : ?>
                        <a href="<?php echo esc_url( $cta_custom_url ); ?>" class="zc-btn zc-btn-yellow zc-header-cta-btn">
                            <i class="fa-solid fa-headset"></i>
                            <span><?php echo esc_html( $cta_text ); ?></span>
                        </a>
                    <?php else : ?>
                        <button type="button" class="zc-btn zc-btn-yellow zc-header-cta-btn" data-zc-modal-open="zc-consult-modal">
                            <i class="fa-solid fa-headset"></i>
                            <span><?php echo esc_html( $cta_text ); ?></span>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Mobile Hamburger Menu Toggle (Visible on Tablet & Mobile) -->
                <button type="button" class="zc-icon-btn zc-mobile-menu-toggle" data-zc-drawer-open="zc-mobile-drawer" aria-controls="zc-mobile-drawer" aria-expanded="false" aria-label="باز کردن منوی موبایل" title="منوی اصلی">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Responsive Mobile & Tablet Navigation Drawer -->
<div id="zc-mobile-drawer" class="zc-mobile-drawer-backdrop" aria-hidden="true" inert>
    <aside class="zc-mobile-drawer" role="dialog" aria-modal="true" tabindex="-1" aria-label="منوی موبایل زرین کد">
        <div class="zc-mobile-drawer-head">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="zc-brand" style="border:none;padding:0;background:transparent;">
                <span class="zc-brand-logo-box"><?php echo esc_html( $logo_monogram ); ?></span>
                <span class="zc-brand-text">
                    <span class="zc-brand-name"><?php echo esc_html( $brand_name ); ?></span>
                    <span class="zc-brand-sub"><?php echo esc_html( $brand_sub ); ?></span>
                </span>
            </a>
            <button type="button" class="zc-icon-btn" data-zc-drawer-close aria-label="بستن منو">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Executive Quick Contact Banner inside Mobile Drawer -->
        <div class="zc-mobile-founder-card">
            <img src="<?php echo esc_url( $ehsan_avatar ); ?>" alt="<?php echo esc_attr( $dev_name ); ?>" class="zc-mobile-founder-avatar">
            <div>
                <div style="font-size:0.88rem;font-weight:900;color:#fff;"><?php echo esc_html( $dev_name ); ?></div>
                <div style="font-size:0.73rem;color:var(--color-accent);font-weight:700;">مدیریت و توسعه‌دهنده ارشد زرین کد</div>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" dir="ltr" style="font-size:0.8rem;color:#D1D5DB;font-weight:800;display:inline-block;margin-top:0.15rem;">
                    <i class="fa-solid fa-phone-volume" style="color:var(--color-accent);"></i> <?php echo esc_html( $phone ); ?>
                </a>
            </div>
        </div>

        <!-- Mobile Navigation Links -->
        <nav class="zc-mobile-nav-wrap" aria-label="منوی موبایل">
            <ul class="zc-mobile-nav-list">
                <li>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <span><i class="fa-solid fa-house"></i> صفحه اصلی</span>
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                </li>
                <li class="zc-mobile-has-sub">
                    <div class="zc-mobile-sub-row">
                        <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">
                            <span><i class="fa-solid fa-layer-group"></i> خدمات تخصصی ما (۶ دپارتمان)</span>
                        </a>
                        <button type="button" class="zc-mobile-sub-toggle" data-zc-mobile-sub-toggle aria-label="باز کردن زیرمنو">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="zc-mobile-sub-list">
                        <li><a href="<?php echo esc_url( home_url( '/service/website-design/' ) ); ?>">۱. طراحی سایت اختصاصی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/software-development/' ) ); ?>">۲. برنامه‌نویسی نرم‌افزار</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/website-seo/' ) ); ?>">۳. سئو سایت تضمینی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/social-media-seo/' ) ); ?>">۴. سئو شبکه‌های اجتماعی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/content-creation/' ) ); ?>">۵. تولید محتوا تخصصی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/service/ready-made-products/' ) ); ?>">۶. فروش محصولات آماده</a></li>
                    </ul>
                </li>
                <li>
                    <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">
                        <span><i class="fa-solid fa-store"></i> فروشگاه قالب، افزونه و سورس‌کد</span>
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>">
                        <span><i class="fa-solid fa-briefcase"></i> نمونه‌کارها و پروژه‌ها</span>
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                </li>
                <li class="zc-mobile-has-sub">
                    <div class="zc-mobile-sub-row">
                        <a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>">
                            <span><i class="fa-solid fa-gavel"></i> قوانین، گارانتی و حقوقی</span>
                        </a>
                        <button type="button" class="zc-mobile-sub-toggle" data-zc-mobile-sub-toggle aria-label="باز کردن زیرمنو">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="zc-mobile-sub-list">
                        <li><a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>">قوانین و شرایط + قوانین ایران</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>">شرایط گارانتی و پشتیبانی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">سیاست حفظ حریم خصوصی</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>">شرایط ضمانت بازگشت وجه</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/gdpr-compliance/' ) ); ?>">قوانین حفاظت داده (GDPR)</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/dmca-policy/' ) ); ?>">DMCA Policy (English)</a></li>
                    </ul>
                </li>
                <li>
                    <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">
                        <span><i class="fa-solid fa-award"></i> درباره استودیو زرین کد</span>
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">
                        <span><i class="fa-solid fa-headset"></i> تماس با ما</span>
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="zc-mobile-drawer-foot">
            <button type="button" class="zc-btn zc-btn-yellow" style="width:100%;" data-zc-drawer-close data-zc-modal-open="zc-consult-modal">
                <i class="fa-solid fa-headset"></i> درخواست مشاوره رایگان
            </button>
            <a href="tel:<?php echo esc_attr( $phone ); ?>" class="zc-btn zc-btn-dark" style="width:100%;margin-top:0.6rem;" dir="ltr">
                <i class="fa-solid fa-phone-volume" style="color:var(--color-accent);"></i> <?php echo esc_html( $phone ); ?>
            </a>
        </div>
    </aside>
</div>

<?php endif; // Elementor header or native fallback. ?>
