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

        $sale_model = $this->model->get('salereportModel');
        $data_sale = array(
            'where' => 'sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            'order_by' => 'sale_date ASC, code',
            'order' => 'ASC',
        );
        $sales = $sale_model->getAllSale($data_sale,$join_order);
        foreach ($sales as $sl) {
            $doanhthu[]['code'] = $sl->code;
            $sale[$sl->code]['customer'] = $sl->customer_name;
            $sale[$sl->code]['money'] = $sl->revenue+round($sl->revenue_vat/1.1);
            $sale[$sl->code]['date'] = $sl->sale_date;
            $check[$sl->code] = $sl->code;
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

        $acc_logs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 5112 AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc).')'));
        foreach ($acc_logs as $ac) {
            $tk[$ac->account_id] = $ac->account_number;
        }

        $data_account_log = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
            'order_by' => 'additional_date ASC, code',
            'order' => 'ASC',
        );
        $join_account_log = array('table'=>'account','where'=>'credit = account_id AND account_number = 5112');

        $account_logs = $additional_model->getAllAdditional($data_account_log,$join_account_log);
        foreach ($account_logs as $account) {
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

        $sale_model = $this->model->get('salereportModel');
        $data_sale = array(
            'where' => 'revenue_vat > 0 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
            'order_by' => 'sale_date ASC, code',
            'order' => 'ASC',
        );
        $sales = $sale_model->getAllSale($data_sale,$join_order);
        foreach ($sales as $sl) {
            $doanhthu[]['code'] = $sl->code;
            $sale[$sl->code]['customer'] = $sl->customer_name;
            $sale[$sl->code]['money'] = $sl->revenue_vat-round($sl->revenue_vat/1.1);
            $sale[$sl->code]['date'] = $sl->sale_date;
            $check[$sl->code] = $sl->code;
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

        $acc_logs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 3332 AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc).')'));
        foreach ($acc_logs as $ac) {
            $tk[$ac->account_id] = $ac->account_number;
        }

        $data_account_log = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
            'order_by' => 'additional_date ASC, code',
            'order' => 'ASC',
        );
        $join_account_log = array('table'=>'account','where'=>'credit = account_id AND account_number = 3332');

        $account_logs = $additional_model->getAllAdditional($data_account_log,$join_account_log);
        foreach ($account_logs as $account) {
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
    public function cost() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Chi phí';

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

        $chiphi = array();
        $check = array();

        $order_cost_model = $this->model->get('ordertirecostModel');
        
        $join_order = array('table'=>'order_tire, shipment_vendor','where'=>'vendor = shipment_vendor_id AND order_tire = order_tire_id');
        $data_order = array(
            'where' => 'order_tire_cost > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            'order_by' => 'delivery_date ASC, order_number',
            'order' => 'ASC',
        );
        $orders = $order_cost_model->getAllTire($data_order,$join_order);
        $sale = array();
        foreach ($orders as $order) {
            if (!isset($check[$order->order_number])) {
                $chiphi[]['code'] = $order->order_number;
                $sale[$order->order_number]['money'] = $order->order_tire_cost;
                $sale[$order->order_number]['date'] = $order->delivery_date;
                $sale[$order->order_number]['nd'] = $order->comment.":".$this->lib->formatMoney($order->order_tire_cost);
                $check[$order->order_number] = $order->order_number;
            }
            else{
                $sale[$order->order_number]['money'] = $sale[$order->order_number]['money']+$order->order_tire_cost;
                $sale[$order->order_number]['nd'] = $sale[$order->order_number]['nd']." - ".$order->comment.":".$this->lib->formatMoney($order->order_tire_cost);
            }
            
        }
        $this->view->data['sale'] = $sale;

        $account_model = $this->model->get('accountModel');
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT credit FROM addtional,account WHERE debit = account_id AND account_number = 6418 AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc).')'));
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
        $join_account = array('table'=>'account','where'=>'debit = account_id AND account_number = 6418');

        $accounts = $additional_model->getAllAdditional($data_account,$join_account);
        $acc = array();
        foreach ($accounts as $account) {
            if (!isset($check[$account->code])) {
                $chiphi[]['code'] = $account->code;
                $check[$account->code] = $account->code;
            }
            $acc[$account->code]['money'] = isset($acc[$account->code]['money'])?$acc[$account->code]['money']+$account->money:$account->money;
            $acc[$account->code]['date'] = $account->additional_date;
            $acc[$account->code]['nd'] = $account->additional_comment;
        }
        $this->view->data['acc'] = $acc;

        $this->view->data['chiphi'] = $chiphi;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checking/cost');
    }

}
?>