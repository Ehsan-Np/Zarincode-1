<?php
/** Destructive cache tests on disposable local WordPress ONLY. Never run on production. */
if(!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA||wp_get_environment_type()!=='local')throw new RuntimeException('Disposable local WordPress only.');
use Zarincode\Runtime260\Admin\Cache_Manager as Cache;
require_once ABSPATH.'wp-admin/includes/template.php';
$checks=0;$ok=static function($v,$m)use(&$checks){if(!$v)throw new RuntimeException($m);++$checks;};
$admins=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);wp_set_current_user((int)$admins[0]);
$ok(has_action('admin_post_zarincode_purge_cache')!==false,'Boot registered authenticated action');
$old=get_option('zarincode_cache_epoch','');
set_transient('qa_customer_session_sentinel',['keep'=>true],600);update_option('qa_cache_settings_sentinel',['keep'=>true]);
$post=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Cache safety fixture']);
wp_cache_set('qa_cache_sentinel','cached','zc-cache-qa');$ok(wp_cache_get('qa_cache_sentinel','zc-cache-qa')==='cached','Actual WP object cache seeded');
$r=Cache::purge();$ok(is_array($r),'Real purge completes');$ok(wp_cache_get('qa_cache_sentinel','zc-cache-qa')===false,'Actual WordPress object cache flushed');
$ok(get_option('zarincode_cache_epoch')!==$old,'Worker epoch persisted in DB');$ok(get_option('zarincode_cache_purge_lock',null)===null,'Owned lock deleted in real DB');
$ok(get_transient('qa_customer_session_sentinel')===['keep'=>true],'Valid transient / customer session sentinel preserved');
$ok(get_option('qa_cache_settings_sentinel')===['keep'=>true],'Settings preserved');$ok(get_post($post)->post_title==='Cache safety fixture','Content preserved');
add_option('zarincode_cache_purge_lock',wp_json_encode(['time'=>time(),'token'=>'other']),'',false);
$r=Cache::purge();$ok(is_wp_error($r)&&$r->get_error_code()==='cache_busy','Real DB mutex blocks duplicate requests');
update_option('zarincode_cache_purge_lock',wp_json_encode(['time'=>time()-301,'token'=>'expired']));$r=Cache::purge();$ok(!is_wp_error($r),'Expired lock recovered via compare/delete');
$ok(get_option('zarincode_cache_purge_lock',null)===null,'Recovered mutex released');
// A third party replacing the lock mid-request must retain ownership in finally.
$replacement=wp_json_encode(['time'=>time(),'token'=>'replacement']);$replace=static function()use($replacement){update_option('zarincode_cache_purge_lock',$replacement);};
add_action('zarincode_purge_site_cache',$replace);Cache::purge();remove_action('zarincode_purge_site_cache',$replace);
$ok(get_option('zarincode_cache_purge_lock')===$replacement,'Old operation cannot unlock a new owner');delete_option('zarincode_cache_purge_lock');
// Authenticate actual POST / nonce and intercept only the final redirect, before handler exits.
$redirect=static function($url){throw new RuntimeException('QA_REDIRECT:'.$url);};
$die=static function(){return static function($message){throw new RuntimeException('QA_DIE:'.(is_scalar($message)?$message:'error'));};};
add_filter('wp_die_handler',$die);
$_SERVER['REQUEST_METHOD']='GET';try{Cache::handle();throw new LogicException('GET accepted');}catch(RuntimeException $e){$ok(str_starts_with($e->getMessage(),'QA_DIE:'),'GET rejected on real WordPress');}
$_SERVER['REQUEST_METHOD']='POST';$_REQUEST['_wpnonce']='bad';try{Cache::handle();throw new LogicException('Bad nonce accepted');}catch(RuntimeException $e){$ok(str_starts_with($e->getMessage(),'QA_DIE:'),'Invalid nonce rejected on real WordPress');}
$_REQUEST['_wpnonce']=wp_create_nonce('zarincode_purge_cache');add_filter('wp_redirect',$redirect);
try{Cache::handle();throw new LogicException('Expected redirect');}catch(RuntimeException $e){$ok($e->getMessage()==='QA_REDIRECT:'.Cache::url(),'Valid real nonce POST executes and redirects to fixed URL');}
remove_filter('wp_redirect',$redirect);remove_filter('wp_die_handler',$die);
$report=get_transient('zc_cache_report_'.get_current_user_id());$ok(is_array($report)&&!empty($report['steps']),'Current-user report stored after cache flush');
ob_start();Cache::page();$html=ob_get_clean();$ok(str_contains($html,'name="_wpnonce"')&&str_contains($html,'method="post"'),'Actual UI nonce/POST');
$ok(str_contains($html,'CDN')&&str_contains($html,'Service Worker'),'Actual UI limits and PWA scope');
wp_set_current_user(0);$ok(is_wp_error(Cache::purge()),'Anonymous user forbidden on actual WordPress');
echo "PASS: $checks real WordPress cache/nonce/DB-lock/safety assertions; no external cache plugins installed.\n";
