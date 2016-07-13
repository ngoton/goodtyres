<?php
Class procumentController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 5 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo lô hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sale_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
            $trangthai = 0;
        }

        $id = $this->registry->router->param_id;

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data[$bank->bank_id] = $bank->bank_name;
        }
        $this->view->data['bank_data'] = $bank_data;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $vendor_data = array();
        foreach ($vendors as $vendor) {
            $vendor_data['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
            $vendor_data['phone'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_phone;
        }
        $this->view->data['vendors'] = $vendor_data;

        $sale_model = $this->model->get('salereportModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        if ($trangthai==0) {
            $data['where'] .= ' AND cost+cost_vat <= 0';
        }
        else if ($trangthai==1) {
            if($_SESSION['role_logined'] == 5)
                $data['where'] .= ' AND cost+cost_vat > 0 AND (procument IS NULL OR procument = '.$_SESSION['userid_logined'].')';
            else
                $data['where'] .= ' AND cost+cost_vat > 0 ';
        }

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = sale_report.customer AND user.user_id = sale_report.sale');
        
        $tongsodong = count($sale_model->getAllSale($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            );

        if ($trangthai==0) {
            $data['where'] .= ' AND cost+cost_vat <= 0';
        }
        else if ($trangthai==1) {
            if($_SESSION['role_logined'] == 5)
                $data['where'] .= ' AND cost+cost_vat > 0 AND (procument IS NULL OR procument = '.$_SESSION['userid_logined'].')';
            else
                $data['where'] .= ' AND cost+cost_vat > 0 ';
        }

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }
        
        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR username LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" 
                OR loc_from in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) 
                OR loc_to in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" )  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        $location_model = $this->model->get('locationModel');
        $location = $location_model->getAllLocation(null,array('table'=>'district','where'=>'district.district_id = location.district'));
        

        $location_data = array();
        foreach ($location as $location) {
            $location_data['location_id'][$location->location_id] = $location->location_id;
            $location_data['location_name'][$location->location_id] = $location->location_name;
            $location_data['district_name'][$location->location_id] = $location->district_name;
        }
        
        $this->view->data['location'] = $location_data;

        $district_model = $this->model->get('districtModel');
        $district = $district_model->getAllDistrict();
        $this->view->data['districts'] = $district;

        $port_model = $this->model->get('portModel');
            $port = $port_model->getAllPort(array('order_by'=>'district ASC, port_name ','order'=>'ASC'));

            $this->view->data['ports'] = $port;

        $all_sale = $sale_model->getAllSale($data,$join);
        
        $this->view->data['sales'] = $all_sale;
        $this->view->data['lastID'] = isset($sale_model->getLastSale()->shipment_id)?$sale_model->getLastSale()->shipment_id:0;


        $sale_vendor = $this->model->get('salevendorModel');
        $vendor_data = array();
        foreach ($all_sale as $sale) {
            $vendor = $sale_vendor->getAllVendor(array('where'=>'sale_report = '.$sale->sale_report_id.' OR trading = '.$sale->sale_report_id));

            foreach ($vendor as $v) {
                $vendor_data[] = $v;
            }
        }

        $this->view->data['vendor_data'] = $vendor_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('procument/index');
    }

    public function lock(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {


            $sale = $this->model->get('salereportModel');
            $sale_data = $sale->getSale($_POST['data']);

            $data = array(
                        'procument_lock' => trim($_POST['value']),
                        );
          
            $sale->updateSale($data,array('sale_report_id' => $_POST['data']));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."lock"."|".$_POST['data']."|procument|".$_POST['value']."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 5 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $sale = $this->model->get('salereportModel');
            
            $sales_model = $this->model->get('salesModel');
            $vendor = $this->model->get('shipmentvendorModel');
            $pending_payable = $this->model->get('pendingpayableModel');
            

            /**************/
            $vendor_cost = $_POST['vendor_cost'];
            /**************/
            $sale_vendor = $this->model->get('salevendorModel');
            
            
            $other_cost_model = $this->model->get('othercostModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');
            $costs = $this->model->get('costsModel');


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                $sale_data = $sale->getSale($_POST['yes']);
                
                if($sale_data->sale_type==1){
                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;

                    foreach ($vendor_cost as $v) {
                        $sale_vendor_data = array(
                            'sale_report' => $_POST['yes'],
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_deposit' => $v['check_deposit'],
                        );


                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] != 1 && $sale_vendor_data['type'] != 4) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];
                        }

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

                                /*$owe_data = array(
                                    'owe_date' => $sale_data->sale_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->sale_date),
                                    'year' => (int)date('Y',$sale_data->sale_date),
                                    'sale_report' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                }

                                $owe->updateOwe($owe_data,array('sale_report'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));
                                */
                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                

                                $receivable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'receivable_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'check_vat'=>0,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }


                                /*$invoice_data = array(
                                    
                                    'money' => $sale_vendor_data['invoice_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí mua HĐ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($invoice_data['week'] == 53){
                                    $invoice_data['week'] = 1;
                                    $invoice_data['year'] = $invoice_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $invoice_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_invoice_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->updateCosts($invoice_data,array('money'=>$old_invoice_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_invoice_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->createCosts($invoice_data);
                                    }
                                }

                                $pay_data = array(
                                    
                                    'money' => $sale_vendor_data['pay_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí chuyển tiền '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($pay_data['week'] == 53){
                                    $pay_data['week'] = 1;
                                    $pay_data['year'] = $pay_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $pay_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_pay_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->updateCosts($pay_data,array('money'=>$old_pay_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_pay_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->createCosts($pay_data);
                                    }
                                }

                                $document_data = array(
                                    
                                    'money' => $sale_vendor_data['document_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí gửi chứng từ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($document_data['week'] == 53){
                                    $document_data['week'] = 1;
                                    $document_data['year'] = $document_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $document_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_document_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->updateCosts($document_data,array('money'=>$old_document_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_document_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->createCosts($document_data);
                                    }
                                }*/
                                


                                $sale_vendor->updateVendor($sale_vendor_data,array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                /*$owe_data = array(
                                    'owe_date' => $sale_data->sale_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->sale_date),
                                    'year' => (int)date('Y',$sale_data->sale_date),
                                    'sale_report' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes']);
                                    
                                    $owe->createOwe($owe_data);*/
                                    
                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'payable_date' => $sale_data->sale_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'sale_report' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $payable->createCosts($payable_data);
                                        }


                                    $receivable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'payable_date' => $sale_data->sale_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'sale_report' => $_POST['yes'],
                                        'check_vat'=>0,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }


                                /*$invoice_data = array(
                                    
                                    'money' => $sale_vendor_data['invoice_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí mua HĐ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($invoice_data['week'] == 53){
                                    $invoice_data['week'] = 1;
                                    $invoice_data['year'] = $invoice_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $invoice_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->createCosts($invoice_data);
                                    }
                                

                                $pay_data = array(
                                    
                                    'money' => $sale_vendor_data['pay_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí chuyển tiền '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($pay_data['week'] == 53){
                                    $pay_data['week'] = 1;
                                    $pay_data['year'] = $pay_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $pay_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->createCosts($pay_data);
                                    }
                                

                                $document_data = array(
                                    
                                    'money' => $sale_vendor_data['document_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí gửi chứng từ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($document_data['week'] == 53){
                                    $document_data['week'] = 1;
                                    $document_data['year'] = $document_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $document_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->createCosts($document_data);
                                    }*/
                                
                   
                                
                            }
                        }
                        else {
                            if($sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


                                $owe_data = array(
                                    'owe_date' => $sale_data->sale_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->sale_date),
                                    'year' => (int)date('Y',$sale_data->sale_date),
                                    'sale_report' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                }

                                $owe->updateOwe($owe_data,array('sale_report'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));

                                if ($old_cost>0 && $sale_vendor_data['cost_vat']>0 && $sale_vendor_data['cost']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost_vat>0 && $sale_vendor_data['cost']>0 && $sale_vendor_data['cost_vat']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($old_cost>0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost'],
                                                'payable_date' => $sale_data->sale_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 1,
                                                'sale_report' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($old_cost_vat>0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost_vat'],
                                                'payable_date' => $sale_data->sale_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 1,
                                                'sale_report' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                    }
                                    else{

                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost_vat'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                        
                                    


                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                    }

                                /*
                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'approve' => null,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    $check = $payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    
                                }
                                elseif(!$payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                
                            


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    $check = $payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    
                                }
                                elseif(!$payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }*/
                                
                                /*$invoice_data = array(
                                    
                                    'money' => $sale_vendor_data['invoice_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí mua HĐ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($invoice_data['week'] == 53){
                                    $invoice_data['week'] = 1;
                                    $invoice_data['year'] = $invoice_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $invoice_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_invoice_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->updateCosts($invoice_data,array('money'=>$old_invoice_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_invoice_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->createCosts($invoice_data);
                                    }
                                }

                                $pay_data = array(
                                    
                                    'money' => $sale_vendor_data['pay_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí chuyển tiền '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($pay_data['week'] == 53){
                                    $pay_data['week'] = 1;
                                    $pay_data['year'] = $pay_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $pay_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_pay_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->updateCosts($pay_data,array('money'=>$old_pay_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_pay_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->createCosts($pay_data);
                                    }
                                }

                                $document_data = array(
                                    
                                    'money' => $sale_vendor_data['document_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí gửi chứng từ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($document_data['week'] == 53){
                                    $document_data['week'] = 1;
                                    $document_data['year'] = $document_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $document_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($costs->getCostsByWhere(array('money'=>$old_document_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']))){
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->updateCosts($document_data,array('money'=>$old_document_cost,'code'=>$sale_data->code,'expect_date'=>$sale_vendor_data['expect_date']));
                                    }
                                    else{
                                        $costs->queryCosts('DELETE FROM costs WHERE money='.$old_document_cost.' AND code='.$sale_data->code.' AND expect_date='.$sale_vendor_data['expect_date']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->createCosts($document_data);
                                    }
                                }*/


                                $sale_vendor->updateVendor($sale_vendor_data,array('sale_report'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                $owe_data = array(
                                    'owe_date' => $sale_data->sale_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->sale_date),
                                    'year' => (int)date('Y',$sale_data->sale_date),
                                    'sale_report' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes']);
                                    
                                    $owe->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost_vat'],
                                        'payable_date' => $sale_data->sale_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'sale_report' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost_vat'] > 0){
                                            $payable->createCosts($payable_data);
                                        }
                                    
                                


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }


                                /*$invoice_data = array(
                                    
                                    'money' => $sale_vendor_data['invoice_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí mua HĐ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($invoice_data['week'] == 53){
                                    $invoice_data['week'] = 1;
                                    $invoice_data['year'] = $invoice_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $invoice_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['invoice_cost'] > 0){
                                        $costs->createCosts($invoice_data);
                                    }
                                

                                $pay_data = array(
                                    
                                    'money' => $sale_vendor_data['pay_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí chuyển tiền '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($pay_data['week'] == 53){
                                    $pay_data['week'] = 1;
                                    $pay_data['year'] = $pay_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $pay_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['pay_cost'] > 0){
                                        $costs->createCosts($pay_data);
                                    }
                                

                                $document_data = array(
                                    
                                    'money' => $sale_vendor_data['document_cost'],
                                    'costs_date' => $sale_data->sale_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => 'Phí gửi chứng từ '.$sale_data->code.' '.$sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'check_office'=>0,
                                );
                                if($document_data['week'] == 53){
                                    $document_data['week'] = 1;
                                    $document_data['year'] = $document_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $document_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['document_cost'] > 0){
                                        $costs->createCosts($document_data);
                                    }*/
                                
                            }
                        }

                        
                            


                        
                        


                    }
                }
                else if($sale_data->sale_type==2){
                    $kvat = 0;
                    $vat = 0;

                    foreach ($vendor_cost as $v) {
                        $sale_vendor_data = array(
                            'trading' => $_POST['yes'],
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_deposit' => $v['check_deposit'],
                        );

                        $kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        $vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        if($sale_vendor->getVendorByWhere(array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                            $old_cost = $sale_vendor->getVendorByWhere(array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                            $old_cost_vat = $sale_vendor->getVendorByWhere(array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                            $total = $old_cost+$old_cost_vat;

                            $owe_data = array(
                                'owe_date' => $sale_data->sale_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->sale_date),
                                'year' => (int)date('Y',$sale_data->sale_date),
                                'trading' => $_POST['yes'],
                            );
                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                            }

                            $owe->updateOwe($owe_data,array('trading'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));

                            $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else if($sale_vendor_data['cost'] > 0){
                                        $payable_data_2 = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                        );
                                        if($payable_data_2['week'] == 53){
                                            $payable_data_2['week'] = 1;
                                            $payable_data_2['year'] = $payable_data_2['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data_2['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        $payable->updateCosts($payable_data_2,array('money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        //$payable->queryCosts('DELETE FROM payable WHERE check_vat=1 AND money='.$old_cost_vat.' AND vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                
                            


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'sale_report' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else if($sale_vendor_data['cost_vat'] > 0){
                                        $payable_data_2 = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->sale_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 1,
                                            'sale_report' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                        );
                                        if($payable_data_2['week'] == 53){
                                            $payable_data_2['week'] = 1;
                                            $payable_data_2['year'] = $payable_data_2['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data_2['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        $payable->updateCosts($payable_data_2,array('money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'sale_report'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        //$payable->queryCosts('DELETE FROM payable WHERE check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND sale_report='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }

                            $sale_vendor->updateVendor($sale_vendor_data,array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                        }
                        else{
                            $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->sale_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->sale_date),
                                'year' => (int)date('Y',$sale_data->sale_date),
                                'trading' => $_POST['yes'],
                            );
                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                            }

                            //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                
                                $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => $sale_data->sale_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'trading' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                
                            


                            $payable_data = array(
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost'],
                                'payable_date' => $sale_data->sale_date,
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'trading' => $_POST['yes'],
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                            }

                            
                                if($sale_vendor_data['cost'] > 0){
                                    $payable->createCosts($payable_data);
                                }
                            
                        }

                        
                            


                        


                    }
                }
                    

                    $data = array(
                        'procument' => $_SESSION['userid_logined'],
                        'cost' => $kvat,
                        'cost_vat' => $vat,
                        'profit' => $sale_data->revenue-$kvat,
                        'profit_vat' => $sale_data->revenue_vat-$vat,
                        'estimate_cost' => $estimate,

                    );

                    $data['count_update_2'] = $sale_data->count_update_2+1;

                    if($sale_data->count_update_2 > 2){
                        $data['procument_lock'] = 1;
                    }
                    
                    $sale->updateSale($data,array('sale_report_id' => trim($_POST['yes'])));
                    echo "Thêm thành công";

                    $sale_datas = $sale->getSale($_POST['yes']);

                    if (!$pending_payable->getCostsByWhere(array('sale_report'=>$_POST['yes']))) {
                        $data_pending = array(
                            'code' => $sale_datas->code,
                            'revenue' => $sale_datas->revenue+$sale_datas->revenue_vat+$sale_datas->payhalf,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->payhalf+$sale_datas->estimate_cost,
                            'sale_report' => $_POST['yes'],
                            'money' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->payhalf,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                        );

                        $pending_payable->createCosts($data_pending);
                    }
                    else if ($pending_payable->getCostsByWhere(array('sale_report'=>$_POST['yes']))) {
                        $data_pending = array(
                            'code' => $sale_datas->code,
                            'revenue' => $sale_datas->revenue+$sale_datas->revenue_vat+$sale_datas->payhalf,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->payhalf+$sale_datas->estimate_cost,
                            'sale_report' => $_POST['yes'],
                            'money' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->payhalf,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                            'approve' => null,
                        );

                        $check = $pending_payable->getCostsByWhere(array('sale_report'=>$_POST['yes']));

                        if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                            $data_pending['approve'] = 10;
                        }

                        $pending_payable->updateCosts($data_pending,array('sale_report'=>$_POST['yes']));
                    }


                    $salesdata = $sales_model->getSalesByWhere(array('sale_report'=>$_POST['yes']));

                    if ($salesdata) {
                        $data_sales = array(
                            'customer' => $sale_data->customer,
                            'code' => $sale_data->code,
                            'comment' => $sale_data->comment,
                            'revenue' => $sale_data->revenue+$sale_data->revenue_vat,
                            'cost' => $kvat+$vat+$estimate,
                            'profit' => $sale_data->revenue+$sale_data->revenue_vat-$kvat-$vat-$estimate,
                            'sales_create_time' => $sale_data->sale_date,
                            'm' => $sale_data->m,
                            's' => $sale_data->s,
                            'c' => $sale_data->c,
                            'sale_report' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
                    }
                    elseif (!$salesdata) {
                        $data_sales = array(
                            'customer' => $sale_data->customer,
                            'code' => $sale_data->code,
                            'comment' => $sale_data->comment,
                            'revenue' => $sale_data->revenue+$sale_data->revenue_vat,
                            'cost' => $kvat+$vat+$estimate,
                            'profit' => $sale_data->revenue+$sale_data->revenue_vat-$kvat-$vat-$estimate,
                            'sales_create_time' => $sale_data->sale_date,
                            'm' => $sale_data->m,
                            's' => $sale_data->s,
                            'c' => $sale_data->c,
                            'sale_report' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->createSales($data_sales);
                    }


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|procument|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            
                    
        }
    }

    public function getvendor(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('salevendorModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'sale_report='.$_POST['sale_report'].' OR trading='.$_POST['sale_report']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type" name="cost_type[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost"  name="invoice_cost[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost"  name="pay_cost[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td>';
                    $str .= '<td></td><td><input type="checkbox" value="1" name="check_deposit[]" class="check_deposit"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost"  name="document_cost[]" value="0"  ></td></tr></table></td></tr>';
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    

                    foreach($banks as $bank){ 
                        $slnh = ($bank->bank_id == $v->source)?'selected="selected"':null;
                        $ba .= '<option '.$slnh .' value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }

                     $truck = ($v->type==1)?'selected="selected"':null;
                     $bar = ($v->type==2)?'selected="selected"':null;
                     $fee = ($v->type==3)?'selected="selected"':null;
                     $thu = ($v->type==4)?'selected="selected"':null;
                     $hh = ($v->type==5)?'selected="selected"':null;
                     $tt = ($v->type==6)?'selected="selected"':null;
                     $khac = ($v->type==7)?'selected="selected"':null;

                     $checked = $v->check_deposit==1?'checked':null;

                    $str .= '<tr class="'.$v->sale_report.$v->trading.'">';
                    $str .= '<td><input type="checkbox" name="chk" tabindex="'.$v->type.'" data="'.$v->sale_report.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type" name="cost_type[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost"  name="invoice_cost[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost"  name="pay_cost[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td>';
                    $str .= '<td></td><td><input disabled type="checkbox" '.$checked.' value="1" name="check_deposit[]" class="check_deposit"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost"  name="document_cost[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function delete(){
        if(isset($_POST['data'])){
            $sale_vendor = $this->model->get('salevendorModel');
            $sale = $this->model->get('salereportModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $receivable = $this->model->get('receivableModel');
            $costs = $this->model->get('costsModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => '(sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM sale_vendor WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'(sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'(sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);



            
            
            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;
                if($vendor_data->check_deposit != 1 && $vendor_data->type != 4){
                    $kvat += $vendor_data->cost;
                    $vat += $vendor_data->cost_vat;
                }
                
                $estimate += $vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->document_cost;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

                /*$co = $costs->getAllCosts(array('where'=>'money='.$vendor_data->invoice_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code));
                foreach ($co as $c) {
                    $assets->queryAssets('DELETE FROM assets WHERE costs='.$c->costs_id);
                    $pay->queryCosts('DELETE FROM pay WHERE costs='.$c->costs_id);
                }
                $co = $costs->getAllCosts(array('where'=>'money='.$vendor_data->pay_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code));
                foreach ($co as $c) {
                    $assets->queryAssets('DELETE FROM assets WHERE costs='.$c->costs_id);
                    $pay->queryCosts('DELETE FROM pay WHERE costs='.$c->costs_id);
                }
                $co = $costs->getAllCosts(array('where'=>'money='.$vendor_data->document_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code));
                foreach ($co as $c) {
                    $assets->queryAssets('DELETE FROM assets WHERE costs='.$c->costs_id);
                    $pay->queryCosts('DELETE FROM pay WHERE costs='.$c->costs_id);
                }

                $costs->queryCosts('DELETE FROM costs WHERE money='.$vendor_data->invoice_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code);
                $costs->queryCosts('DELETE FROM costs WHERE money='.$vendor_data->pay_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code);
                $costs->queryCosts('DELETE FROM costs WHERE money='.$vendor_data->document_cost.' OR expect_date='.$vendor_data->expect_date.' AND code='.$sale_data->code);*/

            }

            $owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND money='.$old_cost);

            if($sale_data->sale_type == 1){
                $owe_data = array(
                    'owe_date' => $sale_data->sale_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$sale_data->sale_date),
                    'year' => (int)date('Y',$sale_data->sale_date),
                    'sale_report' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                //    $owe->createOwe($owe_data);

                $salesdata = $sales_model->getSalesByWhere(array('sale_report'=>$_POST['data']));
                $data_sales = array(
                    'cost' => $salesdata->cost-$kvat-$vat-$estimate,
                    'profit' => $sale_data->profit+$kvat+$vat+$estimate,
                    'sale_report' => $_POST['data'],
                    'sales_update_user' => $_SESSION['userid_logined'],
                    'sales_update_time' => strtotime(date('d-m-Y')),
                );
                $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
            }
            else{
                $owe_data = array(
                    'owe_date' => $sale_data->sale_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$sale_data->sale_date),
                    'year' => (int)date('Y',$sale_data->sale_date),
                    'trading' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                //    $owe->createOwe($owe_data);

                $salesdata = $sales_model->getSalesByWhere(array('sale_report'=>$_POST['data']));
                $data_sales = array(
                    'cost' => $salesdata->cost-$kvat-$vat-$estimate,
                    'profit' => $sale_data->profit+$kvat+$vat+$estimate,
                    'trading' => $_POST['data'],
                    'sales_update_user' => $_SESSION['userid_logined'],
                    'sales_update_time' => strtotime(date('d-m-Y')),
                );
                $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
            }


            $data = array(
                'cost' => $sale_data->cost-$kvat,
                'cost_vat' => $sale_data->cost_vat-$vat,
                'profit' => $sale_data->profit+$kvat,
                'profit_vat' => $sale_data->profit_vat+$vat,
                'estimate_cost' => $sale_data->estimate_cost-$estimate,

            );
            
            $sale->updateSale($data,array('sale_report_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

            

        }
    }

}
?>