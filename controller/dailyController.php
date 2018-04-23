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
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, daily_id ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date('d-m-Y');
            $ketthuc = date('d-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $id = $this->registry->router->param_id;

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));
        $this->view->data['customers'] = $customers;

        $daily_model = $this->model->get('dailyModel');
        $daily_bank_model = $this->model->get('dailybankModel');
        $bank_model = $this->model->get('bankModel');

        $banks = $bank_model->getAllBank(array('where'=>'symbol IS NOT NULL AND symbol != ""'));
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
            'where' => 'daily_bank_date >= '.strtotime($batdau).' AND daily_bank_date < '.strtotime($ngayketthuc),
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

        $payment_request_model = $this->model->get('paymentrequestModel');

        $payment_requests = $payment_request_model->getAllPayment();
        $request_data = array();
        foreach ($payment_requests as $payment_request) {
            $request_data[$payment_request->payment_request_id] = $payment_request->payment_request_number;
        }
        $this->view->data['request_data'] = $request_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date < '.strtotime($ngayketthuc),
        );

        if ($id > 0) {
            $data['where'] = 'daily_id = '.$id;
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
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
        if ($id > 0) {
            $data['where'] = 'daily_id = '.$id;
        }
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR owner LIKE "%'.$keyword.'%" 
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
    public function index1() {
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
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date('d-m-Y');
            $ketthuc = date('d-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));
        $this->view->data['customers'] = $customers;

        $daily_model = $this->model->get('dailyModel');
        $daily_bank_model = $this->model->get('dailybankModel');
        $bank_model = $this->model->get('bankModel');

        $banks = $bank_model->getAllBank(array('where'=>'symbol IS NOT NULL AND symbol != ""'));
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
            'where' => 'daily_bank_date >= '.strtotime($batdau).' AND daily_bank_date < '.strtotime($ngayketthuc),
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
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date < '.strtotime($ngayketthuc),
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
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR owner LIKE "%'.$keyword.'%" 
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
        $this->view->show('daily/index1');
    }
    public function deposit() {
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
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));
        $this->view->data['customers'] = $customers;

        $daily_model = $this->model->get('dailyModel');
        $bank_model = $this->model->get('bankModel');

        $banks = $bank_model->getAllBank(array('where'=>'symbol IS NOT NULL AND symbol != ""'));
        $this->view->data['banks'] = $banks;

        $data_bank = array(
            'where' => 'daily_bank_date >= '.strtotime($batdau).' AND daily_bank_date < '.strtotime($ngayketthuc),
        );
        

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        
        $this->view->data['account_parents'] = $account_parents;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'deposit>0 AND daily_date >= '.strtotime($batdau).' AND daily_date < '.strtotime($ngayketthuc),
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
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'deposit>0 AND daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR owner LIKE "%'.$keyword.'%" 
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
        $this->view->show('daily/deposit');
    }

    public function getClearing(){
        header('Content-type: application/json');
        $q = $_GET["search"];

        $in = $_GET["in"];
        $out = $_GET["out"];

        if ($in != "" && $in != 0) {
            $receivable_model = $this->model->get('receivableModel');
            $data = array(
                'where' => 'code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%"',
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
                'where' => 'code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%"',
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
    public function ordertire() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {

            return $this->view->redirect('user/login');

        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn đặt hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
            $code = isset($_POST['tu']) ? $_POST['tu'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, order_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 0;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $tg = $this->registry->router->page;
        $stf = $this->registry->router->order_by;

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'customer_name',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        
        
        $data = array(
            'where' => ' ( delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).' )',
        );

        if ($nv == 1) {
            $data['where'] .= ' AND order_tire_id IN (SELECT order_tire FROM additional WHERE order_tire>0)';
        }
        else{
            $data['where'] .= ' AND order_tire_id NOT IN (SELECT order_tire FROM additional WHERE order_tire>0)';
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            $batdau = '01-'.date('m-Y',$tg);
            $ketthuc = date('t-m-Y',$tg);

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
                $page = 1;
                $order_by = 'order_tire_status ASC, delivery_date';
                $order = ' ASC';
            }
        }

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire.customer AND user_id = sale');
        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
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
        $this->view->data['nv'] = $nv;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => ' ( delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).' )',
            );

        if ($nv == 1) {
            $data['where'] .= ' AND order_tire_id IN (SELECT order_tire FROM additional WHERE order_tire>0)';
        }
        else{
            $data['where'] .= ' AND order_tire_id NOT IN (SELECT order_tire FROM additional WHERE order_tire>0)';
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
            }
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $tire_import_model = $this->model->get('tireimportModel');

        $costs = array();
        foreach ($order_tires as $tire) {
            $ngay = $tire->order_tire_status==1?$tire->delivery_date:strtotime(date('d-m-Y'));
            $ngayketthuc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date < '.$ngayketthuc.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC, tire_import_id DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($tire->order_number != "") {
                    $data = array(
                        'where' => 'order_num = "'.$tire->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$ngay)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                        'order_by' => 'start_date',
                        'order' => 'DESC, tire_import_id DESC',
                        'limit' => 1,
                    );
                    $tire_imports = $tire_import_model->getAllTire($data);
                    foreach ($tire_imports as $tire_import) {
                        $gia = $tire_import->tire_price;
                    }
                }

                $costs[$tire->order_tire_id] = isset($costs[$tire->order_tire_id])?$costs[$tire->order_tire_id]+$l->tire_number*$gia:$l->tire_number*$gia;
            }
        }
        
        $this->view->data['costs'] = $costs;
        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $tire_sale_model = $this->model->get('tiresaleModel');
        $join = array('table'=>'customer, user, staff','where'=>'customer.customer_id = tire_sale.customer AND staff_id = sale AND account = user_id');
        $data = array(
            'where' => 'customer = 169 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $tire_sale_model->getAllTire($data,$join);

        $costs2 = array();
        foreach ($sales as $tire) {
            $gia = 0;
            $data = array(
                'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $gia = $tire_import->tire_price;
            }

            $costs2[$tire->tire_sale_id] = isset($costs2[$tire->tire_sale_id])?$costs2[$tire->tire_sale_id]+$tire->volume*$gia:$tire->volume*$gia;
        }
        $this->view->data['costs2'] = $costs2;
        $this->view->data['sales'] = $sales;

        $this->view->show('daily/ordertire');
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
    public function getpayment(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $q = $_POST["keyword"];

            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getAllStaff();
            $staff_data = array();
            foreach ($staffs as $staff) {
                $staff_data[$staff->account] = $staff->staff_name;
            }

            $payment_request_model = $this->model->get('paymentrequestModel');
            $data = array(
                'where' => '(payment_request_pay_money IS NULL OR payment_request_pay_money=0 OR payment_request_pay_money<payment_request_money) AND (payment_request_number LIKE "%'.$q.'%" OR payment_request_comment LIKE "%'.$q.'%")',
            );
            $payments = $payment_request_model->getAllPayment($data);
            foreach ($payments as $payment) {
                    $code = str_replace($q, '<b>'.$q.'</b>', $payment->payment_request_number);   
                    echo '<li onclick="set_item_payment(\''.$payment->payment_request_number.'\',\''.$payment->payment_request_id.'\',\''.$payment->payment_request_comment.'\',\''.$this->lib->formatMoney($payment->payment_request_money-$payment->payment_request_pay_money).'\',\''.$payment->payment_request_receive.'\',\''.$staff_data[$payment->payment_request_user].'\',\''.$staff_data[$payment->payment_request_user_approve].'\')">'.$code." (".$payment->payment_request_comment.") - ".$this->lib->formatMoney($payment->payment_request_money-$payment->payment_request_pay_money).'</li>';
                
            }

            
        }
    }
    public function getpaymentdetail(){
        if (isset($_GET['payment'])) {
            $account_model = $this->model->get('accountModel');
            $payment_request_detail_model = $this->model->get('paymentrequestdetailModel');
            
            $accounts = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
            $additionals = $payment_request_detail_model->getAllPayment(array('where'=>'payment_request='.$_GET['payment']));

            $str = "";
            $i = 1;
            if ($additionals) {
                foreach ($additionals as $additional) {
                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>';
                    
                    $str .= '<td class="width-10"><input disabled data="'.$additional->payable.'" value="'.$additional->payment_request_detail_code.'" type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                    $str .= '<td class="width-10"><input disabled value="'.$this->lib->formatMoney($additional->payment_request_detail_money).'" type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off" data-max="'.$additional->payment_request_detail_money.'"></td>';
                    $str .= '<td><input value="'.$additional->payment_request_detail_comment.'" type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                    $str .= '<option value="1">Hành chính</option>';
                    $str .= '<option '.($additional->payable>0?'selected="selected"':null).' value="2">Lốp xe</option>';
                    $str .= '<option value="3">Logistics</option>';
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

            }
            

            $arr = array(
                'hang'=>$str,
            );
            echo json_encode($arr);
        }
    }
    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $q = $_POST["keyword"];
            $in = $_POST["in"];
            $out = $_POST["out"];

            if ($in != "" && $in != 0) {
                $receivable_model = $this->model->get('receivableModel');
                $data = array(
                    'where' => 'code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%"',
                );
                $receivables = $receivable_model->getAllCosts($data);
                foreach ($receivables as $receivable) {
                    if ($receivable->money-$receivable->pay_money > 0) {
                        $code = str_replace($q, '<b>'.$q.'</b>', $receivable->code);
                        
                        echo '<li onclick="set_item(\''.$receivable->code.'\',\''.$receivable->receivable_id.'\')">'.$code." (".$receivable->comment.") - ".$this->lib->formatMoney($receivable->money-$receivable->pay_money).'</li>';
                
                    }
                    
                }
            }
            if ($out != "" && $out != 0) {
                $payable_model = $this->model->get('payableModel');
                $data = array(
                    'where' => 'code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%"',
                );
                $payables = $payable_model->getAllCosts($data);
                $arr = array();
                foreach ($payables as $payable) {
                    if ($payable->money-$payable->pay_money > 0) {
                        $code = str_replace($q, '<b>'.$q.'</b>', $payable->code);
                        
                        echo '<li onclick="set_item(\''.$payable->code.'\',\''.$payable->payable_id.'\')">'.$code." (".$payable->comment.") - ".$this->lib->formatMoney($payable->money-$payable->pay_money).'</li>';
                    }
                }
            }

            
        }
    }
    public function getlistcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $q = $_POST["keyword"];
            $in = $_POST["in"];
            $out = $_POST["out"];
            $vitri = $_POST['offset'];
            $customer = $_POST['customer'];

            if ($in != "" && $in != 0) {
                $receivable_model = $this->model->get('receivableModel');
                $data = array(
                    'where' => '(code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%")',
                );
                if ($customer>0) {
                    $data['where'] .= ' AND customer='.$customer;
                }
                $receivables = $receivable_model->getAllCosts($data);
                foreach ($receivables as $receivable) {
                    if ($receivable->money-$receivable->pay_money > 0) {
                        $code = str_replace($q, '<b>'.$q.'</b>', $receivable->code);
                        
                        echo '<li onclick="set_item_code(\''.$receivable->code.'\',\''.$receivable->receivable_id.'\',\''.$vitri.'\',\''.$this->lib->formatMoney($receivable->money-$receivable->pay_money).'\',\''.($receivable->money-$receivable->pay_money).'\')">'.$code." (".$receivable->comment.") - ".$this->lib->formatMoney($receivable->money-$receivable->pay_money).'</li>';
                
                    }
                    
                }
            }
            if ($out != "" && $out != 0) {
                $payable_model = $this->model->get('payableModel');
                $data = array(
                    'where' => 'code LIKE "%'.$q.'%" OR comment LIKE "%'.$q.'%"',
                );
                $payables = $payable_model->getAllCosts($data);
                $arr = array();
                foreach ($payables as $payable) {
                    if ($payable->money-$payable->pay_money > 0) {
                        $code = str_replace($q, '<b>'.$q.'</b>', $payable->code);
                        
                        echo '<li onclick="set_item_code(\''.$payable->code.'\',\''.$payable->payable_id.'\',\''.$vitri.'\',\''.$this->lib->formatMoney($payable->money-$payable->pay_money).'\',\''.($payable->money-$payable->pay_money).'\')">'.$code." (".$payable->comment.") - ".$this->lib->formatMoney($payable->money-$payable->pay_money).'</li>';
                    }
                }
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
    public function getitemadd(){
        if (isset($_POST['daily'])) {
            $account_model = $this->model->get('accountModel');
            $additional_model = $this->model->get('additionalModel');
            
            $accounts = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
            $additionals = $additional_model->getAllAdditional(array('where'=>'daily='.$_POST['daily']));

            $str = "";
            $i = 1;
            if ($additionals) {
                foreach ($additionals as $additional) {
                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>';
                    
                    $str .= '<td class="width-10"><input '.($additional->additional_payable>0?"disabled":null).' data="'.$additional->additional_payable.'" value="'.$additional->code.'" type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                    $str .= '<td class="width-10"><input data-max="'.$additional->data_max.'" data="'.$additional->additional_id.'" value="'.$this->lib->formatMoney($additional->money).'" type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                    $str .= '<td><input value="'.$additional->additional_comment.'" type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option '.($account->account_id==$additional->debit?'selected':null).' value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option '.($account->account_id==$additional->credit?'selected':null).' value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                    $str .= '<option '.($additional->additional_service==1?'selected':null).' value="1">Hành chính</option>';
                    $str .= '<option '.($additional->additional_service==2?'selected':null).' value="2">Lốp xe</option>';
                    $str .= '<option '.($additional->additional_service==3?'selected':null).' value="3">Logistics</option>';
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

                $i++;

                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

                $i++;

                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

                $i++;

                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

                $i++;

                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"><ul class="customer_list_id"></ul></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_service[]" class="additional_service" required="required">';
                $str .= '<option value="1">Hành chính</option>';
                $str .= '<option value="2">Lốp xe</option>';
                $str .= '<option value="3">Logistics</option>';
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';

                $i++;

            }
            

            $arr = array(
                'hang'=>$str,
            );
            echo json_encode($arr);
        }
    }
    public function getPayvoucher(){
        $daily_model = $this->model->get('dailyModel');
        $data = array(
            'where' => 'note LIKE "pc%"',
            'order_by' => 'daily_date DESC, ABS(SUBSTRING(note, -3)) DESC',
            'limit' => 1,
        );
        $pays = $daily_model->getAllDaily($data);
        $num = "";
        foreach ($pays as $pay) {
            $num = ++$pay->note;
        }
        echo $num;
    }
    public function getRevoucher(){
        $daily_model = $this->model->get('dailyModel');
        $data = array(
            'where' => 'note LIKE "pt%"',
            'order_by' => 'daily_date DESC, ABS(SUBSTRING(note, -3)) DESC',
            'limit' => 1,
        );
        $pays = $daily_model->getAllDaily($data);
        $num = "";
        foreach ($pays as $pay) {
            $num = ++$pay->note;
        }
        echo $num;
    }

    public function printpay() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $daily = $this->registry->router->param_id;

        $daily_model = $this->model->get('dailyModel');
        $dailys = $daily_model->getDaily($daily);

        $this->view->data['dailys'] = $dailys;

        $info_model = $this->model->get('infoModel');
        $this->view->data['infos'] = $info_model->getLastInfo();

        $account_model = $this->model->get('accountModel');
        $additional_model = $this->model->get('additionalModel');
        
        $additionals = $additional_model->getAllAdditional(array('where'=>'daily='.$daily));

        $code = "";
        foreach ($additionals as $add) {
            $debit = $account_model->getAccount($add->debit);
            $credit = $account_model->getAccount($add->credit);

            if ($code=="") {
                $code = $add->code;
            }
            else{
                $code .= ','.$add->code;
            }
        }

        $this->view->data['code'] = $code;

        $this->view->data['debit'] = $debit;
        $this->view->data['credit'] = $credit;

        $this->view->show('daily/printpay');
    }
    public function printre() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $daily = $this->registry->router->param_id;

        $daily_model = $this->model->get('dailyModel');
        $dailys = $daily_model->getDaily($daily);

        $this->view->data['dailys'] = $dailys;

        $info_model = $this->model->get('infoModel');
        $this->view->data['infos'] = $info_model->getLastInfo();

        $account_model = $this->model->get('accountModel');
        $additional_model = $this->model->get('additionalModel');
        
        $additionals = $additional_model->getAllAdditional(array('where'=>'daily='.$daily));

        $code = "";
        foreach ($additionals as $add) {
            $debit = $account_model->getAccount($add->debit);
            $credit = $account_model->getAccount($add->credit);

            if ($code=="") {
                $code = $add->code;
            }
            else{
                $code .= ','.$add->code;
            }
        }

        $this->view->data['code'] = $code;

        $this->view->data['debit'] = $debit;
        $this->view->data['credit'] = $credit;

        $this->view->show('daily/printre');
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
            $deposit_model = $this->model->get('deposittireModel');
            $payment_request_model = $this->model->get('paymentrequestModel');

            $data = array(
                        
                        'service' => trim($_POST['service']),
                        'owner' => trim($_POST['owner']),
                        'owner_request' => trim($_POST['owner_request']),
                        'owner_approve' => trim($_POST['owner_approve']),
                        'note' => trim($_POST['note']),
                        'account' => trim($_POST['account']),
                        'daily_date' => strtotime(str_replace('/','-',$_POST['daily_date'])),
                        'comment' => trim($_POST['comment']),
                        'debit' => trim($_POST['debit']),
                        'credit' => trim($_POST['credit']),
                        'code' => trim($_POST['code']),
                        'money_in' => trim(str_replace(',','',$_POST['money_in'])),
                        'money_out' => trim(str_replace(',','',$_POST['money_out'])),
                        'receivable' => trim($_POST['receivable']),
                        'payable' => trim($_POST['payable']),
                        'deposit' => trim($_POST['deposit']),
                        'customer' => trim($_POST['customer']),
                        'daily_check_lohang' => trim($_POST['daily_check_lohang']),
                        'daily_check_cost' => trim($_POST['daily_check_cost']),
                        'internal_transfer' => trim($_POST['internal_transfer']),
                        'account_out' => trim($_POST['account_out']),
                        );
            if (trim($_POST['payment_request_number'])!="") {
                if ($_POST['payment_request']>0) {
                    $data['payment_request'] = $_POST['payment_request'];
                }
                else{
                    $payments = $payment_request_model->getPaymentByWhere(array('payment_request_number'=>trim($_POST['payment_request_number'])));
                    if ($payments) {
                        $data['payment_request'] = $payments->payment_request_id;
                    }
                }
            }

            if (isset($data['payment_request']) && $data['payment_request']>0) {
                $payment_request_model->updatePayment(array('payment_request_pay_money'=>$data['money_out'],'payment_request_pay_date'=>$data['daily_date']),array('payment_request_id'=>$data['payment_request']));
            }

            if ($data['deposit']>0) {
                $data['service'] = 1;
            }
            
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

                
                
                    $daily = $daily_model->getDaily(trim($_POST['yes']));

                    $daily_model->updateDaily($data,array('daily_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

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

                    if ($daily->deposit != 1 && $data['deposit']>0) {
                        $data_deposit = array(
                            'customer' => $data['customer'],
                            'daily' => trim($_POST['yes']),
                        );
                        $deposit_model->createDeposit($data_deposit);
                    }
                    else if ($daily->deposit == 1 && $data['deposit']>0) {
                        $data_deposit = array(
                            'customer' => $data['customer'],
                        );
                        $deposit_model->updateDeposit($data_deposit,array('daily'=>trim($_POST['yes'])));
                    }
                    if ($daily->deposit == 1 && $data['deposit'] != 1) {
                        $deposit_model->queryDeposit('DELETE FROM deposit_tire WHERE daily = '.trim($_POST['yes']));
                    }

                    if ($data['internal_transfer']==1) {
                        $bank_old = $bank_model->getBankByWhere(array('symbol'=>$daily->account))->bank_id;
                        $bank_out_old = $bank_model->getBankByWhere(array('symbol'=>$daily->account_out))->bank_id;

                        $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;
                        $bank_out = $bank_model->getBankByWhere(array('symbol'=>$data['account_out']))->bank_id;

                        $daily_banks = $daily_bank_model->getDailyByWhere(array('daily'=>$_POST['yes'],'bank'=>$bank_old));
                        if (!$daily_banks) {
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => $data['money_in'],
                                'bank' => $bank,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->createDaily($data_daily_bank);
                        }
                        else{
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => $data['money_in'],
                                'bank' => $bank,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->updateDaily($data_daily_bank,array('daily_bank_id' => $daily_banks->daily_bank_id));
                        }

                        $daily_banks = $daily_bank_model->getDailyByWhere(array('daily'=>$_POST['yes'],'bank'=>$bank_out_old));
                        if (!$daily_banks) {
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => 0-$data['money_out'],
                                'bank' => $bank_out,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->createDaily($data_daily_bank);
                        }
                        else{
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => 0-$data['money_out'],
                                'bank' => $bank_out,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->updateDaily($data_daily_bank,array('daily_bank_id' => $daily_banks->daily_bank_id));
                        }

                        $d_costs = $costs_model->getCostsByWhere(array('additional'=>$_POST['yes']));
                        if (!$d_costs) {
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source_in' => $bank,
                                'source' => $bank_out,
                                'year' => $year,
                                'pay_money' => $data['money_out'],
                                'pay_date' => $data['daily_date'],
                                'money_in' => $data['money_in'],
                                'money' => $data['money_out'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'additional' => $_POST['yes'],
                                'check_lohang' => $data['daily_check_lohang'],
                                'check_costs' => $data['daily_check_cost'],
                                );
                            $costs_model->createCosts($data_costs);

                            $cost_id = $costs_model->getLastCosts()->costs_id;

                            $data_asset = array(
                                        'bank' => $bank,
                                        'total' => $data['money_in'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $cost_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $assets_model->createAssets($data_asset);

                            $data_asset = array(
                                        'bank' => $bank_out,
                                        'total' => 0-$data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'costs' => $cost_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $assets_model->createAssets($data_asset);

                            $data_pay = array(
                                        'source' => $bank_out,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'costs' => $cost_id,
                                        'week' => $week,
                                        'year' => $year,
                                        'additional' => $id_daily_last,
                                    );
                            $pay_model->createCosts($data_pay);
                        }
                        else{
                            $data_costs = array(
                                'costs_create_date' => $data['daily_date'],
                                'costs_date' => $data['daily_date'],
                                'comment' => $data['comment'],
                                'expect_date' => $data['daily_date'],
                                'week' => $week,
                                'create_user' => $_SESSION['userid_logined'],
                                'source_in' => $bank,
                                'source' => $bank_out,
                                'year' => $year,
                                'pay_money' => $data['money_out'],
                                'money' => $data['money_out'],
                                'pay_date' => $data['daily_date'],
                                'money_in' => $data['money_in'],
                                'code' => $data['code'],
                                'check_office' => 1,
                                'check_other' => 1,
                                'check_lohang' => $data['daily_check_lohang'],
                                'check_costs' => $data['daily_check_cost'],
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
                            $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id,'bank'=>$daily->account));

                            $data_asset = array(
                                        'bank' => $bank_out,
                                        'total' => 0 - $data['money_out'],
                                        'assets_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                    );
                            $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id,'bank'=>$daily->account_out));


                            $data_pay = array(
                                        'source' => $bank_out,
                                        'money' => $data['money_out'],
                                        'pay_date' => $data['daily_date'],
                                        'week' => $week,
                                        'year' => $year,
                                    );
                            $pay_model->updateCosts($data_pay,array('additional' => $_POST['yes'],'costs'=>$cost->costs_id));
                        }

                        
                    }
                    else{
                        $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;

                        $daily_banks = $daily_bank_model->getDailyByWhere(array('daily'=>$_POST['yes']));
                        if (!$daily_banks) {
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? 0-$data['money_out']:null),
                                'bank' => $bank,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->createDaily($data_daily_bank);
                        }
                        else{
                            $data_daily_bank = array(
                                'daily_bank_date' => $data['daily_date'],
                                'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? 0-$data['money_out']:null),
                                'bank' => $bank,
                                'daily' => trim($_POST['yes']),
                            );
                            $daily_bank_model->updateDaily($data_daily_bank,array('daily' => trim($_POST['yes'])));
                        }
                        

                        
                        

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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    );

                                $costs_model->createCosts($data_costs);

                                $cost_id = $costs_model->getLastCosts()->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data['money_out'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $_POST['yes'],
                                        );
                                $assets_model->createAssets($data_asset);


                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data['money_out'],
                                            'pay_date' => $data['daily_date'],
                                            'costs' => $cost_id,
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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    );
                                $costs_model->createCosts($data_costs);

                                $cost_id = $costs_model->getLastCosts()->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data['money_in'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
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
                            $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$cost->costs_id);
                            $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$cost->costs_id);
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

                                $receivable = $receivable_model->getCosts($daily->receivable);
                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money - $data['money_in'],
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$daily->receivable));
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
                                                'total' => $data['money_in'],
                                                'assets_date' => $data['daily_date'],
                                                'receivable' => $data['receivable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $_POST['yes'],
                                            );
                                    $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'receivable'=>$daily->receivable));

                                    
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
                                    
                                    $receive_model->updateCosts($data_receive,array('additional' => $_POST['yes']));

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
                                    $obtain_model->updateObtain($data_obtain,array('additional' => $_POST['yes']));
                                }
                                else{
                                    $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND receivable = '.$daily->receivable);
                                    $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$_POST['yes']);
                                    $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$_POST['yes']);

                                    $receivable = $receivable_model->getCosts($daily->receivable);
                                    $data_receivable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $receivable->pay_money - $data['money_in'],
                                    );
                                    $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$daily->receivable));


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

                                $payable = $payable_model->getCosts($daily->payable);
                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money - $data['money_out'],
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$daily->payable));
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
                                                'total' => 0 - $data['money_out'],
                                                'assets_date' => $data['daily_date'],
                                                'payable' => $data['payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $_POST['yes'],
                                            );
                                    $assets_model->updateAssets($data_asset,array('additional' => $_POST['yes'],'payable'=>$daily->payable));

                                    
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

                                    $pay_model->updateCosts($data_pay,array('additional' => $_POST['yes'],'payable'=>$daily->payable));

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
                                    $owe_model->updateOwe($data_owe,array('additional' => $_POST['yes']));
                                }
                                else{
                                    $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                                    $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$_POST['yes'].' AND payable = '.$daily->payable);
                                    $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$_POST['yes']);

                                    $payable = $payable_model->getCosts($daily->payable);
                                    $data_payable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $payable->pay_money - $data['money_out'],
                                    );
                                    $payable_model->updateCosts($data_payable,array('payable_id'=>$daily->payable));

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
                                                    'receive_comment' => $data['comment'].' ('.$receivable->comment.')',
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
                                                    'receive_comment' => $data['comment'].' ('.$receivable->comment.')',
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
                                                    'pay_comment' => $data['comment'].' ('.$payable->comment.')',
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
                                                    'pay_comment' => $data['comment'].' ('.$payable->comment.')',
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
                                    $pay_model->updateCosts(array('money'=>$conlai),array('costs'=>$cost->costs_id));
                                }
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

                    $week = (int)date('W', $data['daily_date']);
                    $year = (int)date('Y', $data['daily_date']);

                    if($week == 53){
                        $week = 1;
                        $year = $year+1;
                    }
                    if (((int)date('W', $data['daily_date']) == 1) && ((int)date('m', $data['daily_date']) == 12 )) {
                        $year = (int)date('Y', $data['daily_date'])+1;
                    }


                    if ($data['deposit']>0) {
                        $data_deposit = array(
                            'customer' => $data['customer'],
                            'daily' => $id_daily_last,
                        );
                        $deposit_model->createDeposit($data_deposit);
                    }

                    if($data['internal_transfer']==1){
                        $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;
                        $bank_out = $bank_model->getBankByWhere(array('symbol'=>$data['account_out']))->bank_id;

                        $data_daily_bank = array(
                            'daily_bank_date' => $data['daily_date'],
                            'money' => $data['money_in'],
                            'bank' => $bank,
                            'daily' => $id_daily_last,
                        );
                        $daily_bank_model->createDaily($data_daily_bank);

                        $data_daily_bank = array(
                            'daily_bank_date' => $data['daily_date'],
                            'money' => 0-$data['money_out'],
                            'bank' => $bank_out,
                            'daily' => $id_daily_last,
                        );
                        $daily_bank_model->createDaily($data_daily_bank);

                        $data_costs = array(
                            'costs_create_date' => $data['daily_date'],
                            'costs_date' => $data['daily_date'],
                            'comment' => $data['comment'],
                            'expect_date' => $data['daily_date'],
                            'week' => $week,
                            'create_user' => $_SESSION['userid_logined'],
                            'source_in' => $bank,
                            'source' => $bank_out,
                            'year' => $year,
                            'pay_money' => $data['money_out'],
                            'pay_date' => $data['daily_date'],
                            'money_in' => $data['money_in'],
                            'money' => $data['money_out'],
                            'code' => $data['code'],
                            'check_office' => 1,
                            'check_other' => 1,
                            'additional' => $id_daily_last,
                            'check_lohang' => $data['daily_check_lohang'],
                            'check_costs' => $data['daily_check_cost'],
                            );
                        $costs_model->createCosts($data_costs);

                        $cost_id = $costs_model->getLastCosts()->costs_id;

                        $data_asset = array(
                                    'bank' => $bank,
                                    'total' => $data['money_in'],
                                    'assets_date' => $data['daily_date'],
                                    'costs' => $cost_id,
                                    'week' => $week,
                                    'year' => $year,
                                    'additional' => $id_daily_last,
                                );
                        $assets_model->createAssets($data_asset);

                        $data_asset = array(
                                    'bank' => $bank_out,
                                    'total' => 0-$data['money_out'],
                                    'assets_date' => $data['daily_date'],
                                    'costs' => $cost_id,
                                    'week' => $week,
                                    'year' => $year,
                                    'additional' => $id_daily_last,
                                );
                        $assets_model->createAssets($data_asset);

                        $data_pay = array(
                                    'source' => $bank_out,
                                    'money' => $data['money_out'],
                                    'pay_date' => $data['daily_date'],
                                    'costs' => $cost_id,
                                    'week' => $week,
                                    'year' => $year,
                                    'additional' => $id_daily_last,
                                );
                        $pay_model->createCosts($data_pay);
                    }
                    else{
                        $bank = $bank_model->getBankByWhere(array('symbol'=>$data['account']))->bank_id;

                        $data_daily_bank = array(
                            'daily_bank_date' => $data['daily_date'],
                            'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? 0-$data['money_out']:null),
                            'bank' => $bank,
                            'daily' => $id_daily_last,
                        );
                        $daily_bank_model->createDaily($data_daily_bank);

                        

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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
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
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
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
                                                    'receive_comment' => $data['comment'].' ('.$receivable->comment.')',
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
                                                    'receive_comment' => $data['comment'].' ('.$receivable->comment.')',
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
                                                    'pay_comment' => $data['comment'].' ('.$payable->comment.')',
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
                                                    'pay_comment' => $data['comment'].' ('.$payable->comment.')',
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
                                    $pay_model->updateCosts(array('money'=>$conlai),array('costs'=>$cost->costs_id));
                                }
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

            $additionals = $_POST['additional'];
            $arr_item = "";
            foreach ($additionals as $v) {
                $data_additional = array(
                    'document_date' => $data['daily_date'],
                    'additional_date' => $data['daily_date'],
                    'additional_comment' => trim($v['additional_comment']),
                    'debit' => $v['additional_debit'],
                    'credit' => $v['additional_credit'],
                    'money' => str_replace(',','',$v['additional_money']),
                    'code' => trim($v['additional_code']),
                    'additional_receive' => $data['owner'],
                    'additional_request' => $data['owner_request'],
                    'additional_approve' => $data['owner_approve'],
                    'additional_payable' => trim($v['additional_payable']),
                    'data_max' => str_replace(',','',$v['additional_data_max']),
                    'additional_service' => $v['additional_service'],
                    'daily' => $id_daily_last,
                );

                if ($data_additional['money'] > 0 && $data_additional['debit'] > 0 && $data_additional['credit'] > 0) {
                    if ($v['additional_id'] > 0) {
                        $additional_id = $v['additional_id'];
                        $add = $additional_model->getAdditional($additional_id);
                        $additional_model->updateAdditional($data_additional,array('additional_id'=>$v['additional_id']));
                        
                        if($data['money_out']>0){
                            if ($data_additional['additional_payable'] > 0) {
                                if ($add->additional_payable==0 || $add->additional_payable=="") {

                                    $costs = $costs_model->getCostsByWhere(array('daily_additional'=>$additional_id));
                                    $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$costs->costs_id);
                                    $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$costs->costs_id);
                                    $costs_model->queryCosts('DELETE FROM costs WHERE costs_id = '.$costs->costs_id);

                                    $payable = $payable_model->getCosts($data_additional['additional_payable']);
                                    $data_payable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $payable->pay_money + $data_additional['money'],
                                    );
                                    $payable_model->updateCosts($data_payable,array('payable_id'=>$data_additional['additional_payable']));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => 0 - $data_additional['money'],
                                                'assets_date' => $data['daily_date'],
                                                'payable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    $data_pay = array(
                                                'source' => $bank,
                                                'money' => $data_additional['money'],
                                                'pay_date' => $data['daily_date'],
                                                'payable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'pay_comment' => $data_additional['additional_comment'],
                                                'additional' => $id_daily_last,
                                            );

                                    $pay_model->createCosts($data_pay);

                                    $data_owe = array(
                                        'vendor' => $payable->vendor,
                                        'money' => 0 - $data_additional['money'],
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
                                        'payable' => $data_additional['additional_payable'],
                                        'additional' => $id_daily_last,
                                    );
                                    $owe_model->createOwe($data_owe);
                                }
                                else{
                                    $payable = $payable_model->getCosts($data_additional['additional_payable']);
                                    $data_payable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $payable->pay_money - $add->money + $data_additional['money'],
                                    );
                                    $payable_model->updateCosts($data_payable,array('payable_id'=>$data_additional['additional_payable']));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => 0 - $data_additional['money'],
                                                'assets_date' => $data['daily_date'],
                                                'payable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->updateAssets($data_asset,array('additional' => $id_daily_last,'payable'=>$data_additional['additional_payable']));

                                    $data_pay = array(
                                                'source' => $bank,
                                                'money' => $data_additional['money'],
                                                'pay_date' => $data['daily_date'],
                                                'payable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'pay_comment' => $data_additional['additional_comment'],
                                                'additional' => $id_daily_last,
                                            );

                                    $pay_model->updateCosts($data_pay,array('additional' => $id_daily_last,'payable'=>$data_additional['additional_payable']));

                                    $data_owe = array(
                                        'vendor' => $payable->vendor,
                                        'money' => 0 - $data_additional['money'],
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
                                        'payable' => $data_additional['additional_payable'],
                                        'additional' => $id_daily_last,
                                    );
                                    $owe_model->updateOwe($data_owe,array('additional' => $id_daily_last,'payable'=>$data_additional['additional_payable']));
                                }
                                
                            }

                            else if ($data['service'] != 1 && $data_additional['additional_service'] == 1) {
                                $data_costs = array(
                                    'costs_create_date' => $data['daily_date'],
                                    'costs_date' => $data['daily_date'],
                                    'comment' => $data_additional['additional_comment'],
                                    'money' => $data_additional['money'],
                                    'expect_date' => $data['daily_date'],
                                    'week' => $week,
                                    'create_user' => $_SESSION['userid_logined'],
                                    'source' => $bank,
                                    'year' => $year,
                                    'pay_money' => $data_additional['money'],
                                    'pay_date' => $data['daily_date'],
                                    'code' => $data_additional['code'],
                                    'check_office' => 1,
                                    'check_other' => 1,
                                    'additional' => $id_daily_last,
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    'daily_additional' => $additional_id,
                                    );

                                $costs_model->updateCosts($data_costs,array('additional' => $id_daily_last,'daily_additional' => $additional_id));

                                $cost_id = $costs_model->getCostsByWhere(array('additional' => $id_daily_last,'daily_additional' => $additional_id))->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->updateAssets($data_asset,array('costs' => $cost_id));


                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data_additional['money'],
                                            'pay_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $pay_model->updateCosts($data_pay,array('costs' => $cost_id));
                            }
                        }
                        else if ($data['money_in']>0) {
                            if ($data_additional['additional_payable'] > 0) {
                                if ($add->additional_payable==0 || $add->additional_payable=="") {

                                    $costs = $costs_model->getCostsByWhere(array('daily_additional'=>$additional_id));
                                    $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$costs->costs_id);
                                    $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$costs->costs_id);
                                    $costs_model->queryCosts('DELETE FROM costs WHERE costs_id = '.$costs->costs_id);

                                    $receivable = $receivable_model->getCosts($data_additional['additional_payable']);
                                    $data_receivable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $receivable->pay_money + $data_additional['money'],
                                    );
                                    $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data_additional['additional_payable']));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => $data_additional['money'],
                                                'assets_date' => $data['daily_date'],
                                                'receivable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->createAssets($data_asset);

                                    
                                    $data_receive = array(
                                                'source' => $bank,
                                                'money' => $data_additional['money'],
                                                'receive_date' => $data['daily_date'],
                                                'receivable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'receive_comment' => $data_additional['additional_comment'],
                                                'additional' => $id_daily_last,
                                            );
                                    
                                    $receive_model->createCosts($data_receive);

                                    $data_obtain = array(
                                        'customer' => $receivable->customer,
                                        'money' => 0 - $data_additional['additional_payable'],
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
                                        'receivable' => $data_additional['additional_payable'],
                                        'additional' => $id_daily_last,
                                    );
                                    $obtain_model->createObtain($data_obtain);
                                }
                                else{
                                    $receivable = $receivable_model->getCosts($data_additional['additional_payable']);
                                    $data_receivable = array(
                                        'pay_date' => $data['daily_date'],
                                        'pay_money' => $receivable->pay_money - $add->money + $data_additional['money'],
                                    );
                                    $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data_additional['additional_payable']));

                                    $data_asset = array(
                                                'bank' => $bank,
                                                'total' => $data_additional['money'],
                                                'assets_date' => $data['daily_date'],
                                                'receivable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'additional' => $id_daily_last,
                                            );
                                    $assets_model->updateAssets($data_asset,array('additional' => $id_daily_last,'receivable'=>$data_additional['additional_payable']));

                                    
                                    $data_receive = array(
                                                'source' => $bank,
                                                'money' => $data_additional['money'],
                                                'receive_date' => $data['daily_date'],
                                                'receivable' => $data_additional['additional_payable'],
                                                'week' => $week,
                                                'year' => $year,
                                                'receive_comment' => $data_additional['additional_comment'],
                                                'additional' => $id_daily_last,
                                            );
                                    
                                    $receive_model->updateCosts($data_receive,array('additional' => $id_daily_last,'receivable'=>$data_additional['additional_payable']));

                                    $data_obtain = array(
                                        'customer' => $receivable->customer,
                                        'money' => 0 - $data_additional['additional_payable'],
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
                                        'receivable' => $data_additional['additional_payable'],
                                        'additional' => $id_daily_last,
                                    );
                                    $obtain_model->updateObtain($data_obtain,array('additional' => $id_daily_last,'receivable'=>$data_additional['additional_payable']));
                                }
                                
                            }
                            else if ($data['service'] != 1 && $data_additional['additional_service'] == 1) {
                                $data_costs = array(
                                    'costs_create_date' => $data['daily_date'],
                                    'costs_date' => $data['daily_date'],
                                    'comment' => $data_additional['additional_comment'],
                                    'expect_date' => $data['daily_date'],
                                    'week' => $week,
                                    'create_user' => $_SESSION['userid_logined'],
                                    'source_in' => $bank,
                                    'source' => $bank,
                                    'year' => $year,
                                    'pay_money' => 0,
                                    'money' => 0,
                                    'pay_date' => $data['daily_date'],
                                    'money_in' => $data_additional['money'],
                                    'code' => $data_additional['code'],
                                    'check_office' => 1,
                                    'check_other' => 1,
                                    'additional' => $id_daily_last,
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    'daily_additional' => $additional_id,
                                    );
                                $costs_model->updateCosts($data_costs,array('additional' => $id_daily_last,'daily_additional' => $additional_id));

                                $cost_id = $costs_model->getCostsByWhere(array('additional' => $id_daily_last,'daily_additional' => $additional_id))->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->updateAssets($data_asset,array('costs' => $cost_id));
                            }
                        }
                    }
                    else{
                        
                        $additional_model->createAdditional($data_additional);
                        $additional_id = $additional_model->getLastAdditional()->additional_id;
                        $add = $additional_model->getAdditional($additional_id);



                        if($data['money_out']>0){
                            if ($data_additional['additional_payable'] > 0) {
                                $payable = $payable_model->getCosts($data_additional['additional_payable']);
                                $data_payable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $payable->pay_money + $data_additional['money'],
                                );
                                $payable_model->updateCosts($data_payable,array('payable_id'=>$data_additional['additional_payable']));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'payable' => $data_additional['additional_payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data_additional['money'],
                                            'pay_date' => $data['daily_date'],
                                            'payable' => $data_additional['additional_payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'pay_comment' => $data_additional['additional_comment'],
                                            'additional' => $id_daily_last,
                                        );

                                $pay_model->createCosts($data_pay);

                                $data_owe = array(
                                    'vendor' => $payable->vendor,
                                    'money' => 0 - $data_additional['money'],
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
                                    'payable' => $data_additional['additional_payable'],
                                    'additional' => $id_daily_last,
                                );
                                $owe_model->createOwe($data_owe);
                            }  

                            else if ($data['service'] != 1 && $data_additional['additional_service'] == 1) {
                                $data_costs = array(
                                    'costs_create_date' => $data['daily_date'],
                                    'costs_date' => $data['daily_date'],
                                    'comment' => $data_additional['additional_comment'],
                                    'money' => $data_additional['money'],
                                    'expect_date' => $data['daily_date'],
                                    'week' => $week,
                                    'create_user' => $_SESSION['userid_logined'],
                                    'source' => $bank,
                                    'year' => $year,
                                    'pay_money' => $data_additional['money'],
                                    'pay_date' => $data['daily_date'],
                                    'code' => $data_additional['code'],
                                    'check_office' => 1,
                                    'check_other' => 1,
                                    'additional' => $id_daily_last,
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    'daily_additional' => $additional_id,
                                    );

                                $costs_model->createCosts($data_costs);

                                $cost_id = $costs_model->getLastCosts()->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => 0 - $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);


                                $data_pay = array(
                                            'source' => $bank,
                                            'money' => $data_additional['money'],
                                            'pay_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $pay_model->createCosts($data_pay);
                            }
                        }
                        else if ($data['money_in']>0) {
                            if ($data_additional['additional_payable'] > 0) {
                                $receivable = $receivable_model->getCosts($data_additional['additional_payable']);
                                $data_receivable = array(
                                    'pay_date' => $data['daily_date'],
                                    'pay_money' => $receivable->pay_money + $data_additional['money'],
                                );
                                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$data_additional['additional_payable']));

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'receivable' => $data_additional['additional_payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);

                                
                                $data_receive = array(
                                            'source' => $bank,
                                            'money' => $data_additional['money'],
                                            'receive_date' => $data['daily_date'],
                                            'receivable' => $data_additional['additional_payable'],
                                            'week' => $week,
                                            'year' => $year,
                                            'receive_comment' => $data_additional['additional_comment'],
                                            'additional' => $id_daily_last,
                                        );
                                
                                $receive_model->createCosts($data_receive);

                                $data_obtain = array(
                                    'customer' => $receivable->customer,
                                    'money' => 0 - $data_additional['additional_payable'],
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
                                    'receivable' => $data_additional['additional_payable'],
                                    'additional' => $id_daily_last,
                                );
                                $obtain_model->createObtain($data_obtain);
                            }
                            else if ($data['service'] != 1 && $data_additional['additional_service'] == 1) {
                                $data_costs = array(
                                    'costs_create_date' => $data['daily_date'],
                                    'costs_date' => $data['daily_date'],
                                    'comment' => $data_additional['additional_comment'],
                                    'expect_date' => $data['daily_date'],
                                    'week' => $week,
                                    'create_user' => $_SESSION['userid_logined'],
                                    'source_in' => $bank,
                                    'source' => $bank,
                                    'year' => $year,
                                    'pay_money' => 0,
                                    'money' => 0,
                                    'pay_date' => $data['daily_date'],
                                    'money_in' => $data_additional['money'],
                                    'code' => $data_additional['code'],
                                    'check_office' => 1,
                                    'check_other' => 1,
                                    'additional' => $id_daily_last,
                                    'check_lohang' => $data['daily_check_lohang'],
                                    'check_costs' => $data['daily_check_cost'],
                                    'daily_additional' => $additional_id,
                                    );
                                $costs_model->createCosts($data_costs);

                                $cost_id = $costs_model->getLastCosts()->costs_id;

                                $data_asset = array(
                                            'bank' => $bank,
                                            'total' => $data_additional['money'],
                                            'assets_date' => $data['daily_date'],
                                            'costs' => $cost_id,
                                            'week' => $week,
                                            'year' => $year,
                                            'additional' => $id_daily_last,
                                        );
                                $assets_model->createAssets($data_asset);
                            }
                        }
                            
                        
                    }

                    if($additional_id>0){
                        if ($arr_item=="") {
                            $arr_item .= $additional_id;
                        }
                        else{
                            $arr_item .= ','.$additional_id;
                        }

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );

                        if($data_debit['week'] == 53){
                            $data_debit['week'] = 1;
                            $data_debit['year'] = $data_debit['year']+1;

                            $data_credit['week'] = 1;
                            $data_credit['year'] = $data_credit['year']+1;
                        }
                        if (((int)date('W', $data_additional['additional_date']) == 1) && ((int)date('m', $data_additional['additional_date']) == 12) ) {
                            $data_debit['year'] = (int)date('Y', $data_additional['additional_date'])+1;
                            $data_credit['year'] = (int)date('Y', $data_additional['additional_date'])+1;
                        }


                        if (!$account_balance_model->getAccountByWhere(array('additional'=>$additional_id))) {
                            $account_balance_model->createAccount($data_debit);
                            $account_balance_model->createAccount($data_credit);
                        }
                        else{
                            $d = $account_balance_model->getAccountByWhere(array('additional'=>$additional_id,'account'=>$add->debit,'money'=>$add->money));
                            $c = $account_balance_model->getAccountByWhere(array('additional'=>$additional_id,'account'=>$add->credit,'money'=>(0-$add->money)));
                            $account_balance_model->updateAccount($data_debit,array('account_balance_id'=>$d->account_balance_id));
                            $account_balance_model->updateAccount($data_credit,array('account_balance_id'=>$c->account_balance_id));
                        }
                    }

                }
                
            }
            $item_olds = $additional_model->queryAdditional('SELECT * FROM additional WHERE daily='.$id_daily_last.' AND additional_id NOT IN ('.$arr_item.')');
            foreach ($item_olds as $item_old) {
                $receivable = $receivable_model->getCosts($item_old->additional_payable);
                $data_receivable = array(
                    'pay_money' => $receivable->pay_money - $item_old->money,
                );
                $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$item_old->additional_payable));
                $assets_model->queryAssets('DELETE FROM assets WHERE additional='.$item_old->daily.' AND receivable = '.$item_old->additional_payable);
                $receive_model->queryCosts('DELETE FROM receive WHERE additional='.$item_old->daily.' AND receivable = '.$item_old->additional_payable);
                $obtain_model->queryObtain('DELETE FROM obtain WHERE additional='.$item_old->daily.' AND receivable = '.$item_old->additional_payable);
                
                $payable = $payable_model->getCosts($item_old->additional_payable);
                $data_payable = array(
                    'pay_money' => $payable->pay_money - $item_old->money,
                );
                $payable_model->updateCosts($data_payable,array('payable_id'=>$item_old->additional_payable));
                $assets_model->queryAssets('DELETE FROM assets WHERE additional='.$item_old->daily.' AND payable = '.$item_old->additional_payable);
                $pay_model->queryCosts('DELETE FROM pay WHERE additional='.$item_old->daily.' AND payable = '.$item_old->additional_payable);
                $owe_model->queryOwe('DELETE FROM owe WHERE additional='.$item_old->daily.' AND payable = '.$item_old->additional_payable);

                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$item_old->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$item_old->additional_id);

                $costs = $costs_model->getCostsByWhere(array('daily_additional'=>$item_old->additional_id));
                $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$costs->costs_id);
                $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$costs->costs_id);
                $costs_model->queryCosts('DELETE FROM costs WHERE costs_id = '.$costs->costs_id);

            }
                    
        }
    }

    public function addorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');

            $additional_date = strtotime(str_replace('/', '-', $_POST['additional_date']));
            $additionals = $_POST['additional'];
            $type = $_POST['type_order'];
            $order_tire = null;
            $tire_sale = null;
            if ($type==1) {
                $order_tire = $_POST['yes'];
            }
            else {
                $tire_sale = $_POST['yes'];
            }

            $arr_item = "";
            foreach ($additionals as $v) {
                $data_additional = array(
                    'document_date' => $additional_date,
                    'additional_date' => $additional_date,
                    'additional_comment' => trim($v['additional_comment']),
                    'debit' => $v['additional_debit'],
                    'credit' => $v['additional_credit'],
                    'money' => str_replace(',','',$v['additional_money']),
                    'code' => trim($v['additional_code']),
                    'order_tire' => $order_tire,
                    'tire_sale' => $tire_sale,
                );

                if ($v['additional_id'] > 0) {
                    $additional_id = $v['additional_id'];
                    $add = $additional_model->getAdditional($additional_id);
                    $additional_model->updateAdditional($data_additional,array('additional_id'=>$v['additional_id']));
                }
                else{
                    if ($data_additional['money'] > 0 && $data_additional['debit'] > 0 && $data_additional['credit'] > 0) {
                        $additional_model->createAdditional($data_additional);
                        $additional_id = $additional_model->getLastAdditional()->additional_id;
                        $add = $additional_model->getAdditional($additional_id);
                    }
                }

                if ($arr_item=="") {
                    $arr_item .= $additional_id;
                }
                else{
                    $arr_item .= ','.$additional_id;
                }

                $data_debit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['debit'],
                    'money' => $data_additional['money'],
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );
                $data_credit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['credit'],
                    'money' => (0-$data_additional['money']),
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );

                if($data_debit['week'] == 53){
                    $data_debit['week'] = 1;
                    $data_debit['year'] = $data_debit['year']+1;

                    $data_credit['week'] = 1;
                    $data_credit['year'] = $data_credit['year']+1;
                }
                if (((int)date('W', $data_additional['additional_date']) == 1) && ((int)date('m', $data_additional['additional_date']) == 12) ) {
                    $data_debit['year'] = (int)date('Y', $data_additional['additional_date'])+1;
                    $data_credit['year'] = (int)date('Y', $data_additional['additional_date'])+1;
                }


                if (!$account_balance_model->getAccountByWhere(array('additional'=>$additional_id))) {
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);
                }
                else{
                    $account_balance_model->updateAccount($data_debit,array('additional'=>$additional_id,'account'=>$add->debit));
                    $account_balance_model->updateAccount($data_credit,array('additional'=>$additional_id,'account'=>$add->credit));
                }
            }

            if ($type==1) {
                $item_olds = $additional_model->queryAdditional('SELECT * FROM additional WHERE order_tire='.$order_tire.' AND additional_id NOT IN ('.$arr_item.')');
                foreach ($item_olds as $item_old) {
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$item_old->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$item_old->additional_id);
                }
            }
            else {
                $item_olds = $additional_model->queryAdditional('SELECT * FROM additional WHERE tire_sale='.$tire_sale.' AND additional_id NOT IN ('.$arr_item.')');
                foreach ($item_olds as $item_old) {
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$item_old->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$item_old->additional_id);
                }
            }
            echo "Cập nhật thành công";
              
        }
    }
    public function getitemaddorder(){
        if (isset($_POST['yes'])) {
            $account_model = $this->model->get('accountModel');
            $additional_model = $this->model->get('additionalModel');
            
            $accounts = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));

            $type = $_POST['type_order'];
            if ($type==1) {
                $additionals = $additional_model->getAllAdditional(array('where'=>'order_tire='.$_POST['yes']));
            }
            else {
                $additionals = $additional_model->getAllAdditional(array('where'=>'tire_sale='.$_POST['yes']));
            }
            

            $str = "";
            $i = 1;
            if ($additionals) {
                foreach ($additionals as $additional) {
                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option '.($account->account_id==$additional->debit?'selected':null).' value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10">';
                    $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                    $str .= '<option >Tài khoản</option>';
                      foreach ($accounts as $account) {
                          $str .= '<option '.($account->account_id==$additional->credit?'selected':null).' value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                      }
                    $str .= '</select>';
                    $str .= '</td>';
                    $str .= '<td class="width-10"><input data="'.$additional->additional_id.'" value="'.$this->lib->formatMoney($additional->money).'" type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                    $str .= '<td><input value="'.$additional->additional_comment.'" type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                    $str .= '<td class="width-10"><input value="'.$additional->code.'" type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"></td>';
                    
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_debit[]" class="additional_debit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10">';
                $str .= '<select name="additional_credit[]" class="additional_credit dropchosen" required="required">';
                $str .= '<option >Tài khoản</option>';
                  foreach ($accounts as $account) {
                      $str .= '<option value="'.$account->account_id.'">'.$account->account_number.' - '.$account->account_name.'</option>';
                  }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '<td class="width-10"><input type="text" name="additional_money[]" class="additional_money numbers text-right" required="required" autocomplete="off"></td>';
                $str .= '<td><input type="text" name="additional_comment[]" class="additional_comment keep-val" required="required" autocomplete="off"></td>';
                $str .= '<td class="width-10"><input type="text" name="additional_code[]" class="additional_code keep-val" autocomplete="off"></td>';
                
                $str .= '</tr>';

            }
            

            $arr = array(
                'hang'=>$str,
            );
            echo json_encode($arr);
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
            $deposit_model = $this->model->get('deposittireModel');
            $payment_request_model = $this->model->get('paymentrequestModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $dailys = $daily_model->getDaily($data);
                    if ($dailys->payment_request>0) {
                        $payment_requests = $payment_request_model->getPayment($dailys->payment_request);
                        $payment_request_model->updatePayment(array('payment_request_pay_money'=>$payment_requests->payment_request_pay_money-$dailys->money_out),array('payment_request_id'=>$dailys->payment_request));
                    }
                    
            
                       $daily_model->deleteDaily($data);
                       $daily_bank_model->queryDaily('DELETE FROM daily_bank WHERE daily = '.$data);

                       $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$data));
                       foreach ($additionals as $add) {
                            $receivable = $receivable_model->getCosts($add->additional_payable);
                            $data_receivable = array(
                                'pay_money' => $receivable->pay_money - $add->money,
                            );
                            $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$add->additional_payable));
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional='.$add->daily.' AND receivable = '.$add->additional_payable);
                            $receive_model->queryCosts('DELETE FROM receive WHERE additional='.$add->daily.' AND receivable = '.$add->additional_payable);
                            $obtain_model->queryObtain('DELETE FROM obtain WHERE additional='.$add->daily.' AND receivable = '.$add->additional_payable);
                            
                            $payable = $payable_model->getCosts($add->additional_payable);
                            $data_payable = array(
                                'pay_money' => $payable->pay_money - $add->money,
                            );
                            $payable_model->updateCosts($data_payable,array('payable_id'=>$add->additional_payable));
                            $assets_model->queryAssets('DELETE FROM assets WHERE additional='.$add->daily.' AND payable = '.$add->additional_payable);
                            $pay_model->queryCosts('DELETE FROM pay WHERE additional='.$add->daily.' AND payable = '.$add->additional_payable);
                            $owe_model->queryOwe('DELETE FROM owe WHERE additional='.$add->daily.' AND payable = '.$add->additional_payable);

                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }

                        $id_receivable = $receive_model->getAllCosts(array('where'=>'receivable > 0 AND additional = '.$data));
                        foreach ($id_receivable as $re) {
                            $receivable = $receivable_model->getCostsByWhere(array('receivable_id'=>$re->receivable));
                            $pay_money = $receivable->pay_money-$re->money;
                            $receivable_model->updateCosts(array('pay_money'=>$pay_money),array('receivable_id'=>$re->receivable));
                        }
                        $id_payable = $pay_model->getAllCosts(array('where'=>'payable > 0 AND additional = '.$data));
                        foreach ($id_payable as $pay) {
                            $payable = $payable_model->getCostsByWhere(array('payable_id'=>$pay->payable));
                            $pay_money = $payable->pay_money-$pay->money;
                            $payable_model->updateCosts(array('pay_money'=>$pay_money),array('payable_id'=>$pay->payable));
                        }

                        $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$data);
                        $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$data);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$data);
                        $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$data);
                        $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$data);
                        $costs_model->queryCosts('DELETE FROM costs WHERE additional = '.$data);
                        $deposit_model->queryDeposit('DELETE FROM deposit_tire WHERE daily = '.$data);
                       
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
                    $dailys = $daily_model->getDaily($_POST['data']);
                    if ($dailys->payment_request>0) {
                        $payment_requests = $payment_request_model->getPayment($dailys->payment_request);
                        $payment_request_model->updatePayment(array('payment_request_pay_money'=>$payment_requests->payment_request_pay_money-$dailys->money_out),array('payment_request_id'=>$dailys->payment_request));
                    }

                        $daily_model->deleteDaily($_POST['data']);
                        $daily_bank_model->queryDaily('DELETE FROM daily_bank WHERE daily = '.$_POST['data']);

                        $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$_POST['data']));
                       foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }

                        $id_receivable = $receive_model->getAllCosts(array('where'=>'receivable > 0 AND additional = '.$_POST['data']));
                        foreach ($id_receivable as $re) {
                            $receivable = $receivable_model->getCostsByWhere(array('receivable_id'=>$re->receivable));
                            $pay_money = $receivable->pay_money-$re->money;
                            $receivable_model->updateCosts(array('pay_money'=>$pay_money),array('receivable_id'=>$re->receivable));
                        }
                        $id_payable = $pay_model->getAllCosts(array('where'=>'payable > 0 AND additional = '.$_POST['data']));
                        foreach ($id_payable as $pay) {
                            $payable = $payable_model->getCostsByWhere(array('payable_id'=>$pay->payable));
                            $pay_money = $payable->pay_money-$pay->money;
                            $payable_model->updateCosts(array('pay_money'=>$pay_money),array('payable_id'=>$pay->payable));
                        }
                        
                        $assets_model->queryAssets('DELETE FROM assets WHERE additional = '.$_POST['data']);
                        $receive_model->queryCosts('DELETE FROM receive WHERE additional = '.$_POST['data']);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE additional = '.$_POST['data']);
                        $pay_model->queryCosts('DELETE FROM pay WHERE additional = '.$_POST['data']);
                        $owe_model->queryOwe('DELETE FROM owe WHERE additional = '.$_POST['data']);
                        $costs_model->queryCosts('DELETE FROM costs WHERE additional = '.$_POST['data']);
                        $deposit_model->queryDeposit('DELETE FROM deposit_tire WHERE daily = '.$_POST['data']);

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
                        $service = ($service=="Hành chính" || $service=="hành chính" || substr($service, -1)=="h" )?1:(($service=="Lốp xe" || $service=="lốp xe" || substr($service, -1)=="e")?2:(($service=="Logistics" || $service=="logistics" || substr($service, -1)=="s" || substr($service, -1)=="c")?3:null));

                        if (BASE_URL == "http://viet-trade.org" || BASE_URL == "http://www.viet-trade.org" || BASE_URL == "https://www.viet-trade.org" || BASE_URL == "https://viet-trade.org") {
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

    function export(){

        $this->view->disableLayout();

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        $batdau = $this->registry->router->param_id;
        $ketthuc = $this->registry->router->page;

        
        $additional_model = $this->model->get('additionalModel');

        $daily_model = $this->model->get('dailyModel');

        $data = array(
            'where' => 'daily_date >= '.$batdau.' AND daily_date <= '.$ketthuc,
        );

        $dailys = $daily_model->getAllDaily($data);


            require("lib/Classes/PHPExcel/IOFactory.php");

            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();


            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

               ->setCellValue('A1', 'STT')

               ->setCellValue('B1', 'Code')

               ->setCellValue('C1', 'Nội dung')

               ->setCellValue('D1', 'Thu')

               ->setCellValue('E1', 'Chi')

               ->setCellValue('F1', 'Dịch vụ')

               ->setCellValue('G1', 'Người nhận')

               ->setCellValue('H1', 'Ghi chú')

               ->setCellValue('I1', 'Tài khoản');

               

            if ($dailys) {

                $hang = 2;

                $i=1;

                foreach ($dailys as $daily) {
                    
                $additionals = $additional_model->getAllAdditional(array('where'=>'daily='.$daily->daily_id));

                foreach ($additionals as $additional) {
                   
                    if ($daily->internal_transfer==1) {
                        $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $additional->code)

                            ->setCellValue('C' . $hang, $additional->additional_comment)

                            ->setCellValue('D' . $hang, ($daily->money_in>0?$additional->money:0))

                            ->setCellValue('E' . $hang, 0)

                            ->setCellValue('F' . $hang, ($additional->additional_service==1?'Hành chính':($additional->additional_service==2?'Lốp xe':'Logistics')))

                            ->setCellValue('G' . $hang, $daily->owner)

                            ->setCellValue('H' . $hang, $daily->note)

                            ->setCellValue('I' . $hang, $daily->account);

                        $hang++;

                        $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $additional->code)

                            ->setCellValue('C' . $hang, $additional->additional_comment)

                            ->setCellValue('D' . $hang, 0)

                            ->setCellValue('E' . $hang, ($daily->money_out>0?$additional->money:0))

                            ->setCellValue('F' . $hang, ($additional->additional_service==1?'Hành chính':($additional->additional_service==2?'Lốp xe':'Logistics')))

                            ->setCellValue('G' . $hang, $daily->owner)

                            ->setCellValue('H' . $hang, $daily->note)

                            ->setCellValue('I' . $hang, $daily->account_out);

                            $hang++;
                    }
                    else{
                        $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $additional->code)

                            ->setCellValue('C' . $hang, $additional->additional_comment)

                            ->setCellValue('D' . $hang, ($daily->money_in>0?$additional->money:0))

                            ->setCellValue('E' . $hang, ($daily->money_out>0?$additional->money:0))

                            ->setCellValue('F' . $hang, ($additional->additional_service==1?'Hành chính':($additional->additional_service==2?'Lốp xe':'Logistics')))

                            ->setCellValue('G' . $hang, $daily->owner)

                            ->setCellValue('H' . $hang, $daily->note)

                            ->setCellValue('I' . $hang, $daily->account);

                            $hang++;
                    }

                  }

              }

          }

          $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('C'.$hang, 'Tổng cộng')


               ->setCellValue('D'.$hang, '=SUM(D2:D'.($hang-1).')')

               ->setCellValue('E'.$hang, '=SUM(E2:E'.($hang-1).')');


            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



            $highestRow ++;


            $objPHPExcel->getActiveSheet()->getStyle('A1:I'.$hang)->applyFromArray(

                array(

                    

                    'borders' => array(

                        'allborders' => array(

                          'style' => PHPExcel_Style_Border::BORDER_THIN

                        )

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':I'.$hang)->getFont()->setBold(true);


            $objPHPExcel->getActiveSheet()->getStyle('D2:E'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);



            // Set properties

            $objPHPExcel->getProperties()->setCreator("Viet Trade")

                            ->setLastModifiedBy($_SESSION['user_logined'])

                            ->setTitle("Daily Report")

                            ->setSubject("Daily Report")

                            ->setDescription("Daily Report.")

                            ->setKeywords("Daily Report")

                            ->setCategory("Daily Report");

            $objPHPExcel->getActiveSheet()->setTitle(date('d.m.Y',$batdau));



            $objPHPExcel->getActiveSheet()->freezePane('A2');

            $objPHPExcel->setActiveSheetIndex(0);



            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            header("Content-Disposition: attachment; filename= BAO CAO THU CHI.xlsx");

            header("Cache-Control: max-age=0");

            ob_clean();

            $objWriter->save("php://output");

        

    }

}
?>