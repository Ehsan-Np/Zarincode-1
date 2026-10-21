<?php
/** Run only on a disposable WordPress + Elementor installation. */
use Zarincode\Runtime260\Widgets\Inline_Editing;
use Zarincode\Runtime260\Widgets\Renderer;
use Zarincode\Runtime260\Widgets\Professional_Widget;
$checks=0;$check=static function($ok,$msg)use(&$checks){if(!$ok)throw new RuntimeException($msg);$checks++;echo 'PASS '.$msg."\n";};
$manager=\Elementor\Plugin::instance()->elements_manager;
$rows=Inline_Editing::original_defaults('hero');
$check(count($rows)>20,'original hero has independent text controls');
$widget=$manager->create_element_instance(['id'=>'origtest','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>[]]);
$defaults=$widget->get_settings_for_display();$defaults['original_texts']=$rows; // Explicit fixture for the retired 2.7 adapter, not the current UI.
$original=Renderer::original('hero');
$check(Inline_Editing::original($original,$defaults,$widget,'original_texts')===$original,'unedited original hero is byte-for-byte unchanged');
$edit=$defaults['original_texts'];$edit[0]['text']='<strong>ORIGINAL SAVED EDIT</strong>';
$html=Inline_Editing::original($original,['original_texts'=>$edit],$widget,'original_texts');
$check(strpos($html,'ORIGINAL SAVED EDIT')!==false,'saved original text applied to original DOM');
$check(strpos($html,'zc-hero-main-card')!==false,'original bento structure retained after edit');
$edit[0]['baseline']='stale';$check(Inline_Editing::original($original,['original_texts'=>$edit],$widget,'original_texts')===$original,'stale source paths cannot overwrite different dynamic content');
foreach(array_keys(\Zarincode\Runtime260\Widgets\Widget_Schema::definitions()) as $kind){
 $sections=$kind==='page_section'?array_keys(\Zarincode\Runtime260\Widgets\Widget_Schema::sections()):['about-page'];
 foreach($sections as $section){
  $instance=$manager->create_element_instance(['id'=>'unchanged'.str_replace('_','',$kind),'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>['section_key'=>$section]]);
  $key=$kind==='page_section'?'original_texts_'.str_replace('-','_',$section):'original_texts';
  $raw=Renderer::original($kind,['section_key'=>$section]);
  $check(Inline_Editing::original($raw,$instance->get_settings_for_display(),$instance,$key)===$raw,'unedited original unchanged '.$kind.' '.$section);
 }
}
// Decorated text leaves keep the old pill, icon classes and independently edited siblings.
$rich=$defaults['original_texts'];$leafCount=0;
foreach($rich as &$row)if(strpos($row['path'],'/h1/')!==false){$row['text']='LEAF '.$leafCount++;}unset($row);
$leafHtml=Inline_Editing::original($original,['original_texts'=>$rich],$widget,'original_texts');
$check($leafCount>=2,'decorated title exposes independent text leaves');
$check(strpos($leafHtml,'zc-hero-inline-pill')!==false,'editing headline preserves decorated pill class');
$check(strpos($leafHtml,'fa-')!==false,'editing headline preserves icon classes');
for($i=0;$i<$leafCount;$i++)$check(strpos($leafHtml,'LEAF '.$i)!==false,'multiple text leaf edits survive XPath changes '.$i);
$bad=[['path'=>[],'baseline'=>[],'text'=>[]],['path'=>'//script','baseline'=>'x','text'=>'x']];
$check(Inline_Editing::original($original,['original_texts'=>$bad],$widget,'original_texts')===$original,'malformed original overrides fail closed');
$rich=$defaults['original_texts'];foreach($rich as &$row)if(strpos($row['path'],'/h1/')!==false)$row['text']='<script>alert(1)</script><strong onclick="alert(2)">SAFE LEAF</strong>';unset($row);
$safeHtml=Inline_Editing::original($original,['original_texts'=>$rich],$widget,'original_texts');
$check(strpos($safeHtml,'<script>')===false&&strpos($safeHtml,'onclick=')===false,'text leaf edits sanitize script and event handlers');
foreach(array_keys(Professional_Widget::catalog()) as $kind){
    $prototype=\Elementor\Plugin::instance()->widgets_manager->get_widget_types('zarincode_'.$kind);
    $check((bool)$prototype,$kind.' registered');
    foreach(['title','intro','section_padding','heading_type_font_family'] as $key)$check($prototype->get_controls($key)!==null,$kind.' control '.$key);
    $grid=!in_array($kind,['before_after','service_matrix'],true);$check(($prototype->get_controls('columns')!==null)===$grid,$kind.' only exposes applicable grid controls');
    $instance=$manager->create_element_instance(['id'=>'test'.str_replace('_','',$kind),'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>['title'=>'CUSTOM PROFESSIONAL TITLE','records'=>[['title'=>'CUSTOM ROW','text'=>'CUSTOM BODY','value'=>450,'column_1'=>'CUSTOM CELL','_id'=>'','link'=>['url'=>'https://example.org'],'button'=>'CUSTOM ACTION']]]]);
    ob_start();$instance->render_content();$html=ob_get_clean();
    $check(strpos($html,'CUSTOM PROFESSIONAL TITLE')!==false,$kind.' renders saved heading');
    if($kind!=='before_after'){$check(strpos($html,'CUSTOM ROW')!==false,$kind.' renders repeaters');$check(!empty($instance->get_settings_for_display('records')[0]['_id']),$kind.' repairs missing row ID');}
    if($kind==='seo_audit')$check(strpos($html,'aria-valuenow="100"')!==false,'audit score clamped to 100');
    if($kind==='before_after')$check(strpos($html,'type="range"')!==false,'comparison has native keyboard-accessible range');
}
// Native editor keys on custom data must not collide when visual classes are shared.
\Elementor\Plugin::instance()->editor->set_edit_mode(true);
$inlineWidget=$manager->create_element_instance(['id'=>'custominline','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>['design_mode'=>'custom']]);
$sample='<div class="elementor-repeater-item-item01"><h3 class="zc-edit-card-title">Title</h3><div class="zc-edit-subtitle zc-edit-role">Role</div><span class="zc-edit-badge zc-edit-number">01</span><span class="zc-edit-price-current">50</span><small class="zc-edit-period">Month</small></div>';
$html=Inline_Editing::custom($sample,['__kind'=>'hero','items'=>[['_id'=>'item01']]],$inlineWidget);
[$dom,$xp,$root]=Inline_Editing::document($html);
foreach(['zc-edit-card-title'=>'title','zc-edit-role'=>'role','zc-edit-number'=>'number','zc-edit-price-current'=>'price','zc-edit-period'=>'period'] as $class=>$field){
 $node=Inline_Editing::class_nodes($xp,$class,$root)->item(0);
 $check($node&&$node->getAttribute('data-elementor-setting-key')==='items.0.'.$field,'native custom key '.$field);
}
$nav=Inline_Editing::custom('<ul><li class="elementor-repeater-item-nav01"><a>Menu</a></li></ul>',['__kind'=>'header','items'=>[['_id'=>'nav01']]],$inlineWidget);
$check(strpos($nav,'data-elementor-setting-key="items.0.title"')!==false,'native navigation repeater binding');
$legal=Inline_Editing::custom('<article class="zc-edit-document"><h2 class="zc-edit-title">Terms</h2><section class="zc-edit-clause"><h3>Clause</h3><div class="zc-edit-description">Body</div></section></article>',['__kind'=>'page_section','section_key'=>'terms'],$inlineWidget);
$check(strpos($legal,'data-elementor-setting-key="document_terms_title"')!==false,'native legal heading binding');
$check(strpos($legal,'data-elementor-setting-key="document_terms_clauses.0.description"')!==false,'native legal clause body binding');
\Elementor\Plugin::instance()->editor->set_edit_mode(false);
echo 'TOTAL '.$checks." checks passed\n";
