<?php
Class priceselltireController Extends baseController {
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_brand_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_size_number ASC, start_time ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire();
        $tire_sizes = $tire_size_model->getAllTire();
        $tire_patterns = $tire_pattern_model->getAllTire();

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = 2.tire_brand AND tire_size.tire_size_id = price_sell_tire.tire_size AND tire_pattern.tire_pattern_id = price_sell_tire.tire_pattern');

        $tire_price_model = $this->model->get('tirepriceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($tire_price_model->getAllTire($data,$join));
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
                OR tire_size_number LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_prices'] = $tire_price_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_price_model->getLastTire()->tire_price_id)?$tire_price_model->getLastTire()->tire_price_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireprice/index');
    }

   public function getpattern(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_model = $this->model->get('tirepatternModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $tire_model->getAllTire();
            }
            else{
                $data = array(
                'where'=>'( tire_pattern_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $tire_model->getAllTire($data);
            }
            
            $expect_date = "";

            foreach ($list as $rs) {
                // put in bold the written text
                $tire_name = $rs->tire_pattern_name;
                if ($_POST['keyword'] != "*") {
                    $tire_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->tire_pattern_name);
                }
                
                // add new option
                echo '<li onclick="set_item_tire(\''.$rs->tire_pattern_name.'\',\''.$rs->tire_pattern_id.'\')">'.$tire_name.'</li>';
            }
        }
    }
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_price_model = $this->model->get('tirepriceModel');
            $data = array(
                        
                        'tire_brand' => trim($_POST['tire_brand']),
                        'tire_size' => trim($_POST['tire_size']),
                        'supply_price' => trim(str_replace(',','',$_POST['supply_price'])),
                        'custom_price' => trim(str_replace(',','',$_POST['custom_price'])),
                        'tax_price' => trim(str_replace(',','',$_POST['tax_price'])),
                        'price_start_time' => strtotime($_POST['price_start_time']),
                        'price_end_time' => strtotime($_POST['price_end_time']),
                        );
            if (trim($_POST['tire_pattern']) == "" && trim($_POST['tire_pattern_name']) != "") {
                $tire_pattern_model = $this->model->get('tirepatternModel');
                if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => trim($_POST['tire_pattern_name'])))) {
                    $data['tire_pattern'] = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => trim($_POST['tire_pattern_name'])))->tire_pattern_id;
                }
                else{
                    $data_pattern = array(
                        'tire_pattern_name' => trim($_POST['tire_pattern_name']),
                    );
                    $tire_pattern_model->createTire($data_pattern);
                    $data['tire_pattern'] = $tire_pattern_model->getLastTire()->tire_pattern_id;
                }
                
            }
            elseif (trim($_POST['tire_pattern']) != "") {
                $data['tire_pattern'] = trim($_POST['tire_pattern']);
            }

            if ($_POST['yes'] != "") {
                
                    $price_d = $tire_price_model->getTire($_POST['yes']);

                    $price1 = $tire_price_model->getTireByWhere(array('tire_brand'=>$price_d->tire_brand,'tire_size'=>$price_d->tire_size,'tire_pattern'=>$price_d->tire_pattern,'price_end_time'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$price_d->price_start_time).' -1 day'))))));
                    $price2 = $tire_price_model->getTireByWhere(array('tire_brand'=>$price_d->tire_brand,'tire_size'=>$price_d->tire_size,'tire_pattern'=>$price_d->tire_pattern,'price_start_time'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$price_d->price_end_time).' +1 day'))))));
                    if($price1)
                        $tire_price_model->updateTire(array('tire_brand'=>$price_d->tire_brand,'tire_size'=>$price_d->tire_size,'tire_pattern'=>$price_d->tire_pattern,'price_end_time'=>(strtotime(date('d-m-Y',strtotime($_POST['price_start_time'].' -1 day'))))),array('tire_price_id' => $price1->tire_price_id));
                    if($price2)
                        $tire_price_model->updateTire(array('tire_brand'=>$price_d->tire_brand,'tire_size'=>$price_d->tire_size,'tire_pattern'=>$price_d->tire_pattern,'price_start_time'=>(strtotime(date('d-m-Y',strtotime($_POST['price_end_time'].' +1 day'))))),array('tire_price_id' => $price2->tire_price_id));


                    $tire_price_model->updateTire($data,array('tire_price_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_price|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                $dm1 = $tire_price_model->queryTire('SELECT * FROM tire_price WHERE tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND price_start_time <= '.$data['price_start_time'].' AND price_end_time <= '.$data['price_end_time'].' AND price_end_time >= '.$data['price_start_time'].' ORDER BY price_end_time ASC LIMIT 1');
                $dm2 = $tire_price_model->queryTire('SELECT * FROM tire_price WHERE tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND price_end_time >= '.$data['price_end_time'].' AND price_start_time >= '.$data['price_start_time'].' AND price_start_time <= '.$data['price_end_time'].' ORDER BY price_end_time ASC LIMIT 1');
                $dm3 = $tire_price_model->queryTire('SELECT * FROM tire_price WHERE tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND price_start_time <= '.$data['price_start_time'].' AND price_end_time >= '.$data['price_end_time'].' ORDER BY price_end_time ASC LIMIT 1');

                if ($dm3) {
                            foreach ($dm3 as $row) {
                                $d = array(
                                    'price_end_time' => strtotime(date('d-m-Y',strtotime($_POST['price_start_time'].' -1 day'))),
                                    );
                                $tire_price_model->updateTire($d,array('tire_price_id'=>$row->tire_price_id));

                                $c = array(
                                    'tire_brand' => $row->tire_brand,
                                    'tire_size' => $row->tire_size,
                                    'tire_pattern' => $row->tire_pattern,
                                    'supply_price' => $row->supply_price,
                                    'custom_price' => $row->custom_price,
                                    'tax_price' => $row->tax_price,
                                    'price_start_time' => strtotime(date('d-m-Y',strtotime($_POST['price_end_time'].' +1 day'))),
                                    'price_end_time' => $row->price_end_time,
                                    );
                                $tire_price_model->createTire($c);

                            }

                            

                            
                            $tire_price_model->createTire($data);

                        }
                        else if ($dm1 || $dm2) {
                            if($dm1){
                                foreach ($dm1 as $row) {
                                    $d = array(
                                        'price_end_time' => strtotime(date('d-m-Y',strtotime($_POST['price_start_time'].' -1 day'))),
                                        );
                                    $tire_price_model->updateTire($d,array('tire_price_id'=>$row->tire_price_id));

                                    
                                }
                            }
                            if($dm2){
                                foreach ($dm2 as $row) {
                                    $d = array(
                                        'price_start_time' => strtotime(date('d-m-Y',strtotime($_POST['price_end_time'].' +1 day'))),
                                        );
                                    $tire_price_model->updateTire($d,array('tire_price_id'=>$row->tire_price_id));


                                }
                            }


                            
                            $tire_price_model->createTire($data);

                        
                    }
                    else{
                        $tire_price_model->createTire($data);

                    }

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_price_model->getLastTire()->tire_price_id."|tire_price|".implode("-",$data)."\n"."\r\n";
                        
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
            $tire_price_model = $this->model->get('tirepriceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_price_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_price|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_price_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_price|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>