<?php
Class salesController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý doanh số bán hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $nv = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $nv = 0;
        }


        $staff_model = $this->model->get('staffModel');
        $sales_model = $this->model->get('salesModel');

        $staffs = $staff_model->getAllStaff();

        $this->view->data['staffs'] = $staffs;

        $last_staff_data = array();
        foreach ($staffs as $st) {
            $last_staff = $sales_model->querySales('SELECT * FROM new_salary WHERE staff='.$st->staff_id.' ORDER BY create_time DESC LIMIT 1');
            foreach ($last_staff as $l) {
                $last_staff_data['staff_id'][$l->staff] = $st->staff_id;
                $last_staff_data['staff_name'][$l->staff] = $st->staff_name;
                $last_staff_data['basic_salary'][$l->staff] = $l->basic_salary;
            }
        }
        $this->view->data['last_staffs'] = $last_staff_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = null;

        if ($nv > 0) {
            $data = array(
                'where' => '( m = '.$nv.' OR s = '.$nv.' OR c = '.$nv.')',
            );
        }

        if ($_SESSION['role_logined'] == 4) {
            if ($staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))) {
                $id_staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))->staff_id;

                if ($nv > 0) {
                    $id_staff = $nv;
                }

                $data['where'] = '( m = '.$id_staff.' OR s = '.$id_staff.' OR c = '.$id_staff.')';
            }
            else{
                return $this->view->redirect('');
            }
            
        }
        $join = array('table'=>'customer','where'=>'sales.customer = customer.customer_id  AND ( sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc).' )');

        
        
        $tongsodong = count($sales_model->getAllSales($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $nv;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        if ($nv > 0) {
            $data['where'] = '( m = '.$nv.' OR s = '.$nv.' OR c = '.$nv.')';
        }
        
        if ($keyword != '') {
            $search = ' AND ( customer_name LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR comment LIKE "%'.$keyword.'%" 
                OR revenue LIKE "%'.$keyword.'%" 
                OR cost LIKE "%'.$keyword.'%" 
                OR profit LIKE "%'.$keyword.'%" 
                OR m in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")
                OR s in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")
                OR c in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%"))';
            // if ($ngaytao != '') {
            //     $create_time = ' AND ( sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc).' )';
            //     $data['where'] .= $search.$create_time;
            // }
            // else
                $data['where'] .= $search;
        }
        // if ($ngaytao != '' && $keyword == '') {
        //     $create_time = ' AND ( sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc).' )';
        //     $data['where'] .= $create_time;
        // }
        
        $staff = $staff_model->getAllStaff(array('where'=>'( create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' )'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff  AND create_time <= sales_create_time'));
        $staff_data = array();
        foreach ($staff as $staff) {
            $staff_data['staff_id'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_id;
            $staff_data['staff_name'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_name;
            $staff_data['basic_salary'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->basic_salary;
        }
        
        $this->view->data['staff'] = $staff_data;



        if ($_SESSION['role_logined'] == 4) {
            if ($staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))) {
                $id_staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))->staff_id;
                $data['where'] = $data['where'].' AND ( m = '.$id_staff.' OR s = '.$id_staff.' OR c = '.$id_staff.')';

                $staff = $staff_model->getAllStaff(array('where'=>'(staff_id='.$id_staff.' AND ( create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' ) )'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff AND create_time <= sales_create_time'));
                $staff_data = array();
                foreach ($staff as $staff) {
                    $staff_data['staff_id'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_id;
                    $staff_data['staff_name'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_name;
                    $staff_data['basic_salary'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->basic_salary;
                }
                
                $staff2 = $staff_model->getAllStaff(array('where'=>'(staff_id!='.$id_staff.' AND ( create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' ) )'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff AND create_time <= sales_create_time'));
                foreach ($staff2 as $staff) {
                    $staff_data['staff_id'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_id;
                    $staff_data['staff_name'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_name;
                }

                $this->view->data['staff'] = $staff_data;
            }
            else{
                return $this->view->redirect('');
            }
        }

        $staff_all = $staff_model->getAllStaff(array('where'=>'( sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc).' )'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND create_time <= sales_create_time GROUP BY staff_id'));
        $this->view->data['staff_all'] = $staff_all;

        $this->view->data['sales'] = $sales_model->getAllSales($data,$join);
        $this->view->data['lastID'] = isset($sales_model->getLastSales()->sales_id)?$sales_model->getLastSales()->sales_id:0;
        

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('sales/index');
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $customer_model->getAllCustomer();
            }
            else{
                $data = array(
                'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $customer_model->getAllCustomer($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $customer_name = $rs->customer_name;
                if ($_POST['keyword'] != "*") {
                    $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.$rs->customer_phone.'\',\''.$rs->customer_address.'\',\''.$rs->customer_email.'\')">'.$customer_name.'</li>';
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
            $sales = $this->model->get('salesModel');
            $data = array(
                        
                        'code' => trim($_POST['code']),
                        'comment' => trim($_POST['comment']),
                        'revenue' => trim(str_replace(',','',$_POST['revenue'])),
                        'cost' => trim(str_replace(',','',$_POST['cost'])),
                        'profit' => trim(str_replace(',','',$_POST['profit'])),
                        'sales_create_time' => trim($_POST['sales_create_time']),
                        'm' => trim($_POST['m']),
                        's' => trim($_POST['s']),
                        'c' => trim($_POST['c']),
                        );
            if ($_POST['action'] == "") {
                /*if ($_POST['m'] != "") {
                    $data['m'] = $_POST['m'];
                }
                if ($_POST['s'] != "") {
                    $data['s'] = $_POST['s'];
                }
                if ($_POST['c'] != "") {
                    $data['c'] = $_POST['c'];
                }*/
                $data['sales_update_user'] = $_SESSION['userid_logined'];
                $data['sales_update_time'] = time();
                //var_dump($data);
                if ($sales->getSalesQuery('SELECT * FROM sales WHERE sales_id != '.$_POST['yes'].' AND code = '.trim($_POST['code']).' AND comment = '.trim($_POST['comment']).' AND sales_create_time LIKE "%'.trim($_POST['sales_create_time']).'%"')) {
                    echo "Bảng này đã tồn tại";
                    return false;
                }
                else{
                    if ($_SESSION['role_logined'] == 4) {
                        if ($sales->getSalesByWhere(array('sales_id'=>$_POST['yes'],'sales_create_user'=>$_SESSION['userid_logined']))) {
                            $sales->updateSales($data,array('sales_id' => $_POST['yes']));
                            echo "Cập nhật thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|sales|".implode("-",$data)."\n"."\r\n";
                            
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
                        $sales->updateSales($data,array('sales_id' => $_POST['yes']));
                        echo "Cập nhật thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|sales|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                }
                
            }
            else{
                $id_customer = $_POST['customer'];
                if ($_POST['customer_phone'] != "" && $_POST['customer_address'] != "" && $_POST['customer_email'] != "") {
                    $customer_model = $this->model->get('customerModel');
                    $arr_customer = array(
                        'customer_name' => trim($_POST['customer']),
                        'customer_phone' => trim($_POST['customer_phone']),
                        'customer_address' => trim($_POST['customer_address']),
                        'customer_email' => trim($_POST['customer_email']),
                        );
                    $customer_model->createCustomer($arr_customer);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
                
                
                $data['sales_create_user'] = $_SESSION['userid_logined'];
                $data['customer'] = $id_customer;
                /*$data['m'] = trim($_POST['m']);
                $data['s'] = trim($_POST['s']);
                $data['c'] = trim($_POST['c']);*/
                //var_dump($data);
                if ($sales->getSalesByWhere(array('code'=>trim($_POST['code']),'comment'=>trim($_POST['comment']),'sales_create_time' => trim($_POST['sales_create_time'])))) {
                    echo "Bảng này đã tồn tại";
                    return false;
                }
                else{
                    $sales->createSales($data);
                    echo "Thêm thành công";
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sales->getLastSales()->sales_id."|sales|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sales = $this->model->get('salesModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    if ($_SESSION['role_logined'] == 4) {
                        if ($sales->getSalesByWhere(array('sales_id'=>$data,'sales_create_user'=>$_SESSION['userid_logined']))) {
                            $sales->deleteSales($data);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|sales|"."\n"."\r\n";
                            
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
                        $sales->deleteSales($data);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|sales|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                        return true;
                    }
                    
                }
                return true;
            }
            else{
                    if ($_SESSION['role_logined'] == 4) {
                        if ($sales->getSalesByWhere(array('sales_id'=>$_POST['data'],'sales_create_user'=>$_SESSION['userid_logined']))) {
                            $sales->deleteSales($_POST['data']);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|sales|"."\n"."\r\n";
                        
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
                        $sales->deleteSales($_POST['data']);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|sales|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                        return true;
                    }
            }
            
        }
    }

    public function getstaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff_model = $this->model->get('staffModel');
            $join = array('table'=>'salary','where'=>'salary.staff = staff.staff_id AND salary.salary_create_time LIKE "'.$_POST['create_time'].'"');
            if ($_POST['keyword'] == "*") {
                $list = $staff_model->getAllStaff(null,$join);
            }
            else{
                $data = array(
                'where'=>'( staff_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $staff_model->getAllStaff($data,$join);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $staff_name = $rs->staff_name;
                if ($_POST['keyword'] != "*") {
                    $staff_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->staff_name);
                }
                
                // add new option
                echo '<li onclick="set_item_'.$_POST['text_id'].'(\''.$rs->staff_name.'\',\''.$rs->staff_id.'\',\''.$rs->basic_salary.'\')">'.$staff_name.'</li>';
            }
        }
    }

    function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        //var_dump($this->registry->router->addition);die();

        if ($this->registry->router->param_id != null && $this->registry->router->page != null && $this->registry->router->order_by != null) {
            //$ngaytao = $this->registry->router->param_id.'/'.$this->registry->router->page;
            
                $staff_id = $this->registry->router->order_by;
                $batdau = $this->registry->router->param_id;
                $ketthuc = $this->registry->router->page;

                $ngaybatdau = date('m',$batdau);
                $ngayketthuc = date('m',$ketthuc);
                $nam = date('Y',$batdau);
                $namketthuc = date('Y',$ketthuc);

                require("lib/Classes/PHPExcel/IOFactory.php");
                require("lib/Classes/PHPExcel.php");

                $objPHPExcel = new PHPExcel();

                $staff_model = $this->model->get('staffModel');
                $sales_model = $this->model->get('salesModel');

                if ($namketthuc==$nam) {
                    $vonglap = $ngayketthuc-$ngaybatdau;
                }
                elseif ($namketthuc-$nam == 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc;
                }
                elseif ($namketthuc-$nam > 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc+($namketthuc-$nam-1)*12;
                }

                $m = 0;
                $n = 0;
                for ($z=0; $z <= $vonglap; $z++) { 
                    if (($ngaybatdau+$z) >12 ) {
                        $m = 12;
                        $n = 1;
                    }

                    $start = strtotime('01-'.($ngaybatdau+$z-$m).'-'.($nam+$n));
                    $end = strtotime(date('t-m-Y',$start));

                    $join = array('table'=>'customer','where'=>'sales.customer = customer.customer_id');
                    $data = array(
                        'where' => ' ( (m = '.$staff_id.' OR s = '.$staff_id.' OR c = '.$staff_id.') AND sales_create_time >= '.$start.' AND sales_create_time <= '.$end.' )',
                        );
                    $sale = $sales_model->getAllSales($data,$join);

                    $staff_sales = $staff_model->getAllStaff(array('where'=>'sales_create_time >= '.$start.' AND sales_create_time <= '.$end),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND create_time <= sales_create_time AND staff_id = '.$staff_id.' GROUP BY code'));


                    //$staff = $staff_model->getAllStaff(array('where'=>'salary_create_time LIKE "%'.($ngaybatdau+$z-$m).'/'.($nam+$n).'%"'),array('table'=>'salary,sales','where'=>'staff.staff_id = salary.staff AND (m = '.$staff_id.' OR s = '.$staff_id.' OR c = '.$staff_id.') AND sales_create_time = salary_create_time'));
                    $staff_data = array();
                    foreach ($staff_sales as $staff) {
                        $staff_data['staff_id'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_id;
                        $staff_data['staff_name'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_name;
                        $staff_data['basic_salary'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->basic_salary;
                    }

                    $objPHPExcel->createSheet();
                    $index_worksheet = $z; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A1', 'BẢNG TÍNH LƯƠNG DOANH SỐ '.strtoupper($staff->staff_name).' '.($ngaybatdau+$z-$m).'/'.($nam+$n))
                       ->setCellValue('A3', 'STT')
                       ->setCellValue('B3', 'Code')
                       ->setCellValue('C3', 'MSC')
                       ->setCellValue('C4', 'M')
                       ->setCellValue('D4', 'S')
                       ->setCellValue('E4', 'C');


                    $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
                    $objPHPExcel->getActiveSheet()->mergeCells('A3:A4');
                    $objPHPExcel->getActiveSheet()->mergeCells('B3:B4');
                    $objPHPExcel->getActiveSheet()->mergeCells('C3:E3');
                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('B3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('D4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);
                    
                    
                    
                    $total_bonus = array();
                    $arr_msc = array();

                    if ($sale) {

                        foreach ($sale as $row) {
                             /*******/
                            $arr_msc[date('m-Y',$row->sales_create_time)][$row->m]['m'][$row->sales_id][] = $row->profit;
                            $arr_msc[date('m-Y',$row->sales_create_time)][$row->s]['s'][$row->sales_id][] = $row->profit;
                            $arr_msc[date('m-Y',$row->sales_create_time)][$row->c]['c'][$row->sales_id][] = $row->profit;
                            /********/
                            $staff_msc[date('m-Y',$row->sales_create_time)][$row->m]['m'][$row->sales_id] = $row->profit;
                            $staff_msc[date('m-Y',$row->sales_create_time)][$row->s]['s'][$row->sales_id] = $row->profit;
                            $staff_msc[date('m-Y',$row->sales_create_time)][$row->c]['c'][$row->sales_id] = $row->profit;
                        }
                        $array_sum = 0;
                        $total_bonus = array();

                            $m_sum = array();
                            $s_sum = array();
                            $c_sum = array();
                            $salary_arr = array();

                            $thuong_m = 0;
                            $thuong_s = 0;
                            $thuong_c = 0;

                            $arr_thuong_m = array();
                            $arr_thuong_s = array();
                            $arr_thuong_c = array();

                        foreach ($arr_msc as $thang => $mang) {
                            
                            
                            foreach ($mang as $key => $value) {
                              //var_dump($value['m']);die();
                                $m_sum[$thang][$key] = 0;
                                $s_sum[$thang][$key] = 0;
                                $c_sum[$thang][$key] = 0;
                                $total_bonus[$thang][$key] = 0;

                                $arr_thuong_m[$key][$thang] = 0;
                                $arr_thuong_s[$key][$thang] = 0;
                                $arr_thuong_c[$key][$thang] = 0;

                              if (isset($value['m'])) {
                                foreach ($value['m'] as $key1 => $value1) {
                                    $m_sum[$thang][$key] += array_sum($value1);
                                    
                                }
                              }
                              if (isset($value['s'])) {
                                foreach ($value['s'] as $key2 => $value2) {
                                    $s_sum[$thang][$key] += array_sum($value2);
                                    
                                }
                              }
                              if (isset($value['c'])) {
                                foreach ($value['c'] as $key3 => $value3) {
                                    $c_sum[$thang][$key] += array_sum($value3);
                                    
                                }
                              }
                              
                              //$total_bonus[$key] = (isset($m_bonus[$key])?(array_sum($m_bonus[$key])>0?array_sum($m_bonus[$key]):0):0)+(isset($s_bonus[$key])?(array_sum($s_bonus[$key])>0?array_sum($s_bonus[$key]):0):0)+(isset($c_bonus[$key])?(array_sum($c_bonus[$key])>0?array_sum($c_bonus[$key]):0):0);
                              //$array_sum[] = $total_bonus[$key];

                              $thuong_m = isset($staff_data['basic_salary'][$thang][$key]) ? (($m_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_s = isset($staff_data['basic_salary'][$thang][$key]) ? (($s_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_c = isset($staff_data['basic_salary'][$thang][$key]) ? (($c_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;


                              $arr_thuong_m[$key][$thang] += ($thuong_m > 0 ? $thuong_m : 0);
                              $arr_thuong_s[$key][$thang] += ($thuong_s > 0 ? $thuong_s : 0);
                              $arr_thuong_c[$key][$thang] += ($thuong_c > 0 ? $thuong_c : 0);


                              $total_bonus[$thang][$key] += ($thuong_m > 0 ? $thuong_m : 0) + ($thuong_s > 0 ? $thuong_s : 0) + ($thuong_c > 0 ? $thuong_c : 0);
                              
                            }
                            //$array_sum[] = array_sum($total_bonus);
                            //var_dump($total_bonus);
                            $array_sum += array_sum($total_bonus[$thang]);
                        }

                    }

                        $hang = 5;
                        $i = 1;
                        foreach ($staff_sales as $staff_sale) {
                            if($staff_sale->staff_id==$staff_sale->m || $staff_sale->staff_id==$staff_sale->s || $staff_sale->staff_id==$staff_sale->c){
                                $objPHPExcel->setActiveSheetIndex($index_worksheet)
                                   ->setCellValue('A'.$hang, $i++)
                                   ->setCellValue('B'.$hang, $staff_sale->code)
                                   ->setCellValue('C'.$hang, isset($staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['m'][$staff_sale->sales_id])?$staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['m'][$staff_sale->sales_id]:null)
                                   ->setCellValue('D'.$hang, isset($staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['s'][$staff_sale->sales_id])?$staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['s'][$staff_sale->sales_id]:null)
                                   ->setCellValue('E'.$hang, isset($staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['c'][$staff_sale->sales_id])?$staff_msc[date('m-Y',$staff_sale->sales_create_time)][$staff_sale->staff_id]['c'][$staff_sale->sales_id]:null);

                                   $hang++;
                            }
                        }

                        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('C'.($highestRow+1), array_sum($arr_thuong_m[$staff_id]))
                           ->setCellValue('D'.($highestRow+1), array_sum($arr_thuong_s[$staff_id]))
                           ->setCellValue('E'.($highestRow+1), array_sum($arr_thuong_c[$staff_id]))
                           ->setCellValue('A'.($highestRow+2), 'THƯỞNG THÁNG')
                           ->setCellValue('C'.($highestRow+2), '=SUM(C'.($highestRow+1).':E'.($highestRow+1).')');


                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+2).':B'.($highestRow+2));
                        $objPHPExcel->getActiveSheet()->mergeCells('C'.($highestRow+2).':E'.($highestRow+2));

                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getFont()->setBold(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A1:E4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A1:E4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        

                        $objPHPExcel->getActiveSheet()->getStyle('C5:E'.($highestRow+2))->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                        $objPHPExcel->getActiveSheet()->getStyle("A1:E".($highestRow+2))->getFont()->setName('Times New Roman');
                        
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E4')->getAlignment()->setWrapText(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E4')->applyFromArray(
                            array(
                                'borders' => array(
                                    'allborders' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                                'fill' => array(
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => array('rgb' => '00E0FF')
                                )
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+2).':E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+1))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );

                        $objPHPExcel->getActiveSheet()->getStyle('A5:A'.($highestRow+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A5:A'.($highestRow+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        $objPHPExcel->getActiveSheet()->getStyle("A".($highestRow+2).":E".($highestRow+2))->getFont()->getColor()->setARGB('FF0000');

                        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);
                        $objPHPExcel->getActiveSheet()->getStyle("A3:E".($highestRow))->getFont()->setSize(12);
                        $objPHPExcel->getActiveSheet()->getStyle("A".($highestRow+2).":E".($highestRow+2))->getFont()->setSize(14);
                        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
                        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(28);
                        $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(22);
                        $objPHPExcel->getActiveSheet()->getRowDimension('4')->setRowHeight(22);

                        $objPHPExcel->getActiveSheet()->freezePane('A5');
                        // Set properties
                    $objPHPExcel->getProperties()->setCreator("Cai Mep Trading")
                                    ->setLastModifiedBy($_SESSION['user_logined'])
                                    ->setTitle("Revenue Report")
                                    ->setSubject("Revenue Report")
                                    ->setDescription("Revenue Report.")
                                    ->setKeywords("Revenue Report")
                                    ->setCategory("Revenue Report");
                    $objPHPExcel->getActiveSheet()->setTitle("THÁNG ".($ngaybatdau+$z-$m));

                }

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header("Content-Disposition: attachment; filename= BẢNG LƯƠNG DOANH SỐ ".strtoupper($staff_sale->staff_name)."-".$ngaybatdau.'/'.$nam.'-'.$ngayketthuc.'/'.$namketthuc.".xlsx");
                header("Cache-Control: max-age=0");
                ob_clean();
                $objWriter->save("php://output");
            
            
        }
    }

    public function view() {
        
        $this->view->show('sales/view');
    }

}
?>