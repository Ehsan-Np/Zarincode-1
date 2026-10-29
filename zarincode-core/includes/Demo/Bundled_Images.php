<?php
/** Read-only media records for verified, pre-encoded theme assets. No upload/editor required. */
namespace Zarincode\Runtime260\Demo;
defined('ABSPATH') || exit;
final class Bundled_Images {
    public static function boot(): void {
        add_filter('wp_get_attachment_url', [self::class,'url'], PHP_INT_MAX, 2);
        add_filter('image_downsize', [self::class,'downsize'], PHP_INT_MAX, 3);
        add_filter('wp_calculate_image_srcset_meta', static function($meta,$size,$src,$id){return self::name((int)$id) ? [] : $meta;}, PHP_INT_MAX, 4);
    }
    public static function manifest(): array {
        static $cache=[];$root=get_template_directory();
        if(!isset($cache[$root])){
            $file=$root.'/demo-data/media-manifest.json';
            $cache[$root]=is_readable($file)?(json_decode(file_get_contents($file),true)?:[]):[];
        }
        return $cache[$root];
    }
    public static function canonical(string $name): string {
        foreach(self::manifest() as $webp=>$asset)if($name===$webp||$name===($asset['legacy']??''))return $webp;
        return $name;
    }
    public static function asset(string $name): array {
        $name=self::canonical($name);$data=self::manifest()[$name]??[];
        if(!$data||$name!==basename($name)||!preg_match('/\A[a-zA-Z0-9_-]+\.webp\z/',$name))return [];
        $file=get_template_directory().'/assets/images/'.$name;
        if(!is_readable($file)||hash_file('sha256',$file)!==($data['sha256']??''))return [];
        return $data+['name'=>$name,'url'=>get_template_directory_uri().'/assets/images/'.$name];
    }
    public static function name(int $id): string {
        $name=get_post_meta($id,'_zc_bundled_image',true);
        return is_string($name)&&$name!==''&&self::asset($name)?$name:'';
    }
    public static function url($url,$id) {
        $name=self::name((int)$id);return $name?self::asset($name)['url']:$url;
    }
    public static function downsize($result,$id,$size) {
        $name=self::name((int)$id);if(!$name)return $result;
        $asset=self::asset($name);return [$asset['url'],(int)$asset['width'],(int)$asset['height'],false];
    }
    public static function register(string $name) {
        $asset=self::asset($name);if(!$asset)return new \WP_Error('demo_bundle_invalid','فایل WebP همراه قالب موجود یا سالم نیست؛ قالب را کامل به‌روز کنید.');
        $ids=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'fields'=>'ids','meta_key'=>'_zc_bundled_image','meta_value'=>$asset['name']]);
        if($ids)return (int)$ids[0];
        $id=wp_insert_attachment(['post_mime_type'=>'image/webp','post_title'=>'تصویر همراه قالب — '.$asset['name'],
            'post_content'=>'تصویر فقط‌خواندنی همراه قالب؛ برای ویرایش یک تصویر دلخواه آپلود کنید.',
            'post_status'=>'inherit','guid'=>$asset['url'],'meta_input'=>['_zc_bundled_image'=>$asset['name'],'_wp_attachment_image_alt'=>'تصویر نمونه دمو']], false, 0, true);
        if(is_wp_error($id)||!$id)return is_wp_error($id)?$id:new \WP_Error('demo_bundle_db_failed','ثبت رسانه همراه قالب در پایگاه داده انجام نشد.');
        // NEVER point _wp_attached_file at theme files: deleting media must not delete theme assets.
        wp_update_attachment_metadata($id,['width'=>$asset['width'],'height'=>$asset['height'],'file'=>'','sizes'=>[],'filesize'=>$asset['bytes']]);
        return (int)$id;
    }
}
