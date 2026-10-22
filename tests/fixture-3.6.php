<?php
/** Disposable site only; never imported into release demo. */
update_option('comment_moderation',0);update_option('comment_previously_approved',0);update_option('comment_registration',0);update_option('woocommerce_review_rating_verification_required','no');
$fixtures=[];
foreach(['post','product','zc_service','zc_project','page'] as $type){
 $id=wp_insert_post(['post_type'=>$type,'post_status'=>'publish','post_title'=>'گفتگوی تو در تو — '.$type,'post_content'=>'<p>آزمون پاسخ‌های بومی وردپرس.</p><h2>بررسی تجربه</h2><p>متن قابل مطالعه.</p>','comment_status'=>'open']);update_post_meta($id,'_zc36_fixture',1);
 if($type==='product'){$p=new WC_Product_Simple($id);$p->set_regular_price('100');$p->save();}
 $c=wp_insert_comment(['comment_post_ID'=>$id,'comment_content'=>'دیدگاه اصلی برای پاسخ چندمرحله‌ای','comment_author'=>'کاربر آزمایشی','comment_author_email'=>'reader@example.test','comment_approved'=>1,'comment_type'=>$type==='product'?'review':'comment']);if($type==='product')add_comment_meta($c,'rating',5,true);
 $fixtures[$type]=['post'=>$id,'root'=>$c,'url'=>get_permalink($id)];
}
echo wp_json_encode($fixtures,JSON_UNESCAPED_SLASHES)."\n";
