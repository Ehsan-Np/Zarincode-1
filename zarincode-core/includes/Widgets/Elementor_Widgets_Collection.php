<?php
/** Fully controlled widgets: content, repeatable records, responsive layout and component styles. */
namespace Zarincode\Runtime260\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
defined('ABSPATH') || exit;
require_once __DIR__.'/Widget_Schema.php';
require_once __DIR__.'/Editable_Renderer.php';

abstract class Abstract_Zarincode_Widget extends Widget_Base {
    abstract protected function kind(): string;
    public function get_name(): string { return 'zarincode_'.$this->kind(); }
    public function get_title(): string { return Widget_Schema::definitions()[$this->kind()][1]; }
    public function get_icon(): string { return Widget_Schema::definitions()[$this->kind()][2]; }
    public function get_style_depends(): array { return ['zarincode-editable-widgets']; }
    public function get_script_depends(): array { return ['zarincode-editable-widgets']; }
    public function get_categories(): array { return ['zarincode-elements']; }
    public function get_keywords(): array { return ['zarincode','زرین کد','bento']; }
    private function field($id,$label,$type=C::TEXT,$default='',$extra=[]): void {
        $this->add_control($id,array_merge(['label'=>$label,'type'=>$type,'default'=>$default,'dynamic'=>['active'=>in_array($type,[C::TEXT,C::TEXTAREA,C::WYSIWYG,C::MEDIA,C::URL],true)]],$extra));
    }
    protected function register_controls(): void {
        $kind=$this->kind();
        $this->start_controls_section('main_content',['label'=>'محتوا و هویت بخش']);
        if ($kind==='page_section') {
            $this->field('page_cards_source','منبع کارت‌های صفحه',C::SELECT,'auto',['options'=>['auto'=>'خودکار مطابق نوع صفحه','manual'=>'کارت‌های دستی زیر']]);
            $this->field('section_key','الگوی اولیه صفحه',C::SELECT,'about-page',['options'=>Widget_Schema::sections()]);
            $this->field('page_content','محتوای کامل صفحه (جایگزین الگو)',C::WYSIWYG,'',['description'=>'برای شخصی‌سازی کامل متن حقوقی، محتوای خود را اینجا وارد کنید. متن الگو صرفاً نمونه است و باید بازبینی حقوقی شود.']);
        }
        $this->field('badge','نشان بالای عنوان',C::TEXT,in_array($kind,['hero','promo'],true)?'ZARINCODE STUDIO':'');
        $this->field('badge_icon','آیکون نشان',C::ICONS,['value'=>'fas fa-star','library'=>'fa-solid']);
        $this->field('title','عنوان',C::TEXT,Widget_Schema::definitions()[$kind][3]);
        $this->field('title_tag','تگ عنوان',C::SELECT,$kind==='hero'?'h1':'h2',['options'=>array_combine(['h1','h2','h3','h4','div'],['H1','H2','H3','H4','DIV'])]);
        $this->field('subtitle','زیرعنوان',C::TEXT,'');
        $this->field('description','توضیحات',C::WYSIWYG,$kind==='hero'?'طراحی وب، توسعه نرم‌افزار و بازاریابی دیجیتال؛ همراه کسب‌وکار شما از ایده تا رشد.':'');
        $this->field('image','تصویر / لوگو اصلی',C::MEDIA,['url'=>in_array($kind,['hero','why_us'],true)?get_template_directory_uri().'/assets/images/ehsan-hero-executive.jpg':'']);
        $this->field('image_alt','توضیح تصویر',C::TEXT,'');
        $this->field('image_caption','زیرنویس تصویر',C::TEXT,'');
        $this->field('video_url','لینک ویدئو',C::URL,['url'=>'']);
        $this->field('video_label','متن دکمه ویدئو',C::TEXT,'تماشای ویدئو');
        $this->field('brand_subtitle','نام لاتین برند',C::TEXT,$kind==='header'?'Zarincode Studio':'');
        $this->field('phone','شماره تماس',C::TEXT,'09024561001');
        $this->field('email','ایمیل',C::TEXT,'info@zarincode.com');
        $this->field('address','نشانی',C::TEXT,'');
        $this->field('form_shortcode','شورت‌کد فرم تماس (اختیاری)',C::TEXTAREA,'');
        $this->field('map_link','پیوند نقشه',C::URL,['url'=>'']);
        $this->field('map_label','متن پیوند نقشه',C::TEXT,'مشاهده موقعیت روی نقشه');
        $this->field('show_price','نمایش قیمت',C::SWITCHER,'yes');
        $this->field('rating_symbol','نماد امتیاز',C::TEXT,'★');
        $this->field('copyright','متن کپی‌رایت',C::TEXT,$kind==='footer'?'تمام حقوق برای زرین کد محفوظ است.':'');
        $this->field('discount','نشان تخفیف',C::TEXT,$kind==='promo'?'۵۰٪':'');
        foreach (['show_badge'=>'نمایش نشان','show_title'=>'نمایش عنوان','show_description'=>'نمایش توضیحات','show_image'=>'نمایش تصویر','show_icons'=>'نمایش آیکون‌ها','show_buttons'=>'نمایش دکمه‌ها','show_contact'=>'نمایش اطلاعات تماس'] as $id=>$label) {
            $this->field($id,$label,C::SWITCHER,$id==='show_contact'&& !in_array($kind,['header','footer','page_section'],true)?'':'yes');
        }
        $this->end_controls_section();
        if ($kind==='page_section') {
            $clause=new Repeater();
            $clause->add_control('title',['label'=>'عنوان بند','type'=>C::TEXT]);
            $clause->add_control('description',['label'=>'محتوای بند','type'=>C::WYSIWYG]);
            $clause->add_control('icon',['label'=>'آیکون بند','type'=>C::ICONS]);
            $clause->add_control('visible',['label'=>'نمایش بند','type'=>C::SWITCHER,'default'=>'yes']);
            foreach(Legal_And_Inner_Pages::documents() as $key=>$document) {
                $prefix='document_'.str_replace('-','_',$key).'_';
                $this->start_controls_section($prefix.'section',['label'=>'محتوای '.(Widget_Schema::sections()[$key]??$key),'condition'=>['section_key'=>$key]]);
                $this->field($prefix.'title','عنوان صفحه',C::TEXT,$document['title']??'');
                $this->field($prefix.'badge','نشان صفحه',C::TEXT,$document['badge']??'');
                $this->field($prefix.'description','مقدمه صفحه',C::WYSIWYG,$document['description']??'');
                $this->add_control($prefix.'clauses',['label'=>'بندها و بخش‌ها','type'=>C::REPEATER,'fields'=>$clause->get_controls(),'default'=>$document['clauses']??[],'title_field'=>'{{{ title }}}']);
                $this->end_controls_section();
            }
        }
        $this->start_controls_section('actions',['label'=>'دکمه‌ها و پیوندها']);
        foreach (['primary'=>'اصلی','secondary'=>'دوم'] as $prefix=>$label) {
            $this->field($prefix.'_text','متن دکمه '.$label,C::TEXT,in_array($kind,['header','hero','promo','footer'],true)?($prefix==='primary'?'درخواست مشاوره':'مشاهده محصولات'):'');
            $this->field($prefix.'_link','پیوند دکمه '.$label,C::URL,['url'=>home_url($prefix==='primary'?'/contact-us/':'/shop/')]);
            $this->field($prefix.'_icon','آیکون دکمه '.$label,C::ICONS,['value'=>'fas fa-arrow-left','library'=>'fa-solid']);
        }
        if($kind==='page_section') $this->field('read_more','متن دکمه کارت‌های پویا',C::TEXT,'مشاهده جزئیات');
        $this->field('empty_text','پیام بدون نتیجه',C::TEXT,'موردی برای نمایش پیدا نشد.');
        $this->field('all_label','عنوان فیلتر همه',C::TEXT,'همه');
        $this->field('search_placeholder','متن راهنمای جستجو',C::TEXT,'جستجو…');
        $this->field('demo_label','متن پیش‌نمایش محصولات پویا',C::TEXT,'پیش‌نمایش');
        $this->field('show_rating','نمایش امتیاز مشتری',C::SWITCHER,'yes');
        $this->field('menu_label','عنوان منوی موبایل',C::TEXT,'فهرست');
        if($kind==='header') {
            foreach(['cart'=>'سبد خرید','search'=>'جستجو','mode'=>'حالت روشن / تیره'] as $feature=>$label) {
                $this->field('header_show_'.$feature,'نمایش '.$label,C::SWITCHER,'yes');
                $this->field('header_'.$feature.'_label','متن '.$label,C::TEXT,$label);
                $this->field('header_'.$feature.'_icon','آیکون '.$label,C::ICONS,['value'=>'fas fa-'.(['cart'=>'shopping-cart','search'=>'search','mode'=>'moon'][$feature]),'library'=>'fa-solid']);
            }
            $this->field('menu_icon','آیکون فهرست موبایل',C::ICONS,['value'=>'fas fa-bars','library'=>'fa-solid']);
        }
        $this->end_controls_section();
        $this->start_controls_section('records',['label'=>'کارت‌ها / منو / پلن‌ها / نظرات']);
        if (in_array($kind,['products','blog','portfolio','services'],true)) {
            $this->field('source','منبع کارت‌ها',C::SELECT,in_array($kind,['products','blog','portfolio'],true)?'query':'manual',['options'=>['manual'=>'کارت‌های دستی (ویرایش همه جزئیات)','query'=>'محتوای پویا از وردپرس']]);
            $this->field('count','تعداد نتایج',C::NUMBER,8,['min'=>1,'max'=>50]);
            $this->field('include_ids','شناسه نوشته‌ها (با کاما)',C::TEXT,'');
            $this->field('category_ids','شناسه دسته‌ها (با کاما)',C::TEXT,'');
            $this->field('orderby','مرتب‌سازی',C::SELECT,'date',['options'=>['date'=>'تاریخ','title'=>'عنوان','menu_order'=>'ترتیب دستی','rand'=>'تصادفی']]);
            $this->field('order','جهت مرتب‌سازی',C::SELECT,'DESC',['options'=>['ASC'=>'صعودی','DESC'=>'نزولی']]);
            $this->field('read_more','متن دکمه نتایج پویا',C::TEXT,'مشاهده جزئیات');
            $this->field('excerpt_length','طول خلاصه (کلمه)',C::NUMBER,22,['min'=>0,'max'=>200]);
        }
        $r=new Repeater();
        foreach (['title'=>'عنوان / نام','subtitle'=>'زیرعنوان','badge'=>'نشان','number'=>'شماره مرحله / مقدار','price'=>'قیمت','old_price'=>'قیمت قبلی','period'=>'بازه پرداخت','category'=>'گروه / تب','role'=>'سمت مشتری','button_text'=>'متن دکمه','image_alt'=>'توضیح تصویر'] as $id=>$label) $r->add_control($id,['label'=>$label,'type'=>C::TEXT,'dynamic'=>['active'=>true]]);
        $r->add_control('description',['label'=>'توضیحات / متن نظر','type'=>C::WYSIWYG,'dynamic'=>['active'=>true]]);
        $r->add_control('features',['label'=>'ویژگی‌ها (هر خط یک مورد)','type'=>C::TEXTAREA]);
        $r->add_control('icon',['label'=>'آیکون','type'=>C::ICONS,'default'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']]);
        $r->add_control('image',['label'=>'تصویر / لوگو / چهره','type'=>C::MEDIA,'dynamic'=>['active'=>true]]);
        $r->add_control('link',['label'=>'پیوند','type'=>C::URL,'dynamic'=>['active'=>true]]);
        $r->add_control('demo_link',['label'=>'پیوند پیش‌نمایش','type'=>C::URL]);
        $r->add_control('demo_text',['label'=>'متن پیش‌نمایش','type'=>C::TEXT,'default'=>'پیش‌نمایش']);
        $r->add_control('rating',['label'=>'امتیاز','type'=>C::NUMBER,'min'=>0,'max'=>5,'step'=>0.5,'default'=>5]);
        $r->add_control('featured',['label'=>'کارت ویژه','type'=>C::SWITCHER]);
        $r->add_control('visible',['label'=>'نمایش کارت','type'=>C::SWITCHER,'default'=>'yes']);
        foreach (['background'=>'پس‌زمینه کارت','color'=>'رنگ متن کارت','accent'=>'رنگ تأکیدی کارت'] as $id=>$label) $r->add_control($id,['label'=>$label,'type'=>C::COLOR,'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}}'=>$id==='background'?'background: {{VALUE}};':($id==='color'?'color: {{VALUE}};':'--zc-edit-accent: {{VALUE}};')]]);
        $r->add_control('padding',['label'=>'فاصله داخلی کارت','type'=>C::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}}'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $r->add_control('radius',['label'=>'گردی کارت','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>100]],'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}}'=>'border-radius: {{SIZE}}{{UNIT}};']]);
        foreach(['title'=>'.zc-edit-card-title','text'=>'.zc-edit-card-description','price'=>'.zc-edit-price','button'=>'.zc-edit-button'] as $part=>$sel) {
            $r->add_group_control(Group_Control_Typography::get_type(),['name'=>'item_'.$part.'_typography','selector'=>'{{WRAPPER}} {{CURRENT_ITEM}} '.$sel]);
            $r->add_control('item_'.$part.'_color',['label'=>'رنگ اختصاصی '.$part,'type'=>C::COLOR,'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}} '.$sel=>'color: {{VALUE}};']]);
        }
        $r->add_control('item_icon_color',['label'=>'رنگ آیکون کارت','type'=>C::COLOR,'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}} .zc-edit-icon'=>'color: {{VALUE}};']]);
        $r->add_control('item_icon_size',['label'=>'اندازه آیکون کارت','type'=>C::SLIDER,'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}} .zc-edit-icon'=>'font-size: {{SIZE}}{{UNIT}};']]);
        $r->add_control('item_image_height',['label'=>'ارتفاع تصویر کارت','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>800]],'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}} .zc-edit-image'=>'height: {{SIZE}}{{UNIT}};']]);
        $item_fields=$r->get_controls();
        if(in_array($kind,['header','footer','brands'],true)) {
            $allowed=['title','image_alt','icon','image','link','visible','background','color','accent','padding','radius','item_icon_color','item_icon_size','item_image_height'];
            $item_fields=array_filter($item_fields,static fn($field,$key)=>in_array($key,$allowed,true),ARRAY_FILTER_USE_BOTH);
        }
        $this->add_control('items',['label'=>'آیتم‌ها (افزودن، حذف، تکثیر و جابه‌جایی)','type'=>C::REPEATER,'fields'=>$item_fields,'default'=>Widget_Schema::items($kind),'title_field'=>'{{{ title }}}']);
        $this->field('show_filters','نمایش فیلتر گروه‌ها',C::SWITCHER,in_array($kind,['pricing','portfolio'],true)?'yes':'');
        $this->field('show_search','نمایش جستجوی کارت‌ها',C::SWITCHER,'');
        $this->end_controls_section();
        $this->start_controls_section('additional',['label'=>'آمار، مزایا، شبکه‌های اجتماعی و پرسش‌ها']);
        $stats=new Repeater();
        foreach(['title'=>'عنوان','value'=>'مقدار'] as $id=>$label) $stats->add_control($id,['label'=>$label,'type'=>C::TEXT]);
        $stats->add_control('icon',['label'=>'آیکون','type'=>C::ICONS]);
        $this->add_control('stats',['label'=>'آمار و شمارنده‌ها','type'=>C::REPEATER,'fields'=>$stats->get_controls(),'default'=>in_array($kind,['hero','why_us'],true)?Widget_Schema::stats():[],'title_field'=>'{{{ title }}}']);
        $links=new Repeater();
        $links->add_control('title',['label'=>'متن','type'=>C::TEXT]);
        $links->add_control('icon',['label'=>'آیکون','type'=>C::ICONS]);
        $links->add_control('link',['label'=>'پیوند','type'=>C::URL]);
        $this->add_control('links',['label'=>'مزایا / پیوندهای اجتماعی / اعتماد','type'=>C::REPEATER,'fields'=>$links->get_controls(),'title_field'=>'{{{ title }}}','default'=>[]]);
        $faq=new Repeater();
        $faq->add_control('title',['label'=>'پرسش','type'=>C::TEXT]);
        $faq->add_control('answer',['label'=>'پاسخ','type'=>C::WYSIWYG]);
        $this->add_control('faq',['label'=>'پرسش‌های متداول','type'=>C::REPEATER,'fields'=>$faq->get_controls(),'title_field'=>'{{{ title }}}','default'=>[]]);
        $this->end_controls_section();
        $this->start_controls_section('layout_section',['label'=>'چیدمان واکنش‌گرا','tab'=>C::TAB_CONTENT]);
        $this->field('layout','ترکیب بخش',C::SELECT,in_array($kind,['hero','why_us','promo'],true)?'split':'stack',['frontend_available'=>true,'options'=>['stack'=>'عمودی','split'=>'متن و تصویر کنار هم','reverse'=>'تصویر و متن کنار هم']]);
        $this->add_responsive_control('columns',['label'=>'تعداد ستون کارت‌ها','type'=>C::SELECT,'default'=>$kind==='steps'?'4':'3','tablet_default'=>'2','mobile_default'=>'1','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4','6'=>'6'],'selectors'=>['{{WRAPPER}} .zc-edit-grid'=>'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr));']]);
        $this->add_responsive_control('gap',['label'=>'فاصله کارت‌ها','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>100]],'selectors'=>['{{WRAPPER}} .zc-edit-grid'=>'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('alignment',['label'=>'تراز متن','type'=>C::CHOOSE,'options'=>['right'=>['title'=>'راست','icon'=>'eicon-text-align-right'],'center'=>['title'=>'وسط','icon'=>'eicon-text-align-center'],'left'=>['title'=>'چپ','icon'=>'eicon-text-align-left']],'selectors'=>['{{WRAPPER}} .zc-edit'=>'text-align: {{VALUE}};']]);
        $this->add_responsive_control('max_width',['label'=>'عرض محتوا','type'=>C::SLIDER,'size_units'=>['px','%','vw'],'range'=>['px'=>['min'=>320,'max'=>2000],'%' =>['min'=>10,'max'=>100]],'selectors'=>['{{WRAPPER}} .zc-edit'=>'max-width: {{SIZE}}{{UNIT}};']]);
        $this->field('direction','جهت محتوا',C::SELECT,'rtl',['options'=>['rtl'=>'راست به چپ','ltr'=>'چپ به راست'],'selectors'=>['{{WRAPPER}} .zc-edit'=>'direction: {{VALUE}};']]);
        $this->end_controls_section();
        // Every component has its own normal/hover style, not a single global colour.
        $components=['section'=>['کل بخش','.zc-edit'],'card'=>['کارت‌ها','.zc-edit-card'],'title'=>['عنوان اصلی','.zc-edit-title'],'subtitle'=>['زیرعنوان','.zc-edit-subtitle'],'text'=>['توضیحات','.zc-edit-description'],'card_title'=>['عنوان کارت','.zc-edit-card-title'],'card_text'=>['متن کارت','.zc-edit-card-description'],'badge'=>['نشان‌ها','.zc-edit-badge'],'button'=>['دکمه اصلی','.zc-edit-primary'],'secondary'=>['دکمه دوم','.zc-edit-secondary'],'icon'=>['آیکون‌ها','.zc-edit-icon'],'image'=>['تصاویر','.zc-edit-image'],'price'=>['قیمت','.zc-edit-price'],'features'=>['ویژگی پلن','.zc-edit-features'],'stats'=>['آمار','.zc-edit-stat'],'nav'=>['پیوندها و منو','.zc-edit-nav a'],'faq'=>['آکاردئون','.zc-edit-faq'],'faq_summary'=>['عنوان آکاردئون','.zc-edit-faq summary'],'menu_toggle'=>['دکمه فهرست موبایل','.zc-edit-mobile-nav summary'],'filter'=>['فیلترها','.zc-edit-filter'],'contact'=>['اطلاعات تماس','.zc-edit-contact'],'copyright'=>['کپی‌رایت','.zc-edit-copyright'],'rating'=>['امتیاز','.zc-edit-rating'],'document_title'=>['عنوان بند صفحه','.zc-edit-clause h3'],'document_text'=>['متن بند صفحه','.zc-edit-clause .zc-edit-description'],'stat_value'=>['عدد آمار','.zc-edit-stat strong'],'stat_label'=>['عنوان آمار','.zc-edit-stat span'],'caption'=>['زیرنویس تصویر','.zc-edit-figure figcaption'],'search_input'=>['فیلد جستجو','.zc-edit-search-panel input, {{WRAPPER}} .zc-edit-search input'],'form'=>['فرم تماس','.zc-edit-form']];
        foreach($components as $id=>[$label,$selector]) {
            $this->start_controls_section('style_'.$id,['label'=>$label,'tab'=>C::TAB_STYLE]);
            $target='{{WRAPPER}} '.$selector;
            $this->start_controls_tabs('tabs_'.$id);
            foreach(['normal'=>'عادی','hover'=>'هاور'] as $state=>$state_label) {
                $this->start_controls_tab($id.'_'.$state,['label'=>$state_label]);
                $sel=$target.($state==='hover'?':hover':'');
                $this->field($id.'_'.$state.'_color','رنگ متن',C::COLOR,'',['selectors'=>[$sel=>'color: {{VALUE}};']]);
                $this->add_group_control(Group_Control_Background::get_type(),['name'=>$id.'_'.$state.'_background','types'=>['classic','gradient'],'selector'=>$sel]);
                $this->add_group_control(Group_Control_Border::get_type(),['name'=>$id.'_'.$state.'_border','selector'=>$sel]);
                $this->add_group_control(Group_Control_Box_Shadow::get_type(),['name'=>$id.'_'.$state.'_shadow','selector'=>$sel]);
                $this->end_controls_tab();
            }
            $this->end_controls_tabs();
            if($id!=='image') $this->add_group_control(Group_Control_Typography::get_type(),['name'=>$id.'_typography','selector'=>$target]);
            foreach(['padding'=>'فاصله داخلی','margin'=>'فاصله بیرونی','border-radius'=>'گردی گوشه‌ها'] as $prop=>$label2) $this->add_responsive_control($id.'_'.str_replace('-','_',$prop),['label'=>$label2,'type'=>C::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>[$target=>$prop.': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $this->end_controls_section();
        }
        $this->start_controls_section('rating_colors',['label'=>'رنگ ستاره‌های امتیاز','tab'=>C::TAB_STYLE]);
        $this->field('rating_active_color','رنگ امتیاز فعال',C::COLOR,'',['selectors'=>['{{WRAPPER}} .zc-edit-rating'=>'--zc-rating-color: {{VALUE}};']]);
        $this->field('rating_inactive_color','رنگ امتیاز غیرفعال',C::COLOR,'',['selectors'=>['{{WRAPPER}} .zc-edit-rating'=>'--zc-rating-inactive: {{VALUE}};']]);
        $this->end_controls_section();
        $this->start_controls_section('image_style',['label'=>'اندازه تصویر و آیکون','tab'=>C::TAB_STYLE]);
        foreach(['width'=>'عرض','height'=>'ارتفاع'] as $prop=>$label) $this->add_responsive_control('image_'.$prop,['label'=>$label.' تصویر','type'=>C::SLIDER,'size_units'=>['px','%','vw'],'range'=>['px'=>['min'=>0,'max'=>1200]],'selectors'=>['{{WRAPPER}} .zc-edit-image'=>$prop.': {{SIZE}}{{UNIT}};']]);
        $this->field('image_fit','نحوه قرارگیری تصویر',C::SELECT,'cover',['options'=>['cover'=>'پوشش','contain'=>'در بر گرفتن','fill'=>'کشیده','none'=>'اصلی'],'selectors'=>['{{WRAPPER}} .zc-edit-image'=>'object-fit: {{VALUE}};']]);
        $this->add_responsive_control('icon_size',['label'=>'اندازه آیکون','type'=>C::SLIDER,'range'=>['px'=>['min'=>8,'max'=>200]],'selectors'=>['{{WRAPPER}} .zc-edit-icon'=>'font-size: {{SIZE}}{{UNIT}};','{{WRAPPER}} .zc-edit-icon svg'=>'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();
    }
    protected function render(): void {
        echo Editable_Renderer::render($this->kind(),$this->get_settings_for_display(),$this->get_id());
    }
}

class Widget_Header_Crextio extends Abstract_Zarincode_Widget { protected function kind(): string { return 'header'; } }
class Widget_Footer_Crextio extends Abstract_Zarincode_Widget { protected function kind(): string { return 'footer'; } }
class Widget_Hero_Crextio extends Abstract_Zarincode_Widget { protected function kind(): string { return 'hero'; } }
class Widget_Brands_Marquee extends Abstract_Zarincode_Widget { protected function kind(): string { return 'brands'; } }
class Widget_Services_Bento extends Abstract_Zarincode_Widget { protected function kind(): string { return 'services'; } }
class Widget_Products_Marketplace extends Abstract_Zarincode_Widget { protected function kind(): string { return 'products'; } }
class Widget_Portfolio_Showcase extends Abstract_Zarincode_Widget { protected function kind(): string { return 'portfolio'; } }
class Widget_Why_Us_Stats extends Abstract_Zarincode_Widget { protected function kind(): string { return 'why_us'; } }
class Widget_Pricing_Tabs extends Abstract_Zarincode_Widget { protected function kind(): string { return 'pricing'; } }
class Widget_Promo_Banner extends Abstract_Zarincode_Widget { protected function kind(): string { return 'promo'; } }
class Widget_Project_Steps extends Abstract_Zarincode_Widget { protected function kind(): string { return 'steps'; } }
class Widget_Testimonials extends Abstract_Zarincode_Widget { protected function kind(): string { return 'testimonials'; } }
class Widget_Blog_Grid extends Abstract_Zarincode_Widget { protected function kind(): string { return 'blog'; } }
class Widget_Page_Content_Section extends Abstract_Zarincode_Widget { protected function kind(): string { return 'page_section'; } }
