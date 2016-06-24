<?php
Class contsaleController Extends baseController {
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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'cont_sale_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        

        $sale_model = $this->model->get('contsaleModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array();

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] = 'sale = '.$$_SESSION['userid_logined'];
        }
        
        $join = array('table'=>'user, customer','where'=>'user.user_id = cont_sale.sale AND customer.customer_id = cont_sale.customer');
        
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND sale = '.$$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( username LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" 
                OR loc_from in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) 
                OR loc_to in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

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
        $this->view->data['lastID'] = isset($sale_model->getLastSale()->cont_sale_id)?$sale_model->getLastSale()->cont_sale_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('contsale/index');
    }

    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $sale = $this->model->get('contsaleModel');
            $data = array(
                        
                        'loc_from' => trim($_POST['loc_from']),
                        'loc_to' => trim($_POST['loc_to']),
                        'price' => trim(str_replace(',','',$_POST['price'])),
                        'price_change' => trim(str_replace(',','',$_POST['price_change'])),
                        'size' => trim($_POST['size']),
                        'type' => trim($_POST['type']),
                        'number' => trim($_POST['number']),
                        'ton' => trim($_POST['ton']),
                        'sale' => $_SESSION['userid_logined'],
                        'start_date' => trim($_POST['start_date']),
                        'end_date' => trim($_POST['end_date']),
                        'status' => trim($_POST['status']),
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


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                
                    $sale->updateSale($data,array('cont_sale_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|cont_sale|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $sale->createSale($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sale->getLastSale()->cont_sale_id."|cont_sale|".implode("-",$data)."\n"."\r\n";
                        
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
            $sale = $this->model->get('contsaleModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $sale->deleteSale($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|cont_sale|"."\n"."\r\n";
                        
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
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|cont_sale|"."\n"."\r\n";
                        
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