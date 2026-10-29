<?php
/** Explicit local demo media import through the SAME WordPress/WebP upload pipeline. */
namespace Zarincode\Runtime260\Demo;
use Zarincode\Runtime260\Media\WebP_Uploads;
defined('ABSPATH') || exit;

final class Demo_Media {
    private static array $cache = [];
    public static array $errors = [];
    public static array $warnings = [];

    public static function reset(): void { self::$cache = []; self::$errors = []; self::$warnings = []; }

    private static function fail(string $name, string $code, string $message) {
        $error = new \WP_Error($code, $message);
        self::$errors[$name] = $name.' ['.$code.']: '.$message;
        return self::$cache[$name] = $error;
    }

    public static function policy(): string {
        return hash('sha256', wp_json_encode([WebP_Uploads::settings(),
            wp_image_editor_supports(['mime_type'=>'image/webp']), in_array('image/webp', get_allowed_mime_types(), true)]));
    }

    public static function valid(int $id): bool {
        if (Bundled_Images::name($id)) return get_post_type($id)==='attachment' && get_post_status($id)!=='trash';
        $file = get_attached_file($id, true);
        return get_post_status($id) !== 'trash' && get_post_type($id)==='attachment'
            && is_string($file) && is_readable($file) && (bool)wp_get_image_mime($file);
    }

    public static function import(string $name) {
        $name = Bundled_Images::canonical($name);
        try { $result = self::import_local($name); }
        catch (\Throwable $error) { $result = self::fail($name, 'demo_media_exception', wp_strip_all_tags($error->getMessage())); }
        try {
            if (!is_wp_error($result)) {
                if (Bundled_Images::name((int)$result)) self::$warnings[$name] ??= '[demo_bundle_reused] رسانه از WebP فقط‌خواندنی همراه قالب نمایش داده می‌شود.';
                return $result;
            }
            if (!Bundled_Images::asset($name)) return $result;
            $fallback = Bundled_Images::register($name);
            if (is_wp_error($fallback)) return self::fail($name,$fallback->get_error_code(),$fallback->get_error_message());
            self::$warnings[$name] = '['.$result->get_error_code().'] '.$result->get_error_message();
            unset(self::$errors[$name]);
            $source=get_template_directory().'/assets/images/'.$name;
            update_post_meta($fallback,'_zc_demo_asset_hash',hash_file('sha256',$source));
            update_post_meta($fallback,'_zc_demo_asset_policy',self::policy());
            update_post_meta($fallback,'_zc_demo_asset_name',$name);
            return self::$cache[$name]=$fallback;
        }
        catch (\Throwable $error) { return self::fail($name, 'demo_media_exception', wp_strip_all_tags($error->getMessage())); }
    }

    private static function import_local(string $name) {
        if (isset(self::$cache[$name])) {
            if (is_wp_error(self::$cache[$name]) || self::valid((int)self::$cache[$name])) return self::$cache[$name];
            unset(self::$cache[$name]);
        }
        // Names come from bundled demo models, never arbitrary paths or remote URLs.
        if ($name !== basename($name) || !preg_match('/\A[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp|gif)\z/i', $name)) {
            return self::fail($name, 'invalid_demo_asset', 'نام تصویر دمو معتبر نیست.');
        }
        $source = get_template_directory().'/assets/images/'.$name;
        if (!is_readable($source)) return self::fail($name, 'demo_asset_missing', 'فایل تصویر در بسته قالب وجود ندارد یا خواندنی نیست؛ قالب را کامل به‌روز کنید.');
        $hash = hash_file('sha256', $source);
        $policy = self::policy();
        $ids = get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids',
            'meta_query'=>[['key'=>'_zc_demo_asset_hash','value'=>$hash],['key'=>'_zc_demo_asset_policy','value'=>$policy]]]);
        foreach ($ids as $id) if (self::valid((int)$id)) return self::$cache[$name] = (int)$id;
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) return self::fail($name, 'demo_upload_directory', wp_strip_all_tags((string)$uploads['error']));
        if (empty($uploads['path']) || !wp_is_writable($uploads['path'])) return self::fail($name, 'demo_upload_not_writable', 'پوشه uploads قابل نوشتن نیست؛ مجوزها و فضای دیسک میزبان را بررسی کنید.');
        // Avoid a broken/open_basedir-restricted system temp directory and cross-volume moves.
        $temporary = wp_tempnam($name, $uploads['path']);
        if (!$temporary) return self::fail($name, 'demo_temp_failed', 'ساخت فایل موقت در uploads انجام نشد؛ فضای دیسک و مجوز پوشه را بررسی کنید.');
        try {
            if (!copy($source, $temporary)) return self::fail($name, 'demo_copy_failed', 'کپی فایل تصویر به uploads انجام نشد.');
            $uploaded = [];
            $capture = static function($value,$context) use (&$uploaded) {
                if ($context==='sideload' && is_array($value)) $uploaded=$value;
                return $value;
            };
            add_filter('wp_handle_upload',$capture,PHP_INT_MAX,2);
            try { $id = media_handle_sideload(['name'=>$name,'tmp_name'=>$temporary,'error'=>0,'size'=>filesize($temporary)], 0, 'تصویر نمونه دمو'); }
            finally { remove_filter('wp_handle_upload',$capture,PHP_INT_MAX); }
            if (!is_wp_error($id) && !self::valid((int)$id)) self::repair_path((int)$id,$uploaded);

            if (is_wp_error($id)) return self::fail($name, $id->get_error_code(), wp_strip_all_tags($id->get_error_message()));
            if (!self::valid((int)$id)) {
                // Do not delete a record with an untrusted path: filters may point it at customer/theme files.
                if ((int)$id > 0) update_post_meta((int)$id,'_zc_demo_invalid_path',true);
                return self::fail($name, 'demo_invalid_attachment', 'مسیر محلی یا رکورد رسانه پس از sideload معتبر نبود؛ از WebP همراه قالب استفاده می‌شود.');
            }
            update_post_meta($id, '_zc_demo_asset_hash', $hash);
            update_post_meta($id, '_zc_demo_asset_policy', $policy);
            update_post_meta($id, '_zc_demo_asset_name', $name);
            update_post_meta($id, '_wp_attachment_image_alt', 'تصویر نمونه دمو');
            return self::$cache[$name] = (int)$id;
        } catch (\Throwable $error) {
            return self::fail($name, 'demo_media_exception', wp_strip_all_tags($error->getMessage()));
        } finally { if (is_file($temporary)) wp_delete_file($temporary); }
    }

    /** Recover only the known new upload, never an arbitrary path supplied by a renderer. */
    private static function repair_path(int $id,array $uploaded): void {
        if (get_post_type($id)!=='attachment') return;
        $uploads=wp_get_upload_dir();$base=realpath($uploads['basedir']??'');
        if (!$base || !empty($uploads['error'])) return;
        $meta=wp_get_attachment_metadata($id);$candidates=[];
        if (!empty($uploaded['file'])) $candidates[]=$uploaded['file'];
        foreach($candidates as $candidate){
            $file=realpath($candidate);$prefix=trailingslashit(wp_normalize_path($base));
            if (!$file || !is_readable($file) || strpos(wp_normalize_path($file),$prefix)!==0) continue;
            $mime=wp_get_image_mime($file);if (!$mime) continue;
            $relative=substr(wp_normalize_path($file),strlen($prefix));
            // Bypass update_attached_file filters only for our own captured, verified upload.
            update_post_meta($id,'_wp_attached_file',$relative);
            wp_update_post(['ID'=>$id,'post_mime_type'=>$mime]);
            if (is_array($meta)) { $meta['file']=$relative;wp_update_attachment_metadata($id,$meta); }
            return;
        }
    }

    /** Preserve customer images. Refresh only known demo assets when policy changes. */
    public static function assign(int $post_id, string $name): bool {
        $current = (int)get_post_meta($post_id, '_thumbnail_id', true);
        if (!$current) $current = (int)get_post_thumbnail_id($post_id);
        if ($current && self::valid($current)) {
            $demo = get_post_meta($current, '_zc_demo_asset_hash', true) || get_post_meta($current, '_zc_demo_cover_hash', true);
            foreach (['post','project','service','product'] as $asset) {
                if ((int)get_option('zarincode_demo_cover_'.$asset) === $current) $demo = true;
            }
            if (!$demo || get_post_meta($current, '_zc_demo_asset_policy', true) === self::policy()) return true;
            $owned_name = get_post_meta($current, '_zc_demo_asset_name', true);
            if (is_string($owned_name) && $owned_name !== '') $name = $owned_name;
        }
        $id = self::import($name);
        if (is_wp_error($id)) return false;
        // set_post_thumbnail() depends on rendered HTML and can return false under
        // image/lazy/offload filters, even for a valid file. Verify the stored relationship,
        // not an HTML renderer's return value or update_post_meta's "unchanged" false.
        if ((int)get_post_meta($post_id, '_thumbnail_id', true) !== $id) update_post_meta($post_id, '_thumbnail_id', $id);
        if ((int)get_post_meta($post_id, '_thumbnail_id', true) === $id) return true;
        self::$errors[$name] = $name.' [demo_thumbnail_write_failed]: ذخیره ارتباط تصویر شاخص در پایگاه داده انجام نشد.';
        return false;
    }

    /** Rewrite only bundled local demo URLs during an EXPLICIT import. */
    public static function rewrite($value) {
        if (is_array($value)) {
            foreach ($value as &$child) $child = self::rewrite($child);
            unset($child);
            if (isset($value['id'], $value['url']) && is_scalar($value['id'])) {
                $name = get_post_meta(absint($value['id']), '_zc_demo_asset_name', true);
                if (is_string($name) && $name !== '') {
                    $id = self::import($name);
                    if (!is_wp_error($id)) { $value['id'] = $id; $value['url'] = wp_get_attachment_url($id); }
                }
            }
            if (isset($value['url']) && is_string($value['url'])) {
                foreach (self::$cache as $id) if (!is_wp_error($id) && wp_get_attachment_url($id) === $value['url']) { $value['id'] = $id; break; }
            }
            return $value;
        }
        if (!is_string($value)) return $value;
        $base = preg_quote(get_template_directory_uri(), '~');
        return preg_replace_callback('~(?:'.$base.'|\{\{THEME\}\})/assets/images/([a-zA-Z0-9_-]+\.(?:jpe?g|png|webp|gif))(?![a-zA-Z0-9_.-])~i', static function($match) {
            $id = self::import($match[1]);
            return is_wp_error($id) ? str_replace('{{THEME}}', get_template_directory_uri(), $match[0]) : wp_get_attachment_url($id);
        }, $value);
    }
    /** Repair media references only, never regenerate customer-edited page layouts/options. */
    public static function repair_seeded(): array {
        self::reset();
        $ids = get_posts(['post_type'=>['page','post','zc_hf_template','elementor_library','zc_service','zc_project','product'],
            'post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_query'=>['relation'=>'OR',
                ['key'=>'_zarincode_seeded','value'=>'1'],['key'=>'_zc_demo_article','value'=>'1']]]);
        $updated = 0;
        foreach ($ids as $id) {
            $raw = get_post_meta($id, '_elementor_data', true);
            if (is_string($raw) && $raw !== '') {
                $tree = json_decode($raw, true);
                if (is_array($tree)) {
                    $new = self::rewrite($tree);
                    if ($new !== $tree) {
                        $encoded = wp_json_encode($new);
                        update_post_meta($id, '_elementor_data', wp_slash($encoded));
                        if (get_post_meta($id, '_elementor_data', true) === $encoded) {
                            delete_post_meta($id, '_elementor_css');
                            ++$updated;
                        } else self::$errors['record-'.$id] = '[demo_metadata_write_failed]: ذخیره نشانی تصاویر المنتوری انجام نشد.';
                    }
                } else self::$errors['record-'.$id] = '[demo_invalid_json]: اطلاعات المنتوری یک رکورد دمو معتبر نیست؛ بدون تغییر باقی ماند.';
            }
            $content = get_post_field('post_content', $id);
            $new_content = self::rewrite($content);
            if ($new_content !== $content) {
                $result = wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$new_content]), true);
                if (is_wp_error($result)) self::$errors['record-'.$id] = $result->get_error_message();
                else ++$updated;
            }
            $kind = ['post'=>'post','zc_service'=>'service','zc_project'=>'project','product'=>'product'][get_post_type($id)] ?? '';
            if ($kind) self::assign((int)$id, 'demo-'.$kind.'-cover.jpg');
        }
        return ['updated'=>$updated,'errors'=>array_values(self::$errors),'warnings'=>self::$warnings];
    }

}
