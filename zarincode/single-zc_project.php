<?php
/** Project case study: original project details in a product-inspired showcase/sidebar. */
defined('ABSPATH') || exit;
get_header();
while (have_posts()): the_post();
    $id = get_the_ID();
    if (post_password_required()): ?>
        <main id="primary" class="zc-container zc-project-single"><h1><?php the_title(); ?></h1><?php the_content(); ?></main>
        <?php continue;
    endif;
    $fields = [
        '_zc_prj_client'=>'کارفرما / برند', '_zc_prj_agency'=>'مجری پروژه',
        '_zc_prj_date'=>'تاریخ اجرا', '_zc_prj_duration'=>'مدت اجرا',
        '_zc_prj_cms'=>'فناوری و زیرساخت', '_zc_prj_plan'=>'خدمات ارائه‌شده',
    ];
    $metrics = get_post_meta($id, '_zc_prj_metrics', true);
    $raw_url = get_post_meta($id, '_zc_prj_url', true);
    $url = is_string($raw_url) ? esc_url($raw_url, ['http','https']) : '';
    $archive = home_url('/projects-portfolio/');
    $terms = get_the_terms($id, 'zc_project_cat');
    $category = !is_wp_error($terms) && $terms ? $terms[0]->name : 'مطالعهٔ موردی';
    ?>
    <main id="primary" class="zc-container zc-project-single">
        <nav class="zc-project-crumbs" aria-label="مسیر راهنما">
            <a href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی</a><span aria-hidden="true">/</span>
            <a href="<?php echo esc_url($archive); ?>">نمونه‌کارها</a><span aria-hidden="true">/</span>
            <span aria-current="page"><?php the_title(); ?></span>
        </nav>
        <div class="zc-project-layout">
            <div class="zc-project-main">
                <header class="zc-project-showcase">
                    <div class="zc-project-eyebrow"><span class="zc-project-category"><?php echo esc_html($category); ?></span><span dir="ltr">PROJECT / CASE STUDY</span></div>
                    <h1><?php the_title(); ?></h1>
                    <?php if (has_excerpt()): ?><p class="zc-project-intro"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                    <?php if (has_post_thumbnail()): ?>
                        <figure class="zc-project-cover"><?php the_post_thumbnail('large', ['loading'=>'eager','fetchpriority'=>'high','decoding'=>'async']); ?>
                        <?php if (get_the_post_thumbnail_caption()): ?><figcaption><?php echo esc_html(get_the_post_thumbnail_caption()); ?></figcaption><?php endif; ?></figure>
                    <?php else: ?>
                        <div class="zc-project-no-cover"><span aria-hidden="true">↗</span><p>روایت طراحی و اجرای پروژه</p></div>
                    <?php endif; ?>
                    <div class="zc-project-shortcuts"><a href="#zc-project-story">بررسی پروژه <span aria-hidden="true">↓</span></a><?php if ($url): ?><a href="<?php echo $url; ?>" target="_blank" rel="noopener noreferrer">مشاهده وب‌سایت <span aria-hidden="true">↗</span></a><?php endif; ?></div>
                </header>
                <article id="zc-project-story" class="zc-project-story">
                    <div class="zc-project-section-heading"><span aria-hidden="true">01 /</span><h2>دربارهٔ این پروژه</h2></div>
                    <div class="zc-project-content"><?php the_content(); wp_link_pages(['before'=>'<nav class="page-links" aria-label="صفحات پروژه">','after'=>'</nav>']); ?></div>
                </article>
            </div>
            <aside class="zc-project-sidebar" aria-label="مشخصات پروژه">
                <section class="zc-project-specs">
                    <div class="zc-project-section-heading"><span aria-hidden="true">↗</span><h2>شناسنامهٔ پروژه</h2></div>
                    <dl><?php foreach ($fields as $key=>$label): $value=get_post_meta($id, $key, true); if (!is_scalar($value) || trim((string)$value)==='') continue; ?>
                        <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
                    <?php endforeach; ?></dl>
                    <?php if (is_scalar($metrics) && trim((string)$metrics)!==''): ?><div class="zc-project-outcome"><span>نتیجهٔ ثبت‌شدهٔ پروژه</span><p><?php echo esc_html($metrics); ?></p></div><?php endif; ?>
                    <div class="zc-project-inquiry"><h3>پروژهٔ بعدی، برای شما</h3><p>دربارهٔ نیازها، محدودهٔ کار و مسیر اجرای پروژه گفتگو کنیم.</p>
                    <a class="zc-project-primary" href="<?php echo esc_url(add_query_arg('project', $id, home_url('/contact-us/'))); ?>">درخواست پروژه مشابه <span aria-hidden="true">←</span></a>
                    <a class="zc-project-back" href="<?php echo esc_url($archive); ?>">مشاهده همه نمونه‌کارها</a></div>
                </section>
            </aside>
        </div>
        <?php \Zarincode\Theme\Core\Single_Experience::render(); ?>
    </main>
<?php endwhile;
get_footer();
