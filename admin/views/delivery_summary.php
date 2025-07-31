
<?php 
function wpm_get_delivery_stats($start, $end) {
    global $wpdb;

    $completed = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_status = 'wc-completed' AND post_date >= %s AND post_date <= %s", $start, $end . ' 23:59:59')
    );

    $returned = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_status = 'wc-failed' AND post_date >= %s AND post_date <= %s", $start, $end . ' 23:59:59')
    );

    return [
        'completed' => (int)$completed,
        'returned' => (int)$returned,
    ];
}

$today_stats = wpm_get_delivery_stats(date('Y-m-d'), date('Y-m-d'));
$yesterday_stats = wpm_get_delivery_stats(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')));
$last7days_stats = wpm_get_delivery_stats(date('Y-m-d', strtotime('-6 days')), date('Y-m-d'));
$last30days_stats = wpm_get_delivery_stats(date('Y-m-d', strtotime('-29 days')), date('Y-m-d'));

?>
<br>
<div class="wpm-summary-boxes">
    <div class="wpm-card today">
        <div class="icon">📦</div>
        <h3>আজকের রিপোর্ট</h3>
        <p>সফল: <?php echo $today_stats['completed']; ?></p>
        <p>রিটার্ন: <?php echo $today_stats['returned']; ?></p>
    </div>
    <div class="wpm-card yesterday">
        <div class="icon">🕒</div>
        <h3>গতকাল</h3>
        <p>সফল: <?php echo $yesterday_stats['completed']; ?></p>
        <p>রিটার্ন: <?php echo $yesterday_stats['returned']; ?></p>
    </div>
    <div class="wpm-card last7">
        <div class="icon">📊</div>
        <h3>শেষ ৭ দিন</h3>
        <p>সফল: <?php echo $last7days_stats['completed']; ?></p>
        <p>রিটার্ন: <?php echo $last7days_stats['returned']; ?></p>
    </div>
    <div class="wpm-card last30">
        <div class="icon">📈</div>
        <h3>শেষ ৩০ দিন</h3>
        <p>সফল: <?php echo $last30days_stats['completed']; ?></p>
        <p>রিটার্ন: <?php echo $last30days_stats['returned']; ?></p>
    </div>
</div>



<div class="wrap">
    <h2>ডেলিভারি রিপোর্ট (সফল বনাম ব্যর্থ)</h2>

    <?php
    // ডেট রেঞ্জ
    $start = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $end = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    $report = wpm_get_completed_failed_summary($start, $end);

    // মোট সংখ্যা হিসাব করো
    $total_completed = array_sum(array_column($report, 'completed'));
    $total_failed = array_sum(array_column($report, 'failed'));
    ?>

   <form method="get" id="wppt-report-filter">
        <input type="hidden" name="page" value="wpm-delivery-summary">
        <label><strong>শুরুর তারিখ:</strong></label>
        <input type="date" name="start_date" value="<?php echo esc_attr($start); ?>">
        <label><strong>শেষ তারিখ:</strong></label>
        <input type="date" name="end_date" value="<?php echo esc_attr($end); ?>">
        <input type="submit" class="button button-primary" value="রিপোর্ট দেখুন">
    </form>

    <h3>তারিখ: <?php echo esc_html($start); ?> থেকে <?php echo esc_html($end); ?></h3>

   <table class="capital-table">
        <thead>
            <tr>
                <th>তারিখ</th>
                <th>সফল ডেলিভারি</th>
                <th>ব্যর্থ ডেলিভারি</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report as $row): ?>
                <tr>
                    <td><?php echo esc_html($row['date']); ?></td>
                    <td><?php echo esc_html($row['completed']); ?></td>
                    <td><?php echo esc_html($row['failed']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <div style="padding: 15px; background: #f8f8f8; border-left: 5px solid #007cba; margin-top: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <strong>✅ মোট সফল ডেলিভারি:</strong> <?php echo esc_html($total_completed); ?> টি<br>
        <strong>❌ মোট ব্যর্থ ডেলিভারি:</strong> <?php echo esc_html($total_failed); ?> টি
    </div>

    <br><br>
    <canvas id="deliveryChart" height="100"></canvas>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('deliveryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($report, 'date')); ?>,
                    datasets: [
                        {
                            label: 'সফল ডেলিভারি',
                            data: <?php echo json_encode(array_column($report, 'completed')); ?>,
                            backgroundColor: '#28a745'
                        },
                        {
                            label: 'ব্যর্থ ডেলিভারি',
                            data: <?php echo json_encode(array_column($report, 'failed')); ?>,
                            backgroundColor: '#dc3545'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'সফল বনাম ব্যর্থ ডেলিভারি রিপোর্ট'
                        }
                    }
                }
            });
        });
    </script>
</div>
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
            padding: 20px;
            color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }
.wpm-summary-boxes {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.wpm-card {
    border-radius: 15px;
    padding: 20px;
    width: 230px;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    transition: transform 0.3s, box-shadow 0.3s;
    transform: perspective(600px) rotateX(2deg) rotateY(1deg);
}

.wpm-card:hover {
    transform: scale(1.05) rotateX(0deg) rotateY(0deg);
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
}

.wpm-card .icon {
    font-size: 30px;
    margin-bottom: 10px;
}

.wpm-card h3 {
    font-size: 18px;
    margin-bottom: 5px;
}

.wpm-card p {
    font-size: 16px;
    margin: 2px 0;
}

/* চারটি আলাদা রঙ */
.wpm-card.today {
    background: linear-gradient(135deg, #00c6ff, #0072ff); /* নীল */
}

.wpm-card.yesterday {
    background: linear-gradient(135deg, #f7971e, #ffd200); /* কমলা-হলুদ */
}

.wpm-card.last7 {
    background: linear-gradient(135deg, #43e97b, #38f9d7); /* সবুজ-সায়ান */
}

.wpm-card.last30 {
    background: linear-gradient(135deg, #fa709a, #fee140); /* গোলাপি-হলুদ */
}


.capital-report-container {
    font-family: 'Segoe UI', sans-serif;
    background: #f2f4f8;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.capital-title {
    font-size: 28px;
    font-weight: bold;
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
}

.capital-box {
    background: linear-gradient(135deg, #dff9fb, #c7ecee);
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    margin-bottom: 30px;
    transition: transform 0.3s ease;
}
.capital-box:hover {
    transform: scale(1.02);
}
.capital-box h2 {
    margin: 0;
    font-size: 26px;
    color: #1e272e;
}

.capital-form {
    background: #ffffff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}
.capital-form table.form-table th {
    color: #34495e;
    font-weight: 600;
}
.capital-form input[type="number"],
.capital-form textarea,
.capital-form select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #dcdde1;
    border-radius: 10px;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
    transition: all 0.3s;
}
.capital-form input[type="number"]:focus,
.capital-form textarea:focus,
.capital-form select:focus {
    border-color: #3498db;
    box-shadow: 0 0 8px rgba(52, 152, 219, 0.4);
    outline: none;
}
.capital-form textarea {
    resize: vertical;
    min-height: 80px;
}
.capital-form .button-primary {
    background: linear-gradient(to right, #6dd5ed, #2193b0);
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-size: 16px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: background 0.3s ease;
}
.capital-form .button-primary:hover {
    background: linear-gradient(to right, #2193b0, #6dd5ed);
}

.capital-table {
    border-collapse: collapse;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.capital-table th {
    background: #2980b9;
    color: white;
    padding: 12px 16px;
    text-align: left;
}
.capital-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #ecf0f1;
}
.capital-table tr:hover {
    background: #ecf0f1;
    transition: background 0.3s ease;
}
</style>
