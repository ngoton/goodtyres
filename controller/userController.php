<?php
Class userController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 ) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý user';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'user_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        

        $user_model = $this->model->get('userModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'role','where'=>'user.role = role.role_id');

        
        $tongsodong = count($user_model->getAllUser(null,$join));
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
            $search = '( username LIKE "%'.$keyword.'%" OR role_name LIKE "%'.$keyword.'%" )';
            $data['where'] = $search;
        }

        $this->view->data['users'] = $user_model->getAllUser($data,$join);

        return $this->view->show('user/index');
    }

    public function login() {
        $this->view->data['title'] = 'Đăng nhập';
        /*Kiểm tra CSDL*/
        if (isset($_POST['submit'])) {
            if ($_POST['username'] != '' && $_POST['password'] != '' ) {
                $user = $this->model->get('userModel');
                
                $row = $user->getUserByUsername(addslashes($_POST['username']));
                
                if ($row) {
                    if ($row->password == md5($_POST['password']) && $row->user_lock != 1) {
                        $_SESSION['user_logined'] = $row->username;
                        $_SESSION['userid_logined'] = $row->user_id;
                        $_SESSION['role_logined'] = $row->role;
                        echo "Đăng nhập thành công";

                        if (isset($_POST['ghinho']) && $_POST['ghinho'] == 1) { 
                            setcookie("remember", 1,time()+30*60*24*100,"/");
                            setcookie("uu", 'yf'.base64_encode($row->username),time()+30*60*24*100,"/");
                            setcookie("ui", 'kq'.base64_encode($row->user_id),time()+30*60*24*100,"/");
                            setcookie("ro", 'xg'.base64_encode($row->role),time()+30*60*24*100,"/");
                            setcookie("up", 'oi'.md5($_POST['password']),time()+30*60*24*100,"/");
                         }

                        $ipaddress = '';
                        if (getenv('HTTP_CLIENT_IP'))
                            $ipaddress = getenv('HTTP_CLIENT_IP');
                        else if(getenv('HTTP_X_FORWARDED_FOR'))
                            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
                        else if(getenv('HTTP_X_FORWARDED'))
                            $ipaddress = getenv('HTTP_X_FORWARDED');
                        else if(getenv('HTTP_FORWARDED_FOR'))
                            $ipaddress = getenv('HTTP_FORWARDED_FOR');
                        else if(getenv('HTTP_FORWARDED'))
                           $ipaddress = getenv('HTTP_FORWARDED');
                        else if(getenv('REMOTE_ADDR'))
                            $ipaddress = getenv('REMOTE_ADDR');
                        else
                            $ipaddress = 'UNKNOWN';

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "user_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."login"."|".$ipaddress."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                        $this->view->redirect('admin');
                    }
                    else{
                        $this->view->data['error'] = "Sai mật khẩu";
                    }
                }
                else{
                    $this->view->data['error'] =  "Không tồn tại username";
                }
            }
            else{
                $this->view->data['error'] =  "Vui lòng nhập vào username / password";
            }
        }
        return $this->view->show('user/login');
    }

    public function logout(){
        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "user_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."logout"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        session_destroy();
        setcookie("remember", "",time() - 3600,"/");
        setcookie("uu", "",time() - 3600,"/");
        setcookie("ui", "",time() - 3600,"/");
        setcookie("ro", "",time() - 3600,"/");
        setcookie("up", "",time() - 3600,"/");
        return $this->view->redirect('');
    }

    public function add(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 ) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Đăng ký tài khoản';
        /*Lấy danh sách nhân viên*/
        $staff = $this->model->get('staffModel');
        $this->view->data['staff'] = $staff->getAllStaff();
        /*Lấy danh sách quyền*/
        $role = $this->model->get('roleModel');
        $this->view->data['role'] = $role->getAllRole();
        /*Thêm vào CSDL*/
        if (isset($_POST['submit'])) {
            if ($_POST['username'] != '' && $_POST['password'] != '' && $_POST['role'] != '') {
                $user = $this->model->get('userModel');

                $r = $user->getUserByUsername($_POST['username']);
                
                if (!$r) {
                    $time = time();
                    $data = array(
                        'username' => trim($_POST['username']),
                        'password' => trim(md5($_POST['password'])),
                        'create_time' => $time,
                        'role' => trim($_POST['role']),
                        );
                    $user->createUser($data);

                    //Lấy ID mới thêm cập nhật vào bảng nhân viên
                    if ($_POST['staff'] != '') {
                        $last_user = $user->getLastUser();
                        $staff->updateStaff(array('account'=>$last_user->user_id),array('staff_id'=>$_POST['staff']));
                    }
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$user->getLastUser()->user_id."|user|".$data['username']."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                    $this->view->data['error'] = "Đăng kí thành công";
                }
                else{
                     $this->view->data['error'] = "Tên đăng nhập đã tồn tại";
                }
            }
        }
        return $this->view->show('user/add');
    }

    public function edit($id){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 ) {
            return $this->view->redirect('user/login');
        }
        if (!$id) {
            $this->view->redirect('user');
        }
        $this->view->data['title'] = 'Cập nhật tài khoản';
        $user = $this->model->get('userModel');
        $user_data = $user->getUser($id);
        
        if (!$user_data) {
            $this->view->redirect('user');
        }
        else {
            /*Lấy danh sách nhân viên*/
            $staff = $this->model->get('staffModel');
            $staff_data = $staff->getStaffByAccount($id);
            if ($staff_data) {
                $this->view->data['user_staff'] = $staff_data;
                $this->view->data['staff'] = $staff->getAllStaffByWhere($staff_data->staff_id);
            }
            else{
                $this->view->data['user_staff'] = null;
                $this->view->data['staff'] = $staff->getAllStaff();
            }
            
            /*Lấy danh sách quyền*/
            $role = $this->model->get('roleModel');
            $role_data = $role->getRole($user_data->role);
            $this->view->data['user_role'] = $role_data;
            $this->view->data['role'] = $role->getAllRoleByWhere($role_data->role_id);
            /*Thêm vào CSDL*/
            if (isset($_POST['submit'])) {
                if ($_POST['role'] != '') {
                    if ($_POST['password'] != '') {
                        
                        $data = array(
                            'password' => trim(md5($_POST['password'])),
                            'role' => trim($_POST['role']),
                            'user_group' => trim($_POST['user_group']),
                            'user_dept' => trim($_POST['user_dept']),
                            );
                    }
                    else{
                        $data = array(
                            'role' => trim($_POST['role']),
                            'user_group' => trim($_POST['user_group']),
                            'user_dept' => trim($_POST['user_dept']),
                            );
                    }
                        $user->updateUser($data,array('user_id'=>$id));

                        if ($_POST['staff'] != '') {
                            $staff->updateStaff(array('account'=>$id),array('staff_id'=>$_POST['staff']));
                        }
                        
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$id."|user|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                        $this->view->data['error'] = "Cập nhật thành công";
                }
            }
        }
        
        return $this->view->show('user/edit');
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = $this->model->get('userModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $user->deleteUser($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|user|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|user|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $user->deleteUser($_POST['data']);
            }
            
        }
    }
    public function fogot(){
        return $this->view->show('user/fogot');
    }

    private function getUrl(){

    }
    public function info($id){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (!$id) {
            $this->view->redirect('');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['userid_logined'] != $id) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['title'] = 'Thông tin tài khoản';
        $user = $this->model->get('userModel');
        $user_data = $user->getUser($id);
        
        if (!$user_data) {
            $this->view->redirect('user');
        }
        else {
            
            /*Thêm vào CSDL*/
            if (isset($_POST['submit'])) {
                
                    if ($_POST['oldpassword'] != '' && $_POST['password'] != '') {
                        $check = $user->getUserByWhere(array('password'=>md5($_POST['oldpassword'])));
                        if ($check) {
                            $data = array(
                            'password' => trim(md5($_POST['password'])),
                            );
                            $user->updateUser($data,array('user_id'=>$id));
                            $this->view->data['error'] = "Cập nhật thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$id."|user|".implode("-",$data)."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            $this->view->data['error'] = "Mật khẩu cũ không đúng";
                        }
                        
                    }
                    
                
            }
        }
        
        return $this->view->show('user/info');
    }


}
?>