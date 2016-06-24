<?php
Class receivablesaleController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dự thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }
//var_dump(strtotime('28-09-2014'));
        $id = $this->registry->router->param_id;

        $nam = date('Y');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

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

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_data['id'][$staff->staff_id] = $staff->staff_id;
        }
        $this->view->data['staffs'] = $staff_data;

        $join = array('table'=>'bank','where'=>'bank.bank_id = receivable.source');

        $receivable_model = $this->model->get('receivableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
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
            
        
        
        
        $tongsodong = count($receivable_model->getAllCosts($data,$join));
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
            'where' => '1=1',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
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
                OR bank_name LIKE "%'.$keyword.'%"
                OR money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        if ($batdau == "") {
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
        }


        $tongthu = $receivable_model->queryCosts('SELECT SUM(money) AS tongthu FROM receivable');
        $tongdathu = $receivable_model->queryCosts('SELECT SUM(pay_money) AS tongdathu FROM receivable');
        foreach ($tongthu as $thu) {
            $tongthu = $thu->tongthu;
        }
        foreach ($tongdathu as $thu) {
            $tongdathu = $thu->tongdathu;
        }
        $this->view->data['tongthu'] = $tongthu;
        $this->view->data['tongdathu'] = $tongdathu;

        $tongthu = $receivable_model->queryCosts('SELECT SUM(money) AS tongthu FROM receivable WHERE week='.$batdau.' AND year ='.$nam);
        $tongdathu = $receivable_model->queryCosts('SELECT SUM(money) AS tongdathu FROM receive WHERE receivable IS NOT NULL AND week='.$batdau.' AND year ='.$nam);
        foreach ($tongthu as $thu) {
            $tongthu = $thu->tongthu;
        }
        foreach ($tongdathu as $thu) {
            $tongdathu = $thu->tongdathu;
        }

        $con = $receivable_model->queryCosts('SELECT money, pay_money FROM receivable WHERE week <='.$batdau.' AND year <='.$nam);
        foreach ($con as $conlai) {
            $tongthu += $conlai->money-$conlai->pay_money;
        }

        $this->view->data['tongthutuan'] = $tongthu;
        $this->view->data['tongdathutuan'] = $tongdathu;
        
        $this->view->data['receivables'] = $receivable_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($receivable_model->getLastCosts()->receivable_id)?$receivable_model->getLastCosts()->receivable_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('receivablesale/index');
    }

    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            $join = array('table'=>'customer','where'=>'customer.customer_id = sale_report.customer');
            if ($_POST['keyword'] == "*") {
                $list = $sale_report_model->getAllSale(null,$join);
            }
            else{
                $data = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $sale_report_model->getAllSale($data,$join);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->customer.'\',\''.$rs->customer_name.'\',\''.($rs->revenue+$rs->revenue_vat).'\')">'.$code.'</li>';
            }
        }
    }
    public function getstaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff_model = $this->model->get('staffModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $staff_model->getAllStaff();
            }
            else{
                $data = array(
                'where'=>'( staff_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $staff_model->getAllStaff($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $staff = $rs->staff_name;
                if ($_POST['keyword'] != "*") {
                    $staff = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->staff_name);
                }
                
                // add new option
                echo '<li onclick="set_item_staff(\''.$rs->staff_id.'\',\''.$rs->staff_name.'\')">'.$staff.'</li>';
            }
        }
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
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
            
            foreach ($list as $rs) {
                // put in bold the written text
                $customer = $rs->customer_name;
                if ($_POST['keyword'] != "*") {
                    $customer = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                }
                
                // add new option
                echo '<li onclick="set_item_customer(\''.$rs->customer_id.'\',\''.$rs->customer_name.'\')">'.$customer.'</li>';
            }
        }
    }

    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\')">'.$vendor.'</li>';
            }
        }
    }

    
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {

            $receivable = $this->model->get('receivableModel');
            $assets_model = $this->model->get('assetsModel');

            $data = array(
                        'receivable_date' => strtotime(date('d-m-Y')),
                        'comment' => trim($_POST['comment']),
                        'code' => trim($_POST['code']),
                        'source' => trim($_POST['source']),
                        'customer' => trim($_POST['customer']),
                        'vendor' => trim($_POST['vendor']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'staff' => trim($_POST['staff']),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'week' => (int)date('W', strtotime(trim($_POST['expect_date']))),
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => trim($_POST['type']),
                        'year' => (int)date('Y', strtotime(trim($_POST['expect_date']))),
                        'pay_money' => trim(str_replace(',','',$_POST['pay_money'])),
                        'invoice_number' => trim($_POST['invoice_number']),
                        'new_expect_date' => strtotime(trim($_POST['new_expect_date'])),
                        'new_week' => (int)date('W', strtotime(trim($_POST['new_expect_date']))),
                        'new_year' => (int)date('Y', strtotime(trim($_POST['new_expect_date']))),
                        'user_expect' => $_SESSION['userid_logined'],
                        );
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', strtotime(trim($_POST['expect_date']))) == 1) && ((int)date('m', strtotime(trim($_POST['expect_date']))) == 12) ) {
                $data['year'] = (int)date('Y', strtotime(trim($_POST['expect_date'])))+1;
            }

            if ($_POST['yes'] != "") {
                
                    
                    $receivable->updateCosts($data,array('receivable_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|receivable|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    

}
?>