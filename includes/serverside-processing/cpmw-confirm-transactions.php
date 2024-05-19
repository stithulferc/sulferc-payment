<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CPGW_CONFIRM_TRANSACTION')) {
    class CPGW_CONFIRM_TRANSACTION
    {
        use CPGW_HELPER;

        public function __construct()
        {
        }

        public static function cpgw_payment_verify()
        {
            // Sanitize and retrieve POST data
            $order_id = isset($_POST['order_id']) ? (int) sanitize_text_field($_POST['order_id']) : 0;

            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cpgw_metamask_pay' . $order_id)) {
                wp_send_json_error('Nonce verification failed');
            }

            // Initialize objects and retrieve settings
            $obj = new self();
            $options_settings = get_option('cpgw_settings');
            $user_address = !empty($options_settings["user_wallet"]) ? $options_settings["user_wallet"] : "";

            // Retrieve sanitized POST data
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : "";
            $tx_id = isset($_POST['payment_processed']) ? sanitize_text_field($_POST['payment_processed']) : "";
            $payment_status_d = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : "";
            $order_expired = isset($_POST['rejected_transaction']) ? sanitize_text_field($_POST['rejected_transaction']) : "";
            $selected_network = isset($_POST['selected_network']) ? sanitize_text_field($_POST['selected_network']) : "";
            $sender = isset($_POST['sender']) ? sanitize_text_field($_POST['sender']) : "";
            $receiver = isset($_POST['receiver']) ? sanitize_text_field($_POST['receiver']) : "";
            $amount = isset($_POST['amount']) ? $_POST['amount'] : "";
            $amount = $obj->cpgw_format_number($amount);
            $token_address = isset($_POST['token_address']) ? sanitize_text_field($_POST['token_address']) : "";
            $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : "";
            $secret_code = isset($_POST['secret_code']) ? $_POST['secret_code'] : "";

            // Verify signature
            $secret_key = $obj->cpgw_get_secret_key();
            $get_tx_req_data = json_encode([
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($receiver),
                'amount' => str_replace(',', '', $amount),
                'token_address' => strtoupper($token_address),
                'tx_id' => $tx_id,
            ]);

            $get_sign = hash_hmac('sha256', $get_tx_req_data, $secret_key);
            if ($get_sign !== $signature) {
                wp_send_json_error('Signature verification unsuccessful: Transaction data is invalid.');
            }

            // Retrieve the order
            $order = wc_get_order($order_id);

            // Check if the order is already paid
            if ($order->is_paid()) {
                wp_send_json_error('This order has already been successfully processed.');
            }

            // Verify transaction id
            $transaction_local_id = $order->get_meta('cpgwp_confirmation_id');
            if ($transaction_local_id != $tx_id) {
                wp_send_json_error('Transaction doesn\'t match! Something wrong with your transaction.');
            }

            // Verify transaction amount
            $total = $order->get_meta('cpgw_in_crypto');
            if ($amount != $total) {
                $order->update_status('wc-cancelled', __('Order canceled because of incorrect order information.', 'cpgw'));
                wp_send_json_error('Order canceled because of incorrect order information.');
            }

            // Prepare transaction information
            $transaction = [];
            $current_user = wp_get_current_user();
            $user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
            $networks = $obj->cpgw_supported_networks();
            $transaction = [
                'order_id' => $order_id,
                'chain_id' => $selected_network,
                'order_price' => get_woocommerce_currency_symbol() . $order->get_total(),
                'user_name' => $user_name,
                'crypto_price' => $order->get_meta('cpgw_in_crypto') . ' ' . $order->get_meta('cpgw_currency_symbol'),
                'selected_currency' => $order->get_meta('cpgw_currency_symbol'),
                'chain_name' => $networks[$selected_network],
            ];

            try {
                // Process the payment and update order status
                if ($tx_id != "false") {
                    // Create a link hash based on the selected network
                    $link_hash = '';
                    if ($selected_network == '0x61') {
                        $link_hash = '<a href="https://testnet.bscscan.com/tx/' . $tx_id . '" target="_blank">' . $tx_id . '</a>';
                    } elseif ($selected_network == '0x38') {
                        $link_hash = '<a href="https://bscscan.com/tx/' . $tx_id . '" target="_blank">' . $tx_id . '</a>';
                    }

                    // Update order metadata and payment status
                    $order->add_meta_data('TransactionId', $tx_id);
                    $transaction_note = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpgw') . $link_hash;
                    $order->add_order_note($transaction_note);
                    $order->add_meta_data('Sender', $sender);

                    if ($payment_status_d == "default") {
                        $order->payment_complete($tx_id);
                        WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                        WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
                    } else {
                        $order->update_status(apply_filters('cpgw_capture_payment_order_status', $payment_status_d));
                        WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                        WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
                    }

                    // Update order status and save metadata
                    $transaction['status'] = $order->get_status();
                    $transaction['sender'] = $sender;
                    $transaction['transaction_id'] = !empty($tx_id) ? $tx_id : "false";
                    $order->save_meta_data();

                    // Send JSON response and insert transaction data into the database
                    $data = [
                        'is_paid' => ($order->get_status() == "on-hold" && !empty($tx_id)) ? true : $order->is_paid(),
                        'order_status' => $order->get_status(),
                    ];
                    echo json_encode($data);
                    $db = new CPGW_database();
                    $db->cpgw_insert_data($transaction);
                    die();
                }
            } catch (Exception $e) {
                // Handle exceptions if necessary
            }

            // If not a valid order_id, send an error response
            echo json_encode(['status' => 'error', 'error' => 'not a valid order_id']);
            die();
        }

        public static function cpgw_save_transaction()
        {
            // Sanitize and retrieve POST data
            $order_id = isset($_POST['order_id']) ? (int) sanitize_text_field($_POST['order_id']) : 0;
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : "";

            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($nonce, 'cpgw_metamask_pay' . $order_id)) {
                wp_send_json_error('Nonce verification failed');
            }

            // Initialize objects and retrieve settings
            $obj = new self();
            $options_settings = get_option('cpgw_settings');

            // Retrieve sanitized POST data
            $receiver = isset($_POST['receiver']) ? sanitize_text_field($_POST['receiver']) : '';
            $selected_network = isset($options_settings['Chain_network']) ? sanitize_text_field($options_settings['Chain_network']) : '';
            $token_address = isset($_POST['token_address']) ? sanitize_text_field($_POST['token_address']) : '';
            $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
            $amount = isset($_POST['amount']) ? $_POST['amount'] : '';
            $amount = $obj->cpgw_format_number($amount);

            // Create transaction data for signature verification
            $create_tx_req_data = json_encode([
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($receiver),
                'amount' => str_replace(',', '', $amount),
                'token_address' => strtoupper($token_address),
            ]);

            // Verify signature
            $secret_key = $obj->cpgw_get_secret_key();
            $get_sign = hash_hmac('sha256', $create_tx_req_data, $secret_key);

            if ($get_sign !== $signature) {
                wp_send_json_error('Signature verification unsuccessful: Transaction data is invalid.');
            }

            // Retrieve additional POST data
            $tx_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
            $payment_status_d = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : '';
            $sender = isset($_POST['sender']) ? sanitize_text_field($_POST['sender']) : '';

            // Initialize database connection
            $db = new CPGW_database();

            // Retrieve order and related metadata
            $order = wc_get_order($order_id);
            $tx_db_id = $order->get_meta('cpgwp_confirmation_id');
            $saved_receiver = $order->get_meta('cpgw_user_wallet');
            $saved_amount = $order->get_meta('cpgw_in_crypto');
            $saved_token_address = $order->get_meta('cpgwp_contract_address');
            $db_trans_id = $db->check_transaction_id($tx_id);

            // Check for duplicate transactions
            if (!empty($tx_db_id) || $db_trans_id == true) {
                $order->update_status('wc-cancelled', __('Order canceled: Transaction already exists.', 'cpgw'));
                wp_send_json_error('Order canceled: Transaction already exists.');
            }

            // Check for incorrect information
            if ($receiver != $saved_receiver || $amount != $saved_amount) {
                $order->update_status('wc-cancelled', __('Order canceled due to incorrect information.', 'cpgw'));
                wp_send_json_error('Order canceled due to incorrect information.');
            }

            // Update order metadata
            $order->add_meta_data('cpgwp_confirmation_id', $tx_id);
            $order->save_meta_data();

            // Prepare transaction data for response signature
            $pass_tx_req_data = json_encode([
                'order_id' => $order_id,
                'selected_network' => $selected_network,
                'receiver' => strtoupper($saved_receiver),
                'amount' => str_replace(',', '', $saved_amount),
                'token_address' => strtoupper($saved_token_address),
                'tx_id' => $tx_id,
            ]);

            // Generate response signature
            $response_signature = hash_hmac('sha256', $pass_tx_req_data, $secret_key);

            // Construct transaction information for response
            $networks = $obj->cpgw_supported_networks();
            $current_user = wp_get_current_user();
            $user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
            $transaction = [
                'order_id' => $order_id,
                'chain_id' => $selected_network,
                'order_price' => get_woocommerce_currency_symbol() . $order->get_total(),
                'user_name' => $user_name,
                'crypto_price' => $order->get_meta('cpgw_in_crypto') . ' ' . $order->get_meta('cpgw_currency_symbol'),
                'selected_currency' => $order->get_meta('cpgw_currency_symbol'),
                'chain_name' => $networks[$selected_network],
                'status' => $order->get_status(),
                'sender' => $sender,
                'transaction_id' => !empty($tx_id) ? $tx_id : 'false',
            ];

            // Insert transaction data into the database
            $rs = $db->cpgw_insert_data($transaction);

            // Send response with signature
            if ($rs) {
                echo wp_send_json_success(['signature' => $response_signature]);
            }

            // End script execution
            die();
        }

        //Cancel Order
        public static function cpgw_cancel_order()
        {
            // Verify nonce
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

            if (!wp_verify_nonce($nonce, 'cpgw_metamask_pay' . $order_id)) {
                wp_send_json_error('Nonce verification failed');
            }

            // Update order status
            $message = __('Order has been canceled due to user rejection', 'cpgw');
            $order = wc_get_order($order_id);

            if ($order && is_a($order, 'WC_Order')) {
                $order->update_status('wc-cancelled', $message);
                $order->save();
                wp_send_json_error($message);
            } else {
                wp_send_json_error('Invalid order ID');
            }
        }
    }
}
