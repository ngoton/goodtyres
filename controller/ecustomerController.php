<?php
Class ecustomerController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin khách hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'e_customer_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        

        $customer_model = $this->model->get('ecustomerModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($customer_model->getAllCustomer());
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
            $search = '( e_customer_co LIKE "%'.$keyword.'%" 
                    OR e_customer_phone LIKE "%'.$keyword.'%" 
                    OR e_customer_contact LIKE "%'.$keyword.'%" 
                )';
            $data['where'] = $search;
        }
        
        
        
        $this->view->data['customers'] = $customer_model->getAllCustomer($data);

        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->e_customer_id)?$customer_model->getLastCustomer()->e_customer_id:0;
        
        $this->view->show('ecustomer/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (!isset($_SESSION['role_logined']) && $_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $customer = $this->model->get('ecustomerModel');
            $data = array(
                        
                        'e_customer_co' => trim($_POST['e_customer_co']),
                        'e_customer_contact' => trim($_POST['e_customer_contact']),
                        'e_customer_phone' => trim($_POST['e_customer_phone']),
                        'e_customer_email' => trim($_POST['e_customer_email']),
                        'e_customer_address' => trim($_POST['e_customer_address']),
                        'customer_status' => trim($_POST['customer_status']),
                        );
            if ($_POST['check'] == "true") {
                //$data['customer_update_user'] = $_SESSION['userid_logined'];
                //$data['customer_update_time'] = time();
                //var_dump($data);
                
                if ($customer->getAllCustomerByWhere($_POST['yes'].' AND e_customer_co = '.trim($_POST['e_customer_co']))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    
                        $customer->updateCustomer($data,array('e_customer_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|e_customer|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                
            }
            else{
                //$data['customer_create_user'] = $_SESSION['userid_logined'];
                //$data['customer_create_time'] = date('m/Y');
                //$data['customer'] = $_POST['customer'];
                //var_dump($data);
                if ($customer->getCustomerByWhere(array('e_customer_co'=>trim($_POST['e_customer_co'])))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    $customer->createCustomer($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$customer->getLastCustomer()->e_customer_id."|e_customer|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (!isset($_SESSION['role_logined']) && $_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = $this->model->get('ecustomerModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $customer->deleteCustomer($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|e_customer|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                   
                        $customer->deleteCustomer($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|customer|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function getCustomer($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>