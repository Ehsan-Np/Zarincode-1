<?php
namespace Zarincode\Runtime260\Support;
defined( 'ABSPATH' ) || exit;
/** Bounded, read-only health data. Never exposes settings, URLs, credentials or customer records. */
final class Diagnostics {
    public static function snapshot(): array {
        $features = [];
        foreach ( \Zarincode\Runtime260\Features\Registry::all() as $id => $feature ) {
            $features[$id] = [ 'status' => \Zarincode\Runtime260\Features\Manager::status($id), 'version' => $feature['version'] ];
        }
        return [ 'core' => ZARINCODE_CORE_VERSION, 'php' => PHP_VERSION,
            'elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'absent',
            'elementor_pro' => defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : 'absent',
            'woocommerce' => defined('WC_VERSION') ? WC_VERSION : 'absent', 'features' => $features ];
    }
    public static function page(): void {
        if ( ! current_user_can('manage_options') ) { wp_die(esc_html__('Insufficient permissions.','zarincode-core')); }
        echo '<div class="wrap"><h2>'.esc_html__('ZarinCode diagnostics','zarincode-core').'</h2><pre dir="ltr">'.esc_html(wp_json_encode(self::snapshot(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</pre></div>';
    }
    public static function site_health( array $info ): array {
        $info['zarincode-core'] = [ 'label' => 'ZarinCode Core', 'fields' => [
            'version' => [ 'label' => 'Version', 'value' => ZARINCODE_CORE_VERSION ],
            'provider' => [ 'label' => 'Runtime owner', 'value' => 'Companion plugin' ],
        ] ];
        return $info;
    }
}
