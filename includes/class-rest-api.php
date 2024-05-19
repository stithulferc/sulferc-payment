<?PHP

class CpgwRestApi
{
    use CPGW_HELPER;
    public static $instanceApi;
    const Rest_End_Point = 'pay-with-metamask/v1';
    public static function getInstance()
    {
        if (!isset(self::$instanceApi)) {
            self::$instanceApi = new self();
        }
        return self::$instanceApi;
    }

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'registerRestApi'));
    }
    //Register all required rest roots
    public function registerRestApi()
    {
        $routes = [
            'verify-transaction' => 'verify_transaction_handler',
            'save-transaction' => 'save_transaction_handler',
            'selected-network' => 'get_selected_network',
            'cancel-order' => 'set_order_failed',
            'update-price' => 'update_price',
        ];

        foreach ($routes as $route => $callback) {
            register_rest_route(self::Rest_End_Point, $route, [
                'methods' => 'POST',
                'callback' => [$this, $callback],
                'permission_callback' => '__return_true',
            ]);
        }
    }
    // Get network on selected coin base
    public function update_price($request)
    {
        $data = $request->get_json_params();
        // Verify the nonce
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_send_json_error('Nonce verification failed');
        }
        $options = get_option('cpgw_settings');
        $type = $options['currency_conversion_api'];
        // Get selected network
        $get_network = $options["Chain_network"];
        $crypto_currency = ($get_network == '0x1' || $get_network == '0x5' || $get_network == '0xaa36a7') ?
            $options["eth_select_currency"] : $options["bnb_select_currency"];
        $total_price = !empty($data['total_amount']) ? sanitize_text_field($data['total_amount']) : '';
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
        return new WP_REST_Response($enabledCurrency);
    }

    // Get network on selected coin base
    public function get_selected_network($request)
    {
        $data = $request->get_json_params();
        // Verify the nonce
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_send_json_error('Nonce verification failed');
        }
        $symbol = !empty($data['symbol']) ? sanitize_text_field($data['symbol']) : '';

        $network_array = $this->cpgwp_get_active_networks_for_currency($symbol);
        return new WP_REST_Response($network_array);
    }
    // Canel or fail Order
    public static function set_order_failed($request)
    {
        $data = $request->get_json_params();
        // Verify the nonce
        $order_id = (int) sanitize_text_field($data['order_id']);
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_send_json_error('Nonce verification failed');
        }
        $canceled = sanitize_text_field($data['canceled']);
        $message = __('Payment has been failed due to user rejection', 'cpgw');

        $order = new WC_Order($order_id);
        $order->update_status('wc-failed', $message);
        $checkout_page = wc_get_checkout_url();

        $order->save_meta_data();
        return new WP_REST_Response(array('error' => $message, 'url' => $canceled ? $checkout_page : ''), 400);
    }

    // On successfull payment handle order status & save transaction in database
    public function verify_transaction_handler($request)
    {
        global $woocommerce;
        $data = $request->get_json_params();
        // Verify the nonce
        $order_id = (int) sanitize_text_field($data['order_id']);
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            $error_message = __('Nonce verification failed.', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        $order = new WC_Order($order_id);
        if ($order->is_paid()) {
            $error_message = __('This order has already paid', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        $options_settings = get_option('cpgw_settings');
        $block_explorer = $this->cpgw_get_explorer_url();
        $trasn_id = !empty($data['payment_processed']) ? sanitize_text_field($data['payment_processed']) : '';
        $payment_status_d = !empty($data['payment_status']) ? sanitize_text_field($data['payment_status']) : '';
        $selected_network = !empty($data['selected_network']) ? sanitize_text_field($data['selected_network']) : '';
        $sender = !empty($data['sender']) ? sanitize_text_field($data['sender']) : '';
        $receiver = !empty($data['receiver']) ? sanitize_text_field($data['receiver']) : '';
        $token_address = sanitize_text_field($data['token_address']);
        $amount = !empty($data['amount']) ? $data['amount'] : '';
        $amount = $this->cpgw_format_number($amount);
        $secret_code = !empty($data['secret_code']) ? $data['secret_code'] : '';

        $networks = $this->cpgw_supported_networks();

        $user_address = $order->get_meta('cpgwp_user_wallet');
        $total = $order->get_meta('cpgwp_in_crypto');
        $total = str_replace(',', '', $total);
        $transaction_local_id = $order->get_meta('transaction_id');
        $dbnetwork = $order->get_meta('cpgwp_network');
        $secret_key = $this->cpgw_get_secret_key();
        $signature = !empty($data['signature']) ? $data['signature'] : '';
        $receve_tx_req_data = json_encode(
            array(
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($receiver),
                'amount' => str_replace(',', '', $amount),
                'token_address' => strtoupper($token_address),
                'tx_id' => $trasn_id,
            )
        );

        $get_sign = hash_hmac('sha256', $receve_tx_req_data, $secret_key);
        // Verify signature
        if ($get_sign !== $signature) {
            $original_data = json_encode(
                array(
                    'order_id' => $order_id,
                    'selected_network' => $selected_network,
                    'receiver' => strtoupper($order->get_meta('cpgwp_user_wallet')),
                    'amount' => $total,
                    'token_address' => strtoupper($order->get_meta('cpgwp_contract_address')),
                )
            );
            $order->update_status('wc-failed', __('Order has been canceled due to Order Information mismatch', 'cpgw'));
            $error_message = __('Signature verification failed', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]:-' . $receve_tx_req_data . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        if ($transaction_local_id != $trasn_id) {
            $order->update_status('wc-failed', __('Order has been canceled due to Order Information mismatch', 'cpgw'));
            $error_message = __('Transaction mismatch.', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }
        $amount = str_replace(',', '', $amount);
        if ($amount != $total) {
            $order->update_status('wc-failed', __('Order has been canceled due to Order Information mismatch', 'cpgw'));

            $error_message = __('Order Information mismatch', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        $transaction = array();
        $current_user = wp_get_current_user();
        $user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
        $transaction['order_id'] = $order_id;
        $transaction['chain_id'] = $selected_network;
        $transaction['order_price'] = get_woocommerce_currency_symbol() . $order->get_total();
        $transaction['user_name'] = $user_name;
        $transaction['crypto_price'] = $order->get_meta('cpgwp_in_crypto') . ' ' . $order->get_meta('cpgwp_currency_symbol');
        $transaction['selected_currency'] = $order->get_meta('cpgwp_currency_symbol');
        $transaction['chain_name'] = $networks[$selected_network];

        try {
            if ($trasn_id != 'false') {
                $link_hash = '';

                $link_hash = '<a href="' . $block_explorer[$selected_network] . 'tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

                if ($payment_status_d == 'default') {
                    $order->add_meta_data('TransactionId', $trasn_id);
                    $order->add_meta_data('Sender', $sender);
                    $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpgw') . $link_hash;
                    $order->add_order_note($transection);
                    $order->payment_complete($trasn_id);
                    // send email to costumer
                    WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                    // send email to admin
                    WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
                    // WC()->cart->empty_cart();

                } else {
                    $order->add_meta_data('TransactionId', $trasn_id);
                    $order->add_meta_data('Sender', $sender);
                    $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpgw') . $link_hash;
                    $order->add_order_note($transection);
                    $order->update_status(apply_filters('cpgwp_capture_payment_order_status', $payment_status_d));
                    // send email to costumer
                    WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                    // send email to admin
                    WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
                    // WC()->cart->empty_cart();
                }
            }
            $db = new CPGW_database();
            $transaction['status'] = 'completed';
            $transaction['sender'] = $sender;
            $transaction['transaction_id'] = !empty($trasn_id) ? $trasn_id : 'false';
            $order->save_meta_data();
            $data = array(
                'is_paid' => ($order->get_status() == 'on-hold' && !empty($trasn_id)) ? true : $order->is_paid(),
                'order_status' => $order->get_status(),
                'order_received_url' => $order->get_checkout_order_received_url(),
            );
            $order->save_meta_data();
            $db->cpgw_insert_data($transaction);
            return new WP_REST_Response($data);
            // return $data;

        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e), 400);
        }
        return new WP_REST_Response(array('error' => __('not a valid order_id', 'cpgw')), 400);
    }
    // validate and save transation info inside transaction table and order
    public function save_transaction_handler($request)
    {
        global $woocommerce;
        $data = $request->get_json_params();
        $order_id = (int) sanitize_text_field($data['order_id']);
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            $error_message = __('Nonce verification failed.', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        $amount = sanitize_text_field($data['amount']);
        $amount = $this->cpgw_format_number($amount);
        $receiver = sanitize_text_field($data['receiver']);
        $signature = sanitize_text_field($data['signature']);
        $sender = !empty($data['sender']) ? sanitize_text_field($data['sender']) : '';
        $token_address = sanitize_text_field($data['token_address']);
        // $verifyRequest = stripslashes($tx_req_data);
        $tx_data_arr = json_decode($verifyRequest, true); // Decode JSON to associative array
        $order = new WC_Order($order_id);
        $order->add_meta_data('transactionverification', sanitize_text_field($data['transaction_id']));
        $order->save_meta_data();
        $selected_network = $order->get_meta('cpgwp_network');
        $secret_key = $this->cpgw_get_secret_key();
        $create_tx_req_data = json_encode(
            array(
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($receiver),
                'amount' => str_replace(',', '', $amount),
                'token_address' => strtoupper($token_address),
            )
        );
        $get_sign = hash_hmac('sha256', $create_tx_req_data, $secret_key);
        $saved_amount = $order->get_meta('cpgwp_in_crypto');
        // Verify signature
        if ($get_sign !== $signature) {
            $original_data = json_encode(
                array(
                    'order_id' => $order_id,
                    'selected_network' => $selected_network,
                    'receiver' => strtoupper($order->get_meta('cpgwp_user_wallet')),
                    'amount' => $saved_amount,
                    'token_address' => strtoupper($order->get_meta('cpgwp_contract_address')),
                )
            );
            $order->update_status('wc-failed', __('Order has been canceled due to Order Information mismatch', 'cpgw'));
            $error_message = __('Signature verification failed', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]:-' . $create_tx_req_data . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        // if (is_array($tx_data_arr)) {

        $tx_db_id = $order->get_meta('transaction_id');
        if (!empty($tx_db_id)) {
            $order->update_status('wc-failed', __('Order canceled: Transaction already exists.', 'cpgw'));
            $error_message = __('Order canceled: Transaction already exists..', 'cpgw');
            $log_entry = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
            $this->cpgwsaveErrorLogs($log_entry);
            return new WP_REST_Response(array('error' => $error_message), 400);
        }

        $saved_receiver = $order->get_meta('cpgwp_user_wallet');
        $nonce = !empty($data['nonce']) ? sanitize_text_field($data['nonce']) : '';
        $trasn_id = !empty($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : '';

        $block_explorer = $this->cpgw_get_explorer_url();

        $networks = $this->cpgw_supported_networks();
        $transaction = array();
        $current_user = wp_get_current_user();
        $user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
        $order->update_meta_data('transaction_id', $trasn_id);
        $saved_token_address = $order->get_meta('cpgwp_contract_address');
        $db_currency_symbol = $order->get_meta('cpgwp_currency_symbol');
        $transaction['order_id'] = $order_id;
        $transaction['chain_id'] = $selected_network;
        $transaction['order_price'] = get_woocommerce_currency_symbol() . $order->get_total();
        $transaction['user_name'] = $user_name;
        $transaction['crypto_price'] = $order->get_meta('cpgwp_in_crypto') . ' ' . $db_currency_symbol;
        $transaction['selected_currency'] = $db_currency_symbol;
        $transaction['chain_name'] = $networks[$selected_network];
        $transaction['status'] = 'awaiting';
        $transaction['sender'] = $sender;
        $transaction['transaction_id'] = !empty($trasn_id) ? $trasn_id : 'false';
        $order->save_meta_data();
        $db = new CPGW_database();

        $pass_tx_req_data = json_encode(
            array(
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($saved_receiver),
                'amount' => str_replace(',', '', $saved_amount),
                'token_address' => strtoupper($saved_token_address),
                'tx_id' => $trasn_id,
            )
        );
        $signature = hash_hmac('sha256', $pass_tx_req_data, $secret_key);
        $db->cpgw_insert_data($transaction);
        // save transation
        $data = array(
            'nonce' => wp_create_nonce('wp_rest'),
            'signature' => $signature,
            'order_id' => $order_id,
        );
        return new WP_REST_Response($data);
        // }
        die();
    }
}

CpgwRestApi::getInstance();
