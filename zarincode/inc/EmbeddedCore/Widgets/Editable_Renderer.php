<?php
/** Settings-only Elementor renderer. No fixed display copy or fixed card collections. */
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;
final class Editable_Renderer {
    private static function text($v): string { return esc_html((string)$v); }
    private static function rich($v): string { return wp_kses_post((string)$v); }
    private static function url($v): string { return esc_url(is_array($v)?($v['url']??''):(string)$v); }
    private static function icon($v): string {
        if(empty($v['value'])) return '';
        return '<span class="zc-edit-icon">'.Icon_Compatibility::render($v,['aria-hidden'=>'true']).'</span>';
    }
    private static function attrs($link): string {
        if(!is_array($link)) $link=['url'=>$link];
        $attrs=' href="'.self::url($link).'"';
        if(!empty($link['is_external'])) $attrs.=' target="_blank"';
        $rel=[];if(!empty($link['is_external']))$rel[]='noopener noreferrer';if(!empty($link['nofollow']))$rel[]='nofollow';
        if($rel)$attrs.=' rel="'.esc_attr(implode(' ',$rel)).'"';
        foreach(\Elementor\Utils::parse_custom_attributes($link['custom_attributes']??'') as $key=>$value)if(!in_array(strtolower($key),['href','class','style','id','target','rel'],true)&&stripos($key,'on')!==0)$attrs.=' '.esc_attr($key).'="'.esc_attr($value).'"';
        return $attrs;
    }
    private static function button($text,$link,$icon=[],$class='primary'): string {
        if($text==='' || !self::url($link)) return '';
        return '<a class="zc-edit-button zc-edit-'.esc_attr($class).'"'.self::attrs($link).'>'.self::text($text).self::icon($icon).'</a>';
    }
    private static function image($image,$alt='',$caption=''): string {
        if(empty($image['url'])) return '';
        $alt=$alt!==''?$alt:(!empty($image['id'])?get_post_meta(absint($image['id']),'_wp_attachment_image_alt',true):'');
        $html='<figure class="zc-edit-figure"><img class="zc-edit-image" src="'.esc_url($image['url']).'" alt="'.esc_attr($alt).'" loading="lazy" decoding="async">';
        if($caption!=='') $html.='<figcaption>'.self::text($caption).'</figcaption>';
        return $html.'</figure>';
    }
    private static function ids($value):array {
        $values=is_array($value)?$value:(is_scalar($value)?explode(',',(string)$value):[]);$ids=[];
        foreach($values as $id)if(is_scalar($id)&&absint($id)>0)$ids[]=absint($id);
        return array_values(array_unique($ids));
    }
    public static function query_items(string $kind,array $s): array {
        $types=['products'=>'product','blog'=>'post','portfolio'=>'zc_project','services'=>'zc_service'];
        $tax=['products'=>'product_cat','blog'=>'category','portfolio'=>'zc_project_cat','services'=>'zc_service_cat'];
        $args=['post_type'=>($types[$kind] ?? 'post'),'post_status'=>'publish','posts_per_page'=>(($s['show_all']??'')==='yes' || (int)($s['count']??8)===-1 ? -1 : max(1,min(100,absint($s['count']??8)))),'no_found_rows'=>true,'ignore_sticky_posts'=>true,'orderby'=>in_array($s['orderby']??'date',['date','title','menu_order','rand'],true)?($s['orderby']??'date'):'date','order'=>($s['order']??'DESC')==='ASC'?'ASC':'DESC'];
        $ids=self::ids($s['include_ids']??'');
        if($ids) $args['post__in']=$ids;
        $cats=self::ids($s['category_ids']??'');
        if($cats) $args['tax_query']=[['taxonomy'=>($tax[$kind]??'category'),'field'=>'term_id','terms'=>$cats]];
        if($kind==='products') {
            $visibility=function_exists('wc_get_product_visibility_term_ids')?wc_get_product_visibility_term_ids():[];
            if(get_option('woocommerce_hide_out_of_stock_items')==='yes'&&!empty($visibility['outofstock']))$args['tax_query'][]=['taxonomy'=>'product_visibility','field'=>'term_taxonomy_id','terms'=>[$visibility['outofstock']],'operator'=>'NOT IN'];
            if(!empty($visibility['exclude-from-catalog'])) $args['tax_query'][]=['taxonomy'=>'product_visibility','field'=>'term_taxonomy_id','terms'=>[$visibility['exclude-from-catalog']],'operator'=>'NOT IN'];
        }
        $q=new \WP_Query($args);$items=[];
        foreach($q->posts as $post) {
            $terms=get_the_terms($post->ID,($tax[$kind]??'category'));
            $item=['_id'=>'post-'.$post->ID,'visible'=>'yes','title'=>get_the_title($post),'description'=>wp_trim_words(wp_strip_all_tags($post->post_excerpt?:$post->post_content),max(0,min(200,absint($s['excerpt_length']??22)))),'image'=>['url'=>get_the_post_thumbnail_url($post,'zarincode-card')?:'','id'=>get_post_thumbnail_id($post)],'link'=>['url'=>get_permalink($post)],'button_text'=>$s['read_more']??'','category'=>!is_wp_error($terms)&&$terms?implode('، ',wp_list_pluck($terms,'name')):''];
            $item['image']['alt']=get_the_title($post);
            $item['date']=get_the_date('', $post);
            $item['author']=get_the_author_meta('display_name',$post->post_author);
            $item['record_meta']=get_post_meta($post->ID);
            if($kind==='products'&&function_exists('wc_get_product')) {
                $product=wc_get_product($post->ID);
                if(!$product || !$product->is_visible()) continue;
                $item['price_html']=$product->get_price_html();
                $item['price_amount']=wc_get_price_to_display($product);$item['price_currency']=get_woocommerce_currency_symbol();
                $item['demo_link']=['url'=>get_post_meta($post->ID,'_zc_prod_demo_url',true),'is_external'=>true];
                $item['demo_text']=$s['demo_label']??'';
            }
            $filters=[];
            foreach(is_array($terms)?$terms:[] as $term) {
                if($kind==='products') {
                    $ancestors=get_ancestors($term->term_id,($tax[$kind]??'category'),'taxonomy');
                    if($ancestors){$parent=get_term((int)end($ancestors),($tax[$kind]??'category'));if($parent&&!is_wp_error($parent))$term=$parent;}
                }
                $filters[$term->slug]=$term->name;
            }
            $item['filter_terms']=$filters;
            $item['category_slug']=implode(' ',array_keys($filters));
            $items[]=$item;
        }
        return $items;
    }
    public static function render(string $kind,array $s,string $id): string {
        $items=$s['items']??[];
        if($kind==='page_section'&&($s['page_cards_source']??'auto')==='auto') {
            if(($s['section_key']??'')==='services-page') $items=self::query_items('services',$s);
            if(($s['section_key']??'')==='projects-page') $items=self::query_items('portfolio',$s);
        }
        if(($s['source']??'manual')==='query'&&in_array($kind,['products','blog','portfolio','services'],true)) $items=self::query_items($kind,$s);
        $items=array_values(array_filter($items,static fn($item)=>($item['visible']??'yes')==='yes'));
        $layout=in_array($s['layout']??'stack',['stack','split','reverse'],true)?($s['layout']??'stack'):'stack';
        $tag=in_array($s['title_tag']??'h2',['h1','h2','h3','h4','div'],true)?($s['title_tag']??'h2'):'h2';
        ob_start(); ?>
        <section class="zc-edit zc-edit-<?php echo esc_attr($kind); ?>" data-zc-edit="<?php echo esc_attr($id); ?>">
            <div class="zc-edit-intro zc-edit-layout-<?php echo esc_attr($layout); ?>">
                <div class="zc-edit-copy">
                    <?php if(($s['show_badge']??'yes')==='yes'&&($s['badge']??'')!==''): ?><span class="zc-edit-badge"><?php echo ($s['show_icons']??'yes')==='yes'?self::icon($s['badge_icon']??[]):''; echo self::text($s['badge']); ?></span><?php endif; ?>
                    <?php if(($s['show_title']??'yes')==='yes'&&($s['title']??'')!=='') echo '<'.$tag.' class="zc-edit-title">'.self::text($s['title']).'</'.$tag.'>'; ?>
                    <?php if(!empty($s['subtitle'])): ?><div class="zc-edit-subtitle"><?php echo self::text($s['subtitle']); ?></div><?php endif; ?>
                    <?php if(!empty($s['brand_subtitle'])): ?><div class="zc-edit-subtitle"><?php echo self::text($s['brand_subtitle']); ?></div><?php endif; ?>
                    <?php if(($s['show_description']??'yes')==='yes'): ?><div class="zc-edit-description"><?php echo self::rich($s['description']??''); ?></div><?php endif; ?>
                    <?php if(!empty($s['discount'])): ?><span class="zc-edit-badge"><?php echo self::text($s['discount']); ?></span><?php endif; ?>
                    <?php if(($s['show_buttons']??'yes')==='yes'): ?><div class="zc-edit-actions"><?php foreach(['primary','secondary'] as $p) echo self::button($s[$p.'_text']??'',$s[$p.'_link']??[],$s[$p.'_icon']??[],$p); echo self::button($s['video_label']??'',$s['video_url']??[],[],'secondary'); ?></div><?php endif; ?>
                </div>
                <?php if(($s['show_image']??'yes')==='yes') echo self::image($s['image']??[],$s['image_alt']??'',$s['image_caption']??''); ?>
            </div>
            <?php if($kind==='page_section') {
                $key=$s['section_key']??'about-page';
                $content=$s['page_content']??'';
                $prefix='document_'.str_replace('-','_',$key).'_';
                if($content!=='') echo '<div class="zc-edit-description zc-edit-document">'.self::rich($content).'</div>';
                else {
                    echo '<div class="zc-edit-document"'.($key==='dmca'?' dir="ltr"':'').'>';
                    if(($s[$prefix.'badge']??'')!=='') echo '<span class="zc-edit-badge">'.self::text($s[$prefix.'badge']).'</span>';
                    if(($s[$prefix.'title']??'')!=='') echo '<h1 class="zc-edit-title">'.self::text($s[$prefix.'title']).'</h1>';
                    echo '<div class="zc-edit-description">'.self::rich($s[$prefix.'description']??'').'</div>';
                    foreach($s[$prefix.'clauses']??[] as $clause) {
                        if(($clause['visible']??'yes')!=='yes') continue;
                        echo '<article class="zc-edit-clause"><h3>'.(($s['show_icons']??'yes')==='yes'?self::icon($clause['icon']??[]):'').self::text($clause['title']??'').'</h3><div class="zc-edit-description">'.self::rich($clause['description']??'').'</div></article>';
                    }
                    echo '</div>';
                }
            } ?>
            <?php if($kind==='header'): ?><div class="zc-edit-header-tools">
                <?php if(($s['header_show_cart']??'')==='yes'&&function_exists('wc_get_cart_url')) echo self::button($s['header_cart_label']??'',['url'=>wc_get_cart_url()],$s['header_cart_icon']??[],'secondary'); ?>
                <?php if(($s['header_show_mode']??'')==='yes'): ?><button type="button" class="zc-edit-button zc-edit-secondary" data-zc-mode aria-pressed="false"><?php echo self::icon($s['header_mode_icon']??[]); echo self::text($s['header_mode_label']??''); ?></button><?php endif; ?>
                <?php if(($s['header_show_search']??'')==='yes'): ?><details class="zc-edit-search-panel"><summary><?php echo self::icon($s['header_search_icon']??[]); echo self::text($s['header_search_label']??''); ?></summary><form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>"><input type="search" name="s" placeholder="<?php echo esc_attr($s['search_placeholder']??''); ?>" aria-label="<?php echo esc_attr($s['header_search_label']??''); ?>"><button type="submit" class="zc-edit-button zc-edit-primary"><?php echo self::text($s['header_search_label']??''); ?></button></form></details><?php endif; ?>
            </div><?php endif; ?>
            <?php if($items): ?>
                <?php if(!empty($s['show_search'])): ?><label class="zc-edit-search"><input type="search" data-zc-search placeholder="<?php echo esc_attr($s['search_placeholder']??''); ?>" aria-label="<?php echo esc_attr($s['search_placeholder']??''); ?>"></label><?php endif; ?>
                <?php if(!empty($s['show_filters'])): $groups=[];foreach($items as $record){if(!empty($record['filter_terms']))$groups=array_merge($groups,$record['filter_terms']);elseif(!empty($record['category']))$groups[$record['category']]=$record['category'];} ?>
                    <div class="zc-edit-filters" role="group" aria-label="<?php echo esc_attr($s['title']??''); ?>">
                        <button type="button" class="zc-edit-filter is-active" data-zc-filter="" aria-pressed="true"><?php echo self::text($s['all_label']??''); ?></button>
                        <?php foreach($groups as $groupKey=>$group): ?><button type="button" class="zc-edit-filter" data-zc-filter="<?php echo esc_attr($groupKey); ?>" aria-pressed="false"><?php echo self::text($group); ?></button><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if($kind==='header'): ?><details class="zc-edit-mobile-nav"><summary><?php echo self::icon($s['menu_icon']??[]); echo self::text($s['menu_label']??''); ?></summary><?php self::nav($items,true,($s['show_icons']??'yes')==='yes'); ?></details><nav class="zc-edit-desktop-nav"><?php self::nav($items,true,($s['show_icons']??'yes')==='yes'); ?></nav>
                <?php elseif(in_array($kind,['footer','brands'],true)): self::nav($items,true,($s['show_icons']??'yes')==='yes'); ?>
                <?php else: ?><div class="zc-edit-grid">
                    <?php foreach($items as $i=>$item) echo self::card($kind,$item,$s,$i); ?>
                </div><?php endif; ?>
            <?php elseif(in_array($kind,['products','blog','portfolio','services'],true)): ?><p class="zc-edit-empty"><?php echo self::text($s['empty_text']??''); ?></p><?php endif; ?>
            <?php if(!empty($s['stats'])): ?><div class="zc-edit-stats"><?php foreach($s['stats'] as $stat): ?><div class="zc-edit-stat elementor-repeater-item-<?php echo esc_attr($stat['_id']??''); ?>"><?php if(($s['show_icons']??'yes')==='yes') echo self::icon($stat['icon']??[]); ?><strong><?php echo self::text($stat['value']??''); ?></strong><span><?php echo self::text($stat['title']??''); ?></span></div><?php endforeach; ?></div><?php endif; ?>
            <?php if(!empty($s['links'])): ?><ul class="zc-edit-nav zc-edit-links"><?php foreach($s['links'] as $link): ?><li><a<?php echo self::attrs($link['link']??[]); ?>><?php if(($s['show_icons']??'yes')==='yes') echo self::icon($link['icon']??[]); echo self::text($link['title']??''); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
            <?php foreach($s['faq']??[] as $faq): ?><details class="zc-edit-faq"><summary><?php echo self::text($faq['title']??''); ?></summary><div class="zc-edit-description"><?php echo self::rich($faq['answer']??''); ?></div></details><?php endforeach; ?>
            <?php if(($s['show_contact']??'')==='yes'): ?><address class="zc-edit-contact"><?php if(!empty($s['phone'])): ?><a href="<?php echo esc_url('tel:'.preg_replace('/[^+0-9]/','',$s['phone'])); ?>" dir="ltr"><?php echo self::text($s['phone']); ?></a><?php endif; ?><?php if(!empty($s['email'])): ?><a href="<?php echo esc_url('mailto:'.sanitize_email($s['email'])); ?>"><?php echo self::text($s['email']); ?></a><?php endif; ?><span><?php echo self::text($s['address']??''); ?></span></address><?php endif; ?>
            <?php echo self::button($s['map_label']??'',$s['map_link']??[],[],'secondary'); ?>
            <?php if(!empty($s['form_shortcode'])) echo '<div class="zc-edit-form">'.do_shortcode(wp_kses_post($s['form_shortcode'])).'</div>'; ?>
            <?php if(!empty($s['copyright'])): ?><div class="zc-edit-copyright"><?php echo self::text($s['copyright']); ?></div><?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }
    private static function nav(array $items,bool $images=false,bool $icons=true): void { ?>
        <ul class="zc-edit-nav"><?php foreach($items as $item): ?><li class="elementor-repeater-item-<?php echo esc_attr($item['_id']??''); ?>"><a<?php echo self::attrs($item['link']??[]); ?>><?php if($images) echo self::image($item['image']??[],$item['image_alt']??''); if($icons) echo self::icon($item['icon']??[]); echo self::text($item['title']??''); ?></a></li><?php endforeach; ?></ul>
    <?php }
    private static function card(string $kind,array $item,array $s,int $i): string {
        ob_start(); ?>
        <article class="zc-edit-card elementor-repeater-item-<?php echo esc_attr($item['_id']??'item-'.$i); ?><?php echo !empty($item['featured'])?' is-featured':''; ?>" data-zc-groups="<?php echo esc_attr(wp_json_encode(array_keys($item['filter_terms']??[]))); ?>" data-zc-category="<?php echo esc_attr($item['category']??''); ?>">
            <?php if(($s['show_image']??'yes')==='yes') echo self::image($item['image']??[],$item['image_alt']??''); ?>
            <?php if(($s['show_icons']??'yes')==='yes') echo self::icon($item['icon']??[]); ?>
            <?php if(($item['number']??'')!==''): ?><span class="zc-edit-badge zc-edit-number"><?php echo self::text($item['number']); ?></span><?php endif; ?>
            <?php if(($item['badge']??'')!==''): ?><span class="zc-edit-badge"><?php echo self::text($item['badge']); ?></span><?php endif; ?>
            <h3 class="zc-edit-card-title"><?php if(self::url($item['link']??[])): ?><a<?php echo self::attrs($item['link']); ?>><?php echo self::text($item['title']??''); ?></a><?php else: echo self::text($item['title']??''); endif; ?></h3>
            <?php if(!empty($item['subtitle'])): ?><div class="zc-edit-subtitle"><?php echo self::text($item['subtitle']); ?></div><?php endif; ?>
            <div class="zc-edit-card-description"><?php echo self::rich($item['description']??''); ?></div>
            <?php if(!empty($item['role'])): ?><div class="zc-edit-subtitle zc-edit-role"><?php echo self::text($item['role']); ?></div><?php endif; ?>
            <?php if($kind==='testimonials'&&($s['show_rating']??'yes')==='yes'): ?><div class="zc-edit-rating" role="img" aria-label="<?php echo esc_attr((string)($item['rating']??5)); ?>"><span style="--zc-rating:<?php echo esc_attr((string)(max(0,min(5,(float)($item['rating']??5)))*20)); ?>%"><?php echo self::text(str_repeat(mb_substr($s['rating_symbol']??'★',0,1),5)); ?></span></div><?php endif; ?>
            <?php if(($s['show_price']??'yes')==='yes'&&(!empty($item['price_html'])||($item['price']??'')!=='')): ?><div class="zc-edit-price"><?php if(!empty($item['old_price'])): ?><del class="zc-edit-price-old"><?php echo self::text($item['old_price']); ?></del><?php endif; ?> <span class="zc-edit-price-current"><?php echo isset($item['price_html'])?self::rich($item['price_html']):self::text($item['price']??''); ?></span> <small class="zc-edit-period"><?php echo self::text($item['period']??''); ?></small></div><?php endif; ?>
            <?php if(!empty($item['features'])): ?><ul class="zc-edit-features"><?php foreach(preg_split('/\r\n|\r|\n/',$item['features']) as $feature) if(trim($feature)!=='') echo '<li>'.self::text($feature).'</li>'; ?></ul><?php endif; ?>
            <?php if(($s['show_buttons']??'yes')==='yes'): ?><div class="zc-edit-actions"><?php echo self::button($item['button_text']??'',$item['link']??[],$s['primary_icon']??[]); echo self::button($item['demo_text']??'',$item['demo_link']??[],[],'secondary'); ?></div><?php endif; ?>
        </article>
        <?php return ob_get_clean();
    }
}
