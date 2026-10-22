<?php
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
/** Presentation bridge. Durable policies and metadata editing belong to the companion. */
final class Product_Policies {
    public static function boot():void {
        add_action('admin_menu',static function(){add_submenu_page(null,'سیاست‌ها و پرسش‌های محصول','گارانتی و پرسش‌های محصول','manage_options','zarincode-product-policies',[self::class,'page']);});
    }
    public static function defaults():array {return class_exists('Zarincode\\Runtime260\\Content\\Product_Policy_Store')?\Zarincode\Runtime260\Content\Product_Policy_Store::defaults():[];}
    public static function sanitize($raw):array {return class_exists('Zarincode\\Runtime260\\Content\\Product_Policy_Store')?\Zarincode\Runtime260\Content\Product_Policy_Store::sanitize($raw):[];}
    public static function get(int $id):array {if(class_exists('Zarincode\\Runtime260\\Features\\Manager')&&!\Zarincode\Runtime260\Features\Manager::enabled('product_policies'))return [];return class_exists('Zarincode\\Runtime260\\Content\\Product_Policy_Store')?\Zarincode\Runtime260\Content\Product_Policy_Store::get($id):[];}
    public static function row(array $row,$index):void {if(class_exists('Zarincode\\Runtime260\\Admin\\Product_Policy_Admin'))\Zarincode\Runtime260\Admin\Product_Policy_Admin::row($row,$index);}
    public static function fields(array $data):void {if(class_exists('Zarincode\\Runtime260\\Admin\\Product_Policy_Admin'))\Zarincode\Runtime260\Admin\Product_Policy_Admin::fields($data);}
    public static function box($post):void {if(class_exists('Zarincode\\Runtime260\\Admin\\Product_Policy_Admin'))\Zarincode\Runtime260\Admin\Product_Policy_Admin::box($post);}
    public static function page():void {if(!current_user_can('manage_options'))return;if(class_exists('Zarincode\\Runtime260\\Features\\Manager')&&!\Zarincode\Runtime260\Features\Manager::enabled('product_policies')){echo '<div class="notice notice-info"><p>'.esc_html__('Product policy services are disabled. Existing data is retained.','zarincode').'</p></div>';return;}if(class_exists('Zarincode\\Runtime260\\Admin\\Product_Policy_Admin'))\Zarincode\Runtime260\Admin\Product_Policy_Admin::page();}
    public static function render(int $id):void {$p=self::get($id);if(!$p)return;echo '<section class="zc-product-policy"><div class="zc-policy-heading"><span>شفافیت پیش از خرید</span><h3>گارانتی، پشتیبانی و پرسش‌های شما</h3></div>';if($p['summary'])echo '<div class="zc-policy-summary">'.wpautop($p['summary']).'</div>';if($p['url'])echo '<a class="zc-policy-link" href="'.esc_url($p['url']).'">مطالعه سیاست کامل ↗</a>';foreach($p['faqs'] as $i=>$row)echo '<details class="zc-policy-question"><summary><span>'.esc_html(sprintf('%02d',$i+1)).'</span>'.esc_html($row['question']).'</summary><div>'.wpautop($row['answer']).'</div></details>';echo '</section>';}
}
