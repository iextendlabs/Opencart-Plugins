<?php
class ControllerExtensionModuleProductExport extends Controller {
    public function index() {
        $this->load->language('extension/module/product_export');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_product_export', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true)
            )
        );

        $data['action'] = $this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['export_descriptions'] = $this->url->link('extension/module/product_export/export_descriptions', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_options'] = $this->url->link('extension/module/product_export/export_options', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_attributes'] = $this->url->link('extension/module/product_export/export_attributes', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_images'] = $this->url->link('extension/module/product_export/export_images', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_data'] = $this->url->link('extension/module/product_export/export_data', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_to_cat'] = $this->url->link('extension/module/product_export/export_p_to_cat', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_to_store'] = $this->url->link('extension/module/product_export/export_p_to_store', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_related'] = $this->url->link('extension/module/product_export/export_p_related', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_specials'] = $this->url->link('extension/module/product_export/export_p_specials', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_discounts'] = $this->url->link('extension/module/product_export/export_p_discounts', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_p_rewards'] = $this->url->link('extension/module/product_export/export_p_rewards', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_option_values'] = $this->url->link('extension/module/product_export/export_option_values', 'user_token=' . $this->session->data['user_token'], true);

        $data['export_customers'] = $this->url->link('extension/module/product_export/export_customers', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_groups'] = $this->url->link('extension/module/product_export/export_customer_groups', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_addresses'] = $this->url->link('extension/module/product_export/export_customer_addresses', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_affiliates'] = $this->url->link('extension/module/product_export/export_customer_affiliates', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_transactions'] = $this->url->link('extension/module/product_export/export_customer_transactions', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_rewards'] = $this->url->link('extension/module/product_export/export_customer_rewards', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_customer_ips'] = $this->url->link('extension/module/product_export/export_customer_ips', 'user_token=' . $this->session->data['user_token'], true);

        $data['module_product_export_status'] = $this->request->post['module_product_export_status'] ?? $this->config->get('module_product_export_status');

        $data['export_orders'] = $this->url->link('extension/module/product_export/export_orders', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_order_products'] = $this->url->link('extension/module/product_export/export_order_products', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_order_options'] = $this->url->link('extension/module/product_export/export_order_options', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_order_totals'] = $this->url->link('extension/module/product_export/export_order_totals', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_order_histories'] = $this->url->link('extension/module/product_export/export_order_histories', 'user_token=' . $this->session->data['user_token'], true);

        // Get all tables for generic export
        $tables = array();
        $query = $this->db->query("SHOW TABLES");
        foreach ($query->rows as $row) {
            $tables[] = array_values($row)[0];
        }
        $data['all_tables'] = $tables;
        $data['export_table_url'] = $this->url->link('extension/module/product_export/export_table', 'user_token=' . $this->session->data['user_token'], true);
        $data['import_table_url'] = $this->url->link('extension/module/product_export/import_table', 'user_token=' . $this->session->data['user_token'], true);
        $data['download_sample_csv_url'] = $this->url->link('extension/module/product_export/download_sample_csv', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/product_export', $data));
    }

    public function export() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProducts();

        $filename = "product_export_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Product ID', 'Name', 'Model', 'Price']);

        foreach ($products as $product) {
            fputcsv($output, [$product['product_id'], $product['name'], $product['model'], $product['price']]);
        }

        fclose($output);
        exit();
    }

    public function export_descriptions() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductDescriptions();
        $filename = "product_descriptions_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'language_id', 'name', 'description', 'tag', 'meta_title', 'meta_description', 'meta_keyword']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_options() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductOptions();
        $filename = "product_options_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'product_option_id', 'option_id', 'value', 'required']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_attributes() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductAttributes();
        $filename = "product_attributes_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'attribute_id', 'language_id', 'text']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_images() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductImages();
        $filename = "product_images_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'product_image_id', 'image', 'sort_order']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_data() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductData();
        $filename = "product_data_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'location', 'quantity', 'stock_status_id', 'image', 'manufacturer_id', 'shipping', 'price', 'points', 'tax_class_id', 'weight', 'weight_class_id', 'length', 'width', 'height', 'length_class_id', 'subtract', 'minimum', 'sort_order', 'status', 'viewed']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_to_cat() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductToCategory();
        $filename = "product_to_category_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'category_id']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_to_store() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductToStore();
        $filename = "product_to_store_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'store_id']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_related() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductRelated();
        $filename = "product_related_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'related_id']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_specials() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductSpecials();
        $filename = "product_specials_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'customer_group_id', 'priority', 'price']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_discounts() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductDiscounts();
        $filename = "product_discounts_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'customer_group_id', 'quantity', 'priority', 'price']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_p_rewards() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductRewards();
        $filename = "product_rewards_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'customer_group_id', 'points']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_option_values() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProductOptionValues();
        $filename = "product_option_values_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['product_id', 'name', 'product_option_value_id', 'product_option_id', 'option_id', 'option_value_id', 'quantity', 'subtract', 'price', 'price_prefix', 'points', 'points_prefix', 'weight', 'weight_prefix']);
        foreach ($products as $product) {
            fputcsv($output, $product);
        }
        fclose($output);
        exit();
    }

    public function export_customers() {
        $this->load->model('extension/module/product_export');
        $customers = $this->model_extension_module_product_export->getCustomers();
        $filename = "customers_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_id', 'customer_group_id', 'store_id', 'language_id', 'firstname', 'lastname', 'email', 'telephone', 'fax', 'newsletter', 'address_id', 'ip', 'status', 'safe', 'token', 'code']);
        foreach ($customers as $customer) {
            fputcsv($output, $customer);
        }
        fclose($output);
        exit();
    }

    public function export_customer_groups() {
        $this->load->model('extension/module/product_export');
        $customer_groups = $this->model_extension_module_product_export->getCustomerGroups();
        $filename = "customer_groups_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_group_id', 'approval', 'sort_order']);
        foreach ($customer_groups as $customer_group) {
            fputcsv($output, $customer_group);
        }
        fclose($output);
        exit();
    }

    public function export_customer_addresses() {
        $this->load->model('extension/module/product_export');
        $customer_addresses = $this->model_extension_module_product_export->getCustomerAddresses();
        $filename = "customer_addresses_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['address_id', 'customer_id', 'firstname', 'lastname', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country_id', 'zone_id']);
        foreach ($customer_addresses as $address) {
            fputcsv($output, $address);
        }
        fclose($output);
        exit();
    }

    public function export_customer_affiliates() {
        $this->load->model('extension/module/product_export');
        $customer_affiliates = $this->model_extension_module_product_export->getCustomerAffiliates();
        $filename = "customer_affiliates_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_id', 'company', 'website', 'tracking', 'commission', 'tax', 'payment', 'cheque', 'paypal', 'bank_name', 'bank_branch_number', 'bank_swift_code', 'bank_account_name', 'bank_account_number', 'status']);
        foreach ($customer_affiliates as $affiliate) {
            fputcsv($output, $affiliate);
        }
        fclose($output);
        exit();
    }

    public function export_customer_transactions() {
        $this->load->model('extension/module/product_export');
        $customer_transactions = $this->model_extension_module_product_export->getCustomerTransactions();
        $filename = "customer_transactions_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_transaction_id', 'customer_id', 'order_id', 'description', 'amount']);
        foreach ($customer_transactions as $transaction) {
            fputcsv($output, $transaction);
        }
        fclose($output);
        exit();
    }

    public function export_customer_rewards() {
        $this->load->model('extension/module/product_export');
        $customer_rewards = $this->model_extension_module_product_export->getCustomerRewards();
        $filename = "customer_rewards_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_reward_id', 'customer_id', 'order_id', 'description', 'points']);
        foreach ($customer_rewards as $reward) {
            fputcsv($output, $reward);
        }
        fclose($output);
        exit();
    }

    public function export_customer_ips() {
        $this->load->model('extension/module/product_export');
        $customer_ips = $this->model_extension_module_product_export->getCustomerIps();
        $filename = "customer_ips_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['customer_ip_id', 'customer_id', 'ip']);
        foreach ($customer_ips as $ip) {
            fputcsv($output, $ip);
        }
        fclose($output);
        exit();
    }

    public function export_xml() {
        $this->load->model('extension/module/product_export');
        $products = $this->model_extension_module_product_export->getProducts();

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true;

        $urunler = $xml->createElement('Urunler');
        $xml->appendChild($urunler);

        foreach ($products as $product) {
            $urun = $xml->createElement('Urun');
            $urunler->appendChild($urun);
            $urun->appendChild($xml->createElement('Kod', $product['model']));
            $urun->appendChild($xml->createElement('Tedarikci', ''));
            $urun->appendChild($xml->createElement('Barkod', ''));
            $baslik = $xml->createElement('Baslik');
            $baslik->appendChild($xml->createCDATASection($product['name']));
            $urun->appendChild($baslik);
            $kisaTanim = $xml->createElement('KisaTanim');
            $kisaTanim->appendChild($xml->createCDATASection($product['meta_description']));
            $urun->appendChild($kisaTanim);
            $urun->appendChild($xml->createElement('Kategori', ''));
            $urun->appendChild($xml->createElement('AnaKategori', ''));
            $urun->appendChild($xml->createElement('UstKategori', ''));
            $urun->appendChild($xml->createElement('Marka', ''));
            $urun->appendChild($xml->createElement('Fiyat', $product['price']));
            $urun->appendChild($xml->createElement('Indirim', $product['price']));
            $urun->appendChild($xml->createElement('IndirimOran', '0.00'));
            $urun->appendChild($xml->createElement('ParaBirimi', 'TL'));
            $urun->appendChild($xml->createElement('Stok', $product['quantity']));
            $urun->appendChild($xml->createElement('Durum', $product['status']));
            $urun->appendChild($xml->createElement('Kdv', '20'));
            $urun->appendChild($xml->createElement('Desi', '0.00'));
            $aciklama = $xml->createElement('Aciklama');
            $aciklama->appendChild($xml->createCDATASection($product['description']));
            $urun->appendChild($aciklama);
            $resimler = $xml->createElement('Resimler');
            $urun->appendChild($resimler);
            $resimler->appendChild($xml->createElement('Resim1', $product['image']));
            $resimler->appendChild($xml->createElement('Resim2', ''));
            $resimler->appendChild($xml->createElement('Resim3', ''));
            $resimler->appendChild($xml->createElement('Resim4', ''));
            $resimler->appendChild($xml->createElement('Resim5', ''));
            $varyantlar = $xml->createElement('Varyantlar');
            $urun->appendChild($varyantlar);
            $variants = $this->model_extension_module_product_export->getProductVariants($product['product_id']);
            foreach ($variants as $variant) {
                $varyant = $xml->createElement('Varyant');
                $varyantlar->appendChild($varyant);
                $varyant->appendChild($xml->createElement('Name', $variant['option_name']));
                $varyant->appendChild($xml->createElement('VaryantCode', ''));
                $varyant->appendChild($xml->createElement('VaryantBarcode', ''));
                $varyant->appendChild($xml->createElement('Value', $variant['option_value_name']));
                $varyant->appendChild($xml->createElement('Stok', $variant['quantity']));
                $varyant->appendChild($xml->createElement('Price', $variant['price']));
            }
        }

        $filename = "product_export_" . date('Y-m-d') . ".xml";
        $this->setDownloadHeaders($filename, 'xml');
        echo $xml->saveXML();
        exit();
    }

    public function export_orders() {
        $this->load->model('extension/module/product_export');
        $orders = $this->model_extension_module_product_export->getOrdersWithDetails();
        $filename = "orders_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        // Main order fields (from oc_order)
        $header = [
            'order_id', 'invoice_prefix', 'store_id', 'store_name', 'store_url', 'customer_id', 'customer_group_id', 'firstname', 'lastname', 'email', 'telephone', 'total', 'order_status_id', 'date_added', 'date_modified', 'ip', 'payment_firstname', 'payment_lastname', 'payment_company', 'payment_address_1', 'payment_address_2', 'payment_city', 'payment_postcode', 'payment_country', 'payment_country_id', 'payment_zone', 'payment_zone_id', 'payment_method', 'shipping_firstname', 'shipping_lastname', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_country_id', 'shipping_zone', 'shipping_zone_id', 'shipping_method'
        ];
        fputcsv($output, $header);
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['order_id'], $order['invoice_prefix'], $order['store_id'], $order['store_name'], $order['store_url'], $order['customer_id'], $order['customer_group_id'], $order['firstname'], $order['lastname'], $order['email'], $order['telephone'], $order['total'], $order['order_status_id'], $order['date_added'], $order['date_modified'], $order['ip'], $order['payment_firstname'], $order['payment_lastname'], $order['payment_company'], $order['payment_address_1'], $order['payment_address_2'], $order['payment_city'], $order['payment_postcode'], $order['payment_country'], $order['payment_country_id'], $order['payment_zone'], $order['payment_zone_id'], $order['payment_method'], $order['shipping_firstname'], $order['shipping_lastname'], $order['shipping_company'], $order['shipping_address_1'], $order['shipping_address_2'], $order['shipping_city'], $order['shipping_postcode'], $order['shipping_country'], $order['shipping_country_id'], $order['shipping_zone'], $order['shipping_zone_id'], $order['shipping_method']
            ]);
        }
        fclose($output);
        exit();
    }

    public function export_order_products() {
        $this->load->model('extension/module/product_export');
        $rows = $this->model_extension_module_product_export->getOrderProducts();
        $filename = "order_products_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $header = ['order_product_id', 'order_id', 'product_id', 'name', 'model', 'quantity', 'price', 'total', 'tax', 'reward'];
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
    public function export_order_options() {
        $this->load->model('extension/module/product_export');
        $rows = $this->model_extension_module_product_export->getOrderOptions();
        $filename = "order_options_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $header = ['order_option_id', 'order_id', 'order_product_id', 'product_option_id', 'product_option_value_id', 'name', 'value', 'type'];
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
    public function export_order_totals() {
        $this->load->model('extension/module/product_export');
        $rows = $this->model_extension_module_product_export->getOrderTotals();
        $filename = "order_totals_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $header = ['order_total_id', 'order_id', 'code', 'title', 'value', 'sort_order'];
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
    public function export_order_histories() {
        $this->load->model('extension/module/product_export');
        $rows = $this->model_extension_module_product_export->getOrderHistories();
        $filename = "order_histories_" . date('Y-m-d') . ".csv";
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $header = ['order_history_id', 'order_id', 'order_status_id', 'notify', 'comment', 'date_added'];
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }

    public function export_table() {
        if (!isset($this->request->get['table'])) {
            exit('No table specified');
        }
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $this->request->get['table']);
        $query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "`");
        if (!$query->num_rows) {
            exit('Invalid table');
        }
        $columns = array();
        foreach ($query->rows as $col) {
            $columns[] = $col['Field'];
        }
        $data_query = $this->db->query("SELECT * FROM `" . $this->db->escape($table) . "`");
        $filename = $table .".csv";
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $columns);
        foreach ($data_query->rows as $row) {
            fputcsv($output, array_values($row));
        }
        fclose($output);
        exit();
    }

    public function import_table() {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->data['error_warning'] = 'No file uploaded or upload error.';
            $this->response->redirect($this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        $file = $_FILES['import_file']['tmp_name'];
        $filename = pathinfo($_FILES['import_file']['name'], PATHINFO_FILENAME);
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $filename);
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->session->data['error_warning'] = 'Could not open file.';
            $this->response->redirect($this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        $columns = fgetcsv($handle);
        if (!$columns) {
            fclose($handle);
            $this->session->data['error_warning'] = 'CSV missing header row.';
            $this->response->redirect($this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        // Get auto-increment primary key info
        $first_col = (string)$columns[0];

        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        $first_col  = str_replace($bom, '', $first_col );
        // remove quotes if present
        $first_col = trim($first_col);
        $first_col = str_replace('"', '', $first_col);
        $first_col = $this->db->escape($first_col);
        
        $auto_increment = false;
        $primary_key = false;
        $col_query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` WHERE `Key` = 'PRI' AND  `Extra` = 'auto_increment' AND `Field` = '" .$first_col."'");
        if ($col_query->num_rows) {
            $primary_key = true;
        }
        $this->db->query('START TRANSACTION');
        $success = true;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) != count($columns)) {
                $success = false;
                break;
            }
            $fields = array();
            foreach ($columns as $i => $col) {
                $fields[] = "`" . $this->db->escape($col) . "` = '" . $this->db->escape($row[$i]) . "'";
            }
            // If first column is auto_increment primary key, try update first
            if ($primary_key) {
                $pk_value = $this->db->escape($row[0]);
                // Build WHERE clause for primary key
                $where = "`" . $this->db->escape($first_col) . "` = '" . $pk_value . "'";
                // Try update
                $sql_update = "UPDATE `" . $this->db->escape($table) . "` SET " . implode(', ', array_slice($fields, 1)) . " WHERE $where";
                $this->db->query($sql_update);
                if ($this->db->countAffected() == 0) {
                    // If no row updated, do insert (include all fields) except primary field
                    unset($fields[0]); // Remove first field (primary key)
                    $sql_insert = "INSERT INTO `" . $this->db->escape($table) . "` SET " . implode(', ', $fields);
                    if (!$this->db->query($sql_insert)) {
                        $success = false;
                        break;
                    }
                }
            } else {
                // No auto-increment PK, just insert
                $sql = "INSERT INTO `" . $this->db->escape($table) . "` SET " . implode(', ', $fields);
                if (!$this->db->query($sql)) {
                    $success = false;
                    break;
                }
            }
        }
        fclose($handle);
        if ($success) {
            $this->db->query('COMMIT');
            $this->session->data['success'] = 'Table imported successfully.';
        } else {
            $this->db->query('ROLLBACK');
            $this->session->data['error_warning'] = 'Import failed. No data was imported.';
        }
        $this->response->redirect($this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function download_sample_csv() {
        if (!isset($this->request->get['table'])) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            exit('Table not specified');
        }
        $table = $this->db->escape($this->request->get['table']);
        // Get columns
        $query = $this->db->query("SHOW COLUMNS FROM `" . $table . "`");
        if (!$query->rows) {
            $this->response->addHeader('HTTP/1.1 404 Not Found');
            exit('Table not found');
        }
        $columns = array();
        foreach ($query->rows as $row) {
            $columns[] = $row['Field'];
        }
        $filename = $table . '.csv';
        $this->setDownloadHeaders($filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $columns);
        fclose($output);
        exit();
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/product_export')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    private function setDownloadHeaders($filename, $type = 'csv') {
        if ($type === 'xml') {
            header("Content-Type: application/xml");
        } else {
            header("Content-Type: text/csv; charset=utf-8");
        }
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
}