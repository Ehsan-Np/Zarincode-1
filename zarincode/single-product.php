<?php
/**
 * Single Digital Product Template (Zhaket & ThemeForest Marketplace Layout + Crextio Sunshine UI)
 *
 * Renders the dedicated inner page for Ready-Made Digital Products (WordPress Themes,
 * Plugins, and Software Source Codes) with a native WooCommerce purchase summary, interactive
 * technical tabs, developer profile card, and related products.
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
    if(post_password_required()){echo '<main class="zc-container">'.get_the_password_form().'</main>';continue;}
    $post_id      = get_the_ID();
    $zc_wc_product=function_exists('wc_get_product')?wc_get_product($post_id):null;
    $changelog=get_post_meta($post_id,'_zc_product_changelog',true);
    $title        = get_the_title();
    $dev_name     = zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' );
    $dev_img      = get_template_directory_uri() . '/assets/images/ehsan-contact-consultation.jpg';

    // Product metadata; commerce pricing is always supplied by WooCommerce.
    $version      = get_post_meta( $post_id, '_zc_prod_version', true ) ?: 'اعلام نشده';
    $cat_badge    = get_post_meta( $post_id, '_zc_prod_cat_badge', true ) ?: 'محصول دیجیتال آماده';
    $compat       = get_post_meta( $post_id, '_zc_prod_compat', true ) ?: 'پیش‌نیازها را در توضیحات محصول بررسی کنید';
    $updated_date = get_post_meta( $post_id, '_zc_prod_updated', true ) ?: get_the_modified_date();
    $demo_url     = get_post_meta( $post_id, '_zc_prod_demo_url', true );
    if ( ! $demo_url ) {
        $demo_url = get_post_meta( $post_id, 'demo_url', true ) ?: '';
    }
    $demo_label   = get_post_meta( $post_id, '_zc_prod_demo_label', true ) ?: 'پیش‌نمایش آنلاین دمو';

    ?>

    <main id="primary" class="zc-container zc-single-product-wrap" style="padding-top:2rem;padding-bottom:4.5rem;">
        <?php if($zc_wc_product)do_action('woocommerce_before_single_product'); ?>
        <!-- Breadcrumb Bar -->
        <nav class="zc-breadcrumb-bar" aria-label="Breadcrumb" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;padding:0.85rem 1.35rem;background:rgba(255,255,255,0.65);border:1px solid var(--zc-border-subtle);border-radius:1rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.55rem;font-size:0.84rem;color:var(--zc-text-secondary);">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;"><i class="fa-solid fa-house"></i> صفحه اصلی</a>
                <span>/</span>
                <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" style="color:var(--zc-text-primary);font-weight:700;">فروشگاه محصولات آماده</a>
                <span>/</span>
                <span style="color:var(--color-accent-contrast);background:var(--color-accent);padding:0.15rem 0.65rem;border-radius:999px;font-weight:800;font-size:0.76rem;"><?php echo esc_html( $cat_badge ); ?></span>
                <span>/</span>
                <span style="font-weight:800;color:var(--zc-text-primary);"><?php echo esc_html( $title ); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;font-size:0.8rem;">
                <span style="color:#059669;font-weight:800;"><i class="fa-solid fa-circle-check"></i> مشخصات و شرایط را پیش از خرید بررسی کنید</span>
                <span class="zc-meta-tag" dir="ltr"><?php echo esc_html( $version ); ?></span>
            </div>
        </nav>

        <?php if($zc_wc_product)\Zarincode\Theme\Core\Presentation::hero($zc_wc_product,['category'=>$cat_badge,'version'=>$version,'updated'=>$updated_date,'demo'=>\Zarincode\Theme\Core\Social_Manager::url($demo_url),'demo_label'=>$demo_label]); ?>
        <?php if(!$zc_wc_product): ?><header class="zc-market-hero"><div><h1><?php the_title(); ?></h1><?php the_excerpt(); ?></div><div><?php the_post_thumbnail('large'); ?></div></header><?php endif; ?>
        <div class="zc-single-product-grid">
            <div class="zc-single-product-main">
                <div class="zc-glass-panel" style="padding:2.25rem;">
                    <div role="tablist" aria-label="جزئیات محصول" class="zc-prod-tabs-nav" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;border-bottom:2px solid var(--zc-border-subtle);padding-bottom:1.1rem;margin-bottom:1.75rem;">
                        <button type="button" class="zc-btn zc-btn-yellow zc-prod-tab-btn is-active" role="tab" id="zc-tab-overview" aria-controls="tab-overview" aria-selected="true" tabindex="0" data-prod-tab="tab-overview" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-file-lines"></i> معرفی و امکانات
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" role="tab" id="zc-tab-specs" aria-controls="tab-specs" aria-selected="false" tabindex="-1" data-prod-tab="tab-specs" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-sliders"></i> مشخصات فنی
                        </button>
                        <?php if($changelog): ?><button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" role="tab" id="zc-tab-changelog" aria-controls="tab-changelog" aria-selected="false" tabindex="-1" data-prod-tab="tab-changelog" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-code-commit"></i> لیست تغییرات
                        </button><?php endif; ?>
                        <?php if(\Zarincode\Theme\Core\Product_Policies::get($post_id)): ?><button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" role="tab" id="zc-tab-faq" aria-controls="tab-faq" aria-selected="false" tabindex="-1" data-prod-tab="tab-faq" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-shield-halved"></i> گارانتی و سوالات
                        </button><?php endif; ?>
                    </div>

                    <!-- TAB 1: Overview & Key Features -->
                    <div id="tab-overview" role="tabpanel" aria-labelledby="zc-tab-overview" tabindex="0" class="zc-prod-tab-pane" style="display:block;">
                        <div style="font-size:0.96rem;line-height:2;color:var(--zc-text-primary);margin-bottom:2rem;">
                            <?php the_content(); ?>

                        </div>

                        <h3 style="font-size:1.2rem;font-weight:900;margin:0 0 1.25rem;">
                            <i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> پیش از انتخاب بررسی کنید
                        </h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;margin-bottom:1.5rem;">
                            <?php
                            $highlights = [
                                ['icon'=>'fa-solid fa-list-check','title'=>'ویژگی‌های مورد نیاز','desc'=>'قابلیت‌های درج‌شده در توضیحات را با نیاز واقعی پروژه مقایسه کنید.'],
                                ['icon'=>'fa-solid fa-server','title'=>'سازگاری و پیش‌نیازها','desc'=>'نسخه‌ها، زیرساخت و وابستگی‌های لازم را پیش از سفارش بررسی کنید.'],
                                ['icon'=>'fa-solid fa-file-contract','title'=>'مجوز استفاده','desc'=>'نوع مجوز و محدودیت استفاده یا توسعه را از مشخصات محصول بخوانید.'],
                                ['icon'=>'fa-solid fa-headset','title'=>'پشتیبانی و تحویل','desc'=>'روش تحویل و تعهدات پشتیبانی مطابق سیاست این محصول است.'],
                            ];
                            foreach ( $highlights as $hl ) :
                                ?>
                                <div class="zc-white-card" style="padding:1.25rem;">
                                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                                        <span style="width:2.4rem;height:2.4rem;border-radius:0.75rem;background:var(--color-accent);color:var(--color-accent-contrast);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                                            <i class="<?php echo esc_attr( $hl['icon'] ); ?>"></i>
                                        </span>
                                        <h4 style="margin:0;font-size:0.95rem;font-weight:800;"><?php echo esc_html( $hl['title'] ); ?></h4>
                                    </div>
                                    <p style="margin:0;font-size:0.84rem;color:var(--zc-text-secondary);line-height:1.75;"><?php echo esc_html( $hl['desc'] ); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 2: Technical Specifications -->
                    <div id="tab-specs" role="tabpanel" aria-labelledby="zc-tab-specs" tabindex="0" class="zc-prod-tab-pane" style="display:none;">
                        <h3 style="font-size:1.15rem;font-weight:900;margin:0 0 1.25rem;">جدول مشخصات فنی و پیش‌نیازهای سرور</h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0.85rem;">
                            <?php
                            $specs = ['نام محصول'=>$title,'نسخه اعلام‌شده'=>$version,'پیش‌نیازها'=>$compat,'آخرین ویرایش اطلاعات'=>$updated_date,'پشتیبانی و مجوز'=>'مطابق توضیحات و سیاست این محصول'];
                            foreach ( $specs as $sk => $sv ) :
                                ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:0.95rem 1.15rem;background:rgba(255,255,255,0.8);border:1px solid var(--zc-border-subtle);border-radius:0.95rem;font-size:0.86rem;">
                                    <span style="color:var(--zc-text-secondary);font-weight:700;"><?php echo esc_html( $sk ); ?>:</span>
                                    <strong style="color:var(--zc-text-primary);text-align:left;"><?php echo esc_html( $sv ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 3: Changelog -->
                    <div id="tab-changelog" role="tabpanel" aria-labelledby="zc-tab-changelog" tabindex="0" class="zc-prod-tab-pane" style="display:none;"><?php echo wpautop(wp_kses_post($changelog)); ?></div>

                    <!-- TAB 4: Guarantee & FAQ (Redesigned Professional Crextio Accordion) -->
                    <div id="tab-faq" role="tabpanel" aria-labelledby="zc-tab-faq" tabindex="0" class="zc-prod-tab-pane" style="display:none;">
                        <?php \Zarincode\Theme\Core\Product_Policies::render($post_id); ?>
                    </div>
                </div>
            </div>

            <!-- Left Column: Sticky Purchase & Developer Sidebar (Zhaket / ThemeForest Style) -->
            <aside class="zc-single-product-sidebar">
                <div class="zc-glass-panel" style="padding:1.5rem;">
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <img src="<?php echo esc_url( $dev_img ); ?>" alt="<?php echo esc_attr( $dev_name ); ?>" style="width:68px;height:68px;border-radius:1.1rem;object-fit:cover;border:2px solid var(--color-accent);flex-shrink:0;">
                        <div>
                            <span style="display:inline-block;background:var(--color-accent);color:var(--color-accent-contrast);padding:0.15rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:900;margin-bottom:0.25rem;">OFFICIAL AUTHOR</span>
                            <h4 style="margin:0;font-size:1rem;font-weight:900;"><?php echo esc_html( $dev_name ); ?></h4>
                            <div style="font-size:0.78rem;color:var(--zc-text-secondary);">معمار نرم‌افزار و مدیر فنی زرین کد</div>
                        </div>
                    </div>
                    <p style="font-size:0.82rem;color:var(--zc-text-secondary);line-height:1.8;margin:0 0 1rem;">
                        برای شناخت ارائه‌دهنده و شیوه همکاری، صفحه معرفی را ببینید. شرایط هر محصول در توضیحات و سیاست مربوط به همان محصول مشخص می‌شود.
                    </p>
                    <div style="display:flex;gap:0.5rem;">
                        <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="zc-btn zc-btn-dark" style="flex:1;padding:0.5rem;font-size:0.78rem;">رزومه توسعه‌دهنده</a>
                        <a href="<?php echo esc_url( home_url( '/warranty-policy/' ) ); ?>" class="zc-btn zc-btn-glass" style="flex:1;padding:0.5rem;font-size:0.78rem;">شرایط گارانتی</a>
                    </div>
                </div>
            </aside>
        </div>

        <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
    </main>



<?php
if($zc_wc_product)do_action('woocommerce_after_single_product');
endwhile;
get_footer();
