<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;


class BuyerCheck_Settings {

    public static function init() {
        // Add settings menu
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_post_buyercheck_save_settings', array(__CLASS__, 'handle_settings_save'));
        add_action('add_meta_boxes', 'add_risk_check_meta_box');
    }

    public static function add_settings_page() {
        add_options_page(
            'BuyerCheck Settings',
            'BuyerCheck',
            'manage_options',
            'buyercheck-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function handle_settings_save() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('buyercheck_settings');

        $email = sanitize_email($_POST['buyercheck_email'] ?? '');
        $api_key = sanitize_text_field($_POST['buyercheck_api_key'] ?? '');
        $store_category = sanitize_text_field($_POST['buyercheck_store_category'] ?? '');
        $risky_action = sanitize_text_field($_POST['buyercheck_risky_action'] ?? '');
        $raw_data_consent = sanitize_text_field($_POST['buyercheck_raw_data_consent'] ?? 0);

        if (empty($email) || empty($api_key) || empty($store_category)) {
            set_transient('buyercheck_settings_error', 'Please fill in all fields.', 10);
            wp_redirect(admin_url('admin.php?page=buyercheck-settings'));
            exit;
        }

        $webhook_secret = get_option('buyercheck_webhook_secret');
        $webhook_url = get_option('buyercheck_webhook_url');
        $webhook_prefix = get_option('buyercheck_webhook_prefix');

        $data = array(
            'api_user' => $email,
            'domain' => $_SERVER['HTTP_HOST'],
            'category' => $store_category,
            'webhook_secret' => $webhook_secret,
            'webhook_url' => home_url('/wp-json/' . $webhook_prefix . '/v1/' . $webhook_url),
            'raw_data_consent' => (bool) $raw_data_consent
        );

        $response = wp_remote_post('https://api.buyercheck.bg/onboard-store', array(
            'headers' => array(
                'X-Buyercheck-Key' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data)
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('BuyerCheck API validation failed: ' . print_r($response, true));
            $body = json_decode(wp_remote_retrieve_body($response));
            set_transient('buyercheck_settings_error', 'BuyerCheck API validation failed with error: ' . $body->error, 10);
            wp_redirect(admin_url('admin.php?page=buyercheck-settings'));
            exit;
        }

        update_option('buyercheck_email', $email);
        update_option('buyercheck_api_key', $api_key);
        update_option('buyercheck_store_category', $store_category);
        update_option('buyercheck_risky_action', $risky_action);
        update_option('buyercheck_raw_data_consent', $raw_data_consent);
        $body = json_decode(wp_remote_retrieve_body($response));
        update_option('buyercheck_subscription_status', $body->subscription_status);

        set_transient('buyercheck_settings_success', 'Settings saved and validated successfully.', 10);
        wp_redirect(admin_url('admin.php?page=buyercheck-settings'));
        exit;
    }

    public static function render_settings_page() {
        $api_key = get_option('buyercheck_api_key');
        $email = get_option('buyercheck_email');
        $risky_action = get_option('buyercheck_risky_action', 'do_nothing');
        $raw_data_consent = get_option('buyercheck_raw_data_consent', 0);
        ?>
        <div class="wrap">
            <h1>BuyerCheck Settings</h1>
            <?php if ($error = get_transient('buyercheck_settings_error')) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($error); ?></p>
                </div>
                <?php delete_transient('buyercheck_settings_error'); ?>
            <?php endif; ?>
            <?php if ($success = get_transient('buyercheck_settings_success')) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($success); ?></p>
                </div>
                <?php delete_transient('buyercheck_settings_success'); ?>
            <?php endif; ?>
            <?php if (empty($api_key)) : ?>
                <div class="notice notice-warning">
                    <p>Please enter your email and API key for BuyerCheck below. If you don't have a key yet, go to <a href="https://buyercheck.bg" target="_blank">BuyerCheck</a>.</p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('buyercheck_settings'); ?>
                <input type="hidden" name="action" value="buyercheck_save_settings">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Email</th>
                        <td><input type="email" name="buyercheck_email" value="<?php echo esc_attr($email); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">API Key</th>
                        <td><input type="password" name="buyercheck_api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Store Category</th>
                        <td>
                            <select name="buyercheck_store_category">
                                <?php self::render_store_category_options(); ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Raw Data Consent</th>
                        <td>
                            <label><input type="checkbox" name="buyercheck_raw_data_consent" value="1" <?php checked('1', $raw_data_consent); ?> /> Enable raw data consent</label>
                            <p class="description">Raw data consent enhances the accuracy of our reports.</p>
                        </td>
                    </tr>
                    <?php if (!empty($api_key) && !empty($email)) : ?>
                    <tr valign="top">
                        <th scope="row">Default Action if client is very risky:</th>
                        <td>
                            <label><input type="radio" name="buyercheck_risky_action" value="do_nothing" <?php checked('do_nothing', $risky_action); ?> /> Do nothing</label><br>
                            <label><input type="radio" name="buyercheck_risky_action" value="disable_cod" <?php checked('disable_cod', $risky_action); ?> /> Disable COD</label>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function render_store_category_options() {
        // Fetch categories from custom API
        $categories = self::fetch_store_categories();
        $selected_category = get_option('buyercheck_store_category');

        if ($categories) {
            foreach ($categories as $key => $name) {
                $selected = ($key == $selected_category) ? 'selected' : '';
                echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($name) . "</option>";
            }
        }
    }

    private static function fetch_store_categories() {
        $response = wp_remote_get('https://api.buyercheck.bg/onboard-store');

        if (is_wp_error($response)) {
            error_log('Failed to fetch store categories: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['categories']) && is_array($data['categories'])) {
            return $data['categories'];
        }

        return [];
    }
}

function add_risk_check_meta_box() {
    $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';

    add_meta_box('risk_check_meta_box', 'BuyerCheck Risk Analysis', 'display_risk_check_meta_box', $screen, 'side', 'default');
}

function translate_risk_details($details) {
    $translations = [
        "High value order (>5000)" => "Висока стойност на поръчката (>5000)",
        "New customer - cannot calculate risk score" => "Нов клиент - недостатъчно данни за изчисляване на риск",
        "High success chance (>90%)" => "Висока вероятност за успех (>90%)",
        "Frequent shopper" => "Редовен клиент",
        "Very low success chance (<40%)" => "Много ниска вероятност за успех (<40%)",
        "Medium success chance (<70%)" => "Средна вероятност за успех (<70%)",
        "Possible success chance (<90%)" => "Възможна вероятност за успех (<90%)",
        "Low cancel rate (<10%)" => "Малка вероятност за неуспех (<10%)",
        "High cancellation chance in similar store category" => "Висока степен на отказани поръчки в подобна категория магазин",
        "New IP location" => "Необичайно местонахождение",
        "New unusual device" => "Подозрително устройство за поръчка",
        "Failed orders in your store" => "Имал е неуспешни поръчки във вашия магазин и преди",
        "Error calculating risk score" => "Грешка при изчисляване на риска",
        "Suspicious spending activity" => "Малко подозрителна активност на харчене",
        "Super suspicious spending activity" => "Високо подозрителна активност на харчене",
        "Extremely suspicious spending activity" => "Много подозрителна активност на харчене",
    ];

    // Split the details by the separator and translate each part
    $parts = explode(" | ", $details);
    $translated_parts = array_map(function($part) use ($translations) {
        if (isset($translations[$part])) {
            return $translations[$part];
        } else {
            $part = str_replace('scam reports detected in other data sources', 'засечени оплаквания в други източници на данни', $part);
            $part = str_replace('scam reports detected in public data sources', 'засечени оплаквания в публични източници на данни', $part);
            return $part;
        }
    }, $parts);

    return implode(" | ", $translated_parts);
}

function display_risk_check_meta_box($post) {
    $order = wc_get_order($post->ID);
    $buyercheck_status = $order->get_meta('buyercheck-status');
    $risk_score = $order->get_meta('risk_score');
    $recommended_action = $order->get_meta('recommended_action');
    $risk_details = $order->get_meta('risk_details');
    $calculated_at = $order->get_meta('calculated_at');

    $risk_score_label = 'Success Chance (0-100)';
    $buyercheck_status_label = 'BuyerCheck Order Status';
    $buyercheck_status_map = [
        'buyercheck-processing' => 'Processing',
        'buyercheck-finalized' => 'Finalized',
    ];
    $recommended_action_label = 'Recommended Action';
    $risk_details_label = 'Risk Details';
    $calculated_at_label = 'Calculated At';

    // Get current language
    $current_language = get_locale();
    
    // Translate risk details if needed
    if ($current_language === 'bg_BG') {
        $risk_details = translate_risk_details($risk_details);
        $risk_score_label = 'Шанс за успех (0-100)';
        $recommended_action_label = 'Препоръчано действие';
        $risk_details_label = 'Детайли на риска';
        $calculated_at_label = 'Изчислено на';
        $buyercheck_status_label = 'Статус на проверката';
        $buyercheck_status_map = [
            'buyercheck-processing' => 'Проверява се',
            'buyercheck-finalized' => 'Завършена',
        ];

        $translations = [
            'Process' => 'Изпълни',
            'Verify' => 'Провери',
            'Cancel' => 'Откажи',
        ];

        $recommended_action = $translations[$recommended_action] ?? $recommended_action;
    }

    // If we have a buyercheck report and the order is still not finalized, show the user the report is ready
    if ($recommended_action && $buyercheck_status == 'buyercheck-processing') {
        $buyercheck_status = 'buyercheck-finalized';
    }

    echo '<p><strong>' . esc_html($buyercheck_status_label) . ':</strong> ' . esc_html($buyercheck_status_map[$buyercheck_status]) . '</p>';
    echo '<p><strong>' . esc_html($risk_score_label) . ':</strong> ' . esc_html($risk_score) . '</p>';
    echo '<p><strong>' . esc_html($recommended_action_label) . ':</strong> ' . esc_html($recommended_action) . '</p>';
    echo '<p><strong>' . esc_html($risk_details_label) . ':</strong> ' . esc_html($risk_details) . '</p>';
    // Convert UTC timestamp to user's local timezone
    $utc_timestamp = $calculated_at;
    $user_timezone = wp_timezone();
    $user_datetime = new DateTime($utc_timestamp, new DateTimeZone('UTC'));
    $user_datetime->setTimezone($user_timezone);
    $formatted_time = $user_datetime->format('Y-m-d H:i:s T');
    
    echo '<p><strong>' . esc_html($calculated_at_label) . ':</strong> ' . esc_html($formatted_time) . '</p>';
    }
?>