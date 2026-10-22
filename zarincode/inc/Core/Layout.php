<?php
/** Shared container sizing; never mutate or repeatedly multiply the saved base width. */
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Layout {
    /** Empty override means automatic, not a fixed device width. Values include container gutters. */
    public const RANGES = ['layout_width_desktop'=>[1200,1920], 'layout_width_tablet'=>[640,1200], 'layout_width_mobile'=>[280,767]];
    public static function optional($value, string $key): string {
        if(!isset(self::RANGES[$key]) || (!is_int($value) && !is_string($value)))return '';
        $value=trim((string)$value);
        if($value==='' || $value==='0' || !preg_match('/^[0-9]{1,9}$/D',$value))return '';
        [$min,$max]=self::RANGES[$key];return (string)max($min,min($max,(int)$value));
    }
    public static function normalize($options) {
        if(is_array($options))foreach(self::RANGES as $key=>$range)if(array_key_exists($key,$options))$options[$key]=self::optional($options[$key],$key);
        return $options;
    }
    public static function responsive(array $options): array {
        $desktop=self::optional($options['layout_width_desktop']??'', 'layout_width_desktop');
        $desktop=$desktop!==''?(int)$desktop:(int)self::width($options['container_max_width']??'1360px',$options['layout_width_scale']??108);
        $tablet=self::optional($options['layout_width_tablet']??'', 'layout_width_tablet');
        $tablet=$tablet!==''?(int)$tablet:$desktop;
        $mobile=self::optional($options['layout_width_mobile']??'', 'layout_width_mobile');
        $mobile=$mobile!==''?(int)$mobile:$tablet;
        $gutter=max(24,min(40,round($desktop*32/1469,2)));
        return ['desktop'=>$desktop,'tablet'=>$tablet,'mobile'=>$mobile,'gutter'=>$gutter,'tablet_gutter'=>round($gutter*.75,2),'mobile_gutter'=>round($gutter*.5,2),'small_gutter'=>round($gutter*.375,2)];
    }

    public static function width($base = '1360px', $scale = 108): string {
        $base = is_scalar($base) && preg_match('/^([0-9]{3,4})(?:px)?$/D', (string)$base, $match) ? (int)$match[1] : 1360;
        $base = max(960, min(1920, $base));
        return (string)round($base * (is_scalar($scale) && (string)$scale === '100' ? 1 : 1.08)) . 'px';
    }
}
