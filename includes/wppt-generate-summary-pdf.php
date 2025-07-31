<?php
if (!defined('ABSPATH')) exit;

//require_once plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

add_action('admin_post_wppt_download_summary_pdf', 'wppt_generate_business_summary_pdf');
function wppt_generate_business_summary_pdf() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;

    $start_date = sanitize_text_field($_POST['wppt_start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['wppt_end_date'] ?? '');
    if (!$start_date || !$end_date) {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d');
    }

    require_once plugin_dir_path(__FILE__) . 'report-logic.php';
    $report = wppt_generate_report_data($start_date, $end_date);

    ob_start();
    ?>
    <style>
        body { font-family: nikosh, sans-serif; }
        h1 { text-align: center; font-size: 22px; margin-bottom: 20px; }
        .section-title {
            background: #0073aa;
            color: #fff;
            padding: 10px;
            border-radius: 6px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 14px;
        }
        th {
            background: #f4f4f4;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>

    <h1>Business Summary Report</h1>
    <p>তারিখ: <?php echo $start_date . ' থেকে ' . $end_date; ?></p>

    <div class="section-title">Economic information</div>
    <table>
        <tr><th>Metrics</th><th>পরিমাণ</th></tr>
        <tr><td>টোটাল বিক্রি</td><td><?php echo wc_price($report['total_sales']); ?></td></tr>
        <tr><td>মোট খরচ</td><td><?php echo wc_price($report['total_expense']); ?></td></tr>
        <tr><td>ফেইল ডেলিভারি খরচ</td><td><?php echo wc_price($report['failed_order_cost']); ?></td></tr>
        <tr><td>মোট লাভ</td><td><?php echo wc_price($report['total_profit']); ?></td></tr>
        <tr><td>নেট লাভ</td><td><?php echo wc_price($report['net_profit']); ?></td></tr>
    </table>

    <div class="section-title">অর্ডার তথ্য</div>
    <table>
        <tr><th>প্রকার</th><th>সংখ্যা</th></tr>
        <tr><td>সফল অর্ডার</td><td><?php echo esc_html($report['total_completed_orders']); ?> টি</td></tr>
        <tr><td>ফেইল অর্ডার</td><td><?php echo esc_html($report['total_failed_orders']); ?> টি</td></tr>
    </table>

    <div class="footer">
        Tasnim Watch - Powered by Woo Profit Master
    </div>
    <?php
    $html = ob_get_clean();

    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'fontDir' => array_merge($fontDirs, [plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/mpdf/mpdf/ttfonts']),
        'fontdata' => $fontData + [
            'nikosh' => ['R' => 'Nikosh.ttf'],
        ],
        'default_font' => 'nikosh',
    ]);

    $mpdf->WriteHTML($html);
    $mpdf->Output('Business-Summary.pdf', 'I');
    exit;
}
