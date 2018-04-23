<?php
Class checksalarytotalController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng lương';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 100;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'priority';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngaytao = date('m-Y');
        }

        $batdau = '01-'.$ngaytao;
        $ketthuc = date('t-m-Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $staff_model = $this->model->get('staffModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'user','where'=>'account=user_id','join'=>'LEFT JOIN');
        $data = array(
            'where' => '(start_date < '.strtotime($ngayketthuc).' AND end_date >= '.strtotime($ketthuc).') OR (start_date < '.strtotime($ngayketthuc).' AND (end_date IS NULL OR end_date = 0) )',
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
            'where' => '(start_date < '.strtotime($ngayketthuc).' AND end_date >= '.strtotime($ketthuc).') OR (start_date < '.strtotime($ngayketthuc).' AND (end_date IS NULL OR end_date = 0) )',
            );

        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $staffs = $staff_model->getAllStaff($data,$join);
        $this->view->data['staffs'] = $staffs;
        
        

        $phoneallowance_model = $this->model->get('phoneallowanceModel');
        $eatingallowance_model = $this->model->get('eatingallowanceModel');
        $curricular_model = $this->model->get('curricularModel');
        $position_salary_model = $this->model->get('positionsalaryModel');
        $insurrance_model = $this->model->get('insurranceModel');
        $lift_model = $this->model->get('liftModel');
        //$importtire_model = $this->model->get('importtireModel');

        $tu = 2000;

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

        /*$data = array(
            'where' => 'expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

        $imports = $importtire_model->getAllSale($data,$join);
        
        foreach ($imports as $sale) {
            $arr_lift[$sale->staff_id] = isset($arr_lift[$sale->staff_id])?$arr_lift[$sale->staff_id]+1500000:1500000;
        }*/

        $this->view->data['arr_lift'] = $arr_lift;

        $qr = 'SELECT * FROM (SELECT * FROM phone_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $phones = $phoneallowance_model->queryAllowance($qr);
        $arr_phone = array();
        foreach ($phones as $phone) {
            $arr_phone[$phone->staff] = isset($arr_phone[$phone->staff])?$arr_phone[$phone->staff]+$phone->phone_allowance:$phone->phone_allowance;
        }

        $this->view->data['arr_phone'] = $arr_phone;

        $qr = 'SELECT * FROM (SELECT * FROM eating_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $eatings = $eatingallowance_model->queryAllowance($qr);
        $arr_eating = array();
        foreach ($eatings as $eating) {
            $arr_eating[$eating->staff] = isset($arr_eating[$eating->staff])?$arr_eating[$eating->staff]+$eating->eating_allowance:$eating->eating_allowance;
        }

        $this->view->data['arr_eating_allowance'] = $arr_eating;

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

        $curriculars = $curricular_model->getAllSalary($data);
        $arr_curricular = array();
        foreach ($curriculars as $curricular) {
            $arr_curricular[$curricular->staff] = isset($arr_curricular[$curricular->staff])?$arr_curricular[$curricular->staff]+$curricular->curricular_salary:$curricular->curricular_salary;
        }

        $this->view->data['arr_curricular'] = $arr_curricular;

        
        $attendance_model = $this->model->get('attendanceModel');
        $attendance_rate_model = $this->model->get('attendancerateModel');
        $position_staff_evaluate_model = $this->model->get('positionstaffevaluateModel');
        $position_staff_work_model = $this->model->get('positionstaffworkModel');
        $position_rule_apply_model = $this->model->get('positionruleapplyModel');
        $position_rule_point_model = $this->model->get('positionrulepointModel');

        $data = array(
            'where' => 'attendance_date >= '.strtotime($batdau).' AND attendance_date < '.strtotime($ngayketthuc),
        );
        $attendances = $attendance_model->getAllAttendance($data);
        $arr_attend = array();
        foreach ($attendances as $attendance) {
            $sotieng = round($attendance->attendance_total/8,2);
            if ($attendance->check_in_1 != "" && $attendance->check_in_1 != 0) {
                $ngay = explode(':', $attendance->check_in_1);
            }
            else{
                $ngay = [7,30];
            }

            if ($ngay[0] > 11) {
                $ngay = [7,30];
            }
            
            $phut = (7*60)+33;
            $phuttre = ($ngay[0]*60)+$ngay[1];
            $tre = $phuttre>$phut?round((($phuttre-$phut)*2/60)/8,2):0;

            $arr_attend[$attendance->staff] = isset($arr_attend[$attendance->staff])?$arr_attend[$attendance->staff]+$sotieng-$tre:$sotieng-$tre;
        }

        $this->view->data['arr_attend'] = $arr_attend;

        $qr = 'SELECT * FROM (SELECT * FROM attendance_rate WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $attendance_rates = $attendance_rate_model->queryAttendance($qr);
        $arr_attendance_rate = array();
        foreach ($attendance_rates as $attendance_rate) {
            $arr_attendance_rate[$attendance_rate->staff] = isset($arr_attendance_rate[$attendance_rate->staff])?$arr_attendance_rate[$attendance_rate->staff]+$attendance_rate->attendance_rate_salary:$attendance_rate->attendance_rate_salary;
        }

        $this->view->data['arr_attendance_rate'] = $arr_attendance_rate;


        $position_evaluates = $position_staff_evaluate_model->getAllSalary(array('where' => '(position_staff_evaluate_date >= '.strtotime($batdau).' AND position_staff_evaluate_date < '.strtotime($ngayketthuc).')'));
        $position_evaluate = array();
        foreach ($position_evaluates as $evaluate) {
            $position_evaluate[$evaluate->staff] = $evaluate->position_staff_evaluate_percent;
        }
        $this->view->data['position_evaluate'] = $position_evaluate;

        $position_works = $position_staff_work_model->getAllSalary(array('where' => '(position_staff_work_date >= '.strtotime($batdau).' AND position_staff_work_date < '.strtotime($ngayketthuc).')'));
        $position_work = array();
        foreach ($position_works as $work) {
            $position_work[$work->staff] = $work->position_staff_work_percent;
        }
        $this->view->data['position_work'] = $position_work;

        $position_rule_points = $position_rule_point_model->getAllSalary(array('where' => '(position_rule_point_start_date <= '.strtotime($batdau).' AND position_rule_point_end_date >= '.strtotime($ketthuc).') OR (position_rule_point_start_date <= '.strtotime($batdau).' AND (position_rule_point_end_date IS NULL OR position_rule_point_end_date = 0) )','order_by'=>'position_rule_point_start_date DESC','limit'=>1));
        $position_point = array();
        foreach ($position_rule_points as $point) {
            $position_point[$point->position_rule] = $point->position_rule_point;
        }

        $position_rule_applys = $position_rule_apply_model->getAllSalary(array('where' => '(position_rule_apply_date >= '.strtotime($batdau).' AND position_rule_apply_date < '.strtotime($ngayketthuc).')'));
        $position_rule = array();
        $position_rule_apply = array();
        foreach ($position_rule_applys as $apply) {
            $position_rule_apply[$apply->staff][$apply->position_rule] = isset($position_rule_apply[$apply->staff][$apply->position_rule])?$position_rule_apply[$apply->staff][$apply->position_rule]+$apply->position_rule_apply_number:$apply->position_rule_apply_number;
            
            if (isset($position_rule_apply[$apply->staff][$apply->position_rule]) && $position_rule_apply[$apply->staff][$apply->position_rule]>=$position_point[$apply->position_rule]) {
                $position_rule[$apply->staff] = isset($position_rule[$apply->staff])?$position_rule[$apply->staff]+(int)($position_rule_apply[$apply->staff][$apply->position_rule]/$position_point[$apply->position_rule])*5:(int)($position_rule_apply[$apply->staff][$apply->position_rule]/$position_point[$apply->position_rule])*5;
            }
        }
        $this->view->data['position_rule'] = $position_rule;

        $check_sale_salary_model = $this->model->get('checksalesalaryModel');

        $luong_sp = array();
        $luong_vuotgia = array();
        $luong_kpi = array();
        $congno = array();

        $check_sale_salarys = $check_sale_salary_model->getAllSalary(array('where'=>'salary_date >= '.strtotime($batdau).' AND salary_date <= '.strtotime($ketthuc)),null);

        if ($check_sale_salarys) {
            foreach ($check_sale_salarys as $checks) {
                
                $luong_sp[$checks->staff] = isset($luong_sp[$checks->staff])?$luong_sp[$checks->staff]+(round($checks->bonus/1000)*1000):(round($checks->bonus/1000)*1000);
                $luong_vuotgia[$checks->staff] = isset($luong_vuotgia[$checks->staff])?$luong_vuotgia[$checks->staff]+$checks->bonus_over:$checks->bonus_over;
                $luong_kpi[$checks->staff] = isset($luong_kpi[$checks->staff])?$luong_kpi[$checks->staff]+$checks->bonus_kpi:$checks->bonus_kpi;

                /*if ($checks->debit==1) {
                    $congno[$checks->staff] = isset($congno[$checks->staff])?$congno[$checks->staff]+1:1;
                }*/
                
            }
        }

        $this->view->data['luong_sp'] = $luong_sp;
        $this->view->data['luong_vuotgia'] = $luong_vuotgia;
        $this->view->data['luong_kpi'] = $luong_kpi;
        

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name','order'=>'ASC'));

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $receivables = $receivable_model->getAllCosts(null,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $receive_model = $this->model->get('receiveModel');

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );


            $sales = $tire_sale_model->getAllTire($data);
            foreach ($sales as $sale) {
                $data_customer['staff'][$order->customer] = $sale->sale;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                
                $sales = $tire_sale_model->getAllTire($data);
                foreach ($sales as $sale) {
                    $data_customer['staff'][$order->customer] = $sale->sale;
                }
            }
            
            
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $join = array('table'=>'customer, staff, receivable','where'=>'customer.customer_id = order_tire.customer AND account = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel'); 
        

        $data = array(
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );


        $orders = $order_tire_model->getAllTire($data,$join);

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['staff'][$order->customer] = $order->staff_id;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        foreach ($customers as $customer) {
            if (!isset($data_customer['money'][$customer->customer_id])) {
                $data_customer['money'][$customer->customer_id] = 0;
            }
            if (!isset($data_customer['pay_money'][$customer->customer_id])) {
                $data_customer['pay_money'][$customer->customer_id] = 0;
            }

            if ($data_customer['money'][$customer->customer_id] - $data_customer['pay_money'][$customer->customer_id] > 0) {
                $congno[$data_customer['staff'][$customer->customer_id]] = isset($congno[$data_customer['staff'][$customer->customer_id]])?$congno[$data_customer['staff'][$customer->customer_id]]+1:1;
            }
        }
        $this->view->data['congno'] = $congno;

        $ketthuc_truoc = date('d-m-Y', strtotime($ketthuc. ' - 1 month'));

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        

        $receivables = $receivable_model->getAllCosts(null,$join);

         

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );


            $sales = $tire_sale_model->getAllTire($data);
            foreach ($sales as $sale) {
                $data_customer2['staff'][$order->customer] = $sale->sale;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                
                $sales = $tire_sale_model->getAllTire($data);
                foreach ($sales as $sale) {
                    $data_customer2['staff'][$order->customer] = $sale->sale;
                }
            }
            
            
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer2['money'][$order->customer] = isset($data_customer2['money'][$order->customer])?$data_customer2['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer2['pay_money'][$order->customer] = isset($data_customer2['pay_money'][$order->customer])?$data_customer2['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $join = array('table'=>'customer, staff, receivable','where'=>'customer.customer_id = order_tire.customer AND account = sale AND order_tire = order_tire_id');


        $data = array(
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
        );


        $orders = $order_tire_model->getAllTire($data,$join);

        $data_customer2 = array();
        foreach ($orders as $order) {
            $data_customer2['money'][$order->customer] = isset($data_customer2['money'][$order->customer])?$data_customer2['money'][$order->customer]+$order->total:$order->total;
            $data_customer2['staff'][$order->customer] = $order->staff_id;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer2['pay_money'][$order->customer] = isset($data_customer2['pay_money'][$order->customer])?$data_customer2['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        

        
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer2['pay_money'][$de->customer] = isset($data_customer2['pay_money'][$de->customer])?$data_customer2['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer2['pay_money'][$de->customer] = isset($data_customer2['pay_money'][$de->customer])?$data_customer2['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        $congnothangtruoc = array();
        $congnodatra = array();
        foreach ($customers as $customer) {
            if (!isset($data_customer2['money'][$customer->customer_id])) {
                $data_customer2['money'][$customer->customer_id] = 0;
            }
            if (!isset($data_customer2['pay_money'][$customer->customer_id])) {
                $data_customer2['pay_money'][$customer->customer_id] = 0;
            }

            if ($data_customer2['money'][$customer->customer_id] - $data_customer2['pay_money'][$customer->customer_id] > 0) {
                $congnothangtruoc[$data_customer2['staff'][$customer->customer_id]] = isset($congnothangtruoc[$data_customer2['staff'][$customer->customer_id]])?$congnothangtruoc[$data_customer2['staff'][$customer->customer_id]]+1:1;
            }

            if (($data_customer2['money'][$customer->customer_id] - $data_customer2['pay_money'][$customer->customer_id] > 0) && ($data_customer['money'][$customer->customer_id] - $data_customer['pay_money'][$customer->customer_id] <= 0)) {
                $congnodatra[$data_customer2['staff'][$customer->customer_id]] = isset($congnodatra[$data_customer2['staff'][$customer->customer_id]])?$congnodatra[$data_customer2['staff'][$customer->customer_id]]+1:1;
            }
        }

        
        $this->view->data['congnothangtruoc'] = $congnothangtruoc;
        $this->view->data['congnodatra'] = $congnodatra;
        

        $salary_keep_model = $this->model->get('salarykeepModel');
        $qr = 'SELECT * FROM (SELECT * FROM salary_keep WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $salary_keeps = $salary_keep_model->querySalary($qr);
        $arr_salary_keep = array();
        foreach ($salary_keeps as $salary_keep) {
            $arr_salary_keep[$salary_keep->staff] = isset($arr_salary_keep[$salary_keep->staff])?$arr_salary_keep[$salary_keep->staff]+$salary_keep->salary_keep:$salary_keep->salary_keep;
        }

        $this->view->data['arr_salary_keep'] = $arr_salary_keep;

        /*************/
        $this->view->show('checksalarytotal/index');
    }
    public function index2() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng lương';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 100;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'priority';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngaytao = date('m-Y');
        }

        $batdau = '01-'.$ngaytao;
        $ketthuc = date('t-m-Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $staff_model = $this->model->get('staffModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'user','where'=>'account=user_id','join'=>'LEFT JOIN');
        $data = array(
            'where' => '(start_date < '.strtotime($ngayketthuc).' AND end_date >= '.strtotime($ketthuc).') OR (start_date < '.strtotime($ngayketthuc).' AND (end_date IS NULL OR end_date = 0) )',
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
            'where' => '(start_date < '.strtotime($ngayketthuc).' AND end_date >= '.strtotime($ketthuc).') OR (start_date < '.strtotime($ngayketthuc).' AND (end_date IS NULL OR end_date = 0) )',
            );

        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $staffs = $staff_model->getAllStaff($data,$join);
        $this->view->data['staffs'] = $staffs;
        
        

        $phoneallowance_model = $this->model->get('phoneallowanceModel');
        $eatingallowance_model = $this->model->get('eatingallowanceModel');
        $curricular_model = $this->model->get('curricularModel');
        $position_salary_model = $this->model->get('positionsalaryModel');
        $insurrance_model = $this->model->get('insurranceModel');
        $lift_model = $this->model->get('liftModel');
        //$importtire_model = $this->model->get('importtireModel');

        $tu = 2000;

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

        /*$data = array(
            'where' => 'expect_date >= '.strtotime($batdau).' AND expect_date <= '.strtotime($ketthuc),
        );
        $join = array('table'=>'staff, user','where'=>'user_id = account AND user_id = sale');

        $imports = $importtire_model->getAllSale($data,$join);
        
        foreach ($imports as $sale) {
            $arr_lift[$sale->staff_id] = isset($arr_lift[$sale->staff_id])?$arr_lift[$sale->staff_id]+1500000:1500000;
        }*/

        $this->view->data['arr_lift'] = $arr_lift;

        $qr = 'SELECT * FROM (SELECT * FROM phone_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $phones = $phoneallowance_model->queryAllowance($qr);
        $arr_phone = array();
        foreach ($phones as $phone) {
            $arr_phone[$phone->staff] = isset($arr_phone[$phone->staff])?$arr_phone[$phone->staff]+$phone->phone_allowance:$phone->phone_allowance;
        }

        $this->view->data['arr_phone'] = $arr_phone;

        $qr = 'SELECT * FROM (SELECT * FROM eating_allowance WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $eatings = $eatingallowance_model->queryAllowance($qr);
        $arr_eating = array();
        foreach ($eatings as $eating) {
            $arr_eating[$eating->staff] = isset($arr_eating[$eating->staff])?$arr_eating[$eating->staff]+$eating->eating_allowance:$eating->eating_allowance;
        }

        $this->view->data['arr_eating_allowance'] = $arr_eating;

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

        $curriculars = $curricular_model->getAllSalary($data);
        $arr_curricular = array();
        foreach ($curriculars as $curricular) {
            $arr_curricular[$curricular->staff] = isset($arr_curricular[$curricular->staff])?$arr_curricular[$curricular->staff]+$curricular->curricular_salary:$curricular->curricular_salary;
        }

        $this->view->data['arr_curricular'] = $arr_curricular;

        
        $attendance_model = $this->model->get('attendanceModel');
        $attendance_rate_model = $this->model->get('attendancerateModel');
        $position_staff_evaluate_model = $this->model->get('positionstaffevaluateModel');
        $position_staff_work_model = $this->model->get('positionstaffworkModel');
        $position_rule_apply_model = $this->model->get('positionruleapplyModel');
        $position_rule_point_model = $this->model->get('positionrulepointModel');

        $data = array(
            'where' => 'attendance_date >= '.strtotime($batdau).' AND attendance_date < '.strtotime($ngayketthuc),
        );
        $attendances = $attendance_model->getAllAttendance($data);
        $arr_attend = array();
        foreach ($attendances as $attendance) {
            $sotieng = round($attendance->attendance_total/8,2);
            if ($attendance->check_in_1 != "" && $attendance->check_in_1 != 0) {
                $ngay = explode(':', $attendance->check_in_1);
            }
            else{
                $ngay = [7,30];
            }

            if ($ngay[0] > 11) {
                $ngay = [7,30];
            }
            
            $phut = (7*60)+33;
            $phuttre = ($ngay[0]*60)+$ngay[1];
            $tre = $phuttre>$phut?round((($phuttre-$phut)*2/60)/8,2):0;

            $arr_attend[$attendance->staff] = isset($arr_attend[$attendance->staff])?$arr_attend[$attendance->staff]+$sotieng-$tre:$sotieng-$tre;
        }

        $this->view->data['arr_attend'] = $arr_attend;

        $qr = 'SELECT * FROM (SELECT * FROM attendance_rate WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $attendance_rates = $attendance_rate_model->queryAttendance($qr);
        $arr_attendance_rate = array();
        foreach ($attendance_rates as $attendance_rate) {
            $arr_attendance_rate[$attendance_rate->staff] = isset($arr_attendance_rate[$attendance_rate->staff])?$arr_attendance_rate[$attendance_rate->staff]+$attendance_rate->attendance_rate_salary:$attendance_rate->attendance_rate_salary;
        }

        $this->view->data['arr_attendance_rate'] = $arr_attendance_rate;


        $position_evaluates = $position_staff_evaluate_model->getAllSalary(array('where' => '(position_staff_evaluate_date >= '.strtotime($batdau).' AND position_staff_evaluate_date < '.strtotime($ngayketthuc).')'));
        $position_evaluate = array();
        foreach ($position_evaluates as $evaluate) {
            $position_evaluate[$evaluate->staff] = $evaluate->position_staff_evaluate_percent;
        }
        $this->view->data['position_evaluate'] = $position_evaluate;

        $position_works = $position_staff_work_model->getAllSalary(array('where' => '(position_staff_work_date >= '.strtotime($batdau).' AND position_staff_work_date < '.strtotime($ngayketthuc).')'));
        $position_work = array();
        foreach ($position_works as $work) {
            $position_work[$work->staff] = $work->position_staff_work_percent;
        }
        $this->view->data['position_work'] = $position_work;

        $position_rule_points = $position_rule_point_model->getAllSalary(array('where' => '(position_rule_point_start_date <= '.strtotime($batdau).' AND position_rule_point_end_date >= '.strtotime($ketthuc).') OR (position_rule_point_start_date <= '.strtotime($batdau).' AND (position_rule_point_end_date IS NULL OR position_rule_point_end_date = 0) )','order_by'=>'position_rule_point_start_date DESC','limit'=>1));
        $position_point = array();
        foreach ($position_rule_points as $point) {
            $position_point[$point->position_rule] = $point->position_rule_point;
        }

        $position_rule_applys = $position_rule_apply_model->getAllSalary(array('where' => '(position_rule_apply_date >= '.strtotime($batdau).' AND position_rule_apply_date < '.strtotime($ngayketthuc).')'));
        $position_rule = array();
        $position_rule_apply = array();
        foreach ($position_rule_applys as $apply) {
            $position_rule_apply[$apply->staff][$apply->position_rule] = isset($position_rule_apply[$apply->staff][$apply->position_rule])?$position_rule_apply[$apply->staff][$apply->position_rule]+$apply->position_rule_apply_number:$apply->position_rule_apply_number;
            
            if (isset($position_rule_apply[$apply->staff][$apply->position_rule]) && $position_rule_apply[$apply->staff][$apply->position_rule]>=$position_point[$apply->position_rule]) {
                $position_rule[$apply->staff] = isset($position_rule[$apply->staff])?$position_rule[$apply->staff]+(int)($position_rule_apply[$apply->staff][$apply->position_rule]/$position_point[$apply->position_rule])*5:(int)($position_rule_apply[$apply->staff][$apply->position_rule]/$position_point[$apply->position_rule])*5;
            }
        }
        $this->view->data['position_rule'] = $position_rule;

        $check_sale_salary_model = $this->model->get('checksalesalaryModel');

        $luong_sp = array();
        $luong_vuotgia = array();
        $luong_kpi = array();
        $congno = array();

        $check_sale_salarys = $check_sale_salary_model->getAllSalary(array('where'=>'salary_date >= '.strtotime($batdau).' AND salary_date <= '.strtotime($ketthuc)),null);

        if ($check_sale_salarys) {
            foreach ($check_sale_salarys as $checks) {
                
                $luong_sp[$checks->staff] = isset($luong_sp[$checks->staff])?$luong_sp[$checks->staff]+(round($checks->bonus/1000)*1000):(round($checks->bonus/1000)*1000);
                $luong_vuotgia[$checks->staff] = isset($luong_vuotgia[$checks->staff])?$luong_vuotgia[$checks->staff]+$checks->bonus_over:$checks->bonus_over;
                $luong_kpi[$checks->staff] = isset($luong_kpi[$checks->staff])?$luong_kpi[$checks->staff]+$checks->bonus_kpi:$checks->bonus_kpi;

                /*if ($checks->debit==1) {
                    $congno[$checks->staff] = isset($congno[$checks->staff])?$congno[$checks->staff]+1:1;
                }*/
                
            }
        }

        $this->view->data['luong_sp'] = $luong_sp;
        $this->view->data['luong_vuotgia'] = $luong_vuotgia;
        $this->view->data['luong_kpi'] = $luong_kpi;
        

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name','order'=>'ASC'));

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $receivables = $receivable_model->getAllCosts(null,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $receive_model = $this->model->get('receiveModel');

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );


            $sales = $tire_sale_model->getAllTire($data);
            foreach ($sales as $sale) {
                $data_customer['staff'][$order->customer] = $sale->sale;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                
                $sales = $tire_sale_model->getAllTire($data);
                foreach ($sales as $sale) {
                    $data_customer['staff'][$order->customer] = $sale->sale;
                }
            }
            
            
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $join = array('table'=>'customer, staff, receivable','where'=>'customer.customer_id = order_tire.customer AND account = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel'); 
        

        $data = array(
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );


        $orders = $order_tire_model->getAllTire($data,$join);

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['staff'][$order->customer] = $order->staff_id;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        foreach ($customers as $customer) {
            if (!isset($data_customer['money'][$customer->customer_id])) {
                $data_customer['money'][$customer->customer_id] = 0;
            }
            if (!isset($data_customer['pay_money'][$customer->customer_id])) {
                $data_customer['pay_money'][$customer->customer_id] = 0;
            }

            if ($data_customer['money'][$customer->customer_id] - $data_customer['pay_money'][$customer->customer_id] > 0) {
                $congno[$data_customer['staff'][$customer->customer_id]] = isset($congno[$data_customer['staff'][$customer->customer_id]])?$congno[$data_customer['staff'][$customer->customer_id]]+1:1;
            }
        }
        $this->view->data['congno'] = $congno;

        $ketthuc_truoc = date('d-m-Y', strtotime($ketthuc. ' - 1 month'));

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        

        $receivables = $receivable_model->getAllCosts(null,$join);

         

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );


            $sales = $tire_sale_model->getAllTire($data);
            foreach ($sales as $sale) {
                $data_customer2['staff'][$order->customer] = $sale->sale;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                
                $sales = $tire_sale_model->getAllTire($data);
                foreach ($sales as $sale) {
                    $data_customer2['staff'][$order->customer] = $sale->sale;
                }
            }
            
            
            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer2['money'][$order->customer] = isset($data_customer2['money'][$order->customer])?$data_customer2['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer2['pay_money'][$order->customer] = isset($data_customer2['pay_money'][$order->customer])?$data_customer2['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $join = array('table'=>'customer, staff, receivable','where'=>'customer.customer_id = order_tire.customer AND account = sale AND order_tire = order_tire_id');


        $data = array(
            'where'=>'delivery_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
        );


        $orders = $order_tire_model->getAllTire($data,$join);

        $data_customer2 = array();
        foreach ($orders as $order) {
            $data_customer2['money'][$order->customer] = isset($data_customer2['money'][$order->customer])?$data_customer2['money'][$order->customer]+$order->total:$order->total;
            $data_customer2['staff'][$order->customer] = $order->staff_id;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer2['pay_money'][$order->customer] = isset($data_customer2['pay_money'][$order->customer])?$data_customer2['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        

        
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))),
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer2['pay_money'][$de->customer] = isset($data_customer2['pay_money'][$de->customer])?$data_customer2['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime(date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'))));
            foreach ($receives as $re) {
                $data_customer2['pay_money'][$de->customer] = isset($data_customer2['pay_money'][$de->customer])?$data_customer2['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        $congnothangtruoc = array();
        $congnodatra = array();
        foreach ($customers as $customer) {
            if (!isset($data_customer2['money'][$customer->customer_id])) {
                $data_customer2['money'][$customer->customer_id] = 0;
            }
            if (!isset($data_customer2['pay_money'][$customer->customer_id])) {
                $data_customer2['pay_money'][$customer->customer_id] = 0;
            }

            if ($data_customer2['money'][$customer->customer_id] - $data_customer2['pay_money'][$customer->customer_id] > 0) {
                $congnothangtruoc[$data_customer2['staff'][$customer->customer_id]] = isset($congnothangtruoc[$data_customer2['staff'][$customer->customer_id]])?$congnothangtruoc[$data_customer2['staff'][$customer->customer_id]]+1:1;
            }

            if (($data_customer2['money'][$customer->customer_id] - $data_customer2['pay_money'][$customer->customer_id] > 0) && ($data_customer['money'][$customer->customer_id] - $data_customer['pay_money'][$customer->customer_id] <= 0)) {
                $congnodatra[$data_customer2['staff'][$customer->customer_id]] = isset($congnodatra[$data_customer2['staff'][$customer->customer_id]])?$congnodatra[$data_customer2['staff'][$customer->customer_id]]+1:1;
            }
        }

        
        $this->view->data['congnothangtruoc'] = $congnothangtruoc;
        $this->view->data['congnodatra'] = $congnodatra;
        

        $salary_keep_model = $this->model->get('salarykeepModel');
        $qr = 'SELECT * FROM (SELECT * FROM salary_keep WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC) d GROUP BY d.staff';
        $salary_keeps = $salary_keep_model->querySalary($qr);
        $arr_salary_keep = array();
        foreach ($salary_keeps as $salary_keep) {
            $arr_salary_keep[$salary_keep->staff] = isset($arr_salary_keep[$salary_keep->staff])?$arr_salary_keep[$salary_keep->staff]+$salary_keep->salary_keep:$salary_keep->salary_keep;
        }

        $this->view->data['arr_salary_keep'] = $arr_salary_keep;

        /*************/
        $this->view->show('checksalarytotal/index2');
    }
    public function giuluong(){

        

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if (isset($_POST['data'])) {

            $batdau = '01-'.$_POST['thang'];
            $ketthuc = date('t-m-Y',strtotime($batdau));
            $staff = $_POST['data'];
            if ($staff == "group10") {
                $staff = 0;
            }

            $salary_keep = $this->model->get('salarykeepModel');

            $salary_keep_data = $salary_keep->getAllSalary(array('where'=>'create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc).' AND staff = '.$staff));

            $data = array(
                'staff' => $staff,
                'create_time' => strtotime($batdau),
                'salary_keep' => trim(str_replace(',','',$_POST['keyword'])),
                );

            if (!$salary_keep_data) {
                $salary_keep->createSalary($data);
            }
            else{
                $salary_keep->querySalary('UPDATE salary_keep SET salary_keep = '.trim(str_replace(',','',$_POST['keyword'])).' WHERE staff = '.$staff.' AND create_time >= '.strtotime($batdau).' AND create_time <= '.strtotime($ketthuc));
                
            }



            date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."salary_keep"."|".$data."|salary_keep|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



            return true;

                    

        }

    }

}
?>