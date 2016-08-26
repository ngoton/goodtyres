<?php
Class checksalesalaryController Extends baseController {
    
    public function index() {
        
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Doanh số tính lương';

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

        $ma = $this->registry->router->param_id;

        $sodonhang = $this->registry->router->addition;

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $check_sale_salary_model = $this->model->get('checksalesalaryModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;


        
        $data = array(
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money = receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
        );

        if ($nv == 0) {
            $data['where'] .= ' AND (check_salary IS NULL OR check_salary = 0)';
        }
        else if ($nv == 1) {
            $data['where'] .= ' AND check_salary = 1';
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
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money = receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
            );

        if ($nv == 0) {
            $data['where'] .= ' AND (check_salary IS NULL OR check_salary = 0)';
        }
        else if ($nv == 1) {
            $data['where'] .= ' AND check_salary = 1';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $info = array();

        $tiresale_model = $this->model->get('tiresaleModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');

        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
        $join_q = array('table'=>'tire_quotation_brand, tire_quotation_size','where'=>'tire_quotation_brand=tire_quotation_brand_id AND tire_quotation_size=tire_quotation_size_id');

        $old = array();
        $str = "";
        foreach ($order_tires as $order_tire) {
            if ($str == "") {
                $str .= $order_tire->order_tire_id;
            }
            else{
                $str .= ",".$order_tire->order_tire_id;
            }

            $check_sale = $check_sale_salary_model->getSalaryByWhere(array('order_tire'=>$order_tire->order_tire_id));
            if ($check_sale) {
                $info['khmoi'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['percent'][$order_tire->order_tire_id] = $check_sale->bonus_percent;
                $info['vuotgia'][$order_tire->order_tire_id] = $check_sale->bonus_over;
                $info['ghichu'][$order_tire->order_tire_id] = $check_sale->comment;
                $info['bonus'][$order_tire->order_tire_id] = $check_sale->bonus;
            }
            else{
                $data = array(
                    'where' => 'tire_sale_date < '.$order_tire->delivery_date,
                );
                $sale_olds = $tiresale_model->getAllTire($data);
                foreach ($sale_olds as $sale) {
                    if (!in_array($sale->customer,$old)) {
                        $old[] = $sale->customer;
                    }
                }

                if (in_array($order_tire->customer,$old)) {
                    $info['khmoi'][$order_tire->order_tire_id] = 0;
                }
                else{
                    $info['khmoi'][$order_tire->order_tire_id] = 1;
                }

                $data = array(
                    'where' => 'order_tire = '.$order_tire->order_tire_id,
                );
                $sales = $tiresale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    if ($sale->tire_brand_name == "Aoteli" || $sale->tire_brand_name == "Yatai" || $sale->tire_brand_name == "Yatone" || $sale->tire_brand_name == "Three-A") {
                        $tire_brand_name = "Shengtai";
                    }
                    else{
                        $tire_brand_name = $sale->tire_brand_name;
                    }

                    $data_q = array(
                        'where' => 'tire_quotation_brand_name ="'.$tire_brand_name.'" AND tire_quotation_size_number ="'.$sale->tire_size_number.'" AND tire_quotation_pattern iN ('.$sale->tire_pattern_type.') AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date > '.$sale->tire_sale_date.')',
                    );
                    $tire_quotations = $tire_quotation_model->getAllTire($data_q,$join_q);

                    $tire_prices = array();
                    foreach ($tire_quotations as $tire) {
                        $tire_prices[$tire->tire_quotation_brand_name][$tire->tire_quotation_size_number][$tire->tire_quotation_pattern] = $tire->tire_quotation_price;
                    }
                    $pt_type = explode(',', $sale->tire_pattern_type);
                    for ($l=0; $l < count($pt_type); $l++) {

                        if (isset($tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]])) {
                            $phi = round(($sale->volume*$order_tire->order_tire_number)/$order_tire->order_cost);
                            $ck = $order_tire->discount>0?round(($sale->volume*$order_tire->order_tire_number)/$order_tire->discount):0;
                            $va = $order_tire->vat>0?round(($sale->volume*$order_tire->order_tire_number)/$order_tire->vat):0;

                            $info['percent'][$sale->order_tire] = 0;

                            if ($sale->sell_price >= $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                                $info['percent'][$sale->order_tire] = 1;
                                if ($sale->sell_price > $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                                    
                                    $vuot = ((($sale->sell_price - $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]])-$phi)*$sale->volume)/2;
                                    if($vuot>0){
                                        $info['vuotgia'][$sale->order_tire] = isset($info['vuotgia'][$sale->order_tire])?$info['vuotgia'][$sale->order_tire]+$vuot:$vuot;
                                    }
                                    
                                }
                            }
                            else if($sale->sell_price < $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]){
                                $a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                                $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                                if ($a >= 0.95*$b) {
                                    if ($order_tire->order_tire_number >= 20) {
                                        $info['percent'][$sale->order_tire] = 1;
                                    }
                                }
                                else if ($a >= 0.94*$b) {
                                    if ($order_tire->order_tire_number >= 50) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.93*$b) {
                                    if ($order_tire->order_tire_number >= 100) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.92*$b) {
                                    if ($order_tire->order_tire_number >= 150) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.91*$b) {
                                    if ($order_tire->order_tire_number >= 200) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else{
                                    $info['percent'][$sale->order_tire] = 0;
                                }
                            }
                            else{
                                $info['percent'][$sale->order_tire] = 0;
                            }
                        }
                        
                    }
                }
            }

            
        }
        $this->view->data['info'] = $info;        

        $this->view->data['order_tires'] = $order_tires;

        $receivable_model = $this->model->get('receivableModel');
        
        $join = array('table'=>'customer, user, staff','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND user_id = account');
        
        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'where'=>'order_tire_status = 1 AND ((check_salary IS NULL OR check_salary = 0) OR check_salary_date >= '.strtotime($batdau).' AND check_salary_date <= '.strtotime($ketthuc).') AND order_tire_id NOT IN ('.$str.')',
            );

        if ($nv != "" && $nv == 0) {
            $data['where'] = 'order_tire_status = 1 AND (check_salary IS NULL OR check_salary = 0)';
        }
        else if ($nv == 1) {
            $data['where'] = 'check_salary = 1 AND check_salary_date >= '.strtotime($batdau).' AND check_salary_date <= '.strtotime($ketthuc).'AND order_tire_id NOT IN ('.$str.')';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        $info = array();

        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
        $join_q = array('table'=>'tire_quotation_brand, tire_quotation_size','where'=>'tire_quotation_brand=tire_quotation_brand_id AND tire_quotation_size=tire_quotation_size_id');

        $old = array();
        foreach ($order_tires as $order_tire) {
            $receivables = $receivable_model->getCostsByWhere(array('order_tire'=>$order_tire->order_tire_id));
            $info['congno'][$order_tire->order_tire_id] = $receivables->money-$receivables->pay_money;

            $check_sale = $check_sale_salary_model->getSalaryByWhere(array('order_tire'=>$order_tire->order_tire_id));
            if ($check_sale) {
                $info['khmoi'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['percent'][$order_tire->order_tire_id] = $check_sale->bonus_percent;
                $info['vuotgia'][$order_tire->order_tire_id] = $check_sale->bonus_over;
                $info['ghichu'][$order_tire->order_tire_id] = $check_sale->comment;
                $info['bonus'][$order_tire->order_tire_id] = $check_sale->bonus;
            }
            else{

                $data = array(
                    'where' => 'tire_sale_date < '.$order_tire->delivery_date,
                );
                $sale_olds = $tiresale_model->getAllTire($data);
                foreach ($sale_olds as $sale) {
                    if (!in_array($sale->customer,$old)) {
                        $old[] = $sale->customer;
                    }
                }

                if (in_array($order_tire->customer,$old)) {
                    $info['khmoi'][$order_tire->order_tire_id] = 0;
                }
                else{
                    $info['khmoi'][$order_tire->order_tire_id] = 1;
                }

                $data = array(
                    'where' => 'order_tire = '.$order_tire->order_tire_id,
                );
                $sales = $tiresale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    if ($sale->tire_brand_name == "Aoteli" || $sale->tire_brand_name == "Yatai" || $sale->tire_brand_name == "Yatone" || $sale->tire_brand_name == "Three-A") {
                        $tire_brand_name = "Shengtai";
                    }
                    else{
                        $tire_brand_name = $sale->tire_brand_name;
                    }

                    $data_q = array(
                        'where' => 'tire_quotation_brand_name ="'.$tire_brand_name.'" AND tire_quotation_size_number ="'.$sale->tire_size_number.'" AND tire_quotation_pattern iN ('.$sale->tire_pattern_type.') AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date > '.$sale->tire_sale_date.')',
                    );
                    $tire_quotations = $tire_quotation_model->getAllTire($data_q,$join_q);

                    $tire_prices = array();
                    foreach ($tire_quotations as $tire) {
                        $tire_prices[$tire->tire_quotation_brand_name][$tire->tire_quotation_size_number][$tire->tire_quotation_pattern] = $tire->tire_quotation_price;
                    }
                    $pt_type = explode(',', $sale->tire_pattern_type);
                    for ($l=0; $l < count($pt_type); $l++) {
                        if (isset($tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]])) {
                            $phi = round(($sale->volume*$order_tire->order_tire_number)/$order_tire->order_cost);
                            $ck = $order_tire->discount>0?round(($sale->volume*$order_tire->order_tire_number)/$order_tire->discount):0;
                            $va = $order_tire->vat>0?round(($sale->volume*$order_tire->order_tire_number)/$order_tire->vat):0;

                            $info['percent'][$sale->order_tire] = 0;
                            
                            if ($sale->sell_price >= $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                                $info['percent'][$sale->order_tire] = 1;
                                if ($sale->sell_price > $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                                    
                                    $vuot = ((($sale->sell_price - $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]])-$phi)*$sale->volume)/2;
                                    if($vuot>0){
                                        $info['vuotgia'][$sale->order_tire] = isset($info['vuotgia'][$sale->order_tire])?$info['vuotgia'][$sale->order_tire]+$vuot:$vuot;
                                    }
                                    
                                }
                            }
                            else if($sale->sell_price < $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]){
                                $a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                                $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                                if ($a >= 0.95*$b) {
                                    if ($order_tire->order_tire_number >= 20) {
                                        $info['percent'][$sale->order_tire] = 1;
                                    }
                                }
                                else if ($a >= 0.94*$b) {
                                    if ($order_tire->order_tire_number >= 50) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.93*$b) {
                                    if ($order_tire->order_tire_number >= 100) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.92*$b) {
                                    if ($order_tire->order_tire_number >= 150) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else if ($a >= 0.91*$b) {
                                    if ($order_tire->order_tire_number >= 200) {
                                        $info['percent'][$sale->order_tire] = 0.5;
                                    }
                                }
                                else{
                                    $info['percent'][$sale->order_tire] = 0;
                                }
                            }
                            else{
                                $info['percent'][$sale->order_tire] = 0;
                            }
                        }
                        
                    }
                }
            }
        }

        $this->view->data['info2'] = $info;        

        $this->view->data['order_tires2'] = $order_tires;


        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('checksalesalary/index');
    }
    public function check(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $percent = trim(str_replace('%', '', $_POST['percent']));
            $doanhso = trim(str_replace(',', '', $_POST['doanhso']));
            $vuotgia = trim(str_replace(',', '', $_POST['vuotgia']));
            $khmoi = trim($_POST['khmoi']);
            $ghichu = trim($_POST['ghichu']);
            $staff = trim($_POST['staff']);

            $order_tire_model = $this->model->get('ordertireModel');
            $check_sale_salary_model = $this->model->get('checksalesalaryModel');
            $order_tire = $order_tire_model->getTire($_POST['data']);

            if (isset($_POST['update'])) {
                $data_check = array(
                    'staff' => $staff,
                    'order_tire' => $_POST['data'],
                    'bonus_percent' => $percent,
                    'bonus' => $doanhso,
                    'bonus_over' => $vuotgia,
                    'new_customer' => $khmoi != ""?1:0,
                    'comment' => $ghichu,
                );
                $check_sale_salary_model->updateSalary($data_check,array('order_tire'=>$_POST['data']));
            }
            else{
                if ($order_tire->check_salary==1) {
                    $data = array(
                        'check_salary' => 0,
                        'check_salary_date' => null,
                    );

                    $check_sale_salary_model->querySalary('DELETE FROM check_sale_salary WHERE order_tire = '.$_POST['data']);
                }
                else{
                    $data = array(
                        'check_salary' => 1,
                        'check_salary_date' => strtotime(date('d-m-Y')),
                    );

                    $data_check = array(
                        'staff' => $staff,
                        'order_tire' => $_POST['data'],
                        'salary_date' => strtotime(date('d-m-Y')),
                        'bonus_percent' => $percent,
                        'bonus' => $doanhso,
                        'bonus_over' => $vuotgia,
                        'new_customer' => $khmoi != ""?1:0,
                        'comment' => $ghichu,
                    );
                    $check_sale_salary_model->createSalary($data_check);
                }
              
                $order_tire_model->updateTire($data,array('order_tire_id' => $_POST['data']));
            }
            

            return true;
                    
        }
    }
    

}
?>