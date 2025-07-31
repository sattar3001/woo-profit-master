<?php
if (!defined('ABSPATH')) exit;

function wppt_plugin_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // --- Capital History Table ---
    $capital_table = $wpdb->prefix . 'wppt_capital_history';
    $sql1 = "CREATE TABLE $capital_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('add', 'subtract') NOT NULL,
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    dbDelta($sql1);

    // --- Expense Table ---
$expense_table = $wpdb->prefix . 'wppt_expenses';
$sql2 = "CREATE TABLE $expense_table (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    amount FLOAT NOT NULL,
    usd_amount FLOAT DEFAULT 0,              -- ✅ নতুন কলাম (USD ব্যয়)
    usd_to_bdt FLOAT DEFAULT 0,              -- ✅ নতুন কলাম (USD টাকার রূপান্তর)
    description TEXT,
    category VARCHAR(100),
    voucher_name VARCHAR(100),
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) $charset_collate;";
dbDelta($sql2);

    // --- Courier Status   Table ---
		$table = $wpdb->prefix . 'wppt_return_costs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			order_id BIGINT UNSIGNED NOT NULL,
			return_charge FLOAT NOT NULL,
			reason TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
   // --- Product Purchase Status   Table ---
        	$table = $wpdb->prefix . 'wppt_product_purchases';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			product_id BIGINT UNSIGNED NOT NULL,
			quantity INT NOT NULL,
			unit_price FLOAT NOT NULL,
			total_price FLOAT NOT NULL,
			purchase_date DATE NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
}
