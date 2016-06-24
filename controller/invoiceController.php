<?php
Class invoiceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Hóa đơn';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'invoice_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $id = $this->registry->router->param_id;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor();
        $vendor_data = array();
        foreach ($vendors as $vendor) {
            $vendor_data['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
            $vendor_data['id'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_id;
        }
        $this->view->data['vendors'] = $vendor_data;

        $invoice_model = $this->model->get('invoiceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'invoice_date >= '.strtotime($batdau).' AND invoice_date <= '.$ketthuc,
        );

        if (isset($id) && $id != "") {
            $data['where'] = 'invoice_id = '.$id;
        }

        
        $join = array('table'=>'customer','where'=>'customer.customer_id = invoice.customer');
        
        $tongsodong = count($invoice_model->getAllInvoice($data,$join));
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
            'where' => 'invoice_date >= '.strtotime($batdau).' AND invoice_date <= '.strtotime($ketthuc),
            );

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND create_user = '.$_SESSION['userid_logined'];
        }

        if (isset($id) && $id != "") {
            $data['where'] = 'invoice_id = '.$id;
        }

        if ($keyword != '') {
            $search = '( shipment_vendor_name LIKE "%'.$keyword.'%"  
                OR customer_name LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $this->view->data['invoices'] = $invoice_model->getAllInvoice($data,$join);
        $this->view->data['lastID'] = isset($invoice_model->getLastInvoice()->invoice_id)?$invoice_model->getLastInvoice()->invoice_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('invoice/index');
    }

    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            
            $invoice = $this->model->get('invoiceModel');
             $customer = $this->model->get('customerModel');
              $receivable = $this->model->get('receivableModel');
               $obtain = $this->model->get('obtainModel');
               $owe = $this->model->get('oweModel');
               $payable = $this->model->get('payableModel');
               $vendor = $this->model->get('shipmentvendorModel');
               $pending_payable = $this->model->get('pendingpayableModel');

            
            //$id_vendor = $vendor->getVendorByWhere(array('shipment_vendor_name'=>'TCMT','vendor_type'=>2))->shipment_vendor_id;
            $id_vendor2 = $vendor->getVendorByWhere(array('shipment_vendor_name'=>'Mr Sơn'))->shipment_vendor_id;
            $id_vendor3 = $vendor->getVendorByWhere(array('shipment_vendor_name'=>'Mr Quý (HD)'))->shipment_vendor_id;

            if ($_POST['customer'] == null) {
                 
                 $customer_data = array(
                    'customer_name'=> trim($_POST['customer_name']),
                );
                 $customer->createCustomer($customer_data);

                 $id_customer = $customer->getLastCustomer()->customer_id;
            }
            else{
                $id_customer = trim($_POST['customer']);
            }

            if ($_POST['vendor'] == null) {
                 
                 $vendor_data = array(
                    'shipment_vendor_name'=> trim($_POST['shipment_vendor_name']),
                );
                 $vendor->createVendor($vendor_data);

                 $id_vendor = $vendor->getLastVendor()->shipment_vendor_id;
            }
            else{
                $id_vendor = trim($_POST['vendor']);
            }

            $data = array(
                        'invoice_date' => strtotime(trim($_POST['invoice_date'])),
                        'invoice_number' => trim($_POST['invoice_number']),
                        'invoice_number_in' => trim($_POST['invoice_number_in']),
                        'customer' => $id_customer,
                        'comment' => trim($_POST['comment']),
                        'receive' => trim(str_replace(',','',$_POST['receive'])),
                        'pay1' => trim(str_replace(',','',$_POST['pay1'])),
                        'pay2' => trim(str_replace(',','',$_POST['pay2'])),
                        'pay3' => trim(str_replace(',','',$_POST['pay3'])),
                        'expect_date_receive' => strtotime(trim($_POST['expect_date_receive'])),
                        'expect_date_pay1' => strtotime(trim($_POST['expect_date_pay1'])),
                        'expect_date_pay2' => strtotime(trim($_POST['expect_date_pay2'])),
                        'day_invoice' => strtotime(trim($_POST['day_invoice'])),
                        'vendor'=> $id_vendor,
                        'vendor2'=> $id_vendor2,
                        'vendor3'=> $id_vendor3,
                        'document_cost' => trim(str_replace(',','',$_POST['document_cost'])),
                        'pay_cost' => trim(str_replace(',','',$_POST['pay_cost'])),
                        
                        );
            $data['profit'] = $data['receive'] - $data['pay1'] - $data['pay2'] - $data['pay3'] - $data['document_cost'] - $data['pay_cost'];
            $data['estimate_cost'] = $data['document_cost']+$data['pay_cost'];
            
            


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                
                    $invoice_data = $invoice->getInvoice($_POST['yes']);
                
                    $invoice->updateInvoice($data,array('invoice_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    
                    if($data['pay1'] != $invoice_data->pay1 && $invoice_data->pay1 > 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->updateOwe($owe_data,array('invoice'=>$_POST['yes'],'vendor'=>$id_vendor,'money'=>$invoice_data->pay1));

                        $payable_data = array(
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay1'],
                            'week' => (int)date('W',$data['expect_date_pay1']),
                            'year' => (int)date('Y',$data['expect_date_pay1']),
                            'code' => $data['invoice_number'],
                            'source' => 6,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay1']) == 1) && ((int)date('m',$data['expect_date_pay1']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay1'])+1;
                        }

                        $check = $payable->getCostsByWhere(array('vendor'=>$id_vendor,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay1));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                        $payable->updateCosts($payable_data,array('vendor'=>$id_vendor,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay1));

                    }
                    else if($data['pay1'] != $invoice_data->pay1 && $invoice_data->pay1 <= 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay1'],
                            'week' => (int)date('W',$data['expect_date_pay1']),
                            'year' => (int)date('Y',$data['expect_date_pay1']),
                            'code' => $data['invoice_number'],
                            'source' => 6,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay1']) == 1) && ((int)date('m',$data['expect_date_pay1']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay1'])+1;
                        }

                        $payable->createCosts($payable_data);

                    }

                    if($data['pay2'] != $invoice_data->pay2 && $invoice_data->pay2 > 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->updateOwe($owe_data,array('vendor'=>$id_vendor2,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay2));

                        $payable_data = array(
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay2'],
                            'week' => (int)date('W',$data['expect_date_pay2']),
                            'year' => (int)date('Y',$data['expect_date_pay2']),
                            'code' => $data['invoice_number'],
                            'source' => 5,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay2']) == 1) && ((int)date('m',$data['expect_date_pay2']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay2'])+1;
                        }

                        $check = $payable->getCostsByWhere(array('vendor'=>$id_vendor2,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay2));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                        $payable->updateCosts($payable_data,array('vendor'=>$id_vendor2,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay2));

                    }
                    else if($data['pay2'] != $invoice_data->pay2 && $invoice_data->pay2 <= 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay2'],
                            'week' => (int)date('W',$data['expect_date_pay2']),
                            'year' => (int)date('Y',$data['expect_date_pay2']),
                            'code' => $data['invoice_number'],
                            'source' => 5,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay2']) == 1) && ((int)date('m',$data['expect_date_pay2']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay2'])+1;
                        }

                        $payable->createCosts($payable_data);

                    }

                    if($data['pay3'] != $invoice_data->pay3 && $invoice_data->pay3 > 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->updateOwe($owe_data,array('vendor'=>$id_vendor3,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay3));

                        $payable_data = array(
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_receive'],
                            'week' => (int)date('W',$data['expect_date_receive']),
                            'year' => (int)date('Y',$data['expect_date_receive']),
                            'code' => $data['invoice_number'],
                            'source' => 1,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }

                        $check = $payable->getCostsByWhere(array('vendor'=>$id_vendor3,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay3));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                        $payable->updateCosts($payable_data,array('vendor'=>$id_vendor3,'invoice'=>$_POST['yes'],'money'=>$invoice_data->pay3));

                    }

                    else if($data['pay3'] != $invoice_data->pay3 && $invoice_data->pay3 <= 0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_receive'],
                            'week' => (int)date('W',$data['expect_date_receive']),
                            'year' => (int)date('Y',$data['expect_date_receive']),
                            'code' => $data['invoice_number'],
                            'source' => 1,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }

                        $payable->createCosts($payable_data);

                    }

                    else{
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->updateOwe($owe_data,array('invoice'=>$_POST['yes'],'owe_date'=>$invoice_data->day_invoice));

                        $payable_data = array(
                            
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay1'],
                            'week' => (int)date('W',$data['expect_date_pay1']),
                            'year' => (int)date('Y',$data['expect_date_pay1']),
                            'code' => $data['invoice_number'],
                            'source' => 6,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay1']) == 1) && ((int)date('m',$data['expect_date_pay1']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay1'])+1;
                        }

                        $payable->updateCosts($payable_data,array('expect_date'=>$invoice_data->expect_date_pay1,'invoice'=>$_POST['yes']));

                        $payable_data = array(
                            
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay2'],
                            'week' => (int)date('W',$data['expect_date_pay2']),
                            'year' => (int)date('Y',$data['expect_date_pay2']),
                            'code' => $data['invoice_number'],
                            'source' => 5,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay2']) == 1) && ((int)date('m',$data['expect_date_pay2']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay2'])+1;
                        }

                        $payable->updateCosts($payable_data,array('expect_date'=>$invoice_data->expect_date_pay2,'invoice'=>$_POST['yes']));

                        $payable_data = array(
                            
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_receive'],
                            'week' => (int)date('W',$data['expect_date_receive']),
                            'year' => (int)date('Y',$data['expect_date_receive']),
                            'code' => $data['invoice_number'],
                            'source' => 1,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $_POST['yes'],
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }

                        $payable->updateCosts($payable_data,array('expect_date'=>$invoice_data->expect_date_receive,'invoice'=>$_POST['yes']));

                    }
                        

                    
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['receive'],
                        'receivable_date' => $data['invoice_date'],
                        'expect_date' => $data['expect_date_receive'],
                        'week' => (int)date('W',$data['expect_date_receive']),
                        'year' => (int)date('Y',$data['expect_date_receive']),
                        'code' => $data['invoice_number'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 3,
                        
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }
                    
                    $receivable->updateCosts($receivable_data,array('invoice'=>trim($_POST['yes']),'money'=>$invoice_data->receive));

                    if($data['receive'] != $invoice_data->receive){
                        $obtain_data = array(
                            'obtain_date' => $data['day_invoice'],
                            'customer' => $data['customer'],
                            'money' => $data['receive'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                            }

                        $obtain->updateObtain($obtain_data,array('invoice'=>$_POST['yes'],'money'=>$invoice_data->receive));
                    }
                    elseif ($data['day_invoice'] != $invoice_data->day_invoice) {
                        $obtain_data = array(
                            'obtain_date' => $data['day_invoice'],
                            'customer' => $data['customer'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $_POST['yes'],
                        );

                        if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                            }

                        $obtain->updateObtain($obtain_data,array('invoice'=>$_POST['yes'],'obtain_date'=>$invoice_data->day_invoice));
                    }

                    $data_pending = array(
                        'code' => $data['invoice_number'],
                        'revenue' => $data['receive'],
                        'cost' => $data['pay1']+$data['pay2']+$data['pay3']+$data['document_cost']+$data['pay_cost'],
                        'money' => $data['pay1']+$data['pay2']+$data['pay3'],
                        'comment' => 'Chi phí '.$data['invoice_number'].' '.$data['comment'],
                        'approve' => null,
                    );

                    $check = $pending_payable->getCostsByWhere(array('invoice'=>$_POST['yes']));

                    if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                        $data_pending['approve'] = 10;
                    }

                    $pending_payable->updateCosts($data_pending,array('invoice'=>$_POST['yes']));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|invoice|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    

                
                    $invoice->createInvoice($data);
                    echo "Thêm thành công";

                    if($data['pay1']>0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                        );

                    if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor,
                            'money' => $data['pay1'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay1'],
                            'week' => (int)date('W',$data['expect_date_pay1']),
                            'year' => (int)date('Y',$data['expect_date_pay1']),
                            'code' => $data['invoice_number'],
                            'source' => 6,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                            'check_vat' => 1,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay1']) == 1) && ((int)date('m',$data['expect_date_pay1']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay1'])+1;
                        }

                        $payable->createCosts($payable_data);
                    }

                    if($data['pay2']>0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                        );

                    if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor2,
                            'money' => $data['pay2'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_pay2'],
                            'week' => (int)date('W',$data['expect_date_pay2']),
                            'year' => (int)date('Y',$data['expect_date_pay2']),
                            'code' => $data['invoice_number'],
                            'source' => 5,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                            'check_vat' => 1,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_pay2']) == 1) && ((int)date('m',$data['expect_date_pay2']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_pay2'])+1;
                        }

                        $payable->createCosts($payable_data);
                    }

                    if($data['pay3']>0){
                        $owe_data = array(
                            'owe_date' => $data['day_invoice'],
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'week' => (int)date('W',$data['day_invoice']),
                            'year' => (int)date('Y',$data['day_invoice']),
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                        );

                    if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $id_vendor3,
                            'money' => $data['pay3'],
                            'payable_date' => $data['invoice_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['expect_date_receive'],
                            'week' => (int)date('W',$data['expect_date_receive']),
                            'year' => (int)date('Y',$data['expect_date_receive']),
                            'code' => $data['invoice_number'],
                            'source' => 1,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 3,
                            'invoice' => $invoice->getLastInvoice()->invoice_id,
                            'check_vat' => 1,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }

                        $payable->createCosts($payable_data);
                    }
                    

                    
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['receive'],
                        'receivable_date' => $data['invoice_date'],
                        'expect_date' => $data['expect_date_receive'],
                        'week' => (int)date('W',$data['expect_date_receive']),
                        'year' => (int)date('Y',$data['expect_date_receive']),
                        'code' => $data['invoice_number'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 3,
                        'invoice' => $invoice->getLastInvoice()->invoice_id,
                        'check_vat' => 1,
                        
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$data['expect_date_receive']) == 1) && ((int)date('m',$data['expect_date_receive']) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$data['expect_date_receive'])+1;
                        }

                    $receivable->createCosts($receivable_data);

                    $obtain_data = array(
                        'obtain_date' => $data['day_invoice'],
                        'customer' => $data['customer'],
                        'money' => $data['receive'],
                        'week' => (int)date('W',$data['day_invoice']),
                        'year' => (int)date('Y',$data['day_invoice']),
                        'invoice' => $invoice->getLastInvoice()->invoice_id,
                    );

                    if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['day_invoice']) == 1) && ((int)date('m',$data['day_invoice']) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$data['day_invoice'])+1;
                        }

                    $obtain->createObtain($obtain_data);

                    $data_pending = array(
                        'code' => $data['invoice_number'],
                        'revenue' => $data['receive'],
                        'cost' => $data['pay1']+$data['pay2']+$data['pay3']+$data['document_cost']+$data['pay_cost'],
                        'invoice' => $invoice->getLastInvoice()->invoice_id,
                        'money' => $data['pay1']+$data['pay2']+$data['pay3'],
                        'comment' => 'Chi phí '.$data['invoice_number'].' '.$data['comment'],
                    );

                    $pending_payable->createCosts($data_pending);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$invoice->getLastInvoice()->invoice_id."|invoice|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $invoice = $this->model->get('invoiceModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $re = $receivable->getAllCosts(array('where'=>'invoice='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'invoice='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE invoice='.$data);
                        $obtain->queryObtain('DELETE FROM obtain WHERE invoice='.$data);
                        $owe->queryOwe('DELETE FROM owe WHERE invoice='.$data);
                        $payable->queryCosts('DELETE FROM payable WHERE invoice='.$data);
                        $invoice->deleteInvoice($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|invoice|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $re = $receivable->getAllCosts(array('where'=>'invoice='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'invoice='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE invoice='.$_POST['data']);
                        $obtain->queryObtain('DELETE FROM obtain WHERE invoice='.$_POST['data']);
                        $owe->queryOwe('DELETE FROM owe WHERE invoice='.$_POST['data']);
                        $payable->queryCosts('DELETE FROM payable WHERE invoice='.$_POST['data']);
                        $invoice->deleteInvoice($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|invoice|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $invoice = $this->model->get('invoiceModel');
            $vendor = $this->model->get('shipmentvendorModel');
            $customer = $this->model->get('customerModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');

            $id_customer = $customer->getCustomerByWhere(array('customer_name'=>'TCMT (Tiếp vận CM)'))->customer_id;
            $id_vendor = $vendor->getVendorByWhere(array('shipment_vendor_name'=>'Mr Sơn'))->shipment_vendor_id;

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
            

                for ($row =2; $row <= $highestRow; ++ $row) {
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
                    if ($val[1] != null && $val[2] != null && $val[3] != null && $val[4] != null  ) {

                            


                            $invoice_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[1]));                                      
                            $invoice_date = $invoice_date-3600;

                            $expect_date_receive = PHPExcel_Shared_Date::ExcelToPHP(trim($val[5]));                                      
                            $expect_date_receive = $expect_date_receive-3600;

                            $expect_date_pay1 = PHPExcel_Shared_Date::ExcelToPHP(trim($val[7]));                                      
                            $expect_date_pay1 = $expect_date_pay1-3600;

                            $expect_date_pay2 = PHPExcel_Shared_Date::ExcelToPHP(trim($val[9]));                                      
                            $expect_date_pay2 = $expect_date_pay2-3600;


                            if(!$invoice->getInvoiceByWhere(array('invoice_number'=>trim($val[2])))) {
                                $invoice_data = array(
                                'vendor' => $id_vendor,
                                'invoice_date' => $invoice_date,
                                'customer' => $id_customer,
                                'invoice_number' => trim($val[2]),
                                'comment' => trim($val[3]),
                                'receive' => trim($val[4]),
                                'expect_date_receive' => $expect_date_receive,
                                'pay1' => trim($val[6]),
                                'expect_date_pay1' => $expect_date_pay1,
                                'pay2' => trim($val[8]),
                                'expect_date_pay2' => $expect_date_pay2,
                                'profit' => trim($val[10]),

                                );

                                $invoice->createInvoice($invoice_data);

                                

                                $owe_data = array(
                                        'owe_date' => $invoice_data['invoice_date'],
                                        'vendor' => $invoice_data['vendor'],
                                        'money' => $invoice_data['pay1']+$invoice_data['pay2'],
                                        'week' => (int)date('W',$invoice_data['invoice_date']),
                                        'year' => (int)date('Y',$invoice_data['invoice_date']),
                                        'invoice' => $invoice->getLastInvoice()->invoice_id,
                                    );

                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['invoice_date']) == 1) && ((int)date('m',$invoice_data['invoice_date']) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$invoice_data['invoice_date'])+1;
                                }

                                    $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $invoice_data['vendor'],
                                    'money' => $invoice_data['pay1'],
                                    'payable_date' => $invoice_data['invoice_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $invoice_data['expect_date_pay1'],
                                    'week' => (int)date('W',$invoice_data['expect_date_pay1']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_pay1']),
                                    'code' => null,
                                    'source' => 5,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    'invoice' => $invoice->getLastInvoice()->invoice_id,
                                    
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_pay1']) == 1) && ((int)date('m',$invoice_data['expect_date_pay1']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$invoice_data['expect_date_pay1'])+1;
                                }

                                $payable->createCosts($payable_data);

                                $payable_data = array(
                                    'vendor' => $invoice_data['vendor'],
                                    'money' => $invoice_data['pay2'],
                                    'payable_date' => $invoice_data['invoice_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $invoice_data['expect_date_pay2'],
                                    'week' => (int)date('W',$invoice_data['expect_date_pay2']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_pay2']),
                                    'code' => null,
                                    'source' => 5,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    'invoice' => $invoice->getLastInvoice()->invoice_id,
                                    
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_pay2']) == 1) && ((int)date('m',$invoice_data['expect_date_pay2']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$invoice_data['expect_date_pay2'])+1;
                                }

                                $payable->createCosts($payable_data);

                                $receivable_data = array(
                                    'customer' => $invoice_data['customer'],
                                    'money' => $invoice_data['receive'],
                                    'receivable_date' => $invoice_data['invoice_date'],
                                    'expect_date' => $invoice_data['expect_date_receive'],
                                    'week' => (int)date('W',$invoice_data['expect_date_receive']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_receive']),
                                    'code' => null,
                                    'source' => 6,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    'invoice' => $invoice->getLastInvoice()->invoice_id,
                                    
                                );

                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_receive']) == 1) && ((int)date('m',$invoice_data['expect_date_receive']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$invoice_data['expect_date_receive'])+1;
                                }

                                $receivable->createCosts($receivable_data);

                                $obtain_data = array(
                                    'obtain_date' => $invoice_data['invoice_date'],
                                    'customer' => $invoice_data['customer'],
                                    'money' => $invoice_data['receive'],
                                    'week' => (int)date('W',$invoice_data['invoice_date']),
                                    'year' => (int)date('Y',$invoice_data['invoice_date']),
                                    'invoice' => $invoice->getLastInvoice()->invoice_id,
                                );

                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['invoice_date']) == 1) && ((int)date('m',$invoice_data['invoice_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$invoice_data['invoice_date'])+1;
                                }

                                $obtain->createObtain($obtain_data);
                                

                            }
                            else if($invoice->getInvoiceByWhere(array('invoice_number'=>trim($val[2])))) {
                               $id_invoice = $invoice->getInvoiceByWhere(array('invoice_number'=>trim($val[2])))->invoice_id;
                                $invoice_data = array(
                                'vendor' => $id_vendor,
                                'invoice_date' => $invoice_date,
                                'customer' => $id_customer,
                                'invoice_number' => trim($val[2]),
                                'comment' => trim($val[3]),
                                'receive' => trim($val[4]),
                                'expect_date_receive' => $expect_date_receive,
                                'pay1' => trim($val[6]),
                                'expect_date_pay1' => $expect_date_pay1,
                                'pay2' => trim($val[8]),
                                'expect_date_pay2' => $expect_date_pay2,
                                'profit' => trim($val[10]),

                                );

                                $invoice->updateInvoice($invoice_data,array('invoice_id' => $id_invoice));

                               

                                $owe_data = array(
                                        'owe_date' => $invoice_data['invoice_date'],
                                        'vendor' => $invoice_data['vendor'],
                                        'money' => $invoice_data['pay1']+$invoice_data['pay2'],
                                        'week' => (int)date('W',$invoice_data['invoice_date']),
                                        'year' => (int)date('Y',$invoice_data['invoice_date']),
                                    );

                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['invoice_date']) == 1) && ((int)date('m',$invoice_data['invoice_date']) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$invoice_data['invoice_date'])+1;
                                }
                                
                                    $owe->updateOwe($owe_data,array('invoice'=>$id_invoice));

                                $payable->queryCosts('DELETE FROM payable WHERE invoice = '.$id_invoice);

                                $payable_data = array(
                                    'vendor' => $invoice_data['vendor'],
                                    'money' => $invoice_data['pay1'],
                                    'payable_date' => $invoice_data['invoice_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $invoice_data['expect_date_pay1'],
                                    'week' => (int)date('W',$invoice_data['expect_date_pay1']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_pay1']),
                                    'code' => null,
                                    'source' => 5,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    'invoice' => $id_invoice,
                                    
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_pay1']) == 1) && ((int)date('m',$invoice_data['expect_date_pay1']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$invoice_data['expect_date_pay1'])+1;
                                }

                                $payable->createCosts($payable_data);

                                $payable_data = array(
                                    'vendor' => $invoice_data['vendor'],
                                    'money' => $invoice_data['pay2'],
                                    'payable_date' => $invoice_data['invoice_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $invoice_data['expect_date_pay2'],
                                    'week' => (int)date('W',$invoice_data['expect_date_pay2']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_pay2']),
                                    'code' => null,
                                    'source' => 5,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    'invoice' => $id_invoice,
                                    
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_pay2']) == 1) && ((int)date('m',$invoice_data['expect_date_pay2']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$invoice_data['expect_date_pay2'])+1;
                                }

                                $payable->createCosts($payable_data);

                                $receivable_data = array(
                                    'customer' => $invoice_data['customer'],
                                    'money' => $invoice_data['receive'],
                                    'receivable_date' => $invoice_data['invoice_date'],
                                    'expect_date' => $invoice_data['expect_date_receive'],
                                    'week' => (int)date('W',$invoice_data['expect_date_receive']),
                                    'year' => (int)date('Y',$invoice_data['expect_date_receive']),
                                    'code' => null,
                                    'source' => 6,
                                    'comment' => $invoice_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 3,
                                    
                                );

                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['expect_date_receive']) == 1) && ((int)date('m',$invoice_data['expect_date_receive']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$invoice_data['expect_date_receive'])+1;
                                }

                                $receivable->updateCosts($receivable_data,array('invoice'=>$id_invoice));

                                $obtain_data = array(
                                    'obtain_date' => $invoice_data['invoice_date'],
                                    'customer' => $invoice_data['customer'],
                                    'money' => $invoice_data['receive'],
                                    'week' => (int)date('W',$invoice_data['invoice_date']),
                                    'year' => (int)date('Y',$invoice_data['invoice_date']),
                                );

                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$invoice_data['invoice_date']) == 1) && ((int)date('m',$invoice_data['invoice_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$invoice_data['invoice_date'])+1;
                                }

                                $obtain->updateObtain($obtain_data,array('invoice'=>$id_invoice));

                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('invoice');
        }
        $this->view->show('invoice/import');

    }
    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>