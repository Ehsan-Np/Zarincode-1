<?php
/** wp eval-file, release/development fixture only. Outputs curated demo IDs, not every site post. */
$ids=get_posts(['post_type'=>['page','post','product','zc_service','zc_project','zc_hf_template','elementor_library'],'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_zarincode_seeded','meta_value'=>'1']);
$ids=array_merge($ids,get_posts(['post_type'=>'nav_menu_item','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']));
foreach(['product','service','project','post'] as $asset)$ids[]=(int)get_option('zarincode_demo_cover_'.$asset);
echo implode(',',array_unique(array_filter($ids)))."\n";
