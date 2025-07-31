<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get product cost price
 */
function wpm_get_product_cost( $product_id ) {
    return floatval( get_post_meta( $product_id, '_wpm_purchase_price', true ) );
}

/**
 * Get product selling price
 */
function wpm_get_product_selling_price( $product_id ) {
    $product = wc_get_product( $product_id );
    return $product ? floatval( $product->get_price() ) : 0;
}

/**
 * Calculate profit for a given order
 */
function wpm_calculate_order_profit( $order ) {
    $order_items = $order->get_items();
    $total_profit = 0;

    foreach ( $order_items as $item ) {
        $product_id = $item->get_product_id();
        $quantity   = $item->get_quantity();
        $selling_price = floatval( $item->get_total() );
        $cost_price    = wpm_get_product_cost( $product_id ) * $quantity;

        $profit = $selling_price - $cost_price;
        $total_profit += $profit;
    }

    return $total_profit;
}

/**
 * Calculate delivery charge (fixed or based on shipping method)
 */
function wpm_get_failed_order_cost( $order ) {
    $shipping_total = floatval( $order->get_shipping_total() );
    return $shipping_total;
}

/**
 * Get all failed orders for a date range
 */
function wpm_get_failed_orders( $start_date, $end_date ) {
    $args = array(
        'status' => 'failed',
        'limit' => -1,
        'date_created' => $start_date . '...' . $end_date,
        'return' => 'ids',
    );
    return wc_get_orders( $args );
}

/**
 * Get all completed orders for a date range
 */
function wpm_get_completed_orders( $start_date, $end_date ) {
    $args = array(
        'status' => 'completed',
        'limit' => -1,
        'date_created' => $start_date . '...' . $end_date,
        'return' => 'ids',
    );
    return wc_get_orders( $args );
}

/**
 * Calculate total expense from custom expense table
 */
function wpm_get_total_expense( $start_date, $end_date ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wpm_expenses';

    $sql = $wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE expense_date BETWEEN %s AND %s",
        $start_date, $end_date
    );

    return floatval( $wpdb->get_var( $sql ) );
}

/**
 * Calculate net profit (completed profit - failed charges - expenses)
 */
function wpm_calculate_net_profit( $start_date, $end_date ) {
    $completed_orders = wpm_get_completed_orders( $start_date, $end_date );
    $failed_orders = wpm_get_failed_orders( $start_date, $end_date );

    $total_profit = 0;
    foreach ( $completed_orders as $order_id ) {
        $order = wc_get_order( $order_id );
        $total_profit += wpm_calculate_order_profit( $order );
    }

    $total_failed_charge = 0;
    foreach ( $failed_orders as $order_id ) {
        $order = wc_get_order( $order_id );
        $total_failed_charge += wpm_get_failed_order_cost( $order );
    }

    $total_expense = wpm_get_total_expense( $start_date, $end_date );

    return $total_profit - $total_failed_charge - $total_expense;
}
// delivery summary code here

function wpm_get_completed_failed_summary($start_date, $end_date) {
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare("
            SELECT 
                DATE(post_date) as date,
                SUM(CASE WHEN post_status = 'wc-completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN post_status = 'wc-failed' THEN 1 ELSE 0 END) as failed
            FROM {$wpdb->prefix}posts
            WHERE post_type = 'shop_order'
              AND post_date >= %s
              AND post_date <= %s
            GROUP BY DATE(post_date)
            ORDER BY DATE(post_date)
        ", $start_date, $end_date),
        ARRAY_A
    );

    return $results;
}
