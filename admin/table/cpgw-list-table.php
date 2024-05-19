<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Cpgw_metamask_list extends WP_List_Table
{

    public function get_columns()
    {
        $columns = array(
            'order_id' => __("Order Id", "cpgwp"),
            'transaction_id' => __("Transaction ID", "cpgwp"),
            'sender' => __("Sender", "cpgwp"),
            'chain_name' => __("Network", "cpgwp"),
            'selected_currency' => __("Coin", "cpgwp"),
            'crypto_price' => __(" Crypto Price", "cpgwp"),
            'order_price' => __("Fiat Price", "cpgwp"),
            'status' => __("Payment Confirmation", "cpgwp"),
            'order_status' => __("Order Status", "cpgwp"),
            'last_updated' => __("Date", "cpgw"),
        );
        return $columns;
    }

    public function prepare_items()
    {

        global $wpdb, $_wp_column_headers;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $query = 'SELECT * FROM ' . $wpdb->base_prefix . 'cpgw_transaction';
        $user_search_keyword = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $status = isset($_REQUEST['payment_status']) ? wp_unslash(trim($_REQUEST['payment_status'])) : '';
        if (isset($user_search_keyword) && !empty($user_search_keyword)) {
            $query .= ' where ( order_id LIKE "%' . $user_search_keyword . '%" OR chain_name LIKE "%' . $user_search_keyword . '%" OR selected_currency LIKE "%' . $user_search_keyword . '%" OR transaction_id LIKE "%' . $user_search_keyword . '%") ';
        } elseif (isset($status) && !empty($status)) {
            $query .= ' where ( status LIKE "' . $status . '" ) ';
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

    public function column_default($item, $column_name)
    {
        wp_enqueue_style('woocommerce_admin_styles');
        $order = wc_get_order($item->order_id);
        switch ($column_name) {

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
                return ($order) ? $order->get_status() : false;
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
                // if ($order == false) {
                //     return '<span class="order-status status-deleted tips"><span>Deleted</span></span>';
                // }        
                if ($item->status == 'completed' || $item->status == 'processing') {
                    return '<span class="order-status status-processing tips"><span>' . __('Confirmed', 'cpgwp') . '</span></span>';
                } elseif ($item->status == "awaiting") {
                    return '<span class="order-status status-cancelled tips"><span>' . __('Awaiting', 'cpgwp') . '</span></span>';
                } elseif ($item->status == "pending" || $item->status == "canceled" || $item->status == "on-hold") {
                    return '<span class="order-status status-cancelled tips"><span>' . __('Unknown', 'cpgwp') . '</span></span>';
                } else {
                    return '<span class="order-status status-cancelled tips"><span>' . __('Failed', 'cpgwp') . '</span></span>';
                }


            case 'order_status':
                if ($order == false) {
                    return '<span class="order-status status-deleted tips"><span>Deleted</span></span>';
                }
                if ($order->get_status() == "canceled") {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "completed") {
                    return '<span class="order-status status-completed tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "processing") {
                    return '<span class="order-status status-processing tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "on-hold") {
                    return '<span class="order-status status-on-hold tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } else {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                }
            case 'last_updated':
                if ($order == false) {
                    return $item->last_updated;
                }
                return $this->timeAgo($order);
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'order_id' => array('order_id', false),
            'chain_name' => array('chain_name', false),
            'selected_currency' => array('selected_currency', false),
            'crypto_price' => array('crypto_price', false),
            'order_price' => array('order_price', false),
            'last_updated' => array('last_updated', false),
        );
        return $sortable_columns;
    }

    public function timeAgo($order)
    {
        $order_date = $order->get_date_created();
        $time_ago = $order_date->getTimestamp();
        $time_difference = time() - $time_ago;

        if ($time_difference < 60) {
            return $time_difference . ' seconds ago';
        } elseif ($time_difference >= 60 && $time_difference < 3600) {
            $minutes = round($time_difference / 60);
            return ($minutes == 1) ? '1 minute' : $minutes . ' minutes ago';
        } elseif ($time_difference >= 3600 && $time_difference < 86400) {
            $hours = round($time_difference / 3600);
            return ($hours == 1) ? '1 hour ago' : $hours . ' hours ago';
        } elseif ($time_difference >= 86400) {
            if (round($time_difference / 86400) == 1) {
                return date_i18n('M j, Y', $time_ago);
            } else {
                return date_i18n('M j, Y', $time_ago);
            }
        }
    }
}
