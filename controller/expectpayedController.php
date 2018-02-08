<?php
Class expectpayedController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã chi';

        $id = $this->registry->router->param_id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $id = 0;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pay.pay_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date('d-m-Y');
            $ketthuc = date('d-m-Y');
            $trangthai = 1;
        }

        $ngaybatdau = date('d-m-Y', strtotime($batdau. ' - 1 days'));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $nam = date('Y');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer();
        $customer_data = array();
        foreach ($customers as $customer) {
            $customer_data['name'][$customer->customer_id] = $customer->customer_name;
            $customer_data['id'][$customer->customer_id] = $customer->customer_id;
        }
        $this->view->data['customers'] = $customer_data;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor();
        $vendor_data = array();
        foreach ($vendors as $vendor) {
            $vendor_data['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
            $vendor_data['id'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_id;
        }
        $this->view->data['vendors'] = $vendor_data;

        $query = 'SELECT payable.pay_money, approve, approve2, approve3, payable.sale_report, payable.agent, payable.agent_manifest, payable.trading, payable.order_tire, payable.invoice, pay_id, pay.pay_date, pay.money, pay.source, pay.payable, pay.pay_comment, payable_id, payable.code, payable.comment, payable.create_user, payable.customer, payable.vendor, bank_id, bank_name FROM pay, payable, bank WHERE bank.bank_id = pay.source AND payable.payable_id=pay.payable';

        $payable_model = $this->model->get('payableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        

        if (isset($id) && $id > 0) {
            $query .= ' AND payable_id = '.$id;
        }
        
            if ($trangthai==1) {
                $query .= ' AND pay.pay_date >= '.strtotime($batdau).' AND pay.pay_date <= '.strtotime($ketthuc);
            }
            else{
                $query .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).') ) AND (payable.pay_money is null OR payable.pay_money != payable.money)';
            }
            
        
        
        
        $tongsodong = count($payable_model->queryCosts($query));
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
        $this->view->data['trangthai'] = $trangthai;

      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank.bank_name LIKE "%'.$keyword.'%"
                OR code LIKE "%'.$keyword.'%" 
                OR pay.money LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") )';
            
                $query .= ' AND '.$search.' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }
        else{
            $query .= ' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }

        $payables = $payable_model->queryCosts($query);
        
        $this->view->data['payables'] = $payables;

        $join = array('table'=>'bank','where'=>'bank.bank_id = costs.source');
        $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pay_date';

        $costs_model = $this->model->get('costsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'money != 0 AND (check_lohang IS NULL OR check_lohang != 1)',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }

        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <= '.strtotime($ketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }
        
        
        
        $tongsodong = count($costs_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'money != 0 AND (check_lohang IS NULL OR check_lohang != 1) ',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }

            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date >= '.strtotime($batdau).' AND pay_date <= '.strtotime($ketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }


        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR money_in LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $costs = $costs_model->getAllCosts($data,$join);

        $this->view->data['costs'] = $costs;

        $data = array(
            'where' => 'money != 0 AND check_lohang = 1',
        );
        if ($trangthai==1) {
            $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
        }
        else{
            $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_date is null OR pay_date = 0)';
        }

        $lohang_costs = $costs_model->getAllCosts($data,$join);

        $this->view->data['lohang_costs'] = $lohang_costs;

        $join = array('table'=>'bank,assets','where'=>'bank.bank_id = lender_pay.source AND lender_pay_id=lender_pay');
        $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'lender_pay_date';

        $costs_model = $this->model->get('lenderpayModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'lender_pay_money > 0 AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc),
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_pay_id = '.$id;
        }

        
        
        $tongsodong = count($costs_model->getAllLender($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'lender_pay_money > 0 AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_pay_id = '.$id;
        }

          

        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR lender_pay_money LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $lenderpays = $costs_model->getAllLender($data,$join);

        $this->view->data['lenderpays'] = $lenderpays;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('expectpayed/index');
    }

    

}
?>