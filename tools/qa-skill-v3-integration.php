<?php
/** wp eval-file in explicitly marked disposable local QA only. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type()!=='local')throw new RuntimeException('Disposable local QA required');
$checks=[];$check=static function($name,$ok)use(&$checks){$checks[$name]=(bool)$ok;if(!$ok)throw new RuntimeException('FAILED '.$name);};
$original=get_option('zarincode_opt',[]);$theme=get_stylesheet();$user=get_current_user_id();$post_id=0;
try{
 $admin=get_users(['role'=>'administrator','number'=>1])[0];wp_set_current_user($admin->ID);
 $check('plugin_loaded_from_plugin',(new ReflectionClass(\Zarincode\Runtime260\Features\Manager::class))->getFileName()===WP_PLUGIN_DIR.'/zarincode-core/includes/Features/Manager.php');
 $check('cpt_registered',post_type_exists('zc_service')&&post_type_exists('zc_project'));
 $post_id=wp_insert_post(['post_type'=>'zc_service','post_title'=>'Persistent QA fixture','post_status'=>'publish']);
 update_post_meta($post_id,'_zc_fixture',['keep'=>true]);
 $o=$original;$o['zc_fixture_unknown']=['items'=>[['_id'=>'unchanged']]];foreach(['native_widgets','studio_widgets','professional_widgets'] as $id)$o['feature_'.$id]=true;update_option('zarincode_opt',$o);
 $wm=\Elementor\Plugin::instance()->widgets_manager;$widgets=$wm->get_widget_types();$custom=array_filter($widgets,static fn($w)=>str_starts_with($w->get_name(),'zarincode_'));
 $check('26_widgets',count($custom)===26);
 foreach($custom as $id=>$widget){$check('contract_'.$id,method_exists($widget,'get_zc_contract')&&$widget->get_zc_contract()['id']===$id);$widget->get_controls();}
 $check('native_controls_initialized',count($custom['zarincode_hero_intro']->get_controls())>0);
 $check('no_auto_install_theme_switch',!has_action('after_switch_theme',[\Zarincode\Theme\Plugins\Plugin_Manager::instance(),'auto_install_bundled_core_plugins']));
 wp_set_current_user(0);$result=\Zarincode\Theme\Plugins\Plugin_Manager::install_and_activate_plugin('zarincode-core');$check('installer_guest_denied',!$result['success']&&$result['status']==='forbidden');wp_set_current_user($admin->ID);
 $check('installer_action_whitelist',!\Zarincode\Theme\Plugins\Plugin_Manager::install_and_activate_plugin('zarincode-core','unexpected')['success']);
 $check('schema_upgrade_idempotent',\Zarincode\Runtime260\Support\Settings_Schema::upgrade(['_zc_settings_schema'=>1,'_id'=>'same'])===['_zc_settings_schema'=>1,'_id'=>'same']);
 // Real switch, not a mock: CPT and data must remain owned by the active plugin.
 $fallback='';foreach(wp_get_themes() as $slug=>$candidate)if(str_starts_with($slug,'twentytwenty')){$fallback=$slug;break;}
 if(!$fallback)throw new RuntimeException('Official fallback theme required');switch_theme($fallback);
 $check('real_other_theme_active',get_template()!== 'zarincode');
 $check('cpt_survives_switch',post_type_exists('zc_service')&&get_post($post_id)->post_type==='zc_service');
 $check('meta_survives_switch',get_post_meta($post_id,'_zc_fixture',true)===['keep'=>true]);
 $check('unknown_options_survive',get_option('zarincode_opt')['zc_fixture_unknown']===$o['zc_fixture_unknown']);
 $reflection=new ReflectionClass(\Zarincode\Runtime260\PostTypes\CPT_Manager::class);$check('provider_remains_plugin',str_starts_with($reflection->getFileName(),WP_PLUGIN_DIR.'/zarincode-core/'));
 $check('diagnostics_no_raw_settings',!isset(\Zarincode\Runtime260\Support\Diagnostics::snapshot()['zc_fixture_unknown']));
 file_put_contents(getenv('ZC_QA_REPORT')?:'/tmp/zc-v3qa/integration.json',wp_json_encode(['environment'=>['wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'elementor'=>ELEMENTOR_VERSION,'fallback_theme'=>$fallback,'database'=>$GLOBALS['wpdb']->db_version()],'checks'=>$checks,'limitations'=>['Same-process theme switch is supplemented by fresh-request tests.','Not Pro/Woo/WPML, visual or full accessibility coverage.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo count($checks).' integration assertions passed.'.PHP_EOL;
}finally{wp_set_current_user($admin->ID);switch_theme($theme);update_option('zarincode_opt',$original);if($post_id)wp_delete_post($post_id,true);wp_set_current_user($user);}
