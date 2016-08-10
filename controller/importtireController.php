<?php
Class importtireController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nhập hàng lốp xe';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'import_tire_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));
        $this->view->data['vendor_list'] = $vendors;

        $sale_model = $this->model->get('importtireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'import_tire_date >= '.strtotime($batdau).' AND import_tire_date <= '.strtotime($ketthuc),
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }
        
        
        $tongsodong = count($sale_model->getAllSale($data));
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
            'where' => 'import_tire_date >= '.strtotime($batdau).' AND import_tire_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }

        /*if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR comment LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $sales = $sale_model->getAllSale($data);
        
        $this->view->data['sales'] = $sales;

        $this->view->data['lastID'] = isset($sale_model->getLastSale()->import_tire_id)?$sale_model->getLastSale()->import_tire_id:0;

        $this->view->show('importtire/index');
    }

    public function goingimport(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $tiregoing = $this->model->get('tiregoingModel');
            $tirebrand = $this->model->get('tirebrandModel');
            $tiresize = $this->model->get('tiresizeModel');
            $tirepattern = $this->model->get('tirepatternModel');

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
                        //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        $val[] = $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[1] != null && $val[2] != null && $val[4] != null) {

                        $tire_brand = $tirebrand->getTireByWhere(array('tire_brand_name'=>trim($val[3]))); 
                        if (!$tire_brand) {
                               $tirebrand->createTire(array('tire_brand_name'=>trim($val[3])));
                               $tire_brand_id = $tirebrand->getLastTire()->tire_brand_id;
                           }   
                           else{
                                $tire_brand_id = $tire_brand->tire_brand_id;
                           }

                        $tire_size = $tiresize->getTireByWhere(array('tire_size_number'=>trim($val[4]))); 
                        if (!$tire_size) {
                               $tiresize->createTire(array('tire_size_number'=>trim($val[4])));
                               $tire_size_id = $tiresize->getLastTire()->tire_size_id;
                           }   
                           else{
                                $tire_size_id = $tire_size->tire_size_id;
                           }

                        $tire_pattern = $tirepattern->getTireByWhere(array('tire_pattern_name'=>trim($val[5]))); 
                        if (!$tire_pattern) {
                               $tirepattern->createTire(array('tire_pattern_name'=>trim($val[5])));
                               $tire_pattern_id = $tirepattern->getLastTire()->tire_pattern_id;
                           }   
                           else{
                                $tire_pattern_id = $tire_pattern->tire_pattern_id;
                           }

                        $ngay = PHPExcel_Shared_Date::ExcelToPHP(trim($val[2]));                                      

                        $ngay = $ngay-3600;
                                
                                if($tiregoing->getTireByWhere(array('code'=>trim($val[1]),'tire_brand'=>$tire_brand_id,'tire_size'=>$tire_size_id,'tire_pattern'=>$tire_pattern_id))) {
                                    $id_tire_going = $tiregoing->getTireByWhere(array('code'=>trim($val[1]),'tire_brand'=>$tire_brand_id,'tire_size'=>$tire_size_id,'tire_pattern'=>$tire_pattern_id))->tire_going_id;

                                    $tire_going_data = array(
                                    'tire_going_date' => $ngay,
                                    'code' => trim($val[1]),
                                    'tire_size' => $tire_size_id,
                                    'tire_pattern' => $tire_pattern_id,
                                    'tire_brand' => $tire_brand_id,
                                    'tire_number' => trim($val[6]),
                                    );
                                    $tiregoing->updateTire($tire_going_data,array('tire_going_id' => $id_tire_going));
                                }
                                else{
                                    $tire_going_data = array(
                                    'tire_going_date' => $ngay,
                                    'code' => trim($val[1]),
                                    'tire_size' => $tire_size_id,
                                    'tire_pattern' => $tire_pattern_id,
                                    'tire_brand' => $tire_brand_id,
                                    'tire_number' => trim($val[6]),
                                    );
                                    $tiregoing->createTire($tire_going_data);
                                }
                            
                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            //return $this->view->redirect('stock');
        }
        $this->view->show('importtire/goingimport');
        
    }

    public function going(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $code = $this->registry->router->param_id;

        $tire_price_model = $this->model->get('tirepriceModel');
        $tire_going_model = $this->model->get('tiregoingModel');
        $join = array('table'=>'tire_pattern,tire_brand,tire_size','where'=>'tire_pattern = tire_pattern_id AND tire_brand = tire_brand_id AND tire_size = tire_size_id');

        $data = array(
            'where' => 'code='.$code,
        );

        $goings = $tire_going_model->getAllTire($data,$join);
        $this->view->data['tire_goings'] = $goings;

        $price = array();
        foreach ($goings as $going) {
            $qr = $tire_price_model->queryTire('SELECT * FROM tire_price WHERE tire_brand = '.$going->tire_brand.' AND tire_size = '.$going->tire_size.' AND tire_pattern = '.$going->tire_pattern.' AND price_end_time >= '.$going->tire_going_date.' ORDER BY price_end_time DESC');
            foreach ($qr as $key) {
                if (!isset($price[$going->tire_going_id])) {
                    $price[$going->tire_going_id] = $key->supply_price;
                }
            }
        }
        $this->view->data['price'] = $price;

        $this->view->data['code'] = $code;
        $this->view->data['tire_going_date'] = $this->registry->router->order_by;

        $this->view->show('importtire/going');
    }
    public function editgoing(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_going_model = $this->model->get('tiregoingModel');

            $brand = trim($_POST['tire_brand']);
            $pattern = trim($_POST['tire_pattern']);
            $size = trim($_POST['tire_size']);
            $number = trim($_POST['tire_number']);
            $code = trim($_POST['code']);
            $tire_going_date = strtotime(trim($_POST['tire_going_date']));

            $data = array(
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
                'code'=>$code,
                'tire_going_date'=>$tire_going_date,
            );

            if ($_POST['yes'] != "") {
                $tire_going_model->updateTire($data,array('tire_going_id'=>$_POST['yes']));

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_going|"."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);
            }
            else{
                $tire_going_model->createTire($data);
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_going_model->getLastTire()->tire_going_id."|tire_going|"."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);
            }
            

        }
    }
    public function deletegoing(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_going_model = $this->model->get('tiregoingModel');
            if(isset($_POST['data'])){
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
                        
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        
                    }
                    else{
                        $tire_going_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_going|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
            }
            
        }
    }

  
    

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $sale = $this->model->get('importtireModel');
            $data = array(
                        'import_tire_date' => strtotime(trim($_POST['import_tire_date'])),
                        'code' => trim($_POST['code']),
                        'comment' => trim($_POST['comment']),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'import_tire_lock' => 0,
                        );
    
            $vendor = $this->model->get('shipmentvendorModel');
           

            /**************/
            $vendor_cost = $_POST['vendor_cost'];
            $vendor_cost4 = $_POST['vendor_cost4'];
            $vendor_cost2 = $_POST['vendor_cost2'];
            
            /**************/
            $sale_vendor = $this->model->get('importtirecostModel');
            
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');
            $costs = $this->model->get('costsModel');

            $sales_model = $this->model->get('salesModel');
            $pending_payable = $this->model->get('pendingpayableModel');

            $tire_going_model = $this->model->get('tiregoingModel');

            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                    $sale_data = $sale->getSale($_POST['yes']);

                    $data['count_update'] = $sale_data->count_update+1;

                    if($sale_data->count_update > 0){
                        $data['import_tire_lock'] = 1;
                    }
                
                    $sale->updateSale($data,array('import_tire_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $going = $tire_going_model->queryTire('UPDATE tire_going set tire_going_date = '.$data['expect_date'].', code = '.$data['code'].' WHERE code = '.$sale_data->code);

                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;

                    foreach ($vendor_cost as $v) {
                        $sale_vendor_data = array(
                            'trading' => $_POST['yes'],
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_deposit' => $v['check_deposit'],
                            'check_cost' => 2,
                        );

                        if ($sale_vendor_data['check_deposit'] != 1) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];
                        }
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>2,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_cost=2 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                

                                $receivable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'receivable_date' => $sale_data->import_tire_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'check_vat'=>0,
                                    'check_cost'=>2,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_cost=2 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }
                               


                                $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                
                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                        'check_cost'=>2,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $payable->createCosts($payable_data);
                                        }


                                    $receivable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'receivable_date' => $sale_data->import_tire_date,
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'check_vat'=>0,
                                        'check_cost'=>2,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                 
                                
                            }
                        }

                        else{

                            if($sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                    $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                    $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                    $total = $old_cost+$old_cost_vat;

                                    $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                    $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                    $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


                                    $owe_data = array(
                                        'owe_date' => $sale_data->import_tire_date,
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                        'week' => (int)date('W',$sale_data->import_tire_date),
                                        'year' => (int)date('Y',$sale_data->import_tire_date),
                                        'import_tire' => $_POST['yes'],
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                    }

                                    $owe->updateOwe($owe_data,array('import_tire'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));

                                    if ($old_cost>0 && $sale_vendor_data['cost_vat']>0 && $sale_vendor_data['cost']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost_vat>0 && $sale_vendor_data['cost']>0 && $sale_vendor_data['cost_vat']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($sale_vendor_data['cost'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                                'check_cost'=>2,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($sale_vendor_data['cost_vat'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost_vat'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                                'check_cost'=>2,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                    }
                                    else{

                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost_vat'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                        
                                    


                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>2,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>2,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                    }
                                    

                                    $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>2,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                $owe_data = array(
                                    'owe_date' => $sale_data->import_tire_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->import_tire_date),
                                    'year' => (int)date('Y',$sale_data->import_tire_date),
                                    'import_tire' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    
                                    $owe->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost_vat'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                        'check_cost'=>2,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost_vat'] > 0){
                                            $payable->createCosts($payable_data);
                                        }
                                    
                                


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>2,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }

                                
                            }
                        }


                    }


                    foreach ($vendor_cost4 as $v) {
                        $sale_vendor_data = array(
                            'trading' => $_POST['yes'],
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_deposit' => $v['check_deposit'],
                            'check_cost' => 1,
                        );

                        if ($sale_vendor_data['check_deposit'] != 1) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];

                        }
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_cost=1 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                

                                $receivable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'receivable_date' => $sale_data->import_tire_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'check_vat'=>0,
                                    'check_cost'=>1,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_cost=1 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }
                               


                                $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                
                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                        'check_cost'=>1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $payable->createCosts($payable_data);
                                        }


                                    $receivable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'receivable_date' => $sale_data->import_tire_date,
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'check_vat'=>0,
                                        'check_cost'=>1,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                 
                                
                            }
                        }

                        else{

                            if($sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                    $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                    $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                    $total = $old_cost+$old_cost_vat;

                                    $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                    $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                    $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


                                    $owe_data = array(
                                        'owe_date' => $sale_data->import_tire_date,
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                        'week' => (int)date('W',$sale_data->import_tire_date),
                                        'year' => (int)date('Y',$sale_data->import_tire_date),
                                        'import_tire' => $_POST['yes'],
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                    }

                                    $owe->updateOwe($owe_data,array('import_tire'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));

                                    if ($old_cost>0 && $sale_vendor_data['cost_vat']>0 && $sale_vendor_data['cost']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost_vat>0 && $sale_vendor_data['cost']>0 && $sale_vendor_data['cost_vat']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($sale_vendor_data['cost'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                                'check_cost'=>1,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($sale_vendor_data['cost_vat'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost_vat'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                                'check_cost'=>1,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                    }
                                    else{

                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost_vat'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                        
                                    


                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>1,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>1,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                    }
                                    

                                    $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>1,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                $owe_data = array(
                                    'owe_date' => $sale_data->import_tire_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->import_tire_date),
                                    'year' => (int)date('Y',$sale_data->import_tire_date),
                                    'import_tire' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    
                                    $owe->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost_vat'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                        'check_cost'=>1,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost_vat'] > 0){
                                            $payable->createCosts($payable_data);
                                        }
                                    
                                


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }


                                
                            }
                        }


                    }

                    foreach ($vendor_cost2 as $v) {
                        $sale_vendor_data = array(
                            'trading' => $_POST['yes'],
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_deposit' => $v['check_deposit'],
                            'check_cost' => 3,
                        );

                        if ($sale_vendor_data['check_deposit'] != 1) {
                            $kvat += $sale_vendor_data['cost'];
                            $vat += $sale_vendor_data['cost_vat'];

                        }
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];

                        if ($sale_vendor_data['check_deposit'] == 1) {
                            if($sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                $total = $old_cost+$old_cost_vat;

                                $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>3,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $check = $payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                            $payable_data['approve'] = 10;
                                        }

                                        $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                    }
                                    else{
                                        $payable->queryCosts('DELETE FROM payable WHERE check_cost=3 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes'].' AND cost_type='.$sale_vendor_data['type']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                }
                                

                                $receivable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'receivable_date' => $sale_data->import_tire_date,
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'check_vat'=>0,
                                    'check_cost'=>3,
                                );
                                if($receivable_data['week'] == 53){
                                    $receivable_data['week'] = 1;
                                    $receivable_data['year'] = $receivable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                if($receivable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])))){
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->updateCosts($receivable_data,array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes'])));
                                    }
                                    else{
                                        $receivable->queryCosts('DELETE FROM receivable WHERE check_cost=3 AND check_vat=0 AND money='.$old_cost.' AND vendor='.$sale_vendor_data['vendor'].' AND import_tire='.$_POST['yes']);
                                    }
                                }
                                else{
                                    if($sale_vendor_data['cost'] > 0){
                                        $receivable->createCosts($receivable_data);
                                    }
                                }
                               


                                $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                
                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>0,
                                        'approve' => null,
                                        'check_cost'=>3,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $payable->createCosts($payable_data);
                                        }


                                    $receivable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost'],
                                        'receivable_date' => $sale_data->import_tire_date,
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'check_vat'=>0,
                                        'check_cost'=>3,
                                    );
                                    if($receivable_data['week'] == 53){
                                        $receivable_data['week'] = 1;
                                        $receivable_data['year'] = $receivable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $receivable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost'] > 0){
                                            $receivable->createCosts($receivable_data);
                                        }
                 
                                
                            }
                        }

                        else{

                            if($sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))){
                                    $old_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost;
                                    $old_cost_vat = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->cost_vat;
                                    $total = $old_cost+$old_cost_vat;

                                    $old_invoice_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->invoice_cost;
                                    $old_pay_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->pay_cost;
                                    $old_document_cost = $sale_vendor->getVendorByWhere(array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']))->document_cost;


                                    $owe_data = array(
                                        'owe_date' => $sale_data->import_tire_date,
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                        'week' => (int)date('W',$sale_data->import_tire_date),
                                        'year' => (int)date('Y',$sale_data->import_tire_date),
                                        'import_tire' => $_POST['yes'],
                                    );
                                    if($owe_data['week'] == 53){
                                        $owe_data['week'] = 1;
                                        $owe_data['year'] = $owe_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                        $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                    }

                                    $owe->updateOwe($owe_data,array('import_tire'=>$_POST['yes'],'vendor'=>$sale_vendor_data['vendor'],'money'=>$total));

                                    if ($old_cost>0 && $sale_vendor_data['cost_vat']>0 && $sale_vendor_data['cost']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>3,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost_vat>0 && $sale_vendor_data['cost']>0 && $sale_vendor_data['cost_vat']==0) {
                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>3,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }
                                        if($payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                    }
                                    elseif ($old_cost>0 && $old_cost_vat>0) {
                                        if ($sale_vendor_data['cost'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>0,
                                                'approve' => null,
                                                'check_cost'=>3,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                        if ($sale_vendor_data['cost_vat'] == 0) {
                                            $payable_data = array(
                                                'vendor' => $sale_vendor_data['vendor'],
                                                'money' => $sale_vendor_data['cost_vat'],
                                                'payable_date' => $sale_data->import_tire_date,
                                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                                'expect_date' => $sale_vendor_data['expect_date'],
                                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                                'code' => $sale_data->code,
                                                'source' => $sale_vendor_data['source'],
                                                'comment' => $sale_vendor_data['comment'],
                                                'create_user' => $_SESSION['userid_logined'],
                                                'type' => 4,
                                                'import_tire' => $_POST['yes'],
                                                'cost_type' => $sale_vendor_data['type'],
                                                'check_vat'=>1,
                                                'approve' => null,
                                                'check_cost'=>3,
                                            );
                                            if($payable_data['week'] == 53){
                                                $payable_data['week'] = 1;
                                                $payable_data['year'] = $payable_data['year']+1;
                                            }
                                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                            }
                                            $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                        }
                                    }
                                    else{

                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost_vat'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>1,
                                            'approve' => null,
                                            'check_cost'=>3,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }

                                                $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost_vat,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost_vat'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                        
                                    


                                        $payable_data = array(
                                            'vendor' => $sale_vendor_data['vendor'],
                                            'money' => $sale_vendor_data['cost'],
                                            'payable_date' => $sale_data->import_tire_date,
                                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                            'expect_date' => $sale_vendor_data['expect_date'],
                                            'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                            'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                            'code' => $sale_data->code,
                                            'source' => $sale_vendor_data['source'],
                                            'comment' => $sale_vendor_data['comment'],
                                            'create_user' => $_SESSION['userid_logined'],
                                            'type' => 4,
                                            'import_tire' => $_POST['yes'],
                                            'cost_type' => $sale_vendor_data['type'],
                                            'check_vat'=>0,
                                            'approve' => null,
                                            'check_cost'=>3,
                                        );
                                        if($payable_data['week'] == 53){
                                            $payable_data['week'] = 1;
                                            $payable_data['year'] = $payable_data['year']+1;
                                        }
                                        if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                            $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                        }

                                        if($payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            $check = $payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));

                                            if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                                                $payable_data['approve'] = 10;
                                            }
                                                $payable->updateCosts($payable_data,array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']));
                                            
                                        }
                                        elseif(!$payable->getCostsByWhere(array('check_cost'=>3,'money'=>$old_cost,'vendor' => $sale_vendor_data['vendor'],'import_tire'=>trim($_POST['yes']),'cost_type' => $sale_vendor_data['type']))){
                                            if($sale_vendor_data['cost'] > 0){
                                                $payable->createCosts($payable_data);
                                            }
                                        }
                                    }
                                    

                                    $sale_vendor->updateVendor($sale_vendor_data,array('check_cost'=>3,'trading'=>$_POST['yes'],'vendor'=>$v['vendor'],'type' => $v['cost_type']));
                            }
                            else{
                                $sale_vendor->createVendor($sale_vendor_data);

                                $owe_data = array(
                                    'owe_date' => $sale_data->import_tire_date,
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                    'week' => (int)date('W',$sale_data->import_tire_date),
                                    'year' => (int)date('Y',$sale_data->import_tire_date),
                                    'import_tire' => $_POST['yes'],
                                );
                                if($owe_data['week'] == 53){
                                    $owe_data['week'] = 1;
                                    $owe_data['year'] = $owe_data['year']+1;
                                }
                                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                                }

                                    //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                    
                                    $owe->createOwe($owe_data);

                                    $payable_data = array(
                                        'vendor' => $sale_vendor_data['vendor'],
                                        'money' => $sale_vendor_data['cost_vat'],
                                        'payable_date' => $sale_data->import_tire_date,
                                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                        'expect_date' => $sale_vendor_data['expect_date'],
                                        'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                        'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                        'code' => $sale_data->code,
                                        'source' => $sale_vendor_data['source'],
                                        'comment' => $sale_vendor_data['comment'],
                                        'create_user' => $_SESSION['userid_logined'],
                                        'type' => 4,
                                        'import_tire' => $_POST['yes'],
                                        'cost_type' => $sale_vendor_data['type'],
                                        'check_vat'=>1,
                                        'approve' => null,
                                        'check_cost'=>3,
                                    );
                                    if($payable_data['week'] == 53){
                                        $payable_data['week'] = 1;
                                        $payable_data['year'] = $payable_data['year']+1;
                                    }
                                    if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                        $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                    }

                                    
                                        if($sale_vendor_data['cost_vat'] > 0){
                                            $payable->createCosts($payable_data);
                                        }
                                    
                                


                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost'],
                                    'payable_date' => $sale_data->import_tire_date,
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $_POST['yes'],
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>0,
                                    'approve' => null,
                                    'check_cost'=>3,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                    if($sale_vendor_data['cost'] > 0){
                                        $payable->createCosts($payable_data);
                                    }


                                
                            }
                        }


                    }


                    $data_update = array(
                        'cost' => $kvat,
                        'cost_vat' => $vat,
                        'estimate_cost' => $estimate,

                    );
                    
                    $sale->updateSale($data_update,array('import_tire_id' => $_POST['yes']));

                    $sale_datas = $sale->getSale($_POST['yes']);

                    $data_pending = array(
                            'code' => $sale_datas->code,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->estimate_cost,
                            
                            'money' => $sale_datas->cost+$sale_datas->cost_vat,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                            'approve' => null,
                        );

                    $check = $pending_payable->getCostsByWhere(array('import_tire'=>$_POST['yes']));

                    if ($check->money >= $data_pending['money'] && $check->approve > 0) {
                        $data_pending['approve'] = 10;
                    }

                        $pending_payable->updateCosts($data_pending,array('import_tire'=>$_POST['yes']));

                    $salesdata = $sales_model->getSalesByWhere(array('import_tire'=>$_POST['yes']));

                    if ($salesdata) {
                        $data_sales = array(
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'cost' => $kvat+$vat+$estimate,
                            'sales_create_time' => $data['import_tire_date'],
                            'import_tire' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
                    }
                    elseif (!$salesdata) {
                        $data_sales = array(
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'cost' => $kvat+$vat+$estimate,
                            'sales_create_time' => $data['import_tire_date'],
                            'import_tire' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->createSales($data_sales);
                    }


                    


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_tire|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    if($data['code']==0){
                        $code_model = $this->model->get('codeModel');
                        $last_code = $code_model->getLastCode()->code;
                        $nam = substr(date('Y'), 2);
                        $thang = date('m');

                        if (substr($last_code, 0, 4) != $nam.$thang) {
                            $code_data = array(
                                'code' => $nam.$thang.'01',
                            );
                            $code_model->createCode($code_data);

                            $data['code'] = $code_model->getLastCode()->code;
                        }
                        else{
                            $code_data = array(
                                'code' => (int)$last_code + 1,
                            );
                            $code_model->createCode($code_data);

                            $data['code'] = $code_data['code'];
                        }
                    }


                    $data['sale'] = $_SESSION['userid_logined'];
                    $data['cost'] = 0;
                    $data['cost_vat'] = 0;

                    $sale->createSale($data);
                    echo "Thêm thành công";

                    /*********************/
                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;


                    $id_trading = $sale->getLastSale()->import_tire_id;
                    $sale_data = $sale->getSale($id_trading);

                    foreach ($vendor_cost as $v) {
                        $sale_vendor_data = array(
                            'trading' => $id_trading,
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_cost'=>2,
                        );

                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        $kvat += $sale_vendor_data['cost'];
                        $vat += $sale_vendor_data['cost_vat'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];


                        $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->import_tire_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->import_tire_date),
                                'year' => (int)date('Y',$sale_data->import_tire_date),
                                'import_tire' => $id_trading,
                            );
                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                            }

                            //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                
                                $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => strtotime(date('d-m-Y')),
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $id_trading,
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'check_cost'=>2,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                
                            


                            $payable_data = array(
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost'],
                                'payable_date' => strtotime(date('d-m-Y')),
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'import_tire' => $id_trading,
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                                'check_cost'=>2,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                            }

                            
                            if($sale_vendor_data['cost'] > 0){
                                $payable->createCosts($payable_data);
                            }

                        
                    }


                    foreach ($vendor_cost4 as $v) {
                        $sale_vendor_data = array(
                            'trading' => $id_trading,
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_cost'=>1,
                        );

                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        $kvat += $sale_vendor_data['cost'];
                        $vat += $sale_vendor_data['cost_vat'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];


                        $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->import_tire_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->import_tire_date),
                                'year' => (int)date('Y',$sale_data->import_tire_date),
                                'import_tire' => $id_trading,
                            );
                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                            }

                            //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                
                                $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => strtotime(date('d-m-Y')),
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $id_trading,
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'check_cost'=>1,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                
                            


                            $payable_data = array(
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost'],
                                'payable_date' => strtotime(date('d-m-Y')),
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'import_tire' => $id_trading,
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                                'check_cost'=>1,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                            }

                            
                            if($sale_vendor_data['cost'] > 0){
                                $payable->createCosts($payable_data);
                            }

                        
                    }

                    foreach ($vendor_cost2 as $v) {
                        $sale_vendor_data = array(
                            'trading' => $id_trading,
                            'vendor' => $v['vendor'],
                            'type' => $v['cost_type'],
                            'cost' => trim(str_replace(',','',$v['cost'])),
                            'cost_vat' => trim(str_replace(',','',$v['cost_vat'])),
                            'expect_date' => strtotime(date('d-m-Y',strtotime($v['vendor_expect_date']))),
                            'source' => $v['vendor_source'],
                            'invoice_cost' => trim(str_replace(',','',$v['invoice_cost'])),
                            'pay_cost' => trim(str_replace(',','',$v['pay_cost'])),
                            'document_cost' => trim(str_replace(',','',$v['document_cost'])),
                            'comment' => $v['cost_comment'],
                            'check_cost'=>3,
                        );

                        //$kvat += $sale_vendor_data['cost']+$sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost'];
                        //$vat += $sale_vendor_data['cost_vat']+$sale_vendor_data['document_cost'];

                        $kvat += $sale_vendor_data['cost'];
                        $vat += $sale_vendor_data['cost_vat'];
                        $estimate += $sale_vendor_data['invoice_cost']+$sale_vendor_data['pay_cost']+$sale_vendor_data['document_cost'];


                        $sale_vendor->createVendor($sale_vendor_data);

                            $owe_data = array(
                                'owe_date' => $sale_data->import_tire_date,
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost']+$sale_vendor_data['cost_vat'],
                                'week' => (int)date('W',$sale_data->import_tire_date),
                                'year' => (int)date('Y',$sale_data->import_tire_date),
                                'import_tire' => $id_trading,
                            );
                            if($owe_data['week'] == 53){
                                $owe_data['week'] = 1;
                                $owe_data['year'] = $owe_data['year']+1;
                            }
                            if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                                $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                            }

                            //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                                
                                $owe->createOwe($owe_data);

                                $payable_data = array(
                                    'vendor' => $sale_vendor_data['vendor'],
                                    'money' => $sale_vendor_data['cost_vat'],
                                    'payable_date' => strtotime(date('d-m-Y')),
                                    'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                    'expect_date' => $sale_vendor_data['expect_date'],
                                    'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                    'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                    'code' => $sale_data->code,
                                    'source' => $sale_vendor_data['source'],
                                    'comment' => $sale_vendor_data['comment'],
                                    'create_user' => $_SESSION['userid_logined'],
                                    'type' => 4,
                                    'import_tire' => $id_trading,
                                    'cost_type' => $sale_vendor_data['type'],
                                    'check_vat'=>1,
                                    'check_cost'=>3,
                                );
                                if($payable_data['week'] == 53){
                                    $payable_data['week'] = 1;
                                    $payable_data['year'] = $payable_data['year']+1;
                                }
                                if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                    $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                                }

                                
                                    if($sale_vendor_data['cost_vat'] > 0){
                                        $payable->createCosts($payable_data);
                                    }
                                
                            


                            $payable_data = array(
                                'vendor' => $sale_vendor_data['vendor'],
                                'money' => $sale_vendor_data['cost'],
                                'payable_date' => strtotime(date('d-m-Y')),
                                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                                'expect_date' => $sale_vendor_data['expect_date'],
                                'week' => (int)date('W',$sale_vendor_data['expect_date']),
                                'year' => (int)date('Y',$sale_vendor_data['expect_date']),
                                'code' => $sale_data->code,
                                'source' => $sale_vendor_data['source'],
                                'comment' => $sale_vendor_data['comment'],
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'import_tire' => $id_trading,
                                'cost_type' => $sale_vendor_data['type'],
                                'check_vat'=>0,
                                'check_cost'=>3,
                            );
                            if($payable_data['week'] == 53){
                                $payable_data['week'] = 1;
                                $payable_data['year'] = $payable_data['year']+1;
                            }
                            if (((int)date('W',$sale_vendor_data['expect_date']) == 1) && ((int)date('m',$sale_vendor_data['expect_date']) == 12) ) {
                                $payable_data['year'] = (int)date('Y',$sale_vendor_data['expect_date'])+1;
                            }

                            
                            if($sale_vendor_data['cost'] > 0){
                                $payable->createCosts($payable_data);
                            }

                        
                    }


                    ////////////////////



                    $data_update = array(
                        'cost' => $kvat,
                        'cost_vat' => $vat,
                        'estimate_cost' => $estimate,

                    );
                    
                    $sale->updateSale($data_update,array('import_tire_id' => $id_trading));

                    $sale_datas = $sale->getSale($id_trading);

                    $data_pending = array(
                            'code' => $sale_datas->code,
                            'cost' => $sale_datas->cost+$sale_datas->cost_vat+$sale_datas->estimate_cost,
                            'import_tire' => $id_trading,
                            'money' => $sale_datas->cost+$sale_datas->cost_vat,
                            'comment' => 'Chi phí code '.$sale_datas->code.' '.$sale_datas->comment,
                        );

                        $pending_payable->createCosts($data_pending);


                    $data_sales = array(
                            'code' => $data['code'],
                            'comment' => $data['comment'],
                            'cost' => $kvat+$vat+$estimate,
                            'sales_create_time' => $data['import_tire_date'],
                            'import_tire' => $_POST['yes'],
                            'sales_update_user' => $_SESSION['userid_logined'],
                            'sales_update_time' => strtotime(date('d-m-Y')),
                        );
                        $sales_model->createSales($data_sales);

                    /**************/

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sale->getLastSale()->import_tire_id."|import_tire|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    

    public function lock(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {


            $sale = $this->model->get('importtireModel');
            $sale_data = $sale->getSale($_POST['data']);

            $data = array(
                        
                        'import_tire_lock' => trim($_POST['value']),
                        );
          
            $sale->updateSale($data,array('import_tire_id' => $_POST['data']));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."lock"."|".$_POST['data']."|import_tire|".$_POST['value']."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    


    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale = $this->model->get('importtireModel');
            $receivable = $this->model->get('receivableModel');
            $payable = $this->model->get('payableModel');
            $obtain = $this->model->get('obtainModel');
            $owe = $this->model->get('oweModel');
            $vendor = $this->model->get('importtirecostModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $sale_data = $sale->getSale($data);

                        $re = $receivable->getAllCosts(array('where'=>'import_tire='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'import_tire='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable->queryCosts('DELETE FROM receivable WHERE import_tire = '.$data);
                        $payable->queryCosts('DELETE FROM payable WHERE import_tire = '.$data);
                        $obtain->queryObtain('DELETE FROM obtain WHERE import_tire = '.$data);
                        $owe->queryOwe('DELETE FROM owe WHERE import_tire = '.$data);
                        $vendor->queryVendor('DELETE FROM import_tire_cost WHERE trading = '.$data);
                        $sales_model->querySales('DELETE FROM sales WHERE import_tire = '.$data);
                        $sale->deleteSale($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $sale_data = $sale->getSale($_POST['data']);

                        $re = $receivable->getAllCosts(array('where'=>'import_tire='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable->getAllCosts(array('where'=>'import_tire='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                         $receivable->queryCosts('DELETE FROM receivable WHERE import_tire = '.$_POST['data']);
                         $payable->queryCosts('DELETE FROM payable WHERE import_tire = '.$_POST['data']);
                        $obtain->queryObtain('DELETE FROM obtain WHERE import_tire = '.$_POST['data']);
                        $owe->queryOwe('DELETE FROM owe WHERE import_tire = '.$_POST['data']);
                        $vendor->queryVendor('DELETE FROM import_tire_cost WHERE trading = '.$_POST['data']);
                        $sales_model->querySales('DELETE FROM sales WHERE import_tire = '.$_POST['data']);
                        $sale->deleteSale($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }




    public function getvendor4(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'check_cost = 1 AND trading='.$_POST['sale_report']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk4"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type4" name="cost_type4[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor4" name="vendor4[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date4" type="date"   name="vendor_expect_date4[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source4"  name="vendor_source4[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat4"  name="cost_vat4[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost4"  name="invoice_cost4[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment4" name="cost_comment4[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost4"  name="cost4[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost4"  name="pay_cost4[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td>';
                    $str .= '<td></td><td><input type="checkbox" value="1" name="check_deposit4[]" class="check_deposit4"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost4"  name="document_cost4[]" value="0"  ></td></tr></table></td></tr>';
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    

                    foreach($banks as $bank){ 
                        $slnh = ($bank->bank_id == $v->source)?'selected="selected"':null;
                        $ba .= '<option '.$slnh .' value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }

                     $truck = ($v->type==1)?'selected="selected"':null;
                     $bar = ($v->type==2)?'selected="selected"':null;
                     $fee = ($v->type==3)?'selected="selected"':null;
                     $thu = ($v->type==4)?'selected="selected"':null;
                     $hh = ($v->type==5)?'selected="selected"':null;
                     $tt = ($v->type==6)?'selected="selected"':null;
                     $khac = ($v->type==7)?'selected="selected"':null;

                     $checked = $v->check_deposit==1?'checked':null;

                    $str .= '<tr class="'.$v->trading.'">';
                    $str .= '<td><input type="checkbox" name="chk4" tabindex="'.$v->type.'" data="'.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type4" name="cost_type4[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor4" name="vendor4[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date4" type="date"   name="vendor_expect_date4[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source4"  name="vendor_source4[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat4"  name="cost_vat4[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost4"  name="invoice_cost4[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment4" name="cost_comment4[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost4"  name="cost4[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost4"  name="pay_cost4[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->trading .'"><td></td>';
                    $str .= '<td></td><td><input disabled type="checkbox" '.$checked.' value="1" name="check_deposit4[]" class="check_deposit4"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost4"  name="document_cost4[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletevendor4(){
        if(isset($_POST['data'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $sale = $this->model->get('importtireModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $receivable = $this->model->get('receivableModel');
            $costs = $this->model->get('costsModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => 'check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM import_tire_cost WHERE check_cost = 1 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'check_cost = 1 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'check_cost = 1 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE check_cost = 1 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE check_cost = 1 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']);



            
            
            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;
                if($vendor_data->check_deposit != 1 && $vendor_data->type != 4){
                    $kvat += $vendor_data->cost;
                    $vat += $vendor_data->cost_vat;
                }
                
                $estimate += $vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->document_cost;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

               

            }

            $owe->queryOwe('DELETE FROM owe WHERE import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND money='.$old_cost.' LIMIT 1');

            
                $owe_data = array(
                    'owe_date' => $sale_data->import_tire_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$sale_data->import_tire_date),
                    'year' => (int)date('Y',$sale_data->import_tire_date),
                    'import_tire' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                //    $owe->createOwe($owe_data);

                $salesdata = $sales_model->getSalesByWhere(array('import_tire'=>$_POST['data']));
                $data_sales = array(
                    'cost' => $salesdata->cost-$kvat-$vat-$estimate,
                    'profit' => $sale_data->profit+$kvat+$vat+$estimate,
                    'import_tire' => $_POST['data'],
                    'sales_update_user' => $_SESSION['userid_logined'],
                    'sales_update_time' => strtotime(date('d-m-Y')),
                );
                $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
            


            $data = array(
                'cost' => $sale_data->cost-$kvat,
                'cost_vat' => $sale_data->cost_vat-$vat,
                'estimate_cost' => $sale_data->estimate_cost-$estimate,

            );
            
            $sale->updateSale($data,array('import_tire_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

            

        }
    }

    public function getvendor(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'check_cost = 2 AND trading='.$_POST['sale_report']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type" name="cost_type[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost"  name="invoice_cost[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost"  name="pay_cost[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td>';
                    $str .= '<td></td><td><input type="checkbox" value="1" name="check_deposit[]" class="check_deposit"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost"  name="document_cost[]" value="0"  ></td></tr></table></td></tr>';
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    

                    foreach($banks as $bank){ 
                        $slnh = ($bank->bank_id == $v->source)?'selected="selected"':null;
                        $ba .= '<option '.$slnh .' value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }

                     $truck = ($v->type==1)?'selected="selected"':null;
                     $bar = ($v->type==2)?'selected="selected"':null;
                     $fee = ($v->type==3)?'selected="selected"':null;
                     $thu = ($v->type==4)?'selected="selected"':null;
                     $hh = ($v->type==5)?'selected="selected"':null;
                     $tt = ($v->type==6)?'selected="selected"':null;
                     $khac = ($v->type==7)?'selected="selected"':null;

                     $checked = $v->check_deposit==1?'checked':null;

                    $str .= '<tr class="'.$v->trading.'">';
                    $str .= '<td><input type="checkbox" name="chk" tabindex="'.$v->type.'" data="'.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type" name="cost_type[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date" type="date"   name="vendor_expect_date[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source"  name="vendor_source[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat"  name="cost_vat[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost"  name="invoice_cost[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment" name="cost_comment[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost"  name="cost[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost"  name="pay_cost[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->trading .'"><td></td>';
                    $str .= '<td></td><td><input disabled type="checkbox" '.$checked.' value="1" name="check_deposit[]" class="check_deposit"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost"  name="document_cost[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletevendor(){
        if(isset($_POST['data'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $sale = $this->model->get('importtireModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $receivable = $this->model->get('receivableModel');
            $costs = $this->model->get('costsModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => 'check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM import_tire_cost WHERE check_cost = 2 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'check_cost = 2 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'check_cost = 2 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE check_cost = 2 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE check_cost = 2 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']);



            
            
            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;
                if($vendor_data->check_deposit != 1 && $vendor_data->type != 4){
                    $kvat += $vendor_data->cost;
                    $vat += $vendor_data->cost_vat;
                }
                
                $estimate += $vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->document_cost;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

               

            }

            $owe->queryOwe('DELETE FROM owe WHERE import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND money='.$old_cost.' LIMIT 1');

            
                $owe_data = array(
                    'owe_date' => $sale_data->import_tire_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$sale_data->import_tire_date),
                    'year' => (int)date('Y',$sale_data->import_tire_date),
                    'import_tire' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                //    $owe->createOwe($owe_data);

                $salesdata = $sales_model->getSalesByWhere(array('import_tire'=>$_POST['data']));
                $data_sales = array(
                    'cost' => $salesdata->cost-$kvat-$vat-$estimate,
                    'profit' => $sale_data->profit+$kvat+$vat+$estimate,
                    'import_tire' => $_POST['data'],
                    'sales_update_user' => $_SESSION['userid_logined'],
                    'sales_update_time' => strtotime(date('d-m-Y')),
                );
                $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
            


            $data = array(
                'cost' => $sale_data->cost-$kvat,
                'cost_vat' => $sale_data->cost_vat-$vat,
                'estimate_cost' => $sale_data->estimate_cost-$estimate,

            );
            
            $sale->updateSale($data,array('import_tire_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

            

        }
    }

    public function getvendor2(){
        if(isset($_POST['sale_report'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $vendors = $sale_vendor->getAllVendor(array('where'=>'check_cost = 3 AND trading='.$_POST['sale_report']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    foreach($banks as $bank){ 
                        $ba .= '<option  value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }


                $str .= '<tr class="'.$_POST['sale_report'].'">';
                    $str .= '<td><input type="checkbox"  name="chk2"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="cost_type2" name="cost_type2[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['sale_report'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor2" name="vendor2[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date2" type="date"   name="vendor_expect_date2[]" required="required" value=""></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source2"  name="vendor_source2[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'].'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat2"  name="cost_vat2[]" value="0"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost2"  name="invoice_cost2[]" value="0"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment2" name="cost_comment2[]"  ></textarea></td></tr>';                                         
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost2"  name="cost2[]" value="0"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost2"  name="pay_cost2[]" value="0" ></td></tr>';
                    $str .= '<tr class="'.$_POST['sale_report'] .'"><td></td>';
                    $str .= '<td></td><td><input type="checkbox" value="1" name="check_deposit2[]" class="check_deposit2"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost2"  name="document_cost2[]" value="0"  ></td></tr></table></td></tr>';
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $ba = "";

                    

                    foreach($banks as $bank){ 
                        $slnh = ($bank->bank_id == $v->source)?'selected="selected"':null;
                        $ba .= '<option '.$slnh .' value="'. $bank->bank_id .'">'.$bank->bank_name .'</option>';
                     }

                     $truck = ($v->type==1)?'selected="selected"':null;
                     $bar = ($v->type==2)?'selected="selected"':null;
                     $fee = ($v->type==3)?'selected="selected"':null;
                     $thu = ($v->type==4)?'selected="selected"':null;
                     $hh = ($v->type==5)?'selected="selected"':null;
                     $tt = ($v->type==6)?'selected="selected"':null;
                     $khac = ($v->type==7)?'selected="selected"':null;

                     $checked = $v->check_deposit==1?'checked':null;

                    $str .= '<tr class="'.$v->trading.'">';
                    $str .= '<td><input type="checkbox" name="chk2" tabindex="'.$v->type.'" data="'.$v->trading .'" class="'.$v->vendor.'" title="'.($v->cost+$v->cost_vat).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="cost_type2" name="cost_type2[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$v->trading .'">';
                    $str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor2" name="vendor2[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Dự chi</td>';
                    $str .= '<td><input tabindex="5" class="vendor_expect_date2" type="date"   name="vendor_expect_date2[]" required="required" value="'.date('Y-m-d',$v->expect_date) .'"></td>';
                    $str .= '<td> Tài khoản </td>';
                    $str .= '<td><select tabindex="9" style="width:120px" class="vendor_source2"  name="vendor_source2[]"  required="required">'.$ba.'</select></td></tr>';
                    $str .= '<tr class="'.$v->trading.'"><td></td><td>Số tiền (VAT)</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers cost_vat2"  name="cost_vat2[]" value="'.$this->lib->formatMoney($v->cost_vat) .'"  ></td>';
                    $str .= '<td>Phí mua HĐ</td>';
                    $str .= '<td><input tabindex="6" type="text" style="width:120px" class="numbers invoice_cost2"  name="invoice_cost2[]" value="'.$this->lib->formatMoney($v->invoice_cost) .'"  ></td>';                                    
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="cost_comment2" name="cost_comment2[]"  >'.$v->comment .'</textarea></td></tr>';                                         
                    $str .= '<tr class="'.$v->trading .'"><td></td><td> Số tiền (0 VAT)</td>';
                    $str .= '<td><input tabindex="4" type="text" style="width:120px" class="numbers cost2"  name="cost2[]" value="'.$this->lib->formatMoney($v->cost) .'"  ></td>';
                    $str .= '<td>Phí chuyển tiền</td>';
                    $str .= '<td><input tabindex="7" type="text" style="width:120px" class="numbers pay_cost2"  name="pay_cost2[]" value="'.$this->lib->formatMoney($v->pay_cost) .'" ></td></tr>';
                    $str .= '<tr class="'.$v->trading .'"><td></td>';
                    $str .= '<td></td><td><input disabled type="checkbox" '.$checked.' value="1" name="check_deposit2[]" class="check_deposit2"> Tiền đặt cọc</td>';
                    $str .= '<td>Phí gửi chứng từ</td>';
                    $str .= '<td><input tabindex="8" type="text" style="width:120px" class="numbers document_cost2"  name="document_cost2[]" value="'.$this->lib->formatMoney($v->document_cost) .'"  ></td></tr></table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletevendor2(){
        if(isset($_POST['data'])){
            $sale_vendor = $this->model->get('importtirecostModel');
            $sale = $this->model->get('importtireModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $receivable = $this->model->get('receivableModel');
            $costs = $this->model->get('costsModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $sales_model = $this->model->get('salesModel');

            $sale_data = $sale->getSale($_POST['data']);

            
                $data = array(
                    'where' => 'check_cost = 3 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type'],
                );

                $vendor_datas = $sale_vendor->getAllVendor($data);

                $sale_vendor->queryVendor('DELETE FROM import_tire_cost WHERE check_cost = 3 AND trading='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND type='.$_POST['type']);
                //$owe->queryOwe('DELETE FROM owe WHERE (sale_report='.$_POST['data'].' OR trading='.$_POST['data'].') AND vendor='.$_POST['vendor']);
                
                $re = $receivable->getAllCosts(array('where'=>'check_cost = 3 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']));
                foreach ($re as $r) {
                    $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                    $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                }
                $pa = $payable->getAllCosts(array('where'=>'check_cost = 3 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']));
                foreach ($pa as $p) {
                    $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                    $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                }

                $payable->queryCosts('DELETE FROM payable WHERE check_cost = 3 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND cost_type='.$_POST['type']);
                $receivable->queryCosts('DELETE FROM receivable WHERE check_cost = 3 AND import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor']);



            
            
            $kvat = 0;
            $vat = 0;
            $estimate = 0;

            $old_cost = 0;

            foreach ($vendor_datas as $vendor_data) {
                //$kvat += $vendor_data->cost+$vendor_data->invoice_cost+$vendor_data->pay_cost;
                //$vat += $vendor_data->cost_vat+$vendor_data->document_cost;
                if($vendor_data->check_deposit != 1 && $vendor_data->type != 4){
                    $kvat += $vendor_data->cost;
                    $vat += $vendor_data->cost_vat;
                }
                
                $estimate += $vendor_data->invoice_cost+$vendor_data->pay_cost+$vendor_data->document_cost;

                $old_cost += $vendor_data->cost+$vendor_data->cost_vat;

               

            }

            $owe->queryOwe('DELETE FROM owe WHERE import_tire='.$_POST['data'].' AND vendor='.$_POST['vendor'].' AND money='.$old_cost.' LIMIT 1');

            
                $owe_data = array(
                    'owe_date' => $sale_data->import_tire_date,
                    'vendor' => $_POST['vendor'],
                    'money' => 0-$old_cost,
                    'week' => (int)date('W',$sale_data->import_tire_date),
                    'year' => (int)date('Y',$sale_data->import_tire_date),
                    'import_tire' => $_POST['data'],
                );
                if($owe_data['week'] == 53){
                    $owe_data['week'] = 1;
                    $owe_data['year'] = $owe_data['year']+1;
                }
                if (((int)date('W',$sale_data->import_tire_date) == 1) && ((int)date('m',$sale_data->import_tire_date) == 12) ) {
                    $owe_data['year'] = (int)date('Y',$sale_data->import_tire_date)+1;
                }

                //$owe->queryOwe('DELETE FROM owe WHERE vendor='.$sale_vendor_data['vendor'].' AND trading='.$_POST['yes']);
                    
                //    $owe->createOwe($owe_data);

                $salesdata = $sales_model->getSalesByWhere(array('import_tire'=>$_POST['data']));
                $data_sales = array(
                    'cost' => $salesdata->cost-$kvat-$vat-$estimate,
                    'profit' => $sale_data->profit+$kvat+$vat+$estimate,
                    'import_tire' => $_POST['data'],
                    'sales_update_user' => $_SESSION['userid_logined'],
                    'sales_update_time' => strtotime(date('d-m-Y')),
                );
                $sales_model->updateSales($data_sales,array('sales_id'=>$salesdata->sales_id));
            


            $data = array(
                'cost' => $sale_data->cost-$kvat,
                'cost_vat' => $sale_data->cost_vat-$vat,
                'estimate_cost' => $sale_data->estimate_cost-$estimate,

            );
            
            $sale->updateSale($data,array('import_tire_id' => trim($_POST['data'])));
            echo 'Đã xóa thành công';

            

        }
    }

}
?>