<?php
class ControllerCheckoutRepay extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		// if (!$this->customer->isLogged()) {
		// 	$this->session->data['redirect'] = $this->url->link('checkout/repay', 'order_id=' . $order_id, true);
		// 	$this->response->redirect($this->url->link('account/login', '', true));
		// }

		$this->load->model('checkout/order');
		$this->load->model('account/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		// if (!$order_info || $this->customer->getId() != $order_info['customer_id']) {
		// 	$this->session->data['error_warning'] = $this->language->get('error_not_found');
		// 	$this->response->redirect($this->url->link('account/order', '', true));
		// }

		// Clear the cart
		$this->cart->clear();

		// Add products to the cart
		$order_products = $this->model_account_order->getOrderProducts($order_id);

		foreach ($order_products as $product) {
			$option_data = array();
			$order_options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

			foreach ($order_options as $option) {
				if ($option['type'] != 'file') {
					$option_data[$option['product_option_id']] = $option['value'];
				} else {
					$option_data[$option['product_option_id']] = $this->encryption->encrypt($this->config->get('config_encryption'), $option['value']);
				}
			}

			$this->cart->add($product['product_id'], $product['quantity'], $option_data);
		}

        // Set session data from order
        $this->session->data['currency'] = $order_info['currency_code'];
        $this->session->data['comment'] = $order_info['comment'];

		// Set session data for checkout
		$this->session->data['payment_address'] = array(
			'firstname'      => $order_info['payment_firstname'],
			'lastname'       => $order_info['payment_lastname'],
			'company'        => $order_info['payment_company'],
			'address_1'      => $order_info['payment_address_1'],
			'address_2'      => $order_info['payment_address_2'],
			'postcode'       => $order_info['payment_postcode'],
			'city'           => $order_info['payment_city'],
			'country_id'     => $order_info['payment_country_id'],
			'zone_id'        => $order_info['payment_zone_id'],
			'country'        => $order_info['payment_country'],
			'iso_code_2'     => $order_info['payment_iso_code_2'],
			'iso_code_3'     => $order_info['payment_iso_code_3'],
			'address_format' => $order_info['payment_address_format'],
			'custom_field'   => $order_info['payment_custom_field'],
			'zone'           => $order_info['payment_zone'],
			'zone_code'      => $order_info['payment_zone_code']
		);

		if ($this->cart->hasShipping()) {
			$this->session->data['shipping_address'] = array(
				'firstname'      => $order_info['shipping_firstname'],
				'lastname'       => $order_info['shipping_lastname'],
				'company'        => $order_info['shipping_company'],
				'address_1'      => $order_info['shipping_address_1'],
				'address_2'      => $order_info['shipping_address_2'],
				'postcode'       => $order_info['shipping_postcode'],
				'city'           => $order_info['shipping_city'],
				'country_id'     => $order_info['shipping_country_id'],
				'zone_id'        => $order_info['shipping_zone_id'],
				'country'        => $order_info['shipping_country'],
				'iso_code_2'     => $order_info['shipping_iso_code_2'],
				'iso_code_3'     => $order_info['shipping_iso_code_3'],
				'address_format' => $order_info['shipping_address_format'],
				'custom_field'   => $order_info['shipping_custom_field'],
				'zone'           => $order_info['shipping_zone'],
				'zone_code'      => $order_info['shipping_zone_code']
			);

            // Get shipping methods
            $method_data = array();
            $this->load->model('setting/extension');
            $results = $this->model_setting_extension->getExtensions('shipping');
            foreach ($results as $result) {
                if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);
                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
                    if ($quote) {
                        $method_data[$result['code']] = array(
                            'title'      => $quote['title'],
                            'quote'      => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error'      => $quote['error']
                        );
                    }
                }
            }
            $sort_order = array();
            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }
            array_multisort($sort_order, SORT_ASC, $method_data);
            $this->session->data['shipping_methods'] = $method_data;

            // Set the shipping method from the original order
            if (isset($order_info['shipping_code']) && !empty($this->session->data['shipping_methods'])) {
                $shipping = explode('.', $order_info['shipping_code']);
                if (isset($shipping[0]) && isset($shipping[1]) && isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                    $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                }
            }
		}
		
		$this->session->data['order_id'] = $order_id;
		$this->session->data['repay'] = true;

		$this->response->redirect($this->url->link('checkout/repay_checkout', '', true));
	}
}
