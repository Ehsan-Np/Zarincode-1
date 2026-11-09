<?php
/**
 * One Elementor widget per inner-page model.
 *
 * Why this file exists
 * --------------------
 * `zarincode_page_section` used to register the controls of ALL ten page models
 * (about, contact, services, projects, terms, warranty, privacy, refund, GDPR,
 * DMCA) inside a single widget. The Elementor editor ships the whole control
 * stack of every widget that appears on the page, so editing one legal page
 * pushed **2,457 KB / 2,805 controls** into `initial_document.widgets` — measured
 * with a real editor request; total editor HTML was 5.3 MB and the panel took
 * seconds to boot.
 *
 * Each model now has its own widget class that registers only its own model
 * (`Abstract_Zarincode_Widget::$zc_scope`), so a page carries ~250 controls
 * instead of 2,805. The old all-in-one widget stays registered (hidden from the
 * panel) so pages built by earlier versions keep rendering unchanged.
 *
 * @package Zarincode
 */

namespace Zarincode\Runtime260\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Shared behaviour of the scoped page widgets.
 */
abstract class Widget_Page_Section_Scoped extends Widget_Page_Content_Section {

	/** Human title of the page model, shown in the Elementor panel. */
	abstract protected function scope_title(): string;

	/** Panel icon (Elementor eicons name). */
	protected function scope_icon(): string {
		return 'eicon-document-file';
	}

	public function get_name(): string {
		return 'zarincode_page_' . str_replace( '-', '_', $this->zc_scope );
	}

	public function get_title(): string {
		return $this->scope_title();
	}

	public function get_icon(): string {
		return $this->scope_icon();
	}

	public function show_in_panel(): bool {
		return true;
	}

	public function get_keywords(): array {
		return array( 'zarincode', 'زرین کد', 'صفحه', 'page', $this->zc_scope );
	}
}

/** درباره ما */
class Widget_Page_About extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'about-page';
	protected function scope_title(): string { return 'زرین کد — صفحه درباره ما'; }
	protected function scope_icon(): string { return 'eicon-info-circle-o'; }
}

/** تماس با ما */
class Widget_Page_Contact extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'contact-page';
	protected function scope_title(): string { return 'زرین کد — صفحه تماس با ما'; }
	protected function scope_icon(): string { return 'eicon-comments'; }
}

/** خدمات */
class Widget_Page_Services extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'services-page';
	protected function scope_title(): string { return 'زرین کد — صفحه خدمات'; }
	protected function scope_icon(): string { return 'eicon-posts-grid'; }
}

/** نمونه‌کارها */
class Widget_Page_Projects extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'projects-page';
	protected function scope_title(): string { return 'زرین کد — صفحه نمونه‌کارها'; }
	protected function scope_icon(): string { return 'eicon-gallery-grid'; }
}

/** قوانین و مقررات */
class Widget_Page_Terms extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'terms';
	protected function scope_title(): string { return 'زرین کد — قوانین و مقررات'; }
	protected function scope_icon(): string { return 'eicon-file-text'; }
}

/** گارانتی */
class Widget_Page_Warranty extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'warranty';
	protected function scope_title(): string { return 'زرین کد — گارانتی محصولات'; }
	protected function scope_icon(): string { return 'eicon-shield'; }
}

/** حریم خصوصی */
class Widget_Page_Privacy extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'privacy';
	protected function scope_title(): string { return 'زرین کد — حریم خصوصی'; }
	protected function scope_icon(): string { return 'eicon-lock-user'; }
}

/** بازگشت وجه */
class Widget_Page_Refund extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'refund';
	protected function scope_title(): string { return 'زرین کد — بازگشت وجه'; }
	protected function scope_icon(): string { return 'eicon-undo'; }
}

/** GDPR */
class Widget_Page_Gdpr extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'gdpr';
	protected function scope_title(): string { return 'زرین کد — حفاظت داده (GDPR)'; }
	protected function scope_icon(): string { return 'eicon-globe'; }
}

/** DMCA */
class Widget_Page_Dmca extends Widget_Page_Section_Scoped {
	protected $zc_scope = 'dmca';
	protected function scope_title(): string { return 'زرین کد — سیاست DMCA'; }
	protected function scope_icon(): string { return 'eicon-copyright'; }
}
