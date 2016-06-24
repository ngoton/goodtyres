<?php
Class statsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('d-m-Y', time()+86400);

            $tuan_batdau = (int)date('W', strtotime($batdau));
            $tuan_ketthuc = (int)date('W', strtotime($ketthuc));

            $nam_batdau = (int)date('Y',strtotime($batdau));
            $nam_ketthuc = (int)date('Y',strtotime($ketthuc));
        }

        $owe_model = $this->model->get('oweModel');
        $obtain_model = $this->model->get('obtainModel');
        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $agentmanifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');

        $data = array(
            'where' => 'sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            'order_by' => 'code',
            'order' => 'ASC',
        );

        $sales = $sale_model->getAllSale($data);

        $data = array(
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
            'order_by' => 'code',
            'order' => 'ASC',
        );

        $agents = $agent_model->getAllAgent($data);

        $data = array(
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
            'order_by' => 'code',
            'order' => 'ASC',
        );

        $manifests = $agentmanifest_model->getAllAgent($data);

        $data = array(
            'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
            'order_by' => 'invoice_number',
            'order' => 'ASC',
        );

        $invoices = $invoice_model->getAllInvoice($data);

        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['sales'] = $sales;
        $this->view->data['agents'] = $agents;
        $this->view->data['manifests'] = $manifests;
        $this->view->data['invoices'] = $invoices;
        
        $this->view->show('stats/index');
    }

    

}
?>