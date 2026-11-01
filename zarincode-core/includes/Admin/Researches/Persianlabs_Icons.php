<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/** persianlabs/icons — MIT colored social icons. */
final class Persianlabs_Icons {

	public const REPO = 'https://github.com/persianlabs/icons';
	public const LICENSE = 'MIT';

	public static function service(): array {
		return array(
			'name'    => 'persianlabs/icons',
			'group'   => 'منابع آیکون',
			'repo'    => self::REPO,
			'license' => self::LICENSE,
			'callout' => 'آیکون‌های رنگی شبکه‌های اجتماعی با لایسنس MIT؛ منبع آیکون‌های بار شناور.',
		);
	}
}
