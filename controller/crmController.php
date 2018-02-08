<?php
Class crmController Extends baseController {
    public function index(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'CRM';

        $customer_model = $this->model->get('customertireModel');
        $customer_contact_model = $this->model->get('customertirecontactModel');
        $order_tire_model = $this->model->get('ordertireModel');

        $kh_trong = $customer_model->queryCustomer('SELECT customer_tire_id FROM customer_tire WHERE customer_tire_id NOT IN (SELECT customer_tire FROM customer WHERE customer_tire IS NOT NULL) AND (customer_tire_care IS NULL OR customer_tire_care = "") ');
        $this->view->data['kh_trong'] = $kh_trong;

        $kh_lienhe = $customer_contact_model->queryCustomer('SELECT customer_tire_id FROM customer_tire WHERE customer_tire_id IN (SELECT customer_tire FROM customer_tire_contact WHERE customer_tire IS NOT NULL)');
        $this->view->data['kh_lienhe'] = $kh_lienhe;

        $kh_chaogia = $customer_model->queryCustomer('SELECT customer_tire_id FROM customer_tire WHERE customer_tire_status=2');
        $this->view->data['kh_chaogia'] = $kh_chaogia;

        $donhang = $order_tire_model->queryTire('SELECT order_tire_id,total FROM order_tire');
        $this->view->data['donhang'] = $donhang;

        $doanhthu = 0;
        foreach ($donhang as $don) {
            $doanhthu += $don->total;
        }

        $this->view->data['doanhthu'] = $doanhthu;

        $this->view->show('crm/index');

    }
    public function customerfree(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'CRM';

        $district_model = $this->model->get('districtModel');
        $customer_model = $this->model->get('customertireModel');

        $districts = $district_model->getAllDistrict();
        $district_data = array();
        foreach ($districts as $district) {
            $district_data[$district->district_id] = $district->district_name;
        }
        $this->view->data['district_data'] = $district_data;

        $data = array(
            'where'=>'customer_tire_id NOT IN (SELECT customer_tire FROM customer WHERE customer_tire IS NOT NULL) AND (customer_tire_care IS NULL OR customer_tire_care = "") ',
        );

        $customers = $customer_model->getAllCustomer($data);
        $this->view->data['customers'] = $customers;
        
        $this->view->show('crm/customerfree');

    }

    public function customer(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'CRM';

        $act = $this->registry->router->param_id;
        $id = $this->registry->router->page;

        $district_model = $this->model->get('districtModel');
        $customer_model = $this->model->get('customertireModel');

        $districts = $district_model->getAllDistrict();
        $this->view->data['districts'] = $districts;

        if ($act==2) {
            $this->view->data['customers'] = $customer_model->getCustomer($id);

            $vehicle_type = $this->model->get('vehicletypeModel');

            $vehicle_types = $vehicle_type->getAllVehicle();
            $data_vehicle = array();
            foreach ($vehicle_types as $vehicle) {
                $data_vehicle['name'][$vehicle->vehicle_type_id] = $vehicle->vehicle_type_name;
            }
            $this->view->data['vehicle_type'] = $data_vehicle;

            $customer_tire_contact_model = $this->model->get('customertirecontactModel');
            $contact_persons = $customer_tire_contact_model->getAllCustomer(array('where'=>'customer_tire = '.$id));
            $this->view->data['contact_persons'] = $contact_persons;

            $customer_tire_type_model = $this->model->get('customertiretypeModel');
            $tire_types = $customer_tire_type_model->getAllCustomer(array('where'=>'customer_tire = '.$id),array('table'=>'tire_size','where'=>'tire_size=tire_size_id'));
            $this->view->data['tire_types'] = $tire_types;

            $this->view->show('crm/editcus');
        }
        elseif ($act==1) {
            $this->view->show('crm/addcus');
        }
        
        

    }
    public function getCus(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = $this->model->get('customertireModel');

            $datas = explode(',', $_POST['data']);
            foreach ($datas as $dt) {
                $data = array(
                    'customer_tire_care'=> $_POST['type']==1?$_SESSION['userid_logined']:null,
                );

                $customer->updateCustomer($data,array('customer_tire_id'=>$dt));
            }

            

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|customer_care|".$_POST['data']."|customer_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $customer = $this->model->get('customertireModel');
            /**************/
            $tire_type = $_POST['tire_type'];
            $customer_tire_contact = $_POST['customer_tire_contact'];
            /**************/
            $customer_tire_type = $this->model->get('customertiretypeModel');
            $customer_tire_contact_model = $this->model->get('customertirecontactModel');

            $data = array(
                        'customer_tire_code' => trim($_POST['customer_tire_code']),
                        'customer_tire_company' => trim($_POST['customer_tire_company']),
                        'customer_tire_co' => trim($_POST['customer_tire_co']),
                        'customer_tire_mst' => trim($_POST['customer_tire_mst']),
                        'customer_tire_fax' => trim($_POST['customer_tire_fax']),
                        'customer_tire_sdt' => trim($_POST['customer_tire_sdt']),
                        'customer_tire_director' => trim($_POST['customer_tire_director']),
                        'customer_tire_street' => trim($_POST['customer_tire_street']),
                        'customer_tire_ward' => trim($_POST['customer_tire_ward']),
                        'customer_tire_district' => trim($_POST['customer_tire_district']),
                        'customer_tire_city' => trim($_POST['customer_tire_city']),
                        'customer_tire_province' => trim($_POST['customer_tire_province']),
                        'customer_tire_type' => trim($_POST['customer_tire_type']),
                        'vehicle_number' => trim($_POST['vehicle_number']),
                        'customer_tire_ref' => trim($_POST['customer_tire_ref']),
                        
                        );

            if (trim($_POST['vehicle_type']) != "") {
                $data['vehicle_type'] = trim($_POST['vehicle_type']);
            }
            else{
                if (trim($_POST['vehicle_type_name']) != "") {
                    $vehicle_type = $this->model->get('vehicletypeModel');
                    $vehicle_type->createVehicle(array('vehicle_type_name' => trim($_POST['vehicle_type_name'])));
                    $vehicle_type_id = $vehicle_type->getLastVehicle()->vehicle_type_id;
                    $data['vehicle_type'] = $vehicle_type_id;
                }
            }


            if ($_POST['yes'] != "") {
                $customer_data = $customer->getCustomer($_POST['yes']);

                $check = $customer->queryCustomer('SELECT * FROM customer_tire WHERE customer_tire_id != '.$_POST['yes'].' AND customer_tire_mst = '.$data['customer_tire_mst']);

                if ($check && $data['customer_tire_mst'] != 0 ) {
                    echo 'Khách hàng đã tồn tại';
                    return false;
                }
                else{
                    $customer->updateCustomer($data,array('customer_tire_id' => $_POST['yes']));

                    $id_customer_tire = $_POST['yes'];
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|customer_tire|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
                
            }
            else{
                $data['customer_tire_sale'] = $_SESSION['userid_logined'];
                $data['customer_tire_date'] = strtotime(date('d-m-Y'));


                if($customer->getCustomerByWhere(array('customer_tire_mst'=>$data['customer_tire_mst'])) && $data['customer_tire_mst'] != 0 ){
                    echo 'Khách hàng đã tồn tại';
                    return false;
                }
                else{
                    $customer->createCustomer($data);
                    echo "Thêm thành công";

                $id_customer_tire = $customer->getLastCustomer()->customer_tire_id;

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$customer->getLastCustomer()->customer_tire_id."|customer_tire|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                }
                    
                
            }


            foreach ($tire_type as $v) {
                $s = isset($v['tire_size'])?$v['tire_size']:null;

                $data_tire_type = array(
                    'tire_size' => $s,
                    'tire_pattern_type' => $v['tire_pattern_type'],
                    'customer_tire'=>$id_customer_tire
                );


                if ($customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_pattern_type'=>$data_tire_type['tire_pattern_type'],'customer_tire'=>$id_customer_tire))) {
                    $id_customer_tire_type = $customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_pattern_type'=>$data_tire_type['tire_pattern_type'],'customer_tire'=>$id_customer_tire))->customer_tire_type_id;
                    $customer_tire_type->updateCustomer($data_tire_type,array('customer_tire_type_id'=>$id_customer_tire_type));
                }
                else if (!$customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_pattern_type'=>$data_tire_type['tire_pattern_type'],'customer_tire'=>$id_customer_tire))) {
                    if ($data_tire_type['tire_size'] > 0) {
                        $customer_tire_type->createCustomer($data_tire_type);
                    }
                }
            }

            foreach ($customer_tire_contact as $v2) {
                $data_contact = array(
                    'customer_tire_contact_name' => trim($v2['customer_tire_contact_name']),
                    'customer_tire_contact_phone' => trim($v2['customer_tire_contact_phone']),
                    'customer_tire_contact_birth' => trim(strtotime($v2['customer_tire_contact_birth'])),
                    'customer_tire_contact_position' => trim($v2['customer_tire_contact_position']),
                    'customer_tire_contact_email' => trim($v2['customer_tire_contact_email']),
                    'customer_tire_contact_fid' => trim($v2['customer_tire_contact_fid']),
                    'customer_tire_contact_zid' => trim($v2['customer_tire_contact_zid']),
                    'customer_tire_contact_sid' => trim($v2['customer_tire_contact_sid']),
                    'customer_tire'=>$id_customer_tire,
                );


                if ($customer_tire_contact_model->getCustomerByWhere(array('customer_tire_contact_name'=>$data_contact['customer_tire_contact_name'],'customer_tire_contact_phone'=>$data_contact['customer_tire_contact_phone'],'customer_tire'=>$id_customer_tire))) {
                    $id_customer_tire_contact = $customer_tire_contact_model->getCustomerByWhere(array('customer_tire_contact_name'=>$data_contact['customer_tire_contact_name'],'customer_tire_contact_phone'=>$data_contact['customer_tire_contact_phone'],'customer_tire'=>$id_customer_tire))->customer_tire_contact_id;
                    $customer_tire_contact_model->updateCustomer($data_contact,array('customer_tire_contact_id'=>$id_customer_tire_contact));
                }
                else if (!$customer_tire_contact_model->getCustomerByWhere(array('customer_tire_contact_name'=>$data_contact['customer_tire_contact_name'],'customer_tire_contact_phone'=>$data_contact['customer_tire_contact_phone'],'customer_tire'=>$id_customer_tire))) {
                    if ($data_contact['customer_tire_contact_name'] != "") {
                        $customer_tire_contact_model->createCustomer($data_contact);
                    }
                }
            }
            
                    
        }
    }


}
?>