<?php
// File: woo-profit-master/admin/fb-ads-report.php

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'wppt_expenses';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT date, usd_amount, amount as bdt_amount, amount  FROM $table
     WHERE category = %s AND date BETWEEN %s AND %s
     ORDER BY date ASC",
    'Facebook Ads', $start_date, $end_date
));

$dates = [];
$usd_data = [];
$bdt_data = [];
$total_usd = 0;
$total_bdt = 0;
foreach ($rows as $row) {
    $dates[] = $row->date;
    $usd_data[] = round($row->usd_amount, 2);
    $bdt_data[] = round($row->bdt_amount, 2);
         $total_usd += floatval($row->usd_amount);
        $total_bdt += floatval($row->amount);
}
?>
 <style>
        #wppt-report-filter {
    background: #fff;
    padding: 20px 25px;
    border: 1px solid #e0e0e0;
    border-left: 4px solid #2271b1;
    border-radius: 6px;
    max-width: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

#wppt-report-filter label {
    font-weight: 600;
    margin-right: 10px;
    color: #444;
    min-width: 100px;
}

#wppt-report-filter input[type="date"] {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    width: 180px;
}

#wppt-report-filter input[type="submit"] {
    padding: 8px 16px;
    font-size: 14px;
    font-weight: bold;
    background-color: #2271b1;
    border: none;
    border-radius: 4px;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 5px;
}

#wppt-report-filter input[type="submit"]:hover {
    background-color: #135e96;
}


.wppt-report-grid {
     display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}


.wppt-card {
   
    min-width: 220px;
    max-width: 250px;
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    color: white;
    font-weight: bold;
    font-size: 16px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    transform: perspective(600px) rotateX(2deg) rotateY(1deg);
    position: relative;
    overflow: hidden;
}

.wppt-card:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 25px rgba(0,0,0,0.3);
}

/* আইকন স্টাইল */
.wppt-card::before {
    content: attr(data-icon);
    font-size: 36px;
    position: absolute;
    top: 15px;
    left: 15px;
    opacity: 0.2;
}

/* আলাদা গ্রেডিয়েন্ট ব্যাকগ্রাউন্ড */
.card-sales {
    background: linear-gradient(135deg, #667eea, #764ba2); /* বেগুনি-নীল */
}
.card-expense {
    background: linear-gradient(135deg, #f7971e, #ffd200); /* কমলা-হলুদ */
}

</style>

<div class="wrap">
    <h1>📊 Facebook Ads ব্যয় রিপোর্ট</h1>

    <form method="get" id="wppt-report-filter">
        <input type="hidden" name="page" value="wppt-facebook-ads-report">
        <label>শুরুর তারিখ:</label>
        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
        <label>শেষ তারিখ:</label>
        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
        <input type="submit" class="button button-primary" value="ফিল্টার করুন">
    </form>

  <div class="wppt-report-grid">
    <div class="wppt-card card-sales" data-icon="💰">মোট USD: $<?php echo number_format($total_usd, 2); ?></div>
    <div class="wppt-card card-expense" data-icon="📉"> মোট BDT: <?php echo number_format($total_bdt, 2); ?>৳</div>
  
</div>


    <canvas id="fbAdsChart" height="150"></canvas>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank">
        <input type="hidden" name="action" value="wppt_download_fb_ads_pdf">
        <input type="hidden" name="start_date" value="<?php echo esc_attr($start_date); ?>">
        <input type="hidden" name="end_date" value="<?php echo esc_attr($end_date); ?>">
        <button class="button button-secondary" style="margin-top: 20px;">📄 পিডিএফ ডাউনলোড করুন</button>
    </form>
</div>
   
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('fbAdsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [
            {
                label: 'USD ব্যয়',
                data: <?php echo json_encode($usd_data); ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'BDT ব্যয়',
                data: <?php echo json_encode($bdt_data); ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Facebook Ads ডলার এবং টাকায় খরচ রিপোর্ট'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
