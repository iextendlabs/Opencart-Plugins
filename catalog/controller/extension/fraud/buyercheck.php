<?php
class ControllerExtensionFraudBuyercheck extends Controller {
    public function webhook() {
        $this->log(['message' => 'Webhook called.'], 'webhook');

        $webhook_secret = $this->config->get('fraud_buyercheck_webhook_secret');
        $webhook_url_path = $this->config->get('fraud_buyercheck_webhook_url');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->get['path']) && ($this->request->get['path'] == $webhook_url_path)) {
            $timestamp = $this->request->server['HTTP_X_BUYERCHECK_TIMESTAMP'] ?? null;
            if (!$timestamp || abs(time() - (int) $timestamp) > 300) { // 5 minute window
                $this->log(['message' => 'Timestamp is invalid or expired.'], 'webhook');
                $this->response->addHeader('HTTP/1.0 403 Forbidden');
                $this->response->setOutput('Timestamp is invalid or expired');
                return;
            }

            $raw_body = file_get_contents('php://input');
            $received_signature = $this->request->server['HTTP_X_BUYERCHECK_SIGNATURE'] ?? null;
            $expected_signature = hash_hmac('sha256', $raw_body, $webhook_secret);
            if (!$received_signature || !hash_equals($expected_signature, $received_signature)) {
                $this->log(['message' => 'Signature is invalid.'], 'webhook');
                $this->response->addHeader('HTTP/1.0 403 Forbidden');
                $this->response->setOutput('Signature is invalid');
                return;
            }

            $params = json_decode($raw_body, true);
            $this->log(['message' => 'Webhook payload received.', 'payload' => $params], 'webhook');

            if (!isset($params['orders']) || !is_array($params['orders'])) {
                $this->log(['message' => 'Invalid orders data in payload.'], 'webhook');
                $this->response->addHeader('HTTP/1.0 400 Bad Request');
                $this->response->setOutput('Invalid orders data');
                return;
            }

            $this->load->model('extension/fraud/buyercheck');
            foreach ($params['orders'] as $order_received) {
                if (!empty($order_received['order_id'])) {
                    $this->log(['message' => 'Updating order data.', 'order_data' => $order_received], 'webhook');
                    $this->model_extension_fraud_buyercheck->updateOrderData($order_received);
                }
            }

            $this->log(['message' => 'Webhook processed successfully.'], 'webhook');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['status' => 'success', 'message' => 'Orders processed']));
        } else {
            $this->log(['message' => 'Invalid request to webhook.'], 'webhook');
            $this->response->addHeader('HTTP/1.0 403 Forbidden');
            $this->response->setOutput('Invalid request');
        }
    }

    public function check_risk() {
        $this->log(['message' => 'check_risk called.'], 'check_risk');

        $json = [];

        $api_email = $this->config->get('fraud_buyercheck_email');
        $api_key = $this->config->get('fraud_buyercheck_api_key');
        $risky_action = $this->config->get('fraud_buyercheck_risky_action');
        $subscription_status = $this->config->get('fraud_buyercheck_subscription_status');

        if ($subscription_status != 'active' || $risky_action != 'disable_cod' || !$api_key || !$api_email) {
            $this->log(['message' => 'Conditions not met for risk check.', 'subscription_status' => $subscription_status, 'risky_action' => $risky_action], 'check_risk');
            $json['hide_cod'] = false;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $params = json_decode(file_get_contents('php://input'), true);

        $email = isset($params['email']) ? filter_var($params['email'], FILTER_SANITIZE_EMAIL) : '';
        $phone = isset($params['phone']) ? preg_replace('/[^0-9+]/ ', '', $params['phone']) : '';
        $cart_total = $this->cart->getTotal();
        $ip_address = $this->request->server['REMOTE_ADDR'];

        if ((!$email && !$phone) || $cart_total <= 0) {
            $this->log(['message' => 'Email/phone or cart total is empty, skipping risk check.'], 'check_risk');
            $json['hide_cod'] = false;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $raw_data_consent = $this->config->get('fraud_buyercheck_raw_data_consent');

        $data = array(
            'api_user' => $api_email,
            'store_id' => $this->getStoreUrl(),
            'email' => $raw_data_consent ? $email : hash('sha256', $email),
            'amount' => $cart_total,
            'ip_hash' => hash('sha256', $ip_address)
        );

        if ($phone) {
            $data['phone'] = $raw_data_consent ? $phone : hash('sha256', $phone);
        }

        $url = 'https://api.buyercheck.bg/check-risk';
        $this->log(['Request URL' => $url, 'Request Type' => 'POST', 'Request Body' => $data], 'check_risk Request');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Buyercheck-Key: ' . $api_key,
            'Content-Type: application/json',
            'User-Agent: OpenCart/' . VERSION . ' BuyerCheck/1.0'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'check_risk Response');

        $json['hide_cod'] = false;
        if ($http_code == 200 && $response) {
            $body = json_decode($response, true);
            if (isset($body['recommended_action']) && strtolower($body['recommended_action']) === 'cancel') {
                $this->log(['message' => 'High risk detected, hiding COD.'], 'check_risk');
                $json['hide_cod'] = true;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function log($data = array(), $title = '') {
        if ($this->config->get('fraud_buyercheck_logging')) {
            $log = new Log('buyercheck.log');
            $log->write('Catalog (' . $title . '): \n' . json_encode($data, JSON_PRETTY_PRINT));
        }
    }


    public function prepareAndCheckRisk(&$route, &$args, &$output) {
        $this->log(['message' => 'prepareAndCheckRisk event triggered'], 'prepareAndCheckRisk');

        if (!$this->config->get('fraud_buyercheck_status')) {
            $this->log(['message' => 'fraud_buyercheck_status is not enabled'], 'prepareAndCheckRisk');
            return;
        }
        
        if ($this->config->get('fraud_buyercheck_subscription_status') != 'active') {
            $this->log(['message' => 'fraud_buyercheck_subscription_status is not active'], 'prepareAndCheckRisk');
            return;
        }
        
        $order_id = $args[0] ?? null;
        
        if (!$order_id) {
            $this->log(['message' => 'order_id is not set'], 'prepareAndCheckRisk');
            return;
        }
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        
        if (!$order_info) {
            $this->log(['message' => 'order_info is not set for order_id: ' . $order_id], 'prepareAndCheckRisk');
            return;
        }

        $this->load->model('extension/fraud/buyercheck');
        $this->model_extension_fraud_buyercheck->addOrder($order_id);
        
        $order_data = array(
            'order_id'      => $order_id,
            'email'         => $order_info['email'],
            'telephone'     => $order_info['telephone'],
            'total'         => $order_info['total'],
            'currency_code' => $order_info['currency_code'],
            'ip'            => $order_info['ip'],
            'date_added'    => $order_info['date_added']
        );
        
        $this->submitOrderToAPI($order_data, $order_info['order_status_id']);
    }

    public function submitOrderToAPI($order_data, $order_status_id) {
        $api_email = $this->config->get('fraud_buyercheck_email');
        $api_key = $this->config->get('fraud_buyercheck_api_key');
        $raw_data_consent = $this->config->get('fraud_buyercheck_raw_data_consent');
        $order_status = $this->mapOrderStatus($order_status_id);
        
        $data = array(
            'api_user' => $api_email,
            'store_id' => $this->getStoreUrl(),
            'orders' => [
                [
                    'order_id' => $order_data['order_id'],
                    'ip_hash' => hash('sha256', $order_data['ip']),
                    'amount' => floatval($order_data['total']),
                    'pending_amount' => $order_status == 'pending' ? floatval($order_data['total']) : 0,
                    'order_status' => $order_status,
                    'created_at' => $order_data['date_added']
                ]
            ]
        );
        
        if ($raw_data_consent) {
            $data['orders'][0]['email'] = $order_data['email'];
            if (!empty($order_data['telephone'])) {
                $data['orders'][0]['phone'] = $order_data['telephone'];
            }
        } else {
            $data['orders'][0]['email'] = hash('sha256', $order_data['email']);
            if (!empty($order_data['telephone'])) {
                $data['orders'][0]['phone'] = hash('sha256', $order_data['telephone']);
            }
        }
        
        $url = 'https://api.buyercheck.bg/submit-order-data';
        $this->log(['Request URL' => $url, 'Request Type' => 'POST', 'Request Body' => $data], 'submitOrderToAPI Request');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Buyercheck-Key: ' . $api_key,
            'Content-Type: application/json',
            'User-Agent: OpenCart/' . VERSION . ' BuyerCheck/1.0'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'submitOrderToAPI Response');
            $result = json_decode($response, true);
            return $result;
        }
        
        $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'submitOrderToAPI Response');
        return false;
    }

    private function getStoreUrl() {
        return preg_replace("(^https?://)", "", rtrim($this->config->get('config_url'), '/'));
    }

    private function mapOrderStatus($order_status_id) {
        $pending_statuses = array(1, 2); // 1 for Pending, 2 for Processing
        if (in_array($order_status_id, $pending_statuses)) {
            return 'pending';
        }

        $completed_statuses = array(5, 3); // 5 for Complete, 3 for Shipped
        if (in_array($order_status_id, $completed_statuses)) {
            return 'completed';
        }

        $canceled_statuses = array(7, 8, 9, 10, 11, 12, 13, 14, 16);
        if (in_array($order_status_id, $canceled_statuses)) {
            return 'canceled';
        }
        
        return 'pending';
    }

    public function process_orders() {
        $this->log(['message' => 'process_orders cron job started.'], 'process_orders');

        $api_key = $this->config->get('fraud_buyercheck_api_key');
        $email = $this->config->get('fraud_buyercheck_email');
        $subscription_status = $this->config->get('fraud_buyercheck_subscription_status');

        if (empty($api_key) || empty($email) || $subscription_status !== 'active') {
            $this->log(['message' => 'Subscription not active or settings not configured. Exiting.'], 'process_orders');
            return;
        }

        $this->load->model('extension/fraud/buyercheck');

        $batch_size = 25;
        $page = 1;

        do {
            $filter_data = [
                'start' => ($page - 1) * $batch_size,
                'limit' => $batch_size
            ];
            
            $orders = $this->model_extension_fraud_buyercheck->getProcessableOrders($filter_data);
            
            $orders_to_submit = [];

            if ($orders) {
                $this->load->model('checkout/order');
                $raw_data_consent = $this->config->get('fraud_buyercheck_raw_data_consent');

                foreach ($orders as $order_summary) {
                    $order_info = $this->model_checkout_order->getOrder($order_summary['order_id']);

                    if (!$order_info) continue;

                    $order_status = $this->mapOrderStatus($order_info['order_status_id']);

                    $order_payload = [
                        'order_id' => $order_info['order_id'],
                        'ip_hash' => hash('sha256', $order_info['ip']),
                        'amount' => floatval($order_info['total']),
                        'pending_amount' => $order_status == 'pending' ? floatval($order_info['total']) : 0,
                        'order_status' => $order_status,
                        'created_at' => $order_info['date_added']
                    ];

                    if ($raw_data_consent) {
                        $order_payload['email'] = $order_info['email'];
                        if (!empty($order_info['telephone'])) {
                            $order_payload['phone'] = $order_info['telephone'];
                        }
                    } else {
                        $order_payload['email'] = hash('sha256', $order_info['email']);
                        if (!empty($order_info['telephone'])) {
                            $order_payload['phone'] = hash('sha256', $order_info['telephone']);
                        }
                    }
                    
                    $orders_to_submit[] = $order_payload;

                    $this->model_extension_fraud_buyercheck->setOrderStatus($order_info['order_id'], 'processing');
                }
            }

            if (!empty($orders_to_submit)) {
                $data = [
                    'api_user' => $email,
                    'store_id' => $this->getStoreUrl(),
                    'orders' => $orders_to_submit
                ];
                $this->submitBatchToAPI($data);
            }

            $page++;
        } while (!empty($orders) && count($orders) === $batch_size);

        $this->log(['message' => 'process_orders cron job finished.'], 'process_orders');
    }

    public function submitBatchToAPI($data) {
        $api_key = $this->config->get('fraud_buyercheck_api_key');

        $url = 'https://api.buyercheck.bg/submit-order-data';
        $this->log(['Request URL' => $url, 'Request Type' => 'POST', 'Request Body' => $data], 'submitBatchToAPI Request');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Buyercheck-Key: ' . $api_key,
            'Content-Type: application/json',
            'User-Agent: OpenCart/' . VERSION . ' BuyerCheck/1.0'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'submitBatchToAPI Response');
        
        return $http_code == 200;
    }
}