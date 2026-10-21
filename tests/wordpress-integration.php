<?php
/** Run with wp eval-file tests/wordpress-integration.php on a disposable WP site. */
$GLOBALS['zc_failures']=[];
function zc_check($ok,$message){if(!$ok)$GLOBALS['zc_failures'][]=$message;echo ($ok?'PASS ':'FAIL ').$message."\n";}
$manager=\Elementor\Plugin::instance()->widgets_manager;
$types=$manager->get_widget_types();
$names=['header','footer','hero','brands','services','products','portfolio','why_us','pricing','promo','steps','testimonials','blog','page_section'];
foreach($names as $kind){
    $name='zarincode_'.$kind;
    zc_check(isset($types[$name]),$name.' registered');
    if(!isset($types[$name])) continue;
    $widget=$types[$name];$controls=$widget->get_controls();
    foreach(['title','description','image','items','primary_link','card_typography_font_family','columns','section_padding','icon_size','layout'] as $control) zc_check($widget->get_controls($control)!==null,$name.' control '.$control);
    $instance=\Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'test'.str_replace('_','',$kind),'elType'=>'widget','widgetType'=>$name,'settings'=>['design_mode'=>'custom','title'=>'CUSTOM TITLE <script>x</script>','description'=>'<p>CUSTOM DESCRIPTION</p>','source'=>'manual','items'=>[['_id'=>'record1','title'=>'CUSTOM RECORD','description'=>'<p>RECORD TEXT</p>','visible'=>'yes','link'=>['url'=>'https://example.org/custom','is_external'=>true],'icon'=>['value'=>'fas fa-heart','library'=>'fa-solid'],'button_text'=>'CUSTOM BUTTON']]]]);
    if(!$instance){zc_check(false,$name.' instantiated');continue;}
    ob_start();$instance->render_content();$html=ob_get_clean();
    zc_check(strpos($html,'CUSTOM TITLE')!==false,$name.' custom heading rendered');
    zc_check(strpos($html,'CUSTOM RECORD')!==false,$name.' manual repeater rendered');
    zc_check(strpos($html,'<script>x</script>')===false,$name.' text escaped');
    zc_check(strpos($html,'https://example.org/custom')!==false,$name.' custom URL rendered');
    zc_check(strpos($html,'noopener')!==false,$name.' external URL safe');
    echo 'CONTROLS '.$name.' '.count($controls)."\n";
}
$legal=$manager->get_widget_types('zarincode_page_section');
zc_check($legal->get_controls('document_dmca_clauses')!==null,'legal clauses use an editable repeater');
$html=\Zarincode\Runtime260\Widgets\Editable_Renderer::render('page_section',['section_key'=>'dmca','document_dmca_title'=>'CUSTOM LEGAL TITLE','document_dmca_description'=>'<p>CUSTOM INTRO</p>','document_dmca_clauses'=>[['title'=>'CUSTOM CLAUSE','description'=>'<p>CUSTOM BODY</p>','visible'=>'yes']]],'legaltest');
zc_check(strpos($html,'CUSTOM LEGAL TITLE')!==false&&strpos($html,'CUSTOM CLAUSE')!==false&&strpos($html,'CUSTOM BODY')!==false,'legal title and clause settings rendered');
$before=[];
foreach(['page','zc_service','zc_project','product','zc_hf_template'] as $type)$before[$type]=wp_count_posts($type)->publish??0;
\Zarincode\Runtime260\Demo\Demo_Seeder::seed_all();
foreach($before as $type=>$count) zc_check((wp_count_posts($type)->publish??0)===$count,'reimport idempotent '.$type);
zc_check(post_type_exists('zc_service')&&post_type_exists('zc_project'),'CPTs registered');
if($GLOBALS['zc_failures']) throw new RuntimeException(count($GLOBALS['zc_failures']).' failed assertions');
echo "ALL INTEGRATION TESTS PASSED\n";
