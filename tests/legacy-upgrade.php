<?php
$old='Zarincode\\Core\\PostTypes\\CPT_Manager';
if(!class_exists($old,false)) throw new RuntimeException('Legacy plugin was not loaded for this test.');
if(method_exists($old,'register_cpts_and_taxonomies')) throw new RuntimeException('Legacy fixture must lack the failing method.');
foreach($GLOBALS['wp_filter'] as $hook=>$obj) {
    foreach($obj->callbacks as $callbacks) foreach($callbacks as $callback) {
        $fn=$callback['function'];
        if(is_array($fn)&&is_object($fn[0])&&strpos(get_class($fn[0]),'Zarincode\\Core\\')===0) throw new RuntimeException('Legacy callback remained: '.$hook);
    }
}
\Zarincode\Runtime260\Demo\Demo_Seeder::seed_all();
echo "PASS: legacy CPT class lacking register_cpts_and_taxonomies is loaded; old callbacks detached; current demo import succeeds in the SAME request.\n";
