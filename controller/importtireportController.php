<?php
Class importtireportController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Cảng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'import_tire_port_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $import_tire_country_model = $this->model->get('importtirecountryModel');
        $countrys = $import_tire_country_model->getAllImport();
        $this->view->data['countrys'] = $countrys;

        $import_tire_port_model = $this->model->get('importtireportModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'import_tire_country','where'=>'import_tire_country=import_tire_country_id');
        $data = array(
            'where' => '1=1',
        );
        
        $tongsodong = count($import_tire_port_model->getAllImport($data,$join));
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
            $search = '( import_tire_port_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['import_tire_ports'] = $import_tire_port_model->getAllImport($data,$join);
        $this->view->data['lastID'] = isset($import_tire_port_model->getLastImport()->import_tire_port_id)?$import_tire_port_model->getLastImport()->import_tire_port_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('importtireport/index');
    }

   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $import_tire_port_model = $this->model->get('importtireportModel');
            $data = array(
                        
                        'import_tire_port_name' => trim($_POST['import_tire_port_name']),
                        'import_tire_country' => trim($_POST['import_tire_country']),
                        );
            

            if ($_POST['yes'] != "") {
                

                    $import_tire_port_model->updateImport($data,array('import_tire_port_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_tire_port|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $import_tire_port_model->createImport($data);

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$import_tire_port_model->getLastImport()->import_tire_port_id."|import_tire_port|".implode("-",$data)."\n"."\r\n";
                        
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
            $import_tire_port_model = $this->model->get('importtireportModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $import_tire_port_model->deleteImport($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_tire_port|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $import_tire_port_model->deleteImport($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_tire_port|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>