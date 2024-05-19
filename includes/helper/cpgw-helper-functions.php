<?php
if (!defined('ABSPATH')) {
    exit();
}

trait CPGW_HELPER
{
    public function __construct()
    {
    }
    // Generate a dynamic secret key for hash_hmac
    protected function cpgw_get_secret_key()
    {
        if (get_option('cpgwp_secret_key') == false) {
            update_option('cpgwp_secret_key', wp_generate_password(4, true, true));
        }
        return get_option('cpgwp_secret_key');
    }

    //Price conversion API start

    protected function cpgw_price_conversion($total, $crypto, $type)
    {
        global $woocommerce;
        $currency = get_woocommerce_currency();
        $settings_obj = get_option('cpgw_settings');

        $api = !empty($settings_obj['crypto_compare_key']) ? $settings_obj['crypto_compare_key'] : '';

        if ($type == "cryptocompare") {
            if (empty($api)) {
                return "no_key";
            }

            $current_price = CPGW_API_DATA::cpgw_crypto_compare_api($currency, $crypto);
            $current_price_array = (array) $current_price;

            return isset($current_price_array[$crypto]) ? $this->cpgw_format_number(($current_price_array[$crypto]) * $total) : null;
        } else {
            $price_list = CPGW_API_DATA::cpgw_openexchangerates_api();

            if (isset($price_list->error) && $currency != 'USD') {
                return array('error' => $price_list->description);
            }

            $price_array =  ($currency != 'USD') ? (array) $price_list->rates : '';
            $current_rate = ($currency != 'USD') ? $price_array[$currency] : 1;

            if ($crypto == "USDT" || $crypto == "BUSD") {
                $current_price_USDT = CPGW_API_DATA::cpgw_crypto_compare_api($currency, $crypto);
                $current_price_array_USDT = (array) $current_price_USDT;

                return isset($current_price_array_USDT[$crypto]) ? $this->cpgw_format_number(($current_price_array_USDT[$crypto]) * $total) : null;
            } else {
                $binance_price = CPGW_API_DATA::cpgw_binance_price_api('' . $crypto . 'USDT');

                if (isset($binance_price->lastPrice)) {
                    $lastprice = $binance_price->lastPrice;
                    return !empty($current_rate) ? $this->cpgw_format_number(($total / $current_rate) / $lastprice) : null;
                } elseif (current_user_can('manage_options')) {
                    return isset($binance_price->msg) ? array('restricted' => __("Binance API Is Restricted In Your region, Please Switch With CryptoCompare API.", "cpgw")) : 'error';
                }
            }
        }
    }

    protected function cpgw_format_number($n)
    {
        if (is_numeric($n)) {
            if ($n >= 25) {
                return $formatted = number_format($n, 2, '.', ',');
            } else if ($n >= 0.50 && $n < 25) {
                return $formatted = number_format($n, 3, '.', ',');
            } else if ($n >= 0.01 && $n < 0.50) {
                return $formatted = number_format($n, 4, '.', ',');
            } else if ($n >= 0.001 && $n < 0.01) {
                return $formatted = number_format($n, 5, '.', ',');
            } else if ($n >= 0.0001 && $n < 0.001) {
                return $formatted = number_format($n, 6, '.', ',');
            } else {
                return $formatted = number_format($n, 8, '.', ',');
            }
        }
    }

    //Price conversion API end here

    protected function cpgw_supported_currency()
    {
        $oe_currency = array("AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTC", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLF", "CLP", "CNH", "CNY", "COP", "CRC", "CUC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "GBP", "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK", "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KMF", "KPW", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRO", "MRU", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLL", "SOS", "SRD", "SSP", "STD", "STN", "SVC", "SYP", "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XAG", "XAU", "XCD", "XDR", "XOF", "XPD", "XPF", "XPT", "YER", "ZAR", "ZMW", "ZWL");
        return $oe_currency;
    }

    //Add custom tokens for networks
    protected function cpgw_add_tokens()
    {
        $tokens = [];

        $tokens['0x38'] = array(
            'BUSD' => '0xe9e7cea3dedca5984780bafc599bd69add087d56',
        );
        $tokens['0x61'] = array(
            'BUSD' => '0xeD24FC36d5Ee211Ea25A80239Fb8C4Cfd80f12Ee',
        );
        $tokens['0x38'] = array(
            'SULFERC' => '0x9Df9De5Ed89ADBBd9fa2C14691903A0DE9048A87',
        );

        return $tokens;
    }

    //Add network names here
    protected function cpgw_get_explorer_url()
    {
        $explorer = [];
        $explorer = array(
            '0x61' => 'https://testnet.bscscan.com/',
            '0x38' => 'https://bscscan.com/',
        );

        return $explorer;
    }

    //Add network names here
    protected function cpgw_supported_networks()
    {
        $networks = [];
        $networks = array(
            '0x38' => __('Binance Smart Chain (BEP20)', 'cpgw'),
            '0x61' => __('Binance Smart Chain (Testnet)', 'cpgw'),
        );

        return $networks;
    }
    protected function cpgwp_get_active_networks_for_currency($currencySymbol)
    {
        $options = get_option('cpgw_settings');
        $activeNetworks = array();
        $decimalactiveNetworks = array();
        $contract_address = [];
        $get_network = $options["Chain_network"];
        $allnetworks = $this->cpgw_supported_networks();
        $all_tokens = $this->cpgw_add_tokens();
        $hex_network = $allnetworks[$get_network];

        return [
            'contract_address' => isset($all_tokens[$get_network][$currencySymbol]) ? array(hexdec($get_network) => $all_tokens[$get_network][$currencySymbol]) : [],
            'active_network' => array($get_network => $hex_network),
            'decimal_networks' => array(hexdec($get_network) => $hex_network),

        ];
    }

    //Add all constant messages
    protected function cpgw_const_messages()
    {
        $messages = "";

        $messages = array(
            // Checkout&validate fields static messages start here
            'metamask_address' => __('Please enter your  Payment address', 'cpgw'),
            'valid_wallet_address' => __('Please enter valid  Payment address', 'cpgw'),
            'required_fiat_key' => __('Please enter price conversion API key', 'cpgw'),
            'valid_fiat_key' => __('Please enter valid price conversion API key', 'cpgw'),
            'required_currency' => __('Please select a currency', 'cpgw'),
            'required_network_check' => __('Please select a payment network', 'cpgw'),
            'payment_network' => __('Select Payment Network', 'cpgw'),
            'switch_network_msg' => __('Please switch to the network below inside your wallet to complete this payment.', 'cpgw'),
            'connected_to' => __('Connected to', 'cpgw'),
            'disconnect' => __('Disconnect Wallet', 'cpgw'),
            'wallet' => __('Wallet', 'cpgw'),
            'network' => __('Network', 'cpgw'),
            'insufficent' => __('Insufficient balance in your wallet for this order. Try different network, coin, or wallet.', 'cpgw'),
            'payment_notice' => __('Please proceed with the payment below.', 'cpgw'),
            'notice_msg' => __('Please dont change the payment amount in your wallet, it could lead to order failure.', 'cpgw'),
            'payment_notice_msg' => __('Please wait while we check your transaction confirmation on the block explorer. Do not change the gas fee until the transaction is complete to avoid order failure.', 'cpgw'),
            'cancel_order' => __('If you want to pay with a different cryptocurrency, network, or wallet, please', 'cpgw'),
            'cancel_this_order' => __('cancel this order', 'cpgw'),
            'create_new_one' => __('and create a new one.', 'cpgw'),
            'to_complete' => __('to complete this order', 'cpgw'),
            'through' => __('through ', 'cpgw'),
            'processing' => __('Processing', 'cpgw'),
            'connected' => __('Connected', 'cpgw'),
            'not_connected' => __('Not Connected', 'cpgw'),
            'order_price' => __('Order price: ', 'cpgw'),
            'pay_with' => __('Please pay', 'cpgw'),
            'payment_status' => __('Payment Status', 'cpgw'),
            'in_process' => __('In Process...', 'cpgw'),
            'pending' => __('Pending', 'cpgw'),
            'failed' => __('Failed', 'cpgw'),
            'completed' => __('Completed', 'cpgw'),
            'check_in_explorer' => __('Check in explorer', 'cpgw'),
            'rejected_msg' => __('Your payment has been rejected. Please try to make payment again.', 'cpgw'),
            'confirmed_payments_msg' => __('Thank you for making the payment. Your transaction has been confirmed by the explorer.', 'cpgw'),
            'connect_wallet' => __('Connect Wallet', 'cpgw'),
            'select_network' => __('Payment Network', 'cpgw'),
            'select_currency' => __('Select a Currency', 'cpgw'),
            'select_cryptocurrency' => __('Select Cryptocurrency..', 'cpgw'),

        );
        return $messages;
    }

    //Add network names here
    protected function cpgw_get_coin_logo($value)
    {
        $coin_svg = CPGW_PATH . 'assets/images/' . $value . '.svg';
        $coin_png = CPGW_PATH . 'assets/images/' . $value . '.png';
        $coin_svg_img = CPGW_URL . 'assets/images/' . $value . '.svg';
        $coin_png_img = CPGW_URL . 'assets/images/' . $value . '.png';
        $image_url = "";

        if (file_exists($coin_svg)) {

            $image_url = $coin_svg_img;
        } else if (file_exists($coin_png)) {
            $image_url = $coin_png_img;
        } else {
            $image_url = CPGW_URL . 'assets/images/default-logo.png';
        }
        return $image_url;
    }

    protected function add_error_custom_notice($error_message, $link = true)
    {
        // Get Metamask settings link
        $cpgw_settings = admin_url() . 'admin.php?page=cpgw-metamask-settings';
        $link_html = (current_user_can('manage_options')) ?
            '.<a href="' . esc_url($cpgw_settings) . '" target="_blank">' .
            __("Click here", "cpgw") . '</a>' . __('to open settings', 'cpgw') : "";
        if (!empty($error_message) && $link) {
            wc_add_notice('<strong>' . esc_html($error_message) . wp_kses_post($link_html) . '</strong>', 'error');
            return false;
        } else {
            wc_add_notice('<strong>' . esc_html($error_message) . '</strong>', 'error');
            return false;
        }
    }
    public function cpgwsaveErrorLogs($log_entry)
    {
        $settings = get_option('cpgw_settings');
        if (!isset($settings['enable_debug_log']) || $settings['enable_debug_log'] == "1") {
            $logger = wc_get_logger();
            $logger->error($log_entry, array('source' => 'pay_with_metamask'));
        }
    }
}
