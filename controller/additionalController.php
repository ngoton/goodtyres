<?php
Class additionalController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Phát sinh';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $code = "";
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'additional_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $trangthai = "";
            $code = $this->registry->router->addition;
        }
        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $id = $this->registry->router->param_id;


        $additional_model = $this->model->get('additionalModel');

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;


        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($kt),
        );

        if ($id>0) {
            $data['where'] = 'additional_id = '.$id;
        }

        if ($trangthai > 0) {
            $account_choose = $account_model->getAccount($trangthai);
            if ($account_choose->account_parent > 0) {
                $data['where'] .= ' AND (debit = '.$trangthai.' OR credit = '.$trangthai.')';
            }
            else{
                $data['where'] .= ' AND ((debit = '.$trangthai.' OR credit = '.$trangthai.') OR (debit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') OR credit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') ))';
            }
        }
        
        if ($code != "") {
            $data['where'] = 'code = "'.$code.'"';
        }
        
        $tongsodong = count($additional_model->getAllAdditional($data));
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
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($kt),
            );
        
        if ($id>0) {
            $data['where'] = 'additional_id = '.$id;
        }

        if ($trangthai > 0) {
            $account_choose = $account_model->getAccount($trangthai);
            if ($account_choose->account_parent > 0) {
                $data['where'] .= ' AND (debit = '.$trangthai.' OR credit = '.$trangthai.')';
            }
            else{
                $data['where'] .= ' AND ((debit = '.$trangthai.' OR credit = '.$trangthai.') OR (debit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') OR credit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') ))';
            }
        }

        if ($code != "") {
            $data['where'] = 'code = "'.$code.'"';
        }
      
        if ($keyword != '') {
            $search = '( document_number LIKE "%'.$keyword.'%" 
                    OR additional_comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money LIKE "%'.$keyword.'%" 
                    OR debit IN (SELECT account_id FROM account WHERE account_number LIKE "%'.$keyword.'%" ) 
                    OR credit IN (SELECT account_id FROM account WHERE account_number LIKE "%'.$keyword.'%" ) 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $additionals = $additional_model->getAllAdditional($data);

        
        $this->view->data['additionals'] = $additionals;

        $order_model = $this->model->get('ordertireModel');
        $customer_model = $this->model->get('customerModel');
        $cus = array();
        foreach ($additionals as $add) {
            if ($add->code!="") {
                if ($order_model->getTireByWhere(array('order_number'=>$add->code))) {
                    $cus[$add->additional_id] = $customer_model->getCustomer($order_model->getTireByWhere(array('order_number'=>$add->code))->customer)->customer_name;
                }
            }
            
        }
        $this->view->data['cus'] = $cus;

        $this->view->data['lastID'] = isset($additional_model->getLastAdditional()->additional_id)?$additional_model->getLastAdditional()->additional_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('additional/index');
    }
    public function viewadditional() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Phát sinh';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $code = "";
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'additional_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
            $trangthai = "";
            $code = $this->registry->router->addition;
        }
        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $trangthai = $this->registry->router->param_id;


        $additional_model = $this->model->get('additionalModel');

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
        }
        $this->view->data['account_parents'] = $account_parents;
        $this->view->data['account_data'] = $account_data;


        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($kt),
        );


        if ($trangthai > 0) {
            $account_choose = $account_model->getAccount($trangthai);
            if ($account_choose->account_parent > 0) {
                $data['where'] .= ' AND (debit = '.$trangthai.' OR credit = '.$trangthai.')';
            }
            else{
                $data['where'] .= ' AND ((debit = '.$trangthai.' OR credit = '.$trangthai.') OR (debit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') OR credit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') ))';
            }
        }
        
        if ($code != "") {
            $data['where'] = 'code = "'.$code.'"';
        }
        
        $tongsodong = count($additional_model->getAllAdditional($data));
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
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'additional_date >= '.strtotime($batdau).' AND additional_date < '.strtotime($kt),
            );
        

        if ($trangthai > 0) {
            $account_choose = $account_model->getAccount($trangthai);
            if ($account_choose->account_parent > 0) {
                $data['where'] .= ' AND (debit = '.$trangthai.' OR credit = '.$trangthai.')';
            }
            else{
                $data['where'] .= ' AND ((debit = '.$trangthai.' OR credit = '.$trangthai.') OR (debit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') OR credit IN (SELECT account_id FROM account WHERE account_parent = '.$trangthai.') ))';
            }
        }

        if ($code != "") {
            $data['where'] = 'code = "'.$code.'"';
        }
      
        if ($keyword != '') {
            $search = '( document_number LIKE "%'.$keyword.'%" 
                    OR additional_comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money LIKE "%'.$keyword.'%" 
                    OR debit IN (SELECT account_id FROM account WHERE account_number LIKE "%'.$keyword.'%" ) 
                    OR credit IN (SELECT account_id FROM account WHERE account_number LIKE "%'.$keyword.'%" ) 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $additionals = $additional_model->getAllAdditional($data);

        
        $this->view->data['additionals'] = $additionals;

        $order_model = $this->model->get('ordertireModel');
        $customer_model = $this->model->get('customerModel');
        $cus = array();
        foreach ($additionals as $add) {
            if ($add->code!="") {
                if ($order_model->getTireByWhere(array('order_number'=>$add->code))) {
                    $cus[$add->additional_id] = $customer_model->getCustomer($order_model->getTireByWhere(array('order_number'=>$add->code))->customer)->customer_name;
                }
            }
            
        }
        $this->view->data['cus'] = $cus;

        $this->view->data['lastID'] = isset($additional_model->getLastAdditional()->additional_id)?$additional_model->getLastAdditional()->additional_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('additional/index');
    }

    public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            $agent_model = $this->model->get('agentModel');
            $agent_manifest_model = $this->model->get('agentmanifestModel');
            $invoice_model = $this->model->get('invoiceModel');
            $order_tire_model = $this->model->get('ordertireModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $sale_report_model->getAllSale();
                $list_agent = $agent_model->getAllAgent();
                $list_agentmanifest = $agent_manifest_model->getAllAgent();
                $list_invoice = $invoice_model->getAllInvoice();
                $list_order = $order_tire_model->getAllTire();
            }
            else{
                $data_sale = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_sale = $sale_report_model->getAllSale($data_sale);

                $data_agent = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_agent = $agent_model->getAllAgent($data_agent);

                $data_agentmanifest = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_agentmanifest = $agent_manifest_model->getAllAgent($data_agentmanifest);

                $data_invoice = array(
                'where'=>'( invoice_number LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_invoice = $invoice_model->getAllInvoice($data_invoice);

                $data_order = array(
                'where'=>'( order_number LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list_order = $order_tire_model->getAllTire($data_order);
            }

            
            foreach ($list_sale as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_agent as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->name.")".'</li>';
            }
            foreach ($list_agentmanifest as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_invoice as $rs) {
                // put in bold the written text
                $code = $rs->invoice_number;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->invoice_number);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->invoice_number.'\',\''.$rs->invoice_number.'\')">'.$code." (".$rs->comment.")".'</li>';
            }
            foreach ($list_order as $rs) {
                // put in bold the written text
                $code = $rs->order_number;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->order_number);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->order_number.'\',\''.$rs->order_number.'\')">'.$code.'</li>';
            }
        }
    }
   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $data = array(
                        
                        'document_number' => trim($_POST['document_number']),
                        'document_date' => strtotime(trim($_POST['document_date'])),
                        'additional_date' => strtotime(trim($_POST['additional_date'])),
                        'additional_comment' => trim($_POST['additional_comment']),
                        'debit' => trim($_POST['debit']),
                        'credit' => trim($_POST['credit']),
                        'code' => trim($_POST['code']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        );
            
            $data_debit = array(
                'account_balance_date' => $data['additional_date'],
                'account' => $data['debit'],
                'money' => $data['money'],
                'week' => (int)date('W', $data['additional_date']),
                'year' => (int)date('Y', $data['additional_date']),
            );
            $data_credit = array(
                'account_balance_date' => $data['additional_date'],
                'account' => $data['credit'],
                'money' => (0-$data['money']),
                'week' => (int)date('W', $data['additional_date']),
                'year' => (int)date('Y', $data['additional_date']),
            );

            if($data_debit['week'] == 53){
                $data_debit['week'] = 1;
                $data_debit['year'] = $data_debit['year']+1;

                $data_credit['week'] = 1;
                $data_credit['year'] = $data_credit['year']+1;
            }
            if (((int)date('W', $data['additional_date']) == 1) && ((int)date('m', $data['additional_date']) == 12) ) {
                $data_debit['year'] = (int)date('Y', $data['additional_date'])+1;
                $data_credit['year'] = (int)date('Y', $data['additional_date'])+1;
            }

            if ($_POST['yes'] != "") {
                    $add = $additional_model->getAdditional(trim($_POST['yes']));

                    $additional_model->updateAdditional($data,array('additional_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $account_balance_model->updateAccount($data_debit,array('additional'=>trim($_POST['yes']),'account'=>$add->debit));
                    $account_balance_model->updateAccount($data_credit,array('additional'=>trim($_POST['yes']),'account'=>$add->credit));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|additional|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    $additional_model->createAdditional($data);
                    echo "Thêm thành công";

                    $id_additional = $additional_model->getLastAdditional()->additional_id;
                    $data_debit['additional'] = $id_additional;
                    $data_credit['additional'] = $id_additional;

                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);
                

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$additional_model->getLastAdditional()->additional_id."|additional|".implode("-",$data)."\n"."\r\n";
                    
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
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $additional_model->deleteAdditional($data);
                       $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|additional|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $additional_model->deleteAdditional($_POST['data']);
                        $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|additional|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] > 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $account = $this->model->get('accountModel');
            $additional = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');

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

            

            for ($row = 2; $row <= $highestRow; ++ $row) {
                $val = array();
                for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                    $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                    // Check if cell is merged
                    foreach ($objWorksheet->getMergeCells() as $cells) {
                        if ($cell->isInRange($cells)) {
                            $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                            $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                            if ($col == 1) {
                                $y++;
                            }
                            
                            break;
                            
                        }
                    }

                    $val[] = $cell->getCalculatedValue();
                    //here's my prob..
                    //echo $val;
                }


                if ($val[3] != null && $val[6] != null && $val[6] > 0 ) {
                    
                    $ngay = PHPExcel_Shared_Date::ExcelToPHP(trim($val[1]));                                      
                    //$ngay = $ngay-3600;

                    $no_id = null;
                    $co_id = null;

                    if (trim($val[4]) != null) {
                        $parents = $account->getAccountByWhere(array('account_number'=>trim($val[4])));
                        if ($parents) {
                            $no_id = $parents->account_id;
                        }
                        else{
                            if (is_numeric(trim($val[4]))) {
                                $acc = array(
                                    'account_number'=>trim($val[4]),
                                    'account_parent'=>0,
                                );
                                $account->createAccount($acc);
                                $no_id = $account->getLastAccount()->account_id;
                            }
                            else{
                                $ac = substr(trim($val[4]), 0, strpos(trim($val[4]), '_'));
                                $acc_parent = $account->getAccountByWhere(array('account_number'=>$ac));
                                $acc = array(
                                    'account_number'=>trim($val[4]),
                                    'account_parent'=>$acc_parent->account_id,
                                );
                                $account->createAccount($acc);
                                $no_id = $account->getLastAccount()->account_id;
                            }
                        }
                    }
                    if (trim($val[5]) != null) {
                        $parents = $account->getAccountByWhere(array('account_number'=>trim($val[5])));
                        if ($parents) {
                            $co_id = $parents->account_id;
                        }
                        else{
                            if (is_numeric(trim($val[5]))) {
                                $acc = array(
                                    'account_number'=>trim($val[5]),
                                    'account_parent'=>0,
                                );
                                $account->createAccount($acc);
                                $co_id = $account->getLastAccount()->account_id;
                            }
                            else{
                                $ac = substr(trim($val[5]), 0, strpos(trim($val[5]), '_'));
                                $acc_parent = $account->getAccountByWhere(array('account_number'=>$ac));
                                $acc = array(
                                    'account_number'=>trim($val[5]),
                                    'account_parent'=>$acc_parent->account_id,
                                );
                                $account->createAccount($acc);
                                $co_id = $account->getLastAccount()->account_id;
                            }
                        }
                    }


                    if (!$additional->getAdditionalByWhere(array('additional_date'=>$ngay,'debit'=>$no_id,'credit'=>$co_id,'money'=>trim($val[6]),'code'=>trim($val[7])))) {
                        $additional_data = array(
                            'document_number' => trim($val[0]),
                            'document_date' => $ngay,
                            'additional_date' => $ngay,
                            'additional_comment' => trim($val[3]),
                            'debit' => $no_id,
                            'credit' => $co_id,
                            'money' => trim($val[6]),
                            'code' => trim($val[7]),
                            );

                        $additional->createAdditional($additional_data);

                        $data_debit = array(
                            'account_balance_date' => $additional_data['additional_date'],
                            'account' => $additional_data['debit'],
                            'money' => $additional_data['money'],
                            'week' => (int)date('W', $additional_data['additional_date']),
                            'year' => (int)date('Y', $additional_data['additional_date']),
                        );
                        $data_credit = array(
                            'account_balance_date' => $additional_data['additional_date'],
                            'account' => $additional_data['credit'],
                            'money' => (0-$additional_data['money']),
                            'week' => (int)date('W', $additional_data['additional_date']),
                            'year' => (int)date('Y', $additional_data['additional_date']),
                        );

                        if($data_debit['week'] == 53){
                            $data_debit['week'] = 1;
                            $data_debit['year'] = $data_debit['year']+1;

                            $data_credit['week'] = 1;
                            $data_credit['year'] = $data_credit['year']+1;
                        }
                        if (((int)date('W', $additional_data['additional_date']) == 1) && ((int)date('m', $additional_data['additional_date']) == 12) ) {
                            $data_debit['year'] = (int)date('Y', $additional_data['additional_date'])+1;
                            $data_credit['year'] = (int)date('Y', $additional_data['additional_date'])+1;
                        }

                        $id_additional = $additional->getLastAdditional()->additional_id;
                        $data_debit['additional'] = $id_additional;
                        $data_credit['additional'] = $id_additional;

                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);

                    }
                    else{
                        $add = $additional->getAdditionalByWhere(array('additional_date'=>$ngay,'debit'=>$no_id,'credit'=>$co_id,'money'=>trim($val[6]),'code'=>trim($val[7])));
                        $id_additional = $add->additional_id;

                        $additional_data = array(
                            'document_number' => trim($val[0]),
                            'document_date' => $ngay,
                            'additional_date' => $ngay,
                            'additional_comment' => trim($val[3]),
                            'debit' => $no_id,
                            'credit' => $co_id,
                            'money' => trim($val[6]),
                            'code' => trim($val[7]),
                            );

                            $additional->updateAdditional($additional_data,array('additional_id' => $id_additional));

                        $data_debit = array(
                            'account_balance_date' => $additional_data['additional_date'],
                            'account' => $additional_data['debit'],
                            'money' => $additional_data['money'],
                            'week' => (int)date('W', $additional_data['additional_date']),
                            'year' => (int)date('Y', $additional_data['additional_date']),
                        );
                        $data_credit = array(
                            'account_balance_date' => $additional_data['additional_date'],
                            'account' => $additional_data['credit'],
                            'money' => (0-$additional_data['money']),
                            'week' => (int)date('W', $additional_data['additional_date']),
                            'year' => (int)date('Y', $additional_data['additional_date']),
                        );

                        if($data_debit['week'] == 53){
                            $data_debit['week'] = 1;
                            $data_debit['year'] = $data_debit['year']+1;

                            $data_credit['week'] = 1;
                            $data_credit['year'] = $data_credit['year']+1;
                        }
                        if (((int)date('W', $additional_data['additional_date']) == 1) && ((int)date('m', $additional_data['additional_date']) == 12) ) {
                            $data_debit['year'] = (int)date('Y', $additional_data['additional_date'])+1;
                            $data_credit['year'] = (int)date('Y', $additional_data['additional_date'])+1;
                        }

                        $account_balance_model->updateAccount($data_debit,array('additional'=>$id_additional,'account'=>$add->debit));
                        $account_balance_model->updateAccount($data_credit,array('additional'=>$id_additional,'account'=>$add->credit));
                    }
                    
                }
                


            }
            return $this->view->redirect('additional');
        }
        $this->view->show('additional/import');

    }

    

}
?>