<?php
/**
 * Single Digital Product Template (Zhaket & ThemeForest Marketplace Layout + Crextio Sunshine UI)
 *
 * Renders the dedicated inner page for Ready-Made Digital Products (WordPress Themes,
 * Plugins, and Software Source Codes) with sticky license/purchase box, interactive
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
    $post_id      = get_the_ID();
    $zc_wc_product=function_exists('wc_get_product')?wc_get_product($post_id):null;
    $changelog=get_post_meta($post_id,'_zc_product_changelog',true);
    $slug         = get_post_field( 'post_name', $post_id );
    $title        = get_the_title();
    $phone        = zarincode_get_option( 'contact_phone', '09024561001' );
    $dev_name     = zarincode_get_option( 'developer_name', 'احسان نادری پناه (Ehsan NaderiPanah)' );
    $dev_img      = get_template_directory_uri() . '/assets/images/ehsan-contact-consultation.jpg';

    // Product metadata with rich defaults
    $raw_price    = get_post_meta( $post_id, '_zc_prod_price_display', true );
    if ( ! $raw_price ) {
        $wc_price  = get_post_meta( $post_id, '_regular_price', true ) ?: '0';
        $raw_price = zarincode_fa_digits( number_format( (int) $wc_price ) );
    }
    $old_price    = get_post_meta( $post_id, '_zc_prod_old_price', true ) ?: '';
    $version      = get_post_meta( $post_id, '_zc_prod_version', true ) ?: 'اعلام نشده';
    $cat_badge    = get_post_meta( $post_id, '_zc_prod_cat_badge', true ) ?: 'محصول دیجیتال آماده';
    $sales_cnt    = $zc_wc_product ? number_format_i18n($zc_wc_product->get_total_sales()) : '—';
    $compat       = get_post_meta( $post_id, '_zc_prod_compat', true ) ?: 'پیش‌نیازها را در توضیحات محصول بررسی کنید';
    $icon_class   = get_post_meta( $post_id, '_zc_prod_icon', true ) ?: 'fa-solid fa-Layer-group';
    $tags_str     = get_post_meta( $post_id, '_zc_prod_tags', true ) ?: '';
    $tags         = array_map( 'trim', explode( ',', $tags_str ) );
    $updated_date = get_post_meta( $post_id, '_zc_prod_updated', true ) ?: get_the_modified_date();
    $demo_url     = get_post_meta( $post_id, '_zc_prod_demo_url', true );
    if ( ! $demo_url ) {
        $demo_url = get_post_meta( $post_id, 'demo_url', true ) ?: home_url( '/?demo_preview=' . $slug );
    }
    $demo_label   = get_post_meta( $post_id, '_zc_prod_demo_label', true ) ?: 'پیش‌نمایش آنلاین دمو';

    $add_to_cart_url = function_exists( 'wc_get_cart_url' ) ? add_query_arg( 'add-to-cart', $post_id, wc_get_cart_url() ) : '#zc-consult-modal';
    ?>

    <main id="primary" class="zc-container zc-single-product-wrap" style="padding-top:2rem;padding-bottom:4.5rem;">
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

        <!-- Main 2-Column Marketplace Grid -->
        <div class="zc-single-product-grid">
            <!-- Right Column: Product Showcase, Preview & Interactive Technical Tabs -->
            <div class="zc-single-product-main">
                <!-- Product Hero Card -->
                <div class="zc-glass-panel" style="padding:2.25rem;margin-bottom:1.75rem;">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1rem;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;">
                            <span class="zc-badge-pill" style="margin-bottom:0;"><i class="fa-solid fa-award"></i> <?php echo esc_html( $cat_badge ); ?></span>
                            <span class="zc-trust-pill" style="padding:0.25rem 0.75rem;font-size:0.76rem;"><?php $review_product=function_exists("wc_get_product")?wc_get_product($post_id):null;if($review_product&&$review_product->get_rating_count()){echo wp_kses_post(wc_get_rating_html($review_product->get_average_rating(),$review_product->get_rating_count()));echo esc_html(sprintf("%s دیدگاه",number_format_i18n($review_product->get_review_count())));}else echo "هنوز امتیازی ثبت نشده است"; ?></span>
                        </div>
                        <span style="font-size:0.8rem;color:var(--zc-text-muted);font-weight:700;">
                            <i class="fa-regular fa-calendar-check"></i> آخرین بروزرسانی: <?php echo esc_html( $updated_date ); ?>
                        </span>
                    </div>

                    <h1 style="font-size:clamp(1.3rem,1.95vw,1.65rem);font-weight:900;margin:0 0 1.15rem;line-height:1.5;color:var(--zc-text-primary);">
                        <?php echo esc_html( $title ); ?>
                    </h1>

                    <!-- Native Woo gallery: attachments, thumbnail navigation, zoom and lightbox. -->
                    <div class="zc-product-media">
                        <div class="zc-product-media-label"><span><i class="fa-regular fa-image" aria-hidden="true"></i> نمای محصول</span><small>برای مشاهده جزئیات، تصویر را باز کنید</small></div>
                        <?php if($zc_wc_product){$GLOBALS['product']=$zc_wc_product;woocommerce_show_product_images();}else{echo get_the_post_thumbnail($post_id,'large',['class'=>'zc-product-cover']);} ?>
                        <noscript><style>.zc-product-media .woocommerce-product-gallery__wrapper{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.zc-product-media .woocommerce-product-gallery__wrapper>div:first-child{grid-column:1/-1}</style></noscript>
                    </div>
                    <div class="zc-dark-card" style="padding:1.5rem;margin-bottom:1.5rem;">
                        <p style="color:#d1d5db;font-size:.85rem;line-height:2;margin:0;"> <?php echo wp_kses_post(get_the_excerpt()); ?> </p>
                        <div class="zc-product-media-meta">
                            <?php $sku=$zc_wc_product?$zc_wc_product->get_sku():get_post_meta($post_id,'_sku',true);if($sku)echo '<span dir="ltr">SKU: '.esc_html($sku).'</span>';foreach(array_filter($tags) as $tag)echo '<span dir="auto">#'.esc_html($tag).'</span>'; ?>
                        </div>
                        <!-- 4 Quick Product KPIs inside Visual Banner -->
                        <div class="zc-prod-kpi-strip" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.85rem;margin-top:1.75rem;padding-top:1.35rem;border-top:1px solid rgba(255,255,255,0.1);">
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:var(--color-accent);"><?php echo esc_html( $sales_cnt ); ?></div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">فروش ثبت‌شده ووکامرس</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:#34D399;">مجوز استفاده</div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">مطابق مشخصات محصول</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:#fff;" dir="ltr"><?php echo esc_html( $version ); ?></div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">نسخه اعلام‌شده محصول</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.05);padding:0.85rem;border-radius:1rem;text-align:center;">
                                <div style="font-size:1.05rem;font-weight:900;color:var(--color-accent);">پشتیبانی شفاف</div>
                                <div style="font-size:0.74rem;color:#9CA3AF;margin-top:0.2rem;">مطابق سیاست این محصول</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Bar under Preview -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;background:rgba(253,208,91,0.14);border:1px solid rgba(253,208,91,0.45);border-radius:1.25rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <i class="fa-solid fa-bolt" style="font-size:1.35rem;color:var(--color-accent-contrast);background:var(--color-accent);width:2.4rem;height:2.4rem;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"></i>
                            <div>
                                <div style="font-weight:900;font-size:0.9rem;">پیش از سفارش، توضیحات و شیوه تحویل محصول را بررسی کنید</div>
                                <div style="font-size:0.77rem;color:var(--zc-text-secondary);">بلافاصله پس از تکمیل سفارش، لینک دانلود مستقیم در پنل کاربری و ایمیل شما فعال می‌شود.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.55rem;flex-wrap:wrap;">
                            <?php if ( ! empty( $demo_url ) ) : ?>
                                <a href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener" class="zc-btn zc-btn-yellow" style="padding:0.55rem 1.15rem;font-size:0.82rem;">
                                    <i class="fa-solid fa-desktop"></i> <?php echo esc_html( $demo_label ); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="zc-btn zc-btn-dark" style="padding:0.55rem 1.15rem;font-size:0.82rem;" data-zc-modal-open="zc-consult-modal">
                                <i class="fa-solid fa-headset"></i> مشاوره فنی پیش از خرید
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Product Details Tabs (Overview, Tech Specs, Changelog, FAQ & Guarantee) -->
                <div class="zc-glass-panel" style="padding:2.25rem;">
                    <div class="zc-prod-tabs-nav" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;border-bottom:2px solid var(--zc-border-subtle);padding-bottom:1.1rem;margin-bottom:1.75rem;">
                        <button type="button" class="zc-btn zc-btn-yellow zc-prod-tab-btn is-active" data-prod-tab="tab-overview" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-file-lines"></i> معرفی و امکانات
                        </button>
                        <button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-specs" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-sliders"></i> مشخصات فنی
                        </button>
                        <?php if($changelog): ?><button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-changelog" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-code-commit"></i> لیست تغییرات
                        </button><?php endif; ?>
                        <?php if(\Zarincode\Theme\Core\Product_Policies::get($post_id)): ?><button type="button" class="zc-btn zc-btn-glass zc-prod-tab-btn" data-prod-tab="tab-faq" style="padding:0.55rem 0.65rem;font-size:0.78rem;width:100%;">
                            <i class="fa-solid fa-shield-halved"></i> گارانتی و سوالات
                        </button><?php endif; ?>
                    </div>

                    <!-- TAB 1: Overview & Key Features -->
                    <div id="tab-overview" class="zc-prod-tab-pane" style="display:block;">
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
                    <div id="tab-specs" class="zc-prod-tab-pane" style="display:none;">
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
                    <div id="tab-changelog" class="zc-prod-tab-pane" style="display:none;"><?php echo wpautop(wp_kses_post($changelog)); ?></div>

                    <!-- TAB 4: Guarantee & FAQ (Redesigned Professional Crextio Accordion) -->
                    <div id="tab-faq" class="zc-prod-tab-pane" style="display:none;">
                        <?php \Zarincode\Theme\Core\Product_Policies::render($post_id); ?>
                    </div>
                </div>
            </div>

            <!-- Left Column: Sticky Purchase & Developer Sidebar (Zhaket / ThemeForest Style) -->
            <aside class="zc-single-product-sidebar">
                <!-- Purchase & License Bento Card -->
                <div class="zc-dark-card" style="padding:2rem;margin-bottom:1.5rem;border:2px solid var(--color-accent);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <span class="zc-prod-badge"><i class="fa-solid fa-bolt"></i> تحویل و دانلود آنی</span>
                        <span style="font-size:0.78rem;color:#9CA3AF;text-decoration:line-through;"><?php echo esc_html( $old_price ); ?> تومان</span>
                    </div>

                    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:1.25rem;padding:1.15rem;text-align:center;margin-bottom:1.35rem;">
                        <div style="font-size:0.78rem;color:#D1D5DB;margin-bottom:0.25rem;">قیمت محصول:</div>
                        <div style="font-size:2rem;font-weight:900;color:var(--color-accent);line-height:1.2;">
                            <?php echo esc_html( $raw_price ); ?>
                            <span style="font-size:0.9rem;font-weight:700;color:#fff;">تومان</span>
                        </div>
                    </div>

                    <!-- License Options -->
                    <div style="margin-bottom:1.25rem;display:flex;flex-direction:column;gap:0.6rem;">
                        <label style="display:flex;align-items:center;gap:0.6rem;padding:0.75rem 0.95rem;background:rgba(253,208,91,0.14);border:1px solid var(--color-accent);border-radius:0.9rem;cursor:pointer;font-size:0.82rem;font-weight:700;color:#fff;">
                            <input type="radio" name="zc_license_type" checked style="accent-color:var(--color-accent);">
                            <span>لایسنس استاندارد نامحدود + پشتیبانی شفاف</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.6rem;padding:0.75rem 0.95rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:0.9rem;cursor:pointer;font-size:0.82rem;color:#D1D5DB;">
                            <input type="radio" name="zc_license_type" style="accent-color:var(--color-accent);">
                            <span>خدمات نصب فقط در صورت درج در مشخصات</span>
                        </label>
                    </div>

                    <!-- 6 SLA & Guarantee Checkmarks -->
                    <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:0.65rem;font-size:0.83rem;color:#E5E7EB;">
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> کدنویسی اختصاصی احسان نادری پناه</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> مجوز و محدودیت‌های استفاده را بررسی کنید</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> محدوده پشتیبانی طبق سیاست محصول</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> شرایط به‌روزرسانی طبق توضیحات فروشنده</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:var(--color-accent);"></i> شامل مستندات فارسی و فایل درون‌ریز دمو</li>
                        <li style="display:flex;align-items:center;gap:0.55rem;"><i class="fa-solid fa-circle-check" style="color:#34D399;"></i> شرایط پشتیبانی مطابق سیاست این محصول</li>
                    </ul>

                    <!-- Primary Purchase CTA Buttons -->
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <?php if ( function_exists( 'WC' ) ) : ?>
                            <a href="<?php echo esc_url( $add_to_cart_url ); ?>" class="zc-btn zc-btn-yellow" style="width:100%;padding:0.95rem;font-size:0.95rem;">
                                <i class="fa-solid fa-cart-shopping"></i> افزودن به سبد خرید و دانلود آنی
                            </a>
                        <?php endif; ?>
                        <?php if ( ! empty( $demo_url ) ) : ?>
                            <a href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener" class="zc-btn" style="width:100%;padding:0.85rem;font-size:0.88rem;background:rgba(253,208,91,0.16);color:#FDD05B;border:1.5px solid var(--color-accent);">
                                <i class="fa-solid fa-desktop"></i> <?php echo esc_html( $demo_label ); ?>
                            </a>
                        <?php endif; ?>
                        <button type="button" class="zc-btn <?php echo function_exists( 'WC' ) ? 'zc-btn-glass' : 'zc-btn-yellow'; ?>" style="width:100%;padding:0.85rem;font-size:0.88rem;<?php echo function_exists( 'WC' ) ? 'background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);' : ''; ?>" data-zc-modal-open="zc-consult-modal">
                            <i class="fa-solid fa-bolt"></i> خرید سریع / سفارش سفارشی‌سازی
                        </button>
                        <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="zc-btn" style="width:100%;padding:0.75rem;font-size:0.84rem;background:rgba(255,255,255,0.06);color:#FDD05B;border:1px solid rgba(253,208,91,0.3);">
                            <i class="fa-regular fa-comments"></i> پرسش پیش از خرید
                        </a>
                    </div>
                </div>

                <!-- Developer / Author Real Portrait Card -->
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
