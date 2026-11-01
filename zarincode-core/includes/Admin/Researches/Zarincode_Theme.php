<?php
namespace Zarincode\Runtime260\Admin\Researches;
\defined( 'ABSPATH' ) || exit;

/** Zarincode theme infrastructure research (Crextio + Webinex + afzoneha deps). */
final class Zarincode_Theme {

	public const PARENT   = 'Crextio';
	public const MODAL    = 'Webinex';
	public const DEPS     = 'https://github.com/afzoneha';

	public static function service(): array {
		return array(
			'name'    => 'زیرساخت قالب',
			'group'   => 'قالب',
			'parent'  => self::PARENT,
			'modal'   => self::MODAL,
			'deps'    => self::DEPS,
			'callout' => 'ساختار Crextio؛ مودال مشاوره Webinex؛ وابستگی‌های afzoneha (بدون کپی از قالب‌های مادر).',
		);
	}
}
