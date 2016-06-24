<?php
Class newtransportController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý giá cước vận tải';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'new_transport_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $vendor_model = $this->model->get('vendorModel');
        $vendors = $vendor_model->getAllVendor();
        $this->view->data['vendors'] = $vendors;

        $join = array('table'=>'vendor','where'=>'new_transport.vendor = vendor.vendor_id');

        $transport_model = $this->model->get('newtransportModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($transport_model->getAllTransport(null,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( loc_from in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" ) 
                        OR loc_to in (SELECT location_id FROM location WHERE location_name LIKE "%'.$keyword.'%" )
                        OR vendor_name LIKE "%'.$keyword.'%")';
            $data['where'] = $search;
        }
        
        $location_model = $this->model->get('locationModel');
        $location = $location_model->getAllLocation(null,array('table'=>'district','where'=>'district.district_id = location.district'));
        
        $this->view->data['locations'] = $location;

        $location_data = array();
        foreach ($location as $location) {
            $location_data['location_id'][$location->location_id] = $location->location_id;
            $location_data['location_name'][$location->location_id] = $location->location_name;
            $location_data['district_name'][$location->location_id] = $location->district_name;
        }
        
        $this->view->data['location'] = $location_data;
        
        
        $this->view->data['transports'] = $transport_model->getAllTransport($data,$join);

        $this->view->data['lastID'] = isset($transport_model->getLastTransport()->new_transport_id)?$transport_model->getLastTransport()->new_transport_id:0;
        
        $this->view->show('newtransport/index');
    }

    public function getlocation(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $location_model->getAllLocation();
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->location_name.'\',\''.$rs->location_id.'\',\''.$rs->location_code.'\',\''.$rs->location_birth.'\',\''.$rs->location_gender.'\')">'.$location_name.'</li>';
            }
        }
    }

    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('vendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor_name = $rs->vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->vendor_name.'\',\''.$rs->vendor_id.'\',\''.$rs->vendor_phone.'\')">'.$vendor_name.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $transport = $this->model->get('newtransportModel');
            $data = array(
                        
                        'c20_feet' => trim(str_replace(',','',$_POST['c20_feet'])),
                        'c40_feet' => trim(str_replace(',','',$_POST['c40_feet'])),
                        'c45_feet' => trim(str_replace(',','',$_POST['c45_feet'])),
                        'c2x20_feet' => trim(str_replace(',','',$_POST['c2x20_feet'])),
                        'vendor' => trim($_POST['vendor']),
                        );
            if ($data['vendor'] == "" || $data['vendor'] == null) {
                $vendor = $this->model->get('vendorModel');
                $data_vendor = array(
                    'vendor_name'=>trim($_POST['vendor_name']),
                    'vendor_phone'=>trim($_POST['vendor_phone']),
                );

                $vendor->createVendor($data_vendor);

                $data['vendor'] = $vendor->getLastVendor()->vendor_id;
            }

            if ($_POST['check'] == "") {
                $data['transport_update_user'] = $_SESSION['userid_logined'];
                $data['transport_update_time'] = time();
                //var_dump($data);
                $transport->updateTransport($data,array('new_transport_id' => $_POST['yes']));
                echo "Cập nhật thành công";
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|newtransport|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            else{
                $data['loc_from'] = trim($_POST['loc_from']);
                $data['loc_to'] = trim($_POST['loc_to']);
                $data['transport_create_user'] = $_SESSION['userid_logined'];
                $data['transport_create_time'] = date('m/Y');
                //$data['staff'] = $_POST['staff'];
                //var_dump($data);
                if ($transport->getTransportByWhere(array('loc_from'=>$_POST['loc_from'],'loc_to' => $_POST['loc_to']))) {
                    echo "Tuyến đường này đã tồn tại";
                    return false;
                }
                else{
                    $transport->createTransport($data);
                    echo "Thêm thành công";
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$transport->getLastTransport()->new_transport_id."|newtransport|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }

    public function gettransportauto(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['loc_from']) && isset($_POST['loc_to'])) {
            if ($_POST['loc_from'] != "" && $_POST['loc_to'] != "") {
                if ($_POST['loc_from'] != 1 && $_POST['loc_to'] != 1) {
                    if ($_POST['loc_from'] !=  $_POST['loc_to']) {
                    
                        /* Chỉ kiểm tra điểm xuất phát là Cát Lái */
                        $transport = $this->model->get('newtransportModel');
                        $r = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to' => $_POST['loc_from']));
                        $t = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to' => $_POST['loc_to']));
                        if ($r && $t) {
                            $from_c20_feet = $r->c20_feet;
                            $to_c20_feet = $t->c20_feet;

                            $from_c20_feet = $r->c20_feet;
                            $to_c20_feet = $t->c20_feet;

                            $from_c2x20_feet = $r->c2x20_feet;
                            $to_c2x20_feet = $t->c2x20_feet;

                            $from_c45_feet = $r->c45_feet;
                            $to_c45_feet = $t->c45_feet;

                            $data['c20_feet'] = (($from_c20_feet + $to_c20_feet)/2)+1000000;

                            $data['c40_feet'] = (($from_c40_feet + $to_c40_feet)/2)+1000000;
                            $data['c2x20_feet'] = (($from_c2x20_feet + $to_c2x20_feet)/2)+1000000;
                            $data['c45_feet'] = (($from_c45_feet + $to_c45_feet)/2)+1000000;

                            echo json_encode($data);
                        }
                    }
                }
            }
        }
    }
    public function getlocationfrom(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $location_model->getAllLocation();
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item_loc_from(\''.$rs->location_id.'\',\''.$rs->location_name.'\')">'.$location_name.'</li>';
            }
        }
    }
    public function getlocationto(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $location_model->getAllLocation();
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item_loc_to(\''.$rs->location_id.'\',\''.$rs->location_name.'\')">'.$location_name.'</li>';
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
            $transport = $this->model->get('newtransportModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $transport->deleteTransport($data);
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|newtransport|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                return true;
            }
            else{
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|newtransport|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                return $transport->deleteTransport($_POST['data']);
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

            $district = $this->model->get('districtModel');
            $location = $this->model->get('locationModel');
            $transport = $this->model->get('newtransportModel');
            $vendor_model = $this->model->get('vendorModel');

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
            if ($_POST['type'] == 1) {
             

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
                    if ($val[0] != null && $val[1] != null) {

                        $id_vendor1 = null;
                        $id_vendor2 = null;
                        $id_vendor3 = null;
                        $id_vendor4 = null;

                        if (trim($val[4]) != null) {
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[4]),
                                    'vendor_phone' => trim($val[5]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor1 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))) {
                                $id_vendor1 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))->vendor_id;
                            }
                        }
                        
                        if(trim($val[7]) != null){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[7]),
                                    'vendor_phone' => trim($val[8]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor2 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))) {
                                $id_vendor2 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))->vendor_id;
                            }
                        }

                        if(trim($val[10]) != null){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[10]),
                                    'vendor_phone' => trim($val[11]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor3 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))) {
                                $id_vendor3 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))->vendor_id;
                            }
                        }

                        if(trim($val[13])){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[13])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[13]),
                                    'vendor_phone' => trim($val[14]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor4 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[13])))) {
                                $id_vendor4 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[14])))->vendor_id;
                            }
                        }


                        $name_district = $this->getNameDistrict($this->lib->stripUnicode(trim($val[1])));
                        $id_district = $district->getDistrictByWhere(array('district_name'=>$name_district))->district_id;
                        
                        if(!$location->getLocationByWhere(array('location_name'=>trim($val[0]),'district'=>$id_district))){
                            $location_data = array(
                                'location_name' => trim($val[0]),
                                'district' => $id_district,
                                );
                            $location->createLocation($location_data);

                            $id_location = $location->getLastLocation()->location_id;

                        }
                        else{
                            $id_location = $location->getLocationByWhere(array('location_name'=>trim($val[0])))->location_id;

                            
                        }

                        if($id_vendor1 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor1))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor1))->new_transport_id;
                                $transport_data = array(
                                    'c20_feet' => trim($val[3]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor1))) {
                                
                                $transport_data = array(
                                    'loc_from' => 1,
                                    'loc_to' => $id_location,
                                    'vendor' => $id_vendor1,
                                    'c20_feet' => trim($val[3]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor2 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor2))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor2))->new_transport_id;
                                $transport_data = array(
                                    'c40_feet' => trim($val[6]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor2))) {
                                
                                $transport_data = array(
                                    'loc_from' => 1,
                                    'loc_to' => $id_location,
                                    'vendor' => $id_vendor2,
                                    'c40_feet' => trim($val[6]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor3 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor3))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor3))->new_transport_id;
                                $transport_data = array(
                                    'c2x20_feet' => trim($val[9]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor3))) {
                                
                                $transport_data = array(
                                    'loc_from' => 1,
                                    'loc_to' => $id_location,
                                    'vendor' => $id_vendor3,
                                    'c2x20_feet' => trim($val[9]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor4 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor4))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor4))->new_transport_id;
                                $transport_data = array(
                                    'c45_feet' => trim($val[12]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>1,'loc_to'=>$id_location,'vendor'=>$id_vendor4))) {
                                
                                $transport_data = array(
                                    'loc_from' => 1,
                                    'loc_to' => $id_location,
                                    'vendor' => $id_vendor4,
                                    'c45_feet' => trim($val[12]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }

                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            }
            elseif ($_POST['type'] == 2) {
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
                    if ($val[1] != null && $val[2] != null) {
                        $diadiem = str_replace("_", " - ", $val[1]);
                        $diadiem = str_replace("- ", " - ", $diadiem);
                        $diadiem = str_replace(" -", " - ", $diadiem);
                        $diadiem = str_replace(" _ ", " - ", $diadiem);
                        $diadiem = str_replace(" _", " - ", $diadiem);
                        $diadiem = str_replace("_ ", " - ", $diadiem);
                        $diadiem = explode(" - ", $diadiem);
                        $loc_from = trim($diadiem[0]);
                        $loc_to = trim($diadiem[1]);

                        $id_vendor1 = null;
                        $id_vendor2 = null;
                        $id_vendor3 = null;
                        $id_vendor4 = null;

                        if (trim($val[4]) != null) {
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[4]),
                                    'vendor_phone' => trim($val[5]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor1 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))) {
                                $id_vendor1 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[4])))->vendor_id;
                            }
                        }
                        
                        if(trim($val[7]) != null){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[7]),
                                    'vendor_phone' => trim($val[8]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor2 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))) {
                                $id_vendor2 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[7])))->vendor_id;
                            }
                        }

                        if(trim($val[10]) != null){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[10]),
                                    'vendor_phone' => trim($val[11]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor3 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))) {
                                $id_vendor3 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[10])))->vendor_id;
                            }
                        }

                        if(trim($val[13])){
                            if (!$vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[13])))) {
                                $vendor_data = array(
                                    'vendor_name' => trim($val[13]),
                                    'vendor_phone' => trim($val[14]),
                                );
                                $vendor_model->createVendor($vendor_data);
                                $id_vendor4 = $vendor_model->getLastVendor()->vendor_id;
                            }
                            elseif ($vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[13])))) {
                                $id_vendor4 = $vendor_model->getVendorByWhere(array('vendor_name'=>trim($val[14])))->vendor_id;
                            }
                        }
                        
                        $name_district_from = $this->getNameDistrict($this->lib->stripUnicode($loc_from));
                        if (!$district->getDistrictByWhere(array('district_name'=>$name_district_from))) {
                            if(!$location->getLocationByWhere(array('location_name'=>$loc_from))){
                                $location_data_from1 = array(
                                    'location_name' => $loc_from,
                                    'district' => $district->getDistrictByWhere(array('district_name'=>$this->getDistrictByWard($this->lib->stripUnicode($loc_from))))->district_id,
                                    );
                                $location->createLocation($location_data_from1);

                                $id_district_from = $location->getLastLocation()->district;
                            }
                            else{
                                $id_district_from = $location->getLocationByWhere(array('location_name'=>$loc_from))->district;
                            }
                        }
                        else{
                            $id_district_from = $district->getDistrictByWhere(array('district_name'=>$name_district_from))->district_id;
                        }
                        
                        if ($loc_from == 'HCM' || $loc_from == 'hcm' || $loc_from =='ho chi minh' || $loc_from == 'HO CHI MINH' || $loc_from  == 'Ho Chi Minh') {
                            $loc_from = 'Cát Lái';
                        }

                        $name_district_to = $this->getNameDistrict($this->lib->stripUnicode($loc_to));
                        //$name_district_to = $loc_to;
                        $id_district_to = $district->getDistrictByWhere(array('district_name'=>$name_district_to))->district_id;
                        
                        if(!$location->getLocationByWhere(array('location_name'=>$loc_from,'district'=>$id_district_from))){
                            $location_data_from = array(
                                'location_name' => $loc_from,
                                'district' => $id_district_from,
                                );
                            $location->createLocation($location_data_from);

                            $id_location_from = $location->getLastLocation()->location_id;

                            if(!$location->getLocationByWhere(array('location_name'=>trim($val[2]),'district'=>$id_district_to))){
                                $location_data_to = array(
                                    'location_name' => trim($val[2]),
                                    'district' => $id_district_to,
                                    );
                                $location->createLocation($location_data_to);

                                $id_location_to = $location->getLastLocation()->location_id;
                            }
                            else{
                                $id_location_to = $location->getLocationByWhere(array('location_name'=>trim($val[2])))->location_id;
                            }

                            

                            

                        }
                        else{
                            $id_location_from = $location->getLocationByWhere(array('location_name'=>$loc_from))->location_id;

                            if(!$location->getLocationByWhere(array('location_name'=>trim($val[2]),'district'=>$id_district_to))){
                                $location_data_to = array(
                                    'location_name' => trim($val[2]),
                                    'district' => $id_district_to,
                                    );
                                $location->createLocation($location_data_to);

                                $id_location_to = $location->getLastLocation()->location_id;
                            }
                            else{
                                $id_location_to = $location->getLocationByWhere(array('location_name'=>trim($val[2])))->location_id;
                            }

                            

                            
                        }

                        if($id_vendor1 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor1))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor1))->new_transport_id;
                                $transport_data = array(
                                    'c20_feet' => trim($val[3]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor1))) {
                                
                                $transport_data = array(
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'vendor' => $id_vendor1,
                                    'c20_feet' => trim($val[3]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor2 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor2))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor2))->new_transport_id;
                                $transport_data = array(
                                    'c40_feet' => trim($val[6]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor2))) {
                                
                                $transport_data = array(
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'vendor' => $id_vendor2,
                                    'c40_feet' => trim($val[6]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor3 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor3))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor3))->new_transport_id;
                                $transport_data = array(
                                    'c2x20_feet' => trim($val[9]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor3))) {
                                
                                $transport_data = array(
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'vendor' => $id_vendor3,
                                    'c2x20_feet' => trim($val[9]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }
                        if($id_vendor4 != null){
                            if ($transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor4))) {
                                $id_transport = $transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor4))->new_transport_id;
                                $transport_data = array(
                                    'c45_feet' => trim($val[12]),
                                );
                                $transport->updateTransport($transport_data,array('new_transport_id'=>$id_transport));
                            }
                            else if (!$transport->getTransportByWhere(array('loc_from'=>$id_location_from,'loc_to'=>$id_location_to,'vendor'=>$id_vendor4))) {
                                
                                $transport_data = array(
                                    'loc_from' => $id_location_from,
                                    'loc_to' => $id_location_to,
                                    'vendor' => $id_vendor4,
                                    'c45_feet' => trim($val[12]),
                                );
                                $transport->createTransport($transport_data);
                            }
                        }

                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
            }
            return $this->view->redirect('newtransport');
        }
        $this->view->show('newtransport/import');

    }
    public function getNameDistrict($str){
      if(!$str) return false;
       $unicode = array(
          'TP.HCM'=>'hcm|ho chi minh|tp.hcm|tp.ho chi minh',
          'TP Cần Thơ'=>'can tho',
          'TP Đà Nẵng'=>'da nang',
          'TP Hà Nội'=>'ha noi',
          'TP Hải Phòng'=>'hai phong',
          'Đồng Nai'=>'dong nai',
          'Bình Dương'=>'binh duong',
          'Bà Rịa - Vũng Tàu'=>'ba ria - vung tau|ba ria vung tau',
          'Long An'=>'long an',
          'Bình Phước'=>'binh phuoc|binh phuic',
          'Tây Ninh'=>'tay ninh',
          'An Giang'=>'an giang',
          'Bắc Kạn'=>'bac kan',
          'Bạc Liêu'=>'bac lieu',
          'Bắc Ninh'=>'bac ninh',
          'Bến Tre'=>'ben tre',
          'Bình Định'=>'binh dinh|quy nhon',
          'Bình Thuận'=>'binh thuan',
          'Cà Mau'=>'ca mau',
          'Cao Bằng'=>'cao bang',
          'Đắk Lắk'=>'dak lak',
          'Đắk Nông'=>'dak nong',
          'Điện Biên'=>'dien bien',
          'Đồng Tháp'=>'dong thap',
          'Gia Lai'=>'gia lai',
          'Hà Giang'=>'ha giang',
          'Hà Nam'=>'ha nam',
          'Hà Tĩnh'=>'ha tinh',
          'Hải Dương'=>'hai duong',
          'Hậu Giang'=>'hau giang',
          'Hòa Bình'=>'hoa binh',
          'Hưng Yên'=>'hung yen',
          'Khánh Hòa'=>'khanh hoa',
          'Kiên Giang'=>'kien giang',
          'Kon Tum'=>'kon tum',
          'Lai Châu'=>'lai chau',
          'Lâm Đồng'=>'lam dong',
          'Lạng Sơn'=>'lang son',
          'Lào Cai'=>'lao cai',
          'Nam Định'=>'nam dinh',
          'Nghệ An'=>'nghe an',
          'Ninh Bình'=>'ninh binh',
          'Ninh Thuận'=>'ninh thuan',
          'Phú Thọ'=>'phu tho',
          'Phú Yên'=>'phu yen',
          'Quảng Bình'=>'quang binh',
          'Quảng Nam'=>'quang nam',
          'Quảng Ngãi'=>'quang ngai',
          'Quảng Ninh'=>'quang ninh',
          'Quảng Trị'=>'quang tri',
          'Sóc Trăng'=>'soc trang',
          'Sơn La'=>'son la',
          'Thái Bình'=>'thai binh',
          'Thái Nguyên'=>'thai nguyen',
          'Thanh Hóa'=>'thanh hoa',
          'Thừa Thiên Huế'=>'thua thien hue|hue',
          'Tiền Giang'=>'tien giang',
          'Trà Vinh'=>'tra vinh',
          'Tuyên Quang'=>'tuyen quang',
          'Vĩnh Long'=>'vinh long',
          'Vĩnh Phúc'=>'vinh phuc',
          'Yên Bái'=>'yen bai',
          
 
       );
    foreach($unicode as $nonUnicode=>$uni) $str = preg_replace("/($uni)/i",$nonUnicode,$str);
    

    return $str;
    }
    public function getDistrictByWard($str){
      if(!$str) return false;
       $unicode = array(
          'TP.HCM'=>'quan 1|quan 2|quan 3|quan 4|quan 5|quan 6|quan 7|quan 8|quan 9|quan 10|quan 11|quan 12|thu duc|go vap|binh thanh|tan binh|tan phu|phu nhuan|binh tan|cu chi|hoc mon|binh chanh|nha be|can gio',
          'Đồng Nai'=>'bien hoa|tp bien hoa|tp.bien hoa|long khanh|tx.long khanh|tx long khanh|long thanh|nhon trach|vinh cuu|trang bom|thong nhat|tan phu|dinh quan|xuan loc|cam my',
          'Bình Dương'=>'thu dau mot|tp.thu dau mot|tp thu dau mot|thuan an|tx.thuan an|tx thuan an|di an|tx.di an|tx di an|tan uyen|tx.tan uyen|tx tan uyen|ben cat|tx.ben cat|tx ben cat|dau tieng|phu giao|bac tan uyen|bau bang',
          'Bà Rịa - Vũng Tàu'=>'phu my|ba ria|tp.ba ria|tp ba ria|vung tau|tp.vung tau|tp vung tau|chau duc|con dao|long dien|tan thanh|xuyen moc|dat do',
          'Long An'=>'tan an|tp.tan an|tp tan an|ben luc|chau thanh|can giuoc|can duoc|moc hoa|thanh hoa|thu thua|tan hung|tan thanh|tan tru|vinh hung|duc hue|duc hoa|kien tuong|tx.kien tuong|tx kien tuong',
          'Bình Phước'=>'binh long|tx.binh long|tx binh long|phuoc long|tx.phuoc long|tx phuoc long|dong xoai|tx.dong xoai|tx dong xoai|bu dang|bu dop|bu gia map|chon thanh|dong phu|hon quan|loc ninh',
          'Tây Ninh'=>'tay ninh|tx.tay ninh|tx tay ninh|ben cau|chau thanh|duong minh chau|go dau|hoa thanh|trang bang|tan bien|tan chau',
          
 
       );
    foreach($unicode as $nonUnicode=>$uni) $str = preg_replace("/($uni)/i",$nonUnicode,$str);
    

    return $str;
    }

    public function view() {
        
        $this->view->show('transport/view');
    }

}
?>