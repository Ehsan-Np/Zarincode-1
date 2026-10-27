<?php
/** Build explicitly disposable, installed-version widget fixtures with reproducible locale/content context. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== 'local') throw new RuntimeException('Disposable local QA only');
wp_set_current_user(1);
// Reproducible RTL/LTR switching for ?qa_locale=; guarded for non-local environments.
$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($mu_dir)) { mkdir($mu_dir, 0755, true); }
file_put_contents($mu_dir . '/zc-qa-locale.php', '<?php
if ( ! defined( "ZARINCODE_DISPOSABLE_QA" ) || ! ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== "local" ) { return; }
add_filter( "locale", static function ( $locale ) {
	$q = isset( $_GET["qa_locale"] ) && is_string( $_GET["qa_locale"] ) ? substr( wp_unslash( $_GET["qa_locale"] ), 0, 5 ) : "";
	return in_array( $q, array( "fa_IR", "en_US" ), true ) ? $q : $locale;
}, 99 );
if ( isset( $_GET["qa_locale"] ) && is_string( $_GET["qa_locale"] ) ) {
	$GLOBALS["text_direction"] = "fa_IR" === substr( wp_unslash( $_GET["qa_locale"] ), 0, 5 ) ? "rtl" : "ltr";
}
');
\Zarincode\Runtime260\Demo\Demo_Seeder::seed_all();
// Local attachment with real alternative text; used for image, logo and badge fixtures.
$attachment_id = 0; $attachment_url = '';
$existing_attachment = get_posts(['post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_zc_qa_asset','numberposts'=>1,'fields'=>'ids']);
if ($existing_attachment) { $attachment_id = (int)$existing_attachment[0]; $attachment_url = (string)wp_get_attachment_url($attachment_id); }
if (!$attachment_id) {
    $source = get_template_directory() . '/assets/images/demo-service-cover.jpg';
    if (is_readable($source)) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_upload_bits('zc-qa-cover.jpg', null, (string)file_get_contents($source));
        if (empty($upload['error'])) {
            $attachment_id = wp_insert_attachment(['post_title'=>'QA demo image','post_mime_type'=>'image/jpeg','post_status'=>'inherit'], $upload['file'], 0);
            if ($attachment_id && !is_wp_error($attachment_id)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', 'QA demo image with alternative text');
                update_post_meta($attachment_id, '_zc_qa_asset', '1');
                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
                $attachment_url = (string)wp_get_attachment_url($attachment_id);
            }
        }
    }
}
if ($attachment_id && !get_theme_mod('custom_logo')) { set_theme_mod('custom_logo', $attachment_id); }
// Local silent WAV gives the audio widget a same-origin media URL instead of a page oEmbed iframe.
$wav_url = '';
$existing_wav = get_posts(['post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_zc_qa_audio','numberposts'=>1,'fields'=>'ids']);
if ($existing_wav) { $wav_url = (string)wp_get_attachment_url($existing_wav[0]); }
if (!$wav_url) {
    $data = str_repeat("\x80", 400); $wav = 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt ' . pack('VvvvvV', 16, 1, 1, 8000, 8000, 1, 8) . 'data' . pack('V', strlen($data)) . $data;
    $upload = wp_upload_bits('zc-qa-silence.wav', null, $wav);
    if (empty($upload['error'])) {
        $wav_id = wp_insert_attachment(['post_title'=>'QA silent audio','post_mime_type'=>'audio/wav','post_status'=>'inherit'], $upload['file'], 0);
        if ($wav_id && !is_wp_error($wav_id)) { update_post_meta($wav_id, '_zc_qa_audio', '1'); $wav_url = (string)wp_get_attachment_url($wav_id); }
    }
}
$wm= \Elementor\Plugin::instance()->widgets_manager;
$widgets=$wm->get_widget_types();
$product=wc_get_products(['limit'=>1])[0] ?? null;
$inventory=[];
foreach($widgets as $name=>$prototype){
 try {
  if(in_array($name,['common-base','common','common-optimized','inner-section'],true)){$inventory[]=['widget'=>$name,'status'=>'internal_control_provider','custom'=>false];continue;}
  $prototype= \Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'q'.substr(md5($name),0,6),'elType'=>'widget','widgetType'=>$name,'settings'=>[],'elements'=>[]]);
  if(!$prototype)throw new RuntimeException('Widget instance unavailable');
  $controls=$prototype->get_controls();$settings=$prototype->get_settings();
  // Supply meaningful public settings where the official default is empty.
  foreach(['title'=>'Widget QA heading','text'=>'Accessible QA content','editor'=>'<p>Accessible QA content for the installed widget.</p>','description'=>'Widget QA description','button_text'=>'QA action','label'=>'QA field label','placeholder'=>'QA placeholder','accessible_name'=>'QA interactive region','announcement_text'=>'QA announcement text','cta_text'=>'QA action','coupon_code'=>'QA10'] as $key=>$text)if(isset($controls[$key])&&empty($settings[$key]))$settings[$key]=$text;
  if(isset($controls['link']))$settings['link']=['url'=>home_url('/contact-us/')];
  if($name==='audio'&&$wav_url)$settings['link']=['url'=>$wav_url];
  if(isset($controls['image'])&&empty($settings['image']['id'])&&$attachment_id)$settings['image']=['url'=>$attachment_url,'id'=>$attachment_id];
  if(isset($controls['testimonial_image'])&&empty($settings['testimonial_image']['id'])&&$attachment_id)$settings['testimonial_image']=['url'=>$attachment_url,'id'=>$attachment_id];
  if($name==='zarincode_trust_badges')$settings['badges']=[['_id'=>'qabadge1','provider'=>'custom','label'=>'نشان آزمونی کیفیت','link'=>['url'=>'https://example.com/verification','is_external'=>'1','nofollow'=>'0','custom_attributes'=>''],'image'=>['url'=>$attachment_url,'id'=>$attachment_id]]];
  // Atomic form fields expose schema props instead of legacy controls; author a real label association.
  $slug=substr(md5($name),0,6);
  $atomic=str_starts_with($name,'e-form-');
  if($atomic&&$name!=='e-form-label'){
   $settings['_cssid']='zcqa-field-'.$slug;
   if(in_array($name,['e-form-input','e-form-textarea','e-form-date-picker','e-form-time-picker','e-form-file-upload'],true))$settings['placeholder']='QA placeholder';
  }
  $existing=get_page_by_path('qa-widget-'.sanitize_title($name));
  $id=wp_insert_post(['ID'=>$existing?$existing->ID:0,'post_type'=>'page','post_status'=>'publish','post_title'=>'QA '.$name,'post_name'=>'qa-widget-'.sanitize_title($name)]);
  $widget_node=['id'=>'qw'.substr(md5($name),0,5),'elType'=>'widget','widgetType'=>$name,'settings'=>$settings,'elements'=>[]];
  $kids=[$widget_node];
  if($atomic&&$name!=='e-form-label'&&$name!=='e-form-submit-button')array_unshift($kids,['id'=>'ql'.$slug,'elType'=>'widget','widgetType'=>'e-form-label','settings'=>['text'=>'QA field label','input-id'=>'zcqa-field-'.$slug],'elements'=>[]]);
  // Floating-button widgets officially live in Elementor containers; their handlers require .e-con-inner.
  $floating=$name==='contact-buttons'||preg_match('/^(contact-buttons-var|floating-bars)/',$name);
  if($atomic)$tree=[['id'=>'qf'.$slug,'elType'=>'container','settings'=>[],'elements'=>[['id'=>'qg'.$slug,'elType'=>'e-form','settings'=>[],'elements'=>$kids]]]];
  elseif($floating)$tree=[['id'=>'qf'.substr(md5($name),0,5),'elType'=>'container','settings'=>[],'elements'=>[$widget_node]]];
  else$tree=[['id'=>'qa'.substr(md5($name),0,5),'elType'=>'section','settings'=>[],'elements'=>[['id'=>'qc'.substr(md5($name),0,5),'elType'=>'column','settings'=>['_column_size'=>100],'elements'=>[$widget_node]]]]];
  update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);update_post_meta($id,'_wp_page_template','elementor_header_footer');
  $types=[];foreach($controls as $key=>$control){$types[$control['type']??'unknown']=($types[$control['type']??'unknown']??0)+1;}
  $inventory[]=['widget'=>$name,'class'=>get_class($prototype),'source'=>(new ReflectionClass($prototype))->getFileName(),'page'=>$id,'url'=>get_permalink($id),'controls'=>count($controls),'control_types'=>$types,'custom'=>str_starts_with($name,'zarincode_'),'dependencies'=>['scripts'=>$prototype->get_script_depends(),'styles'=>$prototype->get_style_depends()],'requires_context'=>str_contains($name,'woocommerce')||str_starts_with($name,'theme-')||str_starts_with($name,'loop-'),'status'=>'fixture_created'];
 } catch(Throwable $e){$inventory[]=['widget'=>$name,'status'=>'fixture_failed','error'=>get_class($e).': '.$e->getMessage()];}
}
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key='_elementor_element_cache' OR meta_key='_elementor_css'");
if (class_exists('\\Elementor\\Plugin')) { \Elementor\Plugin::instance()->files_manager->clear_cache(); }
$blocks=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA core blocks','post_content'=>'<!-- wp:heading --><h2 class="wp-block-heading">Accessible block heading</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Core block content and <a href="/">homepage</a>.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/">Return home</a></div><!-- /wp:button --></div><!-- /wp:buttons -->']);
$out=['environment'=>['wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'elementor'=>ELEMENTOR_VERSION,'pro'=>ELEMENTOR_PRO_VERSION,'woocommerce'=>WC_VERSION],'widgets'=>$inventory,'blocks'=>get_permalink($blocks),'product'=>$product?get_permalink($product->get_id()):null,'product_id'=>$product?$product->get_id():0,'home'=>home_url('/')];
file_put_contents(getenv('ZC_QA_REPORT')?:'/tmp/zc-complete/inventory.json',wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($inventory)." widget fixtures created\n";
