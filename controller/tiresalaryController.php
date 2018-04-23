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
        if (isset($st) && $st > 0) {
            $join_order['where'] .= ' AND staff_id = '.$st;
        }
        $orders = $order_tire_model->getAllTire(array('where'=>'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),'order_by'=>'order_number ASC'),$join_order);
        
        $doanhthu = array();
        $arr_cost = array();
        $arr_customer = array();
        $arr_discount = array();
        $arr_vat = array();
        $arr_number = array();

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

        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
        $check_salary_percent_model = $this->model->get('checksalarypercentModel');

        $qr = 'SELECT * FROM check_salary_percent WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC LIMIT 1';
        $check_salarys = $check_salary_percent_model->querySalary($qr);
        $arr_salary = array();
        foreach ($check_salarys as $check_salary) {
            $arr_salary['sanluong'] = $check_salary->order_number;
            $arr_salary['phantram'] = $check_salary->order_percent;
        }

        $total_order_day = array();

        $last_month = array();
        $this_month = array();

        $str = "";
        $old = array();
        
        foreach ($orders as $order_tire) {
            $arr_cost[$order_tire->order_tire_id] = $order_tire->order_cost/$order_tire->order_tire_number;
            $doanhthu[$order_tire->staff_id] = isset($doanhthu[$order_tire->staff_id])?$doanhthu[$order_tire->staff_id]+$order_tire->total:$order_tire->total;
            $arr_customer[$order_tire->customer][date('d-m-Y',$order_tire->delivery_date)] = isset($arr_customer[$order_tire->customer][date('d-m-Y',$order_tire->delivery_date)])?$arr_customer[$order_tire->customer][date('d-m-Y',$order_tire->delivery_date)]+$order_tire->order_tire_number:$order_tire->order_tire_number;
            $arr_discount[$order_tire->order_tire_id] = ($order_tire->discount+$order_tire->reduce)/$order_tire->order_tire_number;
            $arr_vat[$order_tire->order_tire_id] = $order_tire->vat/$order_tire->order_tire_number;
            $arr_number[$order_tire->order_tire_id] = $order_tire->order_tire_number;

            $str .= ",".$order_tire->order_tire_id;

            $first_order = 0;

            $data = array(
                'where' => 'customer = '.$order_tire->customer.' AND tire_sale_date < '.$order_tire->delivery_date,
                'limit' => 1,
            );
            $sale_olds = $tiresale_model->getAllTire($data);
            foreach ($sale_olds as $sale) {
                if (!in_array($sale->customer,$old)) {
                    $old[] = $sale->customer;
                }
            }

            $total_order_before = 0; //Tổng sản lượng tháng trước
            $total_order = 0; //Tổng sản lượng tháng này

            $myDate = strtotime(date("d-m-Y", $order_tire->delivery_date) . "-1 month" ) ;

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$order_tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$myDate)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$myDate)).' GROUP BY customer');
                
            foreach ($sum_order as $sum) {
                $total_order_before = $sum->tong;
            }

            ////////

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$order_tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$order_tire->delivery_date)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$order_tire->delivery_date)).' GROUP BY customer');
            foreach ($sum_order as $sum) {
                $total_order = $sum->tong;
            }

            $last_month[$order_tire->order_tire_id] = $total_order_before;
            $this_month[$order_tire->order_tire_id] = $total_order;

            if ($total_order_before>0) {
                if ($total_order_before<20) {
                    $column = "tire_retail";
                }
                else if ($total_order_before<40) {
                    $column = "tire_20";
                }
                else if ($total_order_before<60) {
                    $column = "tire_40";
                }
                else if ($total_order_before<80) {
                    $column = "tire_60";
                }
                else if ($total_order_before<100) {
                    $column = "tire_80";
                }
                else if ($total_order_before<120) {
                    $column = "tire_100";
                }
                else if ($total_order_before<150) {
                    $column = "tire_120";
                }
                else if ($total_order_before<180) {
                    $column = "tire_150";
                }
                else if ($total_order_before<220) {
                    $column = "tire_180";
                }
                else {
                    $column = "tire_cont";
                }
            }
            else{
                if ($total_order<20) {
                    $column = "tire_retail";
                }
                else if ($total_order<40) {
                    $column = "tire_20";
                }
                else if ($total_order<60) {
                    $column = "tire_40";
                }
                else if ($total_order<80) {
                    $column = "tire_60";
                }
                else if ($total_order<100) {
                    $column = "tire_80";
                }
                else if ($total_order<120) {
                    $column = "tire_100";
                }
                else if ($total_order<150) {
                    $column = "tire_120";
                }
                else if ($total_order<180) {
                    $column = "tire_150";
                }
                else if ($total_order<220) {
                    $column = "tire_180";
                }
                else {
                    $column = "tire_cont";
                }
            }

            $tongchiphi = $order_tire->order_cost+$order_tire->discount+$order_tire->reduce;
            $tongsoluong = $order_tire->order_tire_number;

            $data = array(
                'where' => 'order_tire = '.$order_tire->order_tire_id,
            );
            $sales = $tiresale_model->getAllTire($data);

            foreach ($sales as $sale) {

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
                        $donmoi[$sale->sale] = isset($donmoi[$sale->sale])?$donmoi[$sale->sale]+1:1;
                        // if ($arr_number[$sale->order_tire] > 2) {
                        //     $donmoi[$sale->sale] = isset($donmoi[$sale->sale])?$donmoi[$sale->sale]+1:1;
                        // }
                        // else{
                        //     $doncu[$sale->sale] = isset($doncu[$sale->sale])?$doncu[$sale->sale]+1:1;
                        // }
                        
                    }
                }

                $cus_arr[$sale->sale][] = $sale->customer;
                $od_arr[$sale->sale][] = $sale->order_tire;

                if ($sale->sell_price_vat=="" || $sale->sell_price_vat==0) {
                    $dongia = $sale->sell_price+($sale->sell_price*$order_tire->vat_percent/100);
                }
                else{
                    if ($order_tire->check_price_vat==1) {
                        $dongia = $sale->sell_price_vat;
                    }
                    else{
                        $dongia = $sale->sell_price+($sale->sell_price*$order_tire->vat_percent/100);
                    }
                }
                

                $tire_prices = $dongia;
                $tire_price_origin = $dongia;

                $data_q = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date > '.$sale->tire_sale_date.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

                $data_e = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date > '.$sale->tire_sale_date.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );

                $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e); // Khuyến mãi

                foreach ($tire_price_discounts as $tire) {
                    if (!isset($tire->$column) || $tire->$column==0 || $tire->$column=="") {
                        $column = 'tire_'.(str_replace('tire_', '', $column)+10);
                    }
                    while (!isset($tire->$column) || $tire->$column==0 || $tire->$column=="") {
                        $column = 'tire_'.(str_replace('tire_', '', $column)-10);
                    }
                        
                    $tire_prices = $tire->$column; 
                    $tire_price_origin = ($tire->tire_price*0.75); // giá công khai giảm 25% + vc

                    foreach ($tire_price_discount_events as $event) {
                        if ($event->percent_discount > 0) {
                            $tire_prices = $tire->$column*((100-$event->percent_discount)/100);
                            $tire_price_origin = (($tire->tire_price*0.75))*((100-$event->percent_discount)/100);
                        }
                        else{
                            $tire_prices = $tire->$column-$event->money_discount;
                            $tire_price_origin = ($tire->tire_price*0.75)-$event->money_discount;
                        }
                    }
                }

                $gia = $dongia;

                $chiphi = $tongchiphi/$tongsoluong;
                // $gia = $dongia-$chiphi;

                // if ($tongsoluong>49) { // Miễn phí vận chuyển đơn 50 cái
                //     if ($gia+70000 > $dongia) {
                //         $gia = $dongia;
                //     }
                //     else{
                //         $gia = $gia+70000;
                //     }
                // }

                // Không lấy Hđ
                if ($order_tire->vat==0) {
                    if ($tire_prices<5000000) {
                        $discount = 100000;
                    }
                    else{
                        $discount = 200000;
                    }

                    $gia = $gia+$discount;
                    $dongia = $dongia+$discount;
                }
                
                


                $salary = (($gia-$tire_price_origin)*$arr_salary['phantram']/100)*$sale->volume;

                if ($dongia < $tire_prices) {
                    if ($sale->customer_type==1) {
                        if ($dongia < $tire_prices*0.95 || $dongia < $tire_price_origin) {
                            $salary = $arr_salary['sanluong']*$sale->volume;
                        }
                    }
                    else{
                        $salary = $arr_salary['sanluong']*$sale->volume;
                    }
                }

                if ($tongchiphi>(120000*$tongsoluong) && $tongsoluong>49) {
                    $salary = $arr_salary['sanluong']*$sale->volume;
                }

                if ($tongsoluong<50) {
                    $salary -= $chiphi*$sale->volume;
                }

                if ($salary < $arr_salary['sanluong']*$sale->volume) {
                    $salary = $arr_salary['sanluong']*$sale->volume;
                }
                
                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+$salary:$salary;
                
                
            }
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