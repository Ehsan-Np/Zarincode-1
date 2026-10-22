<?php
/** Product discussion: native WP tree and Woo review callbacks/ratings, no vendor template fork. */
defined('ABSPATH') || exit;
if(post_password_required())return;
$product=wc_get_product(get_the_ID());if(!$product)return;
$reply=\Zarincode\Theme\Core\Discussion::requested_parent(get_the_ID());
$verified=get_option('woocommerce_review_rating_verification_required')==='yes';
$buyer=!$verified||wc_customer_bought_product('',get_current_user_id(),$product->get_id());
$staff=\Zarincode\Theme\Core\Discussion::staff();
?>
<div id="reviews" class="woocommerce-Reviews zc-threaded-reviews">
 <div id="comments">
 <?php if(have_comments()): ?>
  <ol class="commentlist"><?php wp_list_comments(apply_filters('woocommerce_product_review_list_args',['callback'=>'woocommerce_comments','style'=>'ol','max_depth'=>get_option('thread_comments')?(int)get_option('thread_comments_depth',5):-1])); ?></ol>
  <?php the_comments_navigation(['prev_text'=>'دیدگاه‌های قدیمی‌تر','next_text'=>'دیدگاه‌های جدیدتر']); ?>
 <?php else: ?><p class="woocommerce-noreviews">اولین بررسی یا پرسش را شما بنویسید.</p><?php endif; ?>
 </div>
 <?php if(comments_open()&&($buyer||$staff)):
 $rating='';if(!$reply&&$buyer&&wc_review_ratings_enabled()){$required=wc_review_ratings_required();$rating='<div class="comment-form-rating"><label id="comment-form-rating-label" for="rating">امتیاز شما'.($required?' *':' (اختیاری)').'</label><select name="rating" id="rating"'.($required?' required':'').'><option value="">انتخاب امتیاز</option>';for($n=5;$n>=1;$n--)$rating.='<option value="'.$n.'">'.$n.' از پنج</option>';$rating.='</select></div>';}
 $args=['title_reply'=>'تجربه شما از این محصول','title_reply_to'=>'پاسخ به %s','cancel_reply_link'=>'لغو پاسخ','label_submit'=>$reply?'ارسال پاسخ':'ثبت بررسی محصول','comment_notes_before'=>'<p class="zc-review-note">پرسش‌ها و پاسخ‌ها در همان گفتگو می‌مانند؛ پاسخ دادن، امتیاز جدیدی برای محصول ثبت نمی‌کند.</p>','comment_field'=>$rating.'<p class="comment-form-comment"><label for="comment">دیدگاه یا پاسخ شما *</label><textarea id="comment" name="comment" rows="5" required maxlength="65525"></textarea></p>'];
 $args=apply_filters('woocommerce_product_review_comment_form_args',$args);
 if(!$buyer&&$staff){$args['comment_notes_before'].='<p class="zc-review-note">برای پاسخ مدیریتی، دکمه پاسخ زیر دیدگاه کاربر را انتخاب کنید. ثبت نقد جدید همچنان تابع شرط خرید فروشگاه است.</p>';$args['submit_button']='<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" data-zc-reply-only="1"'.($reply?'':' disabled').' />';}
 echo '<div id="review_form_wrapper"><div id="review_form">';comment_form($args);echo '</div></div>';
 elseif(comments_open()): ?><p class="woocommerce-verification-required">طبق سیاست این فروشگاه، ثبت بررسی و پاسخ برای خریداران تأییدشده فعال است.</p>
 <?php else: ?><p>ارسال دیدگاه جدید برای این محصول بسته است.</p><?php endif; ?>
</div>
