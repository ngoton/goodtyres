<?php
Class dailyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Thu chi ';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
        }

        
        $daily_model = $this->model->get('dailyModel');
        $daily_bank_model = $this->model->get('dailybankModel');
        $bank_model = $this->model->get('bankModel');

        $banks = $bank_model->getAllBank(array('where'=>'symbol IS NOT NULL'));
        $this->view->data['banks'] = $banks;

        $data_bank = array(
            'where' => 'daily_bank_date < '.strtotime($batdau),
        );
        $bank_dau = $daily_bank_model->getAllDaily($data_bank);
        $tondau = array();
        foreach ($bank_dau as $ba) {
            $tondau[$ba->bank] = isset($tondau[$ba->bank])?$tondau[$ba->bank]+$ba->money:$ba->money;
        }

        $data_bank = array(
            'where' => 'daily_bank_date >= '.strtotime($batdau).' AND daily_bank_date <= '.strtotime($ketthuc),
        );
        $bank_ps = $daily_bank_model->getAllDaily($data_bank);
        $thu = array(); $chi = array();
        foreach ($bank_ps as $ba) {
            if ($ba->money > 0) {
                $thu[$ba->bank] = isset($thu[$ba->bank])?$thu[$ba->bank]+$ba->money:$ba->money;
            }
            else{
                $chi[$ba->bank] = isset($chi[$ba->bank])?$chi[$ba->bank]+$ba->money:$ba->money;
            }
        }

        $this->view->data['tondau'] = $tondau;
        $this->view->data['thu'] = $thu;
        $this->view->data['chi'] = $chi;

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
        );
        
        
        $tongsodong = count($daily_model->getAllDaily($data));
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
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money_in LIKE "%'.$keyword.'%" 
                    OR money_out LIKE "%'.$keyword.'%" 
                    OR account LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $dailys = $daily_model->getAllDaily($data);
        
        $this->view->data['dailys'] = $dailys;

        $receivable_model = $this->model->get('receivableModel');
        $payable_model = $this->model->get('payableModel');
        $clearing = array();
        foreach ($dailys as $daily) {
            
            $sts = explode(',', $daily->clearing);
            foreach ($sts as $key) {
                if ($daily->money_in > 0) {
                    $receivables = $receivable_model->getCosts($key);
                    if ($receivables) {
                        if (!isset($clearing[$daily->daily_id])) {
                            $clearing[$daily->daily_id] = str_replace(',', '.', $receivables->code.':'.$receivables->comment.'~'.$receivables->receivable_id);
                        }
                        else{
                            $clearing[$daily->daily_id] .= ','.str_replace(',', '.', $receivables->code.':'.$receivables->comment.'~'.$receivables->receivable_id);
                        }
                    }
                }
                if ($daily->money_out > 0) {
                    $payables = $payable_model->getCosts($key);
                    if ($payables) {
                        if (!isset($clearing[$daily->daily_id])) {
                            $clearing[$daily->daily_id] = str_replace(',', '.', $payables->code.':'.$payables->comment.'~'.$payables->payable_id);
                        }
                        else{
                            $clearing[$daily->daily_id] .= ','.str_replace(',', '.', $payables->code.':'.$payables->comment.'~'.$payables->payable_id);
                        }
                    }
                }
                
            }
            
        }
        $this->view->data['clearing'] = $clearing;

        $this->view->data['lastID'] = isset($daily_model->getLastDaily()->daily_id)?$daily_model->getLastDaily()->daily_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('daily/index');
    }

    public function getClearing(){
        header('Content-type: application/json');
        $q = $_GET["search"];

        $in = $_GET["in"];
        $out = $_GET["out"];

        if ($in != "" && $in != 0) {
            $receivable_model = $this->model->get('receivableModel');
            $data = array(
                'where' => 'code LIKE "%'.$q.'%"',
            );
            $receivables = $receivable_model->getAllCosts($data);
            $arr = array();
            foreach ($receivables as $receivable) {
                if ($receivable->money-$receivable->pay_money > 0) {
                    $arr[] = str_replace(',', '.', $receivable->code.':'.$receivable->comment.'-('.$this->lib->formatMoney($receivable->money-$receivable->pay_money).')~'.$receivable->receivable_id);
                }
                
            }
        }
        if ($out != "" && $out != 0) {
            $payable_model = $this->model->get('payableModel');
            $data = array(
                'where' => 'code LIKE "%'.$q.'%"',
            );
            $payables = $payable_model->getAllCosts($data);
            $arr = array();
            foreach ($payables as $payable) {
                if ($payable->money-$payable->pay_money > 0) {
                    $arr[] = str_replace(',', '.', $payable->code.':'.$payable->comment.'-('.$this->lib->formatMoney($payable->money-$payable->pay_money).')~'.$payable->payable_id);
                }
            }
        }
        
        
        echo json_encode($arr);
    }

    public function account() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Thu chi ';

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
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $trangthai = 0;
        }

        
        $daily_model = $this->model->get('dailyModel');

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );

        if ($trangthai==1) {
                $data['where'] = ' AND daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
            }
        
        
        $tongsodong = count($daily_model->getAllDaily($data));
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
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );
        if ($trangthai==1) {
                $data['where'] .= ' AND daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
            }
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money_in LIKE "%'.$keyword.'%" 
                    OR money_out LIKE "%'.$keyword.'%" 
                    OR account LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['dailys'] = $daily_model->getAllDaily($data);
        $this->view->data['lastID'] = isset($daily_model->getLastDaily()->daily_id)?$daily_model->getLastDaily()->daily_id:0;

///////////////////

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


        $join = array('table'=>'bank','where'=>'bank.bank_id = receivable.source');

        $receivable_model = $this->model->get('receivableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(staff IS NULL OR staff <= 0) AND money != 0 ',
        );

            if ($trangthai==1) {
                $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
            }
            
        
        
        
        $tongsodong = count($receivable_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $order_by = 'expect_date';

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(staff IS NULL OR staff <= 0) AND money != 0 ',
            );

            if ($trangthai==1) {
                $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
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

        $receivables = $receivable_model->getAllCosts($data,$join);

        $this->view->data['receivables'] = $receivables;

        //////////

        $join = array('table'=>'bank','where'=>'bank.bank_id = payable.source');

        $payable_model = $this->model->get('payableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'money != 0 ',
        );

        
            if ($trangthai==1) {
                $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
            }
        
        
        
        $tongsodong = count($payable_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'money != 0 ',
            );

            if ($trangthai==1) {
                $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).' AND debit > 0 AND credit > 0';
            }
            else{
                $data['where'] .= ' AND (debit IS NULL OR debit = 0) AND (credit IS NULL OR credit = 0)';
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

        $payables = $payable_model->getAllCosts($data,$join);
        
        $this->view->data['payables'] = $payables;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('daily/account');
    }

    public function additional(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $data = array(
                        
                        'document_number' => trim($_POST['document_number']),
                        'document_date' => strtotime(trim($_POST['document_date'])),
                        'additional_date' => strtotime(trim($_POST['additional_date'])),
                        'additional_comment' => trim($_POST['additional_comment']),
                        'debit' => trim($_POST['debit']),
                        'credit' => trim($_POST['credit']),
                        'code' => trim($_POST['code']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        );
            
            $data_debit = array(
                'account_balance_date' => $data['additional_date'],
                'account' => $data['debit'],
                'money' => $data['money'],
                'week' => (int)date('W', $data['additional_date']),
                'year' => (int)date('Y', $data['additional_date']),
            );
            $data_credit = array(
                'account_balance_date' => $data['additional_date'],
                'account' => $data['credit'],
                'money' => (0-$data['money']),
                'week' => (int)date('W', $data['additional_date']),
                'year' => (int)date('Y', $data['additional_date']),
            );

            if($data_debit['week'] == 53){
                $data_debit['week'] = 1;
                $data_debit['year'] = $data_debit['year']+1;

                $data_credit['week'] = 1;
                $data_credit['year'] = $data_credit['year']+1;
            }
            if (((int)date('W', $data['additional_date']) == 1) && ((int)date('m', $data['additional_date']) == 12) ) {
                $data_debit['year'] = (int)date('Y', $data['additional_date'])+1;
                $data_credit['year'] = (int)date('Y', $data['additional_date'])+1;
            }

            
                
                    $additional_model->createAdditional($data);
                    echo "Thêm thành công";

                    $id_additional = $additional_model->getLastAdditional()->additional_id;
                    $data_debit['additional'] = $id_additional;
                    $data_credit['additional'] = $id_additional;

                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);

                    if ($_POST['payable'] != "") {
                        $payable_model = $this->model->get('payableModel');
                        $data_payable = array(
                            'debit' =>$data['debit'],
                            'credit' => $data['credit'],
                        );
                        $payable_model->updateCosts($data_payable,array('payable_id'=>$_POST['payable']));
                    }
                    if ($_POST['receivable'] != "") {
                        $receivable_model = $this->model->get('receivableModel');
                        $data_receivable = array(
                            'debit' =>$data['debit'],
                            'credit' => $data['credit'],
                        );
                        $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$_POST['receivable']));
                    }
                

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$additional_model->getLastAdditional()->additional_id."|additional|".implode("-",$data)."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);
                
                    
                
            
                    
        }
    }

    public function report() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Thu chi ';

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
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC, approve DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $trangthai = 0;
        }

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $daily_model = $this->model->get('dailyModel');

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
        );
        
        if ($trangthai>0) {
            $data['where'] .= ' AND approve > 0';
        }
        else{
            $data['where'] = '(approve IS NULL OR approve = 0)';
        }

        $tongsodong = count($daily_model->getAllDaily($data));
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
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
        if ($trangthai>0) {
            $data['where'] .= ' AND approve > 0';
        }
        else{
            $data['where'] = '(approve IS NULL OR approve = 0)';
        }
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money_in LIKE "%'.$keyword.'%" 
                    OR money_out LIKE "%'.$keyword.'%" 
                    OR account LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['dailys'] = $daily_model->getAllDaily($data);
        $this->view->data['lastID'] = isset($daily_model->getLastDaily()->daily_id)?$daily_model->getLastDaily()->daily_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('daily/report');
    }

    public function approve(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $dailys = $this->model->get('dailyModel');
            //$costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
          
            $dailys->updateDaily($data,array('daily_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|daily|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approveall(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $dailys = $this->model->get('dailyModel');
            //$costs_data = $costs->getCosts($_POST['data']);
            $dailys->queryDaily('UPDATE daily SET approve = '.$_SESSION['userid_logined'].' WHERE (approve IS NULL OR approve = 0)');
            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
          
            $dailys->updateDaily($data,array('daily_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve|all|daily|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            $agent_model = $this->model->get('agentModel');
            $agent_manifest_model = $this->model->get('agentmanifestModel');
            $invoice_model = $this->model->get('invoiceModel');
            $order_tire_model = $this->model->get('ordertireModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $sale_report_model->getAllSale();
                $list_agent = $agent_model->getAllAgent();
                $list_agentmanifest = $agent_manifest_model->getAllAgent();
                $list_invoice = $invoice_model->getAllInvoice();
                $list_order = $order_tire_model->getAllTire();
            }
            else{
                $data_sale = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_sale = $sale_report_model->getAllSale($data_sale);

                $data_agent = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_agent = $agent_model->getAllAgent($data_agent);

                $data_agentmanifest = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_agentmanifest = $agent_manifest_model->getAllAgent($data_agentmanifest);

                $data_invoice = array(
                'where'=>'( invoice_number LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_invoice = $invoice_model->getAllInvoice($data_invoice);

                $data_order = array(
                'where'=>'( order_number LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_order = $order_tire_model->getAllTire($data_order);
            }

            
            foreach ($list_sale as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_agent as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->name.")".'</li>';
            }
            foreach ($list_agentmanifest as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_invoice as $rs) {
                // put in bold the written text
                $code = $rs->invoice_number;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->invoice_number);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->invoice_number.'\',\''.$rs->invoice_number.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_order as $rs) {
                // put in bold the written text
                $code = $rs->order_number;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->order_number);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->order_number.'\',\''.$rs->order_number.'\')">'.$code.'</li>';
            }
        }
    }

    public function getreceivable(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $receivable_model = $this->model->get('receivableModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $receivable_model->getAllCosts();
            }
            else{
                $data_sale = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_sale = $receivable_model->getAllCosts($data_sale);
            }

            
            foreach ($list_sale as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                if ($rs->money-$rs->pay_money > 0) {
                    echo '<li onclick="set_item_receivable(\''.$rs->code.'\',\''.$rs->receivable_id.'\')">'.$code." (".$rs->comment.") - ".$this->lib->formatMoney($rs->money-$rs->pay_money).'</li>';
                }
                // add new option
                
            }
            
        }
    }
    public function getpayable(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payable_model = $this->model->get('payableModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $receivable_model->getAllCosts();
            }
            else{
                $data_sale = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_sale = $payable_model->getAllCosts($data_sale);
            }

            
            foreach ($list_sale as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                if ($rs->money-$rs->pay_money > 0) {
                    echo '<li onclick="set_item_payable(\''.$rs->code.'\',\''.$rs->payable_id.'\')">'.$code." (".$rs->comment.") - ".$this->lib->formatMoney($rs->money-$rs->pay_money).'</li>';
                }
                // add new option
                
            }
            
        }
    }
   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $daily_model = $this->model->get('dailyModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $receivable_model = $this->model->get('receivableModel');
            $payable_model = $this->model->get('payableModel');
            $assets_model = $this->model->get('assetsModel');
            $receive_model = $this->model->get('receiveModel');
            $pay_model = $this->model->get('payModel');
            $bank_model = $this->model->get('bankModel');
            $owe_model = $this->model->get('oweModel');
            $obtain_model = $this->model->get('obtainModel');
            $costs_model = $this->model->get('costsModel');
            $daily_bank_model = $this->model->get('dailybankModel');

            $data = array(
                        
                        'service' => trim($_POST['service']),
                        'owner' => trim($_POST['owner']),
                        'note' => trim($_POST['note']),
                        'account' => trim($_POST['account']),
                        'daily_date' => strtotime(trim($_POST['daily_date'])),
                        'comment' => trim($_POST['comment']),
                        'debit' => trim($_POST['debit']),
                        'credit' => trim($_POST['credit']),
                        'code' => trim($_POST['code']),
                        'money_in' => trim(str_replace(',','',$_POST['money_in'])),
                        'money_out' => trim(str_replace(',','',$_POST['money_out'])),
                        'receivable' => trim($_POST['receivable']),
                        'payable' => trim($_POST['payable']),
                        );
            
            $data['clearing'] = null;
            $clearing = "";
            if(trim($_POST['clearing']) != ""){
                $support = explode(',', trim($_POST['clearing']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = substr($key, strpos($key, "~") + 1); 
                        if ($clearing == "")
                            $clearing .= $name;
                        else
                            $clearing .= ','.$name;
                    }
                }

                $data['clearing'] = $clearing;
            }

            if ($_POST['yes'] != "") {

                if ($data['debit'] > 0 || $data['credit'] > 0) {
                    $add = $additional_model->getAdditionalByWhere(array('daily'=>trim($_POST['yes'])));
                    $data_add = array(
                            'additional_date' => $data['daily_date'],
                            'document_date' => $data['daily_date'],
                            'additional_comment' => $data['comment'],
                            'debit' => $data['debit'],
                            'credit' => $data['credit'],
                            'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? $data['money_out']:null),
                            'code' => $data['code'],
                            'daily' => trim($_POST['yes']),
                        );

                    $data_debit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['debit'],
                        'money' => $data_add['money'],
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['credit'],
                        'money' => (0-$data_add['money']),
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );

                    if($data_debit['week'] == 53){
                        $data_debit['week'] = 1;
                        $data_debit['year'] = $data_debit['year']+1;

                        $data_credit['week'] = 1;
                        $data_credit['year'] = $data_credit['year']+1;
                    }
                    if (((int)date('W', $data_add['additional_date']) == 1) && ((int)date('m', $data_add['additional_date']) == 12) ) {
                        $data_debit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                        $data_credit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                    }

                    if ($add) {
                        $additional_model->updateAdditional($data_add,array('additional_id'=>$add->additional_id));

                        $account_balance_model->updateAccount($data_debit,array('additional'=>$add->additional_id,'account'=>$add->debit));
                        $account_balance_model->updateAccount($data_credit,array('additional'=>$add->additional_id,'account'=>$add->credit));

            
                    }
                    else{
                        $additional_model->createAdditional($data_add);

                        $id_additional = $additional_model->getLastAdditional()->additional_id;
                        $data_debit['additional'] = $id_additional;
                        $data_credit['additional'] = $id_additional;

                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);

                    }
                }
                
                    $daily = $daily_model->getDaily(trim($_POST['yes']));

                    $daily_model->updateDaily($data,array('daily_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;

                    $data_daily_bank = array(
                        'daily_bank_date' => $data['daily_date'],
                        'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? 0-$data['money_out']:null),
                        'bank' => $bank,
                    );
                    $daily_bank_model->updateDaily($data_daily_bank,array('daily' => trim($_POST['yes'])));

                    
                    $week = (int)date('W', $data['daily_date']);
                    $year = (int)date('Y', $data['daily_date']);

                    if($week == 53){
                        $week = 1;
                        $year = $year+1;
                    }
                    if (((int)date('W', $data['daily_date']) == 1) && ((int)date('m', $data['daily_date']) == 12 )) {
                        $year = (int)date('Y', $data['daily_date'])+1;
                    }

                    $id_daily_last = $_POST['yes'];

                    if ($daily->clearing != "") {
                        
                        $cl = explode(',', $daily->clearing);
                        if ($daily->money_in > 0) {
                            foreach ($cl as $key) {
                                $receive = $receive_model->getCostsByWhere(array('additional'=>$id_daily_last,'receivable'=>$key));
                                $receivable = $receivable_model->getCosts($key);
                                $data_receivable = array(
                                    'pay_money' => $receivable->pay_money - $receive->money,
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$key));
                            }
                        }
                        if ($daily->money_out > 0) {
                            foreach ($cl as $key) {
                                $pay = $pay_model->getCostsByWhere(array('additional'=>$id_daily_last,'payable'=>$key));
                                $payable = $payable_model->getCosts($key);
                                $data_payable = array(
                                    'pay_money' => $payable->pay_money - $pay->money,
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$key));
                            }
                        }
                        
                        $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$id_daily_last);
                        $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$id_daily_last);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$id_daily_last);
                        $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$id_daily_last);
                        $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$id_daily_last);
                    }


                    if ($daily->service == 1 && $data['service'] == 1) {
                        if ($data['money_out'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'money' => $data['money_out'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => $data['money_out'],
                                'pay_date' => $data['daily_date'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                );

                            $costs_model->updateCosts($data_costs,array('additional' => $_POST['yes']));
                            $cost = $costs_model->getCostsByWhere(array('additional' => $_POST['yes']));

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => 0 - $data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                    );
                            $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id));


                            $data_pay = array(
                                        'source' => $bank,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                    );
                            $pay_model->updateCosts($data_pay,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id));
                        }
                        if ($data['money_in'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source_in' => $bank,
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => 0,
                                'money' => 0,
                                'pay_date' => $data['daily_date'],
                                'money_in' => $data['money_in'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                );
                            $costs_model->updateCosts($data_costs,array('additional' => $_POST['yes']));
                            $cost = $costs_model->getCostsByWhere(array('additional' => $_POST['yes']));

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => $data['money_in'],
                                        'assets_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                    );
                            $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id));
                        }
                    }
                    else if ($daily->service > 1 && $data['service'] == 1) {
                        if ($data['money_out'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'money' => $data['money_out'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => $data['money_out'],
                                'pay_date' => $data['daily_date'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'additional' => $_POST['yes'],
                                );

                            $costs_model->createCosts($data_costs);

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => 0 - $data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $_POST['yes'],
                                    );
                            $assets_model->createAssets($data_asset);


                            $data_pay = array(
                                        'source' => $bank,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $_POST['yes'],
                                    );
                            $pay_model->createCosts($data_pay);
                        }
                        if ($data['money_in'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source_in' => $bank,
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => 0,
                                'money' => 0,
                                'pay_date' => $data['daily_date'],
                                'money_in' => $data['money_in'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'additional' => $_POST['yes'],
                                );
                            $costs_model->createCosts($data_costs);

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => $data['money_in'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $_POST['yes'],
                                    );
                            $assets_model->createAssets($data_asset);
                        }
                    }
                    else if ($daily->service == 1 && $data['service'] > 1) {
                        $cost = $costs_model->getCostsByWhere(array('additional' => $_POST['yes']));
                        $costs_model->queryCosts('DELETE FROM costs WHERE costs_id = '.$cost->costs_id);
                        $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$costs_id);
                        $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$costs_id);
                    }


                    if ($daily->receivable == "" || $daily->receivable == 0) {
                        if ($data['receivable'] > 0) {
                            $receivable = $receivable_model->getCosts($data['receivable']);
                            $data_receivable = array(
                                'pay_date' => $data['daily_date'],
                                'pay_money' => $receivable->pay_money + $data['money_in'],
                            );
                            $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => $data['money_in'],
                                        'assets_date' => $data['daily_date'],
                                        'receivable' => $data['receivable'],
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $_POST['yes'],
                                    );
                            $assets_model->createAssets($data_asset);

                            
                            $data_receive = array(
                                        'source' => $bank,
                                        'money' => $data['money_in'],
                                        'receive_date' => $data['daily_date'],
                                        'receivable' => $data['receivable'],
                                        'week' => $week,
                                        'year' => $year,
                                        'receive_comment' => $data['comment'],
                                        'additional' => $_POST['yes'],
                                    );
                            
                            $receive_model->createCosts($data_receive);

                            $data_obtain = array(
                                'customer' => $receivable->customer,
                                'money' => 0 - $data['money_in'],
                                'obtain_date' => $data['daily_date'],
                                'week' => $week,
                                'year' => $year,
                                'sale_report' => $receivable->sale_report,
                                'trading' => $receivable->trading,
                                'agent' => $receivable->agent,
                                'agent_manifest' => $receivable->agent_manifest,
                                'invoice' => $receivable->invoice,
                                'import_tire' => $receivable->import_tire,
                                'order_tire' => $receivable->order_tire,
                                'additional' => $_POST['yes'],
                            );
                            $obtain_model->createObtain($data_obtain);
                        }
                    }
                    elseif ($daily->receivable > 0) {
                        if ($data['receivable'] == "" || $data['receivable'] == 0) {
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND receivable = '.$daily->receivable);
                            $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$_POST['yes']);
                            $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$_POST['yes']);
                        }
                        else{
                            if ($daily->receivable == $data['receivable']) {
                                $receivable = $receivable_model->getCosts($data['receivable']);
                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money - $daily->money_in + $data['money_in'],
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data['money_in'] - $daily->money_in,
                                            'assets_date' => $data['daily_date'],
                                            'receivable' => $data['receivable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $_POST['yes'],
                                        );
                                $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'receivable'=>$daily->receivable));

                                
                                $data_receive = array(
                                            'source' => $bank,
                                            'money' => $data['money_in'] - $daily->money_in,
                                            'receive_date' => $data['daily_date'],
                                            'receivable' => $data['receivable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'receive_comment' => $data['comment'],
                                            'additional' => $_POST['yes'],
                                        );
                                
                                $receive_model->updateCosts($data_receive,array('additional' => $_POST['yes']));

                                $data_obtain = array(
                                    'customer' => $receivable->customer,
                                    'money' => 0 - $data['money_in'] + $daily->money_in,
                                    'obtain_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $receivable->sale_report,
                                    'trading' => $receivable->trading,
                                    'agent' => $receivable->agent,
                                    'agent_manifest' => $receivable->agent_manifest,
                                    'invoice' => $receivable->invoice,
                                    'import_tire' => $receivable->import_tire,
                                    'order_tire' => $receivable->order_tire,
                                    'additional' => $_POST['yes'],
                                );
                                $obtain_model->updateObtain($data_obtain,array('additional' => $_POST['yes']));
                            }
                            else{
                                $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND receivable = '.$daily->receivable);
                                $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$_POST['yes']);
                                $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$_POST['yes']);

                                $receivable = $receivable_model->getCosts($data['receivable']);
                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money - $daily->money_in + $data['money_in'],
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data['money_in'],
                                            'assets_date' => $data['daily_date'],
                                            'receivable' => $data['receivable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $_POST['yes'],
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_receive = array(
                                            'source' => $bank,
                                            'money' => $data['money_in'],
                                            'receive_date' => $data['daily_date'],
                                            'receivable' => $data['receivable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'receive_comment' => $data['comment'],
                                            'additional' => $_POST['yes'],
                                        );
                                
                                $receive_model->createCosts($data_receive);

                                $data_obtain = array(
                                    'customer' => $receivable->customer,
                                    'money' => 0 - $data['money_in'],
                                    'obtain_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $receivable->sale_report,
                                    'trading' => $receivable->trading,
                                    'agent' => $receivable->agent,
                                    'agent_manifest' => $receivable->agent_manifest,
                                    'invoice' => $receivable->invoice,
                                    'import_tire' => $receivable->import_tire,
                                    'order_tire' => $receivable->order_tire,
                                    'additional' => $_POST['yes'],
                                );
                                $obtain_model->createObtain($data_obtain);
                            }
                            
                        }
                    }

                    if ($daily->payable == "" || $daily->payable == 0) {
                        if ($data['payable'] > 0) {
                            $payable = $payable_model->getCosts($data['payable']);
                            $data_payable = array(
                                'pay_date' => $data['daily_date'],
                                'pay_money' => $payable->pay_money + $data['money_out'],
                            );
                            $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));


                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => 0 - $data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'payable' => $data['payable'],
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $_POST['yes'],
                                    );
                            $assets_model->createAssets($data_asset);

                            
                            $data_pay = array(
                                        'source' => $bank,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'payable' => $data['payable'],
                                        'week' => $week,
                                        'year' => $year,
                                        'pay_comment' => $data['comment'],
                                        'additional' => $_POST['yes'],
                                    );

                            $pay_model->createCosts($data_pay);

                            $data_owe = array(
                                'vendor' => $payable->vendor,
                                'money' => 0 - $data['money_out'],
                                'owe_date' => $data['daily_date'],
                                'week' => $week,
                                'year' => $year,
                                'sale_report' => $payable->sale_report,
                                'trading' => $payable->trading,
                                'agent' => $payable->agent,
                                'agent_manifest' => $payable->agent_manifest,
                                'invoice' => $payable->invoice,
                                'import_tire' => $payable->import_tire,
                                'order_tire' => $payable->order_tire,
                                'additional' => $_POST['yes'],
                            );
                            $owe_model->createOwe($data_owe);
                        }
                    }
                    elseif ($daily->payable > 0) {
                        if ($data['payable'] == "" || $data['payable'] == 0) {
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                            $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                            $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$_POST['yes']);
                        }
                        else{
                            if ($daily->payable == $data['payable']) {
                                $payable = $payable_model->getCosts($data['payable']);
                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money - $daily->money_out + $data['money_out'],
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));


                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data['money_out'] + $daily->money_out,
                                            'assets_date' => $data['daily_date'],
                                            'payable' => $data['payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $_POST['yes'],
                                        );
                                $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'payable'=>$daily->payable));

                                
                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data['money_out'] - $daily->money_out,
                                            'pay_date' => $data['daily_date'],
                                            'payable' => $data['payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'pay_comment' => $data['comment'],
                                            'additional' => $_POST['yes'],
                                        );

                                $pay_model->updateCosts($data_pay,array('additional' => $_POST['yes'],'payable'=>$daily->payable));

                                $data_owe = array(
                                    'vendor' => $payable->vendor,
                                    'money' => 0 - $data['money_out'] + $daily->money_out,
                                    'owe_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $payable->sale_report,
                                    'trading' => $payable->trading,
                                    'agent' => $payable->agent,
                                    'agent_manifest' => $payable->agent_manifest,
                                    'invoice' => $payable->invoice,
                                    'import_tire' => $payable->import_tire,
                                    'order_tire' => $payable->order_tire,
                                    'additional' => $_POST['yes'],
                                );
                                $owe_model->updateOwe($data_owe,array('additional' => $_POST['yes']));
                            }
                            else{
                                $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                                $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                                $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$_POST['yes']);

                                $payable = $payable_model->getCosts($data['payable']);
                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money - $daily->money_out + $data['money_out'],
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));


                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data['money_out'],
                                            'assets_date' => $data['daily_date'],
                                            'payable' => $data['payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $_POST['yes'],
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data['money_out'],
                                            'pay_date' => $data['daily_date'],
                                            'payable' => $data['payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'pay_comment' => $data['comment'],
                                            'additional' => $_POST['yes'],
                                        );

                                $pay_model->createCosts($data_pay);

                                $data_owe = array(
                                    'vendor' => $payable->vendor,
                                    'money' => 0 - $data['money_out'],
                                    'owe_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $payable->sale_report,
                                    'trading' => $payable->trading,
                                    'agent' => $payable->agent,
                                    'agent_manifest' => $payable->agent_manifest,
                                    'invoice' => $payable->invoice,
                                    'import_tire' => $payable->import_tire,
                                    'order_tire' => $payable->order_tire,
                                    'additional' => $_POST['yes'],
                                );
                                $owe_model->createOwe($data_owe);
                            }
                            
                        }
                    }

                    
                    $id_daily_last = $_POST['yes'];
                        
                    $support = explode(',', $clearing);
                    if ($clearing != null) {
                        if ($data['money_in'] > 0) {
                            if ($data['receivable'] > 0) {
                                $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$id_daily_last.' AND receivable = '.$data['receivable']);
                                $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$id_daily_last);
                                $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$id_daily_last);

                                $receivable = $receivable_model->getCosts($data['receivable']);
                                $data_receivable = array(
                                    'pay_money' => $receivable->pay_money - $data['money_in'],
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                                $daily_model->updateDaily(array('receivable'=>null),array('daily_id'=>$id_daily_last));
                            }
                            
                            
                            $conlai = $data['money_in'];
                            foreach ($support as $key) {
                                $receivable = $receivable_model->getCosts($key);
                                $money = $receivable->money-$receivable->pay_money;
                                if ($conlai >= $money) {
                                    $conlai -= $money;

                                    $data_receivable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $receivable->pay_money + $money,
                                    );
                                    $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$key));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => $money,
                                                'assets_date' => $data['daily_date'],
                                                'receivable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    
                                    $data_receive = array(
                                                'source' => $bank,
                                                'money' => $money,
                                                'receive_date' => $data['daily_date'],
                                                'receivable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'receive_comment' => $data['comment'],
                                                'additional' => $id_daily_last,
                                            );
                                    
                                    $receive_model->createCosts($data_receive);

                                    $data_obtain = array(
                                        'customer' => $receivable->customer,
                                        'money' => 0 - $money,
                                        'obtain_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                        'sale_report' => $receivable->sale_report,
                                        'trading' => $receivable->trading,
                                        'agent' => $receivable->agent,
                                        'agent_manifest' => $receivable->agent_manifest,
                                        'invoice' => $receivable->invoice,
                                        'import_tire' => $receivable->import_tire,
                                        'order_tire' => $receivable->order_tire,
                                        'additional' => $id_daily_last,
                                    );
                                    $obtain_model->createObtain($data_obtain);
                                }
                                else if ($conlai > 0 && $conlai < $money) {
                                    $data_receivable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $receivable->pay_money + $conlai,
                                    );
                                    $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$key));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => $conlai,
                                                'assets_date' => $data['daily_date'],
                                                'receivable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    
                                    $data_receive = array(
                                                'source' => $bank,
                                                'money' => $conlai,
                                                'receive_date' => $data['daily_date'],
                                                'receivable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'receive_comment' => $data['comment'],
                                                'additional' => $id_daily_last,
                                            );
                                    
                                    $receive_model->createCosts($data_receive);

                                    $data_obtain = array(
                                        'customer' => $receivable->customer,
                                        'money' => 0 - $conlai,
                                        'obtain_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                        'sale_report' => $receivable->sale_report,
                                        'trading' => $receivable->trading,
                                        'agent' => $receivable->agent,
                                        'agent_manifest' => $receivable->agent_manifest,
                                        'invoice' => $receivable->invoice,
                                        'import_tire' => $receivable->import_tire,
                                        'order_tire' => $receivable->order_tire,
                                        'additional' => $id_daily_last,
                                    );
                                    $obtain_model->createObtain($data_obtain);

                                    $conlai = 0;
                                }
                            }

                            if ($data['service'] == 1) {
                                $cost = $costs_model->getCostsByWhere(array('additional' => $id_daily_last));
                                $costs_model->updateCosts(array('money_in'=>$conlai),array('costs_id'=>$cost->costs_id));
                                $assets_model->updateAssets(array('total'=>$conlai),array('costs'=>$cost->costs_id));
                            }
                        }
                        if ($data['money_out'] > 0) {
                            if ($data['payable'] > 0) {
                                $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$id_daily_last.' AND payable = '.$data['payable']);
                                $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$id_daily_last.' AND payable = '.$data['payable']);
                                $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$id_daily_last);

                                $payable = $payable_model->getCosts($data['payable']);
                                $data_payable = array(
                                    'pay_money' => $payable->pay_money - $data['money_out'],
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));

                                $daily_model->updateDaily(array('payable'=>null),array('daily_id'=>$id_daily_last));
                            }
                            

                            $conlai = $data['money_out'];
                            foreach ($support as $key) {
                                $payable = $payable_model->getCosts($key);
                                $money = $payable->money-$payable->pay_money;
                                if ($conlai >= $money) {
                                    $conlai -= $money;

                                    $data_payable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $payable->pay_money + $money,
                                    );
                                    $payable_model->updateCosts($data_payable,array('payable_id'=>$key));


                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => 0 - $money,
                                                'assets_date' => $data['daily_date'],
                                                'payable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    
                                    $data_pay = array(
                                                'source' => $bank,
                                                'money' => $money,
                                                'pay_date' => $data['daily_date'],
                                                'payable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'pay_comment' => $data['comment'],
                                                'additional' => $id_daily_last,
                                            );

                                    $pay_model->createCosts($data_pay);

                                    $data_owe = array(
                                        'vendor' => $payable->vendor,
                                        'money' => 0 - $money,
                                        'owe_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                        'sale_report' => $payable->sale_report,
                                        'trading' => $payable->trading,
                                        'agent' => $payable->agent,
                                        'agent_manifest' => $payable->agent_manifest,
                                        'invoice' => $payable->invoice,
                                        'import_tire' => $payable->import_tire,
                                        'order_tire' => $payable->order_tire,
                                        'additional' => $id_daily_last,
                                    );
                                    $owe_model->createOwe($data_owe);
                                }
                                else if ($conlai > 0 && $conlai < $money) {
                                    $data_payable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $payable->pay_money + $conlai,
                                    );
                                    $payable_model->updateCosts($data_payable,array('payable_id'=>$key));


                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => 0 - $conlai,
                                                'assets_date' => $data['daily_date'],
                                                'payable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    
                                    $data_pay = array(
                                                'source' => $bank,
                                                'money' => $conlai,
                                                'pay_date' => $data['daily_date'],
                                                'payable' => $key,
                                                'week' => $week,
                                                'year' => $year,
                                                'pay_comment' => $data['comment'],
                                                'additional' => $id_daily_last,
                                            );

                                    $pay_model->createCosts($data_pay);

                                    $data_owe = array(
                                        'vendor' => $payable->vendor,
                                        'money' => 0 - $conlai,
                                        'owe_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                        'sale_report' => $payable->sale_report,
                                        'trading' => $payable->trading,
                                        'agent' => $payable->agent,
                                        'agent_manifest' => $payable->agent_manifest,
                                        'invoice' => $payable->invoice,
                                        'import_tire' => $payable->import_tire,
                                        'order_tire' => $payable->order_tire,
                                        'additional' => $id_daily_last,
                                    );
                                    $owe_model->createOwe($data_owe);

                                    $conlai = 0;
                                }
                            }

                            if ($data['service'] == 1) {
                                $cost = $costs_model->getCostsByWhere(array('additional' => $id_daily_last));
                                $costs_model->updateCosts(array('money'=>$conlai,'pay_money'=>$conlai),array('costs_id'=>$cost->costs_id));
                                $assets_model->updateAssets(array('total'=>(0-$conlai)),array('costs'=>$cost->costs_id));
                                $pay_model->updateAssets(array('money'=>$conlai),array('costs'=>$cost->costs_id));
                            }
                        }
                    }

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|daily|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    $daily_model->createDaily($data);
                    echo "Thêm thành công";

                    $id_daily_last = $daily_model->getLastDaily()->daily_id;

                    $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;

                    $data_daily_bank = array(
                        'daily_bank_date' => $data['daily_date'],
                        'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? 0-$data['money_out']:null),
                        'bank' => $bank,
                        'daily' => $id_daily_last,
                    );
                    $daily_bank_model->createDaily($data_daily_bank);

                    $week = (int)date('W', $data['daily_date']);
                    $year = (int)date('Y', $data['daily_date']);

                    if($week == 53){
                        $week = 1;
                        $year = $year+1;
                    }
                    if (((int)date('W', $data['daily_date']) == 1) && ((int)date('m', $data['daily_date']) == 12 )) {
                        $year = (int)date('Y', $data['daily_date'])+1;
                    }

                    if ($data['service'] == 1) {
                        if ($data['money_out'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'money' => $data['money_out'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => $data['money_out'],
                                'pay_date' => $data['daily_date'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'additional' => $id_daily_last,
                                );

                            $costs_model->createCosts($data_costs);

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => 0 - $data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $assets_model->createAssets($data_asset);


                            $data_pay = array(
                                        'source' => $bank,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $pay_model->createCosts($data_pay);
                        }
                        if ($data['money_in'] > 0) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source_in' => $bank,
                                'source' => $bank,
                                'year' => $year,
                                'pay_money' => 0,
                                'money' => 0,
                                'pay_date' => $data['daily_date'],
                                'money_in' => $data['money_in'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'additional' => $id_daily_last,
                                );
                            $costs_model->createCosts($data_costs);

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => $data['money_in'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $costs_model->getLastCosts()->costs_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $assets_model->createAssets($data_asset);
                        }
                    }
                    
                    

                    if ($data['receivable'] > 0) {
                        $receivable = $receivable_model->getCosts($data['receivable']);
                        $data_receivable = array(
                            'pay_date' => $data['daily_date'],
                            'pay_money' => $receivable->pay_money + $data['money_in'],
                        );
                        $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                        $data_asset = array(
                                    'bank' => $bank,
                                    'total' => $data['money_in'],
                                    'assets_date' => $data['daily_date'],
                                    'receivable' => $data['receivable'],
                                    'week' => $week,
                                    'year' => $year,
                                    'additional' => $id_daily_last,
                                );
                        $assets_model->createAssets($data_asset);

                        
                        $data_receive = array(
                                    'source' => $bank,
                                    'money' => $data['money_in'],
                                    'receive_date' => $data['daily_date'],
                                    'receivable' => $data['receivable'],
                                    'week' => $week,
                                    'year' => $year,
                                    'receive_comment' => $data['comment'],
                                    'additional' => $id_daily_last,
                                );
                        
                        $receive_model->createCosts($data_receive);

                        $data_obtain = array(
                            'customer' => $receivable->customer,
                            'money' => 0 - $data['money_in'],
                            'obtain_date' => $data['daily_date'],
                            'week' => $week,
                            'year' => $year,
                            'sale_report' => $receivable->sale_report,
                            'trading' => $receivable->trading,
                            'agent' => $receivable->agent,
                            'agent_manifest' => $receivable->agent_manifest,
                            'invoice' => $receivable->invoice,
                            'import_tire' => $receivable->import_tire,
                            'order_tire' => $receivable->order_tire,
                            'additional' => $id_daily_last,
                        );
                        $obtain_model->createObtain($data_obtain);
                    }
                    if ($data['payable'] > 0) {
                        $payable = $payable_model->getCosts($data['payable']);
                        $data_payable = array(
                            'pay_date' => $data['daily_date'],
                            'pay_money' => $payable->pay_money + $data['money_out'],
                        );
                        $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));


                        $data_asset = array(
                                    'bank' => $bank,
                                    'total' => 0 - $data['money_out'],
                                    'assets_date' => $data['daily_date'],
                                    'payable' => $data['payable'],
                                    'week' => $week,
                                    'year' => $year,
                                    'additional' => $id_daily_last,
                                );
                        $assets_model->createAssets($data_asset);

                        
                        $data_pay = array(
                                    'source' => $bank,
                                    'money' => $data['money_out'],
                                    'pay_date' => $data['daily_date'],
                                    'payable' => $data['payable'],
                                    'week' => $week,
                                    'year' => $year,
                                    'pay_comment' => $data['comment'],
                                    'additional' => $id_daily_last,
                                );

                        $pay_model->createCosts($data_pay);

                        $data_owe = array(
                            'vendor' => $payable->vendor,
                            'money' => 0 - $data['money_out'],
                            'owe_date' => $data['daily_date'],
                            'week' => $week,
                            'year' => $year,
                            'sale_report' => $payable->sale_report,
                            'trading' => $payable->trading,
                            'agent' => $payable->agent,
                            'agent_manifest' => $payable->agent_manifest,
                            'invoice' => $payable->invoice,
                            'import_tire' => $payable->import_tire,
                            'order_tire' => $payable->order_tire,
                            'additional' => $id_daily_last,
                        );
                        $owe_model->createOwe($data_owe);
                    }

                if ($data['debit'] > 0 || $data['credit'] > 0) {
                    $data_add = array(
                            'additional_date' => $data['daily_date'],
                            'additional_comment' => $data['comment'],
                            'debit' => $data['debit'],
                            'credit' => $data['credit'],
                            'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? $data['money_out']:null),
                            'code' => $data['code'],
                            'daily' => $id_daily_last,
                        );
                    
                    $additional_model->createAdditional($data_add);

                    $data_debit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['debit'],
                        'money' => $data_add['money'],
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['credit'],
                        'money' => (0-$data_add['money']),
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );

                    if($data_debit['week'] == 53){
                        $data_debit['week'] = 1;
                        $data_debit['year'] = $data_debit['year']+1;

                        $data_credit['week'] = 1;
                        $data_credit['year'] = $data_credit['year']+1;
                    }
                    if (((int)date('W', $data_add['additional_date']) == 1) && ((int)date('m', $data_add['additional_date']) == 12) ) {
                        $data_debit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                        $data_credit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                    }

                    $id_additional = $additional_model->getLastAdditional()->additional_id;
                    $data_debit['additional'] = $id_additional;
                    $data_credit['additional'] = $id_additional;

                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);

                    
                    
                }


                $support = explode(',', $clearing);
                if ($clearing != null) {
                    if ($data['money_in'] > 0) {
                        if ($data['receivable'] > 0) {
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$id_daily_last.' AND receivable = '.$data['receivable']);
                            $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$id_daily_last);
                            $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$id_daily_last);

                            $receivable = $receivable_model->getCosts($data['receivable']);
                            $data_receivable = array(
                                'pay_money' => $receivable->pay_money - $data['money_in'],
                            );
                            $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data['receivable']));

                            $daily_model->updateDaily(array('receivable'=>null),array('daily_id'=>$id_daily_last));
                        }
                        
                        
                        $conlai = $data['money_in'];
                        foreach ($support as $key) {
                            $receivable = $receivable_model->getCosts($key);
                            $money = $receivable->money-$receivable->pay_money;
                            if ($conlai >= $money) {
                                $conlai -= $money;

                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money + $money,
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$key));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $money,
                                            'assets_date' => $data['daily_date'],
                                            'receivable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_receive = array(
                                            'source' => $bank,
                                            'money' => $money,
                                            'receive_date' => $data['daily_date'],
                                            'receivable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'receive_comment' => $data['comment'],
                                            'additional' => $id_daily_last,
                                        );
                                
                                $receive_model->createCosts($data_receive);

                                $data_obtain = array(
                                    'customer' => $receivable->customer,
                                    'money' => 0 - $money,
                                    'obtain_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $receivable->sale_report,
                                    'trading' => $receivable->trading,
                                    'agent' => $receivable->agent,
                                    'agent_manifest' => $receivable->agent_manifest,
                                    'invoice' => $receivable->invoice,
                                    'import_tire' => $receivable->import_tire,
                                    'order_tire' => $receivable->order_tire,
                                    'additional' => $id_daily_last,
                                );
                                $obtain_model->createObtain($data_obtain);
                            }
                            else if ($conlai > 0 && $conlai < $money) {
                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money + $conlai,
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$key));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $conlai,
                                            'assets_date' => $data['daily_date'],
                                            'receivable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_receive = array(
                                            'source' => $bank,
                                            'money' => $conlai,
                                            'receive_date' => $data['daily_date'],
                                            'receivable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'receive_comment' => $data['comment'],
                                            'additional' => $id_daily_last,
                                        );
                                
                                $receive_model->createCosts($data_receive);

                                $data_obtain = array(
                                    'customer' => $receivable->customer,
                                    'money' => 0 - $conlai,
                                    'obtain_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $receivable->sale_report,
                                    'trading' => $receivable->trading,
                                    'agent' => $receivable->agent,
                                    'agent_manifest' => $receivable->agent_manifest,
                                    'invoice' => $receivable->invoice,
                                    'import_tire' => $receivable->import_tire,
                                    'order_tire' => $receivable->order_tire,
                                    'additional' => $id_daily_last,
                                );
                                $obtain_model->createObtain($data_obtain);

                                $conlai = 0;
                            }
                        }

                        if ($data['service'] == 1) {
                            $cost = $costs_model->getCostsByWhere(array('additional' => $id_daily_last));
                            $costs_model->updateCosts(array('money_in'=>$conlai),array('costs_id'=>$cost->costs_id));
                            $assets_model->updateAssets(array('total'=>$conlai),array('costs'=>$cost->costs_id));
                        }
                    }
                    if ($data['money_out'] > 0) {
                        if ($data['payable'] > 0) {
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$id_daily_last.' AND payable = '.$data['payable']);
                            $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$id_daily_last.' AND payable = '.$data['payable']);
                            $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$id_daily_last);

                            $payable = $payable_model->getCosts($data['payable']);
                            $data_payable = array(
                                'pay_money' => $payable->pay_money - $data['money_out'],
                            );
                            $payable_model->updateCosts($data_payable,array('payable_id'=>$data['payable']));

                            $daily_model->updateDaily(array('payable'=>null),array('daily_id'=>$id_daily_last));
                        }
                        

                        $conlai = $data['money_out'];
                        foreach ($support as $key) {
                            $payable = $payable_model->getCosts($key);
                            $money = $payable->money-$payable->pay_money;
                            if ($conlai >= $money) {
                                $conlai -= $money;

                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money + $money,
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$key));


                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $money,
                                            'assets_date' => $data['daily_date'],
                                            'payable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $money,
                                            'pay_date' => $data['daily_date'],
                                            'payable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'pay_comment' => $data['comment'],
                                            'additional' => $id_daily_last,
                                        );

                                $pay_model->createCosts($data_pay);

                                $data_owe = array(
                                    'vendor' => $payable->vendor,
                                    'money' => 0 - $money,
                                    'owe_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $payable->sale_report,
                                    'trading' => $payable->trading,
                                    'agent' => $payable->agent,
                                    'agent_manifest' => $payable->agent_manifest,
                                    'invoice' => $payable->invoice,
                                    'import_tire' => $payable->import_tire,
                                    'order_tire' => $payable->order_tire,
                                    'additional' => $id_daily_last,
                                );
                                $owe_model->createOwe($data_owe);
                            }
                            else if ($conlai > 0 && $conlai < $money) {
                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money + $conlai,
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$key));


                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $conlai,
                                            'assets_date' => $data['daily_date'],
                                            'payable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $conlai,
                                            'pay_date' => $data['daily_date'],
                                            'payable' => $key,
                                            'week' => $week,
                                            'year' => $year,
                                            'pay_comment' => $data['comment'],
                                            'additional' => $id_daily_last,
                                        );

                                $pay_model->createCosts($data_pay);

                                $data_owe = array(
                                    'vendor' => $payable->vendor,
                                    'money' => 0 - $conlai,
                                    'owe_date' => $data['daily_date'],
                                    'week' => $week,
                                    'year' => $year,
                                    'sale_report' => $payable->sale_report,
                                    'trading' => $payable->trading,
                                    'agent' => $payable->agent,
                                    'agent_manifest' => $payable->agent_manifest,
                                    'invoice' => $payable->invoice,
                                    'import_tire' => $payable->import_tire,
                                    'order_tire' => $payable->order_tire,
                                    'additional' => $id_daily_last,
                                );
                                $owe_model->createOwe($data_owe);

                                $conlai = 0;
                            }
                        }

                        if ($data['service'] == 1) {
                            $cost = $costs_model->getCostsByWhere(array('additional' => $id_daily_last));
                            $costs_model->updateCosts(array('money'=>$conlai,'pay_money'=>$conlai),array('costs_id'=>$cost->costs_id));
                            $assets_model->updateAssets(array('total'=>(0-$conlai)),array('costs'=>$cost->costs_id));
                            $pay_model->updateAssets(array('money'=>$conlai),array('costs'=>$cost->costs_id));
                        }
                    }
                }

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$daily_model->getLastDaily()->daily_id."|daily|".implode("-",$data)."\n"."\r\n";
                    
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $daily_model = $this->model->get('dailyModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $receivable_model = $this->model->get('receivableModel');
            $payable_model = $this->model->get('payableModel');
            $assets_model = $this->model->get('assetsModel');
            $receive_model = $this->model->get('receiveModel');
            $pay_model = $this->model->get('payModel');
            $owe_model = $this->model->get('oweModel');
            $obtain_model = $this->model->get('obtainModel');
            $daily_bank_model = $this->model->get('dailybankModel');
            $costs_model = $this->model->get('costsModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $daily_model->deleteDaily($data);
                       $daily_bank_model->queryDaily('DELETE FROM daily_bank WHERE daily = '.$data);

                       $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$data));
                       foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }

                       $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$data);
                        $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$data);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$data);
                        $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$data);
                        $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$data);
                        $costs_model->queryCosts('DELETE FROM costs WHERE additional = '.$data);
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|daily|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $daily_model->deleteDaily($_POST['data']);
                        $daily_bank_model->queryDaily('DELETE FROM daily_bank WHERE daily = '.$_POST['data']);

                        $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$_POST['data']));
                       foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }

                       $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['data']);
                        $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$_POST['data']);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$_POST['data']);
                        $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$_POST['data']);
                        $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$_POST['data']);
                        $costs_model->queryCosts('DELETE FROM costs WHERE additional = '.$_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|daily|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $daily = $this->model->get('dailyModel');
            $daily_bank_model = $this->model->get('dailybankModel');
            $bank_model = $this->model->get('bankModel');
            $costs_model = $this->model->get('costs2Model');
            $assets_model = $this->model->get('assets2Model');
            $pay_model = $this->model->get('pay2Model');

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

            $i = 0;
            while ($objPHPExcel->setActiveSheetIndex($i)){
                $objWorksheet = $objPHPExcel->getActiveSheet();

                $nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
                $day = explode(".", $nameWorksheet); 

                $ngay1 = $day[0];
                $ngay2 = $day[0];
                if (strpos($day[0], '-') > 0) {
                    $ngay1 = substr($day[0], 0, strpos($day[0], '-'));
                    $ngay2 = substr($day[0], strpos($day[0], "-") + 1);
                }

                $ngaythang = "-".(strlen($day[1]) < 2 ? "0".$day[1] : $day[1] )."-".$day[2] ;
                
                

                $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
                $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

                

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

                        $val[] = $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }


                    if ($val[2] != null && $val[5] != null && $val[8] != null ) {

                        if ($val[9] != null) {
                            $ngay = $ngay1.$ngaythang;
                        }
                        else{
                            $ngay = $ngay2.$ngaythang;
                        }

                        $ngay = strtotime($ngay);
                 
                        $service = trim($val[5]);
                        $service = ($service=="Hành chính" || $service=="hành chính")?1:(($service=="Lốp xe" || $service=="lốp xe")?2:(($service=="Logistics" || $service=="logistics")?3:null));

                        if (BASE_URL == "http://viet-trade.org" || BASE_URL == "http://www.viet-trade.org") {
                            if ($service < 3) {
                                $daily_data = array(
                                    'daily_date' => $ngay,
                                    'code'=> trim($val[1]),
                                    'comment' => trim($val[2]),
                                    'money_in' => trim($val[3]),
                                    'money_out' => trim($val[4]),
                                    'service' => $service,
                                    'owner' => trim($val[6]),
                                    'note' => trim($val[7]),
                                    'account' => trim($val[8]),
                                    );

                                $daily->createDaily($daily_data);

                                $bank = $bank_model->getBankByWhere(array('symbol'=>$daily_data['account']))->bank_id;

                                $data_daily_bank = array(
                                    'daily_bank_date' => $daily_data['daily_date'],
                                    'money' => $daily_data['money_in'] > 0 ? $daily_data['money_in'] : ($daily_data['money_out'] > 0 ? 0-$daily_data['money_out']:null),
                                    'bank' => $bank,
                                    'daily' => $daily->getLastDaily()->daily_id,
                                );
                                $daily_bank_model->createDaily($data_daily_bank);

                                if ($service == 1) {
                                    if ($daily_data['money_out'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'money' => $daily_data['money_out'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => $daily_data['money_out'],
                                            'pay_date' => $daily_data['daily_date'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => 0 - $daily_data['money_out'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $assets_model->createAssets($data_asset);


                                        $data_pay = array(
                                                    'source' => $bank,
                                                    'money' => $daily_data['money_out'],
                                                    'pay_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $pay_model->createCosts($data_pay);
                                    }
                                    if ($daily_data['money_in'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source_in' => $bank,
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => 0,
                                            'money' => 0,
                                            'pay_date' => $daily_data['daily_date'],
                                            'money_in' => $daily_data['money_in'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => $daily_data['money_in'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $assets_model->createAssets($data_asset);
                                    }
                                }

                            }
                            else{
                                $daily_data = array(
                                    'daily_date' => $ngay,
                                    'code'=> trim($val[1]),
                                    'comment' => trim($val[2]),
                                    'money_in' => trim($val[3]),
                                    'money_out' => trim($val[4]),
                                    'service' => $service,
                                    'owner' => trim($val[6]),
                                    'note' => trim($val[7]),
                                    'account' => trim($val[8]),
                                    );

                                $daily->createDaily3($daily_data);

                                $data_daily_bank = array(
                                    'daily_bank_date' => $daily_data['daily_date'],
                                    'money' => $daily_data['money_in'] > 0 ? $daily_data['money_in'] : ($daily_data['money_out'] > 0 ? 0-$daily_data['money_out']:null),
                                    'bank' => $bank_model->getBankByWhere(array('symbol'=>$daily_data['account']))->bank_id,
                                    'daily' => $daily->getLastDaily3()->daily_id,
                                );
                                $daily_bank_model->createDaily3($data_daily_bank);
                            }
                        }
                        else if (BASE_URL == "http://cmglogs.com" || BASE_URL == "http://www.cmglogs.com") {
                            if ($service < 3) {
                                $daily_data = array(
                                    'daily_date' => $ngay,
                                    'code'=> trim($val[1]),
                                    'comment' => trim($val[2]),
                                    'money_in' => trim($val[3]),
                                    'money_out' => trim($val[4]),
                                    'service' => $service,
                                    'owner' => trim($val[6]),
                                    'note' => trim($val[7]),
                                    'account' => trim($val[8]),
                                    );

                                $daily->createDaily3($daily_data);

                                $bank = $bank_model->getBankByWhere(array('symbol'=>$daily_data['account']))->bank_id;

                                $data_daily_bank = array(
                                    'daily_bank_date' => $daily_data['daily_date'],
                                    'money' => $daily_data['money_in'] > 0 ? $daily_data['money_in'] : ($daily_data['money_out'] > 0 ? 0-$daily_data['money_out']:null),
                                    'bank' => $bank,
                                    'daily' => $daily->getLastDaily3()->daily_id,
                                );
                                $daily_bank_model->createDaily3($data_daily_bank);

                                if ($service == 1) {
                                    if ($daily_data['money_out'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'money' => $daily_data['money_out'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => $daily_data['money_out'],
                                            'pay_date' => $daily_data['daily_date'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily3()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts3($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => 0 - $daily_data['money_out'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts3()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily3()->daily_id,
                                                );
                                        $assets_model->createAssets3($data_asset);


                                        $data_pay = array(
                                                    'source' => $bank,
                                                    'money' => $daily_data['money_out'],
                                                    'pay_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts3()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily3()->daily_id,
                                                );
                                        $pay_model->createCosts3($data_pay);
                                    }
                                    if ($daily_data['money_in'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source_in' => $bank,
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => 0,
                                            'money' => 0,
                                            'pay_date' => $daily_data['daily_date'],
                                            'money_in' => $daily_data['money_in'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily3()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts3($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => $daily_data['money_in'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts3()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily3()->daily_id,
                                                );
                                        $assets_model->createAssets3($data_asset);
                                    }
                                }
                            }
                            else{
                                $daily_data = array(
                                    'daily_date' => $ngay,
                                    'code'=> trim($val[1]),
                                    'comment' => trim($val[2]),
                                    'money_in' => trim($val[3]),
                                    'money_out' => trim($val[4]),
                                    'service' => $service,
                                    'owner' => trim($val[6]),
                                    'note' => trim($val[7]),
                                    'account' => trim($val[8]),
                                    );

                                $daily->createDaily($daily_data);

                                $data_daily_bank = array(
                                    'daily_bank_date' => $daily_data['daily_date'],
                                    'money' => $daily_data['money_in'] > 0 ? $daily_data['money_in'] : ($daily_data['money_out'] > 0 ? 0-$daily_data['money_out']:null),
                                    'bank' => $bank_model->getBankByWhere(array('symbol'=>$daily_data['account']))->bank_id,
                                    'daily' => $daily->getLastDaily()->daily_id,
                                );
                                $daily_bank_model->createDaily($data_daily_bank);
                            }
                        }
                        else{
                            $daily_data = array(
                                    'daily_date' => $ngay,
                                    'code'=> trim($val[1]),
                                    'comment' => trim($val[2]),
                                    'money_in' => trim($val[3]),
                                    'money_out' => trim($val[4]),
                                    'service' => $service,
                                    'owner' => trim($val[6]),
                                    'note' => trim($val[7]),
                                    'account' => trim($val[8]),
                                    );

                                $daily->createDaily($daily_data);

                                $bank = $bank_model->getBankByWhere(array('symbol'=>$daily_data['account']))->bank_id;

                                $data_daily_bank = array(
                                    'daily_bank_date' => $daily_data['daily_date'],
                                    'money' => $daily_data['money_in'] > 0 ? $daily_data['money_in'] : ($daily_data['money_out'] > 0 ? 0-$daily_data['money_out']:null),
                                    'bank' => $bank,
                                    'daily' => $daily->getLastDaily()->daily_id,
                                );
                                $daily_bank_model->createDaily($data_daily_bank);

                                if ($service == 1) {
                                    if ($daily_data['money_out'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'money' => $daily_data['money_out'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => $daily_data['money_out'],
                                            'pay_date' => $daily_data['daily_date'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => 0 - $daily_data['money_out'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $assets_model->createAssets($data_asset);


                                        $data_pay = array(
                                                    'source' => $bank,
                                                    'money' => $daily_data['money_out'],
                                                    'pay_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $pay_model->createCosts($data_pay);
                                    }
                                    if ($daily_data['money_in'] > 0) {
                                        $data_costs = array(
                                            'costs_create_date' => $daily_data['daily_date'],
                                            'costs_date' => $daily_data['daily_date'],
                                            'comment' => $daily_data['comment'],
                                            'expect_date' => $daily_data['daily_date'],
                                            'week' => (int)date('W', $daily_data['daily_date']),
                                            'create_user' => $_SESSION['userid_logined'],
                                            'source_in' => $bank,
                                            'source' => $bank,
                                            'year' => (int)date('Y', $daily_data['daily_date']),
                                            'pay_money' => 0,
                                            'money' => 0,
                                            'pay_date' => $daily_data['daily_date'],
                                            'money_in' => $daily_data['money_in'],
                                            'code' => $daily_data['code'],
                                            'check_office' => 1,
                                            'check_other' => 1,
                                            'additional' => $daily->getLastDaily()->daily_id,
                                            );
                                        if($data_costs['week'] == 53){
                                            $data_costs['week'] = 1;
                                            $data_costs['year'] = $data_costs['year']+1;
                                        }
                                        if (((int)date('W', $daily_data['daily_date']) == 1) && ((int)date('m', $daily_data['daily_date']) == 12) ) {
                                            $data_costs['year'] = (int)date('Y', $daily_data['daily_date'])+1;
                                        }

                                        $costs_model->createCosts($data_costs);

                                        $data_asset = array(
                                                    'bank' => $bank,
                                                    'total' => $daily_data['money_in'],
                                                    'assets_date' => $daily_data['daily_date'],
                                                    'costs' => $costs_model->getLastCosts()->costs_id,
                                                    'week' => $data_costs['week'],
                                                    'year' => $data_costs['year'],
                                                    'additional' => $daily->getLastDaily()->daily_id,
                                                );
                                        $assets_model->createAssets($data_asset);
                                    }
                                }
                        }
                        
                        
                        
                    }
                    


                }
                $i++;
            }
            return $this->view->redirect('daily');
        }
        $this->view->show('daily/import');

    }

    

}
?>