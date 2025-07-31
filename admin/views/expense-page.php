<div class="wrap">
    <h1>Manual Expenses</h1>

    <form method="post" action="">
        <h2><?php echo isset($_GET['edit']) ? 'Edit Expense' : 'Add Expense'; ?></h2>
        <?php
            $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
            $expense = null;

            if ($edit_id) {
                global $wpdb;
                $table = $wpdb->prefix . 'wppt_expenses';
                $expense = $wpdb->get_row("SELECT * FROM $table WHERE id = $edit_id");
            }
        ?>
        <input type="hidden" name="expense_id" value="<?php echo esc_attr($expense->id ?? 0); ?>">

        <table class="form-table">
            <tr>
                <th><label for="amount">Amount</label></th>
                <td><input name="amount" type="number" step="0.01" value="<?php echo esc_attr($expense->amount ?? ''); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><textarea name="description"><?php echo esc_textarea($expense->description ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="voucher_name">Voucher Name</label></th>
                <td><input name="voucher_name" type="text" value="<?php echo esc_attr($expense->voucher_name ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="category">Category</label></th>
                <td>
                    <select name="category">
                        <option value="Facebook Ads" <?php selected($expense->category ?? '', 'Facebook Ads'); ?>>Facebook Ads</option>
                        <option value="Transport" <?php selected($expense->category ?? '', 'Transport'); ?>>Transport</option>
                        <option value="Product Purchase" <?php selected($expense->category ?? '', 'Product Purchase'); ?>>Product Purchase</option>
                        <option value="Salary" <?php selected($expense->category ?? '', 'Salary'); ?>>Salary</option>
                        <option value="Others" <?php selected($expense->category ?? '', 'Others'); ?>>Others</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="date">Date</label></th>
                <td><input name="date" type="date" value="<?php echo esc_attr($expense->date ?? date('Y-m-d')); ?>" required /></td>
            </tr>
        </table>
        <?php submit_button($edit_id ? 'Update Expense' : 'Add Expense'); ?>
    </form>
<div class="wrap">
    <h2>All Expenses</h2>

    <!-- Filter Form -->
    <form method="GET" id="expense-filter-form">
        <input type="hidden" name="page" value="wppt-expenses">

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>" required>

        <label>End Date:</label>
        <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>" required>

        <label>Category:</label>
        <select name="category">
            <option value="">All Categories</option>
            <option value="Facebook Ads" <?php selected($_GET['category'] ?? '', 'Facebook Ads'); ?>>Facebook Ads</option>
            <option value="Transport" <?php selected($_GET['category'] ?? '', 'Transport'); ?>>Transport</option>
            <option value="Product Purchase" <?php selected($_GET['category'] ?? '', 'Product Purchase'); ?>>Product Purchase</option>
            <option value="Salary" <?php selected($_GET['category'] ?? '', 'Salary'); ?>>Salary</option>
            <option value="Others" <?php selected($_GET['category'] ?? '', 'Others'); ?>>Others</option>
        </select>

        <input type="submit" value="Filter" class="button">


     
    </form>
    <?php if (isset($_GET['start_date']) && isset($_GET['end_date'])): ?>
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank">
    <input type="hidden" name="action" value="wppt_generate_expense_pdf">
    <input type="hidden" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>">
    <input type="hidden" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>">
    <input type="hidden" name="category" value="<?php echo esc_attr($_GET['category'] ?? ''); ?>">
    <button type="submit" class="button button-primary">📄 পিডিএফ ডাউনলোড</button>
</form>
<?php endif; ?>
    <!-- Expenses Table -->
    <table class="capital-table">
        <thead>
            <tr>
                <th>Amount</th>
                <th>Category</th>
                <th>Description</th>
                <th>Voucher</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        global $wpdb;
        $table = $wpdb->prefix . 'wppt_expenses';

        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $where .= " AND date BETWEEN %s AND %s";
            $params[] = sanitize_text_field($_GET['start_date']);
            $params[] = sanitize_text_field($_GET['end_date']);
        }

        if (!empty($_GET['category'])) {
            $category_name = sanitize_text_field($_GET['category']);
            $where .= " AND category = %s";
            $params[] = $category_name;
        }

        $query = $wpdb->prepare("SELECT * FROM $table $where ORDER BY date DESC", $params);
        $results = $wpdb->get_results($query);

        $total_amount = 0;
        foreach ($results as $row) {
            $total_amount += $row->amount;
            echo "<tr>
                <td>{$row->amount}</td>
                <td>{$row->category}</td>
                <td>{$row->description}</td>
                <td>{$row->voucher_name}</td>
                <td>{$row->date}</td>
                <td>
                    <a href='?page=wppt-expenses&edit={$row->id}'>Edit</a> |
                    <a href='?page=wppt-expenses&delete={$row->id}' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                </td>
            </tr>";
        }
        ?>
        </tbody>
    </table>

    <h3>Total Expenses: <?php echo wc_price($total_amount); ?></h3>

    <h2>Expense by Category (Date Filtered)</h2>
    <canvas id="expenseCategoryChart" width="400" height="300"></canvas>

    <?php
    // Prepare Category Chart Data
    $cat_where = 'WHERE 1=1';
    $cat_params = [];

    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $cat_where .= " AND date BETWEEN %s AND %s";
        $cat_params[] = sanitize_text_field($_GET['start_date']);
        $cat_params[] = sanitize_text_field($_GET['end_date']);
    }

    if (!empty($_GET['category'])) {
        $category_name = sanitize_text_field($_GET['category']);
        $cat_where .= " AND category = %s";
        $cat_params[] = $category_name;
    }

    $category_data_query = $wpdb->prepare("SELECT category, SUM(amount) as total FROM $table $cat_where GROUP BY category", $cat_params);
    $category_data = $wpdb->get_results($category_data_query);

    $categories = [];
    $amounts = [];

    foreach ($category_data as $row) {
        $categories[] = $row->category;
        $amounts[] = floatval($row->total);
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('expenseCategoryChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    label: 'Expense by Category',
                    data: <?php echo json_encode($amounts); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#C9CBCF', '#8B5CF6'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

</div>

<style type="text/css">
    /* Background for the whole wrap */
.wrap {
    background: linear-gradient(to right, #f8f9fa, #e9ecef);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    max-width: 1000px;
}

/* Title styling */
.wrap h1, .wrap h2 {
    color: #222;
    font-weight: 700;
    margin-bottom: 20px;
}

/* Form container */
form {
    background: #ffffff;
    padding: 20px 25px;
    margin-bottom: 30px;
    border-radius: 10px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
}

/* Table form fields */
.form-table th {
    text-align: left;
    padding-bottom: 10px;
    color: #333;
    font-weight: 600;
}

.form-table td {
    padding-bottom: 15px;
}

.form-table input[type="text"],
.form-table input[type="number"],
.form-table input[type="date"],
.form-table select,
.form-table textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #f8f8f8;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    font-size: 14px;
}

.form-table input:focus,
.form-table textarea:focus,
.form-table select:focus {
    border-color: #0073aa;
    box-shadow: 0 0 5px rgba(0,115,170,0.5);
    background: #fff;
}

/* Submit Button Stylish (3D Look) */
button.button,
input[type="submit"].button,
input[type="submit"] {
    background: linear-gradient(to bottom, #28a745, #218838);
    color: white;
    padding: 10px 20px;
    border: none;
    font-weight: bold;
    border-radius: 6px;
    box-shadow: 0 5px 0 #1e7e34;
    transition: 0.2s ease-in-out;
    cursor: pointer;
}

input[type="submit"]:hover {
    background: linear-gradient(to bottom, #218838, #1c7430);
    box-shadow: 0 3px 0 #145c2c;
    transform: translateY(2px);
}

/* Expenses List Table */
.widefat.striped {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
}

.widefat.striped th {
    background: #0073aa;
    color: white;
    padding: 12px;
    font-weight: 600;
    font-size: 14px;
}

.widefat.striped td {
    padding: 10px 12px;
    border-bottom: 1px solid #eaeaea;
    font-size: 13px;
}

.widefat.striped tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

/* Action links */
.widefat.striped a {
    color: #0073aa;
    text-decoration: none;
    font-weight: bold;
}

.widefat.striped a:hover {
    color: #005177;
}

/* Filter form */
form input[type="date"] {
    background: #e2f0ff;
    border: 1px solid #ccc;
    border-radius: 5px;
    padding: 8px;
    margin-right: 10px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

/* Colorful Filter Submit Button */
form input[type="submit"] {
    background: linear-gradient(to bottom, #ff9800, #f57c00);
    box-shadow: 0 5px 0 #e65100;
    border-radius: 6px;
    color: #fff;
    padding: 8px 16px;
    font-weight: bold;
}

form input[type="submit"]:hover {
    background: linear-gradient(to bottom, #f57c00, #ef6c00);
    transform: translateY(1px);
}

/* Chart canvas spacing */
#expenseCategoryChart {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.05);
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