<?php
Class totalController Extends baseController {
    

    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo lợi nhuận - chi phí';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;
        }
        else{
            $dat = date('d-m-Y');
            $tuan = (int)date('W', strtotime($dat));
            $nam_report = (int)date('Y');
        }

        $mang = $this->getStartAndEndDate($tuan,$nam_report);
        $batdau = $mang[0];
        $ketthuc = $mang[1];

        
        $assets_model = $this->model->get('assetsModel');
        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');

        $cjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
        $cdata = array(
            'where' => 'costs.check_office = 1 AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND assets.week = '.$tuan.' AND assets.year = '.$nam_report,
        );

        $costs = $assets_model->getAllAssets($cdata,$cjoin);



        $sdata = array(
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($sdata);

        

        $tdata = array(
            'where' => 'sale_type = 2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $tradings = $sale_model->getAllSale($tdata);



        $adata = array(
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
        );

        $agents = $agent_model->getAllAgent($adata);

        $phidaily = 0;

        $agent_data_cost = array();

        foreach ($agents as $agent) {
            $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
            foreach ($phi_agent as $phi) {
                $phidaily = $phi->money;
            }
            $agent_data_cost[$agent->agent_id] = $phidaily;
            
            $phidaily = 0;
        }
        

        $data_manifest = array(
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
        );

        $manifests = $manifest_model->getAllAgent($data_manifest);



        $idata = array(
            'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
        );

        $invoices = $invoice_model->getAllInvoice($idata);

        
        $this->view->data['costs'] = $costs;
        $this->view->data['sales'] = $sales;
        $this->view->data['tradings'] = $tradings;
        $this->view->data['agents'] = $agents;
        $this->view->data['manifests'] = $manifests;
        $this->view->data['invoices'] = $invoices;
        $this->view->data['tuan'] = $tuan;
        $this->view->data['nam'] = $nam_report;
        $this->view->data['agent_data_cost'] = $agent_data_cost;
        
        $this->view->show('total/index');
    }

   
    function getStartAndEndDate($week, $year)
    {
        $week = $week-1;
        $time = strtotime('01-01-'.$year, time());
        $day = date('w', $time);
        $time += ((7*$week)+1-$day)*24*3600;
        $return[0] = date('d-m-Y', $time);
        $time += 6*24*3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }

    

}
?>