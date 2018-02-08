<?php
Class checksalaryprofitController Extends baseController {
    
    public function index() {
        
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lợi nhuận tính lương';

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


        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;


        
        $data = array(
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money <= receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
        );


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
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money <= receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
            );

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_import_model = $this->model->get('tireimportModel');
        
        $tire_prices = array();
        foreach ($order_tires as $order_tire) {
            $ngay = $order_tire->order_tire_status==1?$order_tire->delivery_date:strtotime(date('d-m-Y'));
            $ngayketthuc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));
            $data_list = array(
                'where' => 'order_tire='.$order_tire->order_tire_id,
            );
            $tire_lists = $order_tire_list_model->getAllTire($data_list);
            foreach ($tire_lists as $tire_list) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.$ngayketthuc.' AND tire_brand = '.$tire_list->tire_brand.' AND tire_size = '.$tire_list->tire_size.' AND tire_pattern = '.$tire_list->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($order_tire->order_number != "") {
                    $data = array(
                        'where' => 'order_num = "'.$order_tire->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$order_tire->delivery_date)).' AND tire_brand = '.$tire_list->tire_brand.' AND tire_size = '.$tire_list->tire_size.' AND tire_pattern = '.$tire_list->tire_pattern,
                        'order_by' => 'start_date',
                        'order' => 'DESC',
                        'limit' => 1,
                    );
                    $tire_imports = $tire_import_model->getAllTire($data);
                    foreach ($tire_imports as $tire_import) {
                        $gia = $tire_import->tire_price;
                    }
                }
                
                $tire_prices[$order_tire->order_tire_id] = isset($tire_prices[$order_tire->order_tire_id])?$tire_prices[$order_tire->order_tire_id]+$tire_list->tire_number*$gia:$tire_list->tire_number*$gia;
            }
            
        }

        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['tire_prices'] = $tire_prices;


        $this->view->show('checksalaryprofit/index');
    }
    

}
?>