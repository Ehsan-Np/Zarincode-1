<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** A presentation layer only: Woo owns endpoints, authentication, nonces and customer data. */
final class Account {
    public static function boot():void {
        add_filter('zarincode_header_account_link',[self::class,'button']);
        add_action('wp_enqueue_scripts',static function(){if(function_exists('is_account_page')&&is_account_page())wp_enqueue_style('zarincode-account',get_template_directory_uri().'/assets/css/account.css',['zarincode-main'],ZARINCODE_VERSION);});
        add_action('woocommerce_before_template_part',static function($name){if($name==='myaccount/lost-password-confirmation.php')self::auth_before();});
        add_action('woocommerce_after_template_part',static function($name){if($name==='myaccount/lost-password-confirmation.php')self::auth_after();});
        foreach(['customer_login','lost_password','reset_password'] as $form){add_action('woocommerce_before_'.$form.'_form',[self::class,'auth_before']);add_action('woocommerce_after_'.$form.'_form',[self::class,'auth_after']);}
    }
    public static function button():string {
        if(!zarincode_get_option('header_account_enabled',true))return '';
        $fallback=function_exists('wc_get_page_id')&&wc_get_page_id('myaccount')>0?wc_get_page_permalink('myaccount'):wp_login_url();if(!$fallback)$fallback=wp_login_url();
        $raw=zarincode_get_option('header_account_url','');if(is_string($raw)&&strpos($raw,'/')===0&&strpos($raw,'//')!==0)$raw=home_url($raw);
        $url=Social_Manager::url($raw)?:$fallback;$label=zarincode_get_option(is_user_logged_in()?'header_account_logged_text':'header_account_text',is_user_logged_in()?'حساب من':'ورود / حساب کاربری');
        return '<a class="zc-header-account zc-icon-btn" href="'.esc_url($url).'" aria-label="'.esc_attr($label).'" title="'.esc_attr($label).'"><i class="fa-regular fa-user" aria-hidden="true"></i><span>'.esc_html($label).'</span></a>';
    }
    public static function labels():array{return ['dashboard'=>['پیشخوان','fa-solid fa-grip'],'orders'=>['سفارش‌های من','fa-solid fa-box-open'],'downloads'=>['فایل‌ها و دانلودها','fa-solid fa-cloud-arrow-down'],'edit-address'=>['آدرس‌های من','fa-solid fa-location-dot'],'payment-methods'=>['روش‌های پرداخت','fa-regular fa-credit-card'],'edit-account'=>['اطلاعات حساب','fa-regular fa-user'],'customer-logout'=>['خروج از حساب','fa-solid fa-arrow-right-from-bracket'],'view-order'=>['جزئیات سفارش','fa-solid fa-receipt'],'add-payment-method'=>['افزودن روش پرداخت','fa-regular fa-credit-card']];}
    private static function is_current(string $endpoint):bool {
        return function_exists('wc_is_current_account_menu_item')?wc_is_current_account_menu_item($endpoint):strpos(' '.wc_get_account_menu_item_classes($endpoint).' ',' is-active ')!==false;
    }
    public static function heading():void {
        $endpoint=WC()->query->get_current_endpoint()?:'dashboard';$label=self::labels()[$endpoint][0]??(wc_get_account_menu_items()[$endpoint]??'حساب کاربری');
        echo '<header class="zc-account-heading"><div><span>ZARINCODE / MY SPACE</span><h1>'.esc_html($label).'</h1><p>سفارش‌ها، فایل‌ها و اطلاعات حساب؛ همه در یک فضای مشخص.</p></div><a href="'.esc_url(home_url('/')).'" class="zc-account-home">بازگشت به سایت <i class="fa-solid fa-arrow-up-left" aria-hidden="true"></i></a></header>';
    }
    public static function navigation():void {
        $user=wp_get_current_user();$labels=self::labels();do_action('woocommerce_before_account_navigation');echo '<nav class="woocommerce-MyAccount-navigation zc-account-nav" aria-label="بخش‌های حساب کاربری"><div class="zc-account-person">'.get_avatar($user->ID,56).'<div><small>خوش آمدید</small><strong>'.esc_html($user->display_name).'</strong></div></div><ul>';
        foreach(wc_get_account_menu_items() as $endpoint=>$label){$item=$labels[$endpoint]??[$label,'fa-regular fa-circle'];echo '<li class="'.esc_attr(wc_get_account_menu_item_classes($endpoint)).'"><a href="'.esc_url(wc_get_account_endpoint_url($endpoint)).'"'.(self::is_current($endpoint)?' aria-current="page"':'').'><i class="'.esc_attr($item[1]).'" aria-hidden="true"></i><span>'.esc_html($item[0]).'</span><b aria-hidden="true">‹</b></a></li>';}
        echo '</ul><div class="zc-account-help"><i class="fa-regular fa-comments" aria-hidden="true"></i><strong>پرسشی درباره سفارش دارید؟</strong><a href="'.esc_url(home_url('/contact-us/')).'">راه‌های ارتباط با زرین‌کد ↗</a></div></nav>';do_action('woocommerce_after_account_navigation');
    }
    public static function dashboard():void {
        $user=wp_get_current_user();$orders=wc_get_orders(['customer_id'=>$user->ID,'limit'=>1,'paginate'=>true,'return'=>'ids']);$total=is_object($orders)?(int)$orders->total:0;$downloads=wc_get_customer_available_downloads($user->ID);
        echo '<section class="zc-account-welcome"><div><span>فضای همراهی شما با زرین‌کد</span><h2>'.esc_html($user->display_name).'، خوش آمدید.</h2><p>مسیر هر سفارش را دنبال کنید، فایل‌های مجاز خود را دریافت کنید و اطلاعات حساب را به‌روز نگه دارید.</p><a href="'.esc_url(wc_get_account_endpoint_url('orders')).'">پیگیری سفارش‌ها <span aria-hidden="true">←</span></a></div><span class="zc-account-monogram" aria-hidden="true">ZC</span></section><div class="zc-account-stats">';
        foreach([['orders','سفارش ثبت‌شده',$total,'fa-solid fa-box-open'],['downloads','فایل قابل دریافت',count($downloads),'fa-solid fa-cloud-arrow-down'],['edit-account','مدیریت مشخصات','حساب شما','fa-regular fa-user']] as [$endpoint,$title,$count,$icon])echo '<a href="'.esc_url(wc_get_account_endpoint_url($endpoint)).'"><i class="'.esc_attr($icon).'" aria-hidden="true"></i><strong>'.esc_html(is_int($count)?number_format_i18n($count):$count).'</strong><span>'.esc_html($title).'</span><b aria-hidden="true">↗</b></a>';
        echo '</div><section class="zc-account-start"><h2>قدم بعدی شما</h2><div><a href="'.esc_url(wc_get_account_endpoint_url('downloads')).'"><i class="fa-regular fa-folder-open" aria-hidden="true"></i><strong>دسترسی به فایل‌ها</strong><span>فایل‌هایی که اجازه دریافت آن‌ها را دارید، در بخش دانلودها هستند.</span></a><a href="'.esc_url(wc_get_account_endpoint_url('edit-account')).'"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><strong>به‌روزرسانی حساب</strong><span>نام، ایمیل و رمز عبور حساب خود را در یک بخش به‌روز کنید.</span></a></div></section>';
    }
    public static function auth_before():void {echo '<section class="zc-account-auth"><header><span class="zc-auth-monogram">ZC</span><div><span>ZARINCODE / YOUR ACCOUNT</span><h1>به فضای کاربری زرین‌کد خوش آمدید.</h1><p>برای پیگیری سفارش‌ها و دسترسی به فایل‌ها، وارد حساب خود شوید.</p></div></header><div class="zc-account-auth-body">';}
    public static function auth_after():void {echo '</div></section>';}
}
