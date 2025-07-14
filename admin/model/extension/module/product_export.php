<?php
class ModelExtensionModuleProductExport extends Model {
    public function getProducts() {
        $query = $this->db->query("SELECT p.*, pd.* FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->rows;
    }

    public function getProductVariants($product_id) {
        $query = $this->db->query("SELECT pov.quantity, pov.price, od.name AS option_name, ovd.name AS option_value_name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "product_option po ON (pov.product_option_id = po.product_option_id) LEFT JOIN " . DB_PREFIX . "option o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order, ov.sort_order");

        return $query->rows;
    }
}