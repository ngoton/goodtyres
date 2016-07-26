<?php
Class checkeatController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng cơm';

        $batdau = '01-'.date( 'm-Y');
        $ketthuc = date( 't-m-Y');

        $staff_model = $this->model->get('staffModel');
        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $checkeat_model = $this->model->get('checkeatModel');
        
        $data = array(
            'where' => 'staff = '.$staff->staff_id.' AND checkeat_date >= '.strtotime($batdau).' AND checkeat_date <= '.strtotime($ketthuc),
        );

        $checkeats = $checkeat_model->getAllCheckeat($data);

        $checkeat = array();
        $tongngay = 0;
        foreach ($checkeats as $check) {
            $checkeat[(int)date('dmY',$check->checkeat_date)] = $check->number;
            if ($check->number > 0) {
                $tongngay++;
            }
        }
        $this->view->data['checkeat'] = $checkeat;
        $this->view->data['tongngay'] = $tongngay;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checkeat/index');
    }

    public function add(){
        if (isset($_POST['number'])) {
            $checkeat_model = $this->model->get('checkeatModel');
            $staff_model = $this->model->get('staffModel');
            $eating_model = $this->model->get('eatingModel');

            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $data = array(
                'staff' => $staff->staff_id,
                'checkeat_date' => strtotime($_POST['date']),
                'number' => $_POST['number'],
            );
            $yesterday = date('d-m-Y', strtotime('-1 day', $data['checkeat_date']));
            $tomorow = date('d-m-Y', strtotime('+1 day', $data['checkeat_date']));
            $check = $checkeat_model->queryCheckeat('SELECT * FROM checkeat WHERE checkeat_date > '.strtotime($yesterday).' AND checkeat_date < '.strtotime($tomorow).' AND staff = '.$data['staff']);
            if (!$check) {
                $checkeat_model->createCheckeat($data);

                $check = $eating_model->queryEating('SELECT * FROM eating WHERE create_time >= '.strtotime('01-'.date('m-Y',$data['checkeat_date'])).' AND create_time <= '.strtotime(date('t-m-Y',$data['checkeat_date'])).' AND staff = '.$data['staff']);
                if (!$check) {
                    $price = 20000;
                    $data = array(
                        'staff' => $staff->staff_id,
                        'eating_day' => 1,
                        'eating_number' => $data['number'],
                        'eating_price' => $price,
                        'eating_total' => $data['number']*$price,
                        'eating_staff_total' => ($data['number']*$price)/2,
                        'create_time' => strtotime('01-'.date('m-Y',$data['checkeat_date'])),
                    );
                    $eating_model->createEating($data);
                }
                else{
                    foreach ($check as $c) {
                        $data = array(
                            'eating_day' => $c->eating_day+1,
                            'eating_number' => $c->eating_number+$data['number'],
                            'eating_total' => $c->eating_total+($data['number']*$c->eating_price),
                            'eating_staff_total' => $c->eating_staff_total+($data['number']*$c->eating_price)/2,
                        );
                        $eating_model->updateEating($data,array('eating_id'=>$c->eating_id));
                    }
                    
                }
            }
            else{
                foreach ($check as $c) {
                    $checkeat_model->updateCheckeat($data,array('checkeat_id'=>$c->checkeat_id));

                    $check2 = $eating_model->queryEating('SELECT * FROM eating WHERE create_time >= '.strtotime('01-'.date('m-Y',$data['checkeat_date'])).' AND create_time <= '.strtotime(date('t-m-Y',$data['checkeat_date'])).' AND staff = '.$data['staff']);
                    
                    foreach ($check2 as $c2) {
                        $data2 = array(
                            'eating_number' => $c2->eating_number-$c->number+$data['number'],
                            'eating_total' => $c2->eating_total-($c->number*$c2->eating_price)+($data['number']*$c2->eating_price),
                            'eating_staff_total' => $c2->eating_staff_total-(($c->number*$c2->eating_price)/2)+($data['number']*$c2->eating_price)/2,
                        );
                        if ($data['number'] == 0) {
                            $data2['eating_day'] = $c2->eating_day-1;
                        }
                        else if ($data['number'] > 0 && $c->number == 0) {
                            $data2['eating_day'] = $c2->eating_day+1;
                        }
                        $eating_model->updateEating($data2,array('eating_id'=>$c2->eating_id));
                    }
                }
                
            }
            
        }
        return true;
    }
    
}
?>