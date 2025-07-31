<?php
namespace WPPT;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Stock_Manager {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin ফর্ম সাবমিশন হ্যান্ডল করার জন্য হুক
        add_action( 'admin_init', [ __CLASS__, 'handle_stock_form_submission' ] );
    }

    /**
     * Stock entry form submission হ্যান্ডল করা
     */
    public static function handle_stock_form_submission() {
        // যদি nonce না থাকে বা ভুল হয়, তাহলে রিটার্ন
        if ( ! isset( $_POST['wppt_stock_entry_nonce'] ) || ! wp_verify_nonce( $_POST['wppt_stock_entry_nonce'], 'wppt_stock_entry_action' ) ) {
            return;
        }

        // ইউজারের পারমিশন চেক
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // প্রয়োজনীয় ডাটা স্যানিটাইজ ও নেওয়া
        $product_id    = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity      = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $unit_price    = isset( $_POST['unit_price'] ) ? floatval( $_POST['unit_price'] ) : 0;
        $purchase_date = isset( $_POST['purchase_date'] ) ? sanitize_text_field( $_POST['purchase_date'] ) : '';

        // Validate essential data
        if ( $product_id <= 0 || $quantity <= 0 || $unit_price <= 0 || empty( $purchase_date ) ) {
            return; // Invalid data, stop execution
        }

        // Calculate total price
        $total_price = $quantity * $unit_price;

        global $wpdb;
        $table_name = $wpdb->prefix . 'wppt_product_purchases';

        // ডাটাবেজে ইনসার্ট
        $inserted = $wpdb->insert(
            $table_name,
            [
                'product_id'    => $product_id,
                'quantity'      => $quantity,
                'unit_price'    => $unit_price,
                'total_price'   => $total_price,
                'purchase_date' => $purchase_date,
                'created_at'    => current_time( 'mysql' ),
            ],
            [
                '%d',
                '%d',
                '%f',
                '%f',
                '%s',
                '%s',
            ]
        );

         // Success মেসেজ দেখাবেন যদি থাকে
        if ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) {
            echo '<div class="notice notice-success is-dismissible"><p>Stock entry saved successfully.</p></div>';
        }

    }
}

Stock_Manager::init();     