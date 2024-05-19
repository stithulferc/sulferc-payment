<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Cpgw_metamask_list extends WP_List_Table
{


    function get_columns()
    {
        $columns = array(

            'id' => '#',
            'order_id' => __("Order Id", "cpgw"),
            'transaction_id' => __("Transaction Id", "cpgw"),
            'sender' => __("Sender", "cpgw"),
            'chain_name' => __("Chain Name", "cpgw"),
            'selected_currency' => __("Payment Currency", "cpgw"),
            'crypto_price' => __("Payment Price", "cpgw"),
            'order_price' => __("Order Price", "cpgw"),
            'status' => __("Status", "cpgw"),
            'last_updated' => __("Date", "cpgw")
        );
        return $columns;
    }

    function prepare_items()
    {

        global $wpdb, $_wp_column_headers;
        //    echo '<h1>Coins List</h1><form method="post">';
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $query = 'SELECT * FROM ' . $wpdb->base_prefix . 'cpgw_transaction';
        /*  $this->cmc_process_bulk_action();
        $this->cmc_perform_row_actions(); */
        // delete_option('cpgw-coins-search');

        // search keyword

        $user_search_keyword = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $processing = isset($_REQUEST['cpgw_processing']) ? wp_unslash(trim($_REQUEST['cpgw_processing'])) : '';
        $canceled = isset($_REQUEST['cpgw_canceled']) ? wp_unslash(trim($_REQUEST['cpgw_canceled'])) : '';
        $completed = isset($_REQUEST['cpgw_completed']) ? wp_unslash(trim($_REQUEST['cpgw_completed'])) : '';
        $onhold = isset($_REQUEST['cpgw_on_hold']) ? wp_unslash(trim($_REQUEST['cpgw_on_hold'])) : '';


        /*      if( !empty($user_search_keyword) ){
            
            update_option('cpgw-coins-search', $user_search_keyword );
        }else if( false != get_option('cpgw-coins-search', false) && empty($user_search_keyword) ){
            $user_search_keyword = get_option('cpgw-coins-search', '');
        } */

        if (isset($user_search_keyword) && !empty($user_search_keyword)) {
            $query .= ' where ( order_id LIKE "%' . $user_search_keyword . '%" OR chain_name LIKE "%' . $user_search_keyword . '%" OR selected_currency LIKE "%' . $user_search_keyword . '%") ';
        } elseif (isset($processing) && !empty($processing)) {
            $query .= ' where ( status LIKE "%' . $processing . '%" ) ';
        } elseif (isset($canceled) && !empty($canceled)) {
            $query .= ' where ( status LIKE "%' . $canceled . '%" ) ';
        } elseif (isset($completed) && !empty($completed)) {
            $query .= ' where ( status LIKE "%' . $completed . '%" ) ';
        } elseif (isset($onhold) && !empty($onhold)) {
            $query .= ' where ( status LIKE "%' . $onhold  . '%" ) ';
        }

        // Ordering parameters
        $orderby = !empty($_REQUEST["orderby"]) ? esc_sql($_REQUEST["orderby"]) : 'last_updated';
        $order = !empty($_REQUEST["order"]) ? esc_sql($_REQUEST["order"]) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        // Pagination parameters
        $totalitems = $wpdb->query($query);
        $perpage = 10;
        if (!is_numeric($perpage) || empty($perpage)) {
            $perpage = 10;
        }

        $paged = !empty($_REQUEST["paged"]) ? esc_sql($_REQUEST["paged"]) : false;

        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);

        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }

        // Register the pagination & build link
        $this->set_pagination_args(
            array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage,
            )
        );
    }

    function column_default($item, $column_name)
    {
        wp_enqueue_style('woocommerce_admin_styles');
        switch ($column_name) {

            case 'id':
                return $item->id;
            case 'order_id':
                return '<a href="' . admin_url() . 'post.php?post=' . $item->order_id . '&action=edit">#' . $item->order_id . ' ' . $item->user_name . '</a>';

            case 'transaction_id':
                if ($item->transaction_id != "false") {
                    if ($item->chain_id == '0x61') {
                        return '<a href="https://testnet.bscscan.com/tx/' . $item->transaction_id . '" target="_blank">' . $item->transaction_id . '</a>';
                    } elseif ($item->chain_id == '0x38') {
                        return '<a href="https://bscscan.com/tx/' . $item->transaction_id . '" target="_blank">' . $item->transaction_id . '</a>';
                    }
                }
                return $item->status;
                break;

            case 'sender':
                return $item->sender;

            case 'chain_name':
                return $item->chain_name;

            case 'selected_currency':
                return $item->selected_currency;

            case 'crypto_price':
                return $item->crypto_price;

            case 'order_price':
                return $item->order_price;

            case 'status':
                if ($item->status == "canceled") {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($item->status) . '</span></span>';
                } elseif ($item->status == "completed") {
                    return '<span class="order-status status-completed tips"><span>' . ucfirst($item->status) . '</span></span>';
                } elseif ($item->status == "processing") {
                    return '<span class="order-status status-processing tips"><span>' . ucfirst($item->status) . '</span></span>';
                } elseif ($item->status == "on-hold") {
                    return '<span class="order-status status-on-hold tips"><span>' . ucfirst($item->status) . '</span></span>';
                } else {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($item->status) . '</span></span>';
                }

            case 'last_updated':
                return $this->timeAgo($item->last_updated);
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'id' => array('id', false),
            //    'transaction_id' => array('Transaction Id', false),
            'order_id' => array('order_id', false),
            //   'sender' => array('Sender', false),
            'chain_name' => array('chain_name', false),
            'selected_currency' => array('selected_currency', false),
            'crypto_price' => array('crypto_price', false),
            'order_price' => array('order_price', false),
            //  'status' => array('Status', false),
            'last_updated' => array('last_updated', false),
        );
        return $sortable_columns;
    }

    function timeAgo($time_ago)
    {
        $time_ago = strtotime($time_ago) ? strtotime($time_ago) : $time_ago;
        $time = time() - $time_ago;
        switch ($time):
                // seconds
            case $time < 60:
                return '1 minute ago';
                // minutes
            case $time >= 60 && $time < 3600:
                return (round($time / 60) == 1) ? '1 minute' : round($time / 60) . ' minutes ago';
                // hours
            case $time >= 3600 && $time < 86400:
                return (round($time / 3600) == 1) ? '1 hour ago' : round($time / 3600) . ' hours ago';
                // days
            case $time >= 86400:
                return (round($time / 86400) == 1) ? date_i18n('M j, Y', $time_ago) : date_i18n('M j, Y', $time_ago);

        endswitch;
    }
}
