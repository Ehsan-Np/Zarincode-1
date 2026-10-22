<?php
use PHPUnit\Framework\TestCase;
use Zarincode\Runtime260\Content\Product_Policy_Store as Policies;
use Zarincode\Runtime260\Content\Discussion_Service as Discussion;
use Zarincode\Runtime260\Admin\Product_Policy_Admin;

/** Actual WordPress/Woo metadata persistence and native comment validation. */
final class ContentServicesTest extends TestCase {
    private int $product;
    private int $post;
    private array $original;
    private mixed $sharedPolicies;
    protected function setUp():void {
        $this->sharedPolicies=get_option('zarincode_product_policies',[]);update_option('zarincode_product_policies',['faqs'=>[['id'=>'shared','question'=>'Shared question','answer'=>'Shared answer']]]);
        $this->original=$_POST;$_POST=[];wp_set_current_user(1);
        $this->product=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>'Policy QA']);
        $this->post=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Discussion QA']);
        add_filter('wp_die_handler',[$this,'dieHandler']);
    }
    protected function tearDown():void {
        update_option('zarincode_product_policies',$this->sharedPolicies);
        $_POST=[];wp_delete_post($this->product,true);wp_delete_post($this->post,true);
        remove_filter('wp_die_handler',[$this,'dieHandler']);$_POST=$this->original;
    }
    public function dieHandler():callable {return static function($m){throw new DomainException(wp_strip_all_tags($m));};}
    private function submit(array $fields=[]):void {$_POST=array_replace(['zc_product_policies_nonce'=>wp_create_nonce('zc_product_policies_'.$this->product)],$fields);}
    public function testMetadataPreservedAcrossModesAndPartialUpdates():void {
        $old=['summary'=>'Original','url'=>'https://example.test/','future_key'=>['preserved'=>true],'faqs'=>[['id'=>'stable-id','question'=>'Original?','answer'=>'Original','future_row'=>'keep']]];
        update_post_meta($this->product,'_zc_product_policies',$old);
        $this->submit(['zc_policy_mode'=>'custom','zc_policy'=>['summary'=>'<script>alert(1)</script><strong>Safe</strong>','faqs'=>[['id'=>'stable-id','question'=>'<b>Question?</b>','answer'=>'<p>Answer</p>']]],'zc_product_changelog'=>'<script>bad</script><p>Changes</p>']);
        wp_update_post(['ID'=>$this->product,'post_title'=>'Saved product']);
        $stored=get_post_meta($this->product,'_zc_product_policies',true);
        self::assertSame(['preserved'=>true],$stored['future_key']);self::assertSame('keep',$stored['faqs'][0]['future_row']);self::assertSame('stable-id',$stored['faqs'][0]['id']);
        self::assertStringNotContainsString('<script>', $stored['summary']);self::assertSame('Question?',$stored['faqs'][0]['question']);
        self::assertSame(1,$stored['_zc_policy_schema']);self::assertCount(1,Policies::get($this->product)['faqs']);
        $this->submit(['zc_policy_mode'=>'hide']);Policies::save_product($this->product);
        self::assertSame([],Policies::get($this->product));self::assertSame($stored,get_post_meta($this->product,'_zc_product_policies',true));
        $this->submit(['zc_policy_mode'=>'custom']);Policies::save_product($this->product);self::assertSame('Question?',Policies::get($this->product)['faqs'][0]['question']);
        self::assertStringNotContainsString('<script>',get_post_meta($this->product,'_zc_product_changelog',true));
        $this->submit(['zc_policy_mode'=>'invalid']);Policies::save_product($this->product);self::assertSame('custom',get_post_meta($this->product,'_zc_policy_mode',true));
        delete_post_meta($this->product,'_zc_policy_mode');self::assertNotEmpty(Policies::get($this->product)['faqs']);
    }
    public function testPolicySecurityAndFutureSchemaFailClosed():void {
        $old=['summary'=>'keep','_zc_policy_schema'=>99];update_post_meta($this->product,'_zc_product_policies',$old);
        $this->submit(['zc_policy'=>['summary'=>'bad'],'zc_product_changelog'=>'bad','zc_policy_mode'=>'hide']);Policies::save_product($this->product);
        self::assertSame($old,get_post_meta($this->product,'_zc_product_policies',true));self::assertSame('',get_post_meta($this->product,'_zc_policy_mode',true));
        update_post_meta($this->product,'_zc_product_policies',['summary'=>'keep']);$_POST['zc_product_policies_nonce']='forged';Policies::save_product($this->product);
        self::assertSame(['summary'=>'keep'],get_post_meta($this->product,'_zc_product_policies',true));
        $this->submit(['zc_policy'=>['summary'=>'bad']]);wp_set_current_user(0);Policies::save_product($this->product);Policies::save_product(0);
        self::assertSame(['summary'=>'keep'],get_post_meta($this->product,'_zc_product_policies',true));
    }
    public function testPolicySanitizerBoundsAndMalformedRows():void {
        self::assertSame(['summary'=>'','url'=>'','faqs'=>[]],Policies::sanitize(false));
        $r=Policies::sanitize(['summary'=>[],'url'=>'javascript:alert(1)','faqs'=>[false,['question'=>[],'answer'=>'a'],['question'=>'Q','answer'=>[]],['id'=>'same','question'=>'Q','answer'=>'A'],['id'=>'same','question'=>'Q2','answer'=>'A2'],['id'=>[],'question'=>7,'answer'=>'A3']]]);
        self::assertSame('',$r['url']);self::assertCount(3,$r['faqs']);self::assertCount(3,array_unique(array_column($r['faqs'],'id')));
        self::assertCount(40,Policies::sanitize(['faqs'=>array_fill(0,60,['question'=>'Q','answer'=>'A'])])['faqs']);
        self::assertTrue(Policies::supported(null));self::assertFalse(Policies::supported(['_zc_policy_schema'=>null]));self::assertFalse(Policies::supported(['_zc_policy_schema'=>-1]));self::assertTrue(Policies::supported(['_zc_policy_schema'=>0]));
        self::assertSame('https://example.test/policy',Policies::merge(null,['url'=>'https://example.test/policy'])['url']);
    }
    public function testSocialSanitizationAndIndependentPersistence():void {
        $store=\Zarincode\Runtime260\Content\Social_Profile_Store::class;
        $store::boot();self::assertSame([], $store::sanitize(false));
        $old=get_option('zarincode_custom_socials',[]);
        try {
            update_option('zarincode_custom_socials',[['id'=>'stable','name'=>'Old','future'=>['keep'=>true]]]);
            $_POST=['zc_social_save'=>'1','_wpnonce'=>wp_create_nonce('zc_socials'),'zc_socials'=>[['id'=>'stable','name'=>'<b>Network</b>','url'=>'javascript:alert(1)','font'=>'bad class','enabled'=>true,'type'=>'svg','svg'=>'<svg viewBox="0 0 24 24" onload="alert(1)"><script>bad</script><a href="javascript:bad"><path d="M1 1"/></a><path d="M0 0 L1 1" fill="url(https://bad.test/)"/></svg>']]];
            $_REQUEST['_wpnonce']=$_POST['_wpnonce'];
            $store::save();$rows=get_option('zarincode_custom_socials');self::assertSame(['keep'=>true],$rows[0]['future']);self::assertSame('Network',$rows[0]['name']);self::assertSame('',$rows[0]['url']);self::assertSame(1,$rows[0]['_zc_profile_schema']);
            self::assertStringNotContainsString('onload',$rows[0]['svg']);self::assertStringNotContainsString('<script',$rows[0]['svg']);self::assertStringNotContainsString('href',$rows[0]['svg']);self::assertStringNotContainsString('url(',$rows[0]['svg']);self::assertCount(1,$store::rows());
            update_option('zarincode_custom_socials',[['id'=>'stable','name'=>'Old','_zc_profile_schema'=>99]]);$before=get_option('zarincode_custom_socials');$store::save();self::assertSame($before,get_option('zarincode_custom_socials'));
            wp_set_current_user(0);$store::save();self::assertSame($before,get_option('zarincode_custom_socials'));
        } finally {unset($_REQUEST['_wpnonce']);wp_set_current_user(1);update_option('zarincode_custom_socials',$old);}
    }
    public function testSocialMarkupBoundsAndInvalidTypes():void {
        $store=\Zarincode\Runtime260\Content\Social_Profile_Store::class;
        foreach([null,[], '',str_repeat('a',50001),'<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg/>','<!ENTITY x "bad">','<div/>','<svg>'] as $raw)self::assertSame('',$store::svg($raw));
        self::assertSame('',$store::url([]));self::assertSame('',$store::url('//example.test'));self::assertSame('',$store::template([]));self::assertSame('',$store::template(str_repeat('a',2049)));
        self::assertSame('https://example.test/share?url={url}', $store::template('https://example.test/share?url={url}'));
        $rows=$store::sanitize([null,['name'=>[]],['id'=>'same','name'=>'A','type'=>'bad','image'=>[]],['id'=>'same','name'=>'B','font'=>'fa-brands fa-telegram','enabled'=>1]]);self::assertCount(2,$rows);self::assertNotSame($rows[0]['id'],$rows[1]['id']);self::assertSame('fa-brands fa-telegram',$rows[1]['font']);
    }
    public function testSharedPolicyAdminSaveAndEscapedViews():void {
        require_once ABSPATH . "wp-admin/includes/template.php";
        $old=get_option('zarincode_product_policies',[]);
        try {
            $incoming=['summary'=>'<strong>Shared</strong>','url'=>'https://example.test/policy','faqs'=>[['id'=>'stable','question'=>'A "quoted" question','answer'=>'<p>Answer</p>']]];
            $_POST=['zc_policy_save'=>'1','zc_policy'=>$incoming,'_wpnonce'=>wp_create_nonce('zc_shared_policy')];$_REQUEST['_wpnonce']=$_POST['_wpnonce'];
            Product_Policy_Admin::save_shared();self::assertSame('<strong>Shared</strong>',get_option('zarincode_product_policies')['summary']);
            ob_start();Product_Policy_Admin::fields($incoming);$html=ob_get_clean();self::assertStringContainsString('zc-policy-template',$html);self::assertStringContainsString('A &quot;quoted&quot; question',$html);
            ob_start();Product_Policy_Admin::box(get_post($this->product));$box=ob_get_clean();self::assertStringContainsString('zc_product_policies_nonce',$box);self::assertStringContainsString('zc_product_changelog',$box);
            ob_start();Product_Policy_Admin::page();$page=ob_get_clean();self::assertStringContainsString('role="status"',$page);self::assertStringContainsString('zc_policy_save',$page);
            update_option('zarincode_product_policies',['_zc_policy_schema'=>99]);Product_Policy_Admin::save_shared();self::assertSame(['_zc_policy_schema'=>99],get_option('zarincode_product_policies'));
            wp_set_current_user(0);ob_start();Product_Policy_Admin::page();self::assertSame('',ob_get_clean());Product_Policy_Admin::save_shared();
        } finally {wp_set_current_user(1);unset($_REQUEST['_wpnonce']);update_option('zarincode_product_policies',$old);}
    }
    public function testAllRowsCanBeDeletedWithoutLosingUnsubmittedPartialData():void {
        $merged=Policies::merge(['summary'=>'Keep','faqs'=>[['id'=>'stable','question'=>'Q','answer'=>'A']]],['faqs'=>'']);
        self::assertSame([],$merged['faqs']);self::assertSame('Keep',$merged['summary']);
        $store=\Zarincode\Runtime260\Content\Social_Profile_Store::class;$old=get_option('zarincode_custom_socials',[]);
        try {
            update_option('zarincode_custom_socials',[['id'=>'stable','name'=>'Network']]);
            $_POST=['zc_social_save'=>'1','zc_socials'=>'','_wpnonce'=>wp_create_nonce('zc_socials')];$_REQUEST['_wpnonce']=$_POST['_wpnonce'];
            $store::save();self::assertSame([],get_option('zarincode_custom_socials'));
            foreach([null,99] as $version){$future=[['id'=>[],'_zc_profile_schema'=>$version]];update_option('zarincode_custom_socials',$future);$store::save();self::assertSame($future,get_option('zarincode_custom_socials'));}
        } finally {unset($_REQUEST['_wpnonce']);update_option('zarincode_custom_socials',$old);}
    }
    private function comment(int $parent=0,string $status='1',?int $post=null):WP_Comment {
        $id=wp_insert_comment(['comment_post_ID'=>$post??$this->post,'comment_parent'=>$parent,'comment_approved'=>$status,'comment_content'=>'QA','comment_type'=>'comment']);return get_comment($id);
    }
    public function testCommentRelationsAndReplyVotes():void {
        Discussion::boot();$parent=$this->comment();$child=$this->comment((int)$parent->comment_ID);
        self::assertTrue(Discussion::staff());self::assertNull(Discussion::parent(0,$this->post));self::assertNull(Discussion::parent((int)$parent->comment_ID,$this->product));self::assertSame(2,Discussion::depth($child));
        self::assertNull(Discussion::parent((int)$this->comment(0,'0')->comment_ID,$this->post));
        $_GET['replytocom']=$parent->comment_ID;self::assertSame($parent->comment_ID,Discussion::requested_parent($this->post)->comment_ID);$_GET['replytocom']=[];self::assertNull(Discussion::requested_parent($this->post));unset($_GET['replytocom']);
        $_POST=['rating'=>'5','zc_article_rating'=>'5','comment_parent'=>$parent->comment_ID];
        $data=['comment_post_ID'=>$this->post,'comment_parent'=>$parent->comment_ID];self::assertSame($data,Discussion::prepare($data));self::assertArrayNotHasKey('rating',$_POST);self::assertArrayNotHasKey('zc_article_rating',$_POST);
        self::assertSame([],Discussion::prepare([]));
        $this->expectException(DomainException::class);Discussion::prepare(['comment_post_ID'=>$this->post,'comment_parent'=>PHP_INT_MAX]);
    }
    public function testWooVerifiedBuyerExceptionIsLimitedToValidStaffReplies():void {
        $old=get_option('woocommerce_review_rating_verification_required','no');
        try {
            update_option('woocommerce_review_rating_verification_required','yes');
            $parent=$this->comment(0,'1',$this->product);$_POST=['comment_parent'=>$parent->comment_ID];
            Discussion::verify_buyer($this->product);self::assertTrue(true,'Approved staff reply reaches the native comment validator.');
            $_POST['comment_parent']=$this->comment()->comment_ID;
            $this->expectException(DomainException::class);Discussion::verify_buyer($this->product);
        } finally {update_option('woocommerce_review_rating_verification_required',$old);}
    }
    public function testStaffCannotBypassCrossProductParentValidation():void {
        $parent=$this->comment();$_POST['comment_parent']=$parent->comment_ID;
        self::assertNull(Discussion::requested_parent($this->product,'comment_parent'));
        $this->expectException(DomainException::class);Discussion::prepare(['comment_post_ID'=>$this->product,'comment_parent'=>$parent->comment_ID]);
    }
}
