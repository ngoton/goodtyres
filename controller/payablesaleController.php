<?php
Class payablesaleController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dự chi vendor';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'payable_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }

        $id = $this->registry->router->param_id;

        $nam = date('Y');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer();
        $customer_data = array();
        foreach ($customers as $customer) {
            $customer_data['name'][$customer->customer_id] = $customer->customer_name;
            $customer_data['id'][$customer->customer_id] = $customer->customer_id;
        }
        $this->view->data['customers'] = $customer_data;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor();
        $vendor_data = array();
        foreach ($vendors as $vendor) {
            $vendor_data['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
            $vendor_data['id'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_id;
        }
        $this->view->data['vendors'] = $vendor_data;

        $join = array('table'=>'bank','where'=>'bank.bank_id = payable.source');

        $payable_model = $this->model->get('payableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND payable_id = '.$id;
        }
        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money';
                if ($batdau != "") {
                    $data['where'] .= ' AND week = '.$batdau.' AND year ='.$nam;
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money)';
                if ($batdau != "") {
                    $data['where'] .= ' AND week <= '.$batdau.' AND year ='.$nam;
                }
            }
            
        
        
        
        $tongsodong = count($payable_model->getAllCosts($data,$join));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND payable_id = '.$id;
        }
        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money';
                if ($batdau != "") {
                    $data['where'] .= ' AND week = '.$batdau.' AND year ='.$nam;
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money)';
                if ($batdau != "") {
                    $data['where'] .= ' AND week <= '.$batdau.' AND year ='.$nam;
                }
            }
            
        
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank.bank_name LIKE "%'.$keyword.'%"
                OR code LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        if ($batdau == "") {
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
        }

        $tongtra = $payable_model->queryCosts('SELECT SUM(money) AS tongtra FROM payable WHERE week='.$batdau.' AND year ='.$nam);
        $tongdatra = $payable_model->queryCosts('SELECT SUM(money) AS tongdatra FROM pay WHERE payable IS NOT NULL AND week='.$batdau.' AND year ='.$nam);
        foreach ($tongtra as $tra) {
            $tongtra = $tra->tongtra;
        }
        foreach ($tongdatra as $tra) {
            $tongdatra = $tra->tongdatra;
        }

        $con = $payable_model->queryCosts('SELECT money, pay_money FROM payable WHERE week <='.$batdau.' AND year <='.$nam);
        foreach ($con as $conlai) {
            $tongtra += $conlai->money-$conlai->pay_money;
        }

        $this->view->data['tongtratuan'] = $tongtra;
        $this->view->data['tongdatratuan'] = $tongdatra;

        $tongtra = $payable_model->queryCosts('SELECT SUM(money) AS tongtra FROM payable ');
        $tongdatra = $payable_model->queryCosts('SELECT SUM(pay_money) AS tongdatra FROM payable ');
        foreach ($tongtra as $tra) {
            $tongtra = $tra->tongtra;
        }
        foreach ($tongdatra as $tra) {
            $tongdatra = $tra->tongdatra;
        }
        $this->view->data['tongtra'] = $tongtra;
        $this->view->data['tongdatra'] = $tongdatra;

        
        $this->view->data['payables'] = $payable_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($payable_model->getLastCosts()->payable_id)?$payable_model->getLastCosts()->payable_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('payablesale/index');
    }

    

}
?>