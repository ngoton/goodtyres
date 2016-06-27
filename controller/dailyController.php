<?php
Class dailyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Thu chi ';

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
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'daily_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
        }

        
        $daily_model = $this->model->get('dailyModel');

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
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
        );
        
        
        $tongsodong = count($daily_model->getAllDaily($data));
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'daily_date >= '.strtotime($batdau).' AND daily_date <= '.strtotime($ketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( note LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money_in LIKE "%'.$keyword.'%" 
                    OR money_out LIKE "%'.$keyword.'%" 
                    OR account LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['dailys'] = $daily_model->getAllDaily($data);
        $this->view->data['lastID'] = isset($daily_model->getLastDaily()->daily_id)?$daily_model->getLastDaily()->daily_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('daily/index');
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
            
            $daily_model = $this->model->get('dailyModel');
            $additional_model = $this->model->get('additionalModel');

            $data = array(
                        
                        'service' => trim($_POST['service']),
                        'owner' => trim($_POST['owner']),
                        'note' => trim($_POST['note']),
                        'account' => trim($_POST['account']),
                        'daily_date' => strtotime(trim($_POST['daily_date'])),
                        'comment' => trim($_POST['comment']),
                        'debit' => trim($_POST['debit']),
                        'credit' => trim($_POST['credit']),
                        'code' => trim($_POST['code']),
                        'money_in' => trim(str_replace(',','',$_POST['money_in'])),
                        'money_out' => trim(str_replace(',','',$_POST['money_out'])),
                        );
            

            if ($_POST['yes'] != "") {

                if ($data['debit'] > 0 || $data['credit'] > 0) {
                    $add = $additional_model->getAdditionalByWhere(array('daily'=>trim($_POST['yes'])));
                    $data_add = array(
                            'additional_date' => $data['daily_date'],
                            'additional_comment' => $data['comment'],
                            'debit' => $data['debit'],
                            'credit' => $data['credit'],
                            'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? $data['money_out']:null),
                            'code' => $data['code'],
                            'daily' => trim($_POST['yes']),
                        );

                    $data_debit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['debit'],
                        'money' => $data_add['money'],
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['credit'],
                        'money' => (0-$data_add['money']),
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );

                    if($data_debit['week'] == 53){
                        $data_debit['week'] = 1;
                        $data_debit['year'] = $data_debit['year']+1;

                        $data_credit['week'] = 1;
                        $data_credit['year'] = $data_credit['year']+1;
                    }
                    if (((int)date('W', $data_add['additional_date']) == 1) && ((int)date('m', $data_add['additional_date']) == 12) ) {
                        $data_debit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                        $data_credit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                    }

                    if ($add) {
                        $additional_model->updateAdditional($data_add,array('additional_id'=>$add->additional_id));

                        $account_balance_model->updateAccount($data_debit,array('additional'=>$add->additional_id,'account'=>$add->debit));
                        $account_balance_model->updateAccount($data_credit,array('additional'=>$add->additional_id,'account'=>$add->credit));
                    }
                    else{
                        $additional_model->createAdditional($data_add);

                        $id_additional = $additional_model->getLastAdditional()->additional_id;
                        $data_debit['additional'] = $id_additional;
                        $data_credit['additional'] = $id_additional;

                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);
                    }
                }
                
                    $daily_model->updateDaily($data,array('daily_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|daily|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    $daily_model->createDaily($data);
                    echo "Thêm thành công";

                if ($data['debit'] > 0 || $data['credit'] > 0) {
                    $data_add = array(
                            'additional_date' => $data['daily_date'],
                            'additional_comment' => $data['comment'],
                            'debit' => $data['debit'],
                            'credit' => $data['credit'],
                            'money' => $data['money_in'] > 0 ? $data['money_in'] : ($data['money_out'] > 0 ? $data['money_out']:null),
                            'code' => $data['code'],
                            'daily' => $daily_model->getLastDaily()->daily_id,
                        );
                    
                    $additional_model->createAdditional($data_add);

                    $data_debit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['debit'],
                        'money' => $data_add['money'],
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_add['additional_date'],
                        'account' => $data_add['credit'],
                        'money' => (0-$data_add['money']),
                        'week' => (int)date('W', $data_add['additional_date']),
                        'year' => (int)date('Y', $data_add['additional_date']),
                    );

                    if($data_debit['week'] == 53){
                        $data_debit['week'] = 1;
                        $data_debit['year'] = $data_debit['year']+1;

                        $data_credit['week'] = 1;
                        $data_credit['year'] = $data_credit['year']+1;
                    }
                    if (((int)date('W', $data_add['additional_date']) == 1) && ((int)date('m', $data_add['additional_date']) == 12) ) {
                        $data_debit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                        $data_credit['year'] = (int)date('Y', $data_add['additional_date'])+1;
                    }

                    $id_additional = $additional_model->getLastAdditional()->additional_id;
                    $data_debit['additional'] = $id_additional;
                    $data_credit['additional'] = $id_additional;

                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);
                    
                }

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$daily_model->getLastDaily()->daily_id."|daily|".implode("-",$data)."\n"."\r\n";
                    
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
            $daily_model = $this->model->get('dailyModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $daily_model->deleteDaily($data);
                       $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$data));
                       foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }
                       
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|daily|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $daily_model->deleteDaily($_POST['data']);
                        $additionals = $additional_model->getAllAdditional(array('where'=>'daily = '.$_POST['data']));
                       foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                       }

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|daily|"."\n"."\r\n";
                        
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

            $daily = $this->model->get('dailyModel');

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

            $nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = $day[0]."-".(strlen($day[1]) < 2 ? "0".$day[1] : $day[1] )."-".$day[2] ;
            
            $ngay = strtotime($ngaythang);

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            

            for ($row = 4; $row <= $highestRow; ++ $row) {
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


                if ($val[2] != null && $val[8] != null ) {
                    
                    $service = trim($val[5]);
                    $service = $service=="Hành chính"?1:($service=="Lốp xe"?2:($service=="Logistics"?3:null));

                    $daily_data = array(
                        'daily_date' => $ngay,
                        'code'=> trim($val[1]),
                        'comment' => trim($val[2]),
                        'money_in' => trim($val[3]),
                        'money_out' => trim($val[4]),
                        'service' => $service,
                        'owner' => trim($val[6]),
                        'note' => trim($val[7]),
                        'account' => trim($val[8]),
                        );

                    $daily->createDaily($daily_data);
                    
                    
                }
                


            }
            return $this->view->redirect('daily');
        }
        $this->view->show('daily/import');

    }

    

}
?>