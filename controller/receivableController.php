<?php
Class receivableController Extends baseController {
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

        $id = $this->registry->router->param_id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $id = 0;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = "";
            $ketthuc = "";
            $trangthai = 0;
        }
//var_dump(strtotime('28-09-2014'));
        

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
            'where' => 'money != 0',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <='.strtotime($ketthuc);
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <='.strtotime($ketthuc);
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
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'money != 0',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

            if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <='.strtotime($ketthuc);
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <='.strtotime($ketthuc);
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

        
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
        


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
        $this->view->show('receivable/index');
    }

    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
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

    public function pay(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $receivable = $this->model->get('receivableModel');
            $receivable_data = $receivable->getCosts($_POST['data']);

            $data = array(
                        
                        'pay_date' => strtotime(trim($_POST['pay_date'])),
                        'pay_money' => $receivable_data->pay_money + trim(str_replace(',','',$_POST['money'])),
                        'source' => $_POST['source'],
                        'invoice_number' => trim($_POST['invoice_number']),
                        );
          
            $receivable->updateCosts($data,array('receivable_id' => $_POST['data']));

            $assets_model = $this->model->get('assetsModel');
            $data_asset = array(
                        'bank' => $data['source'],
                        'total' => trim(str_replace(',','',$_POST['money'])),
                        'assets_date' => $data['pay_date'],
                        'receivable' => $_POST['data'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                    );
            if($data_asset['week'] == 53){
                $data_asset['week'] = 1;
                $data_asset['year'] = $data_asset['year']+1;
            }


            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $assets_model->createAssets($data_asset);

            $receive_model = $this->model->get('receiveModel');
            $data_receive = array(
                        'source' => $data['source'],
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'receive_date' => $data['pay_date'],
                        'receivable' => $_POST['data'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                        'receive_comment' => trim($_POST['comment']),
                    );
            if($data_receive['week'] == 53){
                $data_receive['week'] = 1;
                $data_receive['year'] = $data_receive['year']+1;
            }
            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $receivable_data['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $receive_model->createCosts($data_receive);

            if($receivable_data->staff > 0){

                $staff_debt_model = $this->model->get('staffdebtModel');
                $data_staff_debt = array(
                            'staff' => $receivable_data->staff,
                            'source' => $data['source'],
                            'money' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'staff_debt_date' => $data['pay_date'],
                            'comment' => $receivable_data->comment,
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'status' => 1,
                        );
                if($data_staff_debt['week'] == 53){
                    $data_staff_debt['week'] = 1;
                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_staff_debt['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $staff_debt_model->createCost($data_staff_debt);
            }

            if($receivable_data->customer > 0){

                $obtain_model = $this->model->get('obtainModel');
                $data_obtain = array(
                            'customer' => $receivable_data->customer,
                            'money' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'obtain_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'sale_report' => $receivable_data->sale_report,
                            'trading' => $receivable_data->trading,
                            'agent' => $receivable_data->agent,
                            'agent_manifest' => $receivable_data->agent_manifest,
                            'invoice' => $receivable_data->invoice,
                            'import_tire' => $receivable_data->import_tire,
                            'order_tire' => $receivable_data->order_tire,
                        );
                if($data_obtain['week'] == 53){
                    $data_obtain['week'] = 1;
                    $data_obtain['year'] = $data_obtain['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_obtain['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $obtain_model->createObtain($data_obtain);
            }

            if($receivable_data->vendor > 0){

                $owe_model = $this->model->get('oweModel');
                $data_owe = array(
                            'vendor' => $receivable_data->vendor,
                            'money' => trim(str_replace(',','',$_POST['money'])),
                            'owe_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'sale_report' => $receivable_data->sale_report,
                            'trading' => $receivable_data->trading,
                            'agent' => $receivable_data->agent,
                            'agent_manifest' => $receivable_data->agent_manifest,
                            'invoice' => $receivable_data->invoice,
                        );
                if($data_owe['week'] == 53){
                    $data_owe['week'] = 1;
                    $data_owe['year'] = $data_owe['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_owe['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $owe_model->createOwe($data_owe);
            }

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pay"."|".$_POST['data']."|receivable|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
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
                
                    $receivable_data = $receivable->getCosts($_POST['yes']);

                    if ($data['pay_money'] > 0 && $data['pay_money'] != $receivable_data->pay_money) {
                        
                        $data_asset = array(
                                    
                                    'total' => $data['pay_money'],
                                    
                                );
                        

                        $assets_model->updateAssets($data_asset,array('receivable'=>$_POST['yes']));

                        $receive_model = $this->model->get('receiveModel');
                        $data_receive = array(
                                    'money' => $data['pay_money'],
                                );
                     

                        $receive_model->updateCosts($data_receive,array('receivable'=>$_POST['yes']));

                        if($receivable_data->staff > 0){

                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $data_staff_debt = array(
                                        'staff' => $receivable_data->staff,
                                        'source' => $data['source'],
                                        'money' => 0 - ($data['pay_money']-$receivable_data->pay_money),
                                        'staff_debt_date' => $receivable_data->pay_date,
                                        'comment' => $receivable_data->comment,
                                        'week' => (int)date('W',$receivable_data->pay_date),
                                        'year' => (int)date('Y',$receivable_data->pay_date),
                                        'status' => 1,
                                    );
                            if($data_staff_debt['week'] == 53){
                                $data_staff_debt['week'] = 1;
                                $data_staff_debt['year'] = $data_staff_debt['year']+1;
                            }
                            if (((int)date('W',$receivable_data->pay_date) == 1) && ((int)date('m',$receivable_data->pay_date) == 12) ) {
                                $data_staff_debt['year'] = (int)date('Y',$receivable_data->pay_date)+1;
                            }

                            $staff_debt_model->createCost($data_staff_debt);
                        }

                        if($receivable_data->customer > 0){

                            $obtain_model = $this->model->get('obtainModel');
                            $data_obtain = array(
                                        'customer' => $receivable_data->customer,
                                        'money' => 0 - ($data['pay_money']-$receivable_data->pay_money),
                                        'obtain_date' => $receivable_data->pay_date,
                                        'week' => (int)date('W',$receivable_data->pay_date),
                                        'year' => (int)date('Y',$receivable_data->pay_date),
                                        'sale_report' => $receivable_data->sale_report,
                                        'trading' => $receivable_data->trading,
                                        'agent' => $receivable_data->agent,
                                        'agent_manifest' => $receivable_data->agent_manifest,
                                        'invoice' => $receivable_data->invoice,
                                    );
                            if($data_obtain['week'] == 53){
                                $data_obtain['week'] = 1;
                                $data_obtain['year'] = $data_obtain['year']+1;
                            }
                            if (((int)date('W',$receivable_data->pay_date) == 1) && ((int)date('m',$receivable_data->pay_date) == 12) ) {
                                $data_obtain['year'] = (int)date('Y',$receivable_data->pay_date)+1;
                            }

                            $obtain_model->createObtain($data_obtain);
                        }

                        if($receivable_data->vendor > 0){

                            $owe_model = $this->model->get('oweModel');
                            $data_owe = array(
                                        'vendor' => $receivable_data->vendor,
                                        'money' => 0 - ($data['pay_money']-$receivable_data->pay_money),
                                        'owe_date' => $receivable_data->pay_date,
                                        'week' => (int)date('W',$receivable_data->pay_date),
                                        'year' => (int)date('Y',$receivable_data->pay_date),
                                        'sale_report' => $receivable_data->sale_report,
                                        'trading' => $receivable_data->trading,
                                        'agent' => $receivable_data->agent,
                                        'agent_manifest' => $receivable_data->agent_manifest,
                                        'invoice' => $receivable_data->invoice,
                                    );
                            if($data_owe['week'] == 53){
                                $data_owe['week'] = 1;
                                $data_owe['year'] = $data_owe['year']+1;
                            }
                            if (((int)date('W',$receivable_data->pay_date) == 1) && ((int)date('m',$receivable_data->pay_date) == 12) ) {
                                $data_owe['year'] = (int)date('Y',$receivable_data->pay_date)+1;
                            }

                            $owe_model->createOwe($data_owe);
                        }

                    }

                    if ($data['source'] != $receivable_data->source) {
                        $data_asset = array(
                                    'bank' => $data['source'],
                                );
                        $assets_model->updateAssets($data_asset,array('receivable'=>$_POST['yes']));
                    }

                    $receivable->updateCosts($data,array('receivable_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|receivable|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $receivable->createCosts($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$receivable->getLastCosts()->receivable_id."|receivable|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $receivable = $this->model->get('receivableModel');
            $assets_model = $this->model->get('assetsModel');
            $receive_model = $this->model->get('receiveModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $obtain_model = $this->model->get('obtainModel');
            $owe_model = $this->model->get('oweModel');

            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $re = $receivable->getCosts($data);
                       $receivable->deleteCosts($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE receivable = '.$data);
                       $receive_model->queryCosts('DELETE FROM receive WHERE receivable = '.$data);
                       if($re->pay_money > 0){
                            if($re->staff > 0){

                                $data_staff_debt = array(
                                            'staff' => $re->staff,
                                            'source' => $re->source,
                                            'money' => $re->pay_money,
                                            'staff_debt_date' => $re->pay_date,
                                            'comment' => $re->comment,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'status' => 1,
                                        );
                                if($data_staff_debt['week'] == 53){
                                    $data_staff_debt['week'] = 1;
                                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_staff_debt['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $staff_debt_model->createCost($data_staff_debt);
                            }

                            if($re->customer > 0){

                                $data_obtain = array(
                                            'customer' => $re->customer,
                                            'money' => $re->pay_money,
                                            'obtain_date' => $re->pay_date,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'sale_report' => $re->sale_report,
                                            'trading' => $re->trading,
                                            'agent' => $re->agent,
                                            'agent_manifest' => $re->agent_manifest,
                                            'invoice' => $re->invoice,
                                        );
                                if($data_obtain['week'] == 53){
                                    $data_obtain['week'] = 1;
                                    $data_obtain['year'] = $data_obtain['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_obtain['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $obtain_model->createObtain($data_obtain);
                            }
                            if($re->vendor > 0){

                                $data_owe = array(
                                            'vendor' => $re->vendor,
                                            'money' => $re->pay_money,
                                            'owe_date' => $re->pay_date,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'sale_report' => $re->sale_report,
                                            'trading' => $re->trading,
                                            'agent' => $re->agent,
                                            'agent_manifest' => $re->agent_manifest,
                                            'invoice' => $re->invoice,
                                        );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $owe_model->createOwe($data_owe);
                            }
                       }
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|receivable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $re = $receivable->getCosts($_POST['data']);
                        $receivable->deleteCosts($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE receivable = '.$_POST['data']);
                        $receive_model->queryCosts('DELETE FROM receive WHERE receivable = '.$_POST['data']);
                        if($re->pay_money > 0){
                            if($re->staff > 0){

                                $data_staff_debt = array(
                                            'staff' => $re->staff,
                                            'source' => $re->source,
                                            'money' => $re->pay_money,
                                            'staff_debt_date' => $re->pay_date,
                                            'comment' => $re->comment,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'status' => 1,
                                        );
                                if($data_staff_debt['week'] == 53){
                                    $data_staff_debt['week'] = 1;
                                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_staff_debt['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $staff_debt_model->createCost($data_staff_debt);
                            }

                            if($re->customer > 0){

                                $data_obtain = array(
                                            'customer' => $re->customer,
                                            'money' => $re->pay_money,
                                            'obtain_date' => $re->pay_date,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'sale_report' => $re->sale_report,
                                            'trading' => $re->trading,
                                            'agent' => $re->agent,
                                            'agent_manifest' => $re->agent_manifest,
                                            'invoice' => $re->invoice,
                                        );
                                if($data_obtain['week'] == 53){
                                    $data_obtain['week'] = 1;
                                    $data_obtain['year'] = $data_obtain['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_obtain['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $obtain_model->createObtain($data_obtain);
                            }
                            if($re->vendor > 0){

                                $data_owe = array(
                                            'vendor' => $re->vendor,
                                            'money' => $re->pay_money,
                                            'owe_date' => $re->pay_date,
                                            'week' => (int)date('W',$re->pay_date),
                                            'year' => (int)date('Y',$re->pay_date),
                                            'sale_report' => $re->sale_report,
                                            'trading' => $re->trading,
                                            'agent' => $re->agent,
                                            'agent_manifest' => $re->agent_manifest,
                                            'invoice' => $re->invoice,
                                        );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',$re->pay_date) == 1) && ((int)date('m',$re->pay_date) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',$re->pay_date)+1;
                                }

                                $owe_model->createOwe($data_owe);
                            }
                       }
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|receivable|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $receivable = $this->model->get('receivableModel');
            $staff = $this->model->get('staffModel');
            $bank = $this->model->get('bankModel');
            $customer = $this->model->get('customerModel');
            $assets_model = $this->model->get('assetsModel');
            $receive_model = $this->model->get('receiveModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $obtain_model = $this->model->get('obtainModel');

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
            
             

                for ($row = 4; $row <= $highestRow; ++ $row) {
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
                    if ($val[1] != null ) {

                            if(trim($val[1]) != null){

                                if(!$customer->getCustomerByWhere(array('customer_name'=>trim($val[1])))) {
                                    $customer_data = array(
                                    'customer_name' => trim($val[1]),
                                    
                                    );
                                    $customer->createCustomer($customer_data);
                                    $id_customer = $customer->getLastCustomer()->customer_id;
                                }
                                else if($customer->getCustomerByWhere(array('customer_name'=>trim($val[1])))){
                                    $id_customer = $customer->getCustomerByWhere(array('customer_name'=>trim($val[1])))->customer_id;
                                    
                                }
                            }
                            else{
                                $id_customer = "";
                            }


                            if(trim($val[2]) != null){

                                if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[2])))) {
                                    $staff_data = array(
                                    'staff_name' => trim($val[2]),
                                    
                                    );
                                    $staff->createStaff($staff_data);
                                    $id_staff = $staff->getLastStaff()->staff_id;
                                }
                                else if($staff->getStaffByWhere(array('staff_name'=>trim($val[2])))){
                                    $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[2])))->staff_id;
                                    
                                }
                            }
                            else{
                                $id_staff = "";
                            }

                            
                            $receivable_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[4]));                                      
                            $receivable_date = $receivable_date-3600;

                            $expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[5]));                                      
                            $expect_date = $expect_date-3600;

                            if(!$bank->getBankByWhere(array('bank_name'=>trim($val[10])))) {
                                $bank_data = array(
                                'bank_name' => trim($val[10]),
                                
                                );
                                $bank->createBank($bank_data);
                                $id_bank = $bank->getLastBank()->bank_id;
                            }
                            else if($bank->getBankByWhere(array('bank_name'=>trim($val[10])))){
                                $id_bank = $bank->getBankByWhere(array('bank_name'=>trim($val[10])))->bank_id;
                                
                            }

                            if(!$receivable->getCostsByWhere(array('staff'=>$id_staff,'customer'=>$id_customer,'code'=>trim($val[9])))) {
                                $receivable_data = array(
                                'staff' => $id_staff,
                                'customer' => $id_customer,
                                'money' => trim($val[3]),
                                'receivable_date' => $receivable_date,
                                'expect_date' => $expect_date,
                                'week' => trim($val[6]),
                                'source' => $id_bank,
                                'code' => trim($val[9]),
                                'comment' => trim($val[11]),
                                'create_user' => $_SESSION['userid_logined'],
                                'year' => date('Y',$receivable_date),
                                'type' => trim($val[12]),
                                );

                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if ((trim($val[6]) == 1) && (date('m',$receivable_date) == 12) ) {
                                    $receivable_data['year'] = date('Y',$receivable_date)+1;
                                }

                                $receivable->createCosts($receivable_data);

                                if(trim($val[2]) != null){

                                    
                                    $data_staff_debt = array(
                                                'staff' => $id_staff,
                                                'source' => $receivable_data['source'],
                                                'money' => trim($val[3]),
                                                'staff_debt_date' => $receivable_date,
                                                'comment' => trim($val[11]),
                                                'week' => trim($val[6]),
                                                'year' => date('Y',$receivable_date),
                                                'status' => 1,
                                            );
                                    if($data_staff_debt['week'] == 53){
                                        $data_staff_debt['week'] = 1;
                                        $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                    }
                                    if ((trim($val[6]) == 1) && (date('m',$receivable_date) == 12) ) {
                                        $data_staff_debt['year'] = date('Y',$receivable_date)+1;
                                    }

                                    $staff_debt_model->createCost($data_staff_debt);
                                }

                                if(trim($val[1]) != null){

                                    
                                    $data_obtain = array(
                                                'customer' => $id_customer,
                                                'money' => trim($val[3]),
                                                'obtain_date' => $receivable_date,
                                                'week' => trim($val[6]),
                                                'year' => date('Y',$receivable_date),
                                            );
                                    if($data_obtain['week'] == 53){
                                        $data_obtain['week'] = 1;
                                        $data_obtain['year'] = $data_obtain['year']+1;
                                    }
                                    if ((trim($val[6]) == 1) && (date('m',$receivable_date) == 12) ) {
                                        $data_obtain['year'] = date('Y',$receivable_date)+1;
                                    }

                                    $obtain_model->createObtain($data_obtain);
                                }

                                if (trim($val[7] != null && trim($val[8]) != null)) {
                                    $receive_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[7]));                                      
                                    $receive_date = $receive_date-3600;

                                    $data_receive = array(
                                                'source' => $receivable_data['source'],
                                                'money' => trim($val[8]),
                                                'receive_date' => $receive_date,
                                                'receivable' => $receivable->getLastCosts()->receivable_id,
                                                'week' => (int)date('W',$receive_date),
                                                'year' => (int)date('Y',$receive_date),
                                            );
                                    if($data_receive['week'] == 53){
                                        $data_receive['week'] = 1;
                                        $data_receive['year'] = $data_receive['year']+1;
                                    }
                                    if (((int)date('W',$receive_date) == 1) && (date('m',$receivable_date) == 12) ) {
                                        $data_receive['year'] = date('Y',$receivable_date)+1;
                                    }

                                    $receive_model->createCosts($data_receive);

                                    $data_asset = array(
                                                'bank' => $receivable_data['source'],
                                                'total' => trim($val[8]),
                                                'assets_date' => $receive_date,
                                                'receivable' => $receivable->getLastCosts()->receivable_id,
                                                'week' => (int)date('W',$receive_date),
                                                'year' => (int)date('Y',$receive_date),
                                            );
                                    if($data_asset['week'] == 53){
                                        $data_asset['week'] = 1;
                                        $data_asset['year'] = $data_asset['year']+1;
                                    }
                                    if (((int)date('W',$receive_date) == 1) && (date('m',$receivable_date) == 12) ) {
                                        $data_asset['year'] = date('Y',$receivable_date)+1;
                                    }

                                    $assets_model->createAssets($data_asset);

                                    if(trim($val[2]) != null){

                                    
                                        $data_staff_debt = array(
                                                    'staff' => $id_staff,
                                                    'source' => $receivable_data['source'],
                                                    'money' => 0 - trim($val[8]),
                                                    'staff_debt_date' => $receive_date,
                                                    'comment' => trim($val[11]),
                                                    'week' => (int)date('W',$receive_date),
                                                    'year' => date('Y',$receive_date),
                                                    'status' => 1,
                                                );
                                        if($data_staff_debt['week'] == 53){
                                            $data_staff_debt['week'] = 1;
                                            $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                        }
                                        if (((int)date('W',$receive_date) == 1) && (date('m',$receivable_date) == 12) ) {
                                            $data_staff_debt['year'] = date('Y',$receivable_date)+1;
                                        }

                                        $staff_debt_model->createCost($data_staff_debt);
                                    }

                                    if(trim($val[1]) != null){

                                        
                                        $data_obtain = array(
                                                    'customer' => $id_customer,
                                                    'money' => 0 - trim($val[8]),
                                                    'obtain_date' => $receive_date,
                                                    'week' => (int)date('W',$receive_date),
                                                    'year' => date('Y',$receive_date),
                                                );
                                        if($data_obtain['week'] == 53){
                                            $data_obtain['week'] = 1;
                                            $data_obtain['year'] = $data_obtain['year']+1;
                                        }
                                        if (((int)date('W',$receive_date) == 1) && (date('m',$receivable_date) == 12) ) {
                                            $data_obtain['year'] = date('Y',$receivable_date)+1;
                                        }

                                        $obtain_model->createObtain($data_obtain);
                                    }
                                }

                            }
                            else if($receivable->getCostsByWhere(array('staff'=>$id_staff,'customer'=>$id_customer,'code'=>trim($val[9])))){
                                $id_receivable = $receivable->getCostsByWhere(array('staff'=>$id_staff,'customer'=>$id_customer,'code'=>trim($val[9])))->receivable_id;
                                $receivable_data = array(
                                'staff' => $id_staff,
                                'customer' => $id_customer,
                                'money' => trim($val[3]),
                                'receivable_date' => $receivable_date,
                                'expect_date' => $expect_date,
                                'week' => trim($val[6]),
                                'source' => $id_bank,
                                'code' => trim($val[9]),
                                'comment' => trim($val[11]),
                                'create_user' => $_SESSION['userid_logined'],
                                'year' => date('Y',$receivable_date),
                                'type' => trim($val[12]),
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if ((trim($val[6]) == 1) && (date('m',$receivable_date) == 12) ) {
                                    $receivable_data['year'] = date('Y',$receivable_date)+1;
                                }
                                $receivable->updateCosts($receivable_data,array('receivable_id' => $id_receivable));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('receivable');
        }
        $this->view->show('receivable/import');

    }

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>