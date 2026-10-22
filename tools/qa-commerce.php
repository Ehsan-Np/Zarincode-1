<?php
/** Actual WooCommerce CRUD, HPOS/classic storage and download entitlement isolation. */
if(!defined('WP_CLI')||!WP_CLI||!defined('ZARINCODE_DISPOSABLE_QA')||!ZARINCODE_DISPOSABLE_QA||wp_get_environment_type()!=='local')throw new RuntimeException('Disposable local fixture only');
add_filter('pre_wp_mail',static fn()=>true);
$checks=[];$ok=static function($n,$v)use(&$checks){$checks[$n]=(bool)$v;if(!$v)throw new RuntimeException($n);};
$expected=getenv('ZC_QA_HPOS')==='yes';
$ok('actual_store_mode',\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()===$expected);
$user=wp_insert_user(['user_login'=>'qa-buyer-'.uniqid(),'user_email'=>'buyer-'.uniqid().'@example.test','role'=>'customer']);$other=wp_insert_user(['user_login'=>'qa-other-'.uniqid(),'user_email'=>'other-'.uniqid().'@example.test','role'=>'customer']);
$uploads=wp_upload_dir();$folder=$uploads['basedir'].'/woocommerce_uploads';wp_mkdir_p($folder);$file=$folder.'/qa-protected-fixture.txt';file_put_contents($file,'Disposable download QA');
$p=new WC_Product_Simple();$p->set_name('QA protected download');$p->set_status('publish');$p->set_regular_price('10');$p->set_virtual(true);$p->set_downloadable(true);$download=new WC_Product_Download();$download->set_id('qa-download');$download->set_name('QA protected file');$download->set_file($file);$p->set_downloads([$download]);$p->save();
$order=wc_create_order(['customer_id'=>$user]);$ok('create_order',!is_wp_error($order));$order->add_product($p,1);$order->set_billing_email(get_userdata($user)->user_email);$order->set_payment_method('cod');$order->calculate_totals();$order->save();$id=$order->get_id();
$loaded=wc_get_order($id);$ok('read_order',$loaded&&$loaded->get_customer_id()===$user);$ok('total',(float)$loaded->get_total()===10.0);
$ok('no_permission_before_payment',count(wc_get_customer_available_downloads($user))===0);
$order->update_status('completed','Disposable QA completion; no payment processed');
$ok('owner_download_allowed',count(wc_get_customer_available_downloads($user))===1);$ok('other_customer_no_download',count(wc_get_customer_available_downloads($other))===0);
$ok('orders_owner_query',count(wc_get_orders(['customer_id'=>$user,'return'=>'ids']))===1);$ok('other_orders_empty',count(wc_get_orders(['customer_id'=>$other,'return'=>'ids']))===0);
$refund=wc_create_refund(['order_id'=>$id,'amount'=>5,'reason'=>'Disposable QA partial refund','refund_payment'=>false]);$ok('refund_created',!is_wp_error($refund));$ok('refund_amount',(float)wc_get_order($id)->get_total_refunded()===5.0);
$store=WC_Data_Store::load('order');
$backend=$store->get_current_class_name();
$out=['woocommerce'=>WC_VERSION,'hpos'=>$expected,'backend'=>$backend,'checks'=>$checks,'scope'=>'Real order/refund/entitlement CRUD. No real gateway charge, external payment sandbox, email deliverability or WCML certification.'];
unlink($file);$refund->delete(true);$order->delete(true);$p->delete(true);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user);wp_delete_user($other);
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
