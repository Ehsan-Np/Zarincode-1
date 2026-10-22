<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Shared site sizing and general presentation. */
final class Presentation {
    public static function boot():void {
        add_action('wp_enqueue_scripts',static function(){wp_enqueue_style('zarincode-cards',get_template_directory_uri().'/inc/EmbeddedCore/Widgets/assets/cards.css',[],ZARINCODE_VERSION);wp_enqueue_style('zarincode-presentation',get_template_directory_uri().'/assets/css/presentation.css',['zarincode-main'],ZARINCODE_VERSION);},30);
        add_action('after_setup_theme',static function(){
            if(get_option('zarincode_presentation_38'))return;
            $options=get_option('zarincode_opt',[]);
            if(is_array($options)&&isset($options['body_font_size'])&&(int)$options['body_font_size']===15){$options['body_font_size']=16;update_option('zarincode_opt',$options);$GLOBALS['zarincode_opt']=$options;}
            update_option('zarincode_presentation_38',1,false);
        },40);
    }
    public static function width($base,$scale):string {
        $base=is_scalar($base)?(float)$base:1360;$base=max(960,min(1920,$base));
        $scale=(int)$scale===100?100:115;
        return (string)round($base*$scale/100).'px';
    }
}
