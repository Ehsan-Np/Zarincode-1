<?php
namespace Zarincode\Theme\Core {class Product_Policies{static function get($id){return [];}static function render($id){}}class Single_Experience{static function render(){}}}
namespace {
define('ABSPATH','/tmp/');
function get_header(){echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/zarincode/assets/css/vazirmatn.css"><link rel="stylesheet" href="/zarincode/assets/css/zarincode-main.css"><link rel="stylesheet" href="/zarincode/assets/fontawesome/css/all.min.css"><link rel="stylesheet" href="/zarincode/assets/css/product-reference.css"><style>:root{--zc-container-width:1360px}body{font-family:Vazirmatn,Tahoma,sans-serif}.zc-single-product-wrap{padding-top:32px}</style><body>';}
function get_footer(){echo '</body></html>';}
function have_posts(){static $i=0;return $i++===0;}function the_post(){}function get_the_ID(){return 1;}
function get_the_title(){return 'افزونه سئو هوشمند و ایندکس آنی گوگل (ZarinSEO Booster)';}
function get_the_excerpt(){return 'افزونه اختصاصی وردپرس برای اتصال مستقیم به Google Indexing API، تولید خودکار اسکیماهای پیشرفته JSON-LD و آنالیز لحظه‌ای چگالی کلیدی.';}
function the_content(){echo '<p>'.get_the_excerpt().'</p>';}
function get_post_field($f,$i){return 'product-demo';}
function get_post_meta($id,$key,$single){return ['_regular_price'=>'1290000','_zc_prod_version'=>'v3.2.1','_zc_prod_cat_badge'=>'افزونه وردپرس','_zc_prod_tags'=>'WordPress, Auto-Index, Schema Pro, Google Indexing API','_zc_prod_demo_url'=>'https://example.com/demo'][$key]??'';}
function get_the_modified_date(){return '۲۲ اکتبر ۲۰۲۶';}function zarincode_get_option($k,$d){return $d;}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}function esc_attr($v){return esc_html($v);}function esc_url($v){return esc_html($v);}function home_url($v=''){return 'https://example.com'.$v;}
function get_template_directory_uri(){return '/zarincode';}function zarincode_fa_digits($v){return $v;}function number_format_i18n($v){return number_format($v);}function wp_kses_post($v){return $v;}function wpautop($v){return '<p>'.$v.'</p>';}function add_query_arg($k,$v,$url){return $url.'?'.$k.'='.$v;}function wc_get_cart_url(){return 'https://example.com/cart';}function WC(){return true;}
class FixtureProduct{function get_total_sales(){return 0;}function get_rating_count(){return 0;}function get_sku(){return 'ZC-PLUGIN-01';}}
function wc_get_product($id){return new FixtureProduct();}function woocommerce_show_product_images(){echo '<div class="woocommerce-product-gallery"><div class="woocommerce-product-gallery__wrapper"><div class="woocommerce-product-gallery__image"><a href="/zarincode/assets/images/demo-product-cover.jpg"><img src="/zarincode/assets/images/demo-product-cover.jpg" width="960" height="600" alt="محصول آزمایشی"></a></div></div></div>';}
require '/home/user/zarincode/single-product.php';
}
