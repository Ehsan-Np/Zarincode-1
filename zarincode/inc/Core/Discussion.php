<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Native WP parent relationships, moderation and reply script; Woo ratings remain root-only. */
final class Discussion {
    public static function boot():void {
        // Remove the website field at the source, including Woo's native review form.
        add_filter('comment_form_default_fields',[self::class,'fields'],99);
        add_filter('comment_form_fields',[self::class,'fields'],99);
        add_action('after_setup_theme',static function(){
            if(!get_option('zarincode_threaded_discussion_v1')){update_option('thread_comments',1);update_option('thread_comments_depth',max(3,min(10,(int)get_option('thread_comments_depth',5))));update_option('zarincode_threaded_discussion_v1',1,false);}
        },35);
        add_action('wp_enqueue_scripts',static function(){if(is_singular()&&comments_open()&&get_option('thread_comments'))wp_enqueue_script('comment-reply');});
        add_filter('comments_template',static function($template){return is_singular('product')&&function_exists('wc_get_product')?get_template_directory().'/comments-product.php':$template;},50);
        add_action('woocommerce_review_after_comment_text',[self::class,'reply_link']);
        add_filter('preprocess_comment',[self::class,'prepare'], -5);
        // Shop staff may answer an approved review without having purchased their own product.
        if(class_exists('WC_Comments')){remove_action('pre_comment_on_post',['WC_Comments','validate_product_review_verified_owners']);add_action('pre_comment_on_post',static function($post){$parent=self::requested_parent((int)$post,'comment_parent');if($parent&&self::staff())return;\WC_Comments::validate_product_review_verified_owners($post);});}
    }
    public static function fields(array $fields):array {
        unset($fields['url']);
        $required=get_option('require_name_email')?' <span class="required" aria-hidden="true">*</span><span class="screen-reader-text">الزامی</span>':'';
        foreach(['author'=>'نام','email'=>'ایمیل'] as $key=>$label)if(isset($fields[$key]))$fields[$key]=preg_replace_callback('~(<label\b[^>]*>).*?(</label>)~s',static fn($m)=>$m[1].$label.$required.$m[2],$fields[$key])??$fields[$key];
        if(isset($fields['cookies']))$fields['cookies']=preg_replace_callback('~(<label\b[^>]*>).*?(</label>)~s',static fn($m)=>$m[1].'ذخیره نام و ایمیل برای دیدگاه بعدی.'.$m[2],$fields['cookies'])??$fields['cookies'];
        return $fields;
    }
    public static function staff():bool{return current_user_can('manage_woocommerce')||current_user_can('moderate_comments');}
    public static function parent(int $id,int $post):?\WP_Comment {
        if(!$id)return null;$c=get_comment($id);return $c&&(int)$c->comment_post_ID===$post&&$c->comment_approved==='1'&&in_array($c->comment_type,['','comment','review'],true)?$c:null;
    }
    public static function requested_parent(int $post,string $key='replytocom'):?\WP_Comment {
        $source=$key==='comment_parent'?$_POST:$_GET;$v=$source[$key]??0;if(!is_scalar($v)||!ctype_digit((string)$v))return null;return self::parent((int)$v,$post);
    }
    public static function depth(\WP_Comment $comment):int {$depth=1;$seen=[$comment->comment_ID=>true];while($comment->comment_parent&&$depth<11){$parent=get_comment($comment->comment_parent);if(!$parent||isset($seen[$parent->comment_ID]))break;$seen[$parent->comment_ID]=true;$depth++;$comment=$parent;}return $depth;}
    public static function prepare(array $data):array {
        $raw=$data['comment_parent']??0;if(!$raw)return $data;
        $parent=is_scalar($raw)&&ctype_digit((string)$raw)?self::parent((int)$raw,(int)$data['comment_post_ID']):null;
        if(!$parent||self::depth($parent)>=(int)get_option('thread_comments_depth',5))wp_die('دیدگاه مقصد معتبر نیست یا به بیشترین عمق پاسخ رسیده‌اید.','پاسخ نامعتبر',['response'=>400]);
        // A response discusses the parent; it is not another vote for the article/product.
        unset($_POST['rating'],$_POST['zc_article_rating']);return $data;
    }
    public static function reply_link($comment):void {
        if(!($comment instanceof \WP_Comment)||!get_option('thread_comments'))return;
        if(get_option('woocommerce_review_rating_verification_required')==='yes'&&!self::staff()&&function_exists('wc_customer_bought_product')&&!wc_customer_bought_product('',get_current_user_id(),$comment->comment_post_ID))return;
        echo '<div class="zc-review-reply">';comment_reply_link(['reply_text'=>'پاسخ به این دیدگاه','login_text'=>'ورود برای پاسخ','depth'=>self::depth($comment),'max_depth'=>(int)get_option('thread_comments_depth',5),'add_below'=>'comment','respond_id'=>'respond'],$comment);echo '</div>';
    }
}
