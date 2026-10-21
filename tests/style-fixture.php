<?php
/** Disposable integration fixture; do not import this into production. */
$existing=get_page_by_path('zc-controls-probe');
$id=wp_insert_post(['ID'=>$existing?$existing->ID:0,'post_type'=>'page','post_name'=>'zc-controls-probe','post_title'=>'Widget controls test','post_status'=>'publish']);
$widget=['id'=>'styleprobe','elType'=>'widget','widgetType'=>'zarincode_services','settings'=>['title'=>'STYLE PROBE','title_normal_color'=>'#cc0033','title_typography_typography'=>'custom','title_typography_font_size'=>['size'=>37,'unit'=>'px'],'columns'=>'2','source'=>'manual','show_filters'=>'yes','all_label'=>'ALL','items'=>[['_id'=>'carda','title'=>'CARD A','visible'=>'yes','category'=>'FIRST','background'=>'#e0ffe0','link'=>['url'=>'https://example.org/a'],'button_text'=>'GO'],['_id'=>'cardb','title'=>'CARD B','visible'=>'yes','category'=>'SECOND','link'=>['url'=>'https://example.org/b'],'button_text'=>'GO']]]];
$tree=[['id'=>'fixturecontainer','elType'=>'container','settings'=>[],'elements'=>[$widget]]];
update_post_meta($id,'_elementor_edit_mode','builder');
update_post_meta($id,'_elementor_template_type','wp-page');
update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));
\Elementor\Core\Files\CSS\Post::create($id)->update();
echo $id."\n";
