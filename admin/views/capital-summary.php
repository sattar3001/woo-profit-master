<div class="wrap capital-report-container">
    <h1 class="capital-title">📊  মূলধন (Capital) রিপোর্ট</h1>

    <form method="get" id="wppt-report-filter">
        <input type="hidden" name="page" value="wpm-capital-summary">
        <label for="start_date">শুরুর তারিখ:</label>
        <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>">

        <label for="end_date">শেষ তারিখ:</label>
        <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>">

        <input type="submit" value="রিপোর্ট দেখাও" class="button button-primary">
    </form>

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
<?php
global $wpdb;

// মূলধন হিস্টোরি
$table_capital = $wpdb->prefix . 'wppt_capital_history';
$total_add = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'add'");
$total_subtract = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'subtract'");
$initial_capital = ($total_add ?: 0) - ($total_subtract ?: 0);

// সব খরচ
$table_expense = $wpdb->prefix . 'wppt_expenses';
$total_expense = floatval($wpdb->get_var("SELECT SUM(amount) FROM $table_expense"));

// রিপোর্ট ডেটা
$current_date = date('Y-m-d');
$report = wppt_generate_report_data('2000-01-01', $current_date);
$total_failed_charge = floatval($report['total_failed_charge'] ?? 0);
$total_profit = floatval($report['total_profit'] ?? 0);

// প্রোডাক্ট ক্রয় খরচ
$table_purchase = $wpdb->prefix . 'wppt_purchases';
$total_purchase_cost = floatval($wpdb->get_var("SELECT SUM(buy_price * quantity) FROM $table_purchase"));

$current_capital = $initial_capital - $total_expense - $total_purchase_cost - $total_failed_charge + $total_profit;

// Date range check
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2000-01-01';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $current_date;

// ডেটা ফেচ
$capital_data = wpm_get_capital_summary_data($start_date, $end_date);
?>

    <h2>রিপোর্ট: <?php echo esc_html($start_date); ?> থেকে <?php echo esc_html($end_date); ?></h2>

  <table class="capital-table">
        <thead>
            <tr>
                <th>বিবরণ</th>
                <th>পরিমাণ (৳)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>বর্তমান মূলধন (সর্বমোট)</td>
                <td><?php echo wc_price($current_capital); ?></td>
            </tr>
            <tr>
                <td>মোট ফেইল ডেলিভারি খরচ</td>
                <td><?php echo number_format($capital_data['failed_order_charge'], 2); ?></td>
            </tr>
            <tr>
                <td>মোট বিজ্ঞাপন ও অন্যান্য খরচ</td>
                <td><?php echo number_format($capital_data['other_expense'], 2); ?></td>
            </tr>
            <tr>
                <td>মোট লাভ</td>
                <td><?php echo number_format($capital_data['total_profit'], 2); ?></td>
            </tr>
            <tr>
                <th>নেট লাভ (এই সময়ের জন্য)</th>
                <th><?php echo number_format($capital_data['total_capital'], 2); ?></th>
            </tr>
        </tbody>
    </table>
<?php if (isset($_GET['start_date']) && isset($_GET['end_date'])): ?>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank">
        <input type="hidden" name="action" value="download_capital_pdf">
        <input type="hidden" name="start_date" value="<?php echo esc_attr($_GET['start_date']); ?>">
        <input type="hidden" name="end_date" value="<?php echo esc_attr($_GET['end_date']); ?>">
        <button type="submit" class="button button-secondary">PDF রিপোর্ট ডাউনলোড</button>
    </form>
<?php endif; ?>

    <h2>Capital Breakdown Pie Chart</h2>
    <canvas id="capitalPieChart" width="400" height="400"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctxCapital = document.getElementById('capitalPieChart').getContext('2d');
    const capitalChart = new Chart(ctxCapital, {
        type: 'pie',
        data: {
            labels: [ 'বিজ্ঞাপন ও অন্যান্য খরচ', 'ফেইল অর্ডার খরচ', 'মোট লাভ', 'নেট লাভ' ],
            datasets: [{
                label: 'Capital Breakdown',
                data: [
                    <?php echo $capital_data['other_expense']; ?>,
                    <?php echo $capital_data['failed_order_charge']; ?>,
                    <?php echo $capital_data['total_profit']; ?>,
                    <?php echo $capital_data['total_capital']; ?>
                ],
                backgroundColor: [
                    '#36A2EB', // Other expense
                    '#FFCE56', // Failed order charge
                    '#D9534F', // Profit
                    '#4BC0C0'  // Net Capital
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
    </script>
</div>
