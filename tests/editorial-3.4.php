<?php
/** Disposable WordPress: current section skins, TOC safety and badge compiler regressions. */
use Zarincode\Runtime260\Widgets\Trust_Badges as T;
use Zarincode\Theme\Core\Article_Toc as Toc;
use Zarincode\Runtime260\Widgets\Native_Design as N;
$n=0;$ok=static function($c,$s)use(&$n){$n++;if(!$c)throw new RuntimeException($s);};
$types=Elementor\Plugin::instance()->widgets_manager->get_widget_types();
$render=static function($settings){$w=Elementor\Plugin::instance()->elements_manager->create_element_instance(['id'=>'styles34','elType'=>'widget','widgetType'=>'zarincode_services','settings'=>$settings]);ob_start();$w->render_content();return ob_get_clean();};
$original=$render([]);$ok(strpos($original,'data-zc-card-skin')===false,'original/default cannot activate mixed skins');
foreach(['editorial','night','outline','horizontal','sunshine','glass'] as $skin){$html=$render(['n_services_card_skin'=>$skin]);preg_match_all('/data-zc-card-skin="([^"]+)"/',$html,$m);$ok(count($m[1])===6,'six manual records preserved');$ok(array_unique($m[1])===[$skin],'uniform skin across section');}
$ok(strpos($render(['n_services_card_skin'=>'<script>']), 'data-zc-card-skin')===false,'invalid skin original fallback');
$html='<p id="keep">Intro</p><h2 id="custom">First <em>heading</em></h2><p>Text</p><h3>دوم</h3><h4>سوم</h4><h2 id="dup">Four</h2><div id="dup"></div><script>const x="<h2>fake</h2>";</script>';
foreach([1,2,3,4,99] as $cols){$out=Toc::content($html,123,$cols);$ok(substr_count($out,'data-level=')===4,'real headings only, not script text');$ok(strpos($out,'href="#custom"')!==false,'existing valid ID preserved');$ok(strpos($out,'const x="<h2>fake</h2>";')!==false,'scripts not reparsed or executed');$ok(strpos($out,'--zc-toc-columns:'.min(4,$cols))!==false,'clamped columns');$ok(Toc::content($out,123,$cols)===$out,'idempotent');}
$ok(Toc::content('<h2>One</h2>',1)==='<h2>One</h2>','no unnecessary one-heading toc');
$z=T::normalize(['provider'=>'zarinpal','code'=>'<script src="https://www.zarinpal.com/webservice/TrustCode"></script>']);$ok(strpos($z['url'],'https://www.zarinpal.com/trustPage/')===0,'official Zarin code compiled');$ok($z['image']==='https://cdn.zarinpal.com/badges/trustLogo/1.png','official badge image');
foreach(['javascript:alert(1)','https://zarinpal.com.evil.example/cert','http://www.zarinpal.com/cert','https://user@zarinpal.com/cert','https://zarinpal.com:999/cert'] as $url)$ok(T::url($url,'zarinpal')==='','unsafe target rejected');
$code='<a href="https://trustseal.enamad.ir/?id=123&amp;Code=abc" onclick="alert(1)"><img src="https://trustseal.enamad.ir/logo.aspx?id=123" onerror="alert(2)"></a><script>alert(3)</script>';
$out=T::html([['provider'=>'enamad','code'=>$code,'label'=>'<b>اعتماد</b>']]);$ok(strpos($out,'<script')===false&&strpos($out,'onclick')===false&&strpos($out,'onerror')===false,'never echo pasted code');$ok(strpos($out,'loading="lazy"')!==false&&strpos($out,'width="120"')!==false,'lazy dimensioned badge');$ok(T::html([])==='','no fake default');$ok(T::html([['provider'=>'zibal','code'=>'<script src="https://evil.example/badge.js"></script>']])==='','unknown scripts fail closed');
$samCode=<<<'HTML'
<img src="https://logo.samandehi.ir/logo.aspx?id=123" onclick='window.open("https://logo.samandehi.ir/Verify.aspx?id=123", "Popup"); alert(1)'>
HTML;
$sam=T::html([['provider'=>'samandehi','code'=>$samCode]]);$ok(strpos($sam,'https://logo.samandehi.ir/Verify.aspx?id=123')!==false&&strpos($sam,'onclick')===false,'legacy literal onclick compiled safely');
$ok(T::normalize(['provider'=>[]])===[],'malformed provider safe');$ok(T::normalize(['provider'=>'zarinpal','image'=>['url'=>[]],'link'=>['url'=>[]]])===[],'malformed URLs safe');
$w=$types['zarincode_trust_badges'];$ok($w->get_script_depends()===[],'no badge JS dependency');$ok($w->get_controls('badges')['type']==='repeater','native badge repeater');
$schema=Zarincode\Theme\Redux\OptionsConfig::get_sections_schema();$ok(isset($schema['article_experience']),'article options registered');$ids=[];foreach($schema as $sec)foreach($sec['fields'] as $f)$ids[]=$f['id'];foreach(['social_bale','social_eitaa','social_rubika','social_soroush','footer_zarinpal_html','footer_gateway_html','blog_toc_columns'] as $id)$ok(in_array($id,$ids,true),'option '.$id);
echo "PASS $n editorial/studio security assertions\n";
