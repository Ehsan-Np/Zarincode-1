<?php
/** Standalone unit test: php tests/layout-width-3.7.3.php */
define('ABSPATH',__DIR__);require dirname(__DIR__).'/zarincode/inc/Core/Layout.php';
use Zarincode\Theme\Core\Layout;
$n=0;$test=static function($ok)use(&$n){if(!$ok)throw new RuntimeException('Width assertion failed');$n++;};
foreach(['1280px'=>1382,'1360px'=>1469,'1440px'=>1555,'1600px'=>1728] as $base=>$width){$test(Layout::width($base,108)===$width.'px');$test(Layout::width($base,100)===$base);$test(Layout::width($base,108)===Layout::width($base,108));}
foreach([null,[],new stdClass(),'bad'] as $base)$test(Layout::width($base,108)==='1469px');
$test(Layout::width('1360px',[])==='1469px');echo "PASS $n shared-width assertions\n";
