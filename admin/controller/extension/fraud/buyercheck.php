<?php
class ControllerExtensionFraudBuyercheck extends Controller {
    private $error = array();

    private $_events = [
        [
            'description' => 'Sends order to BuyerCheck for Risk Analysis',
            'code' => 'buyercheck_prepare_and_check_risk',
            'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
            'action' => 'extension/fraud/buyercheck/prepareAndCheckRisk',
            'sort_order' => 1,
            'status' => true
        ]
    ];

    public function index() {
        $this->load->language('extension/fraud/buyercheck');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('fraud_buyercheck', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['email'])) {
            $data['error_email'] = $this->error['email'];
        } else {
            $data['error_email'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        if (isset($this->error['store_category'])) {
            $data['error_store_category'] = $this->error['store_category'];
        } else {
            $data['error_store_category'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/fraud/buyercheck', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/fraud/buyercheck', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true);

        if (isset($this->request->post['fraud_buyercheck_email'])) {
            $data['fraud_buyercheck_email'] = $this->request->post['fraud_buyercheck_email'];
        } else {
            $data['fraud_buyercheck_email'] = $this->config->get('fraud_buyercheck_email');
        }

        if (isset($this->request->post['fraud_buyercheck_api_key'])) {
            $data['fraud_buyercheck_api_key'] = $this->request->post['fraud_buyercheck_api_key'];
        } else {
            $data['fraud_buyercheck_api_key'] = $this->config->get('fraud_buyercheck_api_key');
        }

        if (isset($this->request->post['fraud_buyercheck_store_category'])) {
            $data['fraud_buyercheck_store_category'] = $this->request->post['fraud_buyercheck_store_category'];
        } else {
            $data['fraud_buyercheck_store_category'] = $this->config->get('fraud_buyercheck_store_category');
        }

        if (isset($this->request->post['fraud_buyercheck_risky_action'])) {
            $data['fraud_buyercheck_risky_action'] = $this->request->post['fraud_buyercheck_risky_action'];
        } else {
            $data['fraud_buyercheck_risky_action'] = $this->config->get('fraud_buyercheck_risky_action');
        }

        if (isset($this->request->post['fraud_buyercheck_raw_data_consent'])) {
            $data['fraud_buyercheck_raw_data_consent'] = $this->request->post['fraud_buyercheck_raw_data_consent'];
        } else {
            $data['fraud_buyercheck_raw_data_consent'] = $this->config->get('fraud_buyercheck_raw_data_consent');
        }

        if (isset($this->request->post['fraud_buyercheck_status'])) {
            $data['fraud_buyercheck_status'] = $this->request->post['fraud_buyercheck_status'];
        } else {
            $data['fraud_buyercheck_status'] = $this->config->get('fraud_buyercheck_status');
        }

        if (isset($this->request->post['fraud_buyercheck_webhook_secret'])) {
            $data['fraud_buyercheck_webhook_secret'] = $this->request->post['fraud_buyercheck_webhook_secret'];
        } else {
            $data['fraud_buyercheck_webhook_secret'] = $this->config->get('fraud_buyercheck_webhook_secret');
        }

        if (isset($this->request->post['fraud_buyercheck_webhook_url'])) {
            $data['fraud_buyercheck_webhook_url'] = $this->request->post['fraud_buyercheck_webhook_url'];
        } else {
            $data['fraud_buyercheck_webhook_url'] = $this->config->get('fraud_buyercheck_webhook_url') ? $this->config->get('fraud_buyercheck_webhook_url') : HTTPS_CATALOG . 'index.php?route=extension/fraud/buyercheck/webhook';
        }

        if (isset($this->request->post['fraud_buyercheck_logging'])) {
            $data['fraud_buyercheck_logging'] = $this->request->post['fraud_buyercheck_logging'];
        } else {
            $data['fraud_buyercheck_logging'] = $this->config->get('fraud_buyercheck_logging');
        }

        $data['store_categories'] = $this->getStoreCategories();

        $data['logs'] = $this->url->link('extension/fraud/buyercheck/logs', 'user_token=' . $this->session->data['user_token'], true);
        $data['orders'] = $this->url->link('extension/fraud/buyercheck/order_list', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/fraud/buyercheck', $data));
    }

    private function getStoreCategories() {
        $categories = array();

        $url = 'https://api.buyercheck.bg/onboard-store';
        $this->log(['Request URL' => $url, 'Request Type' => 'GET'], 'getStoreCategories Request');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'getStoreCategories Response');

        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);

            if (isset($data['categories']) && is_array($data['categories'])) {
                $categories = $data['categories'];
            }
        }
        // create an associative array with 'value' and 'text' keys
        $formatted_categories = array();
        foreach ($categories as $value=>$category) {
            $formatted_categories[] = array(
                'value' =>  $value,
                'text' => $category
            );
        }
        return $formatted_categories;
    }

    public function install() {
        $this->load->model('setting/event');
        $this->load->model('extension/fraud/buyercheck');
        $this->model_extension_fraud_buyercheck->install();

        foreach($this->_events as $event) {
            $this->model_setting_event->addEvent($event['code'], $event['description'], $event['trigger'], $event['action'], $event['status'], $event['sort_order']);
		}
        // enable the event
        $this->model_setting_event->enableEvent('buyercheck_prepare_and_check_risk');
    }

    public function uninstall() {
        $this->load->model('setting/event');
        $this->load->model('extension/fraud/buyercheck');
        $this->model_extension_fraud_buyercheck->uninstall();

        foreach($this->_events as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);
		}
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/fraud/buyercheck')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['fraud_buyercheck_email'])) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (empty($this->request->post['fraud_buyercheck_api_key'])) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (empty($this->request->post['fraud_buyercheck_store_category'])) {
            $this->error['store_category'] = $this->language->get('error_store_category');
        }

        if (!$this->error) {
            $webhook_secret = $this->request->post['fraud_buyercheck_webhook_secret'] ? $this->request->post['fraud_buyercheck_webhook_secret'] : bin2hex(random_bytes(16));
            // path=    $webhook_url_path = 'buyercheck_webhook_' . md5(openssl_random_pseudo_bytes(16));
            
            $webhook_full_url = $this->request->post['fraud_buyercheck_webhook_url'] ?  $this->request->post['fraud_buyercheck_webhook_url'] : HTTPS_CATALOG . 'index.php?route=extension/fraud/buyercheck/webhook' ;

            $data = array(
                'api_user' => $this->request->post['fraud_buyercheck_email'],
                'domain' => rtrim(HTTP_CATALOG, '/'),
                'category' => $this->request->post['fraud_buyercheck_store_category'],
                'webhook_secret' => $webhook_secret,
                'webhook_url' => $webhook_full_url,
                'raw_data_consent' => (bool) ($this->request->post['fraud_buyercheck_raw_data_consent'] ?? 0)
            );

            $url = 'https://api.buyercheck.bg/onboard-store';
            $this->log(['Request URL' => $url, 'Request Type' => 'POST', 'Request Body' => $data], 'validate Request');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Buyercheck-Key: ' . $this->request->post['fraud_buyercheck_api_key'],
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'validate Response');

            if ($http_code == 200 && $response) {
                $result = json_decode($response, true);
                if (isset($result['subscription_status'])) {
                    $this->request->post['fraud_buyercheck_subscription_status'] = $result['subscription_status'];
                }
            } else {
                $result = json_decode($response, true);
                $error_message = isset($result['error']) ? $result['error'] : 'Unknown API error';
                $this->error['warning'] = $this->language->get('error_api_validation') . ' ' . $error_message;
            }
        }

        return !$this->error;
        // return true;
    }

    public function log($data = array(), $title = '') {
        if ($this->config->get('fraud_buyercheck_logging')) {
            $log = new Log('buyercheck.log');
            $log->write('BuyerCheck debug (' . $title . '): ' . json_encode($data));
        }
    }

    public function prepareAndCheckRisk(&$route, &$args, &$output) {
        $this->log(['prepareAndCheckRisk event triggered'], 'prepareAndCheckRisk');

        if (!$this->config->get('fraud_buyercheck_status')) {
            $this->log(['fraud_buyercheck_status is not enabled'], 'prepareAndCheckRisk');
            return;
        }
        
        if ($this->config->get('fraud_buyercheck_subscription_status') != 'active') {
            $this->log(['fraud_buyercheck_subscription_status is not active'], 'prepareAndCheckRisk');
            return;
        }
        
        $order_id = $args[0] ?? null;
        
        if (!$order_id) {
            $this->log(['order_id is not set'], 'prepareAndCheckRisk');
            return;
        }
        
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if (!$order_info) {
            $this->log(['order_info is not set for order_id: ' . $order_id], 'prepareAndCheckRisk');
            return;
        }

        $order_histories = $this->model_sale_order->getOrderHistories($order_id);
        if (count($order_histories) > 1) {
            $this->log(['Order already has history, skipping', $order_id], 'prepareAndCheckRisk');
            return;
        }
        
        $payment_method = strtolower($order_info['payment_method']);
        if (strpos($payment_method, 'cash') === false && 
            strpos($payment_method, 'cod') === false && 
            strpos($payment_method, 'delivery') === false) {
            $this->log(['payment_method is not cash, cod or delivery'], 'prepareAndCheckRisk');
            return;
        }
        
        $order_data = array(
            'order_id' => $order_id,
            'email' => $order_info['email'],
            'telephone' => $order_info['telephone'],
            'total' => $order_info['total'],
            'currency_code' => $order_info['currency_code'],
            'ip' => $order_info['ip'],
            'date_added' => $order_info['date_added']
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
            'Content-Type: application/json'
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
        return preg_replace("(^https?://)", "", $this->config->get('config_url'));
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
        
        return 'other';
    }

    public function order() {
        $this->load->language('extension/fraud/buyercheck');

        $this->load->model('extension/fraud/buyercheck');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $fraud_info = $this->model_extension_fraud_buyercheck->getOrder($order_id);

        if ($fraud_info) {
            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_risk_score'] = $this->language->get('text_risk_score');
            $data['text_recommended_action'] = $this->language->get('text_recommended_action');
            $data['text_risk_details'] = $this->language->get('text_risk_details');
            $data['text_calculated_at'] = $this->language->get('text_calculated_at');

            $data['risk_score'] = $fraud_info['risk_score'];
            $data['recommended_action'] = $fraud_info['recommended_action'];
            $data['risk_details'] = $fraud_info['risk_details'];
            $data['calculated_at'] = $fraud_info['calculated_at'];

            return $this->load->view('extension/fraud/buyercheck_info', $data);
        }
    }

    public function logs() {
        $this->load->language('extension/fraud/buyercheck');
        $this->document->setTitle($this->language->get('heading_title'));
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_logs'] = $this->language->get('text_logs');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['button_clear'] = $this->language->get('button_clear');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/fraud/buyercheck', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['clear_log'] = $this->url->link('extension/fraud/buyercheck/clear_log', 'user_token=' . $this->session->data['user_token'], true);
        $data['log'] = '';
        $file = DIR_LOGS . 'buyercheck.log';
        if (file_exists($file)) {
            $data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/fraud/buyercheck_logs', $data));
    }

    public function clear_log() {
        $this->load->language('extension/fraud/buyercheck');
        if ($this->user->hasPermission('modify', 'extension/fraud/buyercheck')) {
            $file = DIR_LOGS . 'buyercheck.log';
            $handle = fopen($file, 'w+');
            fclose($handle);
            $this->session->data['success'] = $this->language->get('text_success');
        }
        $this->response->redirect($this->url->link('extension/fraud/buyercheck/logs', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function order_list() {
        $this->load->language('extension/fraud/buyercheck');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/fraud/buyercheck');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_order_id'] = $this->language->get('column_order_id');
        $data['column_risk_score'] = $this->language->get('column_risk_score');
        $data['column_recommended_action'] = $this->language->get('column_recommended_action');
        $data['column_calculated_at'] = $this->language->get('column_calculated_at');
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/fraud/buyercheck', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['orders'] = array();
        $results = $this->model_extension_fraud_buyercheck->getOrders();
        foreach ($results as $result) {
            $data['orders'][] = array(
                'order_id' => $result['order_id'],
                'risk_score' => $result['risk_score'],
                'recommended_action' => $result['recommended_action'],
                'calculated_at' => $result['calculated_at'],
                'view' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
            );
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/fraud/buyercheck_orders', $data));
    }
}