<?php
Class reportorderrevenueController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn đặt hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
            $code = isset($_POST['tu']) ? $_POST['tu'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, order_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 1;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $tg = $this->registry->router->page;
        $stf = $this->registry->router->order_by;

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'customer_name',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        
        
        $data = array(
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
        );

        if ($nv == 1) {
            $data = array(
                'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
            );
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            $batdau = '01-'.date('m-Y',$tg);
            $ketthuc = date('t-m-Y',$tg);

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
                $page = 1;
                $order_by = 'order_tire_status ASC, delivery_date';
                $order = ' ASC';
            }
        }

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire.customer AND user_id = sale');
        
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
        $this->view->data['nv'] = $nv;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
            );

        if ($nv == 1) {
            $data['where'] = 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc);
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
            }
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $tire_import_model = $this->model->get('tireimportModel');

        $costs = array();
        foreach ($order_tires as $tire) {
            $ngay = $tire->order_tire_status==1?$tire->delivery_date:strtotime(date('d-m-Y'));
            $ngayketthuc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
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
                
                if ($tire->order_number != "") {
                    $data = array(
                        'where' => 'order_num = "'.$tire->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$ngay)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                        'order_by' => 'start_date',
                        'order' => 'DESC, tire_import_id DESC',
                        'limit' => 1,
                    );
                    $tire_imports = $tire_import_model->getAllTire($data);
                    foreach ($tire_imports as $tire_import) {
                        $gia = $tire_import->tire_price;
                    }
                }

                $costs[$tire->order_tire_id] = isset($costs[$tire->order_tire_id])?$costs[$tire->order_tire_id]+$l->tire_number*$gia:$l->tire_number*$gia;
            }
        }
        
        $this->view->data['costs'] = $costs;
        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $tire_sale_model = $this->model->get('tiresaleModel');
        $join = array('table'=>'customer, user, staff','where'=>'customer.customer_id = tire_sale.customer AND staff_id = sale AND account = user_id');
        $data = array(
            'where' => 'customer = 169 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $tire_sale_model->getAllTire($data,$join);

        $costs2 = array();
        foreach ($sales as $tire) {
            $gia = 0;
            $data = array(
                'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC, tire_import_id DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $gia = $tire_import->tire_price;
            }

            $costs2[$tire->tire_sale_id] = isset($costs2[$tire->tire_sale_id])?$costs2[$tire->tire_sale_id]+$tire->volume*$gia:$tire->volume*$gia;
        }
        $this->view->data['costs2'] = $costs2;
        $this->view->data['sales'] = $sales;

        $this->view->show('reportorderrevenue/index');
    }
 

}
?>