<?php
Class checkingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Doanh thu';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
        }

        $doanhthu = array();
        $check = array();

        $order_tire_model = $this->model->get('ordertireModel');
        
        $join_order = array('table'=>'customer','where'=>'customer = customer_id');
        $data_order = array(
            'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            'order_by' => 'delivery_date ASC, order_number',
            'order' => 'ASC',
        );
        $orders = $order_tire_model->getAllTire($data_order,$join_order);
        $sale = array();
        foreach ($orders as $order) {
            $doanhthu[]['code'] = $order->order_number;
            $sale[$order->order_number]['customer'] = $order->customer_name;
            $sale[$order->order_number]['money'] = $order->total-$order->vat;
            $sale[$order->order_number]['date'] = $order->delivery_date;
            $check[$order->order_number] = $order->order_number;
        }
        $this->view->data['sale'] = $sale;

        $account_model = $this->model->get('accountModel');
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 5111 AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc).')'));
        $tk = array();
        foreach ($accs as $acc) {
            $tk[$acc->account_id] = $acc->account_number;
        }

        $additional_model = $this->model->get('additionalModel');

        $data_account = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
            'order_by' => 'additional_date ASC, code',
            'order' => 'ASC',
        );
        $join_account = array('table'=>'account','where'=>'credit = account_id AND account_number = 5111');

        $accounts = $additional_model->getAllAdditional($data_account,$join_account);
        $acc = array();
        foreach ($accounts as $account) {
            if (!isset($check[$account->code])) {
                $doanhthu[]['code'] = $account->code;
                $check[$account->code] = $account->code;
            }
            $acc[$account->code]['customer'] = $tk[$account->debit];
            $acc[$account->code]['money'] = isset($acc[$account->code]['money'])?$acc[$account->code]['money']+$account->money:$account->money;
            $acc[$account->code]['date'] = $account->additional_date;
        }
        $this->view->data['acc'] = $acc;

        $this->view->data['doanhthu'] = $doanhthu;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checking/index');
    }

    public function invoice() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Doanh thu';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
        }

        $doanhthu = array();
        $check = array();

        $order_tire_model = $this->model->get('ordertireModel');
        
        $join_order = array('table'=>'customer','where'=>'customer = customer_id');
        $data_order = array(
            'where' => 'vat > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            'order_by' => 'delivery_date ASC, order_number',
            'order' => 'ASC',
        );
        $orders = $order_tire_model->getAllTire($data_order,$join_order);
        $sale = array();
        foreach ($orders as $order) {
            $doanhthu[]['code'] = $order->order_number;
            $sale[$order->order_number]['customer'] = $order->customer_name;
            $sale[$order->order_number]['money'] = $order->vat;
            $sale[$order->order_number]['date'] = $order->delivery_date;
            $check[$order->order_number] = $order->order_number;
        }
        $this->view->data['sale'] = $sale;

        $account_model = $this->model->get('accountModel');
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 3331 AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc).')'));
        $tk = array();
        foreach ($accs as $acc) {
            $tk[$acc->account_id] = $acc->account_number;
        }

        $additional_model = $this->model->get('additionalModel');

        $data_account = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
            'order_by' => 'additional_date ASC, code',
            'order' => 'ASC',
        );
        $join_account = array('table'=>'account','where'=>'credit = account_id AND account_number = 3331');

        $accounts = $additional_model->getAllAdditional($data_account,$join_account);
        $acc = array();
        foreach ($accounts as $account) {
            if (!isset($check[$account->code])) {
                $doanhthu[]['code'] = $account->code;
                $check[$account->code] = $account->code;
            }
            $acc[$account->code]['customer'] = $tk[$account->debit];
            $acc[$account->code]['money'] = isset($acc[$account->code]['money'])?$acc[$account->code]['money']+$account->money:$account->money;
            $acc[$account->code]['date'] = $account->additional_date;
        }
        $this->view->data['acc'] = $acc;

        $this->view->data['doanhthu'] = $doanhthu;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checking/invoice');
    }

}
?>