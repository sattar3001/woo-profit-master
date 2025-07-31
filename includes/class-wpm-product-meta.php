<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Product_Meta {

    public static function init() {
        add_action('woocommerce_product_options_pricing', [__CLASS__, 'add_custom_price_fields']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_custom_price_fields']);
    }

    // প্রোডাক্ট এডিট পেজে ফিল্ড দেখাবে
    public static function add_custom_price_fields() {
        woocommerce_wp_text_input([
            'id'          => '_wpm_purchase_price',
            'label'       => __('Purchase Price (Buy Price)', 'woo-profit-master'),
            'desc_tip'    => 'true',
            'description' => __('Enter the purchase/buying price of this product.', 'woo-profit-master'),
            'type'        => 'number',
            'custom_attributes' => ['step' => '0.01', 'min' => '0']
        ]);

        woocommerce_wp_text_input([
            'id'          => '_wpm_selling_price',
            'label'       => __('Expected Selling Price', 'woo-profit-master'),
            'desc_tip'    => 'true',
            'description' => __('Enter your desired selling price for profit calculation.', 'woo-profit-master'),
            'type'        => 'number',
            'custom_attributes' => ['step' => '0.01', 'min' => '0']
        ]);
    }

    // প্রোডাক্ট সেভ করলে মেটা সেভ হবে
    public static function save_custom_price_fields($post_id) {
        if (isset($_POST['_wpm_purchase_price'])) {
            update_post_meta($post_id, '_wpm_purchase_price', wc_clean($_POST['_wpm_purchase_price']));
        }

        if (isset($_POST['_wpm_selling_price'])) {
            update_post_meta($post_id, '_wpm_selling_price', wc_clean($_POST['_wpm_selling_price']));
        }
    }

    // যেকোনো জায়গায় ব্যবহার করার জন্য হেল্পার ফাংশন
    public static function get_purchase_price($product_id) {
        return floatval(get_post_meta($product_id, '_wpm_purchase_price', true));
    }

    public static function get_selling_price($product_id) {
        return floatval(get_post_meta($product_id, '_wpm_selling_price', true));
    }
}

// ইনিশিয়ালাইজ
WPM_Product_Meta::init();
