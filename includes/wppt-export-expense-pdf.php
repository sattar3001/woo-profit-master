<?php
if (!defined('ABSPATH')) exit;

// mPDF autoload file
//require_once plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

function wppt_generate_expense_pdf() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;

    // Filter inputs
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $category = sanitize_text_field($_POST['category']);

    // Build SQL filter condition
    $where = 'WHERE 1=1';
    $params = [];

    if ($start_date && $end_date) {
        $where .= " AND date BETWEEN %s AND %s";
        $params[] = $start_date;
        $params[] = $end_date;
    }

    if (!empty($category)) {
        $where .= " AND category = %s";
        $params[] = $category;
    }

    // Fetch expenses
    $table = $wpdb->prefix . 'wppt_expenses';
    $query = $wpdb->prepare("SELECT * FROM $table $where ORDER BY date DESC", $params);
    $expenses = $wpdb->get_results($query);

    // Build HTML
    $total = 0;
    ob_start();
    ?>
    <style>
        body { font-family: 'nikosh', DejaVu Sans, sans-serif; color: #333; }
        h2 { text-align: center; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 12px;
        }
        th {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
        }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .total-box {
            margin-top: 20px;
            padding: 10px;
            font-weight: bold;
            background: #e3f7e3;
            color: green;
            border-radius: 6px;
        }
    </style>

   <h2>খরচের রিপোর্ট</h2>
    <p>তারিখ: <?php echo esc_html($start_date) . ' থেকে ' . esc_html($end_date); ?></p>
    <?php if ($category): ?>
        <p>ক্যাটাগরি: <?php echo esc_html($category); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>তারিখ</th>
                <th>পরিমাণ</th>
                <th>ক্যাটাগরি</th>
                <th>ভাউচার</th>
                <th>বর্ণনা</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $row): 
                $total += floatval($row->amount); ?>
                <tr>
                    <td><?php echo esc_html($row->date); ?></td>
                    <td><?php echo wc_price($row->amount); ?></td>
                    <td><?php echo esc_html($row->category); ?></td>
                    <td><?php echo esc_html($row->voucher_name); ?></td>
                    <td><?php echo esc_html($row->description); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">মোট খরচ হয়েছে : <?php echo wc_price($total); ?></div>
    <?php
    $html = ob_get_clean();

    // Set mPDF font config
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $defaultFontConfig = (new FontVariables())->getDefaults();

    $fontDirs = $defaultConfig['fontDir'];
    $fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font_size' => 12,
    'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
        plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/mpdf/mpdf/ttfonts',
    ]),
    'fontdata' => [
        'solaimanlipi' => [
            'R' => 'HindSiliguri-Regular.ttf',
        ],
    ],
    'default_font' => 'solaimanlipi'
]);


    $mpdf->WriteHTML($html);
    $mpdf->Output('Manual-Expenses.pdf', 'D'); // Show in browser
    exit;
}

// Add admin hooks
add_action('admin_post_wppt_generate_expense_pdf', 'wppt_generate_expense_pdf');
add_action('admin_post_nopriv_wppt_generate_expense_pdf', 'wppt_generate_expense_pdf');
