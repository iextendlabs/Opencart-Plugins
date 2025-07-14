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

    public function getProductDescriptions() {
        $query = $this->db->query("SELECT pd.product_id, pd.language_id, pd.name, pd.description, pd.tag, pd.meta_title, pd.meta_description, pd.meta_keyword FROM " . DB_PREFIX . "product_description pd");
        return $query->rows;
    }

    public function getProductOptions() {
        $query = $this->db->query("SELECT po.product_id, pd.name, po.product_option_id, po.option_id, po.value, po.required FROM " . DB_PREFIX . "product_option po LEFT JOIN " . DB_PREFIX . "product_description pd ON (po.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductAttributes() {
        $query = $this->db->query("SELECT pa.product_id, pd.name, pa.attribute_id, pa.language_id, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "product_description pd ON (pa.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductImages() {
        $query = $this->db->query("SELECT pi.product_id, pd.name, pi.product_image_id, pi.image, pi.sort_order FROM " . DB_PREFIX . "product_image pi LEFT JOIN " . DB_PREFIX . "product_description pd ON (pi.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductData() {
        $query = $this->db->query("SELECT p.product_id, pd.name, p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.mpn, p.location, p.quantity, p.stock_status_id, p.image, p.manufacturer_id, p.shipping, p.price, p.points, p.tax_class_id, p.weight, p.weight_class_id, p.length, p.width, p.height, p.length_class_id, p.subtract, p.minimum, p.sort_order, p.status, p.viewed FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }
	
	    public function getProductToCategory() {
        $query = $this->db->query("SELECT ptc.product_id, pd.name, ptc.category_id FROM " . DB_PREFIX . "product_to_category ptc LEFT JOIN " . DB_PREFIX . "product_description pd ON (ptc.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductToStore() {
        $query = $this->db->query("SELECT pts.product_id, pd.name, pts.store_id FROM " . DB_PREFIX . "product_to_store pts LEFT JOIN " . DB_PREFIX . "product_description pd ON (pts.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductRelated() {
        $query = $this->db->query("SELECT pr.product_id, pd.name, pr.related_id FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product_description pd ON (pr.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductSpecials() {
        $query = $this->db->query("SELECT ps.product_id, pd.name, ps.customer_group_id, ps.priority, ps.price FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product_description pd ON (ps.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductDiscounts() {
        $query = $this->db->query("SELECT pdi.product_id, pd.name, pdi.customer_group_id, pdi.quantity, pdi.priority, pdi.price FROM " . DB_PREFIX . "product_discount pdi LEFT JOIN " . DB_PREFIX . "product_description pd ON (pdi.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductRewards() {
        $query = $this->db->query("SELECT pr.product_id, pd.name, pr.customer_group_id, pr.points FROM " . DB_PREFIX . "product_reward pr LEFT JOIN " . DB_PREFIX . "product_description pd ON (pr.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductOptionValues() {
        $query = $this->db->query("SELECT pov.product_id, pd.name, pov.product_option_value_id, pov.product_option_id, pov.option_id, pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "product_description pd ON (pov.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getCustomers() {
        $query = $this->db->query("SELECT customer_id, customer_group_id, store_id, language_id, firstname, lastname, email, telephone, fax, newsletter, address_id, ip, status, safe, token, code FROM " . DB_PREFIX . "customer");
        return $query->rows;
    }

    public function getCustomerGroups() {
        $query = $this->db->query("SELECT customer_group_id, approval, sort_order FROM " . DB_PREFIX . "customer_group");
        return $query->rows;
    }

    public function getCustomerAddresses() {
        $query = $this->db->query("SELECT address_id, customer_id, firstname, lastname, company, address_1, address_2, city, postcode, country_id, zone_id FROM " . DB_PREFIX . "address");
        return $query->rows;
    }

    public function getCustomerAffiliates() {
        $query = $this->db->query("SELECT customer_id, company, website, tracking, commission, tax, payment, cheque, paypal, bank_name, bank_branch_number, bank_swift_code, bank_account_name, bank_account_number, status FROM " . DB_PREFIX . "customer_affiliate");
        return $query->rows;
    }

    public function getCustomerTransactions() {
        $query = $this->db->query("SELECT customer_transaction_id, customer_id, order_id, description, amount FROM " . DB_PREFIX . "customer_transaction");
        return $query->rows;
    }

    public function getCustomerRewards() {
        $query = $this->db->query("SELECT customer_reward_id, customer_id, order_id, description, points FROM " . DB_PREFIX . "customer_reward");
        return $query->rows;
    }

    public function getCustomerIps() {
        $query = $this->db->query("SELECT customer_ip_id, customer_id, ip FROM " . DB_PREFIX . "customer_ip");
        return $query->rows;
    }

    public function getOrderProducts() {
        $query = $this->db->query("SELECT order_product_id, order_id, product_id, name, model, quantity, price, total, tax, reward FROM " . DB_PREFIX . "order_product");
        return $query->rows;
    }
    public function getOrderOptions() {
        $query = $this->db->query("SELECT order_option_id, order_id, order_product_id, product_option_id, product_option_value_id, name, value, type FROM " . DB_PREFIX . "order_option");
        return $query->rows;
    }
    public function getOrderTotals() {
        $query = $this->db->query("SELECT order_total_id, order_id, code, title, value, sort_order FROM " . DB_PREFIX . "order_total");
        return $query->rows;
    }
    public function getOrderHistories() {
        $query = $this->db->query("SELECT order_history_id, order_id, order_status_id, notify, comment, date_added FROM " . DB_PREFIX . "order_history");
        return $query->rows;
    }
}