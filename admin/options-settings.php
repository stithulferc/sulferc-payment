<?php defined('ABSPATH') || exit;

if (class_exists('CSF')) :

    $prefix = "cpgw_settings";

    CSF::createOptions($prefix, array(
        'framework_title' => esc_html__('Settings', 'cpgw'),
        'menu_title' => false,
        'menu_slug' => "cpgw-metamask-settings",
        'menu_capability' => 'manage_options',
        'menu_type' => 'submenu',
        'menu_parent' => 'woocommerce',
        'menu_position' => 103,
        'menu_hidden' => true,
        'nav' => 'inline',
        'show_bar_menu' => false,
        'show_sub_menu' => false,
        'show_reset_section' => false,
        'show_reset_all' => false,
        'theme' => 'light',

    ));

    CSF::createSection($prefix, array(

        'id' => 'general_options',
        'title' => esc_html__('General Options', 'cpgw'),
        'icon' => 'fa fa-cog',
        'fields' => array(

            array(
                'id' => 'user_wallet',
                'title' => __('Payment Address <span style="color:red">(Required)</span>', 'cpgw'),
                'type' => 'text',
                'placeholder' => '0x1dCXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'validate' => 'csf_validate_required',
                'help' => esc_html__('Enter your default wallet address to receive crypto payments.', 'cpgw'),
                'desc' => 'Enter your default wallet address to receive crypto payments.<br>
											<span >Payments will be sent to this wallet address.</span>',
            ),
            array(
                'id' => 'currency_conversion_api',
                'title' => esc_html__('Crypto Price API', 'cpgw'),
                'type' => 'select',
                'options' => array(
                    'cryptocompare' => __('CryptoCompare', 'cpgw'),
                    'openexchangerates' => __('Binance', 'cpgw'),
                ),
                'default' => 'openexchangerates',
                'desc' => 'It will convert product price from fiat currency to Stithulf ERC cryptocurrency in real time.<br>,
											<span >Make sure that Binance means API from OpenExchangeRates.</span>',
            ),
            array(
                'id' => 'crypto_compare_key',
                'title' => __('CryptoCompare API Key <span style="color:red">(Required)</span>', 'cpgw'),
                'type' => 'text',
                'dependency' => array('currency_conversion_api', '==', 'cryptocompare'),
                'desc' => 'Check -<a href="https://stithulf.com/blog/how-to-get-cryptocompare-free-api-key" target="_blank">How to retrieve CryptoCompare free API key?</a>',
            ),
            array(
                'id' => 'openexchangerates_key',
                'title' => __('Openexchangerates API Key', 'cpgw'),
                'type' => 'text',
                'dependency' => array('currency_conversion_api', '==', 'openexchangerates'),
                'desc' => 'Please provide the API key if you are utilizing a store currency other than USD. Check -<a href="https://stithulf.com/blog/how-to-retrieve-open-exchange-rates-free-api-key" target="_blank">How to retrieve openexchangerates free api key?</a>',

            ),
            array(
                'id' => 'Chain_network',
                'title' => esc_html__('Select Network/Chain', 'cpgw'),
                'type' => 'select',
                'options' => array(
                    '0x38' => __('Binance Smart Chain (BEP20)', 'cpgw'),
                    '0x61' => __('Binance Smart Chain (Testnet)', 'cpgw'),
                ),
                'default' => '0x1',

            ),
            array(
                'id' => 'bnb_select_currency',
                'title' => __('Select Crypto Currency <span style="color:red">(Required )</span>', 'cpgw'),
                'type' => 'select',
                'placeholder' => 'Select Crypto Currency',
                'validate' => 'csf_validate_required',
                'options' => array(
                    'BNB' => __('Binance Coin', 'cpgw'),
                    'BUSD' => __('BUSD', 'cpgw'),
                    'SULFERC' => __('Stithulf ERC', 'cpgw'),
                ),
                'chosen' => true,
                'multiple' => true,
                'settings' => array('width' => '50%'),
                'dependency' => array('Chain_network', 'any', '0x38,0x61'),
                'default' => 'BNB',
            ),
            array(
                'id' => 'enable_refund',
                'title' => esc_html__('Enable Refund', 'cpgw'),
                'type' => 'switcher',
                'text_on' => 'Enable',
                'text_off' => 'Disable',
                'text_width' => 80,
                'help' => esc_html__('Enable refund option', 'cpgw'),
                'default' => true,
            ),

            array(
                'id' => 'payment_status',
                'title' => esc_html__('Payment Success: Order Status', 'cpgw'),
                'type' => 'select',
                'options' => apply_filters(
                    'cpgwp_settings_order_statuses',
                    array(
                        'default' => __('Woocommerce Default Status', 'cpgw'),
                        'on-hold' => __('On Hold', 'cpgw'),
                        'processing' => __('Processing', 'cpgw'),
                        'completed' => __('Completed', 'cpgw'),
                    )
                ),
                'desc' => __('Order status upon successful cryptocurrency payment.', 'cpgw'),
                'default' => 'default',
            ),

            array(
                'id' => 'dynamic_messages',
                'title' => esc_html__('Customize Text Display', 'cpgw'),
                'type' => 'select',
                'options' => array(
                    'confirm_msg' => __('Payment Confirmation (Popup)', 'cpgw'),
                    'payment_process_msg' => __('Payment Processing (Popup)', 'cpgw'),
                    'rejected_message' => __('Payment Rejected (Popup)', 'cpgw'),
                    'payment_msg' => __('Payment Completed (Popup)', 'cpgw'),
                    'place_order_button' => __('Place Order Button (Checkout page)', 'cpgw'),
                    'select_a_currency' => __('Select Coin (Checkout page)', 'cpgw'),
                ),

                'desc' => __('Customize the text displayed by the plugin on the frontend.', 'cpgw'),
                'default' => 'place_order_button',
            ),
            array(
                'id' => 'confirm_msg',
                'title' => esc_html__('Payment Confirmation (Popup)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'confirm_msg'),
                'desc' => 'You can change it to your preferred text or leave it blank to keep the default text.',
                'placeholder' => __('Confirm Payment Inside Your Wallet!', 'cpgw'),
            ),
            array(
                'id' => 'payment_process_msg',
                'title' => esc_html__('Payment Processing (Popup)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'payment_process_msg'),
                'desc' => 'Custom message to show  while processing payment via blockchain.',
                'placeholder' => __('Payment in process.', 'cpgw'),
            ),
            array(
                'id' => 'rejected_message',
                'title' => esc_html__('Payment Rejected (Popup)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'rejected_message'),
                'desc' => 'Custom message to show  if you rejected payment via metamask.',
                'placeholder' => __('Transaction rejected. ', 'cpgw'),
            ),
            array(
                'id' => 'payment_msg',
                'title' => esc_html__('Payment Completed (Popup)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'payment_msg'),
                'placeholder' => __('Payment completed successfully.', 'cpgw'),
                'desc' => 'Custom message to show  if  payment confirm  by blockchain.',

            ),
            array(
                'id' => 'place_order_button',
                'title' => esc_html__('Place Order Button (Checkout page)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'place_order_button'),
                'placeholder' => __('Pay With Crypto Wallets', 'cpgw'),
                'desc' => 'Please specify a name for the "Place Order" button on the checkout page.',

            ),
            array(
                'id' => 'select_a_currency',
                'title' => esc_html__('Select Coin (Checkout page)', 'cpgw'),
                'type' => 'text',
                'dependency' => array('dynamic_messages', '==', 'select_a_currency'),
                'placeholder' => __('Please Select a Currency', 'cpgw'),
                'desc' => 'Please provide a name for the label that selects the currency on the checkout page.',

            ),
            array(
                'id' => 'enable_debug_log',
                'title' => esc_html__('Debug mode ', 'cpgw'),
                'type' => 'switcher',
                'text_on' => 'Enable',
                'text_off' => 'Disable',
                'text_width' => 80,
                'desc' => 'When enabled, payment error logs will be saved to WooCommerce > Status > <a href="' . esc_url(get_admin_url(null, "admin.php?page=wc-status&tab=logs")) . '">Logs.</a>',
                'help' => esc_html__('Enable debug mode', 'cpgwp'),
                'default' => true,
            ),

        ),
    ));

    CSF::createSection($prefix, array(
        'title' => 'Free Test Tokens',
        'icon' => 'fas fa-rocket',
        'fields' => array(
            array(
                'type' => 'heading',
                'content' => 'Get Free Test Tokens to Test Payment via Metamask on Test Networks/Chains.',
            ),
            array(
                'type' => 'subheading',
                'content' => 'Binance Test Tokens For Binance Network: <a href="https://testnet.binance.org/faucet-smart" target="_blank">https://testnet.binance.org/faucet-smart</a>',
            ),

        ),

    ));

endif;
