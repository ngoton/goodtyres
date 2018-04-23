<?php
Class invoicetiredetailController Extends baseController {
    
    public function index() {
        
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Hóa đơn';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'invoice_tire_detail_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $nv = "";
        }

        $ngay = $this->registry->router->addition;

        if ($this->registry->router->param_id > 0) {
            $trangthai = $this->registry->router->param_id;
        }
        if ($ngay > 0) {
            $batdau = '01-'.date('m-Y',$ngay);
            $ketthuc = date('t-m-Y',$ngay);
        }

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;


        $invoice_tire_model = $this->model->get('invoicetiredetailModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        
        $data = array(
            'where'=>'invoice_tire_detail_date >= '.strtotime($batdau).' AND invoice_tire_detail_date <= '.strtotime($ketthuc),
        );

        
        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'invoice_tire_detail_brand=tire_brand_id AND invoice_tire_detail_size=tire_size_id AND invoice_tire_detail_pattern=tire_pattern_id');
        
        $tongsodong = count($invoice_tire_model->getAllInvoice($data,$join));
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
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['nv'] = $nv;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'invoice_tire_detail_date >= '.strtotime($batdau).' AND invoice_tire_detail_date <= '.strtotime($ketthuc),
            );

        

        if ($keyword != '') {
            $search = '( invoice_tire_detail_number LIKE "%'.$keyword.'%" 
                OR company_hide LIKE "%'.$keyword.'%"  
                OR mst_hide LIKE "%'.$keyword.'%"  
                OR tire_brand_name LIKE "%'.$keyword.'%" 
                OR tire_size_number LIKE "%'.$keyword.'%" 
                OR tire_pattern_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $invoice_tires = $invoice_tire_model->getAllInvoice($data,$join);
        
        $this->view->data['invoice_tires'] = $invoice_tires;

        $order_tire_model = $this->model->get('ordertireModel');

        $invoice_data = array();
        foreach ($invoice_tires as $order) {
            $orders = $order_tire_model->getTire($order->order_tire);
            $invoice_data[$order->invoice_tire_detail_id] = $orders->order_number;
            
            
        }

        $this->view->data['invoice_data'] = $invoice_data;


        $this->view->show('invoicetiredetail/index');
    }
    public function check(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $invoice_tire_number = trim($_POST['invoice_tire_number']);
            $invoice_tire_date = strtotime(trim($_POST['invoice_tire_date']));

            $invoice_tire_model = $this->model->get('invoicetiredetailModel');

            if (isset($_POST['update'])) {
                $data_check = array(
                    'invoice_tire_number' => $invoice_tire_number,
                    'order_tire' => $_POST['data'],
                    'invoice_tire_date' => $invoice_tire_date,
                );
                $invoice_tire_model->updateInvoice($data_check,array('order_tire'=>$_POST['data']));
            }
            else{
                $invoice = $invoice_tire_model->getInvoiceByWhere(array('order_tire'=>$_POST['data']));
                if ($invoice) {
                    $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$_POST['data']);
                }
                else{
                    $data_check = array(
                        'invoice_tire_number' => $invoice_tire_number,
                        'order_tire' => $_POST['data'],
                        'invoice_tire_date' => $invoice_tire_date,
                    );
                    $invoice_tire_model->createInvoice($data_check);
                }
                
            }
            

            return true;
                    
        }
    }
    

}
?>