<?php
/*
Plugin Name: Woo Profit Master
Description: Calculate daily, weekly, and monthly profit from WooCommerce including purchase cost, failed order charges, and expenses. Tracks capital and shows detailed reports with charts and PDF export.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('WPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPM_PLUGIN_VERSION', '1.0');

// Include core classes
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-init.php';
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-order-hook.php';
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-product-meta.php';
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-expense.php';
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-reports.php';
require_once WPM_PLUGIN_PATH . 'includes/class-wpm-capital.php';
require_once WPM_PLUGIN_PATH . 'includes/helpers.php';
require_once WPM_PLUGIN_PATH . 'includes/report-logic.php';
require_once WPM_PLUGIN_PATH . 'includes/generate_capital_pdf.php';
require_once WPM_PLUGIN_PATH . 'includes/wppt-export-expense-pdf.php';
require_once WPM_PLUGIN_PATH . 'includes/wppt-generate-summary-pdf.php';
require_once WPM_PLUGIN_PATH . 'includes/admin-post.php';
require_once WPM_PLUGIN_PATH . 'includes/class-stock-manager.php';
require_once WPM_PLUGIN_PATH . 'includes/class-return-tracker.php';
require_once WPM_PLUGIN_PATH . 'includes/class-courier-tracker.php';
require_once WPM_PLUGIN_PATH . 'includes/wppt-fb-ads-cron.php';
require_once WPM_PLUGIN_PATH . 'includes/wppt-fb-ads-pdf.php';
require_once WPM_PLUGIN_PATH . 'admin/expenses-handler.php';
//require_once WPM_PLUGIN_PATH . 'admin/views/wppt-fb-settings.php';


register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('wppt_daily_fb_ads_cron')) {
        wp_schedule_event(time(), 'daily', 'wppt_daily_fb_ads_cron');
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('wppt_daily_fb_ads_cron');
});

add_action('wppt_daily_fb_ads_cron', 'wppt_cron_fetch_facebook_ads_spend');


// Initialize the plugin
add_action('plugins_loaded', ['WPM_Init', 'init']);


// এফিল্টার হুক দিয়ে Chart.js যোগ করা
function wpm_enqueue_chartjs() {
    if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpm-report' ) {
        wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '2.9.3', true );
    }
}
add_action( 'admin_enqueue_scripts', 'wpm_enqueue_chartjs' );




// Plugin activation hook
register_activation_hook(__FILE__, 'wppt_run_plugin_install');

function wppt_run_plugin_install() {
    require_once plugin_dir_path(__FILE__) . 'includes/install.php';
    wppt_plugin_install();
}


add_filter('bulk_actions-edit-shop_order', function($actions) {
    $actions['download_invoice_pdf'] = 'Download Invoice PDF';
    return $actions;
});

add_filter('handle_bulk_actions-edit-shop_order', 'wppt_bulk_invoice_download', 10, 3);
function wppt_bulk_invoice_download($redirect, $action, $post_ids) {
    if ($action !== 'download_invoice_pdf') return $redirect;

    // Pass order IDs via session or URL
    $_SESSION['bulk_invoice_orders'] = $post_ids;
    wp_redirect(admin_url('admin-post.php?action=generate_bulk_invoice_pdf'));
    exit;
}
