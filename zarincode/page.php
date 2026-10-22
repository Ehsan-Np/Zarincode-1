<?php
/**
 * Standard Page Template (Supports Elementor Builder & Standard Gutenberg/Classic Pages)
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-page-main" role="main">
    <?php
    while ( have_posts() ) :
        the_post();
        $is_elementor = get_post_meta( get_the_ID(), '_elementor_edit_mode', true );
        if ( 'builder' === $is_elementor ) :
            the_content();
        else :
            ?>
            <div class="zc-container" style="padding-top:2rem;padding-bottom:4rem;">
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'zc-glass-panel' ); ?> style="padding:2.5rem;">
                    <header style="margin-bottom:1.75rem;border-bottom:1px solid var(--zc-border-subtle);padding-bottom:1.25rem;">
                        <span class="zc-badge-pill">ZARINCODE OFFICIAL PAGE</span>
                        <h1 class="zc-section-title" style="margin-bottom:0;"><?php the_title(); ?></h1>
                    </header>
                    <div class="entry-content">
                        <?php
                        the_content();
                        wp_link_pages(
                            [
                                'before' => '<div class="page-links">' . esc_html__( 'صفحات:', 'zarincode' ),
                                'after'  => '</div>',
                            ]
                        );
                        ?>
                    </div>
                </article>
            </div>
            <?php
        endif;
        if(comments_open()||get_comments_number()){echo '<div class="zc-container zc-page-discussion">';comments_template();echo '</div>';}
    endwhile;
    ?>
</main>

<?php
get_footer();
