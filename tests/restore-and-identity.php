<?php
/** Disposable WordPress regression checks for 2.6.1. Run with wp eval-file. */
use Zarincode\Runtime260\Widgets\Repeater_Identity;
use Zarincode\Runtime260\Widgets\Renderer;
use Zarincode\Runtime260\Widgets\Widget_Schema;
require_once get_template_directory().'/inc/EmbeddedCore/Widgets/Repeater_Identity.php';
$checks=0;
$check=static function($condition,$message)use(&$checks){if(!$condition)throw new RuntimeException($message);++$checks;echo 'PASS '.$message."\n";};
$rows=[['title'=>'Missing'],['_id'=>'','title'=>'Empty'],['_id'=>'keep123','title'=>'Existing'],['_id'=>'keep123','title'=>'Duplicate'],['_id'=>'unsafe id!','title'=>'Invalid']];
$normalized=Repeater_Identity::rows($rows,'fixture');
$check(count($normalized)===5,'no valid repeater rows lost');
$check(count(array_unique(array_column($normalized,'_id')))===5,'missing, empty, invalid and duplicate IDs replaced with unique identities');
$check($normalized[2]['_id']==='keep123','existing valid ID preserved');
$check($normalized===Repeater_Identity::rows($rows,'fixture'),'identities stable across requests');
$check($normalized===Repeater_Identity::rows($normalized,'fixture'),'normalization is idempotent');
$nested=Repeater_Identity::settings(['rows'=>[['children'=>[['title'=>'Nested']]]],'image'=>['url'=>'https://example.org/a.jpg']],[['name'=>'rows','type'=>'repeater','fields'=>[['name'=>'children','type'=>'repeater']]]],'nested');
$check(!empty($nested['rows'][0]['children'][0]['_id']),'nested repeater identities generated');
$check(!isset($nested['image']['_id']),'media and URL objects are not treated as repeaters');
$manager=\Elementor\Plugin::instance()->elements_manager;
foreach(array_keys(Widget_Schema::definitions()) as $kind){
    $make=static fn()=> $manager->create_element_instance(['id'=>'identityfixture','elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>['items'=>$rows,'stats'=>[['title'=>'Stat','value'=>'1']],'faq'=>[['title'=>'Question','answer'=>'Answer']]]]);
    $widget=$make();$settings=$widget->get_settings_for_display();
    foreach(['items','stats','faq'] as $field){foreach($settings[$field] as $row)$check(!empty($row['_id']),$kind.' '.$field.' runtime ID is nonempty');}
    $check($settings['items']===$make()->get_settings_for_display('items'),$kind.' saved invalid IDs normalized consistently');
    foreach($widget->get_controls() as $control) if(($control['type']??'')==='repeater') foreach($control['default']??[] as $row)$check(!empty($row['_id']),$kind.' default '.$control['name'].' has identity');
    $check(($settings['design_mode']??'')==='original',$kind.' defaults to original design');
}
$hero=Renderer::render_hero();
$check(strpos($hero,'zc-hero-main-card')!==false&&strpos($hero,'zc-edit-hero')===false,'original hero bento structure restored, not generic hero');
$check(strpos(Renderer::render_pricing_tabs(),'zc-pricing')!==false,'original pricing markup restored');
$check(Renderer::render_page_section(['section_key'=>'about-page'])===\Zarincode\Runtime260\Widgets\Original_Pages::render('about-page'),'original about-page output preserved');
$ids=[];$warnings=[];
set_error_handler(static function($level,$message)use(&$warnings){if(strpos($message,'Undefined array key "_id"')!==false){$warnings[]=$message;return true;}return false;});
try {
    foreach(['original','custom'] as $mode){
        $id=wp_insert_post(['post_type'=>'page','post_title'=>'Temporary identity CSS fixture','post_status'=>'publish']);$ids[]=$id;
        $tree=[['id'=>'fixturecontainer','elType'=>'container','settings'=>[],'elements'=>[['id'=>'fixturewidget','elType'=>'widget','widgetType'=>'zarincode_services','settings'=>['design_mode'=>$mode,'items'=>[['title'=>'MISSING','background'=>'#cc0033'],['_id'=>'','title'=>'EMPTY'],['_id'=>'duplicate','title'=>'First'],['_id'=>'duplicate','title'=>'Second']]]]]]];
        update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_template_type','wp-page');update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));
        \Elementor\Core\Files\CSS\Post::create($id)->update();
        $check(true,'actual Elementor post CSS generation completed: '.$mode);
    }
} finally {restore_error_handler();foreach($ids as $id)wp_delete_post($id,true);}
$check(!$warnings,'no Undefined array key _id warnings during CSS generation');
echo 'TOTAL '.$checks." checks passed\n";
