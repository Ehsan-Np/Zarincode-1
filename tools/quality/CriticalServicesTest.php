<?php
use PHPUnit\Framework\TestCase;
use Zarincode\Runtime260\Content\Article_Rating_Repository as Ratings;
use Zarincode\Runtime260\Content\Article_Rating_Service as Submission;
use Zarincode\Runtime260\Support\Settings_Schema as Schema;
use Zarincode\Runtime260\Features\Manager;

/** Actual WordPress and MariaDB integration tests; no mock database. */
final class CriticalServicesTest extends TestCase {
    private int $post;
    private array $originalPost;
    protected function setUp(): void {
        $this->originalPost = $_POST;
        $_SERVER['REMOTE_ADDR']='127.0.0.1';
        wp_set_current_user(1);
        $_POST = [];
        $this->post = wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Rating QA']);
        self::assertGreaterThan(0, $this->post);
        add_filter('wp_die_handler', [$this,'dieHandler']);
    }
    protected function tearDown(): void {
        $_POST = [];
        wp_delete_post($this->post, true);
        remove_filter('wp_die_handler', [$this,'dieHandler']);
        $_POST = $this->originalPost;
    }
    public function dieHandler(): callable {
        return static function($message) { throw new DomainException(wp_strip_all_tags($message)); };
    }
    private function comment(string $status='1', string $type='comment', ?int $post=null): int {
        return (int)wp_insert_comment(['comment_post_ID'=>$post??$this->post,'comment_content'=>'QA rating','comment_author'=>'QA','comment_approved'=>$status,'comment_type'=>$type]);
    }
    private function submit($rating, $nonce=null): void {
        $_POST=['zc_article_rating'=>$rating,'zc_rating_nonce'=>$nonce??wp_create_nonce('zc_article_rating_'.$this->post)];
    }
    public function testCanonicalVotesAndModeration(): void {
        $a=$this->comment(); $b=$this->comment(); $c=$this->comment('0'); $spam=$this->comment('spam'); $ping=$this->comment('1','pingback');
        add_comment_meta($a,'zc_article_rating',5); add_comment_meta($a,'zc_article_rating',1);
        add_comment_meta($b,'zc_article_rating','invalid'); add_comment_meta($b,'zc_article_rating',3);
        add_comment_meta($c,'zc_article_rating',1); add_comment_meta($spam,'zc_article_rating',1); add_comment_meta($ping,'zc_article_rating',1);
        self::assertSame(['count'=>2,'average'=>4.0], Ratings::aggregate($this->post));
        wp_set_comment_status($c,'approve');
        self::assertSame(['count'=>3,'average'=>3.0], Ratings::aggregate($this->post));
        wp_delete_comment($a,true);
        self::assertSame(['count'=>2,'average'=>2.0], Ratings::aggregate($this->post));
        update_comment_meta($b,'zc_article_rating',5);
        self::assertSame(['count'=>2,'average'=>3.0], Ratings::aggregate($this->post));
        self::assertSame(['count'=>0,'average'=>0.0], Ratings::aggregate(0));
        self::assertSame(['count'=>0,'average'=>0.0], Ratings::aggregate($this->post+100000));
    }
    public function testValidNonceAndUniqueCapture(): void {
        $this->submit('5');
        $data=['comment_post_ID'=>$this->post,'unknown_future'=>['keep'=>true]];
        self::assertSame($data, Submission::validate($data));
        $comment=(int)wp_new_comment(['comment_post_ID'=>$this->post,'comment_content'=>'Valid rating '.uniqid(),'comment_author'=>'QA','comment_author_email'=>'qa@example.test','comment_author_url'=>'','user_id'=>1,'comment_type'=>'comment','comment_parent'=>0,'comment_approved'=>'1']);
        self::assertSame('5', get_comment_meta($comment,'zc_article_rating',true));
        $this->submit('1'); Submission::capture($comment);
        self::assertSame(['5'],get_comment_meta($comment,'zc_article_rating',false));
        Submission::capture(0); Submission::capture(PHP_INT_MAX);
        $_POST=[];
        self::assertSame($data,Submission::validate($data));
        $unrated=$this->comment(); Submission::capture($unrated); self::assertSame('',get_comment_meta($unrated,'zc_article_rating',true));
        self::assertSame([],Submission::validate([]));
    }
    /** @dataProvider invalidRequests */
    public function testInvalidRequestsNeverPersist($rating,$nonce): void {
        $this->submit($rating,$nonce);
        if ($nonce === 'MISSING') unset($_POST['zc_rating_nonce']);
        $comment=$this->comment(); Submission::capture($comment);
        self::assertSame('',get_comment_meta($comment,'zc_article_rating',true));
        $this->expectException(DomainException::class);
        Submission::validate(['comment_post_ID'=>$this->post]);
    }
    public static function invalidRequests(): array {
        return [['0',null],['6',null],['1.5',null],['-1',null],['1e0',null],[['5'],null],['5',['nonce']],['5','forged'],['5','MISSING'],['',null]];
    }
    public function testNonceBoundToPostAndNonArticleIgnored(): void {
        $this->submit('5',wp_create_nonce('zc_article_rating_'.($this->post+1)));
        $c=$this->comment(); Submission::capture($c); self::assertSame('',get_comment_meta($c,'zc_article_rating',true));
        $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA page']);
        try {
            self::assertSame(['comment_post_ID'=>$page],Submission::validate(['comment_post_ID'=>$page]));
            $c=$this->comment('1','comment',$page); Submission::capture($c); self::assertSame('',get_comment_meta($c,'zc_article_rating',true));
        } finally { wp_delete_post($page,true); }
    }
    public function testForgedNonceStopsRealWordPressSubmissionBeforeInsert(): void {
        $this->submit('5','forged');
        $before=get_comments(['post_id'=>$this->post,'count'=>true,'status'=>'all']);
        $this->expectException(DomainException::class);
        try {
            wp_new_comment(['comment_post_ID'=>$this->post,'comment_content'=>'Forged '.uniqid(),'comment_author'=>'QA','comment_author_email'=>'qa@example.test','comment_author_url'=>'','user_id'=>1,'comment_type'=>'comment','comment_parent'=>0]);
        } finally {
            self::assertSame($before,get_comments(['post_id'=>$this->post,'count'=>true,'status'=>'all']));
        }
    }
    public function testSchemaPreservesUnknownAndRejectsFuture(): void {
        $settings=['_id'=>'unchanged','future'=>['nested'=>1],'items'=>[['_id'=>'keep']]];
        $result=Schema::upgrade($settings);
        self::assertSame($settings, array_diff_key($result,['_zc_settings_schema'=>true]));
        self::assertSame($result,Schema::upgrade($result));
        $this->expectException(InvalidArgumentException::class);
        Schema::upgrade(['_zc_settings_schema'=>Schema::VERSION+1]);
    }
    /** @dataProvider invalidVersions */
    public function testInvalidSchemaVersions($version): void {
        $this->expectException(InvalidArgumentException::class);
        Schema::upgrade(['_zc_settings_schema'=>$version]);
    }
    public static function invalidVersions(): array {return [[-1],['1'],[[]],[null]];}
    public function testLifecycleDoesNotNeedElementorOrTheme(): void {
        Submission::boot();
        self::assertFalse(defined('ELEMENTOR_VERSION'));
        self::assertNotSame('zarincode',get_template());
        self::assertTrue(Manager::enabled('article_ratings'));
        self::assertSame(10,has_filter('preprocess_comment',[Submission::class,'validate']));
        $this->submit('3'); $id=(int)wp_new_comment(['comment_post_ID'=>$this->post,'comment_content'=>'Theme independent '.uniqid(),'comment_author'=>'QA','comment_author_email'=>'qa@example.test','comment_author_url'=>'','user_id'=>1,'comment_type'=>'comment','comment_parent'=>0,'comment_approved'=>'1']);
        self::assertSame('3',get_comment_meta($id,'zc_article_rating',true));
        $old=get_option('zarincode_opt',[]);
        try {update_option('zarincode_opt',['blog_article_ratings'=>false]); self::assertFalse(Manager::enabled('article_ratings'));}
        finally {update_option('zarincode_opt',$old);}
    }
}
