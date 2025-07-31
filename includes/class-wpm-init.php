<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPM_Init {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menus']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'load_admin_assets']);
    }

    public static function add_admin_menus() {
        add_menu_page(
            'Woo Profit Master',
            'Woo Profit Master',
            'manage_woocommerce',
            'wpm-dashboard',
            [__CLASS__, 'dashboard_page'],
            'dashicons-chart-line',
            58
        );

        add_submenu_page('wpm-dashboard', 'Profit Reports', 'Profit Reports', 'manage_woocommerce', 'wpm-dashboard', [__CLASS__, 'dashboard_page']);
        add_submenu_page('wpm-dashboard', 'Expenses', 'Expenses', 'manage_woocommerce', 'wppt-expenses', [__CLASS__, 'expense_page']);
        add_submenu_page('wpm-dashboard', 'Capital', 'Capital', 'manage_woocommerce', 'wpm-capital', [__CLASS__, 'capital_page']);
           add_submenu_page('wpm-dashboard', 'Capital Summary', 'Capital Summary', 'manage_woocommerce', 'wpm-capital-summary', [__CLASS__, 'capital_summary']);
  add_submenu_page('wpm-dashboard', 'Delivery Summary', 'Delivery Summary', 'manage_woocommerce', 'wpm-delivery-summary', [__CLASS__, 'delivery_summary']);
add_submenu_page(
    'wpm-dashboard',
    'FB Ads Settings',
    'FB Ads Settings',
    'manage_options',
    'wppt-fb-settings',
    function () {
        include plugin_dir_path(__FILE__) . '../admin/views/wppt-fb-settings.php';
    }
);

add_submenu_page(
    'wpm-dashboard',
    'Facebook Ads Report',
    'FB Ads Report',
    'manage_woocommerce',
    'wppt-facebook-ads-report',
    function () {
        include_once plugin_dir_path(__FILE__) . '../admin/views/facebook-ads-report.php';
    }
);
add_submenu_page(
    'wpm-dashboard',
    'FB Ads Tester',
    'FB Ads Tester',
    'manage_options',
    'wppt-fb-ads-tester',
    function() {
        include_once plugin_dir_path(__FILE__) . '../admin/views/wppt-fb-ads-tester.php';
    }
    
);
add_submenu_page(
    'wpm-dashboard',
    'Product Stock Management',
    'প্রোডাক্ট স্টক',
    'manage_options',
    'wppt_stock_entry_view',
    function() {
        include_once plugin_dir_path(__FILE__) . '../admin/views/stock-entry-form.php';
    }
    
);


 
add_submenu_page(
    'wpm-dashboard',
    'Return Parcel Report',
    'রিটার্ন খরচ রিপোর্ট',
    'manage_options',
    'wppt_return_cost_view',
    function() {
        include_once plugin_dir_path(__FILE__) . '../admin/views/return-report-view.php';
    }
    
);

 
add_submenu_page(
    'wpm-dashboard',
    'Courier Status Table',
        'কুরিয়ার রিপোর্ট',
    'manage_options',
   'wppt_courier_status_view',
    function() {
        include_once plugin_dir_path(__FILE__) . '../admin/views/courier-status-view.php';
    }
    
);

    }

    public static function load_admin_assets($hook) {
        if (strpos($hook, 'wpm-') !== false) {
            wp_enqueue_style('wpm-admin-style', WPM_PLUGIN_URL . 'admin/assets/css/admin-style.css', [], WPM_PLUGIN_VERSION);
            wp_enqueue_script('chart-js', WPM_PLUGIN_URL . 'assets/js/chart.js', [], '4.4.0', true);
            wp_enqueue_script('wpm-chart-handler', WPM_PLUGIN_URL . 'admin/assets/js/chart-handler.js', ['chart-js'], WPM_PLUGIN_VERSION, true);
        }
    }

    public static function dashboard_page() {
        include WPM_PLUGIN_PATH . 'admin/views/report-page.php';
    }

    public static function expense_page() {
        include WPM_PLUGIN_PATH . 'admin/views/expense-page.php';
    }

    public static function capital_page() {
        include WPM_PLUGIN_PATH . 'admin/views/capital-page.php';
    }
       public static function capital_summary() {
        include WPM_PLUGIN_PATH . 'admin/views/capital-summary.php';
    }
           public static function delivery_summary() {
        include WPM_PLUGIN_PATH . 'admin/views/delivery_summary.php';
    }

 
}