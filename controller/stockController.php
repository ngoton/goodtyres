<?php
Class stockController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tồn kho lốp xe';

        $tire_brand_group_model = $this->model->get('tirebrandgroupModel');
        $tire_going_model = $this->model->get('tiregoingModel');
        $import_tire_list_model = $this->model->get('importtirelistModel');

        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_desired_model = $this->model->get('tiredesiredModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $tire_order_model = $this->model->get('tireordersaleModel');
        $tire_order_sales = $tire_order_model->getAllTire(array('where'=>'(tire_order_sale_status IS NULL OR tire_order_sale_status = 0)'),array('table'=>'customer,user','where'=>'tire_order_sale_customer=customer_id AND tire_order_sale=user_id'));
        $this->view->data['tire_order_sales'] = $tire_order_sales;


        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);
        $this->view->data['tire_buys'] = $tire_buys;

        $tire_product_model = $this->model->get('tireproductModel');
        $link_picture = array();

        $sell = array();
        foreach ($tire_buys as $tire_buy) {
            $tire_products = $tire_product_model->queryTire('SELECT tire_product_thumb FROM tire_product, tire_product_pattern WHERE tire_pattern = tire_product_pattern_id AND tire_product_pattern_name LIKE "%'.$tire_buy->tire_pattern_name.'%"');

            foreach ($tire_products as $tire_product) {
                $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_product->tire_product_thumb;
            }

            $data_sale = array(
                'where'=>'tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                if ($tire_sale->customer != 119) {
                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;
                }
                
            }
        }

        $this->view->data['link_picture'] = $link_picture;
        $this->view->data['sell'] = $sell;

        $tonkho = array();
        $ban = array();
        $dathang = array();
        $dangve = array();
        $dangorder = array();
        $kho_brand = array();
        $ban_brand = array();
        $dathang_brand = array();
        $nhaphang_brand = array();
        $dangve_brand = array();
        $dangorder_brand = array();

        $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
        foreach ($order_tires as $order) {
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id'));
            foreach ($order_tire_lists as $list) {
                $pt_type = explode(',', $list->tire_pattern_type);
                for ($l=0; $l < count($pt_type); $l++) { 
                    $ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number] = isset($ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number])?$ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number]+$list->tire_number:$list->tire_number;
                }
            }
        }
        $tire_goings = $tire_going_model->getAllTire(null,array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND (status IS NULL OR status=0)')); //tire_brand thay tire_brand_group
        $tire_orders = $import_tire_list_model->getAllImport(null,array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND import_tire_list_id NOT IN (SELECT import_tire_list FROM tire_going WHERE import_tire_list IS NOT NULL)')); //tire_brand thay tire_brand_group
        $tire_desireds = $tire_desired_model->getAllTire(null,array('table'=>'tire_size','where'=>'tire_size=tire_size_id AND (tire_desired_status IS NULL OR tire_desired_status=0)')); //tire_brand thay tire_brand_group
        $tire_buys = $tire_buy_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_buy_pattern=tire_pattern_id and tire_buy_size=tire_size_id AND tire_buy_brand=tire_brand_id'));
        $tire_sales = $tire_sale_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_pattern=tire_pattern_id and tire_size=tire_size_id AND tire_brand=tire_brand_id'));
        
        $tire_brand_groups = $tire_brand_group_model->getAllTire();

        $last_code = 0;
        foreach ($tire_goings as $going) {
            $pt_type = explode(',', $going->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number] = isset($dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number])?$dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number]+$going->tire_number:$going->tire_number;
            }
            
            $last_code = $last_code==0?$going->code:$last_code;
        }

        foreach ($tire_orders as $order) {
            $pt_type = explode(',', $order->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number] = isset($dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number])?$dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number]+$order->tire_number:$order->tire_number;
            }
            
        }

        foreach ($tire_desireds as $desired) {
            $dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number] = isset($dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number])?$dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number]+$desired->tire_number:$desired->tire_number;
        }

        foreach ($tire_buys as $buy) {
            $pt_type = explode(',', $buy->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number] = isset($tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number])?$tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            }
        }

        foreach ($tire_sales as $sale) {
            $pt_type = explode(',', $sale->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number] = isset($tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number])?$tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]-$sale->volume:$sale->volume;
                $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number] = $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]!=0?$tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]:null;
            }
        }

        /*foreach ($tire_brands as $brand) {
            if (isset($tonkho[$brand->tire_brand_id])) {
                $kho_brand[$brand->tire_brand_id]['id'] = $brand->tire_brand_id;
                $kho_brand[$brand->tire_brand_id]['name'] = $brand->tire_brand_name;

                $nhaphang_brand[$brand->tire_brand_id]['name'] = $brand->tire_brand_name;
                $nhaphang_brand[$brand->tire_brand_id]['id'] = $brand->tire_brand_id;
            }
            if (isset($dathang[$brand->tire_brand_id])) {
                $dathang_brand[$brand->tire_brand_id]['id'] = $brand->tire_brand_id;
                $dathang_brand[$brand->tire_brand_id]['name'] = $brand->tire_brand_name;

                $nhaphang_brand[$brand->tire_brand_id]['name'] = $brand->tire_brand_name;
                $nhaphang_brand[$brand->tire_brand_id]['id'] = $brand->tire_brand_id;
            }
        }*/

        foreach ($tire_brand_groups as $brand) {
            if (isset($tonkho[$brand->tire_brand_group_id])) {
                $kho_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $kho_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $ban_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $ban_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($ban[$brand->tire_brand_group_id])) {
                $ban_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $ban_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dathang[$brand->tire_brand_group_id])) {
                $dathang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dathang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dangve[$brand->tire_brand_group_id])) {
                $dangve_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dangve_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dangorder[$brand->tire_brand_group_id])) {
                $dangorder_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dangorder_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
        }

        $this->view->data['last_code'] = $last_code;

        $this->view->data['tonkhos'] = $tonkho;
        $this->view->data['orders'] = $ban;
        $this->view->data['dathangs'] = $dathang;
        $this->view->data['dangves'] = $dangve;
        $this->view->data['dangorders'] = $dangorder;
        $this->view->data['brand_tonkhos'] = $kho_brand;
        $this->view->data['brand_orders'] = $ban_brand;
        $this->view->data['brand_dathangs'] = $dathang_brand;
        $this->view->data['brand_nhaphangs'] = $nhaphang_brand;
        $this->view->data['brand_dangves'] = $dangve_brand;
        $this->view->data['brand_dangorders'] = $dangorder_brand;
        
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('stock/index');
    }

    public function addorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_order_sale_model = $this->model->get('tireordersaleModel');
            $customer_model = $this->model->get('customerModel');
            if ((trim($_POST['tire_order_sale_customer']) == "" || trim($_POST['tire_order_sale_customer']) == null) && trim($_POST['tire_order_sale_customer_name']) != "") {
                $customer_model->createCustomer(array('customer_name'=>trim($_POST['tire_order_sale_customer_name']),'mst'=>trim($_POST['mst'])));
                $id_customer = $customer_model->getLastCustomer()->customer_id;
            }
            else{
                $id_customer = trim($_POST['tire_order_sale_customer']);
            }

            $data = array(
                'tire_order_sale_date'=>strtotime($_POST['tire_order_sale_date']),
                'tire_order_sale_customer'=> $id_customer,
                'tire_order_sale_number'=>trim($_POST['tire_order_sale_number']),
                'tire_order_sale_price'=>trim($_POST['tire_order_sale_price']),
                'tire_order_sale'=>$_SESSION['userid_logined'],
            );

            $tire_order_sale_model->createTire($data);

            $id_order = $tire_order_sale_model->getLastTire()->tire_order_sale_id;

            $tire_order_sale_type_model = $this->model->get('tireordersaletypeModel');
            $tire_type = $_POST['tire_type'];
            foreach ($tire_type as $v) {
                $data = array(
                    'tire_brand'=>$v['tire_brand'],
                    'tire_size'=>$v['tire_size'],
                    'tire_pattern'=>$v['tire_pattern'],
                    'tire_number'=>$v['tire_number'],
                    'tire_price'=>$v['tire_price'],
                    'tire_vat'=>$v['tire_vat'],
                    'tire_order_sale'=>$id_order,
                );

                if ($tire_order_sale_type_model->getTireByWhere(array('tire_brand'=>$v['tire_brand'],'tire_size'=>$v['tire_size'],'tire_pattern'=>$v['tire_pattern'],'tire_order_sale'=>$id_order))) {
                    $order_type = $tire_order_sale_type_model->getTireByWhere(array('tire_brand'=>$v['tire_brand'],'tire_size'=>$v['tire_size'],'tire_pattern'=>$v['tire_pattern'],'tire_order_sale'=>$id_order));

                    $tire_order_sale_type_model->updateTire($data,array('tire_order_sale_type_id'=>$order_type->tire_order_sale_type_id));
                }
                else{
                    $tire_order_sale_type_model->createTire($data);
                }
            }
        }
    }

    public function order(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_desired_model = $this->model->get('tiredesiredModel');

            $brand = trim($_POST['brand']);
            $pattern = trim($_POST['pattern']);
            $size = trim($_POST['size']);
            $number = trim($_POST['number']);

            $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>$size));

            $data = array(
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$tire_size->tire_size_id,
                'tire_number'=>$number,
                'tire_desired_date'=>strtotime(date('d-m-Y')),
                'sale'=>$_SESSION['userid_logined'],
            );

            $tire_desired_model->createTire($data);

        }
    }
    public function deleteorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_order_model = $this->model->get('tireordersaleModel');
            $tire_order_type_model = $this->model->get('tireordersaletypeModel');
            if(isset($_POST['data'])){
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
                        if ($tire_order_model->getTireByWhere(array('tire_order_sale_id'=>$_POST['data'],'tire_order_sale'=>$_SESSION['userid_logined']))) {
                            $tire_order_model->deleteTire($_POST['data']);

                            $tire_order_type_model->query('DELETE FROM tire_order_sale_type WHERE tire_order_sale = '.$_POST['data']);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_order_sale|"."\n"."\r\n";
                            
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
                        $tire_order_model->deleteTire($_POST['data']);
                        $tire_order_type_model->query('DELETE FROM tire_order_sale_type WHERE tire_order_sale = '.$_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_order_sale|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
            }
            
        }
    }
    public function importstock(){
        $code = $this->registry->router->param_id;
        if ($code != "") {
            $tirebuy = $this->model->get('tirebuyModel');
            $tiregoing = $this->model->get('tiregoingModel');
            $tire_goings = $tiregoing->getAllTire(array('where'=>'code = '.$code));

            foreach ($tire_goings as $tire) {
                $data = array(  
                'code' => $tire->code,
                'tire_buy_volume' => $tire->tire_number,
                'tire_buy_brand' => $tire->tire_brand,
                'tire_buy_size' => $tire->tire_size,
                'tire_buy_pattern' => $tire->tire_pattern,
                'rate' => 22400,
                'rate_shipper' => 22400,
                'date_solow' => $tire->tire_going_date,
                'date_shipper' => $tire->tire_going_date,
                'tire_buy_date' => $tire->tire_going_date,
                'date_manufacture' => $tire->date_manufacture,
                );

                $tirebuy->createTire($data);

                $tiregoing->updateTire(array('status'=>1),array('tire_going_id'=>$tire->tire_going_id));
            }

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."import_stock"."|".$code."|tire_going|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
            
        }
        return $this->view->redirect('stock');
    }
    public function editdate(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tiregoing = $this->model->get('tiregoingModel');

            $id = $_POST['data'];
            $date_manufacture = strtotime('01-'.str_replace('/', '-', $_POST['date_manufacture']));

            $tiregoing->updateTire(array('date_manufacture'=>$date_manufacture),array('tire_going_id'=>$id));

            echo "Cập nhật thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."date_manufacture"."|".$id."|tire_going|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
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
            
            return $this->view->redirect('stock');
        }
        $this->view->show('stock/goingimport');
        
    }
    public function editorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_desired_model = $this->model->get('tiredesiredModel');

            $brand = trim($_POST['tire_brand']);
            $pattern = trim($_POST['tire_pattern']);
            $size = trim($_POST['tire_size']);
            $number = trim($_POST['tire_number']);

            $data = array(
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
            );

            $tire_desired_model->updateTire($data,array('tire_desired_id'=>$_POST['yes']));

        }
    }

    public function listorder(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>str_replace('~', '/', $this->registry->router->order_by)));
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $join = array('table'=>'tire_pattern,tire_brand,tire_size,order_tire,user,customer','where'=>'order_tire.customer=customer_id AND order_tire.sale = user_id AND tire_pattern = tire_pattern_id AND tire_brand = tire_brand_id AND tire_size = tire_size_id AND order_tire = order_tire_id AND (order_tire_status IS NULL OR order_tire_status = 0)');

        $data = array(
            'where' => 'tire_brand_group='.$this->registry->router->param_id.' AND tire_size='.$tire_size->tire_size_id.' AND (tire_pattern_type LIKE '.$this->registry->router->order.' OR tire_pattern_type LIKE "%,'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "%,'.$this->registry->router->order.'")',
        );

        $orders = $tire_order_list_model->getAllTire($data,$join);
        $this->view->data['tire_orders'] = $orders;

        $this->view->show('stock/order');
    }
    public function stock(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>str_replace('~', '/', $this->registry->router->order_by)));
        
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');

        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_brand_group=".$this->registry->router->param_id." AND tire_buy_size = ".$tire_size->tire_size_id." AND (tire_pattern_type LIKE ".$this->registry->router->order." OR tire_pattern_type LIKE '%,".$this->registry->router->order.",%' OR tire_pattern_type LIKE '".$this->registry->router->order.",%' OR tire_pattern_type LIKE '%,".$this->registry->router->order."') AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);
        $this->view->data['tire_buys'] = $tire_buys;

        $link_picture = array();

        $sell = array();
        foreach ($tire_buys as $tire_buy) {
            $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_buy->tire_pattern_name.'.jpg';
            

            $data_sale = array(
                'where'=>'tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                if ($tire_sale->customer != 119) {
                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;
                }
                
            }
        }

        $this->view->data['tire_buys'] = $tire_buys;
        $this->view->data['sell'] = $sell;
        $this->view->data['link_picture'] = $link_picture;

        $this->view->show('stock/stock');
    }

    public function going(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>str_replace('~', '/', $this->registry->router->order_by)));
        $tire_going_model = $this->model->get('tiregoingModel');
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand = tire_brand_id AND tire_size = tire_size_id AND tire_pattern = tire_pattern_id AND (status IS NULL OR status=0)');

        $data = array(
            'where' => 'tire_brand_group='.$this->registry->router->param_id.' AND tire_size='.$tire_size->tire_size_id.' AND (tire_pattern_type LIKE "'.$this->registry->router->order.'" OR tire_pattern_type LIKE "%,'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "%,'.$this->registry->router->order.'")',
        );

        $goings = $tire_going_model->getAllTire($data,$join);
        $this->view->data['tire_goings'] = $goings;

        $this->view->data['tire_brand'] = $this->registry->router->param_id==1?"DR":($this->registry->router->param_id==2?"ST":($this->registry->router->param_id==3?"AN":($this->registry->router->param_id==4?"GS":($this->registry->router->param_id==5?"LL":"ZC"))));
        $this->view->data['tire_size'] = $this->registry->router->order_by;
        $this->view->data['tire_pattern'] = $this->registry->router->order==1?"DC01":($this->registry->router->order==2?"DC02":($this->registry->router->order==3?"DC03":($this->registry->router->order==4?"NC01":($this->registry->router->order==5?"BC01":($this->registry->router->order==6?"BC02":($this->registry->router->order==7?"DK01":($this->registry->router->order==8?"DK02":($this->registry->router->order==9?"NK01":"NK02"))))))));

        $this->view->show('stock/going');
    }
    public function goingorder(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>str_replace('~', '/', $this->registry->router->order_by)));
        $tire_going_model = $this->model->get('importtirelistModel');
        $join = array('table'=>'import_tire_order,tire_brand,tire_size,tire_pattern','where'=>'import_tire_order=import_tire_order_id AND tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND import_tire_list_id NOT IN (SELECT import_tire_list FROM tire_going WHERE import_tire_list IS NOT NULL)');

        $data = array(
            'where' => 'tire_brand_group='.$this->registry->router->param_id.' AND tire_size='.$tire_size->tire_size_id.' AND (tire_pattern_type LIKE "'.$this->registry->router->order.'" OR tire_pattern_type LIKE "%,'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "'.$this->registry->router->order.',%" OR tire_pattern_type LIKE "%,'.$this->registry->router->order.'")',
        );

        $goings = $tire_going_model->getAllImport($data,$join);
        $this->view->data['tire_goings'] = $goings;

        $this->view->data['tire_brand'] = $this->registry->router->param_id==1?"DR":($this->registry->router->param_id==2?"ST":($this->registry->router->param_id==3?"AN":($this->registry->router->param_id==4?"GS":($this->registry->router->param_id==5?"LL":"ZC"))));
        $this->view->data['tire_size'] = $this->registry->router->order_by;
        $this->view->data['tire_pattern'] = $this->registry->router->order==1?"DC01":($this->registry->router->order==2?"DC02":($this->registry->router->order==3?"DC03":($this->registry->router->order==4?"NC01":($this->registry->router->order==5?"BC01":($this->registry->router->order==6?"BC02":($this->registry->router->order==7?"DK01":($this->registry->router->order==8?"DK02":($this->registry->router->order==9?"NK01":"NK02"))))))));

        $this->view->show('stock/goingorder');
    }

    public function desired(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>str_replace('~', '/', $this->registry->router->order_by)));
        $tire_desired_model = $this->model->get('tiredesiredModel');
        $join = array('table'=>'user,tire_brand_group,tire_size','where'=>'sale = user_id AND tire_brand = tire_brand_group_id AND tire_size = tire_size_id');

        $data = array(
            'where' => '(tire_desired_status IS NULL OR tire_desired_status=0) AND tire_brand='.$this->registry->router->param_id.' AND tire_size='.$tire_size->tire_size_id.' AND tire_pattern='.$this->registry->router->order,
        );

        $desireds = $tire_desired_model->getAllTire($data,$join);
        $this->view->data['tire_desireds'] = $desireds;

        $this->view->show('stock/desired');
    }
    public function deleteorderdesired(){
        if (isset($_POST['data'])) {
            $tire_desired_model = $this->model->get('tiredesiredModel');
            $tire_desired_model->updateTire(array('tire_desired_status'=>1),array('tire_brand'=>$_POST['data']));
        }

    }
    public function deleteorderdesiredall(){
        if (isset($_POST['data'])) {
            $tire_desired_model = $this->model->get('tiredesiredModel');
            $tire_desired_model->queryTire('UPDATE tire_desired SET tire_desired_status = 1');
        }

    }
    public function deletedesired(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_desired_model = $this->model->get('tiredesiredModel');
            if(isset($_POST['data'])){
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3) {
                        if ($tire_desired_model->getTireByWhere(array('tire_desired_id'=>$_POST['data'],'sale'=>$_SESSION['userid_logined']))) {
                            $tire_desired_model->deleteTire($_POST['data']);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_desired|"."\n"."\r\n";
                            
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
                        $tire_desired_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_desired|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
            }
            
        }
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $customer_model->getAllCustomer();
            }
            else{
                $data = array(
                'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $customer_model->getAllCustomer($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $customer_name = $rs->customer_name;
                if ($_POST['keyword'] != "*") {
                    $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                }

                if ($rs->customer_expect_date != null) {
                    $expect_date = date('d-m-Y',strtotime($rs->customer_expect_date.'-'.date('m-Y',strtotime(date('d-m-Y')))));
                }
                else if ($rs->customer_after_date != null) {
                    $expect_date = date('d-m-Y',strtotime('+'.$rs->customer_after_date.' day', strtotime(date('d-m-Y'))));
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.$rs->customer_phone.'\',\''.$rs->customer_address.'\',\''.$rs->customer_email.'\',\''.$expect_date.'\',\''.$rs->mst.'\')">'.$customer_name.'</li>';
            }
        }
    }

    public function report() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tồn kho lốp xe';

        $ngay = date('d-m-Y');
        if (isset($_POST['date'])) {
            $ngay = $_POST['date'];
        }
        $this->view->data['ngay'] = $ngay;

        $ngayketthuc = date('d-m-Y', strtotime($ngay. ' + 1 days'));

        $tire_order_model = $this->model->get('tireorderModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_import_model = $this->model->get('tireimportModel');

        $tire_imports = $tire_import_model->getAllTire();
        $tire_prices = array();

        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_buy_date < ".strtotime($ngayketthuc)." AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);
        $this->view->data['tire_buys'] = $tire_buys;

        $tire_product_model = $this->model->get('tireproductModel');
        $link_picture = array();

        $sell = array();
        foreach ($tire_buys as $tire_buy) {
            $tire_products = $tire_product_model->queryTire('SELECT tire_product_thumb FROM tire_product, tire_product_pattern WHERE tire_pattern = tire_product_pattern_id AND tire_product_pattern_name LIKE "%'.$tire_buy->tire_pattern_name.'%"');

            foreach ($tire_products as $tire_product) {
                $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_product->tire_product_thumb;
            }

            $data_sale = array(
                'where'=>'tire_sale_date < '.strtotime($ngayketthuc).' AND tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                if ($tire_sale->customer != 119) {
                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;
                }
                
            }


            $data = array(
                'where' => 'start_date < '.strtotime($ngayketthuc).' AND tire_brand = '.$tire_buy->tire_buy_brand.' AND tire_size = '.$tire_buy->tire_buy_size.' AND tire_pattern = '.$tire_buy->tire_buy_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC, tire_import_id DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $tire_prices[$tire_import->tire_brand][$tire_import->tire_size][$tire_import->tire_pattern] = $tire_import->tire_price;
            }
        }

        $this->view->data['link_picture'] = $link_picture;

        $this->view->data['tire_buys'] = $tire_buys;
        $this->view->data['sell'] = $sell;
        $this->view->data['tire_prices'] = $tire_prices;
        $this->view->data['page'] = NULL;
        $this->view->data['order_by'] = NULL;
        $this->view->data['order'] = NULL;
        $this->view->data['keyword'] = NULL;
        $this->view->data['pagination_stages'] = NULL;
        $this->view->data['tongsotrang'] = NULL;
        $this->view->data['limit'] = NULL;
        $this->view->data['sonews'] = NULL;
        
        $buy = $tire_buy_model->queryTire('SELECT max(tire_buy_date) AS max FROM tire_buy');  
        $sale = $tire_sale_model->queryTire('SELECT max(tire_sale_date) AS max FROM tire_sale'); 
        $order = $tire_order_model->queryTire('SELECT max(tire_receive_date) AS max FROM tire_order');

        $max = 0;

        foreach ($buy as $b) {
             $max = $b->max;
        }

        foreach ($sale as $s) {
            if($s->max > $max)
                $max = $s->max;
        }

        foreach ($order as $o) {
            if($o->max > $max)
                $max = $o->max;
        }

        $today = strtotime($ngay);

        $max = $max > $today ? $max : $today;

        $this->view->data['max'] = $max;


        $total = 0;

        $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_date < '.$today);  
        $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE customer != 119 AND tire_sale_date < '.$today); 
        $orders = $tire_order_model->queryTire('SELECT sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date < '.$today);

        foreach ($buys as $buy) {
            $total += $buy->total_buy;
        }

        foreach ($sales as $sale) {
            $total -= $sale->total_sale;
        }

        foreach ($orders as $order) {
            $total -= $order->total_order;
        }

        $this->view->data['total'] = $total;

        $buys = $tire_buy_model->queryTire('SELECT * FROM tire_buy WHERE tire_buy_date >= '.$today);  
        $sales = $tire_sale_model->queryTire('SELECT * FROM tire_sale WHERE customer != 119 AND tire_sale_date >= '.$today); 
        $orders = $tire_order_model->queryTire('SELECT * FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date >= '.$today);

        $tire = array();

        foreach ($buys as $buy) {
            $tire[date('d-m-Y',$buy->tire_buy_date)]['buy'] = isset($tire[date('d-m-Y',$buy->tire_buy_date)]['buy'])?$tire[date('d-m-Y',$buy->tire_buy_date)]['buy']+$buy->tire_buy_volume:$buy->tire_buy_volume;
        }

        foreach ($sales as $sale) {
            $tire[date('d-m-Y',$sale->tire_sale_date)]['sale'] = isset($tire[date('d-m-Y',$sale->tire_sale_date)]['sale'])?$tire[date('d-m-Y',$sale->tire_sale_date)]['sale']+$sale->volume:$sale->volume;
        }

        foreach ($orders as $order) {
            $tire[date('d-m-Y',$order->tire_receive_date)]['order'] = isset($tire[date('d-m-Y',$order->tire_receive_date)]['order'])?$tire[date('d-m-Y',$order->tire_receive_date)]['order']+$order->tire_number:$order->tire_number;
        }

        $this->view->data['tire'] = $tire;
        
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('stock/report');
    }

    
}
?>