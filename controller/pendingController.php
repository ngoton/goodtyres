<?php
Class pendingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc chờ xử lý';
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pending/index');
    }
    public function payable() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí chờ duyệt';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $trangthai = 0;
        }


        $code = $this->registry->router->param_id;

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

        if (isset($code) && $code != "") {
            $data['where'] .= ' AND code = '.$code;
        }
        
            if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)';
                
            }
         
        // if ($_SESSION['role_logined'] == 8) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }   
        
        // if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }
        
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );
        if (isset($code) && $code != "") {
            $data['where'] .= ' AND code = '.$code;
        }
        
            if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)';
                
            }

        // if ($_SESSION['role_logined'] == 8) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }
        
        // if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }
            
        
      
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



        
        $this->view->data['payables'] = $payable_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($payable_model->getLastCosts()->payable_id)?$payable_model->getLastCosts()->payable_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pending/payable');
    }

    public function costs() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí chờ duyệt';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $trangthai = 0;
        }
        
        $nam = date('Y');

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $this->view->data['staffs'] = $staffs;


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
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)';
                
            }

        /*if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
            $data['where'] .= ' AND approve2 > 0';
        }*/
        
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)';
                
            }

        /*if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
            $data['where'] .= ' AND approve2 > 0';
        }*/
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR money_in LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['costs'] = $costs_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($costs_model->getLastCosts()->costs_id)?$costs_model->getLastCosts()->costs_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pending/costs');
    }

    public function office() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí chờ duyệt';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pending_costs_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $trangthai = 0;
        }
        
        $nam = date('Y');

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;


        $costs_model = $this->model->get('pendingcostsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0) ';
                
            }

        /*if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
            $data['where'] .= ' AND approve2 > 0';
        }*/
        
        $tongsodong = count($costs_model->getAllCosts($data));
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0)';
                
            }

        /*if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
            $data['where'] .= ' AND approve2 > 0';
        }*/
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['costs'] = $costs_model->getAllCosts($data);
        $this->view->data['lastID'] = isset($costs_model->getLastCosts()->costs_id)?$costs_model->getLastCosts()->pending_costs_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pending/office');
    }

    public function pendingpayable(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingpayableModel');
            $payable = $this->model->get('payableModel');
            $costs_data = $costs->getCosts($_POST['data']);

            $p = $_POST['val']==1?0:1;

            $data = array(
                        
                        'pending' => $p,
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_payable_id' => $_POST['data']));

            $data = array(
                        'pending' => $p,
                        );
          
            $payable->updateCosts($data,array('code' => $costs_data->code));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pending"."|".$_POST['data']."|payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function pendingcosts(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $payable = $this->model->get('pendingcostsModel');
            //$costs_data = $costs->getCosts($_POST['data']);
            $p = $_POST['val']==1?0:1;

            $data = array(
                        
                        'pending' => $p,
                        );
          
            $payable->updateCosts($data,array('pending_costs_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pending"."|".$_POST['data']."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function approveoffice(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingcostsModel');
            //$costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve2' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_costs_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve2"."|".$_POST['data']."|pending_costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approveofficeall(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingcostsModel');

            $costs_data = $costs->getCosts($_POST['data']);

            $date = $costs_data->pending_costs_date;
            $approve2 = $costs_data->approve2;

            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_costs_id' => $_POST['data']));

            $salary = $this->model->get('newsalaryModel');
            $costs = $this->model->get('costsModel');

            //$costs_data = $costs->getCosts($_POST['data']);
            $salarys = $salary->getAllSalary(array('where'=>'create_time='.$date));

            $total_salary = 0; $total_phone = 0; $total_eating = 0; $total_insurance = 0; $total_bonus = 0;
            foreach ($salarys as $salary_data) {
                $total_salary += $salary_data->total; 
                $total_phone += $salary_data->phone_allowance; 
                $total_eating += $salary_data->eating_allowance; 
                $total_insurance += $salary_data->insurance_allowance+$salary_data->insurance_deduct; 
                $total_bonus += $salary_data->effect_add+$salary_data->time_add+$salary_data->diligence_add;
            }

            $data_cost = array(
                'costs_create_date' => strtotime(date('d-m-Y H:i:s')),
                'costs_date' => strtotime(date('d-m-Y')),
                'expect_date' => strtotime('15-'.date("m-Y", strtotime("+1 month", $date))),
                'week' => (int)date('W', strtotime('15-'.date("m-Y", strtotime("+1 month", $date)))),
                'create_user' => 1,
                'source' => 1,
                'year' => (int)date('Y',strtotime('15-'.date("m-Y", strtotime("+1 month", $date)))),
                'staff' => -1,
                'staff_cost' => 0,
                'check_office' => 1,
                'check_equipment' => 0,
                'check_entertainment' => 0,
                'check_energy' => 0,
                'check_other' => 0,
                'check_salary' => 0,
                'check_phone' => 0,
                'check_eating' => 0,
                'check_insurance' => 0,
                'check_bonus' => 0,
                'approve' => $_SESSION['userid_logined'],
                'approve2' => $approve2,
            );

            if ($total_salary>0) {
                $data_cost['check_salary'] = 1;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền lương tháng '.date('m-Y',$date);
                $data_cost['money'] = ($total_salary-$total_bonus);

                $costs->createCosts($data_cost);
            }
            if ($total_phone>0) {
                $data_cost['check_phone'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền điện thoại tháng '.date('m-Y',$date);
                $data_cost['money'] = $total_phone;

                $costs->createCosts($data_cost);
            }
            if ($total_eating>0) {
                $data_cost['check_eating'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền cơm tháng '.date('m-Y',$date);
                $data_cost['money'] = $total_eating;

                $costs->createCosts($data_cost);
            }
            if ($total_insurance>0) {
                $data_cost['check_insurance'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền bảo hiểm tháng '.date('m-Y',$date);
                $data_cost['money'] = $total_insurance;

                $costs->createCosts($data_cost);
            }
            if ($total_bonus>0) {
                $data_cost['check_bonus'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['comment'] = 'Thưởng tháng '.date('m-Y',$date);
                $data_cost['money'] = $total_bonus;

                $costs->createCosts($data_cost);
            }

            $data = array(
                        
                        'approve' => 1,
                        );
          
            $salary->updateSalary($data,array('create_time' => $date));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|pending_costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approvesalefirst(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingpayableModel');
            $payable = $this->model->get('payableModel');
            $costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve3' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_payable_id' => $_POST['data']));

            $data = array(
                        'approve3' => $_SESSION['userid_logined'],
                        );
          
            $payable->updateCosts($data,array('code' => $costs_data->code));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve3"."|".$_POST['data']."|pending_payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function approvesale(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingpayableModel');
            $payable = $this->model->get('payableModel');
            $costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve2' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_payable_id' => $_POST['data']));

            $data = array(
                        'approve2' => $_SESSION['userid_logined'],
                        );
          
            $payable->updateCosts($data,array('code' => $costs_data->code));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve2"."|".$_POST['data']."|pending_payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approvesaleall(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $costs = $this->model->get('pendingpayableModel');
            $payable = $this->model->get('payableModel');
            $costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
            /*if (isset($_POST['type']) && $_POST['type'] > 0) {
                $data['approve'] = 10;
            }*/
          
            $costs->updateCosts($data,array('pending_payable_id' => $_POST['data']));

            $data = array(
                        'approve3' => $costs_data->approve3,
                        'approve2' => $costs_data->approve2,
                        'approve' => $_SESSION['userid_logined'],
                        );
          
            $payable->updateCosts($data,array('code' => $costs_data->code));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve2"."|".$_POST['data']."|pending_payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function sale() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí chờ duyệt';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $trangthai = 0;
        }
        
        $nam = date('Y');

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;


        $costs_model = $this->model->get('pendingpayableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0)';
                
            }

        // if ($_SESSION['role_logined'] == 8) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }

        // if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }
        
        $tongsodong = count($costs_model->getAllCosts($data));
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );
        if ($trangthai==1) {
                $data['where'] .= ' AND approve > 0';
                
            }
            else{
                $data['where'] .= ' AND (approve IS NULL OR approve <= 0)';
                
            }
        // if ($_SESSION['role_logined'] == 8) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }

        // if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 7) {
        //     $data['where'] .= ' AND approve3 > 0';
        // }
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['costs'] = $costs_model->getAllCosts($data);
        $this->view->data['lastID'] = isset($costs_model->getLastCosts()->costs_id)?$costs_model->getLastCosts()->pending_payable_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pending/sale');
    }

    public function approvepayable(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 7) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payable = $this->model->get('payableModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $payable_data = array(
                            'approve' => $_SESSION['userid_logined'],
                        );

                       $payable->updateCosts($payable_data,array('payable_id'=>$data));


                        echo "Duyệt thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$data."|payable|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            
            
        }
    }

    public function approvecosts(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 7) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $costs = $this->model->get('costsModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $costs_data = array(
                            'approve' => $_SESSION['userid_logined'],
                        );

                       $costs->updateCosts($costs_data,array('costs_id'=>$data));


                        echo "Duyệt thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$data."|costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            
            
        }
    }

    public function tire() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, delivery_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $trangthai = 0;
            $nv = "";
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

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

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(approve IS NULL OR approve = 0 ) AND order_tire_date >= '.strtotime($batdau).' AND order_tire_date <= '.strtotime($ketthuc),
        );

        if ($nv == 1) {
            $data = array(
                'where' => '(approve IS NULL OR approve = 0 ) AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            );
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }
        
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
            'where' => '(approve IS NULL OR approve = 0 ) AND order_tire_date >= '.strtotime($batdau).' AND order_tire_date <= '.strtotime($ketthuc),
            );

        if ($nv == 1) {
            $data['where'] = '(approve IS NULL OR approve = 0 ) AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc);
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
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

        $tire_imports = $tire_import_model->getAllTire();
        $tire_prices = array();
        $count = array();
        foreach ($tire_imports as $tire) {
            if (isset($tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern])) {
                $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+1;
                $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+$tire->tire_price;
            }
            else{
                $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = 1;
                $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire->tire_price;
            }
        }

        $costs = array();
        foreach ($order_tires as $tire) {
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $gia = isset($tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern]:0;
                $sl = isset($count[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$count[$l->tire_brand][$l->tire_size][$l->tire_pattern]:1;
                $costs[$tire->order_tire_id] = isset($costs[$tire->order_tire_id])?$costs[$tire->order_tire_id]+$l->tire_number*($gia/$sl):$l->tire_number*($gia/$sl);
            }
        }
        
        $this->view->data['costs'] = $costs;
        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('pending/tire');
    }

}
?>