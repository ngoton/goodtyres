<?php
Class payController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã chi';

        $id = $this->registry->router->param_id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $id = 0;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pay_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = date('d-m-Y', strtotime("last Sunday"));
            $ketthuc = date('d-m-Y');
        }
//var_dump(strtotime('28-09-2014'));
        

        $nam = date('Y');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $secs = $this->model->get('secsModel');
        $sec = $secs->getAllCosts();
        $sec_data = array();
        foreach ($sec as $s) {
            $sec_data[$s->secs_id]['name'] = $s->secs_name;
        }
        $this->view->data['secs'] = $sec_data;

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

        

        $pay_model = $this->model->get('payModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        

        $query = 'SELECT pay.sec, payable.sale_report, payable.agent, payable.agent_manifest, payable.trading, payable.invoice, pay_id, pay.pay_date, pay.money, pay.source, pay.payable, pay.pay_comment, payable_id, payable.code, payable.comment, payable.create_user, payable.customer, payable.vendor, bank_id, bank_name FROM pay, payable, bank WHERE bank.bank_id = pay.source AND payable.payable_id=pay.payable';
        if (isset($id) && $id > 0) {
            $query.= ' AND pay.payable = '.$id;
        }
        else{
            $query.= ' AND pay.pay_date >= '.strtotime($batdau).' AND pay.pay_date <= '.strtotime($ketthuc);
        }

        $tongsodong = count($pay_model->queryCosts($query));
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

        

        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR pay.money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") )';
            
                $query .= ' AND '.$search.' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }
        else{
            $query .= ' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }


        
        
        $this->view->data['pays'] = $pay_model->queryCosts($query);
        $this->view->data['lastID'] = isset($pay_model->getLastCosts()->pay_id)?$pay_model->getLastCosts()->pay_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('pay/index');
    }

    
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $pay = $this->model->get('payModel');
            $payable = $this->model->get('payableModel');
            $assets_model = $this->model->get('assetsModel');

            $data = array(
                        'pay_date' => strtotime(trim($_POST['pay_date'])),
                        'source' => trim($_POST['source']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'week' => (int)date('W', strtotime(trim($_POST['pay_date']))),
                        'year' => (int)date('Y', strtotime(trim($_POST['pay_date']))),
                        'pay_comment' => trim($_POST['pay_comment']),
                        'sec' => trim($_POST['sec']),
                        );
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', $data['pay_date']) == 1) && ((int)date('m', $data['pay_date']) == 12) ) {
                $data['year'] = (int)date('Y', $data['pay_date'])+1;
            }

            if ($_POST['yes'] != "") {
                
                    $pay_data = $pay->getCosts($_POST['yes']);
                    $payable_data = $payable->getCosts($pay_data->payable);

                    $data_asset = array(
                                    'assets_date' => $data['pay_date'],
                                    'total' => (0-$data['money']),
                                    'bank' => $data['source'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                    $assets_model->updateAssets($data_asset,array('payable'=>$pay_data->payable, 'total'=>(0-$pay_data->money)));


                    if($payable_data->customer > 0){

                        $obtain_model = $this->model->get('obtainModel');
                        $data_obtain = array(
                                    'money' => $data['money'],
                                    'obtain_date' => $data['pay_date'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                        

                        $obtain_model->updateObtain($data_obtain,array('customer'=>$payable_data->customer,'obtain_date'=>$pay_data->pay_date,'money'=>$pay_data->money));
                    }

                    if($payable_data->vendor > 0){

                        $costs = $this->model->get('costsModel');

                        $owe_model = $this->model->get('oweModel');
                        $data_owe = array(
                                    'money' => (0-$data['money']),
                                    'owe_date' => $data['pay_date'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                        

                        $owe_model->updateOwe($data_owe,array('vendor'=>$payable_data->vendor,'owe_date'=>$pay_data->pay_date,'money'=>(0-$pay_data->money)));
                    }

                    $data_payable = array(
                                'pay_money' => ($payable_data->pay_money - $pay_data->money) + $data['money'],
                            );
                    

                    $payable->updateCosts($data_payable,array('payable_id'=>$pay_data->payable));

                    if ($payable_data->lender > 0) {
                        $owe = $this->model->get('oweModel');

                            $data_asset = array(
                                    'bank' => $data['source'],
                                    'total' => $data['money'],
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

                            $assets_model->updateAssets2($data_asset,array('payable'=>$pay_data->payable,'total'=>$pay_data->money));

                            $id_temp = $costs->getCostsByWhere3(array('staff'=>29,'costs_date'=>$pay_data->pay_date,'money'=>$pay_data->money))->costs_id;

                            $data_asset = array(
                                'bank' => $data['source'],
                                'total' => (0-$data['money']),
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

                            $assets_model->updateAssets3($data_asset,array('costs'=>$id_temp,'total'=>(0-$pay_data->money)));

                            $data_costs = array(
                                'costs_create_date' => $data['pay_date'],
                                'costs_date' => $data['pay_date'],
                                'comment' => 'CMG tạm ứng '.$data['pay_comment'],
                                'money' => $data['money'],
                                'expect_date' => $data['pay_date'],
                                'week' => (int)date('W', $data['pay_date']),
                                'create_user' => $_SESSION['userid_logined'],
                                'source' => $data['source'],
                                'year' => (int)date('Y', $data['pay_date']),
                                'staff' => 29,
                                'staff_cost' => $data['money'],
                                'pay_money' => $data['money'],
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

                            $costs->updateCosts3($data_costs,array('staff'=>29,'costs_date'=>$pay_data->pay_date,'money'=>$pay_data->money));


                            $owe_data = array(
                                'owe_date' => $data['pay_date'],
                                'vendor' => 148,
                                'money' => $data['money'],
                                'week' => (int)date('W',$data['pay_date']),
                                'year' => (int)date('Y',$data['pay_date']),
                            );

                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$data['pay_date'])+1;
                            }

                            $owe->updateOwe2($owe_data,array('payable'=>$pay_data->payable,'money'=>$pay_data->money));

                            $payable_data = array(
                                'vendor' => 148,
                                'money' => $data['money'],
                                'payable_date' => $data['pay_date'],
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $data['pay_date'],
                                'week' => (int)date('W',$data['pay_date']),
                                'year' => (int)date('Y',$data['pay_date']),
                                'code' => $payable_data->code,
                                'source' => $data['source'],
                                'comment' => 'Mượn CMG TT '.$payable_data->comment,
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 5,
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

                            $payable->updateCosts2($payable_data,array('payable'=>$pay_data->payable,'money'=>$pay_data->money));

                            $staff_debt_model = $this->model->get('staffdebtModel');
                            $data_staff_debt = array(
                                    'staff' => 29,
                                    'source' => $data['source'],
                                    'money' => $data['money'],
                                    'staff_debt_date' => $data['pay_date'],
                                    'comment' => $payable_data->comment,
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

                            $staff_debt_model->updateCost3($data_staff_debt,array('staff'=>29,'money'=>$pay_data->money,'staff_debt_date'=>$pay_data->pay_date));

                            $receivable = $this->model->get('receivableModel');
                            $data_receivable = array(
                                'staff' => 29,
                                'money' => $data['pay_money'],
                                'receivable_date' => $data['pay_date'],
                                'expect_date' => $data['pay_date'],
                                'week' => (int)date('W',$data['pay_date']),
                                'comment' => $payable_data->comment,
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

                            $receivable->updateCosts3($data_receivable,array('staff'=>29,'money'=>$pay_data->money,'receivable_date'=>$pay_data->pay_date));
                    }


                    $pay->updateCosts($data,array('pay_id'=>$_POST['yes']));

                    
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|pay|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $pay->createCosts($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$pay->getLastCosts()->pay_id."|pay|".implode("-",$data)."\n"."\r\n";
                        
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
            $pay = $this->model->get('payModel');
            $assets_model = $this->model->get('assetsModel');
            $payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $owe_model = $this->model->get('oweModel');
            $receivable = $this->model->get('receivableModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $costs = $this->model->get('costsModel');

            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $re = $pay->getCosts($data);
                        $rece = $payable_model->getCosts($re->payable);
                       
                       $assets_model->queryAssets('DELETE FROM assets WHERE assets_date='.$re->pay_date.' AND total='.(0-$re->money).' AND payable = '.$re->payable);
                       

                        if($rece->customer > 0){

                            $obtain_model->queryObtain('DELETE FROM obtain WHERE obtain_date='.$re->pay_date.' AND money='.$re->money.' AND customer = '.$rece->customer);
                        }
                        if($rece->vendor > 0){

                            $owe_model->queryOwe('DELETE FROM owe WHERE owe_date='.$re->pay_date.' AND money='.(0-$re->money).' AND vendor = '.$rece->vendor);
                        }
                       
                       $data_payable = array(
                                'pay_money' => ($rece->pay_money - $re->money),
                            );
                        $payable_model->updateCosts($data_payable,array('payable_id'=>$re->payable));

                        if ($rece->lender>0) {
                            $id_temp = $costs->getCostsByWhere3(array('staff'=>29,'costs_date'=>$re->pay_date,'money'=>$re->money))->costs_id;

                            $assets_model->queryAssets2('DELETE FROM assets WHERE total='.$re->money.' AND payable='.$re->payable);
                            $owe_model->queryOwe2('DELETE FROM owe WHERE money='.$re->money.' AND payable='.$re->payable);
                            $payable_model->queryCosts2('DELETE FROM payable WHERE money='.$re->money.' AND payable='.$re->payable);
                            $costs->queryCosts3('DELETE FROM costs WHERE staff=29 AND costs_date='.$re->pay_date.' AND money='.$re->money);
                            $assets_model->queryAssets3('DELETE FROM assets WHERE total='.(0-$re->money).' AND costs='.$id_temp);
                            $receivable->queryCosts3('DELETE FROM receivable WHERE staff=29 AND receivable_date='.$re->pay_date.'  AND money='.$re->money);
                            $staff_debt_model->queryCost3('DELETE FROM staff_debt WHERE staff=29 AND staff_debt_date='.$re->pay_date.' AND money='.$re->money);

                        }
                        
                        $pay->deleteCosts($data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|pay|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $re = $pay->getCosts($_POST['data']);
                        
                        $rece = $payable_model->getCosts($re->payable);
                       
                       $assets_model->queryAssets('DELETE FROM assets WHERE assets_date='.$re->pay_date.' AND total='.(0-$re->money).' AND payable = '.$re->payable);
                       

                        if($rece->customer > 0){

                            $obtain_model->queryObtain('DELETE FROM obtain WHERE obtain_date='.$re->pay_date.' AND money='.$re->money.' AND customer = '.$rece->customer);
                        }
                        if($rece->vendor > 0){

                            $owe_model->queryOwe('DELETE FROM owe WHERE owe_date='.$re->pay_date.' AND money='.(0-$re->money).' AND vendor = '.$rece->vendor);
                        }
                       
                       $data_payable = array(
                                'pay_money' => ($rece->pay_money - $re->money),
                            );
                        $payable_model->updateCosts($data_payable,array('payable_id'=>$re->payable));

                        if ($rece->lender>0) {
                            $id_temp = $costs->getCostsByWhere3(array('staff'=>29,'costs_date'=>$re->pay_date,'money'=>$re->money))->costs_id;
                            $assets_model->queryAssets2('DELETE FROM assets WHERE total='.$re->money.' AND payable='.$re->payable);
                            $owe_model->queryOwe2('DELETE FROM owe WHERE money='.$re->money.' AND payable='.$re->payable);
                            $payable_model->queryCosts2('DELETE FROM payable WHERE money='.$re->money.' AND payable='.$re->payable);
                            $costs->queryCosts3('DELETE FROM costs WHERE staff=29 AND costs_date='.$re->pay_date.' AND money='.$re->money);
                            $assets_model->queryAssets3('DELETE FROM assets WHERE total='.(0-$re->money).' AND costs='.$id_temp);
                            $receivable->queryCosts3('DELETE FROM receivable WHERE staff=29 AND receivable_date='.$re->pay_date.'  AND money='.$re->money);
                            $staff_debt_model->queryCost3('DELETE FROM staff_debt WHERE staff=29 AND staff_debt_date='.$re->pay_date.' AND money='.$re->money);

                        }
                        
                        $pay->deleteCosts($_POST['data']);
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|pay|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

   

}
?>