<?php
/** Original magazine composition inspired by editorial reading patterns, not third-party template code. */
defined('ABSPATH') || exit;
get_header();
while(have_posts()):the_post();$author=(int)get_the_author_meta('ID');$sides=\Zarincode\Theme\Core\Article_Layout::sides();$words=preg_split('/\s+/u',trim(wp_strip_all_tags(get_the_content())), -1,PREG_SPLIT_NO_EMPTY); ?>
<main id="primary" class="zc-editorial zc-magazine zc-publication zc-container"><div class="zc-publication-shell <?php echo $sides['right']?'has-right ':'';echo $sides['left']?'has-left':''; ?>"><div class="zc-publication-main">
 <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
 <header class="zc-editorial-header">
  <nav class="zc-article-breadcrumb" aria-label="مسیر مقاله"><a href="<?php echo esc_url(home_url('/')); ?>">زرین کد</a><span aria-hidden="true">/</span><a href="<?php echo esc_url(get_post_type_archive_link('post')?:home_url('/?post_type=post')); ?>">مجله</a><span aria-hidden="true">/</span><span>خواندن مقاله</span></nav>
  <div class="zc-article-categories"><?php the_category(' '); ?></div><h1><?php the_title(); ?></h1>
  <?php if(has_excerpt()&&!post_password_required()): ?><p class="zc-article-deck"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
  <div class="zc-editorial-meta"><a class="zc-article-byline" href="<?php echo esc_url(get_author_posts_url($author)); ?>"><?php echo get_avatar($author,44); ?><span><small>به قلم</small><strong><?php the_author(); ?></strong></span></a><span class="zc-article-date"><small>آخرین به‌روزرسانی</small><time datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo esc_html(get_the_modified_date()); ?></time></span><?php if(zarincode_get_option('blog_show_reading_time',true)): ?><span class="zc-reading-time"><i class="fa-regular fa-clock" aria-hidden="true"></i><?php echo esc_html(number_format_i18n(max(1,(int)ceil(count($words)/200)))); ?> دقیقه مطالعه</span><?php endif; ?></div>
 </header>
 <?php if(has_post_thumbnail()&&!post_password_required()): ?><figure class="zc-article-figure"><?php the_post_thumbnail('large',['class'=>'zc-editorial-cover','fetchpriority'=>'high','loading'=>'eager','decoding'=>'async']);if(get_the_post_thumbnail_caption())echo '<figcaption>'.esc_html(get_the_post_thumbnail_caption()).'</figcaption>'; ?></figure><?php endif; ?>
 <div class="zc-reading-layout"><aside class="zc-reading-rail" aria-label="ابزار مطالعه"><span>مطالعه</span><a href="#zc-article-discussion" aria-label="دیدگاه‌های مقاله"><i class="fa-regular fa-comment" aria-hidden="true"></i></a><a href="#zc-article-share" aria-label="اشتراک‌گذاری مقاله"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></a></aside><div class="entry-content zc-editorial-body"><?php the_content();wp_link_pages(['before'=>'<nav class="page-links">','after'=>'</nav>']); ?></div></div>
 </article>
 <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
</div><?php if($sides['right']): ?><aside class="zc-publication-sidebar is-right" aria-label="ستون راست مقاله"><?php dynamic_sidebar('sidebar-blog'); ?></aside><?php endif;if($sides['left']): ?><aside class="zc-publication-sidebar is-left" aria-label="ستون چپ مقاله"><?php dynamic_sidebar('sidebar-blog-left'); ?></aside><?php endif; ?></div></main>
<?php endwhile;get_footer();
