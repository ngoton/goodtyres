<?php
Class tirependingController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thông tin đang chờ';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_cskh_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $id = $this->registry->router->param_id;

        $district_model = $this->model->get('districtModel');
        $districts = $district_model->getAllDistrict();
        $this->view->data['districts'] = $districts;

        $vehicle_type = $this->model->get('vehicletypeModel');

        $vehicle_types = $vehicle_type->getAllVehicle();
        $data_vehicle = array();
        foreach ($vehicle_types as $vehicle) {
            $data_vehicle['name'][$vehicle->vehicle_type_id] = $vehicle->vehicle_type_name;
        }
        $this->view->data['vehicle_type'] = $data_vehicle;

        $tire_cskh_model = $this->model->get('tirecskhModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '(tire_cskh_sale IS NULL OR tire_cskh_sale = 0)',
        );

        
        $tongsodong = count($tire_cskh_model->getAllTire($data));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(tire_cskh_sale IS NULL OR tire_cskh_sale = 0)',
            );

        
        
        if ($keyword != '') {
            $search = ' AND ( tire_cskh_company LIKE "%'.$keyword.'%" 
                    OR tire_cskh_mst LIKE "%'.$keyword.'%" 
                    OR tire_cskh_email LIKE "%'.$keyword.'%"
                )';
            $data['where'] .= $search;
        }
        
        
        
        $this->view->data['customers'] = $tire_cskh_model->getAllTire($data);

        $this->view->data['lastID'] = isset($tire_cskh_model->getLastTire()->tire_cskh_id)?$tire_cskh_model->getLastTire()->tire_cskh_id:0;
        
        $this->view->show('tirepending/index');
    }

    public function receive(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $customers = $this->model->get('tirecskhModel');
            //$costs_data = $costs->getCosts($_POST['data']);

            $data = array(
                        
                        'tire_cskh_sale' => $_SESSION['userid_logined'],
                        );
          
            $customers->updateTire($data,array('tire_cskh_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."receive"."|".$_POST['data']."|tire_cskh|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

}
?>