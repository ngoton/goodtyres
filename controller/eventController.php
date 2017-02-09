<?php
Class eventController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lịch gửi mail';

        $event_model = $this->model->get('eventModel');
        $events = $event_model->getAllEvent();
        $this->view->data['events'] = $events;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'event_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $join = array('table'=>'event','where'=>'event=event_id');

        $event_run_model = $this->model->get('eventrunModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        
        $tongsodong = count($event_run_model->getAllEvent($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        
        if ($keyword != '') {
            $search = ' AND ( event_name LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        $this->view->data['event_runs'] = $event_run_model->getAllEvent($data,$join);

        $this->view->data['lastID'] = isset($event_run_model->getLastEvent()->event_run_id)?$event_run_model->getLastEvent()->event_run_id:0;

        /*************/
        $this->view->show('event/index');
    }
    public function add(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $event_model = $this->model->get('eventModel');
            $data = array(
                'event_name' => trim($_POST['event_name']),
            );
            if ($event_model->getEventByWhere(array('event_name'=>$data['event_name']))) {
                echo json_encode(array('status'=>'Tên sự kiện đã tồn tại','eventid'=>0));
                return false;
            }
            else{
                $event_model->createEvent($data);
                echo json_encode(array('status'=>'Thành công','eventid'=>$event_model->getLastEvent()->event_id));

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$event_model->getLastEvent()->event_id."|event|".implode("-",$data)."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);
                return true;
            }
        }
    }
    public function addevent() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lịch gửi mail';

        $event_model = $this->model->get('eventModel');
        $events = $event_model->getAllEvent();
        $this->view->data['events'] = $events;


        /*************/
        $this->view->show('event/addevent');
    }
    public function addeventlist(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $event_run = $this->model->get('eventrunModel');

            $data = array(
                        'event_content' => stripslashes(trim($_POST['event_content'])),
                        'event_frequently' => trim($_POST['event_frequently']),
                        'start_date' => strtotime(trim($_POST['start_date'])),
                        'end_date' => strtotime(trim($_POST['end_date'])),
                        'event' => trim($_POST['event_name']),
                        );


            if ($_POST['yes'] != "") {

                    $event_run->updateEvent($data,array('event_run_id' => $_POST['yes']));

                    /*Log*/
                    /**/

                    $mess = array(
                        'msg' => 'Cập nhật thành công',
                        'id' => $_POST['yes'],
                    );

                    echo json_encode($mess);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|event_run|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                    
                
                
            }
            else{

                    $event_run->createEvent($data);

                    /*Log*/
                    /**/

                    $mess = array(
                        'msg' => 'Thêm thành công',
                        'id' => $event_run->getLastEvent()->event_run_id,
                    );

                    echo json_encode($mess);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$event_run->getLastEvent()->event_run_id."|event_run|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                    
                
                
            }
                    
        }
    }
    public function editevent($id){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (!$id) {
            return $this->view->redirect('event');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Cập nhật bài viết';

        $event_run_model = $this->model->get('eventrunModel');
        $event_run = $event_run_model->getEvent($id);
        $this->view->data['event_run'] = $event_run;

        if (!$event_run) {
            return $this->view->redirect('event_run');
        }

        $event_model = $this->model->get('eventModel');
        $events = $event_model->getAllEvent();
        $this->view->data['events'] = $events;

        $this->view->show('event/editevent');
    }
    public function eventrun(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $event_run_model = $this->model->get('eventrunModel');
            $event_run_model->updateEvent(array('event_status'=>trim($_POST['val'])),array('event_run_id'=>$_POST['data']));
            echo "Thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."run"."|".$_POST['val']."|event_run|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    public function listevent($id){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (!$id) {
            return $this->view->redirect('event');
        }

        $this->view->data['lib'] = $this->lib;

        $event_mail_model = $this->model->get('eventmailModel');
        $event_mails = $event_mail_model->getAllEvent(array('where'=>'event_run='.$id,'order_by'=>'event_mail_date ASC, email_customer ASC'));
        $this->view->data['event_mails'] = $event_mails;

        $this->view->show('event/listevent');
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $event_run_model = $this->model->get('eventrunModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $event_run_model->deleteEvent($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|event_run|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $event_run_model->deleteEvent($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|event_run|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }
    public function deleteevent(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $event_model = $this->model->get('eventModel');
            $event_run_model = $this->model->get('eventrunModel');

            $event_run_model->queryEvent('DELETE FROM event_run WHERE event = '.$_POST['data']);
            $event_model->deleteEvent($_POST['data']);
            echo json_encode(array('status'=>'Thành công','eventid'=>$_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|event|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    
    

}
?>