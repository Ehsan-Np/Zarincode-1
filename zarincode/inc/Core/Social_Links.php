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
        foreach(Social_Manager::rows() as $row)if($row['url'])$out.='<a href="'.esc_url($row['url']).'" target="_blank" rel="noopener noreferrer me" aria-label="'.esc_attr($row['name']).'">'.Social_Manager::icon($row).'<span>'.esc_html($row['name']).'</span></a>';
        return $out?'<nav class="zc-social-profiles" aria-label="راه‌های ارتباطی زرین کد">'.$out.'</nav>':'';
    }
    public static function floating():string {
        $mode=zarincode_get_option('floating_contact_action','socials');$label=zarincode_get_option('floating_contact_label','راه‌های ارتباطی');
        if($mode==='socials'){$profiles=self::profiles();return $profiles?'<details class="zc-floating-socials"><summary aria-label="'.esc_attr($label).'"><i class="fa-regular fa-comments" aria-hidden="true"></i></summary><div><strong>گفت‌وگو را از اینجا شروع کنیم</strong>'.$profiles.'</div></details>':'';}
        $url=$mode==='phone'?'tel:'.preg_replace('/[^0-9+]/','',zarincode_get_option('contact_phone','09024561001')):($mode==='custom'?Social_Manager::url(zarincode_get_option('floating_contact_url','')):Social_Manager::url(zarincode_get_option('social_'.$mode,'')));
        if(!$url)return '';$icon=$mode==='phone'?'<i class="fa-solid fa-phone" aria-hidden="true"></i>':self::icon($mode);
        return '<a class="zc-floating-channel" href="'.esc_url($url).'" aria-label="'.esc_attr($label).'"'.($mode==='phone'?'':' target="_blank" rel="noopener noreferrer"').'>'.$icon.'</a>';
    }

}
