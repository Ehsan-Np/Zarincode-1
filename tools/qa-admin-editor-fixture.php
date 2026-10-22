<?php
/** Render actual administrator field surfaces in a disposable WordPress fixture. */
if(!defined('WP_CLI')||!WP_CLI||!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA||wp_get_environment_type()!=='local')throw new RuntimeException('Disposable fixture only');
$root=getenv('ZC_QA_ROOT');if(!$root||!str_starts_with(realpath($root)?:'','/tmp/zc-'))throw new RuntimeException('Disposable output directory required');
require_once ABSPATH.'wp-admin/includes/template.php';
if(!class_exists(\Zarincode\Theme\Core\Social_Manager::class))require dirname(__DIR__).'/zarincode/inc/Core/Social_Manager.php';
wp_set_current_user(1);$old=get_option('zarincode_custom_socials',[]);$options=get_option('zarincode_opt',[]);
try {
    update_option('zarincode_custom_socials',[['id'=>'stable-social','name'=>'Original network']]);
    update_option('zarincode_opt',array_replace(is_array($options)?$options:[],['feature_social_profiles'=>true]));
    ob_start();
    \Zarincode\Runtime260\Admin\Product_Policy_Admin::fields(['faqs'=>[['id'=>'stable-policy','question'=>'Original question','answer'=>'Answer']]]);
    \Zarincode\Theme\Core\Social_Manager::page();
    file_put_contents($root.'/admin-editor-fixture.html',ob_get_clean());
} finally {update_option('zarincode_custom_socials',$old);update_option('zarincode_opt',$options);}
