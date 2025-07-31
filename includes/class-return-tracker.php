<?php
/**
 * Class: Return_Manager
 * Description: Tracks WooCommerce order status changes to manage stock restore when an order is failed.
 */

namespace WPPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class Return_Manager {

    public static function init() {
        add_action('woocommerce_order_status_changed', [__CLASS__, 'track_order_status'], 10, 4);
    }

    /**
     * Track and store order status history in post meta to prevent double stock restores
     */
    public static function track_order_status( $order_id, $from_status, $to_status, $order ) {
        if ( ! is_a($order, 'WC_Order') ) {
            $order = wc_get_order($order_id);
        }

        if ( ! $order ) return;

        // Get the current history
        $history = get_post_meta( $order_id, '_wppt_status_history', true );
        if ( ! is_array($history) ) {
            $history = [];
        }

        $history[] = [
            'from' => $from_status,
            'to'   => $to_status,
            'time' => current_time('mysql')
        ];

        update_post_meta( $order_id, '_wppt_status_history', $history );
    }

    /**
     * Check if an order previously had an 'on-hold' or 'completed' status before becoming 'failed'
     */
    public static function had_valid_stock_reduction_status_before_failed( $order_id ) {
        $history = get_post_meta( $order_id, '_wppt_status_history', true );
        if ( ! is_array($history) ) return false;

        foreach ( $history as $entry ) {
            if ( $entry['to'] === 'on-hold' || $entry['to'] === 'completed' ) {
                return true;
            }
        }

        return false;
    }

}

Return_Manager::init();