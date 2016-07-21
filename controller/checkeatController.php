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

            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $data = array(
                'staff' => $staff->staff_id,
                'checkin_date' => strtotime(date('d-m-Y')),
            );
            if ($_POST['data'] == 'in_1') {
                $data['checkin_1'] = strtotime(date('d-m-Y H:i:s'));
            }
            if ($_POST['data'] == 'out_1') {
                $data['checkout_1'] = strtotime(date('d-m-Y H:i:s'));
            }
            if ($_POST['data'] == 'in_2') {
                $data['checkin_2'] = strtotime(date('d-m-Y H:i:s'));
            }
            if ($_POST['data'] == 'out_2') {
                $data['checkout_2'] = strtotime(date('d-m-Y h:i:s'));
            }

            $check = $checkin_model->queryCheckin('SELECT * FROM checkin WHERE checkin_date >= '.$data['checkin_date'].' AND checkin_date <= '.$data['checkin_date'].' AND staff = '.$data['staff']);
            if (!$check) {
                $checkin_model->createCheckin($data);
            }
            else{
                foreach ($check as $c) {
                    $checkin_model->updateCheckin($data,array('checkin_id'=>$c->checkin_id));
                }
                
            }
            
        }
        return true;
    }
    
}
?>