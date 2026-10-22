<?php
$post=get_page_by_path('elementor-based-wordpress-theme-architecture',OBJECT,'post');$product=get_page_by_path('zarincode-pro-wordpress-theme',OBJECT,'product');$admin=get_user_by('login','zcadmin');
update_option('comment_registration',0);update_option('comment_moderation',0);update_option('comment_previously_approved',0);update_option('require_name_email',1);
$sample=Zarincode\Theme\Core\Social_Manager::sanitize([['name'=>'شبکه دلخواه آزمایشی','url'=>'https://example.com/profile','share_template'=>'https://example.com/share?url={url}&text={title}','type'=>'svg','svg'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/></svg>','enabled'=>1]]);update_option('zarincode_custom_socials',$sample,false);
update_option('zc35_fixture',['custom_socials'=>1]);
$fixture=['url'=>home_url('/'),'post'=>$post->ID,'article'=>get_permalink($post),'product'=>$product->ID,'product_url'=>get_permalink($product),'admin'=>$admin?$admin->ID:0];echo wp_json_encode($fixture,JSON_UNESCAPED_SLASHES)."\n";
