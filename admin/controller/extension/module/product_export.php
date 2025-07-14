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
            'href' => $this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/product_export', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['export'] = $this->url->link('extension/module/product_export/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_xml'] = $this->url->link('extension/module/product_export/export_xml', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['module_product_export_status'])) {
            $data['module_product_export_status'] = $this->request->post['module_product_export_status'];
        } else {
            $data['module_product_export_status'] = $this->config->get('module_product_export_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/product_export', $data));
    }

    public function export() {
        $this->load->model('extension/module/product_export');

        $products = $this->model_extension_module_product_export->getProducts();

        $filename = "product_export_" . date('Y-m-d') . ".csv";

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies

        $output = fopen('php://output', 'w');

        fputcsv($output, array('Product ID', 'Name', 'Model', 'Price'));

        foreach ($products as $product) {
            fputcsv($output, array($product['product_id'], $product['name'], $product['model'], $product['price']));
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

        header("Content-Type: application/xml");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies

        echo $xml->saveXML();

        exit();
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/product_export')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}