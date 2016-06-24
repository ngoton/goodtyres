<?php
Class workController Extends baseController {
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
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'project_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $work_model = $this->model->get('workModel');

        $project_model = $this->model->get('projectModel');

        $data_project = array(
            'where' => 'owner = '.$staff->staff_id,
        );
        if ($_SESSION['userid_logined'] == 1) {
            $data_project = array(
                'where' => '1=1 ',
            );
        }

        if ($trangthai > 0) {
            $data_project['where'] .= ' AND project_id = '.$trangthai;
        }
        $projects_staff = $project_model->getAllProject($data_project);

        $this->view->data['projects_staff'] = $projects_staff;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'owner = '.$staff->staff_id,
        );
        
        if ($_SESSION['userid_logined'] == 1) {
            $data_ = array(
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
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'owner = '.$staff->staff_id,
            );
      
        if ($_SESSION['userid_logined'] == 1) {
            $data['where'] = '1=1';
        }

        if ($keyword != '') {
            $search = '( project_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $projects = $project_model->getAllProject($data);

        $project_data = array();

        foreach ($projects as $project) {
            $works = $work_model->getAllWork(array('where'=>'project='.$project->project_id));
            foreach ($works as $work) {
                $project_data[$project->project_id][] = $work;
            }
        }

        $this->view->data['project_datas'] = $project_data;

        $this->view->data['projects'] = $projects;
        $this->view->data['lastID'] = isset($project_model->getLastProject()->project_id)?$project_model->getLastProject()->project_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('work/index');
    }

   

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
            $project_model = $this->model->get('projectModel');
            $data = array(
                        
                        'project_name' => trim($_POST['project_name']),
                        'owner' => $staffs->staff_id,
                        );
            
            if ($_POST['yes'] != "") {
                                    
                    $project_model->updateProject($data,array('project_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|project|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    $project_model->createProject($data);


                    echo "Thêm thành công";

                 
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$project_model->getLastProject()->project_id."|project|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }
    public function addwork(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $work = $this->model->get('workModel');
            $data = array(
                        'deadline' => strtotime(trim($_POST['deadline'])),
                        'work_name' => trim($_POST['work_name']),
                        'hour' => trim($_POST['hour']),
                        'comment' => trim($_POST['comment']),
                        'owner' => $staffs->staff_id,
                        'contributor' => trim($_POST['contributor']),
                        'project' => trim($_POST['project']),
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

    public function delete(){
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

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $project = $this->model->get('projectModel');
            $work = $this->model->get('workModel');
            $staff = $this->model->get('staffModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();
            $y = 0;
            $project_id = 0;
                for ($row = 3; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                if ($col == 1) {
                                    $y++;
                                }
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        $val[] = $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[1] != null) {

                            $staff_name = $staff->getStaffByWhere(array('staff_name'=>trim($val[4])))->staff_id;

                            $support = explode(',', trim($val[5]));
                            $contributor = "";
                            if ($support) {
                                foreach ($support as $key) {
                                    $name = $staff->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                                    if ($contributor == "")
                                        $contributor .= $name;
                                    else
                                        $contributor .= ','.$name;
                                }
                            }
                            

                            $deadline = PHPExcel_Shared_Date::ExcelToPHP(trim($val[8]));                                      
                            $deadline = $deadline-3600;

                            if(!$project->getProjectByWhere(array('project_name'=>trim($val[1]),'owner' => $staff_name))) {
                                $project_data = array(
                                'project_name' => trim($val[1]),
                                'owner' => $staff_name,
                                );


                                $project->createProject($project_data);

                                $project_id = $project->getLastProject()->project_id;

                                $work_data = array(
                                    'deadline' => $deadline,
                                    'work_name' => trim($val[3]),
                                    'hour' => trim($val[9]),
                                    'comment' => trim($val[6]),
                                    'owner' => $staff_name,
                                    'contributor' => $contributor,
                                    'project' => $project_id,
                                );
                                $work->createWork($work_data);

                            }
                            else if($project->getProjectByWhere(array('project_name'=>trim($val[1]),'owner' => $staff_name))) {
                                
                                $project_id = $project->getProjectByWhere(array('project_name'=>trim($val[1]),'owner' => $staff_name))->project_id;

                                if (!$work->getWorkByWhere(array('work_name'=>trim($val[3]),'project'=>$project_id))) {
                                    $work_data = array(
                                        'deadline' => $deadline,
                                        'work_name' => trim($val[3]),
                                        'hour' => trim($val[9]),
                                        'comment' => trim($val[6]),
                                        'owner' => $staff_name,
                                        'contributor' => $contributor,
                                        'project' => $project_id,
                                    );
                                    $work->createWork($work_data);
                                }
                                else if ($work->getWorkByWhere(array('work_name'=>trim($val[3]),'project'=>$project_id))) {
                                    $id_work = $work->getWorkByWhere(array('work_name'=>trim($val[3]),'project'=>$project_id))->work_id;
                                    $work_data = array(
                                        'deadline' => $deadline,
                                        'work_name' => trim($val[3]),
                                        'hour' => trim($val[9]),
                                        'comment' => trim($val[6]),
                                        'owner' => $staff_name,
                                        'contributor' => $contributor,
                                        'project' => $project_id,
                                    );
                                    $work->updateWork($work_data,array('work_id'=>$id_work));
                                }
                                

                            }

                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('work');
        }
        $this->view->show('work/import');

    }

public function getContributor(){
    header('Content-type: application/json');
    $q = $_GET["search"];
    //check $q, get results from your database and put them in $arr
    $arr[] = 'tag1';
    $arr[] = 'b';
    
    echo json_encode($arr);
}


    

}
?>