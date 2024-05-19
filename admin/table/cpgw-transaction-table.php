<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CPGW_TRANSACTION_TABLE')) {
    class CPGW_TRANSACTION_TABLE
    {
        public function __construct()
        {
        }

        //Transaction table callback

        public static function cpgw_transaction_table()
        {
            $list_table = new Cpgw_metamask_list();
            echo '<div class="wrap"><h2>' . __("Crypto Transactions", "cpgw") . '</h2>';

            $list_table->prepare_items();
?>
            <form method="post" class="alignleft">
                <select name="payment_status">
                    <option value=""><?php echo esc_html__('All Transactions', 'cpgwp'); ?></option>
                    <option value="awaiting" <?php selected(isset($_GET['status']), 'awaiting') ?>>
                        <?php echo esc_html__('Awaiting Confirmation', 'cpgwp'); ?>
                    </option>
                    <option value="completed" <?php selected(isset($_GET['status']), 'completed') ?>>
                        <?php echo esc_html__('Confirmed', 'cpgwp'); ?>
                    </option>
                    <option value="unsuccessful" <?php selected(isset($_GET['status']), 'unsuccessful') ?>>
                        <?php echo esc_html__('Unsuccessful', 'cpgwp'); ?>
                    </option>
                </select>

                <button class="button" type="submit"><?php echo esc_html__('Filter', 'cpgwp'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cpgw-metamask')) ?>" class="button"><?php echo esc_html__('Reset', 'cpgwp'); ?></a>
            </form>
            <form method="post">
                <input type="hidden" name="page" value="my_list_test" />
                <?php
                $list_table->search_box('search', 'search_id');
                ?>
            </form>
<?php
            $list_table->display();

            echo '</div>';
        }
    }
}
