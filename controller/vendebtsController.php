<?php
Class vendebtsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ vendor';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipment_vendor_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = date('d-m-Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($batdau. ' + 1 days'));

        $id = $this->registry->router->param_id;

        $owe_model = $this->model->get('oweModel');
        $shipment_vendor_model = $this->model->get('shipmentvendorModel');

        $data = array(
            'where' => 'shipment_vendor_id IN (SELECT vendor FROM owe)',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND shipment_vendor_id = '.$id;
        }

        $shipment_vendors = $shipment_vendor_model->getAllVendor($data);
        $owe_data = array();
        foreach ($shipment_vendors as $shipment_vendor) {
            $owes = $owe_model->getAllOwe(array('where'=>'money > 0 AND vendor = '.$shipment_vendor->shipment_vendor_id.' AND owe_date < '.strtotime($ngayketthuc)));
            foreach ($owes as $owe) {
                $owe_data['total'][$shipment_vendor->shipment_vendor_id] = isset($owe_data['total'][$shipment_vendor->shipment_vendor_id])?($owe_data['total'][$shipment_vendor->shipment_vendor_id]+$owe->money):(0+$owe->money);
            }

            $owe_downs = $owe_model->getAllOwe(array('where'=>'money < 0 AND vendor = '.$shipment_vendor->shipment_vendor_id.' AND owe_date < '.strtotime($ngayketthuc)));
            foreach ($owe_downs as $owe_down) {
                $owe_data['down'][$shipment_vendor->shipment_vendor_id] = isset($owe_data['down'][$shipment_vendor->shipment_vendor_id])?($owe_data['down'][$shipment_vendor->shipment_vendor_id]-$owe_down->money):(0-$owe_down->money);
            }
        }
        $this->view->data['owe_data'] = $owe_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        
        
        $tongsodong = count($shipment_vendors);
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => ' shipment_vendor_id IN (SELECT vendor FROM owe) ',
            );
        
        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND shipment_vendor_id = '.$id;
        }

        if ($keyword != '') {
            $search = '( shipment_vendor_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        
        $this->view->data['shipment_vendors'] = $shipment_vendor_model->getAllVendor($data);
        $this->view->data['lastID'] = isset($shipment_vendor_model->getLastVendor()->shipment_vendor_id)?$shipment_vendor_model->getLastVendor()->shipment_vendor_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('vendebts/index');
    }

    

}
?>