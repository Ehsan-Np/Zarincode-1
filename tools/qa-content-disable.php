<?php
/** Fresh-request disabled-feature hooks/data check. Disposable local WordPress only. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== 'local') throw new RuntimeException('Disposable local fixture required');
$root=getenv('ZC_QA_ROOT');
if (!$root || !str_starts_with(realpath($root) ?: '', '/tmp/zc-')) throw new RuntimeException('Disposable report directory required');
$file=$root.'/content-disable-backup.json';
$keys=['zarincode_opt','zarincode_product_policies','zarincode_custom_socials'];
$stage=getenv('ZC_QA_STAGE');
if($stage==='prepare') {
    $backup=[];foreach($keys as $key)$backup[$key]=get_option($key,null);
    file_put_contents($file,wp_json_encode($backup));
    $options=is_array($backup['zarincode_opt'])?$backup['zarincode_opt']:[];
    foreach(['product_policies','social_profiles','threaded_discussion'] as $id)$options['feature_'.$id]=false;
    update_option('zarincode_opt',$options);
    update_option('zarincode_product_policies',['summary'=>'Retained policy','faqs'=>[['id'=>'stable','question'=>'Question','answer'=>'Answer']]]);
    update_option('zarincode_custom_socials',[['id'=>'stable','name'=>'Retained profile']]);
} elseif($stage==='verify') {
    $checks=[];
    foreach(['product_policies','social_profiles','threaded_discussion'] as $id)$checks['disabled_'.$id]=!\Zarincode\Runtime260\Features\Manager::enabled($id);
    $checks['no_policy_save_hook']=!has_action('save_post_product',[\Zarincode\Runtime260\Content\Product_Policy_Store::class,'save_product']);
    $checks['no_social_save_hook']=!has_action('admin_init',[\Zarincode\Runtime260\Content\Social_Profile_Store::class,'save']);
    $checks['no_discussion_filter']=!has_filter('preprocess_comment',[\Zarincode\Runtime260\Content\Discussion_Service::class,'prepare']);
    $checks['policies_retained']=get_option('zarincode_product_policies')['faqs'][0]['id']==='stable';
    $checks['profiles_retained']=get_option('zarincode_custom_socials')[0]['id']==='stable';
    echo wp_json_encode(['checks'=>$checks,'passed'=>!in_array(false,$checks,true)],JSON_PRETTY_PRINT)."\n";
    if(in_array(false,$checks,true))throw new RuntimeException('Disabled-feature regression');
} elseif($stage==='restore') {
    $backup=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
    foreach($keys as $key){if($backup[$key]===null)delete_option($key);else update_option($key,$backup[$key]);}
    unlink($file);
} else throw new RuntimeException('Explicit prepare/verify/restore stage required');
