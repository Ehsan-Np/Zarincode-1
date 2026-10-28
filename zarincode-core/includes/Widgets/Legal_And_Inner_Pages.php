<?php
/** Editable page presets; the JSON documents are defaults, not fixed page templates. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Legal_And_Inner_Pages {
    public static function documents(): array {
        static $data;
        if(null===$data) {
            $data=json_decode(file_get_contents(__DIR__.'/documents.json'),true)?:[];
            $resolve=static function($value)use(&$resolve):string|array {
                if(is_array($value))return array_map($resolve,$value);
                return is_string($value)?str_replace(['{{HOME}}','{{THEME}}'],[untrailingslashit(home_url()),get_template_directory_uri()],$value):$value;
            };
            $data=$resolve($data);
        }
        return $data;
    }
    public static function render(string $key): string {
        require_once __DIR__.'/Widget_Schema.php';
        require_once __DIR__.'/Editable_Renderer.php';
        $doc=self::documents()[$key]??[];
        $settings=['section_key'=>$key,'title'=>'','show_contact'=>$key==='contact-page'?'yes':'','phone'=>'09024561001','email'=>'info@zarincode.com','primary_text'=>'','secondary_text'=>''];
        if(in_array($key,['services-page','projects-page'],true)) {
            $settings['items']=$key==='services-page'?Widget_Schema::items('services'):Editable_Renderer::query_items('portfolio',['read_more'=>'مشاهده پروژه']);
        }
        foreach(['title','badge','description','clauses'] as $field) $settings['document_'.str_replace('-','_',$key).'_'.$field]=$doc[$field]??($field==='clauses'?[]:'');
        wp_enqueue_style('zarincode-editable-widgets');
        wp_enqueue_script('zarincode-editable-widgets');
        return Editable_Renderer::render('page_section',$settings,'page-'.sanitize_key($key));
    }
}
