<?php
Class tiredebitController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ đơn hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 0;
            $tha = 0;
        }

        $customer_model = $this->model->get('customerModel');
        $customer = $customer_model->getCustomer($trangthai);

        $sonews = $limit;
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
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'1=1',
            );

        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $customers = $customer_model->getAllCustomer($data);
        $this->view->data['customers'] = $customers;
        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->customer_id)?$customer_model->getLastCustomer()->customer_id:0;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel'); 

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'1=1',
            );


        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $this->view->data['order_tires'] = $orders;

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$order->order_tire_number:$order->order_tire_number;
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$order->pay_money:$order->pay_money;
            $data_customer['sale'][$order->customer] = $order->username;
        }
        $this->view->data['data_customer'] = $data_customer;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/index');
    }
    public function view() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ đơn hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 0;
            $tha = 0;
        }

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getCustomer($trangthai);

        

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        

        if ($tha == 0) {
            $data = array(
                'where'=>'(pay_money IS NULL OR pay_money < money)',
            );
        }
        else{
            $data = array(
                'where'=>'pay_money = money',
            );
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;

            $this->view->data['customers'] = $customers;
        }

        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
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
        $this->view->data['tha'] = $tha;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'(pay_money IS NULL OR pay_money < money)',
            );

        if ($tha == 0) {
            $data['where'] = '(pay_money IS NULL OR pay_money < money)';
        }
        else{
            $data['where'] = 'pay_money = money';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $this->view->data['order_tires'] = $orders;
        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/view');
    }
    public function pay() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

        $id = $this->registry->router->param_id;

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'ASC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE order_tire = '.$id.')',
            );

        $receives = $receive_model->getAllCosts($data,$join);
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/pay');
    }
    public function customer() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        $id = $this->registry->router->param_id;

        $order_tire_model = $this->model->get('ordertireModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'order_tire.customer = '.$id,
            );

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/customer');
    }
    public function cuspay() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

        $id = $this->registry->router->param_id;

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'ASC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE customer = '.$id.')',
            );

        $receives = $receive_model->getAllCosts($data,$join);
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/cuspay');
    }
    public function cus() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        $id = $this->registry->router->param_id;

        $order_tire_model = $this->model->get('ordertireModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'(pay_money IS NULL OR pay_money < money) AND order_tire.customer = '.$id,
            );

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/cus');
    }


}
?>