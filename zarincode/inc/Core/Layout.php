<?php
/** Shared container sizing; never mutate or repeatedly multiply the saved base width. */
namespace Zarincode\Theme\Core;
defined('ABSPATH') || exit;
final class Layout {
    public static function width($base = '1360px', $scale = 108): string {
        $base = is_scalar($base) && preg_match('/^([0-9]{3,4})(?:px)?$/D', (string)$base, $match) ? (int)$match[1] : 1360;
        $base = max(960, min(1920, $base));
        return (string)round($base * (is_scalar($scale) && (string)$scale === '100' ? 1 : 1.08)) . 'px';
    }
}
