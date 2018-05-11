<?php

Class tirepricediscounteventController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Giảm giá';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'end_date';

            $order = $this->registry->router->order ? $this->registry->router->order : 'DESC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name','order'=>'ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number','order'=>'ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name','order'=>'ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;


        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id');



        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;

        

        $tongsodong = count($tire_price_discount_event_model->getAllTire(null,$join));

        $tongsotrang = ceil($tongsodong / $sonews);

        



        $this->view->data['page'] = $page;

        $this->view->data['order_by'] = $order_by;

        $this->view->data['order'] = $order;

        $this->view->data['keyword'] = $keyword;

        $this->view->data['limit'] = $limit;

        $this->view->data['pagination_stages'] = $pagination_stages;

        $this->view->data['tongsotrang'] = $tongsotrang;

        $this->view->data['sonews'] = $sonews;



        $data = array(

            'order_by'=>$order_by,

            'order'=>$order,

            'limit'=>$x.','.$sonews,

            );

        

        if ($keyword != '') {

            $search = '( tire_brand_name LIKE "%'.$keyword.'%" 
                OR tire_size_number LIKE "%'.$keyword.'%" 
                OR tire_pattern_name LIKE "%'.$keyword.'%"
                )';

            $data['where'] = $search;

        }

        

        

        

        $this->view->data['tires'] = $tire_price_discount_event_model->getAllTire($data,$join);



        $this->view->data['lastID'] = isset($tire_price_discount_event_model->getLastTire()->tire_price_discount_event_id)?$tire_price_discount_event_model->getLastTire()->tire_price_discount_event_id:0;

        

        $this->view->show('tirepricediscountevent/index');

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }


        if (isset($_POST['yes'])) {

            $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
            $data = array(

                        'start_date' => strtotime(trim($_POST['start_date'])),

                        'end_date' => strtotime(trim($_POST['end_date'])),

                        'tire_brand' => trim($_POST['tire_brand']),

                        'tire_size' => trim($_POST['tire_size']),

                        'tire_pattern' => trim($_POST['tire_pattern']),

                        'percent_discount' => trim(str_replace(',', '', $_POST['percent_discount'])),

                        'money_discount' => trim(str_replace(',', '', $_POST['money_discount'])),

                        );

            if ($_POST['yes'] != "") {

                    $tire_price_discount_event_model->updateTire($data,array('tire_price_discount_event_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_price_discount_event|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{

                //$data['driver_create_user'] = $_SESSION['userid_logined'];

                //$data['staff'] = $_POST['staff'];

                //var_dump($data);

                if ($tire_price_discount_event_model->getTireByWhere(array('tire_brand'=>$data['tire_brand'],'tire_size'=>$data['tire_size'],'tire_pattern'=>$data['tire_pattern'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date']))) {

                    echo "Thông tin này đã tồn tại";

                    return false;

                }

                else{

                    
                    $tire_price_discount_event_model->createTire($data);

                    

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_price_discount_event_model->getLastTire()->tire_price_discount_event_id."|tire_price_discount_event|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                

            }

                    

        }

    }



    

    



    public function delete(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $tire_price_discount_event_model->deleteTire($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_price_discount_event|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                return true;

            }

            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|driver|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



                return $tire_price_discount_event_model->deleteTire($_POST['data']);

            }

            

        }

    }



}

?>