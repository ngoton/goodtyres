<?php
Class tiresalaryController Extends baseController {
    

    public function index(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tổng hợp';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $vong = isset($_POST['sl_round']) ? $_POST['sl_round'] : null;
            $trangthai = isset($_POST['sl_trangthai']) ? $_POST['sl_trangthai'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $vong = (int)date('m',strtotime($batdau));
            $trangthai = date('Y',strtotime($batdau));
        }

        $id = $this->registry->router->param_id;
        $st = $this->registry->router->page;

        

        $customer_model = $this->model->get('customertireModel');
        $user_model = $this->model->get('userModel');
        $tiresale_model = $this->model->get('tiresaleModel');
        $customer_type_model = $this->model->get('customertiretypeModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');
        $order_tire_model = $this->model->get('ordertireModel');


        $join = array('table'=>'staff','where'=>'user_id=account');
        $data_user = array(
            'where' => 'staff_id IN (SELECT sale FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')',
        );

        if (isset($id) && $id > 0) {
            $data_user = array(
                'where' => 'staff_id IN (SELECT sale FROM tire_sale WHERE tire_sale_date >= '.$id.' AND tire_sale_date <= '.strtotime(date('t-m-Y',$id)).')',
            );

            $batdau = '01-'.date('m-Y',$id);
            $ketthuc = date('t-m-Y',$id);

            if (isset($st) && $st > 0) {
                $data_user['where'] .= ' AND staff_id = '.$st;
            }
        }

        $users = $user_model->getAllUser($data_user,$join);

        $vong = (int)date('m',strtotime($batdau));
        $trangthai = date('Y',strtotime($batdau));


        $data = array(
            'where' => 'tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc),
        );
        if (isset($id) && $id > 0) {
            $data = array(
                'where' => 'tire_sale_date >= '.$id.' AND tire_sale_date <= '.strtotime(date('t-m-Y',$id)),
            );

            if (isset($st) && $st > 0) {
                $data['where'] .= ' AND sale = '.$st;
            }
        }

        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');

        $sales = $tiresale_model->getAllTire($data,$join);

        $join_order = array('table'=>'staff','where'=>'sale = account');
        $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'),$join_order);
        
        $doanhthu = array();
        $arr_cost = array();
        $arr_customer = array();
        $arr_discount = array();
        $arr_vat = array();
        $arr_number = array();
        
        foreach ($orders as $tire) {
            $arr_cost[$tire->order_tire_id] = $tire->order_cost/$tire->order_tire_number;
            $doanhthu[$tire->staff_id] = isset($doanhthu[$tire->staff_id])?$doanhthu[$tire->staff_id]+$tire->total:$tire->total;
            $arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)] = isset($arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)])?$arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)]+$tire->order_tire_number:$tire->order_tire_number;
            $arr_discount[$tire->order_tire_id] = ($tire->discount+$tire->reduce)/$tire->order_tire_number;
            $arr_vat[$tire->order_tire_id] = $tire->vat/$tire->order_tire_number;
            $arr_number[$tire->order_tire_id] = $tire->order_tire_number;
        }

        $data = array(
            'where' => 'tire_sale_date < '.strtotime($batdau),
        );
        if (isset($id) && $id > 0) {
            $data = array(
                'where' => 'tire_sale_date < '.$id,
            );

        }

        $sale_olds = $tiresale_model->getAllTire($data);

        $old = array();
        foreach ($sale_olds as $sale) {
            if (!in_array($sale->customer,$old)) {
                $old[] = $sale->customer;
            }
        }

        $sl_daily = array();
        $sl_tt = array();
        $daily_cu = array();
        $daily_moi = array();
        $tt_moi = array();
        $tt_cu = array();
        $donmoi = array();
        $doncu = array();

        $cus_arr = array();
        $od_arr = array();

        $luong_sp = array();
        $luong_vuotgia = array();

        $join_q = array('table'=>'tire_quotation_brand, tire_quotation_size','where'=>'tire_quotation_brand=tire_quotation_brand_id AND tire_quotation_size=tire_quotation_size_id');

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
                    if (isset($arr_cost[$sale->order_tire])) {
                        $phi = $arr_cost[$sale->order_tire];
                    }
                    else{
                        $phi = 0;
                    }

                    if (isset($arr_discount[$sale->order_tire])) {
                        $ck = $arr_discount[$sale->order_tire];
                    }
                    else{
                        $ck = 0;
                    }

                    if (isset($arr_vat[$sale->order_tire])) {
                        $va = $arr_vat[$sale->order_tire];
                    }
                    else{
                        $va = 0;
                    }

                    if ($sale->sell_price >= $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                        //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*1/100):($sale->volume*$sale->sell_price)*1/100;
                        $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;

                        if ($sale->sell_price > $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]) {
                            

                            $vuot = ((($sale->sell_price - $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]])-$phi)*$sale->volume)/2;
                            if($vuot>0){
                                $luong_vuotgia[$sale->sale] = isset($luong_vuotgia[$sale->sale])?$luong_vuotgia[$sale->sale]+$vuot:$vuot;
                            }
                            
                        }
                    }
                    else if($sale->sell_price < $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]){
                        //$a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                        $a = $sale->volume*($sale->sell_price - $ck + $va);
                        $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                        if ($a >= 0.95*$b) {
                            if ($a >= 0.97*$b) {
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;
                            }
                            else{
                                if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 20) {
                                    //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*1/100):($sale->volume*$sale->sell_price)*1/100;
                                    $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;
                                }
                            }
                            
                        }
                        else if ($a >= 0.94*$b) {
                            if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 50) {
                                //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*0.5/100):($sale->volume*$sale->sell_price)*0.5/100;
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*0.5/100):($sale->volume*($sale->sell_price+$va-$ck))*0.5/100;
                            }
                        }
                        else if ($a >= 0.93*$b) {
                            if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 100) {
                                //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*0.5/100):($sale->volume*$sale->sell_price)*0.5/100;
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*0.5/100):($sale->volume*($sale->sell_price+$va-$ck))*0.5/100;
                            }
                        }
                        else if ($a >= 0.92*$b) {
                            if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 150) {
                                //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*0.5/100):($sale->volume*$sale->sell_price)*0.5/100;
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*0.5/100):($sale->volume*($sale->sell_price+$va-$ck))*0.5/100;
                            }
                        }
                        else if ($a >= 0.91*$b) {
                            if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 200) {
                                //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*0.5/100):($sale->volume*$sale->sell_price)*0.5/100;
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*0.5/100):($sale->volume*($sale->sell_price+$va-$ck))*0.5/100;
                            }
                        }
                        else{
                            $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+0:0;
                        }
                    }
                    else{
                        $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+0:0;
                    }
                }
                
            }

            if ($sale->customer_type == 1) {
                $sl_daily[$sale->sale] = isset($sl_daily[$sale->sale])?$sl_daily[$sale->sale]+$sale->volume:$sale->volume;
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->customer,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                    }
                    else{
                        $daily_moi[$sale->sale] = isset($daily_moi[$sale->sale])?$daily_moi[$sale->sale]+1:1;
                    }
                }
            }
            else{
                $sl_tt[$sale->sale] = isset($sl_tt[$sale->sale])?$sl_tt[$sale->sale]+$sale->volume:$sale->volume;
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->customer,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                    }
                    else{
                        $tt_moi[$sale->sale] = isset($tt_moi[$sale->sale])?$tt_moi[$sale->sale]+1:1;
                    }
                }
            }

            if(!isset($od_arr[$sale->sale]) || !in_array($sale->order_tire,$od_arr[$sale->sale])){
                if (in_array($sale->customer,$old)) {
                    $doncu[$sale->sale] = isset($doncu[$sale->sale])?$doncu[$sale->sale]+1:1;
                }
                else{
                    if ($arr_number[$sale->order_tire] > 2) {
                        $donmoi[$sale->sale] = isset($donmoi[$sale->sale])?$donmoi[$sale->sale]+1:1;
                    }
                    else{
                        $doncu[$sale->sale] = isset($doncu[$sale->sale])?$doncu[$sale->sale]+1:1;
                    }
                    
                }
            }

            $cus_arr[$sale->sale][] = $sale->customer;
            $od_arr[$sale->sale][] = $sale->order_tire;
        }

        
        $this->view->data['users'] = $users;
        $this->view->data['sl_daily'] = $sl_daily;
        $this->view->data['sl_tt'] = $sl_tt;
        $this->view->data['daily_cu'] = $daily_cu;
        $this->view->data['daily_moi'] = $daily_moi;
        $this->view->data['tt_moi'] = $tt_moi;
        $this->view->data['tt_cu'] = $tt_cu;
        $this->view->data['don_moi'] = $donmoi;
        $this->view->data['don_cu'] = $doncu;

        $this->view->data['luong_sp'] = $luong_sp;
        $this->view->data['luong_vuotgia'] = $luong_vuotgia;

        $this->view->data['doanhthu'] = $doanhthu;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['vong'] = $vong;
        $this->view->data['trangthai'] = $trangthai;

        $this->view->show('tiresalary/index');

    }

}
?>