<?php
namespace Zarincode\Runtime260\Demo;
use Zarincode\Runtime260\Widgets\Native_Design as N;
defined('ABSPATH') || exit;
/** Copy is frozen into imported Elementor settings; never mutates existing customer pages. */
final class Service_Demo {
    public static function data():array {static $data;return $data??($data=json_decode(file_get_contents(__DIR__.'/service-demo.json'),true)?:[]);}
    private static function urls($v){if(is_array($v)){foreach($v as &$value)$value=self::urls($value);unset($value);return $v;}return is_string($v)?str_replace(['{{HOME}}','{{THEME}}'],[untrailingslashit(home_url()),get_template_directory_uri()],$v):$v;}
    public static function settings(string $widget,array $settings=[]):array {
        if(strpos($widget,'zarincode_')!==0)return $settings;$kind=substr($widget,10);$section=$settings['section_key']??'about-page';$m=N::model($kind,$section);if(!$m)return array_replace(self::urls(self::data()['widgets'][$kind]??[]),$settings);$key=$kind==='page_section'?$kind.'_'.str_replace('-','_',$section):$kind;$copy=self::data()['widgets'][$key]??[];$out=[];
        foreach($m['fields'] as $k=>$def)$out[N::id($m,$k)]=$copy[$k]??($def['default']??'');
        foreach($m['repeaters'] as $k=>$def){$rows=$def['default'];$changes=self::data()['rows'][$key][$k]??[];foreach($rows as $i=>&$row){$row=array_replace($row,$changes[$i]??[]);if($kind==='testimonials')$row['customer_photo']=['url'=>get_template_directory_uri().'/assets/images/demo-customer-avatar.jpg','id'=>0];}unset($row);$out[N::id($m,$k)]=$rows;}
        if($kind==='pricing'){$out['n_pricing_features']=self::pricing_features($m);foreach($m['repeaters'] as $key=>$def)foreach($out[N::id($m,$key)] as &$plan)$plan['features_source']='icon_list';unset($plan);}
        $out['design_mode']='original';return array_replace(self::urls($out),$settings);
    }
    public static function pricing_features(array $m):array {$out=[];foreach($m['repeaters'] as $rep)foreach($rep['default'] as $plan)foreach(['نیازسنجی و تعریف خروجی','بازبینی مرحله‌ای','تحویل و آموزش طبق توافق'] as $i=>$text)$out[]=['_id'=>substr(hash('sha256',$plan['_id'].$i),0,8),'plan_key'=>$plan['plan_key']??$plan['_id'],'text'=>$text,'icon'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']];return $out;}
    public static function hero():array {$out=[];foreach(['hero_intro','hero_cta'] as $kind)$out=array_replace($out,self::settings('zarincode_'.$kind));return $out;}
    public static function record(array $row,string $kind,int $index):array {
        if($kind==='service'){$copy=self::data()['services'][$index]??[];return array_replace($row,self::urls($copy));}
        if($kind==='project'){$row['title']='مطالعه موردی نمایشی: '.preg_replace('/^طراحی و کدنویسی /u','',$row['title']);$row['client']='کارفرمای نمونه — قابل جایگزینی';$row['metrics']='سناریوی آموزشی، نه آمار پروژه واقعی';$row['content']='<p>این مطالعه موردی برای نمایش ساختار ارائه پروژه در دموی زرین کد نوشته شده و ادعای اجرای پروژه برای کارفرمای واقعی نیست.</p><h2>مسئله و هدف</h2><p>کسب‌وکار به تجربه کاربری روشن، مدیریت محتوای آسان و اتصال منسجم فرایندها نیاز دارد. ابتدا مسیر فعلی کاربر و محدودیت‌های اجرایی ثبت می‌شود.</p><h2>راهکار پیشنهادی</h2><p>پروژه به کشف نیاز، نمونه تعاملی، توسعه مرحله‌ای و آزمون پذیرش تقسیم می‌شود. برای هر مرحله خروجی قابل بررسی و معیار تحویل تعریف می‌کنیم.</p><h2>خروجی قابل تحویل</h2><ul><li>معماری و رابط واکنش‌گرا</li><li>سناریوهای آزمون و راهنمای مدیریت</li><li>برنامه نگهداری بر اساس توافق</li></ul><h2>ارزیابی نتیجه</h2><p>در پروژه واقعی، داده‌های قبل و بعد با رضایت کارفرما و ذکر دوره اندازه‌گیری منتشر می‌شود. تصاویر این دمو نمایشی‌اند.</p>';return $row;}
        if($kind==='product'){$row['sales']='محصول نمایشی';$row['old_price']='';$row['ver']='نسخه نمایشی';$row['desc']='نمونه کاتالوگ دیجیتال برای نمایش ساختار فروشگاه زرین کد. مشخصات، فایل قابل دریافت، مجوز استفاده و شرایط پشتیبانی باید برای محصول واقعی توسط فروشنده تکمیل شود. این داده نمایشی به معنی عرضه یا تضمین قابلیت‌های محصول نیست.';return $row;}return $row;
    }
    private static function section(string $type,array $settings):array {return ['id'=>substr(wp_generate_uuid4(),0,8),'elType'=>'section','settings'=>['layout'=>'boxed'],'elements'=>[['id'=>substr(wp_generate_uuid4(),0,8),'elType'=>'column','settings'=>['_column_size'=>100],'elements'=>[['id'=>substr(wp_generate_uuid4(),0,8),'elType'=>'widget','widgetType'=>$type,'settings'=>$settings,'elements'=>[]]]]]];}
    public static function legal(string $slug):array {$data=self::data()['legal'][$slug]??[];if(!$data)return [];$tree=[self::section('heading',['title'=>$data['title'],'header_size'=>'h1']),self::section('text-editor',['editor'=>'<p><strong>متن نمونه قابل ویرایش:</strong> این صفحه الگوی محتوایی دمو است، نه مشاوره حقوقی یا اعلام انطباق قانونی. مالک سایت باید آن را با رویه واقعی کسب‌وکار و قوانین قابل اعمال تطبیق دهد.</p>'])];foreach($data['sections'] as $part){$tree[]=self::section('heading',['title'=>$part[0],'header_size'=>'h2']);$tree[]=self::section('text-editor',['editor'=>'<p>'.$part[1].'</p>']);}return $tree;}
    public static function legal_content(string $slug):string {$data=self::data()['legal'][$slug]??[];if(!$data)return ''; $html='<p>متن نمونه دمو؛ پیش از انتشار با سیاست واقعی و قوانین قابل اعمال تطبیق دهید.</p>';foreach($data['sections'] as $part)$html.='<h2>'.esc_html($part[0]).'</h2><p>'.esc_html($part[1]).'</p>';return $html;}
    public static function blog():array {return self::urls(self::data()['blog']??[]);}
    public static function sidebars():void {
        $existing=get_option('sidebars_widgets',[]);if(!is_array($existing))$existing=[];
        // Explicit import only, and never replace populated widget areas.
        $hasProduct=false;foreach($existing['sidebar-blog']??[] as $widget)if(strpos($widget,'zc_article_products-')===0)$hasProduct=true;
        if(!$hasProduct){$widgets=get_option('widget_zc_article_products',[]);$id=max(2,1+max(array_merge([1],array_filter(array_keys($widgets),'is_int'))));$widgets[$id]=['title'=>'ابزارهای مکمل این مسیر','count'=>3];$widgets['_multiwidget']=1;update_option('widget_zc_article_products',$widgets);$existing['sidebar-blog']=array_merge(['zc_article_products-'.$id],$existing['sidebar-blog']??[]);}
        if(empty($existing['sidebar-blog-left'])){$widgets=get_option('widget_categories',[]);$id=max(2,1+max(array_merge([1],array_filter(array_keys($widgets),'is_int'))));$widgets[$id]=['title'=>'موضوعات مجله','count'=>1,'hierarchical'=>0,'dropdown'=>0];$widgets['_multiwidget']=1;update_option('widget_categories',$widgets);$existing['sidebar-blog-left']=['categories-'.$id];}update_option('sidebars_widgets',$existing);
    }
}
