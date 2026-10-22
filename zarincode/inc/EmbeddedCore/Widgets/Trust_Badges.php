<?php
/** Compile issued badge markup into inert, bounded image/link markup. Never execute pasted JS. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Trust_Badges {
    public static function providers():array {
        return ['zarinpal'=>'زرین‌پال','zibal'=>'زیبال','idpay'=>'آیدی‌پی','payir'=>'پی‌دات‌آی‌آر','payping'=>'پی‌پینگ','vandar'=>'وندار','nextpay'=>'نکست‌پی','bitpay'=>'بیت‌پی','enamad'=>'اینماد','samandehi'=>'ساماندهی','custom'=>'اعتبارنامه دیگر'];
    }
    public static function url($value,string $provider):string {
        if(!is_string($value))return '';
        $value=trim(html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8'));
        $parts=wp_parse_url(trim(html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8')));
        if(!is_array($parts)||($parts['scheme']??'')!=='https'||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])&&$parts['port']!==443)return '';
        $roots=['zarinpal'=>['zarinpal.com'],'zibal'=>['zibal.ir'],'idpay'=>['idpay.ir'],'payir'=>['pay.ir'],'payping'=>['payping.ir'],'vandar'=>['vandar.io'],'nextpay'=>['nextpay.org','nextpay.ir'],'bitpay'=>['bitpay.ir'],'enamad'=>['enamad.ir'],'samandehi'=>['samandehi.ir']];
        if($provider==='custom')return esc_url_raw($value,['https']);
        $host=strtolower($parts['host']);foreach($roots[$provider]??[] as $root)if($host===$root||substr($host,-strlen('.'.$root))==='.'.$root)return esc_url_raw(html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8'),['https']);return '';
    }
    public static function normalize(array $row):array {
        $provider=$row['provider']??'custom';if(!is_string($provider))return [];if(!isset(self::providers()[$provider]))return [];
        $link=self::url(is_array($row['link']??null)?($row['link']['url']??''):($row['link']??''),$provider);
        $image='';$local=false;$media=is_array($row['image']??null)?$row['image']:[];
        if(!empty($media['id'])){$src=wp_get_attachment_image_src(absint($media['id']),'medium');if($src){$image=$src[0];$local=true;}}
        if(!$image&&!empty($media['url'])&&is_string($media['url'])){$image=self::url($media['url'],$provider);if(!$image&&strpos($media['url'],trailingslashit(content_url()))===0){$image=esc_url_raw($media['url'],['http','https']);$local=true;}}
        $code=is_string($row['code']??null)?substr($row['code'],0,65536):'';$officialZarin=false;
        if($code!==''&&class_exists('WP_HTML_Tag_Processor')){
            $p=new \WP_HTML_Tag_Processor($code);
            while($p->next_tag()){
                if($p->get_tag()==='A'&&!$link)$link=self::url((string)$p->get_attribute('href'),$provider);
                // Legacy Samandehi markup uses window.open on the image. Extract a literal HTTPS URL,
                // validate its provider host, and discard the handler; no JavaScript is ever emitted.
                if(!$link){$click=$p->get_attribute('onclick');if(is_string($click)&&preg_match('~^\s*(?:return\s+)?window\.open\(\s*([\'"])(https://[^\'"]+)\1~i',$click,$match))$link=self::url($match[2],$provider);}
                if($p->get_tag()==='IMG'&&!$image)$image=self::url((string)$p->get_attribute('src'),$provider);
                if($p->get_tag()==='SCRIPT'&&$provider==='zarinpal'){$src=self::url((string)$p->get_attribute('src'),'zarinpal');$path=wp_parse_url($src,PHP_URL_PATH);if(in_array($path,['/webservice/TrustCode','/trustlogo/v1/trustlogo.js'],true))$officialZarin=true;}
            }
        }
        if($officialZarin){if(!$link)$link='https://www.zarinpal.com/trustPage/'.rawurlencode((string)wp_parse_url(home_url(),PHP_URL_HOST));if(!$image)$image='https://cdn.zarinpal.com/badges/trustLogo/1.png';}
        if(!$link||!$image)return [];
        return ['url'=>$link,'image'=>$image,'local'=>$local,'label'=>sanitize_text_field(is_scalar($row['label']??null)&&$row['label']!==''?(string)$row['label']:self::providers()[$provider]),'id'=>sanitize_html_class($row['_id']??'')];
    }
    public static function html(array $rows):string {
        $html='';foreach(array_slice($rows,0,30) as $row){if(!is_array($row)||!($badge=self::normalize($row)))continue;
            $html.='<figure class="zc-credential elementor-repeater-item-'.esc_attr($badge['id']).'"><a href="'.esc_url($badge['url']).'" target="_blank" rel="noopener external" aria-label="'.esc_attr('استعلام اعتبار '.$badge['label']).'"><img src="'.esc_url($badge['image']).'" alt="'.esc_attr($badge['label']).'" width="120" height="120" loading="lazy" decoding="async" referrerpolicy="origin"><figcaption>'.esc_html($badge['label']).'<span>استعلام از صادرکننده ↗</span></figcaption></a></figure>';
        }
        return $html?'<div class="zc-credentials" aria-label="اعتبارنامه‌های کسب‌وکار">'.$html.'</div>':'';
    }
    public static function register_style():void {
        $base=strpos(wp_normalize_path(__DIR__),wp_normalize_path(get_template_directory()).'/')===0?get_template_directory_uri().'/inc/EmbeddedCore/Widgets/assets/':plugin_dir_url(__FILE__).'assets/';
        wp_register_style('zarincode-trust',$base.'trust-badges.css',[],'3.4.0');
    }
}
