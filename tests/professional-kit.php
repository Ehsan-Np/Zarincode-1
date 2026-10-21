<?php
/** Disposable WordPress only: imports a temporary Elementor library template, then deletes it. */
$admins=get_users(['role'=>'administrator','number'=>1]);
if(!$admins)throw new RuntimeException('An administrator is required for the import test.');
wp_set_current_user($admins[0]->ID);
$plugin=\Elementor\Plugin::instance();
$plugin->widgets_manager->get_widget_types();
$path=get_template_directory().'/demo-data/zarincode-professional-kit.json';
$items=$plugin->templates_manager->get_source('local')->import_template(basename($path),$path);
if(is_wp_error($items))throw new RuntimeException($items->get_error_message());
$id=$items[0]['template_id']??0;
if(!$id)throw new RuntimeException('No imported template ID.');
try{
 $html=$plugin->frontend->get_builder_content_for_display($id);
 if(substr_count($html,'data-zc-pro=')!==6)throw new RuntimeException('Imported kit did not render all six widgets.');
 \Elementor\Core\Files\CSS\Post::create($id)->update();
 echo "PASS native Elementor kit import, six rendered widgets and actual CSS generation\n";
}finally{wp_delete_post($id,true);}
