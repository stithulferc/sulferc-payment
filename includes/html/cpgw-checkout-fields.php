<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin options
$options = get_option('cpgw_settings');

// Enqueue necessary styles
wp_enqueue_style('cpgw_checkout', CPGW_URL . 'assets/css/checkout.css', array(), CPGW_VERSION);
// Trigger WooCommerce action to start the form
do_action('woocommerce_cpgw_form_start', $this->id);

// Get Metamask settings link
$cpgw_settings = admin_url() . 'admin.php?page=cpgw-metamask-settings';

// Get user wallet settings
$user_wallet = $options['user_wallet'];

// Get currency options
$bnb_currency = $options['bnb_select_currency'];

// Get currency conversion API options
$compare_key = $options['crypto_compare_key'];
$openex_key = $options['openexchangerates_key'];
$select_currecny = $options['currency_conversion_api'];
$const_msg = $this->cpgw_const_messages();
// Generate settings link HTML for admin
$link_html = (current_user_can('manage_options')) ?
    '<a href="' . esc_url($cpgw_settings) . '" target="_blank">' .
    __("Click here", "cpgw") . '</a>' . __('to open settings', 'cpgw') : "";

// Check for various conditions
if (empty($user_wallet)) {
    echo '<strong>' . esc_html($const_msg['metamask_address']) . wp_kses_post($link_html) . '</strong>';
    return false;
}
if (!empty($user_wallet) && strlen($user_wallet) != "42") {
    echo '<strong>' . esc_html($const_msg['valid_wallet_address']) . wp_kses_post($link_html) . '</strong>';
    return false;
}
if ($select_currecny == "cryptocompare" && empty($compare_key)) {
    echo '<strong>' . esc_html($const_msg['required_fiat_key']) . wp_kses_post($link_html) . '</strong>';
    return false;
}

// Use glob to get an array of file names in the folder
$filePaths = glob(CPGW_PATH . '/assets/pay-with-metamask/build/checkout' . '/*.php');
$fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
$jsbuildUrl = str_replace('.asset', '', $fileName);

// Get supported network names
$network_name = $this->cpgw_supported_networks();

// Get selected network
$get_network = $options["Chain_network"];

// Get constant messages



// Determine crypto currency based on network
$crypto_currency = ($get_network == '0x1' || $get_network == '0x5' || $get_network == '0xaa36a7') ?
    $options["bnb_select_currency"] :
    $select_currency_lbl = (isset($options['select_a_currency']) && !empty($options['select_a_currency'])) ? $options['select_a_currency'] : __('Please Select a Currency', 'cpgwp');
// Get type and total price
$type = $options['currency_conversion_api'];
$total_price = $this->get_order_total();
$enabledCurrency = array();
$error = '';
if (is_array($crypto_currency)) {
    foreach ($crypto_currency as $key => $value) {
        // Get coin logo image URL
        $image_url = $this->cpgw_get_coin_logo($value);
        // Perform price conversion
        $in_crypto = $this->cpgw_price_conversion($total_price, $value, $type);

        if (isset($in_crypto['restricted'])) {
            $error = $in_crypto['restricted'];
            break; // Exit the loop if the API is restricted.
        }
        if (isset($in_crypto['error'])) {
            $error = $in_crypto['error'];
            break; // Exit the loop if the API is restricted.
        }
        $enabledCurrency[$value] = array('symbol' => $value, 'price' => $in_crypto, 'url' => $image_url);
    }
}
// Enqueue the connect wallet script
wp_enqueue_script('cpgw_connect_wallet', CPGW_URL . 'assets/pay-with-metamask/build/checkout/' . $jsbuildUrl . '.js', array('wp-element'), CPGW_VERSION, true);

// Localize the connect wallet script with required data
wp_localize_script('cpgw_connect_wallet', "connect_wallts", array(
    'total_price' => $total_price,
    'api_type' => $type,
    'decimalchainId' => isset($get_network) ? hexdec($get_network) : false,
    'active_network' => isset($get_network) ? $get_network : false,
    'nonce' => wp_create_nonce('wp_rest'),
    'restUrl' => get_rest_url() . 'pay-with-metamask/v1/',
    'currency_lbl' => $select_currency_lbl,
    'const_msg' => $const_msg,
    'networkName' => $network_name[$get_network],
    'enabledCurrency' => $enabledCurrency,
));
// Output supported wallets if available
if ($error) {
    echo esc_html($error);
} else {
    if ($this->description) {
        echo '<div class="cpgwp_gateway_desc">' . esc_html($this->description) . '</div>';
    }
    echo '<div class="cpgwp-supported-wallets-wrap">';
    echo '<div class="cpgwp-supported-wallets" id="cpgwp-connect-wallets">';
    echo '<div class="cegc-ph-item">';
    echo '<div class="cegc-ph-col-12">';
    echo '<div class="ph-row">';
    echo '<div class="cegc-ph-col-6 big"></div>';
    echo '<div class="cegc-ph-col-4  big"></div>';
    echo '<div class="cegc-ph-col-2 big"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Trigger WooCommerce action to end the form
do_action('woocommerce_cpgw_form_end', $this->id);
