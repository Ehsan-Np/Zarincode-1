<?php
/** Dependency-free contract tests. Run: php tools/qa-skill-v3-unit.php */
define('ABSPATH',__DIR__.'/../'); define('ZARINCODE_CORE_VERSION','4.0.0-rc.6');
function apply_filters($name,$value){return $value;}
require ABSPATH.'zarincode-core/includes/Features/Registry.php';
require ABSPATH.'zarincode-core/includes/Features/Manager.php';
require ABSPATH.'zarincode-core/includes/Elementor/Contracts.php';
require ABSPATH.'zarincode-core/includes/Support/Settings_Schema.php';
use Zarincode\Runtime260\Features\Manager;
use Zarincode\Runtime260\Support\Settings_Schema;
$count=0;
$check=static function($ok,$label)use(&$count){if(!$ok)throw new RuntimeException($label);$count++;};
$all=\Zarincode\Runtime260\Elementor\Contracts::all();$check(count($all)===26,'26 stable widget contracts');
foreach($all as $id=>$contract){$check($contract['id']===$id,'identity');$check($contract['settings_schema']===1,'schema');$check($contract['z_wqi']===null && $contract['status']==='Not Reviewed','no fabricated approval');}
$original=['title'=>'Existing content','items'=>[['_id'=>'stable-a','title'=>'A']],'unknown'=>['keep'=>true]];
$upgraded=Settings_Schema::upgrade($original);$check($upgraded['items']===$original['items'],'repeater identity');$check($upgraded['unknown']===$original['unknown'],'unknown fields retained');
$check(Settings_Schema::upgrade($upgraded)===$upgraded,'idempotence');$check(!isset($original['_zc_settings_schema']),'input unchanged');
foreach([2,-1,'1',[],false] as $version){$caught=false;try{Settings_Schema::upgrade(['_zc_settings_schema'=>$version]);}catch(InvalidArgumentException $e){$caught=true;}$check($caught,'unsupported schema rejected');}
$f=['a'=>['default'=>true,'dependencies'=>['b']],'b'=>['default'=>true,'dependencies'=>['a']]];
$check(Manager::resolve('a',$f,[],[])==='cycle','cycle fails closed');$check(Manager::resolve('a',$f,['feature_a'=>0],[])==='disabled','disabled');
$check(Manager::resolve('missing',$f,[],[])==='unknown','unknown');$f['b']['dependencies']=[];$check(Manager::resolve('a',$f,[],[])==='enabled','valid dependencies');
$f['b']['plugins']=['elementor'];$check(Manager::resolve('a',$f,[],[])==='dependency_disabled','missing plugin');
$f['a']['conflicts']=['b'];$check(Manager::resolve('a',$f,[],['elementor'=>true])==='conflict','conflict');
echo json_encode(['suite'=>'skill-v3-pure-contracts','assertions'=>$count,'passed'=>true,'coverage_percent'=>null],JSON_PRETTY_PRINT).PHP_EOL;
