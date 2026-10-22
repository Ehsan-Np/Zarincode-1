<?php
/** Explicit contracts for the existing widget IDs; evidence, not a manufactured quality score. */
namespace Zarincode\Runtime260\Elementor;
defined( 'ABSPATH' ) || exit;
final class Contracts {
    public static function all(): array {
        $contracts = [];
        foreach ( \Zarincode\Runtime260\Features\Registry::all() as $feature ) {
            foreach ( $feature['widgets'] as $id ) {
                $contracts[$id] = [
                    'id' => $id, 'version' => ZARINCODE_CORE_VERSION, 'settings_schema' => 1,
                    'provider' => 'zarincode-core', 'feature' => $feature['id'],
                    'dependencies' => $feature['plugins'], 'css' => $feature['css'], 'js' => $feature['js'],
                    'rendering' => 'Escaped server-side Elementor rendering; existing IDs and repeater identities retained.',
                    'accessibility' => 'Keyboard, accessible names, focus, reduced motion and WCAG 2.2 AA review required.',
                    'security' => 'Validate input; escape at final context; queries limited to published public content.',
                    'budget' => [ 'max_query_records' => 100, 'css_target_bytes' => 65536, 'js_target_bytes' => 32768 ],
                    'budget_status' => 'target_not_measurement',
                    'compatibility' => 'See version-specific compatibility manifest; no universal compatibility claim.',
                    'documentation' => 'docs/releases/4.0.0-rc.1-FA.md',
                    'tests' => [ 'controls', 'editor', 'frontend', 'responsive', 'direction', 'a11y', 'visual', 'migration' ],
                    'status' => 'Not Reviewed', 'z_wqi' => null,
                ];
            }
        }
        return $contracts;
    }
}
