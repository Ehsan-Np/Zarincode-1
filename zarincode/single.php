<?php
/** Editorial single view: author identity comes from WordPress, never fixed demo copy. */
defined('ABSPATH') || exit;
get_header();
while(have_posts()):the_post(); ?>
<main id="primary" class="zc-editorial">
 <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
 <header class="zc-editorial-header"><span class="zc-badge-pill">مجله و دانش زرین کد</span><h1><?php the_title(); ?></h1><div class="zc-editorial-meta"><a href="<?php echo esc_url(get_author_posts_url((int)get_the_author_meta('ID'))); ?>"><?php the_author(); ?></a><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time><span><?php echo esc_html(number_format_i18n(max(1,(int)ceil(count(preg_split('/\s+/u',trim(wp_strip_all_tags(get_the_content())), -1, PREG_SPLIT_NO_EMPTY))/200)))); ?> دقیقه مطالعه</span></div></header>
 <?php if(has_post_thumbnail()&&!post_password_required())the_post_thumbnail('large',['class'=>'zc-editorial-cover']); ?>
 <div class="entry-content zc-editorial-body"><?php the_content();wp_link_pages(['before'=>'<nav class="page-links">','after'=>'</nav>']); ?></div>
 </article>
 <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
</main>
<?php endwhile;get_footer();
