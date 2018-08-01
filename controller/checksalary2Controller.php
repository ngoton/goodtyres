<?php
Class checksalary2Controller Extends baseController {
    
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

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $check_sale_salary_model = $this->model->get('checksalesalaryModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;


        
        $data = array(
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money <= receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
        );

        if ($nv == 0) {
            $data['where'] .= ' AND (check_salary IS NULL OR check_salary = 0)';
        }
        else if ($nv == 1) {
            $data['where'] = '(check_salary_date >= '.strtotime($batdau).' AND check_salary_date <= '.strtotime($ketthuc).') AND check_salary = 1';
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
            'where'=>'order_tire_id IN (SELECT order_tire FROM receivable WHERE receivable.money <= receivable.pay_money AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')',
            );

        if ($nv == 0) {
            $data['where'] .= ' AND (check_salary IS NULL OR check_salary = 0)';
        }
        else if ($nv == 1) {
            $data['where'] = '(check_salary_date >= '.strtotime($batdau).' AND check_salary_date <= '.strtotime($ketthuc).') AND check_salary = 1';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $info = array();

        $tiresale_model = $this->model->get('tiresaleModel');
        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
        $check_salary_percent_model = $this->model->get('checksalarypercentModel');

        $qr = 'SELECT * FROM check_salary_percent WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC LIMIT 1';
        $check_salarys = $check_salary_percent_model->querySalary($qr);
        $arr_salary = array();
        foreach ($check_salarys as $check_salary) {
            $arr_salary['sanluong'] = $check_salary->order_number;
            $arr_salary['moi'] = $check_salary->order_new;
            $arr_salary['cu'] = $check_salary->order_old;
            $arr_salary['phantram'] = $check_salary->order_percent;
        }

        $this->view->data['arr_salary'] = $arr_salary;

        

        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
        
        
        $total_order_day = array();

        $last_month = array();
        $this_month = array();

        $order_tire_discount = array();
        

        $old = array();
        $str = 0;
        foreach ($order_tires as $order_tire) {
            $str .= ",".$order_tire->order_tire_id;

            $first_order = 0;
            

            $check_sale = $check_sale_salary_model->getSalaryByWhere(array('order_tire'=>$order_tire->order_tire_id));
            if ($check_sale) {
                $info['khmoi'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['khcu'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['percent'][$order_tire->order_tire_id] = $check_sale->bonus_percent;
                $info['vuotgia'][$order_tire->order_tire_id] = $check_sale->bonus_over;
                $info['bonus'][$order_tire->order_tire_id] = $check_sale->bonus;
                $info['kpi'][$order_tire->order_tire_id] = $check_sale->bonus_kpi;
                $info['thangluong'][$order_tire->order_tire_id] = $check_sale->salary_date;
            }
            else{
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

                $myDate = strtotime(date("d-m-Y", strtotime('01-'.date('m-Y',$order_tire->delivery_date))) . "-1 month" ) ;

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

                $info['kpi'][$order_tire->order_tire_id] = $arr_salary['sanluong']*$order_tire->order_tire_number;

                if (in_array($order_tire->customer,$old)) {
                    $info['khmoi'][$order_tire->order_tire_id] = 0;
                    
                    if ($order_tire->order_tire_number>1 && !isset($total_order_day[date('d-m-Y',$order_tire->delivery_date)][$order_tire->customer])) {
                        $info['khcu'][$order_tire->order_tire_id] = 0;
                        $info['kpi'][$order_tire->order_tire_id] += $arr_salary['cu'];
                    }
                    else{
                        $info['khcu'][$order_tire->order_tire_id] = 1;
                    }
                }
                else{
                    $first_order = 2;

                    if ($total_order > 2) {
                        $info['khmoi'][$order_tire->order_tire_id] = 1;
                        $info['khcu'][$order_tire->order_tire_id] = 1;
                        $info['kpi'][$order_tire->order_tire_id] += $arr_salary['moi'];
                    }
                    else{
                        if (!isset($total_order_day[date('d-m-Y',$order_tire->delivery_date)][$order_tire->customer])) {
                            $info['khmoi'][$order_tire->order_tire_id] = 0;
                            $info['khcu'][$order_tire->order_tire_id] = 0;
                            $info['kpi'][$order_tire->order_tire_id] += $arr_salary['cu'];
                        }
                        else{
                            $info['khmoi'][$order_tire->order_tire_id] = 0;
                            $info['khcu'][$order_tire->order_tire_id] = 1;
                        }
                    }
                }

                $total_order_day[date('d-m-Y',$order_tire->delivery_date)][$order_tire->customer] = 1; //Đơn trong ngày của KH

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
                    $giacongkhai = $dongia;

                    $data_q = array(
                        'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date >= '.$sale->tire_sale_date.')',
                        'order_by' => 'start_date',
                        'order' => 'DESC',
                        'limit' => 1,
                    );
                    $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

                    $data_e = array(
                        'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date >= '.$sale->tire_sale_date.')',
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
                        $giacongkhai = $tire->tire_price; // giá công khai

                        foreach ($tire_price_discount_events as $event) {
                            if ($event->percent_discount > 0) {
                                $tire_prices = $tire->$column*((100-$event->percent_discount)/100);
                                $tire_price_origin = ($tire->tire_price*0.75)*((100-$event->percent_discount)/100);
                                $giacongkhai = $tire->tire_price*((100-$event->percent_discount)/100);
                            }
                            else{
                                $tire_prices = $tire->$column-$event->money_discount;
                                $tire_price_origin = ($tire->tire_price*0.75)-$event->money_discount;
                                $giacongkhai = $tire->tire_price-$event->money_discount;
                            }
                        }
                    }

                    //$tire_prices = $tire_prices-($tire_prices*$first_order/100); // Giảm 2% cho đơn mới

                    
                    $chiphi = round($tongchiphi/$tongsoluong)-77000;
                    //$chiphi = $chiphi>0?$chiphi:0;
                    
                    $gia = $dongia-$chiphi;
                    $dongia = $dongia-$chiphi;

                    // Không lấy Hđ
                    if ($order_tire->vat==0) {
                        if ($tire_prices<5000000) {
                            $discount = 160000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }

                    $order_tire_discount[$order_tire->order_tire_id]['thu'] = isset($order_tire_discount[$order_tire->order_tire_id]['thu'])?$order_tire_discount[$order_tire->order_tire_id]['thu']+$gia*$sale->volume:$gia*$sale->volume;
                    $order_tire_discount[$order_tire->order_tire_id]['gia'] = isset($order_tire_discount[$order_tire->order_tire_id]['gia'])?$order_tire_discount[$order_tire->order_tire_id]['gia']+$giacongkhai*$sale->volume:$giacongkhai*$sale->volume;

                    
                    $salary = (($gia-$tire_price_origin)*$arr_salary['phantram']/100)*$sale->volume;


                    if ($dongia < $tire_prices*0.95 || $dongia < $tire_price_origin) {
                        $salary = 0;
                    }
                    else{
                        if ($sale->customer_type==1) {
                            if ($dongia < $tire_prices*0.96) {
                                $salary = $arr_salary['sanluong']*$sale->volume;
                            }
                        }
                        else{
                            if ($dongia < $tire_prices*0.98) {
                                $salary = $arr_salary['sanluong']*$sale->volume;
                            }
                        }

                        if ($salary < $arr_salary['sanluong']*$sale->volume) {
                            $salary = $arr_salary['sanluong']*$sale->volume;
                        }
                    }
                    

                    
                    $info['bonus'][$order_tire->order_tire_id] = isset($info['bonus'][$order_tire->order_tire_id])?$info['bonus'][$order_tire->order_tire_id]+$salary:$salary;
                    //$info['vuotgia'][$order_tire->order_tire_id] = isset($info['vuotgia'][$order_tire->order_tire_id])?$info['vuotgia'][$order_tire->order_tire_id]+($over*$sale->volume):($over*$sale->volume);

                    $info['price'][$order_tire->order_tire_id] = isset($info['price'][$order_tire->order_tire_id])?$info['price'][$order_tire->order_tire_id]+$tire_price_origin*$sale->volume:$tire_price_origin*$sale->volume;
                }

                $info['percent'][$order_tire->order_tire_id] = round($info['bonus'][$order_tire->order_tire_id]/$order_tire->total*100,1);

                
            }

            
        }
        $this->view->data['info'] = $info;        

        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['last_month'] = $last_month; 
        $this->view->data['this_month'] = $this_month; 

        $this->view->data['order_tire_discount'] = $order_tire_discount;

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
            $data['where'] = 'check_salary = 1 AND check_salary_date >= '.strtotime($batdau).' AND check_salary_date <= '.strtotime($ketthuc).' AND order_tire_id NOT IN ('.$str.')';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND staff_id = '.$trangthai;
        }


        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        $info = array();

        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
        
        $last_month = array();
        $this_month = array();

        $order_tire_discount = array();
        
        $old = array();
        foreach ($order_tires as $order_tire) {

            $first_order = 0;
            
            $receivables = $receivable_model->getCostsByWhere(array('order_tire'=>$order_tire->order_tire_id));
            $info['congno'][$order_tire->order_tire_id] = $receivables->money-$receivables->pay_money;

            $check_sale = $check_sale_salary_model->getSalaryByWhere(array('order_tire'=>$order_tire->order_tire_id));
            if ($check_sale) {
                $info['khmoi'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['khcu'][$order_tire->order_tire_id] = $check_sale->new_customer;
                $info['percent'][$order_tire->order_tire_id] = $check_sale->bonus_percent;
                $info['vuotgia'][$order_tire->order_tire_id] = $check_sale->bonus_over;
                $info['bonus'][$order_tire->order_tire_id] = $check_sale->bonus;
                $info['kpi'][$order_tire->order_tire_id] = $check_sale->bonus_kpi;
                $info['thangluong'][$order_tire->order_tire_id] = $check_sale->salary_date;
            }
            else{

                $data = array(
                    'where' => 'customer = '.$order_tire->customer.' AND tire_sale_date < '.$order_tire->delivery_date,
                );
                $sale_olds = $tiresale_model->getAllTire($data);
                foreach ($sale_olds as $sale) {
                    if (!in_array($sale->customer,$old)) {
                        $old[] = $sale->customer;
                    }
                }

                $total_order_before = 0; //Tổng sản lượng tháng trước
                $total_order = 0; //Tổng sản lượng tháng này

                $myDate = strtotime(date("d-m-Y", strtotime('01-'.date('m-Y',$order_tire->delivery_date))) . "-1 month" ) ;

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


                $info['kpi'][$order_tire->order_tire_id] = $arr_salary['sanluong']*$order_tire->order_tire_number;

                if (in_array($order_tire->customer,$old)) {
                    $info['khmoi'][$order_tire->order_tire_id] = 0;
                    if ($order_tire->order_tire_number>1) {
                        $info['khcu'][$order_tire->order_tire_id] = 0;
                        $info['kpi'][$order_tire->order_tire_id] += $arr_salary['cu'];
                    }
                    else{
                        $info['khcu'][$order_tire->order_tire_id] = 1;
                    }
                }
                else{
                    $first_order = 2;

                    if ($total_order > 2) {
                        $info['khmoi'][$order_tire->order_tire_id] = 1;
                        $info['khcu'][$order_tire->order_tire_id] = 1;
                        $info['kpi'][$order_tire->order_tire_id] += $arr_salary['moi'];
                    }
                    else{
                        $info['khmoi'][$order_tire->order_tire_id] = 0;
                        $info['khcu'][$order_tire->order_tire_id] = 0;
                        $info['kpi'][$order_tire->order_tire_id] += $arr_salary['cu'];
                    }
                    
                }

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
                    $giacongkhai = $dongia;

                    $data_q = array(
                        'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date >= '.$sale->tire_sale_date.')',
                        'order_by' => 'start_date',
                        'order' => 'DESC',
                        'limit' => 1,
                    );
                    $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

                    $data_e = array(
                        'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date <= '.$sale->tire_sale_date.' AND (end_date IS NULL OR end_date >= '.$sale->tire_sale_date.')',
                        'order_by' => 'start_date',
                        'order' => 'DESC',
                        'limit' => 1,
                    );

                    $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);

                    foreach ($tire_price_discounts as $tire) {
                        if (!isset($tire->$column) || $tire->$column==0 || $tire->$column=="") {
                            $column = 'tire_'.(str_replace('tire_', '', $column)+10);
                        }
                        while (!isset($tire->$column) || $tire->$column==0 || $tire->$column=="") {
                            $column = 'tire_'.(str_replace('tire_', '', $column)-10);
                        }
                        
                        $tire_prices = $tire->$column;
                        $tire_price_origin = ($tire->tire_price*0.75);
                        $giacongkhai = $tire->tire_price;

                        foreach ($tire_price_discount_events as $event) {
                            if ($event->percent_discount > 0) {
                                $tire_prices = $tire->$column*((100-$event->percent_discount)/100);
                                $tire_price_origin = ($tire->tire_price*0.75)*((100-$event->percent_discount)/100);
                                $giacongkhai = $tire->tire_price*((100-$event->percent_discount)/100);
                            }
                            else{
                                $tire_prices = $tire->$column-$event->money_discount;
                                $tire_price_origin = ($tire->tire_price*0.75)-$event->money_discount;
                                $giacongkhai = $tire->tire_price-$event->money_discount;
                            }
                        }
                    }

                    //$tire_prices = $tire_prices-($tire_prices*$first_order/100); // Giảm 2% cho đơn mới
                    
                    $chiphi = round($tongchiphi/$tongsoluong)-77000;
                    //$chiphi = $chiphi>0?$chiphi:0;
                    
                    $gia = $dongia-$chiphi;
                    $dongia = $dongia-$chiphi;

                    // Không lấy Hđ
                    if ($order_tire->vat==0) {
                        if ($tire_prices<5000000) {
                            $discount = 160000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }

                    $order_tire_discount[$order_tire->order_tire_id]['thu'] = isset($order_tire_discount[$order_tire->order_tire_id]['thu'])?$order_tire_discount[$order_tire->order_tire_id]['thu']+$gia*$sale->volume:$gia*$sale->volume;
                    $order_tire_discount[$order_tire->order_tire_id]['gia'] = isset($order_tire_discount[$order_tire->order_tire_id]['gia'])?$order_tire_discount[$order_tire->order_tire_id]['gia']+$giacongkhai*$sale->volume:$giacongkhai*$sale->volume;

                    
                    $salary = (($gia-$tire_price_origin)*$arr_salary['phantram']/100)*$sale->volume;
                    if ($dongia < $tire_prices*0.95 || $dongia < $tire_price_origin) {
                        $salary = 0;
                    }
                    else{
                        if ($sale->customer_type==1) {
                            if ($dongia < $tire_prices*0.96) {
                                $salary = $arr_salary['sanluong']*$sale->volume;
                            }
                        }
                        else{
                            if ($dongia < $tire_prices*0.98) {
                                $salary = $arr_salary['sanluong']*$sale->volume;
                            }
                        }

                        if ($salary < $arr_salary['sanluong']*$sale->volume) {
                            $salary = $arr_salary['sanluong']*$sale->volume;
                        }
                    }

                    $info['bonus'][$order_tire->order_tire_id] = isset($info['bonus'][$order_tire->order_tire_id])?$info['bonus'][$order_tire->order_tire_id]+$salary:$salary;
                    //$info['vuotgia'][$order_tire->order_tire_id] = isset($info['vuotgia'][$order_tire->order_tire_id])?$info['vuotgia'][$order_tire->order_tire_id]+($over*$sale->volume):($over*$sale->volume);

                    $info['price'][$order_tire->order_tire_id] = isset($info['price'][$order_tire->order_tire_id])?$info['price'][$order_tire->order_tire_id]+$tire_price_origin*$sale->volume:$tire_price_origin*$sale->volume;
                }

                $info['percent'][$order_tire->order_tire_id] = round($info['bonus'][$order_tire->order_tire_id]/$order_tire->total*100,1);


                
            }
        }

        $this->view->data['info2'] = $info;        

        $this->view->data['order_tires2'] = $order_tires;

        $this->view->data['last_month2'] = $last_month; 
        $this->view->data['this_month2'] = $this_month; 

        $this->view->data['order_tire_discount2'] = $order_tire_discount;


        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('checksalary2/index');
    }
    

}
?>