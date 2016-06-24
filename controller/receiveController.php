<?php
Class receiveController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'receive_date';
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

        

        $receive_model = $this->model->get('receiveModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        

        $query = 'SELECT receivable.sale_report, receivable.agent, receivable.agent_manifest, receivable.trading, receivable.invoice, receive_id, receive_date, receive.money, receive.source, receive.receivable, receive.receive_comment, receivable_id, receivable.code, receivable.comment, receivable.create_user, receivable.staff, receivable.customer, receivable.vendor, bank_id, bank_name FROM receive, receivable, bank WHERE bank.bank_id = receive.source AND receivable.receivable_id=receive.receivable';

        if (isset($id) && $id > 0) {
            $query.= ' AND receivable = '.$id;
        }
        else{
            $query.= ' AND receive_date >= '.strtotime($batdau).' AND receive_date <= '.strtotime($ketthuc);
        }

        $tongsodong = count($receive_model->queryCosts($query));
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
                OR receive.money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") )';
            
                $query .= ' AND '.$search.' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }
        else{
            $query .= ' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }


        
        
        $this->view->data['receives'] = $receive_model->queryCosts($query);
        $this->view->data['lastID'] = isset($receive_model->getLastCosts()->receive_id)?$receive_model->getLastCosts()->receive_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('receive/index');
    }

    
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $receive = $this->model->get('receiveModel');
            $receivable = $this->model->get('receivableModel');
            $assets_model = $this->model->get('assetsModel');

            $data = array(
                        'receive_date' => strtotime(trim($_POST['receive_date'])),
                        'source' => trim($_POST['source']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'week' => (int)date('W', strtotime(trim($_POST['receive_date']))),
                        'year' => (int)date('Y', strtotime(trim($_POST['receive_date']))),
                        'receive_comment' => trim($_POST['receive_comment']),
                        );
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', $data['receive_date']) == 1) && ((int)date('m', $data['receive_date']) == 12) ) {
                $data['year'] = (int)date('Y', $data['receive_date'])+1;
            }

            if ($_POST['yes'] != "") {
                
                    $receive_data = $receive->getCosts($_POST['yes']);
                    $receivable_data = $receivable->getCosts($receive_data->receivable);

                    $data_asset = array(
                                    'assets_date' => $data['receive_date'],
                                    'total' => $data['money'],
                                    'bank' => $data['source'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                    $assets_model->updateAssets($data_asset,array('receivable'=>$receive_data->receivable, 'total'=>$receive_data->money));

                    if($receivable_data->staff > 0){

                        $staff_debt_model = $this->model->get('staffdebtModel');
                        $data_staff_debt = array(
                                    'source' => $data['source'],
                                    'money' => 0 - ($data['money']),
                                    'staff_debt_date' => $data['receive_date'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                       

                        $staff_debt_model->updateCost($data_staff_debt,array('staff'=>$receivable_data->staff,'staff_debt_date'=>$receive_data->receive_date,'money'=>(0-$receive_data->money)));
                    }

                    if($receivable_data->customer > 0){

                        $obtain_model = $this->model->get('obtainModel');
                        $data_obtain = array(
                                    'money' => 0 - ($data['money']),
                                    'obtain_date' => $data['receive_date'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                        

                        $obtain_model->updateObtain($data_obtain,array('customer'=>$receivable_data->customer,'obtain_date'=>$receive_data->receive_date,'money'=>(0-$receive_data->money)));
                    }

                    if($receivable_data->vendor > 0){

                        $owe_model = $this->model->get('oweModel');
                        $data_owe = array(
                                    'money' => $data['money'],
                                    'owe_date' => $data['receive_date'],
                                    'week' => $data['week'],
                                    'year' => $data['year'],
                                );
                        

                        $owe_model->updateOwe($data_owe,array('vendor'=>$receivable_data->vendor,'owe_date'=>$receive_data->receive_date,'money'=>$receive_data->money));
                    }

                    $data_receivable = array(
                                'pay_money' => ($receivable_data->pay_money - $receive_data->money) + $data['money'],
                            );
                    

                    $receivable->updateCosts($data_receivable,array('receivable_id'=>$receive_data->receivable));


                    $receive->updateCosts($data,array('receive_id'=>$_POST['yes']));

                    
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|receive|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $receive->createCosts($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$receive->getLastCosts()->receive_id."|receive|".implode("-",$data)."\n"."\r\n";
                        
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
            $receive = $this->model->get('receiveModel');
            $assets_model = $this->model->get('assetsModel');
            $receivable_model = $this->model->get('receivableModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $obtain_model = $this->model->get('obtainModel');
            $owe_model = $this->model->get('oweModel');

            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $re = $receive->getCosts($data);
                        $rece = $receivable_model->getCosts($re->receivable);
                       
                       $assets_model->queryAssets('DELETE FROM assets WHERE assets_date='.$re->receive_date.' AND total='.$re->money.' AND receivable = '.$re->receivable);
                       
                       if($rece->staff > 0){

                            $staff_debt_model->queryCost('DELETE FROM staff_debt WHERE staff_debt_date='.$re->receive_date.' AND money='.(0-$re->money).' AND staff = '.$rece->staff);
                        }

                        if($rece->customer > 0){

                            $obtain_model->queryObtain('DELETE FROM obtain WHERE obtain_date='.$re->receive_date.' AND money='.(0-$re->money).' AND customer = '.$rece->customer);
                        }
                        if($rece->vendor > 0){

                            $owe_model->queryOwe('DELETE FROM owe WHERE owe_date='.$re->receive_date.' AND money='.($re->money).' AND vendor = '.$rece->vendor);
                        }
                       
                       $data_receivable = array(
                                'pay_money' => ($rece->pay_money - $re->money),
                            );
                        $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$re->receivable));

                        $receive->deleteCosts($data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|receive|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $re = $receive->getCosts($_POST['data']);
                        
                        $rece = $receivable_model->getCosts($re->receivable);
                       
                       $assets_model->queryAssets('DELETE FROM assets WHERE assets_date='.$re->receive_date.' AND total='.$re->money.' AND receivable = '.$re->receivable);
                       
                       if($rece->staff > 0){

                            $staff_debt_model->queryCost('DELETE FROM staff_debt WHERE staff_debt_date='.$re->receive_date.' AND money='.(0-$re->money).' AND staff = '.$rece->staff);
                        }

                        if($rece->customer > 0){

                            $obtain_model->queryObtain('DELETE FROM obtain WHERE obtain_date='.$re->receive_date.' AND money='.(0-$re->money).' AND customer = '.$rece->customer);
                        }
                        if($rece->vendor > 0){

                            $owe_model->queryOwe('DELETE FROM owe WHERE owe_date='.$re->receive_date.' AND money='.($re->money).' AND vendor = '.$rece->vendor);
                        }
                       
                       $data_receivable = array(
                                'pay_money' => ($rece->pay_money - $re->money),
                            );
                        $receivable_model->updateCosts($data_receivable,array('receivable_id'=>$re->receivable));

                        $receive->deleteCosts($_POST['data']);
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|receive|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

   

}
?>