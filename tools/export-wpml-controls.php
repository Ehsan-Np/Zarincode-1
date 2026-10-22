<?php
/** wp eval-file; writes declarative widget translation mappings from actual registered controls. */
if(!defined('WP_CLI')||!WP_CLI||!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA)throw new RuntimeException('Disposable QA only');
$path=getenv('ZC_WPML_OUTPUT');if(!$path)throw new RuntimeException('Explicit output path required');
$doc=new DOMDocument('1.0','UTF-8');$doc->formatOutput=true;$root=$doc->appendChild($doc->createElement('wpml-config'));$list=$root->appendChild($doc->createElement('elementor-widgets'));
$allowed=static function($id,$control){
 if(!in_array($control['type']??'',['text','textarea','wysiwyg'],true))return false;
 if(preg_match('/(?:editor_search|editor_part|_id$|css|class|attribute|source|include|query|phone|email|url|selector)/i',$id))return false;
 return (bool)preg_match('/(?:title|text|description|heading|label|caption|content|subtitle|alt|name|button|feature|period|notice|role|badge)/i',$id);
};
$total=0;
foreach(\Elementor\Plugin::instance()->widgets_manager->get_widget_types() as $id=>$widget){
 if(!str_starts_with($id,'zarincode_'))continue;
 $w=$list->appendChild($doc->createElement('widget'));$w->setAttribute('name',$id);$fields=$w->appendChild($doc->createElement('fields'));
 foreach($widget->get_controls() as $key=>$control){
  if($allowed($key,$control)){$node=$fields->appendChild($doc->createElement('field',$key));$node->setAttribute('type','ZarinCode: '.$key);$node->setAttribute('editor_type',($control['type']==='wysiwyg'?'VISUAL':($control['type']==='textarea'?'AREA':'LINE')));$total++;}
  if(($control['type']??'')==='repeater'){
   $items=null;foreach($control['fields']??[] as $field=>$def){$field=is_string($field)?$field:($def['name']??'');if(!$allowed($field,$def))continue;
    if(!$items){$items=$w->appendChild($doc->createElement('fields-in-item'));$items->setAttribute('items_of',$key);}
    $node=$items->appendChild($doc->createElement('field',$field));$node->setAttribute('type','ZarinCode: '.$key.' / '.$field);$node->setAttribute('editor_type',$def['type']==='wysiwyg'?'VISUAL':($def['type']==='textarea'?'AREA':'LINE'));$total++;
   }
  }
 }
}
$previous=new DOMDocument();$previous->load(WP_PLUGIN_DIR.'/zarincode-core/wpml-config.xml');foreach($previous->documentElement->childNodes as $child)if($child instanceof DOMElement && $child->tagName!=='elementor-widgets')$root->appendChild($doc->importNode($child,true));
$doc->save($path);echo $list->childNodes->length.' widget mappings / '.$total.' text mappings exported; runtime WPML approval still requires plugin tests.'.PHP_EOL;
