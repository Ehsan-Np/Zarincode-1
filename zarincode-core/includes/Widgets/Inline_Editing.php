<?php
/** Native Elementor inline editing. No independent contenteditable/autosave implementation. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Inline_Editing {
    public static function document(string $html): array {
        $dom=new \DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="zc-inline-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD|LIBXML_NONET);
        libxml_clear_errors();libxml_use_internal_errors($previous);
        return [$dom,new \DOMXPath($dom),$dom->getElementById('zc-inline-root')];
    }
    public static function html(\DOMNode $node): string {
        if($node instanceof \DOMText)return esc_html($node->nodeValue);
        $html='';foreach($node->childNodes as $child)$html.=$node->ownerDocument->saveHTML($child);return $html;
    }
    public static function class_nodes(\DOMXPath $xp,string $class,?\DOMNode $root=null): \DOMNodeList {
        return $xp->query('.//*[contains(concat(" ",normalize-space(@class)," ")," '.$class.' ")]',$root);
    }
    public static function bind(\DOMElement $node,$widget,string $key,string $toolbar='none'): void {
        foreach($widget->inline_attributes($key,$toolbar) as $name=>$values){
            $value=implode(' ',$values);
            $node->setAttribute($name,$name==='class'?trim($node->getAttribute('class').' '.$value):$value);
        }
    }
    public static function replace(\DOMElement $node,string $html): void {
        [$doc,$xp,$root]=self::document(wp_kses_post($html));
        while($node->firstChild)$node->removeChild($node->firstChild);
        foreach(iterator_to_array($root->childNodes) as $child)$node->appendChild($node->ownerDocument->importNode($child,true));
    }
    /** Keep decorated child markup out of the editable range (native editor strips classes).
     * Plain semantic blocks stay whole; mixed blocks expose their non-empty text leaves.
     * Temporary wrappers exist only in the editor, never in unchanged public HTML. */
    private static function blocks(\DOMElement $root): array {
        $blocks=[];
        $walk=function(\DOMElement $el)use(&$walk,&$blocks){
            $tag=strtolower($el->tagName);
            if(in_array($tag,['script','style','svg','i','noscript','select','textarea'],true))return;
            $text=trim($el->textContent);
            $hasBlock=false;$hasMedia=false;
            foreach($el->getElementsByTagName('*') as $desc){
                if(in_array(strtolower($desc->tagName),['div','section','article','aside','ul','ol','p','h1','h2','h3','h4','h5','h6','table','tr','form','details'],true))$hasBlock=true;
                if(in_array(strtolower($desc->tagName),['img','input','svg','select','textarea'],true))$hasMedia=true;
            }
            if($text!==''&&!$hasBlock&&!$hasMedia&&in_array($tag,['h1','h2','h3','h4','h5','h6','p','a','button','span','li','label','summary','strong','small','div','td','th','blockquote','figcaption'],true)){
                $mixed=false;foreach($el->childNodes as $child)if($child instanceof \DOMElement){$mixed=true;break;}
                if(!$mixed){$blocks[]=$el;return;}
                $leaves=function($node)use(&$leaves,&$blocks){
                    foreach($node->childNodes as $child){
                        if($child instanceof \DOMText&&trim($child->nodeValue)!=='')$blocks[]=$child;
                        elseif($child instanceof \DOMElement&&!in_array(strtolower($child->tagName),['script','style','svg','i','noscript'],true))$leaves($child);
                    }
                };
                $leaves($el);return;
            }
            foreach($el->childNodes as $child){
                if($child instanceof \DOMElement)$walk($child);
                elseif($child instanceof \DOMText&&trim($child->nodeValue)!=='')$blocks[]=$child;
            }
        };
        foreach($root->childNodes as $child)if($child instanceof \DOMElement)$walk($child);
        return $blocks;
    }
    public static function original_defaults(string $kind,string $section='about-page'): array {
        if(!class_exists('DOMDocument'))return [];
        static $cache=[];$key=$kind.':'.$section;
        if(isset($cache[$key]))return $cache[$key];
        [$dom,$xp,$root]=self::document(Renderer::original($kind,['section_key'=>$section]));
        $rows=[];
        foreach(self::blocks($root) as $node){
            $html=self::html($node);$path=$node->getNodePath();$baseline=hash('sha256',$html);
            $rows[]=['_id'=>substr(hash('sha256',$path.$baseline),0,8),'path'=>$path,'baseline'=>$baseline,'label'=>mb_substr(trim(preg_replace('/\s+/u',' ',$node->textContent)),0,85),'text'=>$html];
        }
        return $cache[$key]=$rows;
    }
    public static function original(string $html,array $settings,$widget,string $key): string {
        if(!class_exists('DOMDocument'))return $html;
        $editor=\Elementor\Plugin::instance()->editor->is_edit_mode();
        $rows=$settings[$key]??[];
        if(!$editor&&!$rows)return $html;
        [$dom,$xp,$root]=self::document($html);$changed=false;
        // Resolve every original XPath before inserting editor wrappers / rich inline nodes.
        $targets=[];
        foreach($rows as $index=>$row){
            $path=$row['path']??'';
            if(!is_string($path)||!is_string($row['baseline']??null))continue;
            if(!preg_match('#^/div(?:/[-a-zA-Z0-9]+(?:\[\d+\])?)*(?:/text\(\)(?:\[\d+\])?)?$#D',$path))continue;
            $node=$xp->query($path)->item(0);
            if(!($node instanceof \DOMElement||$node instanceof \DOMText)||!hash_equals($row['baseline'],hash('sha256',self::html($node))))continue;
            $targets[]=[$index,$row,$node];
        }
        foreach($targets as [$index,$row,$node]){
            $value=is_scalar($row['text']??'')?(string)($row['text']??''):'';
            if($node instanceof \DOMText){
                // Do not allow block markup or nested anchors inside a decorated heading/button.
                if($value!==self::html($node)){preg_match('/^\s*/u',$node->nodeValue,$leading);preg_match('/\s*$/u',$node->nodeValue,$trailing);$value=($leading[0]??'').trim($value).($trailing[0]??'');}
                $value=wp_kses($value,['strong'=>[],'b'=>[],'em'=>[],'i'=>[],'u'=>[],'s'=>[],'br'=>[],'code'=>[],'span'=>['style'=>true]]);
                if($editor){
                    $span=$dom->createElement('span');$span->setAttribute('class','zc-inline-text');
                    $node->parentNode->replaceChild($span,$node);self::replace($span,$value);
                    self::bind($span,$widget,$key.'.'.$index.'.text','basic');$changed=true;
                }elseif($value!==self::html($node)){
                    [$fragmentDoc,$fragmentXp,$fragmentRoot]=self::document($value);
                    foreach(iterator_to_array($fragmentRoot->childNodes) as $child)$node->parentNode->insertBefore($dom->importNode($child,true),$node);
                    $node->parentNode->removeChild($node);$changed=true;
                }
            }else{
                if($value!==self::html($node)){self::replace($node,$value);$changed=true;}
                if($editor){self::bind($node,$widget,$key.'.'.$index.'.text','advanced');$changed=true;}
            }
        }
        return $changed?self::html($root):$html;
    }
    public static function custom(string $html,array $s,$widget): string {
        if(!class_exists('DOMDocument'))return $html;
        if(!\Elementor\Plugin::instance()->editor->is_edit_mode())return $html;
        [$dom,$xp,$root]=self::document($html);
        foreach(['zc-edit-title'=>['title','none'],'zc-edit-subtitle'=>['subtitle','none'],'zc-edit-description'=>['description','advanced'],'zc-edit-badge'=>['badge','none'],'zc-edit-copyright'=>['copyright','none']] as $class=>[$key,$toolbar]){
            foreach(self::class_nodes($xp,$class,$root) as $node){
                if(($node->parentNode instanceof \DOMElement&&strpos($node->parentNode->getAttribute('class'),'zc-edit-copy')!==false)||$class==='zc-edit-copyright')self::bind($node,$widget,$key,$toolbar);
            }
        }
        foreach(['primary','secondary'] as $prefix)foreach(self::class_nodes($xp,'zc-edit-actions',$root) as $actions){
            if(strpos($actions->parentNode->getAttribute('class'),'zc-edit-copy')===false)continue;
            foreach(self::class_nodes($xp,'zc-edit-'.$prefix,$actions) as $button)self::bind($button,$widget,$prefix.'_text');
        }
        if(($s['source']??'manual')==='manual')foreach($s['items']??[] as $index=>$item){
            if(empty($item['_id']))continue;
            foreach(self::class_nodes($xp,'elementor-repeater-item-'.$item['_id'],$root) as $card){
                foreach(['zc-edit-card-title'=>['title','none'],'zc-edit-card-description'=>['description','advanced'],'zc-edit-subtitle'=>['subtitle','none'],'zc-edit-role'=>['role','none'],'zc-edit-number'=>['number','none'],'zc-edit-price-current'=>['price','none'],'zc-edit-price-old'=>['old_price','none'],'zc-edit-period'=>['period','none'],'zc-edit-badge'=>['badge','none'],'zc-edit-secondary'=>['demo_text','none'],'zc-edit-primary'=>['button_text','none']] as $class=>[$field,$toolbar]){
                    foreach(self::class_nodes($xp,$class,$card) as $node){
                        $classes=' '.$node->getAttribute('class').' ';
                        if(($class==='zc-edit-subtitle'&&strpos($classes,' zc-edit-role ')!==false)||($class==='zc-edit-badge'&&strpos($classes,' zc-edit-number ')!==false))continue;
                        self::bind($node,$widget,'items.'.$index.'.'.$field,$toolbar);
                    }
                }
            }
        }
        if(in_array($s['__kind']??'',['header','footer','brands'],true))foreach($s['items']??[] as $index=>$item){
            if(empty($item['_id']))continue;
            foreach(self::class_nodes($xp,'elementor-repeater-item-'.$item['_id'],$root) as $li){$a=$li->getElementsByTagName('a')->item(0);if($a)self::bind($a,$widget,'items.'.$index.'.title');}
        }
        if(($s['__kind']??'')==='page_section'){
            $prefix='document_'.str_replace('-','_',$s['section_key']??'about-page').'_';
            foreach(self::class_nodes($xp,'zc-edit-document',$root) as $doc){
                if(($s['page_content']??'')!==''){self::bind($doc,$widget,'page_content','advanced');continue;}
                foreach($doc->childNodes as $child){if(!$child instanceof \DOMElement)continue;$class=$child->getAttribute('class');
                    if(strpos($class,'zc-edit-title')!==false)self::bind($child,$widget,$prefix.'title');
                    elseif(strpos($class,'zc-edit-badge')!==false)self::bind($child,$widget,$prefix.'badge');
                    elseif(strpos($class,'zc-edit-description')!==false)self::bind($child,$widget,$prefix.'description','advanced');
                }
                foreach(self::class_nodes($xp,'zc-edit-clause',$doc) as $index=>$clause){$h=$clause->getElementsByTagName('h3')->item(0);if($h)self::bind($h,$widget,$prefix.'clauses.'.$index.'.title');foreach(self::class_nodes($xp,'zc-edit-description',$clause) as $body)self::bind($body,$widget,$prefix.'clauses.'.$index.'.description','advanced');}
            }
        }
        foreach($s['stats']??[] as $index=>$stat){
            $node=self::class_nodes($xp,'zc-edit-stat',$root)->item($index);if(!$node)continue;
            foreach(['strong'=>'value','span'=>'title'] as $tag=>$field){$el=$node->getElementsByTagName($tag)->item($tag==='span'&& !empty($stat['icon']['value'])?1:0);if($el)self::bind($el,$widget,'stats.'.$index.'.'.$field);}
        }
        foreach($s['faq']??[] as $index=>$faq){$node=self::class_nodes($xp,'zc-edit-faq',$root)->item($index);if(!$node)continue;$el=$node->getElementsByTagName('summary')->item(0);if($el)self::bind($el,$widget,'faq.'.$index.'.title');foreach(self::class_nodes($xp,'zc-edit-description',$node) as $el)self::bind($el,$widget,'faq.'.$index.'.answer','advanced');}
        return self::html($root);
    }
}
