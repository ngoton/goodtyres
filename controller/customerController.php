<?php
Class customerController Extends baseController {
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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $id = $this->registry->router->param_id;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('order_by'=>'staff_name ASC'));
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data[$staff->account] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;
        $this->view->data['staffs'] = $staffs;

        $district_model = $this->model->get('districtModel');
        $districts = $district_model->getAllDistrict();

        $district_datas = array();
        foreach ($districts as $district) {
            $district_datas[$district->district_id]['name'] = $district->district_name;
        }
        $this->view->data['district_datas'] = $district_datas;
        $this->view->data['districts'] = $districts;

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $customer_model = $this->model->get('customerModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND customer_create_user = '.$_SESSION['userid_logined'];
        }

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_id = '.$id;
        }
        
        $tongsodong = count($customer_model->getAllCustomer($data));
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
            'where' => '1=1',
            );
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND customer_create_user = '.$_SESSION['userid_logined'];
        }

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND customer_id = '.$id;
        }
        
        if ($keyword != '') {
            $search = ' AND ( customer_name LIKE "%'.$keyword.'%" 
                            OR mst LIKE "%'.$keyword.'%" 
                            OR customer_code LIKE "%'.$keyword.'%" 
                            OR customer_address LIKE "%'.$keyword.'%" 
                            OR REPLACE(customer_phone, " ", "") LIKE "%'.str_replace(" ", "", $keyword).'%" 
                            OR customer_email LIKE "%'.$keyword.'%" 
                            )';
            $data['where'] .= $search;
        }
        
        
        
        $this->view->data['customers'] = $customer_model->getAllCustomer($data);

        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->customer_id)?$customer_model->getLastCustomer()->customer_id:0;
        
        $this->view->show('customer/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $customer = $this->model->get('customerModel');
            $contact_person_model = $this->model->get('contactpersonModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $data = array(
                        
                        'customer_name' => trim($_POST['customer_name']),
                        'customer_address' => trim($_POST['customer_address']),
                        'customer_phone' => trim($_POST['customer_phone']),
                        'customer_email' => trim($_POST['customer_email']),
                        'company_name' => trim($_POST['company_name']),
                        'co_name' => trim($_POST['co_name']),
                        'mst' => trim($_POST['mst']),
                        'customer_bank_name' => trim($_POST['customer_bank_name']),
                        'account_number' => trim($_POST['account_number']),
                        'cmg_bank' => trim($_POST['cmg_bank']),
                        'customer_expect_date' => trim($_POST['customer_expect_date']),
                        'customer_after_date' => trim($_POST['customer_after_date']),
                        'director' => trim($_POST['director']),
                        'customer_fax' => trim($_POST['customer_fax']),
                        'customer_street' => trim($_POST['customer_street']),
                        'customer_ward' => trim($_POST['customer_ward']),
                        'customer_district' => trim($_POST['customer_district']),
                        'customer_city' => trim($_POST['customer_city']),
                        'customer_province' => trim($_POST['customer_province']),
                        'customer_code' => trim($_POST['customer_code']),
                        'customer_contact' => trim($_POST['customer_contact']),
                        'company_phone' => trim($_POST['company_phone']),
                        'customer_tire_type' => trim($_POST['customer_tire_type']),
                        'customer_agent_link' => trim($_POST['customer_agent_link']),
                        'customer_create_user' => trim($_POST['customer_create_user']),
                        'customer_status' => trim($_POST['customer_status']),
                        );
            if ($_POST['yes'] != "") {
                $data['customer_update_user'] = $_SESSION['userid_logined'];
                $data['customer_update_time'] = time();
                //var_dump($data);
                if ($data['mst'] != "" && $data['mst'] > 0) {
                    if ($customer->getAllCustomerByWhere($_POST['yes'].' AND mst = '.trim($_POST['mst']))) {
                        echo "Thông tin khách hàng đã tồn tại";
                        return false;
                    }
                }
                if ($customer->getAllCustomerByWhere($_POST['yes'].' AND customer_code = '.trim($_POST['customer_code']))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                if ($customer->getAllCustomerByWhere($_POST['yes'].' AND customer_name = '.trim($_POST['customer_name']))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
                        if ($customer->getCustomerByWhere(array('customer_id'=>$_POST['yes'],'customer_create_user'=>$_SESSION['userid_logined']))) {
                            $customer->updateCustomer($data,array('customer_id' => $_POST['yes']));
                            echo "Cập nhật thành công";

                            $id_customer = $_POST['yes'];

                            $order_tire_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$id_customer));
                            $tire_sale_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$id_customer));

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|customer|".implode("-",$data)."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $customer->updateCustomer($data,array('customer_id' => $_POST['yes']));

                        $id_customer = $_POST['yes'];
                        echo "Cập nhật thành công";

                        $order_tire_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$id_customer));
                        $tire_sale_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$id_customer));

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|customer|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    
                }
                
            }
            else{
                //$data['customer_create_user'] = $_SESSION['userid_logined'];
                $data['customer_create_time'] = date('m/Y');
                //$data['customer'] = $_POST['customer'];
                //var_dump($data);
                if ($data['mst'] != "" && $data['mst'] > 0) {
                    if ($customer->getCustomerByWhere(array('mst'=>trim($_POST['mst'])))) {
                        echo "Thông tin khách hàng đã tồn tại";
                        return false;
                    }
                }
                if ($customer->getCustomerByWhere(array('customer_code'=>trim($_POST['customer_code'])))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                if ($customer->getCustomerByWhere(array('customer_name'=>trim($_POST['customer_name'])))) {
                    echo "Thông tin khách hàng đã tồn tại";
                    return false;
                }
                else{
                    $customer->createCustomer($data);

                    $id_customer = $customer->getLastCustomer()->customer_id;
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_customer."|customer|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }

            $contact_person = $_POST['contact_person'];
            if (isset($id_customer)) {
                foreach ($contact_person as $v) {
                    $data_contact_person = array(
                        'contact_person_name' => trim($v['contact_person_name']),
                        'contact_person_phone' => trim($v['contact_person_phone']),
                        'contact_person_mobile' => trim($v['contact_person_mobile']),
                        'contact_person_email' => trim($v['contact_person_email']),
                        'contact_person_birthday' => strtotime($v['contact_person_birthday']),
                        'contact_person_address' => trim($v['contact_person_address']),
                        'contact_person_position' => trim($v['contact_person_position']),
                        'contact_person_department' => trim($v['contact_person_department']),
                        'customer' => $id_customer,
                    );

                    if ($v['id_contact_person']>0) {
                        $contact_person_model->updateCustomer($data_contact_person,array('contact_person_id'=>$v['id_contact_person']));
                    }
                    else{
                        if ($data_contact_person['contact_person_name']!="") {
                            $contact_person_model->createCustomer($data_contact_person);
                        }
                        
                    }

                }
            }
                    
        }
    }
    public function deletecontact(){
        if (isset($_POST['data'])) {
            $contact_person = $this->model->get('contactpersonModel');

            $contact_person->queryCustomer('DELETE FROM contact_person WHERE contact_person_id='.$_POST['data'].' AND customer='.$_POST['customer']);
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = $this->model->get('customerModel');
            $contact_person_model = $this->model->get('contactpersonModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
                        if ($customer->getCustomerByWhere(array('customer_id'=>$data,'customer_create_user'=>$_SESSION['userid_logined']))) {
                            $contact_person_model->queryCustomer('DELETE FROM contact_person WHERE customer='.$data);
                            $customer->deleteCustomer($data);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|customer|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $contact_person_model->queryCustomer('DELETE FROM contact_person WHERE customer='.$data);
                        $customer->deleteCustomer($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|customer|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                    
                }
                return true;
            }
            else{
                    if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
                        if ($customer->getCustomerByWhere(array('customer_id'=>$_POST['data'],'customer_create_user'=>$_SESSION['userid_logined']))) {
                            $contact_person_model->queryCustomer('DELETE FROM contact_person WHERE customer='.$_POST['data']);
                            $customer->deleteCustomer($_POST['data']);
                            echo "Xóa thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|customer|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);
                        }
                        else{
                            echo "Bạn không có quyền thực hiện thao tác này";
                            return false;
                        }
                    }
                    else{
                        $contact_person_model->queryCustomer('DELETE FROM contact_person WHERE customer='.$_POST['data']);
                        $customer->deleteCustomer($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|customer|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
            }
            
        }
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

            $customer = $this->model->get('customerModel');

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
        $this->view->show('customer/import');

    }
    public function getcontact(){
        if(isset($_POST['customer'])){

            $contact_person_model = $this->model->get('contactpersonModel');
            $contacts = $contact_person_model->getAllCustomer(array('where'=>'customer='.$_POST['customer']));
            

            $str = "";

            if (!$contacts) {
                $str .= '<tr>
                  <td><input type="checkbox" name="chk"></td>
                  <td>
                      <table style="width:100%">
                          <tr>
                              <td>Họ tên</td>
                              <td>
                                  <input type="text" class="contact_person_name" name="contact_person_name[]"  tabindex="22">
                              </td>
                              <td>
                                  Di động
                              </td>
                              <td>
                                  <input type="text" class="contact_person_mobile numbers" name="contact_person_mobile[]" tabindex="23" >
                              </td>
                              <td>
                                  SĐT
                              </td>
                              <td>
                                  <input type="text" class="contact_person_phone numbers" name="contact_person_phone[]" tabindex="24" >
                              </td>
                            </tr>
                            <tr>
                              
                              <td>
                                  Email
                              </td>
                              <td>
                                  <input type="text" class="contact_person_email" name="contact_person_email[]" tabindex="25" >
                              </td>
                              <td>
                                  Ngày sinh
                              </td>
                              <td>
                                  <input type="text" class="contact_person_birthday ngay" name="contact_person_birthday[]" tabindex="26" >
                              </td>
                              <td>Địa chỉ</td>
                              <td>
                                  <input type="text" class="contact_person_address" name="contact_person_address[]" tabindex="27" >
                              </td>
                          </tr>
                          <tr>
                            
                              <td>Chức vụ</td>
                              <td>
                                  <input type="text" class="contact_person_position" name="contact_person_position[]" tabindex="28" >
                              </td>
                            
                              <td>Bộ phận</td>
                              <td>
                                  <input type="text" class="contact_person_department" name="contact_person_department[]" tabindex="29" >
                              </td>
                          </tr>
                      </table>
                  </td>
              </tr>';
            }
            else{
                foreach ($contacts as $contact) {
                    $str .= '<tr>
                      <td><input type="checkbox" name="chk" data="'.$contact->contact_person_id.'" title="'.$contact->customer.'"></td>
                      <td>
                          <table style="width:100%">
                              <tr>
                                  <td>Họ tên</td>
                                  <td>
                                      <input type="text" class="contact_person_name" name="contact_person_name[]"  tabindex="22" data="'.$contact->contact_person_id.'" value="'.$contact->contact_person_name.'">
                                  </td>
                                  <td>
                                      Di động
                                  </td>
                                  <td>
                                      <input type="text" class="contact_person_mobile numbers" name="contact_person_mobile[]" tabindex="23" value="'.$contact->contact_person_mobile.'" >
                                  </td>
                                  <td>
                                      SĐT
                                  </td>
                                  <td>
                                      <input type="text" class="contact_person_phone numbers" name="contact_person_phone[]" tabindex="24" value="'.$contact->contact_person_phone.'" >
                                  </td>
                                </tr>
                                <tr>
                                  
                                  <td>
                                      Email
                                  </td>
                                  <td>
                                      <input type="text" class="contact_person_email" name="contact_person_email[]" tabindex="25" value="'.$contact->contact_person_email.'" >
                                  </td>
                                  <td>
                                      Ngày sinh
                                  </td>
                                  <td>
                                      <input type="text" class="contact_person_birthday ngay" name="contact_person_birthday[]" tabindex="26" value="'.date('d-m-Y',$contact->contact_person_birthday).'" >
                                  </td>
                                  <td>Địa chỉ</td>
                                  <td>
                                      <input type="text" class="contact_person_address" name="contact_person_address[]" tabindex="27" value="'.$contact->contact_person_address.'" >
                                  </td>
                              </tr>
                              <tr>
                                
                                  <td>Chức vụ</td>
                                  <td>
                                      <input type="text" class="contact_person_position" name="contact_person_position[]" tabindex="28" value="'.$contact->contact_person_position.'" >
                                  </td>
                                
                                  <td>Bộ phận</td>
                                  <td>
                                      <input type="text" class="contact_person_department" name="contact_person_department[]" tabindex="29" value="'.$contact->contact_person_department.'" >
                                  </td>
                              </tr>
                          </table>
                      </td>
                  </tr>';
              }
            }

            echo $str;
        }
    }
    public function getCustomer($id){
        return $this->getByID($this->table,$id);
    }

    public function getcustomerinfo(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_sale_model = $this->model->get('tiresaleModel');
            $customer_model = $this->model->get('customerModel');
            $company = trim($_POST['company']);
            $mst = trim($_POST['mst']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $type = trim($_POST['type']);
            $staff = trim($_POST['staff']);
            $customer_name = trim($_POST['customer_name']);
            $customer = trim($_POST['customer']);
            $address = trim($_POST['address']);
            $contact = trim($_POST['contact']);

            $arr = array(
                'company'=>$company,
                'mst'=>$mst,
                'phone'=>$phone,
                'email'=>$email,
                'type'=>$type,
                'staff'=>$staff,
                'customer_name'=>$customer_name,
                'customer'=>$customer,
                'address'=>$address,
                'contact'=>$contact,
            );

            if ($company != "") {
                $customers = $customer_model->getCustomerByWhere(array('company_name'=>$company));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($mst != "") {
                $customers = $customer_model->getCustomerByWhere(array('mst'=>$mst));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($phone != "") {
                $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>$phone));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($email != "") {
                $customers = $customer_model->getCustomerByWhere(array('customer_email'=>$email));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }

            echo json_encode($arr);
        }
    }


}
?>