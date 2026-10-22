<?php
/** Standalone: php tests/layout-width-3.7.5.php */
define('ABSPATH',__DIR__);require dirname(__DIR__).'/zarincode/inc/Core/Layout.php';
use Zarincode\Theme\Core\Layout;
$n=0;$test=static function($ok)use(&$n){if(!$ok)throw new RuntimeException('Layout assertion '.($n+1).' failed');$n++;};
$a=Layout::responsive([]);$test($a['desktop']===1469&&$a['tablet']===1469&&$a['mobile']===1469);$test($a['gutter']===32.0&&$a['tablet_gutter']===24.0&&$a['mobile_gutter']===16.0&&$a['small_gutter']===12.0);
foreach(Layout::RANGES as $key=>[$min,$max]){
 foreach(['',0,'0',null,[],new stdClass(),false,'calc(100vw)','1500.5','-100'] as $v)$test(Layout::optional($v,$key)==='');
 $test(Layout::optional(1,$key)===(string)$min);$test(Layout::optional('99999999',$key)===(string)$max);$test(Layout::optional($min,$key)===(string)$min);$test(Layout::optional($max,$key)===(string)$max);
 $normal=Layout::normalize([$key=>'99999999','other'=>['retain'=>1]]);$test($normal[$key]===(string)$max&&$normal['other']===['retain'=>1]);
}
foreach([1200,1469,1600,1920] as $v){$a=Layout::responsive(['layout_width_desktop'=>$v,'layout_width_scale'=>108]);$test($a['desktop']===$v&&$a['tablet']===$v&&$a['mobile']===$v);$test($a['gutter']>=24&&$a['gutter']<=40);}
$a=Layout::responsive(['layout_width_desktop'=>1600,'layout_width_tablet'=>900]);$test($a['tablet']===900&&$a['mobile']===900);
$a=Layout::responsive(['layout_width_desktop'=>1600,'layout_width_tablet'=>900,'layout_width_mobile'=>360]);$test($a['desktop']===1600&&$a['tablet']===900&&$a['mobile']===360);
$test(Layout::responsive(['container_max_width'=>'1600px','layout_width_scale'=>100])['desktop']===1600);$test(Layout::responsive(['container_max_width'=>'1600px','layout_width_scale'=>108])['desktop']===1728);
$test(Layout::normalize('no-array')==='no-array');echo "PASS $n responsive sizing assertions\n";
