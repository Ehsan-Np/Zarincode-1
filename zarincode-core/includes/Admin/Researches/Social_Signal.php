<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/** Signal official brand/icon reference. */
final class Social_Signal {

	public const SITE = 'https://signal.org/';

	public static function service(): array {
		return array(
			'name'    => 'Signal',
			'group'   => 'شبکه‌های اجتماعی',
			'site'    => self::SITE,
			'callout' => 'استایل رسمی آیکون Signal برای بار؛ از برند رسمی پیروی می‌کند.',
		);
	}
}
