<?php
namespace Zarincode\Runtime260\Elementor;
defined( 'ABSPATH' ) || exit;
/** Read-only, additive public metadata. Existing Elementor controls and saved settings are unchanged. */
trait Widget_Contract {
    public function get_zc_contract(): array {
        return Contracts::all()[$this->get_name()] ?? [];
    }
}
