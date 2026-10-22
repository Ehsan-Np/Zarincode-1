<?php
/** Build explicitly disposable, installed-version widget fixtures. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== 'local') throw new RuntimeException('Disposable local QA only');
wp_set_current_user(1);
\Zarincode\Runtime260\Demo\Demo_Seeder::seed_all();
$wm=\Elementor\Plugin::instance()->widgets_manager;
$widgets=$wm->get_widget_types();
$product=wc_get_products(['limit'=>1])[0] ?? null;
$inventory=[];
foreach($widgets as $name=>$prototype){
 try {
  if(in_array($name,['common-base','common','common-optimized','inner-section'],true)){$inventory[]=['widget'=>$name,'status'=>'internal_control_provider','custom'=>false];continue;}
  $prototype=\Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'q'.substr(md5($name),0,6),'elType'=>'widget','widgetType'=>$name,'settings'=>[],'elements'=>[]]);
  if(!$prototype)throw new RuntimeException('Widget instance unavailable');
  $controls=$prototype->get_controls();$settings=$prototype->get_settings();
  // Supply meaningful public settings where the official default is empty.
  foreach(['title'=>'Widget QA heading','text'=>'Accessible QA content','editor'=>'<p>Accessible QA content for the installed widget.</p>','description'=>'Widget QA description','button_text'=>'QA action'] as $key=>$text)if(isset($controls[$key])&&empty($settings[$key]))$settings[$key]=$text;
  if(isset($controls['link']))$settings['link']=['url'=>home_url('/contact-us/')];
  if(isset($controls['image'])&&empty($settings['image']['url']))$settings['image']=['url'=>get_template_directory_uri().'/assets/images/demo-service-cover.jpg','id'=>0];
  $existing=get_page_by_path('qa-widget-'.sanitize_title($name));
  $id=wp_insert_post(['ID'=>$existing?$existing->ID:0,'post_type'=>'page','post_status'=>'publish','post_title'=>'QA '.$name,'post_name'=>'qa-widget-'.sanitize_title($name)]);
  $tree=[['id'=>'qa'.substr(md5($name),0,5),'elType'=>'section','settings'=>[],'elements'=>[['id'=>'qc'.substr(md5($name),0,5),'elType'=>'column','settings'=>['_column_size'=>100],'elements'=>[['id'=>'qw'.substr(md5($name),0,5),'elType'=>'widget','widgetType'=>$name,'settings'=>$settings,'elements'=>[]]]]]]];
  update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);update_post_meta($id,'_wp_page_template','elementor_header_footer');
  $types=[];foreach($controls as $key=>$control){$types[$control['type']??'unknown']=($types[$control['type']??'unknown']??0)+1;}
  $inventory[]=['widget'=>$name,'class'=>get_class($prototype),'source'=>(new ReflectionClass($prototype))->getFileName(),'page'=>$id,'url'=>get_permalink($id),'controls'=>count($controls),'control_types'=>$types,'custom'=>str_starts_with($name,'zarincode_'),'dependencies'=>['scripts'=>$prototype->get_script_depends(),'styles'=>$prototype->get_style_depends()],'requires_context'=>str_contains($name,'woocommerce')||str_starts_with($name,'theme-')||str_starts_with($name,'loop-'),'status'=>'fixture_created'];
 } catch(Throwable $e){$inventory[]=['widget'=>$name,'status'=>'fixture_failed','error'=>get_class($e).': '.$e->getMessage()];}
}
$blocks=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA core blocks','post_content'=>'<!-- wp:heading --><h2 class="wp-block-heading">Accessible block heading</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Core block content and <a href="/">homepage</a>.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/">Return home</a></div><!-- /wp:button --></div><!-- /wp:buttons -->']);
$out=['environment'=>['wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'elementor'=>ELEMENTOR_VERSION,'pro'=>ELEMENTOR_PRO_VERSION,'woocommerce'=>WC_VERSION],'widgets'=>$inventory,'blocks'=>get_permalink($blocks),'product'=>$product?get_permalink($product->get_id()):null,'product_id'=>$product?$product->get_id():0,'home'=>home_url('/')];
file_put_contents(getenv('ZC_QA_REPORT')?:'/tmp/zc-complete/inventory.json',wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($inventory)." widget fixtures created\n";
