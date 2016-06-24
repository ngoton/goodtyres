<?php
Class agentController Extends baseController {
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'agent_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = date('d-m-Y', strtotime("last monday"));
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $id = $this->registry->router->param_id;

        $staff_model = $this->model->get('shipmentvendorModel');
        $staffs = $staff_model->getAllVendor(array('where'=>'vendor_type = 1'));
        $this->view->data['staffs'] = $staffs;

        $staffs_model = $this->model->get('staffModel');
        $staff = $staffs_model->getAllStaff();
        $staff_data = array();
        foreach ($staff as $staff) {
            $staff_data['staff_id'][$staff->staff_id] = $staff->staff_id;
            $staff_data['staff_name'][$staff->staff_id] = $staff->staff_name;
        }
        
        $this->view->data['staff'] = $staff_data;

        $agent_model = $this->model->get('agentModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.$ketthuc,
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }
        
        $join = array('table'=>'customer, shipment_vendor','where'=>'customer.customer_id = agent.customer AND agent.staff = shipment_vendor.shipment_vendor_id');
        
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
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
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
                OR name LIKE "%'.$keyword.'%"  
                OR code LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $this->view->data['agents'] = $agent_model->getAllAgent($data,$join);
        $this->view->data['lastID'] = isset($agent_model->getLastAgent()->agent_id)?$agent_model->getLastAgent()->agent_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('agent/index');
    }

    public function invoice(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $receivable = $this->model->get('receivableModel');

            $sale = $this->model->get('agentModel');
            $sale_data = $sale->getAgent($_POST['data']);

            $data = array(
                        'invoice_date' => strtotime($_POST['day']),
                        );
          
            $sale->updateAgent($data,array('agent_id' => $_POST['data']));

            
            $receivable_data = array(
                'invoice_date' => $data['invoice_date'],
                
            );
            
            $receivable->updateCosts($receivable_data,array('agent'=>trim($_POST['data'])));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."invoice"."|".$_POST['data']."|agent|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $agent_cost = $this->model->get('agentcostModel');
            $costs = $agent_cost->getAllCosts();
            $cost_data = array();
            foreach ($costs as $cost) {
                $cost_data[$cost->agent_cost_id]['cost'] = $cost->cost;
                $cost_data[$cost->agent_cost_id]['offer'] = $cost->offer;
            }

            $agent = $this->model->get('agentModel');
            $customer = $this->model->get('customerModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $sales_model = $this->model->get('salesModel');
            $pending_payable = $this->model->get('pendingpayableModel');
            $data = array(
                        'm' => trim($_POST['m']),
                        's' => trim($_POST['s']),
                        'c' => trim($_POST['c']),
                        'agent_date' => strtotime(trim($_POST['agent_date'])),
                        'code' => trim($_POST['code']),
                        'staff' => trim($_POST['staff']),
                        'create_user' => $_SESSION['userid_logined'],
                        'name' => trim($_POST['name']),
                        'cost_1' => trim($_POST['cost_1']),
                        'cost_2' => trim($_POST['cost_2']),
                        'cost_3' => trim(str_replace(',','',$_POST['cost_3'])),
                        'cost_4' => trim($_POST['cost_4']),
                        'cost_5' => trim($_POST['cost_5']),
                        'cost_6' => trim($_POST['cost_6']),
                        'cost_7' => trim($_POST['cost_7']),
                        'cost_8' => trim($_POST['cost_8']),
                        'cost_9' => trim($_POST['cost_9']),
                        'cost_10' => trim($_POST['cost_10']),
                        'cost_11' => trim($_POST['cost_11']),
                        'cost_12' => trim($_POST['cost_12']),
                        'cost_13' => trim($_POST['cost_13']),
                        'cost_14' => trim($_POST['cost_14']),
                        'cost_15' => trim($_POST['cost_15']),
                        'cost_16' => trim($_POST['cost_16']),
                        'cost_17' => trim($_POST['cost_17']),
                        'cost_18' => trim($_POST['cost_18']),
                        'bill_cost' => trim(str_replace(',','',$_POST['bill_cost'])),
                        'excess_cost' => trim(str_replace(',','',$_POST['excess_cost'])),
                        'excess_comment' => trim($_POST['excess_comment']),
                        'document_cost' => trim(str_replace(',','',$_POST['document_cost'])),
                        'pay_cost' => trim(str_replace(',','',$_POST['pay_cost'])),
                        'cost_1_over' => trim(str_replace(',','',$_POST['cost_1_over'])),
                        'cost_4_over' => trim(str_replace(',','',$_POST['cost_4_over'])),
                        'cost_6_over' => trim(str_replace(',','',$_POST['cost_6_over'])),
                        'cost_8_over' => trim(str_replace(',','',$_POST['cost_8_over'])),
                        'cost_11_over' => trim(str_replace(',','',$_POST['cost_11_over'])),
                        'cost_6_bill_over' => trim($_POST['cost_6_bill_over']),
                        'cost_8_cont_over' => trim($_POST['cost_8_cont_over']),
                        'cost_6_over_offer' => trim(str_replace(',','',$_POST['cost_6_over_offer'])),
                        'cost_8_over_offer' => trim(str_replace(',','',$_POST['cost_8_over_offer'])),
                        'offer_other' => trim(str_replace(',','',$_POST['offer_other'])),
                        );
            $total_cost = ($cost_data[1]['cost']*$data['cost_1'])+($cost_data[2]['cost']*$data['cost_2'])
                            +($cost_data[3]['cost']*$data['cost_3'])
                            +($cost_data[4]['cost']*$data['cost_4'])
                            +($cost_data[5]['cost']*$data['cost_5'])
                            +($cost_data[6]['cost']*$data['cost_6'])
                            +($cost_data[7]['cost']*$data['cost_7'])
                            +($cost_data[8]['cost']*$data['cost_8'])
                            +($cost_data[9]['cost']*$data['cost_9'])
                            +($cost_data[10]['cost']*$data['cost_10'])
                            +($cost_data[11]['cost']*$data['cost_11'])
                            +($cost_data[12]['cost']*$data['cost_12'])
                            +($cost_data[13]['cost']*$data['cost_13'])
                            +($cost_data[14]['cost']*$data['cost_14'])
                            +($cost_data[15]['cost']*$data['cost_15'])
                            +($cost_data[16]['cost']*$data['cost_16'])
                            +($cost_data[17]['cost']*$data['cost_17'])
                            +($cost_data[18]['cost']*$data['cost_18'])
            ;
            $total_offer = ($cost_data[1]['offer']*$data['cost_1'])+($cost_data[2]['offer']*$data['cost_2'])
                            +($cost_data[3]['offer']*$data['cost_3'])
                            +($cost_data[4]['offer']*$data['cost_4'])
                            +($cost_data[5]['offer']*$data['cost_5'])
                            +($cost_data[6]['offer']*$data['cost_6'])
                            +($cost_data[7]['offer']*$data['cost_7'])
                            +($cost_data[8]['offer']*$data['cost_8'])
                            +($cost_data[9]['offer']*$data['cost_9'])
                            +($cost_data[10]['offer']*$data['cost_10'])
                            +($cost_data[11]['offer']*$data['cost_11'])
                            +($cost_data[16]['offer']*$data['cost_16'])
            ;

            $data['total_cost'] = $total_cost+$data['excess_cost']+$data['document_cost']+$data['pay_cost']+$data['cost_1_over']+$data['cost_4_over']+$data['cost_11_over']+($data['cost_6_bill_over']*$data['cost_6_over'])+($data['cost_8_cont_over']*$data['cost_8_over']);
            $data['total_offer'] = $data['offer_other']+(($total_offer+500000+($data['cost_6_bill_over']*$data['cost_6_over_offer'])+($data['cost_8_cont_over']*$data['cost_8_over_offer']))*1.1);

            $data['estimate_cost'] = ($cost_data[17]['cost']*$data['cost_17'])+($cost_data[18]['cost']*$data['cost_18'])+$data['document_cost']+$data['pay_cost'];

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
            


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                $agent_data = $agent->getAgent($_POST['yes']);
                
                    $agent->updateAgent($data,array('agent_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $salesdata = $sales_model->getSalesByWhere(array('agent'=>$_POST['yes']));

                    if ($salesdata) {
                        $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['name'],
                            'revenue' => $data['total_offer'],
                            'cost' => $data['total_cost'],
                            'profit' => $data['total_offer']-$data['total_cost'],
                            'sales_create_time' => $data['agent_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'agent' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
                    }
                    elseif (!$salesdata) {
                        $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['name'],
                            'revenue' => $data['total_offer'],
                            'cost' => $data['total_cost'],
                            'profit' => $data['total_offer']-$data['total_cost'],
                            'sales_create_time' => $data['agent_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'agent' => $_POST['yes'],
                            'sales_create_user' => $_SESSION['userid_logined'],
                        );
                        $sales_model->createSales($data_sales);
                    }

                    if($data['total_cost'] != $agent_data->total_cost){
                        $owe_data = array(
                                'owe_date' => $data['agent_date'],
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'] + ($data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']),
                                'week' => (int)date('W',$data['agent_date']),
                                'year' => (int)date('Y',$data['agent_date']),
                                'agent' => $_POST['yes'],
                            );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_date'])+1;
                        }

                            $owe->updateOwe($owe_data,array('agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'money'=>$agent_data->bill_cost+($agent_data->total_cost-($cost_data[17]['cost']*$agent_data->cost_17)-($cost_data[18]['cost']*$agent_data->cost_18)-$agent_data->document_cost-$agent_data->pay_cost)));

                        //$payable->queryCosts('DELETE FROM payable WHERE money=200000 AND agent = '.$_POST['yes'].' AND vendor = '.$data['staff']);

                        if(date("w", $data['agent_date'])>=4){
                        $ngay_du = strtotime('next Monday',$data['agent_date']);
                        }
                        else{
                            $ngay_du = strtotime('next Saturday',$data['agent_date']);
                        }
                        
                        $payable_data = array(
                            'vendor' => $data['staff'],
                            'money' => $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']-200000,
                            'payable_date' => $data['agent_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $ngay_du,
                            'week' => (int)date('W',$ngay_du),
                            'year' => (int)date('Y',$ngay_du),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => $data['name'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $_POST['yes'],
                            'cost_type' => 6,
                            'check_vat' => 0,
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$ngay_du) == 1) && ((int)date('m',$ngay_du) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$ngay_du)+1;
                        }

                        $check = $payable->getCostsByWhere(array('agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>6));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable->updateCosts($payable_data,array('agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>6));

                        $ngay_du2 = '30-'.date('m-Y',$data['agent_date']);

                        $payable_data = array(
                            'vendor' => $data['staff'],
                            'money' => 200000,
                            'payable_date' => $data['agent_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => strtotime($ngay_du2),
                            'week' => (int)date('W',strtotime($ngay_du2)),
                            'year' => (int)date('Y',strtotime($ngay_du2)),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => 'Xăng xe code '.$data['code'].$data['name'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $_POST['yes'],
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

                        $check = $payable->getCostsByWhere(array('money'=>200000,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable->updateCosts($payable_data,array('money'=>200000,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));

                        if($data['bill_cost'] > 0 && $agent_data->bill_cost > 0){
                            $payable_data = array(
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'],
                                'payable_date' => $data['agent_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => strtotime($ngay_du2),
                                'week' => (int)date('W',strtotime($ngay_du2)),
                                'year' => (int)date('Y',strtotime($ngay_du2)),
                                'code' => $data['code'],
                                'source' => 4,
                                'comment' => 'Phí mua cuốn tờ khai quá cảnh',
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent' => $_POST['yes'],
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

                            $check = $payable->getCostsByWhere(array('money'=>$agent_data->bill_cost,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                            $payable->updateCosts($payable_data,array('money'=>$agent_data->bill_cost,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));
                        }
                        else if($data['bill_cost'] > 0 && $agent_data->bill_cost <= 0){
                            $payable_data = array(
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'],
                                'payable_date' => $data['agent_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => strtotime($ngay_du2),
                                'week' => (int)date('W',strtotime($ngay_du2)),
                                'year' => (int)date('Y',strtotime($ngay_du2)),
                                'code' => $data['code'],
                                'source' => 4,
                                'comment' => 'Phí mua biên lai',
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent' => $_POST['yes'],
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

                            $payable->createCosts($payable_data);
                        }
                        else if($data['bill_cost'] <= 0 && $agent_data->bill_cost > 0){
                            
                            $payable->queryCosts('DELETE FROM payable WHERE agent='.$_POST['yes'].' AND vendor='.$agent_data->staff.' AND money='.$agent_data->bill_cost);
                        }

                        
                    }

                    else{
                        $owe_data = array(
                                'owe_date' => $data['agent_date'],
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'] + ($data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']),
                                'week' => (int)date('W',$data['agent_date']),
                                'year' => (int)date('Y',$data['agent_date']),
                                'agent' => $_POST['yes'],
                            );
                        if($owe_data['week'] == 53){
                            $owe_data['week'] = 1;
                            $owe_data['year'] = $owe_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                            $owe_data['year'] = (int)date('Y',$data['agent_date'])+1;
                        }

                            $owe->updateOwe($owe_data,array('agent'=>$_POST['yes'],'money'=>$agent_data->bill_cost+($agent_data->total_cost-($cost_data[17]['cost']*$agent_data->cost_17)-($cost_data[18]['cost']*$agent_data->cost_18)-$agent_data->document_cost-$agent_data->pay_cost)));

                        
                        $ngay_du2 = '30-'.date('m-Y',$data['agent_date']);

                        $payable_data = array(
                            'vendor' => $data['staff'],
                            'money' => 200000,
                            'payable_date' => $data['agent_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => strtotime($ngay_du2),
                            'week' => (int)date('W',strtotime($ngay_du2)),
                            'year' => (int)date('Y',strtotime($ngay_du2)),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => 'Xăng xe code '.$data['code'].$data['name'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $_POST['yes'],
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

                        $check = $payable->getCostsByWhere(array('agent'=>$_POST['yes'],'payable_date'=>$agent_data->agent_date,'money'=>200000,'cost_type'=>7));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                        $payable->updateCosts($payable_data,array('agent'=>$_POST['yes'],'payable_date'=>$agent_data->agent_date,'money'=>200000,'cost_type'=>7));

                        if($data['bill_cost'] > 0 && $agent_data->bill_cost > 0){
                            $payable_data = array(
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'],
                                'payable_date' => $data['agent_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => strtotime($ngay_du2),
                                'week' => (int)date('W',strtotime($ngay_du2)),
                                'year' => (int)date('Y',strtotime($ngay_du2)),
                                'code' => $data['code'],
                                'source' => 4,
                                'comment' => 'Phí mua cuốn tờ khai quá cảnh',
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent' => $_POST['yes'],
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

                            $check = $payable->getCostsByWhere(array('money'=>$agent_data->bill_cost,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                            $payable->updateCosts($payable_data,array('money'=>$agent_data->bill_cost,'agent'=>$_POST['yes'],'vendor'=>$agent_data->staff,'cost_type'=>7));
                        }
                        else if($data['bill_cost'] > 0 && $agent_data->bill_cost <= 0){
                            $payable_data = array(
                                'vendor' => $data['staff'],
                                'money' => $data['bill_cost'],
                                'payable_date' => $data['agent_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => strtotime($ngay_du2),
                                'week' => (int)date('W',strtotime($ngay_du2)),
                                'year' => (int)date('Y',strtotime($ngay_du2)),
                                'code' => $data['code'],
                                'source' => 4,
                                'comment' => 'Phí mua biên lai',
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 2,
                                'agent' => $_POST['yes'],
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

                            $payable->createCosts($payable_data);
                        }
                        else if($data['bill_cost'] <= 0 && $agent_data->bill_cost > 0){
                            
                            $payable->queryCosts('DELETE FROM payable WHERE agent='.$_POST['yes'].' AND vendor='.$agent_data->staff.' AND money='.$agent_data->bill_cost);
                        }


                        if(date("w", $data['agent_date'])>=4){
                        $ngay_du = strtotime('next Monday',$data['agent_date']);
                        }
                        else{
                            $ngay_du = strtotime('next Saturday',$data['agent_date']);
                        }
                        
                        $payable_data = array(
                            'vendor' => $data['staff'],
                            'money' => $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']-200000,
                            'payable_date' => $data['agent_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $ngay_du,
                            'week' => (int)date('W',$ngay_du),
                            'year' => (int)date('Y',$ngay_du),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => $data['name'],
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $_POST['yes'],
                            'cost_type' => 6,
                            'check_vat' => 0,
                            'approve' => null,
                        );

                        if($payable_data['week'] == 53){
                            $payable_data['week'] = 1;
                            $payable_data['year'] = $payable_data['year']+1;
                        }
                        if (((int)date('W',$ngay_du) == 1) && ((int)date('m',$ngay_du) == 12) ) {
                            $payable_data['year'] = (int)date('Y',$ngay_du)+1;
                        }

                        $check = $payable->getCostsByWhere(array('agent'=>$_POST['yes'],'payable_date'=>$agent_data->agent_date,'cost_type'=>6));

                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                $payable_data['approve'] = 10;
                            }

                        $payable->updateCosts($payable_data,array('agent'=>$_POST['yes'],'payable_date'=>$agent_data->agent_date,'cost_type'=>6));

                        
                    }

                    //$payable->queryCosts('DELETE FROM payable WHERE agent = '.$_POST['yes'].' AND vendor = '.$data['staff']);

                    


                    $ngay = strtotime('15-'.date('m-Y',strtotime('+1 month',$data['agent_date'])));
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['total_offer'],
                        'receivable_date' => $data['agent_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 4,
                        'comment' => $data['name'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'check_vat' => 1,
                        
                    );

                    if($receivable_data['week'] == 53){
                        $receivable_data['week'] = 1;
                        $receivable_data['year'] = $receivable_data['year']+1;
                    }
                    if (((int)date('W',$ngay) == 1) && ((int)date('m',$ngay) == 12) ) {
                        $receivable_data['year'] = (int)date('Y',$ngay)+1;
                    }

                    $receivable->updateCosts($receivable_data,array('money'=>$agent_data->total_offer,'agent'=>trim($_POST['yes'])));

                    if($data['total_offer'] != $agent_data->total_offer){
                        $obtain_data = array(
                            'obtain_date' => $data['agent_date'],
                            'customer' => $data['customer'],
                            'money' => $data['total_offer'],
                            'week' => (int)date('W',$data['agent_date']),
                            'year' => (int)date('Y',$data['agent_date']),
                            'agent' => $_POST['yes'],
                        );

                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                            $obtain['year'] = (int)date('Y',$data['agent_date'])+1;
                        }

                        $obtain->updateObtain($obtain_data,array('agent'=>$_POST['yes'],'money'=>$agent_data->total_offer));
                    }
                    else if($data['agent_date'] != $agent_data->agent_date){
                        $obtain_data = array(
                            'obtain_date' => $data['agent_date'],
                            'customer' => $data['customer'],
                            'money' => $data['total_offer'],
                            'week' => (int)date('W',$data['agent_date']),
                            'year' => (int)date('Y',$data['agent_date']),
                            'agent' => $_POST['yes'],
                        );

                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                            $obtain['year'] = (int)date('Y',$data['agent_date'])+1;
                        }

                        $obtain->updateObtain($obtain_data,array('agent'=>trim($_POST['yes']),'obtain_date'=>$agent_data->agent_date));
                    }

                    $data_pending = array(
                        'code' => $data['code'],
                        'revenue' => $data['total_offer'],
                        'cost' => $data['total_cost'],
                        'money' => $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']+$data['bill_cost'],
                        'comment' => 'Chi phí code '.$data['code'].' '.$data['name'],
                        'approve' => null,
                    );

                    $check = $pending_payable->getCostsByWhere(array('agent'=>$_POST['yes']));

                    if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                        $data_pending['approve'] = 10;
                    }

                    $pending_payable->updateCosts($data_pending,array('agent'=>trim($_POST['yes'])));


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|agent|".implode("-",$data)."\n"."\r\n";
                        
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

                    $agent_code = $agent->getLastAgent()->code;

                    $data['agent_create_user'] = $_SESSION['userid_logined'];
                
                    $agent->createAgent($data);
                    echo "Thêm thành công";

                    $data_sales = array(
                            'customer' => $data['customer'],
                            'code' => $data['code'],
                            'comment' => $data['name'],
                            'revenue' => $data['total_offer'],
                            'cost' => $data['total_cost'],
                            'profit' => $data['total_offer']-$data['total_cost'],
                            'sales_create_time' => $data['agent_date'],
                            'm' => $data['m'],
                            's' => $data['s'],
                            'c' => $data['c'],
                            'agent' => $agent->getLastAgent()->agent_id,
                            'sales_create_user' => $_SESSION['userid_logined'],
                        );
                        $sales_model->createSales($data_sales);
                    

                    if (substr($agent_code, 0, 4) != substr($data['code'], 0, 4)) {
                        $ngay = strtotime('15-'.date('m-Y',strtotime('+1 month',$data['agent_date'])));
                        $receivable_data = array(
                            'customer' => $data['customer'],
                            'money' => 1100000,
                            'receivable_date' => $data['agent_date'],
                            'expect_date' => $ngay,
                            'week' => (int)date('W',$ngay),
                            'year' => (int)date('Y',$ngay),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => 'Phí đại lý tháng '.date('Y',$data['agent_date']),
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $agent->getLastAgent()->agent_id,
                            'check_vat' => 1,
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
                            'obtain_date' => $data['agent_date'],
                            'customer' => $data['customer'],
                            'money' => 1100000,
                            'week' => (int)date('W',$data['agent_date']),
                            'year' => (int)date('Y',$data['agent_date']),
                            'agent' => $agent->getLastAgent()->agent_id,
                        );

                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                            $obtain['year'] = (int)date('Y',$data['agent_date'])+1;
                        }

                        $obtain->createObtain($obtain_data);
                    }


                    $owe_data = array(
                            'owe_date' => $data['agent_date'],
                            'vendor' => $data['staff'],
                            'money' => $data['bill_cost'] + $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost'],
                            'week' => (int)date('W',$data['agent_date']),
                            'year' => (int)date('Y',$data['agent_date']),
                            'agent' => $agent->getLastAgent()->agent_id,
                        );

                    if($owe_data['week'] == 53){
                        $owe_data['week'] = 1;
                        $owe_data['year'] = $owe_data['year']+1;
                    }
                    if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                        $owe_data['year'] = (int)date('Y',$data['agent_date'])+1;
                    }

                        $owe->createOwe($owe_data);

                    if(date("w", $data['agent_date'])>=4){
                        $ngay_du = strtotime('next Monday',$data['agent_date']);
                    }
                    else{
                        $ngay_du = strtotime('next Saturday',$data['agent_date']);
                    }
                    
                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']-200000,
                        'payable_date' => $data['agent_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $ngay_du,
                        'week' => (int)date('W',$ngay_du),
                        'year' => (int)date('Y',$ngay_du),
                        'code' => $data['code'],
                        'source' => 4,
                        'comment' => $data['name'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent' => $agent->getLastAgent()->agent_id,
                        'cost_type' => 6,
                        'check_vat' => 0,
                    );

                    if($payable_data['week'] == 53){
                        $payable_data['week'] = 1;
                        $payable_data['year'] = $payable_data['year']+1;
                    }
                    if (((int)date('W',$ngay_du) == 1) && ((int)date('m',$ngay_du) == 12) ) {
                        $payable_data['year'] = (int)date('Y',$ngay_du)+1;
                    }

                    $payable->createCosts($payable_data);

                    $ngay_du2 = '30-'.date('m-Y');

                    $payable_data = array(
                        'vendor' => $data['staff'],
                        'money' => 200000,
                        'payable_date' => $data['agent_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => strtotime($ngay_du2),
                        'week' => (int)date('W',strtotime($ngay_du2)),
                        'year' => (int)date('Y',strtotime($ngay_du2)),
                        'code' => $data['code'],
                        'source' => 4,
                        'comment' => 'Xăng xe code '.$data['code'].$data['name'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent' => $agent->getLastAgent()->agent_id,
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

                    if($data['bill_cost'] > 0){
                        $payable_data = array(
                            'vendor' => $data['staff'],
                            'money' => $data['bill_cost'],
                            'payable_date' => $data['agent_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => strtotime($ngay_du2),
                            'week' => (int)date('W',strtotime($ngay_du2)),
                            'year' => (int)date('Y',strtotime($ngay_du2)),
                            'code' => $data['code'],
                            'source' => 4,
                            'comment' => 'Phí mua cuốn tờ khai quá cảnh',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 2,
                            'agent' => $agent->getLastAgent()->agent_id,
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
                    }



                    $ngay = strtotime('15-'.date('m-Y',strtotime('+1 month',$data['agent_date'])));
                    $receivable_data = array(
                        'customer' => $data['customer'],
                        'money' => $data['total_offer'],
                        'receivable_date' => $data['agent_date'],
                        'expect_date' => $ngay,
                        'week' => (int)date('W',$ngay),
                        'year' => (int)date('Y',$ngay),
                        'code' => $data['code'],
                        'source' => 4,
                        'comment' => $data['name'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 2,
                        'agent' => $agent->getLastAgent()->agent_id,
                        'check_vat' => 1,
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
                        'obtain_date' => $data['agent_date'],
                        'customer' => $data['customer'],
                        'money' => $data['total_offer'],
                        'week' => (int)date('W',$data['agent_date']),
                        'year' => (int)date('Y',$data['agent_date']),
                        'agent' => $agent->getLastAgent()->agent_id,
                    );

                    if($obtain_data['week'] == 53){
                        $obtain_data['week'] = 1;
                        $obtain_data['year'] = $obtain_data['year']+1;
                    }
                    if (((int)date('W',$data['agent_date']) == 1) && ((int)date('m',$data['agent_date']) == 12) ) {
                        $obtain['year'] = (int)date('Y',$data['agent_date'])+1;
                    }

                    $obtain->createObtain($obtain_data);

                    $data_pending = array(
                        'code' => $data['code'],
                        'revenue' => $data['total_offer'],
                        'cost' => $data['total_cost'],
                        'agent' => $agent->getLastAgent()->agent_id,
                        'money' => $data['total_cost']-($cost_data[17]['cost']*$data['cost_17'])-($cost_data[18]['cost']*$data['cost_18'])-$data['document_cost']-$data['pay_cost']+$data['bill_cost'],
                        'comment' => 'Chi phí code '.$data['code'].' '.$data['name'],
                    );

                    $pending_payable->createCosts($data_pending);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$agent->getLastAgent()->agent_id."|agent|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $agent = $this->model->get('agentModel');
            $receivable = $this->model->get('receivableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $re = $receivable->getAllCosts(array('where'=>'agent='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'agent='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE agent='.$data);
                        $obtain->queryObtain('DELETE FROM obtain WHERE agent='.$data);
                        $owe->queryOwe('DELETE FROM owe WHERE agent='.$data);
                        $payable->queryCosts('DELETE FROM payable WHERE agent='.$data);
                        $sales_model->querySales('DELETE FROM sales WHERE agent = '.$data);
                        $agent->deleteAgent($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|agent|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $re = $receivable->getAllCosts(array('where'=>'agent='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'agent='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE agent='.$_POST['data']);
                        $obtain->queryObtain('DELETE FROM obtain WHERE agent='.$_POST['data']);
                        $owe->queryOwe('DELETE FROM owe WHERE agent='.$_POST['data']);
                        $payable->queryCosts('DELETE FROM payable WHERE agent='.$_POST['data']);
                        $sales_model->querySales('DELETE FROM sales WHERE agent = '.$_POST['data']);
                        $agent->deleteAgent($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|agent|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $agent = $this->model->get('agentModel');
            $agent_cost = $this->model->get('agentcostModel');
            $staff = $this->model->get('shipmentvendorModel');
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
            
            $costs = $agent_cost->getAllCosts();
            $cost_data = array();
            foreach ($costs as $cost) {
                $cost_data[$cost->agent_cost_id]['cost'] = $cost->cost;
                $cost_data[$cost->agent_cost_id]['offer'] = $cost->offer;
            }

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

                            if(!$staff->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))) {
                                $staff_data = array(
                                'shipment_vendor_name' => trim($val[1]),
                                'vendor_type' => 1,
                                );
                                $staff->createVendor($staff_data);
                                $id_staff = $staff->getLastVendor()->shipment_vendor_id;
                            }
                            else if($staff->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))){
                                $id_staff = $staff->getVendorByWhere(array('shipment_vendor_name'=>trim($val[1])))->shipment_vendor_id;
                                
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


                            if(!$agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_date'=>$agent_date,'customer'=>$id_customer,'name'=>trim($val[4])))) {
                                $agent_data = array(
                                'staff' => $id_staff,
                                'name' => trim($val[4]),
                                'agent_date' => $agent_date,
                                'customer' => $id_customer,
                                'code' => trim($val[2]),
                                'cost_1' => trim($val[5]),
                                'cost_2' => trim($val[6]),
                                'cost_3' => trim($val[7]),
                                'cost_4' => trim($val[8]),
                                'cost_5' => trim($val[9]),
                                'cost_6' => trim($val[10]),
                                'cost_7' => trim($val[11]),
                                'cost_8' => trim($val[12]),
                                'cost_9' => trim($val[13]),
                                'cost_10' => trim($val[14]),
                                'cost_11' => trim($val[15]),
                                'cost_12' => trim($val[16]),
                                'cost_13' => trim($val[17]),
                                'cost_14' => trim($val[18]),
                                'cost_15' => trim($val[19]),
                                'cost_16' => trim($val[20]),
                                'cost_17' => trim($val[21]),
                                'cost_18' => trim($val[22]),

                                );

                                $total_cost = ($cost_data[1]['cost']*$agent_data['cost_1'])+($cost_data[2]['cost']*$agent_data['cost_2'])
                                                            +($cost_data[3]['cost']*$agent_data['cost_3'])
                                                            +($cost_data[4]['cost']*$agent_data['cost_4'])
                                                            +($cost_data[5]['cost']*$agent_data['cost_5'])
                                                            +($cost_data[6]['cost']*$agent_data['cost_6'])
                                                            +($cost_data[7]['cost']*$agent_data['cost_7'])
                                                            +($cost_data[8]['cost']*$agent_data['cost_8'])
                                                            +($cost_data[9]['cost']*$agent_data['cost_9'])
                                                            +($cost_data[10]['cost']*$agent_data['cost_10'])
                                                            +($cost_data[11]['cost']*$agent_data['cost_11'])
                                                            +($cost_data[12]['cost']*$agent_data['cost_12'])
                                                            +($cost_data[13]['cost']*$agent_data['cost_13'])
                                                            +($cost_data[14]['cost']*$agent_data['cost_14'])
                                                            +($cost_data[15]['cost']*$agent_data['cost_15'])
                                                            +($cost_data[16]['cost']*$agent_data['cost_16'])
                                                            +($cost_data[17]['cost']*$agent_data['cost_17'])
                                                            +($cost_data[18]['cost']*$agent_data['cost_18'])
                                            ;
                                $total_offer = ($cost_data[1]['offer']*$agent_data['cost_1'])+($cost_data[2]['offer']*$agent_data['cost_2'])
                                                +($cost_data[3]['offer']*$agent_data['cost_3'])
                                                +($cost_data[4]['offer']*$agent_data['cost_4'])
                                                +($cost_data[5]['offer']*$agent_data['cost_5'])
                                                +($cost_data[6]['offer']*$agent_data['cost_6'])
                                                +($cost_data[7]['offer']*$agent_data['cost_7'])
                                                +($cost_data[8]['offer']*$agent_data['cost_8'])
                                                +($cost_data[9]['offer']*$agent_data['cost_9'])
                                                +($cost_data[10]['offer']*$agent_data['cost_10'])
                                                +($cost_data[11]['offer']*$agent_data['cost_11'])
                                                
                                                +($cost_data[16]['offer']*$agent_data['cost_16'])
                                ;

                                $agent_data['total_cost'] = $total_cost;
                                $agent_data['total_offer'] = $total_offer+500000;

                                $agent->createAgent($agent_data);

                                $owe_data = array(
                                    'owe_date' => $agent_data['agent_date'],
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['total_cost'],
                                    'week' => (int)date('W',$agent_data['agent_date']),
                                    'year' => (int)date('Y',$agent_data['agent_date']),
                                    'agent' => $agent->getLastAgent()->agent_id,
                                );

                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$agent_data['agent_date']) == 1) && ((int)date('m',$agent_data['agent_date']) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$agent_data['agent_date'])+1;
                                }

                                $owe->createOwe($owe_data);

                                if(date("w", $agent_data['agent_date'])>=4){
                                    $ngay_du = strtotime('next Monday',$agent_data['agent_date']);
                                }
                                else{
                                    $ngay_du = strtotime('last Saturday',$agent_data['agent_date']);
                                }
                                
                                $payable_data = array(
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['total_cost'],
                                    'payable_date' => $agent_data['agent_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $ngay_du,
                                    'week' => (int)date('W',$ngay_du),
                                    'year' => (int)date('Y',$ngay_du),
                                    'code' => $agent_data['code'],
                                    'source' => 4,
                                    'comment' => $agent_data['name'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    'agent' => $agent->getLastAgent()->agent_id,
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$ngay_du) == 1) && ((int)date('m',$ngay_du) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$ngay_du)+1;
                                }
                                
                                $payable->createCosts($payable_data);

                                $ngay = strtotime('15-'.date('m-Y',strtotime('+1 month',$agent_data['agent_date'])));
                                $receivable_data = array(
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['total_offer'],
                                    'receivable_date' => $agent_data['agent_date'],
                                    'expect_date' => $ngay,
                                    'week' => (int)date('W',$ngay),
                                    'year' => (int)date('Y',$ngay),
                                    'code' => $agent_data['code'],
                                    'source' => 4,
                                    'comment' => $agent_data['name'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                    'agent' => $agent->getLastAgent()->agent_id,
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
                                    'obtain_date' => $agent_data['agent_date'],
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['total_offer'],
                                    'week' => (int)date('W',$agent_data['agent_date']),
                                    'year' => (int)date('Y',$agent_data['agent_date']),
                                    'agent' => $agent->getLastAgent()->agent_id,
                                );

                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$agent_data['agent_date']) == 1) && ((int)date('m',$agent_data['agent_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$agent_data['agent_date'])+1;
                                }

                                $obtain->createObtain($obtain_data);


                                

                            }
                            else if($agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_date'=>$agent_date,'customer'=>$id_customer,'name'=>trim($val[4])))){
                                $id_agent = $agent->getAgentByWhere(array('staff'=>$id_staff,'code'=>trim($val[2]),'agent_date'=>$agent_date,'customer'=>$id_customer,'name'=>trim($val[4])))->agent_id;
                                $agent_data = array(
                                'staff' => $id_staff,
                                'name' => trim($val[4]),
                                'agent_date' => $agent_date,
                                'customer' => $id_customer,
                                'code' => trim($val[2]),
                                'cost_1' => trim($val[5]),
                                'cost_2' => trim($val[6]),
                                'cost_3' => trim($val[7]),
                                'cost_4' => trim($val[8]),
                                'cost_5' => trim($val[9]),
                                'cost_6' => trim($val[10]),
                                'cost_7' => trim($val[11]),
                                'cost_8' => trim($val[12]),
                                'cost_9' => trim($val[13]),
                                'cost_10' => trim($val[14]),
                                'cost_11' => trim($val[15]),
                                'cost_12' => trim($val[16]),
                                'cost_13' => trim($val[17]),
                                'cost_14' => trim($val[18]),
                                'cost_15' => trim($val[19]),
                                'cost_16' => trim($val[20]),
                                'cost_17' => trim($val[21]),
                                'cost_18' => trim($val[22]),

                                );

                                $total_cost = ($cost_data[1]['cost']*$agent_data['cost_1'])+($cost_data[2]['cost']*$agent_data['cost_2'])
                                                            +($cost_data[3]['cost']*$agent_data['cost_3'])
                                                            +($cost_data[4]['cost']*$agent_data['cost_4'])
                                                            +($cost_data[5]['cost']*$agent_data['cost_5'])
                                                            +($cost_data[6]['cost']*$agent_data['cost_6'])
                                                            +($cost_data[7]['cost']*$agent_data['cost_7'])
                                                            +($cost_data[8]['cost']*$agent_data['cost_8'])
                                                            +($cost_data[9]['cost']*$agent_data['cost_9'])
                                                            +($cost_data[10]['cost']*$agent_data['cost_10'])
                                                            +($cost_data[11]['cost']*$agent_data['cost_11'])
                                                            +($cost_data[12]['cost']*$agent_data['cost_12'])
                                                            +($cost_data[13]['cost']*$agent_data['cost_13'])
                                                            +($cost_data[14]['cost']*$agent_data['cost_14'])
                                                            +($cost_data[15]['cost']*$agent_data['cost_15'])
                                                            +($cost_data[16]['cost']*$agent_data['cost_16'])
                                                            +($cost_data[17]['cost']*$agent_data['cost_17'])
                                                            +($cost_data[18]['cost']*$agent_data['cost_18'])
                                            ;
                                $total_offer = ($cost_data[1]['offer']*$agent_data['cost_1'])+($cost_data[2]['offer']*$agent_data['cost_2'])
                                                +($cost_data[3]['offer']*$agent_data['cost_3'])
                                                +($cost_data[4]['offer']*$agent_data['cost_4'])
                                                +($cost_data[5]['offer']*$agent_data['cost_5'])
                                                +($cost_data[6]['offer']*$agent_data['cost_6'])
                                                +($cost_data[7]['offer']*$agent_data['cost_7'])
                                                +($cost_data[8]['offer']*$agent_data['cost_8'])
                                                +($cost_data[9]['offer']*$agent_data['cost_9'])
                                                +($cost_data[10]['offer']*$agent_data['cost_10'])
                                                +($cost_data[11]['offer']*$agent_data['cost_11'])
                                                +($cost_data[16]['offer']*$agent_data['cost_16'])
                                ;

                                $agent_data['total_cost'] = $total_cost;
                                $agent_data['total_offer'] = $total_offer+500000;

                                $agent->updateAgent($agent_data,array('agent_id' => $id_agent));

                                $owe_data = array(
                                    'owe_date' => $agent_data['agent_date'],
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['total_cost'],
                                    'week' => (int)date('W',$agent_data['agent_date']),
                                    'year' => (int)date('Y',$agent_data['agent_date']),
                                );

                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$agent_data['agent_date']) == 1) && ((int)date('m',$agent_data['agent_date']) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$agent_data['agent_date'])+1;
                                }
                                
                                $owe->updateOwe($owe_data,array('agent'=>$id_agent));

                                 if(date("w", $agent_data['agent_date'])>=4){
                                    $ngay_du = strtotime('next Monday',$agent_data['agent_date']);
                                }
                                else{
                                    $ngay_du = strtotime('last Saturday',$agent_data['agent_date']);
                                }
                                
                                $payable_data = array(
                                    'vendor' => $agent_data['staff'],
                                    'money' => $agent_data['total_cost'],
                                    'payable_date' => $agent_data['agent_date'],
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $ngay_du,
                                    'week' => (int)date('W',$ngay_du),
                                    'year' => (int)date('Y',$ngay_du),
                                    'code' => $agent_data['code'],
                                    'source' => 4,
                                    'comment' => $agent_data['name'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 2,
                                );

                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$ngay_du) == 1) && ((int)date('m',$ngay_du) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$ngay_du)+1;
                                }

                                $payable->updateCosts($payable_data,array('agent'=>$id_agent));

                                $ngay = strtotime('15-'.date('m-Y',strtotime('+1 month',$agent_data['agent_date'])));
                                $receivable_data = array(
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['total_offer'],
                                    'receivable_date' => $agent_data['agent_date'],
                                    'expect_date' => $ngay,
                                    'week' => (int)date('W',$ngay),
                                    'year' => (int)date('Y',$ngay),
                                    'code' => $agent_data['code'],
                                    'source' => 4,
                                    'comment' => $agent_data['name'],
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

                                $receivable->updateCosts($receivable_data,array('agent'=>$id_agent));

                                $obtain_data = array(
                                    'obtain_date' => $agent_data['agent_date'],
                                    'customer' => $agent_data['customer'],
                                    'money' => $agent_data['total_offer'],
                                    'week' => (int)date('W',$agent_data['agent_date']),
                                    'year' => (int)date('Y',$agent_data['agent_date']),
                                );

                                if($obtain_data['week'] == 53){
                                    $obtain_data['week'] = 1;
                                    $obtain_data['year'] = $obtain_data['year']+1;
                                }
                                if (((int)date('W',$agent_data['agent_date']) == 1) && ((int)date('m',$agent_data['agent_date']) == 12) ) {
                                    $obtain_data['year'] = (int)date('Y',$agent_data['agent_date'])+1;
                                }

                                $obtain->updateObtain($obtain_data,array('agent'=>$id_agent));

                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('agent');
        }
        $this->view->show('agent/import');

    }
    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>