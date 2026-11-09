<?php
/** Public rendering facade: Elementor and shortcode output share one settings-driven engine. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
require_once __DIR__.'/Widget_Schema.php';
require_once __DIR__.'/Editable_Renderer.php';
class Renderer {
    private static ?Renderer $instance=null;
    public static function instance(): Renderer { return self::$instance??(self::$instance=new self()); }
    private function __construct(){add_shortcode('zarincode_widget',[$this,'handle_shortcode']);}
    /**
     * Homepage sections rendered by dedicated templates.
     *
     * The shortcode type is the widget name (`home_hero`); the template method is
     * the bare section name (`hero`). Both sides are mapped explicitly so that a
     * rename on either one can never produce a fatal "undefined method" error.
     */
    private const HOME_SECTIONS=[
        'home_hero' => 'hero',
        'home_partners' => 'partners',
        'home_services' => 'services',
        'home_products' => 'products',
        'home_portfolio' => 'portfolio',
        'home_why' => 'why',
        'home_steps' => 'steps',
        'home_pricing' => 'pricing',
        'home_testimonials' => 'testimonials',
        'home_blog' => 'blog',
        'home_promo' => 'promo',
    ];
    public function handle_shortcode($atts): string {
        $atts=shortcode_atts(['type'=>'hero','section'=>'','count'=>8],$atts,'zarincode_widget');
        $kind=sanitize_key($atts['type']);
        if(isset(self::HOME_SECTIONS[$kind])){
            $tpl='\Zarincode\Theme\Home\Home_Templates';
            $method=self::HOME_SECTIONS[$kind];
            if(!class_exists($tpl)||!method_exists($tpl,$method))return '';
            $html=$tpl::$method(array());
            if(''===$html)return '';
            // The design system is normally enqueued by the theme on the front page;
            // a shortcode can appear on any other template, so pull it in here too.
            if(wp_style_is('zarincode-home-cheerful','registered'))wp_enqueue_style('zarincode-home-cheerful');
            return $tpl::wrap_open().'<div class="zc-hp-body">'.$html.'</div>'.$tpl::wrap_close();
        }
        if(!isset(Widget_Schema::definitions()[$kind]))return '';
        if($kind==='page_section')$atts['section_key']=$atts['section']?:'about-page';
        return self::output($kind,$atts);
    }
    public static function original(string $kind,array $settings=[]): string {
        require_once __DIR__.'/Original_Design.php';
        require_once __DIR__.'/Original_Pages.php';
        $methods=['header'=>'render_header_block','footer'=>'render_footer_block','hero'=>'render_hero','brands'=>'render_brands_marquee','services'=>'render_services_bento','products'=>'render_products_marketplace','portfolio'=>'render_portfolio_showcase','why_us'=>'render_why_us_stats','pricing'=>'render_pricing_tabs','promo'=>'render_promo_banner','steps'=>'render_project_steps','testimonials'=>'render_testimonials','blog'=>'render_blog_grid','page_section'=>'render_page_section'];
        return isset($methods[$kind]) ? Original_Design::{$methods[$kind]}($settings) : '';
    }
    public static function output(string $kind,array $settings=[]): string {
        // The approved original design is plain PHP/CSS: it does not need
        // Elementor, so it keeps rendering when Elementor is not active (for
        // example while it is being updated, or in the pages' non-Elementor
        // fallback content). Only the v2.6 custom layout depends on Elementor's
        // widget layer, and that one still respects the feature flag.
        if(($settings['design_mode']??'original')!=='custom') return self::original($kind,$settings);
        if(!\Zarincode\Runtime260\Features\Manager::enabled('native_widgets'))return '';
        $defaults=Widget_Schema::defaults($kind);
        if(class_exists('Elementor\\Plugin')&&did_action('elementor/init')){
            $element=\Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'zc'.substr(md5($kind),0,6),'elType'=>'widget','widgetType'=>'zarincode_'.$kind,'settings'=>$settings]);
            if($element)$defaults=array_replace($defaults,$element->get_settings_for_display());
        }
        $settings=array_replace($defaults,$settings);
        if($kind==='page_section'){
            $key=$settings['section_key']??'about-page';
            $prefix='document_'.str_replace('-','_',$key).'_';
            foreach(Legal_And_Inner_Pages::documents()[$key]??[] as $field=>$value) if(!array_key_exists($prefix.$field,$settings))$settings[$prefix.$field]=$value;
        }
        wp_enqueue_style('zarincode-editable-widgets');wp_enqueue_script('zarincode-editable-widgets');
        return Editable_Renderer::render($kind,$settings,'zc-'.sanitize_key($kind));
    }
    public static function render_hero(array $s=[]): string{return self::output('hero',$s);}
    public static function render_brands_marquee(array $s=[]): string{return self::output('brands',$s);}
    public static function render_header_block(array $s=[]): string{return self::output('header',$s);}
    public static function render_footer_block(array $s=[]): string{return self::output('footer',$s);}
    public static function render_services_bento(array $s=[]): string{return self::output('services',$s);}
    public static function render_products_marketplace(array $s=[]): string{return self::output('products',$s);}
    public static function render_portfolio_showcase(array $s=[]): string{return self::output('portfolio',$s);}
    public static function render_why_us_stats(array $s=[]): string{return self::output('why_us',$s);}
    public static function render_pricing_tabs(array $s=[]): string{return self::output('pricing',$s);}
    public static function render_promo_banner(array $s=[]): string{return self::output('promo',$s);}
    public static function render_project_steps(array $s=[]): string{return self::output('steps',$s);}
    public static function render_testimonials(array $s=[]): string{return self::output('testimonials',$s);}
    public static function render_blog_grid(array $s=[]): string{return self::output('blog',$s);}
    public static function render_page_section(array $s=[]): string{return self::output('page_section',$s);}
}
