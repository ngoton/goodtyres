<?php
Class shipmentController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý đơn hàng';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipment_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = date('d-m-Y', strtotime("last monday"));
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        

        $sale_model = $this->model->get('shipmentModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date <= '.$ketthuc,
        );

        
        $join = array('table'=>'customer, vendor','where'=>'customer.customer_id = shipment.customer AND shipment.vendor = vendor.vendor_id');
        
        $tongsodong = count($sale_model->getAllSale($data,$join));
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
            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date <= '.strtotime($ketthuc),
            );

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( sale in (SELECT user_id FROM user WHERE username LIKE "%'.$keyword.'%") 
                OR procument in (SELECT user_id FROM user WHERE username LIKE "%'.$keyword.'%") 
                OR customer_name LIKE "%'.$keyword.'%" 
                OR vendor_name LIKE "%'.$keyword.'%" 
                OR loc_from in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) 
                OR loc_to in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) 
                OR loc_to2 in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser(array('where'=>'role = 5'));
        
        $this->view->data['users'] = $users;

        $users = $user_model->getAllUser();

        $user_data = array();
        foreach ($users as $user) {
            $user_data['user_id'][$user->user_id] = $user->user_id;
            $user_data['username'][$user->user_id] = $user->username;
        }
        
        $this->view->data['user'] = $user_data;

        $location_model = $this->model->get('locationModel');
        $location = $location_model->getAllLocation(null,array('table'=>'district','where'=>'district.district_id = location.district'));
        

        $location_data = array();
        foreach ($location as $location) {
            $location_data['location_id'][$location->location_id] = $location->location_id;
            $location_data['location_name'][$location->location_id] = $location->location_name;
            $location_data['district_name'][$location->location_id] = $location->district_name;
        }
        
        $this->view->data['location'] = $location_data;

        $district_model = $this->model->get('districtModel');
        $district = $district_model->getAllDistrict();
        $this->view->data['districts'] = $district;

        
        $this->view->data['sales'] = $sale_model->getAllSale($data,$join);
        $this->view->data['lastID'] = isset($sale_model->getLastSale()->shipment_id)?$sale_model->getLastSale()->shipment_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('shipment/index');
    }

    public function getlocationto(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $location_model->getAllLocation(array('where'=>'district = '.$_POST['district']));
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" AND district = '.$_POST['district'].')',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item_location_to2(\''.$rs->location_id.'\',\''.$rs->location_name.'\')">'.$location_name.'</li>';
            }
        }
    }
    public function getvendor(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('vendorModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( vendor_name LIKE "%'.$_POST['keyword'].'%")',
                );
                $list = $vendor_model->getAllvendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor_name = $rs->vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->vendor_id.'\',\''.$rs->vendor_name.'\',\''.$rs->vendor_phone.'\')">'.$vendor_name.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $sale = $this->model->get('shipmentModel');
            $data = array(
                        'shipment_date' => strtotime(trim($_POST['shipment_date'])),
                        'loc_from' => trim($_POST['loc_from']),
                        'loc_to' => trim($_POST['loc_to']),
                        'loc_to2' => trim($_POST['loc_to2']),
                        'price' => trim(str_replace(',','',$_POST['price'])),
                        'price_customer' => trim(str_replace(',','',$_POST['price_customer'])),
                        'price_offer' => trim(str_replace(',','',$_POST['price_offer'])),
                        'sale' => $_SESSION['userid_logined'],
                        'procument' => trim($_POST['procument']),
                        'start_date' => strtotime(trim($_POST['start_date'])),
                        'status' => trim($_POST['status']),
                        'item' => trim($_POST['item']),
                        'number' => trim($_POST['number']),
                        'unit' => trim($_POST['unit']),
                        'type' => trim($_POST['type']),
                        'transport' => trim($_POST['transport']),
                        'cost_add' => trim(str_replace(',','',$_POST['cost_add'])),
                        'pay_type' => trim($_POST['pay_type']),
                        'price_vendor' => trim(str_replace(',','',$_POST['price_vendor'])),
                        );

    
            if ($_POST['customer'] == null) {
                 $customer = $this->model->get('customerModel');
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
            if ($_POST['vendor'] == null) {
                 $vendor = $this->model->get('vendorModel');
                 $vendor_data = array(
                    'vendor_name'=> trim($_POST['vendor_name']),
                    'vendor_phone' => trim($_POST['vendor_phone']),
                );
                 $vendor->createVendor($vendor_data);

                 $data['vendor'] = $vendor->getLastVendor()->vendor_id;
            }
            else{
                $data['vendor'] = trim($_POST['vendor']);
            }


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                
                    $sale->updateSale($data,array('shipment_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|shipment|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $sale->createSale($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sale->getLastSale()->shipment_id."|shipment|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale = $this->model->get('shipmentModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $sale->deleteSale($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|shipment|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    
                        $sale->deleteSale($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|shipment|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>