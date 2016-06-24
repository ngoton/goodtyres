<?php
Class planningController Extends baseController {
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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'project_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }
        $staff_model = $this->model->get('staffModel');
        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        
        $project_model = $this->model->get('projectModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'owner = '.$staff->staff_id,
        );
        if ($_SESSION['userid_logined'] == 1) {
            $data = array(
                'where' => '1=1 ',
            );
        }

        $tongsodong = count($project_model->getAllProject($data));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'owner = '.$staff->staff_id,
            );
        if ($_SESSION['userid_logined'] == 1) {
            $data['where'] = '1=1 ';
        }

        if ($keyword != '') {
            $search = '( project_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $projects = $project_model->getAllProject($data);

        $this->view->data['projects'] = $projects;
        $this->view->data['lastID'] = isset($project_model->getLastProject()->project_id)?$project_model->getLastProject()->project_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('planning/index');
    }

   public function view($id) {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if (!$id) {
            return $this->view->redirect('planning');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'complete ASC,';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'deadline ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $project_model = $this->model->get('projectModel');
        $project = $project_model->getProject($id);
        $this->view->data['project'] = $id;
        $this->view->data['project_name'] = $project->project_name;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;
        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        
        $work_model = $this->model->get('workModel');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'owner = '.$staff->staff_id.' AND project ='.$id,
        );
        if ($_SESSION['userid_logined'] == 1) {
            $data = array(
                'where' => 'project ='.$id,
            );
        }
        

        $tongsodong = count($work_model->getAllWork($data));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;



        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'owner = '.$staff->staff_id.' AND project ='.$id,
            );
        if ($_SESSION['userid_logined'] == 1) {
            $data['where'] = 'project ='.$id;
        }

        if ($keyword != '') {
            $search = '( work_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_id)?$work_model->getLastWork()->work_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('planning/view');
    }

    public function deleteproject(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $project = $this->model->get('projectModel');
            $work = $this->model->get('workModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $project->deleteProject($data);
                       //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$data);
                       $work->queryWork('DELETE FROM work WHERE project = '.$data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|project|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $project->deleteProject($_POST['data']);
                       //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$data);
                       $work->queryWork('DELETE FROM work WHERE project = '.$_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|project|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function deletework(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $work = $this->model->get('workModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $work->deletework($data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|work|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $work->deleteWork($_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|work|"."\n"."\r\n";
                        
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

    public function addwork(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $contributor = "";
            if(trim($_POST['contributor']) != ""){
                $support = explode(',', trim($_POST['contributor']));

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

            $work = $this->model->get('workModel');
            $data = array(
                        'deadline' => strtotime(trim($_POST['deadline'])),
                        'work_name' => trim($_POST['work_name']),
                        'hour' => trim($_POST['hour']),
                        'comment' => trim($_POST['comment']),
                        'owner' => $staffs->staff_id,
                        'contributor' => $contributor,
                        'project' => trim($_POST['project']),
                        'complete' => 0,
                        );
            
            if ($_POST['yes'] != "") {
                

                    $work->updateWork($data,array('work_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|work|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    $work->createWork($data);

                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$work->getLastWork()->work_id."|work|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

}
?>