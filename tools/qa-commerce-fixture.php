<?php
/** Build Woo test pages with the installed vendor's own public creation filter. */
if(!defined('WP_CLI')||!WP_CLI||!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA||wp_get_environment_type()!=='local')throw new RuntimeException('Disposable local QA only');
$definitions=[];add_filter('woocommerce_create_pages',static function($pages)use(&$definitions){$definitions=$pages;return $pages;});WC_Install::create_pages();
$previous=is_file('/tmp/zc-complete/commerce-pages.json')?json_decode(file_get_contents('/tmp/zc-complete/commerce-pages.json'),true):[];
$pages=[];
foreach(['classic_cart'=>'[woocommerce_cart]','classic_checkout'=>'[woocommerce_checkout]','block_cart'=>$definitions['cart']['content'],'block_checkout'=>$definitions['checkout']['content']] as $name=>$content){$pages[$name]=wp_insert_post(['ID'=>$previous[$name]??0,'post_type'=>'page','post_status'=>'publish','post_title'=>'QA '.$name,'post_content'=>$content]);}
$p=!empty($previous['product'])?wc_get_product($previous['product']):new WC_Product_Simple();$p->set_name('QA checkout product');$p->set_status('publish');$p->set_regular_price('10');$p->set_virtual(true);$p->save();$pages['product']=$p->get_id();
update_option('woocommerce_cod_settings',['enabled'=>'yes','title'=>'Cash on delivery','description'=>'Disposable offline checkout','enable_for_virtual'=>'yes']);update_option('woocommerce_enable_guest_checkout','yes');update_option('woocommerce_default_country','US:CA');update_option('woocommerce_currency','USD');update_option('woocommerce_coming_soon','no');
file_put_contents(getenv('ZC_QA_REPORT')?:'/tmp/zc-complete/commerce-pages.json',wp_json_encode($pages));
