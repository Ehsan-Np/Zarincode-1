<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Native WP parent relationships, moderation and reply script; Woo ratings remain root-only. */
final class Discussion {
    public static function boot():void {
        add_action('wp_enqueue_scripts',static function(){if(is_singular()&&comments_open()&&get_option('thread_comments'))wp_enqueue_script('comment-reply');});
        add_filter('comments_template',static function($template){return is_singular('product')&&function_exists('wc_get_product')?get_template_directory().'/comments-product.php':$template;},50);
        add_action('woocommerce_review_after_comment_text',[self::class,'reply_link']);

    }
    public static function staff():bool {return class_exists('Zarincode\\Runtime260\\Content\\Discussion_Service')?\Zarincode\Runtime260\Content\Discussion_Service::staff():false;}
    public static function parent(int $id,int $post):?\WP_Comment {return class_exists('Zarincode\\Runtime260\\Content\\Discussion_Service')?\Zarincode\Runtime260\Content\Discussion_Service::parent($id,$post):null;}
    public static function requested_parent(int $post,string $key='replytocom'):?\WP_Comment {return class_exists('Zarincode\\Runtime260\\Content\\Discussion_Service')?\Zarincode\Runtime260\Content\Discussion_Service::requested_parent($post,$key):null;}
    public static function depth(\WP_Comment $comment):int {return class_exists('Zarincode\\Runtime260\\Content\\Discussion_Service')?\Zarincode\Runtime260\Content\Discussion_Service::depth($comment):1;}
    public static function prepare(array $data):array {return class_exists('Zarincode\\Runtime260\\Content\\Discussion_Service')?\Zarincode\Runtime260\Content\Discussion_Service::prepare($data):$data;}
    public static function reply_link($comment):void {
        if(!($comment instanceof \WP_Comment)||!get_option('thread_comments'))return;
        if(get_option('woocommerce_review_rating_verification_required')==='yes'&&!self::staff()&&function_exists('wc_customer_bought_product')&&!wc_customer_bought_product('',get_current_user_id(),$comment->comment_post_ID))return;
        echo '<div class="zc-review-reply">';comment_reply_link(['reply_text'=>'پاسخ به این دیدگاه','login_text'=>'ورود برای پاسخ','depth'=>self::depth($comment),'max_depth'=>(int)get_option('thread_comments_depth',5),'add_below'=>'comment','respond_id'=>'respond'],$comment);echo '</div>';
    }
}
