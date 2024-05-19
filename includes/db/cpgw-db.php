<?php

class CPGW_database
{
    public $table_name;
    public $primary_key;
    public $version;

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
            'transaction_id' => '%s',
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
    public function update_fields_value($order_id, $column_name, $new_value)
    {
        global $wpdb;

        $dd = $wpdb->update(
            $this->table_name,
            array(
                $column_name => $new_value, // Set the new value for the specified column
            ),
            array(
                'order_id' => $order_id, // Set the ID of the row you want to update
            )
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

    public function check_transaction_id($transaction_id)
    {

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE transaction_id ='%s'", $transaction_id));
        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function cpgw_insert_data($transactions)
    {
        if (is_array($transactions) && count($transactions) >= 1) {

            return $this->wp_insert_rows($transactions, $this->table_name, true, 'transaction_id');
        }
    }

    public function wp_insert_rows($row_arrays, $wp_table_name, $update = false, $primary_key = null)
    {
        global $wpdb;
        $wp_table_name = esc_sql($wp_table_name);
        // Setup arrays for Actual Values, and Placeholders
        $values = array();
        $place_holders = array();
        $query = "";
        $query_columns = "";
        // $floatCols = array('price', 'percent_change_24h', 'percent_change_1y', 'percent_change_30d', 'percent_change_7d', 'market_cap', 'total_volume', 'circulating_supply', 'ath', 'ath_change_percentage', 'high_24h', 'low_24h');

        $query .= "INSERT INTO `{$wp_table_name}` (";

        foreach ($row_arrays as $key => $value) {
            // foreach ($row_array as $key => $value) {
            //  if ($key == 0) {
            if ($query_columns) {
                $query_columns .= ", " . $key . "";
            } else {
                $query_columns .= "" . $key . "";
            }
            //  }

            $values[] = $value;

            $symbol = "%s";
            /*     if (is_numeric($value)) {
            $symbol = "%d";
            }

            if (in_array($key, $floatCols)) {
            $symbol = "%f";
            } */
            if (isset($place_holders[$key])) {
                $place_holders[$key] .= ", '$symbol'";
            } else {
                $place_holders[$key] = "( '$symbol'";
            }
            // }
            // mind closing the GAP
            $place_holders[$key] .= ")";
        }
        // $place_holders[] .= ")";

        $query .= " $query_columns ) VALUES (";

        $query .= implode(', ', $place_holders) . ')';

        if ($update) {
            $update = " ON DUPLICATE KEY UPDATE $primary_key=VALUES( $primary_key ),";
            $cnt = 0;

            foreach ($row_arrays as $key => $value) {
                if ($cnt == 0) {
                    $update .= "$key=VALUES($key)";
                    $cnt = 1;
                } else {
                    $update .= ", $key=VALUES($key)";
                }
            }
            $query .= $update;
        }
        $sql = $wpdb->prepare($query, $values);

        if ($wpdb->query($sql)) {
            return true;
        } else {
            return false;
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
        order_id bigint(20) NOT NULL ,
        chain_id longtext NOT NULL,
		order_price longtext NOT NULL,
        user_name longtext NOT NULL,
		crypto_price longtext NOT NULL,
        selected_currency longtext NOT NULL,
        chain_name longtext NOT NULL,
        status longtext NOT NULL,
        sender longtext NOT NULL,
        transaction_id varchar(250) NOT NULL UNIQUE,
		last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        //$wpdb->query($sql);
        dbDelta($sql);

        update_option($this->table_name . '_db_version', $this->version);
    }

    public function drop_table()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $this->table_name);
    }
}
