<?php
Class expectrevenuedController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'receive_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date('d-m-Y');
            $ketthuc = date('d-m-Y');
            $trangthai = 1;
        }
//var_dump(strtotime('28-09-2014'));
        $ngaybatdau = date('d-m-Y', strtotime($batdau. ' - 1 days'));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $nam = date('Y');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

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

        $query = 'SELECT receivable.pay_money, expect_date, receivable.sale_report, receivable.agent, receivable.agent_manifest, receivable.trading, receivable.order_tire, receivable.invoice, receive_id, receive_date, receive.money, receive.source, receive.receivable, receive.receive_comment, receivable_id, receivable.code, receivable.comment, receivable.create_user, receivable.staff, receivable.customer, receivable.vendor, bank_id, bank_name FROM receive, receivable, bank WHERE bank.bank_id = receive.source AND receivable.receivable_id=receive.receivable';
        

        $receivable_model = $this->model->get('receivableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $query .= ' AND (staff IS NULL OR staff <= 0) ';

        if (isset($id) && $id > 0) {
            $query .= ' AND receivable_id = '.$id;
        }

        
            if ($trangthai==1) {
                $query .= ' AND receive_date >= '.strtotime($batdau).' AND receive_date <= '.strtotime($ketthuc);
            }
            else{
                $query .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).') ) AND (pay_money is null OR pay_money != receivable.money)';
            }
            
        
        
        
        $tongsodong = count($receivable_model->queryCosts($query));
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
                OR bank_name LIKE "%'.$keyword.'%"
                OR receive.money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") )';
            
                $query .= ' AND '.$search.' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }
        else{
            $query .= ' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }

        $receivables = $receivable_model->queryCosts($query);

        $this->view->data['receivables'] = $receivables;
        

        

        $query = 'SELECT staff_id, staff_name, receivable.pay_money, expect_date, receivable.sale_report, receivable.agent, receivable.agent_manifest, receivable.trading, receivable.order_tire, receivable.invoice, receive_id, receive_date, receive.money, receive.source, receive.receivable, receive.receive_comment, receivable_id, receivable.code, receivable.comment, receivable.create_user, receivable.staff, receivable.customer, receivable.vendor, bank_id, bank_name FROM receive, receivable, bank, staff WHERE staff.staff_id = receivable.staff AND bank.bank_id = receive.source AND receivable.receivable_id=receive.receivable';
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $query .= ' AND staff > 0 ';

        if (isset($id) && $id > 0) {
            $query .= ' AND receivable_id = '.$id;
        }

        
            if ($trangthai==1) {
                $query .= ' AND receive_date >= '.strtotime($batdau).' AND receive_date <= '.strtotime($ketthuc);
            }
            else{
                $query .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc).') ) AND (pay_money is null OR pay_money != receivable.money)';
            }
        
        
        $tongsodong = count($receivable_model->queryCosts($query));
        $tongsotrang = ceil($tongsodong / $sonews);
        



        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR receivable.money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")  )';
            
                $query .= ' AND '.$search.' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }
        else{
            $query .= ' ORDER BY '.$order_by.' '.$order.' LIMIT '.$x.','.$sonews;
        }

        $staffs = $receivable_model->queryCosts($query);

        $this->view->data['staffs'] = $staffs;

        $costs_model = $this->model->get('costsModel');

        $join = array('table'=>'bank, daily, customer','where'=>'bank.bank_id = costs.source_in AND additional=daily_id AND customer=customer_id');
        $data = array(
            'where' => 'costs.money_in != 0 AND deposit = 1',
        );
        if ($trangthai==1) {
            $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
        }
        else{
            $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_date is null OR pay_date = 0)';
        }

        $deposits = $costs_model->getAllCosts($data,$join);
        $this->view->data['deposits'] = $deposits;

        $join = array('table'=>'bank','where'=>'bank.bank_id = costs.source_in');

        $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'pay_date';

        
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'money_in != 0 AND (check_lohang IS NULL OR check_lohang != 1) AND additional NOT IN (SELECT daily_id FROM daily WHERE deposit = 1)',
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
            'where' => 'money_in != 0 AND (check_lohang IS NULL OR check_lohang != 1) AND additional NOT IN (SELECT daily_id FROM daily WHERE deposit = 1)',
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
            'where' => 'costs.money_in != 0 AND check_lohang = 1',
        );
        if ($trangthai==1) {
            $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
        }
        else{
            $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_date is null OR pay_date = 0)';
        }

        $lohang_costs = $costs_model->getAllCosts($data,$join);
        $this->view->data['lohang_costs'] = $lohang_costs;

        $join = array('table'=>'bank, lender','where'=>'bank.bank_id = lender_cost.source AND lender.lender_id = lender_cost.lender');

        $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'lender_cost_date';

        $lender_model = $this->model->get('lendercostModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'lender_cost_date >= '.strtotime($batdau).' AND lender_cost_date <= '.strtotime($ketthuc),
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_cost_id = '.$id;
        }

        
        
        
        $tongsodong = count($lender_model->getAllLender($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'lender_cost_date >= '.strtotime($batdau).' AND lender_cost_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_cost_id = '.$id;
        }

         

        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR money_in LIKE "%'.$keyword.'%" 
                OR lender_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $lendercosts = $lender_model->getAllLender($data,$join);

        $this->view->data['lendercosts'] = $lendercosts;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('expectrevenued/index');
    }

    

}
?>