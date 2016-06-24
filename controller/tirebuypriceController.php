<?php
Class tirebuypriceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_producer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_product_size_number ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        
        $tire_brand_model = $this->model->get('tireproducerModel');
        $tire_size_model = $this->model->get('tireproductsizeModel');

        $tire_brands = $tire_brand_model->getAllTire();
        $tire_sizes = $tire_size_model->getAllTire();

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;

        $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern, tire_supplier','where'=>'tire_producer.tire_producer_id = tire_buy_price.tire_brand AND tire_product_size.tire_product_size_id = tire_buy_price.tire_size AND tire_product_pattern.tire_product_pattern_id = tire_buy_price.tire_pattern AND tire_supplier=tire_supplier_id');

        $tire_buy_price_model = $this->model->get('tirebuypriceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($tire_buy_price_model->getAllTire($data,$join));
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
            'where' => '1=1',
            );
        
      
        if ($keyword != '') {
            $search = '( tire_producer_name LIKE "%'.$keyword.'%" 
                OR tire_product_size_number LIKE "%'.$keyword.'%" 
                OR tire_supplier_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_buy_prices'] = $tire_buy_price_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_buy_price_model->getLastTire()->tire_buy_price_id)?$tire_buy_price_model->getLastTire()->tire_buy_price_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tirebuyprice/index');
    }

   public function getpattern(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tireproductpatternModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_product_pattern_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_product_pattern_name;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_product_pattern_name);
                }
                
                // add new option
                echo '<li onclick="set_item_tire(\''.$rs->tire_product_pattern_name.'\',\''.$rs->tire_product_pattern_id.'\')">'.$tire_name.'</li>';
            }
        }
    }

    public function getsupplier(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tiresupplierModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_supplier_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_supplier_name;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_supplier_name);
                }
                
                // add new option
                echo '<li onclick="set_item_supplier(\''.$rs->tire_supplier_name.'\',\''.$rs->tire_supplier_id.'\')">'.$tire_name.'</li>';
            }
        }
    }
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_buy_price_model = $this->model->get('tirebuypriceModel');
            $data = array(
                        
                        'tire_brand' => trim($_POST['tire_brand']),
                        'tire_size' => trim($_POST['tire_size']),
                        'tire_buy_price' => trim(str_replace(',','',$_POST['tire_buy_price'])),
                        );
            if (trim($_POST['tire_pattern']) == "" && trim($_POST['tire_pattern_name']) != "") {
                $tire_pattern_model = $this->model->get('tireproductpatternModel');
                if ($tire_pattern_model->getTireByWhere(array('tire_product_pattern_name' => trim($_POST['tire_pattern_name'])))) {
                    $data['tire_pattern'] = $tire_pattern_model->getTireByWhere(array('tire_product_pattern_name' => trim($_POST['tire_pattern_name'])))->tire_product_pattern_id;
                }
                else{
                    $data_pattern = array(
                        'tire_product_pattern_name' => trim($_POST['tire_pattern_name']),
                    );
                    $tire_pattern_model->createTire($data_pattern);
                    $data['tire_pattern'] = $tire_pattern_model->getLastTire()->tire_product_pattern_id;
                }
                
            }
            elseif (trim($_POST['tire_pattern']) != "") {
                $data['tire_pattern'] = trim($_POST['tire_pattern']);
            }

            if (trim($_POST['tire_supplier']) == "" && trim($_POST['tire_supplier_name']) != "") {
                $tire_supplier_model = $this->model->get('tiresupplierModel');
                if ($tire_supplier_model->getTireByWhere(array('tire_supplier_name' => trim($_POST['tire_supplier_name'])))) {
                    $data['tire_supplier'] = $tire_supplier_model->getTireByWhere(array('tire_supplier_name' => trim($_POST['tire_supplier_name'])))->tire_supplier_id;
                }
                else{
                    $data_supplier = array(
                        'tire_supplier_name' => trim($_POST['tire_supplier_name']),
                    );
                    $tire_supplier_model->createTire($data_supplier);
                    $data['tire_supplier'] = $tire_supplier_model->getLastTire()->tire_supplier_id;
                }
                
            }
            elseif (trim($_POST['tire_supplier']) != "") {
                $data['tire_supplier'] = trim($_POST['tire_supplier']);
            }

            if ($_POST['yes'] != "") {
                
                    $tire_buy_price_model->updateTire($data,array('tire_buy_price_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_buy_price|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    if ($tire_buy_price_model->getTireByWhere(array('tire_brand'=>$data['tire_brand'],'tire_size'=>$data['tire_size'],'tire_pattern'=>$data['tire_pattern']))) {
                        $price = $tire_buy_price_model->getTireByWhere(array('tire_brand'=>$data['tire_brand'],'tire_size'=>$data['tire_size'],'tire_pattern'=>$data['tire_pattern']));
                        if ($data['tire_buy_price'] < $price->tire_buy_price) {
                            $tire_buy_price_model->updateTire($data,array('tire_buy_price_id'=>$price->tire_buy_price_id));
                        }
                    }
                    else
                    {
                        $tire_buy_price_model->createTire($data);
                    }
                    

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_buy_price_model->getLastTire()->tire_buy_price_id."|tire_buy_price|".implode("-",$data)."\n"."\r\n";
                        
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
            $tire_buy_price_model = $this->model->get('tirebuypriceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_buy_price_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_buy_price|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_buy_price_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_buy_price|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $tirebrand = $this->model->get('tireproducerModel');
            $tirepattern = $this->model->get('tireproductpatternModel');
            $tiresize = $this->model->get('tireproductsizeModel');
            $tiresupplier = $this->model->get('tiresupplierModel');
            $tirebuyprice = $this->model->get('tirebuypriceModel');

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

            $i = 0;
            while ($objPHPExcel->setActiveSheetIndex($i)){

                $objWorksheet = $objPHPExcel->getActiveSheet();

                

                $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
                $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

                //var_dump($objWorksheet->getMergeCells());die();

                /*$cell_ngay = $objWorksheet->getCellByColumnAndRow(0, 5);
                $ngay = $cell_ngay->getCalculatedValue();
                $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);                                      
                $ngaythang = $ngaythang-3600;

                $ngaytruoc = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$ngaythang).' -1 day')));
    */
                $val1 = array();
                for ($col1 = 0; $col1 <= $highestColumnIndex; ++ $col1) {
                    $cell1 = $objWorksheet->getCellByColumnAndRow($col1, 3);
                    // Check if cell is merged
                    foreach ($objWorksheet->getMergeCells() as $cells) {
                        if ($cell1->isInRange($cells)) {
                            $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                            $cell1 = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                            break;
                            
                        }
                    }
                    $val1[] = $cell1->getCalculatedValue();
                }

                $data_supplier = array(
                    'tire_supplier_name' => trim($val1[0]),
                    'tire_supplier_phone' => trim($val1[1]),
                    'tire_supplier_email' => trim($val1[2]),
                    'tire_supplier_skype' => trim($val1[3]),
                    'tire_supplier_whatsapp' => trim($val1[4]),
                    'tire_supplier_viber' => trim($val1[5]),
                    'tire_supplier_wechat' => trim($val1[6]),
                );

                if (!$tiresupplier->getTireByWhere(array('tire_supplier_name'=>$data_supplier['tire_supplier_name']))) {
                    $tiresupplier->createTire($data_supplier);
                    $id_supplier = $tiresupplier->getLastTire()->tire_supplier_id;
                }
                else{
                    $id_supplier = $tiresupplier->getTireByWhere(array('tire_supplier_name'=>$data_supplier['tire_supplier_name']))->tire_supplier_id;
                    $tiresupplier->updateTire($data_supplier,array('tire_supplier_id'=>$id_supplier));
                }
            

                    for ($row = 6; $row <= $highestRow; ++ $row) {
                        $val = array();
                        for ($col = 0; $col <= $highestColumnIndex; ++ $col) {
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
                        

                        if ($val[1] != null && $val[2] != null && $val[3] != null && $val[5] != null) {

                                if($tirebrand->getTireByWhere(array('tire_producer_name'=>trim($val[1])))) {
                                    $id_brand = $tirebrand->getTireByWhere(array('tire_producer_name'=>trim($val[1])))->tire_producer_id;
                                }
                                else if(!$tirebrand->getTireByWhere(array('tire_producer_name'=>trim($val[1])))){
                                    $tireproductbrand_data = array(
                                        'tire_producer_name' => trim($val[1]),
                                        );
                                    $tirebrand->createTire($tireproductbrand_data);

                                    $id_brand = $tirebrand->getLastTire()->tire_producer_id;
                                }

                                if($tiresize->getTireByWhere(array('tire_product_size_number'=>trim($val[2])))) {
                                    $id_size = $tiresize->getTireByWhere(array('tire_product_size_number'=>trim($val[2])))->tire_product_size_id;
                                }
                                else if(!$tiresize->getTireByWhere(array('tire_product_size_number'=>trim($val[2])))){
                                    $tireproductsize_data = array(
                                        'tire_product_size_number' => trim($val[2]),
                                        );
                                    $tiresize->createTire($tireproductsize_data);

                                    $id_size = $tiresize->getLastTire()->tire_product_size_id;
                                }

                                if($tirepattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[3])))) {
                                    $id_pattern = $tirepattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[3])))->tire_product_pattern_id;
                                }
                                else if(!$tirepattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[3])))){
                                    $tirepattern_data = array(
                                        'tire_product_pattern_name' => trim($val[3]),
                                        'tire_product_pattern_type' => trim($val[4]),
                                        );
                                    $tirepattern->createTire($tirepattern_data);

                                    $id_pattern = $tirepattern->getLastTire()->tire_product_pattern_id;
                                }


                                if (!$tirebuyprice->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern))) {
              
                                    $data = array(
                                        'tire_brand'=>$id_brand,
                                        'tire_size'=>$id_size,
                                        'tire_pattern'=>$id_pattern,
                                        'tire_buy_price' => trim($val[5]),
                                        'tire_supplier' => $id_supplier,
                                    );
                                    $tirebuyprice->createTire($data);
                                }
                                else if ($tirebuyprice->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern))) {
                                    $price = $tirebuyprice->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern));
                                    
                                    $data = array(
                                        'tire_brand'=>$id_brand,
                                        'tire_size'=>$id_size,
                                        'tire_pattern'=>$id_pattern,
                                        'tire_buy_price' => trim($val[5]),
                                        'tire_supplier' => $id_supplier,
                                    );
                                    if ($price->tire_buy_price == "" || $data['tire_buy_price'] < $price->tire_buy_price) {
                                        $tirebuyprice->updateTire($data,array('tire_buy_price_id'=>$price->tire_buy_price_id));
                                    }
                                    
                                }
                            
                        }
                        
                        //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                        // insert


                    }

                $i++;
                }
                
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('tirebuyprice');
        }
        $this->view->show('tirebuyprice/import');

    }

}
?>