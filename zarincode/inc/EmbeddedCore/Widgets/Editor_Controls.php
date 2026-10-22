<?php
namespace Zarincode\Runtime260\Widgets;
defined('ABSPATH') || exit;

/** Exclude fields unreachable in groups restricted to classic/gradient backgrounds.
 * Do not apply this to Elementor's own Advanced background or to video-enabled groups.
 */
final class Editor_Controls {
    public const BACKGROUND_EXCLUDE = [
        'video_link','video_start','video_end','play_once','play_on_mobile','privacy_mode','video_fallback',
        'slideshow_gallery','slideshow_loop','slideshow_slide_duration','slideshow_slide_transition',
        'slideshow_transition_duration','slideshow_background_size','slideshow_background_position',
        'slideshow_lazyload','slideshow_ken_burns','slideshow_ken_burns_zoom_direction',
    ];
}
