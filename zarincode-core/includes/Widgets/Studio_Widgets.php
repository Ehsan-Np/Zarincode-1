<?php
/** Focused, independent widgets; intentionally no shared nested pricing schema. */
namespace Zarincode\Runtime260\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
defined('ABSPATH') || exit;
abstract class Studio_Widget extends Widget_Base {
    use \Zarincode\Runtime260\Elementor\Widget_Contract;
    public function get_categories():array{return ['zarincode-professional'];}
    public function get_style_depends():array{return ['zarincode-studio'];}
    public function get_script_depends():array{return ['zarincode-studio'];}
    public function add_control($id,array $args,$options=[]){$args=Icon_Compatibility::control($args);if(($args['type']??'')===C::REPEATER)$args['default']=Repeater_Identity::rows($args['default']??[],$this->get_name().'.'.$id,$args['fields']??[]);return parent::add_control($id,$args,$options);}
    protected function get_init_settings(){return Repeater_Identity::settings(parent::get_init_settings(),$this->get_controls(),$this->get_name().'.'.$this->get_id());}
    public function get_raw_data($with_html_content=false){$data=parent::get_raw_data($with_html_content);$data['settings']=Repeater_Identity::settings($data['settings'],$this->get_controls(),$this->get_name().'.'.$this->get_id());return $data;}
    protected function field(string $key,string $label,string $type,$default='',array $args=[]):void {$this->add_control($key,array_merge(['label'=>$label,'type'=>$type,'default'=>$default],$args));}
    protected function link(array $link,string $key):string {unset($link['custom_attributes']);$this->remove_render_attribute($key);$this->add_link_attributes($key,$link);return $this->get_render_attribute_string($key);}
    protected function styles(string $selector):void {
        $this->start_controls_section('appearance',['label'=>'هویت بصری و فاصله‌ها','tab'=>C::TAB_STYLE]);
        foreach(['accent'=>'رنگ تأکیدی','surface'=>'پس‌زمینه','ink'=>'رنگ متن'] as $key=>$label)$this->field($key,$label,C::COLOR,'',['selectors'=>['{{WRAPPER}} '.$selector=>'--studio-'.$key.':{{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'title_typography','label'=>'تایپوگرافی عنوان','selector'=>'{{WRAPPER}} '.$selector.' h2,{{WRAPPER}} '.$selector.' h3']);
        $this->add_responsive_control('padding',['label'=>'فاصله داخلی','type'=>C::DIMENSIONS,'size_units'=>['px','em'],'selectors'=>['{{WRAPPER}} '.$selector=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->end_controls_section();
    }
}
final class Widget_Client_Logos extends Studio_Widget {
    public function get_name():string{return 'zarincode_client_logos';}
    public function get_title():string{return 'کاروسل لوگوی مشتریان';}
    public function get_icon():string{return 'eicon-logo';}
    protected function register_controls():void {
        $this->start_controls_section('content',['label'=>'مشتریان و هویت مجموعه‌ها']);
        $this->field('title','عنوان بخش',C::TEXT,'همراه برندهای پیشرو');$this->field('description','معرفی کوتاه',C::TEXTAREA,'مجموعه‌هایی که در مسیر تحول دیجیتال همراهشان بوده‌ایم.');
        $r=new Repeater();$r->add_control('name',['label'=>'نام مجموعه','type'=>C::TEXT,'label_block'=>true]);$r->add_control('logo',['label'=>'لوگوی مجموعه','type'=>C::MEDIA]);$r->add_control('alt',['label'=>'متن جایگزین لوگو','type'=>C::TEXT]);$r->add_control('service',['label'=>'شرح کوتاه همکاری','type'=>C::TEXT]);$r->add_control('link',['label'=>'صفحه معرفی همکاری','type'=>C::URL]);
        $this->field('clients','لوگوها و مجموعه‌ها',C::REPEATER,[],['fields'=>$r->get_controls(),'title_field'=>'{{{ name }}}','prevent_empty'=>false]);
        $this->end_controls_section();$this->start_controls_section('carousel',['label'=>'حرکت و نمایش کاروسل']);
        $this->add_responsive_control('columns',['label'=>'تعداد لوگو در ردیف','type'=>C::SELECT,'options'=>['2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶'],'default'=>'4','tablet_default'=>'3','mobile_default'=>'2','selectors'=>['{{WRAPPER}} .zc-client-carousel'=>'--logo-columns:{{VALUE}};']]);
        $this->field('autoplay','حرکت خودکار',C::SWITCHER,'');$this->field('delay','فاصله حرکت (میلی‌ثانیه)',C::NUMBER,5000,['min'=>3000,'max'=>15000,'condition'=>['autoplay'=>'yes']]);$this->field('monochrome','لوگوهای تک‌رنگ تا زمان هاور',C::SWITCHER,'');$this->end_controls_section();$this->styles('.zc-client-carousel');
    }
    protected function render():void {
        $s=$this->get_settings_for_display();$id='zc-clients-'.$this->get_id();
        echo '<section class="zc-client-carousel'.(($s['monochrome']??'')==='yes'?' is-monochrome':'').'" data-studio-carousel data-autoplay="'.esc_attr(($s['autoplay']??'')==='yes'?'yes':'no').'" data-delay="'.esc_attr(max(3000,min(15000,(int)($s['delay']??5000)))).'" aria-roledescription="کاروسل" aria-label="'.esc_attr($s['title']??'مشتریان').'">';
        echo '<header><div><span class="zc-studio-eyebrow">روایت همکاری</span><h2>'.esc_html($s['title']??'').'</h2><p>'.esc_html($s['description']??'').'</p></div><div class="zc-carousel-controls"><button type="button" data-carousel-prev aria-controls="'.esc_attr($id).'" aria-label="مشتریان قبلی">→</button><button type="button" data-carousel-next aria-controls="'.esc_attr($id).'" aria-label="مشتریان بعدی">←</button><button type="button" data-carousel-pause aria-pressed="false">توقف حرکت</button></div></header>';
        echo '<div id="'.esc_attr($id).'" class="zc-client-track" tabindex="0" role="list" aria-label="لوگوی مشتریان">';
        foreach($s['clients']??[] as $i=>$row){$linked=!empty($row['link']['url']);echo '<div role="listitem" class="zc-client-item elementor-repeater-item-'.esc_attr($row['_id']??'').'">';echo $linked?'<a '.$this->link($row['link'],'client'.$i).'>':'<div class="zc-client-content">';$image=$row['logo']??[];$alt=($row['alt']??'')?:($row['name']??'');
            if(!empty($image['id']))echo wp_get_attachment_image(absint($image['id']),'medium',false,['alt'=>$alt,'loading'=>'lazy']);elseif(!empty($image['url']))echo '<img src="'.esc_url($image['url']).'" alt="'.esc_attr($alt).'" loading="lazy">';else echo '<span class="zc-logo-placeholder" aria-hidden="true">'.esc_html(wp_html_excerpt($row['name']??'',1,'')).'</span>';
            echo '<h3>'.esc_html($row['name']??'').'</h3><p>'.esc_html($row['service']??'').'</p>'.($linked?'</a>':'</div>').'</div>';
        }echo '</div><span class="screen-reader-text" role="status" data-carousel-status></span></section>';
    }
}
final class Widget_Pricing_Plan extends Studio_Widget {
    public function get_name():string{return 'zarincode_pricing_plan';}
    public function get_title():string{return 'کارت تعرفه مستقل — مدیریت سریع';}
    public function get_icon():string{return 'eicon-price-table';}
    protected function register_controls():void {
        $this->start_controls_section('plan',['label'=>'عنوان، قیمت و اقدام']);
        $this->field('eyebrow','نشان بالای پلن',C::TEXT,'طراحی وب‌سایت');$this->field('title','نام پلن',C::TEXT,'پلن رشد');$this->field('description','توضیح کوتاه',C::TEXTAREA,'برای کسب‌وکارهایی که آماده قدم بعدی هستند.');$this->field('price','مبلغ یا بازه قیمت',C::TEXT,'با مشاوره');$this->field('period','واحد یا دوره پرداخت',C::TEXT,'متناسب با دامنه پروژه');$this->field('featured','پلن پیشنهادی',C::SWITCHER,'');$this->field('featured_label','متن نشان پیشنهاد',C::TEXT,'انتخاب پیشنهادی');$this->field('button','متن دکمه',C::TEXT,'درخواست مشاوره');$this->field('link','پیوند دکمه',C::URL,['url'=>home_url('/contact-us/')]);$this->field('footnote','یادداشت پایین کارت',C::TEXT,'دامنه کار و تعهدات در قرارداد مشخص می‌شود.');$this->end_controls_section();
        $this->start_controls_section('benefits',['label'=>'فهرست آیکون امکانات این پلن']);$r=new Repeater();$r->add_control('text',['label'=>'امکان یا مزیت','type'=>C::TEXT,'label_block'=>true]);$r->add_control('icon',['label'=>'آیکون','type'=>C::ICONS,'default'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']]);$r->add_control('description',['label'=>'توضیح تکمیلی (اختیاری)','type'=>C::TEXTAREA]);$r->add_control('included',['label'=>'شامل این پلن است','type'=>C::SWITCHER,'default'=>'yes']);
        $this->field('features','امکانات',C::REPEATER,[['text'=>'طراحی واکنش‌گرا','included'=>'yes','icon'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']]],['fields'=>$r->get_controls(),'prevent_empty'=>false,'title_field'=>'{{{ text }}}']);$this->end_controls_section();
        $this->start_controls_section('design',['label'=>'چیدمان و سبک']);$this->field('skin','سبک بصری',C::SELECT,'editorial',['options'=>['editorial'=>'روشن و تحریریه‌ای','night'=>'تیره و طلایی','outline'=>'خطی و مینیمال','sunshine'=>'زرد شاخص']]);$this->end_controls_section();$this->styles('.zc-pricing-plan');
    }
    protected function render():void {
        $s=$this->get_settings_for_display();$skin=in_array($s['skin']??'',['editorial','night','outline','sunshine'],true)?$s['skin']:'editorial';
        echo '<article class="zc-pricing-plan'.(($s['featured']??'')==='yes'?' is-featured':'').'" data-studio-skin="'.esc_attr($skin).'">';
        if(($s['featured']??'')==='yes')echo '<span class="zc-plan-ribbon">'.esc_html($s['featured_label']??'').'</span>';
        echo '<header><span class="zc-studio-eyebrow">'.esc_html($s['eyebrow']??'').'</span><h2>'.esc_html($s['title']??'').'</h2><p>'.esc_html($s['description']??'').'</p></header><div class="zc-plan-price"><strong>'.esc_html($s['price']??'').'</strong><span>'.esc_html($s['period']??'').'</span></div><ul class="zc-plan-benefits">';
        foreach($s['features']??[] as $row)echo '<li'.(($row['included']??'yes')==='yes'?'':' class="is-excluded"').'><span class="zc-benefit-icon">'.Icon_Compatibility::render($row['icon']??[]).'</span><div><strong>'.esc_html($row['text']??'').'</strong>'.(!empty($row['description'])?'<small>'.esc_html($row['description']).'</small>':'').'</div></li>';
        echo '</ul>';if(!empty($s['link']['url']))echo '<a class="zc-plan-action" '.$this->link($s['link'],'action').'>'.esc_html($s['button']??'').' <span aria-hidden="true">←</span></a>';echo '<p class="zc-plan-note">'.esc_html($s['footnote']??'').'</p></article>';
    }
}

final class Widget_Social_Profiles extends Studio_Widget {
    public function get_name():string{return 'zarincode_social_profiles';}
    public function get_title():string{return 'شبکه‌های اجتماعی قابل ویرایش';}
    public function get_icon():string{return 'eicon-social-icons';}
    public function get_keywords():array{return ['social','شبکه','ارتباط','footer'];}

    protected function register_controls():void {
        $this->start_controls_section('content',['label'=>'شبکه‌ها و پیوندها']);
        $this->field('aria','برچسب دسترس‌پذیری','text','راه‌های ارتباطی زرین کد');
        $r=new Repeater();
        $r->add_control('icon',['label'=>'آیکون شبکه','type'=>C::ICONS,'default'=>['value'=>'fab fa-telegram','library'=>'fa-brands']]);
        $r->add_control('label',['label'=>'نام نمایش‌داده‌شده','type'=>C::TEXT,'label_block'=>true,'default'=>'تلگرام']);
        $r->add_control('link',['label'=>'نشانی صفحه','type'=>C::URL]);
        $r->add_control('new_tab',['label'=>'باز شدن در زبانهٔ تازه','type'=>C::SWITCHER,'default'=>'yes']);
        $this->field('profiles','فهرست شبکه‌ها',C::REPEATER,self::default_profiles(),[
            'fields'=>$r->get_controls(),
            'title_field'=>'{{{ label }}}',
            'prevent_empty'=>false,
        ]);
        $this->end_controls_section();

        $this->start_controls_section('layout',['label'=>'چیدمان']);
        $this->add_responsive_control('align',['label'=>'ترازبندی','type'=>C::CHOOSE,'options'=>['flex-start'=>['title'=>'راست','icon'=>'eicon-text-align-right'],'center'=>['title'=>'وسط','icon'=>'eicon-text-align-center'],'flex-end'=>['title'=>'چپ','icon'=>'eicon-text-align-left']],'default'=>'flex-start','selectors'=>['{{WRAPPER}} .zc-social-profiles'=>'justify-content:{{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('skin',['label'=>'ظاهر','tab'=>C::TAB_STYLE]);
        $this->field('text_color','رنگ متن','color','',['selectors'=>['{{WRAPPER}} .zc-social-profiles a'=>'color:{{VALUE}};']]);
        $this->field('border_color','رنگ حاشیه','color','',['selectors'=>['{{WRAPPER}} .zc-social-profiles a'=>'border-color:{{VALUE}};']]);
        $this->field('hover_color','رنگ هاور','color','',['selectors'=>['{{WRAPPER}} .zc-social-profiles a:hover'=>'border-color:{{VALUE}};color:{{VALUE}};']]);
        $this->add_responsive_control('icon_size',['label'=>'اندازهٔ آیکون','type'=>C::SLIDER,'size_units'=>['px','em'],'range'=>['px'=>['min'=>10,'max'=>40]],'selectors'=>['{{WRAPPER}} .zc-social-profiles i,{{WRAPPER}} .zc-social-profiles svg'=>'font-size:{{SIZE}}{{UNIT}};width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();
    }

    /** Defaults come from the theme's own Social Links options, so the widget
     *  opens with the networks the owner already configured — and every one of
     *  them can then be renamed, re-iconed, relinked, reordered or removed
     *  right inside the Elementor panel. */
    public static function default_profiles():array {
        $rows=[];
        $networks=['telegram'=>['fab fa-telegram','fa-brands'],'instagram'=>['fab fa-instagram','fa-brands'],'linkedin'=>['fab fa-linkedin-in','fa-brands'],'whatsapp'=>['fab fa-whatsapp','fa-brands'],'x'=>['fab fa-x-twitter','fa-brands']];
        $labels=['telegram'=>'تلگرام','instagram'=>'اینستاگرام','linkedin'=>'لینکدین','whatsapp'=>'واتس‌اپ','x'=>'ایکس'];
        foreach($networks as $key=>[$value,$library]){
            $url='';
            if(function_exists('zarincode_get_option')){$url=(string)zarincode_get_option('social_'.$key,'');}
            if(!$url||!in_array(wp_parse_url($url,PHP_URL_SCHEME),['https','http'],true)){$url='https://zarincode.com';}
            $rows[]=['_id'=>substr(md5($key),0,7),'icon'=>['value'=>$value,'library'=>$library],'label'=>$labels[$key],'link'=>['url'=>$url,'is_external'=>'','nofollow'=>'noopener'],'new_tab'=>'yes'];
        }
        return $rows;
    }

    /** Public alias: the control default and the builder read the same list. */
    public static function profiles_default():array { return self::default_profiles(); }

    protected function render():void {
        $s=$this->get_settings_for_display();
        $profiles=is_array($s['profiles']??null)?$s['profiles']:[];
        if(!$profiles){echo '<p class="zc-social-empty">'.esc_html__('شبکه‌ای انتخاب نشده است.','zarincode-core').'</p>';return;}
        $class='zc-social-profiles elementor-social-profiles';
        echo '<nav class="'.esc_attr($class).'" aria-label="'.esc_attr($s['aria']??'راه‌های ارتباطی').'">';
        foreach($profiles as $i=>$row){
            $label=trim((string)($row['label']??''));
            $url=(string)($row['link']['url']??'');
            $tab='yes'===(string)($row['new_tab']??'yes');
            $attrs=' href="'.esc_url($url?:'#').'"';
            if($tab){$attrs.=' target="_blank" rel="noopener noreferrer me"';}
            $attrs.=' aria-label="'.esc_attr($label?('ارتباط با ما در '.$label):'ارتباط با ما').'"';
            echo '<a'.$attrs.' data-zc-social="'.esc_attr($label).'">';
            echo Icon_Compatibility::render($row['icon']??[]);
            if(''!==$label){echo '<span>'.esc_html($label).'</span>';}
            echo '</a>';
        }
        echo '</nav>';
    }
}
