<?php
Class eatingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý ngày ăn';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'eating_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngaytao = date('m-Y',strtotime('-1 month'));
        }

        $id = $this->registry->router->param_id;
        $st = $this->registry->router->page;

        
        
        $join = array('table'=>'staff','where'=>'eating.staff = staff.staff_id');
        $data = array(
            'where' => 'create_time >='.strtotime('01-'.$ngaytao).' AND create_time <= '.strtotime('28-'.$ngaytao),
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'create_time >= '.$id.' AND create_time <= '.strtotime('28-'.date('m-Y',$id));

            $ngaytao = date('m-Y',$id);

            if (isset($st) && $st > 0) {
                $data['where'] .= ' AND staff = '.$st;
                $page = 1;
            }
        }

        $eating_model = $this->model->get('eatingModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($eating_model->getAllEating($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'create_time >='.strtotime('01-'.$ngaytao).' AND create_time <= '.strtotime('28-'.$ngaytao),
            );

        if (isset($id) && $id > 0) {
            $data['where'] = 'create_time >= '.$id.' AND create_time <= '.strtotime('28-'.date('m-Y',$id));
            if (isset($st) && $st > 0) {
                $data['where'] .= ' AND staff = '.$st;
            }
        }
        
        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $this->view->data['eatings'] = $eating_model->getAllEating($data,$join);
        $this->view->data['lastID'] = isset($eating_model->getLastEating()->eating_id)?$eating_model->getLastEating()->eating_id:0;

        $this->view->show('eating/index');
    }

    public function getstaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
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
                echo '<li onclick="set_item(\''.$rs->staff_name.'\',\''.$rs->staff_id.'\',\''.$rs->staff_code.'\',\''.$rs->staff_birth.'\',\''.$rs->staff_gender.'\')">'.$staff_name.'</li>';
            }
        }
    }

    public function add(){
        
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }

        if (isset($_POST['yes'])) {
            $eating = $this->model->get('eatingModel');
            $data = array(
                        
                        'eating_day' => trim($_POST['eating_day']),
                        'eating_number' => trim($_POST['eating_number']),
                        'eating_price' => trim(str_replace(',','',$_POST['eating_price'])),
                        'eating_total' => trim(str_replace(',','',$_POST['eating_total'])),
                        'eating_staff_total' => trim(str_replace(',','',$_POST['eating_staff_total'])),
                        'create_time' => strtotime('01-'.$_POST['create_time']),
                        
                        );

            
            if ($_POST['staff'] == "") {
                //var_dump($data);
                $eating_data = $eating->getEating($_POST['yes']);

                $eating->updateEating($data,array('eating_id' => $_POST['yes']));
                echo "Cập nhật thành công";


                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|eating|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                $data['staff'] = trim($_POST['staff']);
                //var_dump($data);
                if ($eating->getEatingByWhere(array('staff'=>trim($_POST['staff']),'create_time' => $data['create_time']))) {
                    echo "Bảng lương này đã tồn tại";
                    return false;
                }
                else{
                    $eating->createEating($data);
                    echo "Thêm thành công";



                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$eating->getLastEating()->eating_id."|eating|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }

    

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $eating = $this->model->get('eatingModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $eating->deleteEating($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|eating|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|eating|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $eating->deleteEating($_POST['data']);
            }
            
        }
    }

    
    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $staff = $this->model->get('staffModel');
            $eating = $this->model->get('eatingModel');

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

            $nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = (strlen($day[0]) < 2 ? "0".$day[0] : $day[0] )."-".$day[1] ;
            $ngaythang = '01-'.$ngaythang;

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            $tongchiphi = 0;

            $y = 0;

            for ($row = 6; $row <= $highestRow; ++ $row) {
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

                    $val[] = $cell->getCalculatedValue();
                    //here's my prob..
                    //echo $val;
                }


                if ($val[1] != null) {
                    //var_dump($val[11]);die();
                    if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[1])))){
                        $staff_data = array(
                            'staff_name' => trim($val[1]),
                            );
                        $staff->createStaff($staff_data);

                        $id_staff = $staff->getLastStaff()->staff_id;
                        
                            $eating_data = array(
                                'staff' => trim($id_staff),
                                'eating_day' => trim($val[2]),
                                'eating_number' => trim($val[3]),
                                'eating_price' => trim($val[4]),
                                'eating_total' => trim($val[5]),
                                'eating_staff_total' => trim($val[6]),
                                'create_time' => strtotime($ngaythang),
                                
                                );

                            $eating->createEating($eating_data);

                        
                    }
                    else{
                        $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[1])))->staff_id;


                        if (!$eating->getEatingByWhere(array('staff'=>$id_staff,'create_time' => strtotime($ngaythang)))) {
                            $eating_data = array(
                                'staff' => trim($id_staff),
                                'eating_day' => trim($val[2]),
                                'eating_number' => trim($val[3]),
                                'eating_price' => trim($val[4]),
                                'eating_total' => trim($val[5]),
                                'eating_staff_total' => trim($val[6]),
                                'create_time' => strtotime($ngaythang),
                                
                                );

                            $eating->createEating($eating_data);
                        }
                        else{
                            $id_eating = $eating->getEatingByWhere(array('staff'=>$id_staff,'create_time' => strtotime($ngaythang)))->eating_id;

                            $eating_data = array(
                                'staff' => trim($id_staff),
                                'eating_day' => trim($val[2]),
                                'eating_number' => trim($val[3]),
                                'eating_price' => trim($val[4]),
                                'eating_total' => trim($val[5]),
                                'eating_staff_total' => trim($val[6]),
                                'create_time' => strtotime($ngaythang),
                                
                                );

                                $eating->updateEating($eating_data,array('eating_id' => $id_eating));
                        }
                    }
                    
                }
                


            }
            return $this->view->redirect('eating');
        }
        $this->view->show('eating/import');

    }


}
?>