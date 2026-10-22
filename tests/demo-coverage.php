<?php
/** wp eval-file, after explicit full demo import on a disposable site. */
use Zarincode\Runtime260\Widgets\Widget_Schema;
use Zarincode\Runtime260\Widgets\Professional_Widget;
Elementor\Plugin::instance()->widgets_manager->get_widget_types();
$ids=get_posts(['post_type'=>['page','zc_hf_template','elementor_library'],'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_zarincode_seeded','meta_value'=>'1']);
$used=[];$walk=function($nodes)use(&$walk,&$used){foreach($nodes as $node){if(isset($node['widgetType']))$used[$node['widgetType']]=true;$walk($node['elements']??[]);}};
foreach($ids as $id){$data=json_decode(get_post_meta($id,'_elementor_data',true),true);if(is_array($data))$walk($data);}
$expected=array_merge(array_keys(Widget_Schema::definitions()),array_keys(Professional_Widget::catalog()),['client_logos','pricing_plan','trust_badges']);
foreach($expected as $kind)if($kind!=='hero'&&!isset($used['zarincode_'.$kind]))throw new RuntimeException('Demo is missing '.$kind);
if(isset($used['zarincode_hero']))throw new RuntimeException('Demo still contains the monolithic hero');
$home=get_page_by_path('home');$homeTree=get_post_meta($home->ID,'_elementor_data',true);foreach(array_keys(Professional_Widget::catalog()) as $kind)if(strpos($homeTree,'zarincode_'.$kind)!==false)throw new RuntimeException('Specialist widget overcrowds home: '.$kind);
echo 'PASS '.count($used)." active widget types used across home, inner pages and header/footer templates\n";
