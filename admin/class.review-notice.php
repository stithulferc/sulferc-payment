<?php

if (!class_exists('Cpgw_Review_Notice')) :
    class Cpgw_Review_Notice
    {
        const PLUGIN = 'Cryptocurrency Payments Using MetaMask For WooCommerce';
        const SLUG = 'cpgw';
        const LOGO = CPGW_URL . 'assets/images/icon-256x256.png';
        const SPARE_ME = 'cpgw_spare_me';
        const ACTIVATE_TIME = 'cpgw_activation_time';
        const AJAX_REQUEST = 'cpgw_dismiss_notice';
    }
endif;

if (!class_exists('CPGW_Review_Class')) {
    class CPGW_Review_Class
    {
        /**
         * The Constructor
         */
        public function __construct()
        {
            // register actions

            if (is_admin()) {
                add_action('admin_notices', array($this, 'atlt_admin_notice_for_reviews'));
                add_action('wp_ajax_' . Cpgw_Review_Notice::AJAX_REQUEST, array($this, 'atlt_dismiss_review_notice'));
            }
        }

        // ajax callback for review notice
        public function atlt_dismiss_review_notice()
        {
            $rs = update_option(Cpgw_Review_Notice::SPARE_ME, 'yes');
            echo  json_encode(array("success" => "true"));
            exit;
        }
        // admin notice  
        public function atlt_admin_notice_for_reviews()
        {


            if (!current_user_can('update_plugins')) {
                return;
            }


            if (get_option(Cpgw_Review_Notice::ACTIVATE_TIME)) {
                // get installation dates and rated settings
                $installation_date = date('Y-m-d h:i:s', (int)get_option(Cpgw_Review_Notice::ACTIVATE_TIME));
            }

            // check user already rated 
            if (get_option(Cpgw_Review_Notice::SPARE_ME)) {
                return;
            }

            // grab plugin installation date and compare it with current date
            $display_date = date('Y-m-d h:i:s');
            $install_date = new DateTime($installation_date);
            $current_date = new DateTime($display_date);
            $difference = $install_date->diff($current_date);
            $diff_days = $difference->days;

            // check if installation days is greator then week
            if (isset($diff_days) && $diff_days >= 3) {
                echo $this->atlt_create_notice_content();
            }
        }
    } //class end
    new CPGW_Review_Class();
}
