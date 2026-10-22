<?php
namespace Zarincode\Runtime260\Elementor;
defined( 'ABSPATH' ) || exit;
/** Shared native controls: no defaults, no replacement panel, no change to existing stored IDs. */
final class Shared_Controls {
    public static function register($element,string $section_id): void {
        static $seen;
        if (!$element instanceof \Elementor\Widget_Base || !str_starts_with($element->get_name(),'zarincode_')) {return;}
        if (!$seen) {$seen=new \WeakMap();}
        if (isset($seen[$element])) {return;}
        $seen[$element]=true;
        // Native widget section wrapper allows n_ sections in the approved original layout mode.
        $element->start_controls_section('n_zc_interaction_states',['label'=>esc_html__('Focus and interaction states','zarincode-core'),'tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
        $selector='{{WRAPPER}} :where(a,button,input,select,textarea,[tabindex]):focus-visible';
        $element->add_control('zc_focus_color',['label'=>esc_html__('Focus outline color','zarincode-core'),'type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>[$selector=>'outline-color: {{VALUE}};outline-style:solid;outline-width:2px;outline-offset:3px;']]);
        $element->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(),['name'=>'zc_focus_shadow','label'=>esc_html__('Focus shadow','zarincode-core'),'selector'=>$selector]);
        $element->add_control('zc_hover_link_color',['label'=>esc_html__('Link hover color','zarincode-core'),'type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} a:hover'=>'color:{{VALUE}};']]);
        $element->end_controls_section();
    }
}
