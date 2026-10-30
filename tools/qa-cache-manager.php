<?php
/** Isolated doubles: security, lock ownership, adapter failures, multisite and escaped reporting. */
define('ABSPATH',__DIR__);define('MINUTE_IN_SECONDS',60);
$cap=true;$multi=false;$options=[];$calls=[];$hooks=[];$transients=[];$nonce=true;$checks=0;
class WP_Error {public function __construct(public $code,public $message){} public function get_error_code(){return $this->code;}public function get_error_message(){return $this->message;}}
function is_wp_error($v){return $v instanceof WP_Error;}
function current_user_can($c){return $GLOBALS['cap'];}function is_multisite(){return $GLOBALS['multi'];}
function add_action($h,$c,...$a){$GLOBALS['hooks'][$h][]=$c;}function has_action($h){return isset($GLOBALS['hooks'][$h])?0:false;}
function do_action($h,...$a){$GLOBALS['calls'][]=$h;foreach($GLOBALS['hooks'][$h]??[] as $c)$c(...$a);}
function get_option($k,$d=false){return $GLOBALS['options'][$k]??$d;}
function add_option($k,$v,...$a){if(array_key_exists($k,$GLOBALS['options']))return false;$GLOBALS['options'][$k]=$v;return true;}
function update_option($k,$v,...$a){$GLOBALS['options'][$k]=$v;return true;}
function wp_generate_uuid4(){return uniqid('uuid-',true);}function wp_json_encode($v){return json_encode($v);}
function wp_cache_delete(...$a){}function wp_cache_flush(){$GLOBALS['calls'][]='object';return true;}
function rocket_clean_domain(){$GLOBALS['calls'][]='rocket';throw new RuntimeException('SECRET_SHOULD_NOT_LEAK');}
function w3tc_flush_all(){$GLOBALS['calls'][]='w3tc';return false;}
function wp_cache_clear_cache($id){$GLOBALS['calls'][]='super-'.$id;}
function get_current_blog_id(){return 7;}function get_current_user_id(){return 2;}
function set_transient($k,$v,$ttl){$GLOBALS['transients'][$k]=$v;}function get_transient($k){return $GLOBALS['transients'][$k]??false;}
function admin_url($p){return 'https://example.test/wp-admin/'.$p;}function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES);}
function esc_url($v){return esc_html($v);}function wp_date($f,$t){return date($f,$t);}
function wp_die($msg,...$a){throw new RuntimeException('DIE:'.$msg);}function check_admin_referer($action){if(!$GLOBALS['nonce'])throw new RuntimeException('NONCE');}
function wp_safe_redirect($url){throw new RuntimeException('REDIRECT:'.$url);}
function wp_nonce_field($a){echo '<input name="_wpnonce" value="nonce">';}
function submit_button($label,...$a){echo '<button>'.esc_html($label).'</button>';}
function add_management_page(...$a){}
class Cache_QA_DB {
 public $options='wp_options';public function prepare($sql,...$args){return $args;}
 public function query($args){[$key,$value]=$args;if(($GLOBALS['options'][$key]??null)!==$value)return 0;unset($GLOBALS['options'][$key]);return 1;}
}
$wpdb=new Cache_QA_DB;
require dirname(__DIR__).'/zarincode-core/includes/Admin/Cache_Manager.php';
use Zarincode\Runtime260\Admin\Cache_Manager as Cache;
$ok=static function($v,$m)use(&$checks){if(!$v)throw new RuntimeException($m);++$checks;};
Cache::boot();$ok(isset($hooks['admin_post_zarincode_purge_cache']),'Authenticated POST endpoint');$ok(!isset($hooks['admin_post_nopriv_zarincode_purge_cache']),'No anonymous endpoint');
$bar=new class {public $nodes=[];public function add_node($v){$this->nodes[]=$v;}};
$cap=false;Cache::toolbar($bar);$ok(!$bar->nodes,'No editor/anonymous toolbar action');$ok(is_wp_error(Cache::purge()),'Purge enforces capability independently');$ok(!$calls,'Unauthorized purge has no effects');
$cap=true;Cache::toolbar($bar);$ok(str_contains($bar->nodes[0]['href'],'tools.php?page=zarincode-cache'),'Toolbar is confirmation GET, not purge');
$_SERVER['REQUEST_METHOD']='GET';try{Cache::handle();throw new Exception('GET accepted');}catch(RuntimeException $e){$ok(str_starts_with($e->getMessage(),'DIE:'),'GET blocked');}$ok(!$calls,'GET had no side effects');
$_SERVER['REQUEST_METHOD']='POST';$nonce=false;try{Cache::handle();throw new Exception('Nonce accepted');}catch(RuntimeException $e){$ok($e->getMessage()==='NONCE','Bad nonce blocked');}$ok(!$calls,'Bad nonce had no effects');$nonce=true;
$lock=wp_json_encode(['time'=>time(),'token'=>'another-request']);$options['zarincode_cache_purge_lock']=$lock;
$r=Cache::purge();$ok(is_wp_error($r)&&$r->get_error_code()==='cache_busy','Concurrent purge rejected');$ok($options['zarincode_cache_purge_lock']===$lock,'Other owner lock retained');
$options['zarincode_cache_purge_lock']=wp_json_encode(['time'=>time()-301,'token'=>'stale']);
add_action('litespeed_purge_all',static function(){});add_action('wpfc_clear_all_cache',static function(){});add_action('zarincode_purge_site_cache',static function($id){$GLOBALS['calls'][]='custom-'.$id;});
$r=Cache::purge();$ok(!is_wp_error($r),'Expired lock recovered');$ok(!isset($options['zarincode_cache_purge_lock']),'Lock released after provider errors');
$ok(isset($options['zarincode_cache_epoch']),'PWA epoch rotated');$statuses=array_column($r['steps'],'status','name');
$ok($statuses['WP Rocket']==='failed','Exception bounded');$ok($statuses['W3 Total Cache']==='failed','False provider response reported');$ok($statuses['LiteSpeed Cache']==='requested','Async purge not claimed as verified success');
$ok(in_array('object',$calls,true)&&in_array('super-7',$calls,true),'Other providers continue, Super Cache scoped to blog');$ok(in_array('custom-7',$calls,true),'Custom integration receives site ID');
$ok(!str_contains(json_encode($r),'SECRET_SHOULD_NOT_LEAK'),'Secret in exception never exposed');
$multi=true;$calls=[];$r=Cache::purge();$ok(!in_array('object',$calls,true),'No network object-cache flush');$ok(!in_array('rocket',$calls,true)&&!in_array('w3tc',$calls,true),'No installation-wide cache APIs on multisite');$ok(in_array('super-7',$calls,true),'Blog-aware API still works on multisite');
$multi=false;$calls=[];try{Cache::handle();throw new Exception('Expected redirect');}catch(RuntimeException $e){$ok(str_starts_with($e->getMessage(),'REDIRECT:https://example.test/wp-admin/tools.php?page=zarincode-cache'),'Redirect is fixed safe admin URL');}
$ok(isset($transients['zc_cache_report_2']),'Report stored only for current user');
$transients['zc_cache_report_2']['steps'][]=['name'=>'<script>bad</script>','status'=>'failed','detail'=>'<img onerror=bad>'];ob_start();Cache::page();$html=ob_get_clean();
$ok(str_contains($html,'method="post"')&&str_contains($html,'name="_wpnonce"'),'UI has POST and nonce');$ok(!str_contains($html,'<script>bad')&&str_contains($html,'&lt;script&gt;'),'Report escaped');
$ok(str_contains($html,'CDN')&&str_contains($html,'transient'),'Limitations explicit');
echo "PASS: $checks isolated cache/security/adapter/multisite assertions; provider APIs are doubles.\n";
