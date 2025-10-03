<?php
class ControllerExtensionModuleCustomDesignCart extends Controller {
	private $error = array();
	
	public function install() {
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."product_custom_config` (
			`product_id` int NOT NULL,
			`custom_data` json DEFAULT NULL
		)");

		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."order_custom` (
			`order_custom_id` int NOT NULL AUTO_INCREMENT,
			`order_id` int NOT NULL,
			`order_product_id` int NOT NULL,
			`custom_data` json DEFAULT NULL,
			PRIMARY KEY (`order_custom_id`),
			KEY `order_id` (`order_id`),
			KEY `order_product_id` (`order_product_id`)
		)");

		$custom_data = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."cart` LIKE 'custom_data'");
		if(!$custom_data->num_rows) {
			$this->db->query("ALTER TABLE `".DB_PREFIX."cart` ADD `custom_data` json DEFAULT NULL AFTER `option`");
		}
		
	}
	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."product_custom_config`");
		$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."order_custom`");
		$this->db->query("ALTER TABLE `".DB_PREFIX."cart` DROP COLUMN IF EXISTS `custom_data`");
	}

	public function index() {
		// Load the language file
		$this->load->language('extension/module/customdesigncart');

		// Set page title
		$this->document->setTitle($this->language->get('heading_title'));
		
		// Add common language entries
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		// Add form labels
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_iframe_url'] = $this->language->get('entry_iframe_url');
		$data['entry_source_product'] = $this->language->get('entry_source_product');
		$data['entry_target_products'] = $this->language->get('entry_target_products');
		
		// Add button texts
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$this->load->model('setting/setting');
		$this->load->model('catalog/product');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$settings = array(
				'module_customdesigncart_status' => $this->request->post['module_customdesigncart_status'],
				'module_customdesigncart_iframe_url' => $this->request->post['module_customdesigncart_iframe_url']
			);

			$this->model_setting_setting->editSetting('module_customdesigncart', $settings);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/customdesigncart', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/customdesigncart', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_customdesigncart_status'])) {
			$data['module_customdesigncart_status'] = $this->request->post['module_customdesigncart_status'];
		} else {
			$data['module_customdesigncart_status'] = $this->config->get('module_customdesigncart_status');
		}

		if (isset($this->request->post['module_customdesigncart_iframe_url'])) {
			$data['module_customdesigncart_iframe_url'] = $this->request->post['module_customdesigncart_iframe_url'];
		} else {
			$data['module_customdesigncart_iframe_url'] = $this->config->get('module_customdesigncart_iframe_url');
		}

		// Load target products data
		$data['module_related_products'] = array();

		// Make sure user_token is available for AJAX calls
		$data['user_token'] = $this->session->data['user_token'];

		// Add autocomplete URLs
		$data['product_autocomplete'] = $this->url->link('catalog/product/autocomplete', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/customdesigncart', $data));
	}
	public function saveProductConfig($product_id, $data) {
		$exists = $this->db->query("DELETE FROM " . DB_PREFIX . "product_custom_config WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_custom_config SET product_id = '" . (int)$product_id . "', custom_data = '" . $this->db->escape($data) . "'");
	}

	public function apiSaveProductConfig() {
		$product_id = (int)$this->request->post['product_id'];
		$custom_data = $this->request->post['custom_data'];
        $custom_data = html_entity_decode($custom_data);

		$this->saveProductConfig($product_id, $custom_data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['success' => true]));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/customdesigncart')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	private function  getOrderCustoms($order_id, $order_product_id) {
          $query = $this->db->query("SELECT custom_data FROM " . DB_PREFIX . "order_custom WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

          return $query->row['custom_data'];
        }

	private function downloadOrderAssetsZip($jsonString, $orderId) {
		$data = json_decode($jsonString, true);
		if (!isset($data['elements']) || !is_array($data['elements'])) {
			die("Invalid JSON format.");
		}

		// Create a temporary directory
		$tmpDir = sys_get_temp_dir() . "/order_{$orderId}_" . uniqid();
		mkdir($tmpDir);

		$imageCount = 0;

		// Loop through elements and extract base64 images
		foreach ($data['elements'] as $index => $element) {
			if ($element['type'] === 'image' && !(isset($element['readonly']) && $element['readonly'] === true) && isset($element['src']) && substr($element['src'], 0, 11) === 'data:image/') {
				// Extract base64 data
				preg_match('/data:image\/(\w+);base64,(.+)/', $element['src'], $matches);
				if (count($matches) === 3) {
					$ext = $matches[1];
					$base64Data = $matches[2];
					$imageData = base64_decode($base64Data);

					// Save to file
					$filename = $tmpDir . "/image_{$index}.{$ext}";
					file_put_contents($filename, $imageData);
					$imageCount++;
				}
			}
		}

		if ($imageCount === 0) {
			// Clean up temp dir
			foreach (glob("$tmpDir/*") as $file) {
				unlink($file);
			}
			rmdir($tmpDir);
			die("No images found to zip.");
		}

		// Create zip
		$zipFilename = tempnam(sys_get_temp_dir(), "order_{$orderId}_assets_") . ".zip";
		$zip = new ZipArchive();
		if ($zip->open($zipFilename, ZipArchive::CREATE) !== TRUE) {
			// Clean up temp dir
			foreach (glob("$tmpDir/*") as $file) {
				unlink($file);
			}
			rmdir($tmpDir);
			die("Could not create ZIP file.");
		}

		// Add files to zip
		$files = glob("$tmpDir/*");
		foreach ($files as $file) {
			$zip->addFile($file, basename($file));
		}
		$zip->close();

		// Clean up image files
		foreach ($files as $file) {
			unlink($file);
		}
		rmdir($tmpDir);

		// Send to browser
		if (file_exists($zipFilename)) {
			header('Content-Type: application/zip');
			header("Content-Disposition: attachment; filename=order_{$orderId}_assets.zip");
			header('Content-Length: ' . filesize($zipFilename));
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Transfer-Encoding: binary');
			flush();
			readfile($zipFilename);
			unlink($zipFilename);
			exit;
		} else {
			die("ZIP file not found.");
		}
	}
	public function downloadOrderAssets() {
		$orderId = (int)$this->request->get['order_id'];
		$order_product_id = (int)$this->request->get['order_product_id'];
		$jsonString = $this->getOrderCustoms($orderId, $order_product_id);
		if (!$orderId || !$jsonString) {
			die("Invalid request.");
		}
		$this->downloadOrderAssetsZip($jsonString, $orderId);
	}
	public function apiClearProductConfig() {
		$product_id = (int)$this->request->post['product_id'];
		if (!$product_id) {
			$this->response->setOutput(json_encode(['success' => false, 'message' => 'Invalid product ID']));
			return;
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_custom_config WHERE product_id = '" . (int)$product_id . "'");
		$this->response->setOutput(json_encode(['success' => true, 'message' => 'Product customization cleared']));
	}

	public function copyData() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/customdesigncart')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['source_product_id'])) {
			$json['error'] = 'Source product ID is required';
		}

		if (!isset($this->request->post['target_product_ids']) || !is_array($this->request->post['target_product_ids'])) {
			$json['error'] = 'Target product IDs are required';
		}

		if (!isset($json['error'])) {
			$source_product_id = (int)$this->request->post['source_product_id'];
			$target_product_ids = array_map('intval', $this->request->post['target_product_ids']);

			// Get source product custom config
			$query = $this->db->query("SELECT custom_data FROM " . DB_PREFIX . "product_custom_config WHERE product_id = '" . (int)$source_product_id . "'");
			
			if ($query->num_rows) {
				$custom_data = $query->row['custom_data'];
				
				// Copy to each target product
				foreach ($target_product_ids as $target_product_id) {
					if ($target_product_id != $source_product_id) {  // Prevent copying to self
						$this->saveProductConfig($target_product_id, $custom_data);
					}
				}
				
				$json['success'] = 'Custom design data has been copied successfully to ' . count($target_product_ids) . ' products';
			} else {
				$json['error'] = 'No custom design data found for the source product';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
