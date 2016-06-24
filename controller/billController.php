<?php
Class billController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê hóa đơn';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $hanhchinh = isset($_POST['hanhchinh']) ? $_POST['hanhchinh'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $trangthai = isset($_POST['sl_status']) ? $_POST['sl_status'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
            $hanhchinh = 1;
            $trangthai = 0;
        }

        if(isset($this->registry->router->param_id) && isset($this->registry->router->page) && isset($this->registry->router->order_by)){
            $trangthai = $this->registry->router->param_id;
            $batdau = date('d-m-Y',$this->registry->router->page);
            $ketthuc = date('d-m-Y',$this->registry->router->order_by);
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $invoice_model = $this->model->get('invoiceModel');
        $agent_model = $this->model->get('agentModel');
        $agentmanifest_model = $this->model->get('agentmanifestModel');
        $sale_model = $this->model->get('salereportModel');
        $agent_cost = $this->model->get('agentcostModel');
        $costs = $agent_cost->getAllCosts();
        $cost_data = array();
        foreach ($costs as $cost) {
            $cost_data[$cost->agent_cost_id]['cost'] = $cost->cost;
            $cost_data[$cost->agent_cost_id]['offer'] = $cost->offer;
        }

        $bill_data = array();
        $i = 0;

        if ($trangthai == 0) {
            $data = array(
                'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
            );

            $invoices = $invoice_model->getAllInvoice($data);

            foreach ($invoices as $invoice) {
                $bill_data['code'][] = $invoice->invoice_number;
                $bill_data['revenue_vat'][] = $invoice->receive;
                $bill_data['revenue'][] = 0;
                $bill_data['cost'][] = $invoice->pay2;
                $bill_data['cost_vat'][] = $invoice->pay1;
                $bill_data['quy'][] = round(($invoice->pay3*0.6)/0.4);
                $bill_data['trich'][] = round(($invoice->pay3*1)/0.4);
                $bill_data['mua'][] = $invoice->pay3;
                $bill_data['check'][] = 1;
                $bill_data['invoice_number'][] = $invoice->invoice_id;
                $i++;
            }

            $data = array(
                'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
            );

            $agents = $agent_model->getAllAgent($data);

            foreach ($agents as $agent) {
                $bill_data['code'][] = $agent->code;
                $bill_data['revenue_vat'][] = $agent->total_offer;
                $bill_data['revenue'][] = 0;
                $bill_data['cost'][] = $agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18);
                $bill_data['cost_vat'][] = 0;
                $bill_data['quy'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.06)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.06);
                $bill_data['trich'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.1)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.1);
                $bill_data['mua'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.04)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.04);
                $bill_data['check'][] = 2;
                $i++;
            }

            $data = array(
                'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
            );

            $manifests = $agentmanifest_model->getAllAgent($data);

            foreach ($manifests as $manifest) {
                $bill_data['code'][] = $manifest->code;
                $bill_data['revenue_vat'][] = $manifest->revenue_vat;
                $bill_data['revenue'][] = $manifest->revenue;
                $bill_data['cost'][] = $manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost;
                $bill_data['cost_vat'][] = 0;
                $bill_data['quy'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.06)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.06):0;
                $bill_data['trich'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.1)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.1):0;
                $bill_data['mua'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.04)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.04):0;
                $bill_data['check'][] = 3;
                $i++;
            }

            $data = array(
                'where' => 'sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            );

            $sales = $sale_model->getAllSale($data);

            foreach ($sales as $sale) {
                $bill_data['code'][] = $sale->code;
                $bill_data['revenue_vat'][] = $sale->revenue_vat+$sale->other_revenue_vat;
                $bill_data['revenue'][] = $sale->revenue+$sale->other_revenue;
                $bill_data['cost'][] = $sale->cost;
                $bill_data['cost_vat'][] = $sale->cost_vat;
                $bill_data['quy'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.06)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.06):0;
                $bill_data['trich'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.1)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.1):0;
                $bill_data['mua'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.04)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.04):0;
                $bill_data['check'][] = $sale->sale_type==1?4:5;
                $i++;
            }
        }
        elseif ($trangthai == 1) {
            $data = array(
                'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            );

            $sales = $sale_model->getAllSale($data);

            foreach ($sales as $sale) {
                $bill_data['code'][] = $sale->code;
                $bill_data['revenue_vat'][] = $sale->revenue_vat;
                $bill_data['revenue'][] = $sale->revenue;
                $bill_data['cost'][] = $sale->cost;
                $bill_data['cost_vat'][] = $sale->cost_vat;
                $bill_data['quy'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.06)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.06):0;
                $bill_data['trich'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.1)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.1):0;
                $bill_data['mua'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.04)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.04):0;
                $bill_data['check'][] = 4;
                $i++;
            }
        }
        elseif ($trangthai == 2) {
            $data = array(
                'where' => 'sale_type = 2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            );

            $sales = $sale_model->getAllSale($data);

            foreach ($sales as $sale) {
                $bill_data['code'][] = $sale->code;
                $bill_data['revenue_vat'][] = $sale->revenue_vat+$sale->other_revenue_vat;
                $bill_data['revenue'][] = $sale->revenue+$sale->other_revenue;
                $bill_data['cost'][] = $sale->cost;
                $bill_data['cost_vat'][] = $sale->cost_vat;
                $bill_data['quy'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.06)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.06):0;
                $bill_data['trich'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.1)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.1):0;
                $bill_data['mua'][] = ($sale->revenue_vat+$sale->other_revenue_vat)>0?round($sale->cost*0.04)+round((round($sale->revenue_vat+$sale->other_revenue_vat+$sale->revenue+$sale->other_revenue-($sale->cost_vat+$sale->cost*1.1)))*0.04):0;
                $bill_data['check'][] = 5;
                $i++;
            }
        }
        elseif ($trangthai == 3) {
            $data = array(
                'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
            );

            $agents = $agent_model->getAllAgent($data);

            foreach ($agents as $agent) {
                $bill_data['code'][] = $agent->code;
                $bill_data['revenue_vat'][] = $agent->total_offer;
                $bill_data['revenue'][] = 0;
                $bill_data['cost'][] = $agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18);
                $bill_data['cost_vat'][] = 0;
                $bill_data['quy'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.06)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.06);
                $bill_data['trich'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.1)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.1);
                $bill_data['mua'][] = round(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*0.04)+round((round($agent->total_offer-(($agent->total_cost-$agent->document_cost-$agent->pay_cost-($cost_data[17]['cost']*$agent->cost_17)-($cost_data[18]['cost']*$agent->cost_18))*1.1)))*0.04);
                $bill_data['check'][] = 2;
                $i++;
            }

            $data = array(
                'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
            );

            $manifests = $agentmanifest_model->getAllAgent($data);

            foreach ($manifests as $manifest) {
                $bill_data['code'][] = $manifest->code;
                $bill_data['revenue_vat'][] = $manifest->revenue_vat;
                $bill_data['revenue'][] = $manifest->revenue;
                $bill_data['cost'][] = $manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost;
                $bill_data['cost_vat'][] = 0;
                $bill_data['quy'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.06)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.06):0;
                $bill_data['trich'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.1)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.1):0;
                $bill_data['mua'][] = $manifest->revenue_vat>0?round(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*0.04)+round((round($manifest->revenue_vat+$manifest->revenue-(($manifest->commission_cost+$manifest->other_cost+$manifest->cost_cm+$manifest->cost_sg+$manifest->driver_cost)*1.1)))*0.04):0;
                $bill_data['check'][] = 3;
                $i++;
            }
        }
        elseif ($trangthai == 4) {
            $data = array(
                'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
            );

            $invoices = $invoice_model->getAllInvoice($data);

            foreach ($invoices as $invoice) {
                $bill_data['code'][] = $invoice->invoice_number;
                $bill_data['revenue_vat'][] = $invoice->receive;
                $bill_data['revenue'][] = 0;
                $bill_data['cost'][] = $invoice->pay1+$invoice->pay2;
                $bill_data['cost_vat'][] = 0;
                $bill_data['quy'][] = round(($invoice->pay3*0.6)/0.4);
                $bill_data['trich'][] = round(($invoice->pay3*1)/0.4);
                $bill_data['mua'][] = $invoice->pay3;
                $bill_data['check'][] = 1;
                $bill_data['invoice_number'][] = $invoice->invoice_id;
                $i++;
            }
        }

        

        $this->view->data['bills'] = $bill_data;
        $this->view->data['total'] = $i;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;
        
        $this->view->show('bill/index');
    }

    public function costs() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí cân đối hóa đơn';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pay_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $trangthai = 0;
        }
        
        $nam = date('Y');


        $join = array('table'=>'bank','where'=>'bank.bank_id = costs.source');

        $costs_model = $this->model->get('costsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'check_invoice = 1',
        );
        
        
        $tongsodong = count($costs_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'check_invoice = 1',
            );
        
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR money_in LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['costs'] = $costs_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($costs_model->getLastCosts()->costs_id)?$costs_model->getLastCosts()->costs_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('bill/costs');
    }
    

}
?>