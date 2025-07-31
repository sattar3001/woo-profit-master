<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Capital {

    public static function init() {
        // Initialization hooks if needed
    }

    // মূলধনে পরিবর্তন (যোগ বা বিয়োগ)
    public static function adjust_capital($amount, $type, $note = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_capital_logs';

        $wpdb->insert($table, [
            'amount'      => floatval($amount),
            'type'        => sanitize_text_field($type), // 'initial', 'purchase', 'profit', 'expense'
            'note'        => sanitize_text_field($note),
            'created_at'  => current_time('mysql')
        ]);
    }

    // শুরুতে মূলধন সেট করা
    public static function set_initial_capital($amount, $note = 'Initial Capital') {
        self::adjust_capital($amount, 'initial', $note);
    }

    // মোট মূলধন হিসাব
    public static function get_total_capital() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_capital_logs';

        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table");
        return floatval($total);
    }

    // ক্যাটাগরি অনুযায়ী রিপোর্ট
    public static function get_capital_summary() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_capital_logs';

        $results = $wpdb->get_results("SELECT type, SUM(amount) as total FROM $table GROUP BY type", OBJECT_K);

        return [
            'current_capital'   => self::get_total_capital(),
            'product_purchase'  => isset($results['purchase']) ? floatval($results['purchase']) * -1 : 0,
            'ads_expense'       => isset($results['expense']) ? floatval($results['expense']) * -1 : 0,
            'profit_total'      => isset($results['profit']) ? floatval($results['profit']) : 0,
            'initial_capital'   => isset($results['initial']) ? floatval($results['initial']) : 0
        ];
    }

    // সব লগ রিটার্ন
    public static function get_all_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpm_capital_logs';

        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
}
