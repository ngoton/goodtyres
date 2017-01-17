<?php
Class customertireController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin khách hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_tire_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC, customer_tire_id DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $trangthai = "";
        }

        $user_model = $this->model->get('userModel');
        $user_info = $user_model->getUser($_SESSION['userid_logined']);
        $users = $user_model->getAllUser();
        $data_user = array();
        foreach ($users as $u) {
            $data_user['name'][$u->user_id] = $u->username;
        }
        $this->view->data['data_user'] = $data_user;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('order_by'=>'staff_name','order'=>'ASC'));
        $this->view->data['staffs'] = $staffs;

        $district_model = $this->model->get('districtModel');
        $districts = $district_model->getAllDistrict();
        $this->view->data['districts'] = $districts;

        $vehicle_type = $this->model->get('vehicletypeModel');
        $tire_brand = $this->model->get('tirebrandModel');
        $tire_size = $this->model->get('tiresizeModel');

        $vehicle_types = $vehicle_type->getAllVehicle();
        $data_vehicle = array();
        foreach ($vehicle_types as $vehicle) {
            $data_vehicle['name'][$vehicle->vehicle_type_id] = $vehicle->vehicle_type_name;
        }

        $tire_brands = $tire_brand->getAllTire();
        $data_brand = array();
        foreach ($tire_brands as $tire) {
            $data_brand['name'][$tire->tire_brand_id] = $tire->tire_brand_name;
        }

        $tire_sizes = $tire_size->getAllTire();
        $data_size = array();
        foreach ($tire_sizes as $tire) {
            $data_size['name'][$tire->tire_size_id] = $tire->tire_size_number;
        }

        $this->view->data['tire_brand'] = $data_brand;
        $this->view->data['tire_size'] = $data_size;
        $this->view->data['vehicle_type'] = $data_vehicle;

        $id = $this->registry->router->param_id;

        $join = array('table'=>'user','where'=>'user.user_id = customer_tire.customer_tire_sale');

        $customer_model = $this->model->get('customertireModel');

        $list_customers = $customer_model->queryCustomer('SELECT customer_tire_company FROM customer_tire ORDER BY customer_tire_company ASC');
        if ($_SESSION['role_logined'] != 1) {
            $list_customers = $customer_model->queryCustomer('SELECT customer_tire_company FROM customer_tire WHERE customer_tire_sale = '.$_SESSION['userid_logined'].' ORDER BY customer_tire_company ASC');
        }
        $this->view->data['list_customers'] = $list_customers;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => 'customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc),
        );

        if ($trangthai != "") {
            $data['where'] = 'customer_tire_company LIKE "%'.$trangthai.'%"';
        }

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_tire_id = '.$id;
        }

        if ($_SESSION['role_logined'] != 1) {
            $data['where'] .= ' AND ( customer_tire_sale IN (SELECT user_id FROM user WHERE user_group = '.$user_info->user_group.') OR customer_tire_sale = '.$_SESSION['userid_logined'].')';
        }

        

        
        $tongsodong = count($customer_model->getAllCustomer($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc),
            );

        if ($trangthai != "") {
            $data['where'] = 'customer_tire_company LIKE "%'.$trangthai.'%"';
        }

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_tire_id = '.$id;
        }

        if ($_SESSION['role_logined'] != 1) {
            $data['where'] .= ' AND ( customer_tire_sale IN (SELECT user_id FROM user WHERE user_group = '.$user_info->user_group.') OR customer_tire_sale = '.$_SESSION['userid_logined'].')';
        }
        
        if ($keyword != '') {
            $search = ' AND ( customer_tire_company LIKE "%'.$keyword.'%" 
                OR customer_tire_contact LIKE "%'.$keyword.'%" 
                OR customer_tire_email LIKE "%'.$keyword.'%" 
                OR customer_tire_mst LIKE "%'.$keyword.'%" 
                OR customer_tire_phone LIKE "%'.$keyword.'%" 
                OR vehicle_type IN (SELECT vehicle_type_id FROM vehicle_type WHERE vehicle_type_name LIKE "%'.$keyword.'%") 
                OR tire_brand IN (SELECT tire_brand_id FROM tire_brand WHERE tire_brand_name LIKE "%'.$keyword.'%") 
                OR tire_size IN (SELECT tire_size_id FROM tire_size WHERE tire_size_number LIKE "%'.$keyword.'%")
                OR username LIKE "%'.$keyword.'%"
                )';
            $data['where'] .= $search;
        }
        
        $customers = $customer_model->getAllCustomer($data,$join);
        
        $this->view->data['customers'] = $customers;

        $customer_type_model = $this->model->get('customertiretypeModel');

        $check_null = array();

        foreach ($customers as $cus) {
            $check_null[$cus->customer_tire_id] = 'null';
            $types = $customer_type_model->getCustomerByWhere(array('customer_tire'=>$cus->customer_tire_id));

            if ($cus->customer_tire_contact == "") {
                $check_null[$cus->customer_tire_id] = 'error';
            }
            if ($cus->customer_tire_email == "") {
                $check_null[$cus->customer_tire_id] = 'error';
            }
            if ($cus->customer_tire_phone == "") {
                $check_null[$cus->customer_tire_id] = 'error';
            }

            if (!$types) {
                $check_null[$cus->customer_tire_id] = 'error';
            }
        }

        $this->view->data['check_null'] = $check_null;

        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->customer_tire_id)?$customer_model->getLastCustomer()->customer_tire_id:0;
        
        $this->view->show('customertire/index');
    }

    public function getvehicle(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vehicle_model = $this->model->get('vehicletypeModel');
            

            if ($_POST['keyword'] == "*") {

                $list = $vehicle_model->getAllVehicle();
            }
            else{
                $data = array(
                'where'=>'( vehicle_type_name LIKE "%'.$_POST['keyword'].'%") ',
                );
                $list = $vehicle_model->getAllVehicle($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vehicle_name = $rs->vehicle_type_name;
                if ($_POST['keyword'] != "*") {
                    $vehicle_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->vehicle_type_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vehicle(\''.$rs->vehicle_type_name.'\',\''.$rs->vehicle_type_id.'\')">'.$vehicle_name.'</li>';
            }
        }
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
                echo '<li onclick="set_item_tire(\''.$rs->tire_pattern_name.'\',\''.$rs->tire_pattern_id.'\',\''.$_POST['offset'].'\')">'.$tire_name.'</li>';
            }
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
            /**************/
            $customer_tire_type = $this->model->get('customertiretypeModel');

            $data = array(
                        
                        'customer_tire_company' => trim($_POST['customer_tire_company']),
                        'customer_tire_contact' => trim($_POST['customer_tire_contact']),
                        'customer_tire_phone' => trim($_POST['customer_tire_phone']),
                        'customer_tire_email' => trim($_POST['customer_tire_email']),
                        'vehicle_number' => trim($_POST['vehicle_number']),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
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

                foreach ($tire_type as $v) {
                    if (isset($v['tire_size']) && $v['tire_size'] != "" && $customer_data->customer_tire_sale2 == "") {
                        $data['customer_tire_sale2'] = $_SESSION['userid_logined'];
                        break;
                    }
                }

                if (trim($_POST['customer_tire_sale2']) > 0) {
                    $data['customer_tire_sale2'] = trim($_POST['customer_tire_sale2']);
                }

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

                if (trim($_POST['customer_tire_sale2']) > 0) {
                    $data['customer_tire_sale2'] = trim($_POST['customer_tire_sale2']);
                }

                foreach ($tire_type as $v) {
                    if (isset($v['tire_size']) && $v['tire_size'] != "") {
                        $data['customer_tire_sale2'] = $_SESSION['userid_logined'];
                        break;
                    }
                }
                

                if($customer->getCustomerByWhere(array('customer_tire_email'=>$data['customer_tire_email']))){
                    echo 'Khách hàng đã tồn tại';
                    return false;
                }
                else if($customer->getCustomerByWhere(array('customer_tire_mst'=>$data['customer_tire_mst'])) && $data['customer_tire_mst'] != 0 ){
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

                    if ($data['customer_tire_sale2'] <= 0 || $data['customer_tire_sale2'] == "") {
                        $this->auto_send_mail($data); // gửi mail
                    }
                    
                }
                    
                
            }

            $tire_brand = $this->model->get('tirebrandModel');
            $tire_size = $this->model->get('tiresizeModel');
            $tire_pattern = $this->model->get('tirepatternModel');

            foreach ($tire_type as $v) {
                $b = isset($v['tire_brand'])?$v['tire_brand']:null;
                $s = isset($v['tire_size'])?$v['tire_size']:null;
                $p = isset($v['tire_pattern'])?$v['tire_pattern']:null;

                $data_tire_type = array(
                    'tire_brand' => $b,
                    'tire_size' => $s,
                    'tire_pattern' => $p,
                    'tire_number' => str_replace(',','',$v['tire_number']),
                    'tire_price' => str_replace(',','',$v['tire_price']),
                    'tire_commission' => str_replace(',','',$v['tire_commission']),
                    'customer_tire' => $id_customer_tire,
                    'check_vat' => $v['check_vat'],
                    'comment' => trim($v['comment']),
                );

                if ($data_tire_type['tire_brand'] != "") {
                    $data_tire_type['tire_brand'] = $data_tire_type['tire_brand'];
                }
                else{
                    if (trim($v['tire_brand_name']) != "") {
                        if ($tire_brand->getTireByWhere(array('tire_brand_name' => trim($v['tire_brand_name'])))) {
                            $data_tire_type['tire_brand'] = $tire_brand->getTireByWhere(array('tire_brand_name' => trim($v['tire_brand_name'])))->tire_brand_id;
                        }
                        else{
                            $tire_brand->createTire(array('tire_brand_name' => trim($v['tire_brand_name'])));
                            $tire_brand_id = $tire_brand->getLastTire()->tire_brand_id;
                            $data_tire_type['tire_brand'] = $tire_brand_id;
                        }
                        
                    }
                }

                if ($data_tire_type['tire_size'] != "") {
                    $data_tire_type['tire_size'] = $data_tire_type['tire_size'];
                }
                else{
                    if (trim($v['tire_size_number']) != "") {
                        if ($tire_size->getTireByWhere(array('tire_size_number' => trim($v['tire_size_number'])))) {
                            $data_tire_type['tire_size'] = $tire_size->getTireByWhere(array('tire_size_number' => trim($v['tire_size_number'])))->tire_size_id;
                        }
                        else{
                            $tire_size->createTire(array('tire_size_number' => trim($v['tire_size_number'])));
                            $tire_size_id = $tire_size->getLastTire()->tire_size_id;
                            $data_tire_type['tire_size'] = $tire_size_id;
                        }
                        
                    }
                }

                if ($data_tire_type['tire_pattern'] != "") {
                    $data_tire_type['tire_pattern'] = $data_tire_type['tire_pattern'];
                }
                else{
                    if (trim($v['tire_pattern_name']) != "") {
                        if($tire_pattern->getTireByWhere(array('tire_pattern_name' => trim($v['tire_pattern_name'])))){
                            $data_tire_type['tire_pattern'] = $tire_pattern->getTireByWhere(array('tire_pattern_name' => trim($v['tire_pattern_name'])))->tire_pattern_id;
                        }
                        else{
                            $tire_pattern->createTire(array('tire_pattern_name' => trim($v['tire_pattern_name'])));
                            $tire_pattern_id = $tire_pattern->getLastTire()->tire_pattern_id;
                            $data_tire_type['tire_pattern'] = $tire_pattern_id;
                        }
                        
                    }
                }

                if ($customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'customer_tire'=>$id_customer_tire))) {
                    $id_customer_tire_type = $customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'customer_tire'=>$id_customer_tire))->customer_tire_type_id;
                    $customer_tire_type->updateCustomer($data_tire_type,array('customer_tire_type_id'=>$id_customer_tire_type));
                }
                else if (!$customer_tire_type->getCustomerByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'customer_tire'=>$id_customer_tire))) {
                    if ($data_tire_type['tire_pattern'] > 0 || $data_tire_type['tire_size'] > 0 || $data_tire_type['tire_brand'] > 0) {
                        $customer_tire_type->createCustomer($data_tire_type);
                    }
                }
            }


            
                    
        }
    }

    public function getcustomertire(){
        if(isset($_POST['customer_tire'])){
            $tire_brand = $this->model->get('tirebrandModel');
            $tire_size = $this->model->get('tiresizeModel');
            $tire_pattern = $this->model->get('tirepatternModel');

            $tire_brands = $tire_brand->getAllTire();
            $tire_sizes = $tire_size->getAllTire();
            $tire_patterns = $tire_pattern->getAllTire();

            $brand[0]['name'] = null;
            $size[0]['name'] = null;
            $pattern[0]['name'] = null;

            foreach ($tire_brands as $tire) {
                $brand[$tire->tire_brand_id]['name'] = $tire->tire_brand_name;
            }
            foreach ($tire_sizes as $tire) {
                $size[$tire->tire_size_id]['name'] = $tire->tire_size_number;
            }
            foreach ($tire_patterns as $tire) {
                $pattern[$tire->tire_pattern_id]['name'] = $tire->tire_pattern_name;
            }

            $customer_tire_type = $this->model->get('customertiretypeModel');
           
            $customer_types = $customer_tire_type->getAllCustomer(array('where'=>'customer_tire='.$_POST['customer_tire']));
            

            $str = "";

            if(!$customer_types){

                $str .= '<tr class="'.$_POST['customer_tire'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['customer_tire'] .'">';
                    $str .= '<td>Thương hiệu</td>';
                    $str .= '<td><input type="text" class="tire_brand" name="tire_brand[]" autocomplete="false" tabindex="8" >';
                    $str .= '<ul class="brand_list_id"></ul></td>';
                    $str .= '<td>Size</td>';
                    $str .= '<td><input type="text" class="tire_size" name="tire_size[]" autocomplete="false" tabindex="9" >';
                    $str .= '<ul class="size_list_id"></ul></td>';
                    $str .= '<td>Mã gai</td>';
                    $str .= '<td><input type="text" class="numbers tire_pattern" name="tire_pattern[]" autocomplete="false" tabindex="10" >';
                    $str .= '<ul class="tire_list_id"></ul></td>';
                    $str .= '<td>Số lượng</td>';
                    $str .= '<td><input  type="text" class="numbers tire_number"  name="tire_number[]" tabindex="11" ></td></tr>';
                    $str .= '<tr class="'.$_POST['customer_tire'] .'">';
                    $str .= '<td>Giá chào</td>';
                    $str .= '<td><input  type="text" style="width:90px" class="number tire_price"  name="tire_price[]" tabindex="12" > <input  type="checkbox"  class="check_vat"  name="check_vat[]"  > VAT</td>';
                    $str .= '<td>Hoa hồng </td>';
                    $str .= '<td><input  type="text"  class="number tire_commission"  name="tire_commission[]" tabindex="13" ></td>';
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td><textarea name="comment[]" class="comment" tabindex="14"></textarea></td></tr>';
                    
                    $str .= '</table></td></tr>';
            }
            else{

                foreach ($customer_types as $v) {
                    $str .= '<tr class="'.$v->customer_tire.'">';
                    $str .= '<td><input type="checkbox"  name="chk" class="'.$v->tire_pattern.'" data="'.$v->tire_brand.'" tabindex="'.$v->tire_size.'" title="'.$v->customer_tire.'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->customer_tire.'">';
                    $str .= '<td>Thương hiệu</td>';
                    $str .= '<td><input type="text" disabled class="tire_brand" tabindex="8" name="tire_brand[]" data="'.$v->tire_brand.'" value="'.$brand[$v->tire_brand]['name'].'" autocomplete="false" >';
                    $str .= '<ul class="brand_list_id"></ul></td>';
                    $str .= '<td>Size</td>';
                    $str .= '<td><input type="text" disabled class="tire_size" tabindex="9" name="tire_size[]" data="'.$v->tire_size.'" value="'.$size[$v->tire_size]['name'].'" autocomplete="false" >';
                    $str .= '<ul class="size_list_id"></ul></td>';
                    $str .= '<td>Mã gai</td>';
                    $str .= '<td><input type="text" disabled class="numbers tire_pattern" tabindex="10" name="tire_pattern[]" data="'.$v->tire_pattern.'" value="'.$pattern[$v->tire_pattern]['name'].'" autocomplete="false" >';
                    $str .= '<ul class="size_list_id"></ul></td>';
                    $str .= '<td>Số lượng</td>';
                    $str .= '<td><input  type="text" class="numbers tire_number" tabindex="11" value="'.$v->tire_number.'" name="tire_number[]"  ></td></tr>';
                    $str .= '<tr class="'.$_POST['customer_tire'] .'">';
                    $str .= '<td>Giá chào</td>';
                    $str .= '<td><input  type="text" style="width:90px" class="number tire_price" tabindex="12" value="'.$this->lib->formatMoney($v->tire_price).'" name="tire_price[]"  > <input  type="checkbox"  class="check_vat" '.($v->check_vat==1?'checked':null).'  name="check_vat[]" value="'.$v->check_vat.'" > VAT</td>';
                    $str .= '<td>Hoa hồng </td>';
                    $str .= '<td><input  type="text"  class="number tire_commission" tabindex="13" value="'.$this->lib->formatMoney($v->tire_commission).'" name="tire_commission[]"  ></td>';
                    $str .= '<td>Ghi chú</td>';
                    $str .= '<td><textarea name="comment[]" class="comment" tabindex="14">'.$v->comment.'</textarea></td></tr>';
                    
                    $str .= '</table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletetiretype(){
        if (isset($_POST['data'])) {
            $tire_type = $this->model->get('customertiretypeModel');

            $tire_type->queryCustomer('DELETE FROM customer_tire_type WHERE tire_brand='.$_POST['data'].' AND tire_size='.$_POST['type'].' AND tire_pattern='.$_POST['pattern'].' AND customer_tire='.$_POST['customer']);
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = $this->model->get('customertireModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $customer->deleteCustomer($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|customer_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                }
                return true;
            }
            else{
                    $customer->deleteCustomer($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|customer_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
            }
            
        }
    }

    public function statistic(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thống kê';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $vong = isset($_POST['sl_round']) ? $_POST['sl_round'] : null;
            $trangthai = isset($_POST['sl_trangthai']) ? $_POST['sl_trangthai'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $vong = (int)date('m',strtotime($batdau));
            $trangthai = date('Y',strtotime($batdau));
        }

        $vong = (int)date('m',strtotime($batdau));
        $trangthai = date('Y',strtotime($batdau));

        $customer_model = $this->model->get('customertireModel');
        $user_model = $this->model->get('userModel');
        $salary_model = $this->model->get('newsalaryModel');

        $join = array('table'=>'staff','where'=>'user_id=account');
        $data_user = array(
            'where' => 'user_id IN (SELECT customer_tire_sale FROM customer_tire WHERE customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc).')',
        );
        $users = $user_model->getAllUser($data_user,$join);

        $join_salary = array('table'=>'staff, user','where'=>'user_id=account AND staff=staff_id AND new_salary.create_time >= '.strtotime($batdau).' AND new_salary.create_time <= '.strtotime($ketthuc));
        $salarys = $salary_model->getAllSalary($data_user,$join_salary);

        $data_salary = array();
        foreach ($salarys as $salary) {
            $data_salary[$salary->user_id] = $salary->basic_salary;
        }


        $data = array(
            'where' => '(customer_tire_email != "" OR customer_tire_phone != "") AND customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc),
        );

        $customers = $customer_model->getAllCustomer($data);
        $sales = array();

        foreach ($customers as $customer) {
            $sales[$customer->customer_tire_sale][$customer->customer_tire_date] = isset($sales[$customer->customer_tire_sale][$customer->customer_tire_date])?$sales[$customer->customer_tire_sale][$customer->customer_tire_date]+1:1;
        }

        $data['where'] .= ' GROUP BY customer_tire_date ORDER BY customer_tire_date ASC';
        $customers = $customer_model->getAllCustomer($data);

        $this->view->data['customers'] = $customers;
        $this->view->data['users'] = $users;
        $this->view->data['data_salary'] = $data_salary;
        $this->view->data['sales'] = $sales;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['vong'] = $vong;
        $this->view->data['trangthai'] = $trangthai;

        $this->view->show('customertire/statistic');

    }

    public function total(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tổng hợp';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $vong = isset($_POST['sl_round']) ? $_POST['sl_round'] : null;
            $trangthai = isset($_POST['sl_trangthai']) ? $_POST['sl_trangthai'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $vong = (int)date('m',strtotime($batdau));
            $trangthai = date('Y',strtotime($batdau));
        }

        $vong = (int)date('m',strtotime($batdau));
        $trangthai = date('Y',strtotime($batdau));

        $customer_model = $this->model->get('customertireModel');
        $user_model = $this->model->get('userModel');
        $tiresale_model = $this->model->get('tiresaleModel');
        $customer_type_model = $this->model->get('customertiretypeModel');


        $join = array('table'=>'staff','where'=>'user_id=account');
        $data_user = array(
            'where' => '(user_id IN (SELECT customer_tire_sale FROM customer_tire WHERE customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc).') OR staff_id IN (SELECT sale FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).') )',
        );
        $users = $user_model->getAllUser($data_user,$join);

        $data = array(
            'where' => 'customer_tire_date >= '.strtotime($batdau).' AND customer_tire_date <= '.strtotime($ketthuc),
        );

        $customers = $customer_model->getAllCustomer($data);
        $du = array();
        $thieu = array();

        foreach ($customers as $cus) {
            $types = $customer_type_model->getCustomerByWhere(array('customer_tire'=>$cus->customer_tire_id));

            if ($cus->customer_tire_contact == "" || $cus->customer_tire_email == "" || $cus->customer_tire_phone == "" || ($cus->customer_tire_street == "" && $cus->customer_tire_ward == "" && $cus->customer_tire_district == "" && $cus->customer_tire_city == "") || !$types) {
                $thieu[$cus->customer_tire_sale] = isset($thieu[$cus->customer_tire_sale])?$thieu[$cus->customer_tire_sale]+1:1;
            }
            else{
                $du[$cus->customer_tire_sale] = isset($du[$cus->customer_tire_sale])?$du[$cus->customer_tire_sale]+1:1;
            }
            
        }

        $data = array(
            'where' => 'tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc),
        );

        $sales = $tiresale_model->getAllTire($data);

        $data = array(
            'where' => 'tire_sale_date < '.strtotime($batdau),
        );

        $sale_olds = $tiresale_model->getAllTire($data);

        $old = array();
        foreach ($sale_olds as $sale) {
            if (!in_array($sale->customer,$old)) {
                $old[] = $sale->customer;
            }
        }

        $sl_daily = array();
        $sl_tt = array();
        $daily_cu = array();
        $daily_moi = array();
        $tt_moi = array();
        $tt_cu = array();

        $cus_arr = array();

        foreach ($sales as $sale) {
            if ($sale->customer_type == 1) {
                $sl_daily[$sale->sale] = isset($sl_daily[$sale->sale])?$sl_daily[$sale->sale]+$sale->volume:$sale->volume;
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->customer,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $daily_cu[$sale->sale] = isset($daily_cu[$sale->sale])?$daily_cu[$sale->sale]+1:1;
                    }
                    else{
                        $daily_moi[$sale->sale] = isset($daily_moi[$sale->sale])?$daily_moi[$sale->sale]+1:1;
                    }
                }
            }
            else{
                $sl_tt[$sale->sale] = isset($sl_tt[$sale->sale])?$sl_tt[$sale->sale]+$sale->volume:$sale->volume;
                if(!isset($cus_arr[$sale->sale]) || !in_array($sale->customer,$cus_arr[$sale->sale])){
                    if (in_array($sale->customer,$old)) {
                        $tt_cu[$sale->sale] = isset($tt_cu[$sale->sale])?$tt_cu[$sale->sale]+1:1;
                    }
                    else{
                        $tt_moi[$sale->sale] = isset($tt_moi[$sale->sale])?$tt_moi[$sale->sale]+1:1;
                    }
                }
            }

            $cus_arr[$sale->sale][] = $sale->customer;
        }

        
        $this->view->data['users'] = $users;
        $this->view->data['du'] = $du;
        $this->view->data['thieu'] = $thieu;
        $this->view->data['sl_daily'] = $sl_daily;
        $this->view->data['sl_tt'] = $sl_tt;
        $this->view->data['daily_cu'] = $daily_cu;
        $this->view->data['daily_moi'] = $daily_moi;
        $this->view->data['tt_moi'] = $tt_moi;
        $this->view->data['tt_cu'] = $tt_cu;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['vong'] = $vong;
        $this->view->data['trangthai'] = $trangthai;

        $this->view->show('customertire/total');

    }

    public function mail(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Gửi mail';

        $customer_tire_model = $this->model->get('customertireModel');
        $customer_model = $this->model->get('customerModel');

        $data = array(
            'where' => '(customer_tire_email IS NOT NULL AND customer_tire_email != "") ',
        );

        if ($_SESSION['role_logined'] == 3) {
            $data['where'] .= ' AND customer_tire_sale = '.$_SESSION['userid_logined'];
        }

        $customers = $customer_tire_model->getAllCustomer($data);

        $data = array(
            'where' => '(customer_email IS NOT NULL AND customer_email != "") AND customer_email NOT IN (SELECT customer_tire_email FROM customer_tire)',
        );
        $customer_others = $customer_model->getAllCustomer($data);

        $this->view->data['customers'] = $customers;
        $this->view->data['customer_others'] = $customer_others;

        $this->view->show('customertire/mail');
    }

    public function postmail(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require "lib/class.phpmailer.php";

            $arrays = $_POST['customer_email'];
            $noidung = stripslashes(trim($_POST['mail_content']));
            $chude = trim($_POST['subject']);
            $usr = trim($_POST['tendangnhap']);
            $pas = trim($_POST['matkhau']);
            $hostname = trim($_POST['hostname']);
            $chuky = stripslashes(trim($_POST['signature']));

            $err = array();

            foreach ($arrays as $arr) {
                // Khai báo tạo PHPMailer
                $mail = new PHPMailer();
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = $hostname; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = $usr; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = $pas; //Mật khẩu của gmail
                $mail->SetFrom($usr, "VIET TRADE"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                $mail->AddAddress($arr, $arr);//Email của người nhận
                $mail->Subject = $chude; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                //$mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                $mail->MsgHTML($noidung.$chuky); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = $chude;//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if(!$mail->Send()){
                    $err[] = array(
                        'email' => $arr,
                        'err' => 1,
                    );
                }
                else{
                    $err[] = array(
                        'email' => $arr,
                        'err' => 0,
                    );
                }
                //Tiến hành gửi email và kiểm tra lỗi
                sleep(10);
            }

            echo json_encode($err);
        }
    }

    public function sendmail(){
        $customer_model = $this->model->get('customertireModel');
        $user_id = 23;
        $data_customer = array(
            'where' => 'customer_tire_email IS NOT NULL AND customer_tire_sale = '.$user_id,
            'limit' => 50,50,
        );

        $customers = $customer_model->getAllCustomer($data);



        $noidung = "
        <p style='margin:0in;margin-bottom:.0001pt;line-height:18.75pt;background:white'>
        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>Kính gửi Quý khách hàng</span>
        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'><br>
        Nhân dịp Giáng sinh và Năm mới sắp tới, <br>
        Chúng tôi xin trân trọng gửi lời chúc ấm áp của chúng tôi cho kỳ nghỉ lễ sắp tới và xin chúc bạn và gia đình một Giáng sinh vui vẻ và một năm mới thịnh vượng. Chúng tôi cũng muốn nhân cơ hội này để nói lời cảm ơn đến doanh nghiệp của bạn đã đồng hành cùng chúng tôi trong năm vừa qua và mong muốn được tiếp tục hợp tác trong những năm tới.
        </span>
        </p>
        <p style='mso-margin-top-alt:0in;margin-right:0in;margin-bottom:12.0pt;margin-left:0in;line-height:18.75pt;background:white;BOX-SIZING: border-box !important;MIN-HEIGHT: 1em;MAX-WIDTH: 100%;WORD-WRAP: break-word !important;white-space:pre-wrap;-webkit-text-stroke-width: 0px;word-spacing:0px'>
        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>
        Merry Christmas and Happy New Year.<br>
        <img width=697 height=488 id='christmas' src='cid:hinhanh'><br>
        </span>
        <b><span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>
        Best regards,
        </span></b>
        <b><span style='font-family:'Arial',sans-serif;color:#3E3E3E'>Sale Team<br></span></b>
        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#666666'>Mobile: 0937 131 845</span>
        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#666666'>Hotline: 083 500 9000</span>
        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#4E8FCD'>Email: 
        <a href='mailto:lopxe@viet-trade.org'>lopxe@viet-trade.org</a></span>
        </p>
        ";
        
        // Khai báo thư viên phpmailer
            require "lib/class.phpmailer.php";
             
            foreach ($customers as $customer) {
                // Khai báo tạo PHPMailer
                $mail = new PHPMailer();
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = "nghi.nguyen@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = "nghinguyen!@#$"; //Mật khẩu của gmail
                $mail->SetFrom("nghi.nguyen@viet-trade.org", "VIET TRADE"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                $mail->AddAddress($customer->customer_tire_email, $customer_tire_contact);//Email của người nhận
                $mail->Subject = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE"; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                $mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                $mail->MsgHTML($noidung); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 
                $mail->Send();
                //Tiến hành gửi email và kiểm tra lỗi
            }

            
    }

    public function auto_send_mail($data){
        $email = $data['customer_tire_email'];
        $chude = 'LỐP XE TẢI BỐ KẼM CHẤT LƯỢNG GIÁ RẺ - GOODTYRES';
        $user = 'cskh@goodtyres.vn';
        $pass = 'cskh!@#$';
        $host = 'smtp.zoho.com';
        $kt1 = 'ms,ms.,chi,chị,c ,c.';
        $kt2 = 'mr,mr.,anh,a , a.';

        $xungho = 'anh/chị';
        $ten = str_replace('ms.', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('ms', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('mr.', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('mr', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('anh', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('a ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('a. ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('chị', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('chi', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('c ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('c. ', '', strtolower($data['customer_tire_contact']));

        $arr=explode(',',$kt1);
        foreach($arr as $st)
        {
            if(strpos(strtolower($data['customer_tire_contact']),$st)!==false)
            {
              $xungho = 'chị';
              break;
            }
        }  
        
        $arr=explode(',',$kt2);
        foreach($arr as $st)
        {
            if(strpos(strtolower($data['customer_tire_contact']),$st)!==false)
            {
              $xungho = 'anh';
              break;
            }
        } 


        $noidung = '<p>Chào '.$xungho.' '.$ten.',</p>
<p>
    Em bên công ty GoodTyres - <b><span style="font-family: Consolas; color: black;">NHÀ NHẬP KHẨU VÀ PHÂN PHỐI TRỰC TIẾP </span></b><b><span style="font-family: Consolas; color: red;">LỐP XE TẢI BỐ KẼM.</span></b></p>
<p>
    Hiện tại, em đang có giá tốt cho bên mình '.$xungho.' tham khảo qua. Bên cạnh đó công ty em còn có nhiều chính sách ưu đãi khác cho khách hàng.</p>
<p>
    Có gì thắc mắc '.$xungho.' cứ liên lạc trao đổi trực tiếp với em.</p>
<p>
    Hi vọng được hợp tác cùng '.$xungho.'.</p>
<p></p>

<table border="1" cellpadding="1" cellspacing="0" style="border-collapse:
 collapse;width:411pt" width="100%">
    <colgroup>
        <col style="width: 41pt; text-align: center;" width="54" />
        <col style="width: 182pt; text-align: center;" width="442" />
        <col style="width: 52pt; text-align: center;" width="69" />
        <col style="width: 43pt; text-align: center;" width="57" />
        <col style="width: 93pt; text-align: center;" width="124" />
    </colgroup>
    <tbody>
        <tr height="20" style="height:15.0pt">
            <td class="xl69" height="20" style="height: 15pt; width: 41pt; text-align: center;" width="54">
                <strong>M&atilde; gai</strong></td>
            <td class="xl70" style="border-left-style: none; width: 182pt; text-align: center;" width="242">
                <strong>H&igrave;nh ảnh</strong></td>
            <td class="xl70" style="border-left-style: none; width: 52pt; text-align: center;" width="69">
                <strong>K&iacute;ch cỡ</strong></td>
            <td class="xl69" style="border-left-style: none; width: 43pt; text-align: center;" width="57">
                <strong>Lớp bố</strong></td>
            <td class="xl70" style="border-left-style: none; width: 93pt; text-align: center;" width="124">
                <strong>Đơn gi&aacute;</strong></td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                DC01</td>
            <td align="left" height="180" rowspan="9" style="height:135.0pt;width:182pt" valign="top" width="242">
                <p style="text-align: center;">
<!--[endif]-->              </p>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="xl72" height="180" rowspan="9" style="height: 135pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                <p>
                                    <img height="178" src="cid:DC01"  width="233" /></p>
                                <p>
                                    <img height="178" src="cid:DC02"  width="233" /></p>
                                <p>
                                    <img height="178" src="cid:DC03"  width="233" /></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                6.50R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                12PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1,972,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                7.00R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                14PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,070,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                7.50R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,280,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                DC02</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                8.25R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,520,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                8.25R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,040,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                9.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,710,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                DC03</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                10.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,020,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                11.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,260,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,840,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl73" height="160" rowspan="8" style="height: 120pt; border-top-style: none; text-align: center;">
                NC01</td>
            <td align="left" height="160" rowspan="8" style="height:120.0pt;width:182pt" valign="top" width="242">
                <p style="text-align: center;">
<!--[endif]-->              </p>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="xl72" height="160" rowspan="8" style="height: 120pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                <img height="178" src="cid:NC01"  width="233" /></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                7.00R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                14PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,160,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                7.50R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,380,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                8.25R16</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,550,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                8.25R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,130,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                9.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,820,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                10.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,020,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                11.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,520,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,930,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl73" height="40" rowspan="2" style="height: 30pt; border-top-style: none; text-align: center;">
                BC01</td>
            <td align="left" height="40" rowspan="2" style="height:30.0pt;width:182pt" valign="top" width="242">
                <p style="text-align: center;">
<!--[endif]-->              </p>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="xl72" height="40" rowspan="2" style="height: 30pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                <img height="178" src="cid:BC01"  width="233" /></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                11.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,530,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,120,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl73" height="40" rowspan="2" style="height: 30pt; border-top-style: none; text-align: center;">
                BC02</td>
            <td class="xl72" rowspan="2" style="border-top-style: none; text-align: center;">
                <img height="178" src="cid:BC02"  width="233" /></td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                11.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,210,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12.00R20</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                18PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,460,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                DK01<br />
                DK02</td>
            <td class="xl72" rowspan="3" style="border-top-style: none; text-align: center;">
                <img height="178" src="cid:DK01"  width="233" /><img height="178" src="cid:DK02"  width="233" /></td>
            <td class="xl68" style="border-top-style: none; border-left-style: none; text-align: center;">
                295/75R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,720,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                11R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,780,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,120,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                NK01<br />
                NK02</td>
            <td align="left" height="60" rowspan="3" style="height:45.0pt;width:182pt" valign="top" width="242">
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td height="0" style="text-align: center;" width="0">
                                &nbsp;</td>
                            <td style="text-align: center;" width="99">
                                &nbsp;</td>
                            <td style="text-align: center;" width="7">
                                &nbsp;</td>
                            <td style="text-align: center;" width="100">
                                &nbsp;</td>
                        </tr>
                        <tr>
                            <td height="1" style="text-align: center;">
                                &nbsp;</td>
                            <td colspan="2" style="text-align: center;">
                                <img height="178" src="cid:NK01"  width="233" /></td>
                        </tr>
                        <tr>
                            <td height="55" style="text-align: center;">
                                &nbsp;</td>
                        </tr>
                    </tbody>
                </table>
                <p style="text-align: center;">
<!--[endif]-->              </p>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="xl72" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                <img height="178" src="cid:NK02"  width="233" /></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="xl68" style="border-top-style: none; border-left-style: none; text-align: center;">
                295/75R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,850,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                11R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,010,000</td>
        </tr>
        <tr height="20" style="height:15.0pt">
            <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                12R22.5</td>
            <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                16PR</td>
            <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,170,000</td>
        </tr>
    </tbody>
</table>
<p>
    &nbsp;</p>';

        $chuky = '<div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
    <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
        <strong><span style="color:#f00;">IRIS TON</span></strong></p>
    <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
        <span style="color:#808080;"><em>Bussiness Development Manager</em></span></p>
</div>
        <div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
    <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
        &nbsp;</p>
</div>
<div align="center" class="MsoNormal" style="text-align: center; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial;background-clip: initial; background-position: initial; background-repeat: initial;">
    <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
</div>
<table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
    <tbody>
        <tr>
            <td style="width:80pt;padding:0in 0in 0in 0in" valign="top" width="102">
                <p class="MsoNormal">
<span new="" style="font-size: 12pt; font-family: " times=""><img height="102" src="cid:favicon"  width="102" /></span></p>
            </td>
            <td style="padding:0in 0in 0in 0in">
                <p class="MsoNormal">
                    <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Good Tyres Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                    </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                    Tel: +84 (61) 3 937 607 / 747 - Fax: +84 (61) 3 937 677&nbsp;</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                    </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.goodtyres.vn"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.goodtyres.vn</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
            </td>
        </tr>
    </tbody>
</table>
<div align="center" class="MsoNormal" style="text-align:center">
    <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
</div>
<p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
    <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
<p class="MsoNormal">
    &nbsp;</p>
<p class="MsoNormal">
    <i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Đặt h&agrave;ng v&agrave; nhận gi&aacute; ưu đ&atilde;i nhất h&atilde;y li&ecirc;n hệ: </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 557 775 </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">- </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 55 99 09</span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><br />
    <span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Email: </span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:cskh@goodtyres.vn">cskh@goodtyres.vn</a> <o:p></o:p></i></p>
';

        $this->send_mail($email,$noidung,$chude,$chuky,$user,$pass,$host);
    }

    public function send_mail($email,$content,$subject,$sign,$user,$pass,$host){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require "lib/class.phpmailer.php";

            $arr = $email;
            $noidung = stripslashes(trim($content));
            $chude = trim($subject);
            $usr = trim($user);
            $pas = trim($pass);
            $hostname = trim($host);
            $chuky = stripslashes(trim($sign));

            $err = array();

            
                // Khai báo tạo PHPMailer
                $mail = new PHPMailer();
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = $hostname; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = $usr; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = $pas; //Mật khẩu của gmail
                $mail->SetFrom($usr, "GoodTyres"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                $mail->AddAddress($arr, $arr);//Email của người nhận
                $mail->Subject = $chude; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                $mail->AddEmbeddedImage('public/images/upload/DC01.jpg', 'DC01');
                $mail->AddEmbeddedImage('public/images/upload/DC02.jpg', 'DC02');
                $mail->AddEmbeddedImage('public/images/upload/DC03.jpg', 'DC03');
                $mail->AddEmbeddedImage('public/images/upload/NC01.jpg', 'NC01');
                $mail->AddEmbeddedImage('public/images/upload/BC01.jpg', 'BC01');
                $mail->AddEmbeddedImage('public/images/upload/BC02.jpg', 'BC02');
                $mail->AddEmbeddedImage('public/images/upload/DC01.jpg', 'DC01');
                $mail->AddEmbeddedImage('public/images/upload/DK01.jpg', 'DK01');
                $mail->AddEmbeddedImage('public/images/upload/DK02.jpg', 'DK02');
                $mail->AddEmbeddedImage('public/images/upload/NK01.jpg', 'NK01');
                $mail->AddEmbeddedImage('public/images/upload/NK02.jpg', 'NK02');
                $mail->AddEmbeddedImage('public/img/favicon.ico', 'favicon');

                $mail->MsgHTML($noidung.$chuky); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = $chude;//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if(!$mail->Send()){
                    $err[] = array(
                        'email' => $arr,
                        'err' => 1,
                    );
                }
                else{
                    $err[] = array(
                        'email' => $arr,
                        'err' => 0,
                    );
                }
                //Tiến hành gửi email và kiểm tra lỗi
                
        }
    }

    public function importsendmail(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $customer = $this->model->get('customertireModel');

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
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();
            
             require "lib/class.phpmailer.php";

                for ($row = 3; $row <= $highestRow; ++ $row) {
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
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[7] != null  ) {

                        
                        $noidung = "
                        <p style='margin:0in;margin-bottom:.0001pt;line-height:18.75pt;background:white'>
                        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>Kính gửi Quý khách hàng</span>
                        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'><br>
                        Nhân dịp Giáng sinh và Năm mới sắp tới, <br>
                        Chúng tôi xin trân trọng gửi lời chúc ấm áp của chúng tôi cho kỳ nghỉ lễ sắp tới và xin chúc bạn và gia đình một Giáng sinh vui vẻ và một năm mới thịnh vượng. Chúng tôi cũng muốn nhân cơ hội này để nói lời cảm ơn đến doanh nghiệp của bạn đã đồng hành cùng chúng tôi trong năm vừa qua và mong muốn được tiếp tục hợp tác trong những năm tới.
                        </span>
                        </p>
                        <p style='mso-margin-top-alt:0in;margin-right:0in;margin-bottom:12.0pt;margin-left:0in;line-height:18.75pt;background:white;BOX-SIZING: border-box !important;MIN-HEIGHT: 1em;MAX-WIDTH: 100%;WORD-WRAP: break-word !important;white-space:pre-wrap;-webkit-text-stroke-width: 0px;word-spacing:0px'>
                        <span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>
                        Merry Christmas and Happy New Year.<br>
                        <img width=697 height=488 id='christmas' src='cid:hinhanh'><br>
                        </span>
                        <b><span style='font-size:10.5pt;font-family:'Arial',sans-serif;color:#3E3E3E'>
                        Best regards,
                        </span></b>
                        <b><span style='font-family:'Arial',sans-serif;color:#3E3E3E'>Sale Team<br></span></b>
                        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#666666'>Mobile: 0931 298 189 - 0933 235 815 - 0937 131 845</span>
                        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#666666'>Hotline: 083 500 9000</span>
                        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#4E8FCD'>Email: 
                        <a href='mailto:lopxe@viet-trade.org'>lopxe@viet-trade.org</a></span>
                        <span style='font-size:11.0pt;font-family:'Arial',sans-serif;color:#4E8FCD'>Website: 
                        <a href='http://www.viet-trade.org'>www.viet-trade.org</a></span>
                        </p>
                        ";

                        

                        // Khai báo tạo PHPMailer
                        $mail = new PHPMailer();
                        //Khai báo gửi mail bằng SMTP
                        $mail->IsSMTP();
                        //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                        // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                        // 1 = Thông báo lỗi ở client
                        // 2 = Thông báo lỗi cả client và lỗi ở server
                        $mail->SMTPDebug  = 0;
                         
                        $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                        $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
                        $mail->Port       = 587; // cổng để gửi mail
                        $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                        $mail->SMTPAuth   = true; //Xác thực SMTP
                        $mail->CharSet = 'UTF-8';
                        $mail->Username   = "lopxe@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
                        $mail->Password   = "lopxe!@#$"; //Mật khẩu của gmail
                        $mail->SetFrom("lopxe@viet-trade.org", "VIET TRADE"); // Thông tin người gửi
                        //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                        $mail->AddAddress(trim($val[7]), trim($val[1]));//Email của người nhận
                        $mail->Subject = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE"; //Tiêu đề của thư
                        $mail->IsHTML(true); // send as HTML   
                        $mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                        $mail->MsgHTML($noidung); //Nội dung của bức thư.
                        // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                        // Gửi thư với tập tin html

                        $mail->AltBody = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                        //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                        // For most clients expecting the Priority header:
                        // 1 = High, 2 = Medium, 3 = Low
                        $mail->Priority = 1;
                        // MS Outlook custom header
                        // May set to "Urgent" or "Highest" rather than "High"
                        $mail->AddCustomHeader("X-MSMail-Priority: High");
                        // Not sure if Priority will also set the Importance header:
                        $mail->AddCustomHeader("Importance: High"); 
                        $mail->Send();
                        //Tiến hành gửi email và kiểm tra lỗi

                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('customertire');
        }
        $this->view->show('customertire/importsendmail');

    }

    public function pdf(){
        require "lib/class.phpmailer.php";
        require("lib/Classes/tcpdf/tcpdf.php");

        $customer_model = $this->model->get('customertireModel');
        $customers = $customer_model->getAllCustomer(array('limit'=>5));

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Ngô Tôn');
        $pdf->SetTitle('Bảng báo giá');
        $pdf->SetSubject('BÁO GIÁ');
        $pdf->SetKeywords('Viet Trade, tire');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $pdf->SetMargins(11, PDF_MARGIN_TOP, 11);

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

        $pdf->SetFont('freeserif', '', 9);
        $pdf->AddPage();

        $left_cell_width = 60;
        $row_height = 6;

        $pdf->Image(BASE_URL . '/public/img/banggia.png', 0, 5, null, 16, null, null, 'N', false, null,'L');
        $pdf->Ln('3');

        $html = '<html>
                <head></head>
                <body><table border="1" cellpadding="5">
                <tr><th>Tên</th>
                <th>số điện thoại</th></tr>
                <tr>
                <td>hello</td>
                <td>xx technologies</td>
                </tr>
                </table>
                </body>
                </html>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = "baogia.pdf";

        $pdf->Output($filename, 'F'); // save the pdf under filename
        
        // Khai báo tạo PHPMailer
        $mail = new PHPMailer();
        //Khai báo gửi mail bằng SMTP
        $mail->IsSMTP();
        //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
        // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
        // 1 = Thông báo lỗi ở client
        // 2 = Thông báo lỗi cả client và lỗi ở server
        $mail->SMTPDebug  = 0;
         
        $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
        $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
        $mail->Port       = 587; // cổng để gửi mail
        $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
        $mail->SMTPAuth   = true; //Xác thực SMTP
        $mail->CharSet = 'UTF-8';
        $mail->Username   = "lopxe@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
        $mail->Password   = "lopxe!@#$"; //Mật khẩu của gmail
        $mail->SetFrom("lopxe@viet-trade.org", "VIET TRADE"); // Thông tin người gửi
        //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

        $pdf_content = file_get_contents($filename);

        $mail->AddAddress('ngoton.it@gmail.com', 'Ngô Tôn');//Email của người nhận
        $mail->Subject = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE"; //Tiêu đề của thư
        $mail->IsHTML(true); // send as HTML   
        $mail->AddStringAttachment($pdf_content, "baogia.pdf", "base64", "application/pdf");  // note second item is name of emailed pdf
        $mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
        $mail->MsgHTML($html); //Nội dung của bức thư.
        // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
        // Gửi thư với tập tin html

        $mail->AltBody = "MERRY CHRISTMAS & HAPPY NEW YEAR - VIET TRADE";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
        //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
        // For most clients expecting the Priority header:
        // 1 = High, 2 = Medium, 3 = Low
        $mail->Priority = 1;
        // MS Outlook custom header
        // May set to "Urgent" or "Highest" rather than "High"
        $mail->AddCustomHeader("X-MSMail-Priority: High");
        // Not sure if Priority will also set the Importance header:
        $mail->AddCustomHeader("Importance: High"); 
        $mail->Send();
        //Tiến hành gửi email và kiểm tra lỗi

        unlink($filename); // this will delete the file off of server

    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $customer = $this->model->get('customertireModel');

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
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[0] != null  ) {

                        
                            if(!$customer->getCustomerByWhere(array('customer_name'=>trim($val[0])))) {
                                $customer_data = array(
                                'customer_name' => trim($val[0]),
                                'company_name' => trim($val[1]),
                                'co_name' => trim($val[0]),
                                'mst' => trim($val[2]),
                                'customer_address' => trim($val[3]),
                                'customer_phone' => trim($val[4]),
                                );
                                $customer->createCustomer($customer_data);
                            }
                            else if($customer->getCustomerByWhere(array('customer_name'=>trim($val[0])))){
                                $id_customer = $customer->getCustomerByWhere(array('customer_serie'=>trim($val[0])))->customer_id;
                                $customer_data = array(
                                'company_name' => trim($val[1]),
                                'co_name' => trim($val[0]),
                                'mst' => trim($val[2]),
                                'customer_address' => trim($val[3]),
                                'customer_phone' => trim($val[4]),
                                );
                                $customer->updateCustomer($customer_data,array('customer_id' => $id_customer));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('customer');
        }
        $this->view->show('customertire/import');

    }

    public function send_mail_auto($email,$content,$subject,$sign,$user,$pass,$host,$images=array()){
        
            //require "lib/class.phpmailer.php";

            $arr = $email;
            $noidung = stripslashes(trim($content));
            $chude = trim($subject);
            $usr = trim($user);
            $pas = trim($pass);
            $hostname = trim($host);
            $chuky = stripslashes(trim($sign));
            $hinhanh = $images;

            $err = array();

            
                // Khai báo tạo PHPMailer
                $mail = new PHPMailer();
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = $hostname; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = $usr; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = $pas; //Mật khẩu của gmail
                $mail->SetFrom($usr, "Việt Trade"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                $mail->AddAddress($arr, $arr);//Email của người nhận
                $mail->Subject = $chude; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   

                foreach ($hinhanh as $key => $value) {
                    $mail->AddEmbeddedImage($hinhanh['link'], $hinhanh['ten']);
                }

                $mail->MsgHTML($noidung.$chuky); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = $chude;//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if(!$mail->Send()){
                    $err[] = array(
                        'email' => $arr,
                        'err' => 1,
                    );
                }
                else{
                    $err[] = array(
                        'email' => $arr,
                        'err' => 0,
                    );
                }
                //Tiến hành gửi email và kiểm tra lỗi
                
        
    }

    public function gio_to_hung_vuong(){
        $event = $this->model->get('eventmailModel');
        $customer = $this->model->get('customertireModel');
        $ngaybatdau = strtotime('11-04-2016'); // Mung 5 thang 3
        $ngayketthuc = strtotime('16-04-2016'); // Mung 10 thang 3

        $data = array(
            'where' => 'customer_tire_email IS NOT NULL AND customer_tire_email != "" AND customer_tire_mst NOT IN (SELECT mst FROM customer WHERE mst IS NOT NULL AND customer_mst != "") AND customer_tire_id NOT IN (SELECT customer FROM event_mail WHERE event=2 AND event_mail_date>= '.$ngaybatdau.' AND event_mail_date<= '.$ngayketthuc.')'
        );
        $customers = $customer->getAllCustomer($data);

        $this->send_mail_auto();
    }

    public function happy_monday(){
        $event = $this->model->get('eventmailModel');
        $customer = $this->model->get('customertireModel');
        $nam = date('Y');
        $ngay = date('d-m-Y');
        $tuan = (int)date('W',strtotime($ngay));

        $mang = $this->getStartAndEndDate($tuan,$nam);
        $batdau = strtotime($mang[0]);
        $ketthuc = strtotime($mang[1]);

        $data = array(
            'where' => 'customer_tire_email IS NOT NULL AND customer_tire_email != "" AND customer_tire_mst NOT IN (SELECT mst FROM customer WHERE mst IS NOT NULL AND customer_mst != "") AND customer_tire_id NOT IN (SELECT customer FROM event_mail WHERE event=1 AND event_mail_date>= '.$batdau.' AND event_mail_date<= '.$ketthuc.')'
        );
        $customers = $customer->getAllCustomer($data);

        $hinhanh = array(
            'link' => array(
                'public/images/upload/DC01.jpg',
                'public/images/upload/DC02.jpg',
                'public/images/upload/DC03.jpg',
                'public/images/upload/NC01.jpg',
                'public/images/upload/BC01.jpg',
                'public/images/upload/BC02.jpg',
                'public/images/upload/DK01.jpg',
                'public/images/upload/DK02.jpg',
                'public/images/upload/NK01.jpg',
                'public/images/upload/NK02.jpg',
                ),
            'ten' => array(
                'DC01',
                'DC02',
                'DC03',
                'NC01',
                'BC01',
                'BC02',
                'DK01',
                'DK02',
                'NK01',
                'NK02',
                ),
        );

        
        $chude = 'CẬP NHẬT MỚI NHẤT VỂ THỊ TRƯỜNG';
        $user = 'lopxe@viet-trade.org';
        $pass = 'lopxe!@#$';
        $host = 'smtp.zoho.com';
        $kt1 = 'ms,ms.,chi,chị,c ,c.';
        $kt2 = 'mr,mr.,anh,a , a.';

        $xungho = 'anh/chị';

        

        
        $banggia = '<table border="1" cellpadding="1" cellspacing="0" style="border-collapse:
         collapse;width:411pt" width="100%">
        <colgroup>
            <col style="width: 41pt; text-align: center;" width="54" />
            <col style="width: 182pt; text-align: center;" width="442" />
            <col style="width: 52pt; text-align: center;" width="69" />
            <col style="width: 43pt; text-align: center;" width="57" />
            <col style="width: 93pt; text-align: center;" width="124" />
        </colgroup>
        <tbody>
            <tr height="20" style="height:15.0pt">
                <td class="xl69" height="20" style="height: 15pt; width: 41pt; text-align: center;" width="54">
                    <strong>M&atilde; gai</strong></td>
                <td class="xl70" style="border-left-style: none; width: 182pt; text-align: center;" width="242">
                    <strong>H&igrave;nh ảnh</strong></td>
                <td class="xl70" style="border-left-style: none; width: 52pt; text-align: center;" width="69">
                    <strong>K&iacute;ch cỡ</strong></td>
                <td class="xl69" style="border-left-style: none; width: 43pt; text-align: center;" width="57">
                    <strong>Lớp bố</strong></td>
                <td class="xl70" style="border-left-style: none; width: 93pt; text-align: center;" width="124">
                    <strong>Đơn gi&aacute;</strong></td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                    DC01</td>
                <td align="left" height="180" rowspan="9" style="height:135.0pt;width:182pt" valign="top" width="242">
                    <p style="text-align: center;">
    <!--[endif]-->              </p>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="xl72" height="180" rowspan="9" style="height: 135pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                    <p>
                                        <img height="178" src="cid:DC01" width="233" /></p>
                                    <p>
                                        <img height="178" src="cid:DC02" width="233" /></p>
                                    <p>
                                        <img height="178" src="cid:DC03" width="233" /></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    6.50R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    12PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1,930,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    7.00R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    14PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,030,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    7.50R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,240,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                    DC02</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    8.25R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,475,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    8.25R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,978,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    9.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,641,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                    DC03</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    10.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,940,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    11.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,183,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,749,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl73" height="160" rowspan="8" style="height: 120pt; border-top-style: none; text-align: center;">
                    NC01</td>
                <td align="left" height="160" rowspan="8" style="height:120.0pt;width:182pt" valign="top" width="242">
                    <p style="text-align: center;">
    <!--[endif]-->              </p>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="xl72" height="160" rowspan="8" style="height: 120pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                    <img height="178" src="cid:NC01" width="233" /></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    7.00R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    14PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,120,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    7.50R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,330,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    8.25R16</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2,502,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    8.25R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,078,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    9.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,750,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    10.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,940,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    11.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,435,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,836,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl73" height="40" rowspan="2" style="height: 30pt; border-top-style: none; text-align: center;">
                    BC01</td>
                <td align="left" height="40" rowspan="2" style="height:30.0pt;width:182pt" valign="top" width="242">
                    <p style="text-align: center;">
    <!--[endif]-->              </p>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="xl72" height="40" rowspan="2" style="height: 30pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                    <img height="178" src="cid:BC01" width="233" /></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    11.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,448,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,027,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl73" height="40" rowspan="2" style="height: 30pt; border-top-style: none; text-align: center;">
                    BC02</td>
                <td class="xl72" rowspan="2" style="border-top-style: none; text-align: center;">
                    <img height="178" src="cid:BC02" width="233" /></td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    11.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,110,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12.00R20</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    18PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5,358,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                    DK01<br />
                    DK02</td>
                <td class="xl72" rowspan="3" style="border-top-style: none; text-align: center;">
                    <img height="178" src="cid:DK01" width="233" /><img height="178" src="cid:DK02" width="233" /></td>
                <td class="xl68" style="border-top-style: none; border-left-style: none; text-align: center;">
                    295/75R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,648,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    11R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,718,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,044,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl71" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 41pt; text-align: center;" width="54">
                    NK01<br />
                    NK02</td>
                <td align="left" height="60" rowspan="3" style="height:45.0pt;width:182pt" valign="top" width="242">
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td height="0" style="text-align: center;" width="0">
                                    &nbsp;</td>
                                <td style="text-align: center;" width="99">
                                    &nbsp;</td>
                                <td style="text-align: center;" width="7">
                                    &nbsp;</td>
                                <td style="text-align: center;" width="100">
                                    &nbsp;</td>
                            </tr>
                            <tr>
                                <td height="1" style="text-align: center;">
                                    &nbsp;</td>
                                <td colspan="2" style="text-align: center;">
                                    <img height="178" src="cid:NK01" width="233" /></td>
                            </tr>
                            <tr>
                                <td height="55" style="text-align: center;">
                                    &nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="text-align: center;">
    <!--[endif]-->              </p>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="xl72" height="60" rowspan="3" style="height: 45pt; border-top-style: none; width: 182pt; text-align: center;" width="242">
                                    <img height="178" src="cid:NK02" width="233" /></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="xl68" style="border-top-style: none; border-left-style: none; text-align: center;">
                    295/75R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,781,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    11R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3,930,000</td>
            </tr>
            <tr height="20" style="height:15.0pt">
                <td class="xl66" height="20" style="height: 15pt; border-top-style: none; border-left-style: none; text-align: center;">
                    12R22.5</td>
                <td class="xl66" style="border-top-style: none; border-left-style: none; text-align: center;">
                    16PR</td>
                <td class="xl67" style="border-top-style: none; border-left-style: none; text-align: center;">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4,097,000</td>
            </tr>
        </tbody>
    </table>';

        $chuky = '<div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
            <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                <strong><span style="color:#f00;">IRIS TON</span></strong></p>
            <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                <span style="color:#808080;"><em>Bussiness Development Manager</em></span></p>
        </div>
        <div align="center" class="MsoNormal" style="text-align: center; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial;background-clip: initial; background-position: initial; background-repeat: initial;">
            <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
        </div>
        <p>
            &nbsp;</p>
        <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
            <tbody>
                <tr>
                    <td style="width:131.25pt;padding:0in 0in 0in 0in" valign="top" width="175">
                        <p class="MsoNormal">
        <!--[if gte vml 1]><v:shapetype  id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f"><v:stroke joinstyle="miter"></v:stroke><v:formulas><v:f eqn="if lineDrawn pixelLineWidth 0"></v:f><v:f eqn="sum @0 1 0"></v:f><v:f eqn="sum 0 0 @1"></v:f><v:f eqn="prod @2 1 2"></v:f><v:f eqn="prod @3 21600 pixelWidth"></v:f><v:f eqn="prod @3 21600 pixelHeight"></v:f><v:f eqn="sum @0 0 1"></v:f><v:f eqn="prod @6 1 2"></v:f><v:f eqn="prod @7 21600 pixelWidth"></v:f><v:f eqn="sum @8 21600 0"></v:f><v:f eqn="prod @7 21600 pixelHeight"></v:f><v:f eqn="sum @10 21600 0"></v:f></v:formulas><v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"></v:path><o:lock v:ext="edit" aspectratio="t"></o:lock></v:shapetype><v:shape id="Picture_x0020_1" o:spid="_x0000_i1027" type="#_x0000_t75" style="width:151.5pt;height:45.75pt;visibility:visible;mso-wrap-style:square"><v:imagedata src=http://viet-trade.org/public/images/1.png o:title=""></v:imagedata></v:shape><![endif]--><!--[if !vml]-->                   <span new="" style="font-size: 12pt; font-family: " times=""><img height="61" src="http://viet-trade.org/public/images/1.png" v:shapes="Picture_x0020_1" width="202" /><!--[endif]--><o:p></o:p></span></p>
                    </td>
                    <td style="padding:0in 0in 0in 0in">
                        <p class="MsoNormal">
                            <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Viet Trade Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                            </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                            Tel: +84 (61) 3 937 607 / 747 - Fax: +84 (61) 3 937 677 - Hotline: +84 (8) 3 500 9000</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                            </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.viet-trade.org"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.viet-trade.org</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p>
            &nbsp;</p>
        <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
        </table>
        <div align="center" class="MsoNormal" style="text-align:center">
            <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
        </div>
        <p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
            <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
        <p class="MsoNormal">
            &nbsp;</p>
        <p class="MsoNormal">
            <i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Đặt h&agrave;ng v&agrave; nhận gi&aacute; ưu đ&atilde;i nhất h&atilde;y li&ecirc;n hệ:</span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp;</span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 55 99 09&nbsp;</span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">-&nbsp;</span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 557 775<br />
            </span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Email:&nbsp;</span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:it@viet-trade.org">it@viet-trade.org</a>&nbsp;-</span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp;</span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:carl@viet-trade.org">carl@viet-trade.org</a></span></i></p>
        ';


        $email = $data['customer_tire_email'];
        $ten = str_replace('ms.', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('ms', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('mr.', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('mr', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('anh', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('a ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('a. ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('chị', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('chi', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('c ', '', strtolower($data['customer_tire_contact']));
        $ten = str_replace('c. ', '', strtolower($data['customer_tire_contact']));

        $arr=explode(',',$kt1);
        foreach($arr as $st)
        {
            if(strpos(strtolower($data['customer_tire_contact']),$st)!==false)
            {
              $xungho = 'chị';
              break;
            }
        }  
        
        $arr=explode(',',$kt2);
        foreach($arr as $st)
        {
            if(strpos(strtolower($data['customer_tire_contact']),$st)!==false)
            {
              $xungho = 'anh';
              break;
            }
        } 


        $noidung = '<p>Chào '.$xungho.' '.$ten.',</p>
        <p>
            Em thay mặt công ty Việt Trade - <b><span style="font-family: Consolas; color: black;">NHÀ NHẬP KHẨU VÀ PHÂN PHỐI TRỰC TIẾP </span></b><b><span style="font-family: Consolas; color: red;">LỐP XE TẢI BỐ KẼM.</span></b></p>
        <p>
            Xin phép được gửi tới '.$xungho.' những thông tin mới nhất về thị trường lốp xe. Với sự biến động liên tục của thị trường vận tải và giá cao su thế giới, mặt hàng phụ tùng ôtô cũng biến động theo.</p>
        <p>
            Em xin gửi '.$xungho.' bảng giá lốp xe mới nhất tới thời điểm hiện tại để tham khảo qua.</p>
        <p>
            Hi vọng những thông tin này có giá trị với '.$xungho.'.</p>
        <p>
            Chúc '.$xungho.' một ngày tốt lành.</p>
        <p></p>';

        require "lib/class.phpmailer.php";

        $this->send_mail_auto($email, $noidung, $chude, $chuky, $user, $pass, $host, $hinhanh);
    }
    function getStartAndEndDate($week, $year)
    {
        $week_start = new DateTime();
        $week_start->setISODate($year,$week);
        $return[0] = $week_start->format('d-m-Y');
        $time = strtotime($return[0], time());
        $time += 6*24*3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }


}
?>