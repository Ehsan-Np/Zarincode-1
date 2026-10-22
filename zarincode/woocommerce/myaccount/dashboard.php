<?php
/** Personalized dashboard with actual customer-scoped counts. @version 4.4.0 */
defined('ABSPATH') || exit;
\Zarincode\Theme\Core\Account::dashboard();
do_action('woocommerce_account_dashboard');
// Preserve the legacy extension hooks still emitted by Woo's native dashboard.
do_action('woocommerce_before_my_account');
do_action('woocommerce_after_my_account');
