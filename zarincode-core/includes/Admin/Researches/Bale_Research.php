<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/** Bale messenger research (share link + official bot API). */
final class Bale_Research {

	public const SHARE_URL  = 'https://web.bale.ai/';
	public const BOT_API    = 'https://api.bale.ai/bot';
	public const SEND_PATH  = '/token/sendMessage';
	public const DOC        = 'https://web.bale.ai/blog/bale-bot';

	public static function service(): array {
		return array(
			'name'   => 'Bale',
			'group'  => 'پیام‌رسان ایرانی',
			'share'  => self::SHARE_URL,
			'api'    => self::BOT_API . self::SEND_PATH,
			'doc'    => self::DOC,
			'callout'=> 'اشتراک‌گذاری لینک با web.bale.ai و پشتیبانی با بات رسمی (Bearer token).',
		);
	}
}
