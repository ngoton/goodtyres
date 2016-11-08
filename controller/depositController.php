<?php
Class depositController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đặt cọc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'deposit_tire.customer';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, daily_date ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
        }

        $customer_model = $this->model->get('customerModel');
        $receive_model = $this->model->get('receiveModel');

        $customers = $customer_model->getAllCustomer();
        $data_customer = array();
        foreach ($customers as $cus) {
            $data_customer[$cus->customer_id] = $cus->customer_name;
        }

        $this->view->data['data_customer'] = $data_customer;

        $deposit_model = $this->model->get('deposittireModel');

        $join = array('table'=>'daily','where'=>'daily = daily_id');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($deposit_model->getAllDeposit($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );
        
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                    OR money_in LIKE "%'.$keyword.'%" 
                    OR money_out LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR deposit_tire.customer IN (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%")  
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $deposits = $deposit_model->getAllDeposit($data,$join);

        $receive_data = array();
        foreach ($deposits as $de) {
            $receive_data[$de->deposit_tire_id] = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily);
        }

        $this->view->data['receive_data'] = $receive_data;
        
        $this->view->data['deposits'] = $deposits;
        $this->view->data['lastID'] = isset($deposit_model->getLastDeposit()->deposit_tire_id)?$deposit_model->getLastDeposit()->deposit_tire_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('deposit/index');
    }

   
   
    

}
?>