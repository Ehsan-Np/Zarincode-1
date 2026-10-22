<?php
/** Isolated adapter fixtures. They are not full WPML/Polylang compatibility certification. */
define('ABSPATH',__DIR__.'/../');
$mode=$argv[1]??'none';
if(in_array($mode,['wpml','conflict'],true))define('ICL_SITEPRESS_VERSION','fixture');
if(in_array($mode,['polylang','conflict'],true)){
 function pll_get_post($id,$language){return $language==='fa'?$id+100:0;}
 function pll_get_term($id,$language){return $language==='fa'?$id+200:0;}
 function pll_current_language($field){return 'fa';}
}
function get_post_type($id){return $id===9?'private_type':'zc_service';}
function is_post_type_viewable($type){return $type!=='private_type';}
function taxonomy_exists($taxonomy){return $taxonomy==='zc_service_cat';}
function apply_filters($hook,$id,$type,$fallback,$language){return $type==='zc_service_cat'?$id+200:$id+100;}
require ABSPATH.'zarincode-core/includes/Integrations/Multilingual.php';
use Zarincode\Runtime260\Integrations\Multilingual;
$check=static function($ok){if(!$ok)throw new RuntimeException('Adapter assertion failed');};
$check(Multilingual::engine()===$mode);$check(Multilingual::post_id(0)===0);$check(Multilingual::post_id(9)===9);
$active=in_array($mode,['wpml','polylang'],true);
$check(Multilingual::post_id(5,'fa')===($active?105:5));$check(Multilingual::term_id(5,'zc_service_cat','fa')===($active?205:5));$check(Multilingual::term_id(5,'missing','fa')===5);
echo json_encode(['fixture'=>$mode,'assertions'=>6,'passed'=>true]).PHP_EOL;
