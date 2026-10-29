<?php
/** Destructive tests: ONLY a disposable, local WordPress with current theme/core. */
use Zarincode\Runtime260\Demo\Demo_Media as Media;
use Zarincode\Runtime260\Demo\Bundled_Images as Bundle;
use Zarincode\Runtime260\Demo\Demo_Seeder as Seeder;
if(!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA||wp_get_environment_type()!=='local')throw new RuntimeException('Disposable local fixture only.');
$admin=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);wp_set_current_user((int)$admin[0]);
$checks=0;$expect=static function($ok,$why)use(&$checks){if(!$ok)throw new RuntimeException($why);++$checks;};
$before=get_option('zarincode_opt',[]);$quality=70;
$fresh=static function()use(&$quality){$o=get_option('zarincode_opt',[]);$o['webp_quality']=$quality++;update_option('zarincode_opt',$o);Media::reset();};
$bad_file=static fn()=>'/nonexistent/filtered-path.webp';
$no_editors=static fn()=>[];
$readonly=static function($u){$u['path']=get_template_directory().'/assets/images/demo-post-cover.webp';return $u;};
$no_webp=static function($m){return array_filter($m,static fn($v)=>$v!=='image/webp');};
$preflight=static function(){throw new RuntimeException('QA_PREFLIGHT_EXCEPTION');};
$deny=static function($f){$f['error']='QA_UPLOAD_DENIED';return $f;};
try {
    $expect(count(Bundle::manifest())===16,'16 pre-encoded assets');
    foreach(Bundle::manifest() as $name=>$asset){
        $expect((bool)Bundle::asset($name),'Verified bundled bytes '.$name);
        $id=Media::import($asset['legacy']);$expect(!is_wp_error($id)&&Media::valid($id),'Legacy alias imports '.$name);
        $expect(get_post_mime_type($id)==='image/webp','Actual media MIME '.$name);
        $expect(str_contains(wp_get_attachment_image($id,'large'),'.webp'),'Real rendered WebP '.$name);
    }
    $fresh();add_filter('get_attached_file',$bad_file);
    $id=Media::import('demo-post-cover.jpg');$expect(!is_wp_error($id)&&Media::valid($id),'Filtered display path does not falsely reject local upload');
    $expect(is_readable(get_attached_file($id,true)),'Canonical file is readable');remove_filter('get_attached_file',$bad_file);
    $fresh();add_filter('update_attached_file',$bad_file);
    $id=Media::import('demo-project-cover.jpg');$expect(!is_wp_error($id)&&Media::valid($id),'Broken stored path repaired from captured upload');
    $expect(is_readable(get_attached_file($id,true)),'Repaired metadata path is readable');remove_filter('update_attached_file',$bad_file);
    $fresh();add_filter('wp_image_editors',$no_editors);
    $id=Media::import('demo-service-cover.jpg');$expect(!is_wp_error($id)&&Media::valid($id),'No server editor/encoder required');remove_filter('wp_image_editors',$no_editors);
    $fresh();add_filter('upload_dir',$preflight);
    $id=Media::import('ehsan-portrait.webp');$expect(!is_wp_error($id)&&Bundle::name($id),'Preflight exception also falls back safely: '.(is_wp_error($id)?$id->get_error_code().': '.$id->get_error_message():'id '.$id));
    $expect(str_contains(implode(' ',Media::$warnings),'QA_PREFLIGHT_EXCEPTION'),'Preflight original diagnostic remains visible');
    remove_filter('upload_dir',$preflight);
    $fresh();add_filter('upload_dir',$readonly);
    $id=Media::import('demo-product-cover.jpg');$expect(!is_wp_error($id)&&Bundle::name($id),'Read-only uploads falls back to bundled media');
    $expect(!get_post_meta($id,'_wp_attached_file',true),'No theme file registered as deletable attached file');
    $expect((bool)wp_get_attachment_image_src($id,'thumbnail'),'Fallback image source works');
    $expect(str_contains(wp_get_attachment_image($id,'large'),'demo-product-cover.webp'),'Fallback markup uses bundled WebP URL');
    $asset=Bundle::asset('demo-product-cover.webp');$path=get_template_directory().'/assets/images/'.$asset['name'];$hash=hash_file('sha256',$path);
    wp_delete_attachment($id,true);$expect(is_file($path)&&hash_file('sha256',$path)===$hash,'Deleting bundled media never deletes theme asset');
    remove_filter('upload_dir',$readonly);
    $fresh();add_filter('upload_mimes',$no_webp);
    $id=Media::import('demo-client-1.png');$expect(!is_wp_error($id)&&Bundle::name($id),'MIME upload restriction does not require weakening upload policy');
    remove_filter('upload_mimes',$no_webp);
    $fresh();add_filter('wp_handle_sideload_prefilter',$deny);add_filter('wp_image_editors',$no_editors);
    Seeder::seed_all();$expect(empty(\Zarincode\Runtime260\Demo\Article_Importer::$last_result['errors']),'Full demo succeeds even when every sideload is denied');
    $posts=get_posts(['post_type'=>'post','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_zc_demo_article','meta_value'=>'1']);
    $expect(count($posts)===71,'71 articles with bundled mode');
    foreach($posts as $post){$thumb=(int)get_post_thumbnail_id($post);$expect($thumb>0&&Media::valid($thumb),'Valid article thumbnail');$expect((bool)get_the_post_thumbnail_url($post,'large'),'Native thumbnail URL works');$expect(str_contains(get_the_post_thumbnail($post,'large'),'.webp'),'Native thumbnail HTML works');}
    $count=count(get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids']));
    \Zarincode\Runtime260\Demo\Article_Importer::run();
    $expect(count(get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids']))===$count,'Repeat bundled import creates no duplicate media');
    $repair=Media::repair_seeded();$expect(!$repair['errors'],'Repair seeded images works in bundled mode');
} finally {
    remove_filter('upload_dir',$preflight);remove_filter('get_attached_file',$bad_file);remove_filter('update_attached_file',$bad_file);remove_filter('wp_image_editors',$no_editors);remove_filter('upload_dir',$readonly);remove_filter('upload_mimes',$no_webp);remove_filter('wp_handle_sideload_prefilter',$deny);update_option('zarincode_opt',$before);Media::reset();
}
echo "PASS: $checks real WordPress prebuilt WebP/fallback assertions.\n";
