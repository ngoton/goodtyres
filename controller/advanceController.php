<?php
Class advanceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đề nghị tạm ứng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'advance_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngay = date('d-m-Y');
            $batdau = "";
        }

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $join = array('table'=>'bank, staff','where'=>'bank.bank_id = advance.source AND staff.staff_id = advance.staff');

        $advance_model = $this->model->get('advanceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        if ($batdau != "") {
            $data['where'] .= ' AND week = '.$batdau;
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 7) {
            $data['where'] .= ' AND staff.account = '.$_SESSION['userid_logined'];
        }
        
        $tongsodong = count($advance_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );
        if ($batdau != "") {
            $data['where'] .= ' AND week = '.$batdau;
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7) {
            $data['where'] .= ' AND staff.account = '.$_SESSION['userid_logined'];
        }
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR username LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['advances'] = $advance_model->getAllCosts($data,$join);
        $this->view->data['lastID'] = isset($advance_model->getLastCosts()->advance_id)?$advance_model->getLastCosts()->advance_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('advance/index');
    }

   public function approve(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $advance = $this->model->get('advanceModel');
            //$advance_data = $advance->getCosts($_POST['data']);

            $data = array(
                        
                        'approve' => $_SESSION['userid_logined'],
                        );
          
            $advance->updateCosts($data,array('advance_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approve2(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $advance = $this->model->get('advanceModel');
            //$advance_data = $advance->getCosts($_POST['data']);

            $data = array(
                        
                        'approve2' => $_SESSION['userid_logined'],
                        );
          
            $advance->updateCosts($data,array('advance_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }
    public function approve3(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $advance = $this->model->get('advanceModel');
            //$advance_data = $advance->getCosts($_POST['data']);

            $data = array(
                        
                        'approve3' => $_SESSION['userid_logined'],
                        );
          
            $advance->updateCosts($data,array('advance_id' => $_POST['data']));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."approve"."|".$_POST['data']."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

    public function pay(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $advance = $this->model->get('advanceModel');
            $advance_data = $advance->getCosts($_POST['data']);

            $data = array(
                        
                        'pay_date' => strtotime(trim($_POST['pay_date'])),
                        'pay_money' => $advance_data->pay_money + trim(str_replace(',','',$_POST['money'])),
                        'source' => $_POST['source'],
                        );
          
            $advance->updateCosts($data,array('advance_id' => $_POST['data']));

            if ($advance_data->check_trading != 1) {
                $receivable = $this->model->get('receivableModel');
            
                $data_receivable = array(
                    'staff' => $advance_data->staff,
                    'money' => trim(str_replace(',','',$_POST['money'])),
                    'receivable_date' => $data['pay_date'],
                    'expect_date' => $advance_data->expect_date,
                    'week' => (int)date('W',$data['pay_date']),
                    'comment' => $advance_data->comment,
                    'create_user' => $_SESSION['userid_logined'],
                    'year' => (int)date('Y',$data['pay_date']),
                    'type' => 5,
                    'source' => $data['source'],
                );

                $receivable->createCosts($data_receivable);
            

                $assets_model = $this->model->get('assetsModel');
                $data_asset = array(
                            'bank' => $data['source'],
                            'total' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'assets_date' => $data['pay_date'],
                            'advance' => $_POST['data'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                        );
                if($data_asset['week'] == 53){
                    $data_asset['week'] = 1;
                    $data_asset['year'] = $data_asset['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $assets_model->createAssets($data_asset);

                $pay_model = $this->model->get('payModel');
                $data_pay = array(
                            'source' => $data['source'],
                            'money' => trim(str_replace(',','',$_POST['money'])),
                            'pay_date' => $data['pay_date'],
                            'advance' => $_POST['data'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                        );
                if($data_pay['week'] == 53){
                    $data_pay['week'] = 1;
                    $data_pay['year'] = $data_pay['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_pay['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $pay_model->createCosts($data_pay);

                $staff_debt_model = $this->model->get('staffdebtModel');
                $data_staff_debt = array(
                            'staff' => $advance_data->staff,
                            'source' => $data['source'],
                            'money' => trim(str_replace(',','',$_POST['money'])),
                            'staff_debt_date' => $data['pay_date'],
                            'comment' => $advance_data->comment,
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'status' => 1,
                        );
                if($data_staff_debt['week'] == 53){
                    $data_staff_debt['week'] = 1;
                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_staff_debt['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $staff_debt_model->createCost($data_staff_debt);
            }
            else{
                $receivable = $this->model->get('receivableModel');
            
                $receivable_data = $receivable->getCostsByWhere(array('staff'=>$advance_data->staff,'money'=>$advance_data->money,'receivable_date'=>$advance_data->advance_date));

                $data_receivable = array(
                    'staff' => $receivable_data->staff,
                    'money' => $receivable_data->money - trim(str_replace(',','',$_POST['money'])),
                    'receivable_date' => $receivable_data->receivable_date,
                    'expect_date' => $receivable_data->expect_date,
                    'week' => $receivable_data->week,
                    'comment' => $receivable_data->comment,
                    'create_user' => $receivable_data->create_user,
                    'year' => $receivable_data->year,
                    'type' => 5,
                    'source' => $receivable_data->source,
                );

                $receivable->updateCosts($data_receivable,array('receivable_id'=>$receivable_data->receivable_id));

                $assets_model = $this->model->get('assetsModel');
                $data_asset = array(
                            'bank' => $data['source'],
                            'total' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'assets_date' => $data['pay_date'],
                            'advance' => $_POST['data'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                        );
                if($data_asset['week'] == 53){
                    $data_asset['week'] = 1;
                    $data_asset['year'] = $data_asset['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $assets_model->createAssets($data_asset);

                $pay_model = $this->model->get('payModel');
                $data_pay = array(
                            'source' => $data['source'],
                            'money' => trim(str_replace(',','',$_POST['money'])),
                            'pay_date' => $data['pay_date'],
                            'advance' => $_POST['data'],
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                        );
                if($data_pay['week'] == 53){
                    $data_pay['week'] = 1;
                    $data_pay['year'] = $data_pay['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_pay['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $pay_model->createCosts($data_pay);

                $staff_debt_model = $this->model->get('staffdebtModel');
                $data_staff_debt = array(
                            'staff' => $advance_data->staff,
                            'source' => $data['source'],
                            'money' => 0 - trim(str_replace(',','',$_POST['money'])),
                            'staff_debt_date' => $data['pay_date'],
                            'comment' => $advance_data->comment,
                            'week' => (int)date('W',$data['pay_date']),
                            'year' => (int)date('Y',$data['pay_date']),
                            'status' => 2,
                        );
                if($data_staff_debt['week'] == 53){
                    $data_staff_debt['week'] = 1;
                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                }
                if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                    $data_staff_debt['year'] = (int)date('Y',$data['pay_date'])+1;
                }

                $staff_debt_model->createCost($data_staff_debt);
            }
            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pay"."|".$_POST['data']."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
        }
    }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $staffs = $this->model->get('staffModel');
            $staff = $staffs->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
            $advance = $this->model->get('advanceModel');
            $data = array(
                        'advance_date' => strtotime(date('d-m-Y')),
                        'comment' => trim($_POST['comment']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'expect_date' => strtotime(trim($_POST['expect_date'])),
                        'week' => (int)date('W', strtotime(trim($_POST['expect_date']))),
                        'staff' => $staff->staff_id,
                        'source' => 1,
                        'year' => (int)date('Y'),
                        );
            if($data['week'] == 53){
                $data['week'] = 1;
                $data['year'] = $data['year']+1;
            }
            if (((int)date('W', strtotime(trim($_POST['expect_date']))) == 1) && ((int)date('m',$data['expect_date']) == 12) ) {
                $data['year'] = (int)date('Y')+1;
            }

            if ($_POST['yes'] != "") {
                


                    $advance->updateCosts($data,array('advance_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|advance|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $advance->createCosts($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$advance->getLastCosts()->advance_id."|advance|".implode("-",$data)."\n"."\r\n";
                        
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
            $advance = $this->model->get('advanceModel');
            $assets_model = $this->model->get('assetsModel');
            $pay_model = $this->model->get('payModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $advance->deleteCosts($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE advance = '.$data);
                       $pay_model->queryCosts('DELETE FROM pay WHERE advance = '.$data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $advance->deleteCosts($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE advance = '.$_POST['data']);
                       $pay_model->queryCosts('DELETE FROM pay WHERE advance = '.$_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|advance|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $advance = $this->model->get('advanceModel');
            $staff = $this->model->get('staffModel');
            $bank = $this->model->get('bankModel');
            $assets_model = $this->model->get('assetsModel');
            $staff_debt_model = $this->model->get('staffdebtModel');

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
                    if ($val[1] != null && $val[2] != null ) {

                            if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[1])))) {
                                $staff_data = array(
                                'staff_name' => trim($val[1]),
                                
                                );
                                $staff->createStaff($staff_data);
                                $id_staff = $staff->getLastStaff()->staff_id;
                            }
                            else if($staff->getStaffByWhere(array('staff_name'=>trim($val[1])))){
                                $id_staff = $staff->getStaffByWhere(array('staff_name'=>trim($val[1])))->staff_id;
                                
                            }

                            if(!$staff->getStaffByWhere(array('staff_name'=>trim($val[3])))) {
                                $staff_data = array(
                                'staff_name' => trim($val[1]),
                                
                                );
                                $staff->createStaff($staff_data);
                                $id_approve = $staff->getLastStaff()->staff_id;
                            }
                            else if($staff->getStaffByWhere(array('staff_name'=>trim($val[3])))){
                                $id_approve = $staff->getStaffByWhere(array('staff_name'=>trim($val[3])))->staff_id;
                                
                            }

                            $id_approve2 = $staff->getStaffByWhere(array('staff_name'=>trim('Trịnh Thị Thân')))->staff_id;
                            $id_approve3 = $staff->getStaffByWhere(array('staff_name'=>trim('Nancy')))->staff_id;

                            $advance_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[4]));                                      
                            $advance_date = $advance_date-3600;

                            $expect_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[5]));                                      
                            $expect_date = $expect_date-3600;

                            if(!$bank->getBankByWhere(array('bank_name'=>trim($val[6])))) {
                                $bank_data = array(
                                'bank_name' => trim($val[6]),
                                
                                );
                                $bank->createBank($bank_data);
                                $id_bank = $bank->getLastBank()->bank_id;
                            }
                            else if($bank->getBankByWhere(array('bank_name'=>trim($val[6])))){
                                $id_bank = $bank->getBankByWhere(array('bank_name'=>trim($val[6])))->bank_id;
                                
                            }

                            if(!$advance->getCostsByWhere(array('staff'=>$id_staff,'advance_date'=>$advance_date))) {
                                $advance_data = array(
                                'staff' => $id_staff,
                                'money' => trim($val[2]),
                                'approve' => $id_approve,
                                'approve2' => $id_approve2,
                                'approve3' => $id_approve3,
                                'advance_date' => $advance_date,
                                'expect_date' => $expect_date,
                                'week' => (int)date('W',$expect_date),
                                'source' => $id_bank,
                                'comment' => trim($val[7]),
                                );
                                $advance->createCosts($advance_data);


                                $data_asset = array(
                                            'bank' => $advance['source'],
                                            'total' => 0 - trim($val[2]),
                                            'assets_date' => $data['pay_date'],
                                            'advance' => $advance_date,
                                            'week' => (int)date('W',$advance_date),
                                            'year' => (int)date('Y',$advance_date),
                                        );
                                if($data_asset['week'] == 53){
                                    $data_asset['week'] = 1;
                                    $data_asset['year'] = $data_asset['year']+1;
                                }
                                if (((int)date('W',$advance_date) == 1) && ((int)date('m',$advance_date) == 12) ) {
                                    $data_asset['year'] = (int)date('Y',$advance_date)+1;
                                }

                                $assets_model->createAssets($data_asset);

                                
                                $data_staff_debt = array(
                                            'staff' => $id_staff,
                                            'source' => $advance['source'],
                                            'money' => $advance['money'],
                                            'staff_debt_date' => $advance_date,
                                            'comment' => $advance['comment'],
                                            'week' => (int)date('W',$advance_date),
                                            'year' => (int)date('Y',$advance_date),
                                            'status' => 1,
                                        );
                                if($data_staff_debt['week'] == 53){
                                    $data_staff_debt['week'] = 1;
                                    $data_staff_debt['year'] = $data_staff_debt['year']+1;
                                }
                                if (((int)date('W',$advance_date) == 1) && ((int)date('m',$advance_date) == 12) ) {
                                    $data_staff_debt['year'] = (int)date('Y',$advance_date)+1;
                                }

                                $staff_debt_model->createCost($data_staff_debt);

                            }
                            else if($advance->getCostsByWhere(array('staff'=>$id_staff,'advance_date'=>$advance_date))){
                                $id_advance = $advance->getCostsByWhere(array('staff'=>$id_staff,'advance_date'=>$advance_date))->advance_id;
                                $advance_data = array(
                                'staff' => $id_staff,
                                'money' => trim($val[2]),
                                'approve' => $id_approve,
                                'approve2' => $id_approve2,
                                'approve3' => $id_approve3,
                                'advance_date' => $advance_date,
                                'expect_date' => $expect_date,
                                'week' => (int)date('W',$expect_date),
                                'source' => $id_bank,
                                'comment' => trim($val[7]),
                                );
                                $advance->updateCosts($advance_data,array('advance_id' => $id_advance));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('advance');
        }
        $this->view->show('advance/import');

    }

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>