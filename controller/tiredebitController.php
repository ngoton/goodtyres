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

        $customer_model = $this->model->get('customerModel');
        $customer = $customer_model->getCustomer($trangthai);
        $this->view->data['customer'] = $customer;

        $data = array(
            'where' => '(customer_id IN (SELECT customer FROM tire_sale) OR customer_id IN (SELECT customer FROM deposit_tire))',
        );
        if ($nv > 0) {
            $data['where'] = '(customer_id IN (SELECT customer FROM tire_sale WHERE sale = '.$nv.' ) )';
        }

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $tongsodong = count($customer_model->getAllCustomer($data));
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
        $this->view->data['ketthuc'] = $ketthuc;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'(customer_id IN (SELECT customer FROM tire_sale) OR customer_id IN (SELECT customer FROM deposit_tire))',
            );

        if ($nv > 0) {
            $data['where'] = '(customer_id IN (SELECT customer FROM tire_sale WHERE sale  = '.$nv.' ) )';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 

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
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );


        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $this->view->data['order_tires'] = $orders;

        $receive_model = $this->model->get('receiveModel');

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$order->order_tire_number:$order->order_tire_number;
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['sale'][$order->customer] = $order->username;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $data = array(
            'where'=>'receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );


        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        

        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $receivables = $receivable_model->getAllCosts($data,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');

        

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );
            if ($nv > 0) {
                $data['where'] .= ' AND sale = '.$nv;
            }

            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                if ($nv > 0) {
                    $data['where'] .= ' AND sale = '.$nv;
                }

                $sales = $tire_sale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                    $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
                }
            }

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        $pay_model = $this->model->get('payableModel');
        $join = array('table'=>'pay','where'=>'pay.payable = payable_id');
        $data = array(
            'where' => 'pay.pay_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $pays = $pay_model->getAllCosts($data,$join);

        foreach ($pays as $pay) {
            $data_customer['money'][$pay->customer] = isset($data_customer['money'][$pay->customer])?$data_customer['money'][$pay->customer]+$pay->money:$pay->money;
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

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
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

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
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
        $ketthuc = date('d-m-Y',$this->registry->router->order);

        $cus = $this->registry->router->page;
        $previous_url = $this->registry->router->order_by;
        $this->view->data['previous_url'] = $previous_url.'/'.$cus.'/'.strtotime($ketthuc);

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'DESC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE order_tire = '.$id.') AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

        $receives = $receive_model->getAllCosts($data,$join);
        if (!$receives) {
            $data = array(
                'order_by'=>'receive_date',
                'order'=>'DESC',
                'where'=>'receivable  = '.$id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
                );

            $receives = $receive_model->getAllCosts($data,$join);
        }
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/pay');
    }
    public function paydeposit() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã cấn trừ';

        $id = $this->registry->router->param_id;
        $ketthuc = date('d-m-Y',$this->registry->router->order);

        $cus = $this->registry->router->page;
        $previous_url = $this->registry->router->order_by;
        $this->view->data['previous_url'] = $previous_url.'/'.$cus.'/'.strtotime($ketthuc);

        $receive_model = $this->model->get('receiveModel');

        $receives = $receive_model->queryCosts('SELECT receivable.code, receive_date, receive.money, bank_id, bank_name, receive_comment FROM receive, receivable, bank WHERE receive.source = bank_id AND receivable = receivable_id AND receive.additional = '.$id.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))).' ORDER BY receive_date ASC');
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/paydeposit');
    }
    public function customer() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        $id = $this->registry->router->param_id;
        $this->view->data['cus'] = $id;

        $ketthuc = date('d-m-Y',$this->registry->router->page);
        $this->view->data['ketthuc'] = $ketthuc;

        $batdau = $this->registry->router->order>0?date('d-m-Y',$this->registry->router->order):null;
        $this->view->data['batdau'] = $batdau;

        $trangthai = $this->registry->router->order_by;
        $this->view->data['trangthai'] = $trangthai;

        $order_tire_model = $this->model->get('ordertireModel');

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'order_tire.customer = '.$id.' AND delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

        if ($batdau>0) {
            $data['where'] .= ' AND delivery_date >= '.strtotime($batdau);
        }

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 
        $data = array(
            'order_by'=>'receivable.expect_date',
            'order'=>'DESC',
            'where'=>'customer_id = '.$id.' AND receivable.expect_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
        if ($batdau>0) {
            $data['where'] .= ' AND receivable.expect_date >= '.strtotime($batdau);
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

            if ($batdau>0) {
                $data['where'] .= ' AND receive_date >= '.strtotime($batdau);
            }

            $receives = $receive_model->getAllCosts($data);
            
            foreach ($receives as $receive) {
                $receivable_data[$re->receivable_id]['pay_money'] = isset($receivable_data[$re->receivable_id]['pay_money'])?$receivable_data[$re->receivable_id]['pay_money']+$receive->money:$receive->money;
            }
        }

        $invoice_tire_model = $this->model->get('invoicetireModel');
        
        $invoice_data = array();
        foreach ($orders as $order) {
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

            if ($batdau>0) {
                $data['where'] .= ' AND receive_date >= '.strtotime($batdau);
            }

            $receives = $receive_model->getAllCosts($data);
            
            foreach ($receives as $receive) {
                $receivable_data[$order->receivable_id]['pay_money'] = isset($receivable_data[$order->receivable_id]['pay_money'])?$receivable_data[$order->receivable_id]['pay_money']+$receive->money:$receive->money;
            }

            $invoice = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($invoice as $invoices) {
                $invoice_data[$order->order_tire_id]['number'] = isset($invoice_data[$order->order_tire_id]['number'])?$invoice_data[$order->order_tire_id]['number'].' | '.$invoices->invoice_tire_number:$invoices->invoice_tire_number;
                $invoice_data[$order->order_tire_id]['date'] = isset($invoice_data[$order->order_tire_id]['date'])?$invoice_data[$order->order_tire_id]['date'].' | '.$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date):$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date);
            }
        }

        $this->view->data['receivable_data'] = $receivable_data;
        $this->view->data['invoice_data'] = $invoice_data;

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily, customer','where'=>'daily = daily_id AND deposit_tire.customer = customer_id');
        $data = array(
            'where' => 'deposit_tire.customer = '.$id.' AND daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );

        if ($batdau>0) {
            $data['where'] .= ' AND daily_date >= '.strtotime($batdau);
        }

        $deposits = $deposit_model->getAllDeposit($data,$join);
        $this->view->data['deposits'] = $deposits;

        $deposit_data = array();
        foreach ($deposits as $de) {
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));

            if ($batdau>0) {
                $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))).' AND receivable_date >= '.strtotime($batdau));
            }

            foreach ($receives as $re) {
                $deposit_data[$de->deposit_tire_id]['pay_money'] = isset($deposit_data[$de->deposit_tire_id]['pay_money'])?$deposit_data[$de->deposit_tire_id]['pay_money']+$re->money:$re->money;
            }
        }
        $this->view->data['deposit_data'] = $deposit_data;

        $pay_model = $this->model->get('payableModel');
        $join = array('table'=>'pay, customer','where'=>'pay.payable = payable_id AND payable.customer = customer_id');
        $data = array(
            'where' => 'payable.customer = '.$id.' AND pay.pay_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );

        if ($batdau>0) {
            $data['where'] .= ' AND pay.pay_date >= '.strtotime($batdau);
        }

        $pays = $pay_model->getAllCosts($data,$join);
        $this->view->data['pays'] = $pays;

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
        $ketthuc = date('d-m-Y',$this->registry->router->page);

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'DESC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE customer = '.$id.') AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
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
        $ketthuc = date('d-m-Y',$this->registry->router->page);
        $this->view->data['ketthuc'] = $ketthuc;

        $order_tire_model = $this->model->get('ordertireModel');
        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'order_tire.customer = '.$id.' AND delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 
        $data = array(
            'order_by'=>'receivable.expect_date',
            'order'=>'DESC',
            'where'=>'customer_id = '.$id.' AND receivable.expect_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );

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

        $invoice_tire_model = $this->model->get('invoicetireModel');
        
        $invoice_data = array();
        foreach ($orders as $order) {
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            foreach ($receives as $receive) {
                $receivable_data[$order->receivable_id]['pay_money'] = isset($receivable_data[$order->receivable_id]['pay_money'])?$receivable_data[$order->receivable_id]['pay_money']+$receive->money:$receive->money;
            }

            $invoice = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($invoice as $invoices) {
                $invoice_data[$order->order_tire_id]['number'] = isset($invoice_data[$order->order_tire_id]['number'])?$invoice_data[$order->order_tire_id]['number'].' | '.$invoices->invoice_tire_number:$invoices->invoice_tire_number;
                $invoice_data[$order->order_tire_id]['date'] = isset($invoice_data[$order->order_tire_id]['date'])?$invoice_data[$order->order_tire_id]['date'].' | '.$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date):$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date);
            }
        }

        $this->view->data['receivable_data'] = $receivable_data;
        $this->view->data['invoice_data'] = $invoice_data;

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily, customer','where'=>'daily = daily_id AND deposit_tire.customer = customer_id');
        $data = array(
            'where' => 'deposit_tire.customer = '.$id.' AND daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
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

        $pay_model = $this->model->get('payableModel');
        $join = array('table'=>'pay, customer','where'=>'pay.payable = payable_id AND payable.customer = customer_id');
        $data = array(
            'where' => 'payable.customer = '.$id.' AND pay.pay_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $pays = $pay_model->getAllCosts($data,$join);
        $this->view->data['pays'] = $pays;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/cus');
    }

    function export(){

        $this->view->disableLayout();

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }



        $kh = $this->registry->router->param_id;
        $ketthuc = $this->registry->router->page>0?date('d-m-Y',$this->registry->router->page):null;
        $batdau = $this->registry->router->order>0?date('d-m-Y',$this->registry->router->order):null;
        $trangthai = $this->registry->router->order_by;
        

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $receivable_model = $this->model->get('receivableModel');

        $join = array('table'=>'customer','where'=>'customer_id = customer');

        $data['where'] = "1=1";

        if($kh > 0){

            $data['where'] .= ' AND order_tire_status=1 AND customer = '.$kh;

        }

        if ($batdau != null) {
            $data['where'] .= ' AND delivery_date >= '.strtotime($batdau);
        }

        if ($ketthuc != null) {
            $data['where'] .= ' AND delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days')));
        }

        /*if ($_SESSION['role_logined'] == 3) {

            $data['where'] = $data['where'].' AND shipment_create_user = '.$_SESSION['userid_logined'];

            

        }*/


        $data['order_by'] = 'order_number';

        $data['order'] = 'DESC';



        $orders = $order_tire_model->getAllTire($data,$join);

        $invoice_tire_model = $this->model->get('invoicetireModel');
        
        $invoice_data = array();
        foreach ($orders as $order) {
            $invoice = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($invoice as $invoices) {
                $invoice_data[$order->order_tire_id]['number'] = isset($invoice_data[$order->order_tire_id]['number'])?$invoice_data[$order->order_tire_id]['number'].' | '.$invoices->invoice_tire_number:"'".$invoices->invoice_tire_number;
                $invoice_data[$order->order_tire_id]['date'] = isset($invoice_data[$order->order_tire_id]['date'])?$invoice_data[$order->order_tire_id]['date'].' | '.$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date):$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date);
            }
        }

        

            require("lib/Classes/PHPExcel/IOFactory.php");

            require("lib/Classes/PHPExcel.php");



            $objPHPExcel = new PHPExcel();



            



            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A1', 'CÔNG TY TNHH VIỆT TRA DE')

                ->setCellValue('A2', 'PHÒNG KINH DOANH')

                ->setCellValue('H1', 'CỘNG HÒA XÃ CHỦ NGHĨA VIỆT NAM')

                ->setCellValue('H2', 'Độc lập - Tự do - Hạnh phúc')

                ->setCellValue('I4', 'Biên Hòa, ngày '.date('d').' tháng '.date('m').' năm '.date('Y'))

                ->setCellValue('A6', 'BẢNG KÊ MUA HÀNG LỐP XE')

                ->setCellValue('A8', 'STT')

               ->setCellValue('B8', 'Ngày')

               ->setCellValue('C8', 'Số ĐH')

               ->setCellValue('D8', 'Tên hàng')

               ->setCellValue('E8', 'Loại hàng')

               ->setCellValue('F8', 'Số lượng')

               ->setCellValue('G8', 'Đơn giá')

               ->setCellValue('H8', 'Thành tiền')

               ->setCellValue('I8', 'Trừ giảm')

               ->setCellValue('J8', 'Đã TT')

               ->setCellValue('K8', 'KH Phải trải')

               ->setCellValue('L8', 'Ghi chú');

               


            if ($orders) {



                $hang = 9;

                $i=1;


                $k=0;
                foreach ($orders as $row) {
                    $tencongty = $row->company_name;

                    $sohang = $hang;

                    $receivable = $receivable_model->getCostsByWhere(array('order_tire'=>$row->order_tire_id));

                    if ($trangthai==1) {
                        if ($row->total-$receivable->pay_money != 0) {
                            $join_order = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
                            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$row->order_tire_id), $join_order);
                            if ($order_lists) {

                                
                                //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

                                 $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('A' . $hang, $i++)

                                    ->setCellValueExplicit('B' . $hang, $this->lib->hien_thi_ngay_thang($row->order_tire_date))

                                    ->setCellValue('C' . $hang, $row->order_number)

                                    ->setCellValue('L' . $hang, $invoice_data[$row->order_tire_id]['number']);


                                foreach ($order_lists as $order_list) {

                                    $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('D' . $hang, $order_list->tire_brand_name)

                                    ->setCellValue('E' . $hang, $order_list->tire_size_number.' '.$order_list->tire_pattern_name)

                                    ->setCellValue('F' . $hang, $order_list->tire_number)

                                    ->setCellValue('G' . $hang, ($row->check_price_vat==1?$order_list->tire_price_vat:$order_list->tire_price+($row->vat/$row->order_tire_number)))

                                    ->setCellValue('H' . $hang, '=F'.$hang.'*G'.$hang)

                                    ->setCellValue('I' . $hang, $row->discount+$row->reduce)

                                    ->setCellValue('J' . $hang, $receivable->pay_money);

                                    $hang++;

                                }

                                $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('K' . $sohang, '=SUM(H'.$sohang.':H'.($hang-1).')-J'.$sohang.'-I'.$sohang);

                                $objPHPExcel->getActiveSheet()->mergeCells('A'.$sohang.':A'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('B'.$sohang.':B'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('C'.$sohang.':C'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('I'.$sohang.':I'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('J'.$sohang.':J'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('K'.$sohang.':K'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('L'.$sohang.':L'.($hang-1));


                            }
                        }
                    }
                    else if($trangthai==2){
                        if ($row->total-$receivable->pay_money == 0) {
                            $join_order = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
                            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$row->order_tire_id), $join_order);
                            if ($order_lists) {

                                
                                //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

                                 $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('A' . $hang, $i++)

                                    ->setCellValueExplicit('B' . $hang, $this->lib->hien_thi_ngay_thang($row->order_tire_date))

                                    ->setCellValue('C' . $hang, $row->order_number)

                                    ->setCellValue('L' . $hang, $invoice_data[$row->order_tire_id]['number']);


                                foreach ($order_lists as $order_list) {

                                    $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('D' . $hang, $order_list->tire_brand_name)

                                    ->setCellValue('E' . $hang, $order_list->tire_size_number.' '.$order_list->tire_pattern_name)

                                    ->setCellValue('F' . $hang, $order_list->tire_number)

                                    ->setCellValue('G' . $hang, ($row->check_price_vat==1?$order_list->tire_price_vat:$order_list->tire_price+($row->vat/$row->order_tire_number)))

                                    ->setCellValue('H' . $hang, '=F'.$hang.'*G'.$hang)

                                    ->setCellValue('I' . $hang, $row->discount+$row->reduce)

                                    ->setCellValue('J' . $hang, $receivable->pay_money);

                                    $hang++;

                                }

                                $objPHPExcel->setActiveSheetIndex(0)

                                    ->setCellValue('K' . $sohang, '=SUM(H'.$sohang.':H'.($hang-1).')-J'.$sohang.'-I'.$sohang);

                                $objPHPExcel->getActiveSheet()->mergeCells('A'.$sohang.':A'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('B'.$sohang.':B'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('C'.$sohang.':C'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('I'.$sohang.':I'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('J'.$sohang.':J'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('K'.$sohang.':K'.($hang-1));
                                $objPHPExcel->getActiveSheet()->mergeCells('L'.$sohang.':L'.($hang-1));


                            }
                        }
                    }
                    else{

                    

                        $join_order = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
                        $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$row->order_tire_id), $join_order);
                        if ($order_lists) {

                            
                            //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

                             $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('A' . $hang, $i++)

                                ->setCellValueExplicit('B' . $hang, $this->lib->hien_thi_ngay_thang($row->order_tire_date))

                                ->setCellValue('C' . $hang, $row->order_number)

                                ->setCellValue('L' . $hang, $invoice_data[$row->order_tire_id]['number']);


                            foreach ($order_lists as $order_list) {

                                $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('D' . $hang, $order_list->tire_brand_name)

                                ->setCellValue('E' . $hang, $order_list->tire_size_number.' '.$order_list->tire_pattern_name)

                                ->setCellValue('F' . $hang, $order_list->tire_number)

                                ->setCellValue('G' . $hang, ($row->check_price_vat==1?$order_list->tire_price_vat:$order_list->tire_price+($row->vat/$row->order_tire_number)))

                                ->setCellValue('H' . $hang, '=F'.$hang.'*G'.$hang)

                                ->setCellValue('I' . $hang, $row->discount+$row->reduce)

                                ->setCellValue('J' . $hang, $receivable->pay_money);

                                $hang++;

                            }

                            $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('K' . $sohang, '=SUM(H'.$sohang.':H'.($hang-1).')-J'.$sohang.'-I'.$sohang);

                            $objPHPExcel->getActiveSheet()->mergeCells('A'.$sohang.':A'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('B'.$sohang.':B'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('C'.$sohang.':C'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('I'.$sohang.':I'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('J'.$sohang.':J'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('K'.$sohang.':K'.($hang-1));
                            $objPHPExcel->getActiveSheet()->mergeCells('L'.$sohang.':L'.($hang-1));


                        }
                    }

                }

            }

            $objPHPExcel->getActiveSheet()->getStyle('I9:L'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.$hang, 'TỔNG CỘNG')

                ->setCellValue('F'.$hang, '=SUM(F9:F'.($hang-1).')')

                ->setCellValue('H'.$hang, '=SUM(H9:H'.($hang-1).')')

                ->setCellValue('I'.$hang, '=SUM(I9:I'.($hang-1).')')

                ->setCellValue('J'.$hang, '=SUM(J9:J'.($hang-1).')')

               ->setCellValue('K'.$hang, '=SUM(K9:K'.($hang-1).')');



            $objPHPExcel->getActiveSheet()->getStyle('A8:L'.$hang)->applyFromArray(

                array(

                    

                    'borders' => array(

                        'allborders' => array(

                          'style' => PHPExcel_Style_Border::BORDER_THIN

                        )

                    )

                )

            );



            $cell = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(10, $hang)->getCalculatedValue();

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.($hang+2), 'Bằng chữ: ');

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

            ->setCellValue('C'.($hang+2), $this->lib->convert_number_to_words(round($cell)).' đồng');



            $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':E'.$hang);

            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+2).':B'.($hang+2));

            $objPHPExcel->getActiveSheet()->mergeCells('C'.($hang+2).':L'.($hang+2));


            $objPHPExcel->getActiveSheet()->getRowDimension($hang+1)->setRowHeight(8);


            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);





            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.($hang+4), 'NGƯỜI LẬP BIỂU')

                ->setCellValue('E'.($hang+4), 'CÔNG TY TNHH VIỆT TRA DE')

               ->setCellValue('H'.($hang+4), mb_strtoupper($tencongty, "UTF-8"));



            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+4).':D'.($hang+4));

            $objPHPExcel->getActiveSheet()->mergeCells('E'.($hang+4).':G'.($hang+4));

            $objPHPExcel->getActiveSheet()->mergeCells('H'.($hang+4).':L'.($hang+4));


            $objPHPExcel->getActiveSheet()->getStyle('A8:E'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:E'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':L'.($hang+4))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':L'.($hang+4))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);



            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':L'.($hang+4))->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->getStyle('C'.($hang+2))->getFont()->setBold(false);
            $objPHPExcel->getActiveSheet()->getStyle('C'.($hang+2))->getFont()->setItalic(true);



            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



            $highestRow ++;



            $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');

            $objPHPExcel->getActiveSheet()->mergeCells('H1:L1');

            $objPHPExcel->getActiveSheet()->mergeCells('A2:E2');

            $objPHPExcel->getActiveSheet()->mergeCells('H2:L2');

            $objPHPExcel->getActiveSheet()->mergeCells('I4:L4');

            $objPHPExcel->getActiveSheet()->mergeCells('A6:L6');



            $objPHPExcel->getActiveSheet()->getStyle('A1:L6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:L6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('H2')->getFont()->setItalic(true);

            $objPHPExcel->getActiveSheet()->getStyle('I4')->getFont()->setItalic(true);

            $objPHPExcel->getActiveSheet()->getStyle('I4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);


            $objPHPExcel->getActiveSheet()->getStyle('A1:L3')->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->getStyle('A6:L6')->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->applyFromArray(

                array(

                    

                    'font' => array(

                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('G9:L'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A8:L8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:L8')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:L8')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);

            $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(26);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);

            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(18);

            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);

            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:L'.$highestRow)->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A1:L'.$highestRow)->getFont()->setSize(12);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(22);



            // Set properties

            $objPHPExcel->getProperties()->setCreator("VT")

                            ->setLastModifiedBy($_SESSION['user_logined'])

                            ->setTitle("Sale Report")

                            ->setSubject("Sale Report")

                            ->setDescription("Sale Report.")

                            ->setKeywords("Sale Report")

                            ->setCategory("Sale Report");

            $objPHPExcel->getActiveSheet()->setTitle("Bang ke san luong");



            $objPHPExcel->getActiveSheet()->freezePane('A9');

            $objPHPExcel->setActiveSheetIndex(0);







            



            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            header("Content-Disposition: attachment; filename= BẢNG KÊ MUA HÀNG.xlsx");

            header("Cache-Control: max-age=0");

            ob_clean();

            $objWriter->save("php://output");

        

    }

        function exportdebit(){

        $this->view->disableLayout();

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        $sal = $this->registry->router->param_id;
        $ketthuc = date('d-m-Y',$this->registry->router->page);

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name','order'=>'ASC'));

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel'); 
        $receive_model = $this->model->get('receiveModel');

        $data = array(
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );

        if ($sal>0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$sal.')';
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$order->order_tire_number:$order->order_tire_number;
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['sale'][$order->customer] = $order->username;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $receivables = $receivable_model->getAllCosts(null,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );

            if ($sal>0) {
                $data['where'] .= ' AND sale = '.$sal;
            }
            

            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                if ($sal>0) {
                    $data['where'] .= ' AND sale = '.$sal;
                }
                

                $sales = $tire_sale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                    $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
                }
            }
            
            
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        $pay_model = $this->model->get('payableModel');
        $join = array('table'=>'pay','where'=>'pay.payable = payable_id');
        $data = array(
            'where' => 'pay.pay_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $pays = $pay_model->getAllCosts($data,$join);

        foreach ($pays as $pay) {
            $data_customer['money'][$pay->customer] = isset($data_customer['money'][$pay->customer])?$data_customer['money'][$pay->customer]+$pay->money:$pay->money;
        }

        

            require("lib/Classes/PHPExcel/IOFactory.php");

            require("lib/Classes/PHPExcel.php");



            $objPHPExcel = new PHPExcel();



            



            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A1', 'CÔNG TY TNHH VIỆT TRA DE')

                ->setCellValue('A2', 'PHÒNG KINH DOANH')

                ->setCellValue('E1', 'CỘNG HÒA XÃ CHỦ NGHĨA VIỆT NAM')

                ->setCellValue('E2', 'Độc lập - Tự do - Hạnh phúc')

                ->setCellValue('E4', 'Biên Hòa, ngày '.date('d').' tháng '.date('m').' năm '.date('Y'))

                ->setCellValue('A6', 'BẢNG KÊ CÔNG NỢ KHÁCH HÀNG')

                ->setCellValue('A8', 'STT')

               ->setCellValue('B8', 'Khách hàng')

               ->setCellValue('C8', 'KH phải trả')

               ->setCellValue('D8', 'Đã trả')

               ->setCellValue('E8', 'Còn lại')

               ->setCellValue('F8', 'Sale')

               ->setCellValue('G8', 'Ghi chú');

               


            if ($customers) {



                $hang = 9;

                $i=1;


                $k=0;
                foreach ($customers as $order_tire) {
                    if (!isset($data_customer['money'][$order_tire->customer_id])) {
                        $data_customer['money'][$order_tire->customer_id] = 0;
                    }
                    if (isset($data_customer['money'][$order_tire->customer_id]) && ( ($data_customer['money'][$order_tire->customer_id]-$data_customer['pay_money'][$order_tire->customer_id]>0) || ($data_customer['pay_money'][$order_tire->customer_id]-$data_customer['money'][$order_tire->customer_id]>0)) ) {

                        $sohang = $hang;

                         $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $order_tire->customer_name)

                            ->setCellValue('E' . $hang, $data_customer['money'][$order_tire->customer_id]-$data_customer['pay_money'][$order_tire->customer_id])

                            ->setCellValueExplicit('F' . $hang, $data_customer['sale'][$order_tire->customer_id]);

                        $hang++;

                        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
                        $data = array(
                            'order_by'=>'delivery_date',
                            'order'=>'DESC',
                            'where'=>'order_tire.customer = '.$order_tire->customer_id.' AND delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
                            );

                        $orders = $order_tire_model->getAllTire($data,$join);

                        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

                        $data = array(
                            'order_by'=>'receivable.expect_date',
                            'order'=>'DESC',
                            'where'=>'customer_id = '.$order_tire->customer_id.' AND receivable.expect_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
                            );

                        $receivables = $receivable_model->getAllCosts($data,$join);

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

                        $join = array('table'=>'daily, customer','where'=>'daily = daily_id AND deposit_tire.customer = customer_id');
                        $data = array(
                            'where' => 'deposit_tire.customer = '.$order_tire->customer_id.' AND daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
                        );
                        $deposits = $deposit_model->getAllDeposit($data,$join);

                        $deposit_data = array();
                        foreach ($deposits as $de) {
                            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
                            foreach ($receives as $re) {
                                $deposit_data[$de->deposit_tire_id]['pay_money'] = isset($deposit_data[$de->deposit_tire_id]['pay_money'])?$deposit_data[$de->deposit_tire_id]['pay_money']+$re->money:$re->money;
                            }
                        }

                        $join = array('table'=>'pay, customer','where'=>'pay.payable = payable_id AND payable.customer = customer_id');
                        $data = array(
                            'where' => 'payable.customer = '.$order_tire->customer_id.' AND pay.pay_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
                        );
                        $pays = $pay_model->getAllCosts($data,$join);


                        foreach ($orders as $order_list) {
                            $pay_money = isset($receivable_data[$order_list->receivable_id]['pay_money'])?$receivable_data[$order_list->receivable_id]['pay_money']:0;
                            if ($order_list->total-$pay_money != 0) {
                                $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('B' . $hang, $order_list->order_number)

                                ->setCellValue('C' . $hang, $order_list->total)

                                ->setCellValue('D' . $hang, $pay_money)

                                ->setCellValue('E' . $hang, '=C'.$hang.'-D'.$hang);

                                $hang++;
                            }

                        }

                        foreach ($receivables as $order_list) {
                            $pay_money = isset($receivable_data[$order_list->receivable_id]['pay_money'])?$receivable_data[$order_list->receivable_id]['pay_money']:0;
                            if ($order_list->money-$pay_money != 0) {

                                $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('B' . $hang, $order_list->code)

                                ->setCellValue('C' . $hang, $order_list->money)

                                ->setCellValue('D' . $hang, $pay_money)

                                ->setCellValue('E' . $hang, '=C'.$hang.'-D'.$hang);

                                $hang++;
                            }

                        }

                        foreach ($deposits as $order_list) {
                            $pay_money = isset($deposit_data[$order_list->deposit_tire_id]['pay_money'])?$deposit_data[$order_list->deposit_tire_id]['pay_money']:0;
                            if ($order_list->money_in-$pay_money-$order_list->money_out != 0) {

                                $objPHPExcel->setActiveSheetIndex(0)

                                ->setCellValue('B' . $hang, $order_list->comment)

                                ->setCellValue('C' . $hang, $order_list->money_out)

                                ->setCellValue('D' . $hang, $order_list->money_in-$pay_money)

                                ->setCellValue('E' . $hang, '=C'.$hang.'-D'.$hang);

                                $hang++;
                            }

                        }

                        foreach ($pays as $order_list) {
                            $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('B' . $hang, $order_list->pay_comment)

                            ->setCellValue('C' . $hang, $order_list->money)

                            ->setCellValue('D' . $hang, 0)

                            ->setCellValue('E' . $hang, '=C'.$hang.'-D'.$hang);

                            $hang++;

                        }

                        $objPHPExcel->getActiveSheet()->mergeCells('A'.$sohang.':A'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('F'.$sohang.':F'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('G'.$sohang.':G'.($hang-1));

                        $objPHPExcel->getActiveSheet()->getStyle('B'.$sohang.':E'.$sohang)->getFont()->setBold(true);

                        $objPHPExcel->getActiveSheet()->getStyle('B'.($sohang+1).':E'.($hang-1))->getFont()->setItalic(true);
                        $objPHPExcel->getActiveSheet()->getStyle('B'.($sohang+1).':B'.($hang-1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

                        $objPHPExcel->getActiveSheet()->getStyle('B'.$sohang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('B'.$sohang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                        
                        //$objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':G'.$hang);
                        //$hang++;

                      }

                }

            }

            $objPHPExcel->getActiveSheet()->getStyle('F9:G'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('F9:G'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.$hang, 'TỔNG CỘNG')

                ->setCellValue('E'.$hang, '=SUM(E9:E'.($hang-1).')/2');

            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':E'.$hang)->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':B'.$hang);



            $objPHPExcel->getActiveSheet()->getStyle('A8:G'.$hang)->applyFromArray(

                array(

                    

                    'borders' => array(

                        'allborders' => array(

                          'style' => PHPExcel_Style_Border::BORDER_THIN

                        )

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.($hang+2), 'NGƯỜI LẬP BIỂU')

                ->setCellValue('E'.($hang+2), 'CÔNG TY TNHH VIỆT TRA DE');



            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+2).':C'.($hang+2));

            $objPHPExcel->getActiveSheet()->mergeCells('E'.($hang+2).':G'.($hang+2));


            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+2).':G'.($hang+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+2).':G'.($hang+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);



            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+2).':G'.($hang+2))->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );



            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



            $highestRow ++;


            $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');

            $objPHPExcel->getActiveSheet()->mergeCells('E1:G1');

            $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');

            $objPHPExcel->getActiveSheet()->mergeCells('E2:G2');

            $objPHPExcel->getActiveSheet()->mergeCells('E4:G4');

            $objPHPExcel->getActiveSheet()->mergeCells('A6:G6');



            $objPHPExcel->getActiveSheet()->getStyle('A1:G6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:G6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('E2')->getFont()->setItalic(true);

            $objPHPExcel->getActiveSheet()->getStyle('E4')->getFont()->setItalic(true);

            $objPHPExcel->getActiveSheet()->getStyle('E4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);


            $objPHPExcel->getActiveSheet()->getStyle('A1:G3')->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->getStyle('A6:G6')->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('A2:C2')->applyFromArray(

                array(

                    

                    'font' => array(

                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,

                    )

                )

            );

            


            $objPHPExcel->getActiveSheet()->getStyle('C9:E'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A9:A'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A9:A'.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:G8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:G8')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A8:G8')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);

            $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(26);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(17);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);

            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);

            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);

            $objPHPExcel->getActiveSheet()->getStyle('A1:G'.$highestRow)->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A1:G'.$highestRow)->getFont()->setSize(12);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(18);



            // Set properties

            $objPHPExcel->getProperties()->setCreator("VT")

                            ->setLastModifiedBy($_SESSION['user_logined'])

                            ->setTitle("Sale Report")

                            ->setSubject("Sale Report")

                            ->setDescription("Sale Report.")

                            ->setKeywords("Sale Report")

                            ->setCategory("Sale Report");

            $objPHPExcel->getActiveSheet()->setTitle("Cong no khach hang");



            $objPHPExcel->getActiveSheet()->freezePane('A9');

            $objPHPExcel->setActiveSheetIndex(0);







            



            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            header("Content-Disposition: attachment; filename= CÔNG NỢ.xlsx");

            header("Cache-Control: max-age=0");

            ob_clean();

            $objWriter->save("php://output");

        

    }


}
?>