<?php
/**
 * Single Portfolio Project Template (Product-Page Design Pattern + Crextio Sunshine UI)
 *
 * Mirrors the approved single-product.php anatomy (breadcrumb bar → two-column
 * grid → hero glass panel with media row + dark KPI card → action buttons →
 * interactive tabs → sticky detail sidebar → developer card) so portfolio
 * case studies share the exact same professional inner-page language as the
 * marketplace product pages.
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
    $title    = get_the_title();
    $client   = get_post_meta( $post_id, '_zc_prj_client', true ) ?: 'مجموعه تجاری برتر';
    $agency   = get_post_meta( $post_id, '_zc_prj_agency', true ) ?: 'زرین کد (احسان نادری پناه)';
    $date     = get_post_meta( $post_id, '_zc_prj_date', true ) ?: 'فروردین ۱۴۰۵';
    $duration = get_post_meta( $post_id, '_zc_prj_duration', true ) ?: '۲۵ روز کاری';
    $cms      = get_post_meta( $post_id, '_zc_prj_cms', true ) ?: 'وردپرس + المنتور اختصاصی';
    $plan     = get_post_meta( $post_id, '_zc_prj_plan', true ) ?: 'پلن الماسی + خدمات سئو';
    $metrics  = get_post_meta( $post_id, '_zc_prj_metrics', true ) ?: '+۳۲۰٪ رشد فروش آنلاین';
    $url      = get_post_meta( $post_id, '_zc_prj_url', true ) ?: 'https://zarincode.com';
    $has_live = $url && $url !== 'https://zarincode.com';
    $dev_name = zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' );
    $dev_img  = get_template_directory_uri() . '/assets/images/ehsan-contact-consultation.jpg';
    $thumb_id = get_post_thumbnail_id( $post_id );
    ?>
    <main id="primary" class="zc-container zc-single-product-wrap" style="padding-top:2rem;padding-bottom:4.5rem;" role="main">

        <!-- Breadcrumb Bar (product-page glass pill) -->
        <nav class="zc-breadcrumb-bar" aria-label="Breadcrumb" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;padding:0.85rem 1.35rem;background:rgba(255,255,255,0.65);border:1px solid var(--zc-border-subtle);border-radius:1rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.55rem;font-size:0.84rem;color:var(--zc-text-secondary);">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;"><i class="fa-solid fa-house"></i> صفحه اصلی</a>
                <span>/</span>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'zc_project' ) ?: home_url( '/projects-portfolio/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;">نمونه‌کارها</a>
                <span>/</span>
                <span style="color:var(--color-accent-contrast);background:var(--color-accent);padding:0.15rem 0.65rem;border-radius:999px;font-weight:800;font-size:0.76rem;">نمونه‌کار ویژه</span>
                <span>/</span>
                <span style="font-weight:800;color:var(--zc-text-primary);"><?php echo esc_html( $title ); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;font-size:0.8rem;">
                <span style="color:#059669;font-weight:800;"><i class="fa-solid fa-circle-check"></i> کیس‌استدی واقعی و قابل استناد</span>
                <span class="zc-meta-tag" dir="ltr"><?php echo esc_html( $duration ); ?></span>
            </div>
        </nav>

        <!-- Main 2-Column Grid (product-page layout) -->
        <div class="zc-single-product-grid">
            <!-- Right/Main Column: Case Study Showcase & Interactive Tabs -->
            <div class="zc-single-product-main">
                <!-- Project Hero Card -->
                <div class="zc-glass-panel" style="padding:2.25rem;margin-bottom:1.75rem;">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1rem;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;">
                            <span class="zc-badge-pill" style="margin-bottom:0;"><i class="fa-solid fa-briefcase"></i> ZARINCODE CASE STUDY</span>
                            <span class="zc-trust-pill" style="padding:0.25rem 0.75rem;font-size:0.76rem;"><i class="fa-solid fa-chart-line" style="color:var(--color-accent);"></i> <?php echo esc_html( $metrics ); ?></span>
                        </div>
                        <span style="font-size:0.8rem;color:var(--zc-text-muted);font-weight:700;">
                            <i class="fa-regular fa-calendar-check"></i> زمان اجرا: <?php echo esc_html( $date ); ?>
                        </span>
                    </div>

                    <h1 style="font-size:clamp(1.3rem,1.95vw,1.65rem);font-weight:900;margin:0 0 1.15rem;line-height:1.5;color:var(--zc-text-primary);">
                        <?php echo esc_html( $title ); ?>
                    </h1>

                    <div class="zc-product-reference-row">
                        <!-- Project Visual -->
                        <div class="zc-product-media">
                            <div class="zc-product-media-label"><span><i class="fa-regular fa-image" aria-hidden="true"></i> نمای پروژه</span><small>برای مشاهده جزئیات، تصویر را باز کنید</small></div>
                            <?php if ( $thumb_id ) : ?>
                                <?php echo get_the_post_thumbnail( $post_id, 'large', [ 'class' => 'zc-product-cover', 'style' => 'width:100%;height:auto;border-radius:10px;display:block;' ] ); ?>
                            <?php else : ?>
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.6rem;min-height:220px;border-radius:10px;background:linear-gradient(160deg,#FFF5D1,#FDD05B55);border:1px dashed #d9b84a;color:#8a6d1d;">
                                    <i class="fa-solid fa-briefcase" style="font-size:2rem;"></i>
                                    <span style="font-size:0.8rem;font-weight:800;">تصویر اختصاصی این پروژه به‌زودی</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="zc-dark-card zc-product-reference-info" style="padding:1.5rem;margin-bottom:1.5rem;">
                            <p style="color:#d1d5db;font-size:.85rem;line-height:2;margin:0;"><?php echo wp_kses_post( get_the_excerpt() ?: get_the_title() ); ?></p>
                            <!-- 4 Quick Project KPIs inside Dark Card (product-page KPI strip) -->
                            <div class="zc-prod-kpi-strip" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.85rem;margin-top:1.75rem;padding-top:1.35rem;border-top:1px solid rgba(255,255,255,0.1);">
                                <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                    <div style="font-size:0.92rem;font-weight:900;color:var(--color-accent);"><?php echo esc_html( $client ); ?></div>
                                    <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">کارفرما / برند</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                    <div style="font-size:0.92rem;font-weight:900;color:#34D399;"><?php echo esc_html( $duration ); ?></div>
                                    <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">مدت اجرا</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                    <div style="font-size:0.92rem;font-weight:900;color:#fff;"><?php echo esc_html( $cms ); ?></div>
                                    <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">تکنولوژی / CMS</div>
                                </div>
                                <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                    <div style="font-size:0.92rem;font-weight:900;color:var(--color-accent);"><?php echo esc_html( $metrics ); ?></div>
                                    <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">نتیجه و دستاورد</div>
                                </div>
                            </div>
                        </div>
                    </div><!-- .zc-product-reference-row -->

                    <div class="zc-product-reference-actions" aria-label="سفارش و بازدید پروژه">
                        <button type="button" class="zc-btn zc-btn-yellow zc-product-reference-buy" data-zc-modal-open="zc-consult-modal"><i class="fa-solid fa-rocket" aria-hidden="true"></i> سفارش پروژه مشابه برای کسب‌وکار شما</button>
                        <?php if ( $has_live ) : ?>
                            <a class="zc-btn zc-product-reference-demo" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-desktop" aria-hidden="true"></i> مشاهده زنده پروژه</a>
                        <?php endif; ?>
                        <a class="zc-btn zc-product-reference-demo" href="<?php echo esc_url( get_post_type_archive_link( 'zc_project' ) ?: home_url( '/projects-portfolio/' ) ); ?>"><i class="fa-solid fa-images" aria-hidden="true"></i> سایر نمونه‌کارها</a>
                    </div>
                </div>

                <!-- Project Details Tabs (Overview, Specs & Results) -->
                <div class="zc-glass-panel" style="padding:2.25rem;">
                    <div class="zc-prod-tabs-nav" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;border-bottom:2px solid var(--zc-border-subtle);padding-bottom:1.1rem;margin-bottom:1.75rem;">
                        <button type="button" class="zc-btn zc-btn-yellow zc-prod-tab-btn is-active" data-prod-tab="prj-tab-overview" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-file-lines"></i> معرفی و روند اجرا
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="prj-tab-specs" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-sliders"></i> مشخصات پروژه
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="prj-tab-results" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-trophy"></i> نتایج و دستاوردها
                        </button>
                    </div>

                    <!-- TAB 1: Overview & Execution Story -->
                    <div id="prj-tab-overview" class="zc-prod-tab-pane" style="display:block;">
                        <div style="font-size:0.96rem;line-height:2;color:var(--zc-text-primary);margin-bottom:2rem;">
                            <?php the_content(); ?>
                        </div>

                        <h2 style="font-size:1.2rem;font-weight:900;margin:0 0 1.25rem;">
                            <i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> روند اجرای این پروژه
                        </h2>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;margin-bottom:1.5rem;">
                            <?php
                            $phases = [
                                [ 'icon' => 'fa-solid fa-lightbulb', 'title' => 'تحلیل و نقشه راه', 'desc' => 'نیازسنجی دقیق کسب‌وکار، تحلیل رقبا و تدوین نقشه راه اجرایی پیش از شروع توسعه.' ],
                                [ 'icon' => 'fa-solid fa-pen-ruler', 'title' => 'طراحی تجربه و رابط کاربری', 'desc' => 'طراحی صفحات بر اساس ساختار برند، سلسله‌مراتب محتوا و مسیر تبدیل بازدیدکننده.' ],
                                [ 'icon' => 'fa-solid fa-code', 'title' => 'توسعه اختصاصی', 'desc' => 'پیاده‌سازی تمیز و استاندارد روی وردپرس، بهینه‌شده برای سرعت و سئوی فنی.' ],
                                [ 'icon' => 'fa-solid fa-rocket', 'title' => 'تحویل و پشتیبانی', 'desc' => 'آموزش کار با پنل، مستندسازی و پشتیبانی مطابق پلن اجرایی این پروژه.' ],
                            ];
                            foreach ( $phases as $ph ) :
                                ?>
                                <div class="zc-white-card" style="padding:1.25rem;">
                                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                                        <span style="width:2.4rem;height:2.4rem;border-radius:0.75rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                                            <i class="<?php echo esc_attr( $ph['icon'] ); ?>"></i>
                                        </span>
                                        <h3 style="margin:0;font-size:0.95rem;font-weight:800;"><?php echo esc_html( $ph['title'] ); ?></h3>
                                    </div>
                                    <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;"><?php echo esc_html( $ph['desc'] ); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 2: Project Specifications -->
                    <div id="prj-tab-specs" class="zc-prod-tab-pane" style="display:none;">
                        <h2 style="font-size:1.15rem;font-weight:900;margin:0 0 1.25rem;">مشخصات فنی و اجرایی پروژه</h2>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0.85rem;">
                            <?php
                            $specs = [
                                'نام پروژه'   => $title,
                                'کارفرما'     => $client,
                                'مجری پروژه'  => $agency,
                                'تاریخ اجرا'  => $date,
                                'مدت اجرا'    => $duration,
                                'تکنولوژی'    => $cms,
                                'پلن اجرایی'  => $plan,
                            ];
                            if ( $has_live ) {
                                $specs['لینک زنده'] = $url;
                            }
                            foreach ( $specs as $sk => $sv ) :
                                ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:0.95rem 1.15rem;background:rgba(255,255,255,0.8);border:1px solid var(--zc-border-subtle);border-radius:0.95rem;font-size:0.86rem;">
                                    <span style="color:var(--zc-text-secondary);font-weight:700;"><?php echo esc_html( $sk ); ?>:</span>
                                    <strong style="color:var(--zc-text-primary);text-align:left;overflow-wrap:anywhere;"><?php echo esc_html( $sv ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 3: Results & Achievements -->
                    <div id="prj-tab-results" class="zc-prod-tab-pane" style="display:none;">
                        <div class="zc-yellow-card" style="padding:1.5rem;margin-bottom:1.5rem;display:flex;flex-wrap:wrap;align-items:center;gap:1rem;">
                            <span style="width:3rem;height:3rem;border-radius:1rem;background:rgba(0,0,0,0.12);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;"><i class="fa-solid fa-chart-line"></i></span>
                            <div>
                                <div style="font-size:0.8rem;font-weight:800;opacity:0.8;">نتیجه کلیدی این پروژه</div>
                                <div style="font-size:1.35rem;font-weight:900;margin-top:0.15rem;"><?php echo esc_html( $metrics ); ?></div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;">
                            <div class="zc-white-card" style="padding:1.25rem;">
                                <h3 style="margin:0 0 0.6rem;font-size:0.95rem;font-weight:800;display:flex;align-items:center;gap:0.5rem;"><i class="fa-solid fa-gauge-high" style="color:var(--color-accent);"></i> عملکرد و سرعت</h3>
                                <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;">بهینه‌سازی سرعت بارگذاری، کاهش حجم منابع و تجربه روان روی موبایل و دسکتاپ.</p>
                            </div>
                            <div class="zc-white-card" style="padding:1.25rem;">
                                <h3 style="margin:0 0 0.6rem;font-size:0.95rem;font-weight:800;display:flex;align-items:center;gap:0.5rem;"><i class="fa-solid fa-magnifying-glass-chart" style="color:var(--color-accent);"></i> سئو و دیده‌شدن</h3>
                                <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;">ساختار محتوایی و نشانه‌گذاری استاندارد برای رشد پایدار در نتایج جستجو.</p>
                            </div>
                            <div class="zc-white-card" style="padding:1.25rem;">
                                <h3 style="margin:0 0 0.6rem;font-size:0.95rem;font-weight:800;display:flex;align-items:center;gap:0.5rem;"><i class="fa-solid fa-shield-halved" style="color:var(--color-accent);"></i> پایداری و پشتیبانی</h3>
                                <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;">تحویل مستند، آموزش تیم کارفرما و پشتیبانی شفاف مطابق پلن اجرایی پروژه.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left Column: Sticky Project Detail & Developer Sidebar -->
            <aside class="zc-single-product-sidebar">
                <!-- Project Details Bento Card -->
                <div class="zc-dark-card zc-product-sticky-sidebar" style="padding:2rem;margin-bottom:1.5rem;border:2px solid var(--color-accent);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <span class="zc-prod-badge"><i class="fa-solid fa-bolt"></i> اجرا توسط زرین کد</span>
                        <span style="font-size:0.78rem;color:#9CA3AF;"><?php echo esc_html( $date ); ?></span>
                    </div>

                    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:1.25rem;padding:1.15rem;text-align:center;margin-bottom:1.35rem;">
                        <div style="font-size:0.78rem;color:#D1D5DB;margin-bottom:0.25rem;">نتیجه این پروژه:</div>
                        <div style="font-size:1.55rem;font-weight:900;color:var(--color-accent);line-height:1.35;">
                            <?php echo esc_html( $metrics ); ?>
                        </div>
                    </div>

                    <!-- Project Fact Sheet -->
                    <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:0.65rem;font-size:0.83rem;color:#E5E7EB;">
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> کارفرما: <?php echo esc_html( $client ); ?></li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> مدت اجرا: <?php echo esc_html( $duration ); ?></li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> CMS: <?php echo esc_html( $cms ); ?></li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> پلن: <?php echo esc_html( $plan ); ?></li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:#34D399;"></i> مجری: <?php echo esc_html( $agency ); ?></li>
                    </ul>

                    <!-- Sidebar CTA Buttons -->
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <button type="button" class="zc-btn zc-btn-yellow" style="width:100%;padding:0.95rem;font-size:0.95rem;" data-zc-modal-open="zc-consult-modal">
                            <i class="fa-solid fa-rocket"></i> سفارش پروژه مشابه
                        </button>
                        <?php if ( $has_live ) : ?>
                            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="zc-btn" style="width:100%;padding:0.85rem;font-size:0.88rem;background:rgba(253,208,91,0.16);color:#FDD05B;border:1.5px solid var(--color-accent);">
                                <i class="fa-solid fa-desktop"></i> مشاهده زنده پروژه
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" class="zc-btn" style="width:100%;padding:0.75rem;font-size:0.84rem;background:rgba(255,255,255,0.06);color:#FDD05B;border:1px solid rgba(253,208,91,0.3);">
                            <i class="fa-regular fa-comments"></i> گفتگو درباره پروژه شما
                        </a>
                    </div>
                </div>

                <!-- Developer / Author Real Portrait Card -->
                <div class="zc-glass-panel" style="padding:1.5rem;">
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <img src="<?php echo esc_url( $dev_img ); ?>" alt="<?php echo esc_attr( $dev_name ); ?>" style="width:68px;height:68px;border-radius:1.1rem;object-fit:cover;border:2px solid var(--color-accent);flex-shrink:0;">
                        <div>
                            <span style="display:inline-block;background:var(--color-accent);color:var(--color-accent-contrast);padding:0.15rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:900;margin-bottom:0.25rem;">OFFICIAL AUTHOR</span>
                            <h3 style="margin:0;font-size:1rem;font-weight:900;"><?php echo esc_html( $dev_name ); ?></h3>
                            <div style="font-size:0.78rem;color:var(--zc-text-secondary);">معمار نرم‌افزار و مدیر فنی زرین کد</div>
                        </div>
                    </div>
                    <p style="font-size:0.82rem;color:var(--zc-text-secondary);line-height:1.8;margin:0 0 1rem;">
                        برای شناخت ارائه‌دهنده و شیوه همکاری، صفحه معرفی را ببینید. شرایط هر پروژه در جلسه مشاوره مشخص می‌شود.
                    </p>
                    <div style="display:flex;gap:0.5rem;">
                        <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="zc-btn zc-btn-dark" style="flex:1;padding:0.5rem;font-size:0.78rem;">رزومه توسعه‌دهنده</a>
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'zc_project' ) ?: home_url( '/projects-portfolio/' ) ); ?>" class="zc-btn zc-btn-glass" style="flex:1;padding:0.5rem;font-size:0.78rem;">سایر نمونه‌کارها</a>
                    </div>
                </div>
            </aside>
        </div>

        <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
    </main>

    <script>
    (function(){
        const tabBtns = document.querySelectorAll('.zc-prod-tab-btn');
        const tabPanes = document.querySelectorAll('.zc-prod-tab-pane');
        tabBtns.forEach(function(btn){
            btn.addEventListener('click', function(){
                const target = btn.getAttribute('data-prod-tab');
                tabBtns.forEach(function(b){
                    b.classList.remove('zc-btn-yellow', 'is-active');
                    b.classList.add('zc-btn-glass');
                });
                tabPanes.forEach(function(p){ p.style.display = 'none'; });
                btn.classList.remove('zc-btn-glass');
                btn.classList.add('zc-btn-yellow', 'is-active');
                const pane = document.getElementById(target);
                if (pane) pane.style.display = 'block';
            });
        });
    })();
    </script>

<?php
endwhile;

get_footer();
