<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get constant messages
$const_msg = $this->cpgw_const_messages();
// Get plugin options
$options = get_option('cpgw_settings');
// Get user wallet settings
$user_wallet = $options['user_wallet'];
// Get currency conversion API options
$compare_key = $options['crypto_compare_key'];
$openex_key = $options['openexchangerates_key'];
$select_currecny = $options['currency_conversion_api'];
$symbol = isset($_POST['cpgwp_crypto_coin']) ? $_POST['cpgwp_crypto_coin'] : "";
// Check for various conditions and add WooCommerce notices
if (empty($user_wallet)) {
    return $this->add_error_custom_notice($const_msg['metamask_address']);
}
if (!empty($user_wallet) && strlen($user_wallet) != "42") {
    return $this->add_error_custom_notice($const_msg['valid_wallet_address']);
}
if ($select_currecny == "cryptocompare" && empty($compare_key)) {
    return $this->add_error_custom_notice($const_msg['required_fiat_key']);
}
if (empty($symbol)) {
    return $this->add_error_custom_notice($const_msg['required_currency'], false);
}
// Check if payment network is empty
if (empty($_POST['cpgw_payment_network'])) {
    return $this->add_error_custom_notice($const_msg['required_network_check']);
}
$total_price = $this->get_order_total();
$in_crypto = $this->cpgw_price_conversion($total_price, $symbol, $select_currecny);
// Check if current balance is less than the required amount to pay
if (isset($_POST['current_balance']) && $_POST['current_balance'] < $in_crypto) {
    $msg = __('Current Balance:', 'cpgw') . $_POST['current_balance'] . ' ' . __('Required amount to pay:', 'cpgw') . $in_crypto;
    return $this->add_error_custom_notice($msg, false);
}
// Check if current balance is not set (Wallet not connected)
if (!isset($_POST['current_balance'])) {
    return $this->add_error_custom_notice(__('Please connect Wallet first', 'cpgw'), false);
}
// Check if the selected network is supported
if ($options["Chain_network"] != $_POST['cpgw_payment_network']) {
    return $this->add_error_custom_notice(__('Network not supported in this server', 'cpgw'), false);
}
// If all checks pass, return true
return true;
