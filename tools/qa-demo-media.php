<?php
/** Run after qa-demo-articles.php in the SAME disposable local WP fixture. */
use Zarincode\Runtime260\Demo\Demo_Media as Media;
use Zarincode\Runtime260\Demo\Article_Importer as Articles;
use Zarincode\Runtime260\Media\Lazy_Images as Lazy;
if (!defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type()!=='local') throw new RuntimeException('Disposable local WordPress required.');
$count=0;$assert=static function($ok,$why)use(&$count){if(!$ok)throw new RuntimeException($why);++$count;};
$before=get_option('zarincode_opt',[]);
$reject=static function($file){$file['error']='QA_MEDIA_DENIED';return $file;};
try {
    update_option('zarincode_opt',array_merge($before,['webp_quality'=>83,'webp_only_smaller'=>false]));
    add_filter('wp_handle_sideload_prefilter',$reject);
    $failed=Articles::run();
    $assert($failed['posts']===71&&$failed['images']===71,'Failure does not delete or hide imported posts');
    $assert(!$failed['errors']&&count(Media::$warnings)===4,'Four shared asset failures, not 71 repetitive errors');
    $assert(str_contains(implode(' ',Media::$warnings),'QA_MEDIA_DENIED'),'Actual WordPress upload error is visible');
    remove_filter('wp_handle_sideload_prefilter',$reject);
    $fixed=Articles::run();$assert(!$fixed['errors']&&$fixed['images']===71,'Retry repairs all images after host upload error');
    $assert(get_option('zarincode_opt')['webp_quality']===83,'Importer does not silently override WebP configuration');
    $configured=get_option('zarincode_opt');\Zarincode\Runtime260\Demo\Demo_Seeder::seed_redux_options();
    $assert(get_option('zarincode_opt')['webp_quality']===83 && get_option('zarincode_opt')['webp_only_smaller']===false,'Full-demo settings preserve owner WebP policy');
    // Every shipped raster asset exercises the same pipeline, including PNG logos and portraits.
    foreach(glob(get_template_directory().'/assets/images/*') as $file){
        $name=basename($file);$source_hash=hash_file('sha256',$file);$id=Media::import($name);
        $assert(!is_wp_error($id)&&Media::valid($id),'Local asset import '.$name);
        $assert(get_post_mime_type($id)==='image/webp'&&(\Zarincode\Runtime260\Demo\Bundled_Images::name($id)||wp_get_image_mime(get_attached_file($id,true))==='image/webp'),'Real WebP bytes and MIME '.$name);
        $assert($source_hash===hash_file('sha256',$file),'Bundled source is untouched '.$name);
        $url=wp_get_attachment_url($id);
        $rewritten=Media::rewrite(['image'=>['url'=>get_template_directory_uri().'/assets/images/'.$name,'id'=>0], 'html'=>'<img src="{{THEME}}/assets/images/'.$name.'">']);
        $assert($rewritten['image']['id']===$id&&$rewritten['image']['url']===$url,'Elementor URL and ID '.$name);
        $assert(str_contains($rewritten['html'],$url),'Raw HTML URL '.$name);
        $assert(Media::import($name)===$id,'No duplicate media '.$name);
    }
    $records=get_posts(['post_type'=>['page','zc_hf_template'],'post_status'=>'any','numberposts'=>-1,'meta_key'=>'_zarincode_seeded','meta_value'=>'1']);
    foreach($records as $record){
        $tree=get_post_meta($record->ID,'_elementor_data',true);
        $assert(!str_contains($tree,get_template_directory_uri().'/assets/images/'),'Imported Elementor tree has media URLs, not raw demo rasters');
    }
    // Legacy pre-hash demo thumbnails are recognized by their original option ownership.
    $legacy_post=get_posts(['post_type'=>'post','numberposts'=>1,'fields'=>'ids','meta_key'=>'_zc_demo_article','meta_value'=>'1'])[0];
    $legacy_id=(int)get_post_thumbnail_id($legacy_post);
    delete_post_meta($legacy_id,'_zc_demo_asset_hash');delete_post_meta($legacy_id,'_zc_demo_asset_policy');
    update_option('zarincode_demo_cover_post',$legacy_id);Media::reset();
    $assert(Media::assign($legacy_post,'demo-post-cover.jpg'),'Legacy option-owned image migrates');
    $assert(get_post_meta(get_post_thumbnail_id($legacy_post),'_zc_demo_asset_policy',true)===Media::policy(),'Legacy ownership does not block WebP policy upgrade');
    $deny_webp=static function($mimes){return array_filter($mimes,static fn($mime)=>$mime!=='image/webp');};
    add_filter('upload_mimes',$deny_webp);Media::reset();$fallback=Media::import('demo-client-1.png');
    $assert(!is_wp_error($fallback)&&get_post_mime_type($fallback)==='image/webp','Host without allowed upload WebP uses verified bundled WebP');
    remove_filter('upload_mimes',$deny_webp);Media::reset();$retry=Media::import('demo-client-1.png');
    $assert(!is_wp_error($retry)&&get_post_mime_type($retry)==='image/webp','Restored host capability reuses valid WebP policy');
    $target=get_posts(['post_type'=>'page','numberposts'=>1,'fields'=>'ids','meta_key'=>'_zarincode_seeded','meta_value'=>'1'])[0];
    $raw=wp_json_encode([['id'=>'qa-preserve','settings'=>['image'=>['id'=>0,'url'=>get_template_directory_uri().'/assets/images/demo-client-1.png']]]]);
    update_post_meta($target,'_elementor_data',wp_slash($raw));
    $customer=wp_insert_post(['post_type'=>'page','post_title'=>'Unmarked customer page','post_content'=>'Customer layout','post_status'=>'publish']);
    update_post_meta($customer,'_elementor_data',wp_slash($raw));
    $settings_before=get_option('zarincode_opt');$repair=Media::repair_seeded();$tree=json_decode(get_post_meta($target,'_elementor_data',true),true);
    $assert(!$repair['errors']&&$tree[0]['id']==='qa-preserve'&&$tree[0]['settings']['image']['id']>0,'Image repair retains layout and maps media');
    $assert(get_post_meta($customer,'_elementor_data',true)===$raw,'Unmarked customer layout untouched');
    $assert(get_option('zarincode_opt')===$settings_before,'Image-only repair does not reset theme settings');
    $html='<picture><source srcset="large.webp 2x"><img src="small.webp" srcset="small.webp 1x,large.webp 2x" sizes="100vw" width="640" height="360" alt="a &amp; b"></picture><img src="hero.webp" fetchpriority="high" loading="lazy"><img data-zc-eager src="logo.webp"><script>const x="<img src=fake>";</script>';
    $out=Lazy::html($html);$p=new WP_HTML_Tag_Processor($out);$p->next_tag('IMG');
    $assert($p->get_attribute('loading')==='lazy'&&$p->get_attribute('decoding')==='async','Native lazy and async');
    $assert($p->get_attribute('srcset')==='small.webp 1x,large.webp 2x'&&$p->get_attribute('width')==='640','Responsive sources and dimensions preserved');
    $p->next_tag('IMG');$assert($p->get_attribute('loading')==='eager'&&$p->get_attribute('fetchpriority')==='high','High-priority LCP is not lazy');
    $p->next_tag('IMG');$assert($p->get_attribute('loading')==='eager','Explicit critical logo remains eager');
    $assert(str_contains($out,'const x="<img src=fake>";'),'Script strings untouched');
    $assert(Lazy::html($out)===$out,'HTML transform is idempotent');
    update_option('zarincode_opt',array_merge(get_option('zarincode_opt'),['lazy_images'=>false]));
    $assert(Lazy::html($html)===$html,'Lazy switch really disables HTML changes');
    $assert(Lazy::attributes(['src'=>'a.webp'])===['src'=>'a.webp'],'Lazy switch disables attribute changes');
} finally {remove_filter('wp_handle_sideload_prefilter',$reject);update_option('zarincode_opt',$before);}
echo "PASS: $count real WordPress media/WebP/lazy assertions.\n";
