<?php
/** Run with wp eval-file tests/native-design.php on a disposable WordPress installation. */
use Zarincode\Runtime260\Widgets\Native_Design as N;
use Zarincode\Runtime260\Widgets\Widget_Schema as S;
use Zarincode\Runtime260\Widgets\Renderer;
$passed=0;$checks=static function($ok,$message)use(&$passed){if(!$ok)throw new RuntimeException($message);$passed++;};
$manager=Elementor\Plugin::instance()->elements_manager;
$make=static fn($kind,$settings=[],$id='nativeprobe')=>$manager->create_element_instance(['id'=>$id,'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$settings]);
$render=static function($w){ob_start();$w->render_content();return ob_get_clean();};
$compare=[];
foreach(array_keys(S::definitions()) as $kind){
    foreach($kind==='page_section'?array_keys(S::sections()):['about-page'] as $section){
        $m=N::model($kind,$section);$w=$make($kind,['section_key'=>$section]);$controls=$w->get_controls();$html=$render($w);
        $checks(str_contains($html,'zc-native-layout'),'Native renderer: '.$m['name']);
        $checks(!str_contains($html,'original_texts.'),'No DOM-text settings in output');
        $checks(!preg_match('/<p[^>]*>\s*<p\b/',$html),'No nested paragraphs');
        foreach($m['fields'] as $key=>$def){
            $id=N::id($m,$key);$checks(isset($controls[$id]),'Registered native field '.$id);
            $settings=['section_key'=>$section];$sample='NATIVE_'.substr(hash('sha256',$key),0,12);
            if($def['type']==='text'){$settings[$id]=$sample;$checks(str_contains($render($make($kind,$settings)),$sample),'Text drives output '.$id);}
            if($def['type']==='icons'){$settings[$id]=['value'=>'fas fa-anchor','library'=>'fa-solid'];$checks(str_contains($render($make($kind,$settings)),'e-fas-anchor'),'Native icon drives output '.$id);}
            if($def['type']==='media'){$settings[$id]=['id'=>0,'url'=>'https://example.org/'.$sample.'.jpg'];$checks(str_contains($render($make($kind,$settings)),'https://example.org/'.$sample.'.jpg'),'Media drives output '.$id);}
            if($def['type']==='wysiwyg'){$settings[$id]='<strong>'.$sample.'</strong>';$checks(str_contains($render($make($kind,$settings)),'<strong>'.$sample.'</strong>'),'Rich text drives output '.$id);}
            if($def['type']==='url'){if(isset($controls[substr($id,0,-4).'action']))$settings[substr($id,0,-4).'action']='url';$settings[$id]=['url'=>'https://example.org/'.$sample,'is_external'=>'yes','nofollow'=>'yes','custom_attributes'=>'data-proof|native,onmouseover|alert(1)'];$out=$render($make($kind,$settings));$checks(str_contains($out,'https://example.org/'.$sample)&&str_contains($out,'data-proof="native"')&&!str_contains($out,'onmouseover='),'Safe URL object '.$id);}
        }
        foreach($m['repeaters'] as $key=>$rep){
            $id=N::id($m,$key);$rows=$rep['default'];$rows[]=$rows[0];unset($rows[count($rows)-1]['_id']);$rows=array_reverse($rows);
            $w2=$make($kind,['section_key'=>$section,$id=>$rows]);$normalized=$w2->get_settings($id);$ids=array_column($normalized,'_id');
            $checks(count($ids)===count(array_unique($ids))&&count($ids)===count($rows),'Unique pre-CSS identities '.$id);
            $out=$render($w2);$checks(str_contains($out,'elementor-repeater-item-'.$ids[0]),'Row CSS identity rendered '.$id);
            $checks(!str_contains($render($make($kind,['section_key'=>$section,$id=>[]])),'elementor-repeater-item-'.$rep['default'][0]['_id']),'Empty rows respected '.$id);
            foreach($rep['fields'] as $field=>$def){if($def['type']==='icons'){$changed=$rep['default'];$changed[0][$field]=['value'=>'fas fa-anchor','library'=>'fa-solid'];if(in_array($changed[0]['_variant'],$def['variants'],true))$checks(str_contains($render($make($kind,['section_key'=>$section,$id=>$changed])),'e-fas-anchor'),'Repeater native icon '.$id.'.'.$field);}if($def['type']!=='text')continue;$changed=$rep['default'];$sample='ROW_'.substr(hash('sha256',$field),0,12);$index=0;foreach($changed as $i=>$r)if(in_array($r['_variant'],$def['variants'],true)){$index=$i;break;}$changed[$index][$field]=$sample;
                $checks(str_contains($render($make($kind,['section_key'=>$section,$id=>$changed])),$sample),'Repeater text drives output '.$id.'.'.$field);
            }
        }
        foreach(array_slice($m['legacy'],0,2,true) as $path=>$map){
            $old=$kind==='page_section'?'original_texts_'.str_replace('-','_',$section):'original_texts';$raw=['section_key'=>$section,$old=>[['path'=>$path,'baseline'=>$map['baseline'],'text'=>'LEGACY_PRESERVED']]];
            $oldWidget=$make($kind,$raw);$checks(str_contains($render($oldWidget),'LEGACY_PRESERVED'),'Legacy saved edit renders '.$m['name']);$export=$oldWidget->get_raw_data();$checks(str_contains(wp_json_encode($export['settings']),'LEGACY_PRESERVED'),'Legacy exported settings migrated');
        }
        $compare[]=['kind'=>$kind,'section'=>$section,'old'=>Renderer::original($kind,['section_key'=>$section]),'new'=>N::render($make($kind,['section_key'=>$section]),$make($kind,['section_key'=>$section])->get_settings_for_display(),$kind)];
        echo $m['name']." PASS\n";
    }
}
$checks(count(N::model('brands')['repeaters'])===1,'Brand logos are real repeater records');
$checks(N::model('page_section','../../private')===[],'Template path traversal rejected');
foreach(['services','products','portfolio','blog'] as $kind){$m=N::model($kind);$items=Zarincode\Runtime260\Widgets\Editable_Renderer::query_items($kind,['count'=>2]);$html=$render($make($kind,[N::id($m,'source')=>'query',N::id($m,'count')=>2]));foreach($items as $item)$checks(str_contains($html,esc_html($item['title'])),'Query title '.$kind);}
file_put_contents('/tmp/zc300-compare.json',wp_json_encode($compare));
echo "PASS {$passed} native-design assertions; 23 layouts.\n";
