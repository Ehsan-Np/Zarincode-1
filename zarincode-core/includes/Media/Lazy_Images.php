<?php
/** Native browser image lazy loading; no src removal, JS dependency or layout changes. */
namespace Zarincode\Runtime260\Media;
defined('ABSPATH') || exit;

final class Lazy_Images {
    public static function boot(): void {
        add_filter('wp_get_attachment_image_attributes', [self::class, 'attributes'], 50);
        add_filter('the_content', [self::class, 'html'], 99);
        add_filter('widget_text_content', [self::class, 'html'], 99);
        add_filter('elementor/widget/render_content', [self::class, 'html'], 99);
        add_action('template_redirect', [self::class, 'buffer'], 999);
    }

    public static function enabled(): bool {
        $options = get_option('zarincode_opt', []);
        return !is_array($options) || !array_key_exists('lazy_images', $options)
            || !in_array($options['lazy_images'], [false, 0, '0', 'off', 'no'], true);
    }

    public static function attributes($attributes) {
        if (!self::enabled() || is_admin() || !is_array($attributes)) return $attributes;
        $eager = self::critical($attributes);
        if ($eager) $attributes['loading'] = 'eager';
        elseif (empty($attributes['loading'])) $attributes['loading'] = 'lazy';
        if (empty($attributes['decoding'])) $attributes['decoding'] = 'async';
        return $attributes;
    }

    private static function critical(array $a): bool {
        return strtolower((string)($a['fetchpriority'] ?? '')) === 'high'
            || strtolower((string)($a['loading'] ?? '')) === 'eager'
            || array_key_exists('data-zc-eager', $a)
            || (bool)preg_match('/(?:^|\s)(?:skip-lazy|no-lazy|custom-logo|zc-editorial-cover)(?:\s|$)/', (string)($a['class'] ?? ''));
    }

    public static function html($html) {
        if (!is_string($html) || !self::enabled() || is_admin() || !class_exists('\WP_HTML_Tag_Processor')
            || strlen($html) > 4194304 || stripos($html, '<img') === false) return $html;
        $processor = new \WP_HTML_Tag_Processor($html);
        while ($processor->next_tag('IMG')) {
            $a = [];
            foreach (['fetchpriority','loading','class','data-zc-eager'] as $key) {
                $value = $processor->get_attribute($key);
                if ($value !== null) $a[$key] = $value;
            }
            if (self::critical($a)) $processor->set_attribute('loading', 'eager');
            elseif ($processor->get_attribute('loading') === null) $processor->set_attribute('loading', 'lazy');
            if ($processor->get_attribute('decoding') === null) $processor->set_attribute('decoding', 'async');
        }
        return $processor->get_updated_html();
    }

    public static function buffer(): void {
        if (!self::enabled() || is_admin() || is_feed() || is_embed() || is_trackback() || wp_doing_ajax()
            || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request())
            || is_customize_preview() || isset($_GET['elementor-preview'])) return;
        // Final HTML covers raw theme header/footer, WooCommerce and non-WP image renderers.
        // Never touch JSON/XML/binary downloads or already-compressed page cache output.
        ob_start(static function($html) {
            foreach (headers_list() as $header) {
                if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false) return $html;
                if (stripos($header, 'Content-Disposition: attachment') === 0) return $html;
            }
            if (substr(ltrim($html), 0, 1) !== '<' || stripos(ltrim($html), '<?xml') === 0 || stripos($html, '<html') === false) return $html;
            return self::html($html);
        });
    }
}
