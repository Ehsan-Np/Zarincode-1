<?php
/** Real GD + real WP editor classes, with WP environment/API doubles (no DB).
 * ZARINCODE_QA_WP_EDITOR=/path/to/editor/class/files php tools/qa-webp-uploads.php
 * See docs/WEBP-UPLOADS-FA.md for pinned upstream fixtures. No fixtures are bundled.
 */
define('ABSPATH',__DIR__);
$fixtures=getenv('ZARINCODE_QA_WP_EDITOR');
if(!$fixtures||!is_file($fixtures.'/class-wp-image-editor.php')||!is_file($fixtures.'/class-wp-image-editor-gd.php')){fwrite(STDERR,"Real WP editor fixtures required; test NOT run.\n");exit(1);}
if(!function_exists('imagewebp')){fwrite(STDERR,"GD with WebP required; test NOT run.\n");exit(1);}
$checks=0;$hooks=[];$options=[];$reason='';$mode='';$webp_supported=true;$webp_allowed=true;$exclude=false;$keep_source=false;
$base=sys_get_temp_dir().'/zc-webp-test-'.bin2hex(random_bytes(6));mkdir($base,0700,true);mkdir($base.'/2026',0700);
class WP_Error{public function __construct(public string $code='',public string $message='',public $data=null){}}
function is_wp_error($v){return $v instanceof WP_Error;}
function __($s,$domain=''){return $s;}
function add_filter($hook,$callback,$priority=10,$accepted=1){$GLOBALS['hooks'][$hook][$priority][]=[$callback,$accepted];}
function apply_filters($hook,$value,...$args){
 if($hook==='zarincode_webp_upload_allowed'&&$GLOBALS['exclude'])return false;
 if($hook==='wp_image_maybe_exif_rotate'&&$GLOBALS['mode']==='rotate')return 6;
 $callbacks=$GLOBALS['hooks'][$hook]??[];ksort($callbacks);
 foreach($callbacks as $group)foreach($group as [$callback,$accepted])$value=$callback(...array_slice([$value,...$args],0,$accepted));return $value;
}
function do_action($hook,...$args){if($hook==='zarincode_webp_result'){$GLOBALS['reason']=$args[0];if($GLOBALS['mode']==='observer_throw')throw new RuntimeException('observer');}}
function get_option($key,$default=[]){return $GLOBALS['options'][$key]??$default;}
function wp_get_upload_dir(){return ['basedir'=>$GLOBALS['base'],'baseurl'=>'https://example.test/uploads','error'=>false];}
function wp_normalize_path($p){return str_replace('\\','/',$p);}function trailingslashit($p){return rtrim($p,'/\\').'/';}
function wp_basename($p,$suffix=''){return basename($p,$suffix);}function wp_filesize($p){return filesize($p);}
function wp_getimagesize($p){return @getimagesize($p);}
function wp_get_image_mime($p){return (@getimagesize($p))['mime']??false;}
function wp_get_mime_types(){return ['jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp','avif'=>'image/avif'];}
function wp_get_default_extension_for_mime_type($mime){foreach(wp_get_mime_types() as $ext=>$type)if($type===$mime)return explode('|',$ext)[0];return false;}
function get_allowed_mime_types(){return $GLOBALS['webp_allowed']?wp_get_mime_types():['png'=>'image/png'];}
function wp_image_editor_supports($args){return $GLOBALS['webp_supported'];}
function wp_unique_filename($dir,$name){if($GLOBALS['mode']==='filename_bad')return '../escape.webp';$stem=pathinfo($name,PATHINFO_FILENAME);$ext=pathinfo($name,PATHINFO_EXTENSION);$i=0;while(file_exists($dir.'/'.$name))$name=$stem.'-'.(++$i).'.'.$ext;return $name;}
function wp_delete_file($p){if($GLOBALS['keep_source']&&!str_ends_with($p,'.webp'))return;unlink($p);}
function wp_raise_memory_limit($context){return ini_get('memory_limit');}
function wp_convert_hr_to_bytes($s){$s=trim($s);$n=(float)$s;return (int)($n*match(strtolower(substr($s,-1))){'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1});}
function is_gd_image($image){return $image instanceof GdImage;}
function wp_is_stream($p){return str_contains($p,'://');}
function wp_mkdir_p($p){return is_dir($p)||mkdir($p,0755,true);}
function wp_get_image_editor_output_format(...$args){return [];}
function wp_get_webp_info($p){return ['type'=>'lossy'];}
function wp_imagecreatetruecolor($w,$h){$im=imagecreatetruecolor($w,$h);imagealphablending($im,false);imagesavealpha($im,true);imagefilledrectangle($im,0,0,$w,$h,imagecolorallocatealpha($im,0,0,0,127));return $im;}
require $fixtures.'/class-wp-image-editor.php';require $fixtures.'/class-wp-image-editor-gd.php';
class ZC_Failing_Editor extends WP_Image_Editor_GD{
 public function set_quality($quality=null,$dims=[]){if($GLOBALS['mode']==='quality_error'&&$quality!==null)return new WP_Error('quality');return parent::set_quality($quality,$dims);}
 public function save($destfilename=null,$mime_type=null){
  if($GLOBALS['mode']==='throw')throw new RuntimeException('encoder');
  if($GLOBALS['mode']==='error')return new WP_Error('save');
  if($GLOBALS['mode']==='corrupt'){file_put_contents($destfilename,'RIFFnotwebp');return ['path'=>$destfilename];}
  if($GLOBALS['mode']==='wrong_path')return ['path'=>__FILE__];
  if($GLOBALS['mode']==='wrong_size'){$im=imagecreatetruecolor(3,3);imagewebp($im,$destfilename);return ['path'=>$destfilename];}
  try{return parent::save($destfilename,$mime_type);}catch(Throwable $e){$GLOBALS['editor_error']=$e->getMessage();throw $e;}
 }
}
function wp_get_image_editor($file){
 if($GLOBALS['mode']==='verify_fail'&&str_ends_with($file,'.webp'))return new WP_Error('verify');
 if($GLOBALS['mode']==='no_decoder')return new WP_Error('decoder');
 $editor=new ZC_Failing_Editor($file);try{$loaded=$editor->load();}catch(Throwable $e){$GLOBALS['editor_error']=$e->getMessage();throw $e;}return is_wp_error($loaded)?$loaded:$editor;
}
$root=dirname(__DIR__);
require $root.'/zarincode-core/includes/Features/Registry.php';require $root.'/zarincode-core/includes/Features/Manager.php';
require $root.'/zarincode-core/includes/Media/Animation_Safety.php';require $root.'/zarincode-core/includes/Media/WebP_Uploads.php';
use Zarincode\Runtime260\Media\WebP_Uploads as WebP;
use Zarincode\Runtime260\Media\Animation_Safety as Frames;
WebP::boot();
function ok($v,$message){global $checks;if(!$v)throw new RuntimeException($message.' [reason: '.$GLOBALS['reason'].'; editor: '.($GLOBALS['editor_error']??'none').']');++$checks;}
function sample($name='image.png',$format='png',$w=320,$h=180){
 $im=wp_imagecreatetruecolor($w,$h);imagefilledrectangle($im,10,10,$w-10,$h-10,imagecolorallocatealpha($im,25,100,200,40));
 $p=$GLOBALS['base'].'/2026/'.$name;
 if($format==='jpg')imagejpeg($im,$p,95);elseif($format==='gif'){imagetruecolortopalette($im,true,32);imagegif($im,$p);}elseif($format==='webp')imagewebp($im,$p);elseif($format==='avif')imageavif($im,$p);else imagepng($im,$p);
 clearstatcache(true,$p);return ['file'=>$p,'url'=>'https://example.test/uploads/2026/'.$name,'type'=>wp_get_image_mime($p)];
}
function unchanged($upload,$expected){$hash=hash_file('sha256',$upload['file']);$before=glob($GLOBALS['base'].'/2026/*');$out=WebP::convert($upload);ok($out===$upload,$expected.' response');ok(hash_file('sha256',$upload['file'])===$hash,$expected.' original');ok(glob($GLOBALS['base'].'/2026/*')===$before,$expected.' cleanup');ok($GLOBALS['reason']===$expected,$expected.' reason');}
try{
 $options=['zarincode_opt'=>[]];
 $u=sample('default.jpg','jpg');$old_bytes=filesize($u['file']);$out=WebP::convert($u);ok($out['type']==='image/webp'&&filesize($out['file'])<$old_bytes,'default settings reduce JPEG');
 $options['zarincode_opt']['webp_only_smaller']=false;
 foreach(['png','jpg','gif'] as $ext){$u=sample('static.'.$ext,$ext);$out=apply_filters('wp_handle_upload',$u,'upload');ok($out['type']==='image/webp','real '.$ext.' MIME');ok(!file_exists($u['file']),'real '.$ext.' replaces source');ok(wp_get_image_mime($out['file'])==='image/webp','real '.$ext.' bytes');ok(getimagesize($out['file'])[0]===320,'dimensions '.$ext);ok(str_ends_with($out['url'],basename($out['file'])),'URL '.$ext);}
 // Opaque and transparent pixels both survive real GD PNG -> WebP.
 $u=sample('alpha.png');$out=WebP::convert($u);$decoded=imagecreatefromwebp($out['file']);ok(((imagecolorat($decoded,0,0)>>24)&127)>=120,'transparent background');ok(((imagecolorat($decoded,20,20)>>24)&127)>=30,'partial alpha');
 // Indexed PNG is normalized through the public WP crop API, not protected internals.
 $u=sample('indexed.png');$im=imagecreatefrompng($u['file']);imagetruecolortopalette($im,true,16);imagepng($im,$u['file']);$out=WebP::convert($u);ok($out['type']==='image/webp','indexed PNG');
 // Existing same-stem WebP output is never overwritten.
 $u=sample('collision.png');file_put_contents($base.'/2026/collision.webp','DO NOT REPLACE');$out=WebP::convert($u);ok(file_get_contents($base.'/2026/collision.webp')==='DO NOT REPLACE','collision preserved');ok(str_ends_with($out['file'],'collision-1.webp'),'unique filename');
 $u=sample('repeat.webp','webp');unchanged($u,'already_webp');
 $u=sample('broken.png');file_put_contents($u['file'],'not an image');clearstatcache(true,$u['file']);unchanged($u,'unsupported_input');
 $u=sample('mislabel.png');$u['type']='image/jpeg';unchanged($u,'unsupported_input');
 foreach(['error','throw','corrupt','wrong_path','wrong_size','no_decoder','verify_fail','quality_error','filename_bad'] as $failure){$mode=$failure;$u=sample('failure-'.$failure.'.png');unchanged($u,match($failure){'error','corrupt','wrong_path'=>'invalid_output','wrong_size'=>'dimension_mismatch','no_decoder'=>'decoder_unavailable','verify_fail'=>'output_decode_failed','quality_error'=>'quality_failed','filename_bad'=>'invalid_filename',default=>'conversion_failed'});}$mode='';
 $keep_source=true;$u=sample('keep.png');unchanged($u,'source_retained');$keep_source=false;
 $u=sample('offload.png');$u['url']='https://cdn.example.test/offload.png';unchanged($u,'remote_storage');
 $webp_supported=false;$u=sample('no-codec.png');unchanged($u,'webp_unavailable');$webp_supported=true;
 $webp_allowed=false;$u=sample('policy.png');unchanged($u,'webp_unavailable');$webp_allowed=true;
 $exclude=true;$u=sample('exclude.png');unchanged($u,'excluded');$exclude=false;
 $u=sample('animate.gif','gif');$bytes=file_get_contents($u['file']);$start=strpos($bytes,"\x2c",13);$frame=substr($bytes,$start,-1);file_put_contents($u['file'],substr($bytes,0,-1).$frame.';');clearstatcache(true,$u['file']);unchanged($u,'animated_or_unverified');
 $u=sample('multipicture.jpg','jpg');$bytes=file_get_contents($u['file']);file_put_contents($u['file'],substr($bytes,0,2)."\xff\xe2\x00\x06MPF\x00".substr($bytes,2));clearstatcache(true,$u['file']);unchanged($u,'animated_or_unverified');
 $u=sample('comment.gif','gif');$bytes=file_get_contents($u['file']);$packed=ord($bytes[10]);$offset=13+(($packed&128)?3*(1<<(($packed&7)+1)):0);
 file_put_contents($u['file'],substr($bytes,0,$offset)."\x21\xfe\x03\x2c\x00\x2c\x00".substr($bytes,$offset));clearstatcache(true,$u['file']);ok(Frames::single_frame($u['file'],'image/gif'),'GIF comment is not a frame');$out=WebP::convert($u);ok($out['type']==='image/webp','comment GIF real conversion');
 $u=sample('animate.png');$bytes=file_get_contents($u['file']);$chunk='acTL'.pack('NN',2,0);$chunk=pack('N',8).$chunk.pack('N',crc32($chunk));file_put_contents($u['file'],substr($bytes,0,33).$chunk.substr($bytes,33));clearstatcache(true,$u['file']);unchanged($u,'animated_or_unverified');
 $u=sample('truncated.png');$bytes=file_get_contents($u['file']);file_put_contents($u['file'],substr($bytes,0,-12));clearstatcache(true,$u['file']);unchanged($u,'animated_or_unverified');
 $u=sample('large.png','png',1100,1000);$options['zarincode_opt']['webp_max_megapixels']=1;unchanged($u,'pixel_limit');unset($options['zarincode_opt']['webp_max_megapixels']);
 $u=sample('byte-limit.png');$fp=fopen($u['file'],'ab');fwrite($fp,str_repeat('x',1100000));fclose($fp);clearstatcache(true,$u['file']);$options['zarincode_opt']['webp_max_megabytes']=1;unchanged($u,'byte_limit');unset($options['zarincode_opt']['webp_max_megabytes']);
 $u=sample('tiny.png','png',1,1);$options['zarincode_opt']['webp_only_smaller']=true;
 // 1x1 flat PNG may be larger than WebP on some codecs. Force a known 67-byte PNG.
 file_put_contents($u['file'],base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aGn8AAAAASUVORK5CYII='));clearstatcache(true,$u['file']);
 // Independent actual size comparison decides which result is correct.
 $original_size=filesize($u['file']);$out=WebP::convert($u);ok($out['type']==='image/webp'?filesize($out['file'])<$original_size:file_exists($u['file']),'only-smaller invariant');
 $options['zarincode_opt']['webp_only_smaller']=false;
 // A low-quality noisy JPEG is intentionally smaller than a quality-100 WebP.
 $u=sample('not-smaller.jpg','jpg',80,80);$im=imagecreatetruecolor(80,80);mt_srand(123);
 for($y=0;$y<80;$y++)for($x=0;$x<80;$x++)imagesetpixel($im,$x,$y,(mt_rand(0,255)<<16)|(mt_rand(0,255)<<8)|mt_rand(0,255));
 imagejpeg($im,$u['file'],5);clearstatcache(true,$u['file']);$options['zarincode_opt']['webp_only_smaller']=true;$options['zarincode_opt']['webp_quality']=100;unchanged($u,'not_smaller');
 $options['zarincode_opt']['webp_only_smaller']=false;$options['zarincode_opt']['webp_quality']=82;
 $u=sample('heap.png');$previous_memory=ini_get('memory_limit');ini_set('memory_limit',(string)(memory_get_usage(true)+8388608));unchanged($u,'memory_limit');ini_set('memory_limit',$previous_memory);
 if(function_exists('imageavif')&&!class_exists('Imagick')){$u=sample('safe-avif.avif','avif');unchanged($u,'animated_or_unverified');}
 $u=sample('symlink.png');$link=$base.'/2026/link.png';if(symlink($u['file'],$link)){$alias=$u;$alias['file']=$link;ok(WebP::convert($alias)===$alias&&$GLOBALS['reason']==='not_local','symlink not converted');unlink($link);}
 $svg=$base.'/2026/vector.svg';file_put_contents($svg,'<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');$vector=['file'=>$svg,'url'=>'https://example.test/uploads/2026/vector.svg','type'=>'image/svg+xml'];unchanged($vector,'unsupported_input');
 $u=sample('oriented.jpg','jpg',320,180);$mode='rotate';$out=WebP::convert($u);ok(getimagesize($out['file'])[0]===180&&getimagesize($out['file'])[1]===320,'EXIF orientation pipeline');$mode='';
 $u=sample('observer.png');$mode='observer_throw';$out=WebP::convert($u);ok(file_exists($out['file'])&&$out['type']==='image/webp','observer cannot break commit');$mode='';
 $options['zarincode_opt']['webp_quality']=93;ok(apply_filters('wp_editor_set_quality',75,'image/webp')===93,'quality setting consumed');ok(apply_filters('wp_editor_set_quality',75,'image/jpeg')===75,'other quality untouched');
 $options['zarincode_opt']['webp_quality']=200;ok(WebP::settings()['quality']===100,'quality bounded');
 $options['zarincode_opt']['webp_quality']=[];ok(WebP::settings()['quality']===82,'malformed quality');
 $options['zarincode_opt']['webp_only_smaller']=[];ok(WebP::settings()['only_smaller']===true,'malformed smaller policy stays safe');$options['zarincode_opt']['webp_only_smaller']=false;
 $u=sample('disabled.png');$options['zarincode_opt']['feature_webp_uploads']=false;ok(WebP::convert($u)===$u&&file_exists($u['file']),'disabled preserves original');ok(WebP::editor_quality(75,'image/webp')===75,'disabled quality');unset($options['zarincode_opt']['feature_webp_uploads']);
 $u=sample('sideload.png');$out=apply_filters('wp_handle_upload',$u,'sideload');ok($out['type']==='image/webp','sideload hook');
 $outside=tempnam(sys_get_temp_dir(),'zc-outside-');copy($out['file'],$outside);$external=['file'=>$outside,'url'=>'https://example.test/x.png','type'=>'image/png'];ok(WebP::convert($external)===$external&&file_exists($outside),'outside uploads');unlink($outside);
 ok(WebP::convert(['error'=>'already failed'])===['error'=>'already failed'],'upstream upload error');
 $status=WebP::status();ok($status['webp_editor']&&$status['allowed_webp'],'health capabilities');ok(!str_contains(json_encode($status),$base),'no paths in health');
 ok(!Frames::single_frame(__FILE__,'image/png'),'invalid PNG rejected');ok(!Frames::single_frame(__FILE__,'image/gif'),'invalid GIF rejected');
 echo "PASS: $checks assertions with real GD WebP encoding/decoding and upstream WordPress editor classes; WP environment APIs are doubles.\n";
}catch(Throwable $error){$failed=$error;}finally{
 foreach(glob($base.'/2026/*') as $p)unlink($p);rmdir($base.'/2026');rmdir($base);
}

if(isset($failed)){fwrite(STDERR,$failed->getMessage()."\n");exit(1);}
