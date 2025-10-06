<?php
class ModelExtensionFraudBuyercheck extends Model {

    public function check($data) {
		if (!$this->config->get('fraud_buyercheck_status')) {
			return;
		}

		$api_email = $this->config->get('fraud_buyercheck_email');
		$api_key = $this->config->get('fraud_buyercheck_api_key');
		$subscription_status = $this->config->get('fraud_buyercheck_subscription_status');

		if ($subscription_status != 'active' || !$api_key || !$api_email) {
			$this->log(['message' => 'Conditions not met for risk check.', 'subscription_status' => $subscription_status], 'check');
			return;
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "buyercheck_orders` WHERE order_id = '" . (int)$data['order_id'] . "' AND risk_score IS NOT NULL");

		if ($query->num_rows) {
			return;
		}

		$email = $data['email'];
		$phone = $data['telephone'];
		$order_total = $this->currency->format($data['total'], $data['currency_code'], $data['currency_value'], false);
		$ip_address = $data['ip'];

		if ((!$email && !$phone) || $order_total <= 0) {
			$this->log(['message' => 'Email/phone or order total is empty, skipping risk check.'], 'check');
			return;
		}

		$raw_data_consent = $this->config->get('fraud_buyercheck_raw_data_consent');

		$request_data = array(
			'api_user' => $api_email,
			'store_id' => $this->getStoreUrl(),
			'email' => $raw_data_consent ? $email : hash('sha256', $email),
			'amount' => $order_total,
			'ip_hash' => hash('sha256', $ip_address)
		);

		if ($phone) {
			$request_data['phone'] = $raw_data_consent ? $phone : hash('sha256', $phone);
		}

		$url = 'https://api.buyercheck.bg/check-risk';
		$this->log(['Request URL' => $url, 'Request Type' => 'POST', 'Request Body' => $request_data], 'check Request');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
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

		$this->log(['Response Code' => $http_code, 'Raw Response' => $response], 'check Response');

		if ($http_code == 200 && $response) {
			$body = json_decode($response, true);

			if (isset($body['recommended_action'])) {
				$order_received = [
					'order_id' => (int)$data['order_id'],
					'risk_score' => isset($body['risk_score']) ? (int)$body['risk_score'] : null,
					'recommended_action' => $body['recommended_action'],
					'risk_details' => isset($body['risk_details']) ? $body['risk_details'] : null,
				];
				$this->addOrder($data['order_id']);
				$this->updateOrderData($order_received);
			}

			if (isset($body['recommended_action']) && strtolower($body['recommended_action']) === 'cancel') {
				$this->log(['message' => 'High risk detected, returning fraud status.'], 'check');
				return $this->config->get('fraud_buyercheck_order_status_id');
			}
		}
	}

    public function getProcessableOrders($data = array()) {
        $sql = "SELECT o.order_id FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN `" . DB_PREFIX . "buyercheck_orders` bo ON (o.order_id = bo.order_id) 
                WHERE bo.order_id IS NULL OR bo.buyercheck_status = 'pending'";

        $sql .= " ORDER BY o.order_id DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function setOrderStatus($order_id, $status) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "buyercheck_orders` SET order_id = '" . (int)$order_id . "', buyercheck_status = '" . $this->db->escape($status) . "', created_at = NOW(), updated_at = NOW() ON DUPLICATE KEY UPDATE buyercheck_status = '" . $this->db->escape($status) . "', updated_at = NOW()");
    }

    public function updateOrderData($order_received) {
        if (!empty($order_received['order_id'])) {
            $sql = "UPDATE `" . DB_PREFIX . "buyercheck_orders` SET ";
            $fields = [];
            if (isset($order_received['risk_score'])) {
                $fields[] = "risk_score = '" . (int)$order_received['risk_score'] . "'";
            }
            if (isset($order_received['recommended_action'])) {
                $fields[] = "recommended_action = '" . $this->db->escape($order_received['recommended_action']) . "'";
            }
            if (isset($order_received['risk_details'])) {
                $fields[] = "risk_details = '" . $this->db->escape(json_encode($order_received['risk_details'])) . "'";
            }
            
            $fields[] = "buyercheck_status = 'finalized'";
            $fields[] = "calculated_at = NOW()";

            $sql .= implode(", ", $fields);
            $sql .= " WHERE order_id = '" . (int)$order_received['order_id'] . "'";

            $this->db->query($sql);
        }
    }
    
    public function addOrder($order_id) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "buyercheck_orders` SET order_id = '" . (int)$order_id . "', buyercheck_status = 'pending', created_at = NOW(), updated_at = NOW() ON DUPLICATE KEY UPDATE updated_at = NOW()");
    }

    private function log($data = array(), $title = '') {
        if ($this->config->get('fraud_buyercheck_logging')) {
            $log = new Log('buyercheck.log');
            $log->write('Model (' . $title . '): 
' . json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    private function getStoreUrl() {
        return preg_replace("(^https?://)", "", rtrim($this->config->get('config_url'), '/'));
    }
}