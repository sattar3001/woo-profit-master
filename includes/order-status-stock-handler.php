<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wppt_get_product_sold($product_id) {
    $sold_key = 'wppt_product_sold_' . $product_id;
    return (int) get_option($sold_key, 0);
}

add_action('woocommerce_order_status_changed', 'wppt_adjust_stock_based_on_status', 10, 4);

function wppt_adjust_stock_based_on_status($order_id, $old_status, $new_status, $order) {
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    // Use custom meta to prevent double processing
    $processed_key = '_wppt_stock_adjusted';
    $processed     = get_post_meta($order_id, $processed_key, true);

    // Get line items
    $items = $order->get_items();

    // Case 1: Deduct stock (on-hold or completed)
    if (in_array($new_status, ['on-hold', 'completed']) && empty($processed)) {
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $qty        = $item->get_quantity();
            $sold_key   = 'wppt_product_sold_' . $product_id;

            $sold       = (int) get_option($sold_key, 0);
            update_option($sold_key, $sold + $qty);
        }

        update_post_meta($order_id, $processed_key, 'deducted');
    }

    // Case 2: Restore stock on failed (only if previously deducted)
    if ($new_status === 'failed' && $processed === 'deducted') {
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $qty        = $item->get_quantity();
            $sold_key   = 'wppt_product_sold_' . $product_id;

            $sold = (int) get_option($sold_key, 0);
            $restored = max(0, $sold - $qty);
            update_option($sold_key, $restored);
        }

        update_post_meta($order_id, $processed_key, 'restored');
    }
}

