<?php
Class tirecskhController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin khách hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_cskh_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $id = $this->registry->router->param_id;

        $district_model = $this->model->get('districtModel');
        $districts = $district_model->getAllDistrict();
        $this->view->data['districts'] = $districts;

        $vehicle_type = $this->model->get('vehicletypeModel');

        $vehicle_types = $vehicle_type->getAllVehicle();
        $data_vehicle = array();
        foreach ($vehicle_types as $vehicle) {
            $data_vehicle['name'][$vehicle->vehicle_type_id] = $vehicle->vehicle_type_name;
        }
        $this->view->data['vehicle_type'] = $data_vehicle;

        $tire_cskh_model = $this->model->get('tirecskhModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        
        $tongsodong = count($tire_cskh_model->getAllTire($data));
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
            $search = ' AND ( tire_cskh_company LIKE "%'.$keyword.'%" 
                    OR tire_cskh_mst LIKE "%'.$keyword.'%" 
                    OR tire_cskh_email LIKE "%'.$keyword.'%"
                )';
            $data['where'] .= $search;
        }
        
        
        
        $this->view->data['customers'] = $tire_cskh_model->getAllTire($data);

        $this->view->data['lastID'] = isset($tire_cskh_model->getLastTire()->tire_cskh_id)?$tire_cskh_model->getLastTire()->tire_cskh_id:0;
        
        $this->view->show('tirecskh/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $customer = $this->model->get('tirecskhModel');
            $user_model = $this->model->get('userModel');
            $user = $user_model->getUser($_SESSION['userid_logined']);

            $data = array(
                        
                        'tire_cskh_date' => strtotime(date('d-m-Y')),
                        'tire_cskh_company' => trim($_POST['tire_cskh_company']),
                        'tire_cskh_co' => trim($_POST['tire_cskh_co']),
                        'tire_cskh_street' => trim($_POST['tire_cskh_street']),
                        'tire_cskh_ward' => trim($_POST['tire_cskh_ward']),
                        'tire_cskh_district' => trim($_POST['tire_cskh_district']),
                        'tire_cskh_city' => trim($_POST['tire_cskh_city']),
                        'tire_cskh_province' => trim($_POST['tire_cskh_province']),
                        'tire_cskh_mst' => trim($_POST['tire_cskh_mst']),
                        'tire_cskh_email' => trim($_POST['tire_cskh_email']),
                        'tire_cskh_sdt' => trim($_POST['tire_cskh_sdt']),
                        'tire_cskh_fax' => trim($_POST['tire_cskh_fax']),
                        'tire_cskh_director' => trim($_POST['tire_cskh_director']),
                        'tire_cskh_contact' => trim($_POST['tire_cskh_contact']),
                        'tire_cskh_contact_sdt' => trim($_POST['tire_cskh_contact_sdt']),
                        'tire_cskh_vehicle_number' => trim($_POST['tire_cskh_vehicle_number']),
                        'tire_cskh_user' => $_SESSION['userid_logined'],
                        
                        );

            if (trim($_POST['tire_cskh_vehicle']) != "") {
                $data['tire_cskh_vehicle'] = trim($_POST['tire_cskh_vehicle']);
            }
            else{
                if (trim($_POST['tire_cskh_vehicle_name']) != "") {
                    $vehicle_type = $this->model->get('vehicletypeModel');
                    $vehicle_type->createVehicle(array('vehicle_type_name' => trim($_POST['tire_cskh_vehicle_name'])));
                    $vehicle_type_id = $vehicle_type->getLastVehicle()->vehicle_type_id;
                    $data['tire_cskh_vehicle'] = $vehicle_type_id;
                }
            }

            if ($_POST['yes'] != "") {
                //var_dump($data);
                
                if ($customer->getAllTireByWhere($_POST['yes'].' AND tire_cskh_mst = '.trim($_POST['tire_cskh_mst']))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    if ($user->user_dept==2 || $user->user_dept==3) {
                        if ($customer->getTireByWhere(array('tire_cskh_id'=>$_POST['yes'],'tire_cskh_user'=>$_SESSION['userid_logined']))) {
                            $customer->updateTire($data,array('tire_cskh_id' => $_POST['yes']));
                            echo "Cập nhật thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_cskh|".implode("-",$data)."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $customer->updateTire($data,array('tire_cskh_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_cskh|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    
                }
                
            }
            else{
                //$data['customer'] = $_POST['customer'];
                //var_dump($data);
                if ($customer->getTireByWhere(array('tire_cskh_mst'=>trim($_POST['tire_cskh_mst'])))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    $customer->createTire($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$customer->getLastTire()->tire_cskh_id."|tire_cskh|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = $this->model->get('tirecskhModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
                        if ($customer->getTireByWhere(array('tire_cskh_id'=>$data,'tire_cskh_user'=>$_SESSION['userid_logined']))) {
                            $customer->deleteTire($data);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_cskh|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $customer->deleteTire($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_cskh|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    
                }
                return true;
            }
            else{
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
                        if ($customer->getTireByWhere(array('tire_cskh_id'=>$_POST['data'],'tire_cskh_user'=>$_SESSION['userid_logined']))) {
                            $customer->deleteTire($_POST['data']);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_cskh|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $customer->deleteTire($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_cskh|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
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

            $customer = $this->model->get('tirecskhModel');
            $staff_model = $this->model->get('staffModel');
            $district_model = $this->model->get('districtModel');
            $vehicle_model = $this->model->get('vehicletypeModel');

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
            
             

                for ($row = 2; $row <= $highestRow; ++ $row) {
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

                    $province_id = "";
                    $vehicle_id = "";
                    $user_id = "";

                    if ($val[8] != null  ) {
                        $province_id = $district_model->getDistrictByWhere(array('district_name'=>trim($val[8])))->district_id;
                    }
                    if ($val[16] != null  ) {
                        $vehicle = $vehicle_model->getVehicleByWhere(array('vehicle_type_name'=>trim($val[16])));

                        if (!$vehicle) {
                            $vehicle_model->createVehicle(array('vehicle_type_name' => trim($val[16])));
                            $vehicle_id = $vehicle_model->getLastVehicle()->vehicle_type_id;
                        }
                        else{
                            $vehicle_id= $vehicle->vehicle_type_id;
                        }
                    }
                    if ($val[18] != null) {
                        $user_id = $staff_model->getStaffByWhere(array('staff_name'=>trim($val[18])))->account;
                    }

                    if ($val[2] != null  ) {
                        $customer_data = array(
                                'tire_cskh_date' => strtotime(trim($val[1])),
                                'tire_cskh_company' => trim($val[2]),
                                'tire_cskh_co' => trim($val[3]),
                                'tire_cskh_street' => trim($val[4]),
                                'tire_cskh_ward' => trim($val[5]),
                                'tire_cskh_district' => trim($val[6]),
                                'tire_cskh_city' => trim($val[7]),
                                'tire_cskh_province' => $province_id,
                                'tire_cskh_mst' => trim($val[9]),
                                'tire_cskh_email' => trim($val[10]),
                                'tire_cskh_sdt' => trim($val[11]),
                                'tire_cskh_fax' => trim($val[12]),
                                'tire_cskh_director' => trim($val[13]),
                                'tire_cskh_contact' => trim($val[14]),
                                'tire_cskh_contact_sdt' => trim($val[15]),
                                'tire_cskh_vehicle' => $vehicle_id,
                                'tire_cskh_vehicle_number' => trim($val[17]),
                                'tire_cskh_user' => $user_id,
                                );
                        
                            if(!$customer->getTireByWhere(array('tire_cskh_company'=>trim($val[2])))) {
                                
                                $customer->createTire($customer_data);
                            }
                            else if($customer->getTireByWhere(array('tire_cskh_company'=>trim($val[2])))){
                                $id_customer = $customer->getTireByWhere(array('tire_cskh_company'=>trim($val[2])))->tire_cskh_id;
                                $customer->updateTire($customer_data,array('tire_cskh_id' => $id_customer));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('tirecskh');
        }
        $this->view->show('tirecskh/import');

    }

    public function getCustomer($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>