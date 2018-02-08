<?php
Class invoicetireController Extends baseController {
    
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_number';
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


        $invoice_tire_model = $this->model->get('invoicetireModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        
        $data = array(
            'where'=>'approve>0 AND vat > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
        );

        if ($nv == 0) {
            $data['where'] .= ' AND order_tire_id NOT IN (SELECT order_tire FROM invoice_tire)';
        }
        else if ($nv == 1) {
            $data['where'] .= ' AND order_tire_id IN (SELECT order_tire FROM invoice_tire)';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        
        $join = array('table'=>'customer, user, staff','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND user_id = account');
        
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
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['nv'] = $nv;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'approve>0 AND vat > 0 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            );

        if ($nv == 0) {
            $data['where'] .= ' AND order_tire_id NOT IN (SELECT order_tire FROM invoice_tire)';
        }
        else if ($nv == 1) {
            $data['where'] .= ' AND order_tire_id IN (SELECT order_tire FROM invoice_tire)';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"  
                OR order_tire_id IN (SELECT order_tire FROM invoice_tire WHERE invoice_tire_number LIKE "%'.$keyword.'%" ) )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $this->view->data['order_tires'] = $order_tires;

        $invoice_data = array();
        foreach ($order_tires as $order) {
            $invoice = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($invoice as $invoices) {
                $invoice_data[$order->order_tire_id]['number'] = isset($invoice_data[$order->order_tire_id]['number'])?$invoice_data[$order->order_tire_id]['number'].' | '.$invoices->invoice_tire_number:$invoices->invoice_tire_number;
                $invoice_data[$order->order_tire_id]['date'] = isset($invoice_data[$order->order_tire_id]['date'])?$invoice_data[$order->order_tire_id]['date'].' | '.$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date):$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date);
            }
            
        }

        $this->view->data['invoice_data'] = $invoice_data;


        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('invoicetire/index');
    }
    public function check(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $invoice_tire_number = trim($_POST['invoice_tire_number']);
            $invoice_tire_date = strtotime(trim($_POST['invoice_tire_date']));

            $invoice_tire_model = $this->model->get('invoicetireModel');

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