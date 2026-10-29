<?php
/** Isolated WordPress API doubles: enqueue, switches and archive/template regression. */
define('ABSPATH', __DIR__);
$options=[];$styles=[];$actions=[];$filters=[];$checks=0;
function zarincode_get_option($k,$d=true){return $GLOBALS['options'][$k]??$d;}
function get_template_directory(){return dirname(__DIR__).'/zarincode';}
function get_template_directory_uri(){return 'https://example.test/theme';}
function wp_enqueue_style($id,$src,$deps=[],$version=''){ $GLOBALS['styles'][$id]=compact('src','deps','version'); }
function wp_style_is($id,$status){return $id==='woocommerce-general';}
function add_action($hook,$callback,...$args){$GLOBALS['actions'][$hook][]=$callback;}
function add_filter($hook,$callback,...$args){$GLOBALS['filters'][$hook][]=$callback;}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);$GLOBALS['checks']++;}
require dirname(__DIR__).'/zarincode/inc/Compatibility/Native_Widget_Styles.php';
require dirname(__DIR__).'/zarincode/inc/Compatibility/Elementor_Styles.php';
use Zarincode\Theme\Compatibility\Native_Widget_Styles as Native;
use Zarincode\Theme\Compatibility\Elementor_Styles as Elementor;
Native::boot();Elementor::boot();
check(isset($actions['wp_enqueue_scripts']),'Public frontend enqueue');
check(isset($actions['elementor/frontend/after_enqueue_styles']),'Elementor public stylesheet hook');
check(!isset($actions['admin_enqueue_scripts']),'No admin chrome changes');
Native::enqueue();check(isset($styles['zarincode-wordpress-widgets']),'WordPress skins enabled by default');
check(!isset($styles['zarincode-woocommerce-widgets']),'No WC asset without WC');
Elementor::enqueue();check(isset($styles['zarincode-elementor-official']),'Builder template/archive works without singular metadata');
check(in_array('zc-elementor-widget-skin',Native::body_classes([]),true),'Scoped Elementor body class');
check(in_array('zarincode-widget-tokens',$styles['zarincode-elementor-official']['deps'],true),'Shared token dependency');
eval('class WooCommerce {}');Native::enqueue();check(isset($styles['zarincode-woocommerce-widgets']),'WC supports mini-cart on non-shop routes');
check(in_array('woocommerce-general',$styles['zarincode-woocommerce-widgets']['deps'],true),'Vendor WC stylesheet before skin');
foreach($styles as $style){check($style['version']!=='','File modification cache key');check(is_file(get_template_directory().substr($style['src'],strlen(get_template_directory_uri()))),'Stylesheet exists');}
$options=['feature_wordpress_widget_styles'=>false,'feature_woocommerce_widget_styles'=>false,'feature_official_widget_styles'=>false];$styles=[];
Native::enqueue();Elementor::enqueue();check(!$styles,'All three switches suppress their assets');
check(Native::body_classes(['existing'])===['existing'],'Switches remove scope classes and preserve other classes');
echo "PASS: $checks native-widget style hook/switch assertions (WordPress APIs are doubles).\n";
