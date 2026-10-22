<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Profile destinations and share actions are deliberately distinct. No invented app intents. */
final class Social_Links {
    public static function catalog():array {
        return ['telegram'=>['تلگرام','fa-brands fa-telegram'],'whatsapp'=>['واتس‌اپ','fa-brands fa-whatsapp'],'linkedin'=>['لینکدین','fa-brands fa-linkedin-in'],'x'=>['ایکس','fa-brands fa-x-twitter'],'facebook'=>['فیسبوک','fa-brands fa-facebook-f'],'instagram'=>['اینستاگرام','fa-brands fa-instagram'],'github'=>['گیت‌هاب','fa-brands fa-github'],'eitaa'=>['ایتا','eita'],'bale'=>['بله','bale'],'rubika'=>['روبیکا','rubika'],'soroush'=>['سروش پلاس','soroush'],'gap'=>['گپ','gap'],'igap'=>['آی‌گپ','i-gap'],'shad'=>['شاد','shad']];
    }
    public static function icon(string $network):string {
        $entry=self::catalog()[$network]??['','fa-solid fa-envelope'];$icon=$entry[1];
        if(strpos($icon,'fa-')===0)return '<i class="'.esc_attr($icon).'" aria-hidden="true"></i>';
        return '<img src="'.esc_url(get_template_directory_uri().'/assets/icons/persian/'.$icon.'.svg').'" width="20" height="20" alt="" aria-hidden="true" decoding="async">';
    }
    public static function share_urls(string $url,string $title):array {
        $u=rawurlencode($url);$t=rawurlencode($title);
        return ['telegram'=>'https://t.me/share/url?url='.$u.'&text='.$t,'whatsapp'=>'https://wa.me/?text='.rawurlencode($title.' '.$url),'linkedin'=>'https://www.linkedin.com/sharing/share-offsite/?url='.$u,'x'=>'https://x.com/intent/tweet?url='.$u.'&text='.$t,'facebook'=>'https://www.facebook.com/sharer/sharer.php?u='.$u,'eitaa'=>'https://eitaa.com/share/url?url='.$u.'&text='.$t,'email'=>'mailto:?subject='.$t.'&body='.$u];
    }
    public static function profiles():string {
        $out='';foreach(self::catalog() as $key=>$entry){$url=zarincode_get_option('social_'.$key,'');if(!$url||!in_array(wp_parse_url($url,PHP_URL_SCHEME),['https','http'],true))continue;$out.='<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer me" aria-label="'.esc_attr('ارتباط با ما در '.$entry[0]).'">'.self::icon($key).'<span>'.esc_html($entry[0]).'</span></a>';}
        return $out?'<nav class="zc-social-profiles" aria-label="راه‌های ارتباطی زرین کد">'.$out.'</nav>':'';
    }
}
