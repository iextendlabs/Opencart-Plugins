<?php
class ControllerCheckoutReorderPayment extends Controller {
    public function index() {
        $this->load->language('checkout/cart');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('checkout/reorder_payment', 'order_id=' . $order_id, true);
            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info && $this->customer->getId() == $order_info['customer_id']) {
            $this->cart->clear();

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
            
            $this->session->data['success'] = $this->language->get('text_success');

            // Set the order_id in the session to be used in the checkout process
            $this->session->data['order_id'] = $order_id;

            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('account/order', '', true));
        }
    }
}