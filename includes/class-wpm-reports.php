<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Report {

    public static function get_profit_report($start_date, $end_date) {
        global $wpdb;

        $orders = wc_get_orders([
            'status'    => ['completed', 'failed'],
            'limit'     => -1,
            'date_query' => [
                'after'  => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ]
        ]);

        $completed_orders = 0;
        $failed_orders = 0;
        $total_sales = 0;
        $total_profit = 0;
        $failed_order_charge = 0;

        foreach ($orders as $order) {
            if ($order->get_status() === 'completed') {
                $completed_orders++;
                $total_sales += $order->get_total();

                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $purchase_price = floatval(get_post_meta($product_id, '_wpm_purchase_price', true));
                    $line_total = $item->get_total();
                    $quantity = $item->get_quantity();
                    $profit = $line_total - ($purchase_price * $quantity);
                    $total_profit += $profit;
                }

            } elseif ($order->get_status() === 'failed') {
                $failed_orders++;
                $failed_order_charge += 50; // ধরলাম প্রতি ফেইল্ড অর্ডারে ৫০ টাকা চার্জ
            }
        }

        // ম্যানুয়াল খরচ (expense_logs টেবিল)
        $expense_table = $wpdb->prefix . 'wpm_expense_logs';
        $total_expense = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM $expense_table WHERE date BETWEEN %s AND %s",
                $start_date, $end_date
            )
        );

        // ফেইল্ড অর্ডার চার্জ যোগ করা খরচে
        $total_expense = floatval($total_expense) + $failed_order_charge;

        $net_profit = $total_profit - $total_expense;

        return [
            'completed_orders' => $completed_orders,
            'failed_orders'    => $failed_orders,
            'total_sales'      => $total_sales,
            'total_profit'     => $total_profit,
            'total_expense'    => $total_expense,
            'net_profit'       => $net_profit
        ];
    }
}


/**
 * Get current capital
 */
function wpm_get_current_capital() {
    return floatval( get_option('wpm_total_capital', 0) );
}

/**
 * Update capital
 */
function wpm_update_capital($new_amount) {
    update_option('wpm_total_capital', floatval($new_amount));
}

/**
 * Add profit to capital
 */
function wpm_add_profit_to_capital($amount) {
    $current = wpm_get_current_capital();
    wpm_update_capital($current + floatval($amount));
}

/**
 * Subtract expense or purchase from capital
 */
function wpm_subtract_from_capital($amount) {
    $current = wpm_get_current_capital();
    wpm_update_capital($current - floatval($amount));
}

/**
 * Log capital actions (optional for future)
 */
function wpm_log_capital_action($type, $amount, $note = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'wpm_capital_logs';

    $wpdb->insert($table, array(
        'type' => $type, // 'profit', 'purchase', 'expense'
        'amount' => floatval($amount),
        'note' => sanitize_text_field($note),
        'log_date' => current_time('mysql')
    ));
}

/**
 * Get total product purchase cost for a date range
 */
function wpm_get_total_purchase_cost($start_date, $end_date) {
    $completed_orders = wpm_get_completed_orders($start_date, $end_date);
    $total_cost = 0;

    foreach ($completed_orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $cost_price = wpm_get_product_cost($product_id);

            $total_cost += ($cost_price * $quantity);
        }
    }

    return $total_cost;
}

/**
 * Get capital report summary
 */
function wpm_get_capital_summary($start_date, $end_date) {
    global $wpdb;


    $start = date('Y-m-d 00:00:00', strtotime($start_date));
    $end = date('Y-m-d 23:59:59', strtotime($end_date));

    // Get completed orders
    $completed_orders = wc_get_orders([
        'status'        => 'completed',
        'limit'         => -1,
        'date_created'  => $start . '...' . $end,
    ]);

    // Get failed orders
    $failed_orders = wc_get_orders([
        'status'        => 'failed',
        'limit'         => -1,
        'date_created'  => $start . '...' . $end,
    ]);


    // মূলধন হিস্টোরি
    $table_capital = $wpdb->prefix . 'wppt_capital_history';
    $total_add = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'add' AND date BETWEEN '$start_date' AND '$end_date'");
    $total_subtract = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'subtract' AND date BETWEEN '$start_date' AND '$end_date'");
    $initial_capital = ($total_add ?: 0) - ($total_subtract ?: 0);

    // খরচ
    $table_expense = $wpdb->prefix . 'wppt_expenses';
    $total_expense = floatval($wpdb->get_var("SELECT SUM(amount) FROM $table_expense WHERE date BETWEEN '$start_date' AND '$end_date'"));

    // প্রোডাক্ট ক্রয় খরচ
    $table_purchase = $wpdb->prefix . 'wppt_purchases';
    $total_purchase = floatval($wpdb->get_var("SELECT SUM(buy_price * quantity) FROM $table_purchase WHERE purchase_date BETWEEN '$start_date' AND '$end_date'"));

    // রিপোর্ট থেকে লাভ ও ফেইল অর্ডার খরচ
    $report = wppt_generate_report_data($start_date, $end_date);
    $total_profit = floatval($report['total_profit'] ?? 0);
     $failed_order_cost = 0;
    $total_completed_orders = count($completed_orders);
    $total_failed_orders = count($failed_orders);
    $total_failed_charge = floatval($report['total_failed_charge'] ?? 0);
 // Failed delivery cost
    $delivery_charge = floatval(get_option('wppt_failed_order_charge', 150)); // Default ৫০ টাকা
    $failed_order_cost = $total_failed_orders * $delivery_charge;

    // নেট মূলধন
  $net_capital = $initial_capital - $total_expense - $total_purchase - $failed_order_cost + $total_profit;

    return [
        'initial_capital' => $initial_capital,
        'total_purchase' => $total_purchase,
        'total_expense' => $total_expense,
         'failed_order_cost'       => $failed_order_cost,
        'total_profit' => $total_profit,
        'net_capital' => $net_capital
    ];
}


function wpm_get_capital_summary_data($start_date, $end_date) {
    global $wpdb;

    $report = wppt_generate_report_data($start_date, $end_date);

    $table_capital = $wpdb->prefix . 'wppt_capital_history';
    $total_add = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'add' AND date BETWEEN '$start_date' AND '$end_date'");
    $total_subtract = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'subtract' AND date BETWEEN '$start_date' AND '$end_date'");
    $initial_capital = ($total_add ?: 0) - ($total_subtract ?: 0);

    $total_expense = floatval($report['total_expense'] ?? 0);
    $product_cost = floatval($report['total_purchase'] ?? 0);
    $total_profit = floatval($report['total_profit'] ?? 0);

    // ✅ সঠিকভাবে ফেইল অর্ডার খরচ হিসাব করো
    $total_failed_orders = count(wc_get_orders([
        'status'        => 'failed',
        'limit'         => -1,
        'date_created'  => $start_date . '...' . $end_date,
    ]));
    $delivery_charge = floatval(get_option('wppt_failed_order_charge', 150));
    $failed_order_charge = $total_failed_orders * $delivery_charge;

    $net_capital = $initial_capital - $product_cost - $total_expense - $failed_order_charge + $total_profit;

    return [
        'total_capital'         => $net_capital,
        'product_cost'          => $product_cost,
        'ad_expense'            => 0,
        'other_expense'         => $total_expense,
        'failed_order_charge'   => $failed_order_charge,
        'total_profit'          => $total_profit
    ];
}
