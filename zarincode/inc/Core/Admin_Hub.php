<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** One settings entry point, shared existing stores and original nonce-protected tool handlers. */
final class Admin_Hub {
    public static function tools(): array {
        return [
            'settings'=>['تنظیمات قالب',null],
            'cache'=>['پاک‌سازی کش سایت',['Zarincode\\Runtime260\\Admin\\Cache_Manager','page']],
            'diagnostics'=>['سلامت و عیب‌یابی',['Zarincode\\Runtime260\\Support\\Diagnostics','page']],
            'demo'=>['درون‌ریزی دمو',['Zarincode\\Runtime260\\Demo\\Demo_Seeder','render_admin_page']],
            'plugins'=>['افزونه‌های موردنیاز',['Zarincode\\Theme\\Plugins\\Plugin_Manager','render_plugin_manager_page']],
            'socials'=>['شبکه‌های اجتماعی',[Social_Manager::class,'page']],
            'policies'=>['گارانتی و پرسش‌ها',[Product_Policies::class,'page']],
            'layout'=>['ساماندهی دمو',['Zarincode\\Runtime260\\Widgets\\Demo_Composition','page']],
            'hero'=>['تفکیک هیرو',['Zarincode\\Runtime260\\Widgets\\Hero_Layout','page']],
        ];
    }
    public static function url(string $tool='settings'): string {
        return admin_url('admin.php?page=zarincode-theme-options'.($tool!=='settings'?'&zc_tool='.rawurlencode($tool):''));
    }
    public static function selected(): string {
        $page=$_GET['page']??'';$tool=$_GET['zc_tool']??'settings';
        return $page==='zarincode-theme-options' && is_string($tool) && isset(self::tools()[$tool]) ? $tool : 'settings';
    }
    public static function boot(): void {
        add_action('init',[self::class,'legacy_redirect'],20);
    }
    public static function legacy_redirect(): void {
        if(!is_admin())return;
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET' || !current_user_can('manage_options'))return;
        $aliases=['zarincode-demo-importer'=>'demo','zarincode-demo-importer-sub'=>'demo','zarincode-install-plugins'=>'plugins','zarincode-socials'=>'socials','zarincode-product-policies'=>'policies','zarincode-demo-layout'=>'layout','zarincode-split-hero'=>'hero','zarincode-redux-options'=>'settings','zarincode_opt_options'=>'settings','zarincode_opt'=>'settings'];
        $page=$_GET['page']??'';
        if(is_string($page) && isset($aliases[$page])){wp_safe_redirect(self::url($aliases[$page]));exit;}
    }
    public static function navigation(): void {
        $active=self::selected();echo '<div class="wrap zc-admin-hub" dir="rtl"><nav aria-label="بخش‌های مدیریت زرین‌کد" style="display:flex;flex-wrap:wrap;gap:8px;margin-block:16px">';
        foreach(self::tools() as $key=>$tool)echo '<a class="button '.($key===$active?'button-primary':'').'" '.($key===$active?'aria-current="page" ':'').'href="'.esc_url(self::url($key)).'">'.esc_html($tool[0]).'</a>';
        echo '</nav></div>';
    }
    public static function route(): bool {
        if(!current_user_can('manage_options'))return true;
        self::navigation();$selected=self::selected();if($selected==='settings')return false;
        $callback=self::tools()[$selected][1];[$class,$method]=$callback;
        if(!class_exists($class)){echo '<div class="notice notice-warning"><p>برای استفاده از این ابزار، افزونه هستهٔ همراه قالب باید فعال باشد.</p></div>';return true;}
        if(in_array($selected,['demo','plugins'],true))$callback=[$class::instance(),$method];
        if(is_callable($callback))call_user_func($callback);
        return true;
    }
}
