<?php

class CPGW_database
{

    /**
     * Get things started
     *
     * @access  public
     * @since   1.0
     */
    public function __construct()
    {

        global $wpdb;

        $this->table_name = $wpdb->base_prefix . 'cpgw_transaction';
        $this->primary_key = 'id';
        $this->version = '1.0';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   1.0
     */
    public function get_columns()
    {

        return array(
            'id' => '%d',
            'order_id' => '%d',
            'chain_id' => '%s',
            'order_price' => '%s',
            //  'quantity' => '%f',
            'user_name' => '%s',
            'crypto_price' => '%s',
            'selected_currency' => '%s',
            'chain_name' => '%s',
            'status' => '%s',
            'sender' => '%s',
            'transaction_id' => '%s'
        );
    }


    public function cpgw_get_data()
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM $this->table_name ");
        return $results;
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   1.0
     */
    public function get_column_defaults()
    {
        return array(
            'order_id' => '',
            'chain_id' => '',
            'order_price' => '',
            //  'quantity' => '',
            'user_name' => '',
            'crypto_price' => '',
            'selected_currency' => '',
            'chain_name' => '',
            'status' => '',
            'sender' => '',
            'transaction_id' => '',
            'last_updated' => date('Y-m-d H:i:s'),
        );
    }

    public function coin_exists_by_id($coin_ID)
    {

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE order_id ='%s'", $coin_ID));
        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }



    public function cpgw_insert_data($coins_data)
    {
        if (is_array($coins_data) && count($coins_data) > 1) {
            global $wpdb;
            $query_indexes = "INSERT INTO " . $this->table_name . " (order_id,chain_id,order_price,user_name,crypto_price,selected_currency,chain_name,status,sender,transaction_id) VALUES ";
            $query_values = [];
            foreach ($coins_data as $coin => $values) {

                $raw_values = "'" . $values . "'";
                array_push($query_values, $raw_values);
            }
            $query = $query_indexes . '(' . implode(',', $query_values) . ") ON DUPLICATE KEY UPDATE order_id=VALUES(order_id)";

            $result = $wpdb->query($query);
            return $result;
        }
    }



    /**
     * Return the number of results found for a given query
     *
     * @param  array  $args
     * @return int
     */
    public function count($args = array())
    {
        return $this->get_coins($args, true);
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   1.0
     */
    public function create_table()
    {

        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        //IF NOT EXISTS - condition not required

        $sql = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
		id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL UNIQUE,
        chain_id longtext NOT NULL,      
		order_price longtext NOT NULL,
        user_name longtext NOT NULL,       
		crypto_price longtext NOT NULL,
        selected_currency longtext NOT NULL,
        chain_name longtext NOT NULL,
        status longtext NOT NULL,
        sender longtext NOT NULL,       
        transaction_id longtext NOT NULL,
		last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        $wpdb->query($sql);

        update_option($this->table_name . '_db_version', $this->version);
    }

    public function drop_table()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $this->table_name);
    }
}
