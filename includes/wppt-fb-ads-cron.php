<?php
// File: woo-profit-master/includes/wppt-facebook-cron.php

if (!defined('ABSPATH')) exit;


function wppt_cron_fetch_facebook_ads_spend() {
    global $wpdb;

    // 1. Facebook API settings
    $access_token = get_option('wppt_fb_access_token');
    $ad_account_id = get_option('wppt_fb_ad_account_id');
    $currency_rate = floatval(get_option('wppt_fb_currency_rate', 110)); // Default rate: 110

    if (!$access_token || !$ad_account_id) return;

    // 2. Date for "yesterday"
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // 3. API Endpoint
    $url = "https://graph.facebook.com/v19.0/act_{$ad_account_id}/insights?fields=spend&time_range[since]={$yesterday}&time_range[until]={$yesterday}&access_token={$access_token}";

    // 4. Make API Request
    $response = wp_remote_get($url);
    if (is_wp_error($response)) return;

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['data'][0]['spend'])) {
        $usd_spent = floatval($data['data'][0]['spend']);
        $bdt_equiv = $usd_spent * $currency_rate;

        // 5. Check if already inserted
        $table = $wpdb->prefix . 'wppt_expenses';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE category = %s AND date = %s", 'Facebook Ads', $yesterday));

        if (!$exists) {
            // 6. Insert
            $wpdb->insert($table, [
                'amount'        => $bdt_equiv,
                'usd_amount'    => $usd_spent,
                'usd_to_bdt'    => $currency_rate,
                'category'      => 'Facebook Ads',
                'description'   => 'Facebook ad spend auto fetched',
                'voucher_name'  => 'FB-' . $yesterday,
                'date'          => $yesterday,
            ]);
        }
    }
}
