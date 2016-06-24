<?php
Class handlingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý giá cước nâng hạ';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'handling_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        $join = array('table'=>'port','where'=>'handling.port = port.port_id');

        $handling_model = $this->model->get('handlingModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($handling_model->getAllHandling(null,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( port_name LIKE "%'.$keyword.'%" 
                OR c20_feet LIKE "%'.$keyword.'%" 
                OR c40_feet LIKE "%'.$keyword.'%" 
                OR c45_feet LIKE "%'.$keyword.'%")';
            $data['where'] = $search;
        }
        
        
        
        
        $this->view->data['handlings'] = $handling_model->getAllHandling($data,$join);

        $this->view->data['lastID'] = isset($handling_model->getLastHandling()->handling_id)?$handling_model->getLastHandling()->handling_id:0;
        
        $this->view->show('handling/index');
    }

    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $handling = $this->model->get('handlingModel');
            $data = array(
                        
                        'port' => trim($_POST['port']),
                        'c20_feet' => trim(str_replace(',','',$_POST['c20_feet'])),
                        'c40_feet' => trim(str_replace(',','',$_POST['c40_feet'])),
                        'c45_feet' => trim(str_replace(',','',$_POST['c45_feet'])),
                        'truck_barge' => trim($_POST['truck_barge']),
                        'lift' => trim($_POST['lift']),
                        'status' => trim($_POST['status']),
                        );
            if ($_POST['yes'] != "") {
                //$data['handling_update_user'] = $_SESSION['userid_logined'];
                //$data['handling_update_time'] = time();
                //var_dump($data);
                $handling->updateHandling($data,array('handling_id' => $_POST['yes']));
                echo "Cập nhật thành công";

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|handling|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                //$data['handling_create_user'] = $_SESSION['userid_logined'];
                //$data['staff'] = $_POST['staff'];
                //var_dump($data);
                if ($handling->getHandlingByWhere(array('port'=>trim($_POST['port']),'truck_barge'=>trim($_POST['truck_barge']),'lift'=>trim($_POST['lift']),'status'=>trim($_POST['status'])))) {
                    echo "Bảng giá này đã tồn tại";
                    return false;
                }
                else{
                    $handling->createHandling($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$handling->getLastHandling()->handling_id."|handling|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }

    public function getport(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $port_model = $this->model->get('portModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $port_model->getAllPort();
            }
            else{
                $data = array(
                'where'=>'( port_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $port_model->getAllPort($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $port_name = $rs->port_name;
                if ($_POST['keyword'] != "*") {
                    $port_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->port_name);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->port_name.'\',\''.$rs->port_id.'\')">'.$port_name.'</li>';
            }
        }
    }

    

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $handling = $this->model->get('handlingModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $handling->deleteHandling($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|handling|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|handling|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $handling->deleteHandling($_POST['data']);
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $port = $this->model->get('portModel');
            $handling = $this->model->get('handlingModel');

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
            
             

                for ($row = 3; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[0] != null && ($val[1] != null || $val[2] != null || $val[3] != null || $val[4] != null || $val[5] != null || $val[6] != null || $val[7] != null || $val[8] != null || $val[9] != null || $val[10] != null || $val[11] != null || $val[12] != null || $val[13] != null )) {
                        
                        if ($val[1] == 'HÀNG') {
                            $status = 1;
                        }
                        if ($val[1] == 'RỖNG') {
                            $status = 0;
                        }

                        
                        if($port->getPortByWhere(array('port_name'=>trim($val[0])))){
                            $id_port = $port->getPortByWhere(array('port_name'=>trim($val[0])))->port_id;

                            if (!$handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 1,'status'=>$status))) {
                                $handling_data = array(
                                'port' => $id_port,
                                'truck_barge' => 1,
                                'lift' => 1,
                                'status' => $status,
                                'c20_feet' => trim($val[2]),
                                'c40_feet' => trim($val[3]),
                                'c45_feet' => trim($val[4]),
                                );
                                $handling->createHandling($handling_data);
                            }
                            if($handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 1,'status'=>$status))){
                                $id_handling = $handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 1,'status'=>$status))->handling_id;
                                $handling_data = array(
                                'c20_feet' => trim($val[2]),
                                'c40_feet' => trim($val[3]),
                                'c45_feet' => trim($val[4]),
                                );
                                $handling->updateHandling($handling_data,array('handling_id' => $id_handling));
                            }


                            if (!$handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 0,'status'=>$status))) {
                                $handling_data = array(
                                'port' => $id_port,
                                'truck_barge' => 1,
                                'lift' => 0,
                                'status' => $status,
                                'c20_feet' => trim($val[5]),
                                'c40_feet' => trim($val[6]),
                                'c45_feet' => trim($val[7]),
                                );
                                $handling->createHandling($handling_data);
                            }
                            if($handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 0,'status'=>$status))){
                                $id_handling = $handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 1,'lift'=> 0,'status'=>$status))->handling_id;
                                $handling_data = array(
                                'c20_feet' => trim($val[5]),
                                'c40_feet' => trim($val[6]),
                                'c45_feet' => trim($val[7]),
                                );
                                $handling->updateHandling($handling_data,array('handling_id' => $id_handling));
                            }


                            if (!$handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 1,'status'=>$status))) {
                                $handling_data = array(
                                'port' => $id_port,
                                'truck_barge' => 0,
                                'lift' => 1,
                                'status' => $status,
                                'c20_feet' => trim($val[8]),
                                'c40_feet' => trim($val[9]),
                                'c45_feet' => trim($val[10]),
                                );
                                $handling->createHandling($handling_data);
                            }
                            if($handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 1,'status'=>$status))){
                                $id_handling = $handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 1,'status'=>$status))->handling_id;
                                $handling_data = array(
                                'c20_feet' => trim($val[8]),
                                'c40_feet' => trim($val[9]),
                                'c45_feet' => trim($val[10]),
                                );
                                $handling->updateHandling($handling_data,array('handling_id' => $id_handling));
                            }


                            if (!$handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 0,'status'=>$status))) {
                                $handling_data = array(
                                'port' => $id_port,
                                'truck_barge' => 0,
                                'lift' => 0,
                                'status' => $status,
                                'c20_feet' => trim($val[11]),
                                'c40_feet' => trim($val[12]),
                                'c45_feet' => trim($val[13]),
                                );
                                $handling->createHandling($handling_data);
                            }
                            if($handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 0,'status'=>$status))){
                                $id_handling = $handling->getHandlingByWhere(array('port'=> $id_port,'truck_barge' => 0,'lift'=> 0,'status'=>$status))->handling_id;
                                $handling_data = array(
                                'c20_feet' => trim($val[11]),
                                'c40_feet' => trim($val[12]),
                                'c45_feet' => trim($val[13]),
                                );
                                $handling->updateHandling($handling_data,array('handling_id' => $id_handling));
                            }
                            
                        }
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('handling');
        }
        $this->view->show('handling/import');

    }
    

    public function view() {
        
        $this->view->show('handling/view');
    }

}
?>