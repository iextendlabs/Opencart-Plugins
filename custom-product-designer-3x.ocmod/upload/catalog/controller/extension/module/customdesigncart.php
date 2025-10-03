<?php
class ControllerExtensionModuleCustomDesignCart extends Controller {
	public function index() {
			// set headers to allow cross origin requests
            $this->response->addHeader('Access-Control-Allow-Origin: '.$this->config->get('module_customdesigncart_iframe_url'));
            $this->response->addHeader('Access-Control-Allow-Methods: GET');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization');
            // Set JSON response header
            $this->response->addHeader('Content-Type: application/json');

          // Auto-detect type and id
          if (isset($this->request->get['op_id'])) {
              $type = 'order';
              $id = (int)$this->request->get['op_id'];
          } elseif (isset($this->request->get['cart_id'])) {
              $type = 'cart';
              $id = (int)$this->request->get['cart_id'];
          } elseif (isset($this->request->get['p_id'])) {
              $type = 'product';
              $id = (int)$this->request->get['p_id'];
          } else {
              $this->response->setOutput(json_encode([
                  'success' => false,
                  'message' => 'Missing or invalid id'
              ]));
              return;
          }

          $table = '';
          $where = '';
          switch ($type) {
            case 'cart':
                $table = DB_PREFIX . 'cart';
                $where = "cart_id = '" . (int)$id . "'";
                break;
            case 'order':
                $table = DB_PREFIX . 'order_custom';
                $where = "order_product_id = '" . (int)$id . "'";
                break;
            case 'product':
            default:
                $table = DB_PREFIX . 'product_custom_config';
                $where = "product_id = '" . (int)$id . "'";
                break;
          }

          $query = $this->db->query("SELECT custom_data FROM `" . $table . "` WHERE " . $where);

          if ($query->num_rows) {
            $this->response->setOutput($query->row['custom_data']);
          } else {
            $this->response->setOutput(json_encode([
              'success' => false,
              'message' => 'No custom data found for this id'
            ]));
          }
        }
}
