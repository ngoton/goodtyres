<?php
Class accountingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
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
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'accounting_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $ngaytao = date('m/Y');
        }

        

        $accounting_model = $this->model->get('accountingModel');
        $sonews = 1000;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        

        
        $tongsodong = count($accounting_model->getAllAccounting(array('where'=>'( accounting_create_time LIKE "%'.$ngaytao.'%" )')));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( accounting_code LIKE "%'.$keyword.'%" 
                OR accounting_payment_date LIKE "%'.$keyword.'%" 
                OR accounting_bank LIKE "%'.$keyword.'%" 
                OR accounting_amount LIKE "%'.$keyword.'%" 
                OR accounting_cost LIKE "%'.$keyword.'%" )';
            if ($ngaytao != '') {
                $create_time = 'AND ( accounting_create_time LIKE "%'.$ngaytao.'%" )';
                $data['where'] = $search.$create_time;
            }
            else
                $data['where'] = $search;
        }
        if ($ngaytao != '' && $keyword == '') {
            $create_time = '( accounting_create_time LIKE "%'.$ngaytao.'%" )';
            $data['where'] = $create_time;
        }
        
        $this->view->data['accounting'] = $accounting_model->getAllAccounting($data);
        $this->view->data['lastID'] = isset($accounting_model->getLastAccounting()->accounting_id)?$accounting_model->getLastAccounting()->accounting_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('accounting/index');
    }

    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sales_model = $this->model->get('salesModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $sales_model->getAllSales();
            }
            else{
                $data = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $sales_model->getAllSales($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->comment.'\')">'.$code.' - '.$rs->comment.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $accounting = $this->model->get('accountingModel');
            $data = array(
                        
                        'accounting_bank' => trim($_POST['accounting_bank']),
                        'accounting_payment_date' => trim($_POST['accounting_payment_date']),
                        'accounting_amount' => trim(str_replace(',','',$_POST['accounting_amount'])),
                        'accounting_cost' => trim(str_replace(',','',$_POST['accounting_cost'])),
                        'accounting_create_time' => trim($_POST['accounting_create_time']),
                        );
            if ($_POST['accounting_code'] == "") {
                
                $data['accounting_update_user'] = $_SESSION['userid_logined'];
                $data['accounting_update_time'] = time();
                //var_dump($data);
                if ($_SESSION['role_logined'] == 2) {
                    if ($accounting->getAccountingByWhere(array('accounting_id'=>$_POST['yes'],'accounting_create_user'=>$_SESSION['userid_logined']))) {
                        $accounting->updateAccounting($data,array('accounting_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|accounting|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    else{
                        echo "Bạn không có quyền thực hiện thao tác này";
                        return false;
                    }
                }
                else{
                    $accounting->updateAccounting($data,array('accounting_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|accounting|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
            else{
                $data['accounting_code'] = trim($_POST['accounting_code']);
                $data['accounting_comment'] = trim($_POST['accounting_comment']);
                $data['accounting_create_user'] = $_SESSION['userid_logined'];
                //$data['accounting_create_time'] = date('m/Y');
                if ($accounting->getAccountingByWhere(array('accounting_code'=>trim($_POST['accounting_code']),'accounting_comment'=>trim($_POST['accounting_comment']),'accounting_create_time' => trim($_POST['accounting_create_time'])))) {
                    echo "Bảng này đã tồn tại";
                    return false;
                }
                else{
                    $accounting->createAccounting($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$accounting->getLastAccounting()->accounting_id."|accounting|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $accounting = $this->model->get('accountingModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    if ($_SESSION['role_logined'] == 2) {
                        if ($accounting->getAccountingByWhere(array('accounting_id'=>$data,'accounting_create_user'=>$_SESSION['userid_logined']))) {
                            $accounting->deleteAccounting($data);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|accounting|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $accounting->deleteAccounting($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|accounting|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    
                }
                return true;
            }
            else{
                    if ($_SESSION['role_logined'] == 2) {
                        if ($accounting->getAccountingByWhere(array('accounting_id'=>$_POST['data'],'accounting_create_user'=>$_SESSION['userid_logined']))) {
                            $accounting->deleteAccounting($_POST['data']);
                            echo "Xóa thành công";
                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|accounting|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $accounting->deleteAccounting($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['data']."|accounting|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
            }
            
        }
    }

    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>