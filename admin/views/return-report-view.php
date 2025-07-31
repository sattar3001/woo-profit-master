<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'wppt_return_costs';

// Handle insert/update
if ( isset($_POST['wppt_return_cost_nonce']) && wp_verify_nonce($_POST['wppt_return_cost_nonce'], 'wppt_return_cost_action') ) {
    $order_id      = absint($_POST['order_id']);
    $return_charge = floatval($_POST['return_charge']);
    $reason        = sanitize_text_field($_POST['reason']);
    $edit_id       = isset($_POST['edit_id']) ? absint($_POST['edit_id']) : 0;

    if ( $edit_id ) {
        $updated = $wpdb->update($table_name, [
            'order_id'      => $order_id,
            'return_charge' => $return_charge,
            'reason'        => $reason,
        ], ['id' => $edit_id]);

        if ( $updated !== false ) {
            echo '<div class="updated notice"><p>✅ রিটার্ন চার্জ আপডেট হয়েছে</p></div>';
        }
    } else {
        $wpdb->insert($table_name, [
            'order_id'      => $order_id,
            'return_charge' => $return_charge,
            'reason'        => $reason,
        ]);

        echo '<div class="updated notice"><p>✅ রিটার্ন চার্জ সংরক্ষণ হয়েছে</p></div>';
    }
}

// Handle delete
if ( isset($_GET['delete_id']) ) {
    $wpdb->delete($table_name, ['id' => absint($_GET['delete_id'])]);
    echo '<div class="updated notice"><p>❌ রিটার্ন চার্জ ডিলিট হয়েছে</p></div>';
}

// Get all failed orders
$orders = wc_get_orders([
    'limit' => -1,
    'status' => ['failed'],
]);

// Get edit data
$edit_data = null;
if ( isset($_GET['edit_id']) ) {
    $edit_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", absint($_GET['edit_id'])) );
}

// Search & Filter
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

// Pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE 1=1";

// Date Filter
if ( $start_date ) {
    $where .= $wpdb->prepare(" AND created_at >= %s", $start_date . ' 00:00:00');
}
if ( $end_date ) {
    $where .= $wpdb->prepare(" AND created_at <= %s", $end_date . ' 23:59:59');
}

// Search by order ID, name, or phone
if ( $search ) {
    $search_like = '%' . $wpdb->esc_like( $search ) . '%';

    // Get matching order IDs from meta
    $order_ids_meta = $wpdb->get_col( $wpdb->prepare("
        SELECT DISTINCT post_id
        FROM {$wpdb->postmeta}
        WHERE meta_key IN ('_billing_phone', '_billing_first_name', '_billing_last_name')
        AND meta_value LIKE %s
    ", $search_like) );

    // If numeric, also search by order ID directly
    if ( is_numeric($search) ) {
        $order_ids_meta[] = intval($search);
    }

    if ( ! empty($order_ids_meta) ) {
        $escaped_ids = implode(',', array_map('intval', $order_ids_meta));
        $where .= " AND order_id IN ($escaped_ids)";
    } else {
        $where .= " AND 0=1"; // No result if nothing matched
    }
}


// Count total rows
$total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} $where");
$total_pages = ceil($total_rows / $limit);

// Get paginated results
$return_costs = $wpdb->get_results("SELECT * FROM {$table_name} $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
?>

<div class="wrap">
    <h1 style="color:#0073aa;"><?php echo $edit_data ? 'রিটার্ন চার্জ আপডেট করুন' : 'রিটার্ন খরচ এন্ট্রি ফর্ম'; ?></h1>

    <form method="post" action="" style="background:#fff; padding:20px; border-radius:8px; max-width:700px; box-shadow:0 0 10px #ccc;">
        <?php wp_nonce_field('wppt_return_cost_action', 'wppt_return_cost_nonce'); ?>
        <?php if ( $edit_data ) : ?>
            <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_data->id); ?>">
        <?php endif; ?>

        <table class="form-table">
        

            <?php
// Load only 'failed' orders
$orders = wc_get_orders([
    'limit'  => -1,
    'status' => ['failed'],
    'orderby' => 'ID',
    'order'   => 'DESC',
]);
?>
<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<tr>
    <th><label for="order_id">ফেইল্ড অর্ডার সিলেক্ট করুন</label></th>
    <td>
      <select name="order_id" id="order_id" required style="width:100%;">
                        <option value="">-- একটি অর্ডার নির্বাচন করুন --</option>
                        <?php foreach ( $orders as $order ) :
                            $order_id = $order->get_id();
                            $name = $order->get_formatted_billing_full_name();
                            $phone = $order->get_billing_phone();
                        ?>
                            <option value="<?php echo $order_id; ?>" <?php selected( $edit_data && $edit_data->order_id == $order_id ); ?>>
                                #<?php echo $order_id; ?> — <?php echo esc_html($name); ?> — <?php echo esc_html($phone); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
    </td>
</tr>

<!-- Add Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    jQuery('#order_id').select2({
        placeholder: "ফেইল্ড অর্ডার সার্চ করুন...",
        allowClear: true,
        width: '100%'
    });
});
</script>

            <tr>
                <th><label for="return_charge">রিটার্ন খরচ (৳)</label></th>
                <td><input type="number" name="return_charge" id="return_charge" required step="0.01" value="<?php echo $edit_data ? esc_attr($edit_data->return_charge) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="reason">কারণ</label></th>
                <td><textarea name="reason" id="reason" rows="3" style="width:100%;"><?php echo $edit_data ? esc_textarea($edit_data->reason) : ''; ?></textarea></td>
            </tr>
        </table>

        <?php submit_button( $edit_data ? 'আপডেট করুন' : 'সংরক্ষণ করুন' ); ?>
    </form>
</div>

<hr style="margin:50px 0;">

<div class="wrap">
    <h2 style="color:#0073aa;">রিটার্ন চার্জ রিপোর্ট</h2>

    <form method="get" action="" style="margin-bottom: 20px;">
        <input type="hidden" name="page" value="wppt_return_cost_view">
        <input type="text" name="search" placeholder="নাম / মোবাইল / অর্ডার আইডি" value="<?php echo esc_attr($search); ?>">
        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
        <input type="submit" class="button" value="ফিল্টার">
    </form>

    <?php if ( empty( $return_costs ) ) : ?>
        <p>কোন রিটার্ন চার্জ পাওয়া যায়নি।</p>
    <?php else : ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>অর্ডার</th>
                    <th>নাম</th>
                    <th>মোবাইল</th>
                    <th>চার্জ</th>
                    <th>কারণ</th>
                    <th>তারিখ</th>
                    <th>একশন</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $return_costs as $row ) :
                    $order = wc_get_order( $row->order_id );
                    $name = $order ? $order->get_formatted_billing_full_name() : 'N/A';
                    $phone = $order ? $order->get_billing_phone() : 'N/A';
                ?>
                    <tr>
                        <td>#<?php echo esc_html( $row->order_id ); ?></td>
                        <td><?php echo esc_html( $name ); ?></td>
                        <td><?php echo esc_html( $phone ); ?></td>
                        <td><?php echo number_format_i18n( $row->return_charge, 2 ); ?></td>
                        <td><?php echo esc_html( $row->reason ); ?></td>
                        <td><?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime( $row->created_at ) ) ); ?></td>
                        <td>
                            <a href="?page=wppt_return_cost_view&edit_id=<?php echo $row->id; ?>" class="button">✏️</a>
                            <a href="?page=wppt_return_cost_view&delete_id=<?php echo $row->id; ?>" class="button" onclick="return confirm('আপনি কি নিশ্চিত?')">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $url = add_query_arg(['paged' => $i]);
            echo '<a class="button ' . ($i == $page ? 'button-primary' : '') . '" href="' . esc_url($url) . '">' . $i . '</a> ';
        }
        echo '</div></div>';
        ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const select = document.getElementById("order_id");
    if (select) {
        jQuery(select).select2({
            placeholder: "-- একটি অর্ডার নির্বাচন করুন --",
            width: '100%'
        });
    }
});
</script>
