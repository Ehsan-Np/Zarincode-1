<?php
/** wp eval-file: same malformed settings reproduced the FA SVG line-45 warning in 3.0.0. */
set_error_handler(static function ($severity, $message, $file, $line) {
    if (strpos($file, 'font-icon-svg') !== false) {
        throw new RuntimeException($message . ' at ' . basename($file) . ':' . $line);
    }
    return false; // Keep unrelated vendor warnings visible.
});
try {
    $widget = Elementor\Plugin::instance()->elements_manager->create_element_instance([
        'id'=>'iconrepro', 'elType'=>'widget', 'widgetType'=>'zarincode_hero',
        'settings'=>['design_mode'=>'custom', 'badge_icon'=>[
            'value'=>'fa-solid fa-circle-check', 'library'=>'fa-missing',
        ]],
    ]);
    ob_start();
    try { $widget->render_content(); } finally { $html = ob_get_clean(); }
    if (!$html || strpos($html, 'fa-missing') !== false) {
        throw new RuntimeException('Malformed library reached output or widget did not render.');
    }
    echo "PASS exact FA SVG null-offset reproduction renders safely\n";
} finally {
    restore_error_handler();
}
