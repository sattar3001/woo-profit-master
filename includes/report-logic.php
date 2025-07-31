<?php
function wppt_generate_report_data($start_date, $end_date) {
    global $wpdb;

    $start = date('Y-m-d 00:00:00', strtotime($start_date));
    $end = date('Y-m-d 23:59:59', strtotime($end_date));

    $completed_orders = wc_get_orders([
        'status'        => 'completed',
        'limit'         => -1,
        'date_created'  => $start . '...' . $end,
    ]);

    $failed_orders = wc_get_orders([
        'status'        => 'failed',
        'limit'         => -1,
        'date_created'  => $start . '...' . $end,
    ]);

    $total_sales = 0;
    $product_cost = 0;
    $total_profit = 0;
    $total_completed_orders = count($completed_orders);
    $total_failed_orders = count($failed_orders);

    // ✅ প্রোডাক্ট বিক্রি ও খরচ
    foreach ($completed_orders as $order) {
        $total_sales += $order->get_subtotal();

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $qty = $item->get_quantity();
            $buy_price = floatval(get_post_meta($product_id, '_wpm_purchase_price', true));
            $product_cost += $buy_price * $qty;
        }
    }

    // ❌ ব্যর্থ অর্ডারের ডেলিভারি চার্জ * ১.৫ হিসাবে মাইনাস
    $failed_order_cost = 0;
    foreach ($failed_orders as $order) {
        $shipping_charge = floatval($order->get_shipping_total());
        $failed_order_cost += 150;
    }

    // ✅ মোট লাভ
    $total_profit = $total_sales - $product_cost;

    // ✅ এক্সপেন্স
    $expense_results = $wpdb->get_results($wpdb->prepare("
        SELECT SUM(amount) as total_expense
        FROM {$wpdb->prefix}wppt_expenses
        WHERE date BETWEEN %s AND %s
    ", $start_date, $end_date));
    $total_expense = floatval($expense_results[0]->total_expense ?? 0);

    // ✅ নিট লাভ
    $net_profit = $total_profit - $failed_order_cost - $total_expense;

    return [
        'total_sales'             => $total_sales,
        'product_cost'            => $product_cost,
        'failed_order_cost'       => $failed_order_cost,
        'total_profit'            => $total_profit,
        'total_expense'           => $total_expense,
        'net_profit'              => $net_profit,
        'total_completed_orders'  => $total_completed_orders,
        'total_failed_orders'     => $total_failed_orders,
    ];
}
