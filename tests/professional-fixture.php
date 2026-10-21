<?php
/** DESTRUCTIVE TEST FIXTURE: disposable WordPress only; resets the dedicated test page. */
$old=get_page_by_path('zc-professional-fixture');
$id=wp_insert_post(['ID'=>$old?$old->ID:0,'post_type'=>'page','post_title'=>'Zarincode Professional Widget Lab','post_name'=>'zc-professional-fixture','post_status'=>'publish']);
Elementor\Plugin::instance()->widgets_manager->get_widget_types();
$widgets=[['id'=>'originalhero','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>[]],['id'=>'customhero','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>['design_mode'=>'custom','title'=>'CUSTOM INLINE TITLE','source'=>'manual','items'=>[['_id'=>'rowtest','title'=>'CUSTOM REPEATER TITLE','description'=>'<p>Custom body</p>','visible'=>'yes']]]]];
foreach(array_keys(\Zarincode\Runtime260\Widgets\Professional_Widget::catalog()) as $key){$widgets[]=['id'=>str_replace('_','',$key),'elType'=>'widget','widgetType'=>'zarincode_'.$key,'settings'=>[]];}
$tree=[['id'=>'procontainer','elType'=>'container','settings'=>['content_width'=>'full'],'elements'=>$widgets]];
update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_template_type','wp-page');update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));
\Elementor\Core\Files\CSS\Post::create($id)->update();echo $id."\n";
