<?php
/** Declarative, shared content model. Defaults are data, never hidden renderer copy. */
namespace Zarincode\Runtime260\Widgets;
defined( 'ABSPATH' ) || exit;

final class Widget_Schema {
    public static function definitions(): array {
        return [
            'header' => ['Widget_Header_Crextio','هدر و منوی زرین کد','eicon-nav-menu','زریــن کد'],
            'footer' => ['Widget_Footer_Crextio','فوتر زرین کد','eicon-footer','برای تحول دیجیتال آماده‌اید؟'],
            'hero' => ['Widget_Hero_Crextio','هیرو یکپارچه — نسخه قدیمی','eicon-banner','طراحی سایت، نرم‌افزار و سئو برای رشد کسب‌وکار شما'],
            'hero_intro' => ['Widget_Hero_Intro','معرفی اصلی آژانس','eicon-banner','آژانس طراحی سایت و نرم‌افزار زرین کد'],
            'hero_profile' => ['Widget_Hero_Profile','معرفی مدیر و آمار عملکرد','eicon-person','احسان نادری پناه'],
            'hero_cta' => ['Widget_Hero_CTA','دعوت به شروع همکاری','eicon-call-to-action','برای شروع همکاری آماده‌اید؟'],
            'brands' => ['Widget_Brands_Marquee','برندها و تکنولوژی‌ها','eicon-carousel','تکنولوژی‌هایی که به آن‌ها اعتماد داریم'],
            'services' => ['Widget_Services_Bento','خدمات تخصصی','eicon-gallery-grid','خدمات تخصصی آژانس زرین کد'],
            'products' => ['Widget_Products_Marketplace','فروشگاه محصولات دیجیتال','eicon-products','محصولات آماده دیجیتال'],
            'portfolio' => ['Widget_Portfolio_Showcase','نمونه‌کارها','eicon-portfolio-grid','برخی از پروژه‌های موفق ما'],
            'why_us' => ['Widget_Why_Us_Stats','مزیت‌های همکاری','eicon-counter','چرا زرین کد؟'],
            'pricing' => ['Widget_Pricing_Tabs','تعرفه خدمات','eicon-price-table','تعرفه خدمات تخصصی'],
            'promo' => ['Widget_Promo_Banner','پیشنهاد ویژه و فراخوان','eicon-call-to-action','جشنواره ویژه خدمات و محصولات زرین کد'],
            'steps' => ['Widget_Project_Steps','مراحل اجرای پروژه','eicon-time-line','از ایده تا اجرای پروژه'],
            'testimonials' => ['Widget_Testimonials','نظرات مشتریان','eicon-testimonial-carousel','تجربه مشتریان زرین کد'],
            'blog' => ['Widget_Blog_Grid','مقالات وبلاگ','eicon-posts-grid','آخرین مقالات آموزشی'],
            'page_section' => ['Widget_Page_Content_Section','صفحات داخلی و حقوقی','eicon-document-file',''],
        ];
    }
    public static function sections(): array {
        return ['about-page'=>'درباره ما','contact-page'=>'تماس با ما','services-page'=>'خدمات','projects-page'=>'نمونه‌کارها','terms'=>'قوانین و مقررات','warranty'=>'گارانتی','privacy'=>'حریم خصوصی','refund'=>'بازگشت وجه','gdpr'=>'GDPR','dmca'=>'DMCA'];
    }
    public static function items( string $kind ): array {
        $data = [
            'header'=>[['صفحه اصلی','/'],['خدمات ما','/services/'],['فروشگاه','/shop/'],['نمونه‌کارها','/projects-portfolio/'],['درباره ما','/about-us/'],['تماس با ما','/contact-us/']],
            'brands'=>[['WordPress','https://wordpress.org/'],['WooCommerce','https://woocommerce.com/'],['Elementor','https://elementor.com/'],['PHP','https://php.net/']],
            'services'=>[['طراحی وب‌سایت','/services/','وب‌سایت اختصاصی، سریع و واکنش‌گرا'],['توسعه نرم‌افزار','/services/','راهکارهای نرم‌افزاری متناسب با کسب‌وکار'],['سئو وب‌سایت','/services/','رشد پایدار در نتایج جستجو'],['سئو شبکه‌های اجتماعی','/services/','توسعه حضور برند در شبکه‌های اجتماعی'],['تولید محتوا','/services/','محتوای هدفمند برای مخاطب شما'],['محصولات آماده','/shop/','قالب، افزونه و سورس‌کد نرم‌افزار']],
            'steps'=>[['مشاوره و نیازسنجی','','بررسی اهداف و نیازهای کسب‌وکار'],['برنامه‌ریزی و توافق','','تعریف مسیر اجرا و زمان‌بندی'],['طراحی و اجرا','','توسعه همراه با گزارش پیشرفت'],['تحویل و پشتیبانی','','آموزش، تحویل و همراهی فنی']],
            'why_us'=>[['توسعه اختصاصی','','راهکار متناسب با نیاز شما'],['پشتیبانی پاسخگو','','همراه شما پس از تحویل پروژه'],['کیفیت و شفافیت','','مراحل اجرا و تعهدات روشن']],
            'testimonials'=>[['مشتری نمونه','','این یک نظر نمایشی است؛ تجربه واقعی مشتری خود را جایگزین کنید.']],
            'pricing'=>[['اقتصادی','/contact-us/','شروع حضور حرفه‌ای در وب'],['حرفه‌ای','/contact-us/','برای کسب‌وکارهای در حال رشد'],['اختصاصی','/contact-us/','راهکار متناسب با نیاز شما']],
            'footer'=>[['خدمات','/services/'],['فروشگاه','/shop/'],['قوانین و مقررات','/terms-and-conditions/'],['حریم خصوصی','/privacy-policy/'],['بازگشت وجه','/refund-policy/'],['تماس با ما','/contact-us/']],
        ];
        $result=[];
        foreach ( $data[$kind] ?? [] as $i=>$r ) {
            $result[]=[ 'title'=>$r[0], 'link'=>['url'=>$r[1] ? (strpos($r[1],'/')===0 ? home_url($r[1]) : $r[1]) : ''], 'description'=>$r[2]??'', 'icon'=>['value'=>'fas fa-check-circle','library'=>'fa-solid'], 'number'=>$kind==='steps'?sprintf('%02d',$i+1):'', 'price'=>$kind==='pricing'?'تماس بگیرید':'', 'features'=>$kind==='pricing'?"مشاوره اولیه\nطراحی واکنش‌گرا\nپشتیبانی فنی":'', 'button_text'=>'اطلاعات بیشتر', 'category'=>$kind==='pricing'?'طراحی سایت':'', 'role'=>$kind==='testimonials'?'کارفرمای پروژه':'', 'rating'=>5, 'badge'=>'', 'visible'=>'yes' ];
        }
        return $result;
    }
    public static function defaults(string $kind): array {
        $defaults=['title'=>self::definitions()[$kind][3]??'','title_tag'=>$kind==='hero'?'h1':'h2','items'=>self::items($kind),'layout'=>in_array($kind,['hero','why_us','promo'],true)?'split':'stack','phone'=>'09024561001','email'=>'info@zarincode.com','primary_text'=>in_array($kind,['hero','promo','header','footer'],true)?'درخواست مشاوره':'','primary_link'=>['url'=>home_url('/contact-us/')],'secondary_text'=>'','show_badge'=>'yes','show_title'=>'yes','show_description'=>'yes','show_image'=>'yes','show_icons'=>'yes','show_buttons'=>'yes','show_contact'=>in_array($kind,['header','footer'],true)?'yes':'','source'=>in_array($kind,['products','portfolio','blog'],true)?'query':'manual','count'=>8,'read_more'=>'مشاهده جزئیات','demo_label'=>'پیش‌نمایش','empty_text'=>'موردی برای نمایش پیدا نشد.','all_label'=>'همه','menu_label'=>'فهرست','stats'=>in_array($kind,['hero','why_us'],true)?self::stats():[]];
        return $defaults;
    }
    public static function stats(): array {
        return [['title'=>'پروژه موفق','value'=>'۱۲۰+'],['title'=>'سال تجربه','value'=>'۸+'],['title'=>'رضایت مشتری','value'=>'۹۹٪']];
    }
}
