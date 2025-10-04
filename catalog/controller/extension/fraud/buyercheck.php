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
            'store_id' => preg_replace("(^https?://)", "", $this->config->get('config_url')),
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
            'Content-Type: application/json'
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
            $log->write('BuyerCheck Catalog Debug (' . $title . '): ' . json_encode($data));
        }
    }
}