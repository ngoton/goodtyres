<?php
Class tradingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sale_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $id = $this->registry->router->param_id;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $this->view->data['staffs'] = $staffs;

        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['staff_id'][$staff->staff_id] = $staff->staff_id;
            $staff_data['staff_name'][$staff->staff_id] = $staff->staff_name;
        }
        
        $this->view->data['staff'] = $staff_data;

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
            'where' => 'sale_type=2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'sale_type=2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }

        /*if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

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

        $sales = $sale_model->getAllSale($data,$join);
        
        $this->view->data['sales'] = $sales;

        $obtain_model = $this->model->get('obtainModel');

        $stock = array();

        foreach ($sales as $sale) {
            $ob = $obtain_model->getObtainByWhere(array('trading'=>$sale->sale_report_id,'customer'=>119));
            $stock[$sale->sale_report_id] = isset($ob->money)?$ob->money:0;
        }

        $this->view->data['stock'] = $stock;

        $this->view->data['lastID'] = isset($sale_model->getLastSale()->shipment_id)?$sale_model->getLastSale()->shipment_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('trading/index');
    }

  
    public function getvendor(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            $data = array(
                'where'=>'vendor_type = '.$_POST['type'],
                );

            if ($_POST['keyword'] == "*") {

                $list = $vendor_model->getAllVendor($data);
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%") AND vendor_type = '.$_POST['type'],
                );
                $list = $vendor_model->getAllvendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor1(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_phone.'\',\''.$rs->vendor_type.'\')">'.$vendor_name.'</li>';
            }
        }
    }
    public function getvendor2(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            $data = array(
                'where'=>'vendor_type = '.$_POST['type'],
                );

            if ($_POST['keyword'] == "*") {

                $list = $vendor_model->getAllVendor($data);
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%") AND vendor_type = '.$_POST['type'],
                );
                $list = $vendor_model->getAllvendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor2(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_phone.'\',\''.$rs->vendor_type.'\')">'.$vendor_name.'</li>';
            }
        }
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $customer_model->getAllCustomer();
            }
            else{
                $data = array(
                'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $customer_model->getAllCustomer($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $customer_name = $rs->customer_name;
                if ($_POST['keyword'] != "*") {
                    $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                }

                if ($rs->customer_expect_date != null) {
                    $expect_date = date('d-m-Y',strtotime($rs->customer_expect_date.'-'.date('m-Y',strtotime(date('d-m-Y')))));
                }
                else if ($rs->customer_after_date != null) {
                    $expect_date = date('d-m-Y',strtotime('+'.$rs->customer_after_date.' day', strtotime(date('d-m-Y'))));
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.$rs->customer_phone.'\',\''.$rs->customer_address.'\',\''.$rs->customer_email.'\',\''.$expect_date.'\')">'.$customer_name.'</li>';
            }
        }
    }

    public function getothercustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $customer_model->getAllCustomer();
            }
            else{
                $data = array(
                'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $customer_model->getAllCustomer($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $customer_name = $rs->customer_name;
                if ($_POST['keyword'] != "*") {
                    $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                }

                if ($rs->customer_expect_date != null) {
                    $expect_date = date('d-m-Y',strtotime($rs->customer_expect_date.'-'.date('m-Y',strtotime(date('d-m-Y')))));
                }
                else if ($rs->customer_after_date != null) {
                    $expect_date = date('d-m-Y',strtotime('+'.$rs->customer_after_date.' day', strtotime(date('d-m-Y'))));
                }
                
                // add new option
                echo '<li onclick="set_item_other(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.$rs->customer_phone.'\',\''.$rs->customer_address.'\',\''.$rs->customer_email.'\',\''.$expect_date.'\',\''.$_POST['offset'].'\')">'.$customer_name.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');

            $staff = $staff_model->getStaffByWhere(array('staff_id'=>$_POST['staff']));

            $sale = $this->model->get('salereportModel');
            $data = array(
                        'sale_date' => strtotime(trim($_POST['sale_date'])),
                        'loc_from' => trim($_POST['loc_from']),
                        'loc_to' => trim($_POST['loc_to']),
                        'code' => trim($_POST['code']),
                        'number' => trim($_POST['number']),
                        'unit' => trim($_POST['unit']),
                        'number2' => trim($_POST['number2']),
                        'unit2' => trim($_POST['unit2']),
                        'transport' => trim($_POST['transport']),
                        'comment' => trim($_POST['comment']),
                        'customer' => trim($_POST['customer']),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'revenue' => trim(str_replace(',','',$_POST['revenue'])),
                        'revenue_vat' => trim(str_replace(',','',$_POST['revenue_vat'])),
                        'customer_source' => trim($_POST['customer_source']),
                        
                        'lock' => 1,
                        'sale_type'=>2,
                        'staff' => trim($_POST['staff']),
                        'staff_cost' => trim(str_replace(',','',$_POST['staff_cost'])),
                        'deposit' => trim(str_replace(',','',$_POST['deposit'])),
                        'm' => trim($_POST['m']),
                        's' => trim($_POST['s']),
                        'c' => trim($_POST['c']),
                        );
            //$data['cost'] = $data['ops_cost']+$data['trucking_cost']+$data['barging_cost']+$data['commission_cost']+$data['bill_cost']+ trim(str_replace(',','',$_POST['other_cost']))+ trim(str_replace(',','',$_POST['other_cost2']))+ trim(str_replace(',','',$_POST['other_cost3']));
            //$data['cost_vat'] = $data['trucking_cost_vat']+$data['barging_cost_vat']+$data['feeder_cost_vat']+$data['handling_cost_vat']+$data['handling_cost_vat2']+$data['pay_cost_vat']+ trim(str_replace(',','',$_POST['other_cost_vat']))+ trim(str_replace(',','',$_POST['other_cost_vat2']))+ trim(str_replace(',','',$_POST['other_cost_vat3']));
            //$data['profit'] = $data['revenue'] - $data['cost'];
            //$data['profit_vat'] = $data['revenue_vat'] - $data['cost_vat'];
    
            $vendor = $this->model->get('shipmentvendorModel');
            $customer = $this->model->get('customerModel');

            /*$data['ops_name'] = null;
            $data['trucking_vendor'] = null;
            $data['barging_vendor'] = null;
            $data['feeder_vendor'] = null;
            $data['handling_port'] = null;
            $data['handling_port2'] = null;
            $data['commission'] = null;

            if($_POST['ops_name'] != ""){
                if ($_POST['ops'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['ops_name']),
                        'shipment_vendor_phone' => trim($_POST['ops_phone']),
                        'vendor_type' => 1,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['ops_name'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['ops_name'] = trim($_POST['ops']);
                }
            }
            if($_POST['trucking_vendor'] != ""){
                if ($_POST['trucking'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['trucking_vendor']),
                        'shipment_vendor_phone' => trim($_POST['trucking_phone']),
                        'vendor_type' => 2,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['trucking_vendor'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['trucking_vendor'] = trim($_POST['trucking']);
                }
            }
            if($_POST['barging_vendor'] != ""){
                if ($_POST['barging'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['barging_vendor']),
                        'shipment_vendor_phone' => trim($_POST['barging_phone']),
                        'vendor_type' => 3,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['barging_vendor'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['barging_vendor'] = trim($_POST['barging']);
                }
            }
            if($_POST['feeder_vendor'] != ""){
                if ($_POST['feeder'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['feeder_vendor']),
                        'shipment_vendor_phone' => trim($_POST['feeder_phone']),
                        'vendor_type' => 4,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['feeder_vendor'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['feeder_vendor'] = trim($_POST['feeder']);
                }
            }
            if($_POST['handling_port'] != ""){
                if ($_POST['port1'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['handling_port']),
                        'vendor_type' => 6,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['handling_port'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['handling_port'] = trim($_POST['port1']);
                }
            }
            if($_POST['handling_port2'] != ""){
                if ($_POST['port2'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['handling_port2']),
                        'vendor_type' => 6,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['handling_port2'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['handling_port2'] = trim($_POST['port2']);
                }
            }
            if($_POST['commission_name'] != ""){
                if ($_POST['commission'] == null) {
                     $vendor_data = array(
                        'shipment_vendor_name'=> trim($_POST['commission_name']),
                        'shipment_vendor_phone' => trim($_POST['commission_phone']),
                        'vendor_type' => 5,
                    );
                     $vendor->createVendor($vendor_data);

                     $data['commission'] = $vendor->getLastVendor()->shipment_vendor_id;
                }
                else{
                    $data['commission'] = trim($_POST['commission']);
                }
            }*/


            if ($_POST['customer'] == null) {
                 
                 $customer_data = array(
                    'customer_name'=> trim($_POST['customer_name']),
                    'customer_phone' => trim($_POST['customer_phone']),
                    'customer_email' => trim($_POST['customer_email']),
                );
                 $customer->createCustomer($customer_data);

                 $data['customer'] = $customer->getLastCustomer()->customer_id;
            }
            else{
                $data['customer'] = trim($_POST['customer']);
            }

            /**************/
            $vendor_cost = $_POST['vendor_cost'];
            $vendor_cost4 = $_POST['vendor_cost4'];
            $vendor_cost2 = $_POST['vendor_cost2'];
            $other_customers = $_POST['customer_sale'];
            /**************/
            $sale_vendor = $this->model->get('salevendorModel');
            $trading_vendor = $this->model->get('tradingvendorModel');
            $customer_sale = $this->model->get('customersaleModel');
            
            
            $other_cost_model = $this->model->get('othercostModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $costs = $this->model->get('costsModel');

            $sales_model = $this->model->get('salesModel');
            $pending_payable = $this->model->get('pendingpayableModel');

            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                    $sale_data = $sale->getSale($_POST['yes']);
                    $staff_data = $staff_model->getStaffByWhere(array('staff_id'=>$sale_data->staff));

                    $data['count_update'] = $sale_data->count_update+1;

                    if($sale_data->count_update > 0){
                        $data['sale_lock'] = 1;
                    }
                
                    $sale->updateSale($data,array('sale_report_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;

                    $net_cost = 0;
                    $net_cost_vat = 0;
                    $sell_cost = 0;
                    $sell_cost_vat = 0;

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
                            'check_cost' => 2,
                        );

                        if ($sale_vendor_data['check_deposit'] != 1) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];

                            $sell_cost += $sale_vendor_data['cost'];
                            $sell_cost_vat += $sale_vendor_data['cost_vat'];
                        }
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>2,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_cost=2 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'check_vat'=>0,
                                    'check_cost'=>2,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_cost=2 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }
                               


                                $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                
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
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                        'check_cost'=>2,
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
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>0,
                                        'check_cost'=>2,
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
                 
                                
                            }
                        }

                        else{

                            if($sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                    $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                    $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                    $total = $old_cost+$old_cost_vat;

                                    $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                    $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                    $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($sale_vendor_data['cost'] == 0) {
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
                                                'type' => 4,
                                                'trading' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                                'check_cost'=>2,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($sale_vendor_data['cost_vat'] == 0) {
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
                                                'type' => 4,
                                                'trading' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                                'check_cost'=>2,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
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


                                    $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
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
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                        'check_cost'=>2,
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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>2,
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


                    foreach ($vendor_cost4 as $v) {
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
                            'check_cost' => 1,
                        );

                        if ($sale_vendor_data['check_deposit'] != 1) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];

                            $net_cost += $sale_vendor_data['cost'];
                            $net_cost_vat += $sale_vendor_data['cost_vat'];
                        }
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_cost=1 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'check_vat'=>0,
                                    'check_cost'=>1,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_cost=1 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }
                               


                                $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                
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
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                        'check_cost'=>1,
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
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>0,
                                        'check_cost'=>1,
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
                 
                                
                            }
                        }

                        else{

                            if($sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                    $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                    $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                    $total = $old_cost+$old_cost_vat;

                                    $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                    $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                    $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($sale_vendor_data['cost'] == 0) {
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
                                                'type' => 4,
                                                'trading' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                                'check_cost'=>1,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($sale_vendor_data['cost_vat'] == 0) {
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
                                                'type' => 4,
                                                'trading' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                                'check_cost'=>1,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
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
                                            'type' => 4,
                                            'trading' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'trading'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                    }
                                    

                                    $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
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
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                        'check_cost'=>1,
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
                                    'type' => 4,
                                    'trading' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>1,
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

                    $kvat2 = 0;
                    $vat2 = 0;
                    $estimate2 = 0;

                    foreach ($vendor_cost2 as $v) {
                        if(trim(str_replace(',','',$v['cost'])) > 0 || trim(str_replace(',','',$v['cost_vat'])) > 0){
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
                            );

                            $kvat2 += $sale_vendor_data['cost'];
                            $vat2 += $sale_vendor_data['cost_vat'];
                            $estimate2 += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                            if($trading_vendor->getVendorByWhere(array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                
                                $trading_vendor_data = $trading_vendor->getVendorByWhere(array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));

                                $trading_vendor->updateVendor($sale_vendor_data,array('trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));

                                /*if($data['staff_cost'] > 0 && $data['staff'] > 0){
                                    
                                    $data_staff_debt = array(
                                                'staff' => $data['staff'],
                                                'source' => 1,
                                                'money' => 0 - ($sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost']),
                                                'staff_debt_date' => $sale_vendor_data['expect_date'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'status' => 1,
                                            );
                                    if($data_staff_debt['week'] == 53){
                                        $data_staff_debt['week'] = 1;
                                        $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $data_staff_debt['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    $staff_debt_model->updateCost($data_staff_debt,array('staff_debt_date'=>$trading_vendor_data->expect_date,'money'=>(0 - ($trading_vendor_data->cost+$trading_vendor_data->invoice_cost+$trading_vendor_data->pay_cost+$trading_vendor_data->cost_vat+$trading_vendor_data->document_cost)),'staff'=>$data['staff']));
                                }*/
                            }
                            else{
                                $trading_vendor->createVendor($sale_vendor_data);

                                /*if($data['staff_cost'] > 0 && $data['staff'] > 0){
                                    
                                    $data_staff_debt = array(
                                                'staff' => $data['staff'],
                                                'source' => 1,
                                                'money' => 0 - ($sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost']),
                                                'staff_debt_date' => $sale_vendor_data['expect_date'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'status' => 1,
                                            );
                                    if($data_staff_debt['week'] == 53){
                                        $data_staff_debt['week'] = 1;
                                        $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $data_staff_debt['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    $staff_debt_model->createCost($data_staff_debt);
                                }*/
                                                           
                            }

                        }
                    }

                    $other_revenue = 0;
                    $other_revenue_vat = 0;

                    foreach ($other_customers as $v) {
                        $customer_sale_data = array(
                            'sale_report' => $_POST['yes'],
                            'customer' => $v['other_customer'],
                            'bank' => $v['other_source'],
                            'comment' => $v['other_comment'],
                            'revenue_vat' => trim(str_replace(',','',$v['other_revenue_vat'])),
                            'revenue' => trim(str_replace(',','',$v['other_revenue'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['other_expect_date']))),
                            
                        );

                            $other_revenue += $customer_sale_data['revenue'];
                            $other_revenue_vat += $customer_sale_data['revenue_vat'];
                        if ($customer_sale_data['customer'] != "" ) {

                            $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$customer_sale_data['expect_date']). ' - 1 days')));
                            $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$customer_sale_data['expect_date']). ' + 1 days')));
                            
                            $check_customer = $customer_sale->queryCustomer('SELECT * FROM customer_sale WHERE sale_report='.$_POST['yes'].' AND customer='.$v['other_customer'].' AND expect_date>'.$ngaytruoc.' AND expect_date<'.$ngaysau);
                            
                            if($check_customer){
                                foreach ($check_customer as $key) {
                                    $old_revenue = $key->revenue;
                                    $old_revenue_vat = $key->revenue_vat;
                                    $id_customer_sale = $key->customer_sale_id;
                                }
                                    $total = $old_revenue+$old_revenue_vat;

                                    $ob = $obtain->getObtainByWhere(array('trading'=>$_POST['yes'],'customer'=>$customer_sale_data['customer'],'money'=>$total));

                                    $obtain_data = array(
                                        'obtain_date' => $sale_data->sale_date,
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat']+$customer_sale_data['revenue'],
                                        'week' => (int)date('W',$sale_data->sale_date),
                                        'year' => (int)date('Y',$sale_data->sale_date),
                                        'trading' => $_POST['yes'],
                                    );
                                    if($obtain_data['week'] == 53){
                                        $obtain_data['week'] = 1;
                                        $obtain_data['year'] = $obtain_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                        $obtain_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                    }

                                    $obtain->updateObtain($obtain_data,array('trading'=>$_POST['yes'],'customer'=>$customer_sale_data['customer'],'money'=>$total));

                                    $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>1,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    if($receivable->getCostsByWhere(array('money'=>$old_revenue_vat,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])))){
                                        
                                        $re = $receivable->getCostsByWhere(array('money'=>$old_revenue_vat,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])));

                                            $receivable->updateCosts($receivable_data,array('money'=>$old_revenue_vat,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])));

                                    }
                                    elseif(!$receivable->getCostsByWhere(array('money'=>$old_revenue_vat,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])))){
                                        if($customer_sale_data['revenue_vat'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                                    }
                                    
                                


                                    $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>0,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    if($receivable->getCostsByWhere(array('money'=>$old_revenue,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])))){
                                        $re = $receivable->getCostsByWhere(array('money'=>$old_revenue,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])));
                                            $receivable->updateCosts($receivable_data,array('money'=>$old_revenue,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])));
                                        
                                    }
                                    elseif(!$receivable->getCostsByWhere(array('money'=>$old_revenue,'customer' => $customer_sale_data['customer'],'trading'=>trim($_POST['yes'])))){
                                        if($customer_sale_data['revenue'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                                    }
                                    
                                    
                                   

                                    $customer_sale->updateCustomer($customer_sale_data,array('customer_sale_id'=>$id_customer_sale));
                            }
                            else{
                                $customer_sale->createCustomer($customer_sale_data);

                                $obtain_data = array(
                                        'obtain_date' => $sale_data->sale_date,
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat']+$customer_sale_data['revenue'],
                                        'week' => (int)date('W',$sale_data->sale_date),
                                        'year' => (int)date('Y',$sale_data->sale_date),
                                        'trading' => $_POST['yes'],
                                    );
                                    if($obtain_data['week'] == 53){
                                        $obtain_data['week'] = 1;
                                        $obtain_data['year'] = $obtain_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                        $obtain_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                    }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    
                                    $obtain->createObtain($obtain_data);

                                    $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>1,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    
                                        if($customer_sale_data['revenue_vat'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                                    
                                


                                $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>0,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    
                                        if($customer_sale_data['revenue'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }


                                
                            }

                        }


                    }


                    $data_update = array(
                        'cost' => $kvat+$kvat2,
                        'cost_vat' => $vat+$vat2,
                        'profit' => $sale_data->revenue-$kvat-$kvat2+$other_revenue,
                        'profit_vat' => $sale_data->revenue_vat-$vat-$vat2+$other_revenue_vat,
                        'estimate_cost' => $estimate,
                        'estimate_cost_2' => $estimate2,
                        'other_revenue' => $other_revenue,
                        'other_revenue_vat' => $other_revenue_vat,
                        'sell_cost' => $sell_cost,
                        'sell_cost_vat' => $sell_cost_vat,
                        'net_cost' => $net_cost,
                        'net_cost_vat' => $net_cost_vat,

                    );
                    
                    $sale->updateSale($data_update,array('sale_report_id' => $_POST['yes']));

                    $sale_datas = $sale->getSale($_POST['yes']);

                    $data_pending = array(
                            'code' => $sale_datas->code,
                            'revenue' => $sale_datas->revenue+$sale_datas->revenue_vat+$sale_datas->other_revenue+$sale_datas->other_revenue_vat,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->estimate_cost_2+$sale_datas->estimate_cost,
                            
                            'money' => $sale_datas->cost+$sale_datas->cost_vat,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                            'approve' => null,
                        );

                    $check = $pending_payable->getCostsByWhere(array('trading'=>$_POST['yes']));

                    if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                        $data_pending['approve'] = 10;
                    }

                        $pending_payable->updateCosts($data_pending,array('trading'=>$_POST['yes']));

                    $salesdata = $sales_model->getSalesByWhere(array('trading'=>$_POST['yes']));

                    if ($salesdata) {
                        $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'revenue' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat,
                            'cost' => $kvat+$vat+$estimate+$estimate2+$sale_data->staff_cost,
                            'profit' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat-$kvat-$vat-$estimate-$estimate2-$sale_data->staff_cost,
                            'sales_create_time' => $data['sale_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'trading' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
                    }
                    elseif (!$salesdata) {
                        $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'revenue' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat,
                            'cost' => $kvat+$vat+$estimate+$estimate2+$sale_data->staff_cost,
                            'profit' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat-$kvat-$vat-$estimate-$estimate2-$sale_data->staff_cost,
                            'sales_create_time' => $data['sale_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'trading' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->createSales($data_sales);
                    }


                    if($sale_data->staff_cost > 0 && $sale_data->staff > 0){
                        $data_staff_debt = array(
                                    'staff' => $data['staff'],
                                    'source' => 1,
                                    'money' => $data['staff_cost'],
                                    'staff_debt_date' => $data['sale_date'],
                                    'comment' => $data['comment'],
                                    'week' => (int)date('W',$data['sale_date']),
                                    'year' => (int)date('Y',$data['sale_date']),
                                    'status' => 2,
                                    'check_trading' => 1,
                                );
                        if($data_staff_debt['week'] == 53){
                            $data_staff_debt['week'] = 1;
                            $data_staff_debt['year'] = $data_staff_debt['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $data_staff_debt['year'] = (int)date('Y',$data['sale_date'])+1;
                        }

                        $staff_debt_model->updateCost($data_staff_debt,array('staff_debt_date'=>$sale_data->sale_date,'money'=>$sale_data->staff_cost,'staff'=>$data['staff']));

                        $advance = $this->model->get('advanceModel');
                        $advance_data = array(
                                    'advance_date' => $data['sale_date'],
                                    'comment' => $data['comment'],
                                    'money' => $data['staff_cost'],
                                    'expect_date' => $data['sale_date'],
                                    'week' => (int)date('W', $data['sale_date']),
                                    'staff' => $data['staff'],
                                    'source' => 1,
                                    'year' => (int)date('Y',$data['sale_date']),
                                    );
                        if($advance_data['week'] == 53){
                            $advance_data['week'] = 1;
                            $advance_data['year'] = $advance_data['year']+1;
                        }
                        if (((int)date('W', $advance_data['expect_date']) == 1) && ((int)date('m',$advance_data['expect_date']) == 12) ) {
                            $advance_data['year'] = (int)date('Y',$advance_data['expect_date'])+1;
                        }
                        $advance->updateCosts($advance_data,array('staff'=>$sale_data->staff,'advance_date'=>$sale_data->sale_date,'money'=>$sale_data->staff_cost));

                        $data_receivable = array(
                            'staff' => $data['staff'],
                            'money' => $data['staff_cost'],
                            'receivable_date' => $data['sale_date'],
                            'expect_date' => $data['sale_date'],
                            'week' => (int)date('W',$data['sale_date']),
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'year' => (int)date('Y',$data['sale_date']),
                            'type' => 5,
                            'source' => $data['source'],
                        );

                        $receivable->updateCosts($data_receivable,array('staff'=>$sale_data->staff,'money'=>$sale_data->staff_cost,'receivable_date'=>$sale_data->sale_date));
                    }
                    else{
                        if($data['staff_cost'] > 0 && $data['staff'] > 0){
                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => 1,
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['sale_date'],
                                        'comment' => $data['comment'],
                                        'week' => (int)date('W',$data['sale_date']),
                                        'year' => (int)date('Y',$data['sale_date']),
                                        'status' => 2,
                                        'check_trading' => 1,
                                    );
                            if($data_staff_debt['week'] == 53){
                                $data_staff_debt['week'] = 1;
                                $data_staff_debt['year'] = $data_staff_debt['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $data_staff_debt['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $staff_debt_model->createCost($data_staff_debt);

                            $advance = $this->model->get('advanceModel');
                            $advance_data = array(
                                        'advance_date' => $data['sale_date'],
                                        'comment' => $data['comment'],
                                        'money' => $data['staff_cost'],
                                        'expect_date' => $data['sale_date'],
                                        'week' => (int)date('W', $data['sale_date']),
                                        'staff' => $data['staff'],
                                        'source' => 1,
                                        'year' => (int)date('Y',$data['sale_date']),
                                        );
                            if($advance_data['week'] == 53){
                                $advance_data['week'] = 1;
                                $advance_data['year'] = $advance_data['year']+1;
                            }
                            if (((int)date('W', $advance_data['expect_date']) == 1) && ((int)date('m',$advance_data['expect_date']) == 12) ) {
                                $advance_data['year'] = (int)date('Y',$advance_data['expect_date'])+1;
                            }
                            $advance->createCosts($advance_data);

                            $data_receivable = array(
                                'staff' => $data['staff'],
                                'money' => $data['staff_cost'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'year' => (int)date('Y',$data['sale_date']),
                                'type' => 5,
                                'source' => $data['source'],
                            );

                            $receivable->createCosts($data_receivable);

                        }
                    }

                    if($sale_data->revenue_vat > 0){
                        if($data['revenue_vat'] > 0){
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['revenue_vat'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['expect_date'],
                                'week' => (int)date('W',$data['expect_date']),
                                'year' => (int)date('Y',$data['expect_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $_POST['yes'],
                                'check_vat' => 1,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                            }

                            $receivable->updateCosts($receivable_data,array('money'=>$sale_data->revenue_vat,'trading'=>$_POST['yes'],'check_vat'=>1));
                        }
                        else{
                            $receivable->queryCosts('DELETE FROM receivable WHERE trading='.$_POST['yes'].' AND customer='.$sale_data->customer.' AND money='.$sale_data->revenue_vat.' AND check_vat=1');
                        }
                    }
                    else{
                        if ($data['revenue_vat'] > 0) {
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['revenue_vat'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['expect_date'],
                                'week' => (int)date('W',$data['expect_date']),
                                'year' => (int)date('Y',$data['expect_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $_POST['yes'],
                                'check_vat' => 1,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                            }

                            $receivable->createCosts($receivable_data);
                        }
                    }

                    if($sale_data->revenue > 0){
                        if($data['revenue'] > 0){
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['revenue'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['expect_date'],
                                'week' => (int)date('W',$data['expect_date']),
                                'year' => (int)date('Y',$data['expect_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $_POST['yes'],
                                'check_vat' => 0,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                            }

                            $receivable->updateCosts($receivable_data,array('money'=>$sale_data->revenue,'trading'=>$_POST['yes'],'check_vat'=>'0'));
                        }
                        else{
                            $receivable->queryCosts('DELETE FROM receivable WHERE trading='.$_POST['yes'].' AND customer='.$sale_data->customer.' AND money='.$sale_data->revenue.' AND check_vat=0');
                        }
                    }
                    else{
                        if ($data['revenue'] > 0) {
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['revenue'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['expect_date'],
                                'week' => (int)date('W',$data['expect_date']),
                                'year' => (int)date('Y',$data['expect_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $_POST['yes'],
                                'check_vat' => 0,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                            }

                            $receivable->createCosts($receivable_data);
                        }
                    }

                    if ($data['deposit'] > 0) {
                        if($sale_data->deposit>0){
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => 'Đặt cọc '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $_POST['yes'],
                                'check_vat' => 0,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $receivable->updateCosts($receivable_data,array('customer'=>$sale_data->customer,'money'=>$sale_data->deposit,'sale_report'=>$_POST['yes'],'check_vat'=>'0'));

                            $payable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'payable_date' => $data['sale_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => 'Đặt cọc '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $_POST['yes'],
                                'cost_type' => 7,
                                'check_vat'=>0,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $payable->updateCosts($payable_data,array('customer'=>$sale_data->customer,'money'=>$sale_data->deposit,'sale_report'=>$_POST['yes'],'check_vat'=>'0'));
                        }
                        else{
                            $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => 'Đặt cọc '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $_POST['yes'],
                                'check_vat' => 0,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $receivable->createCosts($receivable_data);

                            $payable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'payable_date' => $data['sale_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => 'Đặt cọc '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $_POST['yes'],
                                'cost_type' => 7,
                                'check_vat'=>0,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $payable->createCosts($payable_data);
                        }
                    }
                    elseif ($data['deposit'] == 0) {
                        if($sale_data->deposit>0){
                            $receivable->queryCosts('DELETE FROM receivable WHERE sale_report='.$_POST['yes'].' AND customer='.$sale_data->customer.' AND money='.$sale_data->deposit.' AND check_vat=0');
                            $payable->queryCosts('DELETE FROM payable WHERE sale_report='.$_POST['yes'].' AND customer='.$sale_data->customer.' AND money='.$sale_data->deposit.' AND check_vat=0');
                        }
                    }


                    $obtain_data = array(
                        'obtain_date' => $data['sale_date'],
                        'customer' => $data['customer'],
                        'money' => $data['revenue']+$data['revenue_vat'],
                        'week' => (int)date('W',$data['sale_date']),
                        'year' => (int)date('Y',$data['sale_date']),
                    );
                    if($obtain_data['week'] == 53){
                        $obtain_data['week'] = 1;
                        $obtain_data['year'] = $obtain_data['year']+1;
                    }
                    if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                        $obtain_data['year'] = (int)date('Y',$data['sale_date'])+1;
                    }
                    $obtain->updateObtain($obtain_data,array('money'=>($sale_data->revenue_vat+$sale_data->revenue),'trading'=>trim($_POST['yes'])));

                    

                   


                    /*if($data['ops_name'] > 0){
                        $payable_data = array(
                            'vendor' => $data['ops_name'],
                            'money' => $data['ops_cost'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['ops_expect_date'],
                            'week' => (int)date('W',$data['ops_expect_date']),
                            'year' => (int)date('Y',$data['ops_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['ops_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['ops_expect_date']) == 1) && ((int)date('m',$data['ops_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['ops_expect_date'])+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor' => $data['ops_name'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['ops_name'],
                            'money' => $data['ops_cost'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['ops_name'],'trading'=>trim($_POST['yes'])));

                    }
                    if($data['trucking_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['trucking_vendor'],
                            'money' => $data['trucking_cost']+$data['trucking_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['trucking_expect_date'],
                            'week' => (int)date('W',$data['trucking_expect_date']),
                            'year' => (int)date('Y',$data['trucking_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['trucking_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        // $name = $vendor->getVendor($data['trucking_vendor'])->shipment_vendor_name;
                        // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                        //     $payable_data['type'] = 3;
                        // }
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['trucking_expect_date']) == 1) && ((int)date('m',$data['trucking_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['trucking_expect_date'])+1;
                        }

                        $payable->updateCosts($payable_data,array('vendor' => $data['trucking_vendor'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['trucking_vendor'],
                            'money' => $data['trucking_cost']+$data['trucking_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['trucking_vendor'],'trading'=>trim($_POST['yes'])));
                    }
                    if($data['barging_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['barging_vendor'],
                            'money' => $data['barging_cost']+$data['barging_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['barging_expect_date'],
                            'week' => (int)date('W',$data['barging_expect_date']),
                            'year' => (int)date('Y',$data['barging_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['barging_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['barging_expect_date']) == 1) && ((int)date('m',$data['barging_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['barging_expect_date'])+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor' => $data['barging_vendor'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['barging_vendor'],
                            'money' => $data['barging_cost']+$data['barging_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['barging_vendor'],'trading'=>trim($_POST['yes'])));
                    }
                    if($data['feeder_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['feeder_vendor'],
                            'money' => $data['feeder_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['feeder_expect_date'],
                            'week' => (int)date('W',$data['feeder_expect_date']),
                            'year' => (int)date('Y',$data['feeder_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['feeder_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['feeder_expect_date']) == 1) && ((int)date('m',$data['feeder_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['feeder_expect_date'])+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor' => $data['feeder_vendor'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['feeder_vendor'],
                            'money' => $data['feeder_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['feeder_vendor'],'trading'=>trim($_POST['yes'])));
                    }
                    if($data['commission'] > 0){
                        $payable_data = array(
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['commission_expect_date'],
                            'week' => (int)date('W',$data['commission_expect_date']),
                            'year' => (int)date('Y',$data['commission_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['commission_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['commission_expect_date']) == 1) && ((int)date('m',$data['commission_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['commission_expect_date'])+1;
                        }

                        $payable->updateCosts($payable_data,array('vendor' => $data['commission'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['commission'],'trading'=>trim($_POST['yes'])));
                    }
                    if($data['handling_port'] > 0){
                        $payable_data = array(
                            'vendor' => $data['handling_port'],
                            'money' => $data['handling_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['port1_expect_date'],
                            'week' => (int)date('W',$data['port1_expect_date']),
                            'year' => (int)date('Y',$data['port1_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['port1_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['port1_expect_date']) == 1) && ((int)date('m',$data['port1_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['port1_expect_date'])+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor' => $data['handling_port'],'trading'=>trim($_POST['yes'])));

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['handling_port'],
                            'money' => $data['handling_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['handling_port'],'trading'=>trim($_POST['yes'])));
                    }
                    if($data['handling_port2'] > 0){
                        $payable_data = array(
                            'vendor' => $data['handling_port2'],
                            'money' => $data['handling_cost_vat2'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['port2_expect_date'],
                            'week' => (int)date('W',$data['port2_expect_date']),
                            'year' => (int)date('Y',$data['port2_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['port2_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['port2_expect_date']) == 1) && ((int)date('m',$data['port2_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['port2_expect_date'])+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor' => $data['handling_port2'],'trading'=>trim($_POST['yes'])));
                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['handling_port2'],
                            'money' => $data['handling_cost_vat2'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->updateOwe($owe_data,array('vendor' => $data['handling_port2'],'trading'=>trim($_POST['yes'])));
                    }


                    $other_cost_model->deleteOthercost2($_POST['yes']);

                    if ($_POST['other_cost'] != "") {
                    
                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost2'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost2'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost2']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost3'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost3'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost3']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost_vat'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost_vat']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat2'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost_vat2'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost_vat2']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat3'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost_vat3'])),
                            'trading' => $_POST['yes'],
                            'comment' => trim($_POST['comment_cost_vat3']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }*/


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|trading|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    if($data['code']==0){
                        $code_model = $this->model->get('codeModel');
                        $last_code = $code_model->getLastCode()->code;
                        $nam = substr(date('Y'), 2);
                        $thang = date('m');

                        if (substr($last_code, 0, 4) != $nam.$thang) {
                            $code_data = array(
                                'code' => $nam.$thang.'01',
                            );
                            $code_model->createCode($code_data);

                            $data['code'] = $code_model->getLastCode()->code;
                        }
                        else{
                            $code_data = array(
                                'code' => (int)$last_code + 1,
                            );
                            $code_model->createCode($code_data);

                            $data['code'] = $code_data['code'];
                        }
                    }



                    $data['cost'] = 0;
                    $data['cost_vat'] = 0;

                    $data['sale'] = $_SESSION['userid_logined'];
                    
                    $sale->createSale($data);
                    echo "Thêm thành công";

                    /*********************/
                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;

                    $net_cost = 0;
                    $net_cost_vat = 0;
                    $sell_cost = 0;
                    $sell_cost_vat = 0;

                    $id_trading = $sale->getLastSale()->sale_report_id;
                    $sale_data = $sale->getSale($id_trading);

                    foreach ($vendor_cost as $v) {
                        $sale_vendor_data = array(
                            'trading' => $id_trading,
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
                            'check_cost'=>2,
                        );

                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        $kvat += $sale_vendor_data['cost'];
                        $vat += $sale_vendor_data['cost_vat'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        $sell_cost += $sale_vendor_data['cost'];
                        $sell_cost_vat += $sale_vendor_data['cost_vat'];

                        $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->sale_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->sale_date),
                                'year' => (int)date('Y',$sale_data->sale_date),
                                'trading' => $id_trading,
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
                                    'payable_date' => strtotime(date('d-m-Y')),
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'trading' => $id_trading,
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'check_cost'=>2,
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
                                'payable_date' => strtotime(date('d-m-Y')),
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $id_trading,
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                                'check_cost'=>2,
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


                    foreach ($vendor_cost4 as $v) {
                        $sale_vendor_data = array(
                            'trading' => $id_trading,
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
                            'check_cost'=>1,
                        );

                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        $kvat += $sale_vendor_data['cost'];
                        $vat += $sale_vendor_data['cost_vat'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        $net_cost += $sale_vendor_data['cost'];
                        $net_cost_vat += $sale_vendor_data['cost_vat'];

                        $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->sale_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->sale_date),
                                'year' => (int)date('Y',$sale_data->sale_date),
                                'trading' => $id_trading,
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
                                    'payable_date' => strtotime(date('d-m-Y')),
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'trading' => $id_trading,
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'check_cost'=>1,
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
                                'payable_date' => strtotime(date('d-m-Y')),
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'trading' => $id_trading,
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                                'check_cost'=>1,
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


                    ////////////////////


                    $kvat2 = 0;
                    $vat2 = 0;
                    $estimate2 = 0;

                    foreach ($vendor_cost2 as $v) {
                        if(trim(str_replace(',','',$v['cost'])) > 0 || trim(str_replace(',','',$v['cost_vat'])) > 0){
                            $sale_vendor_data = array(
                                'trading' => $id_trading,
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
                            );

                            //$kvat2 += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                            //$vat2 += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                            $kvat2 += $sale_vendor_data['cost'];
                            $vat2 += $sale_vendor_data['cost_vat'];
                            $estimate2 += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                            $trading_vendor->createVendor($sale_vendor_data);

                            /*if($data['staff_cost'] > 0 && $data['staff'] > 0){
                                
                                $data_staff_debt = array(
                                            'staff' => $data['staff'],
                                            'source' => 1,
                                            'money' => 0 - ($sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost']),
                                            'staff_debt_date' => $sale_vendor_data['expect_date'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'status' => 1,
                                        );
                                if($data_staff_debt['week'] == 53){
                                    $data_staff_debt['week'] = 1;
                                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $data_staff_debt['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                $staff_debt_model->createCost($data_staff_debt);
                            }*/
                        }

                    }

                    $other_revenue = 0;
                    $other_revenue_vat = 0;

                    foreach ($other_customers as $v) {
                        $customer_sale_data = array(
                            'sale_report' => $_POST['yes'],
                            'customer' => $v['other_customer'],
                            'bank' => $v['other_source'],
                            'comment' => $v['other_comment'],
                            'revenue_vat' => trim(str_replace(',','',$v['other_revenue_vat'])),
                            'revenue' => trim(str_replace(',','',$v['other_revenue'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['other_expect_date']))),
                            
                        );

                            $other_revenue += $customer_sale_data['revenue'];
                            $other_revenue_vat += $customer_sale_data['revenue_vat'];
                        

                        $customer_sale->createCustomer($customer_sale_data);

                                $obtain_data = array(
                                        'obtain_date' => $sale_data->sale_date,
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat']+$customer_sale_data['revenue'],
                                        'week' => (int)date('W',$sale_data->sale_date),
                                        'year' => (int)date('Y',$sale_data->sale_date),
                                        'trading' => $_POST['yes'],
                                    );
                                    if($obtain_data['week'] == 53){
                                        $obtain_data['week'] = 1;
                                        $obtain_data['year'] = $obtain_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->sale_date) == 1) && ((int)date('m',$sale_data->sale_date) == 12) ) {
                                        $obtain_data['year'] = (int)date('Y',$sale_data->sale_date)+1;
                                    }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    
                                    $obtain->createObtain($obtain_data);

                                    $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue_vat'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>1,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    
                                        if($customer_sale_data['revenue_vat'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                                    
                                


                                $receivable_data = array(
                                        'customer' => $customer_sale_data['customer'],
                                        'money' => $customer_sale_data['revenue'],
                                        'receivable_date' => $sale_data->sale_date,
                                        'expect_date' => $customer_sale_data['expect_date'],
                                        'week' => (int)date('W',$customer_sale_data['expect_date']),
                                        'year' => (int)date('Y',$customer_sale_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $customer_sale_data['bank'],
                                        'comment' => $customer_sale_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'trading' => $_POST['yes'],
                                        'check_vat'=>0,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$customer_sale_data['expect_date']) == 1) && ((int)date('m',$customer_sale_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$customer_sale_data['expect_date'])+1;
                                    }

                                    
                                        if($customer_sale_data['revenue'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }


                    }

                    $data_update = array(
                        'procument' => $_SESSION['userid_logined'],
                        'cost' => $kvat+$kvat2,
                        'cost_vat' => $vat+$vat2,
                        'profit' => $sale_data->revenue-$kvat-$kvat2+$other_revenue,
                        'profit_vat' => $sale_data->revenue_vat-$vat-$vat2+$other_revenue_vat,
                        'estimate_cost' => $estimate,
                        'estimate_cost_2' => $estimate2,
                        'other_revenue' => $other_revenue,
                        'other_revenue_vat' => $other_revenue_vat,
                        'sell_cost' => $sell_cost,
                        'sell_cost_vat' => $sell_cost_vat,
                        'net_cost' => $net_cost,
                        'net_cost_vat' => $net_cost_vat,

                    );
                    
                    $sale->updateSale($data_update,array('sale_report_id' => $id_trading));

                    $sale_datas = $sale->getSale($id_trading);

                    $data_pending = array(
                            'code' => $sale_datas->code,
                            'revenue' => $sale_datas->revenue+$sale_datas->revenue_vat+$sale_datas->other_revenue+$sale_datas->other_revenue_vat,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->estimate_cost_2+$sale_datas->estimate_cost,
                            'trading' => $id_trading,
                            'money' => $sale_datas->cost+$sale_datas->cost_vat,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                        );

                        $pending_payable->createCosts($data_pending);


                    $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'revenue' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat,
                            'cost' => $kvat+$vat+$estimate+$estimate2+$sale_data->staff_cost,
                            'profit' => $data['revenue']+$data['revenue_vat']+$other_revenue+$other_revenue_vat-$kvat-$vat-$estimate-$estimate2-$sale_data->staff_cost,
                            'sales_create_time' => $data['sale_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'trading' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->createSales($data_sales);

                    /**************/

                    if($data['staff_cost'] > 0 && $data['staff'] > 0){
                        $data_staff_debt = array(
                                    'staff' => $data['staff'],
                                    'source' => 1,
                                    'money' => $data['staff_cost'],
                                    'staff_debt_date' => $data['sale_date'],
                                    'comment' => $data['comment'],
                                    'week' => (int)date('W',$data['sale_date']),
                                    'year' => (int)date('Y',$data['sale_date']),
                                    'status' => 2,
                                    'check_trading' => 1,
                                );
                        if($data_staff_debt['week'] == 53){
                            $data_staff_debt['week'] = 1;
                            $data_staff_debt['year'] = $data_staff_debt['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $data_staff_debt['year'] = (int)date('Y',$data['sale_date'])+1;
                        }

                        $staff_debt_model->createCost($data_staff_debt);

                        $advance = $this->model->get('advanceModel');
                        $advance_data = array(
                                    'advance_date' => $data['sale_date'],
                                    'comment' => $data['comment'],
                                    'money' => $data['staff_cost'],
                                    'expect_date' => $data['sale_date'],
                                    'week' => (int)date('W', $data['sale_date']),
                                    'staff' => $data['staff'],
                                    'source' => 1,
                                    'year' => (int)date('Y',$data['sale_date']),
                                    );
                        if($advance_data['week'] == 53){
                            $advance_data['week'] = 1;
                            $advance_data['year'] = $advance_data['year']+1;
                        }
                        if (((int)date('W', $advance_data['expect_date']) == 1) && ((int)date('m',$advance_data['expect_date']) == 12) ) {
                            $advance_data['year'] = (int)date('Y',$advance_data['expect_date'])+1;
                        }
                        $advance->createCosts($advance_data);

                        //$receivable = $this->model->get('receivableModel');
            
                        $data_receivable = array(
                            'staff' => $data['staff'],
                            'money' => $data['staff_cost'],
                            'receivable_date' => $data['sale_date'],
                            'expect_date' => $data['sale_date'],
                            'week' => (int)date('W',$data['sale_date']),
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'year' => (int)date('Y',$data['sale_date']),
                            'type' => 5,
                            'source' => $data['source'],
                        );

                        $receivable->createCosts($data_receivable);

                    }


                    if($data['revenue_vat'] > 0){
                        $receivable_data = array(
                            'customer' => $data['customer'],
                            'money' => $data['revenue_vat'],
                            'receivable_date' => $data['sale_date'],
                            'expect_date' => $data['expect_date'],
                            'week' => (int)date('W',$data['expect_date']),
                            'year' => (int)date('Y',$data['expect_date']),
                            'code' => $data['code'],
                            'source' => $data['customer_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'check_vat' => 1,
                        );

                        
                        // $name = $customer->getCustomer($data['customer'])->customer_name;
                        // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                        //     $receivable_data['type'] = 3;
                        // }
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                        }

                        $receivable->createCosts($receivable_data);
                    }

                    if($data['revenue'] > 0){
                        $receivable_data = array(
                            'customer' => $data['customer'],
                            'money' => $data['revenue'],
                            'receivable_date' => $data['sale_date'],
                            'expect_date' => $data['expect_date'],
                            'week' => (int)date('W',$data['expect_date']),
                            'year' => (int)date('Y',$data['expect_date']),
                            'code' => $data['code'],
                            'source' => $data['customer_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'check_vat' => 0,
                        );

                        
                        // $name = $customer->getCustomer($data['customer'])->customer_name;
                        // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                        //     $receivable_data['type'] = 3;
                        // }
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$data['expect_date'])+1;
                        }

                        $receivable->createCosts($receivable_data);
                    }

                    if ($data['deposit'] > 0) {
                        $receivable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'receivable_date' => $data['sale_date'],
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $sale->getLastSale()->sale_report_id,
                                'check_vat' => 0,
                            );

                            
                            // $name = $customer->getCustomer($data['customer'])->customer_name;
                            // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                            //     $receivable_data['type'] = 3;
                            // }
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $receivable->createCosts($receivable_data);

                            $payable_data = array(
                                'customer' => $data['customer'],
                                'money' => $data['deposit'],
                                'payable_date' => $data['sale_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['sale_date'],
                                'week' => (int)date('W',$data['sale_date']),
                                'year' => (int)date('Y',$data['sale_date']),
                                'code' => $data['code'],
                                'source' => $data['customer_source'],
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 1,
                                'sale_report' => $sale->getLastSale()->sale_report_id,
                                'cost_type' => 7,
                                'check_vat'=>0,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['sale_date'])+1;
                            }

                            $payable->createCosts($payable_data);

                    }
                    

                    $obtain_data = array(
                        'obtain_date' => $data['sale_date'],
                        'customer' => $data['customer'],
                        'money' => $data['revenue']+$data['revenue_vat'],
                        'week' => (int)date('W',$data['sale_date']),
                        'year' => (int)date('Y',$data['sale_date']),
                        'trading' => $sale->getLastSale()->sale_report_id,
                    );
                    if($obtain_data['week'] == 53){
                        $obtain_data['week'] = 1;
                        $obtain_data['year'] = $obtain_data['year']+1;
                    }
                    if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                        $obtain_data['year'] = (int)date('Y',$data['sale_date'])+1;
                    }
                    $obtain->createObtain($obtain_data);


                    /*if($data['ops_name'] > 0){
                        $payable_data = array(
                            'vendor' => $data['ops_name'],
                            'money' => $data['ops_cost'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['ops_expect_date'],
                            'week' => (int)date('W',$data['ops_expect_date']),
                            'year' => (int)date('Y',$data['ops_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['ops_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['ops_expect_date']) == 1) && ((int)date('m',$data['ops_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['ops_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['ops_name'],
                            'money' => $data['ops_cost'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);

                    }
                    if($data['trucking_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['trucking_vendor'],
                            'money' => $data['trucking_cost']+$data['trucking_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['trucking_expect_date'],
                            'week' => (int)date('W',$data['trucking_expect_date']),
                            'year' => (int)date('Y',$data['trucking_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['trucking_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        // $name = $vendor->getVendor($data['trucking_vendor'])->shipment_vendor_name;
                        // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                        //     $payable_data['type'] = 3;
                        // }
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['trucking_expect_date']) == 1) && ((int)date('m',$data['trucking_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['trucking_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['trucking_vendor'],
                            'money' => $data['trucking_cost']+$data['trucking_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }
                    if($data['barging_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['barging_vendor'],
                            'money' => $data['barging_cost']+$data['barging_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['barging_expect_date'],
                            'week' => (int)date('W',$data['barging_expect_date']),
                            'year' => (int)date('Y',$data['barging_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['barging_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['barging_expect_date']) == 1) && ((int)date('m',$data['barging_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['barging_expect_date'])+1;
                        }

                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['barging_vendor'],
                            'money' => $data['barging_cost']+$data['barging_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }
                    if($data['feeder_vendor'] > 0){
                        $payable_data = array(
                            'vendor' => $data['feeder_vendor'],
                            'money' => $data['feeder_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['feeder_expect_date'],
                            'week' => (int)date('W',$data['feeder_expect_date']),
                            'year' => (int)date('Y',$data['feeder_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['feeder_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['feeder_expect_date']) == 1) && ((int)date('m',$data['feeder_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['feeder_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['feeder_vendor'],
                            'money' => $data['feeder_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }
                    if($data['commission'] > 0){
                        $payable_data = array(
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['commission_expect_date'],
                            'week' => (int)date('W',$data['commission_expect_date']),
                            'year' => (int)date('Y',$data['commission_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['commission_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['commission_expect_date']) == 1) && ((int)date('m',$data['commission_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['commission_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }
                    if($data['handling_port'] > 0){
                        $payable_data = array(
                            'vendor' => $data['handling_port'],
                            'money' => $data['handling_cost_vat'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['port1_expect_date'],
                            'week' => (int)date('W',$data['port1_expect_date']),
                            'year' => (int)date('Y',$data['port1_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['port1_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['port1_expect_date']) == 1) && ((int)date('m',$data['port1_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['port1_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['handling_port'],
                            'money' => $data['handling_cost_vat'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }
                    if($data['handling_port2'] > 0){
                        $payable_data = array(
                            'vendor' => $data['handling_port2'],
                            'money' => $data['handling_cost_vat2'],
                            'payable_date' => $data['sale_date'],
                            'expect_date' => $data['port2_expect_date'],
                            'week' => (int)date('W',$data['port2_expect_date']),
                            'year' => (int)date('Y',$data['port2_expect_date']),
                            'code' => $data['code'],
                            'source' => $data['port2_source'],
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['port2_expect_date']) == 1) && ((int)date('m',$data['port2_expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['port2_expect_date'])+1;
                        }
                        $payable->createCosts($payable_data);

                        $owe_data = array(
                            'owe_date' => $data['sale_date'],
                            'vendor' => $data['handling_port2'],
                            'money' => $data['handling_cost_vat2'],
                            'week' => (int)date('W',$data['sale_date']),
                            'year' => (int)date('Y',$data['sale_date']),
                            'trading' => $sale->getLastSale()->sale_report_id,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['sale_date']) == 1) && ((int)date('m',$data['sale_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['sale_date'])+1;
                        }
                        $owe->createOwe($owe_data);
                    }

                    if ($_POST['other_cost'] != "") {
                
                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost2'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost2'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost2']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost3'] != "") {

                        $other_cost_data = array(
                            'cost' => trim(str_replace(',','',$_POST['other_cost3'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost3']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat'] != "") {

                        $other_cost_data = array(
                            'cost_vat' => trim(str_replace(',','',$_POST['other_cost_vat'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost_vat']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat2'] != "") {

                        $other_cost_data = array(
                            'cost_vat' => trim(str_replace(',','',$_POST['other_cost_vat2'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost_vat2']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }
                    if ($_POST['other_cost_vat3'] != "") {

                        $other_cost_data = array(
                            'cost_vat' => trim(str_replace(',','',$_POST['other_cost_vat3'])),
                            'trading' => $sale->getLastSale()->sale_report_id,
                            'comment' => trim($_POST['comment_cost_vat3']),
                        );
                        $other_cost_model->createOthercost($other_cost_data);
                    }*/


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sale->getLastSale()->sale_report_id."|trading|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function complete(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $sale_vendor = $this->model->get('salevendorModel');

            $sale = $this->model->get('salereportModel');
            $sale_data = $sale->getSale($_POST['data']);

            $data = array(
                        'complete_date' => strtotime($_POST['day']),
                        'status' => 1,
                        );
          
            $sale->updateSale($data,array('sale_report_id' => $_POST['data']));

            $customer = $this->model->get('customerModel');
            $vendor = $this->model->get('shipmentvendorModel');
            
            $payable = $this->model->get('payableModel');
            $receivable = $this->model->get('receivableModel');

            $customer_data = $customer->getCustomer($sale_data->customer);

            $vendor_data = $sale_vendor->getAllvendor(array('where'=>'trading = '.$_POST['data']));


            $customer_expect_date = $sale_data->expect_date;
            if ($customer->customer_expect_date != null) {
                $customer_expect_date = strtotime($customer->customer_expect_date.'-'.date('m-Y',$data['complete_date']));
            }
            else if ($customer->customer_after_date != null) {
                $customer_expect_date = strtotime('+'.($customer->customer_after_date+3).' day', $data['complete_date']);
            }
            $receivable_data = array(
                'receivable_date' => $data['complete_date'],
                'expect_date' => $customer_expect_date,
                'week' => (int)date('W',$customer_expect_date),
                'year' => (int)date('Y',$customer_expect_date),
                
            );
            if($receivable_data['week'] == 53){
                    $receivable_data['week'] = 1;
                    $receivable_data['year'] = $receivable_data['year']+1;
                }
                if (((int)date('W',$customer_expect_date) == 1) && ((int)date('m',$customer_expect_date) == 12) ) {
                    $receivable_data['year'] = (int)date('Y',$customer_expect_date)+1;
                }
            $receivable->updateCosts($receivable_data,array('trading'=>trim($_POST['data'])));

            foreach ($vendor_data as $v) {
                $v_data = $vendor->getVendor($v->vendor);

                $vendor_expect_date = $v->expect_date;
                if ($v_data->vendor_expect_date != null) {
                    $vendor_expect_date = strtotime($v_data->vendor_expect_date.'-'.date('m-Y',$data['complete_date']));
                }
                else if ($v_data->vendor_after_date != null) {
                    $vendor_expect_date = strtotime('+'.$v_data->vendor_after_date.' day', $customer_expect_date);
                }

                if ($v_data->shipment_vendor_name == 'Lộc Thạnh Phú') {
                    if (date('d',$customer_expect_date) < 8 ) {
                        $vendor_expect_date = strtotime('15-'.date('m-Y',strtotime('+1 month',$customer_expect_date)));
                    }
                    else{
                        $vendor_expect_date = strtotime('25-'.date('m-Y',strtotime('+1 month',$customer_expect_date)));
                    }
                    
                }

                $payable_data = array(
                            'payable_date' => $data['complete_date'],
                            'expect_date' => $vendor_expect_date,
                            'week' => (int)date('W',$vendor_expect_date),
                            'year' => (int)date('Y',$ops_expect_date),
                            
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$vendor_expect_date) == 1) && ((int)date('m',$vendor_expect_date) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$vendor_expect_date)+1;
                        }
                        $payable->updateCosts($payable_data,array('vendor'=>$v->vendor,'trading'=>trim($_POST['data'])));

            }

            


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."complete"."|".$_POST['data']."|trading|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
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
                        
                        'sale_lock' => trim($_POST['value']),
                        );
          
            $sale->updateSale($data,array('sale_report_id' => $_POST['data']));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."lock"."|".$_POST['data']."|trading|".$_POST['value']."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function invoice(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $receivable = $this->model->get('receivableModel');

            $sale = $this->model->get('salereportModel');
            $sale_data = $sale->getSale($_POST['data']);

            $data = array(
                        'invoice_date' => strtotime($_POST['day']),
                        );
          
            $sale->updateSale($data,array('sale_report_id' => $_POST['data']));

            
            $receivable_data = array(
                'invoice_date' => $data['invoice_date'],
                
            );
            
            $receivable->updateCosts($receivable_data,array('trading'=>trim($_POST['data'])));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."invoice"."|".$_POST['data']."|trading|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }


    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale = $this->model->get('salereportModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $vendor = $this->model->get('salevendorModel');
            $trading_vendor = $this->model->get('tradingvendorModel');
            $advance = $this->model->get('advanceModel');
            $staff = $this->model->get('staffdebtModel');
            $staffs = $this->model->get('staffModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $sale_data = $sale->getSale($data);
                        $staff_data = $staffs->getStaffByWhere(array('staff_id'=>$sale_data->staff));

                        $re = $receivable->getAllCosts(array('where'=>'trading='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'trading='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE trading = '.$data);
                        $payable->queryCosts('DELETE FROM payable WHERE trading = '.$data);
                        $obtain->queryObtain('DELETE FROM obtain WHERE trading = '.$data);
                        $owe->queryOwe('DELETE FROM owe WHERE trading = '.$data);
                        $vendor->queryVendor('DELETE FROM sale_vendor WHERE trading = '.$data);
                        $trading_vendor->queryVendor('DELETE FROM trading_vendor WHERE trading = '.$data);
                        $advance->queryCosts('DELETE FROM advance WHERE staff = '.$staff_data->account.' AND money = '.$sale_data->staff_cost);
                        $staff->queryCost('DELETE FROM staff_debt WHERE staff = '.$sale_data->staff.' AND money = '.$sale_data->staff_cost);
                        $sales_model->querySales('DELETE FROM sales WHERE trading = '.$data);
                        $sale->deleteSale($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|trading|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $sale_data = $sale->getSale($_POST['data']);
                        $staff_data = $staffs->getStaffByWhere(array('staff_id'=>$sale_data->staff));

                        $re = $receivable->getAllCosts(array('where'=>'trading='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'trading='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                         $receivable->queryCosts('DELETE FROM receivable WHERE trading = '.$_POST['data']);
                         $payable->queryCosts('DELETE FROM payable WHERE trading = '.$_POST['data']);
                        $obtain->queryObtain('DELETE FROM obtain WHERE trading = '.$_POST['data']);
                        $owe->queryOwe('DELETE FROM owe WHERE trading = '.$_POST['data']);
                        $vendor->queryVendor('DELETE FROM sale_vendor WHERE trading = '.$_POST['data']);
                        $trading_vendor->queryVendor('DELETE FROM trading_vendor WHERE trading = '.$data);
                        $advance->queryCosts('DELETE FROM advance WHERE staff = '.$staff_data->account.' AND money = '.$sale_data->staff_cost);
                        $staff->queryCost('DELETE FROM staff_debt WHERE staff = '.$sale_data->staff.' AND money = '.$sale_data->staff_cost);
                        $sales_model->querySales('DELETE FROM sales WHERE trading = '.$_POST['data']);
                        $sale->deleteSale($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|trading|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

public function getvendortrading(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('tradingvendorModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'trading='.$_POST['sale_report']));
            
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
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name.' ('.$type.')' .'</option>';
                           }

                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk2"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type2" name="cost_type2[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor2" name="vendor2[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date2" type="date"   name="vendor_expect_date2[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source2"  name="vendor_source2[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat2"  name="cost_vat2[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost2"  name="invoice_cost2[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment2" name="cost_comment2[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost2"  name="cost2[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost2"  name="pay_cost2[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td></td><td></td><td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost2"  name="document_cost2[]" value="0"  ></td></tr></table></td></tr>';
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

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name.' ('.$type.')' .'</option>';
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

                    $str .= '<tr class="'.$v->sale_report.$v->trading.'">';
                    $str .= '<td><input type="checkbox" name="chk2" tabindex="'.$v->type.'" data="'.$v->sale_report.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type2" name="cost_type2[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor2" name="vendor2[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date2" type="date"   name="vendor_expect_date2[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source2"  name="vendor_source2[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat2"  name="cost_vat2[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost2"  name="invoice_cost2[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment2" name="cost_comment2[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost2"  name="cost2[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost2"  name="pay_cost2[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td><td></td><td></td><td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost2"  name="document_cost2[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function getcustomertrading(){
        if(isset($_POST['sale_report'])){
            $sale_customer = $this->model->get('customersaleModel');
            $customers = $sale_customer->getAllCustomer(array('where'=>'sale_report='.$_POST['sale_report']));
            
            $customer_model = $this->model->get('customerModel');

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();

            $str = "";

            if(!$customers){


                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk3"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td>Khách hàng</td>';
                    $str .= '<td><input type="text" class="other_customer" name="other_customer[]" autocomplete="false" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('customer') .'"> + </a>';
                    $str .= '<ul class="customer_list_id"></ul></td>';
                    $str .= '<td>Số tiền (VAT)</td>';
                    $str .= '<td><input  type="text" style="width:120px" class="numbers other_revenue_vat"  name="other_revenue_vat[]"  ></td>';
                    $str .= '<td>Dự thu</td>';
                    $str .= '<td><input  class="other_expect_date" type="date"   name="other_expect_date[]" required="required" value="'.date('Y-m-d').'" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td>Diễn giải</td>';
                    $str .= '<td><textarea class="other_comment" name="other_comment"></textarea></td>';
                    $str .= '<td>Số tiền (0 VAT)</td>';
                    $str .= '<td><input  type="text" style="width:120px" class="numbers other_revenue"  name="other_revenue[]"  ></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select style="width:120px" class="other_source"  name="other_source[]"  required="required">'.$ba.'</select></td></tr>';
                    
                    $str .= '</table></td></tr>';
            }
            else{

                foreach ($customers as $v) {
                    if($v->customer > 0){

                        $ba = "";

                        

                        foreach($banks as $bank){ 
                            $slnh = ($bank->bank_id == $v->bank)?'selected="selected"':null;
                            $ba .= '<option '.$slnh .' value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                         }


                        $str .= '<tr class="'.$v->sale_report.'">';
                        $str .= '<td><input type="checkbox" name="chk3"  data="'.$v->sale_report.'" tabindex="'.$v->customer.'" ></td>';
                        $str .= '<td><table style="width: 100%">';
                        $str .= '<tr class="'.$v->sale_report.'">';
                        $str .= '<td>Khách hàng</td>';
                        $str .= '<td><input type="text" disabled value="'.$customer_model->getCustomer($v->customer)->customer_name.'" data="'.$v->customer.'" class="other_customer" name="other_customer[]" required="required" autocomplete="false" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('customer') .'"> + </a>';
                        $str .= '<ul class="customer_list_id"></ul></td>';
                        $str .= '<td>Số tiền (VAT)</td>';
                        $str .= '<td><input value="'.$this->lib->formatMoney($v->revenue_vat).'" type="text" style="width:120px" class="numbers other_revenue_vat"  name="other_revenue_vat[]"  ></td>';
                        $str .= '<td>Dự thu</td>';
                        $str .= '<td><input  class="other_expect_date" type="date"   name="other_expect_date[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td></tr>';
                        $str .= '<tr class="'.$v->sale_report.'">';
                        $str .= '<td>Diễn giải</td>';
                        $str .= '<td><textarea class="other_comment" name="other_comment">'.$v->comment.'</textarea></td>';
                        $str .= '<td>Số tiền (0 VAT)</td>';
                        $str .= '<td><input value="'.$this->lib->formatMoney($v->revenue).'" type="text" style="width:120px" class="numbers other_revenue"  name="other_revenue[]"  ></td>';
                        $str .= '<td>Tài khoản</td>';
                        $str .= '<td><select  style="width:120px" class="other_source"  name="other_source[]"  required="required">'.$ba.'</select></td></tr>';
                        $str .= '</table></td></tr>';
                    }
                    else{
                        $ba = "";

                            foreach($banks as $bank){ 
                                $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                             }


                        $str .= '<tr class="'.$_POST['sale_report'].'">';
                            $str .= '<td><input type="checkbox"  name="chk3"></td>';
                            $str .= '<td><table style="width: 100%">';
                            $str .= '<tr class="'.$_POST['sale_report'] .'">';
                            $str .= '<td>Khách hàng</td>';
                            $str .= '<td><input type="text" class="other_customer" name="other_customer[]" autocomplete="false" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('customer') .'"> + </a>';
                            $str .= '<ul class="customer_list_id"></ul></td>';
                            $str .= '<td>Số tiền (VAT)</td>';
                            $str .= '<td><input  type="text" style="width:120px" class="numbers other_revenue_vat"  name="other_revenue_vat[]"  ></td>';
                            $str .= '<td>Dự thu</td>';
                            $str .= '<td><input  class="other_expect_date" type="date"   name="other_expect_date[]" required="required" value="'.date('Y-m-d').'" ></td></tr>';
                            $str .= '<tr class="'.$_POST['sale_report'] .'">';
                            $str .= '<td>Diễn giải</td>';
                            $str .= '<td><textarea class="other_comment" name="other_comment"></textarea></td>';
                            $str .= '<td>Số tiền (0 VAT)</td>';
                            $str .= '<td><input  type="text" style="width:120px" class="numbers other_revenue"  name="other_revenue[]"  ></td>';
                            $str .= '<td> Tài khoản </td>';
                            $str .= '<td><select style="width:120px" class="other_source"  name="other_source[]"  required="required">'.$ba.'</select></td></tr>';
                            
                            $str .= '</table></td></tr>';
                    }
                }
            }

            echo $str;
        }
    }

    public function deletevendor(){
        if(isset($_POST['data'])){
            $sale_vendor = $this->model->get('tradingvendorModel');
            $sale = $this->model->get('salereportModel');
            $staff = $this->model->get('staffdebtModel');
            

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => '(trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM trading_vendor WHERE (trading='.$_POST['data'].') AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                

            
            
            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                $kvat += $vendor_data->cost;
                $vat += $vendor_data->cost_vat;
                $estimate += $vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->document_cost;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

                $staff->queryCost('DELETE FROM staff_debt WHERE staff='.$sale_data->staff.' AND money='.(0-($vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->cost_vat+$vendor_data->document_cost)).' AND staff_debt_date='.$vendor_data->expect_date);
            }

            


            $data = array(
                'cost' => $sale_data->cost-$kvat,
                'cost_vat' => $sale_data->cost_vat-$vat,
                'profit' => $sale_data->profit+$kvat,
                'profit_vat' => $sale_data->profit_vat+$vat,
                'estimate_cost_2' => $sale_data->estimate_cost_2-$estimate,

            );
            
            $sale->updateSale($data,array('sale_report_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

        }
    }

    public function deleteothercustomer(){
        if(isset($_POST['data'])){
            $customer_sale = $this->model->get('customersaleModel');
            $sale = $this->model->get('salereportModel');
            $obtain = $this->model->get('obtainModel');
            $receivable = $this->model->get('receivableModel');
            $costs = $this->model->get('costsModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => 'sale_report='.$_POST['data'].' AND customer='.$_POST['customer'],
                );

                $customer_datas = $customer_sale->getAllCustomer($data);

                $customer_sale->queryCustomer('DELETE FROM customer_sale WHERE sale_report='.$_POST['data'].' AND customer='.$_POST['customer']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'trading='.$_POST['data'].' AND customer='.$_POST['customer']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $receivable->queryCosts('DELETE FROM receivable WHERE trading='.$_POST['data'].' AND customer='.$_POST['customer']);



            
            
            $kvat = 0;
            $vat = 0;

            $old_cost = 0;

            foreach ($customer_datas as $customer_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;

                $kvat += $customer_data->revenue;
                $vat += $customer_data->revenue_vat;
                

                $old_cost += $customer_data->revenue+$customer_data->revenue_vat;

                $obtain->queryObtain('DELETE FROM obtain WHERE customer='.$customer_data->customer.' AND money='.$old_cost.' AND trading='.$customer_data->sale_report);
            }


            $data = array(
                'other_revenue' => $sale_data->other_revenue-$kvat,
                'other_revenue_vat' => $sale_data->other_revenue_vat-$vat,
                'profit' => $sale_data->profit-$kvat,
                'profit_vat' => $sale_data->profit_vat-$vat,

            );
            
            $sale->updateSale($data,array('sale_report_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

        }
    }


    public function getvendor4(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('salevendorModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'check_cost = 1 AND trading='.$_POST['sale_report']));
            
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
                    $str .= '<td><input type="checkbox"  name="chk4"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type4" name="cost_type4[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor4" name="vendor4[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date4" type="date"   name="vendor_expect_date4[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source4"  name="vendor_source4[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat4"  name="cost_vat4[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost4"  name="invoice_cost4[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment4" name="cost_comment4[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost4"  name="cost4[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost4"  name="pay_cost4[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td>';
                    $str .= '<td></td><td><input type="checkbox" value="1" name="check_deposit4[]" class="check_deposit4"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost4"  name="document_cost4[]" value="0"  ></td></tr></table></td></tr>';
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
                    $str .= '<td><input type="checkbox" name="chk4" tabindex="'.$v->type.'" data="'.$v->sale_report.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type4" name="cost_type4[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor4" name="vendor4[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date4" type="date"   name="vendor_expect_date4[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source4"  name="vendor_source4[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat4"  name="cost_vat4[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost4"  name="invoice_cost4[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment4" name="cost_comment4[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost4"  name="cost4[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost4"  name="pay_cost4[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->sale_report.$v->trading .'"><td></td>';
                    $str .= '<td></td><td><input disabled type="checkbox" '.$checked.' value="1" name="check_deposit4[]" class="check_deposit4"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost4"  name="document_cost4[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletevendor4(){
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
                    'where' => 'check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM sale_vendor WHERE check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor']);



            
            
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

            $owe->queryOwe('DELETE FROM owe WHERE trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND money='.$old_cost.' LIMIT 1');

            
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
            


            $data = array(
                'net_cost' => $sale_data->net_cost-$kvat,
                'net_cost_vat' => $sale_data->net_cost_vat-$vat,
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

    public function getvendor5(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('salevendorModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'check_cost = 2 AND trading='.$_POST['sale_report']));
            
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

    public function deletevendor5(){
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
                    'where' => 'check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM sale_vendor WHERE check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor']);



            
            
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

            $owe->queryOwe('DELETE FROM owe WHERE trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND money='.$old_cost.' LIMIT 1');

            
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
            


            $data = array(
                'net_cost' => $sale_data->net_cost-$kvat,
                'net_cost_vat' => $sale_data->net_cost_vat-$vat,
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

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $salereport = $this->model->get('salereportModel');
            $staff = $this->model->get('staffModel');
            $location = $this->model->get('locationModel');
            $district = $this->model->get('districtModel');
            $customer = $this->model->get('customerModel');
            $vendor = $this->model->get('shipmentvendorModel');
            $othercost = $this->model->get('othercostModel');
            $obtain_model = $this->model->get('obtainModel');
            $owe_model = $this->model->get('oweModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();
            
             

                for ($row = 2; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[3] != null && $val[5] != null && $val[20] != null) {
                        $unit = "";
                        $unit2 = "";
                        $transport = "";
                        
                        if (trim($val[11]) == "20'") {
                            $unit = 1;
                        }
                        else if (trim($val[11]) == "40'") {
                            $unit = 2;
                        }
                        if (trim($val[11]) == "45'") {
                            $unit = 3;
                        }
                        else if (trim($val[11]) == "Mooc sàn") {
                            $unit = 4;
                        }
                        else if (trim($val[11]) == "Tấn") {
                            $unit = 5;
                        }
                        else if (trim($val[11]) == "Kiện") {
                            $unit = 6;
                        }
                        else if (trim($val[11]) == "Chứng từ") {
                            $unit = 7;
                        }

                        if (trim($val[13]) == "20'") {
                            $unit2 = 1;
                        }
                        else if (trim($val[13]) == "40'") {
                            $unit2 = 2;
                        }
                        if (trim($val[13]) == "45'") {
                            $unit2 = 3;
                        }
                        else if (trim($val[13]) == "Mooc sàn") {
                            $unit2 = 4;
                        }
                        else if (trim($val[13]) == "Tấn") {
                            $unit2 = 5;
                        }
                        else if (trim($val[13]) == "Kiện") {
                            $unit2 = 6;
                        }
                        else if (trim($val[13]) == "Chứng từ") {
                            $unit2 = 7;
                        }

                        if (strtoupper(trim($val[14])) == "TRUCKING") {
                            $transport = 1;
                        }
                        else if (strtoupper(trim($val[14])) == "BARGING") {
                            $transport = 2;
                        }
                        if (strtoupper(trim($val[14])) == "SHIP") {
                            $transport = 3;
                        }
                        else if (strtoupper(trim($val[14])) == "CUSTOM") {
                            $transport = 4;
                        }
                        else if (strtoupper(trim($val[14])) == "AGENT") {
                            $transport = 5;
                        }
                        else if (strtoupper(trim($val[14])) == "MULTI") {
                            $transport = 6;
                        }

                        /*$ngaythang = PHPExcel_Shared_Date::ExcelToPHP(trim($val[6]));                                      
                            $ngaythang = $ngaythang-3600;*/

                            $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[3])))->account;

                            if(trim($val[15]) != null){
                                $name_district_from = $this->getNameDistrict($this->lib->stripUnicode(trim($val[16])));
                                $id_district_from = $district->getDistrictByWhere(array('district_name'=>$name_district_from))->district_id;
                                
                                if(!$location->getLocationByWhere(array('location_name'=>trim($val[15]),'district'=>$id_district_from))){
                                    $location_data = array(
                                        'location_name' => trim($val[15]),
                                        'district' => $id_district_from,
                                        );
                                    $location->createLocation($location_data);

                                    $id_location_from = $location->getLastLocation()->location_id;

                                }
                                else{
                                    $id_location_from = $location->getLocationByWhere(array('location_name'=>trim($val[15])))->location_id;

                                    
                                }
                            }
                            if(trim($val[17]) != null){
                                $name_district_to = $this->getNameDistrict($this->lib->stripUnicode(trim($val[18])));
                                $id_district_to = $district->getDistrictByWhere(array('district_name'=>$name_district_to))->district_id;
                                
                                if(!$location->getLocationByWhere(array('location_name'=>trim($val[17]),'district'=>$id_district_to))){
                                    $location_data = array(
                                        'location_name' => trim($val[17]),
                                        'district' => $id_district_to,
                                        );
                                    $location->createLocation($location_data);

                                    $id_location_to = $location->getLastLocation()->location_id;

                                }
                                else{
                                    $id_location_to = $location->getLocationByWhere(array('location_name'=>trim($val[17])))->location_id;

                                    
                                }
                            }

                            if(trim($val[20]) != null){
                                if(!$customer->getCustomerByWhere(array('customer_name'=>trim($val[20])))){
                                    $customer_data = array(
                                        'customer_name' => trim($val[20]),
                                        'customer_phone' => trim($val[22]),
                                        'customer_email' => trim($val[23]),
                                        );
                                    $customer->createCustomer($customer_data);

                                    $id_customer = $customer->getLastCustomer()->customer_id;

                                }
                                else{
                                    $id_customer = $customer->getCustomerByWhere(array('customer_name'=>trim($val[20])))->customer_id;

                                    
                                }
                            }
                            if(trim($val[33]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[33]),'vendor_type'=>1))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[33]),
                                        'shipment_vendor_contact' => trim($val[33]),
                                        'shipment_vendor_phone' => trim($val[34]),
                                        'vendor_type' => 1,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_vendor_ops = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_vendor_ops = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[33]),'vendor_type'=>1))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_vendor_ops = "";
                            }
                            if(trim($val[38]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[38]),'vendor_type'=>2))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[38]),
                                        'shipment_vendor_contact' => trim($val[39]),
                                        'shipment_vendor_phone' => trim($val[40]),
                                        'vendor_type' => 2,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_vendor_trucking = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_vendor_trucking = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[38]),'vendor_type'=>2))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_vendor_trucking = "";
                            }
                            if(trim($val[44]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[44]),'vendor_type'=>3))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[44]),
                                        'shipment_vendor_contact' => trim($val[45]),
                                        'shipment_vendor_phone' => trim($val[46]),
                                        'vendor_type' => 3,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_vendor_barging = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_vendor_barging = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[44]),'vendor_type'=>3))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_vendor_barging = "";
                            }
                            if(trim($val[49]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[49]),'vendor_type'=>4))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[49]),
                                        'shipment_vendor_contact' => trim($val[50]),
                                        'shipment_vendor_phone' => trim($val[51]),
                                        'vendor_type' => 4,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_vendor_feeder = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_vendor_feeder = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[49]),'vendor_type'=>4))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_vendor_feeder = "";
                            }
                            if(trim($val[58]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[58]),'vendor_type'=>5))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[58]),
                                        'shipment_vendor_contact' => trim($val[58]),
                                        'shipment_vendor_phone' => trim($val[59]),
                                        'vendor_type' => 5,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_commission = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_commission = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[58]),'vendor_type'=>5))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_commission = "";
                            }

                            if(trim($val[54]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[54]),'vendor_type'=>6))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[54]),
                                        'shipment_vendor_contact' => trim($val[54]),
                                        'vendor_type' => 6,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_port1 = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_port1 = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[54]),'vendor_type'=>6))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_port1 = "";
                            }
                            if(trim($val[56]) != null){
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[56]),'vendor_type'=>6))){
                                    $vendor_data = array(
                                        'shipment_vendor_name' => trim($val[56]),
                                        'shipment_vendor_contact' => trim($val[56]),
                                        'vendor_type' => 6,
                                        );
                                    $vendor->createVendor($vendor_data);

                                    $id_port2 = $vendor->getLastVendor()->shipment_vendor_id;

                                }
                                else{
                                    $id_port2 = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[56]),'vendor_type'=>6))->shipment_vendor_id;

                                    
                                }
                            }
                            else{
                                $id_port2 = "";
                            }
                            
                            if(trim($val[6]) != null){
                                $sale_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[6]));                                      
                                $sale_date = $sale_date-3600;
                            }
                            else{
                                $sale_date = null;
                            }


                            if(trim($val[24]) != null){
                            $expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[24]));                                      
                            $expect_date = $expect_date-3600;
                            }
                            else{
                                $expect_date = null;
                            }

                            if(trim($val[35]) != null){
                            $ops_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[35]));                                      
                            $ops_expect_date = $ops_expect_date-3600;
                            }
                            else{
                                $ops_expect_date = null;
                            }
                            if(trim($val[41]) != null){
                            $trucking_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[41]));                                      
                            $trucking_expect_date = $trucking_expect_date-3600;
                            }
                            else{
                                $trucking_expect_date = null;
                            }
                            if(trim($val[47]) != null){
                            $barging_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[47]));                                      
                            $barging_expect_date = $barging_expect_date-3600;
                            }
                            else{
                                $barging_expect_date = null;
                            }
                            if(trim($val[52]) != null){
                            $feeder_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[52]));                                      
                            $feeder_expect_date = $feeder_expect_date-3600;
                            }
                            else{
                                $feeder_expect_date = null;
                            }
                            if(trim($val[77]) != null){
                            $port1_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[77]));                                      
                            $port1_expect_date = $port1_expect_date-3600;
                            }
                            else{
                                $port1_expect_date = null;
                            }
                            if(trim($val[78]) != null){
                            $port2_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[78]));                                      
                            $port2_expect_date = $port2_expect_date-3600;
                            }
                            else{
                                $port2_expect_date = null;
                            }
                            if(trim($val[79]) != null){
                            $commission_expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[79]));                                      
                            $commission_expect_date = $commission_expect_date-3600;
                            }
                            else{
                                $commission_expect_date = null;
                            }
                            if(trim($val[81]) != null){
                            $complete_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[81]));                                      
                            $complete_date = $complete_date-3600;
                            }
                            else{
                                $complete_date = null;
                            }
                            $bank = 4;
                            $bank_commission = 5;


                            if(!$salereport->getSaleByWhere(array('code'=>trim($val[5])))) {
                                $salereport_data = array(
                                    'sale_date' => $sale_date,
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'sale' => $id_staff,
                                    'code' => trim($val[5]),
                                    'number' => trim($val[10]),
                                    'unit' => $unit,
                                    'number2' => trim($val[12]),
                                    'unit2' => $unit2,
                                    'transport' => $transport,
                                    'comment' => trim($val[19]),
                                    'customer' => $id_customer,
                                    'expect_date' => $expect_date,
                                    'revenue' => trim($val[26]),
                                    'revenue_vat' => trim($val[25]),
                                    'cost_vat' => trim($val[27]),
                                    'cost' => trim($val[28]),
                                    'profit_vat' => trim($val[29]),
                                    'profit' => trim($val[30]),
                                    'ops_cost' => trim($val[32]),
                                    'ops_name' => $id_vendor_ops,
                                    'ops_expect_date' => $ops_expect_date,
                                    'trucking_cost' => trim($val[37]),
                                    'trucking_cost_vat' => trim($val[36]),
                                    'trucking_vendor' => $id_vendor_trucking,
                                    'trucking_expect_date' => $trucking_expect_date,
                                    'barging_cost' => trim($val[43]),
                                    'barging_cost_vat' => trim($val[42]),
                                    'barging_vendor' => $id_vendor_barging,
                                    'barging_expect_date' => $barging_expect_date,
                                    'feeder_cost_vat' => trim($val[48]),
                                    'feeder_vendor' => $id_vendor_feeder,
                                    'feeder_expect_date' => $feeder_expect_date,
                                    'handling_cost_vat' => trim($val[53]),
                                    'handling_port' => $id_port1,
                                    'handling_cost_vat2' => trim($val[55]),
                                    'handling_port2' => $id_port2,
                                    'commission' => $id_commission,
                                    'commission_cost' => trim($val[57]),
                                    'commission_name' => trim($val[58]),
                                    'commission_phone' => trim($val[59]),
                                    'pay_cost_vat' => trim($val[60]),
                                    'bill_cost' => trim($val[61]),
                                    'document_cost' => trim($val[62]),
                                    'status' => trim($val[80]),
                                    'complete_date' => $complete_date,
                                    'customer_source' => $bank,
                                    'ops_source' => $bank,
                                    'trucking_source' => $bank,
                                    'barging_source' => $bank,
                                    'feeder_source' => $bank,
                                    'commission_source' => $bank_commission,
                                    'port1_source' => $bank,
                                    'port2_source' => $bank,
                                    );

                                $salereport->createSale($salereport_data);

                                $receivable_data = array(
                                    'customer' => $salereport_data['customer'],
                                    'money' => $salereport_data['revenue_vat']+$salereport_data['revenue'],
                                    'receivable_date' => $sale_date,
                                    'expect_date' => $expect_date,
                                    'week' => (int)date('W',$expect_date),
                                    'year' => (int)date('Y',$expect_date),
                                    'code' => $salereport_data['code'],
                                    'source' => $salereport_data['customer_source'],
                                    'comment' => $salereport_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                    'trading' => $salereport->getLastSale()->sale_report_id,
                                );

                                
                                // $name = $customer->getCustomer($salereport_data['customer'])->customer_name;
                                // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                                //     $receivable_data['type'] = 3;
                                // }
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$expect_date) == 1) && ((int)date('m',$expect_date) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$expect_date)+1;
                                }

                                $receivable->createCosts($receivable_data);

                                $obtain_data = array(
                                    'obtain_date' => $salereport_data['sale_date'],
                                    'customer' => $salereport_data['customer'],
                                    'money' => $salereport_data['revenue_vat']+$salereport_data['revenue'],
                                    'week' => (int)date('W',$salereport_data['sale_date']),
                                    'year' => (int)date('Y',$salereport_data['sale_date']),
                                    'trading' => $salereport->getLastSale()->sale_report_id,
                                );
                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                }

                                $obtain_model->createObtain($obtain_data);

                                if($salereport_data['ops_name']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['ops_name'],
                                        'money' => $salereport_data['ops_cost'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $salereport_data['ops_name'],
                                        'money' => $salereport_data['ops_cost'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $ops_expect_date,
                                        'week' => (int)date('W',$ops_expect_date),
                                        'year' => (int)date('Y',$ops_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['ops_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);

                                }
                                if($salereport_data['trucking_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['trucking_vendor'],
                                        'money' => $salereport_data['trucking_cost']+$salereport_data['trucking_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $salereport_data['trucking_vendor'],
                                        'money' => $salereport_data['trucking_cost']+$salereport_data['trucking_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $trucking_expect_date,
                                        'week' => (int)date('W',$trucking_expect_date),
                                        'year' => (int)date('Y',$trucking_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['trucking_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    // $name = $vendor->getVendor($salereport_data['trucking_vendor'])->shipment_vendor_name;
                                    // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                                    //     $payable_data['type'] = 3;
                                    // }
                                    $payable->createCosts($payable_data);

                                }
                                if($salereport_data['barging_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['barging_vendor'],
                                        'money' => $salereport_data['barging_cost']+$salereport_data['barging_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);
                                    $payable_data = array(
                                        'vendor' => $salereport_data['barging_vendor'],
                                        'money' => $salereport_data['barging_cost']+$salereport_data['barging_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $barging_expect_date,
                                        'week' => (int)date('W',$barging_expect_date),
                                        'year' => (int)date('Y',$barging_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['barging_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);
                                }
                                if($salereport_data['feeder_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['feeder_vendor'],
                                        'money' => $salereport_data['feeder_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);
                                    $payable_data = array(
                                        'vendor' => $salereport_data['feeder_vendor'],
                                        'money' => $salereport_data['feeder_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $feeder_expect_date,
                                        'week' => (int)date('W',$feeder_expect_date),
                                        'year' => (int)date('Y',$feeder_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['feeder_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);
                                }
                                if($salereport_data['handling_port']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['handling_port'],
                                        'money' => $salereport_data['handling_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);
                                    $payable_data = array(
                                        'vendor' => $salereport_data['handling_port'],
                                        'money' => $salereport_data['handling_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $port1_expect_date,
                                        'week' => (int)date('W',$port1_expect_date),
                                        'year' => (int)date('Y',$port1_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['port1_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);
                                }
                                if($salereport_data['handling_port2']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['handling_port2'],
                                        'money' => $salereport_data['handling_cost_vat2'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);
                                    $payable_data = array(
                                        'vendor' => $salereport_data['handling_port2'],
                                        'money' => $salereport_data['handling_cost_vat2'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $port2_expect_date,
                                        'week' => (int)date('W',$port2_expect_date),
                                        'year' => (int)date('Y',$port2_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['port2_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);
                                }
                                if($salereport_data['commission']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['commission'],
                                        'money' => $salereport_data['commission_cost'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->createOwe($owe_data);
                                    $payable_data = array(
                                        'vendor' => $salereport_data['commission'],
                                        'money' => $salereport_data['commission_cost'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $commission_expect_date,
                                        'week' => (int)date('W',$commission_expect_date),
                                        'year' => (int)date('Y',$commission_expect_date),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['commission_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                    );
                                    $payable->createCosts($payable_data);
                                }


                                if (trim($val[66]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[66]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[65]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[68]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[68]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[67]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[70]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[70]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[69]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[72]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[72]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[71]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[74]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[74]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[73]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[76]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[76]),
                                        'trading' => $salereport->getLastSale()->sale_report_id,
                                        'comment' => trim($val[75]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }

                            }
                            else if($salereport->getSaleByWhere(array('code'=>trim($val[5])))){
                                $id_salereport = $salereport->getSaleByWhere(array('code'=>trim($val[5])))->sale_report_id;
                                $salereport_data = array(
                                    'sale_date' => $sale_date,
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'sale' => $id_staff,
                                    'code' => trim($val[5]),
                                    'number' => trim($val[10]),
                                    'unit' => $unit,
                                    'number2' => trim($val[12]),
                                    'unit2' => $unit2,
                                    'transport' => $transport,
                                    'comment' => trim($val[19]),
                                    'customer' => $id_customer,
                                    'expect_date' => $expect_date,
                                    'revenue' => trim($val[26]),
                                    'revenue_vat' => trim($val[25]),
                                    'cost_vat' => trim($val[27]),
                                    'cost' => trim($val[28]),
                                    'profit_vat' => trim($val[29]),
                                    'profit' => trim($val[30]),
                                    'ops_cost' => trim($val[32]),
                                    'ops_name' => $id_vendor_ops,
                                    'ops_expect_date' => $ops_expect_date,
                                    'trucking_cost' => trim($val[37]),
                                    'trucking_cost_vat' => trim($val[36]),
                                    'trucking_vendor' => $id_vendor_trucking,
                                    'trucking_expect_date' => $trucking_expect_date,
                                    'barging_cost' => trim($val[43]),
                                    'barging_cost_vat' => trim($val[42]),
                                    'barging_vendor' => $id_vendor_barging,
                                    'barging_expect_date' => $barging_expect_date,
                                    'feeder_cost_vat' => trim($val[48]),
                                    'feeder_vendor' => $id_vendor_feeder,
                                    'feeder_expect_date' => $feeder_expect_date,
                                    'handling_cost_vat' => trim($val[53]),
                                    'handling_port' => $id_port1,
                                    'handling_cost_vat2' => trim($val[55]),
                                    'handling_port2' => $id_port2,
                                    'commission' => $id_commission,
                                    'commission_cost' => trim($val[57]),
                                    'commission_name' => trim($val[58]),
                                    'commission_phone' => trim($val[59]),
                                    'pay_cost_vat' => trim($val[60]),
                                    'bill_cost' => trim($val[61]),
                                    'document_cost' => trim($val[62]),
                                    'status' => trim($val[80]),
                                    'complete_date' => $complete_date,
                                    'customer_source' => $bank,
                                    'ops_source' => $bank,
                                    'trucking_source' => $bank,
                                    'barging_source' => $bank,
                                    'feeder_source' => $bank,
                                    'commission_source' => $bank_commission,
                                    'port1_source' => $bank,
                                    'port2_source' => $bank,
                                    );
                                $salereport->updateSale($salereport_data,array('sale_report_id' => $id_salereport));


                                $obtain_data = array(
                                    'obtain_date' => $salereport_data['sale_date'],
                                    'customer' => $salereport_data['customer'],
                                    'money' => $salereport_data['revenue_vat']+$salereport_data['revenue'],
                                    'week' => (int)date('W',$salereport_data['sale_date']),
                                    'year' => (int)date('Y',$salereport_data['sale_date']),
                                );
                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                }
                                $obtain_model->updateObtain($obtain_data,array('trading' => $id_salereport));

                                $receivable_data = array(
                                    'customer' => $salereport_data['customer'],
                                    'money' => $salereport_data['revenue']+$salereport_data['revenue_vat'],
                                    'receivable_date' => $salereport_data['sale_date'],
                                    'expect_date' => $salereport_data['expect_date'],
                                    'week' => (int)date('W',$salereport_data['expect_date']),
                                    'year' => (int)date('Y',$salereport_data['expect_date']),
                                    'code' => $salereport_data['code'],
                                    'source' => $salereport_data['customer_source'],
                                    'comment' => $salereport_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 1,
                                );
                                // $name = $customer->getCustomer($salereport_data['customer'])->customer_name;
                                // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                                //     $receivable_data['type'] = 3;
                                // }
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$salereport_data['expect_date']) == 1) && ((int)date('m',$salereport_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$salereport_data['expect_date'])+1;
                                }
                                $receivable->updateCosts($receivable_data,array('trading'=>$id_salereport));


                                if($salereport_data['ops_name']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['ops_name'],
                                        'money' => $salereport_data['ops_cost'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['ops_name'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['ops_name'],
                                        'money' => $salereport_data['ops_cost'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['ops_expect_date'],
                                        'week' => (int)date('W',$salereport_data['ops_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['ops_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['ops_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['ops_expect_date']) == 1) && ((int)date('m',$salereport_data['ops_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['ops_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['ops_name'],'trading' => $id_salereport));

                                }
                                if($salereport_data['trucking_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['trucking_vendor'],
                                        'money' => $salereport_data['trucking_cost']+$salereport_data['trucking_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['trucking_vendor'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['trucking_vendor'],
                                        'money' => $salereport_data['trucking_cost']+$salereport_data['trucking_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['trucking_expect_date'],
                                        'week' => (int)date('W',$salereport_data['trucking_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['trucking_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['trucking_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    // $name = $vendor->getVendor($salereport_data['trucking_vendor'])->shipment_vendor_name;
                                    // if ($name == 'TCMT' || $name == 'TCMT (Tiếp Vận CM)' || $name == 'Mr Sơn (TCMT)') {
                                    //     $payable_data['type'] = 3;
                                    // }
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['trucking_expect_date']) == 1) && ((int)date('m',$salereport_data['trucking_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['trucking_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['trucking_vendor'],'trading' => $id_salereport));

                                }
                                if($salereport_data['barging_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['barging_vendor'],
                                        'money' => $salereport_data['barging_cost']+$salereport_data['barging_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['barging_vendor'],'trading' => $id_salereport));

                                     $payable_data = array(
                                        'vendor' => $salereport_data['barging_vendor'],
                                        'money' => $salereport_data['barging_cost']+$salereport_data['barging_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['barging_expect_date'],
                                        'week' => (int)date('W',$salereport_data['barging_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['barging_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['barging_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['barging_expect_date']) == 1) && ((int)date('m',$salereport_data['barging_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['barging_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['barging_vendor'],'trading' => $id_salereport));

                                }
                                if($salereport_data['feeder_vendor']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['feeder_vendor'],
                                        'money' => $salereport_data['feeder_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['feeder_vendor'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['feeder_vendor'],
                                        'money' => $salereport_data['feeder_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['feeder_expect_date'],
                                        'week' => (int)date('W',$salereport_data['feeder_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['feeder_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['feeder_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['feeder_expect_date']) == 1) && ((int)date('m',$salereport_data['feeder_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['feeder_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['feeder_vendor'],'trading' => $id_salereport));
                                }
                                if($salereport_data['commission']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['commission'],
                                        'money' => $salereport_data['commission_cost'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['commission'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['commission'],
                                        'money' => $salereport_data['commission_cost'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['commission_expect_date'],
                                        'week' => (int)date('W',$salereport_data['commission_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['commission_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['commission_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['commission_expect_date']) == 1) && ((int)date('m',$salereport_data['commission_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['commission_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['commission'],'trading' => $id_salereport));

                                }
                                if($salereport_data['handling_port']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['handling_port'],
                                        'money' => $salereport_data['handling_cost_vat'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['handling_port'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['handling_port'],
                                        'money' => $salereport_data['handling_cost_vat'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['port1_expect_date'],
                                        'week' => (int)date('W',$salereport_data['port1_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['port1_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['port1_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['port1_expect_date']) == 1) && ((int)date('m',$salereport_data['port1_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['port1_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['handling_port'],'trading' => $id_salereport));

                                }
                                if($salereport_data['handling_port2']!= ""){
                                    $owe_data = array(
                                        'owe_date' => $salereport_data['sale_date'],
                                        'vendor' => $salereport_data['handling_port2'],
                                        'money' => $salereport_data['handling_cost_vat2'],
                                        'week' => (int)date('W',$salereport_data['sale_date']),
                                        'year' => (int)date('Y',$salereport_data['sale_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['sale_date']) == 1) && ((int)date('m',$salereport_data['sale_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$salereport_data['sale_date'])+1;
                                    }
                                    $owe_model->updateOwe($owe_data,array('vendor' => $salereport_data['handling_port2'],'trading' => $id_salereport));

                                    $payable_data = array(
                                        'vendor' => $salereport_data['handling_port2'],
                                        'money' => $salereport_data['handling_cost_vat2'],
                                        'payable_date' => $salereport_data['sale_date'],
                                        'expect_date' => $salereport_data['port2_expect_date'],
                                        'week' => (int)date('W',$salereport_data['port2_expect_date']),
                                        'year' => (int)date('Y',$salereport_data['port2_expect_date']),
                                        'code' => $salereport_data['code'],
                                        'source' => $salereport_data['port2_source'],
                                        'comment' => $salereport_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$salereport_data['port2_expect_date']) == 1) && ((int)date('m',$salereport_data['port2_expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$salereport_data['port2_expect_date'])+1;
                                    }
                                    $payable->updateCosts($payable_data,array('vendor' => $salereport_data['handling_port2'],'trading' => $id_salereport));

                                }
                                


                                $othercost->deleteOthercost2($id_salereport);

                                if (trim($val[66]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[66]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[65]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[68]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[68]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[67]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[70]) != null) {
                
                                    $other_cost_data = array(
                                        'cost_vat' => trim($val[70]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[69]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[72]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[72]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[71]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[74]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[74]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[73]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                                if (trim($val[76]) != null) {
                
                                    $other_cost_data = array(
                                        'cost' => trim($val[76]),
                                        'trading' => $id_salereport,
                                        'comment' => trim($val[75]),
                                    );
                                    $othercost->createOthercost($other_cost_data);
                                }
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('salereport');
        }
        $this->view->show('salereport/import');

    }

    public function view() {
        
        $this->view->show('accounting/view');
    }

    public function getNameDistrict($str){
      if(!$str) return false;
       $unicode = array(
          'TP.HCM'=>'hcm|ho chi minh|tp.hcm|tp.ho chi minh',
          'TP Cần Thơ'=>'can tho',
          'TP Đà Nẵng'=>'da nang',
          'TP Hà Nội'=>'ha noi',
          'TP Hải Phòng'=>'hai phong',
          'Đồng Nai'=>'dong nai',
          'Bình Dương'=>'binh duong',
          'Bà Rịa - Vũng Tàu'=>'ba ria - vung tau|ba ria vung tau',
          'Long An'=>'long an',
          'Bình Phước'=>'binh phuoc|binh phuic',
          'Tây Ninh'=>'tay ninh',
          'An Giang'=>'an giang',
          'Bắc Kạn'=>'bac kan',
          'Bạc Liêu'=>'bac lieu',
          'Bắc Ninh'=>'bac ninh',
          'Bến Tre'=>'ben tre',
          'Bình Định'=>'binh dinh|quy nhon',
          'Bình Thuận'=>'binh thuan',
          'Cà Mau'=>'ca mau',
          'Cao Bằng'=>'cao bang',
          'Đắk Lắk'=>'dak lak',
          'Đắk Nông'=>'dak nong',
          'Điện Biên'=>'dien bien',
          'Đồng Tháp'=>'dong thap',
          'Gia Lai'=>'gia lai',
          'Hà Giang'=>'ha giang',
          'Hà Nam'=>'ha nam',
          'Hà Tĩnh'=>'ha tinh',
          'Hải Dương'=>'hai duong',
          'Hậu Giang'=>'hau giang',
          'Hòa Bình'=>'hoa binh',
          'Hưng Yên'=>'hung yen',
          'Khánh Hòa'=>'khanh hoa',
          'Kiên Giang'=>'kien giang',
          'Kon Tum'=>'kon tum',
          'Lai Châu'=>'lai chau',
          'Lâm Đồng'=>'lam dong',
          'Lạng Sơn'=>'lang son',
          'Lào Cai'=>'lao cai',
          'Nam Định'=>'nam dinh',
          'Nghệ An'=>'nghe an',
          'Ninh Bình'=>'ninh binh',
          'Ninh Thuận'=>'ninh thuan',
          'Phú Thọ'=>'phu tho',
          'Phú Yên'=>'phu yen',
          'Quảng Bình'=>'quang binh',
          'Quảng Nam'=>'quang nam',
          'Quảng Ngãi'=>'quang ngai',
          'Quảng Ninh'=>'quang ninh',
          'Quảng Trị'=>'quang tri',
          'Sóc Trăng'=>'soc trang',
          'Sơn La'=>'son la',
          'Thái Bình'=>'thai binh',
          'Thái Nguyên'=>'thai nguyen',
          'Thanh Hóa'=>'thanh hoa',
          'Thừa Thiên Huế'=>'thua thien hue|hue',
          'Tiền Giang'=>'tien giang',
          'Trà Vinh'=>'tra vinh',
          'Tuyên Quang'=>'tuyen quang',
          'Vĩnh Long'=>'vinh long',
          'Vĩnh Phúc'=>'vinh phuc',
          'Yên Bái'=>'yen bai',
          
 
       );
    foreach($unicode as $nonUnicode=>$uni) $str = preg_replace("/($uni)/i",$nonUnicode,$str);
    

    return $str;
    }

}
?>