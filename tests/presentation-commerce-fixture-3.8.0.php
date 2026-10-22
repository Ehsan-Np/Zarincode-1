<?php
/** Temporary transaction/variation fixtures, never a demo or release asset. */
wp_update_post(['ID'=>11,'post_author'=>1]);
$v=new WC_Product_Variable();$v->set_name('محصول متغیر آزمایشی');$v->set_status('publish');$v->set_image_id(wc_get_product(11)->get_image_id());$attribute=new WC_Product_Attribute();$attribute->set_name('مجوز');$attribute->set_options(['شخصی','تجاری']);$attribute->set_visible(true);$attribute->set_variation(true);$v->set_attributes([$attribute]);$v->save();
foreach(['شخصی','تجاری'] as $i=>$label){$variation=new WC_Product_Variation();$variation->set_parent_id($v->get_id());$variation->set_attributes(['مجوز'=>$label]);$variation->set_regular_price((string)(1000+$i*500));$variation->set_virtual(true);$variation->set_image_id(wc_get_product(11)->get_gallery_image_ids()[$i]??0);$variation->save();}WC_Product_Variable::sync($v->get_id());
$stock=new WC_Product_Simple();$stock->set_name('ناموجود آزمایشی');$stock->set_status('publish');$stock->set_regular_price('400');$stock->set_stock_status('outofstock');$stock->save();
$options=get_option('zarincode_opt');$options['social_custom_rows']=[['_id'=>'qa_custom','name'=>'شبکه آزمایشی','icon_type'=>'font','icon_class'=>'fa-solid fa-globe','profile_url'=>'https://example.com','share_template'=>'https://example.com/share?url={url}&title={title}']];
file_put_contents('/tmp/zc38qa/commerce.json',wp_json_encode(['variable'=>get_permalink($v->get_id()),'stock'=>get_permalink($stock->get_id())]));echo 'commerce fixtures ready';
