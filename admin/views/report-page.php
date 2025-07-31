<div class="wrap">
    <h1>ব্যবসার রিপোর্ট</h1>

    <?php
    // 🛠️ প্রথমে তারিখ ভ্যারিয়েবল ডিফাইন করে নিই
    $start_date = isset($_GET['wppt_start_date']) ? sanitize_text_field($_GET['wppt_start_date']) : '';
    $end_date   = isset($_GET['wppt_end_date']) ? sanitize_text_field($_GET['wppt_end_date']) : '';

    // তারিখ না থাকলে বর্তমান মাস ধরেই ডিফল্ট করে দাও
    if (!$start_date || !$end_date) {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d');
    }
    ?>

    <!-- 🔎 Filter Form -->
    <form method="get" id="wppt-report-filter">
        <input type="hidden" name="page" value="wpm-dashboard" />

        <label for="wppt_start_date">শুরুর তারিখ:</label>
        <input type="date" name="wppt_start_date" value="<?php echo esc_attr($start_date); ?>">

        <label for="wppt_end_date">শেষ তারিখ:</label>
        <input type="date" name="wppt_end_date" value="<?php echo esc_attr($end_date); ?>">

        <input type="submit" value="রিপোর্ট দেখুন" class="button button-primary">
    </form>

    <!-- 📄 PDF Download Button -->
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank" style="margin-top: 30px;">
        <input type="hidden" name="action" value="wppt_download_summary_pdf">
        <input type="hidden" name="wppt_start_date" value="<?php echo esc_attr($start_date); ?>">
        <input type="hidden" name="wppt_end_date" value="<?php echo esc_attr($end_date); ?>">
        <button type="submit" class="button button-primary">📄 সারাংশ পিডিএফ ডাউনলোড</button>
    </form>

    <?php
    // 📊 রিপোর্ট ডেটা জেনারেট করো
    include_once plugin_dir_path(__FILE__) . '../../includes/report-logic.php';
    $report_data = wppt_generate_report_data($start_date, $end_date);
    extract($report_data);
    ?>

    <!-- 📋 রিপোর্ট কার্ড ডিজাইন -->
    <style>
    #wppt-report-filter {
        background: #fff;
        padding: 20px 25px;
        border: 1px solid #e0e0e0;
        border-left: 4px solid #2271b1;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    #wppt-report-filter label {
        font-weight: 600;
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

    .wppt-card::before {
        content: attr(data-icon);
        font-size: 36px;
        position: absolute;
        top: 15px;
        left: 15px;
        opacity: 0.2;
    }

    .card-sales         { background: linear-gradient(135deg, #667eea, #764ba2); }
    .card-expense       { background: linear-gradient(135deg, #f7971e, #ffd200); }
    .card-failed        { background: linear-gradient(135deg, #ff416c, #ff4b2b); }
    .card-profit        { background: linear-gradient(135deg, #11998e, #38ef7d); }
    .card-net-profit    { background: linear-gradient(135deg, #00c6ff, #0072ff); }
    .card-complete      { background: linear-gradient(135deg, #43e97b, #38f9d7); }
    .card-failed-orders { background: linear-gradient(135deg, #ff758c, #ff7eb3); }
    </style>

    <!-- 📦 রিপোর্ট কার্ড -->
    <div class="wppt-report-grid">
        <div class="wppt-card card-sales" data-icon="💰">টোটাল বিক্রি<br><?php echo wc_price($total_sales); ?></div>
        <div class="wppt-card card-expense" data-icon="📉">টোটাল খরচ<br><?php echo wc_price($total_expense); ?></div>
        <div class="wppt-card card-failed" data-icon="❌">ফেইল খরচ<br><?php echo wc_price($failed_order_cost); ?></div>
        <div class="wppt-card card-profit" data-icon="📈">মোট লাভ<br><?php echo wc_price($total_profit); ?></div>
        <div class="wppt-card card-net-profit" data-icon="💹">নেট লাভ<br><?php echo wc_price($net_profit); ?></div>
        <div class="wppt-card card-complete" data-icon="✅">সফল অর্ডার<br><?php echo intval($total_completed_orders); ?> টি</div>
        <div class="wppt-card card-failed-orders" data-icon="🚫">ফেইল অর্ডার<br><?php echo intval($total_failed_orders); ?> টি</div>
    </div>

    <!-- 📊 Chart -->
    <div style="margin-top: 50px;">
        <canvas id="wppt_profit_chart" width="800" height="400"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('wppt_profit_chart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['বিক্রি', 'খরচ', 'ফেইল খরচ', 'লাভ', 'নেট লাভ'],
            datasets: [{
                label: 'টাকার পরিমাণ',
                data: [
                    <?php echo "$total_sales, $total_expense, $failed_order_cost, $total_profit, $net_profit"; ?>
                ],
                backgroundColor: ['#3498db', '#e74c3c', '#9b59b6', '#1abc9c', '#f1c40f'],
                borderColor: ['#2980b9', '#c0392b', '#8e44ad', '#16a085', '#f39c12'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'ব্যবসার সারাংশ গ্রাফ' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '৳' + value;
                        }
                    }
                }
            }
        }
    });
    </script>
</div>
