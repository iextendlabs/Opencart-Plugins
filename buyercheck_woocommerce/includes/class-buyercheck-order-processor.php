<?php

class BuyerCheck_Order_Processor {

    public static function init() {
        // Register cron job action
        add_action('buyercheck_cron_job', array(__CLASS__, 'process_orders'));
        add_action('woocommerce_new_order', array(__CLASS__, 'new_order_check'), 10, 1);
    }

    public static function map_order_status($status) {
        switch ($status) {
            case 'failed':
                return 'cancelled';
            case 'cancelled':
                return 'cancelled';
            case 'completed':
                return 'completed';
            case 'refunded':
                return 'cancelled';
            
            default:
                return 'pending';
        }
    }

    public static function new_order_check($order_id) {
        
        $order = wc_get_order($order_id);
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();

        // Check raw data consent setting
        $raw_data_consent = get_option('buyercheck_raw_data_consent', 0);
        $ip_address = $order->get_customer_ip_address();
        $ip_hash = hash('sha256', $ip_address);
        $order_status = self::map_order_status($order->get_status());

        // Prepare data for API
        $order_data = array(
            'order_id' => $order_id,
            'ip_hash' => $ip_hash,
            'amount' => floatval($order->get_total()),
            'pending_amount' => $order_status === 'pending' ? floatval($order->get_total()) : 0,
            'order_status' => $order_status,
            'created_at' => $order->get_date_created()->date('Y-m-d H:i:s')
        );

        // Add email and phone based on consent
        if ($raw_data_consent) {
            $order_data['email'] = $email;
            if (!empty($phone)) {
                $order_data['phone'] = $phone;
            }
        } else {
            $order_data['email'] = hash('sha256', $email);
            if (!empty($phone)) {
                $order_data['phone'] = hash('sha256', $phone);
            }
        }

        $order->update_meta_data('buyercheck-status', 'buyercheck-processing');
        $order->save();

        // Send batch of orders to custom API
        self::send_to_api(['orders' => [$order_data]]);
    }

    public static function process_orders() {
        // Check if plugin settings are configured
        $api_key = get_option('buyercheck_api_key');
        $email = get_option('buyercheck_email');
        $subscription_status = get_option('buyercheck_subscription_status');

        if (empty($api_key) || empty($email) || $subscription_status !== 'active') {
            error_log('BuyerCheck order processor: Subscription not active');
            return; // Exit if settings are not configured
        }

        $batch_size = 25; // Number of orders per batch
        $paged = 1; // Start with the first page

        do {
            // Get a batch of orders that don't have the 'buyercheck-status' meta field set to 'buyercheck-finalized'
            $args = array(
                'status' => 'any',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'buyercheck-status',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'buyercheck-status',
                        'value' => 'buyercheck-processing',
                        'compare' => '='
                    )
                ),
                'limit' => $batch_size,
                'paged' => $paged
            );

            $orders = wc_get_orders($args);
            $order_data = [];

            foreach ($orders as $order) {
                $order_id = $order->get_id();
                $email = $order->get_billing_email();
                $phone = $order->get_billing_phone();
                $ip_address = $order->get_customer_ip_address();

                // Check raw data consent setting
                $raw_data_consent = get_option('buyercheck_raw_data_consent', 0);
                $ip_hash = hash('sha256', $ip_address);
                $order_status = self::map_order_status($order->get_status());

                // Prepare data for API
                $data = array(
                    'order_id' => $order_id,
                    'amount' => floatval($order->get_total()),
                    'pending_amount' => $order_status === 'pending' ? floatval($order->get_total()) : 0,
                    'order_status' => $order_status,
                    'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'ip_hash' => $ip_hash
                );

                // Add email and phone based on consent
                if ($raw_data_consent) {
                    $data['email'] = $email;
                    if (!empty($phone)) {
                        $data['phone'] = $phone;
                    }
                } else {
                    $data['email'] = hash('sha256', $email);
                    if (!empty($phone)) {
                        $data['phone'] = hash('sha256', $phone);
                    }
                }

                $order_data[] = $data;

                // Mark the order as processing
                $order->update_meta_data('buyercheck-status', 'buyercheck-processing');
                $order->save();
            }

            // Send batch of orders to custom API
            if (!empty($order_data)) {
                self::send_to_api(['orders' => $order_data]);
            }

            $paged++;
        } while (count($orders) === $batch_size); // Continue if the batch is full
    }

    private static function send_to_api($data) {
        $api_key = get_option('buyercheck_api_key');
        $api_email = get_option('buyercheck_email');

        if (empty($api_key) || empty($api_email)) {
            return; // Exit if settings are not configured
        }

        $data['api_user'] = $api_email;
        $data['store_id'] = preg_replace("(^https?://)", "", site_url());

        $response = wp_remote_post('https://api.buyercheck.bg/submit-order-data', array(
            'headers' => array(
                'X-Buyercheck-Key' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data)
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response));
            error_log('BuyerCheck API request failed: ' . $body->error);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);

        // Check for subscription status
        if (isset($response_data['error']) && $response_data['error'] === 'subscription_expired') {
            update_option('buyercheck_subscription_status', 'inactive');
        }
    }
}

?>