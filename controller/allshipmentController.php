<?php
Class allshipmentController Extends baseController {
    

    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $code = $this->registry->router->param_id;
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tổng hợp lô hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $shipcode = isset($_POST['shipcode']) ? $_POST['shipcode'] : null;

            $code = null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
            $shipcode = "";
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));
        
        $assets_model = $this->model->get('assetsModel');
        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');

        if ($shipcode != "") {
            $code = $shipcode;
        }

        $sdata = array(
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        if ($code != null) {
            $sdata['where'] = 'sale_type = 1 AND code = '.$code;
        }

     

        $sales = $sale_model->getAllSale($sdata);

        

        $tdata = array(
            'where' => 'sale_type = 2 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        if ($code != null) {
            $tdata['where'] = 'sale_type = 2 AND code = '.$code;
        }

     

        $tradings = $sale_model->getAllSale($tdata);



        $adata = array(
            'where' => 'agent_date >= '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
        );

        if ($code != null) {
            $adata['where'] = 'code = '.$code;
        }

     

        $agents = $agent_model->getAllAgent($adata);

        

        $data_manifest = array(
            'where' => 'agent_manifest_date >= '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
        );

        if ($code != null) {
            $data_manifest['where'] = 'code = '.$code;
        }


        $manifests = $manifest_model->getAllAgent($data_manifest);



        $idata = array(
            'where' => 'day_invoice >= '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
        );

        if ($code != null) {
            $idata['where'] = 'invoice_id = '.$code;
        }


        $invoices = $invoice_model->getAllInvoice($idata);

        
        $this->view->data['sales'] = $sales;
        $this->view->data['tradings'] = $tradings;
        $this->view->data['agents'] = $agents;
        $this->view->data['manifests'] = $manifests;
        $this->view->data['invoices'] = $invoices;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['shipcode'] = $code;
        
        $this->view->show('allshipment/index');
    }

   
    public function receivable(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dự thu';

        $id = $this->registry->router->param_id;

        $cus = $this->registry->router->page;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $id = 0;
            $cus = 0;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'expect_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }


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

        $join = array('table'=>'bank','where'=>'bank.bank_id = receivable.source');

        $data = array(
            'where' => 'code = '.$id,
        );

        if ($cus > 0) {
            $data['where'] .= ' AND customer = '.$cus;
        }

        $receivable_model = $this->model->get('receivableModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $tongsodong = count($receivable_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        
        $this->view->data['receivables'] = $receivable_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($receivable_model->getLastCosts()->receivable_id)?$receivable_model->getLastCosts()->receivable_id:0;

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
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('allshipment/receivable');
    }

    public function payable(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 3) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dự chi vendor';

        $id = $this->registry->router->param_id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $id = 0;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'payable_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }

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

        $join = array('table'=>'bank','where'=>'bank.bank_id = payable.source');

        $data = array(
            'where' => 'code = '.$id,
        );

        $payable_model = $this->model->get('payableModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        $tongsodong = count($payable_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);

        
        $this->view->data['payables'] = $payable_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($payable_model->getLastCosts()->payable_id)?$payable_model->getLastCosts()->payable_id:0;

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
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('allshipment/payable');
    }

}
?>