<?php
/** Shared editorial tools. Native WordPress discussion, WooCommerce review compatibility. */
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Single_Experience {
    public static function boot():void {
        add_action('wp_enqueue_scripts',static function(){
            if(!is_singular(['post','zc_service','zc_project','product']))return;
            wp_enqueue_style('zarincode-single',get_template_directory_uri().'/assets/css/single-experience.css',[],ZARINCODE_VERSION);
            wp_enqueue_script('zarincode-single',get_template_directory_uri().'/assets/js/single-experience.js',[],ZARINCODE_VERSION,true);
            if(comments_open()&&get_option('thread_comments'))wp_enqueue_script('comment-reply');
        });
        add_action('customize_register',static function($customizer){
            $customizer->add_section('zc_single_tools',['title'=>'صفحات تکی — ابزارهای محتوا','priority'=>150]);
            foreach(['share'=>'اشتراک‌گذاری و لینک کوتاه','author'=>'درباره نویسنده','navigation'=>'قبلی و بعدی','related'=>'مطالب مرتبط','comments'=>'دیدگاه‌ها و بررسی محصول'] as $key=>$label){
                $customizer->add_setting('zc_single_'.$key,['default'=>true,'sanitize_callback'=>static fn($v)=>(bool)$v]);
                $customizer->add_control('zc_single_'.$key,['section'=>'zc_single_tools','label'=>$label,'type'=>'checkbox']);
            }
        });
    }
    public static function related_ids(int $id,int $count=3):array {
        $type=get_post_type($id);$tax=['post'=>'category','product'=>'product_cat','zc_service'=>'zc_service_cat','zc_project'=>'zc_project_cat'][$type]??'';
        if($type==='product'&&function_exists('wc_get_related_products'))return array_values(array_filter(wc_get_related_products($id,$count),static function($p){$product=wc_get_product($p);return $product&&$product->is_visible()&&get_post_status($p)==='publish'&&!post_password_required($p);}));
        $terms=$tax?wp_get_post_terms($id,$tax,['fields'=>'ids']):[];
        if(is_wp_error($terms)||!$terms)return [];
        return get_posts(['post_type'=>$type,'post_status'=>'publish','post__not_in'=>[$id],'has_password'=>false,'posts_per_page'=>$count,'fields'=>'ids','no_found_rows'=>true,'ignore_sticky_posts'=>true,'tax_query'=>[['taxonomy'=>$tax,'field'=>'term_id','terms'=>$terms]]]);
    }
    public static function render():void {
        $id=get_the_ID();if(!$id||post_password_required($id))return;
        $type=get_post_type($id);$settings=[];foreach(['share','author','navigation','related','comments'] as $key)$settings[$key]=(bool)get_theme_mod('zc_single_'.$key,true);
        $settings=apply_filters('zarincode_single_sections',$settings,$type,$id);
        echo '<div class="zc-single-tools">';
        if(!empty($settings['share'])){
            $url=get_permalink($id);$title=get_the_title($id);$short=$type==='post'?(wp_get_shortlink($id)?:add_query_arg('p',$id,home_url('/'))):add_query_arg(['post_type'=>$type,'p'=>$id],home_url('/'));
            $links=['تلگرام'=>'https://t.me/share/url?url='.rawurlencode($url).'&text='.rawurlencode($title),'واتس‌اپ'=>'https://wa.me/?text='.rawurlencode($title.' '.$url),'لینکدین'=>'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url),'ایمیل'=>'mailto:?subject='.rawurlencode($title).'&body='.rawurlencode($url)];
            echo '<section class="zc-story-share" aria-label="اشتراک‌گذاری"><div><span class="zc-story-kicker">این مطلب را به اشتراک بگذارید</span><nav aria-label="شبکه‌های اشتراک‌گذاری">';
            foreach($links as $name=>$link)echo '<a href="'.esc_url($link).'" target="_blank" rel="noopener noreferrer nofollow">'.esc_html($name).'</a>';
            echo '<button type="button" data-zc-native-share data-url="'.esc_url($url).'" data-title="'.esc_attr($title).'">اشتراک‌گذاری</button></nav></div><div class="zc-shortlink"><label for="zc-shortlink-'.$id.'">لینک کوتاه</label><div><input id="zc-shortlink-'.$id.'" readonly dir="ltr" value="'.esc_attr($short).'"><button type="button" data-zc-copy="zc-shortlink-'.$id.'">کپی لینک</button></div><span data-zc-copy-status role="status" aria-live="polite"></span></div></section>';
        }
        if(!empty($settings['author'])){
            $author=(int)get_post_field('post_author',$id);$bio=get_the_author_meta('description',$author);
            echo '<section class="zc-author-box" aria-label="درباره نویسنده">'.get_avatar($author,88,'',get_the_author_meta('display_name',$author)).'<div><span class="zc-story-kicker">درباره نویسنده</span><h2><a href="'.esc_url(get_author_posts_url($author)).'">'.esc_html(get_the_author_meta('display_name',$author)).'</a></h2>';
            if($bio!=='')echo '<div class="zc-author-bio">'.wp_kses_post(wpautop($bio)).'</div>';
            echo '<a class="zc-author-archive" href="'.esc_url(get_author_posts_url($author)).'">مشاهده نوشته‌های نویسنده ←</a></div></section>';
        }
        if(!empty($settings['navigation'])){
            echo '<nav class="zc-story-navigation" aria-label="پیمایش محتوای هم‌نوع">';
            foreach([true=>'قبلی',false=>'بعدی'] as $previous=>$label){$adjacent=get_adjacent_post(false,'',(bool)$previous);if(!$adjacent||$adjacent->post_status!=='publish'||$adjacent->post_password!=='')continue;
                echo '<a href="'.esc_url(get_permalink($adjacent)).'"><span>'.esc_html($label).'</span><strong>'.esc_html(get_the_title($adjacent)).'</strong><span aria-hidden="true">'.($previous?'→':'←').'</span></a>';
            }echo '</nav>';
        }
        if(!empty($settings['related'])){
            $ids=self::related_ids($id);if($ids){
                $label=['product'=>'محصولات مرتبط','zc_service'=>'خدمات مرتبط','zc_project'=>'پروژه‌های مرتبط'][$type]??'نوشته‌های مرتبط';
                echo '<section class="zc-story-related"><div class="zc-story-heading"><span class="zc-story-kicker">ادامه مسیر کشف</span><h2>'.esc_html($label).'</h2></div><div class="zc-related-grid">';
                foreach($ids as $related){echo '<article><a href="'.esc_url(get_permalink($related)).'">';if(has_post_thumbnail($related))echo get_the_post_thumbnail($related,'zarincode-card',['loading'=>'lazy']);echo '<h3>'.esc_html(get_the_title($related)).'</h3></a><p>'.esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt($related)),20)).'</p>';
                    if($type==='product'&&function_exists('wc_get_product')){$product=wc_get_product($related);if($product)echo '<div class="zc-related-price">'.wp_kses_post($product->get_price_html()).'</div>';}
                    echo '</article>';
                }echo '</div></section>';
            }
        }
        if(!empty($settings['comments'])&&(comments_open($id)||get_comments_number($id)))comments_template();
        echo '</div>';
    }
}
