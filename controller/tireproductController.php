<?php
Class tireproductController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
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
            $thuonghieu = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $size = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $magai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_producer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_size ASC, tire_pattern ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $thuonghieu = 0;
            $size = 0;
            $magai = 0;
        }

        
        $tire_producer_model = $this->model->get('tireproducerModel');
        $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name','order'=>'ASC'));

        $tire_size_model = $this->model->get('tireproductsizeModel');
        $tire_sizes = $tire_size_model->getAllTire();

        $tire_pattern_model = $this->model->get('tireproductpatternModel');
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_product_pattern_name','order'=>'ASC'));

        $tire_vehicle_model = $this->model->get('tirevehicleModel');
        $tire_vehicles = $tire_vehicle_model->getAllTire(array('order_by'=>'tire_vehicle_type ASC, tire_vehicle_id','order'=>'ASC'));

        $this->view->data['tire_producers'] = $tire_producers;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;
        $this->view->data['tire_vehicles'] = $tire_vehicles;

        $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern','where'=>'tire_producer.tire_producer_id = tire_product.tire_producer AND tire_product_pattern.tire_product_pattern_id = tire_product.tire_pattern AND tire_product_size.tire_product_size_id = tire_product.tire_size');

        $tire_product_model = $this->model->get('tireproductModel');
        
        $data_p = array(
            'order_by'=>'tire_producer_name',
            'order'=>'ASC',
            'where'=>'1=1',
        );

        if ($thuonghieu>0) {
            $data_p['where'] .= ' AND tire_producer_id = '.$thuonghieu;
        }

        $tire_producers = $tire_producer_model->getAllTire($data_p);
        $this->view->data['tire_data_producers'] = $tire_producers;

        $data_products = array();

        foreach ($tire_producers as $tire_producer) {
            $sonews = $limit;
            $x = ($page-1) * $sonews;
            $pagination_stages = 2;
            
            $data = array(
                'where' => 'tire_producer = '.$tire_producer->tire_producer_id,
            );
            
            
            if ($size>0) {
                $data['where'] .= ' AND tire_size = '.$size;
            }
            if ($magai>0) {
                $data['where'] .= ' AND tire_pattern = '.$magai;
            }
            
            $tongsodong = count($tire_product_model->getAllTire($data,$join));
            $tongsotrang = ceil($tongsodong / $sonews);


            $data = array(
                'order_by'=>$order_by,
                'order'=>$order,
                'limit'=>$x.','.$sonews,
                'where' => 'tire_producer = '.$tire_producer->tire_producer_id,
                );
            
            
            if ($size>0) {
                $data['where'] .= ' AND tire_size = '.$size;
            }
            if ($magai>0) {
                $data['where'] .= ' AND tire_pattern = '.$magai;
            }
          
            if ($keyword != '') {
                $search = '( tire_producer_name LIKE "%'.$keyword.'%" 
                    OR tire_product_size_number LIKE "%'.$keyword.'%" 
                    OR tire_product_pattern_name LIKE "%'.$keyword.'%" 
                    OR tire_product_name LIKE "%'.$keyword.'%" )';
                
                    $data['where'] = $data['where'].' AND '.$search;
            }

            

            
            $data_products[$tire_producer->tire_producer_id] = $tire_product_model->getAllTire($data,$join);
        }

        $this->view->data['lastID'] = isset($tire_product_model->getLastTire()->tire_product_id)?$tire_product_model->getLastTire()->tire_product_id:0;

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['thuonghieu'] = $thuonghieu;
        $this->view->data['size'] = $size;
        $this->view->data['magai'] = $magai;
        
        $this->view->data['data_products'] = $data_products;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireproduct/index');
    }

    public function price() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá nhập lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

            $brand = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $size = isset($_POST['nv']) ? $_POST['nv'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_producer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_product_size_number ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $brand = 0;
            $size = 0;
        }
        $tire_producer_model = $this->model->get('tireproducerModel');
        $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name ASC'));
        $tire_size_model = $this->model->get('tireproductsizeModel');
        $tire_sizes = $tire_size_model->getAllTire();

        $this->view->data['tire_producers'] = $tire_producers;
        $this->view->data['tire_sizes'] = $tire_sizes;

        $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern','where'=>'tire_producer_id = tire_producer AND tire_product_size_id = tire_size AND tire_product_pattern_id = tire_pattern');

        $tire_product_model = $this->model->get('tireproductModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );

        if ($brand > 0) {
            $data['where'] .= ' AND tire_producer = '.$brand;
        }
        if ($size > 0) {
            $data['where'] .= ' AND tire_size = '.$size;
        }
        
        
        $tongsodong = count($tire_product_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $brand;
        $this->view->data['nv'] = $size;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        if ($brand > 0) {
            $data['where'] .= ' AND tire_producer = '.$brand;
        }
        if ($size > 0) {
            $data['where'] .= ' AND tire_size = '.$size;
        }
        
      
        if ($keyword != '') {
            $search = '( tire_producer_name LIKE "%'.$keyword.'%" 
                OR tire_product_size_number LIKE "%'.$keyword.'%" 
                OR tire_product_pattern_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_products'] = $tire_product_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_product_model->getLastTire()->tire_product_id)?$tire_product_model->getLastTire()->tire_product_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireproduct/price');
    }

    public function quotation() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        $size = isset($_POST['nv']) ? $_POST['nv'] : 0;
        $magai = isset($_POST['trangthai']) ? $_POST['trangthai'] : 0;
        
        $this->view->data['size'] = $size;
        $this->view->data['magai'] = $magai;

        $brand = $this->registry->router->order_by;

        if (!empty($brand)) {
            $tire_size_model = $this->model->get('tireproductsizeModel');
            $tire_pattern_model = $this->model->get('tireproductpatternModel');

            $tire_sizes = $tire_size_model->getAllTire();
            $tire_patterns = $tire_pattern_model->getAllTire(array('where'=>'tire_product_pattern_id IN (SELECT tire_pattern FROM tire_product,tire_producer WHERE tire_producer=tire_producer_id AND tire_producer_name LIKE "'.str_replace('-', ' ', $brand).'")'));

            $this->view->data['tire_sizes'] = $tire_sizes;
            $this->view->data['tire_patterns'] = $tire_patterns;

            $tire_product_model = $this->model->get('tireproductModel');
            $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern','where'=>'tire_producer_id = tire_producer AND tire_product_size_id = tire_size AND tire_product_pattern_id = tire_pattern');
            $data = array(
                'where'=>'tire_producer_name LIKE "'.str_replace('-', ' ', $brand).'"',
            );

            if ($size>0) {
                $data['where'] .= ' AND tire_size = '.$size;
            }
            if ($magai>0) {
                $data['where'] .= ' AND tire_pattern = '.$magai;
            }

            $this->view->data['tire_products'] = $tire_product_model->getAllTire($data,$join);
        }
        
        $tire_producer_model = $this->model->get('tireproducerModel');
        $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name','order'=>'ASC'));
        $tire_producer_data = array();
        foreach ($tire_producers as $tire) {
            $tire_producer_data[strtoupper(substr($tire->tire_producer_name, 0, 1))][] = $tire->tire_producer_name;
        }
        $this->view->data['tire_producer_data'] = $tire_producer_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireproduct/quotation');
    }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_product_model = $this->model->get('tireproductModel');
            $data = array(
                        
                        'tire_producer' => trim($_POST['tire_producer']),
                        'tire_product_name' => trim($_POST['tire_product_name']),
                        'tire_type' => trim($_POST['tire_type']),
                        'tire_size' => trim($_POST['tire_size']),
                        'tire_pattern' => trim($_POST['tire_pattern']),
                        'tire_pr' => trim($_POST['tire_pr']),
                        'tire_weight' => trim($_POST['tire_weight']),
                        'tire_depth' => trim($_POST['tire_depth']),
                        'tire_qty' => trim($_POST['tire_qty']),
                        'tire_price' => trim(str_replace(',','',$_POST['tire_price'])),
                        'tire_agent' => trim(str_replace(',','',$_POST['tire_agent'])),
                        'tire_retail' => trim(str_replace(',','',$_POST['tire_retail'])),
                        'tire_product_desc' => trim($_POST['tire_product_desc']),
                        'tire_product_content' => trim($_POST['tire_product_content']),
                        'tire_product_plies' => trim($_POST['tire_product_plies']),
                        'tire_product_tube' => trim($_POST['tire_product_tube']),
                        'tire_product_vehicle' => trim($_POST['tire_product_vehicle']),
                        'tire_product_feature' => trim($_POST['tire_product_feature']),
                        'tire_product_link' => trim($_POST['tire_product_link']),
                        );
        
            if ($_FILES['tire_product_thumb']['name'] != '') {
                $this->lib->upload_image('tire_product_thumb');
                $data['tire_product_thumb'] = $_FILES['tire_product_thumb']['name'];
            }

            if ($_POST['yes'] != "") {
                

                    $tire_product_model->updateTire($data,array('tire_product_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_product|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
            

                    $tire_product_model->createTire($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_product_model->getLastTire()->tire_product_id."|tire_product|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }

       return $this->view->redirect('tireproduct'); 
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_product_model = $this->model->get('tireproductModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_product_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_product|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_product_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_product|"."\n"."\r\n";
                        
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

            $tireproduct = $this->model->get('tireproductModel');
            $tireproducer = $this->model->get('tireproducerModel');
            $tireproductpattern = $this->model->get('tireproductpatternModel');
            $tireproductsize = $this->model->get('tireproductsizeModel');

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
                        if ($val[1] != null && $val[4] != null && $val[6] != null) {

                                if($tireproducer->getTireByWhere(array('tire_producer_name'=>trim($val[1])))) {
                                    $id_producer = $tireproducer->getTireByWhere(array('tire_producer_name'=>trim($val[1])))->tire_producer_id;
                                }
                                else if(!$tireproducer->getTireByWhere(array('tire_producer_name'=>trim($val[1])))){
                                    $tireproducer_data = array(
                                        'tire_producer_name' => trim($val[1]),
                                        );
                                    $tireproducer->createTire($tireproducer_data);

                                    $id_producer = $tireproducer->getLastTire()->tire_producer_id;
                                }

                                if($tireproductsize->getTireByWhere(array('tire_product_size_number'=>trim($val[4])))) {
                                    $id_product_size = $tireproductsize->getTireByWhere(array('tire_product_size_number'=>trim($val[4])))->tire_product_size_id;
                                }
                                else if(!$tireproductsize->getTireByWhere(array('tire_product_size_number'=>trim($val[4])))){
                                    $tireproductsize_data = array(
                                        'tire_product_size_number' => trim($val[4]),
                                        );
                                    $tireproductsize->createTire($tireproductsize_data);

                                    $id_product_size = $tireproductsize->getLastTire()->tire_product_size_id;
                                }

                                if($tireproductpattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[6])))) {
                                    $id_product_pattern = $tireproductpattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[6])))->tire_product_pattern_id;
                                    $tireproductpattern_data = array(
                                        'tire_product_pattern_type' => trim($val[15]),
                                        );
                                    $tireproductpattern->updateTire($tireproductpattern_data,array('tire_product_pattern_id'=>$id_product_pattern));
                                }
                                else if(!$tireproductpattern->getTireByWhere(array('tire_product_pattern_name'=>trim($val[6])))){
                                    $tireproductpattern_data = array(
                                        'tire_product_pattern_name' => trim($val[6]),
                                        'tire_product_pattern_type' => trim($val[15]),
                                        );
                                    $tireproductpattern->createTire($tireproductpattern_data);

                                    $id_product_pattern = $tireproductpattern->getLastTire()->tire_product_pattern_id;
                                }


                                if ($id_producer != null) {
                                    
                                    if($tireproduct->getTireByWhere(array('tire_producer'=>$id_producer,'tire_size'=>$id_product_size,'tire_pattern'=>$id_product_pattern))) {
                                        $id_tire_product = $tireproduct->getTireByWhere(array('tire_producer'=>$id_producer,'tire_size'=>$id_product_size,'tire_pattern'=>$id_product_pattern))->tire_product_id;

                                        $tire_product_data = array(
                                        'tire_producer' => $id_producer,
                                        'tire_product_name' => trim($val[2]),
                                        'tire_type' => trim($val[3]),
                                        'tire_size' => $id_product_size,
                                        'tire_pattern' => $id_product_pattern,
                                        'tire_pr' => trim($val[5]),
                                        'tire_weight' => trim($val[7]),
                                        'tire_depth' => trim($val[8]),
                                        'tire_qty' => trim($val[9]),
                                        'tire_price' => trim($val[10]),
                                        'tire_agent' => trim($val[11]),
                                        'tire_retail' => trim($val[12]),
                                        'tire_product_thumb' => trim($val[13]),
                                        'tire_product_link' => trim(strtolower($val[14])),
                                        );
                                        $tireproduct->updateTire($tire_product_data,array('tire_product_id' => $id_tire_product));
                                    }
                                    else{
                                        $tire_product_data = array(
                                        'tire_producer' => $id_producer,
                                        'tire_product_name' => trim($val[2]),
                                        'tire_type' => trim($val[3]),
                                        'tire_size' => $id_product_size,
                                        'tire_pattern' => $id_product_pattern,
                                        'tire_pr' => trim($val[5]),
                                        'tire_weight' => trim($val[7]),
                                        'tire_depth' => trim($val[8]),
                                        'tire_qty' => trim($val[9]),
                                        'tire_price' => trim($val[10]),
                                        'tire_agent' => trim($val[11]),
                                        'tire_retail' => trim($val[12]),
                                        'tire_product_thumb' => trim($val[13]),
                                        'tire_product_link' => trim(strtolower($val[14])),
                                        );
                                        $tireproduct->createTire($tire_product_data);
                                    }
                                }
                            
                        }
                        
                        //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                        // insert


                    }
                //return $this->view->redirect('transport');
                $i++;
            }
            
            return $this->view->redirect('tireproduct');
        }
        $this->view->show('tireproduct/import');

    }

}
?>