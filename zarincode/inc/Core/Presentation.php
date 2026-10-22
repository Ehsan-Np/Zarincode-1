<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Shared sizing and product presentation; WooCommerce still owns transactions. */
final class Presentation {
    public static function boot():void {
        add_action('wp_enqueue_scripts',static function(){wp_enqueue_style('zarincode-cards',get_template_directory_uri().'/inc/EmbeddedCore/Widgets/assets/cards.css',[],ZARINCODE_VERSION);wp_enqueue_style('zarincode-presentation',get_template_directory_uri().'/assets/css/presentation.css',['zarincode-main'],ZARINCODE_VERSION);},30);
        add_action('after_setup_theme',static function(){
            if(get_option('zarincode_presentation_38'))return;
            $options=get_option('zarincode_opt',[]);
            if(is_array($options)&&isset($options['body_font_size'])&&(int)$options['body_font_size']===15){$options['body_font_size']=16;update_option('zarincode_opt',$options);$GLOBALS['zarincode_opt']=$options;}
            update_option('zarincode_presentation_38',1,false);
        },40);
    }
    public static function width($base,$scale):string {
        $base=is_scalar($base)?(float)$base:1360;$base=max(960,min(1920,$base));
        $scale=(int)$scale===100?100:115;
        return (string)round($base*$scale/100).'px';
    }
    public static function hero(\WC_Product $product,array $context):void {
        $GLOBALS['product']=$product;if(function_exists('WC')&&isset(WC()->structured_data))WC()->structured_data->generate_product_data($product);$id=$product->get_id();$author=(int)get_post_field('post_author',$id);
        ?>
        <section class="zc-market-hero product" aria-labelledby="zc-product-title">
            <div class="zc-market-summary summary entry-summary">
                <div class="zc-market-eyebrow"><span><?php echo esc_html($context['category']); ?></span><span><?php echo $product->get_rating_count()?wp_kses_post(wc_get_rating_html($product->get_average_rating(),$product->get_rating_count())):'هنوز امتیازی ثبت نشده است'; ?></span></div>
                <h1 id="zc-product-title"><?php echo esc_html($product->get_name()); ?></h1>
                <?php if($author>0&&get_userdata($author)): ?><a class="zc-market-seller" href="<?php echo esc_url(get_author_posts_url($author)); ?>"><?php echo get_avatar($author,32); ?><span>ارائه‌دهنده: <strong><?php echo esc_html(get_the_author_meta('display_name',$author)); ?></strong></span></a><?php endif; ?>
                <div class="zc-market-excerpt"><?php echo wp_kses_post(apply_filters('woocommerce_short_description',$product->get_short_description()?:wp_trim_words(wp_strip_all_tags(get_the_excerpt()),45))); ?></div>
                <dl class="zc-market-facts"><div><dt>نسخه محصول</dt><dd dir="auto"><?php echo esc_html($context['version']); ?></dd></div><div><dt>آخرین به‌روزرسانی</dt><dd><?php echo esc_html($context['updated']); ?></dd></div><div><dt>فروش ثبت‌شده</dt><dd><?php echo esc_html(number_format_i18n($product->get_total_sales())); ?></dd></div></dl>
                <?php if($product->get_attributes()): ?><ul class="zc-market-features"><?php $n=0;foreach($product->get_attributes() as $attribute){if(!$attribute->get_visible())continue;$value=$product->get_attribute($attribute->get_name());if(!$value)continue;echo '<li><i class="fa-solid fa-check" aria-hidden="true"></i><span>'.esc_html(wc_attribute_label($attribute->get_name())).': '.esc_html($value).'</span></li>';if(++$n>=4)break;} ?></ul><?php endif; ?>
                <div class="zc-market-purchase"><div class="zc-market-price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php woocommerce_template_single_add_to_cart(); ?></div>
                <div class="zc-market-secondary"><?php if($context['demo']): ?><a class="zc-btn zc-btn-glass" href="<?php echo esc_url($context['demo']); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo esc_html($context['demo_label']); ?></a><?php endif; ?><a href="#tab-specs" class="zc-market-spec-link" data-zc-product-specs>مشخصات و پیش‌نیازها <span aria-hidden="true">↙</span></a></div>
                <p class="zc-market-policy">نوع مجوز، شیوه تحویل و محدوده پشتیبانی را در مشخصات و سیاست این محصول بررسی کنید.</p>
            </div>
            <div class="zc-market-artwork"><div class="zc-product-media"><?php woocommerce_show_product_images(); ?><noscript><style>.zc-market-artwork .woocommerce-product-gallery__wrapper{display:grid;gap:8px}.zc-market-artwork .woocommerce-product-gallery__wrapper>div{display:block!important}.zc-prod-tab-pane{display:block!important}.zc-prod-tabs-nav{display:none!important}</style></noscript></div></div>
        </section>
        <?php
    }
}
