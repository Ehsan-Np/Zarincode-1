<?php
/** Validate our icon settings before Elementor's SVG asset manager sees them. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;

final class Icon_Compatibility {
    private static array $libraries = [];
    private static array $values = [];
    private static array $broken = [];
    private const PREFIXES = ['fa-solid'=>'fas','fa-regular'=>'far','fa-brands'=>'fab'];
    private const ALIASES = [
        'bag-shopping'=>'shopping-bag','bars-staggered'=>'bars','building-shield'=>'building',
        'chart-simple'=>'chart-bar','circle-check'=>'check-circle','circle-xmark'=>'times-circle',
        'earth-europe'=>'globe-europe','file-zipper'=>'file-archive','file-lines'=>'file-alt',
        'money-bill-transfer'=>'exchange-alt','rotate-left'=>'undo','scale-balanced'=>'balance-scale',
        'shield-halved'=>'shield-alt','sparkles'=>'magic','user-pen'=>'user-edit',
        'arrow-up-right-from-square'=>'external-link-alt','magnifying-glass'=>'search',
        'xmark'=>'times','house'=>'home','cart-shopping'=>'shopping-cart','gear'=>'cog',
        'location-dot'=>'map-marker-alt','envelope-open-text'=>'envelope-open-text',
    ];

    private static function library(string $library): array {
        if (array_key_exists($library, self::$libraries)) return self::$libraries[$library];
        $icons = [];
        if (isset(self::PREFIXES[$library]) && defined('ELEMENTOR_ASSETS_PATH')) {
            $path = ELEMENTOR_ASSETS_PATH.'lib/font-awesome/json/'.substr($library, 3).'.json';
            if (is_readable($path)) {
                $content = class_exists('Elementor\\Utils') ? \Elementor\Utils::file_get_contents($path) : file_get_contents($path);
                $data = is_string($content) ? json_decode($content, true) : null;
                if (is_array($data['icons'] ?? null)) $icons = $data['icons'];
            }
            if (!$icons) self::$broken[$library] = true;
        }
        self::$libraries[$library] = $icons;
        return $icons;
    }

    public static function value($icon): array {
        $empty = ['value'=>'','library'=>''];
        if (is_string($icon)) $icon = ['value'=>$icon,'library'=>''];
        if (!is_array($icon)) return $empty;
        $library = is_string($icon['library'] ?? null) ? trim($icon['library']) : '';
        $value = $icon['value'] ?? '';
        if ($library === 'svg') {
            if (!is_array($value) || empty($value['id']) || !is_numeric($value['id'])) return $empty;
            // Uploaded SVG sanitization/rendering remains Elementor's responsibility.
            return ['library'=>'svg','value'=>['id'=>absint($value['id']),'url'=>esc_url_raw(is_string($value['url']??null)?$value['url']:'')]];
        }
        if (!is_string($value) || trim($value) === '') return $empty;
        if (preg_match('/^fa /',$value) && class_exists('Elementor\\Icons_Manager')) $icon=\Elementor\Icons_Manager::fa4_to_fa5_value_migration($value);
        if (isset($icon['value']) && is_string($icon['value']) && $icon['value']!==$value) return self::value($icon);
        $cacheKey = $library.'|'.$value;
        if (isset(self::$values[$cacheKey])) return self::$values[$cacheKey];
        if (!isset(self::PREFIXES[$library]) && $library !== '' && strpos($library, 'fa-') !== 0) {
            // Custom icon packs use their own registered renderer; never coerce them to FA.
            $tabs = class_exists('Elementor\\Icons_Manager') ? \Elementor\Icons_Manager::get_icon_manager_tabs() : [];
            return isset($tabs[$library]) && preg_match('/^[a-zA-Z0-9_ -]+$/D', $value)
                ? ['value'=>$value,'library'=>$library] : $empty;
        }
        if (preg_match('/(?:^|\s)(fas|far|fab|fa-solid|fa-regular|fa-brands|fa)(?:\s|$)/', $value, $prefix)
            && preg_match('/(?:^|\s)fa-([a-zA-Z0-9-]+)(?:\s|$)/', preg_replace('/\bfa-(solid|regular|brands)\b/', '', $value), $name)) {
            $name = strtolower($name[1]); $name = self::ALIASES[$name] ?? $name;
            if (!isset(self::PREFIXES[$library])) {
                $library = in_array($prefix[1], ['fab','fa-brands'], true) ? 'fa-brands' : (in_array($prefix[1], ['far','fa-regular'], true) ? 'fa-regular' : 'fa-solid');
            }
            $order = array_unique(array_merge([$library], array_keys(self::PREFIXES)));
            foreach ($order as $candidate) {
                $data = self::library($candidate)[$name] ?? null;
                if (is_array($data) && count($data)>=5 && is_numeric($data[0]??null) && is_numeric($data[1]??null) && is_string($data[4]??null) && $data[4]!=='') {
                    return self::$values[$cacheKey] = ['value'=>self::PREFIXES[$candidate].' fa-'.$name,'library'=>$candidate];
                }
            }
        }
        return self::$values[$cacheKey] = $empty;
    }

    public static function control(array $control): array {
        if (($control['type']??'') === 'icons' && array_key_exists('default', $control)) $control['default'] = self::value($control['default']);
        if (($control['type']??'') === 'repeater') {
            foreach ($control['fields']??[] as $key=>$field) $control['fields'][$key] = self::control($field);
        }
        return $control;
    }

    public static function settings(array $settings, array $controls): array {
        foreach ($controls as $key=>$control) {
            $name = $control['name']??$key;
            if (!array_key_exists($name, $settings)) continue;
            if (($control['type']??'') === 'icons') $settings[$name] = self::value($settings[$name]);
            elseif (($control['type']??'') === 'repeater' && is_array($settings[$name])) {
                foreach ($settings[$name] as $i=>$row) if (is_array($row)) $settings[$name][$i] = self::settings($row, $control['fields']??[]);
            }
        }
        return $settings;
    }

    public static function render($icon, array $attributes=[]): string {
        $icon = self::value($icon);
        if ($icon['value'] === '') return '';
        ob_start(); \Elementor\Icons_Manager::render_icon($icon, $attributes); return ob_get_clean();
    }

    public static function notice(): void {
        if (!current_user_can('manage_options')) return;
        foreach(array_keys(self::PREFIXES) as $library) self::library($library);
        if (!self::$broken) return;
        echo '<div class="notice notice-warning"><p>'.esc_html__('فایل داده آیکون Elementor موجود یا معتبر نیست. Elementor را از منبع رسمی مجدداً نصب و کش آن را بازسازی کنید. آیکون‌های معیوب زرین کد تا رفع فایل نمایش داده نمی‌شوند؛ فایل داخلی Elementor تغییر نکرده است.', 'zarincode-core').'</p></div>';
    }
}
