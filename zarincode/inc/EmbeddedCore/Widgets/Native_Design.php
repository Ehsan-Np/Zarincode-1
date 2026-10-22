<?php
/** Settings-driven renderer of the approved original layouts. No DOM parsing at runtime.
 * Declarative templates are compiled by tools/build-native-models.php during development only.
 */
namespace Zarincode\Runtime260\Widgets;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
defined('ABSPATH') || exit;
final class Native_Design {
    public static function id(array $m,string $key):string{return 'n_'.$m['name'].'_'.$key;}
    public static function model(string $kind,string $section='about-page'):array {
        static $cache=[];$key=$kind.($kind==='page_section'?'_'.str_replace('-','_',$section):'');
        if(!preg_match('/^[a-z_]+$/D',$key))return [];
        $urls=[get_template_directory_uri(),untrailingslashit(home_url())];$cacheKey=$key.'|'.implode('|',$urls);
        if(!array_key_exists($cacheKey,$cache)){$path=__DIR__.'/models/'.$key.'.json';$raw=is_file($path)?json_decode(file_get_contents($path),true):[];$cache[$cacheKey]=self::portable($raw?:[],$urls);foreach($cache[$cacheKey]['parts']??[] as $part=>$def)$cache[$cacheKey]['parts'][$part]['label']=self::part_label($part,$def);}
        return $cache[$cacheKey];
    }
    private static function portable($value,array $urls){if(is_array($value)){foreach($value as $k=>$v)$value[$k]=self::portable($v,$urls);return $value;}return is_string($value)?str_replace(['{{THEME}}','{{HOME}}'],$urls,$value):$value;}
    /** Panel-only grouping. Never use visibility conditions for this UI filter: they discard active settings. */
    public static function editor_groups(array $m):array {
        $map=[];$labels=['general'=>'کلی'];
        $walk=function(array $nodes,string $group='general')use(&$walk,&$map,&$labels,$m){foreach($nodes as $node){
            if(isset($node['repeat']))continue;
            $classes=preg_split('/\s+/', $node['attrs']['class']??'');
            foreach($classes as $class)if(strpos($class,'zc-')===0&&!preg_match('/(?:container|grid|layout|white-card|glass-panel)$/',$class)){$group=$node['part']??$group;$labels[$group]=$m['parts'][$group]['label']??$class;break;}
            foreach(['text','rich','icon','image','alt','size','visible','heading_tag','action','action_target'] as $field)if(isset($node[$field])&&isset($m['fields'][$node[$field]]))$map[$node[$field]]=$group;
            foreach($node['bindings']??[] as $field)if(isset($m['fields'][$field]))$map[$field]=$group;
            if(isset($node['children']))$walk($node['children'],$group);
        }};$walk($m['tree']);
        $used=[];foreach($m['fields'] as $key=>$def){$g=$map[$key]??'general';$map[$key]=$g;$used[$g]=$labels[$g]??'کلی';}
        $default=array_key_first($used);foreach($map as $key=>$group)if(preg_match('/(?:hero_title|heading|brand_name).*text/',$key)){$default=$group;break;}
        return ['map'=>$map,'labels'=>$used,'default'=>$default];
    }
    private static function part_label(string $key,array $def=[]):string {
        $labels=['hero_main_card'=>'کارت معرفی آژانس','founder'=>'معرفی مدیر','hero_title'=>'عنوان اصلی','hero_inline_pill'=>'نام برند','hero_trust_list'=>'مزیت‌های همکاری','hero_cta_row'=>'دکمه‌های اقدام','crextio_progress'=>'نمودار فعالیت','dark_card'=>'کارت عملکرد و مدیر','yellow_card'=>'کارت شروع همکاری','badge_pill'=>'نشان بالای بخش','btn_yellow'=>'دکمه اصلی','btn_dark'=>'دکمه دوم','btn_glass'=>'دکمه فرعی','customer_photo'=>'تصویر مشتری','featured_image'=>'تصویر شاخص','service_title'=>'عنوان خدمت','service_desc'=>'شرح خدمت','service_num'=>'شماره خدمت','service_icon'=>'آیکون خدمت','product_price'=>'قیمت محصول','price_amount'=>'مبلغ','price_unit'=>'واحد پول','prod_badge'=>'نشان دسته‌بندی','prod_ver'=>'اطلاعات تکمیلی','meta_tag'=>'برچسب فنی','filter_tab'=>'فیلتر دسته‌بندی','pricing_features'=>'فهرست امکانات','trust_pill'=>'مزیت همکاری','heading'=>'عنوان','description'=>'توضیحات','footer'=>'پاورقی کارت','image'=>'تصویر','icon'=>'آیکون','link'=>'پیوند','title'=>'عنوان','text'=>'متن'];
        foreach($labels as $match=>$label)if(strpos($key,$match)!==false)return $label.(preg_match('/_(\d+)$/',$key,$n)?' '.$n[1]:'');
        return ['img'=>'تصویر','text'=>'متن','i'=>'آیکون','box'=>'ناحیه چیدمان'][$def['type']??'box']??'جزء چیدمان';
    }
    private static function query_kind(array $m):string {
        return $m['kind']==='page_section'?(['services-page'=>'services','projects-page'=>'portfolio'][$m['section']]??''):(in_array($m['kind'],['services','products','portfolio','blog'],true)?$m['kind']:'');
    }
    private static function categories(string $kind):array {
        static $cache=[];$taxonomy=['products'=>'product_cat','services'=>'zc_service_cat','portfolio'=>'zc_project_cat','blog'=>'category'][$kind]??'category';
        if(isset($cache[$taxonomy]))return $cache[$taxonomy];
        $terms=get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false]);$options=[];
        foreach(is_array($terms)?$terms:[] as $term)$options[$term->term_id]=str_repeat('— ',min(5,count(get_ancestors($term->term_id,$taxonomy,'taxonomy')))).$term->name;
        return $cache[$taxonomy]=$options;
    }
    private static function repeater_label(string $kind,string $key):string {
        if(strpos($key,'filter_tabs')!==false)return 'فیلترهای دسته‌بندی (حالت دستی)';
        if($kind==='pricing')return ['pricing_grid_items_1'=>'پلن‌های طراحی وب‌سایت','pricing_grid_items_2'=>'پلن‌های توسعه نرم‌افزار','pricing_grid_items_3'=>'پلن‌های سئو و شبکه‌های اجتماعی','pricing_grid_items_4'=>'پلن‌های تولید محتوا'][$key]??'پلن‌ها';
        return ['services'=>'کارت‌های خدمات','products'=>'کارت‌های محصولات (حالت دستی)','portfolio'=>'کارت‌های نمونه‌کار (حالت دستی)','blog'=>'کارت‌های مقالات (حالت دستی)','testimonials'=>'دیدگاه‌ها و تصویر مشتری','brands'=>'برندها و فناوری‌ها','steps'=>'مراحل انجام پروژه'][$kind]??'آیتم‌های بخش';
    }
    private static function editor_tab(string $key,array $def):string {
        if($def['type']==='switcher'||$def['type']==='select'||preg_match('/_(?:target|data_[a-z_]+|name)$/',$key))return 'options';
        if(in_array($def['type'],['icons','media'],true))return 'media';
        if($def['type']==='url')return 'links';return 'content';
    }
    private static function args(array $d,string $key=''):array {
        $label=$d['label'];
        $standard=['wysiwyg'=>'توضیحات','media'=>'تصویر','icons'=>'آیکون','url'=>'پیوند','switcher'=>'نمایش این جزء'];
        if(isset($standard[$d['type']]))$label=$standard[$d['type']];
        $purposes=['title'=>'عنوان','description'=>'توضیحات','price'=>'قیمت','currency'=>'واحد پول','category'=>'دسته‌بندی','demo_link'=>'پیوند پیش‌نمایش','link'=>'پیوند'];
        if(isset($purposes[$d['purpose']??'']))$label=$purposes[$d['purpose']];
        elseif(strpos($label,'متن:')===0)$label=self::part_label($key,['type'=>'text']);
        if($d['type']==='switcher')$label='نمایش '.self::part_label(preg_replace('/_visible$/','',$key));
        if($d['type']==='url')$label='پیوند '.self::part_label(preg_replace('/_href$/','',$key));
        if(strpos($key,'_data_filter')!==false)$label='کلید فیلتر دسته‌بندی';
        if(strpos($key,'_data_category')!==false)$label='کلید دسته‌بندی کارت';
        if($d['label']==='تصویر مشتری'||$d['label']==='تصویر شاخص')$label=$d['label'];
        $a=['label'=>$label,'type'=>$d['type'],'default'=>$d['default']];
        if(isset($d['description']))$a['description']=$d['description'];
        if(isset($d['options']))$a['options']=$d['options'];
        if(in_array($d['type'],['text','textarea','wysiwyg','media','url'],true))$a['dynamic']=['active'=>true];
        if($d['type']==='switcher')$a['return_value']='yes';
        return $a;
    }
    public static function register($widget,string $kind):void {
        $sections=$kind==='page_section'?Widget_Schema::sections():['about-page'=>Widget_Schema::definitions()[$kind][1]];
        foreach($sections as $section=>$name){
            $m=self::model($kind,$section);if(!$m)continue;
            $condition=['design_mode'=>'original'];if($kind==='page_section')$condition['section_key']=$section;
            $queryKind=self::query_kind($m);
            if($queryKind!==''){
                $widget->start_controls_section(self::id($m,'source_section'),['label'=>'منبع داده و نمایش خودکار','condition'=>$condition]);
                $widget->add_control(self::id($m,'source'),['label'=>'منبع داده','type'=>C::SELECT,'default'=>$kind==='page_section'?'query':'manual','options'=>['manual'=>'آیتم‌های دستی همین طرح','query'=>['products'=>'محصولات منتشرشده فروشگاه','services'=>'خدمات منتشرشده','portfolio'=>'پروژه‌های منتشرشده','blog'=>'نوشته‌های منتشرشده'][$queryKind]]]);
                $widget->add_control(self::id($m,'show_all'),['label'=>'نمایش تمام موارد منتشرشده','type'=>C::SWITCHER,'return_value'=>'yes','default'=>'','description'=>'در فروشگاه‌های بزرگ، تعداد محدود برای سرعت بهتر توصیه می‌شود.','condition'=>[self::id($m,'source')=>'query']]);
                $widget->add_control(self::id($m,'count'),['label'=>'تعداد نتایج','type'=>C::NUMBER,'default'=>8,'min'=>1,'max'=>100,'condition'=>[self::id($m,'source')=>'query',self::id($m,'show_all')=>'']]);
                $widget->add_control(self::id($m,'category_ids'),['label'=>'دسته‌بندی‌های موردنظر','type'=>C::SELECT2,'multiple'=>true,'options'=>self::categories($queryKind),'description'=>'خالی یعنی همه دسته‌بندی‌ها؛ انتخاب دسته مادر شامل فرزندان آن نیز می‌شود.','condition'=>[self::id($m,'source')=>'query']]);
                foreach(['include_ids'=>'شناسه رکوردها (با کاما)'] as $key=>$label)$widget->add_control(self::id($m,$key),['label'=>$label,'type'=>C::TEXT,'condition'=>[self::id($m,'source')=>'query']]);
                $widget->add_control(self::id($m,'orderby'),['label'=>'مرتب‌سازی بر اساس','type'=>C::SELECT,'default'=>'date','options'=>['date'=>'تاریخ','title'=>'عنوان','menu_order'=>'ترتیب دستی','rand'=>'تصادفی'],'condition'=>[self::id($m,'source')=>'query']]);
                $widget->add_control(self::id($m,'order'),['label'=>'جهت مرتب‌سازی','type'=>C::SELECT,'default'=>'DESC','options'=>['DESC'=>'نزولی','ASC'=>'صعودی'],'condition'=>[self::id($m,'source')=>'query']]);
                $widget->add_control(self::id($m,'query_help'),['type'=>C::RAW_HTML,'raw'=>'در حالت خودکار عنوان، توضیح، پیوند، دسته و قیمت از رکوردهای سایت خوانده می‌شوند؛ برای ویرایش آن‌ها همان محصول/نوشته را ویرایش کنید. اولین کارت دستی الگوی ظاهر نتایج است.','condition'=>[self::id($m,'source')=>'query']]);
                $widget->end_controls_section();
            }
            $groups=self::editor_groups($m);
            $widget->start_controls_section(self::id($m,'section_content'),['label'=>$name.' — ویرایش محتوا','condition'=>$condition]);
            $widget->add_control(self::id($m,'editor_part'),['label'=>'کدام قسمت را ویرایش می‌کنید؟','type'=>C::SELECT2,'options'=>array_merge(['__all__'=>'همه اجزا'],$groups['labels']),'default'=>$groups['default'],'render_type'=>'none','classes'=>'zc-editor-part-picker zc-editor-model-'.$m['name'],'label_block'=>true]);
            $widget->add_control(self::id($m,'editor_search'),['label'=>'جستجوی کنترل','type'=>C::TEXT,'default'=>'','render_type'=>'none','classes'=>'zc-editor-field-search zc-editor-model-'.$m['name'],'placeholder'=>'جستجو در نام کنترل‌های این بخش']);
            $widget->add_control(self::id($m,'editor_help'),['type'=>C::RAW_HTML,'raw'=>'قسمت را انتخاب و متن یا رسانه دلخواه خود را جایگزین کنید. فیلتر فقط پنل را خلوت می‌کند؛ چیزی از محتوا حذف نمی‌شود.','content_classes'=>'zc-editor-help']);
            $widget->start_controls_tabs(self::id($m,'content_tabs'));
            foreach(['content'=>'متن','media'=>'رسانه و نماد','links'=>'پیوند','options'=>'پیشرفته'] as $tab=>$label){
                $widget->start_controls_tab(self::id($m,'tab_'.$tab),['label'=>$label]);
                foreach($m['fields'] as $key=>$d)if(self::editor_tab($key,$d)===$tab){$args=self::args($d,$key);$args['classes']='zc-editor-field zc-editor-model-'.$m['name'].' zc-editor-tab-'.$tab.' zc-editor-group-'.sanitize_html_class($groups['map'][$key]);$widget->add_control(self::id($m,$key),$args);}
                $widget->end_controls_tab();
            }
            $widget->end_controls_tabs();$widget->end_controls_section();
            foreach($m['repeaters'] as $key=>$rep){
                $widget->start_controls_section(self::id($m,'section_'.$key),['label'=>self::repeater_label($kind,$key),'condition'=>$condition]);
                $r=new Repeater();$options=[];foreach($rep['variants'] as $v=>$template)$options[$v]='ساختار '.substr($v,1);
                $r->start_controls_tabs('zc_row_tabs');
                foreach(['content'=>'محتوا','media'=>'رسانه و نماد','links'=>'پیوند','options'=>'پیشرفته'] as $tab=>$label){
                    $r->start_controls_tab('zc_row_'.$tab,['label'=>$label]);
                    if($tab==='options'){
                        $r->add_control('_variant',['label'=>'ساختار آیتم','type'=>C::SELECT,'options'=>$options,'default'=>array_key_first($options)]);
                        $r->add_control('_enabled',['label'=>'نمایش آیتم','type'=>C::SWITCHER,'default'=>'yes','return_value'=>'yes']);
                    }
                    foreach($rep['fields'] as $field=>$d)if(self::editor_tab($field,$d)===$tab){$a=self::args($d,$field);if(count($d['variants']??[])!==count($options))$a['condition']=['_variant'=>$d['variants']??array_keys($options)];if(!empty($d['legacy_feature']))$a['condition']=array_merge($a['condition']??[],['features_source'=>'legacy']);$r->add_control($field,$a);}
                    if($tab==='options'){
                foreach(['item_background'=>'پس‌زمینه آیتم','item_color'=>'رنگ متن آیتم'] as $f=>$label)$r->add_control($f,['label'=>$label,'type'=>C::COLOR,'selectors'=>['{{WRAPPER}} .zc-native-layout {{CURRENT_ITEM}}'=>($f==='item_color'?'color':'background-color').':{{VALUE}};']]);
                $r->add_responsive_control('item_padding',['label'=>'فاصله داخلی آیتم','type'=>C::DIMENSIONS,'selectors'=>['{{WRAPPER}} .zc-native-layout {{CURRENT_ITEM}}'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
                    }
                    $r->end_controls_tab();
                }
                $r->end_controls_tabs();
                $title='';foreach($rep['fields'] as $f=>$d)if($d['type']==='text'&&strpos($f,'text')!==false){$title=$f;if(($d['purpose']??'')==='title'||strpos($f,'title')!==false||strpos($f,'heading')!==false)break;}
                foreach($rep['fields'] as $f=>$d)if(($d['purpose']??'')==='title'){$title=$f;break;}
                $widget->add_control(self::id($m,$key),['label'=>self::repeater_label($kind,$key),'type'=>C::REPEATER,'fields'=>$r->get_controls(),'default'=>$rep['default'],'prevent_empty'=>false,'title_field'=>$title?'{{{ '.$title.' }}}':'آیتم']);
                $widget->end_controls_section();
            }
            if($kind==='pricing') Pricing_Features::controls($widget,$m,$condition);
            if(!empty($m['has_form'])){
                $widget->start_controls_section(self::id($m,'form_section'),['label'=>'اتصال فرم تماس','condition'=>$condition]);
                $widget->add_control(self::id($m,'form_shortcode'),['label'=>'شورت‌کد فرم واقعی','type'=>C::TEXTAREA,'description'=>'شورت‌کد افزونه فرم نصب‌شده را وارد کنید تا جایگزین فرم نمایشی شود. ارسال، اعتبارسنجی و ضداسپم را همان افزونه مدیریت می‌کند.']);
                $widget->add_control(self::id($m,'form_notice'),['label'=>'پیام فرم نمایشی','type'=>C::TEXT,'default'=>'این فرم نمایشی است؛ برای ارسال واقعی، شورت‌کد فرم را در تنظیمات ویجت وارد کنید.']);
                $widget->end_controls_section();
            }
            if($queryKind!==''||$kind==='testimonials'){
                $widget->start_controls_section(self::id($m,'image_style'),['label'=>'تصاویر و برش استاندارد','tab'=>C::TAB_STYLE,'condition'=>$condition]);
                if($kind==='testimonials')$widget->add_responsive_control(self::id($m,'avatar_size'),['label'=>'اندازه تصویر مشتری','type'=>C::SLIDER,'range'=>['px'=>['min'=>32,'max'=>160]],'default'=>['size'=>64,'unit'=>'px'],'selectors'=>['{{WRAPPER}} .zc-customer-photo'=>'width:{{SIZE}}px;height:{{SIZE}}px;']]);
                else {
                    $widget->add_control(self::id($m,'image_ratio'),['label'=>'نسبت ابعاد تصویر شاخص','type'=>C::SELECT,'default'=>'8/5','options'=>['8/5'=>'استاندارد ۹۶۰ × ۶۰۰','16/9'=>'عریض ۱۶:۹','4/3'=>'کلاسیک ۴:۳','1/1'=>'مربع ۱:۱'],'selectors'=>['{{WRAPPER}} .zc-record-image'=>'aspect-ratio:{{VALUE}};']]);
                    $widget->add_control(self::id($m,'image_fit'),['label'=>'شیوه نمایش تصویر','type'=>C::SELECT,'default'=>'cover','options'=>['cover'=>'پوشش بدون تغییر تناسب','contain'=>'نمایش کامل بدون برش'],'selectors'=>['{{WRAPPER}} .zc-record-image'=>'object-fit:{{VALUE}};']]);
                }
                $widget->end_controls_section();
            }
            self::styles($widget,$m,$condition,$name);
        }
    }
    private static function styles($widget,array $m,array $condition,string $name):void {
        $widget->start_controls_section(self::id($m,'style_section'),['label'=>$name.' — استایل تمام اجزا','tab'=>C::TAB_STYLE,'condition'=>$condition]);
        $widget->add_control(self::id($m,'style_help'),['type'=>C::RAW_HTML,'raw'=>'یک قانون اضافه کنید، جزء موردنظر را انتخاب و استایل آن را تنظیم کنید. شماره آیتم ۰ یعنی همه آیتم‌ها. این استایل مستقیماً روی همان عنصر طرح اصلی اعمال می‌شود.']);
        $r=new Repeater();$parts=[];$text=[];$images=[];foreach($m['parts'] as $p=>$d){$parts[$p]=$d['label'];if($d['type']!=='img')$text[]=$p;else $images[]=$p;}
        $r->add_control('target',['label'=>'جزء موردنظر','type'=>C::SELECT2,'options'=>$parts,'default'=>array_key_first($parts)]);
        $r->add_control('item',['label'=>'شماره آیتم (۰ = همه)','type'=>C::NUMBER,'min'=>0,'default'=>0]);
        $selector='{{WRAPPER}} .zc-native-layout {{CURRENT_ITEM}}';
        $r->start_controls_tabs('zc_style_tabs');$r->start_controls_tab('zc_style_text',['label'=>'متن']);
        $r->add_group_control(Group_Control_Typography::get_type(),['name'=>'font','selector'=>$selector]);
        foreach(['normal'=>'عادی','hover'=>'هاور'] as $state=>$label){$sel=$selector.($state==='hover'?':hover':'');
            $r->add_control($state.'_color',['label'=>'رنگ متن — '.$label,'type'=>C::COLOR,'selectors'=>[$sel=>'color:{{VALUE}};']]);
        }
        $r->end_controls_tab();$r->start_controls_tab('zc_style_appearance',['label'=>'ظاهر']);
        foreach(['normal'=>'عادی','hover'=>'هاور'] as $state=>$label){$sel=$selector.($state==='hover'?':hover':'');
            $r->add_group_control(Group_Control_Background::get_type(),['name'=>$state.'_background','types'=>['classic','gradient'],'exclude'=>Editor_Controls::BACKGROUND_EXCLUDE,'selector'=>$sel]);
            $r->add_group_control(Group_Control_Border::get_type(),['name'=>$state.'_border','selector'=>$sel]);
            $r->add_group_control(Group_Control_Box_Shadow::get_type(),['name'=>$state.'_shadow','selector'=>$sel]);
        }
        $r->end_controls_tab();$r->start_controls_tab('zc_style_layout',['label'=>'چیدمان']);
        foreach(['padding'=>'فاصله داخلی','margin'=>'فاصله بیرونی','border-radius'=>'گردی'] as $prop=>$label)$r->add_responsive_control(str_replace('-','_',$prop),['label'=>$label,'type'=>C::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>[$selector=>$prop.':{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        foreach(['width'=>'عرض','height'=>'ارتفاع','font-size'=>'اندازه متن/آیکون','gap'=>'فاصله فرزندان'] as $prop=>$label)$r->add_responsive_control(str_replace('-','_',$prop),['label'=>$label,'type'=>C::SLIDER,'size_units'=>['px','%','em','vw'],'range'=>['px'=>['min'=>0,'max'=>1500]],'selectors'=>[$selector=>$prop.':{{SIZE}}{{UNIT}};']]);
        $r->add_responsive_control('align',['label'=>'تراز متن','type'=>C::SELECT,'options'=>['right'=>'راست','center'=>'وسط','left'=>'چپ'],'selectors'=>[$selector=>'text-align:{{VALUE}};']]);
        foreach(['display'=>['block'=>'بلوک','inline-block'=>'بلوک درون‌خطی','flex'=>'فلکس','inline-flex'=>'فلکس درون‌خطی','grid'=>'گرید','none'=>'مخفی'],'flex_direction'=>['row'=>'ردیفی','row-reverse'=>'ردیف معکوس','column'=>'ستونی','column-reverse'=>'ستون معکوس'],'justify_content'=>['flex-start'=>'شروع','center'=>'وسط','flex-end'=>'انتها','space-between'=>'فاصله بین','space-around'=>'فاصله پیرامون'],'align_items'=>['stretch'=>'کشیده','flex-start'=>'شروع','center'=>'وسط','flex-end'=>'انتها'],'flex_wrap'=>['wrap'=>'چندخطی','nowrap'=>'تک‌خطی']] as $property=>$options)$r->add_responsive_control($property,['label'=>['display'=>'نمایش / نوع چیدمان','flex_direction'=>'جهت چیدمان','justify_content'=>'توزیع افقی','align_items'=>'تراز فرزندان','flex_wrap'=>'شکستن خط'][$property],'type'=>C::SELECT,'options'=>$options,'selectors'=>[$selector=>str_replace('_','-',$property).':{{VALUE}};']]);
        $r->add_responsive_control('grid_columns',['label'=>'تعداد ستون‌های گرید','type'=>C::NUMBER,'min'=>1,'max'=>12,'selectors'=>[$selector=>'grid-template-columns:repeat({{VALUE}},minmax(0,1fr));']]);
        $r->add_responsive_control('order',['label'=>'ترتیب جزء در فلکس/گرید','type'=>C::NUMBER,'selectors'=>[$selector=>'order:{{VALUE}};']]);
        $r->add_responsive_control('position',['label'=>'موقعیت','type'=>C::SELECT,'options'=>['static'=>'عادی','relative'=>'نسبی','absolute'=>'مطلق','sticky'=>'چسبان'],'selectors'=>[$selector=>'position:{{VALUE}};']]);
        foreach(['top'=>'بالا','right'=>'راست','bottom'=>'پایین','left'=>'چپ'] as $edge=>$label)$r->add_responsive_control('offset_'.$edge,['label'=>'فاصله از '.$label,'type'=>C::SLIDER,'size_units'=>['px','%','em'],'selectors'=>[$selector=>$edge.':{{SIZE}}{{UNIT}};']]);
        $r->add_control('z_index',['label'=>'ترتیب لایه','type'=>C::NUMBER,'selectors'=>[$selector=>'z-index:{{VALUE}};']]);
        if($images)$r->add_control('fit',['label'=>'قرارگیری تصویر','type'=>C::SELECT,'options'=>['cover'=>'پوشش','contain'=>'در بر گرفتن','fill'=>'کشیده','none'=>'اصلی'],'selectors'=>[$selector=>'object-fit:{{VALUE}};']]);
        $r->end_controls_tab();$r->end_controls_tabs();
        $widget->add_control(self::id($m,'styles'),['label'=>'قوانین استایل اجزا','type'=>C::REPEATER,'fields'=>$r->get_controls(),'default'=>[],'prevent_empty'=>false,'title_field'=>'{{{ target }}}']);
        $widget->end_controls_section();
    }
    /** Migrate old saved text values in memory, before Elementor exports settings. No database writes. */
    public static function migrate(array $settings,array $raw,string $kind):array {
        $section=is_string($raw['section_key']??null)?$raw['section_key']:'about-page';$m=self::model($kind,$section);if(!$m)return $settings;
        $sourceKey=self::id($m,'source');if(self::query_kind($m)!==''&&!array_key_exists($sourceKey,$raw))foreach($m['repeaters'] as $key=>$rep)if(strpos($key,'grid_items')!==false&&array_key_exists(self::id($m,$key),$raw)){$settings[$sourceKey]='manual';break;}
        $categoryKey=self::id($m,'category_ids');if(isset($raw[$categoryKey])&&is_string($raw[$categoryKey]))$settings[$categoryKey]=array_values(array_filter(array_map('absint',explode(',',$raw[$categoryKey]))));
        $old=$kind==='page_section'?'original_texts_'.str_replace('-','_',$section):'original_texts';
        foreach($raw[$old]??[] as $row){if(!is_array($row)||!is_string($row['path']??null))continue;$mapping=$m['legacy'][$row['path']]??null;if(!$mapping||($row['baseline']??'')!==$mapping['baseline']||!is_scalar($row['text']??null))continue;
            [$group,$field,$index]=$mapping['target'];$value=(string)$row['text'];
            if($group===''){$key=self::id($m,$field);if(array_key_exists($key,$raw))continue;$type=$m['fields'][$field]['type']??'text';$settings[$key]=$type==='wysiwyg'?wp_kses_post($value):html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES,'UTF-8');}
            else{$key=self::id($m,$group);if(array_key_exists($key,$raw))continue;if(!isset($settings[$key]))$settings[$key]=$m['repeaters'][$group]['default'];if(isset($settings[$key][$index]))$settings[$key][$index][$field]=$value;}
        }
        return $settings;
    }
    private static function attrs(array $attrs):string {$out='';foreach($attrs as $key=>$value)if(is_scalar($value)&&preg_match('/^[a-zA-Z][a-zA-Z0-9:_-]*$/D',$key)&&stripos($key,'on')!==0)$out.=' '.esc_attr($key).'="'.esc_attr((string)$value).'"';return $out;}
    private static function link($widget,array $link):array {
        $custom=is_string($link['custom_attributes']??null)?$link['custom_attributes']:'';unset($link['custom_attributes']);$link['url']=esc_url(is_scalar($link['url']??'')?(string)($link['url']??''):'');
        $widget->remove_render_attribute('_zc_native_link');$widget->add_link_attributes('_zc_native_link',$link);
        $attrs=array_map(static fn($values)=>implode(' ',$values),$widget->get_render_attributes('_zc_native_link')??[]);
        if(!empty($link['is_external']))$attrs['rel']=trim(($attrs['rel']??'').' noreferrer');
        foreach(\Elementor\Utils::parse_custom_attributes($custom) as $key=>$value)if(!in_array(strtolower($key),['href','class','style','id','target','rel'],true)&&stripos($key,'on')!==0)$attrs[$key]=$value;
        return $attrs;
    }
    private static function inline($widget,string $key,string $toolbar='none'):array{return $widget->inline_attributes($key,$toolbar);}
    public static function render($widget,array $settings,string $kind):string {
        $m=self::model($kind,is_string($settings['section_key']??null)?$settings['section_key']:'about-page');if(!$m)return '';
        $values=[];foreach($m['fields'] as $key=>$d)$values[$key]=$settings[self::id($m,$key)]??$d['default'];
        $reps=[];foreach($m['repeaters'] as $key=>$rep)$reps[$key]=array_key_exists(self::id($m,$key),$settings)?(is_array($settings[self::id($m,$key)])?$settings[self::id($m,$key)]:[]):$rep['default'];
        if(($settings[self::id($m,'source')]??'manual')==='query')$reps=self::query($m,$reps,$settings);
        $formShortcode=$settings[self::id($m,'form_shortcode')]??'';$formNotice=$settings[self::id($m,'form_notice')]??'این فرم نمایشی است؛ برای ارسال واقعی، شورت‌کد فرم را در تنظیمات ویجت وارد کنید.';$queryActive=($settings[self::id($m,'source')]??'manual')==='query';$rules=is_array($settings[self::id($m,'styles')]??null)?$settings[self::id($m,'styles')]:[];$css=[];$instance=sanitize_html_class($widget->get_id());$idMap=[];$collect=function($nodes)use(&$collect,&$idMap,$instance){foreach($nodes as $n){if(isset($n['attrs']['id']))$idMap[$n['attrs']['id']]='zc-'.$instance.'-'.$n['attrs']['id'];if(isset($n['children']))$collect($n['children']);}};$collect($m['tree']);foreach($m['repeaters'] as $rep)$collect(array_values($rep['variants']));$scope='.zc-native-'.esc_attr($m['name']).'[data-zc-native-id="'.$instance.'"]';
        $draw=function(array $node,array $data,string $prefix='',int $index=0,string $variant='',string $recordId='')use(&$draw,$widget,$m,$reps,$rules,&$css,$scope,$instance,$idMap,$queryActive,$formShortcode,$formNotice,$settings):string{
            if(isset($node['feature_list'])&&($data['features_source']??'legacy')==='icon_list')return Pricing_Features::render($widget,$m,$settings??[],(string)(($data['plan_key']??'')?:($data['_id']??'')));
            if(!empty($node['fallback_image_for'])&&!empty($data[$node['fallback_image_for']]['url']))return '';
            if(isset($node['literal']))return $node['literal'];
            if(isset($node['repeat'])){$g=$node['repeat'];if($queryActive&&strpos($g,'grid_items')!==false&&empty($reps[$g]))return '<p class="zc-query-empty" role="status">مورد منتشرشده‌ای برای نمایش موجود نیست.</p>';$html='';foreach($reps[$g]??[] as $i=>$row){if(!is_array($row)||($row['_enabled']??'yes')!=='yes')continue;$v=is_string($row['_variant']??null)?$row['_variant']:array_key_first($m['repeaters'][$g]['variants']);$template=$m['repeaters'][$g]['variants'][$v]??reset($m['repeaters'][$g]['variants']);$html.=$draw($template,$row,self::id($m,$g).'.'.$i.'.',$i+1,$g.'-'.$v,$row['_id']??'');}return $html;}
            $part=$node['part']??'';$classes=['zc-part-'.$part];foreach($rules as $r)if(is_array($r)&&($r['target']??'')===$part&&(!(int)($r['item']??0)||(int)$r['item']===$index)&&!empty($r['_id']))$classes[]='elementor-repeater-item-'.sanitize_html_class($r['_id']);
            if(isset($node['text'])){if(!empty($node['commerce_price'])&&isset($data['_price_html']))return wp_kses_post($data['_price_html']);if(!empty($node['commerce_currency'])&&isset($data['_price_html']))return '';$key=$node['text'];$value=$data[$key]??'';if(!empty($node['plain']))return esc_html(is_scalar($value)?(string)$value:'');$attrs=$queryActive&&$prefix!==''?[]:self::inline($widget,$prefix!==''?$prefix.$key:self::id($m,$key));$attrs=array_map(static fn($a)=>implode(' ',$a),$attrs);$attrs['class']=trim(implode(' ',$classes).' '.($attrs['class']??''));return ($node['before']??'').'<zc-text'.self::attrs($attrs).'>'.esc_html(is_scalar($value)?(string)$value:'').'</zc-text>'.($node['after']??'');}
            if(($data[$node['visible']??'']??'yes')!=='yes')return '';
            $tag=$node['tag'];if(isset($node['heading_tag'])){$selected=$data[$node['heading_tag']]??$tag;if(in_array($selected,['h1','h2','h3','h4','h5','h6','div'],true))$tag=$selected;}
            $attrs=$node['attrs'];$style=$attrs['style']??'';unset($attrs['style']);
            if($variant!==''){$classes[]='zc-v-'.$variant;}if($recordId!=='')$classes[]='elementor-repeater-item-'.sanitize_html_class($recordId);
            $attrs['class']=trim(($attrs['class']??'').' '.implode(' ',$classes));
            if($style!==''){$selector=$scope.($variant!==''?' .zc-v-'.$variant:'').' .zc-part-'.$part;$rootSelector=$scope.' .zc-part-'.$part.($variant!==''?'.zc-v-'.$variant:'');$css[$selector.','.$rootSelector]=$style;}
            // Namespace only local targets; shared theme modals remain shared by design.
            if(isset($attrs['id'])){$attrs['data-zc-original-id']=$attrs['id'];$attrs['id']='zc-'.$instance.'-'.$attrs['id'];}
            foreach($node['bindings']??[] as $attr=>$field){$v=$data[$field]??'';
                if(in_array($attr,['href','action'],true)){$link=is_array($v)?$v:['url'=>$v];$linkAttrs=self::link($widget,$link);if($attr==='action'){$linkAttrs['action']=$linkAttrs['href']??'';unset($linkAttrs['href'],$linkAttrs['target'],$linkAttrs['rel']);}$attrs=array_merge($attrs,$linkAttrs);
                }elseif(is_scalar($v))$attrs[$attr]=(string)$v;
            }
            if($queryActive&&isset($attrs['data-filter'])){
                $active=in_array($attrs['data-filter'],['all','*',''],true);
                $attrs['class']=trim(preg_replace('/(?:^|\s)is-active(?=\s|$)/','',$attrs['class']).($active?' is-active':''));$attrs['aria-pressed']=$active?'true':'false';
            }
            if(isset($node['action'])){
                $action=$data[$node['action']]??'none';$target=is_scalar($data[$node['action_target']]??'')?(string)($data[$node['action_target']]??''):'';
                if(in_array($action,['modal','drawer'],true)){$attrs['data-zc-'.$action.'-open']=$idMap[$target]??$target;unset($attrs['href'],$attrs['target'],$attrs['rel']);}
                elseif($action==='url'&&$tag==='button'){$tag='a';unset($attrs['type']);}
                elseif($action==='none')unset($attrs['href']);
            }
            if($tag==='form'&&$formShortcode!=='')return do_shortcode($formShortcode);
            foreach(['data-zc-tabs','href','aria-controls','for'] as $a)if(isset($attrs[$a])&&is_string($attrs[$a])){foreach($idMap as $old=>$new){if($attrs[$a]==='#'.$old)$attrs[$a]='#'.$new;elseif($attrs[$a]===$old)$attrs[$a]=$new;}}
            if(isset($node['icon'])){if(($data[$node['icon']]['value']??'')==='')return '';return Icon_Compatibility::render($data[$node['icon']],$attrs);}
            if(isset($node['image'])){$image=$data[$node['image']]??[];$attrs['alt']=$data[$node['alt']??'']??($image['alt']??(!empty($image['id'])?get_post_meta(absint($image['id']),'_wp_attachment_image_alt',true):''));if(!empty($image['id']))return wp_get_attachment_image(absint($image['id']),$node['image_preset']??($data[$node['size']??'']??'full'),false,$attrs);if(empty($image['url']))return '';$attrs['src']=esc_url($image['url']);return '<img'.self::attrs($attrs).'>';}
            $html='';if(isset($node['rich'])){$key=$node['rich'];$inline=$queryActive&&$prefix!==''?[]:self::inline($widget,$prefix!==''?$prefix.$key:self::id($m,$key),'basic');foreach($inline as $a=>$vals)$attrs[$a]=trim(($attrs[$a]??'').' '.implode(' ',$vals));$richValue=is_scalar($data[$key]??'')?(string)($data[$key]??''):'';$richValue=preg_replace('/<\/(p|div)>\s*<(p|div)(?:\s[^>]*)?>/i','<br><br>',$richValue);$html=wp_kses($richValue,['strong'=>[],'b'=>[],'em'=>[],'i'=>[],'u'=>[],'s'=>[],'code'=>[],'br'=>[],'span'=>['style'=>true],'a'=>['href'=>true,'target'=>true,'rel'=>true]]);}
            else foreach($node['children']??[] as $child)$html.=$draw($child,$data,$prefix,$index,$variant);
            if($tag==='form')$html.='<div class="zc-form-feedback" role="status" data-native-form-message hidden>'.esc_html($formNotice).'</div>';
            if(in_array($tag,['input','br','hr','source','wbr'],true))return '<'.$tag.self::attrs($attrs).'>';
            return '<'.$tag.self::attrs($attrs).'>'.$html.'</'.$tag.'>';
        };
        $html='';foreach($m['tree'] as $node)$html.=$draw($node,$values);
        $sheet='';foreach($css as $selector=>$declarations)$sheet.=$selector.'{'.$declarations.'}';
        return '<style class="zc-native-defaults">'.$sheet.'</style><div class="zc-native-layout zc-native-'.esc_attr($m['name']).'" data-zc-native-id="'.esc_attr($instance).'">'.$html.'</div>';
    }
    private static function query(array $m,array $reps,array $s):array {
        $kind=self::query_kind($m);if($kind==='')return $reps;
        $g='';foreach(array_keys($reps) as $candidate)if(strpos($candidate,'grid_items')!==false){$g=$candidate;break;}if(!$g)return $reps;
        $base=$reps[$g][0]??$m['repeaters'][$g]['default'][0]??[];
        $items=Editable_Renderer::query_items($kind,['count'=>$s[self::id($m,'count')]??8,'show_all'=>$s[self::id($m,'show_all')]??'','category_ids'=>$s[self::id($m,'category_ids')]??'','include_ids'=>$s[self::id($m,'include_ids')]??'','orderby'=>$s[self::id($m,'orderby')]??'date','order'=>$s[self::id($m,'order')]??'DESC']);$rows=[];$categories=[];
        foreach($items as $i=>$item){
            $row=$base;$row['_enabled']='yes';$row['_price_html']=$item['price_html']??null;$row['_id']=substr(hash('sha256',$g.$item['_id']),0,8);
            foreach($m['repeaters'][$g]['fields'] as $key=>$def){
                switch($def['purpose']??''){
                    case 'title':$row[$key]=$item['title'];break;
                    case 'description':$row[$key]=$item['description'];break;
                    case 'category':$row[$key]=$item['category'];break;
                    case 'link':$row[$key]=$item['link'];break;
                    case 'demo_link':$row[$key]=$item['demo_link']??['url'=>''];break;
                    case 'price':$row[$key]=isset($item['price_amount'])?number_format_i18n((float)$item['price_amount'],function_exists('wc_get_price_decimals')?wc_get_price_decimals():0):'';break;
                    case 'currency':$row[$key]=$item['price_currency']??'';break;
                }
                if(strpos($key,'_data_category')!==false)$row[$key]=$item['category_slug'];
                if($def['type']==='media')$row[$key]=$item['image'];
            }
            $meta=$item['record_meta']??[];$get=static fn($key)=>(string)($meta[$key][0]??'');
            if($kind==='blog'){$row['prod_ver_text_1']=$item['date'];$row['span_text_1']=$item['author'];}
            if($kind==='products'){
                $row['prod_ver_text_1']=$get('_zc_prod_version')?:$get('_zc_prod_ver');
                $tags=explode(',',$get('_zc_prod_tags'));foreach(range(1,4) as $tag)$row['meta_tag_text_'.$tag]=trim($tags[$tag-1]??'');
            }
            if($kind==='portfolio'){
                foreach(['prod_ver_text_1'=>'_zc_prj_metrics','span_text_1'=>'_zc_prj_client','span_text_2'=>'_zc_prj_date','span_text_3'=>'_zc_prj_duration','span_text_4'=>'_zc_prj_plan'] as $field=>$key)$row[$field]=$get($key);
            }
            if($kind==='services'){
                $row['service_num_text_1']=sprintf('%02d',$i+1);$row['span_text_1']=$get('_zc_srv_price_range');
                $icon=$get('_zc_srv_icon');if($icon!=='')$row['icon_1_icon']=Icon_Compatibility::value(['value'=>$icon,'library'=>'fa-solid']);
            }
            $rows[]=$row;foreach($item['filter_terms']??[] as $slug=>$label)$categories[$slug]=$label;
        }
        foreach($reps as $filterGroup=>$filterRows)if(strpos($filterGroup,'filter_tabs_items')!==false){
            $filterBase=$filterRows[0]??$m['repeaters'][$filterGroup]['default'][0]??[];$filterBase['_enabled']='yes';$all=$filterBase;foreach($m['repeaters'][$filterGroup]['fields'] as $key=>$def){if(strpos($key,'_data_filter')!==false)$all[$key]='all';elseif($def['type']==='text'&&strpos($key,'_text')!==false)$all[$key]='همه';}$filterRows=$filterBase?[$all]:[];
            foreach($categories as $slug=>$label){$r=$filterBase;$r['_id']=substr(hash('sha256',$g.$slug),0,8);foreach($m['repeaters'][$filterGroup]['fields'] as $key=>$def){if(strpos($key,'_data_filter')!==false)$r[$key]=$slug;elseif($def['type']==='text'&&strpos($key,'_text')!==false)$r[$key]=$label;}$filterRows[]=$r;}$reps[$filterGroup]=$filterRows;
        }
        $reps[$g]=$rows;return $reps;
    }
}
