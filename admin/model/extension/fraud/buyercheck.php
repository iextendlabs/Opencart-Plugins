<?php
class ModelExtensionFraudBuyercheck extends Model {
    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "buyercheck_orders` (`order_id` int(11) NOT NULL, `buyercheck_status` varchar(50) NOT NULL DEFAULT 'pending', `risk_score` int(3) DEFAULT NULL, `recommended_action` varchar(50) DEFAULT NULL, `risk_details` text DEFAULT NULL, `calculated_at` datetime DEFAULT NULL, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`order_id`), KEY `buyercheck_status` (`buyercheck_status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "buyercheck_orders`");
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "buyercheck_orders` WHERE `order_id` = '" . (int)$order_id . "'");
        return $query->row;
    }

    public function getOrders($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "buyercheck_orders`";
        $sort_data = array(
            'order_id',
            'risk_score',
            'calculated_at'
        );
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY order_id";
        }
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function validateAPI($email, $api_key, $store_category, $raw_data_consent) {
        $data = array(
            'api_user' => $email,
            'domain' => HTTP_CATALOG,
            'category' => $store_category,
            'raw_data_consent' => (bool)$raw_data_consent
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.buyercheck.bg/onboard-store');
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

        $result = ['status' => $http_code, 'result' => json_decode($response, true)];
        return $result;
    }
}