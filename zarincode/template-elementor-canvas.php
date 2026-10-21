<?php
/**
 * Template Name: Zarincode Elementor Fullwidth (تمامی صفحات المنتوری زرین کد)
 * Template Post Type: page, post, zc_service, zc_project
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-elementor-fullwidth" role="main">
    <?php
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
    ?>
</main>

<?php
get_footer();
