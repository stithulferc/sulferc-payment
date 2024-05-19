<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_cpgw_Gateway extends WC_Payment_Gateway
{
    use CPGW_HELPER;

    public function __construct()
    {
        $optionss = get_option('cpgw_settings');
        // Initialize payment gateway properties
        $this->id = 'cpgw';
        $this->icon = CPGW_URL . '/assets/images/metamask.png';
        $this->has_fields = true;
        $this->method_title = __('MetaMask Pay', 'cpgw');
        $this->method_description = __('SULFERC Payment Gateway', 'cpgw');

        // Initialize form fields and settings
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = !empty($this->get_option('title')) ? $this->get_option('title') : 'MetaMask Pay';
        $this->description = $this->get_option('custom_description');
        $this->order_button_text = (isset($optionss['place_order_button']) && !empty($optionss['place_order_button'])) ? $optionss['place_order_button'] : __('Pay With Crypto Wallets', 'cpgwp');
        // Add action hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'pay_order_page'));
        //add_action('woocommerce_order_status_changed', array($this, 'check_payment_gateway_status'), 10, 3);

        // Display admin notice if currency is not supported
        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
            add_action('admin_notices', function () {
?>
                <style>
                    div#message.updated {
                        display: none;
                    }
                </style>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Current WooCommerce store currency is not supported by SULFERC Payment Gateway', 'cpgw'); ?></p>
                </div>
<?php
            });
        }

        // Set supported features
        $this->supports = array(
            'products',
            'subscriptions',
        );
    }
    public function check_payment_gateway_status($order_id, $old_status, $new_status)
    {
        $payment_gateway_id = $this->id; // Replace with your payment gateway ID


        $order = wc_get_order($order_id);
        $payment_method = $order->get_payment_method();

        // Check if the payment gateway matches and the order status changed to the required status
        if ($payment_method === $payment_gateway_id) {
            $db = new CPGW_database();
            $db->update_fields_value($order_id, 'status', $new_status);
            // Do something here, like sending an email notification
        }
    }

    public function is_valid_for_use()
    {
        if (in_array(get_woocommerce_currency(), apply_filters('cpgw_supported_currencies', $this->cpgw_supported_currency()))) {
            return true;
        }
        return false;
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable MetaMask Pay',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'cpgw'),
                'type' => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'cpgw'),
                'default' => __('Pay With Cryptocurrency', 'cpgw'),
                'desc_tip' => false,
            ),
            'custom_description' => array(
                'title' => 'Description',
                'type' => 'text',
                'description' => 'Add custom description for checkout payment page',
            ),
        );
    }

    public function payment_fields()
    {
        require_once CPGW_PATH . 'includes/html/cpgw-checkout-fields.php';
    }

    public function validate_fields()
    {
        require_once CPGW_PATH . 'includes/html/cpgw-validate-fields.php';
    }


    public function process_payment($order_id)
    {
        global $woocommerce;

        try {
            // Process the payment
            $order = wc_get_order($order_id);
            $settings_obj = get_option('cpgw_settings');
            $crypto_wallet = !empty($_POST['cpgwp_crypto_wallets']) ? sanitize_text_field($_POST['cpgwp_crypto_wallets']) : 'ethereum';
            $crypto_currency = isset($_POST['cpgwp_crypto_coin']) ? sanitize_text_field($_POST['cpgwp_crypto_coin']) : '';
            $selected_network = !empty($_POST['cpgw_payment_network']) ? sanitize_text_field($_POST['cpgw_payment_network']) : '';
            $total = $order->get_total();
            $type = $settings_obj['currency_conversion_api'];
            $in_crypto = $this->cpgw_price_conversion($total, $crypto_currency, $type);
            $network = isset($settings_obj['Chain_network']) ? $settings_obj['Chain_network'] : '';
            $add_tokens = $this->cpgw_add_tokens();
            $token_address = isset($add_tokens[$network][$crypto_currency]) ? $add_tokens[$network][$crypto_currency] : $crypto_currency;
            $user_wallet = $settings_obj['user_wallet'];
            $order->update_meta_data('cpgwp_selected_wallet', $crypto_wallet);
            $order->update_meta_data('cpgwp_in_crypto', str_replace(',', '', $in_crypto));
            $order->update_meta_data('cpgwp_currency_symbol', $crypto_currency);
            $order->update_meta_data('cpgwp_user_wallet', $user_wallet);
            $order->update_meta_data('cpgwp_network', $selected_network);
            $order->update_meta_data('cpgwp_contract_address',  $token_address);
            $order->save_meta_data();
            //  $woocommerce->cart->empty_cart();
            $url = $order->get_checkout_payment_url(true);

            return array(
                'result' => 'success',
                'redirect' => $url, //$this->get_return_url($order)
            );
        } catch (Exception $e) {
            wc_add_notice(__('Payment error:', 'cpgw') . ' Unknown coin', 'error');
            return null;
        }
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again', 'cpgw'), 'error');
        return null;
    }

    public function pay_order_page($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->is_paid()) {
            wp_redirect($order->get_checkout_order_received_url());
        } else {
            require_once CPGW_PATH . 'includes/html/cpgw-process-order.php';
        }
    }
}
