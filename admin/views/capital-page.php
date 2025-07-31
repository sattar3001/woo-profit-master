<div class="wrap capital-report-container">
    <h1 class="capital-title">📊 মূলধন (Capital) রিপোর্ট</h1>
<?php
global $wpdb;

// Capital হিস্টোরি
$table_capital = $wpdb->prefix . 'wppt_capital_history';
$total_add = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'add'");
$total_subtract = $wpdb->get_var("SELECT SUM(amount) FROM $table_capital WHERE type = 'subtract'");
$initial_capital = ($total_add ?: 0) - ($total_subtract ?: 0);

// খরচ
$table_expense = $wpdb->prefix . 'wppt_expenses';
$total_expense = floatval($wpdb->get_var("SELECT SUM(amount) FROM $table_expense"));

// রিপোর্ট ডেটা আনো
$current_date = date('Y-m-d');
$report = wppt_generate_report_data('2000-01-01', $current_date);


// ফেইল ডেলিভারি খরচ ও লাভ
$total_failed_charge = floatval($report['total_failed_charge'] ?? 0);
$total_profit = floatval($report['total_profit'] ?? 0);

// প্রোডাক্ট ক্রয় খরচ (এখানে তুমি ভুল করে টেবিল নাম দিয়েছো)
$table_purchase = $wpdb->prefix . 'wppt_purchases';
$total_purchase_cost = floatval($wpdb->get_var("SELECT SUM(buy_price * quantity) FROM $table_purchase"));

// ✅ বর্তমান মূলধন
$current_capital = $initial_capital 
                    - $total_expense 
                    - $total_purchase_cost 
                    - $total_failed_charge 
                    + $total_profit;
?>


    <div class="capital-box">
        <h2>বর্তমান মূলধন: <?php echo wc_price($current_capital); ?></h2>
    </div>



    <form method="post" class="capital-form">
        <h3>মূলধন পরিবর্তন করুন</h3>
        <table class="form-table">
            <tr>
                <th><label for="capital_amount">টাকার পরিমাণ</label></th>
                <td><input type="number" step="0.01" name="capital_amount" required></td>
            </tr>
            <tr>
                <th><label for="capital_type">ধরণ</label></th>
                <td>
                    <select name="capital_type">
                        <option value="add">যোগ করুন</option>
                        <option value="subtract">বিয়োগ করুন</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="capital_note">নোট</label></th>
                <td><textarea name="capital_note"></textarea></td>
            </tr>
        </table>
        <?php submit_button('আপডেট করুন'); ?>
    </form>

    <?php
    // Capital update handler
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['capital_amount'], $_POST['capital_type'])) {
        $amount = floatval($_POST['capital_amount']);
        $type = sanitize_text_field($_POST['capital_type']);
        $note = sanitize_textarea_field($_POST['capital_note']);

        $wpdb->insert($table_capital, [
            'amount' => $amount,
            'type' => $type,
            'note' => $note,
            'created_at' => current_time('mysql'),
        ]);

        echo '<div class="updated"><p>মূলধন আপডেট করা হয়েছে।</p></div>';
        echo '<meta http-equiv="refresh" content="0">';
    }

    // Capital History
    $history = $wpdb->get_results("SELECT * FROM $table_capital ORDER BY created_at DESC");
    ?>

    <h2>মূলধনের হিস্টোরি</h2>
    <table class="capital-table">
        <thead>
            <tr>
                <th>তারিখ</th>
                <th>পরিমাণ</th>
                <th>ধরণ</th>
                <th>নোট</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $entry): ?>
                <tr>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($entry->created_at))); ?></td>
                    <td><?php echo wc_price($entry->amount); ?></td>
                    <td><?php echo $entry->type === 'add' ? 'যোগ' : 'বিয়োগ'; ?></td>
                    <td><?php echo esc_html($entry->note); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

   <br>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="download_capital_pdf">
        <?php submit_button('📥 পিডিএফ রিপোর্ট ডাউনলোড করুন', 'primary'); ?>
    </form>
</div>
</div>

<style type="text/css">

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
