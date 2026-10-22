<?php
/** Unbounded native icon list with stable per-plan associations (no nested repeaters). */
namespace Zarincode\Runtime260\Widgets;
use Elementor\Controls_Manager as C;
use Elementor\Repeater;
defined('ABSPATH') || exit;
final class Pricing_Features {
    public static function defaults(array $m):array {
        $features=[];
        foreach($m['repeaters'] as $rep)foreach($rep['default'] as $row){
            $template=$rep['variants'][$row['_variant']??'v1']??reset($rep['variants']);
            $walk=function($node)use(&$walk,&$features,$row){
                if(!is_array($node))return;
                $attrs=$node['attrs']??[];
                if(is_array($attrs)&&strpos($attrs['class']??'','zc-pricing-features')!==false){
                    foreach($node['children']??[] as $li)if(($li['tag']??'')==='li'){
                        $text='';$icon=['value'=>'fas fa-check-circle','library'=>'fa-solid'];
                        $read=function($n)use(&$read,&$text,&$icon,$row){if(isset($n['text']))$text.=$row[$n['text']]??'';if(isset($n['icon']))$icon=$row[$n['icon']]??$icon;foreach($n['children']??[] as $c)$read($c);};$read($li);
                        if($text!=='')$features[]=['_id'=>substr(hash('sha256',$row['_id'].$text),0,8),'plan_key'=>$row['plan_key']??$row['_id'],'text'=>$text,'icon'=>$icon];
                    }
                    return;
                }
                foreach($node['children']??[] as $child)$walk($child);
            };$walk($template);
        }
        return $features;
    }
    public static function controls($widget,array $m,array $condition):void {
        $widget->start_controls_section('n_pricing_features_section',['label'=>'فهرست آیکون امکانات پلن‌ها','condition'=>$condition]);
        $widget->add_control('n_pricing_features_help',['type'=>C::RAW_HTML,'raw'=>'پلن مقصد را از فهرست نام پلن‌ها انتخاب کنید. برای پلن جدید، گزینه «پلن سفارشی جدید» و شناسه دلخواه آن را وارد کنید. در کارت پلن، مدیریت امکانات را «فهرست آیکون قابل افزایش» انتخاب کنید. شناسه ارتباط امکانات هر ردیف باید با شناسه همان پلن یکسان باشد. با جابه‌جایی کارت، امکانات آن جابه‌جا نمی‌شوند. برای پلن جدید یک شناسه یکتا مانند web-premium تعیین کنید.']);
        $options=[];foreach($m['repeaters'] as $group=>$rep)foreach($rep['default'] as $row)$options[$row['plan_key']??$row['_id']]=$row['heading_text_1']??'پلن';
        $options['__custom__']='پلن سفارشی جدید';
        $r=new Repeater();
        $r->add_control('plan_key',['label'=>'پلن مقصد','type'=>C::SELECT,'options'=>$options,'default'=>array_key_first($options),'label_block'=>true]);
        $r->add_control('custom_plan_key',['label'=>'شناسه پلن سفارشی','type'=>C::TEXT,'label_block'=>true,'condition'=>['plan_key'=>'__custom__']]);
        $r->add_control('text',['label'=>'عنوان امکان','type'=>C::TEXT,'label_block'=>true,'dynamic'=>['active'=>true]]);
        $r->add_control('icon',['label'=>'آیکون امکان','type'=>C::ICONS,'default'=>['value'=>'fas fa-check-circle','library'=>'fa-solid']]);
        $r->add_control('color',['label'=>'رنگ آیکون','type'=>C::COLOR,'selectors'=>['{{WRAPPER}} .zc-pricing-features {{CURRENT_ITEM}} .zc-feature-icon'=>'color:{{VALUE}};']]);
        $widget->add_control('n_pricing_features',['label'=>'امکانات همه پلن‌ها','type'=>C::REPEATER,'fields'=>$r->get_controls(),'default'=>self::defaults($m),'prevent_empty'=>false,'title_field'=>'{{{ text }}}']);
        $widget->end_controls_section();
    }
    public static function render($widget,array $m,array $settings,string $plan):string {
        $rows=array_key_exists('n_pricing_features',$settings)?$settings['n_pricing_features']:self::defaults($m);
        $html='<ul class="zc-pricing-features">';
        foreach(is_array($rows)?$rows:[] as $i=>$row){
            if(!is_array($row))continue;
            $target=($row['plan_key']??'')==='__custom__'?($row['custom_plan_key']??''):($row['plan_key']??'');
            if($target!==$plan)continue;
            $attributes=$widget->inline_attributes('n_pricing_features.'.$i.'.text');
            $attr='';foreach($attributes as $key=>$value)$attr.=' '.esc_attr($key).'="'.esc_attr(implode(' ',$value)).'"';
            $html.='<li class="elementor-repeater-item-'.esc_attr($row['_id']??'').'"><span class="zc-feature-icon">'.Icon_Compatibility::render($row['icon']??[]).'</span><span'.$attr.'>'.esc_html($row['text']??'').'</span></li>';
        }
        return $html.'</ul>';
    }
}
