<?php
Class debtsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nợ xấu';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }
//var_dump(strtotime('28-09-2014'));
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

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_data['id'][$staff->staff_id] = $staff->staff_id;
        }
        $this->view->data['staffs'] = $staff_data;


        $first_day = date('d-m-Y', strtotime(date('d-m-Y'). ' - 90 days'));

        $join = array('table'=>'bank','where'=>'bank.bank_id = receivable.source');

        $receivable_model = $this->model->get('receivableModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(pay_money is null OR pay_money != money) AND invoice_date > 0 AND expect_date < '.strtotime($first_day),
        );

        
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(pay_money is null OR pay_money != money)  AND expect_date < '.strtotime($first_day),
            );

          


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

        $payable_model = $this->model->get('payableModel');
        $sale_model = $this->model->get('salereportModel');
        $user_model = $this->model->get('userModel');

        $receivables_data = $receivable_model->getAllCosts($data,$join);

        $debts_data = array();

        foreach ($receivables_data as $re) {
            if ($re->sale_report > 0) {
                $debts_data['sale'][$re->receivable_id] = $user_model->getUser($sale_model->getSale($re->sale_report)->sale)->username;
                $payable_data = $payable_model->getAllCosts(array('where'=>'sale_report='.$re->sale_report));

                $tra = 0;
                $datra = 0;

                foreach ($payable_data as $pay) {
                    $tra += $pay->money;
                    $datra += $pay->pay_money;
                }

                $debts_data['vendor'][$re->receivable_id] = $tra-$datra;
            }
            
        }
        $this->view->data['debts_data'] = $debts_data;

        $this->view->data['receivables'] = $receivables_data;
        $this->view->data['lastID'] = isset($receivable_model->getLastCosts()->receivable_id)?$receivable_model->getLastCosts()->receivable_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('debts/index');
    }


}
?>