<?php
/**
 * Zarincode professional agency widgets.
 * Independent implementation, informed by the public Xpro Elementor Addons widget
 * behaviour/API patterns (wpxpro, commit 7f2457547708ccf02623d15dec79ed9fcf211edc).
 * No upstream PHP/JS or assets are copied into this file. License: GPL-2.0-or-later.
 * New RTL layouts, native inline editing, accessible comparison, data and interactions by Zarincode.
 * See THIRD-PARTY-NOTICES.md for source references and license observations.
 */
namespace Zarincode\Runtime260\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
defined('ABSPATH') || exit;
require_once __DIR__.'/Repeater_Identity.php';

abstract class Professional_Widget extends Widget_Base {
    abstract protected function type(): string;
    public static function catalog(): array {
        return [
            'service_spotlight'=>['خدمات ویژه و راهکارهای زرین کد','eicon-icon-box','راهکارهای دیجیتال برای رشد برند شما'],
            'project_roadmap'=>['نقشه راه اجرای پروژه','eicon-time-line','مسیر شفاف از ایده تا تحویل'],
            'seo_audit'=>['داشبورد ارزیابی سئو و عملکرد','eicon-skill-bar','تصویر روشن از وضعیت وب‌سایت'],
            'before_after'=>['مقایسه تصویری قبل و بعد','eicon-image-before-after','تفاوت را با چشم خود ببینید'],
            'team_experts'=>['تیم متخصص و مشاوران','eicon-person','متخصصان پشت موفقیت پروژه شما'],
            'service_matrix'=>['ماتریس مقایسه خدمات و پکیج‌ها','eicon-table','پکیج مناسب کسب‌وکار خود را انتخاب کنید'],
        ];
    }
    public function get_name(): string{return 'zarincode_'.$this->type();}
    public function get_title(): string{return self::catalog()[$this->type()][0];}
    public function get_icon(): string{return self::catalog()[$this->type()][1];}
    public function get_categories(): array{return ['zarincode-professional'];}
    public function get_keywords(): array{return ['زرین کد','zarincode','seo','agency','xpro'];}
    public function get_style_depends(): array{return ['zarincode-professional'];}
    public function get_script_depends(): array{return ['zarincode-professional'];}
    public function add_control($id,array $args,$options=[]){
        $args=Icon_Compatibility::control($args);
        if(($args['type']??'')===C::REPEATER&&isset($args['default']))$args['default']=Repeater_Identity::rows($args['default'],$this->get_name().'.'.$id,$args['fields']??[]);
        return parent::add_control($id,$args,$options);
    }
    protected function get_init_settings(){return Repeater_Identity::settings(parent::get_init_settings(),$this->get_controls(),$this->get_name().'.'.$this->get_id());}
    public function get_raw_data($with_html_content=false) {
        $data=parent::get_raw_data($with_html_content);
        $data['settings']=Repeater_Identity::settings($data['settings'],$this->get_controls(),$this->get_name().'.'.$this->get_id());return $data;
    }
    protected function control(string $id,string $label,string $type=C::TEXT,$default='',array $extra=[]): void {
        $this->add_control($id,array_merge(['label'=>$label,'type'=>$type,'default'=>$default,'dynamic'=>['active'=>in_array($type,[C::TEXT,C::TEXTAREA,C::WYSIWYG,C::URL,C::MEDIA],true)]],$extra));
    }
    private function defaults(): array {
        switch($this->type()){
            case 'service_spotlight':return [
                ['title'=>'طراحی سایت اختصاصی','text'=>'از تجربه کاربری تا فروش؛ یک وب‌سایت سریع، امن و متناسب با برند شما.','badge'=>'طراحی و توسعه','meta'=>'فروشگاهی / شرکتی','features'=>"طراحی واکنش‌گرا\nاتصال به ابزارهای کسب‌وکار\nآموزش و پشتیبانی",'button'=>'مشاوره طراحی سایت','link'=>['url'=>home_url('/contact-us/')],'icon'=>['value'=>'fas fa-laptop-code','library'=>'fa-solid']],
                ['title'=>'سئو و رشد ارگانیک','text'=>'ممیزی فنی، برنامه محتوایی و پایش مستمر بر اساس اهداف کسب‌وکار.','badge'=>'سئو و محتوا','meta'=>'تحلیل / برنامه‌ریزی / اجرا','features'=>"ممیزی فنی وب‌سایت\nتحلیل رقبا و کلمات کلیدی\nگزارش شفاف پیشرفت",'button'=>'بررسی پروژه سئو','link'=>['url'=>home_url('/contact-us/')],'icon'=>['value'=>'fas fa-chart-line','library'=>'fa-solid']],
                ['title'=>'توسعه نرم‌افزار و محصولات آماده','text'=>'راهکار اختصاصی یا محصول آماده؛ متناسب با بودجه و مسیر رشد شما.','badge'=>'نرم‌افزار و مارکت','meta'=>'افزونه / قالب / سورس‌کد','features'=>"تحلیل نیازمندی‌ها\nتوسعه و تست\nمستندات و تحویل",'button'=>'مشاهده محصولات','link'=>['url'=>home_url('/shop/')],'icon'=>['value'=>'fas fa-code','library'=>'fa-solid']],
            ];
            case 'project_roadmap':return [
                ['title'=>'کشف و تحلیل','text'=>'اهداف، مخاطبان و نیازمندی‌های پروژه را مشخص می‌کنیم.','meta'=>'مرحله ۰۱','badge'=>'شروع همکاری','icon'=>['value'=>'fas fa-search','library'=>'fa-solid']],
                ['title'=>'طراحی و برنامه‌ریزی','text'=>'نقشه راه، ساختار محتوا و تجربه کاربری تدوین می‌شود.','meta'=>'مرحله ۰۲','badge'=>'تأیید مسیر','icon'=>['value'=>'fas fa-pencil-ruler','library'=>'fa-solid']],
                ['title'=>'توسعه و کنترل کیفیت','text'=>'پیاده‌سازی همراه با تست و ارائه گزارش پیشرفت.','meta'=>'مرحله ۰۳','badge'=>'در مسیر اجرا','icon'=>['value'=>'fas fa-code','library'=>'fa-solid']],
                ['title'=>'تحویل و همراهی','text'=>'آموزش و تحویل مستندات؛ آغاز مرحله نگه‌داری و رشد.','meta'=>'مرحله ۰۴','badge'=>'پشتیبانی','icon'=>['value'=>'fas fa-rocket','library'=>'fa-solid']],
            ];
            case 'seo_audit':return [['title'=>'سلامت فنی','text'=>'اعداد این نمونه نمایشی‌اند؛ با داده‌های گزارش واقعی جایگزین کنید.','value'=>85,'meta'=>'Technical SEO'],['title'=>'کیفیت محتوا','text'=>'ارزیابی بر پایه نیاز مخاطب و ساختار محتوایی.','value'=>72,'meta'=>'Content'],['title'=>'تجربه کاربری','text'=>'بررسی دسترس‌پذیری، سرعت و مسیر تبدیل.','value'=>90,'meta'=>'User Experience']];
            case 'team_experts':return [['title'=>'احسان نادری پناه','text'=>'همراه شما در تحلیل، طراحی و توسعه راهکارهای دیجیتال.','meta'=>'بنیان‌گذار و معمار نرم‌افزار','badge'=>'ZARINCODE','image'=>['url'=>get_template_directory_uri().'/assets/images/ehsan-about-founder.jpg'],'alt'=>'احسان نادری پناه','button'=>'گفت‌وگو درباره پروژه','link'=>['url'=>home_url('/contact-us/')]]];
            case 'service_matrix':return [['title'=>'طراحی واکنش‌گرا','column_1'=>'دارد','column_2'=>'دارد','column_3'=>'دارد'],['title'=>'طراحی تجربه کاربری اختصاصی','column_1'=>'پایه','column_2'=>'پیشرفته','column_3'=>'کاملاً اختصاصی'],['title'=>'اتصال به سرویس‌های بیرونی','column_1'=>'اختیاری','column_2'=>'مطابق توافق','column_3'=>'مطابق نیاز پروژه'],['title'=>'پشتیبانی و آموزش','column_1'=>'مطابق قرارداد','column_2'=>'مطابق قرارداد','column_3'=>'مطابق قرارداد']];
        }
        return [];
    }
    protected function register_controls(): void {
        $type=$this->type();
        $this->start_controls_section('intro',['label'=>'عنوان و معرفی']);
        $this->control('eyebrow','نشان بالای عنوان',C::TEXT,'ZARINCODE / DIGITAL STUDIO');
        $this->control('title','عنوان',C::TEXT,self::catalog()[$type][2]);
        $this->control('tag','تگ عنوان',C::SELECT,'h2',['options'=>['h1'=>'H1','h2'=>'H2','h3'=>'H3','div'=>'DIV']]);
        $this->control('intro','توضیحات',C::WYSIWYG,$type==='seo_audit'?'<p>نمونه نمایشی گزارش؛ آمار واقعی پروژه خود را وارد کنید.</p>':'');
        $this->control('show_eyebrow','نمایش نشان',C::SWITCHER,'yes');
        $this->control('show_intro','نمایش توضیحات',C::SWITCHER,'yes');
        $this->end_controls_section();
        if($type==='before_after'){
            $this->start_controls_section('comparison',['label'=>'تصاویر مقایسه']);
            foreach(['before'=>'قبل از بازطراحی','after'=>'بعد از بازطراحی'] as $key=>$label){
                $this->control($key.'_image','تصویر '.$label,C::MEDIA,['url'=>'']);
                $this->control($key.'_alt','متن جایگزین '.$label,C::TEXT,$label);
                $this->control($key.'_label','برچسب '.$label,C::TEXT,$label);
            }
            $this->control('handle_symbol','نماد جداکننده',C::TEXT,'↔');
            $this->control('position','موقعیت اولیه جداکننده',C::NUMBER,50,['min'=>0,'max'=>100]);
            $this->control('range_label','عنوان دسترس‌پذیری اسلایدر',C::TEXT,'مقایسه تصاویر قبل و بعد');
            $this->control('caption','زیرنویس',C::TEXT,'جداکننده را جابه‌جا کنید تا تفاوت دو نسخه را ببینید.');
            $this->control('show_labels','نمایش برچسب تصاویر',C::SWITCHER,'yes');
            $this->end_controls_section();
        }else{
            $this->start_controls_section('data',['label'=>$type==='service_matrix'?'ردیف‌های جدول':'آیتم‌ها و محتوای اختصاصی']);
            $r=new Repeater();
            $r->add_control('title',['label'=>$type==='service_matrix'?'ویژگی':'عنوان / نام','type'=>C::TEXT,'dynamic'=>['active'=>true]]);
            if($type==='service_matrix'){
                foreach([1,2,3] as $n)$r->add_control('column_'.$n,['label'=>'مقدار ستون '.$n,'type'=>C::TEXT,'dynamic'=>['active'=>true]]);
            }else{
                $r->add_control('text',['label'=>'توضیحات','type'=>C::WYSIWYG]);
                foreach(['meta'=>'مرحله / تخصص / زیرعنوان','badge'=>'نشان','button'=>'متن دکمه'] as $key=>$label)$r->add_control($key,['label'=>$label,'type'=>C::TEXT,'dynamic'=>['active'=>true]]);
                $r->add_control('link',['label'=>'پیوند','type'=>C::URL,'dynamic'=>['active'=>true]]);
                $r->add_control('icon',['label'=>'آیکون (کتابخانه یا SVG)','type'=>C::ICONS]);
                if(in_array($type,['service_spotlight','team_experts'],true)){
                    $r->add_control('image',['label'=>'تصویر','type'=>C::MEDIA,'dynamic'=>['active'=>true]]);
                    $r->add_control('alt',['label'=>'متن جایگزین تصویر','type'=>C::TEXT,'dynamic'=>['active'=>true]]);
                }
                if($type==='service_spotlight')$r->add_control('features',['label'=>'مزایا و ویژگی‌ها','type'=>C::WYSIWYG]);
                if($type==='team_experts')foreach(['linkedin'=>'LinkedIn','github'=>'GitHub','website'=>'وب‌سایت'] as $social=>$label){$r->add_control($social.'_label',['label'=>'متن '.$label,'type'=>C::TEXT,'default'=>$label]);$r->add_control($social.'_link',['label'=>'پیوند '.$label,'type'=>C::URL,'dynamic'=>['active'=>true]]);}
                if($type==='seo_audit')$r->add_control('value',['label'=>'امتیاز از ۱۰۰','type'=>C::NUMBER,'min'=>0,'max'=>100,'default'=>75]);
            }
            $r->add_control('highlight',['label'=>'آیتم ویژه','type'=>C::SWITCHER]);
            foreach(['background'=>'پس‌زمینه','text_color'=>'رنگ متن','accent'=>'رنگ تأکیدی'] as $key=>$label)$r->add_control($key,['label'=>$label,'type'=>C::COLOR,'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}}'=>$key==='background'?'background: {{VALUE}};':($key==='accent'?'--zc-pro-accent: {{VALUE}};':'color: {{VALUE}};')]]);
            $r->add_control('padding',['label'=>'فاصله داخلی','type'=>C::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>['{{WRAPPER}} {{CURRENT_ITEM}}'.($type==='service_matrix'?' > *':'')=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $definitions=$r->get_controls();$r=new Repeater();$r->start_controls_tabs('zc_record_tabs');
            foreach(['content'=>'محتوا','media'=>'رسانه','links'=>'پیوند','style'=>'ظاهر'] as $tab=>$label){$r->start_controls_tab('zc_record_'.$tab,['label'=>$label]);
                foreach($definitions as $field=>$args){if($field==='_id')continue;
                    $typeName=$args['type']??'';$category=in_array($typeName,[C::COLOR,C::DIMENSIONS,C::SWITCHER],true)?'style':(in_array($typeName,[C::MEDIA,C::ICONS],true)||$field==='alt'?'media':($typeName===C::URL||$field==='button'||strpos($field,'_label')!==false?'links':'content'));
                    if($category===$tab){unset($args['name']);$r->add_control($field,$args);}
                }$r->end_controls_tab();
            }$r->end_controls_tabs();
            $this->add_control('records',['label'=>'افزودن / حذف / تکثیر / جابه‌جایی','type'=>C::REPEATER,'fields'=>$r->get_controls(),'prevent_empty'=>false,'default'=>$this->defaults(),'title_field'=>'{{{ title }}}']);
            if($type!=='service_matrix')$this->control('show_icons','نمایش آیکون‌ها',C::SWITCHER,'yes');
            if($type!=='service_matrix')$this->control('show_buttons','نمایش دکمه‌ها',C::SWITCHER,'yes');
            if(in_array($type,['service_spotlight','team_experts'],true))$this->control('show_images','نمایش تصاویر',C::SWITCHER,'yes');
            if($type==='seo_audit')$this->control('show_values','نمایش امتیازها',C::SWITCHER,'yes');
            $this->end_controls_section();
        }
        if($type==='service_matrix'){
            $this->start_controls_section('table_settings',['label'=>'عنوان ستون‌ها و انتخاب پکیج']);
            $this->control('feature_label','عنوان ستون ویژگی‌ها',C::TEXT,'ویژگی / خدمت');
            foreach([1=>'شروع',2=>'رشد',3=>'اختصاصی'] as $n=>$label){
                $this->control('column_'.$n,'عنوان پکیج '.$n,C::TEXT,$label);
                $this->control('price_'.$n,'متن قیمت '.$n,C::TEXT,'استعلام قیمت');
                $this->control('button_'.$n,'متن دکمه '.$n,C::TEXT,'مشاوره این پکیج');
                $this->control('link_'.$n,'پیوند دکمه '.$n,C::URL,['url'=>home_url('/contact-us/')]);
            }
            $this->control('table_caption','توضیح دسترس‌پذیری جدول',C::TEXT,'مقایسه ویژگی‌های پکیج‌های خدمات زرین کد');
            $this->end_controls_section();
        }
        $this->start_controls_section('layout',['label'=>'چیدمان و واکنش‌گرایی','tab'=>C::TAB_STYLE]);
        if(!in_array($type,['before_after','service_matrix'],true))$this->add_responsive_control('columns',['label'=>'ستون‌ها','type'=>C::SELECT,'default'=>$type==='project_roadmap'?'4':'3','tablet_default'=>'2','mobile_default'=>'1','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'selectors'=>['{{WRAPPER}} .zc-pro-grid'=>'grid-template-columns:repeat({{VALUE}},minmax(0,1fr));']]);
        if(!in_array($type,['before_after','service_matrix'],true))$this->add_responsive_control('gap',['label'=>'فاصله آیتم‌ها','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>100]],'selectors'=>['{{WRAPPER}} .zc-pro-grid'=>'gap:{{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('alignment',['label'=>'تراز متن','type'=>C::SELECT,'options'=>['right'=>'راست','center'=>'وسط','left'=>'چپ'],'selectors'=>['{{WRAPPER}} .zc-pro'=>'text-align:{{VALUE}};']]);
        $this->control('direction','جهت',C::SELECT,'rtl',['options'=>['rtl'=>'راست به چپ','ltr'=>'چپ به راست'],'selectors'=>['{{WRAPPER}} .zc-pro'=>'direction:{{VALUE}};']]);
        $this->control('accent','رنگ تأکیدی',C::COLOR,'',['selectors'=>['{{WRAPPER}} .zc-pro'=>'--zc-pro-accent:{{VALUE}};']]);
        $this->end_controls_section();
        $parts=['section'=>['کل بخش','.zc-pro'],'heading'=>['عنوان بخش','.zc-pro-heading'],'intro'=>['توضیحات بخش','.zc-pro-intro'],'eyebrow'=>['نشان بالای عنوان','.zc-pro-eyebrow'],'card'=>['آیتم‌ها','.zc-pro-card'],'title'=>['عنوان آیتم','.zc-pro-title'],'text'=>['متن آیتم','.zc-pro-text'],'meta'=>['زیرعنوان آیتم','.zc-pro-meta'],'badge'=>['نشان آیتم','.zc-pro-badge'],'button'=>['دکمه‌ها','.zc-pro-button'],'icon'=>['آیکون‌ها','.zc-pro-icon'],'image'=>['تصاویر','.zc-pro-image'],'feature'=>['مزایا','.zc-pro-features'],'track'=>['مسیر امتیاز','.zc-pro-track'],'fill'=>['نوار امتیاز','.zc-pro-fill'],'value'=>['عدد امتیاز','.zc-pro-value'],'table_head'=>['سرستون جدول','.zc-pro-table thead th'],'table_cell'=>['سلول جدول','.zc-pro-table tbody td'],'row_title'=>['عنوان ویژگی','.zc-pro-table tbody th'],'compare'=>['مقایسه تصویری','.zc-pro-compare'],'caption'=>['زیرنویس','.zc-pro-caption'],'compare_label'=>['برچسب تصاویر','.zc-pro-compare-label'],'divider'=>['جداکننده','.zc-pro-divider'],'social'=>['پیوندهای اجتماعی','.zc-pro-social a']];
        $specific=['service_spotlight'=>['card','title','text','meta','badge','button','icon','image','feature'],'project_roadmap'=>['card','title','text','meta','badge','button','icon'],'seo_audit'=>['card','title','text','meta','badge','button','icon','track','fill','value'],'team_experts'=>['card','title','text','meta','badge','button','icon','image','social'],'service_matrix'=>['card','title','meta','button','table_head','table_cell','row_title'],'before_after'=>['compare','caption','compare_label','divider']];
        if($type==='service_matrix'){$parts['card']=['سلول‌های ردیف‌ها','.zc-pro-table tbody tr > *'];$parts['title'][0]='عنوان پکیج';}
        $parts=array_intersect_key($parts,array_flip(array_merge(['section','heading','intro','eyebrow'],$specific[$type])));
        foreach($parts as $id=>[$label,$sel]){
            $this->start_controls_section('style_'.$id,['label'=>$label,'tab'=>C::TAB_STYLE]);$target='{{WRAPPER}} '.$sel;
            if(!in_array($id,['image','track','fill','divider'],true))$this->add_group_control(Group_Control_Typography::get_type(),['name'=>$id.'_type','selector'=>$target]);
            $this->start_controls_tabs($id.'_tabs');
            foreach(['normal'=>'عادی','hover'=>'هاور'] as $state=>$name){
                $this->start_controls_tab($id.'_'.$state,['label'=>$name]);$selector=$target.($state==='hover'?':hover':'');
                if(!in_array($id,['image','track','fill','divider','compare'],true))$this->control($id.'_'.$state.'_color','رنگ متن',C::COLOR,'',['selectors'=>[$selector=>'color:{{VALUE}};']]);
                if(in_array($id,['section','card'],true))$this->add_group_control(Group_Control_Background::get_type(),['name'=>$id.'_'.$state.'_bg','types'=>['classic','gradient'],'exclude'=>Editor_Controls::BACKGROUND_EXCLUDE,'selector'=>$selector]);
                else $this->control($id.'_'.$state.'_bg','رنگ پس‌زمینه',C::COLOR,'',['selectors'=>[$selector=>'background-color:{{VALUE}};']]);
                $this->add_group_control(Group_Control_Border::get_type(),['name'=>$id.'_'.$state.'_border','selector'=>$selector]);
                $this->add_group_control(Group_Control_Box_Shadow::get_type(),['name'=>$id.'_'.$state.'_shadow','selector'=>$selector]);
                $this->end_controls_tab();
            }
            $this->end_controls_tabs();
            foreach(['padding'=>'فاصله داخلی','margin'=>'فاصله بیرونی','border-radius'=>'گردی'] as $property=>$label2)$this->add_responsive_control($id.'_'.str_replace('-','_',$property),['label'=>$label2,'type'=>C::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>[$target=>$property.':{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $this->end_controls_section();
        }
        if($type!=='service_matrix'){$this->start_controls_section('media_options',['label'=>'تنظیمات تصاویر']);
        $this->control('media_size','اندازه پیوست تصاویر',C::SELECT,'full',['options'=>['thumbnail'=>'بندانگشتی','medium'=>'متوسط','large'=>'بزرگ','full'=>'کامل']]);
        $this->end_controls_section();}
        if($type!=='service_matrix'){
            $this->start_controls_section('sizes',['label'=>'ابعاد تصویر و آیکون','tab'=>C::TAB_STYLE]);
            if(in_array($type,['service_spotlight','team_experts'],true))$this->add_responsive_control('image_height',['label'=>'ارتفاع تصویر','type'=>C::SLIDER,'range'=>['px'=>['min'=>30,'max'=>800]],'selectors'=>['{{WRAPPER}} .zc-pro-image'=>'height:{{SIZE}}{{UNIT}};']]);
            if(in_array($type,['service_spotlight','team_experts','before_after'],true))$this->control('image_fit','قرارگیری تصویر',C::SELECT,'cover',['options'=>['cover'=>'پوشش','contain'=>'در بر گرفتن'],'selectors'=>['{{WRAPPER}} '.($type==='before_after'?'.zc-pro-compare img':'.zc-pro-image')=>'object-fit:{{VALUE}};']]);
            if($type!=='before_after')$this->add_responsive_control('icon_size',['label'=>'اندازه آیکون','type'=>C::SLIDER,'range'=>['px'=>['min'=>10,'max'=>160]],'selectors'=>['{{WRAPPER}} .zc-pro-icon'=>'font-size:{{SIZE}}{{UNIT}};']]);
            if($type==='before_after')$this->add_responsive_control('comparison_height',['label'=>'ارتفاع مقایسه','type'=>C::SLIDER,'range'=>['px'=>['min'=>150,'max'=>1000]],'selectors'=>['{{WRAPPER}} .zc-pro-compare'=>'height:{{SIZE}}{{UNIT}};']]);
            $this->end_controls_section();
        }
    }
    /** Native inline editing key includes the repeater index, not its CSS _id. */
    protected function inline(string $key,string $toolbar='none'): string {
        $this->remove_render_attribute($key,'data-elementor-setting-key');
        $this->remove_render_attribute($key,'data-elementor-inline-editing-toolbar');
        $this->remove_render_attribute($key,'class','elementor-inline-editing');
        $this->add_inline_editing_attributes($key,$toolbar);return $this->get_render_attribute_string($key);
    }
    private function field(string $tag,string $class,string $value,string $key,bool $rich=false): string {
        $this->set_render_attribute($key,'class',$class);
        return '<'.$tag.' '.$this->inline($key,$rich?'advanced':'none').'>'.($rich?wp_kses_post($value):esc_html($value)).'</'.$tag.'>';
    }
    private function icon(array $icon): string {if(empty($icon['value']))return '';return '<span class="zc-pro-icon">'.Icon_Compatibility::render($icon,['aria-hidden'=>'true']).'</span>';}
    private function image(array $image,string $alt,string $class,string $size='full'):string {
        $attrs=['class'=>$class,'alt'=>$alt,'loading'=>'lazy','decoding'=>'async'];
        if(!empty($image['id']))return wp_get_attachment_image(absint($image['id']),in_array($size,['thumbnail','medium','large','full'],true)?$size:'full',false,$attrs);
        return empty($image['url'])?'':'<img class="'.esc_attr($class).'" src="'.esc_url($image['url']).'" alt="'.esc_attr($alt).'" loading="lazy" decoding="async">';
    }
    private function link(string $text,array $link,string $key): string {
        if($text===''||empty($link['url']))return '';
        $this->remove_render_attribute($key);$this->add_link_attributes($key,$link);
        $this->set_render_attribute($key,'class','zc-pro-button');
        return '<a '.$this->inline($key).'>'.esc_html($text).'</a>';
    }
    protected function render(): void {
        $s=$this->get_settings_for_display();$type=$this->type();$tag=in_array($s['tag']??'h2',['h1','h2','h3','div'],true)?$s['tag']:'h2';
        echo '<section class="zc-pro zc-pro-'.esc_attr($type).'" data-zc-pro="'.esc_attr($this->get_id()).'">';
        echo '<header class="zc-pro-header">';
        if(($s['show_eyebrow']??'yes')==='yes')echo $this->field('div','zc-pro-eyebrow',$s['eyebrow']??'','eyebrow');
        echo $this->field($tag,'zc-pro-heading',$s['title']??'','title');
        if(($s['show_intro']??'yes')==='yes')echo $this->field('div','zc-pro-intro',$s['intro']??'','intro',true);
        echo '</header>';
        if($type==='before_after')$this->comparison($s);
        elseif($type==='service_matrix')$this->matrix($s);
        else{
            echo '<div class="zc-pro-grid">';
            foreach($s['records']??[] as $index=>$row){
                $key='records.'.$index.'.';
                echo '<article class="zc-pro-card elementor-repeater-item-'.esc_attr($row['_id']).(!empty($row['highlight'])?' is-highlighted':'').'">';
                if(($s['show_images']??'yes')==='yes'&&!empty($row['image']['url']))echo $this->image($row['image'],$row['alt']??'','zc-pro-image',$s['media_size']??'full');
                if(($s['show_icons']??'yes')==='yes')echo $this->icon($row['icon']??[]);
                if(($row['badge']??'')!=='')echo $this->field('div','zc-pro-badge',$row['badge'],$key.'badge');
                if(($row['meta']??'')!=='')echo $this->field('div','zc-pro-meta',$row['meta'],$key.'meta');
                echo $this->field('h3','zc-pro-title',$row['title']??'',$key.'title');
                echo $this->field('div','zc-pro-text',$row['text']??'',$key.'text',true);
                if($type==='seo_audit'){
                    $value=max(0,min(100,(float)($row['value']??0)));
                    // Native progress track: same widget concept, independently implemented without waypoints.
                    echo '<div class="zc-pro-progress-content">';
                    if(($s['show_values']??'yes')==='yes')echo '<span class="zc-pro-value">'.esc_html((string)$value).'%</span>';
                    echo '</div><div class="zc-pro-track" role="progressbar" aria-label="'.esc_attr(wp_strip_all_tags($row['title']??'')).'" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.esc_attr((string)$value).'"><div class="zc-pro-fill" style="width:'.esc_attr((string)$value).'%"></div></div>';
                }
                if(!empty($row['features'])){
                    echo $this->field('div','zc-pro-features',$row['features'],$key.'features',true);
                }
                if($type==='team_experts'){
                    echo '<div class="zc-pro-social">';foreach(['linkedin','github','website'] as $social)echo $this->link($row[$social.'_label']??'',$row[$social.'_link']??[],$key.$social.'_label');echo '</div>';
                }
                if(($s['show_buttons']??'yes')==='yes')echo $this->link($row['button']??'',$row['link']??[],$key.'button');
                echo '</article>';
            }
            echo '</div>';
        }
        echo '</section>';
    }
    private function comparison(array $s): void {
        $position=max(0,min(100,(float)($s['position']??50)));
        echo '<figure class="zc-pro-comparison"><div class="zc-pro-compare" style="--zc-split:'.esc_attr((string)$position).'%">';
        foreach(['before','after'] as $side){echo '<div class="zc-pro-'.$side.'">';if(!empty($s[$side.'_image']['url']))echo $this->image($s[$side.'_image'],$s[$side.'_alt']??'','zc-pro-compare-image',$s['media_size']??'full');echo '</div>';}
        if(($s['show_labels']??'yes')==='yes')foreach(['before','after'] as $side)echo $this->field('span','zc-pro-compare-label zc-pro-label-'.$side,$s[$side.'_label']??'',$side.'_label');
        echo '<span class="zc-pro-divider" data-symbol="'.esc_attr($s['handle_symbol']??'↔').'" aria-hidden="true"></span><input class="zc-pro-range" type="range" min="0" max="100" value="'.esc_attr((string)$position).'" aria-label="'.esc_attr($s['range_label']??'').'" data-zc-compare></div>';
        echo $this->field('figcaption','zc-pro-caption',$s['caption']??'','caption');echo '</figure>';
    }
    private function matrix(array $s): void {
        echo '<div class="zc-pro-table-scroll" tabindex="0" role="region" aria-label="'.esc_attr($s['table_caption']??'').'"><table class="zc-pro-table"><caption class="screen-reader-text">'.esc_html($s['table_caption']??'').'</caption><thead><tr>';
        echo $this->field('th','zc-pro-cell',$s['feature_label']??'','feature_label');
        foreach([1,2,3] as $n){echo '<th scope="col">'.$this->field('div','zc-pro-title',$s['column_'.$n]??'','column_'.$n).$this->field('div','zc-pro-meta',$s['price_'.$n]??'','price_'.$n).'</th>';}
        echo '</tr></thead><tbody>';
        foreach($s['records']??[] as $i=>$row){echo '<tr class="zc-pro-card elementor-repeater-item-'.esc_attr($row['_id']).(!empty($row['highlight'])?' is-highlighted':'').'">';echo '<th scope="row" '.$this->inline('records.'.$i.'.title').'>'.esc_html($row['title']??'').'</th>';foreach([1,2,3] as $n)echo $this->field('td','zc-pro-cell',$row['column_'.$n]??'','records.'.$i.'.column_'.$n);echo '</tr>';}
        echo '</tbody><tfoot><tr><td></td>';foreach([1,2,3] as $n)echo '<td>'.$this->link($s['button_'.$n]??'',$s['link_'.$n]??[],'button_'.$n).'</td>';echo '</tr></tfoot></table></div>';
    }
}
class Widget_Service_Spotlight extends Professional_Widget{protected function type(): string{return 'service_spotlight';}}
class Widget_Project_Roadmap extends Professional_Widget{protected function type(): string{return 'project_roadmap';}}
class Widget_SEO_Audit extends Professional_Widget{protected function type(): string{return 'seo_audit';}}
class Widget_Before_After extends Professional_Widget{protected function type(): string{return 'before_after';}}
class Widget_Team_Experts extends Professional_Widget{protected function type(): string{return 'team_experts';}}
class Widget_Service_Matrix extends Professional_Widget{protected function type(): string{return 'service_matrix';}}
