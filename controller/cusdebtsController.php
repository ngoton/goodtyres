<?php
Class cusdebtsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ khách hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $id = $this->registry->router->param_id;

        $obtain_model = $this->model->get('obtainModel');
        $customer_model = $this->model->get('customerModel');

        $data = array(
            'where' => 'customer_id IN (SELECT customer FROM obtain)',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_id = '.$id;
        }

        $customers = $customer_model->getAllCustomer($data);
        $obtain_data = array();
        foreach ($customers as $customer) {
            $obtains = $obtain_model->getAllObtain(array('where'=>'money > 0 AND customer = '.$customer->customer_id));
            foreach ($obtains as $obtain) {
                $obtain_data['total'][$customer->customer_id] = isset($obtain_data['total'][$customer->customer_id])?($obtain_data['total'][$customer->customer_id]+$obtain->money):(0+$obtain->money);
            }

            $obtain_downs = $obtain_model->getAllObtain(array('where'=>'money < 0 AND customer = '.$customer->customer_id));
            foreach ($obtain_downs as $obtain_down) {
                $obtain_data['down'][$customer->customer_id] = isset($obtain_data['down'][$customer->customer_id])?($obtain_data['down'][$customer->customer_id]-$obtain_down->money):(0-$obtain_down->money);
            }
        }
        $this->view->data['obtain_data'] = $obtain_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        
        
        $tongsodong = count($customers);
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
            'where' => ' customer_id IN (SELECT customer FROM obtain) ',
            );
        
        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_id = '.$id;
        }

        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $this->view->data['customers'] = $customer_model->getAllCustomer($data);
        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->customer_id)?$customer_model->getLastCustomer()->customer_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('cusdebts/index');
    }

    

}
?>