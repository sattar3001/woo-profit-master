<?php
$logo_url = 'https://yourdomain.com/wp-content/uploads/logo.png';
?>
<style>
body { font-family: 'DejaVu Sans', sans-serif; }
.invoice-box {
    border: 1px solid #ddd;
    padding: 20px;
    font-size: 14px;
    box-shadow: 0 0 5px #ccc;
}
.invoice-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
.invoice-header img { height: 60px; }
.invoice-title { font-size: 20px; font-weight: bold; color: #444; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
table th, table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
.footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
</style>

<div class="invoice-box">
    <div class="invoice-header">
        <img src="<?php echo $logo_url; ?>" alt="Logo">
        <div class="invoice-title">INVOICE<br>#<?php echo $order->get_order_number(); ?></div>
    </div>

    <strong>Billing Info:</strong><br>
    <?php echo $order->get_formatted_billing_address(); ?><br><br>

    <strong>Order Date:</strong> <?php echo wc_format_datetime($order->get_date_created()); ?><br>
    <strong>Payment:</strong> <?php echo $order->get_payment_method_title(); ?><br>

    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($order->get_items() as $item): ?>
            <tr>
                <td><?php echo $item->get_name(); ?></td>
                <td><?php echo $item->get_quantity(); ?></td>
                <td><?php echo wc_price($item->get_total() / $item->get_quantity()); ?></td>
                <td><?php echo wc_price($item->get_total()); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <strong>Total:</strong> <?php echo $order->get_formatted_order_total(); ?>

    <div class="footer">
        Tasnim Watch — Powered by Woo Profit Master Plugin
    </div>
</div>
