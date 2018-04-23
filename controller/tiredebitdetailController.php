<?php
Class tiredebitdetailController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
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
            $ketthuc = date('d-m-Y');
        }

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $customer_model = $this->model->get('customerModel');
        $customer = $customer_model->getCustomer($trangthai);
        $this->view->data['customer'] = $customer;


        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['ketthuc'] = $ketthuc;

        $order_tire_model = $this->model->get('ordertireModel');
        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'due_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

        if ($trangthai>0) {
            $data['where'] .= ' AND order_tire.customer = '.$trangthai;
        }
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }
        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 
        $data = array(
            'order_by'=>'receivable.expect_date',
            'order'=>'DESC',
            'where'=>'receivable.expect_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

        if ($trangthai>0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        }
        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $receivables = $receivable_model->getAllCosts($data,$join);
        $this->view->data['receivables'] = $receivables;

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');
        
        $receivable_data = array();
        foreach ($receivables as $re) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$re->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$re->customer,
            );
            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $receivable_data[$re->receivable_id]['number'] = isset($receivable_data[$re->receivable_id]['number'])?$receivable_data[$re->receivable_id]['number']+$sale->volume:$sale->volume;
                $receivable_data[$re->receivable_id]['sale'] = $sale->username;
            }

            $data = array(
                'where' => 'receivable = '.$re->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            foreach ($receives as $receive) {
                $receivable_data[$re->receivable_id]['pay_money'] = isset($receivable_data[$re->receivable_id]['pay_money'])?$receivable_data[$re->receivable_id]['pay_money']+$receive->money:$receive->money;
            }
        }

        foreach ($orders as $order) {
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            foreach ($receives as $receive) {
                $receivable_data[$order->receivable_id]['pay_money'] = isset($receivable_data[$order->receivable_id]['pay_money'])?$receivable_data[$order->receivable_id]['pay_money']+$receive->money:$receive->money;
            }
        }

        $this->view->data['receivable_data'] = $receivable_data;

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily, customer','where'=>'daily = daily_id AND deposit_tire.customer = customer_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );

        if ($trangthai>0) {
            $data['where'] .= ' AND deposit_tire.customer = '.$trangthai;
        }

        $deposits = $deposit_model->getAllDeposit($data,$join);
        $this->view->data['deposits'] = $deposits;

        $deposit_data = array();
        foreach ($deposits as $de) {
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
            foreach ($receives as $re) {
                $deposit_data[$de->deposit_tire_id]['pay_money'] = isset($deposit_data[$de->deposit_tire_id]['pay_money'])?$deposit_data[$de->deposit_tire_id]['pay_money']+$re->money:$re->money;
            }
        }
        $this->view->data['deposit_data'] = $deposit_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebitdetail/index');
    }

    

}
?>