<?php
namespace Zarincode\Runtime260\Elementor;
defined( 'ABSPATH' ) || exit;
/** Public text only. Arbitrary post meta, private content and customer data are never exposed. */
final class Public_Data_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name(){return 'zarincode-public-content';}
    public function get_title(){return esc_html__('ZarinCode public content','zarincode-core');}
    public function get_group(){return 'zarincode-public';}
    public function get_categories(){return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];}
    protected function register_controls(){
        $this->add_control('field',['label'=>esc_html__('Public field','zarincode-core'),'type'=>\Elementor\Controls_Manager::SELECT,'default'=>'title','options'=>[
            'title'=>esc_html__('Title','zarincode-core'),'excerpt'=>esc_html__('Excerpt','zarincode-core'),
            'service_price'=>esc_html__('Service price range','zarincode-core'),'project_client'=>esc_html__('Project client (public)','zarincode-core')]]);
    }
    public function render(){
        $id=(int)get_the_ID();
        if (!$id || get_post_status($id)!=='publish' || post_password_required($id) || !is_post_type_viewable(get_post_type($id))) {return;}
        $field=$this->get_settings('field');$text='';
        if ($field==='title') {$text=get_the_title($id);}
        elseif($field==='excerpt') {$text=wp_strip_all_tags((string)get_post_field('post_excerpt',$id));}
        elseif($field==='service_price' && get_post_type($id)==='zc_service') {$value=get_post_meta($id,'_zc_srv_price_range',true);$text=is_scalar($value)?(string)$value:'';}
        elseif($field==='project_client' && get_post_type($id)==='zc_project') {$value=get_post_meta($id,'_zc_prj_client',true);$text=is_scalar($value)?(string)$value:'';}
        echo esc_html($text);
    }
}
