<?php

Class positionrulereportController Extends baseController {

    public function index() {


    }
    public function checkvotesalary(){
        $position_staff_evaluate_vote_model = $this->model->get('positionstaffevaluatevoteModel');
        $staff_model = $this->model->get('staffModel');

        $batdau = '01-'.date('m-Y');
        $ketthuc = date('t-m-Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $thangtruoc = date('d-m-Y', strtotime(date('m-Y')." -1 month"));
        $batdau_truoc = date('d-m-Y', strtotime('first day of last month'));
        $ketthuc_truoc = date('t-m-Y',strtotime($batdau_truoc));
        $ngayketthuc_truoc = date('d-m-Y', strtotime($ketthuc_truoc. ' + 1 days'));

        $staff_info = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $data = array(
            'where' => 'staff_vote = '.$staff_info->staff_id.' AND position_staff_evaluate_vote_date >= '.strtotime($batdau_truoc).' AND position_staff_evaluate_vote_date < '.strtotime($ngayketthuc_truoc),
        );

        $reports = $position_staff_evaluate_vote_model->getAllSalary($data);
        
        if (count($reports)>0) {
            $result = array(
                'check'=>0,
                'result'=>null,
            );
        }
        else{
            $data = array(
                'where' => 'position != "Director" AND position != "Cố vấn" AND position != "_" AND staff_id != '.$staff_info->staff_id.' AND ((start_date < '.strtotime($ngayketthuc_truoc).' AND end_date >= '.strtotime($ngayketthuc_truoc).') OR (start_date < '.strtotime($ngayketthuc_truoc).' AND (end_date IS NULL OR end_date = 0) ))',
            );
            $staffs = $staff_model->getAllStaff($data);

            $str = '<center><b>THÁNG '.date('m/Y',strtotime($batdau_truoc)).'</b></center>';
            $str .= '<table id="tb_vote" class="table_data"><tr><th>Nhân viên/Mức đánh giá</th><th>+25%</th><th>+20%</th><th>+15%</th><th>+10%</th><th>+5%</th><th>0%</th><th>-5%</th><th>-10%</th><th>-15%</th><th>-20%</th><th>-25%</th></tr>';
            foreach ($staffs as $staff) {
                $str .= '<tr><td>'.$staff->staff_name.'</td><td><input type="radio" alt="5" data="10" value="25" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="4" data="8" value="20" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="3" data="6" value="15" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="2" data="4" value="10" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="1" data="2" value="5" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="0" data="1" value="0" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="1" data="3" value="-5" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="2" data="5" value="-10" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="3" data="7" value="-15" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="4" data="9" value="-20" name="check_'.$staff->staff_id.'"></td><td><input type="radio" alt="5" data="11" value="-25" name="check_'.$staff->staff_id.'"></td></tr>';
            }
            $str .= '</table>';

            $result = array(
                'check'=>1,
                'result'=>$str,
            );
        }

        echo json_encode($result);
    }
    public function votesalarystaff(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $position_staff_evaluate_model = $this->model->get('positionstaffevaluateModel');
            $position_staff_evaluate_vote_model = $this->model->get('positionstaffevaluatevoteModel');
            $staff_model = $this->model->get('staffModel');
            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $staffs = $_POST['staff'];

            foreach ($staffs as $v) {
                $data = array(
                    'position_staff_evaluate_vote_date' => strtotime(date('d-m-Y', strtotime('last day of last month'))),
                    'staff' => $v['staff_id'],
                    'evaluate_percent' => $v['percent'],
                    'staff_vote' => $staff->staff_id,
                );
                $position_staff_evaluate_vote_model->createSalary($data);

                $evaluates = $position_staff_evaluate_vote_model->getAllSalary(array('where'=>'staff='.$v['staff_id'].' AND position_staff_evaluate_vote_date='.$data['position_staff_evaluate_vote_date']));
                $percent = 0;
                $total = 0;
                foreach ($evaluates as $evaluate) {
                    $percent += $evaluate->evaluate_percent;
                    $total++;
                }
                $data_evaluate = array(
                    'position_staff_evaluate_date' => $data['position_staff_evaluate_vote_date'], 
                    'staff' => $data['staff'],
                    'position_staff_evaluate_percent' => round($percent/$total,1),
                );
                if ($position_staff_evaluate_model->getSalaryByWhere(array('staff'=>$data['staff'],'position_staff_evaluate_date'=>$data['position_staff_evaluate_vote_date']))) {
                    $position_staff_evaluate_model->updateSalary($data_evaluate,array('staff'=>$data['staff'],'position_staff_evaluate_date'=>$data['position_staff_evaluate_vote_date']));
                }
                else{
                    $position_staff_evaluate_model->createSalary($data_evaluate);
                }
                
            }
        }
    }
    public function checkvote(){
        $position_rule_report_model = $this->model->get('positionrulereportModel');
        $staff_model = $this->model->get('staffModel');

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $data = array(
            'where' => 'position_rule_report_id NOT IN (SELECT position_rule_report FROM position_rule_report_staff WHERE staff = '.$staff->staff_id.')',
        );
        $join = array('table'=>'staff,position_rule','where'=>'staff=staff_id AND rule=position_rule_id');

        $reports = $position_rule_report_model->getAllSalary($data,$join);
        $result = array(
            'check'=>0,
            'result'=>null,
            'val'=>null,
        );

        foreach ($reports as $report) {
            $result = array(
                'check'=>1,
                'result'=>'Ngày '.date('d/m/Y',$report->position_rule_report_date).': '.$report->staff_name.' vi phạm <b>'.$report->position_rule_name.'</b>',
                'val'=>$report->position_rule_report_id,
            );
        }

        echo json_encode($result);
    }

    public function vote(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $position_rule_apply_model = $this->model->get('positionruleapplyModel');
            $position_rule_report_model = $this->model->get('positionrulereportModel');
            $position_rule_report_staff_model = $this->model->get('positionrulereportstaffModel');
            $staff_model = $this->model->get('staffModel');

            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $id_report = $_POST['data'];

            $reports = $position_rule_report_model->getSalary($id_report);

            if ($_POST['vote'] == 1) {
                $position_rule_report_model->updateSalary(array('agree'=>$reports->agree+1),array('position_rule_report_id'=>$id_report));
            }
            else{
                $position_rule_report_model->updateSalary(array('disagree'=>$reports->disagree+1),array('position_rule_report_id'=>$id_report));
            }

            $data_report = array(
                'position_rule_report' => $id_report,
                'staff' => $staff->staff_id,
                'position_rule_report_staff_date' => strtotime(date('d-m-Y')),
            );

            $position_rule_report_staff_model->createSalary($data_report);

            $reports = $position_rule_report_model->getSalary($id_report);

            if ($reports->agree >= 3 && $reports->agree > $reports->disagree) {
                $data_apply = array(
                    'position_rule_apply_date' => $reports->position_rule_report_date,
                    'position_rule_apply_number' => 1,
                    'staff' => $reports->staff,
                    'position_rule_apply_source' => 3,
                    'position_rule' => $reports->rule,
                );
                $position_rule_apply_model->createSalary($data_apply);
            }
            else{
                $position_rule_apply_model->querySalary('DELETE FROM position_rule_apply WHERE staff='.$reports->staff.' AND position_rule='.$reports->rule.' AND position_rule_apply_date='.$reports->position_rule_report_date);
            }
        }
    }

    public function getrule(){

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $position_rule_model = $this->model->get('positionruleModel');

            $rules = $position_rule_model->getAllSalary();

            $str = "";
            foreach ($rules as $rule) {
               $str .= '<option value="'.$rule->position_rule_id.'">'.$rule->position_rule_name.'</option>';
            }

            echo $str;
        }

    }

    public function getstaff(){

        if (!isset($_SESSION['userid_logined'])) {

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

                echo '<li onclick="set_item_staff_report(\''.$rs->staff_id.'\',\''.$rs->staff_name.'\')">'.$staff_name.'</li>';

            }

        }

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }


        if (isset($_POST['yes'])) {

            $position_rule_report_model = $this->model->get('positionrulereportModel');
            $position_rule_report_staff_model = $this->model->get('positionrulereportstaffModel');

            $staff_model = $this->model->get('staffModel');

            $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $data = array(

                        'staff' => trim($_POST['staff']),

                        'position_rule_report_date' => strtotime(date('d-m-Y')),

                        'rule' => trim($_POST['position_rule']),

                        'agree' => 1,

                        );

            if ($_POST['yes'] != "") {

                    $position_rule_report_model->updateSalary($data,array('position_rule_report_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|position_rule_report|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{


                    $position_rule_report_model->createSalary($data);

                    $id_report = $position_rule_report_model->getLastSalary()->position_rule_report_id;

                    $data_report = array(
                        'position_rule_report' => $id_report,
                        'staff' => $staff->staff_id,
                        'position_rule_report_staff_date' => $data['position_rule_report_date'],
                    );

                    $position_rule_report_staff_model->createSalary($data_report);

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$position_rule_report_model->getLastSalary()->position_rule_report_id."|position_rule_report|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);


            }

                    

        }

    }



    

    



    public function delete(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $position_staff_model = $this->model->get('positionstaffModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $position_staff_model->deleteSalary($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|position_staff|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                return true;

            }

            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|driver|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



                return $position_staff_model->deleteSalary($_POST['data']);

            }

            

        }

    }



    


}

?>