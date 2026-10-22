<?php
/** Disposable WP/WooCommerce only. Creates QA pages, not production/demo content. */
use Zarincode\Runtime260\Widgets\Native_Design as N;
Elementor\Plugin::instance()->widgets_manager->get_widget_types();
$writer=get_user_by('login','zc_writer_qa');if(!$writer){$uid=wp_insert_user(['user_login'=>'zc_writer_qa','user_pass'=>wp_generate_password(30),'user_email'=>'qa-writer@example.invalid','display_name'=>'نویسنده آزمایشی زرین کد','role'=>'author','description'=>'نمونه معرفی نویسنده برای آزمون صفحات تکی']);$writer=get_user_by('id',$uid);}
$thumb=(int)get_option('zarincode_demo_cover_post');$singles=[];
foreach(['post'=>'category','zc_service'=>'zc_service_cat','product'=>'product_cat'] as $type=>$tax){
 $term=term_exists('QA Studio 33',$tax);if(!$term)$term=wp_insert_term('QA Studio 33',$tax);$termId=(int)$term['term_id'];
 for($i=0;$i<3;$i++){
  $slug='qa33-'.$type.'-'.$i;$old=get_page_by_path($slug,OBJECT,$type);$post=['post_type'=>$type,'post_status'=>'publish','post_title'=>'محتوای آزمایشی '.$type.' '.$i,'post_name'=>$slug,'post_author'=>$writer->ID,'comment_status'=>'open','post_content'=>'<p>محتوای اختصاصی برای بررسی دیدگاه، نویسنده، اشتراک‌گذاری و مطالب مرتبط.</p>','post_date'=>'2026-10-'.(10+$i).' 12:00:00'];if($old)$post['ID']=$old->ID;
  $id=wp_insert_post($post);set_post_thumbnail($id,$thumb);wp_set_object_terms($id,[$termId],$tax);
  if($type==='product'){$p=new WC_Product_Simple($id);$p->set_regular_price('250000');$p->set_reviews_allowed(true);$p->set_category_ids([$termId]);$p->save();}
  if($i===1)$singles[$type]=['id'=>$id,'url'=>get_permalink($id)];
 }
}
$widget=static fn($kind,$id,$settings=[])=>['id'=>$id,'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$settings,'elements'=>[]];
$m=N::model('services');$rows=$m['repeaters']['services_grid_items_1']['default'];foreach($rows as &$row){$row['service_title_text_1']='کارت استایل نمونه';$row['featured_image']=['id'=>$thumb,'url'=>wp_get_attachment_url($thumb)];}unset($row);
$clients=[];for($i=1;$i<=8;$i++)$clients[]=['name'=>'مشتری نمونه '.$i,'logo'=>['url'=>get_template_directory_uri().'/assets/images/demo-client-'.(($i-1)%6+1).'.png'],'service'=>'طراحی و توسعه','link'=>['url'=>$singles['post']['url']]];
$plan=['title'=>'پلن مستقل آزمایشی','features'=>[['text'=>'امکان قابل ویرایش','icon'=>['value'=>'fas fa-check-circle','library'=>'fa-solid'],'included'=>'yes']],'link'=>['url'=>home_url('/contact-us/')]];
$tm=N::model('testimonials');$reviews=$tm['repeaters']['services_grid_items_1']['default'];$reviews[0]['customer_photo']=['id'=>$thumb,'url'=>wp_get_attachment_url($thumb)];
$bm=N::model('brands');$brands=$bm['repeaters']['marquee_track_items_1']['default'];foreach($brands as &$brand){$brand['brand_link']=['url'=>$singles['post']['url']];$brand['brand_description']='تخصص و تجربه قابل ویرایش';}unset($brand);
$tree=[$widget('client_logos','clients33',['title'=>'مشتریان آزمایشی','clients'=>$clients]),$widget('services','skins33',['n_services_services_grid_items_1'=>$rows]),['id'=>'plans33','elType'=>'container','settings'=>['content_width'=>'full','css_classes'=>'zc-pricing-composition'],'elements'=>[$widget('pricing_plan','plan33a',$plan+['skin'=>'editorial']),$widget('pricing_plan','plan33b',$plan+['skin'=>'night']),$widget('pricing_plan','plan33c',$plan+['skin'=>'outline'])]],$widget('testimonials','reviews33',['n_testimonials_services_grid_items_1'=>$reviews]),$widget('brands','brands33',['n_brands_marquee_track_items_1'=>$brands]),$widget('pricing','oldprice33')];
$old=get_page_by_path('zarincode-studio-qa');$post=['post_type'=>'page','post_status'=>'publish','post_title'=>'Studio 3.3 QA','post_name'=>'zarincode-studio-qa'];if($old)$post['ID']=$old->ID;$id=wp_insert_post($post);update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_wp_page_template','template-elementor-canvas.php');Elementor\Plugin::instance()->files_manager->clear_cache();
echo wp_json_encode(['page'=>$id,'url'=>get_permalink($id),'singles'=>$singles],JSON_UNESCAPED_UNICODE)."\n";
