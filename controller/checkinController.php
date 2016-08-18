<?php
Class checkinController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Điểm danh';

        $mon = date( 'd-m-Y', strtotime( 'monday this week' ) );
        $sun = date( 'd-m-Y', strtotime( 'sunday this week' ) );

        $staff_model = $this->model->get('staffModel');
        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $checkin_model = $this->model->get('checkinModel');
        
        $data = array(
            'where' => 'staff = '.$staff->staff_id.' AND checkin_date >= '.strtotime($mon).' AND checkin_date <= '.strtotime($sun),
        );

        $checkins = $checkin_model->getAllCheckin($data);

        $checkin = array();

        foreach ($checkins as $check) {
            if ($check->checkin_1 != "") {
                $checkin['in_1'][date('d-m-Y',$check->checkin_1)][(int)date('H',$check->checkin_1)] = $check->checkin_1;
                if (date('H',$check->checkin_1) >= 21) {
                    $checkin['in_1'][date('d-m-Y',$check->checkin_1)][21] = $check->checkin_1;
                }
                if (date('H',$check->checkin_1) < 7) {
                    $checkin['in_1'][date('d-m-Y',$check->checkin_1)][6] = $check->checkin_1;
                }
            }
            if ($check->checkout_1 != "") {
                $checkin['out_1'][date('d-m-Y',$check->checkout_1)][(int)date('H',$check->checkout_1)] = $check->checkout_1;
                if (date('H',$check->checkout_1) >= 21) {
                    $checkin['out_1'][date('d-m-Y',$check->checkout_1)][21] = $check->checkout_1;
                }
                if (date('H',$check->checkout_1) < 7) {
                    $checkin['out_1'][date('d-m-Y',$check->checkout_1)][6] = $check->checkout_1;
                }
            }
            if ($check->checkin_2 != "") {
                $checkin['in_2'][date('d-m-Y',$check->checkin_2)][(int)date('H',$check->checkin_2)] = $check->checkin_2;
                if (date('H',$check->checkin_2) >= 21) {
                    $checkin['in_2'][date('d-m-Y',$check->checkin_2)][21] = $check->checkin_2;
                }
                if (date('H',$check->checkin_2) < 7) {
                    $checkin['in_2'][date('d-m-Y',$check->checkin_2)][6] = $check->checkin_2;
                }
            }
            if ($check->checkout_2 != "") {
                $checkin['out_2'][date('d-m-Y',$check->checkout_2)][(int)date('H',$check->checkout_2)] = $check->checkout_2;
                if (date('H',$check->checkout_2) >= 21) {
                    $checkin['out_2'][date('d-m-Y',$check->checkout_2)][21] = $check->checkout_2;
                }
                if (date('H',$check->checkout_2) < 7) {
                    $checkin['out_2'][date('d-m-Y',$check->checkout_2)][6] = $check->checkout_2;
                }
            }
            
        }
        $this->view->data['checkin'] = $checkin;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checkin/index');
    }

    public function add(){
        if (isset($_POST['time'])) {
            $checkin_model = $this->model->get('checkinModel');
            $staff_model = $this->model->get('staffModel');
            $attendance_model = $this->model->get('attendanceModel');

            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $data = array(
                'staff' => $staff->staff_id,
                'checkin_date' => strtotime(date('d-m-Y')),
            );
            $data_attend = array(
                'attendance_date' => $data['checkin_date'],
                'attendance_day' => $this->sw_get_current_weekday(date('d-m-Y')),
                'staff' => $data['staff'],
            );

            if ($_POST['data'] == 'in_1') {
                $data['checkin_1'] = strtotime(date('d-m-Y H:i:s'));
                $data_attend['check_in_1'] = $_POST['time'];
            }
            if ($_POST['data'] == 'out_1') {
                $data['checkout_1'] = strtotime(date('d-m-Y H:i:s'));
                $data_attend['check_out_1'] = $_POST['time'];
            }
            if ($_POST['data'] == 'in_2') {
                $data['checkin_2'] = strtotime(date('d-m-Y H:i:s'));
                $data_attend['check_in_2'] = $_POST['time'];
            }
            if ($_POST['data'] == 'out_2') {
                $data['checkout_2'] = strtotime(date('d-m-Y h:i:s'));
                $data_attend['check_out_2'] = $_POST['time'];
            }

            //$hourdiff = round((strtotime($time1) - strtotime($time2))/3600, 1);

            $check = $checkin_model->queryCheckin('SELECT * FROM checkin WHERE checkin_date >= '.$data['checkin_date'].' AND checkin_date <= '.$data['checkin_date'].' AND staff = '.$data['staff']);
            if (!$check) {
                $checkin_model->createCheckin($data);

                $data_attend['attendance_total'] = 0;
                $attendance_model->createAttendance($data_attend);
            }
            else{
                foreach ($check as $c) {
                    $checkin_model->updateCheckin($data,array('checkin_id'=>$c->checkin_id));

                    $attendance = $attendance_model->getAttendanceByWhere(array('staff'=>$c->staff,'attendance_date'=>$c->checkin_date));
                    
                    if ($_POST['data'] == 'out_1') {
                        if ($attendance->check_in_1 != "") {
                            $time2 = $attendance->check_in_1;
                            $hourdiff = round((strtotime($_POST['time']) - strtotime($time2))/3600, 1);
                        }
                    }
                    if ($_POST['data'] == 'in_2') {
                        if ($attendance->check_in_1 != "" && $attendance->check_out_1 != "") {
                            $hourdiff = round((strtotime($attendance->check_out_1) - strtotime($attendance->check_in_1))/3600, 1);
                        }
                        else if ($attendance->check_in_1 != "" && $attendance->check_out_1 == "") {
                            $time2 = $attendance->check_in_1;
                            $hourdiff = round((strtotime('12:00') - strtotime($time2))/3600, 1);
                        }
                    }
                    if ($_POST['data'] == 'out_2') {
                        if ($attendance->check_in_2 != "") {
                            $time2 = $attendance->check_in_2;
                            $hourdiff = round((strtotime($_POST['time']) - strtotime($time2))/3600, 1);
                            if ($attendance->check_in_1 != "" && $attendance->check_out_1 != "") {
                                $hourdiff = $hourdiff + round((strtotime($attendance->check_out_1) - strtotime($attendance->check_in_1))/3600, 1);
                            }
                            else if ($attendance->check_in_1 != "" && $attendance->check_out_1 == "") {
                                $hourdiff = $hourdiff + round((strtotime("12:00") - strtotime($attendance->check_in_1))/3600, 1);
                            }
                        }
                        else{
                            if ($attendance->check_out_1 != "") {
                                $hourdiff = round((strtotime($_POST['time']) - strtotime("13:00"))/3600, 1);
                                if ($attendance->check_in_1 != "") {
                                    $hourdiff = $hourdiff + round((strtotime($attendance->check_out_1) - strtotime($attendance->check_in_1))/3600, 1);
                                }
                            }
                            else{
                                if ($attendance->check_in_1 != "") {
                                    $time2 = $attendance->check_in_1;
                                    $hourdiff1 = round((strtotime("12:00") - strtotime($time2))/3600, 1);
                                    $hourdiff2 = round((strtotime($_POST['time']) - strtotime("13:00"))/3600, 1);
                                    $hourdiff = $hourdiff1+$hourdiff2;
                                }
                            }
                        }
                    }


                    $data_attend['attendance_total'] = $hourdiff;

                    $attendance_model->updateAttendance($data_attend,array('attendance_id'=>$attendance->attendance_id));
                }
                
            }
            
        }
        return true;
    }
    function sw_get_current_weekday($date) {
        $weekday = date("l",strtotime($date));
        $weekday = strtolower($weekday);
        switch($weekday) {
            case 'monday':
                $weekday = 'Hai';
                break;
            case 'tuesday':
                $weekday = 'Ba';
                break;
            case 'wednesday':
                $weekday = 'Tư';
                break;
            case 'thursday':
                $weekday = 'Năm';
                break;
            case 'friday':
                $weekday = 'Sáu';
                break;
            case 'saturday':
                $weekday = 'Bảy';
                break;
            default:
                $weekday = 'CN';
                break;
        }
        return $weekday;
    }
    
}
?>