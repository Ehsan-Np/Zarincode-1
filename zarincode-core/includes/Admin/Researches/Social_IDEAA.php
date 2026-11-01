<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/** IDEAA open-source icon language (social bar source). */
final class Social_IDEAA {

	public const SITE = 'https://ideaa.org/';

	public static function service(): array {
		return array(
			'name'    => 'IDEAA',
			'group'   => 'منابع آیکون',
			'site'    => self::SITE,
			'callout' => 'آیکون‌های متن‌باز (Icon Language)؛ مرجع ثانویهٔ بار شبکه‌های اجتماعی.',
		);
	}
}
