<?php
/** Disposable WordPress only: replaces/publishes the dedicated QA page. */
use Zarincode\Runtime260\Widgets\Hero_Layout as H;
use Zarincode\Runtime260\Widgets\Native_Design as N;
Elementor\Plugin::instance()->widgets_manager->get_widget_types();
$hero=H::composition();$hero['elements'][0]['id']='intro32';$hero['elements'][1]['elements'][0]['id']='profile32';$hero['elements'][1]['elements'][1]['id']='cta32';
$widget=static fn($kind,$id,$settings=[])=>['id'=>$id,'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$settings,'elements'=>[]];
$m=N::model('pricing');$g=array_key_first($m['repeaters']);$rows=$m['repeaters'][$g]['default'];$rows[0]['features_source']='icon_list';$rows[0]['plan_key']='qa-plan';
$price=['n_pricing_'.$g=>$rows,'n_pricing_features'=>[['_id'=>'feat32','plan_key'=>'__custom__','custom_plan_key'=>'qa-plan','text'=>'QA initial feature','icon'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']]]];
$tree=[$hero,$widget('products','products32',['n_products_source'=>'query','n_products_show_all'=>'yes']),$widget('services','services32',['n_services_source'=>'query']),$widget('portfolio','portfolio32',['n_portfolio_source'=>'query']),$widget('blog','blog32',['n_blog_source'=>'query']),$widget('pricing','price32',$price),$widget('testimonials','reviews32')];
$old=get_page_by_path('zarincode-qa-32');$post=['post_type'=>'page','post_status'=>'publish','post_title'=>'Zarincode 3.2 QA','post_name'=>'zarincode-qa-32'];if($old)$post['ID']=$old->ID;
$id=wp_insert_post($post);update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_wp_page_template','template-elementor-canvas.php');Elementor\Plugin::instance()->files_manager->clear_cache();echo $id."\n";
