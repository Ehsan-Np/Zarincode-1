<?php
/**
 * Archive Portfolio Projects Template
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main id="primary" role="main">
    <?php
    if ( class_exists( '\Zarincode\Core\Widgets\Renderer' ) ) {
        echo \Zarincode\Core\Widgets\Renderer::render_page_section( [ 'section_key' => 'projects-page' ] );
    }
    ?>
</main>
<?php
get_footer();
