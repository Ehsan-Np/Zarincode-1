<?php
/** Shared editorial tools. Native WordPress discussion, WooCommerce review compatibility. */
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Single_Experience {
    public static function boot():void {
        add_action('wp_enqueue_scripts',static function(){
            if(!is_singular(['post','zc_service','zc_project','product'])&&!(is_singular()&&(comments_open()||get_comments_number())))return;
            wp_enqueue_style('zarincode-single',get_template_directory_uri().'/assets/css/single-experience.css',[],ZARINCODE_VERSION);
            wp_enqueue_script('zarincode-single',get_template_directory_uri().'/assets/js/single-experience.js',[],ZARINCODE_VERSION,true);
            if(comments_open()&&get_option('thread_comments'))wp_enqueue_script('comment-reply');
        });
        add_filter('woocommerce_product_review_comment_form_args',static function($args){
            $args['title_reply']='تجربه شما از این محصول';$args['title_reply_to']='پاسخ به %s';$args['label_submit']=Discussion::requested_parent((int)get_the_ID())?'ارسال پاسخ':'ثبت بررسی محصول';
            $args['comment_notes_before']='<p class="zc-review-note">تجربه واقعی خود را بنویسید؛ ایمیل شما منتشر نمی‌شود. دیدگاه‌ها طبق سیاست سایت بررسی می‌شوند.</p>';
            return $args;
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
    public static function share(int $id):void {
        $url=get_permalink($id);$title=get_the_title($id);$type=get_post_type($id);$short=$type==='post'?(wp_get_shortlink($id)?:add_query_arg('p',$id,home_url('/'))):add_query_arg(['post_type'=>$type,'p'=>$id],home_url('/'));
        echo '<section id="zc-article-share" class="zc-story-share zc-share-studio" aria-labelledby="zc-share-title-'.$id.'"><header class="zc-share-studio-head"><div><span class="zc-share-eyebrow">ایده‌ها برای به‌اشتراک‌گذاشتن‌اند</span><h2 id="zc-share-title-'.$id.'">این صفحه را برای کسی بفرستید.</h2></div><button class="zc-device-share" type="button" data-zc-native-share data-url="'.esc_url($url).'" data-title="'.esc_attr($title).'" aria-label="ارسال با برنامه‌های دستگاه"><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i><span>ارسال از دستگاه</span></button></header><nav class="zc-share-destinations" aria-label="انتخاب مقصد اشتراک">';
        foreach(Social_Links::share_urls($url,$title) as $network=>$link){$label=Social_Links::catalog()[$network][0]??'ایمیل';echo '<a data-network="'.esc_attr($network).'" href="'.esc_url($link).'" target="_blank" rel="noopener noreferrer nofollow" aria-label="'.esc_attr('اشتراک در '.$label).'"><span class="zc-destination-icon">'.Social_Links::icon($network).'</span><span class="zc-destination-name">'.esc_html($label).'</span></a>';}
        foreach(Social_Manager::rows() as $row){if(!$row['share_template'])continue;$destination=str_replace(['{url}','{title}','{text}'],[rawurlencode($url),rawurlencode($title),rawurlencode($title.' '.$url)],$row['share_template']);echo '<a href="'.esc_url($destination).'" target="_blank" rel="noopener noreferrer nofollow"><span class="zc-destination-icon">'.Social_Manager::icon($row).'</span><span class="zc-destination-name">'.esc_html($row['name']).'</span></a>';}
        echo '</nav><details class="zc-share-more"><summary>پیام‌رسان‌های دیگر <span aria-hidden="true">+</span></summary><div>';
        foreach(['bale','rubika','soroush'] as $network){$label=Social_Links::catalog()[$network][0];echo '<button type="button" data-zc-native-share data-zc-network="'.esc_attr($label).'" data-url="'.esc_url($url).'" data-title="'.esc_attr($title).'">'.Social_Links::icon($network).'<span>'.esc_html($label).'</span></button>';}
        echo '</div><p>برنامه را در انتخاب‌گر دستگاه انتخاب کنید؛ اگر در دسترس نباشد، لینک کپی می‌شود.</p></details><footer class="zc-share-linkbar"><label for="zc-shortlink-'.$id.'"><i class="fa-solid fa-link" aria-hidden="true"></i><span>پیوند کوتاه<small>برای ذخیره یا ارسال مستقیم</small></span></label><div class="zc-share-link-control"><input id="zc-shortlink-'.$id.'" type="text" readonly dir="ltr" aria-label="نشانی کوتاه این صفحه" value="'.esc_attr($short).'"><button type="button" data-zc-copy="zc-shortlink-'.$id.'"><i class="fa-regular fa-copy" aria-hidden="true"></i><span>کپی پیوند</span></button></div></footer><p class="zc-share-feedback" data-zc-copy-status role="status" aria-live="polite"></p></section>';
    }

    private static function author(int $id,bool $commerce):void {
        $author=(int)get_post_field('post_author',$id);$bio=get_the_author_meta('description',$author);
        echo '<section class="zc-author-box'.($commerce?' zc-product-maker':'').'" aria-label="'.($commerce?'ارائه‌دهنده محصول':'درباره نویسنده').'">'.get_avatar($author,96,'',get_the_author_meta('display_name',$author)).'<div><span class="zc-story-kicker">'.($commerce?'تخصص پشت این محصول':'صدای این مقاله').'</span><h2><a href="'.esc_url(get_author_posts_url($author)).'">'.esc_html(get_the_author_meta('display_name',$author)).'</a></h2>';
        if($bio!=='')echo '<div class="zc-author-bio">'.wp_kses_post(wpautop($bio)).'</div>';
        echo '<div class="zc-author-actions"><a class="zc-author-archive" href="'.esc_url(get_author_posts_url($author)).'">'.($commerce?'آشنایی با ارائه‌دهنده':'مقالات بیشتر از این نویسنده').' <span aria-hidden="true">←</span></a>';
        if($commerce)echo '<a href="'.esc_url(home_url('/contact-us/')).'" class="zc-maker-contact">پرسش پیش از خرید <i class="fa-regular fa-comment" aria-hidden="true"></i></a>';
        echo '</div></div><span class="zc-author-watermark" aria-hidden="true"></span></section>';
    }
    private static function navigation(bool $commerce):void {
        $unit=['product'=>'محصول','zc_service'=>'خدمت','zc_project'=>'پروژه'][get_post_type()]??'نوشته';
        echo '<nav class="zc-story-navigation" aria-label="پیمایش محتوای هم‌نوع">';
        foreach([true=>'قبلی',false=>'بعدی'] as $previous=>$label){$adjacent=get_adjacent_post(false,'',(bool)$previous);if(!$adjacent||$adjacent->post_status!=='publish'||$adjacent->post_password!=='')continue;
            $product=$commerce&&function_exists('wc_get_product')?wc_get_product($adjacent->ID):null;if($commerce&&(!$product||!$product->is_visible()))continue;
            echo '<a class="zc-adjacent-card '.($previous?'is-previous':'is-next').'" rel="'.($previous?'prev':'next').'" href="'.esc_url(get_permalink($adjacent)).'">';
            if(has_post_thumbnail($adjacent))echo get_the_post_thumbnail($adjacent,'thumbnail',['loading'=>'lazy','class'=>'zc-adjacent-thumb']);
            echo '<div><span class="zc-adjacent-label">'.esc_html($unit.' ').esc_html($label).'</span><h3>'.esc_html(get_the_title($adjacent)).'</h3>';
            if($product)echo '<div class="zc-adjacent-price">'.wp_kses_post($product->get_price_html()).'</div>';else echo '<time datetime="'.esc_attr(get_the_date('c',$adjacent)).'">'.esc_html(get_the_date('',$adjacent)).'</time>';
            echo '</div><span class="zc-adjacent-arrow" aria-hidden="true">'.($previous?'→':'←').'</span></a>';
        }echo '</nav>';
    }
    private static function related(int $id,bool $commerce):void {
        $ids=self::related_ids($id);if(!$ids)return;$type=get_post_type($id);$label=['product'=>'برای پروژه بعدی شما','zc_service'=>'راهکارهای مکمل کسب‌وکار','zc_project'=>'پروژه‌های مرتبط'][$type]??'داستان هنوز ادامه دارد';
        echo '<section class="zc-story-related"><div class="zc-story-heading"><div><span class="zc-story-kicker">'.($commerce?'انتخاب‌های مرتبط':'پیشنهاد سردبیر').'</span><h2>'.esc_html($label).'</h2></div><span class="zc-heading-line" aria-hidden="true"></span></div><div class="zc-related-grid">';
        foreach($ids as $i=>$related){echo '<article><a class="zc-related-cover" href="'.esc_url(get_permalink($related)).'">';if(has_post_thumbnail($related))echo get_the_post_thumbnail($related,'zarincode-card',['loading'=>'lazy']);else echo '<span class="zc-related-placeholder" aria-hidden="true">ZC</span>';echo '<span class="zc-related-index" aria-hidden="true">'.sprintf('%02d',$i+1).'</span></a><div class="zc-related-copy"><span class="zc-related-meta">'.esc_html($commerce?'محصول دیجیتال':get_the_date('',$related)).'</span><h3><a href="'.esc_url(get_permalink($related)).'">'.esc_html(get_the_title($related)).'</a></h3><p>'.esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt($related)),18)).'</p><footer>';
            if($commerce&&function_exists('wc_get_product')){$product=wc_get_product($related);if($product)echo '<div class="zc-related-price">'.wp_kses_post($product->get_price_html()).'</div>';}
            echo '<a class="zc-related-action" href="'.esc_url(get_permalink($related)).'">'.($commerce?'بررسی محصول':'ادامه مطالعه').' <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a></footer></div></article>';
        }echo '</div></section>';
    }
    public static function render():void {
        $id=get_the_ID();if(!$id||post_password_required($id))return;
        $type=get_post_type($id);$commerce=$type==='product';$settings=[];foreach(['share','author','navigation','related','comments'] as $key)$settings[$key]=(bool)get_theme_mod('zc_single_'.$key,true);
        $settings=apply_filters('zarincode_single_sections',$settings,$type,$id);
        echo '<div class="zc-single-tools'.($commerce?' zc-commerce-tools':'').'">';
        if(!empty($settings['share']))self::share($id);
        if(!empty($settings['author']))self::author($id,$commerce);
        if(!empty($settings['navigation']))self::navigation($commerce);
        if(!empty($settings['related']))self::related($id,$commerce);
        if(!empty($settings['comments'])&&(comments_open($id)||get_comments_number($id))){
            echo '<div id="zc-article-discussion" class="'.($commerce?'zc-product-discussion':'zc-article-discussion').'">';
            if($commerce&&function_exists('wc_get_product')){$product=wc_get_product($id);if($product){$count=$product->get_review_count();echo '<header class="zc-review-heading"><div><span class="zc-story-kicker">تجربه کاربران، راهنمای انتخاب شما</span><h2>بررسی و پرسش درباره محصول</h2></div><div class="zc-review-score">';if($count)echo '<strong>'.esc_html(number_format_i18n((float)$product->get_average_rating(),1)).'<small> / ۵</small></strong>'.wp_kses_post(wc_get_rating_html($product->get_average_rating(),$product->get_rating_count()));else echo '<strong>اولین تجربه</strong>';echo '<span>'.esc_html(number_format_i18n($count)).' نقد ثبت‌شده</span></div></header>';}}
            comments_template();echo '</div>';
        }
        echo '</div>';
    }
}
