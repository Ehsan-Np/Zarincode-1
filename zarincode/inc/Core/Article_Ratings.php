<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Optional, moderated comment ratings. No anonymous AJAX counter or invented aggregate. */
final class Article_Ratings {
    public static function boot():void {
        add_filter('comment_form_field_comment',static function($html){if(!class_exists('\\Zarincode\\Runtime260\\Content\\Article_Rating_Service')||get_post_type()!=='post'||Discussion::requested_parent((int)get_the_ID())||!zarincode_get_option('blog_article_ratings',true))return $html;$field='<fieldset class="zc-rating-input"><legend>این مقاله چقدر برایتان کاربردی بود؟ <small>اختیاری</small></legend><div class="zc-rating-control"><div class="zc-rating-stars" role="group" aria-label="امتیاز از یک تا پنج">';for($n=1;$n<=5;$n++)$field.='<label title="'.$n.' از پنج"><input type="radio" name="zc_article_rating" value="'.$n.'"><span aria-hidden="true">★</span><span class="screen-reader-text">'.$n.' از پنج</span></label>';return $html.$field.'</div><output class="zc-rating-value" aria-live="polite">بدون امتیاز</output><button type="button" class="zc-rating-clear" aria-label="پاک کردن امتیاز">پاک کردن</button></div>'.wp_nonce_field('zc_article_rating_'.get_the_ID(),'zc_rating_nonce',true,false).'</fieldset>' ;});
        add_filter('comment_text',static function($text,$comment){if(!zarincode_get_option('blog_article_ratings',true)||!$comment||get_post_type($comment->comment_post_ID)!=='post')return $text;$n=(int)get_comment_meta($comment->comment_ID,'zc_article_rating',true);if($n>=1&&$n<=5)$text='<div class="zc-comment-rating" aria-label="'.esc_attr($n.' از پنج').'">'.str_repeat('★',$n).'<span aria-hidden="true">'.str_repeat('☆',5-$n).'</span></div>'.$text;return $text;},10,2);
        add_action('wp_head',[self::class,'schema'],40);
    }
    public static function aggregate(int $id):array {
        return class_exists('\\Zarincode\\Runtime260\\Content\\Article_Rating_Repository') ? \Zarincode\Runtime260\Content\Article_Rating_Repository::aggregate($id) : ['count'=>0,'average'=>0.0];
    }
    public static function summary(int $id):void {
        if(!zarincode_get_option('blog_article_ratings',true))return;$r=self::aggregate($id);echo '<div class="zc-article-rating-summary"><span aria-hidden="true">★</span><div><strong>'.($r['count']?esc_html(number_format_i18n($r['average'],1)).' از ۵':'اولین امتیاز را شما بدهید').'</strong><small>'.esc_html(number_format_i18n($r['count'])).' امتیاز از دیدگاه‌های تأییدشده</small></div></div>';
    }
    public static function schema():void {
        if(!is_singular('post')||post_password_required()||!zarincode_get_option('enable_schema_markup',true)||!apply_filters('zarincode_article_schema_enabled',\Zarincode\Theme\Compatibility\SEO::theme_schema_allowed()))return;
        $id=get_queried_object_id();$data=['@context'=>'https://schema.org','@type'=>'BlogPosting','@id'=>get_permalink($id).'#article','mainEntityOfPage'=>get_permalink($id),'headline'=>get_the_title($id),'inLanguage'=>(string)get_bloginfo('language')?:'fa-IR','datePublished'=>get_the_date('c',$id),'dateModified'=>get_the_modified_date('c',$id),'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',(int)get_post_field('post_author',$id)),'url'=>get_author_posts_url((int)get_post_field('post_author',$id))],'publisher'=>['@type'=>'Organization','@id'=>home_url('/').'#organization','name'=>zarincode_get_option('brand_en_name','Zarincode'),'logo'=>['@type'=>'ImageObject','url'=>get_site_icon_url(512)?:get_theme_file_uri('screenshot.png')]]];
        if(has_post_thumbnail($id))$data['image']=[get_the_post_thumbnail_url($id,'full')];if(apply_filters('zarincode_article_rating_schema_enabled',false)&&zarincode_get_option('blog_article_ratings',true)&&get_theme_mod('zc_single_comments',true)&&($r=self::aggregate($id))['count'])$data['aggregateRating']=['@type'=>'AggregateRating','ratingValue'=>$r['average'],'ratingCount'=>$r['count'],'bestRating'=>5,'worstRating'=>1];
        echo '<script type="application/ld+json">'.wp_json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE).'</script>';
    }
}
