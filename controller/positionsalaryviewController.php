<?php
Class positionsalaryviewController Extends baseController {
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
        $staffs = $staff_model->getAllStaff($data,$join);
        $this->view->data['staffs'] = $staffs;
        
        

        $phoneallowance_model = $this->model->get('phoneallowanceModel');
        $eatingallowance_model = $this->model->get('eatingallowanceModel');
        $curricular_model = $this->model->get('curricularModel');
        $position_salary_model = $this->model->get('positionsalaryModel');
        $insurrance_model = $this->model->get('insurranceModel');

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

        $position_staff_model = $this->model->get('positionstaffModel');
        $position_revenue_model = $this->model->get('positionrevenueModel');
        $position_profit_model = $this->model->get('positionprofitModel');

        $sale_direct = array();
        $sale_indirect = array();

        $position_revenues = $position_revenue_model->getAllSalary(array('where' => '(position_revenue_start_date <= '.strtotime($batdau).' AND position_revenue_end_date >= '.strtotime($ketthuc).') OR (position_revenue_start_date <= '.strtotime($batdau).' AND (position_revenue_end_date IS NULL OR position_revenue_end_date = 0) )'),array('table'=>'position','where'=>'position=position_id'));
        foreach ($position_revenues as $revenue) {
            if ($revenue->position_name == "Sales") {
                $sale_direct[$revenue->position_revenue_type] = $revenue->position_revenue_percent;
            }
            else{
                $sale_indirect[$revenue->position][$revenue->position_revenue_type] = $revenue->position_revenue_percent;
            }
        }

        $position_profits = $position_profit_model->getAllSalary(array('where' => '(position_profit_start_date <= '.strtotime($batdau).' AND position_profit_end_date >= '.strtotime($ketthuc).') OR (position_profit_start_date <= '.strtotime($batdau).' AND (position_profit_end_date IS NULL OR position_profit_end_date = 0) )'));
        $position_profit = array();
        foreach ($position_profits as $profit) {
            $position_profit[$profit->position] = $profit->position_profit_percent;
        }

        $position_staffs = $position_staff_model->getAllSalary(array('where' => '(position_staff_start_date <= '.strtotime($batdau).' AND position_staff_end_date >= '.strtotime($ketthuc).') OR (position_staff_start_date <= '.strtotime($batdau).' AND (position_staff_end_date IS NULL OR position_staff_end_date = 0) )'));
        

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_import_model = $this->model->get('tireimportModel');
        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $receivable_model = $this->model->get('receivableModel');

        $data = array(
            'where' => ' (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') ',
        );

        $order_tires = $order_tire_model->getAllTire($data);

        $sanluong = array();
        foreach ($order_tires as $tire) {
            $sanluong[$tire->customer] = isset($sanluong[$tire->customer])?$sanluong[$tire->customer]+$tire->order_tire_number:$tire->order_tire_number;
        }

        $luong_doanhthu = array();
        $luong_loinhuan = array();

        $doanhthu = array();
        $loinhuan = 0;

        foreach ($order_tires as $tire) {
            if ($tire->customer_type == 1) {
                $doanhthu['cont'] = $tire->total;
            }
            else{
                $doanhthu['le'] = $tire->total;
            }

            if ($sanluong[$tire->customer] < 20) {
                $column = "tire_retail";
            }
            else if ($sanluong[$tire->customer] < 50) {
                $column = "tire_20";
            }
            else if ($sanluong[$tire->customer] < 100) {
                $column = "tire_50";
            }
            else if ($sanluong[$tire->customer] < 150) {
                $column = "tire_100";
            }
            else if ($sanluong[$tire->customer] < 200) {
                $column = "tire_150";
            }
            else{
                $column = "tire_cont";
            }
            
            $check_vuotgia = 0;
            $gia_vuot = 0;
            $gia_giam = 0;

            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            $von=0;
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.$tire->delivery_date.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($tire->order_number != "") {
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
                }

                $von += $l->tire_number*$gia;

                $data = array(
                    'where' => 'start_date <= '.$tire->delivery_date.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_price_discounts = $tire_price_discount_model->getAllTire($data);
                foreach ($tire_price_discounts as $tire_price_discount) {
                    if ($l->tire_price > $tire_price_discount->$column) {
                        $check_vuotgia += 1;
                        $gia_vuot += $l->tire_price - $tire_price_discount->$column;
                    }
                    else if ($l->tire_price < $tire_price_discount->$column) {
                        $check_vuotgia -= 1;
                        $gia_giam +=  $tire_price_discount->$column - $l->tire_price;
                    }
                }
            }
            $loinhuan += $tire->total-$tire->order_cost-$von;
            $loinhuan = $loinhuan-($loinhuan*20/100);

            $congno = 0;
            $receivables = $receivable_model->getAllCosts(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($receivables as $recei) {
                if ($recei->pay_money < $tire->total) {
                    $congno += $tire->total - $recei->pay_money;
                }
                else{
                    if ($recei->pay_date > ($tire->delivery_date+604800)) {
                        $congno += $tire->total - $recei->pay_money;
                    }
                }
                
            }

            $staffs = $staff_model->getStaffByWhere(array('account'=>$tire->sale));

            foreach ($position_staffs as $st) {
                if (isset($position_profit[$st->position])) {
                    $luong_loinhuan[$st->staff] = isset($luong_loinhuan[$st->staff])?$luong_loinhuan[$st->staff]+round(($position_profit[$st->position]/100)*$loinhuan):round(($position_profit[$st->position]/100)*$loinhuan);
                }

                //Trực tiếp bán
                if ($st->staff == $staffs->staff_id) {
                    if (isset($sale_direct[1])) {
                        if ($tire->customer_type != 1) {
                            $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[1]/100)*$doanhthu['le']):round(($sale_direct[1]/100)*$doanhthu['le']);
                        }
                    }
                    if (isset($sale_direct[2])) {
                        if ($tire->customer_type == 1) {
                            $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[2]/100)*$doanhthu['cont']):round(($sale_direct[2]/100)*$doanhthu['cont']);
                        }
                    }
                    if (isset($sale_direct[3])) {
                        if ($check_vuotgia > 0) {
                            $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[3]/100)*$gia_vuot):round(($sale_direct[3]/100)*$gia_vuot);
                        }
                    }
                    if (isset($sale_direct[4])) {
                        if ($check_vuotgia < 0) {
                            $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[4]/100)*$gia_giam):round(($sale_direct[4]/100)*$gia_giam);
                        }
                    }
                    
                    if (isset($sale_direct[5])) {
                        if ($congno > 0) {
                            $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[5]/100)*$congno):round(($sale_direct[5]/100)*$congno);
                        }
                    }
                    if (isset($sale_direct[6])) {
                    }
                    if (isset($sale_direct[7])) {
                    }
                }

                //Hưởng theo sales
                if (isset($sale_indirect[$st->position][1])) {
                    if ($tire->customer_type != 1) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][1]/100)*$doanhthu['le']):round(($sale_indirect[$st->position][1]/100)*$doanhthu['le']);
                    }
                }
                if (isset($sale_indirect[$st->position][2])) {
                    if ($tire->customer_type == 1) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][2]/100)*$doanhthu['cont']):round(($sale_indirect[$st->position][2]/100)*$doanhthu['cont']);
                    }
                }
                if (isset($sale_indirect[$st->position][3])) {
                    if ($check_vuotgia > 0) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][3]/100)*$gia_vuot):round(($sale_indirect[$st->position][3]/100)*$gia_vuot);
                    }
                }
                if (isset($sale_indirect[$st->position][4])) {
                    if ($check_vuotgia < 0) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][4]/100)*$gia_giam):round(($sale_indirect[$st->position][4]/100)*$gia_giam);
                    }
                }
                
                if (isset($sale_indirect[$st->position][5])) {
                    if ($congno > 0) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][5]/100)*$congno):round(($sale_indirect[$st->position][5]/100)*$congno);
                    }
                }
                if (isset($sale_indirect[$st->position][6])) {
                }
                if (isset($sale_indirect[$st->position][7])) {
                }
            }

            

        }

        $tire_sale_model = $this->model->get('tiresaleModel');
        $data = array(
            'where' => 'sell_price = 0 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $tire_sale_model->getAllTire($data);

        foreach ($sales as $tire) {
            $gia = 0;
            $data = array(
                'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $gia = $tire_import->tire_price;
            }

            foreach ($position_staffs as $st) {
                if ($tire->sale == $st->staff) {
                    if (isset($sale_direct[7])) {
                        $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_direct[7]/100)*($tire->volume*$gia)):round(($sale_direct[7]/100)*($tire->volume*$gia));
                    }
                }
                
                if (isset($sale_indirect[$st->position][7])) {
                    $luong_doanhthu[$st->staff] = isset($luong_doanhthu[$st->staff])?$luong_doanhthu[$st->staff]+round(($sale_indirect[$st->position][7]/100)*($tire->volume*$gia)):round(($sale_indirect[$st->position][7]/100)*($tire->volume*$gia));
                }
            }
        }

        $this->view->data['luong_doanhthu'] = $luong_doanhthu;
        $this->view->data['luong_loinhuan'] = $luong_loinhuan;


        $attendance_model = $this->model->get('attendanceModel');
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
            $sotieng = round(round($attendance->attendance_total, 2, PHP_ROUND_HALF_UP)/8,2);
            if ($attendance->check_in_1 != "" && $attendance->check_in_1 != 0) {
                $ngay = explode(':', $attendance->check_in_1);
            }
            else{
                $ngay = [7,30];
            }

            if ($ngay[0] > 11) {
                $ngay = [7,30];
            }
            
            $phut = (7*60)+35;
            $phuttre = ($ngay[0]*60)+$ngay[1];
            $tre = $phuttre>$phut?round((($phuttre-$phut)*2/60)/8,2):0;

            $arr_attend[$attendance->staff] = isset($arr_attend[$attendance->staff])?$arr_attend[$attendance->staff]+$sotieng-$tre:$sotieng-$tre;
        }

        $this->view->data['arr_attend'] = $arr_attend;


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

        /*************/
        $this->view->show('positionsalaryview/index');
    }
    public function revenue() {
        $this->view->disableLayout();

        $this->view->data['lib'] = $this->lib;

        $staff_id = $this->registry->router->param_id;
        $batdau = date('d-m-Y',$this->registry->router->page);
        $ketthuc = date('t-m-Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $staff_model = $this->model->get('staffModel');
        $position_staff_model = $this->model->get('positionstaffModel');
        $position_revenue_model = $this->model->get('positionrevenueModel');

        $sale_direct = array();
        $sale_indirect = array();

        $position_revenues = $position_revenue_model->getAllSalary(array('where' => '(position_revenue_start_date <= '.strtotime($batdau).' AND position_revenue_end_date >= '.strtotime($ketthuc).') OR (position_revenue_start_date <= '.strtotime($batdau).' AND (position_revenue_end_date IS NULL OR position_revenue_end_date = 0) )'),array('table'=>'position','where'=>'position=position_id'));
        foreach ($position_revenues as $revenue) {
            if ($revenue->position_name == "Sales") {
                $sale_direct[$revenue->position_revenue_type] = $revenue->position_revenue_percent;
            }
            else{
                $sale_indirect[$revenue->position][$revenue->position_revenue_type] = $revenue->position_revenue_percent;
            }
        }


        $position_staffs = $position_staff_model->getAllSalary(array('where' => 'staff = '.$staff_id.' AND ( (position_staff_start_date <= '.strtotime($batdau).' AND position_staff_end_date >= '.strtotime($ketthuc).') OR (position_staff_start_date <= '.strtotime($batdau).' AND (position_staff_end_date IS NULL OR position_staff_end_date = 0) ) )'),array('table'=>'position','where'=>'position=position_id'));

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_import_model = $this->model->get('tireimportModel');
        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $receivable_model = $this->model->get('receivableModel');

        $join = array('table'=>'customer','where'=>'customer = customer_id');

        $data = array(
            'where' => '(order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') ',
        );

        foreach ($position_staffs as $p) {
            if ($p->position_name == "Sales") {
                $data['where'] .= ' AND sale = (SELECT account FROM staff WHERE staff_id='.$staff_id.')';
            }
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $sanluong = array();
        foreach ($order_tires as $tire) {
            $sanluong[$tire->customer] = isset($sanluong[$tire->customer])?$sanluong[$tire->customer]+$tire->order_tire_number:$tire->order_tire_number;
        }

        $luong_doanhthu = array();
        $giavon = array();
        $doanhthu = array();
        $le = array();
        $vuot = array();
        $no = array();

        foreach ($order_tires as $tire) {
            if ($tire->customer_type == 1) {
                $doanhthu['cont'] = $tire->total;
            }
            else{
                $doanhthu['le'] = $tire->total;
            }

            if ($sanluong[$tire->customer] < 20) {
                $column = "tire_retail";
            }
            else if ($sanluong[$tire->customer] < 50) {
                $column = "tire_20";
            }
            else if ($sanluong[$tire->customer] < 100) {
                $column = "tire_50";
            }
            else if ($sanluong[$tire->customer] < 150) {
                $column = "tire_100";
            }
            else if ($sanluong[$tire->customer] < 200) {
                $column = "tire_150";
            }
            else{
                $column = "tire_cont";
            }
            
            $check_vuotgia = 0;
            $gia_vuot = 0;
            $gia_giam = 0;

            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            $von=0;
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.$tire->delivery_date.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($tire->order_number != "") {
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
                }

                $von += $l->tire_number*$gia;

                $data = array(
                    'where' => 'start_date <= '.$tire->delivery_date.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_price_discounts = $tire_price_discount_model->getAllTire($data);
                foreach ($tire_price_discounts as $tire_price_discount) {
                    if ($l->tire_price > $tire_price_discount->$column) {
                        $check_vuotgia += 1;
                        $gia_vuot += $l->tire_price - $tire_price_discount->$column;
                    }
                    else if ($l->tire_price < $tire_price_discount->$column) {
                        $check_vuotgia -= 1;
                        $gia_giam +=  $tire_price_discount->$column - $l->tire_price;
                    }
                }
            }

            $giavon[$tire->order_tire_id] = $von;
            

            $congno = 0;
            $receivables = $receivable_model->getAllCosts(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($receivables as $recei) {
                if ($recei->pay_money < $tire->total) {
                    $congno += $tire->total - $recei->pay_money;
                }
                else{
                    if ($recei->pay_date > ($tire->delivery_date+604800)) {
                        $congno += $tire->total - $recei->pay_money;
                    }
                }
                
            }

            $staffs = $staff_model->getStaffByWhere(array('account'=>$tire->sale));

            foreach ($position_staffs as $st) {
                //Trực tiếp bán
                if ($st->staff == $staffs->staff_id) {
                    if (isset($sale_direct[1])) {
                        if ($tire->customer_type != 1) {
                            $le[$tire->order_tire_id] = $sale_direct[1];
                            $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_direct[1]/100)*$doanhthu['le']):round(($sale_direct[1]/100)*$doanhthu['le']);
                        }   
                    }
                    if (isset($sale_direct[2])) {
                        if ($tire->customer_type == 1) {
                            $le[$tire->order_tire_id] = $sale_direct[2];
                            $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_direct[2]/100)*$doanhthu['cont']):round(($sale_direct[2]/100)*$doanhthu['cont']);
                        }
                    }
                    if (isset($sale_direct[3])) {
                        if ($check_vuotgia > 0) {
                            $vuot[$tire->order_tire_id] = $sale_direct[3];
                            $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_direct[3]/100)*$gia_vuot):round(($sale_direct[3]/100)*$gia_vuot);
                        }
                    }
                    if (isset($sale_direct[4])) {
                        if ($check_vuotgia < 0) {
                            $vuot[$tire->order_tire_id] = $sale_direct[4];
                            $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_direct[4]/100)*$gia_giam):round(($sale_direct[4]/100)*$gia_giam);
                        }
                    }
                    
                    if (isset($sale_direct[5])) {
                        if ($congno > 0) {
                            $no[$tire->order_tire_id] = $sale_direct[5];
                            $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_direct[5]/100)*$congno):round(($sale_direct[5]/100)*$congno);
                        }
                    }
                    if (isset($sale_direct[6])) {
                    }
                    if (isset($sale_direct[7])) {
                    }
                }

                //Hưởng theo sales
                if (isset($sale_indirect[$st->position][1])) {
                    if ($tire->customer_type != 1) {
                        $le[$tire->order_tire_id] = $sale_indirect[$st->position][1];
                        $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_indirect[$st->position][1]/100)*$doanhthu['le']):round(($sale_indirect[$st->position][1]/100)*$doanhthu['le']);
                    }
                }
                if (isset($sale_indirect[$st->position][2])) {
                    if ($tire->customer_type == 1) {
                        $le[$tire->order_tire_id] = $sale_indirect[$st->position][2];
                        $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_indirect[$st->position][2]/100)*$doanhthu['cont']):round(($sale_indirect[$st->position][2]/100)*$doanhthu['cont']);
                    }
                }
                if (isset($sale_indirect[$st->position][3])) {
                    if ($check_vuotgia > 0) {
                        $vuot[$tire->order_tire_id] = $sale_indirect[$st->position][3];
                        $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_indirect[$st->position][3]/100)*$gia_vuot):round(($sale_indirect[$st->position][3]/100)*$gia_vuot);
                    }
                }
                if (isset($sale_indirect[$st->position][4])) {
                    if ($check_vuotgia < 0) {
                        $vuot[$tire->order_tire_id] = $sale_indirect[$st->position][4];
                        $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_indirect[$st->position][4]/100)*$gia_giam):round(($sale_indirect[$st->position][4]/100)*$gia_giam);
                    }
                }
                
                if (isset($sale_indirect[$st->position][5])) {
                    if ($congno > 0) {
                        $no[$tire->order_tire_id] = $sale_indirect[$st->position][5];
                        $luong_doanhthu[$tire->order_tire_id] = isset($luong_doanhthu[$tire->order_tire_id])?$luong_doanhthu[$tire->order_tire_id]+round(($sale_indirect[$st->position][5]/100)*$congno):round(($sale_indirect[$st->position][5]/100)*$congno);
                    }
                }
                if (isset($sale_indirect[$st->position][6])) {
                }
                if (isset($sale_indirect[$st->position][7])) {
                }
            }
            

        }

        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['luong_doanhthu'] = $luong_doanhthu;
        $this->view->data['costs'] = $giavon;

        $tire_sale_model = $this->model->get('tiresaleModel');
        $data = array(
            'where' => 'sale='.$staff_id.' AND sell_price = 0 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc),
        );
        
        $sales = $tire_sale_model->getAllTire($data,$join);

        $luong_doanhthu_bh = array();
        $von_bh = array();
        foreach ($sales as $tire) {
            $gia = 0;
            $data = array(
                'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $gia = $tire_import->tire_price;
            }

            $von_bh[$tire->tire_sale_id] = $tire->volume*$gia;
            foreach ($position_staffs as $st) {
                if ($tire->sale == $st->staff) {
                    if (isset($sale_direct[7])) {
                        $luong_doanhthu_bh[$tire->tire_sale_id] = isset($luong_doanhthu_bh[$tire->tire_sale_id])?$luong_doanhthu_bh[$tire->tire_sale_id]+round(($sale_direct[7]/100)*($tire->volume*$gia)):round(($sale_direct[7]/100)*($tire->volume*$gia));
                    }
                }
                
                if (isset($sale_indirect[$st->position][7])) {
                    $luong_doanhthu_bh[$tire->tire_sale_id] = isset($luong_doanhthu_bh[$tire->tire_sale_id])?$luong_doanhthu_bh[$tire->tire_sale_id]+round(($sale_indirect[$st->position][7]/100)*($tire->volume*$gia)):round(($sale_indirect[$st->position][7]/100)*($tire->volume*$gia));
                }
            }
        }

        $this->view->data['luong_doanhthu_bh'] = $luong_doanhthu_bh;
        $this->view->data['costs2'] = $von_bh;
        $this->view->data['sales'] = $sales;

        $this->view->data['le'] = $le;
        $this->view->data['vuot'] = $vuot;
        $this->view->data['no'] = $no;


        /*************/
        $this->view->show('positionsalaryview/revenue');
    }
    public function profit() {
        $this->view->disableLayout();

        $this->view->data['lib'] = $this->lib;

        $staff_id = $this->registry->router->param_id;
        $batdau = date('d-m-Y',$this->registry->router->page);
        $ketthuc = date('t-m-Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        
        $position_staff_model = $this->model->get('positionstaffModel');
        $position_profit_model = $this->model->get('positionprofitModel');

        $position_profits = $position_profit_model->getAllSalary(array('where' => '(position_profit_start_date <= '.strtotime($batdau).' AND position_profit_end_date >= '.strtotime($ketthuc).') OR (position_profit_start_date <= '.strtotime($batdau).' AND (position_profit_end_date IS NULL OR position_profit_end_date = 0) )'));
        $position_profit = array();
        foreach ($position_profits as $profit) {
            $position_profit[$profit->position] = $profit->position_profit_percent;
        }

        $position_staffs = $position_staff_model->getAllSalary(array('where' => 'staff='.$staff_id.' AND ( (position_staff_start_date <= '.strtotime($batdau).' AND position_staff_end_date >= '.strtotime($ketthuc).') OR (position_staff_start_date <= '.strtotime($batdau).' AND (position_staff_end_date IS NULL OR position_staff_end_date = 0) ))'));

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_import_model = $this->model->get('tireimportModel');

        $join = array('table'=>'customer','where'=>'customer = customer_id');

        $data = array(
            'where' => '(order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') ',
        );

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $luong_loinhuan = array();
        $giavon = array();
        $doanhthu = array();

        foreach ($order_tires as $tire) {
            
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            $von=0;
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.$tire->delivery_date.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($tire->order_number != "") {
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
                }

                $von += $l->tire_number*$gia;

            }
            $giavon[$tire->order_tire_id] = $von;

            $loinhuan += $tire->total-$tire->order_cost-$von;
            $loinhuan = $loinhuan-($loinhuan*20/100);


            foreach ($position_staffs as $st) {
                if (isset($position_profit[$st->position])) {
                    $luong_loinhuan[$tire->order_tire_id] = round(($position_profit[$st->position]/100)*$loinhuan);
                }
            }

        }

        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['luong_loinhuan'] = $luong_loinhuan;
        $this->view->data['costs'] = $giavon;



        /*************/
        $this->view->show('positionsalaryview/profit');
    }

    

}
?>