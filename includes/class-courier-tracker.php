<?php
/**
 * Class: Courier_Tracker
 * Description: Tracks courier return based on WooCommerce order status (failed) and adjusts stock accordingly.
 * File: includes/class-courier-tracker.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Courier_Tracker {

    public static function init() {
        // Adjust stock when order status changes
        add_action('woocommerce_order_status_changed', [__CLASS__, 'handle_order_status_change'], 10, 4);
    }

    /**
     * Handle stock restore or reduction based on status change
     */
    public static function handle_order_status_change( $order_id, $from_status, $to_status, $order ) {
        if ( ! $order instanceof WC_Order ) {
            $order = wc_get_order( $order_id );
        }

        // Prevent double stock changes using meta flag
        $meta_key = '_wppt_stock_adjusted_for_order';

        // Already handled
        if ( 'failed' === $to_status && get_post_meta( $order_id, $meta_key, true ) === 'restored' ) {
            return;
        }

        if ( in_array( $to_status, ['on-hold', 'completed'] ) && get_post_meta( $order_id, $meta_key, true ) === 'reduced' ) {
            return;
        }

        // Loop through items
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $qty        = $item->get_quantity();

            // Handle restore
            if ( 'failed' === $to_status ) {
                self::adjust_stock( $product_id, $qty, 'restore' );
            }

            // Handle reduce
            if ( in_array( $to_status, ['on-hold', 'completed'] ) ) {
                self::adjust_stock( $product_id, $qty, 'reduce' );
            }
        }

        // Set flag to prevent duplicate adjustment
        if ( 'failed' === $to_status ) {
            update_post_meta( $order_id, $meta_key, 'restored' );
        } elseif ( in_array( $to_status, ['on-hold', 'completed'] ) ) {
            update_post_meta( $order_id, $meta_key, 'reduced' );
        }
    }

    /**
     * Adjust stock: restore or reduce
     */
    private static function adjust_stock( $product_id, $qty, $action = 'reduce' ) {
        if ( ! $product_id || ! $qty ) return;

        $key = 'wppt_order_stock_' . $product_id;
        $current = (int) get_option( $key, 0 );

        if ( 'restore' === $action ) {
            $new = $current + $qty;
        } else {
            $new = max( 0, $current - $qty );
        }

        update_option( $key, $new );
    }

}
