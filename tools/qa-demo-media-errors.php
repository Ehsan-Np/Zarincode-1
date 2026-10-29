<?php
/** Additional error injection on an existing disposable local WordPress fixture. */
use Zarincode\Runtime260\Demo\Demo_Media as Media;
if (!defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type()!=='local') throw new RuntimeException('Disposable local fixture required.');
$before=get_option('zarincode_opt',[]);$checks=0;
$expect=static function($ok,$message)use(&$checks){if(!$ok)throw new RuntimeException($message);++$checks;};
$admin=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);wp_set_current_user((int)$admin[0]);
$throws=static function(){throw new RuntimeException('QA_MEDIA_EXCEPTION');};
$read_only=static function($directory){$directory['path']=get_template_directory().'/assets/images/demo-post-cover.jpg';$directory['error']='QA_READ_ONLY';return $directory;};
try {
    update_option('zarincode_opt',array_merge($before,['webp_quality'=>84]));Media::reset();
    add_filter('wp_handle_sideload_prefilter',$throws);
    $failed=Media::import('demo-post-cover.jpg');
    $expect(!is_wp_error($failed)&&Media::valid($failed),'Third-party exception becomes bounded actionable error');
    $expect(str_contains(implode(' ',Media::$warnings),'QA_MEDIA_EXCEPTION'),'Exception detail is not discarded');
    remove_filter('wp_handle_sideload_prefilter',$throws);update_option('zarincode_opt',array_merge($before,['webp_quality'=>85]));Media::reset();
    add_filter('upload_dir',$read_only);$failed=Media::import('demo-post-cover.jpg');
    $expect(!is_wp_error($failed)&&Media::valid($failed),'Directory failure has safe bundled fallback');
    $expect((str_contains(implode(' ',Media::$warnings),'QA_READ_ONLY')||str_contains(implode(' ',Media::$warnings),'writable')),'Host error preserved');
    remove_filter('upload_dir',$read_only);Media::reset();
    $invalid=Media::import('../private.jpg');
    $expect(is_wp_error($invalid)&&$invalid->get_error_code()==='invalid_demo_asset','Traversal rejected');
} finally {
    remove_filter('wp_handle_sideload_prefilter',$throws);remove_filter('upload_dir',$read_only);update_option('zarincode_opt',$before);Media::reset();
}
echo "PASS: $checks real WordPress additional error-path assertions.\n";
