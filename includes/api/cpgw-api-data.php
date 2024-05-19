<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CPGW_API_DATA')) {
    class CPGW_API_DATA
    {

        /**
         * CRYPTOCOMPARE_TRANSIENT used for fiat conversion API transient time.
         */
        const CRYPTOCOMPARE_TRANSIENT = 10 * MINUTE_IN_SECONDS;

        /**
         * OPENEXCHANGERATE_TRANSIENT used for fiat conversion API  transient time.
         */

        const OPENEXCHANGERATE_TRANSIENT = 120 * MINUTE_IN_SECONDS;

        /**
         * BINANCE_TRANSIENT used for fiat conversion API  transient time.
         */
        const BINANCE_TRANSIENT = 10 * MINUTE_IN_SECONDS;

        /**
         * CMC_API_ENDPOINT
         *
         * Holds the URL of the coins data API.
         *
         * @access public
         *
         */
        const CRYPTOCOMPARE_API = 'https://min-api.cryptocompare.com/data/price?fsym=';

        /**
         * COINGECKO_API_ENDPOINT
         *
         * Holds the URL of the coingecko API.
         *
         * @access public
         *
         */
        const BINANCE_API_COM = 'https://api.binance.com/api/v3/ticker/24hr?symbol=';
        const BINANCE_API_US = 'https://api.binance.us/api/v3/ticker/24hr?symbol=';

        /**
         * OPENEXCHANGERATE_API_ENDPOINT
         *
         * Holds the URL of the openexchangerates API.
         *
         * @access public
         *
         */

        const OPENEXCHANGERATE_API_ENDPOINT = 'https://openexchangerates.org/api/latest.json?app_id=';

        public function __construct()
        {
            // self::CMC_API_ENDPOINT = 'https://apiv3.coinexchangeprice.com/v3/';
        }

        public static function cpgw_crypto_compare_api($fiat, $crypto_token)
        {
            $settings_obj = get_option('cpgw_settings');
            $api = !empty($settings_obj['crypto_compare_key']) ? $settings_obj['crypto_compare_key'] : "";
            $transient = get_transient("cpgw_currency" . $crypto_token);
            if (empty($transient) || $transient === "") {
                $response = wp_remote_post(self::CRYPTOCOMPARE_API . $fiat . '&tsyms=' . $crypto_token . '&api_key=' . $api . '', array('timeout' => 120, 'sslverify' => true));
                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $error_message;
                }
                $body = wp_remote_retrieve_body($response);
                $data_body = json_decode($body);
                set_transient("cpgw_currency" . $crypto_token, $data_body, self::CRYPTOCOMPARE_TRANSIENT);
                return $data_body;
            } else {
                return $transient;
            }
        }

        public static function cpgw_openexchangerates_api()
        {
            $settings_obj = get_option('cpgw_settings');
            $api = !empty($settings_obj['openexchangerates_key']) ? $settings_obj['openexchangerates_key'] : "";
            if (empty($api)) {
                return;
            }
            $transient = get_transient("cpgw_openexchangerates");
            if (empty($transient) || $transient === "") {
                $response = wp_remote_post(self::OPENEXCHANGERATE_API_ENDPOINT . $api . '', array('timeout' => 120, 'sslverify' => true));
                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $error_message;
                }
                $body = wp_remote_retrieve_body($response);
                $data_body = json_decode($body);
                if (isset($data_body->error)) {
                    return (object) array('error' => true, 'message' => $data_body->message, 'description' => $data_body->description);
                }
                set_transient("cpgw_openexchangerates", $data_body, self::OPENEXCHANGERATE_TRANSIENT);
                return $data_body;
            } else {
                return $transient;
            }
        }
        public static function cpgw_binance_price_api($symbol)
        {
            $settings_obj = get_option('cpgw_settings');
            $trans_name = "cpgw_binance_price_" . $symbol;
            $transient = get_transient($trans_name);
            if (empty($transient) || $transient === "") {
                $response = wp_remote_get(self::BINANCE_API_COM . $symbol . '', array('timeout' => 120, 'sslverify' => true));
                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $error_message;
                }
                $body = wp_remote_retrieve_body($response);
                $data_body = json_decode($body);
                if (isset($data_body->msg)) {
                    $response = wp_remote_get(self::BINANCE_API_US . $symbol . '', array('timeout' => 120, 'sslverify' => true));
                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();
                        return $error_message;
                    }
                    $body = wp_remote_retrieve_body($response);
                    $data_body = json_decode($body);
                }
                set_transient($trans_name, $data_body, self::BINANCE_TRANSIENT);
                return $data_body;
            } else {
                return $transient;
            }
        }
    }
}
