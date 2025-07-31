<?php
if (!current_user_can('manage_options')) return;

$token = get_option('wppt_facebook_access_token');
$account_id = get_option('wppt_facebook_ad_account_id');

$spend_result = null;

if (isset($_POST['fb_check_spend'])) {
    $token = sanitize_text_field($_POST['facebook_token']);
    $account_id = sanitize_text_field($_POST['facebook_ad_account_id']);

    // Save to DB
    update_option('wppt_facebook_access_token', $token);
    update_option('wppt_facebook_ad_account_id', $account_id);

    // API Call
    $url = "https://graph.facebook.com/v19.0/act_{$account_id}/insights?fields=spend&date_preset=today&access_token={$token}";
    $response = wp_remote_get($url);
    
    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $spend_result = $body['data'][0]['spend'] ?? 'N/A';
    } else {
        $spend_result = 'API error: ' . $response->get_error_message();
    }
}
?>

<div class="wrap">
    <h1>Facebook Ad Spend Tester</h1>

    <form method="post">
        <table class="form-table">
            <tr>
                <th><label for="facebook_token">Facebook Access Token</label></th>
                <td><textarea name="facebook_token" rows="3" style="width:100%" required><?php echo esc_textarea($token); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="facebook_ad_account_id">Ad Account ID</label></th>
                <td><input type="text" name="facebook_ad_account_id" value="<?php echo esc_attr($account_id); ?>" required></td>
            </tr>
        </table>

        <?php submit_button('Check Today\'s Ad Spend', 'primary', 'fb_check_spend'); ?>
    </form>

    <?php if ($spend_result !== null): ?>
        <h2>আজকের Facebook খরচ: <strong><?php echo is_numeric($spend_result) ? '$' . $spend_result : $spend_result; ?></strong></h2>
    <?php endif; ?>
</div>