<?php
/**
 * File: admin/views/courier-status-view.php
 * Description: Display failed WooCommerce orders as courier return report
 */

if ( ! defined( 'ABSPATH' ) ) exit;

echo '<div class="wrap">';
echo '<h1 style="color:#0073aa;">রিটার্ন কুরিয়ার রিপোর্ট</h1>';

// Fetch failed orders
$args = [
    'status' => 'failed',
    'limit'  => -1,
    'orderby' => 'date',
    'order'   => 'DESC',
];

$orders = wc_get_orders($args);

if ( empty($orders) ) {
    echo '<p style="color:red;">কোন রিটার্ন অর্ডার পাওয়া যায়নি।</p>';
    echo '</div>';
    return;
}
?>

<style>
    .courier-return-table {
        width: 100%;
        max-width: 1000px;
        border-collapse: collapse;
        margin-top: 20px;
        box-shadow: 0 0 8px rgba(0,0,0,0.1);
        background: #fff;
    }

    .courier-return-table th {
        background-color: #0073aa;
        color: #fff;
        padding: 10px;
        font-size: 15px;
    }

    .courier-return-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    .courier-return-table tr:nth-child(even) {
        background: #f9f9f9;
    }

    .status-label {
        background: #dc3232;
        color: #fff;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }
</style>

<table class="courier-return-table">
    <thead>
        <tr>
            <th>অর্ডার #</th>
            <th>তারিখ</th>
            <th>কাস্টমার</th>
            <th>মোবাইল</th>
            <th>প্রোডাক্ট</th>
            <th>পরিমাণ</th>
            <th>মোট</th>
            <th>স্ট্যাটাস</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $orders as $order ) :
            /** @var WC_Order $order */
            $order_id = $order->get_id();
            $date = $order->get_date_created()->date('Y-m-d');
            $customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $phone = $order->get_billing_phone();
            $status = wc_get_order_status_name( $order->get_status() );
            $total = $order->get_formatted_order_total();
        ?>
        <tr>
            <td><a href="<?php echo esc_url( admin_url("post.php?post={$order_id}&action=edit") ); ?>" target="_blank">#<?php echo $order_id; ?></a></td>
            <td><?php echo esc_html( $date ); ?></td>
            <td><?php echo esc_html( $customer ); ?></td>
            <td><?php echo esc_html( $phone ); ?></td>
            <td>
                <ul style="margin:0; padding-left: 18px;">
                    <?php foreach ( $order->get_items() as $item ) : ?>
                        <li><?php echo esc_html( $item->get_name() ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <ul style="margin:0; padding-left: 18px;">
                    <?php foreach ( $order->get_items() as $item ) : ?>
                        <li><?php echo esc_html( $item->get_quantity() ); ?> pcs</li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td><?php echo $total; ?></td>
            <td><span class="status-label"><?php echo esc_html( $status ); ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php echo '</div>'; ?>
