<?php
Class foundController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quỹ trích lũy';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sale_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y'); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;


        $sale_model = $this->model->get('salereportModel');
        $assets_model = $this->model->get('assetsModel');

        $data = array(
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($data);

        $sale_data = array(
            'kvat' => 0,
        );
        foreach ($sales as $sale) {
            $sale_data['doanhthu'] = isset($sale_data['doanhthu'])?($sale_data['doanhthu']+$sale->revenue+$sale->revenue_vat):($sale->revenue+$sale->revenue_vat);
            $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            
            $sale_data['kvat'] = isset($sale_data['kvat'])?($sale_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND sale_estimate AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc));
        foreach ($other_cost as $cost) {
            if($cost->check_pay==1){
                $sale_data['phichuyentien'] = isset($sale_data['phichuyentien'])?($sale_data['phichuyentien']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            else if($cost->check_document==1){
                $sale_data['phichuyenphat'] = isset($sale_data['phichuyenphat'])?($sale_data['phichuyenphat']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            
        }

        $data = array(
            'where' => 'sale_type = 2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($data);

        $trading_data = array(
            'kvat' => 0,
        );
        foreach ($sales as $sale) {
            $trading_data['doanhthu'] = isset($trading_data['doanhthu'])?($trading_data['doanhthu']+$sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat):($sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat);
            $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            
            $trading_data['kvat'] = isset($trading_data['kvat'])?($trading_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND trading_estimate=1 AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc));
        foreach ($other_cost as $cost) {
            if($cost->check_pay==1){
                $trading_data['phichuyentien'] = isset($trading_data['phichuyentien'])?($trading_data['phichuyentien']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            else if($cost->check_document==1){
                $trading_data['phichuyenphat'] = isset($trading_data['phichuyenphat'])?($trading_data['phichuyenphat']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            
        }

        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');

        $data = array(
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
        );

        $agents = $agent_model->getAllAgent($data);

        $agent_data = array(
            'kvat' => 0,
        );
        $phidaily = 0;
        foreach ($agents as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->total_offer):(0+$agent->total_offer);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
            
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));

                $agent_data['xangxe'] = isset($agent_data['xangxe'])?($agent_data['xangxe']+200000):(0+200000);
                $agent_data['phihoamai'] = isset($agent_data['phihoamai'])?($agent_data['phihoamai']+($agent->cost_15*30000)):(0+($agent->cost_15*30000));
            
            $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
            foreach ($phi_agent as $phi) {
                $phidaily = $phi->money;
            }
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$phidaily):(0+$phidaily);
            
            $phidaily = 0;

        }

        $data_manifest = array(
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
        );

        $manifests = $manifest_model->getAllAgent($data_manifest);

        foreach ($manifests as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->revenue_vat+$agent->revenue):(0+$agent->revenue_vat+$agent->revenue);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));

                $agent_data['xangxe'] = isset($agent_data['xangxe'])?($agent_data['xangxe']+$agent->driver_cost):(0+$agent->driver_cost);
                $agent_data['phihoahong'] = isset($agent_data['phihoahong'])?($agent_data['phihoahong']+$agent->commission_cost):(0+$agent->commission_cost);
            
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND agent_estimate=1 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc));
        foreach ($other_cost as $cost) {
            if($cost->check_pay==1){
                $agent_data['phichuyentien'] = isset($agent_data['phichuyentien'])?($agent_data['phichuyentien']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            else if($cost->check_document==1){
                $agent_data['phichuyenphat'] = isset($agent_data['phichuyenphat'])?($agent_data['phichuyenphat']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
        }

        $invoice_model = $this->model->get('invoiceModel');

        $data = array(
            'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
        );

        $invoices = $invoice_model->getAllInvoice($data);

        $invoice_data = array();
        foreach ($invoices as $invoice) {
            $invoice_data['doanhthu'] = isset($invoice_data['doanhthu'])?($invoice_data['doanhthu']+$invoice->receive):(0+$invoice->receive);
            $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+($invoice->pay1+$invoice->pay2)):(0+($invoice->pay1+$invoice->pay2));
            $invoice_data['kvat'] = isset($invoice_data['kvat'])?($invoice_data['kvat']+$invoice->pay3):(0+$invoice->pay3);
                
            
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND tcmt_estimate=1 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc));
        foreach ($other_cost as $cost) {
            if($cost->check_pay==1){
                $invoice_data['phichuyentien'] = isset($invoice_data['phichuyentien'])?($invoice_data['phichuyentien']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
            else if($cost->check_document==1){
                $invoice_data['phichuyenphat'] = isset($invoice_data['phichuyenphat'])?($invoice_data['phichuyenphat']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total)); 
            }
        }


        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND check_invoice=1 AND (sale_estimate=1 OR tcmt_estimate=1 OR agent_estimate=1 OR trading_estimate=1 ) AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc));
        foreach ($other_cost as $cost) {
            $financial_data['chiphi'] = isset($financial_data['chiphi'])?($financial_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            
        }

        $financial_data['doanhthu'] = round(($agent_data['kvat']+$sale_data['kvat']+$trading_data['kvat'])*0.1);
        
        $this->view->data['sale_data'] = $sale_data;
        $this->view->data['trading_data'] = $trading_data;
        $this->view->data['financial_data'] = $financial_data;
        $this->view->data['agent_data'] = $agent_data;
        $this->view->data['invoice_data'] = $invoice_data;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('found/index');
    }

    



}
?>