<?php

/**
 * Plugin Name: SULFERC Payment Gateway
 * Description: Use MataMask cryptocurrency payment gateway for WooCommerce store and let customers pay with BNB or BUSD.
 * Author: Stithulf ERC
 * Author URI: https://erc.stithulf.com/
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: sulferc-payment-gateway
 * Domain Path: /languages
 *
 * @package MetaMask
 */

/*
Copyright (C) 2024  Stithulf contact@stithulf.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if (!defined('ABSPATH')) {
    exit;
}
define('CPGW_VERSION', '1.0.0');
define('CPGW_FILE', __FILE__);
define('CPGW_PATH', plugin_dir_path(CPGW_FILE));
define('CPGW_URL', plugin_dir_url(CPGW_FILE));

/*** cpgw_metamask_pay main class by erc.stithulf.com */
if (!class_exists('cpgw_metamask_pay')) {
    final class cpgw_metamask_pay
    {

        /**
         * The unique instance of the plugin.
         */
        private static $instance;

        /**
         * Gets an instance of our plugin.
         */
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor.
         */
        private function __construct()
        {
        }

        // register all hooks
        public function registers()
        {
            /*** Installation and uninstallation hooks */
            register_activation_hook(CPGW_FILE, array(self::$instance, 'activate'));
            register_deactivation_hook(CPGW_FILE, array(self::$instance, 'deactivate'));
            $this->cpgw_installation_date();
            add_action('plugins_loaded', array(self::$instance, 'cpgw_load_files'));
            add_filter('woocommerce_payment_gateways', array(self::$instance, 'cpgw_add_gateway_class'));
            add_action('admin_enqueue_scripts', array(self::$instance, 'cmpw_admin_style'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(self::$instance, 'cpgw_add_widgets_action_links'));
            add_action('admin_menu', array($this, 'cpgw_add_submenu_page'), 1000);
            add_action('init', array($this, 'cpgw_plugin_version_verify'));
            add_action('plugins_loaded', array($this, 'load_text_domain'));
            // add_action('csf_cpgw_settings_save', array($this, 'cpgw_delete_trainsient'));
            add_action('csf_cpgw_settings_save_before', array($this, 'cpgw_delete_trainsient'), 10, 2);
            add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_gateway_block_support'));
        }

        public function cpgw_delete_trainsient($request, $instance)
        {
            // Set option key, which option will control ?
            $opt_key = 'openexchangerates_key';
            $crypto_compare = 'crypto_compare_key';

            // The saved options from framework instance
            $options = $instance->options;

            // Checking the option-key change or not.
            if (isset($options[$opt_key]) && isset($request[$opt_key]) && ($options[$opt_key] !== $request[$opt_key]) || isset($options[$crypto_compare]) && isset($request[$crypto_compare]) && ($options[$crypto_compare] !== $request[$crypto_compare])) {

                delete_transient('cpgw_openexchangerates');
                delete_transient('cpgw_currencyBUSD');
                delete_transient('cpgw_currencySULFERC');
                delete_transient('cpgw_currencyBNB');
            }
        }

        public function cpgw_add_submenu_page()
        {
            add_submenu_page('woocommerce', 'MetaMask Settings', '<strong>MetaMask</strong>', 'manage_options', 'admin.php?page=wc-settings&tab=checkout&section=cpgw', false, 100);

            add_submenu_page('woocommerce', 'MetaMask Transaction', '↳ Transaction', 'manage_options', 'cpgw-metamask', array('CPGW_TRANSACTION_TABLE', 'cpgw_transaction_table'), 101);
            add_submenu_page('woocommerce', 'Settings', '↳ Settings', 'manage_options', 'admin.php?page=cpgw-metamask-settings', false, 102);
        }

        // custom links for add widgets in all plugins section
        public function cpgw_add_widgets_action_links($links)
        {
            $cpgw_settings = admin_url() . 'admin.php?page=cpgw-metamask-settings';
            $links[] = '<a  style="font-weight:bold" href="' . esc_url($cpgw_settings) . '" target="_self">' . __('Settings', 'cpgw') . '</a>';
            return $links;
        }

        public function cmpw_admin_style($hook)
        {
            wp_enqueue_script('cpgw-custom', CPGW_URL . 'assets/js/cpgw-admin.js', array('jquery'), CPGW_VERSION, true);
            wp_enqueue_style('cpgw_admin_css', CPGW_URL . 'assets/css/cpgw-admin.css', array(), CPGW_VERSION, null, 'all');
        }

        public function cpgw_add_gateway_class($gateways)
        {
            $gateways[] = 'WC_cpgw_Gateway'; // your class name is here
            return $gateways;
        }
        /*** Load required files */
        public function cpgw_load_files()
        {
            if (!class_exists('WooCommerce')) {
                add_action('admin_notices', array($this, 'cpgw_missing_wc_notice'));
                return;
            }
            /*** Include helpers functions*/
            require_once CPGW_PATH . 'includes/api/cpgw-api-data.php';
            require_once CPGW_PATH . 'includes/helper/cpgw-helper-functions.php';
            require_once CPGW_PATH . 'includes/cpgw-woo-payment-gateway.php';
            require_once CPGW_PATH . 'includes/db/cpgw-db.php';
            require_once CPGW_PATH . 'includes/class-rest-api.php';
            if (is_admin()) {
                require_once CPGW_PATH . 'admin/table/cpgw-transaction-table.php';
                require_once CPGW_PATH . 'admin/table/cpgw-list-table.php';
                require_once CPGW_PATH . 'admin/codestar-framework/codestar-framework.php';
                require_once CPGW_PATH . 'admin/options-settings.php';
            }
        }
        public function cpgw_installation_date()
        {
            $get_installation_time = strtotime('now');
            add_option('cpgw_activation_time', $get_installation_time);
        }
        public function cpgw_missing_wc_notice()
        {
            $installurl = admin_url() . 'plugin-install.php?tab=plugin-information&plugin=woocommerce';
            if (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
                echo '<div class="error"><p>' . __('SULFERC Payment Gateway requires WooCommerce to be active', 'cpgw') . '</div>';
            } else {
                wp_enqueue_script('cpgw-custom-notice', CPGW_URL . 'assets/js/cpgw-admin-notice.js', array('jquery'), CPGW_VERSION, true);
                echo '<div class="error"><p>' . sprintf(__('SULFERC Payment Gateway requires WooCommerce to be installed and active. Click here to %s WooCommerce plugin.', 'cpgw'), '<button class="cpgw_modal-toggle" >' . __('Install', 'cpgw') . ' </button>') . '</p></div>';
?>
                <div class="cpgw_modal">
                    <div class="cpgw_modal-overlay cpgw_modal-toggle"></div>
                    <div class="cpgw_modal-wrapper cpgw_modal-transition">
                        <div class="cpgw_modal-header">
                            <button class="cpgw_modal-close cpgw_modal-toggle"><span class="dashicons dashicons-dismiss"></span></button>
                            <h2 class="cpgw_modal-heading"><?php _e('Install WooCommerce', 'cpgw'); ?></h2>
                        </div>
                        <div class="cpgw_modal-body">
                            <div class="cpgw_modal-content">
                                <iframe src="<?php echo esc_url($installurl); ?>" width="600" height="400" id="cpgw_custom_cpgw_modal"> </iframe>
                            </div>
                        </div>
                    </div>
                </div>
<?php
            }
        }

        // set settings on plugin activation
        public static function activate()
        {
            require_once CPGW_PATH . 'includes/db/cpgw-db.php';
            update_option('cpgw-v', CPGW_VERSION);
            update_option('cpgw-type', 'FREE');
            update_option('cpgw-installDate', date('Y-m-d h:i:s'));
            update_option('cpgw-already-rated', 'no');
            $db = new CPGW_database();
            $db->create_table();
        }
        public static function deactivate()
        {
            // $db= new CPGW_database();
            // $db->drop_table();
            delete_option('cpgw-v');
            delete_option('cpgw-type');
            delete_option('cpgw-installDate');
            delete_option('cpgw-already-rated');
        }
        /*
        |--------------------------------------------------------------------------
        |  Check if plugin is just updated from older version to new
        |--------------------------------------------------------------------------
         */
        public function cpgw_plugin_version_verify()
        {
            $CPGW_VERSION = get_option('CPGW_FREE_VERSION');

            if (!isset($CPGW_VERSION) || version_compare($CPGW_VERSION, CPGW_VERSION, '<')) {
                if (!get_option('wp_cpgw_transaction_db_version')) {
                    $this->activate();
                }
                if (isset($CPGW_VERSION) && empty(get_option('cpgw_migarte_settings'))) {
                    $this->cpgw_migrate_settings();
                    update_option('cpgw_migarte_settings', 'migrated');
                }

                update_option('CPGW_FREE_VERSION', CPGW_VERSION);
            }
        }

        // Migrate woocommerce settings to codestar
        protected function cpgw_migrate_settings()
        {
            $woocommerce_settings = get_option('woocommerce_cpgw_settings');
            $codestar_options = get_option('cpgw_settings');
            if (!empty($woocommerce_settings)) {
                $codestar_options['user_wallet'] = $woocommerce_settings['user_wallet'];
                $codestar_options['currency_conversion_api'] = $woocommerce_settings['currency_conversion_api'];
                $codestar_options['crypto_compare_key'] = $woocommerce_settings['crypto_compare_key'];
                $codestar_options['openexchangerates_key'] = $woocommerce_settings['openexchangerates_key'];
                $codestar_options['Chain_network'] = $woocommerce_settings['Chain_network'];
                $codestar_options['user_wallet'] = $woocommerce_settings['user_wallet'];
                $codestar_options['bnb_select_currency'] = $woocommerce_settings['bnb_select_currency'];
                $codestar_options['payment_status'] = $woocommerce_settings['payment_status'];
                $codestar_options['payment_msg'] = $woocommerce_settings['payment_msg'];
                $codestar_options['confirm_msg'] = $woocommerce_settings['confirm_msg'];
                $codestar_options['payment_process_msg'] = $woocommerce_settings['payment_process_msg'];
                $codestar_options['rejected_message'] = $woocommerce_settings['rejected_message'];
                update_option('cpgw_settings', $codestar_options);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Load Text domain
        |--------------------------------------------------------------------------
         */
        public function load_text_domain()
        {
            load_plugin_textdomain('cpgw', false, basename(dirname(__FILE__)) . '/languages/');
        }
        public function woocommerce_gateway_block_support()
        {
            if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
                if (!class_exists('CPGWP_metamask_pay')) {
                    require_once 'includes/blocks/class-payment-gateway-blocks.php';
                    add_action(
                        'woocommerce_blocks_payment_method_type_registration',
                        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                            $payment_method_registry->register(new WC_cpgw_Gateway_Blocks_Support());
                        }
                    );
                }
            }
        }
    }
}
/*** cpgw_metamask_pay main class - END */

/*** THANKS - erc.stithulf.com ) */
$cpgw = cpgw_metamask_pay::get_instance();
$cpgw->registers();
