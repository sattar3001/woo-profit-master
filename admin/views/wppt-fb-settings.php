<?php
if (!defined('ABSPATH')) exit;

// Save options when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wppt_fb_settings_nonce']) && wp_verify_nonce($_POST['wppt_fb_settings_nonce'], 'wppt_fb_settings_action')) {
    update_option('wppt_fb_access_token', sanitize_text_field($_POST['access_token']));
    update_option('wppt_fb_ad_account_id', sanitize_text_field($_POST['ad_account_id']));
    update_option('wppt_usd_to_bdt_rate', floatval($_POST['usd_to_bdt_rate']));

    echo '<div class="updated notice is-dismissible"><p>সেটিংস সংরক্ষণ করা হয়েছে।</p></div>';
}

// Load saved values
$access_token = get_option('wppt_fb_access_token', '');
$ad_account_id = get_option('wppt_fb_ad_account_id', '');
$usd_to_bdt = get_option('wppt_usd_to_bdt_rate', 110);
?>

<div class="wrap">
    <h1>Facebook Ads API সেটিংস</h1>
    <p>এই অপশনগুলো ব্যবহার করে আপনি ফেইসবুক এড একাউন্ট থেকে প্রতিদিনের খরচ অটো সিঙ্ক করতে পারবেন।</p>

    <form method="post" action="">
        <?php wp_nonce_field('wppt_fb_settings_action', 'wppt_fb_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="access_token">Access Token</label></th>
                <td><input type="text" name="access_token" id="access_token" class="regular-text" value="<?php echo esc_attr($access_token); ?>" required></td>
            </tr>

            <tr>
                <th><label for="ad_account_id">Ad Account ID</label></th>
                <td><input type="text" name="ad_account_id" id="ad_account_id" class="regular-text" value="<?php echo esc_attr($ad_account_id); ?>" required></td>
            </tr>

            <tr>
                <th><label for="usd_to_bdt_rate">ডলার থেকে টাকার রেট</label></th>
                <td><input type="number" step="0.01" name="usd_to_bdt_rate" id="usd_to_bdt_rate" class="regular-text" value="<?php echo esc_attr($usd_to_bdt); ?>" required></td>
            </tr>
        </table>

        <?php submit_button('সেভ করুন'); ?>
    </form>
</div>

<style>
    .wrap h1 {
        color: #1d2327;
        font-size: 24px;
    }

    table.form-table th {
        width: 200px;
        font-weight: 600;
        color: #222;
    }

    table.form-table td input {
        width: 400px;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background: #f9f9f9;
        font-size: 14px;
    }

    input[type="submit"] {
        background: linear-gradient(to right, #0073aa, #005177);
        border: none;
        color: white;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 6px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: background 0.3s ease;
    }

    input[type="submit"]:hover {
        background: linear-gradient(to right, #005177, #003f5c);
    }
</style>
