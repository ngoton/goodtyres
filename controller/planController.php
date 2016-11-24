<?php
Class planController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        $plan_event_model = $this->model->get('planeventModel');
        $plans = $plan_event_model->getAllPlan(array('where'=>'user_create='.$_SESSION['userid_logined']));
        $this->view->data['plans'] = $plans;

        /*************/
        $this->view->show('plan/index');
    }
    public function add(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $plan_event_model = $this->model->get('planeventModel');
            $data = array(
                'plan_event_name' => trim($_POST['plan_event_name']),
                'color' => trim($_POST['color']),
                'user_create' => $_SESSION['userid_logined'],
            );
            if ($plan_event_model->getPlanByWhere(array('plan_event_name'=>$data['plan_event_name'],'user_create'=>$data['user_create']))) {
                echo json_encode(array('status'=>'Tên công việc đã tồn tại','planid'=>0));
                return false;
            }
            else{
                $plan_event_model->createPlan($data);
                echo json_encode(array('status'=>'Thành công','planid'=>$plan_event_model->getLastPlan()->plan_event_id));

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$plan_event_model->getLastPlan()->plan_event_id."|plan|".implode("-",$data)."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);
                return true;
            }
        }
    }
    public function delete(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $plan_event_model = $this->model->get('planeventModel');
            $plan_event_list_model = $this->model->get('planeventlistModel');

            $plan_event_list_model->queryPlan('DELETE FROM plan_event_list WHERE plan_event = '.$_POST['data']);
            $plan_event_model->deletePlan($_POST['data']);
            echo json_encode(array('status'=>'Thành công','planid'=>$_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|plan|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    public function listevent(){
        $plan_event_list_model = $this->model->get('planeventlistModel');
        $data = array(
            'where'=>'user_create='.$_SESSION['userid_logined'],
        );
        $join = array('table'=>'plan_event','where'=>'plan_event = plan_event_id');

        $events = array();
        $plans = $plan_event_list_model->getAllPlan($data,$join);
        foreach ($plans as $plan) {
            $e = array();
            $e['id'] = $plan->plan_event_list_id;
            $e['title'] = $plan->plan_event_name;
            $e['start'] = date('Y-m-d',$plan->start_date).'T'.date("H:i:s", $plan->start_date);
            $e['end'] = date('Y-m-d',$plan->end_date).'T'.date("H:i:s", $plan->end_date);
            $e['allDay'] = ($plan->all_day == 1) ? true : false;
            $e['backgroundColor'] = $plan->color;
            $e['borderColor'] = $plan->color;
            array_push($events, $e);
        }
        echo json_encode($events);
    }
    public function addevent(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $plan_event_list_model = $this->model->get('planeventlistModel');
            $data = array(
                'plan_event' => trim($_POST['plan_event']),
                'start_date' => strtotime(trim($_POST['start_date'])),
                'end_date' => strtotime(trim($_POST['end_date'])),
                'all_day' => trim($_POST['all_day']),
                'staff' => $_SESSION['userid_logined'],
            );
            
            $plan_event_list_model->createPlan($data);
            echo json_encode(array('status'=>'Thành công','planid'=>$plan_event_list_model->getLastPlan()->plan_event_list_id));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$plan_event_list_model->getLastPlan()->plan_event_list_id."|plan_list|".implode("-",$data)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
            return true;
            
        }
    }
    public function editevent(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $plan_event_list_model = $this->model->get('planeventlistModel');
            $data = array(
                'start_date' => strtotime(trim($_POST['start_date'])),
                'end_date' => strtotime(trim($_POST['end_date'])),
                'all_day' => trim($_POST['all_day']),
            );
            
            $plan_event_list_model->updatePlan($data,array('plan_event_list_id'=>$_POST['data']));
            echo json_encode(array('status'=>'Thành công','planid'=>$_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['data']."|".implode("-",$data)."|plan_list|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
            return true;
            
        }
    }
    public function deleteevent(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $plan_event_list_model = $this->model->get('planeventlistModel');

            $plan_event_list_model->deletePlan($_POST['data']);
            echo json_encode(array('status'=>'Thành công','planid'=>$_POST['data']));
            
            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|plan_list|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    

}
?>