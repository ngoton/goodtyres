<?php
Class ordertireController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;


        $this->view->show('ordertire/index');
    }
    public function waiting($id) {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if (!$id) {
            return $this->view->redirect('ordertirewaiting');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $order_tire_waiting_model = $this->model->get('ordertirewaitingModel');
        $tire_desired_model = $this->model->get('tiredesiredModel');
        $customer_model = $this->model->get('customerModel');

        $order_tires = $order_tire_waiting_model->getTire($id);
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand_code = tire_brand_id AND tire_size = tire_size_id AND tire_pattern_code = tire_pattern_id');
        $tire_desireds = $tire_desired_model->getAllTire(array('where'=>'order_tire_waiting = '.$id),$join);

        $arr_max = array();

        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $customers = $customer_model->getCustomer($order_tires->customer);

        foreach ($tire_desireds as $tire) {
            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$tire->tire_brand_code.' AND tire_buy_size = '.$tire->tire_size.' AND tire_buy_pattern = '.$tire->tire_pattern_code));
            foreach ($tire_buys as $tire_buy) {
                $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]+$tire_buy->tire_buy_volume:$tire_buy->tire_buy_volume;
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$tire->tire_brand_code.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern_code));
            foreach ($tire_sales as $tire_sale) {
                $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]-$tire_sale->volume:0;
            }

            $order_tire = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tire as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$tire->tire_brand_code.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern_code));
                foreach ($order_tire_lists as $list) {
                    $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]-$list->tire_number:0;
                }
            }
        }

        $this->view->data['arr_max'] = $arr_max;
        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['customers'] = $customers;
        $this->view->data['tire_desireds'] = $tire_desireds;

        $this->view->data['order_tire_waiting'] = $id;

        $this->view->show('ordertire/waiting');
    }
    public function orderlist() {
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
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $trangthai = 0;
            $nv = "";
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $sodonhang = $this->registry->router->addition;

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

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

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') )',
        );
        if ($nv == 1) {
            $data = array(
                'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            );
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($sodonhang != "") {
            $data['where'] = 'order_number = "'.$sodonhang.'"';
        }
        
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
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') )',
            );

        if ($nv == 1) {
            $data['where'] = 'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc);
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($sodonhang != "") {
            $data['where'] = 'order_number = "'.$sodonhang.'"';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff = array();
        foreach ($staffs as $st) {
            $staff[$st->staff_id] = $st->staff_name;
        }

        $lift_model = $this->model->get('liftModel');
        $lift = array();
        foreach ($order_tires as $tire) {
            $lifts = $lift_model->getLiftByWhere(array('order_tire'=>$tire->order_tire_id));
            if ($lifts) {
                $sts = explode(',', $lifts->staff);
                foreach ($sts as $key) {
                    if (!isset($lift[$tire->order_tire_id])) {
                        $lift[$tire->order_tire_id] = $staff[$key];
                    }
                    else{
                        $lift[$tire->order_tire_id] .= ','.$staff[$key];
                    }
                }
            }        
            
        }
        $this->view->data['lift'] = $lift;

        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('ordertire/orderlist');
    }

    public function report() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, delivery_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $trangthai = 0;
            $nv = "";
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $tg = $this->registry->router->page;
        $stf = $this->registry->router->order_by;

        

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
            'where' => 'order_tire_date >= '.strtotime($batdau).' AND order_tire_date <= '.strtotime($ketthuc),
        );

        if ($nv == 1) {
            $data = array(
                'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
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
            'where' => 'order_tire_date >= '.strtotime($batdau).' AND order_tire_date <= '.strtotime($ketthuc),
            );

        if ($nv == 1) {
            $data['where'] = 'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc);
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
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $data = array(
                    'where' => 'start_date <= '.strtotime(date('t-m-Y',$tire->delivery_date)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }

                $data = array(
                    'where' => 'order_num = "'.$tire->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$tire->delivery_date)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
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
            'where' => 'customer = 169 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc),
        );
        $sales = $tire_sale_model->getAllTire($data,$join);

        $costs2 = array();
        foreach ($sales as $tire) {
            $data = array(
                'where' => 'start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC',
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

        $this->view->show('ordertire/report');
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            if ($_SESSION['role_logined']==1 || $_SESSION['role_logined']==3 || $_SESSION['role_logined']==4) {
                if ($_POST['keyword'] == "*") {
                    $list = $customer_model->getAllCustomer();
                }
                else{
                    $data = array(
                    'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                    );
                    $list = $customer_model->getAllCustomer($data);
                }
                
                $expect_date = "";

                foreach ($list as $rs) {
                    // put in bold the written text
                    $customer_name = $rs->customer_name;
                    if ($_POST['keyword'] != "*") {
                        $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                    }

                    if ($rs->customer_expect_date != null) {
                        $expect_date = date('d-m-Y',strtotime($rs->customer_expect_date.'-'.date('m-Y',strtotime(date('d-m-Y')))));
                    }
                    else if ($rs->customer_after_date != null) {
                        $expect_date = date('d-m-Y',strtotime('+'.$rs->customer_after_date.' day', strtotime(date('d-m-Y'))));
                    }
                    
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$rs->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }
                    // add new option
                    echo '<li onclick="set_item(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.$rs->company_name.'\',\''.$rs->customer_phone.'\',\''.$rs->customer_address.'\',\''.$rs->customer_email.'\',\''.$expect_date.'\',\''.$rs->mst.'\',\''.$type.'\')">'.$customer_name.'</li>';
                }
            }
        }
    }
    public function getoldprice(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = trim($_POST['customer']);
            $brand = trim($_POST['brand']);
            $size = trim($_POST['size']);
            $pattern = trim($_POST['pattern']);

            $price = array(
                'price' => "",
                'max' => 0,
                );

            $ton = 0;

            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$brand.' AND tire_buy_size = '.$size.' AND tire_buy_pattern = '.$pattern));
            foreach ($tire_buys as $tire) {
                $ton += $tire->tire_buy_volume;
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_sales as $tire) {
                $ton -= $tire->volume;
            }

            $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tires as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
                foreach ($order_tire_lists as $list) {
                    $ton -= $list->tire_number;
                }
            }
            $price['max'] = $ton;

            

            $sales = $tire_sale_model->queryTire('SELECT * FROM tire_sale WHERE customer = '.$customer.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern.' ORDER BY tire_sale_date DESC LIMIT 1');
            if ($sales) {
                foreach ($sales as $sale) {
                    if ($sale->sell_price_vat > 0) {
                        $price['price'] = $sale->sell_price_vat;
                    }
                    else{
                        $price['price'] = $sale->sell_price;
                    }
                    
                }
            }
            else{
                $tire_brand_model = $this->model->get('tirebrandModel');
                $tire_size_model = $this->model->get('tiresizeModel');
                $tire_pattern_model = $this->model->get('tirepatternModel');

                $tire_brand = $tire_brand_model->getTire($brand);
                if ($tire_brand->tire_brand_name == "Aoteli" || $tire_brand->tire_brand_name == "Yatai" || $tire_brand->tire_brand_name == "Yatone" || $tire_brand->tire_brand_name == "Three-A") {
                    $tire_brand_name = "Shengtai";
                }
                else{
                    $tire_brand_name = $tire_brand->tire_brand_name;
                }

                $tire_size_number = $tire_size_model->getTire($size)->tire_size_number;
                $pattern_type = $tire_pattern_model->getTire($pattern)->tire_pattern_type;

                $tire_quotation_model = $this->model->get('tirequotationModel');
                $tire_quotation_brand_model = $this->model->get('tirequotationbrandModel');
                $tire_quotation_size_model = $this->model->get('tirequotationsizeModel');

                $brand = $tire_quotation_brand_model->getTireByWhere(array('tire_quotation_brand_name'=>$tire_brand_name));
                $brand = $brand?$brand->tire_quotation_brand_id:null;
                $size = $tire_quotation_size_model->getTireByWhere(array('tire_quotation_size_number'=>$tire_size_number));
                $size = $size?$size->tire_quotation_size_id:null;
              
                $data_q = array(
                    'where' => 'tire_quotation_brand ='.$brand.' AND tire_quotation_size ='.$size.' AND tire_quotation_pattern IN ('.$pattern_type.') AND start_date <= '.strtotime(date('d-m-Y')).' AND (end_date IS NULL OR end_date > '.strtotime(date('d-m-Y')).')',
                );
                $prices = $tire_quotation_model->getAllTire($data_q);
                foreach ($prices as $p) {
                    $price['price'] = $p->tire_quotation_price;
                }
                
            }


            echo json_encode($price);
        }
    }

    public function add(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $customer_model = $this->model->get('customerModel');
            $tire_desrired_model = $this->model->get('tiredesiredModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_brands = $tire_brand_model->getAllTire();
            $data_brand = array();
            foreach ($tire_brands as $tire) {
                $data_brand[$tire->tire_brand_id]['group'] = $tire->tire_brand_group;
            }

            if (isset($_POST['order_tire_waiting']) && $_POST['order_tire_waiting'] > 0) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');

                $tire_waiting_model->updateTire(array('order_tire_waiting_status'=>1),array('order_tire_waiting_id'=>$_POST['order_tire_waiting']));
                $tire_desrired_model->updateTire(array('tire_desired_status'=>1),array('order_tire_waiting'=>$_POST['order_tire_waiting']));
            }

            if (trim($_POST['customer']) == "") {
                if (trim($_POST['customer_name']) != "") {
                    $data_cus = array(
                        'customer_name' => trim($_POST['customer_name']),
                        'company_name' => trim($_POST['company']),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => trim($_POST['address']),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_create_user' => $_SESSION['userid_logined'],
                    );

                    $customer_model->createCustomer($data_cus);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
            }
            else{
                $id_customer = trim($_POST['customer']);
                $data_cus = array(
                        'customer_name' => trim($_POST['customer_name']),
                        'company_name' => trim($_POST['company']),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => trim($_POST['address']),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_create_user' => $_SESSION['userid_logined'],
                    );

                $customer_model->updateCustomer($data_cus,array('customer_id'=>$id_customer));
            }

            $order_tire = $_POST['order_tire'];

            $data = array(
                'customer_type' => $_POST['customer_type'],
                'order_tire_date' => strtotime(date('d-m-Y')),
                'sale' => $_SESSION['userid_logined'],
                'customer' => $id_customer,
                'payment' => $_POST['payment'],
                'debt' => $_POST['debt'],
                'debt_number_day' => $_POST['debt_number_day'],
                'deposit' => trim(str_replace(',','',$_POST['deposit'])),
                'deposit_date' => strtotime($_POST['deposit_date']),
                'debt_1' => trim(str_replace(',','',$_POST['debt_1'])),
                'debt_1_date' => trim($_POST['debt_1_date'])!=""?strtotime($_POST['debt_1_date']):null,
                'debt_2' => trim(str_replace(',','',$_POST['debt_2'])),
                'debt_2_date' => trim($_POST['debt_2_date'])!=""?strtotime($_POST['debt_2_date']):null,
                'debt_3' => trim(str_replace(',','',$_POST['debt_3'])),
                'debt_3_date' => trim($_POST['debt_3_date'])!=""?strtotime($_POST['debt_3_date']):null,
                'ck_ttn' => $_POST['ck_ttn'],
                'ck_kho' => $_POST['ck_kho'],
                'ck_sl' => $_POST['ck_sl'],
                'discount' => trim(str_replace(',','',$_POST['discount'])),
                'reduce' => trim(str_replace(',','',$_POST['reduce'])),
                'vat_percent' => $_POST['vat_percent'],
                'vat' => trim(str_replace(',','',$_POST['vat'])),
                'delivery_date' => strtotime($_POST['delivery_date']),
                'total' => trim(str_replace(',','',$_POST['total'])),
                'order_tire_number' => $_POST['order_tire_number'],
                'order_tire_status' => 0,
                'check_price_vat' => $_POST['check_price_vat'],
            );

            $order_tire_model->createTire($data);
            $id_order_tire = $order_tire_model->getLastTire()->order_tire_id;

            if ($_POST['check_order'] == 1) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');
                $data_waiting = array(
                    'order_tire_waiting_date' => strtotime(date('d-m-Y')),
                    'customer' => $id_customer,
                    'customer_type' => $_POST['customer_type'],
                    'sale' => $_SESSION['userid_logined'],
                    'order_tire_waiting_number' => 0,
                );

                $tire_waiting_model->createTire($data_waiting);
                $id_waiting = $tire_waiting_model->getLastTire()->order_tire_waiting_id;

                $total_waiting = 0;
            }

            foreach ($order_tire as $v) {
                $data_order = array(
                    'tire_brand' => $v['tire_brand'],
                    'tire_size' => $v['tire_size'],
                    'tire_pattern' => $v['tire_pattern'],
                    'tire_number' => $v['max_number'] >= $v['tire_number'] ? $v['tire_number'] : $v['max_number'],
                    'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                    'tire_price_vat' => $v['tire_price_vat'],
                    'order_tire' => $id_order_tire,
                );

                if ($order_tire_list_model->getTireByWhere(array('tire_brand'=>$data_order['tire_brand'],'tire_size'=>$data_order['tire_size'],'tire_pattern'=>$data_order['tire_pattern'],'order_tire'=>$data_order['order_tire']))) {
                    $id_order_tire_list = $order_tire_list_model->getTireByWhere(array('tire_brand'=>$data_order['tire_brand'],'tire_size'=>$data_order['tire_size'],'tire_pattern'=>$data_order['tire_pattern'],'order_tire'=>$data_order['order_tire']))->order_tire_list_id;

                    $order_tire_list_model->updateTire($data_order,array('order_tire_list_id'=>$id_order_tire_list));
                }
                else{
                    $order_tire_list_model->createTire($data_order);
                }

                if ($_POST['check_order'] == 1) {
                    if ($v['max_number'] < $v['tire_number']) {
                        $tire_pattern = $tire_pattern_model->getTire($v['tire_pattern']);
                        $data_desired = array(
                            'tire_brand' => $data_brand[$v['tire_brand']]['group'],
                            'tire_brand_code' => $v['tire_brand'],
                            'tire_size' => $v['tire_size'],
                            'tire_pattern' => $tire_pattern->tire_pattern_type,
                            'tire_pattern_code' => $v['tire_pattern'],
                            'tire_number' => $v['tire_number'] - $v['max_number'],
                            'sale' => $_SESSION['userid_logined'],
                            'tire_desired_date' => strtotime(date('d-m-Y')),
                            'tire_desired_priority' => 1,
                            'order_tire' => $id_order_tire,
                            'order_tire_waiting' => $id_waiting,
                            'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                        );
                        $tire_desrired_model->createTire($data_desired);

                        $total_waiting += ($v['tire_number'] - $v['max_number']);
                    }
                    
                }
            }

            if ($_POST['check_order'] == 1) {
                $tire_waiting_model->updateTire(array('order_tire_waiting_number'=>$total_waiting),array('order_tire_waiting_id'=>$id_waiting));
            }

            echo "Đặt hàng thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$order_tire_model->getLastTire()->order_tire_id."|order_tire|".implode("-",$data)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_cost_model = $this->model->get('ordertirecostModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        //$order_data = $order_tire_model->getTire($data);

                        $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$data);
                        $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$data);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$data);
                        $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$data);
                        $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$data);
                        $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$data);
                        $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$data);
                        $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$data);
                        $order_tire_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        //$order_data = $order_tire_model->getTire($_POST['data']);

                        $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$_POST['data']);
                        $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$_POST['data']);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$_POST['data']);
                        $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$_POST['data']);
                        $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$_POST['data']);
                        $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$_POST['data']);
                        $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$_POST['data']);
                        $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$_POST['data']);
                        $order_tire_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function add_desired(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_desrired_model = $this->model->get('tiredesiredModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_waiting_model = $this->model->get('ordertirewaitingModel');
            $customer_model = $this->model->get('customerModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_brands = $tire_brand_model->getAllTire();
            $data_brand = array();
            foreach ($tire_brands as $tire) {
                $data_brand[$tire->tire_brand_id]['group'] = $tire->tire_brand_group;
            }

            if (isset($_POST['order_tire_waiting']) && $_POST['order_tire_waiting'] > 0) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');

                $tire_waiting_model->updateTire(array('order_tire_waiting_status'=>1),array('order_tire_waiting_id'=>$_POST['order_tire_waiting']));
                $tire_desrired_model->updateTire(array('tire_desired_status'=>1),array('order_tire_waiting'=>$_POST['order_tire_waiting']));
            }

            if (trim($_POST['customer']) == "") {
                if (trim($_POST['customer_name']) != "") {
                    $data_cus = array(
                        'customer_name' => trim($_POST['customer_name']),
                        'company_name' => trim($_POST['company']),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => trim($_POST['address']),
                        'customer_phone' => trim($_POST['phone']),
                    );

                    $customer_model->createCustomer($data_cus);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
            }
            else{
                $id_customer = trim($_POST['customer']);
                $data_cus = array(
                        'customer_name' => trim($_POST['customer_name']),
                        'company_name' => trim($_POST['company']),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => trim($_POST['address']),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_create_user' => $_SESSION['userid_logined'],
                    );

                $customer_model->updateCustomer($data_cus,array('customer_id'=>$id_customer));
            }

            $data_waiting = array(
                'order_tire_waiting_date' => strtotime(date('d-m-Y')),
                'customer' => $id_customer,
                'customer_type' => $_POST['customer_type'],
                'sale' => $_SESSION['userid_logined'],
                'order_tire_waiting_number' => $_POST['total_number'],
            );

            $tire_waiting_model->createTire($data_waiting);
            $id_waiting = $tire_waiting_model->getLastTire()->order_tire_waiting_id;

            $order_tire = $_POST['order_tire'];

            foreach ($order_tire as $v) {
                $tire_pattern = $tire_pattern_model->getTire($v['tire_pattern']);
                $data_desired = array(
                    'tire_brand' => $data_brand[$v['tire_brand']]['group'],
                    'tire_brand_code' => $v['tire_brand'],
                    'tire_size' => $v['tire_size'],
                    'tire_pattern' => $tire_pattern->tire_pattern_type,
                    'tire_pattern_code' => $v['tire_pattern'],
                    'tire_number' => $v['tire_number'],
                    'sale' => $_SESSION['userid_logined'],
                    'tire_desired_date' => strtotime(date('d-m-Y')),
                    'tire_desired_priority' => 1,
                    'order_tire_waiting' => $id_waiting,
                    'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                );
                $tire_desrired_model->createTire($data_desired);
            }

            echo "Đặt hàng thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_waiting."|tire_waiting|".implode("-",$data_waiting)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function unlock(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $order = trim($_POST['data']);
            $lock = trim($_POST['val']);
            $order_tire_model->updateTire(array('sale_lock'=>$lock),array('order_tire_id'=>$order));

            echo "Thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."lock"."|".$order."|ordertire|".$lock."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function listtire($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand = tire_brand_id AND tire_size = tire_size_id AND tire_pattern = tire_pattern_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
            $data = array(
                'where' => 'order_tire IN (SELECT order_tire_id FROM order_tire WHERE order_tire_id ='.$id.' AND sale = '.$_SESSION["userid_logined"].')',
            );
        }

        $order_tire_lists = $order_tire_list_model->getAllTire($data,$join);
        $this->view->data['order_tire_lists'] = $order_tire_lists;

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');
        $tire_quotation_brand_model = $this->model->get('tirequotationbrandModel');
        $tire_quotation_size_model = $this->model->get('tirequotationsizeModel');
        $price = array();
        foreach ($order_tire_lists as $order) {
            $tire_brand = $tire_brand_model->getTire($order->tire_brand);
            if ($tire_brand->tire_brand_name == "Aoteli" || $tire_brand->tire_brand_name == "Yatai" || $tire_brand->tire_brand_name == "Yatone" || $tire_brand->tire_brand_name == "Three-A") {
                $tire_brand_name = "Shengtai";
            }
            else{
                $tire_brand_name = $tire_brand->tire_brand_name;
            }

            $tire_size_number = $tire_size_model->getTire($order->tire_size)->tire_size_number;
            $pattern_type = $tire_pattern_model->getTire($order->tire_pattern)->tire_pattern_type;

            $brand = $tire_quotation_brand_model->getTireByWhere(array('tire_quotation_brand_name'=>$tire_brand_name));
            $brand = $brand?$brand->tire_quotation_brand_id:null;
            $size = $tire_quotation_size_model->getTireByWhere(array('tire_quotation_size_number'=>$tire_size_number));
            $size = $size?$size->tire_quotation_size_id:null;

            $data_q = array(
                'where' => 'tire_quotation_brand ='.$brand.' AND tire_quotation_size ='.$size.' AND tire_quotation_pattern IN ('.$pattern_type.') AND start_date <= '.strtotime(date('d-m-Y')).' AND (end_date IS NULL OR end_date > '.strtotime(date('d-m-Y')).')',
            );
            $prices = $tire_quotation_model->getAllTire($data_q);
            foreach ($prices as $p) {
                $price[$order->order_tire_list_id] = $p->tire_quotation_price;
            }
        }

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire = $order_tire_model->getTire($id);

        $this->view->data['price'] = $price;
        $this->view->data['order'] = $id;
        $this->view->data['order_tire'] = $order_tire;

        $this->view->show('ordertire/listtire');
    }
    public function listcost($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_cost_model = $this->model->get('ordertirecostModel');
        $join = array('table'=>'shipment_vendor','where'=>'vendor=shipment_vendor_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        $order_tire_costs = $order_tire_cost_model->getAllTire($data,$join);
        $this->view->data['order_tire_costs'] = $order_tire_costs;

        $this->view->show('ordertire/listcost');
    }
    public function listdiscount($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tires = $order_tire_model->getTire($id);
        $this->view->data['order_tires'] = $order_tires;

        $this->view->show('ordertire/listdiscount');
    }
    public function discountedit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['order_tire']);
            $discount = trim(str_replace(',','',$_POST['discount']));
            $reduce = trim(str_replace(',','',$_POST['reduce']));
            $ck_ttn = trim($_POST['ck_ttn']);
            $ck_kho = trim($_POST['ck_kho']);
            $ck_sl = trim($_POST['ck_sl']);

            $order = $order_tire_model->getTire($id);
            $total = $order->total+$order->discount+$order->reduce-$discount-$reduce;

            $data = array(
                'discount'=>$discount,
                'reduce'=>$reduce,
                'ck_ttn'=>$ck_ttn,
                'ck_kho'=>$ck_kho,
                'ck_sl'=>$ck_sl,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|discount|".$_POST['order_tire']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function editorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            

            $brand = trim($_POST['tire_brand']);
            $pattern = trim($_POST['tire_pattern']);
            $size = trim($_POST['tire_size']);
            $number = trim($_POST['tire_number']);
            $price = trim(str_replace(',','',$_POST['tire_price_vat']));
            $check_price_vat = trim($_POST['check_price_vat']);
            $price_vat = $check_price_vat==1?trim(str_replace(',','',$_POST['tire_price'])):null;

            $data = array(
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
                'tire_price'=>$price,
                'tire_price_vat'=>$price_vat,
            );

            if ($_POST['yes'] != "") {
                $order_tire_list = $order_tire_list_model->getTire($_POST['yes']);

                $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                $order_tire_list_model->updateTire($data,array('order_tire_list_id'=>$_POST['yes']));

                $total_number = $order_tire->order_tire_number;
                $total = $order_tire->total;
                $total_after = $order_tire_list->tire_number*$order_tire_list->tire_price;
                $vat = $order_tire->vat;
                $discount = $order_tire->discount;

                $total_number = $total_number - $order_tire_list->tire_number + $number;
                $total = $total - $total_after;

                if ($order_tire->ck_ttn==1) {
                    $discount = $discount - ($total_after*0.02) + (($number*$price)*0.02);
                    $total = $total + ($total_after*0.02) - (($number*$price)*0.02);
                }
                if ($order_tire->ck_kho==1) {
                    $discount = $discount - ($order_tire_list->tire_number*100000) + ($number*100000);
                    $total = $total + ($order_tire_list->tire_number*100000) - ($number*100000);
                }
                if ($order_tire->ck_sl==1) {
                    if ($order_tire->order_tire_number >= 20 && $order_tire->order_tire_number < 50) {
                        $discount = $discount - ($total_after*0.01) + (($number*$price)*0.01);
                        $total = $total + ($total_after*0.01) - (($number*$price)*0.01);
                    }
                    else if ($order_tire->order_tire_number >= 50 && $order_tire->order_tire_number < 100) {
                        $discount = $discount - ($total_after*0.02) + (($number*$price)*0.02);
                        $total = $total + ($total_after*0.02) - (($number*$price)*0.02);
                    }
                    else if ($order_tire->order_tire_number >= 100) {
                        $discount = $discount - ($total_after*0.03) + (($number*$price)*0.03);
                        $total = $total + ($total_after*0.03) - (($number*$price)*0.03);
                    }
                }

                if ($order_tire->vat_percent > 0) {
                    $vat = $vat - ($total_after*$order_tire->vat_percent/100) + (($number*$price)*$order_tire->vat_percent/100);
                    $total = $total - ($total_after*$order_tire->vat_percent/100) + (($number*$price)*$order_tire->vat_percent/100);
                }

                $total = $total + ($number*$price);

                $data_order = array(
                    'discount'=>$discount,
                    'total'=>$total,
                    'order_tire_number'=>$total_number,
                    'vat'=> $vat,
                );


                $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                if($order_tire->order_tire_status==1){
                    $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                    $order_tire_list_old = $order_tire_list_model->getTire($_POST['yes']);

                    $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                    $tire_sale = $tire_sale_model->getTireByWhere(array('tire_brand'=>$order_tire_list->tire_brand,'tire_size'=>$order_tire_list->tire_size,'tire_pattern'=>$order_tire_list->tire_pattern,'order_tire'=>$order_tire_list->order_tire));
                    $data_sale = array(
                        'tire_brand'=>$order_tire_list_old->tire_brand,
                        'tire_size'=>$order_tire_list_old->tire_size,
                        'tire_pattern'=>$order_tire_list_old->tire_pattern,
                        'volume' => $order_tire_list_old->tire_number,
                        'sell_price' => $order_tire_list_old->tire_price,
                        'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                    );
                    $tire_sale_model->updateTire($data_sale,array('tire_sale_id'=>$tire_sale->tire_sale_id));

                    $obtain_data = array(
                        'obtain_date' => $order_tire_old->delivery_date,
                        'customer' => $order_tire_old->customer,
                        'money' => $order_tire_old->total,
                        'week' => (int)date('W',$order_tire_old->delivery_date),
                        'year' => (int)date('Y',$order_tire_old->delivery_date),
                        'order_tire' => $order_tire_list->order_tire,
                    );
                    if($obtain_data['week'] == 53){
                        $obtain_data['week'] = 1;
                        $obtain_data['year'] = $obtain_data['year']+1;
                    }
                    if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                        $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                    }
                    $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                    $receivable_data = array(
                        'customer' => $order_tire_old->customer,
                        'money' => $order_tire_old->total,
                        'receivable_date' => $order_tire_old->delivery_date,
                        'expect_date' => $order_tire_old->delivery_date,
                        'week' => (int)date('W',$order_tire_old->delivery_date),
                        'year' => (int)date('Y',$order_tire_old->delivery_date),
                        'code' => $order_tire_old->order_number,
                        'source' => 1,
                        'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'order_tire' => $order_tire_list->order_tire,
                        'check_vat' => $order_tire_old->vat>0?1:0,
                    );

                    
                    if($receivable_data['week'] == 53){
                        $receivable_data['week'] = 1;
                        $receivable_data['year'] = $receivable_data['year']+1;
                    }
                    if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                        $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                    }

                    $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                }

                echo "Cập nhật thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
            }
            else{
                $data['order_tire'] = $_POST['order'];

                $order_tire_list_model->createTire($data);

                $order_tire_list = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                $total_number = $order_tire->order_tire_number;
                $total = $order_tire->total;
                $total_after = $order_tire_list->tire_number*$order_tire_list->tire_price;
                $vat = $order_tire->vat;
                $discount = $order_tire->discount;

                $total_number = $total_number + $number;

                if ($order_tire->ck_ttn==1) {
                    $discount = $discount + (($number*$price)*0.02);
                    $total = $total - (($number*$price)*0.02);
                }
                if ($order_tire->ck_kho==1) {
                    $discount = $discount + ($number*100000);
                    $total = $total - ($number*100000);
                }
                if ($order_tire->ck_sl==1) {
                    if ($order_tire_list->tire_number >= 20 && $order_tire_list->tire_number < 50) {
                        $discount = $discount + (($number*$price)*0.01);
                        $total = $total - (($number*$price)*0.01);
                    }
                    else if ($order_tire_list->tire_number >= 50 && $order_tire_list->tire_number < 100) {
                        $discount = $discount + (($number*$price)*0.02);
                        $total = $total - (($number*$price)*0.02);
                    }
                    else if ($order_tire_list->tire_number >= 100) {
                        $discount = $discount + (($number*$price)*0.03);
                        $total = $total - (($number*$price)*0.03);
                    }
                }

                if ($order_tire->vat_percent > 0) {
                    $vat = $vat + (($number*$price)*$order_tire->vat_percent/100);
                    $total = $total + (($number*$price)*$order_tire->vat_percent/100);
                }

                $total = $total + ($number*$price);

                $data_order = array(
                    'discount'=>$discount,
                    'total'=>$total,
                    'order_tire_number'=>$total_number,
                    'vat'=> $vat,
                );


                $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                if($order_tire->order_tire_status==1){

                    $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                    $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                    $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                    $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire_old->sale));

                    $check_vat = $order_tire_old->vat>0?1:0;
                    //$vat = $order->tire_price*$order_tire->vat_percent/100;
                    $data_sale = array(
                            
                        'code' => $order_tire_old->order_number,
                        'volume' => $order_tire_list_old->tire_number,
                        'tire_brand' => $order_tire_list_old->tire_brand,
                        'tire_size' => $order_tire_list_old->tire_size,
                        'sell_price' => $order_tire_list_old->tire_price,
                        'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                        'customer' => $order_tire_old->customer,
                        'tire_sale_date' => $order_tire_old->delivery_date,
                        //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                        'tire_pattern' => $order_tire_list_old->tire_pattern,
                        'check_vat' => $check_vat,
                        'sale' => $staff->staff_id,
                        'customer_type' => $order_tire_old->customer_type,
                        'order_tire' => $order_tire_list->order_tire,
                    );
                    $tire_sale_model->createTire($data_sale);

                    $obtain_data = array(
                        'obtain_date' => $order_tire_old->delivery_date,
                        'customer' => $order_tire_old->customer,
                        'money' => $order_tire_old->total,
                        'week' => (int)date('W',$order_tire_old->delivery_date),
                        'year' => (int)date('Y',$order_tire_old->delivery_date),
                        'order_tire' => $order_tire_list->order_tire,
                    );
                    if($obtain_data['week'] == 53){
                        $obtain_data['week'] = 1;
                        $obtain_data['year'] = $obtain_data['year']+1;
                    }
                    if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                        $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                    }
                    $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                    $receivable_data = array(
                        'customer' => $order_tire_old->customer,
                        'money' => $order_tire_old->total,
                        'receivable_date' => $order_tire_old->delivery_date,
                        'expect_date' => $order_tire_old->delivery_date,
                        'week' => (int)date('W',$order_tire_old->delivery_date),
                        'year' => (int)date('Y',$order_tire_old->delivery_date),
                        'code' => $order_tire_old->order_number,
                        'source' => 1,
                        'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'order_tire' => $order_tire_list->order_tire,
                        'check_vat' => $order_tire_old->vat>0?1:0,
                    );

                    
                    if($receivable_data['week'] == 53){
                        $receivable_data['week'] = 1;
                        $receivable_data['year'] = $receivable_data['year']+1;
                    }
                    if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                        $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                    }

                    $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                }

                echo "Thêm thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
            }
            

        }
    }

    public function editvat(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);
            $vat_percent = trim(str_replace(',','',$_POST['vat_percent']));
            $vat = trim(str_replace(',','',$_POST['vat']));
            $thu = trim(str_replace(',','',$_POST['thu']));

            $order = $order_tire_model->getTire($id);
            $total = $order->total-$order->vat+$vat;

            $data = array(
                'vat'=>$vat,
                'vat_percent'=>$vat_percent,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|vat|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }

    public function addordernumber(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);
            $order_number = strtolower(trim($_POST['order_number']));

            $data = array(
                'order_number'=>$order_number,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $payable_model = $this->model->get('payableModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            
            $data_sale = array(
                'code' => $order_number,
            );
            $tire_sale_model->updateTire($data_sale,array('order_tire'=>$id));
            
            $receivable_data = array(
                'code' => $order_number,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $payable_data = array(
                'code' => $order_number,
            );

            $payable_model->updateCosts($payable_data,array('order_tire'=>$id));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|code|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function approve(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);

            $data = array(
                'approve'=>$_SESSION['userid_logined'],
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|approve|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function exstock(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');

            $id = trim($_POST['data']);

            $data = array(
                'order_tire_status'=>1,
                'delivery_date'=>strtotime($_POST['delivery_date']),
                'sale_lock'=>1,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $week = (int)date('W',$data['delivery_date']);
            $year = (int)date('Y',$data['delivery_date']);

            if($week == 53){
                $week = 1;
                $year = $year+1;
            }
            if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                $year = (int)date('Y',$data['delivery_date'])+1;
            }

            $owe_model->updateOwe(array('owe_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $payable_model->updateCosts(array('payable_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));

            $order_tire = $order_tire_model->getTire($id);
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$id));
            $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire->sale));

            foreach ($order_tire_lists as $order) {
                $check_vat = $order_tire->vat>0?1:0;
                //$vat = $order->tire_price*$order_tire->vat_percent/100;
                $data_sale = array(
                        
                    'code' => $order_tire->order_number,
                    'volume' => $order->tire_number,
                    'tire_brand' => $order->tire_brand,
                    'tire_size' => $order->tire_size,
                    'sell_price' => $order->tire_price,
                    'sell_price_vat' => $order->tire_price_vat,
                    'customer' => $order_tire->customer,
                    'tire_sale_date' => $order_tire->delivery_date,
                    //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                    'tire_pattern' => $order->tire_pattern,
                    'check_vat' => $check_vat,
                    'sale' => $staff->staff_id,
                    'customer_type' => $order_tire->customer_type,
                    'order_tire' => $id,
                );
                $tire_sale_model->createTire($data_sale);
            }

            $obtain_data = array(
                'obtain_date' => $order_tire->delivery_date,
                'customer' => $order_tire->customer,
                'money' => $order_tire->total,
                'week' => (int)date('W',$order_tire->delivery_date),
                'year' => (int)date('Y',$order_tire->delivery_date),
                'order_tire' => $id,
            );
            if($obtain_data['week'] == 53){
                $obtain_data['week'] = 1;
                $obtain_data['year'] = $obtain_data['year']+1;
            }
            if (((int)date('W',$order_tire->delivery_date) == 1) && ((int)date('m',$order_tire->delivery_date) == 12) ) {
                $obtain_data['year'] = (int)date('Y',$order_tire->delivery_date)+1;
            }
            $obtain_model->createObtain($obtain_data);

            $receivable_data = array(
                'customer' => $order_tire->customer,
                'money' => $order_tire->total,
                'receivable_date' => $order_tire->delivery_date,
                'expect_date' => $order_tire->delivery_date,
                'week' => (int)date('W',$order_tire->delivery_date),
                'year' => (int)date('Y',$order_tire->delivery_date),
                'code' => $order_tire->order_number,
                'source' => 1,
                'comment' => $order_tire->order_tire_number.' lốp '.$order_tire->order_number,
                'create_user' => $_SESSION['userid_logined'],
                'type' => 4,
                'order_tire' => $id,
                'check_vat' => $order_tire->vat>0?1:0,
            );

            
            if($receivable_data['week'] == 53){
                $receivable_data['week'] = 1;
                $receivable_data['year'] = $receivable_data['year']+1;
            }
            if (((int)date('W',$order_tire->delivery_date) == 1) && ((int)date('m',$order_tire->delivery_date) == 12) ) {
                $receivable_data['year'] = (int)date('Y',$order_tire->delivery_date)+1;
            }

            $receivable_model->createCosts($receivable_data);


            $lift_model = $this->model->get('liftModel');

            $contributor = "";
            if(trim($_POST['lift']) != ""){
                $support = explode(',', trim($_POST['lift']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                        if ($contributor == "")
                            $contributor .= $name;
                        else
                            $contributor .= ','.$name;
                    }
                }

                $data_lift = array(
                    'lift_date' => $order_tire->delivery_date,
                    'staff' => $contributor,
                    'order_tire' => $id,
                );
                $lift_model->createLift($data_lift);
            }

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|exstock|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function exstockedit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $lift_model = $this->model->get('liftModel');

            $id = trim($_POST['data']);

            $data = array(
                'order_tire_status'=>1,
                'delivery_date'=>strtotime($_POST['delivery_date']),
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $week = (int)date('W',$data['delivery_date']);
            $year = (int)date('Y',$data['delivery_date']);

            if($week == 53){
                $week = 1;
                $year = $year+1;
            }
            if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                $year = (int)date('Y',$data['delivery_date'])+1;
            }

            $owe_model->updateOwe(array('owe_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $payable_model->updateCosts(array('payable_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $tire_sale_model->updateTire(array('tire_sale_date' => $data['delivery_date']),array('order_tire'=>$id));
            $obtain_model->updateObtain(array('obtain_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $receivable_model->updateCosts(array('receivable_date'=>$data['delivery_date'],'expect_date' => $data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));



            $contributor = "";
            if(trim($_POST['lift']) != ""){
                $support = explode(',', trim($_POST['lift']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                        if ($contributor == "")
                            $contributor .= $name;
                        else
                            $contributor .= ','.$name;
                    }
                }

                $data_lift = array(
                    'lift_date' => $data['delivery_date'],
                    'staff' => $contributor,
                    'order_tire' => $id,
                );

                if ($lift_model->getLiftByWhere(array('order_tire'=>$id))) {
                    $lift_model->updateLift($data_lift,array('order_tire'=>$id));
                }
                else{
                    $lift_model->createLift($data_lift);
                }
                
            }
            else{
                $lift_model->queryLift('DELETE FROM lift WHERE order_tire = '.$id);
            }
            

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|exstock|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }

    public function deleteorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            if(isset($_POST['data'])){
                        

                        $order_tire_list = $order_tire_list_model->getTire($_POST['data']);

                        $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                        $total_number = $order_tire->order_tire_number;
                        $total = $order_tire->total;
                        $total_after = $order_tire_list->tire_number*$order_tire_list->tire_price;
                        $vat = $order_tire->vat;
                        $discount = $order_tire->discount;

                        $total_number = $total_number - $order_tire_list->tire_number;
                        $total = $total - $total_after;

                        if ($order_tire->ck_ttn==1) {
                            $discount = $discount - ($total_after*0.02);
                            $total = $total + ($total_after*0.02);
                        }
                        if ($order_tire->ck_kho==1) {
                            $discount = $discount - ($order_tire_list->tire_number*100000);
                            $total = $total + ($order_tire_list->tire_number*100000);
                        }
                        if ($order_tire->ck_sl==1) {
                            if ($order_tire->order_tire_number >= 20 && $order_tire->order_tire_number < 50) {
                                $discount = $discount - ($total_after*0.01);
                                $total = $total + ($total_after*0.01);
                            }
                            else if ($order_tire->order_tire_number >= 50 && $order_tire->order_tire_number < 100) {
                                $discount = $discount - ($total_after*0.02);
                                $total = $total + ($total_after*0.02);
                            }
                            else if ($order_tire->order_tire_number >= 100) {
                                $discount = $discount - ($total_after*0.03);
                                $total = $total + ($total_after*0.03);
                            }
                        }

                        if ($order_tire->vat_percent > 0) {
                            $vat = $vat - ($total_after*$order_tire->vat_percent/100);
                            $total = $total - ($total_after*$order_tire->vat_percent/100);
                        }

                        

                        $data_order = array(
                            'discount'=>$discount,
                            'total'=>$total,
                            'order_tire_number'=>$total_number,
                            'vat'=> $vat,
                        );


                        $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                        if($order_tire->order_tire_status==1){

                            $order_tire_list_old = $order_tire_list_model->getTire($_POST['data']);

                            $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                            $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE tire_brand = '.$order_tire_list_old->tire_brand.' AND tire_size = '.$order_tire_list_old->tire_size.' AND tire_pattern = '.$order_tire_list_old->tire_pattern.' AND order_tire = '.$order_tire_list->order_tire);

                            $obtain_data = array(
                                'obtain_date' => $order_tire_old->delivery_date,
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'order_tire' => $order_tire_list->order_tire,
                            );
                            if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }
                            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                            $receivable_data = array(
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'receivable_date' => $order_tire_old->delivery_date,
                                'expect_date' => $order_tire_old->delivery_date,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'code' => $order_tire_old->order_number,
                                'source' => 1,
                                'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'order_tire' => $order_tire_list->order_tire,
                                'check_vat' => $order_tire_old->vat>0?1:0,
                            );

                            
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }

                            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                        }

                        $order_tire_list_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|order_tire_list|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function addordercost(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');

            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');

            $data_order = $order_tire_model->getTire($_POST['data']);

            $vendor_cost = $_POST['vendor_cost'];

            $cost = null;
            foreach ($vendor_cost as $v) {
                $cost_data = array(
                    'order_tire' => $_POST['data'],
                    'order_tire_cost' => trim(str_replace(',','',$v['order_tire_cost'])),
                    'order_tire_cost_date' => strtotime(date('d-m-Y',strtotime($v['order_tire_cost_date']))),
                    'vendor' => $v['vendor'],
                    'comment' => trim($v['comment']),
                    'order_tire_cost_type' => $v['order_tire_cost_type'],
                );
                $cost += $cost_data['order_tire_cost'];

                $owe_data = array(
                        'owe_date' => $data_order->delivery_date,
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'order_tire' => $_POST['data'],
                    );
                    if($owe_data['week'] == 53){
                        $owe_data['week'] = 1;
                        $owe_data['year'] = $owe_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $owe_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }

                $payable_data = array(
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'payable_date' => $data_order->delivery_date,
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $cost_data['order_tire_cost_date'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'code' => $data_order->order_number,
                        'source' => 1,
                        'comment' => $data_order->order_number.'-'.$cost_data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'order_tire' => $_POST['data'],
                        'cost_type' => $cost_data['order_tire_cost_type'],
                        'approve' => null,
                        'check_cost'=>4,
                    );
                    if($payable_data['week'] == 53){
                        $payable_data['week'] = 1;
                        $payable_data['year'] = $payable_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $payable_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }


                if ($order_cost_model->getTireByWhere(array('order_tire'=>$cost_data['order_tire'],'vendor'=>$cost_data['vendor'],'order_tire_cost_type'=>$cost_data['order_tire_cost_type']))) {
                    $data_order_cost = $order_cost_model->getTireByWhere(array('order_tire'=>$cost_data['order_tire'],'vendor'=>$cost_data['vendor'],'order_tire_cost_type'=>$cost_data['order_tire_cost_type']));
                    $order_cost_model->updateTire($cost_data,array('order_tire_cost_id'=>$data_order_cost->order_tire_cost_id));

                    $owe_model->updateOwe($owe_data,array('order_tire'=>$_POST['data'],'vendor'=>$cost_data['vendor'],'money'=>$data_order_cost->order_tire_cost));
         
                    if($payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']))){
                        $check = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable_model->updateCosts($payable_data,array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']));
                        
                    }

                }
                else{
                    $order_cost_model->createTire($cost_data);

                    $owe_model->createOwe($owe_data);
                    $payable_model->createCosts($payable_data);
                }
            }

            $order_tire_model->updateTire(array('order_cost'=>$cost),array('order_tire_id'=>$_POST['data']));
        }
    }

    public function deleteordercost(){
        if(isset($_POST['data'])){
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');
            $assets = $this->model->get('assetsModel');
            $pay = $this->model->get('payModel');

            $order_tire_cost = $order_cost_model->getTireByWhere(array('order_tire'=>$_POST['data'],'vendor'=>$_POST['vendor'],'order_tire_cost_type'=>$_POST['type']));
            $order_tire = $order_tire_model->getTire($order_tire_cost->order_tire);

            $order_tire_model->updateTire(array('order_cost'=>$order_tire->order_cost-$order_tire_cost->order_tire_cost),array('order_tire_id'=>$order_tire->order_tire_id));

            $p = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$order_tire_cost->order_tire_cost,'vendor'=>$order_tire_cost->vendor,'order_tire'=>$order_tire_cost->order_tire,'cost_type'=>$order_tire_cost->order_tire_cost_type));
            $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$order_tire_cost->order_tire.' AND vendor = '.$order_tire_cost->vendor.' AND money = '.$order_tire_cost->order_tire_cost);
            
            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            $payable_model->queryCosts('DELETE FROM payable WHERE payable_id='.$p->payable_id);

            $order_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire_cost_id = '.$order_tire_cost->order_tire_cost_id);

            echo "Thêm thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$order_tire_cost->order_tire_cost_id."|order_tire_cost_|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            

            foreach ($list as $rs) {
                // put in bold the written text
                $shipment_vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $shipment_vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                // add new option
                echo '<li onclick="set_item_other(\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_id.'\',\''.$_POST['offset'].'\')">'.$shipment_vendor_name.'</li>';
            }
        }
    }
    public function getordercost(){
        if(isset($_POST['order_tire'])){
            $order_cost_model = $this->model->get('ordertirecostModel');
            $vendors = $order_cost_model->getAllTire(array('where'=>'order_tire='.$_POST['order_tire']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }



                $str .= '<tr class="'.$_POST['order_tire'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="order_tire_cost_type" name="order_tire_cost_type[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><input required="required" type="text" class="vendor" name="vendor[]" autocomplete="off" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a>';
                    $str .= '<ul class="customer_list_id"></ul></td>';
                    //$str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Ngày chi</td>';
                    $str .= '<td><input tabindex="5" class="order_tire_cost_date" type="date"   name="order_tire_cost_date[]" required="required" value="'.date('Y-m-d') .'"></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'].'"><td></td><td>Số tiền</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers order_tire_cost"  name="order_tire_cost[]" value="0"  ></td>';
                                                        
                    $str .= '<td>Nội dung</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="comment" name="comment[]"  ></textarea></td></tr></table></td></tr>';                                         
                    
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    

                     $truck = ($v->order_tire_cost_type==1)?'selected="selected"':null;
                     $bar = ($v->order_tire_cost_type==2)?'selected="selected"':null;
                     $fee = ($v->order_tire_cost_type==3)?'selected="selected"':null;
                     $thu = ($v->order_tire_cost_type==4)?'selected="selected"':null;
                     $hh = ($v->order_tire_cost_type==5)?'selected="selected"':null;
                     $tt = ($v->order_tire_cost_type==6)?'selected="selected"':null;
                     $khac = ($v->order_tire_cost_type==7)?'selected="selected"':null;


                    $str .= '<tr class="'.$v->order_tire.'">';
                    $str .= '<td><input type="checkbox" name="chk" tabindex="'.$v->order_tire_cost_type.'" data="'.$v->order_tire .'" class="'.$v->vendor.'" title="'.($v->order_tire_cost).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->order_tire .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="order_tire_cost_type" name="order_tire_cost_type[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->order_tire .'">';
                    $str .= '<td></td><td> Vendor</td><td><input required="required" type="text" disabled value="'.$vendor_model->getvendor($v->vendor)->shipment_vendor_name.'" data="'.$v->vendor.'" class="vendor" name="vendor[]" required="required" autocomplete="off" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a>';
                    $str .= '<ul class="customer_list_id"></ul></td>';
                    //$str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Ngày chi</td>';
                    $str .= '<td><input tabindex="5" class="order_tire_cost_date" type="date"   name="order_tire_cost_date[]" required="required" value="'.date('Y-m-d',$v->order_tire_cost_date) .'"></td></tr>';
                    
                    $str .= '<tr class="'.$v->order_tire.'"><td></td><td>Số tiền </td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers order_tire_cost"  name="order_tire_cost[]" value="'.$this->lib->formatMoney($v->order_tire_cost) .'"  ></td>';
                                                       
                    $str .= '<td>Nội dung</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="comment" name="comment[]"  >'.$v->comment .'</textarea></td></tr></table></td></tr>';                                         
                    
                }
            }

            echo $str;
        }
    }

    public function contract() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getCustomer($this->registry->router->param_id);


        $info = $this->registry->router->addition;
        
        $arr = explode('@', $info);

        $this->view->data['company'] = strtoupper($customers->company_name);
        $this->view->data['mst'] = $customers->mst;
        $this->view->data['address'] = $customers->customer_address;
        $this->view->data['phone'] = $customers->customer_phone;
        $this->view->data['fax'] = $customers->customer_fax;
        $this->view->data['bank_number'] = $customers->account_number;
        $this->view->data['bank'] = $customers->customer_bank_name;
        $this->view->data['name'] = $customers->director;

        $this->view->data['contract_date'] = explode('-', $arr[0]);
        $this->view->data['contract_number'] = $arr[1];
        $this->view->data['contract_pay'] = $arr[2];
        $this->view->data['contract_pay2'] = $arr[3];
        $this->view->data['contract_valid'] = str_replace('-', '/', $arr[4]);
                
        $this->view->show('ordertire/contract');
    }

   

    function bangke(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'Đơn vị bán hàng: CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A2', 'Địa chỉ: Số 545, Tổ 10, Ấp Hương Phước, Phước Tân, Biên Hòa, Đồng Nai')
                ->setCellValue('A3', 'MST: 3603295302')
                ->setCellValue('A4', 'Điện thoại: 0613 937 677')
                ->setCellValue('A6', 'BẢNG KÊ')
                ->setCellValue('G7', 'TP Biên Hòa, Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
               ->setCellValue('A9', 'Kính gửi: '.$customers->company_name)
               ->setCellValue('A10', 'Địa chỉ: '.$customers->customer_address)
               ->setCellValue('A11', 'MST: '.$customers->mst)
               ->setCellValue('A12', 'Đề nghị thanh toán: Tiền lốp xe')
               ->setCellValue('A13', 'STT')
               ->setCellValue('B13', 'TÊN HÀNG')
               ->setCellValue('C13', 'LOẠI HÀNG')
               ->setCellValue('D13', 'ĐƠN VỊ')
               ->setCellValue('E13', 'SỐ LƯỢNG')
               ->setCellValue('F13', 'ĐƠN GIÁ')
               ->setCellValue('G13', 'THÀNH TIỀN')
               ->setCellValue('H13', 'GHI CHÚ');
               

            

            
            
            

            if ($order_types) {

                $hang = 14;
                $i=1;

                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe')
                        ->setCellValue('C' . $hang, $row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('D' . $hang, 'Cái')
                        ->setCellValue('E' . $hang, $row->tire_number)
                        ->setCellValue('F' . $hang, $row->tire_price)
                        ->setCellValue('G' . $hang, '=E'.$hang.'*F'.$hang);
                     $hang++;


                  }

                  $f = $hang;

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng cộng')
                       ->setCellValue('G'.$hang, '=SUM(G7:G'.($hang-1).')');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $hang++;

                if ($orders->vat>0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'VAT')
                        ->setCellValue('F'.$hang, $orders->vat_percent.'%')
                       ->setCellValue('G'.$hang, '=G'.($hang-1).'*F'.$hang);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                       $hang++;
                  }

                  if ($orders->discount>0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Chiết khấu')
                       ->setCellValue('G'.$hang, $orders->discount+$orders->reduce);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                       $hang++;

                       $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=G'.$f.'+G'.($f+1).'-G'.($f+2));

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  }
                  
                  if ($orders->discount=="" || $orders->discount==0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=G'.$f.'+G'.($f+1));

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  }

                    $objPHPExcel->getActiveSheet()->getStyle('A6:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+3), 'XÁC NHẬN KHÁCH HÀNG')
                        ->setCellValue('G'.($hang+3), 'NGƯỜI LẬP');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+3).':D'.($hang+3));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+3).':H'.($hang+3));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':H'.($hang+3))->applyFromArray(
                        array(
                            
                            'font' => array(
                                'bold'  => true,
                                'color' => array('rgb' => '000000')
                            )
                        )
                    );

          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $objPHPExcel->getActiveSheet()->getStyle('A13:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('A4:H4');
            $objPHPExcel->getActiveSheet()->mergeCells('A6:H6');
            $objPHPExcel->getActiveSheet()->mergeCells('G7:H7');

            $objPHPExcel->getActiveSheet()->mergeCells('A9:H9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:H11');
            $objPHPExcel->getActiveSheet()->mergeCells('A12:H12');

            $objPHPExcel->getActiveSheet()->mergeCells('H13:I13');

            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A1:H13')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            

            $objPHPExcel->getActiveSheet()->getStyle('F14:G'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("List")
                            ->setSubject("List")
                            ->setDescription("List.")
                            ->setKeywords("List")
                            ->setCategory("List");
            $objPHPExcel->getActiveSheet()->setTitle("Bang ke");

            $objPHPExcel->getActiveSheet()->freezePane('A14');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG KÊ.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

   

    function invoice(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'HÓA ĐƠN GTGT')
                ->setCellValue('A2', 'Liên 2: Giao cho người mua')
                ->setCellValue('G3', 'Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
                ->setCellValue('A5', 'Đơn vị bán hàng: ')
                ->setCellValue('B5', 'CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A6', 'MST: ')
                ->setCellValue('B6', "'3603295302")
                ->setCellValue('A7', 'Địa chỉ: ')
                ->setCellValue('B7', 'Số 545, Tổ 10, Ấp Hương Phước, xã Phước Tân, TP.Biên Hòa, Đồng Nai')
                ->setCellValue('A8', 'Điện thoại: ')
                ->setCellValue('B8', '0613 937 677')
                ->setCellValue('C8', 'STK: ')
                ->setCellValue('D8', '200970509 ')
                ->setCellValue('E8', 'ACB Biên Hòa')
                ->setCellValue('A9', 'Họ tên người mua hàng: ')
                ->setCellValue('C9', $customers->customer_name)
                ->setCellValue('A10', 'Tên đơn vị: ')
               ->setCellValue('B10', $customers->company_name)
               ->setCellValue('A11', 'Mã số thuế: ')
               ->setCellValue('B11', "'".$customers->mst)
               ->setCellValue('A12', 'Địa chỉ: ')
               ->setCellValue('B12', $customers->customer_address)
               ->setCellValue('A13', 'STK: ')
               ->setCellValue('A14', 'STT')
               ->setCellValue('B14', 'Tên Hàng Hóa Dịch Vụ')
               ->setCellValue('F14', 'Đơn Vị Tính')
               ->setCellValue('G14', 'Số Lượng')
               ->setCellValue('H14', 'Đơn Giá')
               ->setCellValue('I14', 'Thành Tiền')
               ->setCellValue('A16', '1')
               ->setCellValue('B16', '2')
               ->setCellValue('F16', '3')
               ->setCellValue('G16', '4')
               ->setCellValue('H16', '5')
               ->setCellValue('I16', '6 = 4x5');
               
            
            

            if ($order_types) {

                $hang = 17;
                $i=1;
                $tong = 0;
                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe '.$row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('F' . $hang, 'Cái')
                        ->setCellValue('G' . $hang, $row->tire_number)
                        ->setCellValue('H' . $hang, $row->tire_price)
                        ->setCellValue('I' . $hang, '=G'.$hang.'*H'.$hang);
                     $hang++;

                     $tong += $row->tire_number*$row->tire_price;

                     $objPHPExcel->getActiveSheet()->getStyle('B'.$hang.':I'.$hang)->applyFromArray(
                            array(
                                'font' => array(
                                    'color' => array('rgb' => '5bc0de')
                                ),
                            )
                        );

                  }

                  $tong = round($tong+($tong*0.1));

                  for ($j=0; $j < 5; $j++) { 
                      $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' .$hang, null)
                        ->setCellValue('F' . $hang, null)
                        ->setCellValue('G' . $hang, null)
                        ->setCellValue('H' . $hang, null)
                        ->setCellValue('I' . $hang, null);
                     $hang++;
                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('D'.($hang+1), 'Cộng tiền hàng:')
                       ->setCellValue('I'.($hang+1), '=SUM(I17:I'.($hang-2).')')
                       ->setCellValue('D'.($hang+2), 'Tiền thuế GTGT:')
                       ->setCellValue('I'.($hang+2), '=I'.($hang+1).'*10%')
                       ->setCellValue('D'.($hang+3), 'Tổng cộng tiền thanh toán:')
                       ->setCellValue('I'.($hang+3), '=I'.($hang+2).'+I'.($hang+1))
                       ->setCellValue('A'.($hang+4), 'Viết bằng chữ:')
                       ->setCellValue('B'.($hang+4), $this->lib->convert_number_to_words($tong));

                    $objRichText = new PHPExcel_RichText();
                    $textBold = $objRichText->createTextRun("Thuế suất GTGT: ");

                    $under = $objRichText->createTextRun('  10%');
                    $under->getFont()->setBold(true);
                    $under->getFont()->setItalic(true);

                    $objPHPExcel->getActiveSheet()->getCell('A'.($hang+2))->setValue($objRichText);


                    $objPHPExcel->getActiveSheet()->getStyle('A14:I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  

                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+7), '(Kí ghi rõ họ tên)')
                        ->setCellValue('G'.($hang+7), '(Kí ghi rõ họ tên, đóng dấu)');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+7).':B'.($hang+7));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+7).':H'.($hang+7));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:I2');
            $objPHPExcel->getActiveSheet()->mergeCells('G3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('B4:I4');
            $objPHPExcel->getActiveSheet()->mergeCells('B5:I5');
            $objPHPExcel->getActiveSheet()->mergeCells('B6:I6');

            $objPHPExcel->getActiveSheet()->mergeCells('B7:I7');
            $objPHPExcel->getActiveSheet()->mergeCells('E8:I8');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:B9');
            $objPHPExcel->getActiveSheet()->mergeCells('C9:I9');
            $objPHPExcel->getActiveSheet()->mergeCells('B10:I10');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:I11');
            $objPHPExcel->getActiveSheet()->mergeCells('B12:I12');
            $objPHPExcel->getActiveSheet()->mergeCells('B13:I13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:E15');
            $objPHPExcel->getActiveSheet()->mergeCells('A14:A15');
            $objPHPExcel->getActiveSheet()->mergeCells('F14:F15');
            $objPHPExcel->getActiveSheet()->mergeCells('G14:G15');
            $objPHPExcel->getActiveSheet()->mergeCells('H14:H15');
            $objPHPExcel->getActiveSheet()->mergeCells('I14:I15');
            $objPHPExcel->getActiveSheet()->mergeCells('B16:E16');



            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+1).':I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            $objPHPExcel->getActiveSheet()->getStyle('B5:B13')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('D8:E8')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('I17:I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('B'.($hang+4).':I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->mergeCells('B'.($hang+4).':I'.($hang+4));



            

            $objPHPExcel->getActiveSheet()->getStyle('H17:I'.$hang)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('I'.$hang.':I'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);

            $objPHPExcel->getActiveSheet()->getStyle("A1:I".($highestRow+1))->getFont()->setName('Times New Roman');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Invoice")
                            ->setSubject("Invoice")
                            ->setDescription("Invoice.")
                            ->setKeywords("Invoice")
                            ->setCategory("Invoice");
            $objPHPExcel->getActiveSheet()->setTitle("Hoa don");

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= HÓA ĐƠN NHÁP.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    

    
    

}
?>