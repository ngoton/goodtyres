<?php
Class shipchargeController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý phí tàu';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipcharge_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        
        $join = array('table'=>'port','where'=>'shipcharge.port = port.port_id');
        $shipcharge_model = $this->model->get('shipchargeModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($shipcharge_model->getAllShipcharge(null,$join));
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
            $search = '( port_name LIKE "%'.$keyword.'%" )';
            $data['where'] = $search;
        }
        
        $port = $this->model->get('portModel');
        $this->view->data['port'] = $port->getAllPort();
        
        
        $this->view->data['shipcharges'] = $shipcharge_model->getAllShipcharge($data,$join);

        $this->view->data['lastID'] = isset($shipcharge_model->getLastShipcharge()->shipcharge_id)?$shipcharge_model->getLastShipcharge()->shipcharge_id:0;
        
        $this->view->show('shipcharge/index');
    }

    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $shipcharge = $this->model->get('shipchargeModel');
            $data = array(
                        
                        'port' => trim($_POST['port']),
                        'shipcharge_20_feet' => trim(str_replace(',','',$_POST['shipcharge_20_feet'])),
                        'shipcharge_40_feet' => trim(str_replace(',','',$_POST['shipcharge_40_feet'])),
                        );
            if ($_POST['check'] != "") {
                //$data['shipcharge_update_user'] = $_SESSION['userid_logined'];
                //$data['shipcharge_update_time'] = time();
                //var_dump($data);
                $shipcharge->updateShipcharge($data,array('shipcharge_id' => $_POST['yes']));
                echo "Cập nhật thành công";
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|shipcharge|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                //$data['shipcharge_create_user'] = $_SESSION['userid_logined'];
                //$data['staff'] = $_POST['staff'];
                //var_dump($data);
                if ($shipcharge->getShipchargeByWhere(array('port'=>$_POST['port']))) {
                    echo "Nội dung này đã tồn tại";
                    return false;
                }
                else{
                    $shipcharge->createShipcharge($data);
                    echo "Thêm thành công";
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$shipcharge->getLastShipcharge()->shipcharge_id."|shipcharge|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $shipcharge = $this->model->get('shipchargeModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $shipcharge->deleteShipcharge($data);
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|shipcharge|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|shipcharge|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                return $shipcharge->deleteShipcharge($_POST['data']);
            }
            
        }
    }

    public function view() {
        
        $this->view->show('shipcharge/view');
    }

}
?>