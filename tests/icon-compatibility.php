<?php
/** wp eval-file, disposable fixture with the unmodified Elementor plugin. */
use Zarincode\Runtime260\Widgets\Icon_Compatibility as I;
use Zarincode\Runtime260\Widgets\Widget_Schema;
$checks=0;$check=static function($ok,$label)use(&$checks){if(!$ok)throw new RuntimeException($label);$checks++;};
$cases=[
    [null,''],[[], ''],[['value'=>null,'library'=>'fa-solid'],''],
    [['value'=>['bad'],'library'=>'fa-solid'],''],[['value'=>'fas fa-does-not-exist-zc','library'=>'fa-solid'],''],
    [['value'=>'fa-solid fa-circle-check','library'=>'fa-solid'],'fas fa-check-circle'],
    [['value'=>'fa-solid fa-file-Lines','library'=>'fa-solid'],'fas fa-file-alt'],
    [['value'=>'fab fa-github','library'=>'fa-solid'],'fab fa-github'],
    [['value'=>'fas fa-check','library'=>'fa-invalid'],'fas fa-check'],
    ['fa fa-close','fas fa-times'],[['value'=>'fas fa-check','library'=>'fa-solid'],'fas fa-check'],
    [['value'=>'anything<script>','library'=>'not-registered'],''],
    [['value'=>['id'=>null,'url'=>'https://example.org/a.svg'],'library'=>'svg'],''],
];
foreach($cases as [$value,$expected])$check(I::value($value)['value']===$expected,'Icon normalization '.wp_json_encode($value));
set_error_handler(static function($level,$message,$file){if(str_contains($file,'font-icon-svg'))throw new RuntimeException($message);return false;});
try{
    foreach(array_keys(Widget_Schema::definitions())+[] as $kind){
        $proto=Elementor\Plugin::instance()->widgets_manager->get_widget_types('zarincode_'.$kind);$settings=[];
        foreach($proto->get_controls() as $key=>$control)if(($control['type']??'')==='icons')$settings[$key]=['value'=>'fas fa-zc-invalid-name','library'=>'fa-solid'];
        $settings['design_mode']='custom';$settings['items']=[['title'=>'Bad nested icon','icon'=>['value'=>'fa-solid fa-shield-halved','library'=>'fa-solid'],'visible'=>'yes']];
        $w=Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'icon'.$kind,'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$settings]);
        ob_start();$w->render_content();$html=ob_get_clean();$check(!str_contains($html,'zc-invalid-name'),'Invalid icons do not reach SVG renderer '.$kind);
        $export=$w->get_raw_data();foreach($settings as $key=>$value)if(($proto->get_controls($key)['type']??'')==='icons')$check(($export['settings'][$key]['value']??null)==='','Editor export repaired '.$kind.' '.$key);
        $check($w->get_settings('items')[0]['icon']['value']==='fas fa-shield-alt','Nested legacy icon normalized '.$kind);
    }
    foreach(array_keys(Zarincode\Runtime260\Widgets\Professional_Widget::catalog()) as $kind){
        $w=Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'bad'.str_replace('_','',$kind),'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>['records'=>[['title'=>'ICON TEST','icon'=>['value'=>'fas fa-zc-invalid-name','library'=>'fa-solid']]]]]);
        ob_start();$w->render_content();$html=ob_get_clean();$check(!str_contains($html,'zc-invalid-name'),'Professional invalid icon guarded '.$kind);
        if(isset($w->get_controls('records')['fields']['icon']))$check($w->get_raw_data()['settings']['records'][0]['icon']['value']==='','Professional export repaired '.$kind);
    }
    $check(I::render(['value'=>'fas fa-zc-invalid-name','library'=>'fa-solid'])==='','Static renderer guard');
}finally{restore_error_handler();}
echo "PASS $checks icon compatibility checks on 20 widget types\n";
