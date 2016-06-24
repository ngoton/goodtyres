<?php
Class workingController Extends baseController {
    
   public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'report_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
        }


        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $join = array('table'=>'staff','where'=>'report_staff=staff_id');
        $work_model = $this->model->get('reportModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'report_start_time >= '.strtotime($batdau).' AND report_start_time <= '.strtotime($ketthuc),
        );
        
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND report_staff = '.$staff->staff_id;
        }

        $tongsodong = count($work_model->getAllReport($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;



        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'report_start_time >= '.strtotime($batdau).' AND report_start_time <= '.strtotime($ketthuc),
            );
        
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND report_staff = '.$staff->staff_id;
        }

        if ($keyword != '') {
            $search = '( report_title LIKE "%'.$keyword.'%" 
                    OR staff_name LIKE "%'.$keyword.'%"  
                    OR report_comment LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllReport($data,$join);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastReport()->report_id)?$work_model->getLastReport()->report_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('working/index');
    }


    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $work = $this->model->get('reportModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $work->deleteReport($data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|report|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $work->deleteReport($_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|report|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function complete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $work = $this->model->get('workModel');
            $work_update = $this->model->get('workupdateModel');
           
            $work_data = $work->getWork($_POST['data']);

            $mang = $this->getStartAndEndDate(date('W',strtotime($_POST['date'])),date('Y',strtotime($_POST['date'])));
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $check_complete = $work_update->queryWork('SELECT * FROM work_update WHERE work = '.$work_data->work_id.' AND work_update_date >= '.strtotime($batdau).' AND work_update_date <= '.strtotime($ketthuc));

            if ($check_complete) {
                echo 'Exist!';
                return false;
            }
            else{
                $work->updateWork(array('complete'=>strtotime($_POST['date'])),array('work_id'=>$work_data->work_id));
            
                $data = array(
                            'work_update_deadline' => $work_data->deadline,
                            'work_update_date' => strtotime($_POST['date']),
                            'work_update_hour' => $work_data->hour,
                            'work_update_comment' => trim($_POST['comment']),
                            'work_update_owner' => $work_data->owner,
                            'work_update_contributor' => $work_data->contributor,
                            'work' => $work_data->work_id,
                            );
                $work_update->createWork($data);

                echo "Success!";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$work_update->getLastWork()->work_update_id."|work_update|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }

            
        }
    }
    function getStartAndEndDate($week, $year)
    {
        $week = $week-1;
        $time = strtotime('01-01-'.$year, time());
        $day = date('w', $time);
        $time += ((7*$week)+1-$day)*24*3600;
        $return[0] = date('d-m-Y', $time);
        $time += 6*24*3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }
    public function getContributor(){
        header('Content-type: application/json');
        $q = $_GET["search"];

        $staff_model = $this->model->get('staffModel');
        $data = array(
            'where' => 'staff_name LIKE "%'.$q.'%"',
        );
        $staffs = $staff_model->getAllStaff($data);
        $arr = array();
        foreach ($staffs as $staff) {
            $arr[] = $staff->staff_name;
        }
        
        echo json_encode($arr);
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $contributor = "";
            if(trim($_POST['report_partner']) != ""){
                $support = explode(',', trim($_POST['report_partner']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                        if ($contributor == "")
                            $contributor .= $name;
                        else
                            $contributor .= ','.$name;
                    }
                }
            }

            $work = $this->model->get('reportModel');
            $data = array(
                        'report_start_time' => strtotime(trim($_POST['report_start_time'])),
                        'report_end_time' => strtotime(trim($_POST['report_end_time'])),
                        'report_date' => strtotime(date('d-m-Y')),
                        'report_title' => trim($_POST['report_title']),
                        'report_comment' => trim($_POST['report_comment']),
                        'report_staff' => $staffs->staff_id,
                        'report_partner' => $contributor,
                        'report_full_time' => trim($_POST['report_full_time']),
                        'report_repeat' => trim($_POST['report_repeat']),
                        );
            
            if ($_POST['yes'] != "") {
                

                    $work->updateReport($data,array('report_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|report|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    $work->createReport($data);

                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$work->getLastReport()->report_id."|report|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

}
?>