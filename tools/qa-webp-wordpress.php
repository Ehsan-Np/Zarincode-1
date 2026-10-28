<?php
/** Optional REAL WordPress integration, not executed by standalone tests.
 * wp --path=/tmp/disposable-wp eval-file tools/qa-webp-wordpress.php
 * Requires an activated current companion, GD/WebP and a disposable local WP.
 */
if ( ! defined('ABSPATH') || ! defined('ZARINCODE_DISPOSABLE_QA') || ! ZARINCODE_DISPOSABLE_QA
    || wp_get_environment_type() !== 'local' ) { throw new RuntimeException('Disposable local WordPress required. Never run on a live site.'); }
if ( ! class_exists('\Zarincode\Runtime260\Media\WebP_Uploads') || ! function_exists('imagewebp') ) { throw new RuntimeException('Activate current companion and install GD/WebP.'); }
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$previous_user=get_current_user_id();$before=get_option('zarincode_opt',null);$attachment=0;$temporary='';$uploaded='';$checks=0;
$observe=static function($value)use(&$uploaded){if(is_array($value)&&isset($value['file']))$uploaded=$value['file'];return $value;};
add_filter('wp_handle_upload',$observe,999);
$expect=static function($value,$message)use(&$checks){if(!$value)throw new RuntimeException($message);++$checks;};
try {
    $administrators=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
    if(!$administrators)throw new RuntimeException('A fixture administrator is required.');
    wp_set_current_user((int)$administrators[0]);
    update_option('zarincode_opt',array_merge(is_array($before)?$before:[],['feature_webp_uploads'=>true,'webp_quality'=>82,'webp_only_smaller'=>false]));
    $temporary=wp_tempnam('zc-webp-integration.png');
    $image=imagecreatetruecolor(1600,1000);imagefill($image,0,0,imagecolorallocate($image,40,110,180));imagepng($image,$temporary);unset($image);
    $attachment=media_handle_sideload(['name'=>'zc-webp-'.wp_generate_uuid4().'.png','tmp_name'=>$temporary,'type'=>'image/png','error'=>0,'size'=>filesize($temporary)],0,'WebP integration fixture');
    if(is_wp_error($attachment))throw new RuntimeException($attachment->get_error_message());
    $expect(get_post_mime_type($attachment)==='image/webp','Attachment MIME');
    $file=get_attached_file($attachment);$metadata=wp_get_attachment_metadata($attachment);
    $expect(str_ends_with($file,'.webp')&&wp_get_image_mime($file)==='image/webp','Primary path and bytes');
    $expect(str_ends_with(wp_get_attachment_url($attachment),'.webp'),'Attachment URL');
    $expect(str_ends_with($metadata['file'],'.webp'),'Metadata primary filename');
    $expect(!empty($metadata['sizes']),'Real WordPress subsizes generated');
    foreach($metadata['sizes'] as $size){$expect($size['mime-type']==='image/webp','Subsize MIME');$expect(wp_get_image_mime(dirname($file).'/'.$size['file'])==='image/webp','Subsize actual bytes');}
    $expect((bool)wp_get_attachment_image_srcset($attachment,'large'),'Responsive srcset');
    $expect(!file_exists(substr($file,0,-5).'.png'),'Successful source replacement');
    wp_delete_attachment($attachment,true);$attachment=0;$uploaded='';
    // Exercise disabled conversion through the actual WordPress upload pipeline.
    update_option('zarincode_opt',array_merge(is_array($before)?$before:[],['feature_webp_uploads'=>false]));
    $temporary=wp_tempnam('zc-disabled.png');$image=imagecreatetruecolor(128,128);imagepng($image,$temporary);unset($image);
    $attachment=media_handle_sideload(['name'=>'zc-disabled-'.wp_generate_uuid4().'.png','tmp_name'=>$temporary,'type'=>'image/png','error'=>0,'size'=>filesize($temporary)],0);
    if(is_wp_error($attachment))throw new RuntimeException($attachment->get_error_message());
    $expect(get_post_mime_type($attachment)==='image/png','Disabled module preserves MIME');
    $expect(wp_get_image_mime(get_attached_file($attachment))==='image/png','Disabled module preserves actual format');
    wp_delete_attachment($attachment,true);$attachment=0;$uploaded='';
    update_option('zarincode_opt',array_merge(is_array($before)?$before:[],['feature_webp_uploads'=>true]));
    $temporary=wp_tempnam('zc-existing.webp');$image=imagecreatetruecolor(128,128);imagewebp($image,$temporary,82);unset($image);$digest=hash_file('sha256',$temporary);
    $attachment=media_handle_sideload(['name'=>'zc-existing-'.wp_generate_uuid4().'.webp','tmp_name'=>$temporary,'type'=>'image/webp','error'=>0,'size'=>filesize($temporary)],0);
    if(is_wp_error($attachment))throw new RuntimeException($attachment->get_error_message());
    $expect(get_post_mime_type($attachment)==='image/webp','Existing WebP MIME');
    $expect(hash_file('sha256',get_attached_file($attachment))===$digest,'Existing WebP is not re-encoded');
    echo "PASS: $checks real WordPress assertions: media_handle_sideload, attachment, subsizes, metadata, URL, srcset, disable control and unchanged existing WebP.\n";
} finally {
    if(is_int($attachment)&&$attachment>0)wp_delete_attachment($attachment,true);
    elseif($uploaded&&is_file($uploaded))wp_delete_file($uploaded);
    if($temporary&&is_file($temporary))wp_delete_file($temporary);
    if($before===null)delete_option('zarincode_opt');else update_option('zarincode_opt',$before);
    wp_set_current_user($previous_user);remove_filter('wp_handle_upload',$observe,999);
}
