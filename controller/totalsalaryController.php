<?php
Class totalsalaryController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí nhân sự';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 100;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
            $tu = isset($_POST['tu']) ? $_POST['tu'] : null;
            $den = isset($_POST['den']) ? $_POST['den'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'priority';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngaytao = date('m-Y');
            $trangthai = "600000";
            $nv = "200000";
            $tha = "7000";
            $na = "4000";
            $tu = "2000";
            $den = "4000";
        }

        $trangthai = str_replace(',','',$trangthai);
        $nv = str_replace(',','',$nv);
        $tha = str_replace(',','',$tha);
        $na = str_replace(',','',$na);
        $tu = str_replace(',','',$tu);
        $den = str_replace(',','',$den);
        
        $this->view->data['trangthai'] = str_replace(',','',$trangthai);
        $this->view->data['nv'] = str_replace(',','',$nv);
        $this->view->data['tha'] = str_replace(',','',$tha);
        $this->view->data['na'] = str_replace(',','',$na);
        $this->view->data['tu'] = str_replace(',','',$tu);
        $this->view->data['den'] = str_replace(',','',$den);


        $batdau = '01-'.$ngaytao;
        $ketthuc = date('t-m-Y',strtotime($batdau));

        $tiresale_model = $this->model->get('tiresaleModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $attendance_model = $this->model->get('attendanceModel');
        $phoneallowance_model = $this->model->get('phoneallowanceModel');
        $eating_model = $this->model->get('eatingModel');
        $curricular_model = $this->model->get('curricularModel');
        $position_salary_model = $this->model->get('positionsalaryModel');
        $insurrance_model = $this->model->get('insurranceModel');
        $lift_model = $this->model->get('liftModel');
        $sale_model = $this->model->get('salereportModel');
        $receivable_model = $this->model->get('receivableModel');
        $importtire_model = $this->model->get('importtireModel');

        $x = array(
            'where'=>'pay_money >= money AND pay_date >= '.strtotime($batdau).' AND pay_date <= '.strtotime($ketthuc),
        );
        $receivables = $receivable_model->getAllCosts($x);

        $arr_logs = array();
        $arr_sales = array();
        foreach ($receivables as $re) {
            $arr_sales[$re->sale_report] = isset($arr_sales[$re->sale_report])?$arr_sales[$re->sale_report]+$re->pay_money:$re->pay_money;

            $data = array(
                'where' => 'sale_type = 1 AND sale_report_id = '.$re->sale_report,
            );
            $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

            $sales = $sale_model->getAllSale($data,$join);
            
            foreach ($sales as $sale) {
                if (($sale->revenue_vat+$sale->revenue)==$arr_sales[$sale->sale_report_id] ) {
                    $arr_logs[$sale->staff_id] = isset($arr_logs[$sale->staff_id])?$arr_logs[$sale->staff_id]+(($sale->revenue_vat+$sale->revenue-$sale->cost_vat-$sale->cost-$sale->estimate_cost)*0.5):($sale->revenue_vat+$sale->revenue-$sale->cost_vat-$sale->cost-$sale->estimate_cost)*0.5;
                }
                
            }
        }

        
        $this->view->data['arr_logs'] = $arr_logs;

        $data = array(
            'where' => 'lift_date >= '.strtotime($batdau).' AND lift_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'order_tire','where'=>'order_tire=order_tire_id');
        $lifts = $lift_model->getAllLift($data, $join);
        $arr_lift = array();
        foreach ($lifts as $lift) {
            $gia = $tu*$lift->order_tire_number;
            $support = explode(',', $lift->staff);
            $total = round($gia/count($support));
            foreach ($support as $staff) {
                $arr_lift[$staff] = isset($arr_lift[$staff])?$arr_lift[$staff]+$total:$total;
            }
        }

        $data = array(
            'where' => 'expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

        $imports = $importtire_model->getAllSale($data,$join);
        
        foreach ($imports as $sale) {
            $arr_lift[$sale->staff_id] = isset($arr_lift[$sale->staff_id])?$arr_lift[$sale->staff_id]+1500000:1500000;
        }

        $this->view->data['arr_lift'] = $arr_lift;

        $qr = 'SELECT * FROM (SELECT * FROM phone_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $phones = $phoneallowance_model->queryAllowance($qr);
        $arr_phone = array();
        foreach ($phones as $phone) {
            $arr_phone[$phone->staff] = isset($arr_phone[$phone->staff])?$arr_phone[$phone->staff]+$phone->phone_allowance:$phone->phone_allowance;
        }

        $this->view->data['arr_phone'] = $arr_phone;

        $qr = 'SELECT * FROM (SELECT * FROM insurrance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $insurrances = $insurrance_model->querySalary($qr);
        $arr_insurrance = array();
        foreach ($insurrances as $insurrance) {
            $arr_insurrance[$insurrance->staff]['congty'] = isset($arr_insurrance[$insurrance->staff]['congty'])?$arr_insurrance[$insurrance->staff]['congty']+$insurrance->insurrance:$insurrance->insurrance;
            $arr_insurrance[$insurrance->staff]['nhanvien'] = isset($arr_insurrance[$insurrance->staff]['nhanvien'])?$arr_insurrance[$insurrance->staff]['nhanvien']+$insurrance->insurrance_staff:$insurrance->insurrance_staff;
        }

        $this->view->data['arr_insurrance'] = $arr_insurrance;

        $qr = 'SELECT * FROM (SELECT * FROM position_salary WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $position_salarys = $position_salary_model->querySalary($qr);
        $arr_position_salary = array();
        foreach ($position_salarys as $position_salary) {
            $arr_position_salary[$position_salary->staff] = isset($arr_position_salary[$position_salary->staff])?$arr_position_salary[$position_salary->staff]+$position_salary->position_salary:$position_salary->position_salary;
        }

        $this->view->data['arr_position_salary'] = $arr_position_salary;

        $data = array(
            'where' => 'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc),
        );

        $eatings = $eating_model->getAllEating($data);
        $arr_eating = array();
        foreach ($eatings as $eating) {
            $arr_eating[$eating->staff]['ngay'] = isset($arr_eating[$eating->staff]['ngay'])?$arr_eating[$eating->staff]['ngay']+$eating->eating_day:$eating->eating_day;
            $arr_eating[$eating->staff]['tien'] = isset($arr_eating[$eating->staff]['tien'])?$arr_eating[$eating->staff]['tien']+$eating->eating_total:$eating->eating_total;
            $arr_eating[$eating->staff]['tra'] = isset($arr_eating[$eating->staff]['tra'])?$arr_eating[$eating->staff]['tra']+$eating->eating_staff_total:$eating->eating_staff_total;
        }

        $this->view->data['arr_eating'] = $arr_eating;

        $curriculars = $curricular_model->getAllSalary($data);
        $arr_curricular = array();
        foreach ($curriculars as $curricular) {
            $arr_curricular[$curricular->staff] = isset($arr_curricular[$curricular->staff])?$arr_curricular[$curricular->staff]+$curricular->curricular_salary:$curricular->curricular_salary;
        }

        $this->view->data['arr_curricular'] = $arr_curricular;

        
        $data = array(
            'where' => 'attendance_date >= '.strtotime($batdau).' AND attendance_date <= '.strtotime($ketthuc),
        );
        $attendances = $attendance_model->getAllAttendance($data);
        $arr_attend = array();
        $kpi = 300;
        foreach ($attendances as $attendance) {
            $sophut = 0;
            $diemtru = 0;
            if ($attendance->attendance_day == "Bảy") {
                if (4 >= $attendance->attendance_total) {
                    $sophut = (4 - $attendance->attendance_total)*60;
                }
            }
            else{
                if (8 >= $attendance->attendance_total) {
                    $sophut = (8 - $attendance->attendance_total)*60;
                }
            }
            

            if ($sophut <= 3) {
                $diemtru = 0;
            }
            else if ($sophut > 3 && $sophut <= 15) {
                $diemtru = 1;
            }
            else if ($sophut > 15 && $sophut <= 30) {
                $diemtru = 1.5;
            }
            else if ($sophut > 30 && $sophut <= 60) {
                $diemtru = 3;
            }
            else if ($sophut > 60 && $sophut <= 240) {
                $diemtru = 8;
            }
            else if ($sophut > 240 && $sophut <= 360) {
                $diemtru = 10;
            }
            else if ($sophut > 360) {
                $diemtru = 12;
            }
            
            
            $arr_attend[$attendance->staff] = isset($arr_attend[$attendance->staff])?$arr_attend[$attendance->staff]-$diemtru:$kpi-$diemtru;
        }

        $this->view->data['arr_attend'] = $arr_attend;

        ////////

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

        $cus_arr = array();

        $luong_sp = array();
        $luong_vuotgia = array();

        $old = array();

        

        $check_sale_salary_model = $this->model->get('checksalesalaryModel');

        $check_sale_salarys = $check_sale_salary_model->getAllSalary(array('where'=>'salary_date >= '.strtotime($batdau).' AND salary_date <= '.strtotime($ketthuc)),null);

        if ($check_sale_salarys) {
            foreach ($check_sale_salarys as $checks) {
                $orders = $order_tire_model->getTire($checks->order_tire);
                if ($orders->customer_type == 1) {
                    $sl_tt[$checks->staff] = isset($sl_tt[$checks->staff])?$sl_tt[$checks->staff]+$orders->order_tire_number:$orders->order_tire_number;
                    if ($checks->new_customer == 1) {
                        $tt_moi[$checks->staff] = isset($tt_moi[$checks->staff])?$tt_moi[$checks->staff]+1:1;
                    }
                    else{
                        $tt_cu[$checks->staff] = isset($tt_cu[$checks->staff])?$tt_cu[$checks->staff]+1:1;
                    }
                }
                else{
                    $sl_daily[$checks->staff] = isset($sl_daily[$checks->staff])?$sl_daily[$checks->staff]+$orders->order_tire_number:$orders->order_tire_number;
                    if ($checks->new_customer == 1) {
                        $daily_moi[$checks->staff] = isset($daily_moi[$checks->staff])?$daily_moi[$checks->staff]+1:1;
                    }
                    else{
                        $daily_cu[$checks->staff] = isset($daily_cu[$checks->staff])?$daily_cu[$checks->staff]+1:1;
                    }
                }
                $luong_sp[$checks->staff] = isset($luong_sp[$checks->staff])?$luong_sp[$checks->staff]+$checks->bonus:$checks->bonus;
                $luong_vuotgia[$checks->staff] = isset($luong_vuotgia[$checks->staff])?$luong_vuotgia[$checks->staff]+$checks->bonus_over:$checks->bonus_over;
                $doanhthu[$checks->staff] = isset($doanhthu[$checks->staff])?$doanhthu[$checks->staff]+$orders->total:$orders->total;
            }
        }
        else{
            //////// Đơn hàng tháng trước thanh toán trong tháng

            $data = array(
                'where' => 'tire_sale_date < '.strtotime($batdau).' AND order_tire IN (SELECT order_tire FROM receivable WHERE money <= pay_money AND pay_date >= '.strtotime($batdau).' AND pay_date <= '.strtotime($ketthuc).')',
            );
            $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');

            $sales = $tiresale_model->getAllTire($data,$join);

            $join_order = array('table'=>'staff','where'=>'sale = account');
            $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT tire_sale.order_tire FROM tire_sale,receivable WHERE tire_sale.order_tire=receivable.order_tire AND receivable.money <= receivable.pay_money AND tire_sale_date < '.strtotime($batdau).' AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc).')'),$join_order);
            
            foreach ($orders as $tire) {
                $arr_cost[$tire->order_tire_id] = $tire->order_cost/$tire->order_tire_number;
                $doanhthu[$tire->staff_id] = isset($doanhthu[$tire->staff_id])?$doanhthu[$tire->staff_id]+$tire->total:$tire->total;
                $arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)] = isset($arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)])?$arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)]+$tire->order_tire_number:$tire->order_tire_number;
                $arr_discount[$tire->order_tire_id] = ($tire->discount+$tire->reduce)/$tire->order_tire_number;
                $arr_vat[$tire->order_tire_id] = $tire->vat/$tire->order_tire_number;
                $arr_number[$tire->order_tire_id] = $tire->order_tire_number;
            }

            $data = array(
                'where' => 'tire_sale_date < '.strtotime($batdau).' AND receivable.pay_date >= '.strtotime($batdau).' AND receivable.pay_date <= '.strtotime($ketthuc),
                'order_by' => 'tire_sale_date',
                'order' => 'ASC',
                'limit' => 1,
            );
            $join = array('table'=>'tire_brand,tire_size,tire_pattern,receivable','where'=>'tire_sale.order_tire=receivable.order_tire AND receivable.money <= receivable.pay_money AND tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');

            $s = $tiresale_model->getAllTire($data,$join);
            foreach ($s as $k) {
                $kt = $k->tire_sale_date;
            }

            $data = array(
                'where' => 'tire_sale_date < '.$kt,
            );

            $sale_olds = $tiresale_model->getAllTire($data);

            
            foreach ($sale_olds as $sale) {
                if (!in_array($sale->customer,$old)) {
                    $old[] = $sale->customer;
                }
            }

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
                            $a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                            $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                            if ($a >= 0.95*$b) {
                                if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 20) {
                                    //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*1/100):($sale->volume*$sale->sell_price)*1/100;
                                    $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;
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
                    if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                        if (in_array($sale->customer,$old)) {
                            $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                        }
                        else{
                            if ($arr_number[$sale->order_tire] > 2) {
                                $daily_moi[$sale->sale] = isset($daily_moi[$sale->sale])?$daily_moi[$sale->sale]+1:1;
                            }
                            else{
                                $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                            }
                        }
                    }
                }
                else{
                    $sl_tt[$sale->sale] = isset($sl_tt[$sale->sale])?$sl_tt[$sale->sale]+$sale->volume:$sale->volume;
                    if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                        if (in_array($sale->customer,$old)) {
                            $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                        }
                        else{
                            if ($arr_number[$sale->order_tire] > 2) {
                                $tt_moi[$sale->sale] = isset($tt_moi[$sale->sale])?$tt_moi[$sale->sale]+1:1;
                            }
                            else{
                                $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                            }
                        }
                    }
                }

                $cus_arr[$sale->sale][] = $sale->order_tire;
            }



            ///////// Đơn hàng trong tháng đã thanh toán

            $data = array(
                'where' => 'tire_sale_date < '.strtotime($batdau),
            );

            $sale_olds = $tiresale_model->getAllTire($data);

            
            foreach ($sale_olds as $sale) {
                if (!in_array($sale->customer,$old)) {
                    $old[] = $sale->customer;
                }
            }


            $data = array(
                'where' => 'tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).' AND order_tire IN (SELECT order_tire FROM receivable WHERE money <= pay_money AND pay_date >= '.strtotime($batdau).' AND pay_date <= '.strtotime($ketthuc).')',
            );
            $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');

            $sales = $tiresale_model->getAllTire($data,$join);

            $join_order = array('table'=>'staff','where'=>'sale = account');
            $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'),$join_order);
            
            foreach ($orders as $tire) {
                $arr_cost[$tire->order_tire_id] = $tire->order_cost/$tire->order_tire_number;
                $doanhthu[$tire->staff_id] = isset($doanhthu[$tire->staff_id])?$doanhthu[$tire->staff_id]+$tire->total:$tire->total;
                $arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)] = isset($arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)])?$arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)]+$tire->order_tire_number:$tire->order_tire_number;
                $arr_discount[$tire->order_tire_id] = ($tire->discount+$tire->reduce)/$tire->order_tire_number;
                $arr_vat[$tire->order_tire_id] = $tire->vat/$tire->order_tire_number;
                $arr_number[$tire->order_tire_id] = $tire->order_tire_number;
            }

            

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
                            $a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                            $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                            if ($a >= 0.95*$b) {
                                if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 20) {
                                    //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*1/100):($sale->volume*$sale->sell_price)*1/100;
                                    $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;
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
                    if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                        if (in_array($sale->customer,$old)) {
                            $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                        }
                        else{
                            if ($arr_number[$sale->order_tire] > 2) {
                                $daily_moi[$sale->sale] = isset($daily_moi[$sale->sale])?$daily_moi[$sale->sale]+1:1;
                            }
                            else{
                                $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                            }
                        }
                    }
                }
                else{
                    $sl_tt[$sale->sale] = isset($sl_tt[$sale->sale])?$sl_tt[$sale->sale]+$sale->volume:$sale->volume;
                    if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                        if (in_array($sale->customer,$old)) {
                            $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                        }
                        else{
                            if ($arr_number[$sale->order_tire] > 2) {
                                $tt_moi[$sale->sale] = isset($tt_moi[$sale->sale])?$tt_moi[$sale->sale]+1:1;
                            }
                            else{
                                $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                            }
                        }
                    }
                }

                $cus_arr[$sale->sale][] = $sale->order_tire;
            }
        }

        $this->view->data['sl_daily'] = $sl_daily;
        $this->view->data['sl_tt'] = $sl_tt;
        $this->view->data['daily_cu'] = $daily_cu;
        $this->view->data['daily_moi'] = $daily_moi;
        $this->view->data['tt_moi'] = $tt_moi;
        $this->view->data['tt_cu'] = $tt_cu;

        $this->view->data['luong_sp'] = $luong_sp;
        $this->view->data['luong_vuotgia'] = $luong_vuotgia;

        $this->view->data['doanhthu'] = $doanhthu;


        $staff_model = $this->model->get('staffModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array();
        $data = array(
            'where' => '(start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) )',
        );

        
        $tongsodong = count($staff_model->getAllStaff($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) )',
            );

        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $this->view->data['staffs'] = $staff_model->getAllStaff($data,$join);
        $this->view->data['lastID'] = isset($staff_model->getLastStaff()->staff_id)?$staff_model->getLastStaff()->staff_id:0;

        $this->view->show('totalsalary/index');
    }

    public function temp() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lương dự kiến';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 100;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
            $tu = isset($_POST['tu']) ? $_POST['tu'] : null;
            $den = isset($_POST['den']) ? $_POST['den'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'priority';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngaytao = date('m-Y');
            $trangthai = "600000";
            $nv = "200000";
            $tha = "7000";
            $na = "4000";
            $tu = "2000";
            $den = "4000";
        }

        $trangthai = str_replace(',','',$trangthai);
        $nv = str_replace(',','',$nv);
        $tha = str_replace(',','',$tha);
        $na = str_replace(',','',$na);
        $tu = str_replace(',','',$tu);
        $den = str_replace(',','',$den);
        
        $this->view->data['trangthai'] = str_replace(',','',$trangthai);
        $this->view->data['nv'] = str_replace(',','',$nv);
        $this->view->data['tha'] = str_replace(',','',$tha);
        $this->view->data['na'] = str_replace(',','',$na);
        $this->view->data['tu'] = str_replace(',','',$tu);
        $this->view->data['den'] = str_replace(',','',$den);


        $batdau = '01-'.$ngaytao;
        $ketthuc = date('t-m-Y',strtotime($batdau));

        $tiresale_model = $this->model->get('tiresaleModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $attendance_model = $this->model->get('attendanceModel');
        $phoneallowance_model = $this->model->get('phoneallowanceModel');
        $eating_model = $this->model->get('eatingModel');
        $curricular_model = $this->model->get('curricularModel');
        $position_salary_model = $this->model->get('positionsalaryModel');
        $insurrance_model = $this->model->get('insurranceModel');
        $lift_model = $this->model->get('liftModel');
        $sale_model = $this->model->get('salereportModel');
        $receivable_model = $this->model->get('receivableModel');
        $importtire_model = $this->model->get('importtireModel');

        $arr_logs = array();
        $data = array(
            'where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

        $sales = $sale_model->getAllSale($data,$join);
        
        foreach ($sales as $sale) {
            $arr_logs[$sale->staff_id] = isset($arr_logs[$sale->staff_id])?$arr_logs[$sale->staff_id]+(($sale->revenue_vat+$sale->revenue-$sale->cost_vat-$sale->cost-$sale->estimate_cost)*0.5):($sale->revenue_vat+$sale->revenue-$sale->cost_vat-$sale->cost-$sale->estimate_cost)*0.5;   
        }

        
        $this->view->data['arr_logs'] = $arr_logs;

        $data = array(
            'where' => 'lift_date >= '.strtotime($batdau).' AND lift_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'order_tire','where'=>'order_tire=order_tire_id');
        $lifts = $lift_model->getAllLift($data, $join);
        $arr_lift = array();
        foreach ($lifts as $lift) {
            $gia = $tu*$lift->order_tire_number;
            $support = explode(',', $lift->staff);
            $total = round($gia/count($support));
            foreach ($support as $staff) {
                $arr_lift[$staff] = isset($arr_lift[$staff])?$arr_lift[$staff]+$total:$total;
            }
        }

        $data = array(
            'where' => 'expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

        $imports = $importtire_model->getAllSale($data,$join);
        
        foreach ($imports as $sale) {
            $arr_lift[$sale->staff_id] = isset($arr_lift[$sale->staff_id])?$arr_lift[$sale->staff_id]+1500000:1500000;
        }

        $this->view->data['arr_lift'] = $arr_lift;

        $qr = 'SELECT * FROM (SELECT * FROM phone_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $phones = $phoneallowance_model->queryAllowance($qr);
        $arr_phone = array();
        foreach ($phones as $phone) {
            $arr_phone[$phone->staff] = isset($arr_phone[$phone->staff])?$arr_phone[$phone->staff]+$phone->phone_allowance:$phone->phone_allowance;
        }

        $this->view->data['arr_phone'] = $arr_phone;

        $qr = 'SELECT * FROM (SELECT * FROM insurrance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $insurrances = $insurrance_model->querySalary($qr);
        $arr_insurrance = array();
        foreach ($insurrances as $insurrance) {
            $arr_insurrance[$insurrance->staff]['congty'] = isset($arr_insurrance[$insurrance->staff]['congty'])?$arr_insurrance[$insurrance->staff]['congty']+$insurrance->insurrance:$insurrance->insurrance;
            $arr_insurrance[$insurrance->staff]['nhanvien'] = isset($arr_insurrance[$insurrance->staff]['nhanvien'])?$arr_insurrance[$insurrance->staff]['nhanvien']+$insurrance->insurrance_staff:$insurrance->insurrance_staff;
        }

        $this->view->data['arr_insurrance'] = $arr_insurrance;

        $qr = 'SELECT * FROM (SELECT * FROM position_salary WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $position_salarys = $position_salary_model->querySalary($qr);
        $arr_position_salary = array();
        foreach ($position_salarys as $position_salary) {
            $arr_position_salary[$position_salary->staff] = isset($arr_position_salary[$position_salary->staff])?$arr_position_salary[$position_salary->staff]+$position_salary->position_salary:$position_salary->position_salary;
        }

        $this->view->data['arr_position_salary'] = $arr_position_salary;

        $data = array(
            'where' => 'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc),
        );

        $eatings = $eating_model->getAllEating($data);
        $arr_eating = array();
        foreach ($eatings as $eating) {
            $arr_eating[$eating->staff]['ngay'] = isset($arr_eating[$eating->staff]['ngay'])?$arr_eating[$eating->staff]['ngay']+$eating->eating_day:$eating->eating_day;
            $arr_eating[$eating->staff]['tien'] = isset($arr_eating[$eating->staff]['tien'])?$arr_eating[$eating->staff]['tien']+$eating->eating_total:$eating->eating_total;
            $arr_eating[$eating->staff]['tra'] = isset($arr_eating[$eating->staff]['tra'])?$arr_eating[$eating->staff]['tra']+$eating->eating_staff_total:$eating->eating_staff_total;
        }

        $this->view->data['arr_eating'] = $arr_eating;

        $curriculars = $curricular_model->getAllSalary($data);
        $arr_curricular = array();
        foreach ($curriculars as $curricular) {
            $arr_curricular[$curricular->staff] = isset($arr_curricular[$curricular->staff])?$arr_curricular[$curricular->staff]+$curricular->curricular_salary:$curricular->curricular_salary;
        }

        $this->view->data['arr_curricular'] = $arr_curricular;

        
        $data = array(
            'where' => 'attendance_date >= '.strtotime($batdau).' AND attendance_date <= '.strtotime($ketthuc),
        );
        $attendances = $attendance_model->getAllAttendance($data);
        $arr_attend = array();
        $kpi = 300;
        foreach ($attendances as $attendance) {
            $sophut = 0;
            $diemtru = 0;
            if ($attendance->attendance_day == "Bảy") {
                if (4 >= $attendance->attendance_total) {
                    $sophut = (4 - $attendance->attendance_total)*60;
                }
            }
            else{
                if (8 >= $attendance->attendance_total) {
                    $sophut = (8 - $attendance->attendance_total)*60;
                }
            }
            

            if ($sophut <= 3) {
                $diemtru = 0;
            }
            else if ($sophut > 3 && $sophut <= 15) {
                $diemtru = 1;
            }
            else if ($sophut > 15 && $sophut <= 30) {
                $diemtru = 1.5;
            }
            else if ($sophut > 30 && $sophut <= 60) {
                $diemtru = 3;
            }
            else if ($sophut > 60 && $sophut <= 240) {
                $diemtru = 8;
            }
            else if ($sophut > 240 && $sophut <= 360) {
                $diemtru = 10;
            }
            else if ($sophut > 360) {
                $diemtru = 12;
            }
            
            
            $arr_attend[$attendance->staff] = isset($arr_attend[$attendance->staff])?$arr_attend[$attendance->staff]-$diemtru:$kpi-$diemtru;
        }

        $this->view->data['arr_attend'] = $arr_attend;

        $thangtruoc1 = date("d-m-Y", strtotime("-1 month", strtotime($batdau)));
        $thangtruoc2 = date("d-m-Y", strtotime("-1 month", strtotime($ketthuc)));

        $data = array(
            'where' => 'attendance_date >= '.strtotime($thangtruoc1).' AND attendance_date <= '.strtotime($thangtruoc2),
        );
        $attendances = $attendance_model->getAllAttendance($data);
        $arr_attend_temp = array();
        $kpi = 300;
        foreach ($attendances as $attendance) {
            $sophut = 0;
            $diemtru = 0;
            if ($attendance->attendance_day == "Bảy") {
                if (4 >= $attendance->attendance_total) {
                    $sophut = (4 - $attendance->attendance_total)*60;
                }
            }
            else{
                if (8 >= $attendance->attendance_total) {
                    $sophut = (8 - $attendance->attendance_total)*60;
                }
            }
            

            if ($sophut <= 3) {
                $diemtru = 0;
            }
            else if ($sophut > 3 && $sophut <= 15) {
                $diemtru = 1;
            }
            else if ($sophut > 15 && $sophut <= 30) {
                $diemtru = 1.5;
            }
            else if ($sophut > 30 && $sophut <= 60) {
                $diemtru = 3;
            }
            else if ($sophut > 60 && $sophut <= 240) {
                $diemtru = 8;
            }
            else if ($sophut > 240 && $sophut <= 360) {
                $diemtru = 10;
            }
            else if ($sophut > 360) {
                $diemtru = 12;
            }
            
            
            $arr_attend_temp[$attendance->staff] = isset($arr_attend_temp[$attendance->staff])?$arr_attend_temp[$attendance->staff]-$diemtru:$kpi-$diemtru;
        }

        $this->view->data['arr_attend_temp'] = $arr_attend_temp;

        ////////

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

        $cus_arr = array();

        $luong_sp = array();
        $luong_vuotgia = array();

        $old = array();

        

        ///////// Đơn hàng trong tháng đã thanh toán

        $data = array(
            'where' => 'tire_sale_date < '.strtotime($batdau),
        );

        $sale_olds = $tiresale_model->getAllTire($data);

        
        foreach ($sale_olds as $sale) {
            if (!in_array($sale->customer,$old)) {
                $old[] = $sale->customer;
            }
        }


        $data = array(
            'where' => 'tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');

        $sales = $tiresale_model->getAllTire($data,$join);

        $join_order = array('table'=>'staff','where'=>'sale = account');
        $orders = $order_tire_model->getAllTire(array('where'=>'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc)),$join_order);
        
        foreach ($orders as $tire) {
            $arr_cost[$tire->order_tire_id] = $tire->order_cost/$tire->order_tire_number;
            $doanhthu[$tire->staff_id] = isset($doanhthu[$tire->staff_id])?$doanhthu[$tire->staff_id]+$tire->total:$tire->total;
            $arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)] = isset($arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)])?$arr_customer[$tire->customer][date('d-m-Y',$tire->delivery_date)]+$tire->order_tire_number:$tire->order_tire_number;
            $arr_discount[$tire->order_tire_id] = ($tire->discount+$tire->reduce)/$tire->order_tire_number;
            $arr_vat[$tire->order_tire_id] = $tire->vat/$tire->order_tire_number;
            $arr_number[$tire->order_tire_id] = $tire->order_tire_number;
        }

        

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
                        $a = $sale->volume*($sale->sell_price - $phi + 6000 - $ck);
                        $b = $tire_prices[$tire_brand_name][$sale->tire_size_number][$pt_type[$l]]*$sale->volume;
                        if ($a >= 0.95*$b) {
                            if ($arr_customer[$sale->customer][date('d-m-Y',$sale->tire_sale_date)] >= 20) {
                                //$luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*$sale->sell_price)*1/100):($sale->volume*$sale->sell_price)*1/100;
                                $luong_sp[$sale->sale] = isset($luong_sp[$sale->sale])?$luong_sp[$sale->sale]+(($sale->volume*($sale->sell_price+$va-$ck))*1/100):($sale->volume*($sale->sell_price+$va-$ck))*1/100;
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
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                    }
                    else{
                        if ($arr_number[$sale->order_tire] > 2) {
                            $daily_moi[$sale->sale] = isset($daily_moi[$sale->sale])?$daily_moi[$sale->sale]+1:1;
                        }
                        else{
                            $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                        }
                    }
                }
            }
            else{
                $sl_tt[$sale->sale] = isset($sl_tt[$sale->sale])?$sl_tt[$sale->sale]+$sale->volume:$sale->volume;
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->order_tire,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                    }
                    else{
                        if ($arr_number[$sale->order_tire] > 2) {
                            $tt_moi[$sale->sale] = isset($tt_moi[$sale->sale])?$tt_moi[$sale->sale]+1:1;
                        }
                        else{
                            $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                        }
                    }
                }
            }

            $cus_arr[$sale->sale][] = $sale->order_tire;
        }

        $this->view->data['sl_daily'] = $sl_daily;
        $this->view->data['sl_tt'] = $sl_tt;
        $this->view->data['daily_cu'] = $daily_cu;
        $this->view->data['daily_moi'] = $daily_moi;
        $this->view->data['tt_moi'] = $tt_moi;
        $this->view->data['tt_cu'] = $tt_cu;

        $this->view->data['luong_sp'] = $luong_sp;
        $this->view->data['luong_vuotgia'] = $luong_vuotgia;

        $this->view->data['doanhthu'] = $doanhthu;


        $staff_model = $this->model->get('staffModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array();
        $data = array(
            'where' => '(start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) )',
        );

        
        $tongsodong = count($staff_model->getAllStaff($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) )',
            );

        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $this->view->data['staffs'] = $staff_model->getAllStaff($data,$join);
        $this->view->data['lastID'] = isset($staff_model->getLastStaff()->staff_id)?$staff_model->getLastStaff()->staff_id:0;

        $this->view->show('totalsalary/temp');
    }

    public function ngoaikhoa(){

        

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if (isset($_POST['data'])) {

            $batdau = '01-'.$_POST['thang'];
            $ketthuc = date('t-m-Y',strtotime($batdau));

            $curricular = $this->model->get('curricularModel');

            $curricular_data = $curricular->getAllSalary(array('where'=>'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' AND staff = '.$_POST['data']));

            $data = array(
                'staff' => $_POST['data'],
                'create_time' => strtotime($batdau),
                'curricular_salary' => trim(str_replace(',','',$_POST['keyword'])),
                );

            if (!$curricular_data) {
                $curricular->createSalary($data);
            }
            else{
                $curricular->querySalary('UPDATE curricular SET curricular_salary = '.trim(str_replace(',','',$_POST['keyword'])).' WHERE staff = '.$_POST['data'].' AND create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc));
                
            }



            date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."curricular"."|".$data."|curricular|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



            return true;

                    

        }

    }

    public function chucvu(){

        

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if (isset($_POST['data'])) {

            $batdau = '01-'.$_POST['thang'];
            $ketthuc = date('t-m-Y',strtotime($batdau));

            $position_salary = $this->model->get('positionsalaryModel');

            $position_salary_data = $position_salary->getAllSalary(array('where'=>'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' AND staff = '.$_POST['data']));

            $data = array(
                'staff' => $_POST['data'],
                'create_time' => strtotime($batdau),
                'position_salary' => trim(str_replace(',','',$_POST['keyword'])),
                );

            if (!$position_salary_data) {
                $position_salary->createSalary($data);
            }
            else{
                $position_salary->querySalary('UPDATE position_salary SET position_salary = '.trim(str_replace(',','',$_POST['keyword'])).' WHERE staff = '.$_POST['data'].' AND create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc));
                
            }



            date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."position_salary"."|".$data."|position_salary|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



            return true;

                    

        }

    }


    public function getstaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff_model = $this->model->get('staffModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $staff_model->getAllStaff();
            }
            else{
                $data = array(
                'where'=>'( staff_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $staff_model->getAllStaff($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $staff_name = $rs->staff_name;
                if ($_POST['keyword'] != "*") {
                    $staff_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->staff_name);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->staff_name.'\',\''.$rs->staff_id.'\',\''.$rs->staff_code.'\',\''.$rs->staff_birth.'\',\''.$rs->staff_gender.'\')">'.$staff_name.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $salary = $this->model->get('totalsalaryModel');
            $pending_costs = $this->model->get('pendingcostsModel');
            $data = array(
                        
                        'basic_salary' => trim(str_replace(',','',$_POST['basic_salary'])),
                        'work_day' => trim(str_replace(',','',$_POST['work_day'])),
                        'salary' => trim(str_replace(',','',$_POST['salary'])),
                        'insurance_allowance' => trim(str_replace(',','',$_POST['insurance_allowance'])),
                        'phone_allowance' => trim(str_replace(',','',$_POST['phone_allowance'])),
                        'eating_allowance' => trim(str_replace(',','',$_POST['eating_allowance'])),
                        'effect_add' => trim(str_replace(',','',$_POST['effect_add'])),
                        'time_add' => trim(str_replace(',','',$_POST['time_add'])),
                        'graded' => trim(str_replace(',','',$_POST['graded'])),
                        'diligence_add' => trim(str_replace(',','',$_POST['diligence_add'])),
                        'culture' => trim(str_replace(',','',$_POST['culture'])),
                        'insurance_deduct' => trim(str_replace(',','',$_POST['insurance_deduct'])),
                        'total_salary' => trim(str_replace(',','',$_POST['total_salary'])),
                        'total_detruct' => trim(str_replace(',','',$_POST['total_detruct'])),
                        'total' => trim(str_replace(',','',$_POST['total'])),
                        'create_time' => strtotime('01-'.$_POST['create_time']),
                        'create_user' => $_SESSION['userid_logined'],
                        
                        );

            
            if ($_POST['staff'] == "") {
                //var_dump($data);
                $salary_data = $salary->getSalary($_POST['yes']);

                $salary->updateSalary($data,array('new_salary_id' => $_POST['yes']));
                echo "Cập nhật thành công";

                if (!$pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])))) {
                    $pending_costs_data = array(
                        'pending_costs_date' => strtotime('01-'.$_POST['create_time']),
                        'pending_costs_money' => $data['total_salary']-$data['total_detruct'],
                        'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime('01-'.$_POST['create_time'])),
                    );
                    $pending_costs->createCosts($pending_costs_data);
                }
                else if ($pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])))) {
                    $pending_data = $pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])));
                    $id_pending = $pending_data->pending_costs_id;
                    $pending_costs_money = $pending_data->pending_costs_money;

                    $pending_costs_data = array(
                        'pending_costs_date' => strtotime('01-'.$_POST['create_time']),
                        'pending_costs_money' => $pending_costs_money-($salary_data->total_salary-$salary_data->total_detruct)+($data['total_salary']-$data['total_detruct']),
                        'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime('01-'.$_POST['create_time'])),
                    );
                    $pending_costs->updateCosts($pending_costs_data,array('pending_costs_id'=>$id_pending));
                }

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|totalsalary|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                $data['staff'] = trim($_POST['staff']);
                //var_dump($data);
                if ($salary->getSalaryByWhere(array('staff'=>trim($_POST['staff']),'create_time' => trim($_POST['create_time'])))) {
                    echo "Bảng lương này đã tồn tại";
                    return false;
                }
                else{
                    $salary->createSalary($data);
                    echo "Thêm thành công";

                    if (!$pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])))) {
                        $pending_costs_data = array(
                            'pending_costs_date' => strtotime('01-'.$_POST['create_time']),
                            'pending_costs_money' => $data['total_salary']-$data['total_detruct'],
                            'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime('01-'.$_POST['create_time'])),
                        );
                        $pending_costs->createCosts($pending_costs_data);
                    }
                    else if ($pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])))) {
                        $pending_data = $pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime('01-'.$_POST['create_time'])));
                        $id_pending = $pending_data->pending_costs_id;
                        $pending_costs_money = $pending_data->pending_costs_money;

                        $pending_costs_data = array(
                            'pending_costs_date' => strtotime('01-'.$_POST['create_time']),
                            'pending_costs_money' => $pending_costs_money+($data['total_salary']-$data['total_detruct']),
                            'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime('01-'.$_POST['create_time'])),
                        );
                        $pending_costs->updateCosts($pending_costs_data,array('pending_costs_id'=>$id_pending));
                    }


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$salary->getLastSalary()->new_salary_id."|totalsalary|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }

    public function approve(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $salary = $this->model->get('totalsalaryModel');
            $costs = $this->model->get('costsModel');
            $staff = $this->model->get('staffModel');
            $salary_data = $salary->getSalary($_POST['data']);
            $staff_data = $staff->getStaff($salary_data->staff);

            $data_cost = array(
                'costs_create_date' => strtotime(date('d-m-Y H:i:s')),
                'costs_date' => strtotime(date('d-m-Y')),
                'expect_date' => strtotime('15-'.date("m-Y", strtotime("+1 month", $salary_data->create_time))),
                'week' => (int)date('W', strtotime('15-'.date("m-Y", strtotime("+1 month", $salary_data->create_time)))),
                'create_user' => $salary_data->create_user,
                'source' => 1,
                'year' => (int)date('Y',strtotime('15-'.date("m-Y", strtotime("+1 month", $salary_data->create_time)))),
                'staff' => -1,
                'staff_cost' => 0,
                'check_office' => 1,
                'check_equipment' => 0,
                'check_entertainment' => 0,
                'check_energy' => 0,
                'check_other' => 0,
                'check_salary' => 0,
                'check_phone' => 0,
                'check_eating' => 0,
                'check_insurance' => 0,
                'check_bonus' => 0,
                'approve' => $_SESSION['userid_logined'],
            );

            if ($salary_data->total>0) {
                $data_cost['check_salary'] = 1;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền lương tháng '.date('m-Y',$salary_data->create_time).' '.$staff_data->staff_name;
                $data_cost['money'] = $salary_data->total-($salary_data->effect_add+$salary_data->time_add+$salary_data->diligence_add);

                $costs->createCosts($data_cost);
            }
            if ($salary_data->phone_allowance>0) {
                $data_cost['check_phone'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền điện thoại tháng '.date('m-Y',$salary_data->create_time).' '.$staff_data->staff_name;
                $data_cost['money'] = $salary_data->phone_allowance;

                $costs->createCosts($data_cost);
            }
            if ($salary_data->eating_allowance>0) {
                $data_cost['check_eating'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền cơm tháng '.date('m-Y',$salary_data->create_time).' '.$staff_data->staff_name;
                $data_cost['money'] = $salary_data->eating_allowance;

                $costs->createCosts($data_cost);
            }
            if (($salary_data->insurance_allowance)>0) {
                $data_cost['check_insurance'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền bảo hiểm tháng '.date('m-Y',$salary_data->create_time).' '.$staff_data->staff_name;
                $data_cost['money'] = $salary_data->insurance_allowance+$salary_data->insurance_deduct;

                $costs->createCosts($data_cost);
            }
            if (($salary_data->effect_add+$salary_data->time_add+$salary_data->diligence_add)>0) {
                $data_cost['check_bonus'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['comment'] = 'Thưởng tháng '.date('m-Y',$salary_data->create_time).' '.$staff_data->staff_name;
                $data_cost['money'] = $salary_data->effect_add+$salary_data->time_add+$salary_data->diligence_add;

                $costs->createCosts($data_cost);
            }

            $data = array(
                        
                        'approve' => 1,
                        );
          
            $salary->updateSalary($data,array('new_salary_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|salary|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approveall(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $salary = $this->model->get('totalsalaryModel');
            $costs = $this->model->get('costsModel');

            //$costs_data = $costs->getCosts($_POST['data']);
            $salarys = $salary->getAllSalary(array('where'=>'create_time='.strtotime($_POST['data'])));

            $total_salary = 0; $total_phone = 0; $total_eating = 0; $total_insurance = 0; $total_bonus = 0;
            foreach ($salarys as $salary_data) {
                $total_salary += $salary_data->total; 
                $total_phone += $salary_data->phone_allowance; 
                $total_eating += $salary_data->eating_allowance; 
                $total_insurance += $salary_data->insurance_allowance+$salary_data->insurance_deduct; 
                $total_bonus += $salary_data->effect_add+$salary_data->time_add+$salary_data->diligence_add;
            }

            $data_cost = array(
                'costs_create_date' => strtotime(date('d-m-Y H:i:s')),
                'costs_date' => strtotime(date('d-m-Y')),
                'expect_date' => strtotime('15-'.date("m-Y", strtotime("+1 month", strtotime($_POST['data'])))),
                'week' => (int)date('W', strtotime('15-'.date("m-Y", strtotime("+1 month", strtotime($_POST['data']))))),
                'create_user' => 1,
                'source' => 1,
                'year' => (int)date('Y',strtotime('15-'.date("m-Y", strtotime("+1 month", strtotime($_POST['data']))))),
                'staff' => -1,
                'staff_cost' => 0,
                'check_office' => 1,
                'check_equipment' => 0,
                'check_entertainment' => 0,
                'check_energy' => 0,
                'check_other' => 0,
                'check_salary' => 0,
                'check_phone' => 0,
                'check_eating' => 0,
                'check_insurance' => 0,
                'check_bonus' => 0,
                'approve' => $_SESSION['userid_logined'],
            );

            if ($total_salary>0) {
                $data_cost['check_salary'] = 1;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền lương tháng '.date('m-Y',strtotime($_POST['data']));
                $data_cost['money'] = $total_salary;

                $costs->createCosts($data_cost);
            }
            if ($total_phone>0) {
                $data_cost['check_phone'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền điện thoại tháng '.date('m-Y',strtotime($_POST['data']));
                $data_cost['money'] = $total_phone;

                $costs->createCosts($data_cost);
            }
            if ($total_eating>0) {
                $data_cost['check_eating'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền cơm tháng '.date('m-Y',strtotime($_POST['data']));
                $data_cost['money'] = $total_eating;

                $costs->createCosts($data_cost);
            }
            if ($total_insurance>0) {
                $data_cost['check_insurance'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_bonus'] = 0;
                $data_cost['comment'] = 'Thanh toán tiền bảo hiểm tháng '.date('m-Y',strtotime($_POST['data']));
                $data_cost['money'] = $total_insurance;

                $costs->createCosts($data_cost);
            }
            if ($total_bonus>0) {
                $data_cost['check_bonus'] = 1;
                $data_cost['check_salary'] = 0;
                $data_cost['check_phone'] = 0;
                $data_cost['check_eating'] = 0;
                $data_cost['check_insurance'] = 0;
                $data_cost['comment'] = 'Thưởng tháng '.date('m-Y',strtotime($_POST['data']));
                $data_cost['money'] = $total_bonus;

                $costs->createCosts($data_cost);
            }

            $data = array(
                        
                        'approve' => 1,
                        );
          
            $salary->updateSalary($data,array('create_time' => strtotime($_POST['data'])));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|salary|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $salary = $this->model->get('totalsalaryModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $salary->deleteSalary($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|totalsalary|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|totalsalary|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $salary->deleteSalary($_POST['data']);
            }
            
        }
    }

    function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $ngaytao = $this->registry->router->param_id;
        
        $salary_model = $this->model->get('totalsalaryModel');

        $join = array('table'=>'staff','where'=>'new_salary.staff = staff.staff_id');
        $data = array(
            'where' => 'create_time ='.$ngaytao,
        );

        $salarys = $salary_model->getAllSalary($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'BẢNG CHI PHÍ NHÂN SỰ THÁNG '.$ngaytao)
               
               ->setCellValue('A3', 'STT')
               ->setCellValue('B3', 'HỌ TÊN')
               ->setCellValue('c3', 'LƯƠNG CỨNG')
               ->setCellValue('C4', 'LƯƠNG CB')
               ->setCellValue('D4', 'NGÀY CÔNG')
               ->setCellValue('E4', 'LƯƠNG')
               ->setCellValue('F3', 'PHỤ CẤP')
               ->setCellValue('F4', 'ĐIỆN THOẠI')
               ->setCellValue('G4', 'CƠM')
               ->setCellValue('H4', 'BẢO HIỂM DN')
               ->setCellValue('I4', 'BẢO HIỂM CN')
               ->setCellValue('J3', 'CỘNG')
               ->setCellValue('J4', 'HIỆU QUẢ')
               ->setCellValue('K4', 'THỜI GIAN')
               ->setCellValue('L4', 'CHUYÊN CẦN')
               ->setCellValue('M3', 'KHẤU TRỪ')
               ->setCellValue('M4', 'XẾP LOẠI')
               ->setCellValue('N4', 'VĂN HÓA')
               ->setCellValue('O3', 'TỔNG CỘNG')
               ->setCellValue('O4', 'TỔNG CHI')
               ->setCellValue('P4', 'THỰC LĨNH');
               


            if ($salarys) {

                $hang = 5;
                $i=1;

                foreach ($salarys as $row) {
                    
                    


                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, $row->staff_name)
                        ->setCellValue('C' . $hang, $row->basic_salary)
                        ->setCellValue('D' . $hang, $row->work_day)
                        ->setCellValue('E' . $hang, $row->salary)
                        ->setCellValue('F' . $hang, $row->phone_allowance)
                        ->setCellValue('G' . $hang, $row->eating_allowance)
                        ->setCellValue('H' . $hang, $row->insurance_allowance)
                        ->setCellValue('I' . $hang, $row->insurance_deduct)
                        ->setCellValue('J' . $hang, $row->effect_add)
                        ->setCellValue('K' . $hang, $row->time_add)
                        ->setCellValue('L' . $hang, $row->diligence_add)
                        ->setCellValue('M' . $hang, $row->graded)
                        ->setCellValue('N' . $hang, $row->culture)
                        ->setCellValue('O' . $hang, '=SUM(E'.$hang.':H'.$hang.')+SUM(J'.$hang.':L'.$hang.')-SUM(M'.$hang.':N'.$hang.')')
                        ->setCellValue('P' . $hang, '=E'.$hang.'+SUM(J'.$hang.':L'.$hang.')-SUM(M'.$hang.':N'.$hang.')-I'.$hang);
                     $hang++;


                  }


          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:P1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:P2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:A4');
            $objPHPExcel->getActiveSheet()->mergeCells('B3:B4');
            $objPHPExcel->getActiveSheet()->mergeCells('C3:E3');
            $objPHPExcel->getActiveSheet()->mergeCells('F3:I3');
            $objPHPExcel->getActiveSheet()->mergeCells('J3:L3');
            $objPHPExcel->getActiveSheet()->mergeCells('M3:N3');
            $objPHPExcel->getActiveSheet()->mergeCells('O3:P3');

            $objPHPExcel->getActiveSheet()->getStyle('A1:P'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:P'.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
             $objPHPExcel->getActiveSheet()->getStyle('A1:P4')->getFont()->setBold(true);

            
            $objPHPExcel->getActiveSheet()->getStyle('C5:P'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("CMG")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Salary Report")
                            ->setSubject("Salary Report")
                            ->setDescription("Salary Report.")
                            ->setKeywords("Salary Report")
                            ->setCategory("Salary Report");
            $objPHPExcel->getActiveSheet()->setTitle("Chi phi nhan su");

            $objPHPExcel->getActiveSheet()->freezePane('A5');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= CHI PHÍ NHÂN SỰ.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    public function view() {
        
        $this->view->show('salary/view');
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $staff = $this->model->get('staffModel');
            $salary = $this->model->get('totalsalaryModel');
            $pending_costs = $this->model->get('pendingcostsModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            $nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = (strlen($day[0]) < 2 ? "0".$day[0] : $day[0] )."-".$day[1] ;
            $ngaythang = '01-'.$ngaythang;

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            $tongchiphi = 0;

            $y = 0;

            for ($row = 4; $row <= $highestRow; ++ $row) {
                $val = array();
                for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                    $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                    // Check if cell is merged
                    foreach ($objWorksheet->getMergeCells() as $cells) {
                        if ($cell->isInRange($cells)) {
                            $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                            $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                            if ($col == 1) {
                                $y++;
                            }
                            
                            break;
                            
                        }
                    }

                    $val[] = $cell->getCalculatedValue();
                    //here's my prob..
                    //echo $val;
                }

                $tongchiphi += trim($val[14])-trim($val[15]);

                if ($val[1] != null) {
                    //var_dump($val[11]);die();
                    if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[1])))){
                        $staff_data = array(
                            'staff_name' => trim($val[1]),
                            );
                        $staff->createStaff($staff_data);

                        $id_staff = $staff->getLastStaff()->staff_id;
                        
                            $salary_data = array(
                                'staff' => trim($id_staff),
                                'basic_salary' => trim($val[2]),
                                'work_day' => trim($val[3]),
                                'salary' => trim($val[4]),
                                'phone_allowance' => trim($val[5]),
                                'eating_allowance' => trim($val[6]),
                                'insurance_allowance' => trim($val[7]),
                                'effect_add' => trim($val[8]),
                                'time_add' => trim($val[9]),
                                'diligence_add' => trim($val[10]),
                                'graded' => trim($val[11]),
                                'culture' => trim($val[12]),
                                'insurance_deduct' => trim($val[13]),
                                'total_salary' => trim($val[14]),
                                'total_detruct' => trim($val[15]),
                                'total' => trim($val[16]),
                                
                                'create_time' => strtotime($ngaythang),
                                'create_user' => $_SESSION['userid_logined'],
                                
                                );
                            /*if ($salary_data['graded'] == "A") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*10/100 ;
                            }
                            else if ($salary_data['graded'] == "a") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*5/100 ;
                            }
                            else if ($salary_data['graded'] == "b") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*(-5/100) ;
                            }
                            else if ($salary_data['graded'] == "B") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*(-10/100) ;
                            }
                            else{
                                $salary_data['bonus'] = 0;
                            }
                            $salary_data['total_allowance'] = $salary_data['mileage_allowance'] + $salary_data['phone_allowance'] + $salary_data['bh_allowance'] + $salary_data['tn_allowance'] + $salary_data['bonus'] + $salary_data['eating_allowance'];
                            $salary_data['total'] = $salary_data['total_salary'] + $salary_data['total_allowance'] - $salary_data['total_detruct'];
*/

                            $salary->createSalary($salary_data);

                        
                    }
                    else{
                        $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[1])))->staff_id;


                        if (!$salary->getSalaryByWhere(array('staff'=>$id_staff,'create_time' => strtotime($ngaythang)))) {
                            $salary_data = array(
                                'staff' => trim($id_staff),
                                'basic_salary' => trim($val[2]),
                                'work_day' => trim($val[3]),
                                'salary' => trim($val[4]),
                                'phone_allowance' => trim($val[5]),
                                'eating_allowance' => trim($val[6]),
                                'insurance_allowance' => trim($val[7]),
                                'effect_add' => trim($val[8]),
                                'time_add' => trim($val[9]),
                                'diligence_add' => trim($val[10]),
                                'graded' => trim($val[11]),
                                'culture' => trim($val[12]),
                                'insurance_deduct' => trim($val[13]),
                                'total_salary' => trim($val[14]),
                                'total_detruct' => trim($val[15]),
                                'total' => trim($val[16]),
                                
                                'create_time' => strtotime($ngaythang),
                                'create_user' => $_SESSION['userid_logined'],
                                
                                );
                            /*if ($salary_data['graded'] == "A") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*10/100 ;
                            }
                            else if ($salary_data['graded'] == "a") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*5/100 ;
                            }
                            else if ($salary_data['graded'] == "b") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*(-5/100) ;
                            }
                            else if ($salary_data['graded'] == "B") {
                                $salary_data['bonus'] = $salary_data['basic_salary']*(-10/100) ;
                            }
                            else{
                                $salary_data['bonus'] = 0;
                            }
                            $salary_data['total_allowance'] = $salary_data['mileage_allowance'] + $salary_data['phone_allowance'] + $salary_data['bh_allowance'] + $salary_data['tn_allowance'] + $salary_data['bonus'] + $salary_data['eating_allowance'];
                            $salary_data['total'] = $salary_data['total_salary'] + $salary_data['total_allowance'] - $salary_data['total_detruct'];
*/

                            $salary->createSalary($salary_data);
                        }
                        else{
                            $id_salary = $salary->getSalaryByWhere(array('staff'=>$id_staff,'create_time' => strtotime($ngaythang)))->new_salary_id;

                            if($y > 1){
                                $get = $salary->getSalaryByWhere(array('staff'=>$id_staff,'create_time' => strtotime($ngaythang)));

                                $salary_data = array(
                                'staff' => trim($id_staff),
                                'basic_salary' => trim($val[2])+$get->basic_salary,
                                'work_day' => trim($val[3])+$get->work_day,
                                'salary' => trim($val[4])+$get->salary,
                                'phone_allowance' => trim($val[5])+$get->phone_allowance,
                                'eating_allowance' => trim($val[6])+$get->phone_allowance,
                                'insurance_allowance' => trim($val[7])+$get->insurance_allowance,
                                'effect_add' => trim($val[8])+$get->effect_add,
                                'time_add' => trim($val[9])+$get->time_add,
                                'diligence_add' => trim($val[10])+$get->diligence_add,
                                'graded' => trim($val[11])+$get->graded,
                                'culture' => trim($val[12])+$get->culture,
                                'insurance_deduct' => trim($val[13])+$get->insurance_deduct,
                                'total_salary' => trim($val[14])+$get->total_salary,
                                'total_detruct' => trim($val[15])+$get->total_detruct,
                                'total' => trim($val[16])+$get->total,
                                
                                'create_time' => strtotime($ngaythang),
                                'create_user' => $_SESSION['userid_logined'],
                                
                                );

                               /* if ($salary_data['graded'] == "A") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*10/100 ;
                                }
                                else if ($salary_data['graded'] == "a") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*5/100 ;
                                }
                                else if ($salary_data['graded'] == "b") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*(-5/100) ;
                                }
                                else if ($salary_data['graded'] == "B") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*(-10/100) ;
                                }
                                else{
                                    $salary_data['bonus'] = 0;
                                }
                                $salary_data['total_allowance'] = $salary_data['mileage_allowance'] + $salary_data['phone_allowance'] + $salary_data['bh_allowance'] + $salary_data['tn_allowance'] + $salary_data['bonus'] + $salary_data['eating_allowance'];
                                $salary_data['total'] = $salary_data['total_salary'] + $salary_data['total_allowance'] - $salary_data['total_detruct'];
    //var_dump($salary_data);die();*/
                        
                                $salary->updateSalary($salary_data,array('new_salary_id' => $id_salary));
                            }
                            else{

                                $salary_data = array(
                                'staff' => trim($id_staff),
                                'basic_salary' => trim($val[2])+$get->basic_salary,
                                'work_day' => trim($val[3])+$get->work_day,
                                'salary' => trim($val[4])+$get->salary,
                                'phone_allowance' => trim($val[5])+$get->phone_allowance,
                                'eating_allowance' => trim($val[6])+$get->phone_allowance,
                                'insurance_allowance' => trim($val[7])+$get->insurance_allowance,
                                'effect_add' => trim($val[8])+$get->effect_add,
                                'time_add' => trim($val[9])+$get->time_add,
                                'diligence_add' => trim($val[10])+$get->diligence_add,
                                'graded' => trim($val[11])+$get->graded,
                                'culture' => trim($val[12])+$get->culture,
                                'insurance_deduct' => trim($val[13])+$get->insurance_deduct,
                                'total_salary' => trim($val[14])+$get->total_salary,
                                'total_detruct' => trim($val[15])+$get->total_detruct,
                                'total' => trim($val[16])+$get->total,
                                
                                'create_time' => strtotime($ngaythang),
                                'create_user' => $_SESSION['userid_logined'],
                                
                                );

                               /* if ($salary_data['graded'] == "A") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*10/100 ;
                                }
                                else if ($salary_data['graded'] == "a") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*5/100 ;
                                }
                                else if ($salary_data['graded'] == "b") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*(-5/100) ;
                                }
                                else if ($salary_data['graded'] == "B") {
                                    $salary_data['bonus'] = $salary_data['basic_salary']*(-10/100) ;
                                }
                                else{
                                    $salary_data['bonus'] = 0;
                                }
                                $salary_data['total_allowance'] = $salary_data['mileage_allowance'] + $salary_data['phone_allowance'] + $salary_data['bh_allowance'] + $salary_data['tn_allowance'] + $salary_data['bonus'] + $salary_data['eating_allowance'];
                                $salary_data['total'] = $salary_data['total_salary'] + $salary_data['total_allowance'] - $salary_data['total_detruct'];
    //var_dump($salary_data);die();*/
                        
                                $salary->updateSalary($salary_data,array('new_salary_id' => $id_salary));
                            }
                        }
                    }
                    
                }
                if ($y == 1) {
                    $y = $y;
                }
                else{
                    $y = 0;
                }
                
                // insert

                if (!$pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime($ngaythang)))) {
                    $pending_costs_data = array(
                        'pending_costs_date' => strtotime($ngaythang),
                        'pending_costs_money' => $tongchiphi,
                        'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime($ngaythang)),
                    );
                    $pending_costs->createCosts($pending_costs_data);
                }
                else if ($pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime($ngaythang)))) {
                    $id_pending = $pending_costs->getCostsByWhere(array('pending_costs_date'=>strtotime($ngaythang)))->pending_costs_id;
                    $pending_costs_data = array(
                        'pending_costs_date' => strtotime($ngaythang),
                        'pending_costs_money' => $tongchiphi,
                        'comment' => 'Chi phí nhân sự tháng '.date('m/Y',strtotime($ngaythang)),
                    );
                    $pending_costs->updateCosts($pending_costs_data,array('pending_costs_id'=>$id_pending));
                }


            }
            return $this->view->redirect('totalsalary');
        }
        $this->view->show('totalsalary/import');

    }

    /*function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($this->registry->router->param_id != null && $this->registry->router->page != null) {
            $ngaytao = $this->registry->router->param_id.'/'.$this->registry->router->page;

            $staff_model = $this->model->get('staffModel');
            $salary_model = $this->model->get('salaryModel');
            $join = array('table'=>'staff','where'=>'salary.staff = staff.staff_id');
            $data = array(
                'where' => 'salary_create_time LIKE "'. $ngaytao.'"',
                );
            $salary = $salary_model->getAllSalary($data,$join);


            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A6', 'BẢNG LƯƠNG NHÂN VIÊN '.$ngaytao)
               ->setCellValue('A8', 'STT')
               ->setCellValue('B8', 'Họ & tên')
               ->setCellValue('C8', 'LCB 26ng/th')
               ->setCellValue('D8', 'Ngày công')
               ->setCellValue('E8', 'Tăng ca 1h=1.5')
               ->setCellValue('F8', 'Tổng lương')
               ->setCellValue('G8', 'Phụ cấp')
               ->setCellValue('O8', 'Khấu trừ')
               ->setCellValue('T8', 'Thành tiền')
               ->setCellValue('U8', 'Ghi chú')
               ->setCellValue('V8', 'Số CMND')
               ->setCellValue('W8', 'Số TK')
               ->setCellValue('G9', 'Xăng xe')
               ->setCellValue('H9', 'Điện thoại')
               ->setCellValue('I9', 'BH')
               ->setCellValue('J9', 'TN')
               ->setCellValue('K9', 'Xếp loại')
               ->setCellValue('L9', 'Thưởng')
               ->setCellValue('M9', 'Cơm 15/p')
               ->setCellValue('N9', 'Tổng PC')
               ->setCellValue('O9', 'Điện thoại')
               ->setCellValue('P9', 'BH')
               ->setCellValue('Q9', 'T/ứng')
               ->setCellValue('R9', 'Cơm 15/p')
               ->setCellValue('S9', 'Tổng KT');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("CAI MEP TRADING\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(18);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Carrier ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(18);
            $under->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('Managerment Group');
            $nor->getFont()->getColor()->setARGB('022D55');
            $nor->getFont()->setSize(18);
            $nor->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $nor->getFont()->setBold(true);
            $nor->getFont()->setName('Times New Roman');

            $objPHPExcel->getActiveSheet()->getCell('A1')->setValue($objRichText);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);

            
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            

            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setName("name");
            $objDrawing->setDescription("Description");

            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

            $logo = "public/img/cmg.jpg";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);     
            $objDrawing->setCoordinates('B1');

            
            
            $tongngaycong = 0;
            $tongtangca = 0;
            $tongluong = 0;
            $tongphucapxang = 0;
            $tongphucapdienthoai = 0;
            $tongphucapbh = 0;
            $tongphucaptn = 0;
            $tongphucapxeploai = 0;
            $tongphucapthuong = 0;
            $tongphucapcom = 0;
            $tongphucap = 0;
            $tongkhautrudienthoai = 0;
            $tongkhautrubh = 0;
            $tongkhautrutu = 0;
            $tongkhautrucom = 0;
            $tongkhautru = 0;
            $tongcong = 0;

            if ($salary) {

                $hang = 10;
                $i=1;
                foreach ($salary as $row) {
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, $row->staff_name)
                        ->setCellValue('C' . $hang, $row->basic_salary)
                        ->setCellValue('D' . $hang, $row->work_day)
                        ->setCellValue('E' . $hang, $row->overtime)
                        ->setCellValue('F' . $hang, '=C'.$hang.'/26*(D'.$hang.'+E'.$hang.')')
                        ->setCellValue('G' . $hang, $row->mileage_allowance)
                        ->setCellValue('H' . $hang, $row->phone_allowance)
                        ->setCellValue('I' . $hang, $row->bh_allowance)
                        ->setCellValue('J' . $hang, $row->tn_allowance)
                        ->setCellValue('K' . $hang, $row->graded)
                        ->setCellValue('L' . $hang, '=IF(EXACT(K'.$hang.',"A"),10%*C'.$hang.',IF(EXACT(K'.$hang.',"a"),5%*C'.$hang.',IF(EXACT(K'.$hang.',0),0,IF(EXACT(K'.$hang.',"b"),-5%*C'.$hang.',IF(EXACT(K'.$hang.',"B"),-10%*C'.$hang.')))))')
                        ->setCellValue('M' . $hang, $row->eating_allowance)
                        ->setCellValue('N' . $hang, '=SUM(G'.$hang.':M'.$hang.')')
                        ->setCellValue('O' . $hang, $row->phone_detruct)
                        ->setCellValue('P' . $hang, $row->bh_detruct)
                        ->setCellValue('Q' . $hang, $row->tu_detruct)
                        ->setCellValue('R' . $hang, $row->eating_detruct)
                        ->setCellValue('S' . $hang, '=SUM(O'.$hang.':R'.$hang.')')
                        ->setCellValue('T' . $hang, '=F'.$hang.'+N'.$hang.'-S'.$hang.'')
                        ->setCellValue('U' . $hang, "")
                        ->setCellValue('V' . $hang, $row->cmnd)
                        ->setCellValue('W' . $hang, $row->bank);
                     $hang++;

                     $tongngaycong += $row->work_day;
                    $tongtangca += $row->overtime;
                    $tongluong += $row->total_salary;
                    $tongphucapxang += $row->mileage_allowance;
                    $tongphucapdienthoai += $row->phone_allowance;
                    $tongphucapbh += $row->bh_allowance;
                    $tongphucaptn += $row->tn_allowance;
                    
                    $tongphucapthuong += $row->bonus;
                    $tongphucapcom += $row->eating_allowance;
                    $tongphucap += $row->total_allowance;
                    $tongkhautrudienthoai += $row->phone_detruct;
                    $tongkhautrubh += $row->bh_detruct;
                    $tongkhautrutu += $row->tu_detruct;
                    $tongkhautrucom += $row->eating_detruct;
                    $tongkhautru += $row->total_detruct;
                    $tongcong += $row->total;

                  }

          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Cai Mep Trading")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Salary Report")
                            ->setSubject("Salary Report")
                            ->setDescription("Salary Report.")
                            ->setKeywords("Salary Report")
                            ->setCategory("Salary Report");
            $objPHPExcel->getActiveSheet()->setTitle($this->registry->router->param_id.".".$this->registry->router->page);

            $objPHPExcel->getActiveSheet()->getStyle('A6:W9')->getFont()->setBold(true);


            $objPHPExcel->getActiveSheet()->mergeCells('A6:W6');
            $objPHPExcel->getActiveSheet()->mergeCells('A1:W5');


            $objPHPExcel->getActiveSheet()->mergeCells('A8:A9');
            $objPHPExcel->getActiveSheet()->mergeCells('B8:B9');
            $objPHPExcel->getActiveSheet()->mergeCells('C8:C9');
            $objPHPExcel->getActiveSheet()->mergeCells('D8:D9');
            $objPHPExcel->getActiveSheet()->mergeCells('E8:E9');
            $objPHPExcel->getActiveSheet()->mergeCells('F8:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('G8:N8');
            $objPHPExcel->getActiveSheet()->mergeCells('O8:S8');
            $objPHPExcel->getActiveSheet()->mergeCells('T8:T9');
            $objPHPExcel->getActiveSheet()->mergeCells('U8:U9');
            $objPHPExcel->getActiveSheet()->mergeCells('V8:V9');
            $objPHPExcel->getActiveSheet()->mergeCells('W8:W9');

            

            $objPHPExcel->getActiveSheet()->getStyle('C10:C'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('F10:J'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('L10:T'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('E'.$highestRow)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);

            $objPHPExcel->setActiveSheetIndex($index_worksheet)
               ->setCellValue('A'.$highestRow, 'TỔNG CỘNG')
               ->setCellValue('D'.$highestRow, '=SUM(D10:D'.($highestRow-1).')')
               ->setCellValue('E'.$highestRow, '=SUM(E10:E'.($highestRow-1).')')
               ->setCellValue('F'.$highestRow, '=SUM(F10:F'.($highestRow-1).')')
               ->setCellValue('G'.$highestRow, '=SUM(G10:G'.($highestRow-1).')')
               ->setCellValue('H'.$highestRow, '=SUM(H10:H'.($highestRow-1).')')
               ->setCellValue('I'.$highestRow, '=SUM(I10:I'.($highestRow-1).')')
               ->setCellValue('J'.$highestRow, '=SUM(J10:J'.($highestRow-1).')')
               ->setCellValue('K'.$highestRow, "-")
               ->setCellValue('L'.$highestRow, '=SUM(L10:L'.($highestRow-1).')')
               ->setCellValue('M'.$highestRow, '=SUM(M10:M'.($highestRow-1).')')
               ->setCellValue('N'.$highestRow, '=SUM(N10:N'.($highestRow-1).')')
               ->setCellValue('O'.$highestRow, '=SUM(O10:O'.($highestRow-1).')')
               ->setCellValue('P'.$highestRow, '=SUM(P10:P'.($highestRow-1).')')
               ->setCellValue('Q'.$highestRow, '=SUM(Q10:Q'.($highestRow-1).')')
               ->setCellValue('R'.$highestRow, '=SUM(R10:R'.($highestRow-1).')')
               ->setCellValue('S'.$highestRow, '=SUM(S10:S'.($highestRow-1).')')
               ->setCellValue('T'.$highestRow, '=SUM(T10:T'.($highestRow-1).')');

            

            $objPHPExcel->getActiveSheet()->mergeCells('A'.$highestRow.':B'.$highestRow);
            $objPHPExcel->getActiveSheet()->getStyle('A'.$highestRow)->getFont()->setBold(true);

            
            $objPHPExcel->getActiveSheet()->getStyle('A6:W'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A6:W'.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('B10:B'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('U10:U'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('V10:V'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('W10:W'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

            
            $objPHPExcel->getActiveSheet()->getStyle('A8:W9')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '08853A')
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A8:W'.$highestRow)->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A8:W'.$highestRow)->applyFromArray(
                array(
                    'borders' => array(
                        'outline' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                )
            );
            
            
            $objPHPExcel->getActiveSheet()->getStyle('A'.$highestRow.':T'.$highestRow)->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle("A1:W".($highestRow+1))->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle("A1:W6")->getFont()->setSize(18);
            $objPHPExcel->getActiveSheet()->getStyle("A8:W9")->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle("A8:W".$highestRow)->getFont()->setSize(9);

            $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(13);
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('4')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('5')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('6')->setRowHeight(28);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(18);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(18);

            $objPHPExcel->getActiveSheet()->freezePane('A10');

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG LƯƠNG NHÂN VIÊN ".$ngaytao.".xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        }
    }*/

}
?>