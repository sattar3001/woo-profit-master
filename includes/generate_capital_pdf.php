<?php

if (!defined('ABSPATH')) exit;

//require_once plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

add_action('admin_post_download_capital_pdf', 'wpm_generate_capital_pdf_report');

function wpm_generate_capital_pdf_report() {
    global $wpdb;

    // Input Dates
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    // Capital Summary Data
    $capital_data = wpm_get_capital_summary_data($start_date, $end_date);

    // Global Summary
    $table_capital = $wpdb->prefix . 'wppt_capital_history';
    $total_add = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'add'");
    $total_subtract = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'subtract'");
    $initial_capital = ($total_add ?: 0) - ($total_subtract ?: 0);

    $table_expense = $wpdb->prefix . 'wppt_expenses';
    $total_expense = floatval($wpdb->get_var("SELECT SUM(amount) FROM $table_expense"));

    $table_purchase = $wpdb->prefix . 'wppt_purchases';
    $total_purchase_cost = floatval($wpdb->get_var("SELECT SUM(buy_price * quantity) FROM $table_purchase"));

    $report = wppt_generate_report_data('2000-01-01', date('Y-m-d'));
    $total_failed_charge = floatval($report['total_failed_charge'] ?? 0);
    $total_profit = floatval($report['total_profit'] ?? 0);

    $current_capital = $initial_capital - $total_expense - $total_purchase_cost - $total_failed_charge + $total_profit;

    // ✅ PDF HTML Content
    ob_start();
    ?>
    <style>
        body {
            font-family: 'solaimanlipi', sans-serif;
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #666;
            padding: 10px;
            font-size: 16px;
        }
        th {
            background: #2980b9;
            color: white;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 30px;
            color: #888;
        }
    </style>

    <h1>মূলধন রিপোর্ট</h1>
    <p>তারিখ: <?php echo esc_html($start_date); ?> থেকে <?php echo esc_html($end_date); ?></p>

    <table>
        <thead>
            <tr><th>বিবরণ</th><th>পরিমাণ (৳)</th></tr>
        </thead>
        <tbody>
            <tr><td>বর্তমান মূলধন (সর্বমোট)</td><td><?php echo number_format($current_capital, 2); ?></td></tr>
            <tr><td>মোট ফেইল ডেলিভারি খরচ</td><td><?php echo number_format($capital_data['failed_order_charge'], 2); ?></td></tr>
            <tr><td>মোট বিজ্ঞাপন ও অন্যান্য খরচ</td><td><?php echo number_format($capital_data['other_expense'], 2); ?></td></tr>
            <tr><td>মোট লাভ</td><td><?php echo number_format($capital_data['total_profit'], 2); ?></td></tr>
            <tr><th>নেট লাভ (এই সময়ের জন্য)</th><th><?php echo number_format($capital_data['total_capital'], 2); ?></th></tr>
        </tbody>
    </table>

    <div class="footer">Tasnim Watch - Powered by Woo Profit Master</div>
    <?php
    $html = ob_get_clean();

    // ✅ mPDF Font Config with SolaimanLipi or HindSiliguri
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font_size' => 12,
        'fontDir' => array_merge($fontDirs, [plugin_dir_path(__FILE__) . 'lib/fonts']),
        'fontdata' => $fontData + [
            'solaimanlipi' => [
                'R' => 'HindSiliguri-Regular.ttf', // Font file name inside lib/fonts
            ]
        ],
        'default_font' => 'solaimanlipi'
    ]);

    $mpdf->WriteHTML($html);
    $mpdf->Output('Capital-Report.pdf', 'I'); // Force Download
    exit;
}
