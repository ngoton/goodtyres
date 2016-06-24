<?php
Class staffController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin nhân viên';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'staff_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        $id = $this->registry->router->param_id;

        $staff_model = $this->model->get('staffModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND staff_id = '.$id;
        }
        
        $tongsodong = count($staff_model->getAllStaff($data));
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
            'where' => '1=1',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND staff_id = '.$id;
        }
        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" 
                        OR staff_code LIKE "%'.$keyword.'%"
                        OR staff_birth LIKE "%'.$keyword.'%"
                        OR staff_address LIKE "%'.$keyword.'%"
                        OR staff_phone LIKE "%'.$keyword.'%"
                        OR staff_email LIKE "%'.$keyword.'%"
                        OR cmnd LIKE "%'.$keyword.'%"
                        OR bank LIKE "%'.$keyword.'%"
                        OR account in ( SELECT user_id FROM user WHERE username LIKE "%'.$keyword.'%") )';
            $data['where'] .= $search;
        }
        
        $user_model = $this->model->get('userModel');
        $user = $user_model->getAllUser();
        
        $this->view->data['users'] = $user;

        $user_data = array();
        foreach ($user as $user) {
            $user_data['user_id'][$user->user_id] = $user->user_id;
            $user_data['username'][$user->user_id] = $user->username;
        }
        
        $this->view->data['user'] = $user_data;
        
        
        $this->view->data['staffs'] = $staff_model->getAllStaff($data);

        $this->view->data['lastID'] = isset($staff_model->getLastStaff()->staff_id)?$staff_model->getLastStaff()->staff_id:0;
        
        $this->view->show('staff/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $staff = $this->model->get('staffModel');
            $data = array(
                        
                        'staff_code' => trim($_POST['staff_code']),
                        'staff_name' => trim($_POST['staff_name']),
                        'staff_birth' => trim($_POST['staff_birth']),
                        'staff_gender' => trim($_POST['staff_gender']),
                        'staff_address' => trim($_POST['staff_address']),
                        'staff_phone' => trim($_POST['staff_phone']),
                        'staff_email' => trim($_POST['staff_email']),
                        'cmnd' => trim($_POST['cmnd']),
                        'bank' => trim($_POST['bank']),
                        'account' => trim($_POST['account']),
                        'status' => trim($_POST['status']),
                        'position' => trim($_POST['position']),
                        );


            if ($_POST['check'] == "true") {
                $data['staff_update_user'] = $_SESSION['userid_logined'];
                $data['staff_update_time'] = time();
                //var_dump($data);
                if ($staff->getAllStaffByWhere($_POST['yes'].' AND staff_code = "'.trim($_POST['staff_code']).'"')) {
                    echo "Mã nhân viên đã được sử dụng";
                    return false;
                }
                
                
                else{
                    if (trim($_POST['account']) > 0) {
                        if ($staff->getAllStaffByWhere($_POST['yes'].' AND account = '.trim($_POST['account']))) {
                            echo "Tên truy cập đã được sử dụng";
                            return false;
                        }
                    }
                    $staff->updateStaff($data,array('staff_id' => $_POST['yes']));

                    /*Log*/
                    /**/
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|staff|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
            else{
                $data['staff_create_user'] = $_SESSION['userid_logined'];
                $data['staff_create_time'] = date('m/Y');
                //$data['staff'] = $_POST['staff'];
                //var_dump($data);
                if ($staff->getStaffByWhere(array('staff_code'=>trim($_POST['staff_code'])))) {
                    echo "Mã nhân viên đã được sử dụng";
                    return false;
                }
                if ($staff->getStaffByWhere(array('staff_code'=>trim($_POST['staff_code']),'staff_name' => trim($_POST['staff_name'])))) {
                    echo "Nhân viên này đã tồn tại";
                    return false;
                }
                
                else{
                    if (trim($_POST['account']) > 0) {
                        if ($staff->getStaffByWhere(array('account'=>$_POST['account']))) {
                            echo "Tên truy cập đã được sử dụng";
                            return false;
                        }
                    }

                    $staff->createStaff($data);

                    /*Log*/
                    /**/

                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$staff->getLastStaff()->staff_id."|staff|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff = $this->model->get('staffModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $staff->deleteStaff($data);
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|staff|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }

                /*Log*/
                    /**/

                return true;
            }
            else{
                /*Log*/
                    /**/
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|staff|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $staff->deleteStaff($_POST['data']);
            }
            
        }
    }

    public function getStaff($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>