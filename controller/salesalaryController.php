<?php
Class salesalaryController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lương doanh số';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $trangthai = isset($_POST['sl_status']) ? $_POST['sl_status'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sales_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
            $trangthai = 0;
        }
        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $this->view->data['staffs'] = $staffs;

        $sales_model = $this->model->get('salesModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc),
        );

        if ($_SESSION['role_logined'] == 4) {
            if ($staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))) {
                $id_staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']))->staff_id;
                $data['where'] .= ' AND ( m = '.$id_staff.' OR s = '.$id_staff.' OR c = '.$id_staff.')';
            }
            else{
                return $this->view->redirect('');
            }
            
        }
        $join = array('table'=>'customer','where'=>'sales.customer = customer.customer_id ');

        if ($trangthai > 0) {
            $data['where'] .= ' AND ( m = '.$trangthai.' OR s = '.$trangthai.' OR c = '.$trangthai.')';
        }
        
        $tongsodong = count($sales_model->getAllSales($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'sales_create_time >= '.strtotime($batdau).' AND sales_create_time <= '.strtotime($ketthuc),
            );

        if ($trangthai > 0) {
            $data['where'] .= ' AND ( m = '.$trangthai.' OR s = '.$trangthai.' OR c = '.$trangthai.')';
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
            
                $data['where'] .= $search;
        }
        
        $staff = $staff_model->getAllStaff(array('where'=>'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc)),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff'));
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

                $staff = $staff_model->getAllStaff(array('where'=>'(staff_id='.$id_staff.' AND create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).')'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff'));
                $staff_data = array();
                foreach ($staff as $staff) {
                    $staff_data['staff_id'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_id;
                    $staff_data['staff_name'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->staff_name;
                    $staff_data['basic_salary'][date('m-Y',$staff->create_time)][$staff->staff_id] = $staff->basic_salary;
                }
                
                $staff2 = $staff_model->getAllStaff(array('where'=>'(staff_id!='.$id_staff.' AND create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).')'),array('table'=>'new_salary, sales','where'=>'staff.staff_id = new_salary.staff'));
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

        $this->view->data['sales'] = $sales_model->getAllSales($data,$join);
        $this->view->data['lastID'] = isset($sales_model->getLastSales()->sales_id)?$sales_model->getLastSales()->sales_id:0;
        

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('salesalary/index');
    }

    

}
?>