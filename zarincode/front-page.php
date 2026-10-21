<?php
/**
 * Front Page Template (100% Elementor-Based Dynamic Rendering)
 *
 * In accordance with ThemeForest & Zhaket Elementor-based theme standards,
 * the front page renders via the_content() so Elementor widgets control the entire layout.
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-main-content" role="main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
    endif;
    ?>
</main>

<?php
get_footer();
