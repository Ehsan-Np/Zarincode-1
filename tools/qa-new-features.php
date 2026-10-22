<?php
if(!defined('WP_CLI')||!WP_CLI||!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA)throw new RuntimeException('Disposable QA required');
$checks=[];$ok=static function($name,$pass)use(&$checks){$checks[$name]=(bool)$pass;if(!$pass)throw new RuntimeException($name);};
$widgets=\Elementor\Plugin::instance()->widgets_manager->get_widget_types();
foreach($widgets as $id=>$widget){if(str_starts_with($id,'zarincode_')){$ok('focus_'.$id,(bool)$widget->get_controls('zc_focus_color'));$ok('hover_'.$id,(bool)$widget->get_controls('zc_hover_link_color'));}}
$tags=\Elementor\Plugin::instance()->dynamic_tags->get_tags();$ok('tag_registered',isset($tags['zarincode-public-content']));
$original_post=$GLOBALS['post']??null;$ids=[];
try{
 foreach(['publish','draft','private'] as $status){
  $id=wp_insert_post(['post_type'=>'zc_service','post_status'=>$status,'post_title'=>'<script>invalid</script> Public fixture']);$ids[]=$id;
  $GLOBALS['post']=get_post($id);$tag=new \Zarincode\Runtime260\Elementor\Public_Data_Tag(['id'=>'qa-tag','settings'=>['field'=>'title']]);$tag->set_settings('field','title');ob_start();$tag->render();$text=ob_get_clean();
  $ok('tag_'.$status,$status==='publish'?($text!==''&&!str_contains($text,'<script>')):$text==='');
 }
 $GLOBALS['post']=get_post($ids[0]);wp_update_post(['ID'=>$ids[0],'post_password'=>'fixture-password']);$tag=new \Zarincode\Runtime260\Elementor\Public_Data_Tag(['id'=>'qa-tag','settings'=>['field'=>'title']]);$tag->set_settings('field','title');ob_start();$tag->render();$ok('tag_password_protected',ob_get_clean()==='');
 $ok('translation_none',\Zarincode\Runtime260\Integrations\Multilingual::engine()==='none');
 file_put_contents(getenv('ZC_QA_REPORT')?:sys_get_temp_dir().'/zc-new-features.json',wp_json_encode(['checks'=>$checks,'assertions'=>count($checks),'limitations'=>['Adapter fixtures are separate from real WPML/Polylang tests.','Control schema verification is not editor UI parity certification.']],JSON_PRETTY_PRINT));
 echo count($checks).' new-feature assertions passed.'.PHP_EOL;
}finally{foreach($ids as $id)wp_delete_post($id,true);$GLOBALS['post']=$original_post;}
