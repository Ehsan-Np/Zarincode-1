<?php
/**
 * Single Portfolio Project — full case-study redesign (v4.2.0, 2026-11-01)
 *
 * Research-informed case-study anatomy:
 * back-link + dark hero (title, client line, result banner, live link) →
 * stat strip → two-column: narrative article + sticky fact sheet →
 * CTA band (similar project) → Single_Experience suite.
 *
 * @package Zarincode
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'zarincode-inner-pages', get_template_directory_uri() . '/assets/css/inner-pages.css', array(), ZARINCODE_VERSION );

get_header();

while ( have_posts() ) :
    the_post();
    $post_id  = get_the_ID();
    $client   = get_post_meta( $post_id, '_zc_prj_client', true ) ?: 'مجموعه تجاری برتر';
    $agency   = get_post_meta( $post_id, '_zc_prj_agency', true ) ?: 'زرین کد (احسان نادری پناه)';
    $date     = get_post_meta( $post_id, '_zc_prj_date', true ) ?: 'فروردین ۱۴۰۵';
    $duration = get_post_meta( $post_id, '_zc_prj_duration', true ) ?: '۲۵ روز کاری';
    $cms      = get_post_meta( $post_id, '_zc_prj_cms', true ) ?: 'وردپرس + المنتور اختصاصی';
    $plan     = get_post_meta( $post_id, '_zc_prj_plan', true ) ?: 'پلن الماسی + خدمات سئو';
    $metrics  = get_post_meta( $post_id, '_zc_prj_metrics', true ) ?: '+۳۲۰٪ رشد فروش آنلاین';
    $url      = get_post_meta( $post_id, '_zc_prj_url', true ) ?: 'https://zarincode.com';
    $terms    = get_the_terms( $post_id, 'zc_project_cat' );
    $term_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'نمونه‌کارها';
    $portfolio_url = get_post_type_archive_link( 'zc_project' ) ?: home_url( '/projects-portfolio/' );
    ?>
    <main id="primary" class="zc-container zc-inner-page" style="padding-top:2rem;padding-bottom:4rem;" role="main">

        <!-- ============================================================ HERO -->
        <section class="zc-inner-hero zc-reveal" aria-labelledby="zc-prj-title">
            <nav class="zc-eh-breadcrumb" aria-label="مسیر صفحه">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <a href="<?php echo esc_url( $portfolio_url ); ?>">نمونه‌کارها</a>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span><?php echo esc_html( $term_name ); ?></span>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span aria-current="page"><?php the_title(); ?></span>
            </nav>

            <span class="zc-eh-kicker"><i class="fa-solid fa-briefcase"></i> ZARINCODE CASE STUDY — مطالعهٔ موردی</span>
            <h1 class="zc-eh-title" id="zc-prj-title"><?php the_title(); ?></h1>
            <p class="zc-eh-lead">
                پروژه‌ای اجراشده توسط <strong style="color:#fdd05b;"><?php echo esc_html( $agency ); ?></strong>
                برای <strong style="color:#fdd05b;"><?php echo esc_html( $client ); ?></strong> —
                بازهٔ اجرا <?php echo esc_html( $date . ' • ' . $duration ); ?>.
            </p>

            <div class="zc-eh-chips" aria-label="خلاصهٔ مشخصات پروژه">
                <span class="zc-eh-chip"><i class="fa-solid fa-layer-group"></i> <?php echo esc_html( $cms ); ?></span>
                <span class="zc-eh-chip"><i class="fa-solid fa-gem"></i> <?php echo esc_html( $plan ); ?></span>
                <span class="zc-eh-chip"><i class="fa-regular fa-calendar"></i> <?php echo esc_html( $date ); ?></span>
                <span class="zc-eh-chip"><i class="fa-regular fa-clock"></i> <?php echo esc_html( $duration ); ?></span>
            </div>

            <!-- flagship result banner -->
            <div class="zc-case-result" role="note" aria-label="نتیجهٔ شاخص پروژه">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                <div>
                    <small>نتیجه و دستاورد کلیدی</small>
                    <strong><?php echo esc_html( $metrics ); ?></strong>
                </div>
            </div>

            <div class="zc-eh-actions" style="margin-top:1.4rem;">
                <button type="button" class="zc-eh-btn zc-eh-btn-gold" data-zc-modal-open="zc-consult-modal">
                    <i class="fa-solid fa-rocket"></i> سفارش پروژهٔ مشابه برای کسب‌وکار شما
                </button>
                <a href="<?php echo esc_url( $url ); ?>" class="zc-eh-btn zc-eh-btn-ghost" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> مشاهدهٔ زندهٔ پروژه
                </a>
                <a href="<?php echo esc_url( $portfolio_url ); ?>" class="zc-eh-btn zc-eh-btn-ghost" style="border-style:dashed;">
                    <i class="fa-solid fa-arrow-right"></i> سایر نمونه‌کارها
                </a>
            </div>
        </section>

        <!-- ============================================ fact sheet + story -->
        <section class="zc-case-layout" style="margin-top:1.5rem;" aria-label="جزئیات و روایت پروژه">
            <article class="zc-inner-article zc-reveal zc-reveal-d1" aria-label="شرح کامل پروژه">
                <span class="zc-sec-kicker"><i class="fa-solid fa-feather-pointed"></i> THE CHALLENGE &amp; SOLUTION</span>
                <h2 style="margin-top:.4rem;">داستان پروژه</h2>
                <?php the_content(); ?>
            </article>

            <aside class="zc-case-side zc-reveal zc-reveal-d2" aria-label="مشخصات فنی پروژه">
                <div class="zc-sec-head" style="margin-bottom:.8rem;">
                    <div>
                        <span class="zc-sec-kicker"><i class="fa-solid fa-clipboard-list"></i> PROJECT FACT SHEET</span>
                        <h2 style="font-size:1.15rem;">مشخصات پروژه</h2>
                    </div>
                </div>
                <ul class="zc-case-facts">
                    <li><small>کارفرما / برند</small><strong><?php echo esc_html( $client ); ?></strong></li>
                    <li><small>مجری پروژه</small><strong><?php echo esc_html( $agency ); ?></strong></li>
                    <li><small>تاریخ اجرا</small><strong><?php echo esc_html( $date ); ?></strong></li>
                    <li><small>مدت اجرا</small><strong><?php echo esc_html( $duration ); ?></strong></li>
                    <li><small>تکنولوژی / CMS</small><strong><?php echo esc_html( $cms ); ?></strong></li>
                    <li><small>پلن اجرایی</small><strong><?php echo esc_html( $plan ); ?></strong></li>
                    <li style="background:#fdd05b1f;"><small>نتیجه</small><strong style="color:#a16207;"><?php echo esc_html( $metrics ); ?></strong></li>
                </ul>
                <div style="margin-top:.9rem;display:grid;gap:.55rem;">
                    <a href="<?php echo esc_url( $url ); ?>" class="zc-eh-btn zc-eh-btn-dark" target="_blank" rel="noopener noreferrer" style="width:100%;">
                        <i class="fa-solid fa-globe"></i> بازدید از وب‌سایت پروژه
                    </a>
                    <button type="button" class="zc-eh-btn zc-eh-btn-gold" data-zc-modal-open="zc-consult-modal" style="width:100%;">
                        <i class="fa-solid fa-paper-plane"></i> سفارش پروژهٔ مشابه
                    </button>
                </div>
            </aside>
        </section>

        <!-- =============================================== CTA band -->
        <section class="zc-cta-band" style="margin-top:1.5rem;" aria-label="دعوت به شروع پروژه مشابه">
            <div>
                <h2>پروژه‌ای مشابه «<?php the_title(); ?>» در ذهن دارید؟</h2>
                <p>از ایده تا اجرا کنار شما هستیم؛ مشاورهٔ رایگان بگیرید و نقشهٔ راه کسب‌وکارتان را در همان جلسه بشناسید.</p>
            </div>
            <div class="zc-eh-actions">
                <button type="button" class="zc-eh-btn zc-eh-btn-gold" data-zc-modal-open="zc-consult-modal">
                    <i class="fa-solid fa-comments"></i> درخواست مشاورهٔ رایگان
                </button>
                <a href="tel:09024561001" class="zc-eh-btn zc-eh-btn-ghost"><i class="fa-solid fa-phone"></i> ۰۹۰۲۴۵۶۱۰۰۱</a>
            </div>
        </section>

        <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
    </main>

    <!-- mobile sticky bar -->
    <div class="zc-inner-mobilebar" role="region" aria-label="نوار سریع">
        <div>
            <strong><?php echo esc_html( $metrics ); ?></strong>
            <small>نتیجهٔ پروژه • مشاورهٔ رایگان</small>
        </div>
        <div style="display:flex;gap:.45rem;">
            <button type="button" class="zc-eh-btn zc-eh-btn-gold" data-zc-modal-open="zc-consult-modal"><i class="fa-solid fa-rocket"></i> پروژهٔ مشابه</button>
            <a href="<?php echo esc_url( $portfolio_url ); ?>" class="zc-eh-btn zc-eh-btn-ghost"><i class="fa-solid fa-images"></i> نمونه‌کارها</a>
        </div>
    </div>
    <?php
endwhile;

get_footer();
