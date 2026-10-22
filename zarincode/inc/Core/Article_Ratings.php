<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Optional, moderated comment ratings. No anonymous AJAX counter or invented aggregate. */
final class Article_Ratings {
    public static function boot():void {
        add_filter('comment_form_field_comment',static function($html){if(get_post_type()!=='post'||Discussion::requested_parent((int)get_the_ID())||!zarincode_get_option('blog_article_ratings',true))return $html;$field='<fieldset class="zc-rating-input"><legend>این مقاله چقدر برایتان کاربردی بود؟ <small>اختیاری</small></legend><div class="zc-rating-control"><div class="zc-rating-stars" role="group" aria-label="امتیاز از یک تا پنج">';for($n=1;$n<=5;$n++)$field.='<label title="'.$n.' از پنج"><input type="radio" name="zc_article_rating" value="'.$n.'"><span aria-hidden="true">★</span><span class="screen-reader-text">'.$n.' از پنج</span></label>';return $html.$field.'</div><output class="zc-rating-value" aria-live="polite">بدون امتیاز</output><button type="button" class="zc-rating-clear" aria-label="پاک کردن امتیاز">پاک کردن</button></div>'.wp_nonce_field('zc_article_rating_'.get_the_ID(),'zc_rating_nonce',true,false).'</fieldset>' ;});
        add_filter('preprocess_comment',static function($data){if(zarincode_get_option('blog_article_ratings',true)&&isset($_POST['zc_article_rating'])&&get_post_type($data['comment_post_ID'])==='post'){
            $v=wp_unslash($_POST['zc_article_rating']);if(!is_scalar($v)||!preg_match('/^[1-5]$/D',(string)$v)||!isset($_POST['zc_rating_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['zc_rating_nonce'])),'zc_article_rating_'.$data['comment_post_ID']))wp_die('امتیاز یا نشست ارسال معتبر نیست. صفحه را تازه‌سازی کنید.',400);
        }return $data;});
        add_action('comment_post',static function($id){$comment=get_comment($id);if(zarincode_get_option('blog_article_ratings',true)&&$comment&&get_post_type($comment->comment_post_ID)==='post'&&isset($_POST['zc_article_rating'],$_POST['zc_rating_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['zc_rating_nonce'])),'zc_article_rating_'.$comment->comment_post_ID)){$v=wp_unslash($_POST['zc_article_rating']);if(is_scalar($v)&&preg_match('/^[1-5]$/D',(string)$v))add_comment_meta($id,'zc_article_rating',$v,true);}});
        add_filter('comment_text',static function($text,$comment){if(!zarincode_get_option('blog_article_ratings',true)||!$comment||get_post_type($comment->comment_post_ID)!=='post')return $text;$n=(int)get_comment_meta($comment->comment_ID,'zc_article_rating',true);if($n>=1&&$n<=5)$text='<div class="zc-comment-rating" aria-label="'.esc_attr($n.' از پنج').'">'.str_repeat('★',$n).'<span aria-hidden="true">'.str_repeat('☆',5-$n).'</span></div>'.$text;return $text;},10,2);
        add_action('wp_head',[self::class,'schema'],40);
    }
    public static function aggregate(int $id):array {
        global $wpdb;$row=$wpdb->get_row($wpdb->prepare("SELECT COUNT(DISTINCT c.comment_ID) AS votes, AVG(CAST(m.meta_value AS DECIMAL(3,1))) AS score FROM {$wpdb->comments} c INNER JOIN {$wpdb->commentmeta} m ON c.comment_ID=m.comment_id WHERE c.comment_post_ID=%d AND c.comment_approved='1' AND c.comment_type IN ('','comment') AND m.meta_key='zc_article_rating' AND m.meta_value IN ('1','2','3','4','5')",$id),ARRAY_A);return ['count'=>(int)($row['votes']??0),'average'=>round((float)($row['score']??0),1)];
    }
    public static function summary(int $id):void {
        if(!zarincode_get_option('blog_article_ratings',true))return;$r=self::aggregate($id);echo '<div class="zc-article-rating-summary"><span aria-hidden="true">★</span><div><strong>'.($r['count']?esc_html(number_format_i18n($r['average'],1)).' از ۵':'اولین امتیاز را شما بدهید').'</strong><small>'.esc_html(number_format_i18n($r['count'])).' امتیاز از دیدگاه‌های تأییدشده</small></div></div>';
    }
    public static function schema():void {
        if(!is_singular('post')||post_password_required()||!zarincode_get_option('enable_schema_markup',true)||!apply_filters('zarincode_article_schema_enabled',!defined('WPSEO_VERSION')&&!defined('RANK_MATH_VERSION')&&!defined('AIOSEO_VERSION')))return;
        $id=get_queried_object_id();$data=['@context'=>'https://schema.org','@type'=>'BlogPosting','@id'=>get_permalink($id).'#article','mainEntityOfPage'=>get_permalink($id),'headline'=>get_the_title($id),'datePublished'=>get_the_date('c',$id),'dateModified'=>get_the_modified_date('c',$id),'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',(int)get_post_field('post_author',$id)),'url'=>get_author_posts_url((int)get_post_field('post_author',$id))]];
        if(has_post_thumbnail($id))$data['image']=[get_the_post_thumbnail_url($id,'full')];$r=self::aggregate($id);if(zarincode_get_option('blog_article_ratings',true)&&get_theme_mod('zc_single_comments',true)&&$r['count'])$data['aggregateRating']=['@type'=>'AggregateRating','ratingValue'=>$r['average'],'ratingCount'=>$r['count'],'bestRating'=>5,'worstRating'=>1];
        echo '<script type="application/ld+json">'.wp_json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE).'</script>';
    }
}
