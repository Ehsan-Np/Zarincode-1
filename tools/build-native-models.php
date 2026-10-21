<?php
/** Developer-only compiler. Run on a disposable WP fixture; NEVER called by theme/plugin runtime.
 * ZC_SOURCE_ROOT=/path/to/repo wp eval-file tools/build-native-models.php
 * Converts the approved demo into declarative templates + semantic control defaults.
 */
use Zarincode\Runtime260\Widgets\Renderer;
use Zarincode\Runtime260\Widgets\Inline_Editing;
use Zarincode\Runtime260\Widgets\Widget_Schema;
$root=getenv('ZC_SOURCE_ROOT')?:getcwd();
$out=$root.'/zarincode-core/includes/Widgets/models';wp_mkdir_p($out);
final class ZC_Native_Compiler {
    private array $model=[];private array $counts=[];private array $paths=[];private string $scope='';private int $rowIndex=-1;
    private function portable($v){if(is_array($v))return array_map([$this,'portable'],$v);if(!is_string($v))return $v;return str_replace([get_template_directory_uri(),untrailingslashit(home_url())],['{{THEME}}','{{HOME}}'],$v);}
    private function slug(string $v):string{$v=strtolower(preg_replace('/[^a-zA-Z0-9_]+/','_',str_replace('zc-','',$v)));return trim(substr($v,0,40),'_')?:'content';}
    private function role(DOMElement $n):string {foreach(preg_split('/\s+/',$n->getAttribute('class')) as $class)if(strpos($class,'zc-')===0&&!in_array($class,['zc-white-card','zc-glass-panel','zc-btn'],true))return $this->slug($class);return ['h1'=>'heading','h2'=>'heading','h3'=>'heading','h4'=>'heading','p'=>'description','a'=>'link','i'=>'icon','img'=>'image','button'=>'button','li'=>'list_item'][$n->tagName]??$this->slug($n->tagName);}
    private function key(string $role):string{$n=($this->counts[$role]??0)+1;$this->counts[$role]=$n;return $role.'_'.$n;}
    private function label(DOMNode $n,string $prefix=''):string{$text=trim(preg_replace('/\s+/u',' ',$n->textContent));return $prefix.($text!==''?mb_substr($text,0,65):($n instanceof DOMElement?$this->role($n):'متن'));}
    private function field(array &$defs,string $key,string $type,$default,string $label,array $extra=[]):string {$defs[$key]=array_merge(['type'=>$type,'default'=>$this->portable($default),'label'=>$label],$extra);return $key;}
    private function candidate(DOMNode $n):string{if(!$n instanceof DOMElement)return '';if($n->tagName==='li')return 'list';if(strpos($n->getAttribute('class'),'zc-partner-pill')!==false)return 'brands';foreach(preg_split('/\s+/',$n->getAttribute('class')) as $c)if(preg_match('/(?:-card|-clause|-faq-item|-trust-pill|-marquee-item|-brand-item)$/',$c))return substr($c,-5)==='-card'?'cards':$c;if($n->hasAttribute('data-filter'))return 'filters';return '';}
    private function children(DOMElement $n,array &$defs,bool $inRow=false):array {
        $children=iterator_to_array($n->childNodes);$result=[];
        for($i=0;$i<count($children);$i++){
            $child=$children[$i];$sig=$inRow?'':$this->candidate($child);$run=[];$end=$i;
            if($sig!=='')for($j=$i;$j<count($children);$j++){$x=$children[$j];if($x instanceof DOMText&&trim($x->nodeValue)===''){$end=$j;continue;}if($this->candidate($x)!==$sig)break;$run[]=$x;$end=$j;}
            if(count($run)>=2){
                $group=$this->key($this->role($n).'_items');$saved=$this->counts;$variants=[];$fields=[];$rows=[];
                foreach($run as $ix=>$item){$this->counts=[];$rowDefs=[];$oldScope=$this->scope;$this->scope=$group;$this->rowIndex=$ix;$node=$this->node($item,$rowDefs,true);$this->scope=$oldScope;$this->rowIndex=-1;$variant='v'.($ix+1);$variants[$variant]=$node;$row=['_id'=>substr(hash('sha256',$this->model['name'].$group.$ix),0,8),'_variant'=>$variant,'_enabled'=>'yes'];foreach($rowDefs as $k=>$d){$row[$k]=$d['default'];$d['variants']=array_values(array_unique(array_merge($fields[$k]['variants']??[],[$variant])));$fields[$k]=$d;} $rows[]=$row;}
                $this->counts=$saved;
                $this->model['repeaters'][$group]=['label'=>$this->label($run[0],'آیتم‌ها: '),'fields'=>$fields,'default'=>$rows,'variants'=>$variants];
                $result[]=['repeat'=>$group];$i=$end;continue;
            }
            $node=$this->node($child,$defs,$inRow);if($node!==null)$result[]=$node;
        }
        return $result;
    }
    private function node(DOMNode $n,array &$defs,bool $inRow=false):?array {
        if($n instanceof DOMComment)return ['literal'=>'<!--'.$n->nodeValue.'-->'];
        if($n instanceof DOMText){$text=$n->nodeValue;if(trim($text)==='')return ['literal'=>$text];$role=$n->parentNode instanceof DOMElement?$this->role($n->parentNode):'text';$key=$this->key($role.'_text');$this->field($defs,$key,'text',trim($text),$this->label($n,'متن: '));$parent=$n->parentNode;while($parent instanceof DOMElement){if(preg_match('/^h[1-6]$/',$parent->tagName)){$defs[$key]['purpose']='title';break;}$parent=$parent->parentNode;}if(strpos($role,'price_amount')!==false)$defs[$key]['purpose']='price';if(trim($text)==='تومان')$defs[$key]['purpose']='currency';if(strpos($role,'blog_tag')!==false||strpos($role,'product_category')!==false||strpos($role,'portfolio_tag')!==false)$defs[$key]['purpose']='category';$this->paths[$n->getNodePath()]=[$this->scope,$key,$this->rowIndex];if($n->parentNode->childNodes->length===1)$this->paths[$n->parentNode->getNodePath()]=[$this->scope,$key,$this->rowIndex];preg_match('/^\s*/u',$text,$a);preg_match('/\s*$/u',$text,$b);$part=$this->scope.'_'.$key;$this->model['parts'][$part]=['label'=>$defs[$key]['label'],'type'=>'text'];return ['text'=>$key,'part'=>$part,'before'=>$a[0]??'','after'=>$b[0]??'','plain'=>in_array($n->parentNode->nodeName,['option','textarea','title'],true)];}
        if(!$n instanceof DOMElement||in_array($n->tagName,['script','style'],true))return null;
        $role=$this->role($n);$key=$this->key($role);$part=$this->scope.'_'.$key;$attrs=[];foreach($n->attributes as $a)$attrs[$a->name]=$this->portable($a->value);
        $tag=strtolower($n->tagName);$node=['tag'=>$tag,'attrs'=>$attrs,'part'=>$part];$type=in_array($tag,['img','i'],true)?$tag:($n->getElementsByTagName('*')->length?'box':'text');
        $this->model['parts'][$part]=['label'=>$this->label($n,($type==='box'?'بخش: ':'جزء: ')),'type'=>$type];
        if($tag==='i'){$icon=$attrs['class']??'';$aliases=['file-Lines'=>'file-alt','bag-shopping'=>'shopping-bag','bars-staggered'=>'bars','building-shield'=>'building','chart-simple'=>'chart-bar','circle-check'=>'check-circle','earth-europe'=>'globe-europe','file-zipper'=>'file-archive','money-bill-transfer'=>'exchange-alt','rotate-left'=>'undo','scale-balanced'=>'balance-scale','shield-halved'=>'shield-alt','sparkles'=>'magic','user-pen'=>'user-edit'];foreach($aliases as $from=>$to)$icon=str_replace('fa-'.$from,'fa-'.$to,$icon);$icon=str_replace(['fa-solid ','fa-regular ','fa-brands '],['fas ','far ','fab '],$icon);$library=strpos($icon,'fab ')!==false?'fa-brands':(strpos($icon,'far ')!==false?'fa-regular':'fa-solid');$node['icon']=$this->field($defs,$key.'_icon','icons',['value'=>$icon,'library'=>$library],$this->label($n->parentNode,'آیکون: '));unset($node['attrs']['class']);}
        if($tag==='img'){$node['image']=$this->field($defs,$key.'_image','media',['url'=>$attrs['src']??'','id'=>0],$this->label($n,'تصویر: '));$node['alt']=$this->field($defs,$key.'_alt','text',$attrs['alt']??'','متن جایگزین تصویر');$node['size']=$this->field($defs,$key.'_size','select','full','اندازه تصویر',['options'=>['thumbnail'=>'بندانگشتی','medium'=>'متوسط','large'=>'بزرگ','full'=>'کامل']]);unset($node['attrs']['src'],$node['attrs']['alt'],$node['attrs']['srcset']);}
        foreach(['href'=>'url','action'=>'url','value'=>'text','name'=>'text','placeholder'=>'text','title'=>'text','aria-label'=>'text','data-filter'=>'text','data-category'=>'text','data-pricing-target'=>'text','data-pricing-panel'=>'text'] as $attribute=>$control)if(isset($attrs[$attribute])){
            $default=$control==='url'?['url'=>$attrs[$attribute],'is_external'=>($attrs['target']??'')==='_blank'?'yes':'','nofollow'=>strpos($attrs['rel']??'','nofollow')!==false?'yes':'','custom_attributes'=>'']:$attrs[$attribute];
            $node['bindings'][$attribute]=$this->field($defs,$key.'_'.str_replace('-','_',$attribute),$control,$default,$this->label($n,($control==='url'?'پیوند: ':$attribute.': ')));if($attribute==='href')$defs[$node['bindings'][$attribute]]['purpose']=mb_strpos($n->textContent,'دمو')!==false?'demo_link':'link';unset($node['attrs'][$attribute]);
        }
        if($tag==='a')unset($node['attrs']['target'],$node['attrs']['rel']);
        foreach(['data-zc-modal-open'=>'modal','data-zc-drawer-open'=>'drawer'] as $trigger=>$action)if(isset($attrs[$trigger])){
            $node['action']=$this->field($defs,$key.'_action','select',$action,'عملکرد دکمه',['options'=>['url'=>'پیوند','modal'=>'باز کردن پنجره','drawer'=>'باز کردن منوی کشویی','none'=>'بدون عملکرد']]);
            $node['action_target']=$this->field($defs,$key.'_target','text',$attrs[$trigger],'شناسه پنجره یا منوی مقصد');unset($node['attrs'][$trigger]);
            if(!isset($node['bindings']['href']))$node['bindings']['href']=$this->field($defs,$key.'_href','url',['url'=>''],'پیوند دکمه');
        }
        if($tag==='form')$this->model['has_form']=true;

        // Plain paragraph content is one rich-text control, not a collection of unrelated words.
        $rich=$tag==='p';foreach($n->getElementsByTagName('*') as $desc)if(!in_array($desc->tagName,['strong','b','em','u','s','br','code','a','span'],true)||$desc->hasAttribute('class')||$desc->hasAttribute('style'))$rich=false;
        if($rich){$html=Inline_Editing::html($n);$node['rich']=$this->field($defs,$key.'_body','wysiwyg',$html,$this->label($n,'توضیحات: '));$this->paths[$n->getNodePath()]=[$this->scope,$node['rich'],$this->rowIndex];$defs[$node['rich']]['purpose']='description';}
        elseif(!in_array($tag,['i','img'],true))$node['children']=$this->children($n,$defs,$inRow);
        if(in_array($tag,['h1','h2','h3','h4','h5','h6'],true))$node['heading_tag']=$this->field($defs,$key.'_tag','select',$tag,'تگ عنوان',['options'=>['h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4','h5'=>'H5','h6'=>'H6','div'=>'DIV']]);
        $node['visible']=$this->field($defs,$key.'_visible','switcher','yes',$this->label($n,'نمایش: '));
        return $node;
    }
    public function build(string $kind,string $section):array{
        $this->counts=[];$this->paths=[];$this->scope='';$name=$kind.($kind==='page_section'?'_'.$section:'');$this->model=['name'=>str_replace('-','_',$name),'kind'=>$kind,'section'=>$section,'fields'=>[],'repeaters'=>[],'parts'=>[],'legacy'=>[]];
        $html=Renderer::original($kind,['section_key'=>$section]);[$dom,$xp,$root]=Inline_Editing::document($html);
        $this->model['tree']=$this->children($root,$this->model['fields']);
        foreach(Inline_Editing::original_defaults($kind,$section) as $row)if(isset($this->paths[$row['path']]))$this->model['legacy'][$row['path']]=['baseline'=>$row['baseline'],'target'=>$this->paths[$row['path']]];
        // Only metadata for a one-time settings adapter uses old paths. Runtime rendering never resolves DOM/XPath.
        return $this->model;
    }
}
foreach(array_keys(Widget_Schema::definitions()) as $kind){$sections=$kind==='page_section'?array_keys(Widget_Schema::sections()):['about-page'];foreach($sections as $section){$m=(new ZC_Native_Compiler())->build($kind,$section);file_put_contents($out.'/'.$m['name'].'.json',wp_json_encode($m,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));echo $m['name'].' fields='.count($m['fields']).' repeaters='.count($m['repeaters'])."\n";}}
