<?php
/** Disposable WordPress + WooCommerce only. Test records/terms are removed in finally. */
use Zarincode\Runtime260\Widgets\Native_Design as N;
use Zarincode\Runtime260\Widgets\Editable_Renderer as Q;
use Zarincode\Runtime260\Widgets\Hero_Layout as H;
$manager=Elementor\Plugin::instance()->elements_manager;
$make=static fn($kind,$s=[])=>$manager->create_element_instance(['id'=>'qa32','elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$s]);
$render=static function($w){ob_start();try{$w->render_content();}finally{$html=ob_get_clean();}return $html;};
$n=0;$check=static function($ok,$message)use(&$n){if(!$ok)throw new RuntimeException($message);$n++;echo "PASS $message\n";};
$posts=[];$terms=[];$stamp=substr(wp_generate_uuid4(),0,8);
set_error_handler(static function($severity,$message,$file,$line){if(strpos($file,'EmbeddedCore')!==false||strpos($file,'zarincode-core/includes')!==false)throw new RuntimeException($message.' '.$file.':'.$line);return false;});
try {
    $root=wp_insert_term('QA Root A '.$stamp,'product_cat');$a=$root['term_id'];$terms[]=$a;
    $b=wp_insert_term('QA Root B '.$stamp,'product_cat')['term_id'];$terms[]=$b;
    $c=wp_insert_term('QA Child '.$stamp,'product_cat',['parent'=>$a])['term_id'];$terms[]=$c;
    $d=wp_insert_term('QA Grandchild '.$stamp,'product_cat',['parent'=>$c])['term_id'];$terms[]=$d;
    $draftcat=wp_insert_term('QA Draft Only '.$stamp,'product_cat')['term_id'];$terms[]=$draftcat;
    $thumb=(int)get_option('zarincode_demo_cover_product');
    for($i=0;$i<53;$i++){
        $p=new WC_Product_Simple();$p->set_name('QA Product '.$stamp.' '.$i);$p->set_status('publish');$p->set_regular_price('150');$p->set_category_ids($i===0?[$d,$b]:[$d]);if($thumb)$p->set_image_id($thumb);$posts[]=$p->save();
    }
    $published=$posts;
    $p=new WC_Product_Simple();$p->set_name('QA Draft '.$stamp);$p->set_status('draft');$p->set_category_ids([$draftcat]);$draft=$p->save();$posts[]=$draft;
    $p=new WC_Product_Simple();$p->set_name('QA Hidden '.$stamp);$p->set_status('publish');$p->set_catalog_visibility('hidden');$p->set_category_ids([$draftcat]);$posts[]=$p->save();
    $ids=implode(',',$posts);
    $all=Q::query_items('products',['show_all'=>'yes','count'=>1,'include_ids'=>$ids]);
    $check(count($all)===53,'all-products mode is not truncated at 50 and excludes drafts');
    $check(count(Q::query_items('products',['count'=>2,'include_ids'=>$ids]))===2,'bounded result count works');
    $check(count(Q::query_items('products',['show_all'=>'yes','include_ids'=>$ids,'category_ids'=>[$a]]))===53,'named parent-category selection includes child products');
    $first=array_values(array_filter($all,static fn($row)=>$row['_id']==='post-'.$published[0]))[0];
    $check(count($first['filter_terms'])===2,'product supports two independent root-category filters');
    $check(isset($first['filter_terms'][get_term($a)->slug],$first['filter_terms'][get_term($b)->slug]),'nested categories resolve to highest ancestors');
    $check(strpos($render($make('products',['n_products_source'=>'query','n_products_include_ids'=>'999999999'])),'zc-query-empty')!==false,'empty queries show a real empty state rather than demo cards');
    $s=['n_products_source'=>'query','n_products_show_all'=>'yes','n_products_include_ids'=>$ids];$html=$render($make('products',$s));
    $check(substr_count($html,'QA Product '.$stamp)>=53,'all published product titles rendered');
    foreach([$a,$b] as $term)$check(strpos($html,'data-filter="'.get_term($term)->slug.'"')!==false,'root filter rendered '.$term);
    foreach([$c,$d,$draftcat] as $term)$check(strpos($html,'data-filter="'.get_term($term)->slug.'"')===false,'child/draft-only filter omitted '.$term);
    $check(strpos($html,'zc-record-image')!==false&&strpos($html,esc_url(wp_get_attachment_image_url($thumb,'zarincode-card')))!==false,'real product featured attachment rendered');
    $check(strpos($html,'woocommerce-Price-amount')!==false,'WooCommerce formatted price used');
    foreach(['services'=>'zc_service','portfolio'=>'zc_project','blog'=>'post'] as $kind=>$type){
        $id=wp_insert_post(['post_type'=>$type,'post_status'=>'publish','post_title'=>'QA '.$kind.' '.$stamp,'post_content'=>'QA actual excerpt '.$kind]);$posts[]=$id;if($thumb)set_post_thumbnail($id,$thumb);
        $rows=Q::query_items($kind,['include_ids'=>(string)$id,'show_all'=>'yes']);
        $check(count($rows)===1&&$rows[0]['image']['id']===$thumb,'CPT/post source and image '.$kind);
        $html=$render($make($kind,['n_'.$kind.'_source'=>'query','n_'.$kind.'_include_ids'=>(string)$id]));
        $check(strpos($html,'QA '.$kind.' '.$stamp)!==false&&strpos($html,esc_url(wp_get_attachment_image_url($thumb,'zarincode-card')))!==false,'native automatic title and image '.$kind);
        if($kind!=='blog'){
            $section=$kind==='services'?'services-page':'projects-page';$model=N::model('page_section',$section);
            $out=$render($make('page_section',['section_key'=>$section,'n_'.$model['name'].'_source'=>'query','n_'.$model['name'].'_include_ids'=>(string)$id]));
            $check(strpos($out,'QA '.$kind.' '.$stamp)!==false&&strpos($out,esc_url(wp_get_attachment_image_url($thumb,'zarincode-card')))!==false,'inner page also uses automatic CPT and featured image '.$kind);
        }
    }
    $tree=[['id'=>'oldhero','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>['n_hero_hero_title_text_1'=>'QA SAVED HERO'],'elements'=>[]]];$changed=0;$new=H::migrate_tree($tree,$changed);
    $check($changed===1&&$new[0]['id']==='oldhero','hero structural conversion keeps original element ID');
    $flat=[];$walk=function($nodes)use(&$walk,&$flat){foreach($nodes as $node){if(isset($node['widgetType']))$flat[]=$node;$walk($node['elements']??[]);}};$walk($new);
    $check(array_column($flat,'widgetType')===['zarincode_hero_intro','zarincode_hero_profile','zarincode_hero_cta'],'hero is three distinct native widgets, not UI-only sections');
    $check(strpos($render($make('hero_intro',$flat[0]['settings'])),'QA SAVED HERO')!==false,'saved headline survives conversion');
    $again=0;H::migrate_tree($new,$again);$check($again===0,'hero conversion is idempotent');
    $check(!$make('hero')->show_in_panel()&&$make('hero_intro')->show_in_panel(),'legacy monolithic hero hidden from new-widget panel');
    $custom=0;H::migrate_tree([['widgetType'=>'zarincode_hero','settings'=>['design_mode'=>'custom']]],$custom);$check($custom===0,'legacy custom layout is not destructively converted');
    $m=N::model('pricing');$group=array_key_first($m['repeaters']);$rows=$m['repeaters'][$group]['default'];$rows[0]['features_source']='icon_list';$rows[0]['plan_key']='qa-premium';
    $features=[];for($i=0;$i<17;$i++)$features[]=['plan_key'=>'__custom__','custom_plan_key'=>'qa-premium','text'=>'QA FEATURE '.$i,'icon'=>['value'=>'fas fa-anchor','library'=>'fa-solid']];
    $s=['n_pricing_'.$group=>$rows,'n_pricing_features'=>$features];$html=$render($make('pricing',$s));
    $check(substr_count($html,'QA FEATURE ')===17,'17 arbitrary icon-list items render for one plan');
    $check(strpos($html,'e-fas-anchor')!==false,'per-feature native icon is rendered');
    $s['n_pricing_'.$group]=array_reverse($rows);$check(substr_count($render($make('pricing',$s)),'QA FEATURE ')===17,'features remain associated after plan reorder');
    $removed=$s;$removed['n_pricing_'.$group]=array_values(array_filter($removed['n_pricing_'.$group],static fn($row)=>($row['plan_key']??'')!=='qa-premium'));$check(strpos($render($make('pricing',$removed)),'QA FEATURE ')===false,'features of a deleted plan are not reassigned to another plan');
    $s['n_pricing_features']=[];$check(strpos($render($make('pricing',$s)),'QA FEATURE ')===false,'all icon-list features can be removed');
    $tm=N::model('testimonials');$g=array_key_first($tm['repeaters']);$rows=$tm['repeaters'][$g]['default'];$rows[0]['customer_photo']=['id'=>$thumb,'url'=>wp_get_attachment_url($thumb)];
    $check(strpos($render($make('testimonials',['n_testimonials_'.$g=>$rows])),'zc-customer-photo')!==false,'customer image is native media and renders');
    $sizes=wp_get_additional_image_sizes();$check($sizes['zarincode-card']['width']===960&&$sizes['zarincode-card']['height']===600&&$sizes['zarincode-card']['crop'],'standard 960 by 600 hard crop registered');
    $home=get_page_by_path('home');$data=json_decode(get_post_meta($home->ID,'_elementor_data',true),true);$flat=[];$walk($data);$used=array_column($flat,'widgetType');
    foreach(['hero_intro','hero_profile','hero_cta','services','products','portfolio','blog','pricing','testimonials','brands','steps','why_us','promo','service_spotlight','project_roadmap','seo_audit','before_after','team_experts','service_matrix'] as $kind)$check(in_array('zarincode_'.$kind,$used,true),'complete demo uses '.$kind);
    $check(!in_array('zarincode_hero',$used,true),'new demo does not use monolithic hero');
    echo "TOTAL $n dynamic-content 3.2 checks passed\n";
} finally {
    restore_error_handler();foreach($posts as $id)wp_delete_post($id,true);foreach(array_reverse($terms) as $id)wp_delete_term($id,'product_cat');
}
