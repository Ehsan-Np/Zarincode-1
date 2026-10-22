<?php
/** Run with wp eval-file ONLY in an explicitly disposable local WordPress installation. */
if ( ! defined('WP_CLI') || ! WP_CLI || wp_get_environment_type() !== 'local' || !defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA ) {
    throw new RuntimeException('Refusing to modify a non-disposable site.');
}
use Zarincode\Runtime260\Features\Manager;
use Zarincode\Theme\Core\Settings_Security;
use Zarincode\Theme\Compatibility\SEO;
$checks = []; $inventory = [];
$check = static function($name, $ok) use (&$checks) {
    $checks[$name] = (bool)$ok;
    if (!$ok) { throw new RuntimeException('FAILED: '.$name); }
};
$original = get_option('zarincode_opt', []); $user = get_current_user_id();
try {
    $admin = get_users(['role'=>'administrator','number'=>1])[0]; wp_set_current_user($admin->ID);
    $f = ['parent'=>['default'=>true], 'child'=>['default'=>true,'dependencies'=>['parent']]];
    $check('resolver_default',Manager::resolve('child',$f,[],[])==='enabled');
    $check('resolver_preserved_off',Manager::resolve('parent',$f,['feature_parent'=>false],[])==='disabled');
    $check('resolver_dependency',Manager::resolve('child',$f,['feature_parent'=>0],[])==='dependency_disabled');
    $check('resolver_unknown',Manager::resolve('missing',$f,[],[])==='unknown');
    $f['parent']['dependencies']=['child']; $check('resolver_cycle',Manager::resolve('child',$f,[],[])==='cycle');
    $f['parent']['dependencies']=[]; $f['parent']['plugins']=['missing'];
    $check('resolver_plugin',Manager::resolve('parent',$f,[],[])==='dependency_missing');
    $f['parent']['plugins']=[]; $f['child']['conflicts']=['parent'];
    $check('resolver_conflict',Manager::resolve('child',$f,[],[])==='conflict');
    $options = $original;
    $options['feature_native_widgets']=true; $options['feature_studio_widgets']=true; $options['feature_professional_widgets']=true;
    $options['custom_head_js']='<script>window.zcTrusted=true;</script>';
    $options['zc_foreign_preserved']=['nested'=>'value'];
    update_option('zarincode_opt',$options);
    $wm = \Elementor\Plugin::instance()->widgets_manager;
    $count = static function() use ($wm) { return count(array_filter(array_keys($wm->get_widget_types()),static fn($k)=>str_starts_with($k,'zarincode_'))); };
    $check('widgets_default_26',$count()===26);
    foreach ($wm->get_widget_types() as $name=>$widget) {
        $inventory[]=['name'=>$name,'title'=>wp_strip_all_tags($widget->get_title()),'origin'=>str_starts_with($name,'zarincode_')?'companion':'installed third-party','categories'=>$widget->get_categories(),'frontend'=>'not_tested','editor'=>'not_tested','accessibility'=>'not_tested','visual'=>'not_tested'];
    }
    $reset = static function() use ($wm) {
        foreach (array_keys($wm->get_widget_types()) as $name) { if(str_starts_with($name,'zarincode_')) $wm->unregister($name); }
        \Zarincode\Runtime260\Widgets\Elementor_Loader::instance()->register_widgets($wm);
    };
    $options['feature_studio_widgets']=false; update_option('zarincode_opt',$options); $reset();
    $check('studio_disabled_23',$count()===23);
    $options['feature_professional_widgets']=false; update_option('zarincode_opt',$options); $reset();
    $check('professional_disabled_17',$count()===17);
    $options['feature_native_widgets']=false; update_option('zarincode_opt',$options); $reset();
    $check('all_disabled_zero',$count()===0);
    $check('disabled_shortcode_output',\Zarincode\Runtime260\Widgets\Renderer::output('hero',[])==='');
    $saved=get_option('zarincode_opt'); $check('settings_preserved',$saved['zc_foreign_preserved']===['nested'=>'value']);
    $author=get_user_by('login','zc_fixture_author');
    if(!$author) {$id=wp_insert_user(['user_login'=>'zc_fixture_author','user_pass'=>wp_generate_password(32),'role'=>'author']); $author=get_user_by('id',$id);}
    wp_set_current_user($author->ID);
    $attempt=$saved; $attempt['custom_head_js']='<script>window.zcUntrusted=true;</script>'; $attempt['custom_css']='body{color:red}</style><script>alert(1)</script>';
    $attempt['feature_native_widgets']=['invalid'];
    update_option('zarincode_opt',$attempt); $actual=get_option('zarincode_opt');
    $check('restricted_snippet_unchanged',$actual['custom_head_js']===$saved['custom_head_js']);
    $check('style_cannot_close',!str_contains($actual['custom_css'],'<') && !str_contains($actual['custom_css'],'>'));
    $check('malformed_switch_preserved',$actual['feature_native_widgets']===false);
    $check('malformed_store_rejected',Settings_Security::sanitize('bad',$saved)===$saved);
    wp_set_current_user($admin->ID);
    $check('schema_no_owner',SEO::theme_schema_allowed());
    define('WPSEO_VERSION','fixture'); $check('schema_yoast_ownership',!SEO::theme_schema_allowed());
    $check('editor_support',current_theme_supports('editor-styles'));
    $check('pattern_registered',WP_Block_Patterns_Registry::get_instance()->is_registered('zarincode/agency-intro'));
    $check('theme_json_palette',count(wp_get_global_settings(['color','palette','theme']))===3);
    switch_to_locale('en_US'); $check('english_not_rtl',!is_rtl());
    $attrs=apply_filters('language_attributes','lang="en-US"'); $check('english_no_forced_rtl',!str_contains($attrs,'dir="rtl"')); restore_previous_locale();
    $report=['environment'=>['wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'elementor'=>ELEMENTOR_VERSION,'elementor_pro'=>defined('ELEMENTOR_PRO_VERSION')?ELEMENTOR_PRO_VERSION:'not_installed','woocommerce'=>defined('WC_VERSION')?WC_VERSION:'not_installed','redux'=>defined('REDUX_VERSION')?REDUX_VERSION:'loaded','database'=>$GLOBALS['wpdb']->db_version()],'checks'=>$checks,'installed_widget_inventory'=>$inventory,'limitations'=>['Inventory is not visual/editor/a11y coverage.','Not a full Skill-compliance test or 80% coverage evidence.','WPML add-ons and Polylang runtime not tested.']];
    file_put_contents(getenv('ZC_QA_REPORT') ?: '/tmp/zc-skill-runtime/foundations.json',wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    echo count($checks).' foundation assertions passed; '.count($inventory).' widget types inventoried.'.PHP_EOL;
} finally {
    wp_set_current_user($user); remove_filter('pre_update_option_zarincode_opt',[Settings_Security::class,'sanitize'],20);
    update_option('zarincode_opt',$original);
}
