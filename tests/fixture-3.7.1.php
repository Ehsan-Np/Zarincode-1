<?php
/** Disposable UI fixtures, not demo content. */
$post=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'بررسی فرم افقی دیدگاه و اشتراک مینیمال','post_content'=>str_repeat('<p>تجربه همراهی با زرین‌کد؛ نمونه موقت برای بررسی فرم، اشتراک و نمایش در موبایل.</p>',15),'comment_status'=>'open']);
$root=wp_insert_comment(['comment_post_ID'=>$post,'comment_author'=>'همراه آزمایشی','comment_author_email'=>'fixture@example.test','comment_content'=>'دیدگاه برای آزمون پاسخ و لغو پاسخ.','comment_approved'=>1]);
$product=new WC_Product_Simple();$product->set_name('محصول آزمایشی فرم دیدگاه');$product->set_status('publish');$product->set_regular_price('100');$product->set_reviews_allowed(true);$product->save();wp_update_post(['ID'=>$product->get_id(),'comment_status'=>'open']);
$page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'برگه آزمایشی دیدگاه','post_content'=>'بررسی حذف فیلد وب‌سایت از فرم برگه.','comment_status'=>'open']);
update_option('woocommerce_review_rating_verification_required','no');update_option('blog_public',0);update_option('comment_registration',0);
$f=['post'=>$post,'article'=>get_permalink($post),'root'=>$root,'product'=>$product->get_id(),'product_url'=>get_permalink($product->get_id()),'page'=>get_permalink($page)];file_put_contents('/tmp/zc38-fixture.json',wp_json_encode($f));echo "3.7.1 disposable UI fixtures ready\n";
