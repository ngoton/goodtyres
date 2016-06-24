<?php
Class ordertirewaitingController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng đang chờ';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_waiting_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $trangthai = 0;
            $nv = "";
        }


        $order_tire_model = $this->model->get('ordertirewaitingModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_waiting_status = '.$nv;
        }
        if ($nv == "") {
            $data['where'] .= ' AND (order_tire_waiting_status IS NULL OR order_tire_waiting_status=0)';
        }
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire_waiting.customer AND user_id = sale');
        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_waiting_status = '.$nv;
        }
        if ($nv == "") {
            $data['where'] .= ' AND (order_tire_waiting_status IS NULL OR order_tire_waiting_status=0)';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_waiting_id)?$order_tire_model->getLastTire()->order_tire_waiting_id:0;

        $this->view->show('ordertirewaiting/index');
    }

    public function listtire($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_list_model = $this->model->get('tiredesiredModel');
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand_code = tire_brand_id AND tire_size = tire_size_id AND tire_pattern_code = tire_pattern_id');

        $data = array(
            'where' => 'order_tire_waiting='.$id,
        );

        $order_tire_lists = $order_tire_list_model->getAllTire($data,$join);
        $this->view->data['tire_desireds'] = $order_tire_lists;

        $this->view->show('ordertirewaiting/listtire');
    }
    public function editorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('tiredesiredModel');
            $order_tire_model = $this->model->get('ordertirewaitingModel');
            $pattern_model = $this->model->get('tirepatternModel');
            $brand_model = $this->model->get('tirebrandModel');

            $order_tire_list = $order_tire_list_model->getTire($_POST['yes']);

            $order_tire = $order_tire_model->getTire($order_tire_list->order_tire_waiting);

            $brand = trim($_POST['tire_brand']);
            $pattern = trim($_POST['tire_pattern']);
            $size = trim($_POST['tire_size']);
            $number = trim($_POST['tire_number']);
            $price = trim(str_replace(',','',$_POST['tire_price']));

            $patterns = $pattern_model->getTire($pattern);
            $brands = $brand_model->getTire($brand);

            $data = array(
                'tire_brand'=>$brands->tire_brand_group,
                'tire_brand_code'=>$brand,
                'tire_pattern'=>$patterns->tire_pattern_type,
                'tire_pattern_code'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
                'tire_price'=>$price
            );

            $order_tire_list_model->updateTire($data,array('tire_desired_id'=>$_POST['yes']));

            $total_number = $order_tire->order_tire_waiting_number;

            $total_number = $total_number - $order_tire_list->tire_number + $number;
            

            $data_order = array(
                'order_tire_waiting_number'=>$total_number,
            );


            $order_tire_model->updateTire($data_order,array('order_tire_waiting_id'=>$order_tire_list->order_tire_waiting));


            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".implode("-",$data)."|tire_desired|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }

    public function deleteorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('tiredesiredModel');
            $order_tire_model = $this->model->get('ordertirewaitingModel');
            $pattern_model = $this->model->get('tirepatternModel');
            if(isset($_POST['data'])){
                        

                        $order_tire_list = $order_tire_list_model->getTire($_POST['data']);

                        $order_tire = $order_tire_model->getTire($order_tire_list->order_tire_waiting);

                        $total_number = $order_tire->order_tire_waiting_number;

                        $total_number = $total_number - $order_tire_list->tire_number;
                        

                        $data_order = array(
                            'order_tire_waiting_number'=>$total_number,
                        );


                        $order_tire_model->updateTire($data_order,array('order_tire_waiting_id'=>$order_tire_list->order_tire_waiting));

                        $order_tire_list_model->deleteTire($_POST['data']);
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

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertirewaitingModel');
            $order_tire_list_model = $this->model->get('tiredesiredModel');
            
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $order_tire_list_model->queryTire('DELETE FROM tire_desired WHERE order_tire_waiting = '.$data);
                       
                        $order_tire_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|order_tire_waiting|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        
                        $order_tire_list_model->queryTire('DELETE FROM tire_desired WHERE order_tire_waiting = '.$_POST['data']);
                        $order_tire_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|order_tire_waiting|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }
    

}
?>