<?php
/** Zarincode account shell. Native account hooks retained. @version 3.5.0 */
defined('ABSPATH') || exit;
\Zarincode\Theme\Core\Account::heading();
?>
<div class="zc-account-layout">
 <?php do_action('woocommerce_account_navigation'); ?>
 <div class="woocommerce-MyAccount-content zc-account-content"><?php do_action('woocommerce_account_content'); ?></div>
</div>
