<?php
use PHPUnit\Framework\TestCase;
use Zarincode\Runtime260\Features\Manager;

/** Real WP hooks plus pure malformed-registry and dependency regression coverage. */
final class FeatureManagerTest extends TestCase {
    public function testMalformedDefinitionsFailClosed():void {
        foreach([null,false,7,'invalid',['settings'=>[]],['settings'=>null],['settings'=>''],['default'=>1],['default'=>null],['plugins'=>null],['plugins'=>'elementor'],['dependencies'=>[false]],['conflicts'=>['']],['cache_groups'=>[[]]]] as $definition){
            self::assertSame('invalid_definition',Manager::resolve('a',['a'=>$definition],['feature_a'=>true],['elementor'=>true]));
        }
    }
    public function testDependencyAndConflictDefinitions():void {
        self::assertSame('invalid_definition',Manager::resolve('a',['a'=>['default'=>true,'conflicts'=>['missing']]],[],[]));
        self::assertSame('invalid_definition',Manager::resolve('a',['a'=>['default'=>true,'conflicts'=>['b']],'b'=>false],[],[]));
        self::assertSame('dependency_disabled',Manager::resolve('a',['a'=>['default'=>true,'dependencies'=>['missing']]],[],[]));
        $features=['a'=>['default'=>true,'dependencies'=>['b']],'b'=>['default'=>true,'dependencies'=>['a']]];
        self::assertSame('cycle',Manager::resolve('a',$features,[],[]));
        $features['b']=['default'=>true,'plugins'=>['elementor']];
        self::assertSame('dependency_disabled',Manager::resolve('a',$features,[],[]));
        self::assertSame('enabled',Manager::resolve('a',$features,[],['elementor'=>true]));
        $features['a']['conflicts']=['b'];self::assertSame('conflict',Manager::resolve('a',$features,[],['elementor'=>true]));
        self::assertSame('unknown',Manager::resolve('missing',$features,[],[]));
    }
    public function testStrictOptionTokens():void {
        foreach([true,1,'1','yes','on'] as $value)self::assertSame('enabled',Manager::resolve('a',['a'=>[]],['feature_a'=>$value],[]));
        foreach([false,0,'0',null,[],'false','true'] as $value)self::assertSame('disabled',Manager::resolve('a',['a'=>['default'=>true]],['feature_a'=>$value],[]));
    }
    public function testCacheInvalidationUsesEffectiveStatesAndIgnoresMalformedEntries():void {
        $events=[];$listener=static function($id)use(&$events){$events[]=$id;};
        $filter=static function($registry){$registry['malformed']=false;$registry[17]=['default'=>true];return $registry;};
        add_action('zarincode_feature_cache_invalidated',$listener);add_filter('zarincode_feature_registry',$filter);
        try {
            Manager::invalidate(['feature_product_policies'=>true],['feature_product_policies'=>'1']);self::assertSame([],$events);
            Manager::invalidate(['feature_product_policies'=>true],['feature_product_policies'=>false]);self::assertSame(['product_policies'],$events);
            Manager::invalidate(null,false);self::assertSame(['product_policies'],$events);
            self::assertSame('unknown',Manager::status('nonexistent'));self::assertFalse(Manager::enabled('nonexistent'));
        } finally {remove_action('zarincode_feature_cache_invalidated',$listener);remove_filter('zarincode_feature_registry',$filter);}
    }
}
