
<?php
add_action('admin_init', function() {
    if (isset($_POST['amount']) && current_user_can('manage_options')) {
        global $wpdb;
        $table = $wpdb->prefix . 'wppt_expenses';

        $data = [
            'amount' => floatval($_POST['amount']),
            'description' => sanitize_text_field($_POST['description']),
            'voucher_name' => sanitize_text_field($_POST['voucher_name']),
            'category' => sanitize_text_field($_POST['category']),
            'date' => sanitize_text_field($_POST['date']),
        ];

        if (!empty($_POST['expense_id'])) {
            $wpdb->update($table, $data, ['id' => intval($_POST['expense_id'])]);
        } else {
            $wpdb->insert($table, $data);
        }

        wp_redirect(admin_url('admin.php?page=wppt-expenses'));
        exit;
    }

    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'wppt_expenses', ['id' => intval($_GET['delete'])]);
        wp_redirect(admin_url('admin.php?page=wppt-expenses'));
        exit;
    }
});
