<?php
/**
 * Main Index / Blog Archive Template
 *
 * @package Zarincode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="zc-container" style="padding-top:2.5rem;padding-bottom:4rem;" role="main">
    <div class="zc-section-head text-center">
        <span class="zc-badge-pill"><i class="fa-solid fa-newspaper"></i> ZARINCODE MAGAZINE &amp; BLOG</span>
        <h1 class="zc-section-title">مجله تخصصی <span class="zc-highlight">زرین کد</span></h1>
        <p class="zc-section-desc">آخرین مقالات آموزشی طراحی سایت، مهندسی نرم‌افزار، سئو سایت، سئو شبکه‌های اجتماعی و تولید محتوا به قلم احسان نادری پناه</p>
    </div>

    <?php if ( have_posts() ) : ?>
        <div class="zc-services-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                $cat = get_the_category();
                $cat_name = ! empty( $cat ) ? $cat[0]->name : 'آموزش تخصصی';
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'zc-white-card zc-project-card' ); ?>>
                    <div class="zc-project-visual">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span class="zc-prod-badge"><?php echo esc_html( $cat_name ); ?></span>
                            <span class="zc-prod-ver"><i class="fa-regular fa-clock"></i> ۶ دقیقه مطالعه</span>
                        </div>
                        <div>
                            <h2 style="font-size:1.2rem;font-weight:800;margin:1rem 0 0.35rem;color:#fff;">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                        </div>
                    </div>
                    <div style="color:var(--zc-text-secondary);font-size:0.9rem;">
                        <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 24, '...' ) ); ?>
                    </div>
                    <div class="zc-service-footer">
                        <span style="font-size:0.8rem;color:var(--zc-text-muted);">
                            <i class="fa-solid fa-user-pen" style="color:var(--color-accent);"></i> احسان نادری پناه
                        </span>
                        <a href="<?php the_permalink(); ?>" class="zc-btn zc-btn-yellow" style="padding:0.45rem 1.1rem;font-size:0.84rem;">
                            مطالعه مقاله <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <div style="margin-top:2.5rem;text-align:center;">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="zc-glass-panel" style="padding:3rem;text-align:center;">
            <h3>موردی یافت نشد</h3>
            <p>لطفاً عبارت دیگری را جستجو نمایید یا به صفحه اصلی بازگردید.</p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="zc-btn zc-btn-yellow">صفحه اصلی زرین کد</a>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
