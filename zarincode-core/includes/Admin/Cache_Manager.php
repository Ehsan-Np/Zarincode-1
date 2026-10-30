<?php
/** Explicit, site-scoped cache maintenance. No recursive file deletion or session/transient sweep. */
namespace Zarincode\Runtime260\Admin;
defined('ABSPATH') || exit;

final class Cache_Manager {
    private const LOCK = 'zarincode_cache_purge_lock';
    public static function boot(): void {
        add_action('admin_menu', [self::class,'menu']);
        add_action('admin_bar_menu', [self::class,'toolbar'], 100);
        add_action('admin_post_zarincode_purge_cache', [self::class,'handle']);
    }
    public static function url(): string { return admin_url('tools.php?page=zarincode-cache'); }
    public static function menu(): void {
        add_management_page('پاک‌سازی کش سایت','کش سایت زرین‌کد','manage_options','zarincode-cache',[self::class,'page']);
    }
    public static function toolbar($bar): void {
        if (!current_user_can('manage_options')) return;
        // GET opens a confirmation page. A navigation or crawler can never purge caches.
        $bar->add_node(['id'=>'zarincode-cache','title'=>'پاک‌سازی کش سایت','href'=>self::url(),'meta'=>['title'=>'مدیریت و پاک‌سازی کش سایت زرین‌کد']]);
    }
    private static function result_key(): string { return 'zc_cache_report_'.get_current_user_id(); }
    public static function handle(): void {
        if (!current_user_can('manage_options')) wp_die('اجازهٔ پاک‌سازی کش را ندارید.','',['response'=>403]);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_die('این عملیات فقط با درخواست POST مجاز است.','',['response'=>405]);
        check_admin_referer('zarincode_purge_cache');
        $report=self::purge();
        if (is_wp_error($report)) $report=['time'=>time(),'steps'=>[['name'=>'عملیات پاک‌سازی','status'=>'failed','detail'=>$report->get_error_message()]]];
        set_transient(self::result_key(),$report,5*MINUTE_IN_SECONDS);
        wp_safe_redirect(self::url());exit;
    }
    /** Atomic compare-and-delete: an expired request must never unlock a newer request. */
    private static function unlock(string $value): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",self::LOCK,$value));
        wp_cache_delete(self::LOCK,'options');
    }
    public static function purge() {
        if (!current_user_can('manage_options')) return new \WP_Error('cache_forbidden','اجازهٔ پاک‌سازی کش را ندارید.');
        $old=get_option(self::LOCK,'');$decoded=is_string($old)?json_decode($old,true):null;
        if (is_array($decoded) && (int)($decoded['time']??0)<time()-300) self::unlock($old);
        $lock=wp_json_encode(['time'=>time(),'token'=>wp_generate_uuid4()]);
        if (!add_option(self::LOCK,$lock,'',false)) return new \WP_Error('cache_busy','پاک‌سازی دیگری در حال اجراست؛ پس از پایان آن یا حداکثر پنج دقیقه دوباره تلاش کنید.');
        $report=['time'=>time(),'steps'=>[]];
        $step=static function(string $name, callable $callback, string $success='requested') use (&$report): void {
            try {
                $result=$callback();
                $failed=$result===false || is_wp_error($result);
                $report['steps'][]=['name'=>$name,'status'=>$failed?'failed':$success,'detail'=>$failed?'سرویس خطا گزارش کرد؛ گزارش همان سرویس را بررسی کنید.':''];
            } catch (\Throwable $e) {
                // Third-party messages can contain secrets. Do not expose their exception text.
                $report['steps'][]=['name'=>$name,'status'=>'failed','detail'=>'سرویس هنگام پاک‌سازی با خطا روبه‌رو شد؛ سایر مراحل ادامه یافتند.'];
            }
        };
        try {
            // Rotate only ZarinCode's PWA cache name. Existing clients update on their next worker update.
            $step('نسخهٔ کش آفلاین زرین‌کد',static function(){return update_option('zarincode_cache_epoch',wp_generate_uuid4(),false);},'done');
            if (class_exists('Elementor\\Plugin')) {
                $step('فایل‌های CSS تولیدشدهٔ المنتور',static function(){
                    $plugin=\Elementor\Plugin::instance();
                    if (!isset($plugin->files_manager) || !is_callable([$plugin->files_manager,'clear_cache'])) return false;
                    return $plugin->files_manager->clear_cache();
                });
            }
            $multisite=is_multisite();
            // These APIs may purge an entire installation: do not invoke them on multisite.
            $adapters=[
                ['Autoptimize',is_callable(['autoptimizeCache','clearall']),static fn()=>\autoptimizeCache::clearall()],
                ['WP Rocket',function_exists('rocket_clean_domain'),static fn()=>rocket_clean_domain()],
                ['W3 Total Cache',function_exists('w3tc_flush_all'),static fn()=>w3tc_flush_all()],
                ['LiteSpeed Cache',has_action('litespeed_purge_all')!==false,static function(){do_action('litespeed_purge_all');}],
                ['WP Fastest Cache',has_action('wpfc_clear_all_cache')!==false,static function(){do_action('wpfc_clear_all_cache');}],
                ['SiteGround Optimizer',function_exists('sg_cachepress_purge_cache'),static fn()=>sg_cachepress_purge_cache()],
                ['Cache Enabler',is_callable(['Cache_Enabler','clear_total_cache']),static fn()=>\Cache_Enabler::clear_total_cache()],
            ];
            foreach($adapters as [$name,$active,$callback]) {
                if (!$active) continue;
                if ($multisite) $report['steps'][]=['name'=>$name,'status'=>'skipped','detail'=>'در چندسایتی، API با دامنهٔ نامشخص اجرا نمی‌شود تا کش سایت‌های دیگر حذف نشود.'];
                else $step($name,$callback);
            }
            if (function_exists('wp_cache_clear_cache')) $step('WP Super Cache',static fn()=>wp_cache_clear_cache(get_current_blog_id()));
            if ($multisite) $report['steps'][]=['name'=>'کش سراسری آبجکت','status'=>'skipped','detail'=>'پاک‌سازی سراسری در چندسایتی عمداً غیرفعال است؛ روی سایت‌های دیگر اثر می‌گذارد.'];
            else $step('کش آبجکت وردپرس / Redis / Memcached',static fn()=>wp_cache_flush(),'done');
            if (has_action('zarincode_purge_site_cache')!==false) $step('اتصال سفارشی کش میزبان یا CDN',static function(){do_action('zarincode_purge_site_cache',get_current_blog_id());});
            return $report;
        } finally { self::unlock($lock); }
    }
    public static function page(): void {
        if (!current_user_can('manage_options')) wp_die('اجازهٔ دسترسی ندارید.','',['response'=>403]);
        $report=get_transient(self::result_key());
        echo '<div class="wrap" dir="rtl"><h1>پاک‌سازی کش سایت</h1><p>کش سرویس‌های شناسایی‌شده و فایل‌های تولیدشدهٔ المنتور با API رسمی همان سرویس پاک‌سازی می‌شوند. ساخت دوبارهٔ کش ممکن است اولین بازدید را کمی کند کند. وردپرس بدون افزونه یا سرویس کش، کش سراسری صفحات ندارد؛ پاک‌سازی کش آبجکت نیز جای پاک‌سازی CDN یا کش صفحات را نمی‌گیرد.</p>';
        echo '<div class="notice notice-warning inline"><p><strong>محدودهٔ عملیات:</strong> کش مرورگر همهٔ بازدیدکنندگان، OPcache سرور و CDN/کش میزبان بدون اتصال معتبر از اینجا قابل حذف نیست. هیچ فایل دلخواه، سفارش، نشست مشتری، تنظیمات یا transient معتبر به‌صورت گروهی حذف نمی‌شود. کش آفلاین زرین‌کد در به‌روزرسانی بعدی Service Worker نوسازی می‌شود؛ نه فوری روی دستگاه همهٔ کاربران.</p></div>';
        if (is_array($report) && isset($report['steps'],$report['time'])) {
            echo '<h2>نتیجهٔ آخرین درخواست شما</h2><p>'.esc_html(wp_date('Y/m/d H:i:s',(int)$report['time'])).'</p><table class="widefat striped"><thead><tr><th>لایهٔ کش</th><th>وضعیت</th><th>توضیح</th></tr></thead><tbody>';
            $labels=['done'=>'انجام شد','requested'=>'درخواست به سرویس ارسال شد','failed'=>'ناموفق','skipped'=>'اجرا نشد'];
            foreach($report['steps'] as $row) echo '<tr><td>'.esc_html($row['name']).'</td><td>'.esc_html($labels[$row['status']]??'نامشخص').'</td><td>'.esc_html($row['detail']).'</td></tr>';
            echo '</tbody></table><p>ارسال درخواست به افزونه به معنی تأیید مستقل تخلیهٔ کش CDN یا سرور نیست. نبود یک سرویس در جدول یعنی اتصال قابل‌استفادهٔ آن شناسایی نشده است.</p>';
        }
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        wp_nonce_field('zarincode_purge_cache');
        echo '<input type="hidden" name="action" value="zarincode_purge_cache">';
        submit_button('پاک‌سازی کش‌های در دسترس سایت','primary','submit',true);
        echo '</form><p>در شبکهٔ چندسایتی، عملیات با دامنهٔ سراسری اجرا نمی‌شود. برای CDN یا کش اختصاصی هاست، اتصال مربوط باید توسط مدیر فنی پیکربندی شود.</p></div>';
    }
}
