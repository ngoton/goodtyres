<?php
Class importtirepriceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Giá nhập';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_time';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }
        $import_tire_price_model = $this->model->get('importtirepriceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
        
        $tongsodong = count($import_tire_price_model->getAllImport($data,$join));
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
            $search = '( tire_brand_name LIKE "%'.$keyword.'%"  
                    OR tire_size_number LIKE "%'.$keyword.'%"  
                    OR tire_pattern_name LIKE "%'.$keyword.'%"  
                    )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['import_tire_prices'] = $import_tire_price_model->getAllImport($data,$join);
        $this->view->data['lastID'] = isset($import_tire_price_model->getLastImport()->import_tire_price_id)?$import_tire_price_model->getLastImport()->import_tire_price_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('importtireprice/index');
    }

    public function getbrand(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tirebrandModel');
            

            if ($_POST['keyword'] == "*") {

                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_brand_name LIKE "%'.$_POST['keyword'].'%") ',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_brand_name;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_brand_name);
                }
                
                // add new option
                echo '<li onclick="set_item_brand(\''.$rs->tire_brand_name.'\',\''.$rs->tire_brand_id.'\',\''.$_POST['offset'].'\')">'.$tire_name.'</li>';
            }
        }
    }
    public function getsize(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tiresizeModel');
            

            if ($_POST['keyword'] == "*") {

                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_size_number LIKE "%'.$_POST['keyword'].'%") ',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_size_number;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_size_number);
                }
                
                // add new option
                echo '<li onclick="set_item_size(\''.$rs->tire_size_number.'\',\''.$rs->tire_size_id.'\',\''.$_POST['offset'].'\')">'.$tire_name.'</li>';
            }
        }
    }
    public function getpattern(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tirepatternModel');
            

            if ($_POST['keyword'] == "*") {

                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_pattern_name LIKE "%'.$_POST['keyword'].'%") ',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_pattern_name;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_pattern_name);
                }
                
                // add new option
                echo '<li onclick="set_item_pattern(\''.$rs->tire_pattern_name.'\',\''.$rs->tire_pattern_id.'\',\''.$_POST['offset'].'\')">'.$tire_name.'</li>';
            }
        }
    }
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $import_tire_price_model = $this->model->get('importtirepriceModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $data = array(
                        'tire_stuff' => trim($_POST['tire_stuff']),
                        'tire_price' => str_replace(',', '', $_POST['tire_price']),
                        'tire_price_down' => str_replace(',', '', $_POST['tire_price_down']),
                        'start_time' => strtotime($_POST['start_time']),
                        );
            
            if ($_POST['tire_brand'] != "") {
                $data['tire_brand'] = $_POST['tire_brand'];
            }
            else{
                if (trim($_POST['tire_brand_name']) != "") {
                    if ($tire_brand_model->getTireByWhere(array('tire_brand_name' => trim($_POST['tire_brand_name'])))) {
                        $data['tire_brand'] = $tire_brand_model->getTireByWhere(array('tire_brand_name' => trim($_POST['tire_brand_name'])))->tire_brand_id;
                    }
                    else{
                        $tire_brand_model->createTire(array('tire_brand_name' => trim($_POST['tire_brand_name'])));
                        $tire_brand_id = $tire_brand_model->getLastTire()->tire_brand_id;
                        $data['tire_brand'] = $tire_brand_id;
                    }
                    
                }
            }

            if ($_POST['tire_size'] != "") {
                $data['tire_size'] = $_POST['tire_size'];
            }
            else{
                if (trim($_POST['tire_size_number']) != "") {
                    if ($tire_size_model->getTireByWhere(array('tire_size_number' => trim($_POST['tire_size_number'])))) {
                        $data['tire_size'] = $tire_size_model->getTireByWhere(array('tire_size_number' => trim($_POST['tire_size_number'])))->tire_size_id;
                    }
                    else{
                        $tire_size_model->createTire(array('tire_size_number' => trim($_POST['tire_size_number'])));
                        $tire_size_id = $tire_size_model->getLastTire()->tire_size_id;
                        $data['tire_size'] = $tire_size_id;
                    }
                    
                }
            }

            if ($_POST['tire_pattern'] != "") {
                $data['tire_pattern'] = $_POST['tire_pattern'];
            }
            else{
                if (trim($_POST['tire_pattern_name']) != "") {
                    if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => trim($_POST['tire_pattern_name'])))) {
                        $data['tire_pattern'] = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => trim($_POST['tire_pattern_name'])))->tire_pattern_id;
                    }
                    else{
                        $tire_pattern_model->createTire(array('tire_pattern_name' => trim($_POST['tire_pattern_name'])));
                        $tire_pattern_id = $tire_pattern_model->getLastTire()->tire_pattern_id;
                        $data['tire_pattern'] = $tire_pattern_id;
                    }
                    
                }
            }


            if ($_POST['yes'] != "") {
                

                    $import_tire_price_model->updateImport($data,array('import_tire_price_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_tire_price|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $import_tire_price_model->createImport($data);

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$import_tire_price_model->getLastImport()->import_tire_price_id."|import_tire_price|".implode("-",$data)."\n"."\r\n";
                        
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
            $import_tire_price_model = $this->model->get('importtirepriceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $import_tire_price_model->deleteImport($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_tire_price|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $import_tire_price_model->deleteImport($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_tire_price|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>