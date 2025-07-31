<?php
if (!defined('ABSPATH')) exit;

//require_once plugin_dir_path(__FILE__) . 'lib/mpdf/vendor/autoload.php';
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

add_action('admin_post_wppt_download_fb_ads_pdf', 'wppt_generate_fb_ads_pdf');
function wppt_generate_fb_ads_pdf() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'wppt_expenses';

    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    $query = $wpdb->prepare(
        "SELECT * FROM $table WHERE category = 'Facebook Ads' AND date BETWEEN %s AND %s ORDER BY date ASC",
        $start_date, $end_date
    );
    $expenses = $wpdb->get_results($query);

    $total_usd = 0;
    $total_bdt = 0;

    ob_start();
?>
<style>
    body { font-family: nikosh, sans-serif; font-size: 16px; }
    h2 { text-align: center; color: #2c3e50; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        text-align: center;
    }
    th {
        background-color: #0073aa;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .summary {
        margin-top: 20px;
        font-weight: bold;
        color: #2c3e50;
    }
</style>

<h2>Facebook Ads Cost Report</h2>
<p>Date: <?php echo esc_html($start_date); ?> To <?php echo esc_html($end_date); ?></p>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>USD</th>
            <th>BDT</th>
            <th>Rate</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($expenses as $row):
            $usd = floatval($row->usd_amount);
            $bdt = floatval($row->amount);
            $rate = $usd > 0 ? round($bdt / $usd, 2) : 0;
            $total_usd += $usd;
            $total_bdt += $bdt;
        ?>
        <tr>
            <td><?php echo esc_html($row->date); ?></td>
            <td><?php echo number_format($usd, 2); ?></td>
            <td><?php echo number_format($bdt, 2); ?></td>
            <td><?php echo number_format($rate, 2); ?></td>
            <td><?php echo esc_html($row->description); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p class="summary">Total USD: <?php echo number_format($total_usd, 2); ?> | Total BDT: <?php echo number_format($total_bdt, 2); ?></p>

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
    $mpdf->Output('Facebook-Ads-Report.pdf', 'D');
    exit;
}
?>
