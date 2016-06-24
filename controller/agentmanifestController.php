<?php
Class agentmanifestController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Agent';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'agent_manifest_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = date('d-m-Y', strtotime("last monday"));
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $id = $this->registry->router->param_id;

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data[$bank->bank_id] = $bank->bank_name;
        }
        $this->view->data['bank_data'] = $bank_data;

        $vendor = $this->model->get('shipmentvendorModel');
        $vendor_list = $vendor->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendor_list;

        

        $staffs = $vendor->getAllVendor(array('where'=>'vendor_type=1'));
        $this->view->data['staffs'] = $staffs;

        $staff_model = $this->model->get('staffModel');
        $o_staffs = $staff_model->getAllStaff();
        $this->view->data['other_staffs'] = $o_staffs;

        $staff_data = array();
        foreach ($o_staffs as $staff) {
            $staff_data['staff_id'][$staff->staff_id] = $staff->staff_id;
            $staff_data['staff_name'][$staff->staff_id] = $staff->staff_name;
        }
        
        $this->view->data['staff'] = $staff_data;

        
        $vendors = $vendor->getAllVendor(array('where'=>'vendor_type=5'));
        $commission = array();
        foreach ($vendors as $ven) {
            $commission[$ven->shipment_vendor_id] = $ven->shipment_vendor_name;
        }
        $this->view->data['commission'] = $commission;

        $agent_model = $this->model->get('agentmanifestModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.$ketthuc,
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }

        $join = array('table'=>'customer, shipment_vendor','where'=>'customer.customer_id = agent_manifest.customer AND agent_manifest.staff = shipment_vendor.shipment_vendor_id');
        
        $tongsodong = count($agent_model->getAllAgent($data,$join));
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
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
            );

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND create_user = '.$_SESSION['userid_logined'];
        }

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }

        if ($keyword != '') {
            $search = '( shipment_vendor_name LIKE "%'.$keyword.'%"  
                OR customer_name LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $all_agent = $agent_model->getAllAgent($data,$join);
        
        $this->view->data['agents'] = $all_agent;
        $this->view->data['lastID'] = isset($agent_model->getLastAgent()->agent_manifest_id)?$agent_model->getLastAgent()->agent_manifest_id:0;

        

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('agentmanifest/index');
    }

    public function getvendor(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            $data = array(
                'where'=>'vendor_type = 5',
                );

            if ($_POST['keyword'] == "*") {

                $list = $vendor_model->getAllVendor($data);
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%") AND vendor_type = 5',
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
                echo '<li onclick="set_item_vendor(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_phone.'\')">'.$vendor_name.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            
            $agent = $this->model->get('agentmanifestModel');
             $customer = $this->model->get('customerModel');
              $receivable = $this->model->get('receivableModel');
               $obtain = $this->model->get('obtainModel');
               $owe = $this->model->get('oweModel');
               $payable = $this->model->get('payableModel');
               $costs = $this->model->get('costsModel');
               $sales_model = $this->model->get('salesModel');
               $pending_payable = $this->model->get('pendingpayableModel');
            $data = array(
                        'm' => trim($_POST['m']),
                        's' => trim($_POST['s']),
                        'c' => trim($_POST['c']),
                        'agent_manifest_date' => strtotime(trim($_POST['agent_manifest_date'])),
                        'code' => trim($_POST['code']),
                        'staff' => trim($_POST['staff']),
                        'create_user' => $_SESSION['userid_logined'],
                        'comment' => trim($_POST['comment']),
                        'revenue_vat' => trim(str_replace(',','',$_POST['revenue_vat'])),
                        'revenue' => trim(str_replace(',','',$_POST['revenue'])),
                        'cost_sg' => trim(str_replace(',','',$_POST['cost_sg'])),
                        'cost_cm' => trim(str_replace(',','',$_POST['cost_cm'])),
                        'driver_cost' => trim(str_replace(',','',$_POST['driver_cost'])),
                        'commission_cost' => trim(str_replace(',','',$_POST['commission_cost'])),
                        'document_cost' => trim(str_replace(',','',$_POST['document_cost'])),
                        'invoice_date' => strtotime(trim($_POST['invoice_date'])),
                        'other_cost' => trim(str_replace(',','',$_POST['other_cost'])),
                        'other_staff' => trim($_POST['other_staff']),
                        'document_cost_2' => trim(str_replace(',','',$_POST['document_cost_2'])),
                        'pay_cost' => trim(str_replace(',','',$_POST['pay_cost'])),
                        
                        );
            $data['estimate_cost'] = $data['document_cost']+$data['document_cost_2']+$data['pay_cost'];
            
            if(trim($_POST['commission_name']) != ""){
                    if ($_POST['commission'] == null) {
                        $vendor = $this->model->get('shipmentvendorModel');
                         $vendor_data = array(
                            'shipment_vendor_name'=> trim($_POST['commission_name']),
                            'vendor_type' => 5,
                        );
                         $vendor->createVendor($vendor_data);

                         $data['commission'] = $vendor->getLastVendor()->shipment_vendor_id;
                    }
                    else{
                        $data['commission'] = trim($_POST['commission']);
                    }

                }

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
            /**************/
            $agent_vendor = $this->model->get('agentvendorModel');

            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                    $agent_d = $agent->getAgent($_POST['yes']);
                
                    $agent->updateAgent($data,array('agent_manifest_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    

                    if($data['other_cost'] > 0 && $data['other_staff'] > 0){
                        if($agent_d->other_staff > 0){
                            $owe_data = array(
                                'owe_date' => $data['agent_manifest_date'],
                                'vendor' => $data['other_staff'],
                                'money' => $data['other_cost'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'agent_manifest' => $_POST['yes'],
                            );

                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $owe->updateOwe($owe_data,array('vendor' => $agent_d->other_staff,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->other_cost));

                            $payable_data = array(
                                'vendor' => $data['other_staff'],
                                'money' => $data['other_cost'],
                                'payable_date' => $data['agent_manifest_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['agent_manifest_date'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'code' => $data['code'],
                                'source' => 5,
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'cost_type' => 7,
                                'check_vat' => 0,
                                'agent_manifest' => $_POST['yes'],
                                'approve' => null,
                            );

                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $check = $payable->getCostsByWhere(array('vendor' => $agent_d->other_staff,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->other_cost));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                            $payable->updateCosts($payable_data,array('vendor' => $agent_d->other_staff,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->other_cost));
                        }
                        else{
                            $owe_data = array(
                                'owe_date' => $data['agent_manifest_date'],
                                'vendor' => $data['other_staff'],
                                'money' => $data['other_cost'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'agent_manifest' => $_POST['yes'],
                            );

                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $owe->createOwe($owe_data);

                            $payable_data = array(
                                'vendor' => $data['other_staff'],
                                'money' => $data['other_cost'],
                                'payable_date' => $data['agent_manifest_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['agent_manifest_date'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'code' => $data['code'],
                                'source' => 5,
                                'comment' => $data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent_manifest' => $_POST['yes'],
                                'cost_type' => 7,
                                'check_vat' => 0,
                                'approve' => null,
                            );

                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $payable->createCosts($payable_data);
                        }

                    }

                    if(isset($data['commission']) && trim($data['commission']) != null){
                        if($agent_d->commission > 0){
                            $owe_data = array(
                                'owe_date' => $data['agent_manifest_date'],
                                'vendor' => $data['commission'],
                                'money' => $data['commission_cost'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'agent_manifest' => $_POST['yes'],
                            );

                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $owe->updateOwe($owe_data,array('vendor' => $agent_d->commission,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->commission_cost));

                            $payable_data = array(
                                'vendor' => $data['commission'],
                                'money' => $data['commission_cost'],
                                'payable_date' => $data['agent_manifest_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['agent_manifest_date'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'code' => $data['code'],
                                'source' => 5,
                                'comment' => 'Hoa hồng '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'cost_type' => 5,
                                'check_vat' => 0,
                                'agent_manifest' => $_POST['yes'],
                                'approve' => null,
                            );

                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $check = $payable->getCostsByWhere(array('vendor' => $agent_d->commission,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->commission_cost));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                            $payable->updateCosts($payable_data,array('vendor' => $agent_d->commission,'agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->commission_cost));
                        }
                        else{
                            $owe_data = array(
                                'owe_date' => $data['agent_manifest_date'],
                                'vendor' => $data['commission'],
                                'money' => $data['commission_cost'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'agent_manifest' => $_POST['yes'],
                            );

                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $owe->createOwe($owe_data);

                            $payable_data = array(
                                'vendor' => $data['commission'],
                                'money' => $data['commission_cost'],
                                'payable_date' => $data['agent_manifest_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['agent_manifest_date'],
                                'week' => (int)date('W',$data['agent_manifest_date']),
                                'year' => (int)date('Y',$data['agent_manifest_date']),
                                'code' => $data['code'],
                                'source' => 5,
                                'comment' => 'Hoa hồng '.$data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent_manifest' => $_POST['yes'],
                                'cost_type' => 5,
                                'check_vat' => 0,
                                'approve' => null,
                            );

                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                            }

                            $payable->createCosts($payable_data);
                        }
                    }

                    $owe_data = array(
                            'owe_date' => $data['agent_manifest_date'],
                            'vendor' => $data['staff'],
                            'money' => $data['cost_cm']+$data['cost_sg']+$data['driver_cost'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                        );

                    if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $owe->updateOwe($owe_data,array('vendor' => $agent_d->staff,'agent_manifest'=>trim($_POST['yes']),'money'=>($agent_d->cost_cm+$agent_d->cost_sg+$agent_d->driver_cost)));

                    //$payable->queryCosts('DELETE FROM payable WHERE agent_manifest = '.$_POST['yes'].' AND vendor = '.$data['staff']);

                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => $data['cost_cm']+$data['cost_sg'],
                        'payable_date' => $data['agent_manifest_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data['agent_manifest_date'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $_POST['yes'],
                        'cost_type' => 6,
                        'check_vat' => 0,
                        'approve' => null,
                    );

                    if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                    $check = $payable->getCostsByWhere(array('vendor'=>$agent_d->staff,'agent_manifest'=>$_POST['yes'],'money'=>($agent_d->cost_sg+$agent_d->cost_cm)));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                    $payable->updateCosts($payable_data,array('vendor'=>$agent_d->staff,'agent_manifest'=>$_POST['yes'],'money'=>($agent_d->cost_sg+$agent_d->cost_cm)));

                    $ngay_du2 = '30-'.date('m-Y');

                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => $data['driver_cost'],
                        'payable_date' => $data['agent_manifest_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => strtotime($ngay_du2),
                        'week' => (int)date('W',strtotime($ngay_du2)),
                        'year' => (int)date('Y',strtotime($ngay_du2)),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => 'Xăng xe code '.$data['code'].$data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $_POST['yes'],
                        'cost_type' => 7,
                        'check_vat' => 0,
                        'approve' => null,
                    );

                    if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',strtotime($ngay_du2)) == 1) && ((int)date('m',strtotime($ngay_du2)) == 12) ) {
                            $payable_data['year'] = (int)date('Y',strtotime($ngay_du2))+1;
                        }

                    $check = $payable->getCostsByWhere(array('vendor'=>$agent_d->staff,'agent_manifest'=>$_POST['yes'],'money'=>$agent_d->driver_cost));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                    $payable->updateCosts($payable_data,array('vendor'=>$agent_d->staff,'agent_manifest'=>$_POST['yes'],'money'=>$agent_d->driver_cost));


                    $ngay = strtotime('+1 month',$data['invoice_date']);
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['revenue_vat'],
                        'receivable_date' => $data['agent_manifest_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'check_vat' => 1,
                        'invoice_date' => $data['invoice_date'],
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$ngay)+1;
                        }

                    
                    $receivable->updateCosts($receivable_data,array('agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->revenue_vat,'check_vat'=>'1'));

                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['revenue'],
                        'receivable_date' => $data['agent_manifest_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'check_vat' => 0,
                        'invoice_date' => $data['invoice_date'],
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$ngay)+1;
                        }

                    
                    $receivable->updateCosts($receivable_data,array('agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->revenue,'check_vat'=>'0'));

                    $obtain_data = array(
                        'obtain_date' => $data['agent_manifest_date'],
                        'customer' => $data['customer'],
                        'money' => $data['revenue_vat']+$data['revenue'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                    );

                    if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                    $obtain->updateObtain($obtain_data,array('agent_manifest'=>trim($_POST['yes']),'money'=>$agent_d->revenue_vat+$agent_d->revenue));


                    /*$document_data = array(
                        
                        'money' => $data['document_cost'],
                        'costs_date' => $data['agent_manifest_date'],
                        'expect_date' => $data['agent_manifest_date'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                        'code' => $data['code'],
                        'source' => 1,
                        'comment' => 'Phí mua HĐ '.$data['code'].' '.$data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'check_office'=>0,
                    );
                    if($document_data['week'] == 53){
                        $document_data['week'] = 1;
                        $document_data['year'] = $document_data['year']+1;
                    }
                    if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                        $document_data['year'] = (int)date('Y',$data['expect_date'])+1;
                    }

                    if($costs->getCostsByWhere(array('money'=>$agent_d->document_cost,'code'=>$agent_d->code,'expect_date'=>$agent_d->expect_date))){
                        if($data['document_cost'] > 0){
                            $costs->updateCosts($document_data,array('money'=>$agent_d->document_cost,'code'=>$agent_d->code,'expect_date'=>$agent_d->expect_date));
                        }
                        else{
                            $costs->queryCosts('DELETE FROM costs WHERE money='.$agent_d->document_cost.' AND code='.$agent_d->code.' AND expect_date='.$agent_d->expect_date);
                        }
                    }
                    else{
                        if($data['document_cost'] > 0){
                            $costs->createCosts($document_data);
                        }
                    }*/

                    $id_agent_manifest = $_POST['yes'];

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|agent_manifest|".implode("-",$data)."\n"."\r\n";
                        
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

                    $data['agent_manifest_create_user'] = $_SESSION['userid_logined'];
                
                    $agent->createAgent($data);
                    echo "Thêm thành công";

                    

                    if(isset($data['commission']) && trim($data['commission']) != null){

                        $owe_data = array(
                            'owe_date' => $data['agent_manifest_date'],
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                            'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $data['commission'],
                            'money' => $data['commission_cost'],
                            'payable_date' => $data['agent_manifest_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['agent_manifest_date'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                            'code' => $data['code'],
                            'source' => 5,
                            'comment' => 'Hoa hồng '.$data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                            'cost_type' => 5,
                            'check_vat' => 0,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $payable->createCosts($payable_data);

                    }

                    if($data['other_cost'] > 0 && $data['other_staff'] > 0){

                        $owe_data = array(
                            'owe_date' => $data['agent_manifest_date'],
                            'vendor' => $data['other_staff'],
                            'money' => $data['other_cost'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                            'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        );

                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $owe->createOwe($owe_data);

                        $payable_data = array(
                            'vendor' => $data['other_staff'],
                            'money' => $data['other_cost'],
                            'payable_date' => $data['agent_manifest_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data['agent_manifest_date'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                            'code' => $data['code'],
                            'source' => 5,
                            'comment' => $data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                            'cost_type' => 7,
                            'check_vat' => 0,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $payable->createCosts($payable_data);

                    }

                    $owe_data = array(
                            'owe_date' => $data['agent_manifest_date'],
                            'vendor' => $data['staff'],
                            'money' => $data['cost_cm']+$data['cost_sg']+$data['driver_cost'],
                            'week' => (int)date('W',$data['agent_manifest_date']),
                            'year' => (int)date('Y',$data['agent_manifest_date']),
                            'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        );

                    if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                        $owe->createOwe($owe_data);

                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => $data['cost_cm']+$data['cost_sg'],
                        'payable_date' => $data['agent_manifest_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data['agent_manifest_date'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        'cost_type' => 6,
                        'check_vat' => 0,
                    );

                    if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                    $payable->createCosts($payable_data);

                    $ngay_du2 = '30-'.date('m-Y',$data['agent_manifest_date']);

                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => $data['driver_cost'],
                        'payable_date' => $data['agent_manifest_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => strtotime($ngay_du2),
                        'week' => (int)date('W',strtotime($ngay_du2)),
                        'year' => (int)date('Y',strtotime($ngay_du2)),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => 'Xăng xe code '.$data['code'].$data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        'cost_type' => 7,
                        'check_vat' => 0,
                    );

                    if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',strtotime($ngay_du2)) == 1) && ((int)date('m',strtotime($ngay_du2)) == 12) ) {
                            $payable_data['year'] = (int)date('Y',strtotime($ngay_du2))+1;
                        }

                    $payable->createCosts($payable_data);

                    $ngay = strtotime('+1 month',$data['invoice_date']);
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['revenue_vat'],
                        'receivable_date' => $data['agent_manifest_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        'check_vat' => 1,
                        'invoice_date' => $data['invoice_date'],
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$ngay)+1;
                        }
                    
                    $receivable->createCosts($receivable_data);

                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['revenue'],
                        'receivable_date' => $data['agent_manifest_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 6,
                        'comment' => $data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                        'check_vat' => 0,
                        'invoice_date' => $data['invoice_date'],
                    );

                    if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$ngay)+1;
                        }
                    
                    $receivable->createCosts($receivable_data);

                    $obtain_data = array(
                        'obtain_date' => $data['agent_manifest_date'],
                        'customer' => $data['customer'],
                        'money' => $data['revenue_vat']+$data['revenue'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                    );

                    if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_manifest_date']) == 1) && ((int)date('m',$data['agent_manifest_date']) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$data['agent_manifest_date'])+1;
                        }

                    $obtain->createObtain($obtain_data);

                    /*$document_data = array(
                        
                        'money' => $data['document_cost'],
                        'costs_date' => $data['agent_manifest_date'],
                        'expect_date' => $data['agent_manifest_date'],
                        'week' => (int)date('W',$data['agent_manifest_date']),
                        'year' => (int)date('Y',$data['agent_manifest_date']),
                        'code' => $data['code'],
                        'source' => 1,
                        'comment' => 'Phí mua HĐ '.$data['code'].' '.$data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'check_office'=>0,
                    );
                    if($document_data['week'] == 53){
                        $document_data['week'] = 1;
                        $document_data['year'] = $document_data['year']+1;
                    }
                    if (((int)date('W',$data['expect_date']) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                        $document_data['year'] = (int)date('Y',$data['expect_date'])+1;
                    }

                    
                        if($data['document_cost'] > 0){
                            $costs->createCosts($document_data);
                        }*/

                    $id_agent_manifest = $agent->getLastAgent()->agent_manifest_id;
                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$agent->getLastAgent()->agent_manifest_id."|agent_manifest|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }

            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $agent_data = $agent->getAgent($id_agent_manifest);

            foreach ($vendor_cost as $v) {
                $agent_vendor_data = array(
                    'agent_manifest' => $id_agent_manifest,
                    'vendor' => $v['vendor'],
                    'type' => $v['cost_type'],
                    'cost' => trim(str_replace(',','',$v['cost'])),
                    'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                    'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                    'source' => $v['vendor_source'],
                    'comment' => $v['cost_comment'],
                );

                $kvat += $agent_vendor_data['cost'];
                $vat += $agent_vendor_data['cost_vat'];
                
                
                    if($agent_vendor->getVendorByWhere(array('agent_manifest'=>$id_agent_manifest,'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                        $old_cost = $agent_vendor->getVendorByWhere(array('agent_manifest'=>$id_agent_manifest,'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                        $old_cost_vat = $agent_vendor->getVendorByWhere(array('agent_manifest'=>$id_agent_manifest,'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                        $total = $old_cost+$old_cost_vat;

                  

                        $owe_data = array(
                            'owe_date' => $agent_data->agent_manifest_date,
                            'vendor' => $agent_vendor_data['vendor'],
                            'money' => $agent_vendor_data['cost']+$agent_vendor_data['cost_vat'],
                            'week' => (int)date('W',$agent_data->agent_manifest_date),
                            'year' => (int)date('Y',$agent_data->agent_manifest_date),
                            'agent_manifest' => $id_agent_manifest,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$agent_data->agent_manifest_date) == 1) && ((int)date('m',$agent_data->agent_manifest_date) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$agent_data->agent_manifest_date)+1;
                        }

                        $owe->updateOwe($owe_data,array('agent_manifest'=>$id_agent_manifest,'vendor'=>$agent_vendor_data['vendor'],'money'=>$total));

                        $payable_data = array(
                            'vendor' => $agent_vendor_data['vendor'],
                            'money' => $agent_vendor_data['cost_vat'],
                            'payable_date' => $agent_data->agent_manifest_date,
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $agent_vendor_data['expect_date'],
                            'week' => (int)date('W',$agent_vendor_data['expect_date']),
                            'year' => (int)date('Y',$agent_vendor_data['expect_date']),
                            'code' => $agent_data->code,
                            'source' => $agent_vendor_data['source'],
                            'comment' => $agent_vendor_data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent_manifest' => $id_agent_manifest,
                            'cost_type' => $agent_vendor_data['type'],
                            'check_vat'=>1,
                            'approve' => null,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$agent_vendor_data['expect_date']) == 1) && ((int)date('m',$agent_vendor_data['expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$agent_vendor_data['expect_date'])+1;
                        }

                        if($payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']))){
                            
                            $check = $payable->getCostsByWhere(array('vendor'=>$agent_vendor_data['vendor'],'agent_manifest'=>$id_agent_manifest,'money'=>$old_cost_vat,'cost_type'=>$agent_vendor_data['type']));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                                $payable->updateCosts($payable_data,array('money'=>$old_cost_vat,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']));
                            
                        }
                        elseif(!$payable->getCostsByWhere(array('money'=>$old_cost_vat,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']))){
                            if($agent_vendor_data['cost_vat'] > 0){
                                $payable->createCosts($payable_data);
                            }
                        }
                        
                    


                        $payable_data = array(
                            'vendor' => $agent_vendor_data['vendor'],
                            'money' => $agent_vendor_data['cost'],
                            'payable_date' => $agent_data->agent_manifest_date,
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $agent_vendor_data['expect_date'],
                            'week' => (int)date('W',$agent_vendor_data['expect_date']),
                            'year' => (int)date('Y',$agent_vendor_data['expect_date']),
                            'code' => $agent_data->code,
                            'source' => $agent_vendor_data['source'],
                            'comment' => $agent_vendor_data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent_manifest' => $id_agent_manifest,
                            'cost_type' => $agent_vendor_data['type'],
                            'check_vat'=>0,
                            'approve' => null,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$agent_vendor_data['expect_date']) == 1) && ((int)date('m',$agent_vendor_data['expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$agent_vendor_data['expect_date'])+1;
                        }

                        if($payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']))){
                            
                            $check = $payable->getCostsByWhere(array('vendor'=>$agent_vendor_data['vendor'],'agent_manifest'=>$id_agent_manifest,'money'=>$old_cost,'cost_type'=>$agent_vendor_data['type']));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                                $payable->updateCosts($payable_data,array('money'=>$old_cost,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']));
                            
                        }
                        elseif(!$payable->getCostsByWhere(array('money'=>$old_cost,'vendor' => $agent_vendor_data['vendor'],'agent_manifest'=>trim($id_agent_manifest),'cost_type' => $agent_vendor_data['type']))){
                            if($agent_vendor_data['cost'] > 0){
                                $payable->createCosts($payable_data);
                            }
                        }
                        
                        


                        $agent_vendor->updateVendor($agent_vendor_data,array('agent_manifest'=>$id_agent_manifest,'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                    }
                    else{
                        $agent_vendor->createVendor($agent_vendor_data);

                        $owe_data = array(
                            'owe_date' => $agent_data->agent_manifest_date,
                            'vendor' => $agent_vendor_data['vendor'],
                            'money' => $agent_vendor_data['cost']+$agent_vendor_data['cost_vat'],
                            'week' => (int)date('W',$agent_data->agent_manifest_date),
                            'year' => (int)date('Y',$agent_data->agent_manifest_date),
                            'agent_manifest' => $id_agent_manifest,
                        );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$agent_data->agent_manifest_date) == 1) && ((int)date('m',$agent_data->agent_manifest_date) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$agent_data->agent_manifest_date)+1;
                        }

                            //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$agent_vendor_data['vendor'].' AND agent_manifest='.$id_agent_manifest);
                            
                            $owe->createOwe($owe_data);

                            $payable_data = array(
                                'vendor' => $agent_vendor_data['vendor'],
                                'money' => $agent_vendor_data['cost_vat'],
                                'payable_date' => $agent_data->agent_manifest_date,
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $agent_vendor_data['expect_date'],
                                'week' => (int)date('W',$agent_vendor_data['expect_date']),
                                'year' => (int)date('Y',$agent_vendor_data['expect_date']),
                                'code' => $agent_data->code,
                                'source' => $agent_vendor_data['source'],
                                'comment' => $agent_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent_manifest' => $id_agent_manifest,
                                'cost_type' => $agent_vendor_data['type'],
                                'check_vat'=>1,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$agent_vendor_data['expect_date']) == 1) && ((int)date('m',$agent_vendor_data['expect_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$agent_vendor_data['expect_date'])+1;
                            }

                            
                                if($agent_vendor_data['cost_vat'] > 0){
                                    $payable->createCosts($payable_data);
                                }
                            
                        


                        $payable_data = array(
                            'vendor' => $agent_vendor_data['vendor'],
                            'money' => $agent_vendor_data['cost'],
                            'payable_date' => $agent_data->agent_manifest_date,
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $agent_vendor_data['expect_date'],
                            'week' => (int)date('W',$agent_vendor_data['expect_date']),
                            'year' => (int)date('Y',$agent_vendor_data['expect_date']),
                            'code' => $agent_data->code,
                            'source' => $agent_vendor_data['source'],
                            'comment' => $agent_vendor_data['comment'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent_manifest' => $id_agent_manifest,
                            'cost_type' => $agent_vendor_data['type'],
                            'check_vat'=>0,
                        );
                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$agent_vendor_data['expect_date']) == 1) && ((int)date('m',$agent_vendor_data['expect_date']) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$agent_vendor_data['expect_date'])+1;
                        }

                            if($agent_vendor_data['cost'] > 0){
                                $payable->createCosts($payable_data);
                            }

                        
                    }
               
                
            }

            $data_update = array(
                'other_vendor_cost' => $kvat+$vat,
            );
            
            $agent->updateAgent($data_update,array('agent_manifest_id' => $id_agent_manifest));
            echo "Thêm thành công";

            $data_agent = $agent->getAgent($id_agent_manifest);

            if(!$pending_payable->getCostsByWhere(array('agent_manifest'=> $id_agent_manifest))){
                $data_pending = array(
                        'code' => $data_agent->code,
                        'revenue' => $data_agent->revenue+$data_agent->revenue_vat,
                        'cost' => $data_agent->cost_sg+$data_agent->cost_cm+$data_agent->driver_cost+$data_agent->commission_cost+$data_agent->other_cost+$data_agent->document_cost+$data_agent->document_cost_2+$data_agent->pay_cost+$data_agent->other_vendor_cost,
                        'agent_manifest' => $id_agent_manifest,
                        'money' => $data_agent->cost_sg+$data_agent->cost_cm+$data_agent->driver_cost+$data_agent->commission_cost+$data_agent->other_cost+$data_agent->other_vendor_cost,
                        'comment' => 'Chi phí code '.$data_agent->code.' '.$data_agent->comment,
                    );

                    $pending_payable->createCosts($data_pending);
            }
            else if($pending_payable->getCostsByWhere(array('agent_manifest'=> $id_agent_manifest))){
                $data_pending = array(
                        'code' => $data_agent->code,
                        'revenue' => $data_agent->revenue+$data_agent->revenue_vat,
                        'cost' => $data_agent->cost_sg+$data_agent->cost_cm+$data_agent->driver_cost+$data_agent->commission_cost+$data_agent->other_cost+$data_agent->document_cost+$data_agent->document_cost_2+$data_agent->pay_cost+$data_agent->other_vendor_cost,
                        'agent_manifest' => $id_agent_manifest,
                        'money' => $data_agent->cost_sg+$data_agent->cost_cm+$data_agent->driver_cost+$data_agent->commission_cost+$data_agent->other_cost+$data_agent->other_vendor_cost,
                        'comment' => 'Chi phí code '.$data_agent->code.' '.$data_agent->comment,
                        'approve' => null,
                    );

                $check = $pending_payable->getCostsByWhere(array('agent_manifest'=>$id_agent_manifest));

                    if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                        $data_pending['approve'] = 10;
                    }

                    $pending_payable->updateCosts($data_pending,array('agent_manifest' => $id_agent_manifest));
            }

            $salesdata = $sales_model->getSalesByWhere(array('agent_manifest'=>$_POST['yes']));

                    if ($salesdata) {
                        $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'revenue' => $data['revenue_vat']+$data['revenue'],
                            'cost' => $data['cost_cm']+$data['cost_sg']+$data['driver_cost']+$data['commission_cost']+$data['other_cost']+$data_update['other_vendor_cost']+$data['estimate_cost'],
                            'profit' => $data['revenue_vat']+$data['revenue']-$data['cost_cm']-$data['cost_sg']-$data['driver_cost']-$data['commission_cost']-$data['other_cost']-$data_update['other_vendor_cost']-$data['estimate_cost'],
                            'sales_create_time' => $data['agent_manifest_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'agent_manifest' => $id_agent_manifest,
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
                            'revenue' => $data['revenue_vat']+$data['revenue'],
                            'cost' => $data['cost_cm']+$data['cost_sg']+$data['driver_cost']+$data['commission_cost']+$data['other_cost']+$data_update['other_vendor_cost']+$data['estimate_cost'],
                            'profit' => $data['revenue_vat']+$data['revenue']-$data['cost_cm']-$data['cost_sg']-$data['driver_cost']-$data['commission_cost']-$data['other_cost']-$data_update['other_vendor_cost']-$data['estimate_cost'],
                            'sales_create_time' => $data['agent_manifest_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'agent_manifest' => $id_agent_manifest,
                            'sales_update_user' => $_SESSION['userid_logined'],
                        );
                        $sales_model->createSales($data_sales);
                    }
                    
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $agent = $this->model->get('agentmanifestModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $assets = $this->model->get('assetsModel');
            $pay = $this->model->get('payModel');
            $receive = $this->model->get('receiveModel');
            $costs = $this->model->get('costsModel');
            $sales_model = $this->model->get('salesModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $agent_data = $agent->getAgent($data);

                        $re = $receivable->getAllCosts(array('where'=>'agent_manifest='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'agent_manifest='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }
                        /*$co = $costs->getAllCosts(array('where'=>'code='.$agent_data->code.' AND expect_date ='.$agent_data->agent_manifest_date.' AND money='.$agent_data->document_cost));
                        foreach ($co as $c) {
                            $assets->queryAssets('DELETE FROM assets WHERE costs='.$c->costs_id);
                            $pay->queryCosts('DELETE FROM pay WHERE costs='.$c->costs_id);
                        }*/

                        $receivable->queryCosts('DELETE FROM receivable WHERE agent_manifest='.$data);
                        $obtain->queryObtain('DELETE FROM obtain WHERE agent_manifest='.$data);
                        $owe->queryOwe('DELETE FROM owe WHERE agent_manifest='.$data);
                        $payable->queryCosts('DELETE FROM payable WHERE agent_manifest='.$data);
                        //$costs->queryCosts('DELETE FROM costs WHERE code='.$agent_data->code.' AND expect_date ='.$agent_data->agent_manifest_date.' AND money='.$agent_data->document_cost);
                        $sales_model->querySales('DELETE FROM sales WHERE agent_manifest = '.$data);
                        $agent->deleteAgent($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|agent_manifest|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $agent_data = $agent->getAgent($_POST['data']);

                        $re = $receivable->getAllCosts(array('where'=>'agent_manifest='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'agent_manifest='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }
                        /*$co = $costs->getAllCosts(array('where'=>'code='.$agent_data->code.' AND expect_date ='.$agent_data->agent_manifest_date.' AND money='.$agent_data->document_cost));
                        foreach ($co as $c) {
                            $assets->queryAssets('DELETE FROM assets WHERE costs='.$c->costs_id);
                            $pay->queryCosts('DELETE FROM pay WHERE costs='.$c->costs_id);
                        }*/

                        $receivable->queryCosts('DELETE FROM receivable WHERE agent_manifest='.$_POST['data']);
                        $obtain->queryObtain('DELETE FROM obtain WHERE agent_manifest='.$_POST['data']);
                        $owe->queryOwe('DELETE FROM owe WHERE agent_manifest='.$_POST['data']);
                        $payable->queryCosts('DELETE FROM payable WHERE agent_manifest='.$_POST['data']);
                        //$costs->queryCosts('DELETE FROM costs WHERE code='.$agent_data->code.' AND expect_date ='.$agent_data->agent_manifest_date.' AND money='.$agent_data->document_cost);
                        $sales_model->querySales('DELETE FROM sales WHERE agent_manifest = '.$_POST['data']);
                        $agent->deleteAgent($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|agent_manifest|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function showvendor(){
        if(isset($_POST['sale_report'])){
            $agent_vendor = $this->model->get('agentvendorModel');
            $vendors = $agent_vendor->getAllVendor(array('where'=>'agent_manifest='.$_POST['sale_report']));
            
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
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select></td>';
                    $str .= '<td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="0"  ></td>';
                    $str .= '<td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="0"  ></td>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td>';
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  ></textarea></td></tr></table></td></tr>';   
                    
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


                    $str .= '<tr class="'.$v->agent_manifest.'">';
                    $str .= '<td><input type="checkbox" name="chk" tabindex="'.$v->type.'" data="'.$v->agent_manifest.'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->agent_manifest.'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type" name="cost_type[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->agent_manifest.'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select></td>';
                     $str .= '<td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<tr class="'.$v->agent_manifest.'"><td></td><td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td>';
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  >'.$v->comment .'</textarea></td></tr></table></td></tr>';
                    
                }
            }

            echo $str;
        }
    }

    public function deletevendor(){
        if(isset($_POST['data'])){
            $agent_vendor = $this->model->get('agentvendorModel');
            $agent_manifest = $this->model->get('agentmanifestModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $assets = $this->model->get('assetsModel');
            $pay = $this->model->get('payModel');

            $agent_data = $agent_manifest->getAgent($_POST['data']);

            
                $data = array(
                    'where' => 'agent_manifest='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $agent_vendor->getAllVendor($data);

                $agent_vendor->queryVendor('DELETE FROM agent_vendor WHERE agent_manifest='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (agent_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $pa = $payable->getAllCosts(array('where'=>'agent_manifest='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE agent_manifest='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                

            
            $kvat = 0;
            $vat = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;

                $kvat += $vendor_data->cost;
                $vat += $vendor_data->cost_vat;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

            }

                $owe_data = array(
                    'owe_date' => $agent_data->agent_manifest_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$agent_data->agent_manifest_date),
                    'year' => (int)date('Y',$agent_data->agent_manifest_date),
                    'agent_manifest' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$agent_data->agent_manifest_date) == 1) && ((int)date('m',$agent_data->agent_manifest_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$agent_data->agent_manifest_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$agent_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                    $owe->createOwe($owe_data);


            $data = array(
                'cost' => $agent_data->cost-$kvat,
                'cost_vat' => $agent_data->cost_vat-$vat,
                'profit' => $agent_data->profit+$kvat,
                'profit_vat' => $agent_data->profit_vat+$vat,
                'other_vendor_cost' => $agent_data->other_vendor_cost-$old_cost,

            );
            
            $agent_manifest->updateAgent($data,array('agent_manifest_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $agent = $this->model->get('agentmanifestModel');
            $vendor = $this->model->get('shipmentvendorModel');
            $customer = $this->model->get('customerModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
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
                    if ($val[0] != null && $val[1] != null && $val[2] != null && $val[3] != null && $val[4] != null  ) {

                            if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))) {
                                $staff_data = array(
                                'shipment_vendor_name' => trim($val[1]),
                                'vendor_type' => 1,
                                );
                                $vendor->createVendor($staff_data);
                                $id_staff = $vendor->getLastVendor()->shipment_vendor_id;
                            }
                            else if($vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))){
                                $id_staff = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))->shipment_vendor_id;
                                
                            }

                            if(!$customer->getCustomerByWhere(array('customer_name'=>trim($val[3])))) {
                                $customer_data = array(
                                'customer_name' => trim($val[3]),
                                
                                );
                                $customer->createCustomer($customer_data);
                                $id_customer = $customer->getLastCustomer()->customer_id;
                            }
                            else if($customer->getCustomerByWhere(array('customer_name'=>trim($val[3])))){
                                $id_customer = $customer->getCustomerByWhere(array('customer_name'=>trim($val[3])))->customer_id;
                                
                            }


                            $agent_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[0]));                                      
                            $agent_date = $agent_date-3600;

                            $invoice_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[12]));                                      
                            $invoice_date = $invoice_date-3600;


                            if(!$agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_manifest_date'=>$agent_date,'customer'=>$id_customer))) {
                                $agent_data = array(
                                'staff' => $id_staff,
                                'agent_manifest_date' => $agent_date,
                                'customer' => $id_customer,
                                'code' => trim($val[2]),
                                'comment' => trim($val[4]),
                                'revenue_vat' => trim($val[5]),
                                'cost_sg' => trim($val[6]),
                                'cost_cm' => trim($val[7]),
                                'driver_cost' => trim($val[8]),
                                'commission_cost' => trim($val[9]),
                                'document_cost' => trim($val[11]),
                                'create_user' => $_SESSION['userid_logined'],

                                );
                                if (trim($val[10]) != null) {
                                    
                                    if (!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[10]),'vendor_type'=>5))) {
                                        $vendor_data = array(
                                            'shipment_vendor_name'=> trim($_POST['commission_name']),
                                            'vendor_type' => 5,
                                        );
                                         $vendor->createVendor($vendor_data);

                                         $agent_data['commission'] = $vendor->getLastVendor()->shipment_vendor_id;
                                    }
                                    else{
                                        $agent_data['commission'] = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[10]),'vendor_type'=>5))->shipment_vendor_id;
                                    }
                                     
                                }

                                $agent->createAgent($agent_data);

                                 if(trim($agent_data['commission']) != null){

                                    $owe_data = array(
                                        'owe_date' => $agent_data['agent_manifest_date'],
                                        'vendor' => $agent_data['commission'],
                                        'money' => $agent_data['commission_cost'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                    );

                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $owe->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $agent_data['commission'],
                                        'money' => $agent_data['commission_cost'],
                                        'payable_date' => $agent_data['agent_manifest_date'],
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $agent_data['agent_manifest_date'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                        'code' => $agent_data['code'],
                                        'source' => 5,
                                        'comment' => 'Hoa hồng '.$agent_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 2,
                                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                        
                                    );

                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $payable->createCosts($payable_data);

                                }

                                $owe_data = array(
                                        'owe_date' => $agent_data['agent_manifest_date'],
                                        'vendor' => $agent_data['staff'],
                                        'money' => $agent_data['cost_cm']+$agent_data['cost_sg']+$agent_data['driver_cost']+$agent_data['document_cost'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                        'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                    );
                                if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['cost_cm']+$agent_data['cost_sg']+$agent_data['driver_cost']+$agent_data['document_cost'],
                                    'payable_date' => $agent_data['agent_manifest_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $agent_data['agent_manifest_date'],
                                    'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                    'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                    'code' => $agent_data['code'],
                                    'source' => 6,
                                    'comment' => $agent_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                    
                                );
                                
                                if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }
                                
                                $payable->createCosts($payable_data);

                                $ngay = strtotime('+1 month',$agent_data['invoice_date']);
                                $receivable_data = array(
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['revenue_vat'],
                                    'receivable_date' => $agent_data['agent_manifest_date'],
                                    'expect_date' => $ngay,
                                    'week' => (int)date('W',$ngay),
                                    'year' => (int)date('Y',$ngay),
                                    'code' => $agent_data['code'],
                                    'source' => 6,
                                    'comment' => $agent_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                    
                                );

                                if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$ngay)+1;
                                    }

                                $receivable->createCosts($receivable_data);

                                $obtain_data = array(
                                    'obtain_date' => $agent_data['agent_manifest_date'],
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['revenue_vat'],
                                    'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                    'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                    'agent_manifest' => $agent->getLastAgent()->agent_manifest_id,
                                );

                                if($obtain_data['week'] == 53){
                                        $obtain_data['week'] = 1;
                                        $obtain_data['year'] = $obtain_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $obtain_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }
                                    
                                $obtain->createObtain($obtain_data);
                                

                            }
                            else if($agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_manifest_date'=>$agent_date,'customer'=>$id_customer))) {
                               $id_agent = $agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_manifest_date'=>$agent_date,'customer'=>$id_customer))->agent_manifest_id;
                                $agent_data = array(
                                'staff' => $id_staff,
                                'agent_manifest_date' => $agent_date,
                                'customer' => $id_customer,
                                'code' => trim($val[2]),
                                'comment' => trim($val[4]),
                                'revenue_vat' => trim($val[5]),
                                'cost_sg' => trim($val[6]),
                                'cost_cm' => trim($val[7]),
                                'driver_cost' => trim($val[8]),
                                'commission_cost' => trim($val[9]),
                                'document_cost' => trim($val[11]),
                                'create_user' => $_SESSION['userid_logined'],

                                );
                                if (trim($val[10]) != null) {
                                    
                                    if (!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[10]),'vendor_type'=>5))) {
                                        $vendor_data = array(
                                            'shipment_vendor_name'=> trim($_POST['commission_name']),
                                            'vendor_type' => 5,
                                        );
                                         $vendor->createVendor($vendor_data);

                                         $agent_data['commission'] = $vendor->getLastVendor()->shipment_vendor_id;
                                    }
                                    else{
                                        $agent_data['commission'] = $vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[10]),'vendor_type'=>5))->shipment_vendor_id;
                                    }
                                     
                                }

                                $agent->updateAgent($agent_data,array('agent_manifest_id' => $id_agent));

                                if(trim($agent_data['commission']) != null){

                                    $owe_data = array(
                                        'owe_date' => $agent_data['agent_manifest_date'],
                                        'vendor' => $agent_data['commission'],
                                        'money' => $agent_data['commission_cost'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $owe->updateOwe($owe_data,array('agent_manifest'=>$id_agent));

                                    $payable_data = array(
                                        'vendor' => $agent_data['commission'],
                                        'money' => $agent_data['commission_cost'],
                                        'payable_date' => $agent_data['agent_manifest_date'],
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $agent_data['agent_manifest_date'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                        'code' => $agent_data['code'],
                                        'source' => 5,
                                        'comment' => 'Hoa hồng '.$agent_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 2,
                                        
                                    );

                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $payable->updateCosts($payable_data,array('vendor' => $agent_data['commission'],'agent_manifest'=>$id_agent));

                                }

                                $owe_data = array(
                                        'owe_date' => $agent_data['agent_manifest_date'],
                                        'vendor' => $agent_data['staff'],
                                        'money' => $agent_data['cost_cm']+$agent_data['cost_sg']+$agent_data['driver_cost']+$agent_data['document_cost'],
                                        'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                        'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                    );
                                if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                    $owe->updateOwe($owe_data,array('vendor' => $agent_data['staff'],'agent_manifest'=>$id_agent));

                                $payable_data = array(
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['cost_cm']+$agent_data['cost_sg']+$agent_data['driver_cost']+$agent_data['document_cost'],
                                    'payable_date' => $agent_data['agent_manifest_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $agent_data['agent_manifest_date'],
                                    'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                    'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                    'code' => $agent_data['code'],
                                    'source' => 6,
                                    'comment' => $agent_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    
                                );

                                if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                $payable->updateCosts($payable_data,array('agent_manifest'=>$id_agent));


                                $ngay = strtotime('+1 month',$agent_data['invoice_date']);
                                $receivable_data = array(
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['revenue_vat'],
                                    'receivable_date' => $agent_data['agent_manifest_date'],
                                    'expect_date' => $ngay,
                                    'week' => (int)date('W',$ngay),
                                    'year' => (int)date('Y',$ngay),
                                    'code' => $agent_data['code'],
                                    'source' => 6,
                                    'comment' => $agent_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    
                                );

                                if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$ngay)+1;
                                    }

                                $receivable->updateCosts($receivable_data,array('agent_manifest'=>$id_agent));

                                $obtain_data = array(
                                    'obtain_date' => $agent_data['agent_manifest_date'],
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['revenue_vat'],
                                    'week' => (int)date('W',$agent_data['agent_manifest_date']),
                                    'year' => (int)date('Y',$agent_data['agent_manifest_date']),
                                );
                                if($obtain_data['week'] == 53){
                                        $obtain_data['week'] = 1;
                                        $obtain_data['year'] = $obtain_data['year']+1;
                                    }
                                    if (((int)date('W',$agent_data['agent_manifest_date']) == 1) && ((int)date('m',$agent_data['agent_manifest_date']) == 12) ) {
                                        $obtain_data['year'] = (int)date('Y',$agent_data['agent_manifest_date'])+1;
                                    }

                                $obtain->updateObtain($obtain_data,array('agent_manifest'=>$id_agent));

                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('agentmanifest');
        }
        $this->view->show('agentmanifest/import');

    }
    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>