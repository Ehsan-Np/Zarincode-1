<?php
/** Standalone PHP regression: corrupt/missing files in a fake fixture, never edit vendor files. */
namespace Elementor {class Utils{public static function file_get_contents($path){return file_get_contents($path);}}}
namespace {
    define('ABSPATH',__DIR__.'/');
    $dir=sys_get_temp_dir().'/zc-broken-icons-'.getmypid().'/';
    mkdir($dir.'lib/font-awesome/json/',0777,true);define('ELEMENTOR_ASSETS_PATH',$dir);
    function absint($value){return abs((int)$value);}function esc_url_raw($value){return $value;}
    file_put_contents($dir.'lib/font-awesome/json/solid.json','{"icons":null');
    require __DIR__.'/../zarincode-core/includes/Widgets/Icon_Compatibility.php';
    set_error_handler(static function($severity,$message){throw new \RuntimeException($message);});
    try {
        $value=\Zarincode\Runtime260\Widgets\Icon_Compatibility::value(['value'=>'fas fa-check','library'=>'fa-solid']);
        if($value!==['value'=>'','library'=>''])throw new \RuntimeException('Corrupt library reached SVG manager');
        echo "PASS: null/invalid JSON and missing library files fail safely, no vendor patch or suppressed warnings\n";
    }finally{restore_error_handler();unlink($dir.'lib/font-awesome/json/solid.json');rmdir($dir.'lib/font-awesome/json');rmdir($dir.'lib/font-awesome');rmdir($dir.'lib');rmdir($dir);}
}
