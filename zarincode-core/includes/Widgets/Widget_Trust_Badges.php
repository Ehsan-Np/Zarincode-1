<?php
namespace Zarincode\Runtime260\Widgets;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
defined('ABSPATH') || exit;
final class Widget_Trust_Badges extends Studio_Widget {
    public function get_name():string{return 'zarincode_trust_badges';}
    public function get_title():string{return 'اعتبارنامه‌ها و نشان‌های پرداخت ایرانی';}
    public function get_icon():string{return 'eicon-lock-user';}
    public function get_style_depends():array{return ['zarincode-trust'];}
    public function get_script_depends():array{return [];}
    protected function register_controls():void {
        $this->start_controls_section('credentials',['label'=>'اعتبارنامه‌های صادرشده']);
        $this->field('title','عنوان بخش',C::TEXT,'اعتبار و شفافیت، پیش از خرید');
        $this->field('guide','راهنمای درج نشان',C::RAW_HTML,'',['raw'=>'کد رسمی صادرکننده یا تصویر و پیوند HTTPS استعلام را وارد کنید. اسکریپت خام اجرا نمی‌شود. کد رسمی زرین‌پال به تصویر و پیوند استعلام تبدیل می‌شود؛ سایر کدهای صرفاً اسکریپتی نیازمند پیوند و تصویر رسمی‌اند. لوگوی برند به‌تنهایی تأیید اعتبار نیست.']);
        $r=new Repeater();$r->add_control('provider',['type'=>C::SELECT,'label'=>'صادرکننده','options'=>Trust_Badges::providers(),'default'=>'zarinpal']);$r->add_control('label',['type'=>C::TEXT,'label'=>'عنوان نشان']);$r->add_control('code',['type'=>C::TEXTAREA,'label'=>'کد رسمی نشان','rows'=>5,'placeholder'=>'<a href="https://…"><img src="https://…"></a>']);$r->add_control('image',['type'=>C::MEDIA,'label'=>'تصویر رسمی نشان (اختیاری)']);$r->add_control('link',['type'=>C::URL,'label'=>'پیوند رسمی استعلام (اختیاری)']);
        $this->field('badges','نشان‌ها',C::REPEATER,[],['fields'=>$r->get_controls(),'prevent_empty'=>false,'title_field'=>'{{{ label || provider }}}']);$this->end_controls_section();
        $this->start_controls_section('presentation',['label'=>'چیدمان نشان‌ها','tab'=>C::TAB_STYLE]);$this->add_responsive_control('columns',['label'=>'تعداد ستون','type'=>C::SELECT,'options'=>['1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴'],'default'=>'3','tablet_default'=>'2','mobile_default'=>'2','selectors'=>['{{WRAPPER}} .zc-credentials'=>'--zc-credential-columns:{{VALUE}};']]);$this->add_responsive_control('logo_size',['label'=>'اندازه تصویر نشان','type'=>C::SLIDER,'range'=>['px'=>['min'=>48,'max'=>180]],'default'=>['size'=>120,'unit'=>'px'],'selectors'=>['{{WRAPPER}} .zc-credential img'=>'width:{{SIZE}}px;height:{{SIZE}}px;']]);
        $this->add_responsive_control('badge_gap',['label'=>'فاصله بین نشان‌ها','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>48]],'selectors'=>['{{WRAPPER}} .zc-credentials'=>'gap:{{SIZE}}px;']]);
        $this->add_control('badge_radius',['label'=>'گردی گوشه نشان‌ها','type'=>C::SLIDER,'range'=>['px'=>['min'=>0,'max'=>32]],'selectors'=>['{{WRAPPER}} .zc-credential'=>'border-radius:{{SIZE}}px;']]);
        $this->end_controls_section();$this->styles('.zc-credential-section');
    }
    protected function render():void {
        $s=$this->get_settings_for_display();$html=Trust_Badges::html($s['badges']??[]);
        if(!$html){if(\Elementor\Plugin::instance()->editor->is_edit_mode())echo '<p>نشان قابل نمایشی تنظیم نشده است. کد رسمی یا تصویر و پیوند استعلام را اضافه کنید؛ در سایت نشان ساختگی نمایش داده نمی‌شود.</p>';return;}
        echo '<section class="zc-credential-section">';if(!empty($s['title']))echo '<h2>'.esc_html($s['title']).'</h2>';echo $html.'</section>';
    }
}
