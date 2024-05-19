<?php

if (!defined('ABSPATH')) {
    exit;
}
function cpgw_payment_verify()
{
    global $woocommerce;
    $order_id = sanitize_text_field($_REQUEST['order_id']);
    $nonce = !empty($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : "";
    $trasn_id = !empty($_REQUEST['payment_processed']) ? sanitize_text_field($_REQUEST['payment_processed']) : "";
    $payment_status_d = !empty($_REQUEST['payment_status']) ? sanitize_text_field($_REQUEST['payment_status']) : "";
    $order_expired = !empty($_REQUEST['rejected_transaction']) ? sanitize_text_field($_REQUEST['rejected_transaction']) : "";
    $selected_network = !empty($_REQUEST['selected_network']) ? sanitize_text_field($_REQUEST['selected_network']) : "";
    $sender = !empty($_REQUEST['sender']) ? sanitize_text_field($_REQUEST['sender']) : "";
    $networks = array('0x38' => 'Binance Smart Chain (Mainnet)', '0x61' => 'Binance Smart Chain (Testnet)');
    $order = new WC_Order($order_id);
    if ($order->is_paid() || !wp_verify_nonce($nonce, 'cpgw_metamask_pay')) {
        die("*ok*");
    }
    $transaction = [];
    $current_user = wp_get_current_user();
    $user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
    $transaction['order_id'] = $order_id;
    $transaction['chain_id'] = $selected_network;
    $transaction['order_price'] = get_woocommerce_currency_symbol() . $order->get_total();
    $transaction['user_name'] = $user_name;
    $transaction['crypto_price'] = $order->get_meta('cpgw_in_crypto') . ' ' . $order->get_meta('cpgw_currency_symbol');
    $transaction['selected_currency'] = $order->get_meta('cpgw_currency_symbol');
    $transaction['chain_name'] = $networks[$selected_network];
    try {
        if ($order_expired == "true" && $trasn_id == "false") {
            // $order->add_meta_data('Payment_status', "CANCEL");
            $order->add_order_note(__('Order has been canceled due to user rejection', 'cbpw'));
            //  $order->add_meta_data('TransectionId', $trasn_id);
            $order->update_status('wc-cancelled', __('Order has been canceled due to user rejection', 'cbpw'));
        }
        if ($trasn_id != "false") {
            $link_hash = "";
            if ($selected_network == '0x61') {
                $link_hash = '<a href="https://testnet.bscscan.com/tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';
            } elseif ($selected_network == '0x38') {
                $link_hash = '<a href="https://bscscan.com/tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';
            }

            if ($payment_status_d == "default") {
                $order->add_meta_data('TransactionId', $trasn_id);
                $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpgw') . $link_hash;
                $order->add_order_note($transection);
                $order->payment_complete($trasn_id);
                // send email to costumer
                WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                // send email to admin
                WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
            } else {
                $order->add_meta_data('TransactionId', $trasn_id);
                $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpgw') . $link_hash;
                $order->add_order_note($transection);
                $order->update_status(apply_filters('cpgw_capture_payment_order_status', $payment_status_d));
                // send email to costumer
                WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                // send email to admin
                WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
            }
        }
        $db = new CPGW_database();
        $transaction['status'] = $order->get_status();
        $transaction['sender'] = $sender;
        $transaction['transaction_id'] = !empty($trasn_id) ? $trasn_id : "false";
        $order->save_meta_data();
        $data = [
            'is_paid' => ($order->get_status() == "on-hold" && !empty($trasn_id)) ? true : $order->is_paid(),
            'order_status' => $order->get_status(),
        ];
        echo json_encode($data);
        $db->cpgw_insert_data($transaction);
        die();
    } catch (Exception $e) {
    }

    echo json_encode(['status' => 'error', 'error' => 'not a valid order_id']);
    die();
}


function cpgw_format_number($n)
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

//Price conversion API start

function cpgw_price_conversion($total, $crypto, $type)
{
    global $woocommerce;
    $lastprice = "";
    $currency = get_woocommerce_currency();
    $settings_obj = get_option('woocommerce_cpgw_settings');

    if ($type == "cryptocompare") {
        $api = !empty($settings_obj['crypto_compare_key']) ? $settings_obj['crypto_compare_key'] : "";
        if (empty($api)) {
            return "no_key";
        }
        $current_price = cpgw_crypto_compare_api($currency, $crypto);
        $current_price_array = (array)$current_price;


        if (isset($current_price_array['Response'])) {
            return;
        }

        $in_crypto = !empty(($current_price_array[$crypto]) * $total) ? ($current_price_array[$crypto]) * $total : "";
        return cpgw_format_number($in_crypto);
    } else {
        $price_list = cpgw_openexchangerates_api();
        if (isset($price_list->error)) {
            return 'error';
        }

        $price_arryay = (array)$price_list->rates;
        $current_rate = $price_arryay[$currency];
        if ($crypto == "USDT") {
            $current_price_USDT = cpgw_crypto_compare_api($currency, $crypto);
            $current_price_array_USDT = (array)$current_price_USDT;
            if (isset($current_price_array_USDT['Response'])) {
                return;
            }
            $in_crypto_USDT = !empty(($current_price_array_USDT[$crypto]) * $total) ? ($current_price_array_USDT[$crypto]) * $total : "";
            return $in_crypto_USDT;
        } else {
            $binance_price = cpgw_binance_price_api('' . $crypto . 'USDT');
            $lastprice = $binance_price->lastPrice;
            $cal = (!empty($price_arryay) && !empty($current_rate)) ? ($total / $current_rate) / $lastprice : "";
            return  cpgw_format_number($cal);
        }
    }
}

function cpgw_crypto_compare_api($fiat, $crypto_token)
{
    $settings_obj = get_option('woocommerce_cpgw_settings');
    $api = !empty($settings_obj['crypto_compare_key']) ? $settings_obj['crypto_compare_key'] : "";
    $transient = get_transient("cpgw_currency" . $crypto_token);
    if (empty($transient) || $transient === "") {
        $response = wp_remote_post('https://min-api.cryptocompare.com/data/price?fsym=' . $fiat . '&tsyms=' . $crypto_token . '&api_key=' . $api . '', array('timeout' => 120, 'sslverify' => true));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
        }
        $body = wp_remote_retrieve_body($response);
        $data_body = json_decode($body);
        set_transient("cpgw_currency" . $crypto_token, $data_body, 10 * MINUTE_IN_SECONDS);
        return $data_body;
    } else {
        return $transient;
    }
}

function cpgw_openexchangerates_api()
{
    $settings_obj = get_option('woocommerce_cpgw_settings');
    $api = !empty($settings_obj['openexchangerates_key']) ? $settings_obj['openexchangerates_key'] : "";
    if (empty($api)) {
        return;
    }
    $transient = get_transient("cpgw_openexchangerates");
    if (empty($transient) || $transient === "") {
        $response = wp_remote_post('https://openexchangerates.org/api/latest.json?app_id=' . $api . '', array('timeout' => 120, 'sslverify' => true));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
        }
        $body = wp_remote_retrieve_body($response);
        $data_body = json_decode($body);
        if (isset($data_body->error)) {
            return (object) array('error' => true, 'message' => $data_body->message, 'description' => $data_body->description);
        }
        set_transient("cpgw_openexchangerates", $data_body, 120 * MINUTE_IN_SECONDS);
        return $data_body;
    } else {
        return $transient;
    }
}
function cpgw_binance_price_api($symbol)
{
    $trans_name = "cpgw_binance_price" . $symbol;
    $transient = get_transient($trans_name);
    if (empty($transient) || $transient === "") {
        $response = wp_remote_get('https://api.binance.com/api/v3/ticker/24hr?symbol=' . $symbol . '', array('timeout' => 120, 'sslverify' => true));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
        }
        $body = wp_remote_retrieve_body($response);
        $data_body = json_decode($body);
        set_transient($trans_name, $data_body, 10 * MINUTE_IN_SECONDS);
        return $data_body;
    } else {
        return $transient;
    }
}

//Price conversion API end here

function cpgw_supported_currency()
{
    $oe_currency = array("AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTC", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLF", "CLP", "CNH", "CNY", "COP", "CRC", "CUC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "GBP", "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK", "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KMF", "KPW", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRO", "MRU", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLL", "SOS", "SRD", "SSP", "STD", "STN", "SVC", "SYP", "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XAG", "XAU", "XCD", "XDR", "XOF", "XPD", "XPF", "XPT", "YER", "ZAR", "ZMW", "ZWL",);
    return $oe_currency;
}

//Transaction table callback

function cpgw_transaction_table()
{
    $lits_table = new Cpgw_metamask_list();
    echo '<div class="wrap"><h2>' . __("MetaMask Transactions", "cpgw") . '</h2>';

    $lits_table->prepare_items();
?>
    <form method="post" class="alignleft">&nbsp;
        <input type="hidden" name="cpgw_processing" value="processing" />
        <input type="submit" class="button secondary" value="<?php _e('Processing (' . cpgw_count_orders_status('processing') . ')', 'cpgw'); ?>" />
    </form>
    <form method="post" class="alignleft">&nbsp;
        <input type="hidden" name="cpgw_canceled" value="cancelled" />
        <input type="submit" class="button secondary" value="<?php _e('Cancelled (' . cpgw_count_orders_status('cancelled') . ')', 'cpgw'); ?>" />
    </form>
    <form method="post" class="alignleft">&nbsp;
        <input type="hidden" name="cpgw_completed" value="completed" />
        <input type="submit" class="button secondary" value="<?php _e('Completed (' . cpgw_count_orders_status('completed') . ')', 'cpgw'); ?>" />
    </form>
    <form method="post" class="alignleft">&nbsp;
        <input type="hidden" name="cpgw_on_hold" value="on-hold" />
        <input type="submit" class="button secondary" value="<?php _e('On Hold (' . cpgw_count_orders_status('on-hold') . ')', 'cpgw'); ?>" />
    </form>
    <form method="post">
        <input type="hidden" name="page" value="my_list_test" />
        <?php
        $lits_table->search_box('search', 'search_id');
        ?>
    </form>
<?php
    $lits_table->display();

    echo '</div>';
}


function cpgw_count_orders_status($status)
{
    global $wpdb, $_wp_column_headers;

    $query = 'SELECT * FROM ' . $wpdb->base_prefix . 'cpgw_transaction';
    $query .= ' where ( status LIKE "%' . $status . '%" ) ';
    $items = $wpdb->get_results($query);
    return count($items);
}
