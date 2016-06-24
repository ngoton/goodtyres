<?php
Class payableController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
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

        $join = array('table'=>'bank','where'=>'bank.bank_id = payable.source');

        $payable_model = $this->model->get('payableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );

        
        
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
        $this->view->show('payable/index');
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
            $join = array('table'=>'shipment_vendor','where'=>'shipment_vendor.shipment_vendor_id = sale_report.trucking_vendor OR shipment_vendor.shipment_vendor_id = sale_report.barging_vendor OR shipment_vendor.shipment_vendor_id = sale_report.feeder_vendor OR shipment_vendor.shipment_vendor_id = sale_report.commission');
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
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\')">'.$code.'</li>';
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
            $shipment_vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_type = $_POST['vendor_type'];
            if ($vendor_type==1) {
                $data = array(
                'where'=>'( vendor_type = 2 OR vendor_type = 3 OR vendor_type = 4 )',
                );
            }
            else if ($vendor_type==2) {
                $data = array(
                'where'=>'( vendor_type = 6 )',
                );
            }
            else if ($vendor_type==3) {
                $data = array(
                'where'=>'( vendor_type = 5 )',
                );
            }
            else if ($vendor_type==4) {
                $data = array(
                'where'=>'( vendor_type = 1)',
                );
            }
            else if ($vendor_type==5) {
                $data = array(
                'where'=>'( vendor_type = 8)',
                );
            }

            if ($_POST['keyword'] == "*") {
                $list = $shipment_vendor_model->getAllVendor($data);
            }
            else{
                $data['where'] .= ' AND ( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )';
                $list = $shipment_vendor_model->getAllVendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $shipment_vendor = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }


                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\')">'.$shipment_vendor.'</li>';
            }
        }
    }

    public function pay(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $payable = $this->model->get('payableModel');
            $payable_data = $payable->getCosts($_POST['data']);

            $data = array(
                        
                        'pay_date' => strtotime(trim($_POST['pay_date'])),
                        'pay_money' => $payable_data->pay_money + trim(str_replace(',','',$_POST['money'])),
                        'source' => $_POST['source'],
                        'invoice_number' => trim($_POST['invoice_number']),
                        );
          
            $payable->updateCosts($data,array('payable_id' => $_POST['data']));

            $assets_model = $this->model->get('assetsModel');
            $data_asset = array(
                        'bank' => $data['source'],
                        'total' => 0 - trim(str_replace(',','',$_POST['money'])),
                        'assets_date' => $data['pay_date'],
                        'payable' => $_POST['data'],
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

            $pay_model = $this->model->get('payModel');
            $data_pay = array(
                        'source' => $data['source'],
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'pay_date' => $data['pay_date'],
                        'payable' => $_POST['data'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                        'pay_comment' => trim($_POST['comment']),
                    );
            if($data_pay['week'] == 53){
                $data_pay['week'] = 1;
                $data_pay['year'] = $data_pay['year']+1;
            }
            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $data_pay['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $pay_model->createCosts($data_pay);

            if($payable_data->vendor > 0){
                $owe_model = $this->model->get('oweModel');
                $data_owe = array(
                            'vendor' => $payable_data->vendor,
                            'money' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'owe_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'sale_report' => $payable_data->sale_report,
                            'trading' => $payable_data->trading,
                            'agent' => $payable_data->agent,
                            'agent_manifest' => $payable_data->agent_manifest,
                            'invoice' => $payable_data->invoice,
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
            if($payable_data->customer > 0){
                $obtain_model = $this->model->get('obtainModel');
                $data_obtain = array(
                            'customer' => $payable_data->customer,
                            'money' => trim(str_replace(',','',$_POST['money'])),
                            'obtain_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'sale_report' => $payable_data->sale_report,
                            'trading' => $payable_data->trading,
                            'agent' => $payable_data->agent,
                            'agent_manifest' => $payable_data->agent_manifest,
                            'invoice' => $payable_data->invoice,
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

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pay"."|".$_POST['data']."|payable|"."\n"."\r\n";
                        
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

            $payable = $this->model->get('payableModel');
            $assets_model = $this->model->get('assetsModel');
            
            $data = array(
                        'payable_date' => strtotime(date('d-m-Y')),
                        'comment' => trim($_POST['comment']),
                        'code' => trim($_POST['code']),
                        'source' => trim($_POST['source']),
                        'vendor' => trim($_POST['vendor']),
                        'customer' => trim($_POST['customer']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'week' => (int)date('W', strtotime(trim($_POST['expect_date']))),
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => trim($_POST['type']),
                        'year' => (int)date('Y'),
                        'pay_money' => trim(str_replace(',','',$_POST['pay_money'])),
                        'invoice_number' => trim($_POST['invoice_number']),
                        );
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', strtotime(trim($_POST['expect_date']))) == 1) && ((int)date('m', strtotime(trim($_POST['expect_date']))) == 12) ) {
                $data['year'] = (int)date('Y', strtotime(trim($_POST['expect_date'])))+1;
            }

            if ($_POST['yes'] != "") {
                
                    $payable_data = $payable->getCosts($_POST['yes']);

                    if($data['pay_money'] > 0 && $data['pay_money'] != $payable_data->pay_money){
                        

                        
                        $data_asset = array(
                                    'total' => 0 - $data['pay_money'],
                                    
                                );
                        

                        $assets_model->updateAssets($data_asset,array('payable'=>$_POST['yes']));

                        $pay_model = $this->model->get('payModel');
                        $data_pay = array(
                                    
                                    'money' => $data['pay_money'],
                                    
                                );
                        

                        $pay_model->updateCosts($data_pay,array('payable'=>$_POST['yes']));

                        if($payable_data->vendor > 0){
                            $owe_model = $this->model->get('oweModel');
                            $data_owe = array(
                                        'vendor' => $payable_data->vendor,
                                        'money' => 0 - ($data['pay_money'] - $payable_data->pay_money),
                                        'owe_date' => $payable_data->pay_date,
                                        'week' => (int)date('W',$payable_data->pay_date),
                                        'year' => (int)date('Y',$payable_data->pay_date),
                                        'sale_report' => $payable_data->sale_report,
                                        'trading' => $payable_data->trading,
                                        'agent' => $payable_data->agent,
                                        'agent_manifest' => $payable_data->agent_manifest,
                                        'invoice' => $payable_data->invoice,
                                    );
                            if($data_owe['week'] == 53){
                                $data_owe['week'] = 1;
                                $data_owe['year'] = $data_owe['year']+1;
                            }
                            if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                $data_owe['year'] = (int)date('Y',$payable_data->pay_date)+1;
                            }

                            $owe_model->createOwe($data_owe);
                        }
                        if($payable_data->customer > 0){
                            $obtain_model = $this->model->get('obtainModel');
                            $data_obtain = array(
                                        'customer' => $payable_data->customer,
                                        'money' => 0 - ($data['pay_money'] - $payable_data->pay_money),
                                        'obtain_date' => $payable_data->pay_date,
                                        'week' => (int)date('W',$payable_data->pay_date),
                                        'year' => (int)date('Y',$payable_data->pay_date),
                                        'sale_report' => $payable_data->sale_report,
                                        'trading' => $payable_data->trading,
                                        'agent' => $payable_data->agent,
                                        'agent_manifest' => $payable_data->agent_manifest,
                                        'invoice' => $payable_data->invoice,
                                    );
                            if($data_obtain['week'] == 53){
                                $data_obtain['week'] = 1;
                                $data_obtain['year'] = $data_obtain['year']+1;
                            }
                            if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                $data_obtain['year'] = (int)date('Y',$payable_data->pay_date)+1;
                            }

                            $obtain_model->createObtain($data_obtain);
                        }

                    }

                    if ($data['source'] != $payable_data->source) {
                        $data_asset = array(
                                    'bank' => $data['source'],
                                );
                        $assets_model->updateAssets($data_asset,array('payable'=>$_POST['yes']));
                    }

                    $payable->updateCosts($data,array('payable_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|payable|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $payable->createCosts($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$payable->getLastCosts()->payable_id."|payable|".implode("-",$data)."\n"."\r\n";
                        
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
            $payable = $this->model->get('payableModel');
           $assets_model = $this->model->get('assetsModel');
            $pay_model = $this->model->get('payModel');
            $owe_model = $this->model->get('oweModel');
            $obtain_model = $this->model->get('obtainModel');

            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $payable_data=$payable->getCosts($data);

                       $payable->deleteCosts($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE payable = '.$data);
                       $pay_model->queryCosts('DELETE FROM receive WHERE payable = '.$data);

                       if($payable_data->pay_money > 0){

                            if($payable_data->vendor > 0){
                                $data_owe = array(
                                            'vendor' => $payable_data->vendor,
                                            'money' => $payable_data->pay_money,
                                            'owe_date' => $payable_data->pay_date,
                                            'week' => (int)date('W',$payable_data->pay_date),
                                            'year' => (int)date('Y',$payable_data->pay_date),
                                            'sale_report' => $payable_data->sale_report,
                                            'trading' => $payable_data->trading,
                                            'agent' => $payable_data->agent,
                                            'agent_manifest' => $payable_data->agent_manifest,
                                            'invoice' => $payable_data->invoice,
                                        );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',$payable_data->pay_date)+1;
                                }

                                $owe_model->createOwe($data_owe);
                            }
                            if($payable_data->customer > 0){
                                $data_obtain = array(
                                            'customer' => $payable_data->customer,
                                            'money' => $payable_data->pay_money,
                                            'obtain_date' => $payable_data->pay_date,
                                            'week' => (int)date('W',$payable_data->pay_date),
                                            'year' => (int)date('Y',$payable_data->pay_date),
                                            'sale_report' => $payable_data->sale_report,
                                            'trading' => $payable_data->trading,
                                            'agent' => $payable_data->agent,
                                            'agent_manifest' => $payable_data->agent_manifest,
                                            'invoice' => $payable_data->invoice,
                                        );
                                if($data_obtain['week'] == 53){
                                    $data_obtain['week'] = 1;
                                    $data_obtain['year'] = $data_obtain['year']+1;
                                }
                                if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                    $data_obtain['year'] = (int)date('Y',$payable_data->pay_date)+1;
                                }

                                $obtain_model->createObtain($data_obtain);
                            }
                        }

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $payable_data=$payable->getCosts($_POST['data']);

                        $payable->deleteCosts($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE payable = '.$_POST['data']);
                       $pay_model->queryCosts('DELETE FROM receive WHERE payable = '.$_POST['data']);

                       if($payable_data->pay_money > 0){
                            if($payable_data->vendor > 0){
                                $data_owe = array(
                                            'vendor' => $payable_data->vendor,
                                            'money' => $payable_data->pay_money,
                                            'owe_date' => $payable_data->pay_date,
                                            'week' => (int)date('W',$payable_data->pay_date),
                                            'year' => (int)date('Y',$payable_data->pay_date),
                                            'sale_report' => $payable_data->sale_report,
                                            'trading' => $payable_data->trading,
                                            'agent' => $payable_data->agent,
                                            'agent_manifest' => $payable_data->agent_manifest,
                                            'invoice' => $payable_data->invoice,
                                        );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',$payable_data->pay_date)+1;
                                }

                                $owe_model->createOwe($data_owe);
                            }
                            if($payable_data->customer > 0){
                                $data_obtain = array(
                                            'customer' => $payable_data->customer,
                                            'money' => $payable_data->pay_money,
                                            'obtain_date' => $payable_data->pay_date,
                                            'week' => (int)date('W',$payable_data->pay_date),
                                            'year' => (int)date('Y',$payable_data->pay_date),
                                            'sale_report' => $payable_data->sale_report,
                                            'trading' => $payable_data->trading,
                                            'agent' => $payable_data->agent,
                                            'agent_manifest' => $payable_data->agent_manifest,
                                            'invoice' => $payable_data->invoice,
                                        );
                                if($data_obtain['week'] == 53){
                                    $data_obtain['week'] = 1;
                                    $data_obtain['year'] = $data_obtain['year']+1;
                                }
                                if (((int)date('W',$payable_data->pay_date) == 1) && ((int)date('m',$payable_data->pay_date) == 12) ) {
                                    $data_obtain['year'] = (int)date('Y',$payable_data->pay_date)+1;
                                }

                                $obtain_model->createObtain($data_obtain);
                            }
                        }

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|payable|"."\n"."\r\n";
                        
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

            $payable = $this->model->get('payableModel');
            $bank = $this->model->get('bankModel');
            $vendor = $this->model->get('shipmentvendorModel');
            $assets_model = $this->model->get('assetsModel');
            $pay_model = $this->model->get('payModel');
            $owe_model = $this->model->get('oweModel');

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
                    if ($val[1] != null  ) {

                            
                                if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))) {
                                    $vendor_data = array(
                                    'shipment_vendor_name' => trim($val[1]),
                                    
                                    );
                                    $vendor->createVendor($vendor_data);
                                    $id_vendor = $vendor->getLastVendor()->shipment_vendor_id;
                                }
                                else if($vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))){
                                    $id_vendor = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))->shipment_vendor_id;
                                    
                                }


                            
                            $payable_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[3]));                                      
                            $payable_date = $payable_date-3600;

                            $expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[4]));                                      
                            $expect_date = $expect_date-3600;

                            if(!$bank->getBankByWhere(array('bank_name'=>trim($val[9])))) {
                                $bank_data = array(
                                'bank_name' => trim($val[9]),
                                
                                );
                                $bank->createBank($bank_data);
                                $id_bank = $bank->getLastBank()->bank_id;
                            }
                            else if($bank->getBankByWhere(array('bank_name'=>trim($val[9])))){
                                $id_bank = $bank->getBankByWhere(array('bank_name'=>trim($val[9])))->bank_id;
                                
                            }

                            if(!$payable->getCostsByWhere(array('vendor'=>$id_vendor,'code'=>trim($val[8])))) {
                                $payable_data = array(
                                'vendor' => $id_vendor,
                                'money' => trim($val[2]),
                                'payable_date' => $payable_date,
                                'expect_date' => $expect_date,
                                'week' => trim($val[5]),
                                'source' => $id_bank,
                                'code' => trim($val[8]),
                                'comment' => trim($val[10]),
                                'create_user' => $_SESSION['userid_logined'],
                                'year' => date('Y',$payable_date),
                                'type' => trim($val[11]),
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }

                                if ((trim($val[5]) == 1) && (date('m',$payable_date) == 12) ) {
                                    $payable_data['year'] = date('Y',$payable_date)+1;
                                }

                                $payable->createCosts($payable_data);

                                
                                $data_owe = array(
                                            'vendor' => $id_vendor,
                                            'money' => trim($val[2]),
                                            'owe_date' => $payable_date,
                                            'week' => trim($val[5]),
                                            'year' => date('Y',$payable_date),
                                        );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }

                                if ((trim($val[5]) == 1) && (date('m',$payable_date) == 12) ) {
                                    $data_owe['year'] = date('Y',$payable_date)+1;
                                }

                                $owe_model->createOwe($data_owe);


                                if (trim($val[6] != null && trim($val[7]) != null)) {
                                    $pay_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[6]));                                      
                                    $pay_date = $pay_date-3600;

                                    $data_pay = array(
                                                'source' => $payable_data['source'],
                                                'money' => trim($val[7]),
                                                'pay_date' => $pay_date,
                                                'payable' => $payable->getLastCosts()->payable_id,
                                                'week' => (int)date('W',$pay_date),
                                                'year' => (int)date('Y',$pay_date),
                                            );
                                    if($data_pay['week'] == 53){
                                        $data_pay['week'] = 1;
                                        $data_pay['year'] = $data_pay['year']+1;
                                    }
                                    if (((int)date('W',$pay_date) == 1) && ((int)date('m',$pay_date) == 12) ) {
                                        $data_pay['year'] = (int)date('Y',$pay_date)+1;
                                    }

                                    $pay_model->createCosts($data_pay);

                                    $data_asset = array(
                                                'bank' => $payable_data['source'],
                                                'total' => 0 - trim($val[7]),
                                                'assets_date' => $pay_date,
                                                'payable' => $payable->getLastCosts()->payable_id,
                                                'week' => (int)date('W',$pay_date),
                                                'year' => (int)date('Y',$pay_date),
                                            );
                                    if($data_asset['week'] == 53){
                                        $data_asset['week'] = 1;
                                        $data_asset['year'] = $data_asset['year']+1;
                                    }
                                    if (((int)date('W',$pay_date) == 1) && ((int)date('m',$pay_date) == 12) ) {
                                        $data_asset['year'] = (int)date('Y',$pay_date)+1;
                                    }

                                    $assets_model->createAssets($data_asset);

                                    $data_owe = array(
                                                'vendor' => $id_vendor,
                                                'money' => 0 - trim($val[7]),
                                                'owe_date' => $pay_date,
                                                'week' => (int)date('W',$pay_date),
                                                'year' => (int)date('Y',$pay_date),
                                            );
                                    if($data_owe['week'] == 53){
                                        $data_owe['week'] = 1;
                                        $data_owe['year'] = $data_owe['year']+1;
                                    }
                                    if (((int)date('W',$pay_date) == 1) && ((int)date('m',$pay_date) == 12) ) {
                                        $data_owe['year'] = (int)date('Y',$pay_date)+1;
                                    }

                                    $owe_model->createOwe($data_owe);
                                }
                            }
                            else if($payable->getCostsByWhere(array('vendor'=>$id_vendor,'code'=>trim($val[8])))){
                                $id_payable = $payable->getCostsByWhere(array('vendor'=>$id_vendor,'code'=>trim($val[8])))->payable_id;
                                $payable_data = array(
                                'vendor' => $id_vendor,
                                'money' => trim($val[2]),
                                'payable_date' => $payable_date,
                                'expect_date' => $expect_date,
                                'week' => trim($val[5]),
                                'source' => $id_bank,
                                'code' => trim($val[8]),
                                'comment' => trim($val[10]),
                                'create_user' => $_SESSION['userid_logined'],
                                'year' => date('Y',$payable_date),
                                'type' => trim($val[11]),
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }

                                if ((trim($val[5]) == 1) && (date('m',$payable_date) == 12) ) {
                                    $payable_data['year'] = date('Y',$payable_date)+1;
                                }
                                
                                $payable->updateCosts($payable_data,array('payable_id' => $id_payable));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('payable');
        }
        $this->view->show('payable/import');

    }

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>