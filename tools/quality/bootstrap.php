<?php
/** Never point the integration suite at a real site. */
$path = getenv('ZARINCODE_QA_WP');
if (!$path || !str_starts_with(realpath($path) ?: '', '/tmp/zc-')) {
    throw new RuntimeException('An explicitly disposable /tmp/zc-* WordPress fixture is required.');
}
require $path . '/wp-load.php';
if (!defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('Disposable local fixture declaration missing.');
}
if (!class_exists(\Zarincode\Runtime260\Content\Article_Rating_Service::class)) {
    throw new RuntimeException('Activate the current companion plugin in the disposable site first.');
}
