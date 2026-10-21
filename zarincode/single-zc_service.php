<?php
/**
 * Single Service Template (Webinex Single Service Layout + Crextio Sunshine Design)
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
    $post_id    = get_the_ID();
    $en_title   = get_post_meta( $post_id, '_zc_srv_en_title', true ) ?: 'Premium Digital Service';
    $icon       = get_post_meta( $post_id, '_zc_srv_icon', true ) ?: 'fa-solid fa-laptop-code';
    $price      = get_post_meta( $post_id, '_zc_srv_price_range', true ) ?: '۱۰ تا ۵۵ میلیون تومان';
    $old_price  = get_post_meta( $post_id, '_zc_srv_old_price', true ) ?: '۲۰ تا ۱۱۰ میلیون';
    $discount   = get_post_meta( $post_id, '_zc_srv_discount', true ) ?: '50%';
    $mode       = get_post_meta( $post_id, '_zc_srv_delivery_mode', true ) ?: 'دورکاری و قرارداد رسمی';
    $refund     = get_post_meta( $post_id, '_zc_srv_refund_guarantee', true ) ?: 'دارد (۱۰۰٪ تضمینی)';
    $quality    = get_post_meta( $post_id, '_zc_srv_quality_guarantee', true ) ?: 'دارد';
    $prepayment = get_post_meta( $post_id, '_zc_srv_prepayment', true ) ?: '50%';
    $duration   = get_post_meta( $post_id, '_zc_srv_duration', true ) ?: '۲۰ تا ۴۵ روز کاری';
    $reports    = get_post_meta( $post_id, '_zc_srv_reports_count', true ) ?: '۵ بار در روند پروژه';
    $revisions  = get_post_meta( $post_id, '_zc_srv_revisions', true ) ?: '۳ بار پس از تحویل';
    $delivery   = get_post_meta( $post_id, '_zc_srv_final_delivery', true ) ?: 'ارائه فایل سورس و اطلاعات کامل سایت';
    ?>
    <main id="primary" class="zc-container" style="padding-top:2.5rem;padding-bottom:4rem;" role="main">
        <!-- Service Hero Card (Webinex Single Service Hero + Crextio Bento) -->
        <div class="zc-glass-panel" style="padding:2.75rem;margin-bottom:2.5rem;">
            <div class="zc-hero-grid" style="align-items:center;">
                <div>
                    <span class="zc-badge-pill"><i class="<?php echo esc_attr( $icon ); ?>"></i> سرویس ویژه آژانس زرین کد • <?php echo esc_html( $en_title ); ?></span>
                    <h1 class="zc-section-title"><?php the_title(); ?> | <span style="font-size:0.75em;opacity:0.75;" dir="ltr"><?php echo esc_html( $en_title ); ?></span></h1>
                    <div style="color:var(--zc-text-secondary);font-size:1rem;line-height:1.95;margin-bottom:1.5rem;">
                        <?php echo wp_kses_post( has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 55 ) ); ?>
                    </div>

                    <!-- Rating & Stats Pills (Webinex 5.0 Rating + 3700 Projects) -->
                    <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1.75rem;">
                        <div class="zc-white-card" style="padding:0.85rem 1.35rem;display:flex;align-items:center;gap:0.75rem;">
                            <div style="width:2.6rem;height:2.6rem;border-radius:50%;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-weight:900;">۵.۰</div>
                            <div>
                                <div style="font-weight:800;font-size:0.9rem;">امتیاز کامل رضایت</div>
                                <div style="font-size:0.75rem;color:var(--zc-text-muted);">از ۸۴۰+ نظر ثبت شده</div>
                            </div>
                        </div>
                        <div class="zc-white-card" style="padding:0.85rem 1.35rem;display:flex;align-items:center;gap:0.75rem;">
                            <div style="width:2.6rem;height:2.6rem;border-radius:50%;background:var(--zc-dark);color:var(--color-accent);display:flex;align-items:center;justify-content:center;font-weight:900;">+۴۲۰</div>
                            <div>
                                <div style="font-weight:800;font-size:0.9rem;">پروژه موفق اجرا شده</div>
                                <div style="font-size:0.75rem;color:var(--zc-text-muted);">توسط احسان نادری پناه</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Discount Strip -->
                    <div class="zc-dark-card" style="padding:1.5rem 2rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
                        <div>
                            <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.25rem;">
                                <del style="color:#9CA3AF;font-size:0.88rem;"><?php echo esc_html( $old_price ); ?></del>
                                <span class="zc-prod-badge"><?php echo esc_html( $discount ); ?> تخفیف جشنواره</span>
                            </div>
                            <div style="font-size:1.45rem;font-weight:900;color:var(--color-accent);">
                                <?php echo esc_html( $price ); ?>
                            </div>
                        </div>
                        <a href="tel:09024561001" class="zc-btn zc-btn-yellow">
                            <i class="fa-solid fa-phone-volume"></i> مشاوره تلفنی: 09024561001
                        </a>
                    </div>
                </div>

                <!-- Quick Order Form Card (Webinex Quick Project Request) -->
                <div class="zc-white-card" style="padding:2.25rem;">
                    <span class="zc-badge-pill">FAST ORDER REQUEST</span>
                    <h3 style="margin:0.25rem 0 1rem;font-size:1.3rem;font-weight:900;">ثبت سریع درخواست «<?php the_title(); ?>»</h3>
                    <p style="font-size:0.86rem;color:var(--zc-text-secondary);margin-bottom:1.25rem;">
                        اطلاعات تماس خود را وارد کنید تا <strong>احسان نادری پناه</strong> در کمتر از ۱۵ دقیقه جهت مشاوره رایگان با شما تماس بگیرد:
                    </p>
                    <form class="zc-Ajax-form">
                        <div class="zc-form-group">
                            <label class="zc-form-label">نام و نام خانوادگی *</label>
                            <input type="text" class="zc-form-control" required placeholder="مثلاً: محمد رضایی">
                        </div>
                        <div class="zc-form-group">
                            <label class="zc-form-label">شماره تماس *</label>
                            <input type="tel" class="zc-form-control" required placeholder="0912XXXXXXX" dir="ltr">
                        </div>
                        <div class="zc-form-group">
                            <label class="zc-form-label">توضیحات اولیه پروژه (اختیاری)</label>
                            <textarea class="zc-form-control" rows="3" placeholder="نیازمندی‌ها یا بودجه مد نظر خود را بنویسید..."></textarea>
                        </div>
                        <div class="zc-form-feedback" style="display:none;padding:0.8rem;border-radius:1rem;background:var(--color-accent);color:var(--color-accent-contrast);font-weight:700;font-size:0.84rem;margin-bottom:1rem;"></div>
                        <button type="submit" class="zc-btn zc-btn-yellow" style="width:100%;">
                            <i class="fa-solid fa-paper-plane"></i> ثبت درخواست پروژه و مشاوره رایگان
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Conditions Grid (Webinex شرایط ارائه خدمات) -->
        <div class="zc-glass-panel" style="padding:2.5rem;margin-bottom:2.5rem;">
            <span class="zc-badge-pill">SERVICE CONDITIONS &amp; GUARANTEES</span>
            <h2 class="zc-section-title" style="font-size:1.65rem;margin-bottom:1.5rem;">شرایط و تضمین‌های ارائه خدمت «<?php the_title(); ?>»</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem;">
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">نحوه انجام خدمت:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $mode ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">گارانتی بازگشت وجه:</small><div style="font-weight:800;margin-top:0.25rem;color:#10B981;"><?php echo esc_html( $refund ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">ضمانت کیفیت:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $quality ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">پیش‌پرداخت:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $prepayment ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">مدت زمان اجرا:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $duration ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">ارائه گزارش از کار:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $reports ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">اصلاح و بازبینی رایگان:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $revisions ); ?></div></div>
                <div class="zc-white-card" style="padding:1.15rem;"><small style="color:var(--zc-text-muted);">نحوه تحویل نهایی:</small><div style="font-weight:800;margin-top:0.25rem;"><?php echo esc_html( $delivery ); ?></div></div>
            </div>
        </div>

        <!-- Full Service Content -->
        <article class="zc-glass-panel" style="padding:2.5rem;margin-bottom:2.5rem;line-height:2;">
            <?php the_content(); ?>
        </article>
    </main>
    <?php
endwhile;

get_footer();
