<?php
/** Explicit relocation of the six 3.2 demo appendices. No automatic content rewrites. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Demo_Composition {
    private static function destinations():array {
        return ['zarincode_service_spotlight'=>['services','خدمات تخصصی زرین کد'],'zarincode_project_roadmap'=>['services','خدمات تخصصی زرین کد'],'zarincode_before_after'=>['projects-portfolio','نمونه‌کارها'],'zarincode_team_experts'=>['about-us','درباره زرین کد'],'zarincode_seo_audit'=>['seo-performance','سئو و بهبود عملکرد'],'zarincode_service_matrix'=>['pricing','تعرفه خدمات']];
    }
    private static function types(array $node):array {
        $types=[];if(isset($node['widgetType']))$types[]=$node['widgetType'];foreach($node['elements']??[] as $child)if(is_array($child))$types=array_merge($types,self::types($child));return $types;
    }
    public static function plan(int $id):array {
        $data=json_decode(get_post_meta($id,'_elementor_data',true),true);if(!is_array($data))return ['keep'=>[],'moves'=>[]];$keep=[];$moves=[];$map=self::destinations();
        foreach($data as $node){$types=self::types($node);if(count($types)===1&&isset($map[$types[0]])){$slug=$map[$types[0]][0];$moves[$slug]['title']=$map[$types[0]][1];$moves[$slug]['nodes'][]=$node;}else $keep[]=$node;}
        return ['keep'=>$keep,'moves'=>$moves];
    }
    public static function relocate(int $id):int {
        if(!current_user_can('edit_theme_options')||!current_user_can('edit_post',$id))throw new \RuntimeException('دسترسی ویرایش صفحه را ندارید.');
        if(!function_exists('wp_check_post_lock'))require_once ABSPATH.'wp-admin/includes/post.php';
        if(wp_check_post_lock($id))throw new \RuntimeException('صفحه اصلی در حال ویرایش است.');
        $plan=self::plan($id);if(!$plan['moves'])return 0;$created=[];$changes=[];$backups=[];$count=0;
        try {
            foreach($plan['moves'] as $slug=>$move){
                $page=get_page_by_path($slug);if(!$page){if(!current_user_can('publish_pages'))throw new \RuntimeException('اجازه ساخت صفحه مقصد را ندارید.');$new=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$move['title'],'post_name'=>$slug],true);if(is_wp_error($new))throw new \RuntimeException($new->get_error_message());$created[]=$new;$page=get_post($new);}
                if($page->ID===$id||!current_user_can('edit_post',$page->ID)||wp_check_post_lock($page->ID))throw new \RuntimeException('صفحه مقصد قفل یا بدون دسترسی است.');
                $raw=get_post_meta($page->ID,'_elementor_data',true);$tree=json_decode($raw,true);
                if(!is_array($tree)){if($raw!==''||trim($page->post_content)!=='')throw new \RuntimeException('صفحه مقصد محتوای غیرالمنتوری دارد؛ برای حفظ محتوا، انتقال انجام نشد.');$tree=[];}
                $changes[$page->ID]=array_merge($tree,$move['nodes']);$count+=count($move['nodes']);
            }
            $changes[$id]=$plan['keep'];$stamp=time();
            foreach($changes as $post=>$tree){
                foreach(['_elementor_data','_elementor_edit_mode','_wp_page_template'] as $key)$backups[$post][$key]=['exists'=>metadata_exists('post',$post,$key),'value'=>get_post_meta($post,$key,true)];
                add_post_meta($post,'_zc_layout_backup_'.$stamp,wp_slash(wp_json_encode($backups[$post])));
            }
            foreach($changes as $post=>$tree){
                $json=wp_json_encode($tree);update_post_meta($post,'_elementor_data',wp_slash($json));if(get_post_meta($post,'_elementor_data',true)!==$json)throw new \RuntimeException('ذخیره ساختار انجام نشد؛ نسخه قبلی بازگردانده شد.');
                update_post_meta($post,'_elementor_edit_mode','builder');if(in_array($post,$created,true))update_post_meta($post,'_wp_page_template','template-elementor-canvas.php');delete_post_meta($post,'_elementor_css');clean_post_cache($post);
            }
            if(class_exists('Elementor\\Plugin'))\Elementor\Plugin::instance()->files_manager->clear_cache();return $count;
        }catch(\Throwable $error){
            foreach($backups as $post=>$meta)foreach($meta as $key=>$saved){if($saved['exists'])update_post_meta($post,$key,wp_slash($saved['value']));else delete_post_meta($post,$key);}
            foreach($created as $post)wp_delete_post($post,true);throw $error;
        }
    }
    public static function page():void {
        if(!current_user_can('edit_theme_options'))return;
        echo '<div class="wrap"><h1>ساماندهی ویجت‌های دموی ۳٫۲</h1><p>با اقدام شما، شش ابزار تخصصی از صفحه اصلی به صفحات خدمات، پروژه‌ها، درباره ما، سئو و تعرفه‌ها منتقل می‌شوند. همان تنظیمات و محتوا منتقل و از مبدأ و مقصد پشتیبان گرفته می‌شود. گروه‌های حاوی چند ویجت دست‌نخورده می‌مانند. ابتدا در staging آزمایش کنید.</p>';
        $id=absint($_POST['source_id']??get_option('page_on_front'));
        if(isset($_POST['zc_relocate'])){check_admin_referer('zc_relocate');try{$count=self::relocate($id);echo '<div class="notice notice-success"><p>'.esc_html(sprintf('%d بخش تخصصی منتقل شد.',$count)).'</p></div>';}catch(\Throwable $error){echo '<div class="notice notice-error"><p>'.esc_html($error->getMessage()).'</p></div>';}}
        $plan=current_user_can('edit_post',$id)?self::plan($id):['moves'=>[]];echo '<ul>';foreach($plan['moves'] as $slug=>$move)echo '<li>'.esc_html($move['title'].' — '.count($move['nodes']).' بخش').'</li>';echo '</ul><form method="post">';wp_nonce_field('zc_relocate');echo '<label>شناسه صفحه مبدأ <input type="number" name="source_id" min="1" required value="'.esc_attr($id).'"></label><p><button name="zc_relocate" value="1" class="button button-primary">پشتیبان‌گیری و انتقال به صفحات مرتبط</button></p></form></div>';
    }
}
