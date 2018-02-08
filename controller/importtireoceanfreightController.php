<?php
Class importtireoceanfreightController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Cước tàu';

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

        $import_tire_port_model = $this->model->get('importtireportModel');
        $ports = $import_tire_port_model->getAllImport(array('order_by'=>'import_tire_country ASC, import_tire_port_name ASC'));
        $this->view->data['ports'] = $ports;

        $import_tire_ocean_freight_model = $this->model->get('importtireoceanfreightModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        $join = array('table'=>'import_tire_port','where'=>'import_tire_port=import_tire_port_id');
        
        $tongsodong = count($import_tire_ocean_freight_model->getAllImport($data,$join));
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
            $search = '( import_tire_ocean_freight LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['import_tire_ocean_freights'] = $import_tire_ocean_freight_model->getAllImport($data,$join);
        $this->view->data['lastID'] = isset($import_tire_ocean_freight_model->getLastImport()->import_tire_ocean_freight_id)?$import_tire_ocean_freight_model->getLastImport()->import_tire_ocean_freight_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('importtireoceanfreight/index');
    }

   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $import_tire_ocean_freight_model = $this->model->get('importtireoceanfreightModel');
            $data = array(
                        'import_tire_port' => trim($_POST['import_tire_port']),
                        'import_tire_ocean_freight' => str_replace(',', '', $_POST['import_tire_ocean_freight']),
                        'start_time' => strtotime($_POST['start_time']),
                        );
            

            if ($_POST['yes'] != "") {
                

                    $import_tire_ocean_freight_model->updateImport($data,array('import_tire_ocean_freight_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_tire_ocean_freight|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $import_tire_ocean_freight_model->createImport($data);

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$import_tire_ocean_freight_model->getLastImport()->import_tire_ocean_freight_id."|import_tire_ocean_freight|".implode("-",$data)."\n"."\r\n";
                        
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
            $import_tire_ocean_freight_model = $this->model->get('importtireoceanfreightModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $import_tire_ocean_freight_model->deleteImport($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_tire_ocean_freight|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $import_tire_ocean_freight_model->deleteImport($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_tire_ocean_freight|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>