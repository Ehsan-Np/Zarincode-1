<?php
/**
 * Single Post / Service / Project Template
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-container" style="padding-top:2.5rem;padding-bottom:4rem;" role="main">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'zc-glass-panel' ); ?> style="padding:2.5rem;">
            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1rem;">
                <span class="zc-badge-pill" style="margin-bottom:0;">ZARINCODE OFFICIAL ARTICLE</span>
                <span class="zc-meta-tag"><i class="fa-solid fa-user-tie"></i> نویسنده: احسان نادری پناه</span>
                <span class="zc-meta-tag"><i class="fa-solid fa-phone"></i> مشاوره: 09024561001</span>
            </div>
            <h1 class="zc-section-title"><?php the_title(); ?></h1>
            <div class="entry-content" style="line-height:2;font-size:1.02rem;">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();
