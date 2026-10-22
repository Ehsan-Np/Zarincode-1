<?php
/** WordPress handles validation, moderation, threading and submission. */
defined('ABSPATH') || exit;
if(post_password_required())return;
?>
<section id="comments" class="zc-discussion">
 <div class="zc-story-heading"><span class="zc-story-kicker">گفت‌وگو با زرین کد</span><h2>دیدگاه‌ها و پرسش‌ها</h2></div>
 <?php if(have_comments()): ?>
 <ol class="comment-list"><?php wp_list_comments(['style'=>'ol','short_ping'=>true,'avatar_size'=>48]); ?></ol>
 <?php the_comments_navigation(['prev_text'=>'دیدگاه‌های قدیمی‌تر','next_text'=>'دیدگاه‌های جدیدتر']);endif;
 if(!comments_open()&&get_comments_number())echo '<p>ارسال دیدگاه برای این محتوا بسته است.</p>';
 comment_form(['title_reply'=>'دیدگاه خود را بنویسید','title_reply_to'=>'پاسخ به %s','label_submit'=>'ارسال دیدگاه','cancel_reply_link'=>'لغو پاسخ','comment_field'=>'<p class="comment-form-comment"><label for="comment">دیدگاه یا پرسش شما <span aria-hidden="true">*</span></label><textarea id="comment" name="comment" rows="6" required maxlength="65525"></textarea></p>']); ?>
</section>
