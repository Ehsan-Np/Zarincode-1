<?php
/** Disposable WP-CLI site, verifies actual core gates without replacing Woo policy. */
use Zarincode\Theme\Core\Discussion as D;
$n=0;$ok=function($value,$why)use(&$n){$n++;if(!$value)throw new RuntimeException($why);};
$fixtures=json_decode(file_get_contents('/tmp/zc36-fixture.log'),true);if(!$fixtures)$fixtures=json_decode(explode("\n",file_get_contents('/tmp/zc36-fixture.log'))[0],true);
$product=$fixtures['product'];$post=$fixtures['post'];$GLOBALS['wp_query']=new WP_Query(['p'=>$post['post']]);
$ok((bool)get_option('thread_comments'),'native threading enabled');$ok(get_option('thread_comments_depth')>=3,'multilevel depth');
$ok(D::parent($product['root'],$product['post']) instanceof WP_Comment,'approved same-product parent');$ok(D::parent($product['root'],$post['post'])===null,'cross-post parent denied');$ok(D::parent(99999999,$post['post'])===null,'missing parent denied');
$_POST=['comment_parent'=>$product['root'],'rating'=>5,'zc_article_rating'=>4];$result=D::prepare(['comment_parent'=>$product['root'],'comment_post_ID'=>$product['post']]);$ok(!isset($_POST['rating'],$_POST['zc_article_rating']),'responses never add ratings');$ok($result['comment_parent']===$product['root'],'parent preserved');
$_POST=['rating'=>5];D::prepare(['comment_parent'=>0,'comment_post_ID'=>$product['post']]);$ok($_POST['rating']===5,'root rating preserved');
$handler=static function(){return static function(){throw new RuntimeException('blocked');};};add_filter('wp_die_handler',$handler);
try{D::prepare(['comment_parent'=>$product['root'],'comment_post_ID'=>$post['post']]);$ok(false,'crosspost request must reject');}catch(RuntimeException $e){$ok($e->getMessage()==='blocked','bad parent rejected');}
$pending=wp_insert_comment(['comment_post_ID'=>$product['post'],'comment_content'=>'Pending','comment_approved'=>0]);$ok(D::parent($pending,$product['post'])===null,'pending parent not public');wp_delete_comment($pending,true);
$ids=[];$parent=$product['root'];for($depth=2;$depth<=5;$depth++){$parent=wp_insert_comment(['comment_post_ID'=>$product['post'],'comment_parent'=>$parent,'comment_content'=>'Depth fixture','comment_approved'=>1,'comment_type'=>'review']);$ids[]=$parent;}$ok(D::depth(get_comment($parent))===5,'depth calculated from actual ancestors');
try{D::prepare(['comment_parent'=>$parent,'comment_post_ID'=>$product['post']]);$ok(false,'sixth level must reject');}catch(RuntimeException $e){$ok($e->getMessage()==='blocked','configured depth enforced');}foreach(array_reverse($ids) as $id)wp_delete_comment($id,true);
$verification=get_option('woocommerce_review_rating_verification_required');update_option('woocommerce_review_rating_verification_required','yes');
wp_set_current_user(0);$_POST=['comment_parent'=>$product['root']];try{do_action('pre_comment_on_post',$product['post']);$ok(false,'nonbuyer cannot bypass verified-only policy');}catch(RuntimeException $e){$ok(true,'nonbuyer policy preserved');}
wp_set_current_user(1);do_action('pre_comment_on_post',$product['post']);$ok(true,'authorized shop staff can answer');$_POST=['comment_parent'=>0];try{do_action('pre_comment_on_post',$product['post']);$ok(false,'staff root review still checked');}catch(RuntimeException $e){$ok(true,'root review policy not bypassed');}
update_option('woocommerce_review_rating_verification_required',$verification);$_POST=[];remove_filter('wp_die_handler',$handler);wp_set_current_user(0);
echo "PASS $n discussion/security assertions\n";
