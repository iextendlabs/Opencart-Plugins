<?php
class ModelExtensionFraudBuyercheck extends Model {

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