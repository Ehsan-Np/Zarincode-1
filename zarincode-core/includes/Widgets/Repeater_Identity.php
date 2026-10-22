<?php
/** Stable identities for Elementor repeaters, including previously saved data. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
require_once __DIR__.'/Icon_Compatibility.php';
require_once __DIR__.'/Editor_Controls.php';
final class Repeater_Identity {
    public static function rows(array $rows, string $scope, array $fields=[]): array {
        $result=[]; $seen=[];
        foreach(array_values($rows) as $index=>$row) {
            if(!is_array($row)) continue;
            $id=$row['_id']??'';
            if(!is_string($id)||$id===''||!preg_match('/^[a-zA-Z0-9_-]+$/D',$id)||isset($seen[$id])) {
                $seed=$row; unset($seed['_id']);
                $salt=0;
                do { $id=substr(hash('sha256',$scope.':'.$index.':'.$salt++.':'.wp_json_encode($seed)),0,8); } while(isset($seen[$id]));
            }
            $row['_id']=$id; $seen[$id]=true;
            $result[]=self::settings($row,$fields,$scope.'.'.$id);
        }
        return $result;
    }
    public static function settings(array $settings,array $controls,string $scope): array {
        foreach($controls as $key=>$control) {
            if(($control['type']??'')==='icons'){$name=$control['name']??$key;if(array_key_exists($name,$settings))$settings[$name]=Icon_Compatibility::value($settings[$name]);continue;}
            if(($control['type']??'')!=='repeater') continue;
            $name=$control['name']??$key;
            if(isset($settings[$name])&&is_array($settings[$name])) {
                $settings[$name]=self::rows($settings[$name],$scope.'.'.$name,$control['fields']??[]);
            }
        }
        return $settings;
    }
}
