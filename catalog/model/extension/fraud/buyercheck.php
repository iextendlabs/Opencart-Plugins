<?php
class ModelExtensionFraudBuyercheck extends Model {
    public function updateOrderData($order_data) {
        $order_id = (int)$order_data['order_id'];
        $risk_score = isset($order_data['risk_score']) ? (int)$order_data['risk_score'] : 0;
        $recommended_action = isset($order_data['recommended_action']) ? $this->db->escape($order_data['recommended_action']) : '';
        $risk_details = isset($order_data['risk_details_external']) ? $this->db->escape($order_data['risk_details_external']) : '';
        $calculated_at = isset($order_data['calculated_at']) ? $this->db->escape($order_data['calculated_at']) : '';

        $this->db->query("INSERT INTO `" . DB_PREFIX . "buyercheck_order` SET order_id = '" . $order_id . "', risk_score = '" . $risk_score . "', recommended_action = '" . $recommended_action . "', risk_details = '" . $risk_details . "', calculated_at = '" . $calculated_at . "' ON DUPLICATE KEY UPDATE risk_score = '" . $risk_score . "', recommended_action = '" . $recommended_action . "', risk_details = '" . $risk_details . "', calculated_at = '" . $calculated_at . "'");
    }
}
