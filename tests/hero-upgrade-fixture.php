<?php
/** Disposable WordPress only: old saved original hero, never a production page. */
$old=get_page_by_path('zarincode-hero-upgrade-qa');
$post=['post_type'=>'page','post_status'=>'publish','post_name'=>'zarincode-hero-upgrade-qa','post_title'=>'Hero upgrade QA'];if($old)$post['ID']=$old->ID;
$id=wp_insert_post($post);$data=[['id'=>'legacyqa','elType'=>'widget','widgetType'=>'zarincode_hero','settings'=>['design_mode'=>'original','n_hero_hero_title_text_1'=>'عنوان محفوظ پس از تفکیک هیرو'],'elements'=>[]]];
$raw=wp_json_encode($data);update_post_meta($id,'_elementor_data',wp_slash($raw));update_post_meta($id,'_zc_qa_original_hero_data',wp_slash($raw));update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_wp_page_template','template-elementor-canvas.php');echo $id."\n";
