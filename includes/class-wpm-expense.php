<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Expense {

    public static function init() {
        // future hooks if needed
    }

    // খরচের ক্যাটাগরি ড্রপডাউন অপশন
    public static function get_categories() {
        return [
            'Facebook Ads'    => 'Facebook বিজ্ঞাপন',
            'Transport'       => 'যাতায়াত',
            'Product Purchase'=> 'প্রোডাক্ট ক্রয়',
            'Salary'          => 'বেতন',
            'Other'           => 'অন্যান্য',
            'Failed Delivery' => 'ফেইলড ডেলিভারি',
        ];
    }

    // খরচ যোগ করা
    public static function add_expense($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_expenses';

        $wpdb->insert($table, [
            'amount'     => floatval($data['amount']),
            'description'=> sanitize_text_field($data['description']),
            'date'       => $data['date'],
            'voucher'    => sanitize_text_field($data['voucher']),
            'category'   => sanitize_text_field($data['category']),
        ]);

        // ক্যাপিটাল থেকে মাইনাস
        WPM_Capital::adjust_capital(-floatval($data['amount']), 'expense', $data['description']);
    }

    // খরচ এডিট
    public static function update_expense($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_expenses';

        $wpdb->update($table, [
            'amount'     => floatval($data['amount']),
            'description'=> sanitize_text_field($data['description']),
            'date'       => $data['date'],
            'voucher'    => sanitize_text_field($data['voucher']),
            'category'   => sanitize_text_field($data['category']),
        ], ['id' => $id]);
    }

    // খরচ ডিলিট
    public static function delete_expense($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_expenses';

        $wpdb->delete($table, ['id' => $id]);
    }

    // নির্দিষ্ট তারিখে খরচ লোড
    public static function get_expenses_by_date_range($start_date, $end_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_expenses';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE date BETWEEN %s AND %s ORDER BY date DESC",
            $start_date,
            $end_date
        ));
    }

    // একটি নির্দিষ্ট খরচ
    public static function get_expense($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_expenses';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
}
