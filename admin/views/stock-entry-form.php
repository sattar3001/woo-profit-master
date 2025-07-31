<?php
/**
 * File: admin/views/stock-entry-form.php
 * Description: WooCommerce Product Stock Entry Form with Stock Report (fixed stock restore logic)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'wppt_product_purchases';
$products = wc_get_products([ 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC' ]);

// Order-based stock adjustments
$order_items_data = [];
$args = [
    'status' => ['on-hold', 'completed', 'failed'],
    'limit'  => -1,
    'return' => 'ids',
];
$orders = wc_get_orders($args);

foreach ( $orders as $order_id ) {
    $order = wc_get_order($order_id);
    $status = $order->get_status();
    $notes = wc_get_order_notes(['order_id' => $order_id, 'type' => 'system']);

    $was_previously_sold = false;
    foreach ( $notes as $note ) {
        if ( strpos( strtolower($note->content), 'order status changed from' ) !== false &&
             ( strpos( strtolower($note->content), 'to on-hold' ) !== false || strpos( strtolower($note->content), 'to completed' ) !== false ) ) {
            $was_previously_sold = true;
            break;
        }
    }

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $qty = $item->get_quantity();

        if ( ! isset( $order_items_data[$product_id] ) ) {
            $order_items_data[$product_id] = [
                'sold'     => 0,
                'restored' => 0,
            ];
        }

        if ( in_array( $status, ['on-hold', 'completed'] ) ) {
            $order_items_data[$product_id]['sold'] += $qty;
        } elseif ( $status === 'failed' && $was_previously_sold ) {
            $order_items_data[$product_id]['restored'] += $qty;
        }
    }
}

$purchases = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY purchase_date DESC" );
$purchase_totals = [];
foreach ( $purchases as $purchase ) {
    $pid = $purchase->product_id;
    if ( ! isset( $purchase_totals[$pid] ) ) {
        $purchase_totals[$pid] = 0;
    }
    $purchase_totals[$pid] += $purchase->quantity;
}
?>

<div class="wrap">
    <h1 style="color:#0073aa;">প্রোডাক্ট ক্রয় এন্ট্রি ফর্ম</h1>

    <form method="post" action="" style="background:#fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; max-width:700px;">
        <?php wp_nonce_field('wppt_stock_entry_action', 'wppt_stock_entry_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="product_id">প্রোডাক্ট</label></th>
                <td>
                    <select name="product_id" id="product_id" required>
                        <option value="">-- সিলেক্ট করুন --</option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="quantity">পরিমাণ (pcs)</label></th>
                <td><input type="number" name="quantity" id="quantity" required min="1"></td>
            </tr>

            <tr>
                <th><label for="unit_price">প্রতি পিস মূল্য (৳)</label></th>
                <td><input type="number" name="unit_price" id="unit_price" required step="0.01" min="0"></td>
            </tr>

            <tr>
                <th><label>মোট মূল্য</label></th>
                <td><input type="text" id="total_price" readonly style="background:#f4f4f4;"></td>
            </tr>

            <tr>
                <th><label for="purchase_date">তারিখ</label></th>
                <td><input type="date" name="purchase_date" id="purchase_date" value="<?php echo date('Y-m-d'); ?>" required></td>
            </tr>
        </table>

        <?php submit_button('ক্রয় এন্ট্রি সংরক্ষণ করুন'); ?>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const qtyInput = document.getElementById("quantity");
    const priceInput = document.getElementById("unit_price");
    const totalInput = document.getElementById("total_price");

    function calculateTotal() {
        const qty = parseFloat(qtyInput.value) || 0;
        const unitPrice = parseFloat(priceInput.value) || 0;
        const total = qty * unitPrice;
        totalInput.value = total.toFixed(2);
    }

    qtyInput.addEventListener("input", calculateTotal);
    priceInput.addEventListener("input", calculateTotal);
});
</script>

<h2 style="margin-top:50px; color:#444;">ক্রয় তালিকা ও স্টক রিপোর্ট</h2>
<table class="wp-list-table widefat striped" style="max-width:900px;">
    <thead style="background:#f1f1f1;">
        <tr>
            <th>প্রোডাক্ট</th>
            <th>মোট ক্রয়</th>
            <th>বিক্রি হয়েছে</th>
            <th>রিটার্ন হয়েছে</th>
            <th>বর্তমান স্টক</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $products as $product ) :
            $pid       = $product->get_id();
            $name      = $product->get_name();
            $purchased = $purchase_totals[$pid] ?? 0;
            $sold      = $order_items_data[$pid]['sold']     ?? 0;
            $restored  = $order_items_data[$pid]['restored'] ?? 0;
            $stock     = max( 0, ( $purchased + $restored ) - $sold );
        ?>
        <tr>
            <td><?php echo esc_html($name); ?></td>
            <td><?php echo number_format_i18n($purchased); ?></td>
            <td><?php echo number_format_i18n($sold); ?></td>
            <td><?php echo number_format_i18n($restored); ?></td>
            <td><strong><?php echo number_format_i18n($stock); ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>