<?php
/** Server-rendered anchors; WordPress token processor preserves blocks and original HTML. */
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Article_Toc {
    public static function boot():void {
        add_filter('the_content',static function($html){
            if(!is_singular('post')||!in_the_loop()||!is_main_query()||post_password_required()||!zarincode_get_option('blog_toc_enabled',true))return $html;
            return self::content($html,get_the_ID(),(int)zarincode_get_option('blog_toc_columns',2));
        },20);
    }
    public static function content(string $html,int $post,int $columns=2):string {
        if(!class_exists('WP_HTML_Tag_Processor')||!method_exists('WP_HTML_Tag_Processor','next_token')||strpos($html,'data-zc-toc')!==false)return $html;
        $used=[];$scan=new \WP_HTML_Tag_Processor($html);
        while($scan->next_tag()){$id=$scan->get_attribute('id');if(is_string($id)&&$id!=='')$used[$id]=($used[$id]??0)+1;}
        $p=new \WP_HTML_Tag_Processor($html);$entries=[];$pending=null;$number=0;
        while($p->next_token()){
            $tag=$p->get_tag();
            if($p->get_token_type()==='#tag'&&in_array($tag,['H2','H3','H4','H5','H6'],true)){
                if($p->is_tag_closer()){
                    if($pending!==null&&trim($pending['title'])!==''){$pending['title']=trim($pending['title']);$entries[]=$pending;}$pending=null;
                }else{
                    $number++;$id=$p->get_attribute('id');
                    if(!is_string($id)||$id===''||($used[$id]??0)>1){$id='zc-heading-'.$post.'-'.$number;while(isset($used[$id]))$id.='-x';$used[$id]=1;$p->set_attribute('id',$id);}
                    $pending=['id'=>$id,'level'=>(int)substr($tag,1),'title'=>''];
                }
            }elseif($pending!==null&&$p->get_token_type()==='#text')$pending['title'].=html_entity_decode(wp_strip_all_tags($p->get_modifiable_text()),ENT_QUOTES|ENT_HTML5,'UTF-8');
        }
        if(count($entries)<2)return $html;
        $columns=max(1,min(4,$columns));$toc='<nav class="zc-article-toc" data-zc-toc aria-label="فهرست مطالب"><details open><summary><span class="zc-toc-symbol" aria-hidden="true">☷</span><strong>فهرست مطالب</strong><span>در این مقاله می‌خوانید</span></summary><ol style="--zc-toc-columns:'.$columns.'">';
        foreach($entries as $i=>$entry)$toc.='<li data-level="'.$entry['level'].'"><a href="#'.esc_attr(rawurlencode($entry['id'])).'"><span aria-hidden="true">'.esc_html(sprintf('%02d',$i+1)).'</span>'.esc_html($entry['title']).'</a></li>';
        return $toc.'</ol></details></nav>'.$p->get_updated_html();
    }
}
