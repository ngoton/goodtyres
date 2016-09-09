<?php
Class ecartController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'e_cart_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'ASC';
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

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        $customer_model = $this->model->get('ecustomerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'e_customer_contact',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $e_cart_model = $this->model->get('ecartModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'e_cart_date >= '.strtotime($batdau).' AND e_cart_date <= '.strtotime($ketthuc),
        );

        if ($trangthai > 0) {
            $data['where'] .= ' AND e_customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND e_status = '.$nv;
        }

        
        $join = array('table'=>'e_customer','where'=>'e_customer.e_customer_id = e_cart.e_customer');
        
        $tongsodong = count($e_cart_model->getAllCart($data,$join));
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
            'where' => 'e_cart_date >= '.strtotime($batdau).' AND e_cart_date <= '.strtotime($ketthuc),
            );

        if ($trangthai > 0) {
            $data['where'] .= ' AND e_customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND e_status = '.$nv;
        }

        if ($keyword != '') {
            $search = '( e_customer_co LIKE "%'.$keyword.'%" 
                OR e_customer_contact LIKE "%'.$keyword.'%"  
                OR e_customer_email LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $e_carts = $e_cart_model->getAllCart($data,$join);

        $this->view->data['e_carts'] = $e_carts;

        $this->view->data['lastID'] = isset($e_cart_model->getLastCart()->e_cart_id)?$e_cart_model->getLastCart()->e_cart_id:0;

        $this->view->show('ecart/index');
    }

    public function approve(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $e_cart_model = $this->model->get('ecartModel');

            $id = trim($_POST['data']);

            $data = array(
                'e_staff'=>$_SESSION['userid_logined'],
            );

            $e_cart_model->updateCart($data,array('e_cart_id'=>$id));

            echo "Cập nhật thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['data']."|e_cart|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $e_cart_model = $this->model->get('ecartModel');
            
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $e_cart_model->deleteCart($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|e_cart|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $e_cart_model->deleteCart($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|e_cart|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }


    public function listtire($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $e_cart_list_model = $this->model->get('ecartlistModel');
        $join = array('table'=>'tire_product,tire_producer,tire_product_size,tire_product_pattern','where'=>'tire_product=tire_product_id AND tire_producer=tire_producer_id AND tire_size=tire_product_size_id AND tire_pattern=tire_product_pattern_id');

        $data = array(
            'where' => 'e_cart='.$id,
        );

        $e_cart_lists = $e_cart_list_model->getAllCart($data,$join);
        $this->view->data['e_cart_lists'] = $e_cart_lists;

        $this->view->show('ecart/listtire');
    }
    

}
?>