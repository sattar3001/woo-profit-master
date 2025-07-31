<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Order_Hook {

    public static function init() {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'handle_completed_order']);
        add_action('woocommerce_order_status_failed', [__CLASS__, 'handle_failed_order']);
    }

    // সফল অর্ডার - প্রফিট হিসাব করে লগ করে
    public static function handle_completed_order($order_id) {
        $order = wc_get_order($order_id);
        $date  = $order->get_date_completed()->format('Y-m-d');
        $total_profit = 0;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $qty = $item->get_quantity();

            $buy_price = WPM_Product_Meta::get_purchase_price($product_id);
            $sell_price = $item->get_total(); // sell price from order item (could include discount)

            $profit = $sell_price - ($buy_price * $qty);
            $total_profit += $profit;
        }

        // লগ সংরক্ষণ
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_profits';

        $wpdb->insert($table, [
            'order_id'    => $order_id,
            'date'        => $date,
            'profit'      => $total_profit,
            'type'        => 'completed'
        ]);

        // ক্যাপিটাল এ যুক্ত করা হবে এই প্রফিট
        WPM_Capital::adjust_capital($total_profit, 'profit', 'Order #' . $order_id);
    }

    // ব্যর্থ অর্ডার - চার্জ হিসাব করে খরচ হিসাব লগ করে
    public static function handle_failed_order($order_id) {
        $order = wc_get_order($order_id);
        $date  = current_time('Y-m-d');
        $failed_charge = apply_filters('wpm_failed_order_charge', 50); // per failed order charge (editable via filter)

        // খরচে এন্ট্রি নিবে
        WPM_Expense::add_expense([
            'amount'     => $failed_charge,
            'description'=> 'Failed order delivery charge for Order #' . $order_id,
            'date'       => $date,
            'voucher'    => 'FAILED-' . $order_id,
            'category'   => 'Failed Delivery'
        ]);

        // ক্যাপিটাল থেকে মাইনাস হবে
        WPM_Capital::adjust_capital(-$failed_charge, 'expense', 'Failed Order #' . $order_id);
    }
}

// ইনিশিয়ালাইজ
WPM_Order_Hook::init();
