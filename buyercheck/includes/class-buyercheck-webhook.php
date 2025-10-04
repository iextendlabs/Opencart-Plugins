<?php

class BuyerCheck_Webhook {

    private static $webhook_secret;
    private static $webhook_url;

    public static function init() {
        // Generate a unique webhook secret if not already set
        self::$webhook_secret = get_option('buyercheck_webhook_secret');
        if (!self::$webhook_secret) {
            self::$webhook_secret = wp_generate_password(32, false);
            update_option('buyercheck_webhook_secret', self::$webhook_secret);
        }

        // Generate a unique webhook URL if not already set
        self::$webhook_url = get_option('buyercheck_webhook_url');
        if (!self::$webhook_url) {
            self::$webhook_url = wp_generate_password(16, false);
            update_option('buyercheck_webhook_url', self::$webhook_url);
        }

        // Generate a unique webhook prefix if not already set
        $webhook_prefix = get_option('buyercheck_webhook_prefix');
        if (!$webhook_prefix) {
            $webhook_prefix = wp_generate_password(8, false);
            update_option('buyercheck_webhook_prefix', $webhook_prefix);
        }

        // Register webhook endpoint with unique prefix
        add_action('rest_api_init', function () use ($webhook_prefix) {
            register_rest_route($webhook_prefix . '/v1', '/' . self::$webhook_url, array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'handle_webhook'),
                'permission_callback' => array(__CLASS__, 'verify_request_permissions'),
            ));

            // Register /capture endpoint
            register_rest_route($webhook_prefix . '/v1', '/capture', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'handle_capture'),
                'permission_callback' => function () {            
                    $headers = getallheaders();
                    $nonce = isset($headers['X-WP-Nonce']) ? $headers['X-WP-Nonce'] : (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '');
            
                    if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
                        return true;
                    }
            
                    return new WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
                },
            ));
        });
    }

    public static function verify_request_permissions($request) {

        // Verify timestamp
        $timestamp = $request->get_header('x-buyercheck-timestamp');
        if (!$timestamp || abs(time() - (int) $timestamp) > 300) { // 5 minute window
            //return false;
            return new WP_Error('invalid_timestamp', 'Timestamp is invalid or expired', array('status' => 403));
        }

        $raw_body = file_get_contents('php://input');
        $params = json_decode($raw_body, true);

        // Verify signature
        $received_signature = $request->get_header('x-buyercheck-signature');
        $expected_signature = hash_hmac('sha256', $raw_body, self::$webhook_secret);
        if (!$received_signature || !hash_equals($expected_signature, $received_signature)) {
            //return false;
            return new WP_Error('invalid_signature', 'Signature is invalid', array('status' => 403));
        }

        // Verify the request has orders param
        if (!isset($params['orders']) || !is_array($params['orders'])) {
            //return false;
            return new WP_Error('invalid_orders', 'Orders are invalid', array('status' => 403));
        }

        return true;
    }

    public static function handle_webhook($request) {
        $raw_body = file_get_contents('php://input');
        $params = json_decode($raw_body, true);

        // Process orders
        if (isset($params['orders']) && is_array($params['orders'])) {

            foreach ($params['orders'] as $order_received) {
                $order = wc_get_order($order_received['order_id']);

                // Store response data as order meta
                if (isset($order_received['risk_score'])) {
                    $order->update_meta_data('risk_score', $order_received['risk_score']);
                }

                if (isset($order_received['recommended_action'])) {
                    $order->update_meta_data('recommended_action', $order_received['recommended_action']);
                }

                if (isset($order_received['risk_details_external'])) {
                    $order->update_meta_data('risk_details', $order_received['risk_details_external']);
                }
                
                if ($order_received['order_status'] != 'pending') {
                    $order->update_meta_data('buyercheck-status', 'buyercheck-finalized');
                }

                if (isset($order_received['calculated_at'])) {
                    $order->update_meta_data('calculated_at', $order_received['calculated_at']);
                }

                $order->save();
            }
        }

        return new WP_REST_Response('Orders processed', 200);
    }

    public static function handle_capture($request) {
        $api_email = get_option('buyercheck_email');
        $api_key = get_option('buyercheck_api_key');
        $risky_action = get_option('buyercheck_risky_action', 'do_nothing');
        $params = $request->get_json_params();

        // If buyercheck status is not activated, return
        if (get_option('buyercheck_subscription_status') != 'active') {
            return new WP_REST_Response('Subscription not active', 401);
        }

        if ($risky_action != 'disable_cod') {
            return new WP_REST_Response('No action required', 200);
        }
        
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : false;
        $cart_total = isset($params['amount']) ? floatval($params['amount']) : 0;

        // get request ip address
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        // Generate a unique key for the transient based on the email and IP
        $transient_key = 'buyercheck_' . md5($email . $ip_address);

        // Check if the transient exists
        $cached_response = get_transient($transient_key);
        if ($cached_response !== false) {
            return new WP_REST_Response($cached_response, 200);
        }

        $raw_data_consent = get_option('buyercheck_raw_data_consent', 0);

        // Prepare data for API
        $data = array(
            'api_user' => $api_email,
            'store_id' => preg_replace("(^https?://)", "", site_url()),
            'email' => $raw_data_consent ? $email : hash('sha256', $email),
            'amount' => $cart_total,
            'ip_hash' => hash('sha256', $ip_address)
        );

        if ($phone) {
            $data['phone'] = $raw_data_consent ? $phone : hash('sha256', $phone);
        }

        // Send data to BuyerCheck API
        $response = wp_remote_post('https://api.buyercheck.bg/check-risk', array(
            'headers' => array(
                'X-Buyercheck-Key' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data)
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Failed to connect to BuyerCheck API', array('status' => 500));
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        $response = ['hide_cod' => false];

        if (strtolower($body->recommended_action) === 'cancel' && $risky_action === 'disable_cod') {
            // Hide COD option if risk level is high
            $response['hide_cod'] = true;
        }

        // Store the response in a transient for 30 minutes
        set_transient($transient_key, $response, 900);

        return new WP_REST_Response($response, 200);
    }
}

?>