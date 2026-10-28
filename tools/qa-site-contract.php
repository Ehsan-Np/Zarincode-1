<?php
/** Isolated PHP contracts with WP API doubles; not a WordPress integration test.
 * Run: php tools/qa-site-contract.php
 */
define('ABSPATH', __DIR__);
$root=dirname(__DIR__);$checks=0;$options=[];$flags=[];$user=7;$meta=[];$cap=true;$valid_nonce=true;$json=null;
function ok($value,$message){global $checks;if(!$value)throw new RuntimeException($message);++$checks;}
function esc_html__($s,$d=''){return $s;}function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_attr($s){return esc_html($s);}function esc_url($s){return (string)$s;}
function add_action(...$args){}function add_filter(...$args){}function apply_filters($name,$value,...$args){return $GLOBALS['filters'][$name]??$value;}
function current_user_can($name){return $GLOBALS['cap'];}
function sanitize_hex_color($s){return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$s)?$s:null;}
function sanitize_text_field($s){return trim(strip_tags($s));}function sanitize_textarea_field($s){return trim(strip_tags($s));}
function esc_url_raw($s,$protocols=[]){return preg_match('~^https?://~',$s)?$s:'';}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function zarincode_get_option($key,$default=''){return $GLOBALS['options']['zarincode_opt'][$key]??$default;}
function wp_json_encode($s,$flags=0){return json_encode($s,$flags);}
function home_url($s='/'){return 'https://example.test'.$s;}
function get_current_user_id(){return $GLOBALS['user'];}
function get_user_meta($id,$key,$single){return $GLOBALS['meta'][$id][$key]??'';}
function update_user_meta($id,$key,$value){$GLOBALS['meta'][$id][$key]=$value;}
function check_ajax_referer(...$args){if(!$GLOBALS['valid_nonce'])throw new RuntimeException('nonce');}
function wp_send_json_error($data=null,$status=0){$GLOBALS['json']=['success'=>false,'status'=>$status];}
function wp_send_json_success(){ $GLOBALS['json']=['success'=>true]; }
function get_current_screen(){return null;}function admin_url($s=''){return 'https://example.test/wp-admin/'.$s;}
function wp_nonce_field(...$args){echo '<input name="nonce" value="test">';}
function wp_strip_all_tags($s){return strip_tags($s);}function strip_shortcodes($s){return preg_replace('/\[[^\]]+\]/','',$s);}
function is_singular($type=null){if(empty($GLOBALS['flags']['singular']))return false;return $type===null||in_array(get_post_type(),(array)$type,true);}
function is_404(){return !empty($GLOBALS['flags']['404']);}function is_search(){return !empty($GLOBALS['flags']['search']);}
function is_cart(){return !empty($GLOBALS['flags']['cart']);}function is_checkout(){return !empty($GLOBALS['flags']['checkout']);}function is_account_page(){return !empty($GLOBALS['flags']['account']);}
function is_paged(){return !empty($GLOBALS['flags']['paged']);}function post_password_required(){return !empty($GLOBALS['flags']['password']);}
function is_embed(){return false;}function is_admin(){return false;}function is_front_page(){return !empty($GLOBALS['flags']['front']);}
function is_home(){return !empty($GLOBALS['flags']['home']);}function is_shop(){return !empty($GLOBALS['flags']['shop']);}
function is_day(){return false;}function is_month(){return false;}function is_year(){return false;}
function is_category(){return !empty($GLOBALS['flags']['category']);}function is_tag(){return false;}function is_tax(){return false;}
function is_author(){return false;}function is_post_type_archive(){return false;}
function get_query_var($key){return $key==='paged'?2:'';}function get_pagenum_link($n){return home_url('/category/news/page/'.$n.'/');}
function get_permalink($id=null){return home_url('/'.($id===99?'magazine':'entry').'/');}
function get_post_type($id=null){return $GLOBALS['flags']['type']??'post';}function get_queried_object_id(){return 10;}
function get_post_field($key,$id){return $key==='post_excerpt'?'نمونه توضیح معتبر':'<p>Content</p>';}
function get_the_title($id=null){return 'عنوان خدمت';}function get_bloginfo($key){return $key==='language'?'fa-IR':'زرین کد';}
function get_the_post_thumbnail_url(...$args){return false;}function get_site_icon_url($n){return '';}
function get_theme_file_uri($s){return home_url('/theme/'.$s);}function get_template_directory(){return dirname(__DIR__).'/zarincode';}
function wp_get_document_title(){return 'آرشیو';}function wp_get_canonical_url(){return get_permalink();}
function get_the_date($s='',$id=null){return '2026-10-28T00:00:00+00:00';}function get_the_modified_date($s='',$id=null){return get_the_date();}
require $root.'/zarincode/inc/Core/Layout.php';
require $root.'/zarincode/inc/Redux/OptionsConfig.php';
require $root.'/zarincode/inc/Core/Settings_Security.php';
require $root.'/zarincode/inc/Compatibility/SEO.php';
require $root.'/zarincode/inc/Core/Seo_Meta.php';
require $root.'/zarincode-core/includes/Demo/Demo_Seeder.php';
use Zarincode\Theme\Core\Layout;
use Zarincode\Theme\Core\Settings_Security as Security;
use Zarincode\Theme\Core\Seo_Meta as SEO;
use Zarincode\Theme\Redux\OptionsConfig;
if(in_array('--inventory',$argv??[],true)){echo json_encode(OptionsConfig::get_sections_schema(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);return;}
$defaults=OptionsConfig::get_all_defaults();$clean=Security::sanitize($defaults,[]);
foreach($defaults as $key=>$value){ok(array_key_exists($key,$clean),'Missing '.$key);}
foreach(OptionsConfig::get_sections_schema() as $section)foreach($section['fields'] as $field){
 $key=$field['id'];
 if($field['type']==='select'){ok(Security::sanitize([$key=>'INVALID'],[])[$key]===$field['default'],'select '.$key);}
 if($field['type']==='switch'){ok(Security::sanitize([$key=>'off'],[])[$key]===false,'switch off '.$key);ok(Security::sanitize([$key=>'1'],[])[$key]===true,'switch on '.$key);}
}
ok(Security::sanitize(['body_line_height'=>'1; color:red'],[])['body_line_height']==='1.9','line-height injection');
ok(Security::sanitize(['accent_color'=>'red; background:url(x)'],[])['accent_color']==='#FDD05B','color injection');
ok(Security::sanitize(['body_font_size'=>1000],[])['body_font_size']===24,'font bound');
ok(Security::sanitize(['shop_per_page'=>-1],[])['shop_per_page']===1,'pagination bound');
ok(Security::sanitize(['logo_light'=>'javascript:alert(1)'],[])['logo_light']==='','media URL');
ok(Security::sanitize(['body_font_size'=>[]],['body_font_size'=>16])['body_font_size']===16,'malformed input');
ok(Security::sanitize(['body_font_size'=>[]],['body_font_size'=>[]])['body_font_size']===15,'malformed old input');
$badge='<a href="https://example.test"><img src="https://example.test/badge.png"></a>';
ok(Security::sanitize(['footer_enamad_html'=>$badge],[])['footer_enamad_html']===$badge,'badge must not be stripped');
$cap=false;ok(Security::sanitize(['custom_head_js'=>'bad'],['custom_head_js'=>'trusted'])['custom_head_js']==='trusted','restricted snippet');$cap=true;
ok(Security::css('</style><script>x</script>')==='/stylescriptx/script','CSS markup boundary');
ok(Layout::responsive([])['desktop']===1469,'auto width');
ok(Layout::responsive(['layout_width_desktop'=>1500,'layout_width_scale'=>108])['desktop']===1500,'no double scale');
ok(Layout::responsive(['layout_width_mobile'=>999])['mobile']===767,'mobile bound');
foreach(['search','404','cart','checkout','account'] as $state){$flags=[$state=>true];$r=SEO::robots(['index'=>true,'nofollow'=>true]);ok(!isset($r['index'])&&!empty($r['noindex'])&&!empty($r['nofollow']),'robots '.$state);}
$flags=['singular'=>true,'password'=>true];ob_start();SEO::meta();ok(ob_get_clean()==='','protected metadata');
$flags=['paged'=>true];ob_start();SEO::meta();$html=ob_get_clean();ok(str_contains($html,'https://example.test/category/news/page/2/'),'self canonical page 2');ok(!str_contains($html,'name="robots"'),'single robots owner');
$flags=['home'=>true];$options['page_for_posts']=99;ob_start();SEO::meta();ok(str_contains(ob_get_clean(),'https://example.test/magazine/'),'blog canonical');
$options['blog_public']=0;ok(SEO::robots_txt('User-agent: *',true)==='User-agent: *','private sitemap');$options['blog_public']=1;
ok(substr_count(SEO::robots_txt(SEO::robots_txt('',true),true),'Sitemap:')===1,'one sitemap');
foreach(['zc_service'=>'Service','zc_project'=>'CreativeWork'] as $type=>$expected){$flags=['singular'=>true,'type'=>$type];ob_start();SEO::content_schema();$html=ob_get_clean();preg_match('~<script[^>]*>(.*)</script>~s',$html,$m);$graph=json_decode($m[1]??'',true);ok(($graph['@type']??'')===$expected,'entity '.$type);ok(($graph['inLanguage']??'')==='fa-IR','BCP47');ok(!isset($graph['aggregateRating'],$graph['offers']),'no fake reviews');}
$flags=['front'=>true];ob_start();SEO::website_schema();$html=ob_get_clean();ok(str_contains($html,'WebSite')&&!str_contains($html,'SearchAction'),'site schema');
$seeder=(new ReflectionClass(Zarincode\Runtime260\Demo\Demo_Seeder::class))->newInstanceWithoutConstructor();
$cap=false;$seeder->dismiss_notice_ajax();ok($json['status']===403&&!$meta,'notice permissions');
$cap=true;$valid_nonce=false;try{$seeder->dismiss_notice_ajax();ok(false,'nonce accepted');}catch(RuntimeException $e){ok(!$meta,'notice nonce');}
$valid_nonce=true;$seeder->dismiss_notice_ajax();ok($meta[7]['zc_demo_notice_dismissed']==='1','notice persisted');
ob_start();$seeder->render_admin_notice_banner();ok(ob_get_clean()==='','notice stays dismissed');
$user=8;ob_start();$seeder->render_admin_notice_banner();ok(str_contains(ob_get_clean(),'دیگر نمایش نده'),'other user independent');
// Lint every source file using the real PHP parser, without executing template side effects.
$linted=0;foreach(['zarincode','zarincode-core'] as $tree){$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$tree));foreach($it as $file){if($file->getExtension()==='php'){token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE);++$linted;}}}
define('WPSEO_VERSION','test');
$flags=['singular'=>true,'type'=>'zc_service'];ob_start();SEO::content_schema();SEO::meta();SEO::website_schema();ok(ob_get_clean()==='','SEO plugin owns output');
ok(SEO::robots(['index'=>true])===['index'=>true],'SEO plugin robots ownership');
echo "PASS: $checks PHP assertions; $linted source PHP files syntax-checked. WP APIs are doubles, not an integration environment.\n";
