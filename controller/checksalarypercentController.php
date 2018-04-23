<?php
Class checksalarypercentController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Phần trăm tính lương';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'check_salary_percent_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngaytao = date('m-Y',strtotime('-1 month'));
        }

        
        $data = array(
            'where' => 'create_time <= '.strtotime('28-'.$ngaytao),
        );


        $checksalarypercent_model = $this->model->get('checksalarypercentModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($checksalarypercent_model->getAllSalary($data));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'create_time <= '.strtotime('28-'.$ngaytao),
            );

        
        
        $this->view->data['checksalarypercents'] = $checksalarypercent_model->getAllSalary($data);
        $this->view->data['lastID'] = isset($checksalarypercent_model->getLastSalary()->check_salary_percent_id)?$checksalarypercent_model->getLastSalary()->check_salary_percent_id:0;

        $this->view->show('checksalarypercent/index');
    }

    
    public function add(){
        
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }

        if (isset($_POST['yes'])) {
            $checksalarypercent = $this->model->get('checksalarypercentModel');
            $data = array(
                        'order_percent' => trim(str_replace(',', '', $_POST['order_percent'])),
                        'order_number' => trim(str_replace(',', '', $_POST['order_number'])),
                        'order_new' => trim(str_replace(',', '', $_POST['order_new'])),
                        'order_old' => trim(str_replace(',', '', $_POST['order_old'])),
                        'create_time' => strtotime('01-'.$_POST['create_time']),
                        
                        );

            
            if ($_POST['yes'] != "") {
                //var_dump($data);
                $checksalarypercent_data = $checksalarypercent->getSalary($_POST['yes']);

                $checksalarypercent->updateSalary($data,array('check_salary_percent_id' => $_POST['yes']));
                echo "Cập nhật thành công";


                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|check_salary_percent|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                
                //var_dump($data);
                if ($checksalarypercent->getSalaryByWhere(array('create_time' => $data['create_time']))) {
                    echo "Bảng lương này đã tồn tại";
                    return false;
                }
                else{
                    $checksalarypercent->createSalary($data);
                    echo "Thêm thành công";



                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$checksalarypercent->getLastSalary()->check_salary_percent_id."|check_salary_percent|".implode("-",$data)."\n"."\r\n";
                        
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
            $checksalarypercent = $this->model->get('checksalarypercentModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $checksalarypercent->deleteSalary($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|check_salary_percent|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|check_salary_percent|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $checksalarypercent->deleteSalary($_POST['data']);
            }
            
        }
    }

}
?>