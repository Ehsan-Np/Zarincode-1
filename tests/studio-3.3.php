<?php
/** wp eval-file tests/studio-3.3.php --path=/path/to/disposable/wordpress */
use Zarincode\Runtime260\Widgets\Demo_Composition as D;
$checks=0;$ok=static function($condition,$message)use(&$checks){$checks++;if(!$condition)throw new RuntimeException($message);};
$widgets=Elementor\Plugin::instance()->widgets_manager->get_widget_types();
foreach(['zarincode_client_logos','zarincode_pricing_plan'] as $name){$w=$widgets[$name];$controls=$w->get_controls();$ok(count($controls)>150,$name.' deep controls');$ok(in_array('zarincode-studio',$w->get_style_depends(),true),'stylesheet dependency');}
$features=$widgets['zarincode_pricing_plan']->get_controls()['features'];$ok($features['type']==='repeater','direct feature repeater');$ok(isset($features['fields']['text'],$features['fields']['icon'])||count($features['fields'])>=3,'feature controls');
$admin=get_users(['role'=>'administrator','number'=>1])[0];$before=get_current_user_id();wp_set_current_user($admin->ID);
$source=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'QA relocation']);$lockedDestination=get_page_by_path('pricing');$original=get_post_meta($lockedDestination->ID,'_elementor_data',true);
$widget=['id'=>'sourceqa33','elType'=>'widget','widgetType'=>'zarincode_service_matrix','settings'=>['title'=>'محتوای ویرایش‌شده','_custom_css'=>'test preserved'],'elements'=>[]];$unrelated=['id'=>'keepqa33','elType'=>'widget','widgetType'=>'heading','settings'=>['title'=>'دست‌نخورده'],'elements'=>[]];$tree=[$widget,$unrelated];update_post_meta($source,'_elementor_data',wp_slash(wp_json_encode($tree)));
try{
 $plan=D::plan($source);$ok($plan['keep']===[$unrelated],'unrelated section kept');$ok($plan['moves']['pricing']['nodes']===[$widget],'saved settings unchanged');
 $count=D::relocate($source);$ok($count===1,'one relocated');$ok(json_decode(get_post_meta($source,'_elementor_data',true),true)===[$unrelated],'source removed only moved node');$dest=json_decode(get_post_meta($lockedDestination->ID,'_elementor_data',true),true);$ok(end($dest)===$widget,'destination exact settings');$ok(D::relocate($source)===0,'relocation idempotent');
 $meta=get_post_meta($source);$backup=array_filter(array_keys($meta),static fn($k)=>str_starts_with($k,'_zc_layout_backup_'));$ok(count($backup)>0,'backup before mutation');
 wp_set_current_user(0);try{D::relocate($source);$ok(false,'unauthorized relocation denied');}catch(RuntimeException $e){$ok(true,'unauthorized denied');}
}finally{wp_set_current_user($admin->ID);update_post_meta($lockedDestination->ID,'_elementor_data',wp_slash($original));delete_post_meta($lockedDestination->ID,'_elementor_css');wp_delete_post($source,true);wp_set_current_user($before);Elementor\Plugin::instance()->files_manager->clear_cache();}
foreach(['post','zc_service','product'] as $type){$p=get_page_by_path('qa33-'.$type.'-1',OBJECT,$type);$ids=Zarincode\Theme\Core\Single_Experience::related_ids($p->ID);$ok(count($ids)>0,$type.' related IDs');$ok(!in_array($p->ID,$ids,true),'exclude current');foreach($ids as $id)$ok(get_post_status($id)==='publish','related publish only');$ok(comments_open($p->ID),'discussion enabled in fixture');}
echo "PASS $checks studio assertions\n";
