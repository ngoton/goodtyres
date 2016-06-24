<?php
Class expectrevenueController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dự thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date('d-m-Y', strtotime('last Monday',strtotime(date('d-m-Y'))));
            $ketthuc = date('d-m-Y', strtotime('next Sunday',strtotime(date('d-m-Y'))));
            $trangthai = 0;
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


        $join = array('table'=>'bank','where'=>'bank.bank_id = receivable.source');

        $receivable_model = $this->model->get('receivableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(staff IS NULL OR staff <= 0) AND money != 0 ',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }
            
        
        
        
        $tongsodong = count($receivable_model->getAllCosts($data,$join));
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(staff IS NULL OR staff <= 0) AND money != 0 ',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }


        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%") 
                OR customer in (SELECT customer_id FROM customer WHERE customer_name LIKE "%'.$keyword.'%") 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $receivables = $receivable_model->getAllCosts($data,$join);

        $this->view->data['receivables'] = $receivables;
        

        $join = array('table'=>'bank','where'=>'bank.bank_id = costs.source_in');

        $costs_model = $this->model->get('costsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'money_in != 0 ',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }

        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_date is null OR pay_date = 0)';
            }
            
        
        
        
        $tongsodong = count($costs_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'money_in != 0 ',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND costs_id = '.$id;
        }

            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_money is null OR pay_money != money)';
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

        $join = array('table'=>'bank, staff','where'=>'bank.bank_id = receivable.source AND staff.staff_id = receivable.staff');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'money != 0 ',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

        
            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }
            
        
        
        
        $tongsodong = count($receivable_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'money != 0 ',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND receivable_id = '.$id;
        }

            if ($trangthai==1) {
                $data['where'] .= ' AND pay_date > '.strtotime($ngaybatdau).' AND pay_date < '.strtotime($ngayketthuc).' AND pay_money = money';
            }
            else{
                $data['where'] .= ' AND ( expect_date < '.strtotime($batdau).' OR ( expect_date > '.strtotime($ngaybatdau).' AND expect_date < '.strtotime($ngayketthuc).') ) AND (pay_money is null OR pay_money != money)';
            }


        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR money LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR invoice_number LIKE "%'.$keyword.'%" 
                OR invoice_number_vat LIKE "%'.$keyword.'%" 
                OR staff in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $staffs = $receivable_model->getAllCosts($data,$join);

        $this->view->data['staffs'] = $staffs;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('expectrevenue/index');
    }

    

}
?>