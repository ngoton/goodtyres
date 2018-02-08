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
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $doanhthu = array();
        $check = array();

        $order_tire_model = $this->model->get('ordertireModel');
        
        $join_order = array('table'=>'customer','where'=>'customer = customer_id');
        $data_order = array(
            'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
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
            'where' => 'sale_date >= '.strtotime($batdau).' AND sale_date < '.strtotime($ngayketthuc),
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
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 5111 AND additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc).')'));
        $tk = array();
        foreach ($accs as $acc) {
            $tk[$acc->account_id] = $acc->account_number;
        }

        $additional_model = $this->model->get('additionalModel');

        $data_account = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc),
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

        $acc_logs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 5112 AND additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc).')'));
        foreach ($acc_logs as $ac) {
            $tk[$ac->account_id] = $ac->account_number;
        }

        $data_account_log = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc),
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
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $doanhthu = array();
        $check = array();

        $order_tire_model = $this->model->get('ordertireModel');
        
        $join_order = array('table'=>'customer','where'=>'customer = customer_id');
        $data_order = array(
            'where' => 'vat > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
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
            'where' => 'revenue_vat > 0 AND sale_date >= '.strtotime($batdau).' AND sale_date < '.strtotime($ngayketthuc),
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
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 3331 AND additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc).')'));
        $tk = array();
        foreach ($accs as $acc) {
            $tk[$acc->account_id] = $acc->account_number;
        }

        $additional_model = $this->model->get('additionalModel');

        $data_account = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc),
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

        $acc_logs = $account_model->getAllAccount(array('account_id IN (SELECT debit FROM addtional,account WHERE credit = account_id AND account_number = 3332 AND additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc).')'));
        foreach ($acc_logs as $ac) {
            $tk[$ac->account_id] = $ac->account_number;
        }

        $data_account_log = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc),
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
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $chiphi = array();
        $check = array();

        $order_cost_model = $this->model->get('ordertirecostModel');
        
        $join_order = array('table'=>'order_tire, shipment_vendor','where'=>'vendor = shipment_vendor_id AND order_tire = order_tire_id');
        $data_order = array(
            'where' => 'order_tire_cost > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
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
        $accs = $account_model->getAllAccount(array('account_id IN (SELECT credit FROM addtional,account WHERE debit = account_id AND account_number = 6418 AND additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc).')'));
        $tk = array();
        foreach ($accs as $acc) {
            $tk[$acc->account_id] = $acc->account_number;
        }

        $additional_model = $this->model->get('additionalModel');

        $data_account = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($ngayketthuc),
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
    public function office() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Chi phí hành chính';

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
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        
        $additional_model = $this->model->get('additionalModel');
        $cost_model = $this->model->get('costsModel');


        $additionals= array();
        $costs = array();

        $join_account = array('table'=>'account','where'=>'debit = account_id AND (account_number = 6422 OR account_number = 6423 OR account_number = 6425 OR account_number = 6427 OR account_number = 6428)');
        $join_bank = array('table'=>'bank','where'=>'source=bank_id');
        for ($l=strtotime($batdau); $l<strtotime($ngayketthuc); $l=strtotime(date('d-m-Y',$l). ' + 1 days')) {
            $data_account = array(
                'where' => 'additional_date >= '.$l.' AND additional_date < '.strtotime(date('d-m-Y',$l). ' + 1 days'),
                'order_by' => 'additional_date ASC, money',
                'order' => 'ASC',
            );
            $additionals[date('d-m-Y', ($l-3600))] = $additional_model->getAllAdditional($data_account,$join_account);

            $data_cost = array(
                'where' => 'check_costs=1 AND money>0 AND pay_date >= '.$l.' AND pay_date < '.strtotime(date('d-m-Y',$l). ' + 1 days'),
            );
            $costs[date('d-m-Y', $l)] = $cost_model->getAllCosts($data_cost,$join_bank);
        }

        $this->view->data['additionals'] = $additionals;
        $this->view->data['costs'] = $costs;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checking/office');
    }

    public function result() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Kết quả hoạt động';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m');
            $ngaytaobatdau = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $doanhthu_lopxe = 0;
        $doanhthu_logs = 0;
        $sanluong = 0;
        $vat_lopxe = 0;
        $chiphi_lopxe = 0;

        $order_cost_model = $this->model->get('ordertirecostModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_import_model = $this->model->get('tireimportModel');
        $payable_model = $this->model->get('payableModel');
        
        $data_order = array(
            'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
        );
        $orders = $order_tire_model->getAllTire($data_order);
        $sale = array();
        $von = 0;
        foreach ($orders as $order) {
            $doanhthu_lopxe += $order->total-$order->vat;
            $sanluong += $order->order_tire_number;
            $vat_lopxe += $order->vat;

            $order_costs = $order_cost_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id));
            foreach ($order_costs as $c) {
                $chiphi_lopxe += $c->order_tire_cost; 
            }

            $ngay = $order->delivery_date;
            $ngayketthuc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.$ngayketthuc.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC, tire_import_id DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($order->order_number != "") {
                    $data = array(
                        'where' => 'order_num = "'.$order->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$ngay)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                        'order_by' => 'start_date',
                        'order' => 'DESC, tire_import_id DESC',
                        'limit' => 1,
                    );
                    $tire_imports = $tire_import_model->getAllTire($data);
                    foreach ($tire_imports as $tire_import) {
                        $gia = $tire_import->tire_price;
                    }
                }

                $von += $l->tire_number*$gia;
            }
        }

        $data_pay = array(
            'where' => '(sale_report > 0 OR trading > 0) AND pay_date >= '.strtotime($batdau).' AND pay_date < '.strtotime($ngayketthuc),
        );
        $pays = $payable_model->getAllCosts($data_pay);
        foreach ($pays as $pay) {
            $chiphi_lopxe += $pay->pay_money;
        }

        $sale_model = $this->model->get('salereportModel');
        $data_sale = array(
            'where' => 'sale_date >= '.strtotime($batdau).' AND sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $sale_model->getAllSale($data_sale);
        foreach ($sales as $sl) {
            $doanhthu_logs += $sl->revenue+round($sl->revenue_vat/1.1);
        }

        
        $vat_logs = 0;
        
        $data_sale = array(
            'where' => 'revenue_vat > 0 AND sale_date >= '.strtotime($batdau).' AND sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $sale_model->getAllSale($data_sale);
        foreach ($sales as $sl) {
            $vat_logs += $sl->revenue_vat-round($sl->revenue_vat/1.1);
        }


        $doanhthu_khac = 0;
        $chiphi_khac = 0;
        $tralai = 0;
        $luong = 0;
        $thuekho = 0;

        $cost_model = $this->model->get('costsModel');

        $data_cost = array(
            'where' => 'pay_date >= '.strtotime($batdau).' AND pay_date < '.strtotime($ngayketthuc),
        );
        $costs = $cost_model->getAllCosts($data_cost);
        foreach ($costs as $cost) {
            
            if ($cost->money_in >0) {
                if ($cost->check_costs == 1) {
                    $doanhthu_khac += $cost->money_in;
                }

            }
            else{
                if ($cost->check_costs == 1) {
                    $chiphi_khac += $cost->money;
                }
                else if ($cost->check_costs == 2) {
                    $luong += $cost->money;
                }
                else if ($cost->check_costs == 4) {
                    $tralai += $cost->money;
                }
                else if ($cost->check_costs == 5) {
                    $thuekho += $cost->money;
                }
            }
            
        }

        $account_model = $this->model->get('accountModel');

        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0)'),array('order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account->account_number;
        }

        $account_balance_model = $this->model->get('accountbalanceModel');

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $account_before[$account->account_number] = isset($account_before[$account->account_number])?$account_before[$account->account_number]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_before[$account_parents[$account->account_parent]] = isset($account_parent_before[$account_parents[$account->account_parent]])?$account_parent_before[$account_parents[$account->account_parent]]+$account->money:$account->money;
            }
            else{
                $account_parent_before[$account->account_number] = isset($account_parent_before[$account->account_number])?$account_parent_before[$account->account_number]+$account->money:$account->money;
            }
        }

        $data = array(
            'where'=>'account_balance_date >= '.strtotime($batdau).' AND account_balance_date < '.strtotime($ngayketthuc),
        );
        $account_balances = $account_balance_model->getAllAccount($data,$join);
        $account_add = array();
        $account_parent_add = array();

        foreach ($account_balances as $account) {
            if ($account->money > 0) {
                $account_add[$account->account_number]['no'] = isset($account_add[$account->account_number]['no'])?$account_add[$account->account_number]['no']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account_parents[$account->account_parent]]['no'] = isset($account_parent_add[$account_parents[$account->account_parent]]['no'])?$account_parent_add[$account_parents[$account->account_parent]]['no']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account_number]['no'] = isset($account_parent_add[$account->account_number]['no'])?$account_parent_add[$account->account_number]['no']+$account->money:$account->money;
                }
            }
            else{
                $account_add[$account->account_number]['co'] = isset($account_add[$account->account_number]['co'])?$account_add[$account->account_number]['co']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account_parents[$account->account_parent]]['co'] = isset($account_parent_add[$account_parents[$account->account_parent]]['co'])?$account_parent_add[$account_parents[$account->account_parent]]['co']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account_number]['co'] = isset($account_parent_add[$account->account_number]['co'])?$account_parent_add[$account->account_number]['co']+$account->money:$account->money;
                }
            }
            
        }

        $accounts = $account_model->getAllAccount();
        $account_num = array();
        foreach ($accounts as $acc) {
            $account_num[$acc->account_number] = $acc->account_id;
        }
        $this->view->data['account_num'] = $account_num;

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['account_parent_before'] = $account_parent_before;
        $this->view->data['account_parent_add'] = $account_parent_add;

        $this->view->data['doanhthu_lopxe'] = $doanhthu_lopxe;
        $this->view->data['doanhthu_logs'] = $doanhthu_logs;
        $this->view->data['vat_lopxe'] = $vat_lopxe;
        $this->view->data['vat_logs'] = $vat_logs;
        $this->view->data['chiphi_lopxe'] = $chiphi_lopxe;
        $this->view->data['chiphi_khac'] = $chiphi_khac;
        $this->view->data['doanhthu_khac'] = $doanhthu_khac;
        $this->view->data['total_sale'] = $sanluong;
        $this->view->data['von_lopxe'] = $von;

        $this->view->data['luong'] = $luong;
        $this->view->data['tralai'] = $tralai;
        $this->view->data['thuekho'] = $thuekho;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['tha'] = $ngaytao;
        $this->view->data['na'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checking/result');
    }

}
?>