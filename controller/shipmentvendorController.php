<?php
Class shipmentvendorController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 5 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin vendor';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipment_vendor_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $id = $this->registry->router->param_id;

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND shipment_vendor_id = '.$id;
        }
        
        $tongsodong = count($vendor_model->getAllVendor($data));
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
        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND shipment_vendor_id = '.$id;
        }

        if ($keyword != '') {
            $search = ' AND ( shipment_vendor_name LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        
        
        $this->view->data['vendors'] = $vendor_model->getAllVendor($data);

        $this->view->data['lastID'] = isset($vendor_model->getLastVendor()->shipment_vendor_id)?$vendor_model->getLastVendor()->shipment_vendor_id:0;
        
        $this->view->show('shipmentvendor/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $vendor = $this->model->get('shipmentvendorModel');
            $data = array(
                        
                        'shipment_vendor_name' => trim($_POST['shipment_vendor_name']),
                        'shipment_vendor_contact' => trim($_POST['shipment_vendor_contact']),
                        'shipment_vendor_phone' => trim($_POST['shipment_vendor_phone']),
                        'shipment_vendor_email' => trim($_POST['shipment_vendor_email']),
                        'shipment_vendor_address' => trim($_POST['shipment_vendor_address']),
                        'company_name' => trim($_POST['company_name']),
                        'co_name' => trim($_POST['co_name']),
                        'mst' => trim($_POST['mst']),
                        'vendor_bank_name' => trim($_POST['vendor_bank_name']),
                        'account_number' => trim($_POST['account_number']),
                        'cmg_bank' => trim($_POST['cmg_bank']),
                        'loc_from' => trim($_POST['loc_from']),
                        'loc_to' => trim($_POST['loc_to']),
                        'vendor_type' => trim($_POST['vendor_type']),
                        'vendor_expect_date' => trim($_POST['vendor_expect_date']),
                        'vendor_after_date' => trim($_POST['vendor_after_date']),
                        'director' => trim($_POST['director']),
                        
                        );
            if ($_POST['check'] == "true") {
                $data['vendor_update_user'] = $_SESSION['userid_logined'];
                $data['vendor_update_time'] = time();
                //var_dump($data);
                
                if ($vendor->getAllVendorByWhere($_POST['yes'].' AND shipment_vendor_name = '.trim($_POST['shipment_vendor_name']))) {
                    echo "Thông tin vendor đã tồn tại";
                    return false;
                }
                else{
                    
                        $vendor->updateVendor($data,array('shipment_vendor_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|vendor|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                
            }
            else{
                //$data['vendor_create_user'] = $_SESSION['userid_logined'];
                //$data['vendor_create_time'] = date('m/Y');
                //$data['vendor'] = $_POST['vendor'];
                //var_dump($data);
                if ($vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($_POST['shipment_vendor_name'])))) {
                    echo "Thông tin vendor đã tồn tại";
                    return false;
                }
                else{
                    $vendor->createVendor($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$vendor->getLastVendor()->shipment_vendor_id."|vendor|".implode("-",$data)."\n"."\r\n";
                        
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
            $vendor = $this->model->get('shipmentvendorModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $vendor->deleteVendor($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|vendor|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    
                        $vendor->deleteVendor($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|vendor|"."\n"."\r\n";
                        
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

            $vendor = $this->model->get('shipmentvendorModel');

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
                    if ($val[0] != null  ) {

                        
                            if(!$vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[0])))) {
                                $vendor_data = array(
                                'shipment_vendor_name' => trim($val[0]),
                                'company_name' => trim($val[1]),
                                'co_name' => trim($val[0]),
                                'mst' => trim($val[2]),
                                'vendor_address' => trim($val[3]),
                                'vendor_phone' => trim($val[4]),
                                );
                                $vendor->createVendor($vendor_data);
                            }
                            else if($vendor->getVendorByWhere(array('shipment_vendor_name'=>trim($val[0])))){
                                $id_vendor = $vendor->getVendorByWhere(array('vendor_serie'=>trim($val[0])))->shipment_vendor_id;
                                $vendor_data = array(
                                'company_name' => trim($val[1]),
                                'co_name' => trim($val[0]),
                                'mst' => trim($val[2]),
                                'vendor_address' => trim($val[3]),
                                'vendor_phone' => trim($val[4]),
                                );
                                $vendor->updateVendor($vendor_data,array('shipment_vendor_id' => $id_vendor));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('shipmentvendor');
        }
        $this->view->show('shipmentvendor/import');

    }

    public function getVendor($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>