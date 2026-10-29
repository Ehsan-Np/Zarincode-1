<?php
/** Shared explicit demo import: no network, activation migration or XML dependency. */
namespace Zarincode\Runtime260\Demo;
defined('ABSPATH') || exit;

final class Article_Importer {
    public static array $last_result = [];

    public static function run(): array {
        Demo_Media::reset();
        $result = ['posts'=>0, 'images'=>0, 'errors'=>[]];
        $file = get_template_directory().'/demo-data/zarincode-articles.json';
        $data = is_readable($file) ? json_decode(file_get_contents($file), true) : null;
        if (!is_array($data) || count($data['articles'] ?? []) !== 71 || count($data['categories'] ?? []) !== 6) {
            $result['errors'][] = 'بسته مقالات در دسترس یا معتبر نیست؛ قالب را به‌روز کنید.';
            return self::$last_result = $result;
        }
        $terms = [];
        foreach ($data['categories'] as $category) {
            $term = term_exists($category['slug'], 'category');
            if (!$term) $term = wp_insert_term($category['name'], 'category', ['slug'=>$category['slug']]);
            if (is_wp_error($term)) { $result['errors'][] = $term->get_error_message(); continue; }
            $terms[$category['slug']] = (int)(is_array($term) ? $term['term_id'] : $term);
        }
        foreach ($data['articles'] as $i=>$article) {
            if (empty($article['slug']) || empty($article['content']) || !isset($terms[$article['category']])) {
                $result['errors'][] = 'ساختار یا دسته‌بندی مقاله معتبر نیست.'; continue;
            }
            // Never overwrite customer posts merely because the slug matches.
            $existing = get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>1,'fields'=>'ids',
                'meta_key'=>'_zc_demo_article_slug','meta_value'=>$article['slug']]);
            if (!$existing) $existing = get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>1,'fields'=>'ids',
                'name'=>$article['slug'],'meta_key'=>'_zc_demo_article','meta_value'=>'1']);
            $post = ['post_type'=>'post','post_status'=>'publish','post_name'=>$article['slug'],
                'post_title'=>$article['title'],'post_content'=>wp_kses_post(str_replace('https://zarincode.com', untrailingslashit(home_url()), $article['content'])),
                'post_excerpt'=>wp_kses_post($article['excerpt'] ?? ''),'comment_status'=>'open'];
            if ($existing) $post['ID'] = (int)$existing[0];
            $id = wp_insert_post(wp_slash($post), true);
            if (is_wp_error($id) || !$id) { $result['errors'][] = is_wp_error($id) ? $id->get_error_message() : 'ذخیره مقاله انجام نشد.'; continue; }
            update_post_meta($id, '_zc_demo_article', '1');
            update_post_meta($id, '_zc_demo_article_slug', $article['slug']);
            $assigned = wp_set_post_terms($id, [$terms[$article['category']]], 'category');
            if (is_wp_error($assigned)) $result['errors'][] = $assigned->get_error_message();
            ++$result['posts'];
            if (self::assign_cover((int)$id, $i)) ++$result['images'];
            else $result['errors'] = array_values(array_unique(array_merge($result['errors'], Demo_Media::$errors)));
        }
        return self::$last_result = $result;
    }

    public static function assign_cover(int $post_id, int $index): bool {
        $assets = ['post','project','service','product'];
        return Demo_Media::assign($post_id, 'demo-'.$assets[$index % 4].'-cover.webp');
    }
}
