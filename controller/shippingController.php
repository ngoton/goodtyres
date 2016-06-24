<?php
Class shippingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý giá cước đường thủy';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipping_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        

        $shipping_model = $this->model->get('shippingModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($shipping_model->getAllShipping());
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( loc_from in (SELECT district_id FROM district WHERE district_name LIKE "%'.$keyword.'%" ) 
                        OR loc_to in (SELECT district_id FROM district WHERE district_name LIKE "%'.$keyword.'%" ))';
            $data['where'] = $search;
        }
        
        
        $location_model = $this->model->get('districtModel');
        $location = $location_model->getAllDistrict();
        
        $this->view->data['locations'] = $location;

        $location_data = array();
        foreach ($location as $location) {
            $location_data['district_id'][$location->district_id] = $location->district_id;
            $location_data['district_name'][$location->district_id] = $location->district_name;
        }
        
        $this->view->data['location'] = $location_data;
        
        $this->view->data['shippings'] = $shipping_model->getAllShipping($data);

        $this->view->data['lastID'] = isset($shipping_model->getLastShipping()->shipping_id)?$shipping_model->getLastShipping()->shipping_id:0;
        
        $this->view->show('shipping/index');
    }

    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $shipping = $this->model->get('shippingModel');
            $data = array(
                        
                        'c20_feet' => trim(str_replace(',','',$_POST['c20_feet'])),
                        'c40_feet' => trim(str_replace(',','',$_POST['c40_feet'])),
                        );
            if ($_POST['yes'] != "") {
                //$data['shipping_update_user'] = $_SESSION['userid_logined'];
                //$data['shipping_update_time'] = time();
                //var_dump($data);
                $shipping->updateShipping($data,array('shipping_id' => $_POST['yes']));
                echo "Cập nhật thành công";

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|shipping|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                //$data['shipping_create_user'] = $_SESSION['userid_logined'];
                //$data['staff'] = $_POST['staff'];
                //var_dump($data);
                if ($shipping->getShippingByWhere(array('loc_from'=>$_POST['loc_from'],'loc_to' => $_POST['loc_to']))) {
                    echo "Bảng giá này đã tồn tại";
                    return false;
                }
                else{
                    $data['loc_from'] = trim($_POST['loc_from']);
                    $data['loc_to'] = trim($_POST['loc_to']);
                    $shipping->createShipping($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$shipping->getLastShipping()->shipping_id."|shipping|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }

    public function getlocationfrom(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $district_model = $this->model->get('districtModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $district_model->getAllDistrict();
            }
            else{
                $data = array(
                'where'=>'( district_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $district_model->getAllDistrict($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $district_name = $rs->district_name;
                if ($_POST['keyword'] != "*") {
                    $district_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->district_name);
                }
                
                // add new option
                echo '<li onclick="set_item_loc_from(\''.$rs->district_id.'\',\''.$rs->district_name.'\')">'.$district_name.'</li>';
            }
        }
    }
    public function getlocationto(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $district_model = $this->model->get('districtModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $district_model->getAllDistrict();
            }
            else{
                $data = array(
                'where'=>'( district_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $district_model->getAllDistrict($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $district_name = $rs->district_name;
                if ($_POST['keyword'] != "*") {
                    $district_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->district_name);
                }
                
                // add new option
                echo '<li onclick="set_item_loc_to(\''.$rs->district_id.'\',\''.$rs->district_name.'\')">'.$district_name.'</li>';
            }
        }
    }

    

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $shipping = $this->model->get('shippingModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $shipping->deleteShipping($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|shipping|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|shipping|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $shipping->deleteShipping($_POST['data']);
            }
            
        }
    }

    
    

    public function view() {
        
        $this->view->show('handling/view');
    }

}
?>