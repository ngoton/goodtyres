<?php
Class attendanceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý chấm công';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'attendance_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
        }
        $id = $this->registry->router->param_id;
        $st = $this->registry->router->page;

        
        $join = array('table'=>'staff','where'=>'attendance.staff = staff.staff_id');
        $data = array(
            'where' => 'attendance_date >='.strtotime($batdau).' AND attendance_date <= '.strtotime($ketthuc),
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'attendance_date >= '.$id.' AND attendance_date <= '.strtotime(date('t-m-Y',$id));

            $batdau = '01-'.date('m-Y',$id);
            $ketthuc = date('t-m-Y',$id);

            if (isset($st) && $st > 0) {
                $data['where'] .= ' AND staff = '.$st;
                $page = 1;
            }
        }

        $attendance_model = $this->model->get('attendanceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        
        $tongsodong = count($attendance_model->getAllAttendance($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'attendance_date >='.strtotime($batdau).' AND attendance_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] = 'attendance_date >= '.$id.' AND attendance_date <= '.strtotime(date('t-m-Y',$id));
            if (isset($st) && $st > 0) {
                $data['where'] .= ' AND staff = '.$st;
            }
        }

        if ($keyword != '') {
            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] .= $search;
        }
        $this->view->data['attendances'] = $attendance_model->getAllAttendance($data,$join);
        $this->view->data['lastID'] = isset($attendance_model->getLastAttendance()->attendance_id)?$attendance_model->getLastAttendance()->attendance_id:0;

        $this->view->show('attendance/index');
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
            $attendance = $this->model->get('attendanceModel');
            $data = array(
                        'attendance_date' => strtotime(trim($_POST['attendance_date'])),
                        'attendance_day' => $this->sw_get_current_weekday($_POST['attendance_date']),
                        'check_in_1' => trim($_POST['check_in_1']),
                        'check_out_1' => trim($_POST['check_out_1']),
                        'check_in_2' => trim($_POST['check_in_2']),
                        'check_out_2' => trim($_POST['check_out_2']),
                        'attendance_late' => trim($_POST['attendance_late']),
                        'attendance_soon' => trim($_POST['attendance_soon']),
                        'attendance_comment' => trim($_POST['attendance_comment']),
                        'attendance_total' => trim($_POST['attendance_total']),
                        
                        
                        );

            
            if ($_POST['staff'] == "") {
                //var_dump($data);
                $attendance_data = $attendance->getAttendance($_POST['yes']);

                $attendance->updateAttendance($data,array('attendance_id' => $_POST['yes']));
                echo "Cập nhật thành công";


                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|attendance|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                $data['staff'] = trim($_POST['staff']);
                //var_dump($data);
                if ($attendance->getAttendanceByWhere(array('staff'=>trim($_POST['staff']),'attendance_date' => $data['attendance_date']))) {
                    echo "Bảng lương này đã tồn tại";
                    return false;
                }
                else{
                    $attendance->createAttendance($data);
                    echo "Thêm thành công";



                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$attendance->getLastAttendance()->attendance_id."|attendance|".implode("-",$data)."\n"."\r\n";
                        
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
            $attendance = $this->model->get('attendanceModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $attendance->deleteAttendance($data);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|attendance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|attendance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $attendance->deleteAttendance($_POST['data']);
            }
            
        }
    }

    
    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
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
            $attendance = $this->model->get('attendanceModel');

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

            /*$nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = (strlen($day[0]) < 2 ? "0".$day[0] : $day[0] )."-".$day[1] ;
            $ngaythang = '01-'.$ngaythang;*/

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            $tongchiphi = 0;

            $y = 0;

            for ($row = 2; $row <= $highestRow; ++ $row) {
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

                $ngay = $val[1];
                $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);                                      
                $ngaythang = $ngaythang+86400;

                $ngaythang = strtotime(date('d-m-Y',$ngaythang));


                if ($val[0] != null) {
                    //var_dump($val[11]);die();
                    if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[0])))){
                        $staff_data = array(
                            'staff_name' => trim($val[0]),
                            );
                        $staff->createStaff($staff_data);

                        $id_staff = $staff->getLastStaff()->staff_id;
                        
                            $attendance_data = array(
                                'staff' => $id_staff,
                                'attendance_day' => trim($val[2]),
                                'check_in_1' => trim($val[3]),
                                'check_out_1' => trim($val[4]),
                                'check_in_2' => trim($val[5]),
                                'check_out_2' => trim($val[6]),
                                'attendance_late' => trim($val[7]),
                                'attendance_soon' => trim($val[8]),
                                'attendance_comment' => trim($val[9]),
                                'attendance_total' => trim($val[10]),
                                'attendance_date' => $ngaythang,
                                
                                );

                            $attendance->createAttendance($attendance_data);

                        
                    }
                    else{
                        $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[0])))->staff_id;


                        if (!$attendance->getAttendanceByWhere(array('staff'=>$id_staff,'attendance_date' => $ngaythang))) {
                            $attendance_data = array(
                                'staff' => $id_staff,
                                'attendance_day' => trim($val[2]),
                                'check_in_1' => trim($val[3]),
                                'check_out_1' => trim($val[4]),
                                'check_in_2' => trim($val[5]),
                                'check_out_2' => trim($val[6]),
                                'attendance_late' => trim($val[7]),
                                'attendance_soon' => trim($val[8]),
                                'attendance_comment' => trim($val[9]),
                                'attendance_total' => trim($val[10]),
                                'attendance_date' => $ngaythang,
                                
                                );

                            $attendance->createAttendance($attendance_data);
                        }
                        else{
                            $id_attendance = $attendance->getAttendanceByWhere(array('staff'=>$id_staff,'attendance_date' => $ngaythang))->attendance_id;

                            $attendance_data = array(
                                'staff' => $id_staff,
                                'attendance_day' => trim($val[2]),
                                'check_in_1' => trim($val[3]),
                                'check_out_1' => trim($val[4]),
                                'check_in_2' => trim($val[5]),
                                'check_out_2' => trim($val[6]),
                                'attendance_late' => trim($val[7]),
                                'attendance_soon' => trim($val[8]),
                                'attendance_comment' => trim($val[9]),
                                'attendance_total' => trim($val[10]),
                                'attendance_date' => $ngaythang,
                                
                                );

                                $attendance->updateAttendance($attendance_data,array('attendance_id' => $id_attendance));
                        }
                        
                    }
                    
                }
                


            }
            return $this->view->redirect('attendance');
        }
        $this->view->show('attendance/import');

    }

    function sw_get_current_weekday($date) {
        $weekday = date("l",strtotime($date));
        $weekday = strtolower($weekday);
        switch($weekday) {
            case 'monday':
                $weekday = 'Hai';
                break;
            case 'tuesday':
                $weekday = 'Ba';
                break;
            case 'wednesday':
                $weekday = 'Tư';
                break;
            case 'thursday':
                $weekday = 'Năm';
                break;
            case 'friday':
                $weekday = 'Sáu';
                break;
            case 'saturday':
                $weekday = 'Bảy';
                break;
            default:
                $weekday = 'CN';
                break;
        }
        return $weekday;
    }


}
?>