<?php
Class paymentrequestdetailController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi tiết đề nghị thanh toán';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'payment_request_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, payment_request_number ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-01-'.date('Y');
            $ketthuc = date('t-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $id = $this->registry->router->param_id;

        $payment_request_detail_model = $this->model->get('paymentrequestdetailModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'payment_request,user','where'=>'payment_request=payment_request_id AND payment_request_user=user_id');
        $data = array(
            'where' => 'payment_request='.$id,
        );

        
        $tongsodong = count($payment_request_detail_model->getAllPayment($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'payment_request='.$id,
            );

        
        
      
        if ($keyword != '') {
            $search = '( payment_request_number LIKE "%'.$keyword.'%" 
                    OR payment_request_detail_comment LIKE "%'.$keyword.'%" 
                    OR payment_request_detail_money LIKE "%'.$keyword.'%" 
                    OR payment_request_detail_code LIKE "%'.$keyword.'%" 
             )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $payment_request_details = $payment_request_detail_model->getAllPayment($data,$join);

        
        $this->view->data['payment_request_details'] = $payment_request_details;

        $payable_model = $this->model->get('payableModel');
        $order_tire_model = $this->model->get('ordertireModel');

        $order_data = array();
        foreach ($payment_request_details as $payment_request_detail) {
            $payment_detail = $payment_request_detail_model->getPayment($payment_request_detail->payment_request_detail_id);
            if ($payment_detail->payable>0) {
                $payable = $payable_model->getCosts($payment_detail->payable);
                if ($payable->order_tire>0) {
                    $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id='.$payable->order_tire),array('table'=>'customer','where'=>'customer=customer_id'));
                    foreach ($orders as $order) {
                        $order_data[$payment_request_detail->payment_request_detail_id]['kh'] = $order->customer_name;
                        $order_data[$payment_request_detail->payment_request_detail_id]['sl'] = $order->order_tire_number;
                    }
                }
            }
        }
       $this->view->data['order_data'] = $order_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('paymentrequestdetail/index');
    }
    

}
?>