<?php
Class costsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thu chi';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = "";
            $ketthuc = "";
            $trangthai = 0;
        }

        $id = $this->registry->router->param_id;
        

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $this->view->data['staffs'] = $staffs;

        $secs = $this->model->get('secsModel');
        $sec = $secs->getAllCosts();
        $sec_data = array();
        foreach ($sec as $s) {
            $sec_data[$s->secs_id]['name'] = $s->secs_name;
        }
        $this->view->data['secs'] = $sec_data;


        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $join = array('table'=>'bank','where'=>'bank.bank_id = costs.source');

        $costs_model = $this->model->get('costsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }

        if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money AND (pay_date is not null AND pay_date > 0)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <='.strtotime($ketthuc);
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money OR pay_date is null OR pay_date = 0)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <='.strtotime($ketthuc);
                }
            }
        
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
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }


        if ($trangthai==1) {
                $data['where'] .= ' AND pay_money = money AND (pay_date is not null AND pay_date > 0)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <='.strtotime($ketthuc);
                }
            }
            else{
                $data['where'] .= ' AND (pay_money is null OR pay_money != money OR pay_date is null OR pay_date = 0)';
                if ($batdau != "" && $ketthuc != "") {
                    $data['where'] .= ' AND expect_date >= '.strtotime($batdau).' AND expect_date <='.strtotime($ketthuc);
                }
            }
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR money_in LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $nam = date('Y');
        $ngay = date('d-m-Y');
        $batdau = (int)date('W',strtotime($ngay));
        
        
        $tongtra = $costs_model->queryCosts('SELECT SUM(money) AS tongtra FROM costs WHERE week='.$batdau.' AND year ='.$nam);
        $tongdatra = $costs_model->queryCosts('SELECT SUM(money) AS tongdatra FROM pay WHERE costs IS NOT NULL AND week='.$batdau.' AND year ='.$nam);
        foreach ($tongtra as $tra) {
            $tongtra = $tra->tongtra;
        }
        foreach ($tongdatra as $tra) {
            $tongdatra = $tra->tongdatra;
        }

        $con = $costs_model->queryCosts('SELECT money, pay_money FROM costs WHERE week <='.$batdau.' AND year <='.$nam);
        foreach ($con as $conlai) {
            $tongtra += $conlai->money-$conlai->pay_money;
        }

        $this->view->data['tongtratuan'] = $tongtra;
        $this->view->data['tongdatratuan'] = $tongdatra;

        $tongtra = $costs_model->queryCosts('SELECT SUM(money) AS tongtra FROM costs ');
        $tongdatra = $costs_model->queryCosts('SELECT SUM(pay_money) AS tongdatra FROM costs ');
        foreach ($tongtra as $tra) {
            $tongtra = $tra->tongtra;
        }
        foreach ($tongdatra as $tra) {
            $tongdatra = $tra->tongdatra;
        }
        $this->view->data['tongtra'] = $tongtra;
        $this->view->data['tongdatra'] = $tongdatra;

        
        $this->view->data['costs'] = $costs_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($costs_model->getLastCosts()->costs_id)?$costs_model->getLastCosts()->costs_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('costs/index');
    }

    public function getsec(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sec_model = $this->model->get('secsModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $sec_model->getAllCosts();
            }
            else{
                $data = array(
                'where'=>'( secs_name LIKE "%'.$_POST['keyword'].'%")',
                );
                $list = $sec_model->getAllCosts($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $sec_name = $rs->secs_name;
                if ($_POST['keyword'] != "*") {
                    $sec_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->secs_name);
                }
                
                // add new option
                echo '<li onclick="set_item_sec(\''.$rs->secs_name.'\',\''.$rs->secs_id.'\')">'.$sec_name.'</li>';
            }
        }
    }

    public function pending(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('costsModel');
            //$costs_data = $costs->getCosts($_POST['data']);
            $p = $_POST['val']==1?0:1;

            $data = array(
                        
                        'pending' => $p,
                        );
          
            $costs->updateCosts($data,array('costs_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pending"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

   
    public function approve(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('costsModel');
            //$costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
          
            $costs->updateCosts($data,array('costs_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function approve2(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('costsModel');
            //$costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve2' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('costs_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve2"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function pay(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('costsModel');
            $costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'pay_date' => strtotime(trim($_POST['pay_date'])),
                        'source' => $_POST['source'],
                        'pay_money' => $costs_data->pay_money + trim(str_replace(',','',$_POST['money'])),
                        );
          
            $costs->updateCosts($data,array('costs_id' => $_POST['data']));

            $assets_model = $this->model->get('assetsModel');

            if($data['pay_money'] != 0){
                $data_asset = array(
                            'bank' => $data['source'],
                            'total' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'assets_date' => $data['pay_date'],
                            'costs' => $_POST['data'],
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
            }

            if ($_POST['source_in'] > 0) {
                $data_asset = array(
                        'bank' => $_POST['source_in'],
                        'total' => trim(str_replace(',','',$_POST['money_in'])),
                        'assets_date' => $data['pay_date'],
                        'costs' => $_POST['data'],
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
            }

            $pay_model = $this->model->get('payModel');
            $data_pay = array(
                        'source' => $data['source'],
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'pay_date' => $data['pay_date'],
                        'costs' => $_POST['data'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                    );
            if($data_pay['week'] == 53){
                $data_pay['week'] = 1;
                $data_pay['year'] = $data_pay['year']+1;
            }
            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $pay_model->createCosts($data_pay);

            if($costs_data->staff > 0 && $costs_data->staff_cost > 0){

                $staff_debt_model = $this->model->get('staffdebtModel');
                $data_staff_debt = array(
                            'staff' => $costs_data->staff,
                            'source' => $data['source'],
                            'money' => $costs_data->staff_cost,
                            'staff_debt_date' => $data['pay_date'],
                            'comment' => $costs_data->comment,
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

                $receivable = $this->model->get('receivableModel');
                $data_receivable = array(
                    'staff' => $costs_data->staff,
                    'money' => $costs_data->staff_cost,
                    'receivable_date' => $data['pay_date'],
                    'expect_date' => $data['pay_date'],
                    'week' => (int)date('W',$data['pay_date']),
                    'comment' => $costs_data->comment,
                    'create_user' => $_SESSION['userid_logined'],
                    'year' => (int)date('Y',$data['pay_date']),
                    'type' => 5,
                    'source' => $data['source'],
                );

                if($data_receivable['week'] == 53){
                    $data_receivable['week'] = 1;
                    $data_receivable['year'] = $data_receivable['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_receivable['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $receivable->createCosts($data_receivable);
            }

            if ($costs_data->lender > 0) {
                $payable = $this->model->get('payableModel');
                $owe = $this->model->get('oweModel');

                    $data_asset = array(
                            'bank' => $data['source'],
                            'total' => $data['pay_money'],
                            'assets_date' => $data['pay_date'],
                            'costs' => (0-$_POST['data']),
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

                    $assets_model->createAssets2($data_asset);

                    $data_asset = array(
                            'bank' => $data['source'],
                            'total' => 0-$data['pay_money'],
                            'assets_date' => $data['pay_date'],
                            'costs' => $_POST['data'],
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

                    $assets_model->createAssets3($data_asset);

                    $data_costs = array(
                        'costs_create_date' => $data['pay_date'],
                        'costs_date' => $data['pay_date'],
                        'comment' => 'CMG tạm ứng',
                        'money' => $data['pay_money'],
                        'expect_date' => $data['pay_date'],
                        'week' => (int)date('W', $data['pay_date']),
                        'create_user' => $_SESSION['userid_logined'],
                        'source' => $data['source'],
                        'year' => (int)date('Y', $data['pay_date']),
                        'staff' => 29,
                        'staff_cost' => $data['pay_money'],
                        'pay_money' => $data['pay_money'],
                        'pay_date' => $data['pay_date'],
                        'check_office' => 1,
                        'check_other' => 1,
                        );
                    if($data_costs['week'] == 53){
                        $data_costs['week'] = 1;
                        $data_costs['year'] = $data_costs['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_costs['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $costs->createCosts3($data_costs);



                    $owe_data = array(
                        'owe_date' => $data['pay_date'],
                        'vendor' => 148,
                        'money' => $data['pay_money'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                        'costs' => $_POST['data'],
                    );

                    if($owe_data['week'] == 53){
                        $owe_data['week'] = 1;
                        $owe_data['year'] = $owe_data['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $owe_data['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $owe->createOwe2($owe_data);

                    $payable_data = array(
                        'vendor' => 148,
                        'money' => $data['pay_money'],
                        'payable_date' => $data['pay_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data['pay_date'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                        'code' => null,
                        'source' => $data['source'],
                        'comment' => $costs_data->comment,
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 5,
                        'costs' => $_POST['data'],
                        'cost_type' => 7,
                        'check_vat' => 0,
                        'approve' => 1,
                        'approve2' => 1,
                    );

                    if($payable_data['week'] == 53){
                        $payable_data['week'] = 1;
                        $payable_data['year'] = $payable_data['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $payable_data['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $payable->createCosts2($payable_data);

                    $staff_debt_model = $this->model->get('staffdebtModel');
                    $data_staff_debt = array(
                                'staff' => 29,
                                'source' => $data['source'],
                                'money' => $data['pay_money'],
                                'staff_debt_date' => $data['pay_date'],
                                'comment' => $costs_data->comment,
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

                    $staff_debt_model->createCost3($data_staff_debt);

                    $receivable = $this->model->get('receivableModel');
                    $data_receivable = array(
                        'staff' => 29,
                        'money' => $data['pay_money'],
                        'receivable_date' => $data['pay_date'],
                        'expect_date' => $data['pay_date'],
                        'week' => (int)date('W',$data['pay_date']),
                        'comment' => $costs_data->comment,
                        'create_user' => $_SESSION['userid_logined'],
                        'year' => (int)date('Y',$data['pay_date']),
                        'type' => 5,
                        'source' => $data['source'],
                    );

                    if($data_receivable['week'] == 53){
                        $data_receivable['week'] = 1;
                        $data_receivable['year'] = $data_receivable['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_receivable['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $receivable->createCosts3($data_receivable);
            }

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pay"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            $agent_model = $this->model->get('agentModel');
            $agent_manifest_model = $this->model->get('agentmanifestModel');
            $invoice_model = $this->model->get('invoiceModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $sale_report_model->getAllSale();
                $list_agent = $agent_model->getAllAgent();
                $list_agentmanifest = $agent_manifest_model->getAllAgent();
                $list_invoice = $invoice_model->getAllInvoice();
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
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $assets_model = $this->model->get('assetsModel');
            $costs = $this->model->get('costsModel');
            $data = array(
                        'costs_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'costs_date' => strtotime(date('d-m-Y')),
                        'comment' => trim($_POST['comment']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'week' => (int)date('W', strtotime(trim($_POST['expect_date']))),
                        'create_user' => $_SESSION['userid_logined'],
                        'source' => trim($_POST['source']),
                        'source_in' => trim($_POST['source_in']),
                        'year' => (int)date('Y', strtotime(trim($_POST['expect_date']))),
                        'staff' => trim($_POST['staff']),
                        'staff_cost' => trim(str_replace(',','',$_POST['staff_cost'])),
                        'pay_money' => trim(str_replace(',','',$_POST['pay_money'])),
                        'pay_date' => strtotime($_POST['pay_date']),
                        'money_in' => trim(str_replace(',','',$_POST['money_in'])),
                        'code' => trim($_POST['code']),
                        'check_office' => trim($_POST['check_office']),
                        'check_salary' => trim($_POST['check_salary']),
                        'check_phone' => trim($_POST['check_phone']),
                        'check_equipment' => trim($_POST['check_equipment']),
                        'check_bonus' => trim($_POST['check_bonus']),
                        'check_entertainment' => trim($_POST['check_entertainment']),
                        'check_energy' => trim($_POST['check_energy']),
                        'check_insurance' => trim($_POST['check_insurance']),
                        'check_eating' => trim($_POST['check_eating']),
                        'check_other' => trim($_POST['check_other']),
                        'agent_estimate' => trim($_POST['agent_estimate']),
                        'tcmt_estimate' => trim($_POST['tcmt_estimate']),
                        'sale_estimate' => trim($_POST['sale_estimate']),
                        'trading_estimate' => trim($_POST['trading_estimate']),
                        'check_invoice' => trim($_POST['check_invoice']),
                        'check_document' => trim($_POST['check_document']),
                        'check_pay' => trim($_POST['check_pay']),
                        'invoice_balance' => trim($_POST['invoice_balance']),
                        'invoice_month' => strtotime('01-'.trim($_POST['invoice_month'])),
                        'lender' => trim($_POST['lender']),
                        'check_sec' => trim($_POST['check_sec']),
                        'quarter' => trim($_POST['quarter']),
                        'quarter_year' => trim($_POST['quarter_year']),
                        );

            if (($data['money_in'] > 0 && $data['money'] == 0) || $data['lender'] > 0 || ($data['money_in'] == $data['money']) ) {
                $data['approve'] = 1;
                $data['approve2'] = 1;
                $data['approve3'] = 1;
            }
            
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', strtotime(trim($_POST['expect_date']))) == 1) && ((int)date('m', strtotime(trim($_POST['expect_date']))) == 12) ) {
                $data['year'] = (int)date('Y', strtotime(trim($_POST['expect_date'])))+1;
            }

            if (trim($_POST['sec_name']) != "") {
                if (trim($_POST['sec']) != "") {
                    $data['sec'] = trim($_POST['sec']);
                }
                else{
                    $secs = $this->model->get('secsModel');
                    $data_sec = array(
                        'secs_name' => trim($_POST['sec_name']),
                        'secs_date' => $data['expect_date'],
                        'secs_bank' => $data['source'],
                        'secs_money' => $data['money'],

                    );

                    $secs->createCosts($data_sec);
                    $data['sec'] = $secs->getLastCosts()->secs_id;
                }
            }

            if ($_POST['yes'] != "") {
                
                $data['approve'] = null;

                    $costs_data = $costs->getCosts($_POST['yes']);

                $receivable = $this->model->get('receivableModel');
                $staff_debt_model = $this->model->get('staffdebtModel');

                if ($costs_data->money >= $data['money'] && $costs_data->approve > 0) {
                    $data['approve'] = 10;
                }

                if ($costs_data->lender > 0) {
                    $owe = $this->model->get('oweModel');
                    $payable = $this->model->get('payableModel');
                    if ($data['lender'] != 1) {
                        $assets_model->queryAssets2('DELETE FROM assets WHERE total='.$costs_data->pay_money.' AND costs='.(0-$_POST['yes']));
                        $owe->queryOwe2('DELETE FROM owe WHERE costs='.$_POST['yes']);
                        $payable->queryCosts2('DELETE FROM payable WHERE costs='.$_POST['yes']);
                        $costs->queryCosts3('DELETE FROM costs WHERE staff=29 AND costs_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                        $assets_model->queryAssets3('DELETE FROM assets WHERE total='.(0-$costs_data->pay_money).' AND costs='.$_POST['yes']);
                        $receivable->queryCosts3('DELETE FROM receivable WHERE staff=29 AND receivable_date='.$costs_data->pay_date.'  AND money='.$costs_data->pay_money);
                        $staff_debt_model->queryCost3('DELETE FROM staff_debt WHERE staff=29 AND staff_debt_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                    }
                    else{

                        $data_asset = array(
                            'bank' => $data['source'],
                            'total' => $data['pay_money'],
                            'assets_date' => $data['pay_date'],
                            'costs' => (0-$_POST['data']),
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

                        $assets_model->updateAssets2($data_asset,array('costs'=>$_POST['yes'],'total'=>$costs_data->pay_money));

                        $data_asset = array(
                            'bank' => $data['source'],
                            'total' => (0-$data['pay_money']),
                            'assets_date' => $data['pay_date'],
                            'costs' => $_POST['data'],
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

                        $assets_model->updateAssets3($data_asset,array('costs'=>$_POST['yes'],'total'=>(0-$costs_data->pay_money)));

                        $data_costs = array(
                            'costs_create_date' => $data['pay_date'],
                            'costs_date' => $data['pay_date'],
                            'comment' => 'CMG tạm ứng',
                            'money' => $data['pay_money'],
                            'expect_date' => $data['pay_date'],
                            'week' => (int)date('W', $data['pay_date']),
                            'create_user' => $_SESSION['userid_logined'],
                            'source' => $data['source'],
                            'year' => (int)date('Y', $data['pay_date']),
                            'staff' => 29,
                            'staff_cost' => $data['pay_money'],
                            'pay_money' => $data['pay_money'],
                            'pay_date' => $data['pay_date'],
                            'check_office' => 1,
                            'check_other' => 1,
                            );
                        if($data_costs['week'] == 53){
                            $data_costs['week'] = 1;
                            $data_costs['year'] = $data_costs['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $data_costs['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        $costs->updateCosts3($data_costs,array('staff'=>29,'costs_date'=>$costs_data->pay_date,'money'=>$costs_data->pay_money));

                        $owe_data = array(
                            'owe_date' => $data['pay_date'],
                            'vendor' => 148,
                            'money' => $data['pay_money'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'costs' => $_POST['yes'],
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        $owe->updateOwe2($owe_data,array('costs'=>$_POST['yes'],'money'=>$costs_data->pay_money));

                        $payable_data = array(
                            'vendor' => 148,
                            'money' => $data['pay_money'],
                            'payable_date' => $data['pay_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'code' => null,
                            'source' => $data['source'],
                            'comment' => $costs_data->comment,
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 5,
                            'costs' => $_POST['yes'],
                            'cost_type' => 7,
                            'check_vat' => 0,
                            'approve' => 1,
                            'approve2' => 1,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        $payable->updateCosts2($payable_data,array('costs'=>$_POST['yes'],'money'=>$costs_data->pay_money));

                        
                        $data_staff_debt = array(
                                    'staff' => 29,
                                    'source' => $data['source'],
                                    'money' => $data['pay_money'],
                                    'staff_debt_date' => $data['pay_date'],
                                    'comment' => $costs_data->comment,
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

                        $staff_debt_model->updateCost3($data_staff_debt,array('staff'=>29,'money'=>$costs_data->pay_money,'staff_debt_date'=>$costs_data->pay_date));

                        $data_receivable = array(
                            'staff' => 29,
                            'money' => $data['pay_money'],
                            'receivable_date' => $data['pay_date'],
                            'expect_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'comment' => $costs_data->comment,
                            'create_user' => $_SESSION['userid_logined'],
                            'year' => (int)date('Y',$data['pay_date']),
                            'type' => 5,
                            'source' => $data['source'],
                        );

                        if($data_receivable['week'] == 53){
                            $data_receivable['week'] = 1;
                            $data_receivable['year'] = $data_receivable['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $data_receivable['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        $receivable->updateCosts3($data_receivable,array('staff'=>29,'money'=>$costs_data->pay_money,'receivable_date'=>$costs_data->pay_date));
                    }
                }

                    if ($data['source_in'] != $costs_data->source_in) {
                        $data_asset = array(
                                    'bank' => $data['source_in'],
                                );
                        $assets_model->updateAssets($data_asset,array('costs'=>$_POST['yes'],'bank'=>$costs_data->source_in,'total'=>$costs_data->money_in));
                    }

                    if ($data['money_in'] > 0 && $data['money_in'] != $costs_data->money_in && $costs_data->pay_date > 0 ) {
                        if ($_POST['source_in'] != "") {
                            
                            
                                $data_asset = array(
                                    'bank' => $data['source_in'],
                                    'total' => $data['money_in']-$costs_data->money_in,
                                    'assets_date' => $costs_data->pay_date,
                                    'costs' => $_POST['yes'],
                                    'week' => (int)date('W',$costs_data->pay_date),
                                    'year' => (int)date('Y',$costs_data->pay_date),
                                );
                                if($data_asset['week'] == 53){
                                    $data_asset['week'] = 1;
                                    $data_asset['year'] = $data_asset['year']+1;
                                }
                                if (((int)date('W',$costs_data->pay_date) == 1) && ((int)date('m',$costs_data->pay_date) == 12) ) {
                                    $data_asset['year'] = (int)date('Y',$costs_data->pay_date)+1;
                                }

                                $assets_model->createAssets($data_asset);
                            
                        }
                    }

                    if($data['pay_date'] != $costs_data->pay_date){
                        $data_asset = array(
                                    'assets_date' => $data['pay_date'],
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

                        $assets_model->updateAssets($data_asset,array('costs'=>$_POST['yes'],'assets_date'=>$costs_data->pay_date,'total'=>(0-$costs_data->pay_money)));
                    }

                    if($data['pay_money'] > 0 && $data['pay_money'] != $costs_data->pay_money){
                       

                        
                        $data_asset = array(
                                    'bank' => $costs_data->source,
                                    'total' => (0 - $data['pay_money']),
                                    'assets_date' => $costs_data->pay_date,
                                    'costs' => $_POST['yes'],
                                    'week' => (int)date('W',$costs_data->pay_date),
                                    'year' => (int)date('Y',$costs_data->pay_date),
                                );
                        if($data_asset['week'] == 53){
                            $data_asset['week'] = 1;
                            $data_asset['year'] = $data_asset['year']+1;
                        }
                        if (((int)date('W',$costs_data->pay_date) == 1) && ((int)date('m',$costs_data->pay_date) == 12) ) {
                            $data_asset['year'] = (int)date('Y',$costs_data->pay_date)+1;
                        }

                        $assets_model->updateAssets($data_asset,array('costs'=>$_POST['yes'],'assets_date'=>$costs_data->pay_date,'total'=>(0-$costs_data->pay_money)));

                        

                        $pay_model = $this->model->get('payModel');
                        $data_pay = array(
                                    
                                    'money' => $data['pay_money'],
                                    
                                );
                        

                        $pay_model->updateCosts($data_pay,array('costs'=>$_POST['yes']));

                        if($costs_data->staff > 0 && $costs_data->staff_cost > 0 && $costs_data->staff==$data['staff'] && $data['staff_cost'] != $costs_data->staff_cost){

                            
                            $data_staff_debt = array(
                                        'staff' => $costs_data->staff,
                                        'source' => $costs_data->source,
                                        'money' => $data['staff_cost']-$costs_data->staff_cost,
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $costs_data->comment,
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
                        else if($costs_data->staff > 0 && $costs_data->staff_cost > 0 && $costs_data->staff != $data['staff'] ){

                            
                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => $data['source'],
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $data['comment'],
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

                            $staff_debt_model->updateCost($data_staff_debt,array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'staff_debt_date'=>$costs_data->pay_date));
                        }
                        else if($costs_data->staff != $data['staff'] && $data['staff'] > 0 && $costs_data->staff <= 0 ){

                            
                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => $data['source'],
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $data['comment'],
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
                        else if($costs_data->staff != $data['staff'] && $data['staff'] <= 0 && $costs_data->staff > 0 ){

                            
                            
                            $staff_debt_model->queryCost('DELETE FROM staff_debt WHERE staff='.$costs_data->staff.' AND money='.$costs_data->staff_cost.' AND staff_debt_date='.$costs_data->pay_date);
                        }




                        
                        $data_receivable = array(
                            'staff' => $data['staff'],
                            'money' => $data['staff_cost'],
                            'receivable_date' => $data['pay_date'],
                            'expect_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'year' => (int)date('Y',$data['pay_date']),
                            'type' => 5,
                            'source' => $data['source'],
                            'pay_money' => $data['pay_money'],
                            
                        );

                        if($data_receivable['week'] == 53){
                            $data_receivable['week'] = 1;
                            $data_receivable['year'] = $data_receivable['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $data_receivable['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        if ($receivable->getCostsByWhere(array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'receivable_date'=>$costs_data->pay_date))) {
                            if ($data['staff_cost'] > 0) {
                                $receivable->updateCosts($data_receivable,array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'receivable_date'=>$costs_data->pay_date));
                            }
                            else{
                                $receivable->queryCosts('DELETE FROM receivable WHERE staff='.$costs_data->staff.' AND money='.$costs_data->staff_cost.' AND receivable_date='.$costs_data->pay_date);
                            }
                        }
                        else{
                            if ($data['staff_cost'] > 0) {
                                $receivable->createCosts($data_receivable);
                            }
                        }

                        
                    }

                    else if(($costs_data->staff != $data['staff'] || $costs_data->staff_cost != $data['staff_cost']) && $costs_data->pay_money > 0){
                        if($costs_data->staff > 0 && $costs_data->staff_cost > 0 && $costs_data->staff==$data['staff'] && $data['staff_cost'] != $costs_data->staff_cost){

                            
                            $data_staff_debt = array(
                                        'staff' => $costs_data->staff,
                                        'source' => $costs_data->source,
                                        'money' => $data['staff_cost']-$costs_data->staff_cost,
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $costs_data->comment,
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
                        else if($costs_data->staff > 0 && $costs_data->staff_cost > 0 && $costs_data->staff != $data['staff'] ){

                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => $data['source'],
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $data['comment'],
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

                            $staff_debt_model->updateCost($data_staff_debt,array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'staff_debt_date'=>$costs_data->pay_date));
                        }
                        else if($costs_data->staff != $data['staff'] ){

                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => $data['source'],
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $data['comment'],
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


                        $data_receivable = array(
                            'staff' => $data['staff'],
                            'money' => $data['staff_cost'],
                            'receivable_date' => $data['pay_date'],
                            'expect_date' => $data['pay_date'],
                            'week' => (int)date('W',$data['pay_date']),
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'year' => (int)date('Y',$data['pay_date']),
                            'type' => 5,
                            'source' => $data['source'],
                            'pay_money' => $data['pay_money'],
                            
                        );

                        if($data_receivable['week'] == 53){
                            $data_receivable['week'] = 1;
                            $data_receivable['year'] = $data_receivable['year']+1;
                        }
                        if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                            $data_receivable['year'] = (int)date('Y',$data['pay_date'])+1;
                        }

                        if ($receivable->getCostsByWhere(array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'receivable_date'=>$costs_data->pay_date))) {
                            if ($data['staff_cost'] > 0) {
                                $receivable->updateCosts($data_receivable,array('staff'=>$costs_data->staff,'money'=>$costs_data->staff_cost,'receivable_date'=>$costs_data->pay_date));
                            }
                            else{
                                $receivable->queryCosts('DELETE FROM receivable WHERE staff='.$costs_data->staff.' AND money='.$costs_data->staff_cost.' AND receivable_date='.$costs_data->pay_date);
                            }
                        }
                        else{
                            if ($data['staff_cost'] > 0) {
                                $receivable->createCosts($data_receivable);
                            }
                        }
                    }
                    else {
                        if($costs_data->staff <= 0 && $costs_data->staff != $data['staff'] ){

                            $data_staff_debt = array(
                                        'staff' => $data['staff'],
                                        'source' => $data['source'],
                                        'money' => $data['staff_cost'],
                                        'staff_debt_date' => $data['pay_date'],
                                        'comment' => $data['comment'],
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
                    }

                    

                    if ($data['source'] != $costs_data->source) {
                        $data_asset = array(
                                    'bank' => $data['source'],
                                );
                        $assets_model->updateAssets($data_asset,array('costs'=>$_POST['yes'],'bank'=>$costs_data->source,'total'=>(0-$costs_data->money)));
                    }

                    $costs->updateCosts($data,array('costs_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|costs|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $costs->createCosts($data);
                    echo "Thêm thành công";
              

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$costs->getLastCosts()->costs_id."|costs|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $costs = $this->model->get('costsModel');
            $assets_model = $this->model->get('assetsModel');
            $pay_model = $this->model->get('payModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $costs_data = $costs->getCosts($data);

                       $costs->deleteCosts($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$data);
                       $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$data);

                       if($costs_data->staff > 0 && $costs_data->staff_cost > 0 ){

                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $data_staff_debt = array(
                                        'staff' => $costs_data->staff,
                                        'source' => $costs_data->source,
                                        'money' => 0-$costs_data->staff_cost,
                                        'staff_debt_date' => $costs_data->pay_date,
                                        'comment' => $costs_data->comment,
                                        'week' => (int)date('W',$costs_data->pay_date),
                                        'year' => (int)date('Y',$costs_data->pay_date),
                                        'status' => 1,
                                    );
                            if($data_staff_debt['week'] == 53){
                                $data_staff_debt['week'] = 1;
                                $data_staff_debt['year'] = $data_staff_debt['year']+1;
                            }
                            if (((int)date('W',$costs_data->pay_date) == 1) && ((int)date('m',$costs_data->pay_date) == 12) ) {
                                $data_staff_debt['year'] = (int)date('Y',$costs_data->pay_date)+1;
                            }

                            $staff_debt_model->createCost($data_staff_debt);

                            $receivable = $this->model->get('receivableModel');
                        
                            $receivable->queryCosts('DELETE FROM receivable WHERE staff='.$costs_data->staff.' AND comment='.$costs_data->comment.' AND receivable_date='.$costs_data->pay_date);
                        }

                        if ($costs_data->lender>0) {
                            $owe = $this->model->get('oweModel');
                            $payable = $this->model->get('payableModel');
                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $receivable = $this->model->get('receivableModel');
                            $assets_model->queryAssets2('DELETE FROM assets WHERE costs = '.(0-$data));
                            $owe->queryOwe2('DELETE FROM owe WHERE costs='.$data);
                            $payable->queryCosts2('DELETE FROM payable WHERE costs='.$data);
                            $costs->queryCosts3('DELETE FROM costs WHERE staff=29 AND costs_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                            $assets_model->queryAssets3('DELETE FROM assets WHERE costs = '.$data);
                            $receivable->queryCosts3('DELETE FROM receivable WHERE staff=29 AND receivable_date='.$costs_data->pay_date.'  AND money='.$costs_data->pay_money);
                            $staff_debt_model->queryCost3('DELETE FROM staff_debt WHERE staff=29 AND staff_debt_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                        }

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $costs_data = $costs->getCosts($_POST['data']);

                        $costs->deleteCosts($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE costs = '.$_POST['data']);
                       $pay_model->queryCosts('DELETE FROM pay WHERE costs = '.$_POST['data']);

                       if($costs_data->staff > 0 && $costs_data->staff_cost > 0 ){

                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $data_staff_debt = array(
                                        'staff' => $costs_data->staff,
                                        'source' => $costs_data->source,
                                        'money' => 0-$costs_data->staff_cost,
                                        'staff_debt_date' => $costs_data->pay_date,
                                        'comment' => $costs_data->comment,
                                        'week' => (int)date('W',$costs_data->pay_date),
                                        'year' => (int)date('Y',$costs_data->pay_date),
                                        'status' => 1,
                                    );
                            if($data_staff_debt['week'] == 53){
                                $data_staff_debt['week'] = 1;
                                $data_staff_debt['year'] = $data_staff_debt['year']+1;
                            }
                            if (((int)date('W',$costs_data->pay_date) == 1) && ((int)date('m',$costs_data->pay_date) == 12) ) {
                                $data_staff_debt['year'] = (int)date('Y',$costs_data->pay_date)+1;
                            }

                            $staff_debt_model->createCost($data_staff_debt);

                            $receivable = $this->model->get('receivableModel');
                        
                            $receivable->queryCosts('DELETE FROM receivable WHERE staff='.$costs_data->staff.' AND comment='.$costs_data->comment.' AND receivable_date='.$costs_data->pay_date);
                        }

                        if ($costs_data->lender>0) {
                            $owe = $this->model->get('oweModel');
                            $payable = $this->model->get('payableModel');
                            $receivable = $this->model->get('receivableModel');
                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $assets_model->queryAssets2('DELETE FROM assets WHERE costs = '.(0-$_POST['data']));
                            $owe->queryOwe2('DELETE FROM owe WHERE costs='.$_POST['data']);
                            $payable->queryCosts2('DELETE FROM payable WHERE costs='.$_POST['data']);
                            $costs->queryCosts3('DELETE FROM costs WHERE staff=29 AND costs_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                            $assets_model->queryAssets3('DELETE FROM assets WHERE costs = '.$_POST['data']);
                            $receivable->queryCosts3('DELETE FROM receivable WHERE staff=29 AND receivable_date='.$costs_data->pay_date.'  AND money='.$costs_data->pay_money);
                            $staff_debt_model->queryCost3('DELETE FROM staff_debt WHERE staff=29 AND staff_debt_date='.$costs_data->pay_date.' AND money='.$costs_data->pay_money);
                        }

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $costs = $this->model->get('costsModel');
            $bank = $this->model->get('bankModel');
            $assets_model = $this->model->get('assetsModel');
            $pay_model = $this->model->get('payModel');
            $user = $this->model->get('userModel');

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
            
             $id_approve = $user->getUserByWhere(array('username'=>trim('nancy')))->user_id;

                for ($row = 3; $row <= $highestRow; ++ $row) {
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
                    if ($val[2] != null && $val[3] != null ) {

                            

                            $costs_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[1]));                                      
                            $costs_date = $costs_date-3600;

                            $expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[4]));                                      
                            $expect_date = $expect_date-3600;

                            $pay_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[5]));                                      
                            $pay_date = $pay_date-3600;

                            if(!$bank->getBankByWhere(array('bank_name'=>trim($val[6])))) {
                                $bank_data = array(
                                'bank_name' => trim($val[6]),
                                
                                );
                                $bank->createBank($bank_data);
                                $id_bank = $bank->getLastBank()->bank_id;
                            }
                            else if($bank->getBankByWhere(array('bank_name'=>trim($val[6])))){
                                $id_bank = $bank->getBankByWhere(array('bank_name'=>trim($val[6])))->bank_id;
                                
                            }

                            if (trim($val[7]) != null) {
                                if(!$bank->getBankByWhere(array('bank_name'=>trim($val[7])))) {
                                    $bank_data = array(
                                    'bank_name' => trim($val[7]),
                                    
                                    );
                                    $bank->createBank($bank_data);
                                    $id_bank_in = $bank->getLastBank()->bank_id;
                                }
                                else if($bank->getBankByWhere(array('bank_name'=>trim($val[7])))){
                                    $id_bank_in = $bank->getBankByWhere(array('bank_name'=>trim($val[7])))->bank_id;
                                    
                                }
                            }
                            else{
                                $id_bank_in = null;
                            }

                            if(!$costs->getCostsByWhere(array('comment'=>trim($val[3]),'money'=>trim($val[2]),'costs_date'=>$costs_date))) {
                                $costs_data = array(
                                'money' => trim($val[2]),
                                'approve' => $id_approve,
                                'costs_date' => $costs_date,
                                'expect_date' => $expect_date,
                                'pay_date' => $pay_date,
                                'week' => (int)date('W',$expect_date),
                                'source' => $id_bank,
                                'source_in' => $id_bank_in,
                                'comment' => trim($val[3]),
                                'year' => date('Y',$expect_date),
                                );

                                if($costs_data['week'] == 53){
                                        $costs_data['week'] = 1;
                                        $costs_data['year'] = $costs_data['year']+1;
                                    }
                                if (((int)date('W',$expect_date) == 1) && ((int)date('m',$expect_date) == 12) ) {
                                    $costs_data['year'] = (int)date('Y',$expect_date)+1;
                                }

                                $costs->createCosts($costs_data);

                                if (trim($val[5]) != null) {
                                    $pay_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[5]));                                      
                                    $pay_date = $pay_date-3600;

                                    $data_asset = array(
                                                'bank' => $costs_data['source'],
                                                'total' => 0 - $costs_data['money'],
                                                'assets_date' => $pay_date,
                                                'costs' => $costs->getLastCosts()->costs_id,
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

                                    if (trim($val[7]) != null) {
                                        $data_asset = array(
                                                'bank' => $costs_data['source_in'],
                                                'total' => $costs_data['money'],
                                                'assets_date' => $pay_date,
                                                'costs' => $costs->getLastCosts()->costs_id,
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
                                    }

                                    
                                    $data_pay = array(
                                                'source' => $costs_data['source'],
                                                'money' => $costs_data['money'],
                                                'pay_date' => $pay_date,
                                                'costs' => $costs->getLastCosts()->costs_id,
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
                                }
                            }
                            else if($costs->getCostsByWhere(array('comment'=>trim($val[3]),'money'=>trim($val[2]),'costs_date'=>$costs_date))){
                                $id_costs = $costs->getCostsByWhere(array('comment'=>trim($val[3]),'money'=>trim($val[2]),'costs_date'=>$costs_date))->costs_id;
                                $costs_data = array(
                                'money' => trim($val[2]),
                                'approve' => $id_approve,
                                'costs_date' => $costs_date,
                                'expect_date' => $expect_date,
                                'pay_date' => $pay_date,
                                'week' => (int)date('W',$expect_date),
                                'source' => $id_bank,
                                'source_in' => $id_bank_in,
                                'comment' => trim($val[3]),
                                'year' => date('Y',$expect_date),
                                );
                                $costs->updateCosts($costs_data,array('costs_id' => $id_costs));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('costs');
        }
        $this->view->show('costs/import');

    }

    public function view($id) {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thu chi';

        $costs_model = $this->model->get('costsModel');
        
        $costs = $costs_model->getCosts($id);
        $this->view->data['cost'] = $costs;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_data['id'][$staff->staff_id] = $staff->staff_id;
        }
        $this->view->data['staff_data'] = $staff_data;


        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;
        
        $this->view->show('costs/view');
    }

}
?>