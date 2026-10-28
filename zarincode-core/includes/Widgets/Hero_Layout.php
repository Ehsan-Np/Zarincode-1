<?php
/** Explicit, backed-up structural upgrade; never reimports a page or rewrites it on view. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Hero_Layout {
    private static function node(string $type,array $settings,array $children=[]):array {
        return ['id'=>substr(wp_generate_uuid4(),0,8),'elType'=>$type,'settings'=>$settings,'elements'=>$children];
    }
    /** Side column = professional products slider + CTA; the retired profile widget is gone from the project. */
    public static function composition(array $settings=[],array $slider=[]):array {
        $widgets=[];
        foreach(['hero_intro','hero_cta'] as $kind){
            $m=Native_Design::model($kind);$allowed=['design_mode'=>true];
            foreach(array_keys($m['fields']) as $key)$allowed[Native_Design::id($m,$key)]=true;
            foreach(array_keys($m['repeaters']) as $key)$allowed[Native_Design::id($m,$key)]=true;
            $allowed['n_hero_styles']=true;
            $s=array_intersect_key($settings,$allowed);$s['design_mode']='original';
            if(isset($s['n_hero_styles']))$s['n_hero_styles']=array_values(array_filter(is_array($s['n_hero_styles'])?$s['n_hero_styles']:[],static fn($rule)=>isset($m['parts'][$rule['target']??''])));
            // Preserve compatible typography keys; outer spacing and custom CSS require staging review.
            foreach($settings as $key=>$value)if(strpos($key,'_typography')===0)$s[$key]=$value;
            $widget=self::node('widget',$s);$widget['widgetType']='zarincode_'.$kind;$widgets[]=$widget;
        }
        $products=self::node('widget',$slider);$products['widgetType']='zarincode_products_slider';
        $side=self::node('container',['content_width'=>'full','css_classes'=>'zc-hero-side','flex_direction'=>'column'],[$products,$widgets[1]]);
        return self::node('container',['content_width'=>'full','css_classes'=>'zc-hero-composition','flex_direction'=>'row','flex_direction_mobile'=>'column'],[$widgets[0],$side]);
    }
    public static function migrate_tree(array $tree,int &$changed):array {
        foreach($tree as &$node){
            if(($node['widgetType']??'')==='zarincode_hero'&&($node['settings']['design_mode']??'original')==='original'){
                $settings=$node['settings']??[];
                // Migrate legacy text fields before partitioning the settings.
                $settings=Native_Design::migrate($settings,$settings,'hero');
                $slider=class_exists(\Zarincode\Runtime260\Demo\Service_Demo::class)?\Zarincode\Runtime260\Demo\Service_Demo::settings('zarincode_products_slider'):[];
                $replacement=self::composition($settings,$slider);
                $replacement['id']=$node['id']??$replacement['id'];$node=$replacement;$changed++;
            }elseif(!empty($node['elements']))$node['elements']=self::migrate_tree($node['elements'],$changed);
        }unset($node);
        return $tree;
    }
    public static function page():void {
        if(!current_user_can('edit_theme_options'))return;
        require_once __DIR__.'/Elementor_Widgets_Collection.php';
        echo '<div class="wrap"><h1>تفکیک هیرو به سه ویجت مستقل</h1><p>فقط هیرو اصلی صفحه انتخاب‌شده تفکیک می‌شود. محتوای سایر بخش‌ها و منابع دستی/پویا تغییر نمی‌کند. نسخه قبل در متای پشتیبان نگه‌داری می‌شود. استایل‌های اختصاصی سطح ویجت و Custom CSS را پس از تبدیل بررسی کنید.</p>';
        if(isset($_POST['zc_split_hero'])){
            check_admin_referer('zc_split_hero');$id=absint($_POST['page_id']??0);
            if($id&&current_user_can('edit_post',$id)&&!wp_check_post_lock($id)){
                $raw=get_post_meta($id,'_elementor_data',true);$data=json_decode($raw,true);$count=0;
                if(is_array($data)){
                    $tree=self::migrate_tree($data,$count);
                    if($count){
                        add_post_meta($id,'_zc_hero_backup_'.time(),wp_slash($raw));
                        wp_save_post_revision($id);
                        update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($tree)));
                        delete_post_meta($id,'_elementor_css');clean_post_cache($id);
                        if(class_exists('Elementor\\Plugin'))\Elementor\Plugin::instance()->files_manager->clear_cache();
                    }
                    echo '<div class="notice notice-success"><p>'.esc_html(sprintf('%d هیرو تبدیل شد؛ صفحه را در Elementor بررسی و ذخیره کنید.',$count)).'</p></div>';
                }
            }else echo '<div class="notice notice-error"><p>صفحه معتبر نیست، دسترسی ندارید یا اکنون در حال ویرایش است.</p></div>';
        }
        echo '<form method="post">';wp_nonce_field('zc_split_hero');
        echo '<label for="zc-page-id">شناسه صفحه: </label><input type="number" min="1" id="zc-page-id" name="page_id" required value="'.esc_attr(get_option('page_on_front')).'"><p><button class="button button-primary" name="zc_split_hero" value="1">پشتیبان‌گیری و تفکیک هیرو</button></p></form></div>';
    }
}
