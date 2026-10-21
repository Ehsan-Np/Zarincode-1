<?php
/**
 * Single Portfolio Project Template (Webinex Project Detail + Crextio Sunshine UI)
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
    $post_id  = get_the_ID();
    $client   = get_post_meta( $post_id, '_zc_prj_client', true ) ?: 'مجموعه تجاری برتر';
    $agency   = get_post_meta( $post_id, '_zc_prj_agency', true ) ?: 'زرین کد (احسان نادری پناه)';
    $date     = get_post_meta( $post_id, '_zc_prj_date', true ) ?: 'فروردین ۱۴۰۵';
    $duration = get_post_meta( $post_id, '_zc_prj_duration', true ) ?: '۲۵ روز کاری';
    $cms      = get_post_meta( $post_id, '_zc_prj_cms', true ) ?: 'وردپرس + المنتور اختصاصی';
    $plan     = get_post_meta( $post_id, '_zc_prj_plan', true ) ?: 'پلن الماسی + خدمات سئو';
    $metrics  = get_post_meta( $post_id, '_zc_prj_metrics', true ) ?: '+۳۲۰٪ رشد فروش آنلاین';
    $url      = get_post_meta( $post_id, '_zc_prj_url', true ) ?: 'https://zarincode.com';
    ?>
    <main id="primary" class="zc-container" style="padding-top:2.5rem;padding-bottom:4rem;" role="main">
        <div class="zc-glass-panel" style="padding:2.75rem;margin-bottom:2.5rem;">
            <span class="zc-badge-pill"><i class="fa-solid fa-briefcase"></i> ZARINCODE CASE STUDY</span>
            <h1 class="zc-section-title"><?php the_title(); ?></h1>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin:1.75rem 0;">
                <div class="zc-white-card" style="padding:1.2rem;"><small style="color:var(--zc-text-muted);">کارفرما / برند:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $client ); ?></div></div>
                <div class="zc-white-card" style="padding:1.2rem;"><small style="color:var(--zc-text-muted);">مجری پروژه:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $agency ); ?></div></div>
                <div class="zc-white-card" style="padding:1.2rem;"><small style="color:var(--zc-text-muted);">تاریخ و زمان اجرا:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $date . ' • ' . $duration ); ?></div></div>
                <div class="zc-white-card" style="padding:1.2rem;"><small style="color:var(--zc-text-muted);">تکنولوژی / CMS:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $cms ); ?></div></div>
                <div class="zc-white-card" style="padding:1.2rem;"><small style="color:var(--zc-text-muted);">پلن اجرایی:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $plan ); ?></div></div>
                <div class="zc-yellow-card" style="padding:1.2rem;"><small style="font-weight:700;">نتیجه و دستاورد:</small><div style="font-weight:900;margin-top:0.25rem;"><?php echo esc_html( $metrics ); ?></div></div>
            </div>

            <div style="line-height:2;font-size:1.02rem;color:var(--zc-text-secondary);margin-bottom:2rem;">
                <?php the_content(); ?>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:1rem;">
                <button type="button" class="zc-btn zc-btn-yellow" data-zc-modal-open="zc-consult-modal">
                    <i class="fa-solid fa-rocket"></i> سفارش پروژه مشابه برای کسب‌وکار شما
                </button>
                <a href="<?php echo esc_url( home_url( '/projects-portfolio/' ) ); ?>" class="zc-btn zc-btn-dark">
                    مشاهده سایر نمونه‌کارها <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
        </div>
    </main>
    <?php
endwhile;

get_footer();
