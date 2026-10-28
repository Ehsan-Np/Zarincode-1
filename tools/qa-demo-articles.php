<?php
/** Real disposable local WordPress integration; run via wp eval-file. */
use Zarincode\Runtime260\Demo\Article_Importer as Articles;
use Zarincode\Runtime260\Demo\Demo_Seeder as Seeder;
if (!defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== 'local') throw new RuntimeException('Disposable local WordPress required.');
require_once ABSPATH.'wp-admin/includes/template.php';
$checks=0;
$expect=static function($condition,$message)use(&$checks){if(!$condition)throw new RuntimeException($message);++$checks;};
$ids=static fn()=>get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_zc_demo_article','meta_value'=>'1']);
$expect(!$ids(),'Start with an empty demo fixture');
$admin=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);wp_set_current_user((int)$admin[0]);
$collision=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Customer article','post_name'=>'modern-web-design-principles','post_content'=>'Do not overwrite']);
// Exercise the actual full-demo entry point, not only the isolated article service.
Seeder::seed_all();
$expect(!Articles::$last_result['errors'],'Full demo has no article/image errors');
$posts=$ids();$expect(count($posts)===71,'Full demo imports 71 marked magazine articles');
$expect(get_post_field('post_content',$collision)==='Do not overwrite','Customer slug collision is preserved');
$quotas=['web-design'=>10,'seo'=>10,'content-production'=>5,'wordpress-tutorials'=>12,'website-security'=>22,'site-performance'=>12];
foreach($quotas as $slug=>$quota){$count=0;foreach($posts as $id)if(has_term($slug,'category',$id))++$count;$expect($count===$quota,'Category quota '.$slug);}
foreach($posts as $id){
 $thumbnail=(int)get_post_thumbnail_id($id);$file=get_attached_file($thumbnail);$metadata=wp_get_attachment_metadata($thumbnail);
 $expect($thumbnail>0&&is_readable($file)&&wp_get_image_mime($file),'Real readable article image');
 $expect(!empty($metadata['width'])&&!empty($metadata['height']),'Image metadata');
 $expect((bool)wp_get_attachment_image_src($thumbnail,'medium'),'Image source');
 foreach($metadata['sizes']??[] as $size)$expect(is_file(dirname($file).'/'.$size['file']),'Real image subsize');
}
$attachments=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids']);
Articles::run();$expect(count($ids())===71,'Repeated import creates no duplicate articles');
$expect(count(get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids']))===count($attachments),'Repeated import creates no duplicate attachments');
$preserved=(int)get_post_thumbnail_id($posts[1]);set_post_thumbnail($posts[0],$preserved);
Articles::run();$expect((int)get_post_thumbnail_id($posts[0])===$preserved,'Valid custom featured image preserved');
$broken=(int)get_post_thumbnail_id($posts[5]);wp_delete_file(get_attached_file($broken));
Articles::run();$expect(!Articles::$last_result['errors'],'Missing image repaired');
foreach($ids() as $id)$expect(is_readable(get_attached_file(get_post_thumbnail_id($id))),'All article images readable after repair');
$settings=['qa_sentinel'=>'keep'];update_option('zarincode_opt',$settings);
$pages=wp_count_posts('page')->publish;
Seeder::instance()->ocdi_after_import_setup(['local_import_file'=>'/fixture/zarincode-articles.xml']);
$expect(get_option('zarincode_opt')===$settings,'Article-only OCDI does not reset settings');
$expect(wp_count_posts('page')->publish===$pages,'Article-only OCDI does not reseed pages');
$expect(count($ids())===71,'OCDI callback is idempotent');
$_POST=[];ob_start();Seeder::instance()->render_admin_page();$ui=ob_get_clean();
$expect(substr_count($ui,'<form')===1 && str_contains($ui,'value="articles"'),'Original importer with article repair scope');
$expect(str_contains($ui,'zc-demo-main-grid') && str_contains($ui,'zc-demo-fullwidth-wrap'),'Original importer layout retained');
$expect(!str_contains($ui,'100% ELEMENTOR READY')&&!str_contains($ui,'Crextio'),'No obsolete marketing description');
echo "PASS: $checks real WordPress demo assertions.\n";
