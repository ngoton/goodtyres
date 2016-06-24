<?php
Class tireoceanfreightController Extends baseController {
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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_ocean_freight_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        
        $tire_ocean_freight_model = $this->model->get('tireoceanfreightModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($tire_ocean_freight_model->getAllTire($data));
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
            $search = '( tire_ocean_freight LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_ocean_freights'] = $tire_ocean_freight_model->getAllTire($data);
        $this->view->data['lastID'] = isset($tire_ocean_freight_model->getLastTire()->tire_ocean_freight_id)?$tire_ocean_freight_model->getLastTire()->tire_ocean_freight_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireoceanfreight/index');
    }

   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_ocean_freight_model = $this->model->get('tireoceanfreightModel');
            $data = array(
                        
                        'feet' => trim($_POST['feet']),
                        'tire_ocean_freight' => trim(str_replace(',','',$_POST['tire_ocean_freight'])),
                        'tire_ocean_freight_start_time' => strtotime($_POST['tire_ocean_freight_start_time']),
                        'tire_ocean_freight_end_time' => strtotime($_POST['tire_ocean_freight_end_time']),
                        );
            

            if ($_POST['yes'] != "") {
                
                    $price_d = $tire_ocean_freight_model->getTire($_POST['yes']);

                    $price1 = $tire_ocean_freight_model->getTireByWhere(array('tire_ocean_freight_end_time'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$price_d->tire_ocean_freight_start_time).' -1 day'))))));
                    $price2 = $tire_ocean_freight_model->getTireByWhere(array('tire_ocean_freight_start_time'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$price_d->tire_ocean_freight_end_time).' +1 day'))))));
                    if($price1)
                        $tire_ocean_freight_model->updateTire(array('tire_ocean_freight_end_time'=>(strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_start_time'].' -1 day'))))),array('tire_ocean_freight_id' => $price1->tire_ocean_freight_id));
                    if($price2)
                        $tire_ocean_freight_model->updateTire(array('tire_ocean_freight_start_time'=>(strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_end_time'].' +1 day'))))),array('tire_ocean_freight_id' => $price2->tire_ocean_freight_id));


                    $tire_ocean_freight_model->updateTire($data,array('tire_ocean_freight_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_ocean_freight|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                $dm1 = $tire_ocean_freight_model->queryTire('SELECT * FROM tire_ocean_freight WHERE feet='.$data['feet'].' AND tire_ocean_freight_start_time <= '.$data['tire_ocean_freight_start_time'].' AND tire_ocean_freight_end_time <= '.$data['tire_ocean_freight_end_time'].' AND tire_ocean_freight_end_time >= '.$data['tire_ocean_freight_start_time'].' ORDER BY tire_ocean_freight_end_time ASC LIMIT 1');
                $dm2 = $tire_ocean_freight_model->queryTire('SELECT * FROM tire_ocean_freight WHERE feet='.$data['feet'].' AND tire_ocean_freight_end_time >= '.$data['tire_ocean_freight_end_time'].' AND tire_ocean_freight_start_time >= '.$data['tire_ocean_freight_start_time'].' AND tire_ocean_freight_start_time <= '.$data['tire_ocean_freight_end_time'].' ORDER BY tire_ocean_freight_end_time ASC LIMIT 1');
                $dm3 = $tire_ocean_freight_model->queryTire('SELECT * FROM tire_ocean_freight WHERE feet='.$data['feet'].' AND tire_ocean_freight_start_time <= '.$data['tire_ocean_freight_start_time'].' AND tire_ocean_freight_end_time >= '.$data['tire_ocean_freight_end_time'].' ORDER BY tire_ocean_freight_end_time ASC LIMIT 1');

                if ($dm3) {
                            foreach ($dm3 as $row) {
                                $d = array(
                                    'tire_ocean_freight_end_time' => strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_start_time'].' -1 day'))),
                                    );
                                $tire_ocean_freight_model->updateTire($d,array('tire_ocean_freight_id'=>$row->tire_ocean_freight_id));

                                $c = array(
                                    'tire_ocean_freight' => $row->tire_ocean_freight,
                                    'feet' => $row->feet,
                                    'tire_ocean_freight_start_time' => strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_end_time'].' +1 day'))),
                                    'tire_ocean_freight_end_time' => $row->tire_ocean_freight_end_time,
                                    );
                                $tire_ocean_freight_model->createTire($c);

                            }

                            

                            
                            $tire_ocean_freight_model->createTire($data);

                        }
                        else if ($dm1 || $dm2) {
                            if($dm1){
                                foreach ($dm1 as $row) {
                                    $d = array(
                                        'tire_ocean_freight_end_time' => strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_start_time'].' -1 day'))),
                                        );
                                    $tire_ocean_freight_model->updateTire($d,array('tire_ocean_freight_id'=>$row->tire_ocean_freight_id));

                                    
                                }
                            }
                            if($dm2){
                                foreach ($dm2 as $row) {
                                    $d = array(
                                        'tire_ocean_freight_start_time' => strtotime(date('d-m-Y',strtotime($_POST['tire_ocean_freight_end_time'].' +1 day'))),
                                        );
                                    $tire_ocean_freight_model->updateTire($d,array('tire_ocean_freight_id'=>$row->tire_ocean_freight_id));


                                }
                            }


                            
                            $tire_ocean_freight_model->createTire($data);

                        
                    }
                    else{
                        $tire_ocean_freight_model->createTire($data);

                    }
                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_ocean_freight_model->getLastTire()->tire_ocean_freight_id."|tire_ocean_freight|".implode("-",$data)."\n"."\r\n";
                        
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
            $tire_ocean_freight_model = $this->model->get('tireoceanfreightModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_ocean_freight_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_ocean_freight|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_ocean_freight_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_ocean_freight|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>