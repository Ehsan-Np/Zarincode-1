<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Article_Layout {
    public static function boot():void {
        add_action('widgets_init',static function(){
            register_sidebar(['name'=>'مقالات — ستون چپ','id'=>'sidebar-blog-left','before_widget'=>'<section id="%1$s" class="zc-article-widget widget %2$s">','after_widget'=>'</section>','before_title'=>'<h3 class="widget-title">','after_title'=>'</h3>']);
            register_widget(Article_Products_Widget::class);register_widget(Article_Template_Widget::class);
        });
    }
    public static function sides():array {
        $mode=zarincode_get_option('blog_sidebar_layout','right');return ['right'=>in_array($mode,['right','both'],true)&&is_active_sidebar('sidebar-blog'),'left'=>in_array($mode,['left','both'],true)&&is_active_sidebar('sidebar-blog-left')];
    }
}
final class Article_Products_Widget extends \WP_Widget {
    public function __construct(){parent::__construct('zc_article_products','زرین کد — محصولات در مقاله',['description'=>'محصولات منتشرشده و قابل مشاهده ووکامرس، بدون ادعای فروش یا امتیاز ساختگی.']);}
    public function form($i){echo '<p><label>عنوان <input class="widefat" name="'.esc_attr($this->get_field_name('title')).'" value="'.esc_attr($i['title']??'برای اجرای این ایده').'"></label></p><p><label>تعداد <input type="number" min="1" max="6" name="'.esc_attr($this->get_field_name('count')).'" value="'.esc_attr($i['count']??3).'"></label></p><p><label>شناسه محصولات انتخابی (اختیاری، جداشده با کاما)<input dir="ltr" class="widefat" name="'.esc_attr($this->get_field_name('ids')).'" value="'.esc_attr($i['ids']??'').'"></label></p>';}
    public function update($new,$old){return ['title'=>sanitize_text_field($new['title']??''),'count'=>max(1,min(6,absint($new['count']??3))),'ids'=>implode(',',array_slice(wp_parse_id_list(is_scalar($new['ids']??null)?$new['ids']:''),0,30))];}
    public function widget($args,$i){
        if(!function_exists('wc_get_products'))return;$query=['status'=>'publish','visibility'=>'visible','limit'=>max(1,min(6,absint($i['count']??3))),'orderby'=>'date','order'=>'DESC'];$ids=wp_parse_id_list($i['ids']??'');if($ids){$query['include']=$ids;$query['orderby']='post__in';}$products=wc_get_products($query);$products=array_filter($products,static fn($p)=>$p->is_visible()&&!post_password_required($p->get_id()));if(!$products)return;
        echo $args['before_widget'];echo $args['before_title'].esc_html($i['title']??'برای اجرای این ایده').$args['after_title'];echo '<div class="zc-sidebar-products">';foreach($products as $p)echo '<a href="'.esc_url(get_permalink($p->get_id())).'">'.$p->get_image('thumbnail',['loading'=>'lazy']).'<span><strong>'.esc_html($p->get_name()).'</strong><small>'.wp_kses_post($p->get_price_html()).'</small><b>بررسی محصول ↗</b></span></a>';echo '</div>'.$args['after_widget'];
    }
}
final class Article_Template_Widget extends \WP_Widget {
    public function __construct(){parent::__construct('zc_article_template','زرین کد — قالب المنتور در ستون',['description'=>'یک قالب منتشرشده Elementor را در ستون مقاله نمایش دهید.']);}
    public function form($i){$posts=get_posts(['post_type'=>'elementor_library','post_status'=>'publish','posts_per_page'=>100]);echo '<p><label>قالب منتشرشده <select class="widefat" name="'.esc_attr($this->get_field_name('template')).'"><option value="0">انتخاب کنید</option>';foreach($posts as $p)echo '<option value="'.esc_attr($p->ID).'" '.selected($i['template']??0,$p->ID,false).'>'.esc_html($p->post_title).'</option>';echo '</select></label></p>';}
    public function update($n,$o){return ['template'=>absint($n['template']??0)];}
    public function widget($args,$i){static $active=[];$id=absint($i['template']??0);if(!$id||isset($active[$id])||get_post_type($id)!=='elementor_library'||get_post_status($id)!=='publish'||!class_exists('Elementor\\Plugin'))return;$active[$id]=true;try{echo $args['before_widget'].\Elementor\Plugin::instance()->frontend->get_builder_content_for_display($id,true).$args['after_widget'];}finally{unset($active[$id]);}}
}
