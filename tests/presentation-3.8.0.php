<?php
/** wp eval-file tests/presentation-3.8.0.php — disposable WordPress/Elementor/WooCommerce only. */
use Zarincode\Theme\Core\Presentation as P;
use Zarincode\Runtime260\Widgets\Native_Design as N;
$n=0;$check=static function($ok,$message)use(&$n){if(!$ok)throw new RuntimeException($message);$n++;};
foreach(['1280px'=>1472,'1360px'=>1564,'1440px'=>1656,'1600px'=>1840] as $base=>$wide){$check(P::width($base,115)===$wide.'px','15 percent shared width');$check(P::width($base,100)===$base,'base width can be restored');}
foreach([null,[],false] as $bad)$check(is_string(P::width($bad,115)),'defensive option input');
foreach(['products','services','portfolio','blog','services-page','projects-page'] as $kind){
 $section=strpos($kind,'-page')!==false;$m=N::model($section?'page_section':$kind,$section?$kind:'about-page');
 foreach($m['repeaters'] as $group=>$rep){
  if(strpos($group,'grid_items')===false)continue;
  foreach($rep['variants'] as $card){$check(strpos($card['attrs']['class'],'zc-story-card')!==false,'unified root');$check(strpos($card['children'][0]['attrs']['class'],'zc-story-title')!==false,'title precedes artwork');$check(strpos($card['children'][1]['attrs']['class'],'zc-card-scene')!==false,'scene follows title');$check(strpos(end($card['children'][1]['children'])['attrs']['class'],'zc-card-copy')!==false,'copy belongs inside scene');}
  foreach(['story_title','story_scene','story_copy'] as $part)$check(isset($m['parts'][$group.'_'.$part]),'new compositional parts are editable');
 }
}
$w=Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'qa38controls','elType'=>'widget','widgetType'=>'zarincode_products','settings'=>[]]);$ratio=$w->get_controls('n_products_image_ratio');$check(isset($ratio['selectors']['{{WRAPPER}} .zc-card-scene']),'aspect ratio control reaches the composed scene');
$check(in_array('zarincode-cards',$w->get_style_depends(),true),'core stylesheet is loaded in front end and editor');
$check(has_action('woocommerce_before_single_product','woocommerce_output_all_notices')!==false,'WooCommerce success/error notices retain their native hook');
echo "PASS {$n} presentation/model/width assertions\n";
