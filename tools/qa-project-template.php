<?php
/** Template render fixture: WP APIs are doubles, not WordPress integration. */
namespace Zarincode\Theme\Core { class Single_Experience {static function render(){}} }
namespace {
define('ABSPATH',__DIR__);$pass=0;
function get_header(){echo '<!doctype html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>';foreach(['zarincode-main','responsive','site-contract','project-reference'] as $css)echo file_get_contents(__DIR__.'/../zarincode/assets/css/'.$css.'.css');echo '</style></head><body class="zarincode-theme">';}
function get_footer(){echo '<section class="zc-native-portfolio" style="max-width:640px;margin:32px auto;padding:16px;box-sizing:border-box"><div class="zc-filter-tabs" data-zc-tabs="#portfolio-target">';foreach(['همه','طراحی سایت و فروشگاهی','نرم‌افزار اختصاصی','سئو و محتوا','فروشگاهی'] as $i=>$title)echo '<button class="zc-filter-tab '.($i===0?'is-active':'').'" data-filter="'.($i===0?'all':'c'.$i).'" aria-pressed="'.($i===0?'true':'false').'">'.$title.'</button>';echo '</div><div id="portfolio-target"><article data-category="c1">طراحی سایت</article><article data-category="c2">نرم‌افزار</article></div></section><script>'.file_get_contents(__DIR__.'/../zarincode/assets/js/zarincode-main.js').'</script></body></html>';}
function have_posts(){return $GLOBALS['pass']++===0;}function the_post(){}function get_the_ID(){return 42;}
function post_password_required(){return false;}function the_title(){echo 'طراحی فروشگاه آنلاین و تجربهٔ خرید یکپارچه';}
function get_post_meta($id,$key,$single){return ['_zc_prj_client'=>'برند نمونه','_zc_prj_agency'=>'زرین کد','_zc_prj_date'=>'مهر ۱۴۰۵','_zc_prj_duration'=>'۳۰ روز کاری','_zc_prj_cms'=>'وردپرس و ووکامرس','_zc_prj_plan'=>'طراحی، توسعه و بهینه‌سازی','_zc_prj_metrics'=>'شرح خروجی ثبت‌شده در پروژه','_zc_prj_url'=>'https://example.test/'][$key]??'';}
function home_url($path){return 'https://example.test'.$path;}function get_the_terms(){return [(object)['name'=>'طراحی وب و فروشگاه']];}function is_wp_error(){return false;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}function esc_url($s,$p=[]){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function has_excerpt(){return true;}function get_the_excerpt(){return 'از تعریف مسئله تا طراحی تجربهٔ کاربری؛ روایتی از مسیر اجرای یک فروشگاه سریع، خوانا و قابل توسعه.';}
function has_post_thumbnail(){return true;}function get_the_post_thumbnail_caption(){return 'نمای اصلی پروژه — تصویر نمونه';}
function the_post_thumbnail($size,$attrs){echo '<img width="1200" height="700" alt="نمای پروژه" loading="eager" fetchpriority="high" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1200 700\'%3E%3Crect fill=\'%23f5e6bd\' width=\'1200\' height=\'700\'/%3E%3C/svg%3E">';}
function the_content(){echo '<p>در این مطالعهٔ موردی، مسئلهٔ کسب‌وکار و مسیر طراحی و اجرای راهکار را بررسی می‌کنیم.</p><h3>مسئله و راهکار</h3><p>طراحی واکنش‌گرا و معماری روشن محتوا، مسیر خرید را ساده‌تر می‌کند.</p>';}
function wp_link_pages($a){}function add_query_arg($k,$v,$url){return $url.'?'.$k.'='.$v;}
require __DIR__.'/../zarincode/single-zc_project.php';
}
