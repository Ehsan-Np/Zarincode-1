<?php
/**
 * Single Service — full redesign (v4.2.0, 2026-11-01)
 *
 * Research-informed structure (service landing anatomy):
 * breadcrumb → dark hero (outcome + trust chips + price card + quick order)
 * → promise grid (conditions & guarantees) → process timeline → article body
 * → FAQ (objection handling) → CTA band → Single_Experience suite.
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
    $terms      = get_the_terms( $post_id, 'zc_service_cat' );
    $term_name  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'خدمات';
    $excerpt    = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 55 );
    ?>
    <main id="primary" class="zc-container zc-inner-page" style="padding-top:2rem;padding-bottom:4rem;" role="main">

        <!-- ============================================================ HERO -->
        <section class="zc-inner-hero zc-reveal" aria-labelledby="zc-srv-title">
            <nav class="zc-eh-breadcrumb" aria-label="مسیر صفحه">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'zc_service' ) ?: home_url( '/' ) ); ?>">خدمات</a>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span><?php echo esc_html( $term_name ); ?></span>
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span aria-current="page"><?php the_title(); ?></span>
            </nav>

            <div class="zc-eh-split">
                <div>
                    <span class="zc-eh-kicker"><i class="<?php echo esc_attr( $icon ); ?>"></i> خدمت ویژهٔ زرین‌کد</span>
                    <h1 class="zc-eh-title" id="zc-srv-title">
                        <?php the_title(); ?>
                        <span class="zc-eh-en" dir="ltr"><?php echo esc_html( $en_title ); ?></span>
                    </h1>
                    <p class="zc-eh-lead"><?php echo wp_kses_post( $excerpt ); ?></p>

                    <div class="zc-eh-chips" aria-label="نکات کلیدی خدمت">
                        <span class="zc-eh-chip"><i class="fa-regular fa-clock"></i> اجرا: <?php echo esc_html( $duration ); ?></span>
                        <span class="zc-eh-chip"><i class="fa-solid fa-user-tie"></i> <?php echo esc_html( $mode ); ?></span>
                        <span class="zc-eh-chip"><i class="fa-solid fa-rotate"></i> بازبینی: <?php echo esc_html( $revisions ); ?></span>
                        <span class="zc-eh-chip"><i class="fa-solid fa-shield-halved"></i> گارانتی بازگشت وجه</span>
                    </div>

                    <div class="zc-eh-actions">
                        <a href="tel:09024561001" class="zc-eh-btn zc-eh-btn-gold">
                            <i class="fa-solid fa-phone-volume"></i> مشاورهٔ تلفنی رایگان: ۰۹۰۲۴۵۶۱۰۰۱
                        </a>
                        <a href="#zc-srv-order" class="zc-eh-btn zc-eh-btn-ghost">
                            <i class="fa-solid fa-paper-plane"></i> ثبت درخواست سریع
                        </a>
                        <a href="#comments" class="zc-eh-btn zc-eh-btn-ghost" style="border-style:dashed;">
                            <i class="fa-regular fa-comments"></i> <?php echo esc_html( sprintf( '%s پرسش و دیدگاه', number_format_i18n( get_comments_number() ) ) ); ?>
                        </a>
                    </div>

                    <div class="zc-stat-strip" aria-label="خلاصهٔ شرایط">
                        <div class="zc-stat-cell"><small>بازهٔ قیمت</small><strong><?php echo esc_html( $price ); ?></strong></div>
                        <div class="zc-stat-cell"><small>پیش‌پرداخت</small><strong><?php echo esc_html( $prepayment ); ?></strong></div>
                        <div class="zc-stat-cell"><small>گزارش کار</small><strong><?php echo esc_html( $reports ); ?></strong></div>
                        <div class="zc-stat-cell zc-stat-gold"><small>تحویل نهایی</small><strong><?php echo esc_html( $delivery ); ?></strong></div>
                    </div>
                </div>

                <!-- price card -->
                <aside class="zc-price-card zc-reveal zc-reveal-d1" aria-label="قیمت و شرایط">
                    <span class="zc-price-ribbon"><?php echo esc_html( $discount ); ?> تخفیف</span>
                    <div class="zc-price-old"><?php echo esc_html( $old_price ); ?> تومان</div>
                    <div class="zc-price-now"><?php echo esc_html( $price ); ?></div>
                    <p class="zc-price-note">قیمت نهایی پس از جلسهٔ مشاورهٔ رایگان و بررسی دقیق نیازمندی‌ها اعلام می‌شود.</p>
                    <div class="zc-price-sep"></div>
                    <ul class="zc-price-points">
                        <li><i class="fa-solid fa-circle-check"></i> قرارداد رسمی و شفاف</li>
                        <li><i class="fa-solid fa-circle-check"></i> ضمانت کیفیت: <?php echo esc_html( $quality ); ?></li>
                        <li><i class="fa-solid fa-circle-check"></i> بازگشت وجه: <?php echo esc_html( $refund ); ?></li>
                    </ul>
                    <a href="#zc-srv-order" class="zc-eh-btn zc-eh-btn-gold"><i class="fa-solid fa-rocket"></i> شروع پروژه</a>
                </aside>
            </div>
        </section>

        <!-- ============================================== quick order form -->
        <section id="zc-srv-order" class="zc-order-card zc-reveal zc-reveal-d1" style="margin-top:1.5rem;" aria-labelledby="zc-order-title">
            <div class="zc-eh-split" style="align-items:center;">
                <div>
                    <span class="zc-order-badge">FAST ORDER REQUEST</span>
                    <h3 id="zc-order-title">ثبت سریع درخواست «<?php the_title(); ?>»</h3>
                    <p class="zc-order-note">
                        اطلاعات تماس خود را وارد کنید تا <strong>احسان نادری پناه</strong> در کمتر از ۱۵ دقیقه جهت مشاورهٔ رایگان با شما تماس بگیرد.
                        هیچ تعهدی برای شما ایجاد نمی‌شود.
                    </p>
                    <div class="zc-eh-chips" style="margin:0;">
                        <span class="zc-eh-chip" style="background:#153c2b14;border-color:#153c2b33;color:#15803d;"><i class="fa-solid fa-check"></i> پاسخ‌گویی زیر ۱۵ دقیقه در ساعات کاری</span>
                        <span class="zc-eh-chip" style="background:#153c2b14;border-color:#153c2b33;color:#15803d;"><i class="fa-solid fa-check"></i> مشاورهٔ رایگان و بدون تعهد</span>
                    </div>
                </div>
                <form class="zc-Ajax-form" aria-label="فرم سریع درخواست پروژه">
                    <input type="text" name="zc_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute!important;left:-10000px!important;width:1px;height:1px;opacity:0;">
                    <div class="zc-form-group">
                        <label class="zc-form-label">نام و نام خانوادگی *</label>
                        <input type="text" name="zc-consult-name" class="zc-form-control" required placeholder="مثلاً: محمد رضایی">
                    </div>
                    <div class="zc-form-group">
                        <label class="zc-form-label">شماره تماس *</label>
                        <input type="tel" name="zc-consult-phone" class="zc-form-control" required placeholder="0912XXXXXXX" dir="ltr">
                    </div>
                    <div class="zc-form-group">
                        <label class="zc-form-label">توضیحات اولیهٔ پروژه (اختیاری)</label>
                        <textarea name="zc-consult-details" class="zc-form-control" rows="3" placeholder="نیازمندی‌ها یا بودجهٔ مد نظر خود را بنویسید…"></textarea>
                    </div>
                    <div class="zc-form-feedback" style="display:none;padding:0.8rem;border-radius:1rem;background:var(--color-accent);color:var(--color-accent-contrast);font-weight:700;font-size:0.84rem;margin-bottom:1rem;"></div>
                    <button type="submit" class="zc-btn zc-btn-yellow" style="width:100%;">
                        <i class="fa-solid fa-paper-plane"></i> ثبت درخواست و مشاورهٔ رایگان
                    </button>
                </form>
            </div>
        </section>

        <!-- =========================================== promise / conditions -->
        <section class="zc-glass-panel" style="padding:clamp(1.4rem,3vw,2.4rem);margin-top:1.5rem;" aria-labelledby="zc-promise-title">
            <div class="zc-sec-head">
                <div>
                    <span class="zc-sec-kicker"><i class="fa-solid fa-award"></i> CONDITIONS &amp; GUARANTEES</span>
                    <h2 id="zc-promise-title">شرایط و تضمین‌های «<?php the_title(); ?>»</h2>
                </div>
                <span class="zc-sec-line" aria-hidden="true"></span>
            </div>
            <div class="zc-promise-grid">
                <div class="zc-promise-card is-gold">
                    <span class="zc-promise-icon"><i class="fa-solid fa-handshake"></i></span>
                    <small>نحوهٔ انجام خدمت</small><strong><?php echo esc_html( $mode ); ?></strong>
                </div>
                <div class="zc-promise-card is-good">
                    <span class="zc-promise-icon"><i class="fa-solid fa-shield-heart"></i></span>
                    <small>گارانتی بازگشت وجه</small><strong><?php echo esc_html( $refund ); ?></strong>
                </div>
                <div class="zc-promise-card is-good">
                    <span class="zc-promise-icon"><i class="fa-solid fa-medal"></i></span>
                    <small>ضمانت کیفیت</small><strong><?php echo esc_html( $quality ); ?></strong>
                </div>
                <div class="zc-promise-card">
                    <span class="zc-promise-icon"><i class="fa-solid fa-wallet"></i></span>
                    <small>پیش‌پرداخت</small><strong><?php echo esc_html( $prepayment ); ?></strong>
                </div>
                <div class="zc-promise-card">
                    <span class="zc-promise-icon"><i class="fa-regular fa-calendar-check"></i></span>
                    <small>مدت زمان اجرا</small><strong><?php echo esc_html( $duration ); ?></strong>
                </div>
                <div class="zc-promise-card">
                    <span class="zc-promise-icon"><i class="fa-regular fa-file-lines"></i></span>
                    <small>ارائهٔ گزارش از کار</small><strong><?php echo esc_html( $reports ); ?></strong>
                </div>
                <div class="zc-promise-card">
                    <span class="zc-promise-icon"><i class="fa-solid fa-pen-ruler"></i></span>
                    <small>اصلاح و بازبینی رایگان</small><strong><?php echo esc_html( $revisions ); ?></strong>
                </div>
                <div class="zc-promise-card is-gold">
                    <span class="zc-promise-icon"><i class="fa-solid fa-box-open"></i></span>
                    <small>نحوهٔ تحویل نهایی</small><strong><?php echo esc_html( $delivery ); ?></strong>
                </div>
            </div>
        </section>

        <!-- ================================================= process -->
        <section class="zc-glass-panel" style="padding:clamp(1.4rem,3vw,2.4rem);margin-top:1.5rem;" aria-labelledby="zc-process-title">
            <div class="zc-sec-head">
                <div>
                    <span class="zc-sec-kicker"><i class="fa-solid fa-list-ol"></i> HOW WE WORK</span>
                    <h2 id="zc-process-title">روند اجرا، از مشاوره تا تحویل</h2>
                </div>
                <span class="zc-sec-line" aria-hidden="true"></span>
            </div>
            <ol class="zc-process is-wide">
                <li class="zc-process-step">
                    <span class="zc-process-num" aria-hidden="true">۱</span>
                    <div class="zc-process-body">
                        <h3>مشاورهٔ رایگان</h3>
                        <p>تماس یا جلسهٔ کوتاه، بررسی نیاز، بودجه و اهداف کسب‌وکار شما؛ بدون هیچ تعهدی.</p>
                    </div>
                </li>
                <li class="zc-process-step">
                    <span class="zc-process-num" aria-hidden="true">۲</span>
                    <div class="zc-process-body">
                        <h3>قرارداد و شروع</h3>
                        <p>قرارداد رسمی با <?php echo esc_html( $prepayment ); ?> پیش‌پرداخت؛ برنامهٔ زمانی و دامنهٔ کار شفاف می‌شود.</p>
                    </div>
                </li>
                <li class="zc-process-step">
                    <span class="zc-process-num" aria-hidden="true">۳</span>
                    <div class="zc-process-body">
                        <h3>طراحی و اجرا</h3>
                        <p>اجرای پروژه در بازهٔ <?php echo esc_html( $duration ); ?> به‌صورت <?php echo esc_html( $mode ); ?> با هماهنگی مستمر.</p>
                    </div>
                </li>
                <li class="zc-process-step">
                    <span class="zc-process-num" aria-hidden="true">۴</span>
                    <div class="zc-process-body">
                        <h3>گزارش و بازبینی</h3>
                        <p><?php echo esc_html( $reports ); ?> گزارش پیشرفت و <?php echo esc_html( $revisions ); ?> اصلاح و بازبینی رایگان.</p>
                    </div>
                </li>
                <li class="zc-process-step">
                    <span class="zc-process-num" aria-hidden="true">۵</span>
                    <div class="zc-process-body">
                        <h3>تحویل نهایی</h3>
                        <p><?php echo esc_html( $delivery ); ?> — همراه با آموزش کامل و پشتیبانی پس از تحویل.</p>
                    </div>
                </li>
            </ol>
        </section>

        <!-- =============================================== article body -->
        <article class="zc-inner-article zc-reveal" style="margin-top:1.5rem;" aria-label="توضیحات کامل خدمت">
            <?php the_content(); ?>
        </article>

        <!-- ================================================== FAQ -->
        <section class="zc-glass-panel" style="padding:clamp(1.4rem,3vw,2.4rem);margin-top:1.5rem;" aria-labelledby="zc-faq-title">
            <div class="zc-sec-head">
                <div>
                    <span class="zc-sec-kicker"><i class="fa-regular fa-circle-question"></i> FAQ</span>
                    <h2 id="zc-faq-title">پرسش‌های پرتکرار دربارهٔ این خدمت</h2>
                </div>
                <span class="zc-sec-line" aria-hidden="true"></span>
            </div>
            <div class="zc-faq">
                <details open>
                    <summary>چه مدت زمان می‌برد تا پروژه تحویل داده شود؟</summary>
                    <p>بازهٔ استاندارد اجرای این خدمت <strong><?php echo esc_html( $duration ); ?></strong> است؛ برنامهٔ زمانی دقیق پس از مشاورهٔ رایگان و مشخص شدن دامنهٔ کار اعلام می‌شود.</p>
                </details>
                <details>
                    <summary>پرداخت به چه صورت انجام می‌شود؟</summary>
                    <p>با قرارداد رسمی، <strong><?php echo esc_html( $prepayment ); ?> پیش‌پرداخت</strong> برای شروع کار و مابقی در زمان‌های توافق‌شدهٔ تحویل. همهٔ مراحل مکتوب و شفاف است.</p>
                </details>
                <details>
                    <summary>اگر از نتیجه راضی نباشم چه می‌شود؟</summary>
                    <p>این خدمت <strong>گارانتی بازگشت وجه: <?php echo esc_html( $refund ); ?></strong> و <strong>ضمانت کیفیت: <?php echo esc_html( $quality ); ?></strong> دارد؛ ضمناً <?php echo esc_html( $revisions ); ?> اصلاح و بازبینی رایگان پس از تحویل انجام می‌شود.</p>
                </details>
                <details>
                    <summary>در پایان پروژه چه چیزی تحویل می‌گیرم؟</summary>
                    <p><?php echo esc_html( $delivery ); ?>؛ به‌همراه آموزش استفاده و مستندات لازم تا بدون وابستگی، مدیریت کامل داشته باشید.</p>
                </details>
                <details>
                    <summary>امکان انجام پروژه به‌صورت حضوری وجود دارد؟</summary>
                    <p>نحوهٔ استاندارد انجام کار <strong><?php echo esc_html( $mode ); ?></strong> است؛ در صورت نیاز، جلسات حضوری در تهران قابل هماهنگی است.</p>
                </details>
            </div>
        </section>

        <!-- =============================================== CTA band -->
        <section class="zc-cta-band" style="margin-top:1.5rem;" aria-label="دعوت به شروع پروژه">
            <div>
                <h2>برای «<?php the_title(); ?>» آماده‌اید؟</h2>
                <p>همین حالا مشاورهٔ رایگان بگیرید؛ کمتر از ۱۵ دقیقه زمان می‌برد و هیچ تعهدی ایجاد نمی‌کند.</p>
            </div>
            <div class="zc-eh-actions">
                <button type="button" class="zc-eh-btn zc-eh-btn-gold" data-zc-modal-open="zc-consult-modal">
                    <i class="fa-solid fa-comments"></i> درخواست مشاورهٔ رایگان
                </button>
                <a href="tel:09024561001" class="zc-eh-btn zc-eh-btn-ghost"><i class="fa-solid fa-phone"></i> تماس مستقیم</a>
            </div>
        </section>

        <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
    </main>

    <!-- mobile sticky bar: price + call -->
    <div class="zc-inner-mobilebar" role="region" aria-label="نوار سریع تماس">
        <div>
            <strong><?php echo esc_html( $price ); ?></strong>
            <small><?php echo esc_html( $discount ); ?> تخفیف جشنواره • مشاورهٔ رایگان</small>
        </div>
        <div style="display:flex;gap:.45rem;">
            <a href="#zc-srv-order" class="zc-eh-btn zc-eh-btn-gold"><i class="fa-solid fa-paper-plane"></i> درخواست</a>
            <a href="tel:09024561001" class="zc-eh-btn zc-eh-btn-ghost"><i class="fa-solid fa-phone"></i> تماس</a>
        </div>
    </div>
    <?php
endwhile;

get_footer();
