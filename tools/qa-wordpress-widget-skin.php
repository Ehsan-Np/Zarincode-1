<?php
/** Real WordPress test. Only run after bootstrap on a disposable, local fixture. */
if (!defined('ZARINCODE_DISPOSABLE_QA') || !ZARINCODE_DISPOSABLE_QA || wp_get_environment_type()!=='local') throw new RuntimeException('Disposable local WordPress only.');
use Zarincode\Theme\Compatibility\Native_Widget_Styles as Native;
use Zarincode\Theme\Compatibility\Elementor_Styles as Elementor;
$checks=0;$assert=static function($ok,$why)use(&$checks){if(!$ok)throw new RuntimeException($why);++$checks;};
$before=get_option('zarincode_opt',[]);
try {
    update_option('zarincode_opt',array_merge($before,['feature_wordpress_widget_styles'=>true,'feature_official_widget_styles'=>true]));$GLOBALS['zarincode_opt']=get_option('zarincode_opt');
    $classes=get_body_class();$assert(in_array('zc-wp-widget-skin',$classes,true),'Actual WP body_class includes skin');
    $assert(in_array('zc-elementor-widget-skin',$classes,true),'Actual WP body_class includes Elementor skin');
    do_action('wp_enqueue_scripts');Elementor::enqueue(); // Public callback; Elementor itself is NOT installed in this fixture.
    foreach(['zarincode-main','zarincode-widget-tokens','zarincode-wordpress-widgets','zarincode-elementor-official'] as $handle) $assert(wp_style_is($handle,'enqueued'),'Actual WP enqueued '.$handle);
    $assert(!wp_style_is('zarincode-woocommerce-widgets','enqueued'),'No Woo asset without Woo plugin');
    ob_start();wp_print_styles(['zarincode-wordpress-widgets','zarincode-elementor-official']);$head=ob_get_clean();
    $assert(strpos($head,'zarincode-main-css')<strpos($head,'zarincode-widget-tokens-css'),'WP dependency orders main before tokens');
    $assert(strpos($head,'zarincode-widget-tokens-css')<strpos($head,'zarincode-wordpress-widgets-css'),'WP dependency orders tokens before skin');
    $cat=wp_insert_term('Widget category','category');$tag=wp_insert_term('Widget topic','post_tag');
    $post=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Native widget article','post_content'=>'Widget fixture','post_category'=>[is_wp_error($cat)?get_option('default_category'):$cat['term_id']]]);
    if(!is_wp_error($tag))wp_set_post_terms($post,[$tag['term_id']],'post_tag');
    $html='';
    $widgets=[['WP_Widget_Search',[]],['WP_Widget_Recent_Posts',['title'=>'تازه‌های مجله','number'=>3,'show_date'=>true]],['WP_Widget_Categories',['title'=>'دسته‌بندی','hierarchical'=>true,'count'=>true]],['WP_Widget_Tag_Cloud',['title'=>'موضوعات']],['WP_Widget_Calendar',['title'=>'آرشیو']],['WP_Widget_Text',['title'=>'دربارهٔ ما','text'=>'متن نمونهٔ ابزارک بومی وردپرس.']]];
    foreach($widgets as $index=>[$class,$instance]){
        $widget=new $class();ob_start();$widget->widget(['before_widget'=>'<div class="widget '.$widget->widget_options['classname'].'">','after_widget'=>'</div>','before_title'=>'<h3 class="widget-title">','after_title'=>'</h3>','widget_id'=>'qa-widget-'.$index],$instance);$out=ob_get_clean();
        $assert(str_contains($out,'class="widget '),'Actual core widget renders '.$class);$html.=$out;
    }
    if(getenv('ZC_NATIVE_WP_MARKUP'))file_put_contents(getenv('ZC_NATIVE_WP_MARKUP'),$html);
    $GLOBALS['zarincode_opt']=['feature_wordpress_widget_styles'=>false,'feature_woocommerce_widget_styles'=>false,'feature_official_widget_styles'=>false];
    foreach(['zc-wp-widget-skin','zc-wc-widget-skin','zc-elementor-widget-skin'] as $scope)$assert(!in_array($scope,get_body_class(),true),'Actual body_class switch off '.$scope);
} finally {update_option('zarincode_opt',$before);$GLOBALS['zarincode_opt']=$before;}
echo "PASS: $checks real WordPress widget-render/enqueue/dependency/scope assertions; Elementor/WooCommerce plugins not installed.\n";
