<?php
Class secController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8 && $_SESSION['role_logined']!=10) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Rút sec';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sec_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
        }
        
        $sec_cost_model = $this->model->get('seccostModel');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        $sec_model = $this->model->get('secModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'sec_date >= '.strtotime($batdau).' AND sec_date <= '.strtotime($ketthuc),
        );
        
        $tongsodong = count($sec_model->getAllCosts($data));
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

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'sec_date >= '.strtotime($batdau).' AND sec_date <= '.strtotime($ketthuc),
            );
      
        if ($keyword != '') {
            $search = '( sec_comment LIKE "%'.$keyword.'%" 
                OR sec_money LIKE "%'.$keyword.'%" 
                OR sec_code LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $secs = $sec_model->getAllCosts($data);

        $sec_data = array();

        foreach ($secs as $sec) {
            $sec_costs = $sec_cost_model->getAllCosts(array('where'=>'sec='.$sec->sec_id));
            foreach ($sec_costs as $sec_cost) {
                $sec_data[$sec->sec_id][] = $sec_cost;
            }
        }

        $this->view->data['sec_datas'] = $sec_data;

        $this->view->data['secs'] = $secs;
        $this->view->data['lastID'] = isset($sec_model->getLastCosts()->sec_id)?$sec_model->getLastCosts()->sec_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('sec/index');
    }

   
    
   
   public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            $agent_model = $this->model->get('agentModel');
            $agent_manifest_model = $this->model->get('agentmanifestModel');
            $invoice_model = $this->model->get('invoiceModel');

            if ($_POST['keyword'] == "*") {
                $list_sale = $sale_report_model->getAllSale();
                $list_agent = $agent_model->getAllAgent();
                $list_agentmanifest = $agent_manifest_model->getAllAgent();
                $list_invoice = $invoice_model->getAllInvoice();
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
            }

            
            foreach ($list_sale as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code.'</li>';
            }
            foreach ($list_agent as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code.'</li>';
            }
            foreach ($list_agentmanifest as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\',\''.$rs->code.'\')">'.$code.'</li>';
            }
            foreach ($list_invoice as $rs) {
                // put in bold the written text
                $code = $rs->invoice_number;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->invoice_number);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->invoice_number.'\',\''.$rs->invoice_number.'\')">'.$code.'</li>';
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $assets_model = $this->model->get('assetsModel');
            $sec = $this->model->get('secModel');
            $data = array(
                        'sec_date' => strtotime(trim($_POST['sec_date'])),
                        'sec_comment' => trim($_POST['sec_comment']),
                        'sec_money' => trim(str_replace(',','',$_POST['sec_money'])),
                        'bank_out' => trim($_POST['bank_out']),
                        );
            
            if ($_POST['yes'] != "") {
                
                    $costs_data = $sec->getCosts($_POST['yes']);

                    /*$data_asset = array(
                            'bank' => $data['bank_out'],
                            'total' => 0 - $data['sec_money'],
                            'assets_date' => $data['sec_date'],
                            'sec' => $sec->getLastCosts()->sec_id,
                            'week' => (int)date('W',$data['sec_date']),
                            'year' => (int)date('Y',$data['sec_date']),
                        );
                    if($data_asset['week'] == 53){
                        $data_asset['week'] = 1;
                        $data_asset['year'] = $data_asset['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $assets_model->updateAssets($data_asset,array('sec'=>$_POST['yes']));

                    $data_asset = array(
                            'bank' => $data['bank_in'],
                            'total' => $data['sec_money'],
                            'assets_date' => $data['sec_date'],
                            'sec' => $sec->getLastCosts()->sec_id,
                            'week' => (int)date('W',$data['sec_date']),
                            'year' => (int)date('Y',$data['sec_date']),
                        );
                    if($data_asset['week'] == 53){
                        $data_asset['week'] = 1;
                        $data_asset['year'] = $data_asset['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $assets_model->updateAssets($data_asset,array('sec'=>$_POST['yes']));*/

                    
                    $sec->updateCosts($data,array('sec_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|sec|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    $sec->createCosts($data);

                    /*$data_asset = array(
                            'bank' => $data['bank_out'],
                            'total' => 0 - $data['sec_money'],
                            'assets_date' => $data['sec_date'],
                            'sec' => $sec->getLastCosts()->sec_id,
                            'week' => (int)date('W',$data['sec_date']),
                            'year' => (int)date('Y',$data['sec_date']),
                        );
                    if($data_asset['week'] == 53){
                        $data_asset['week'] = 1;
                        $data_asset['year'] = $data_asset['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $assets_model->createAssets($data_asset);

                    $data_asset = array(
                            'bank' => $data['bank_in'],
                            'total' => $data['sec_money'],
                            'assets_date' => $data['sec_date'],
                            'sec' => $sec->getLastCosts()->sec_id,
                            'week' => (int)date('W',$data['sec_date']),
                            'year' => (int)date('Y',$data['sec_date']),
                        );
                    if($data_asset['week'] == 53){
                        $data_asset['week'] = 1;
                        $data_asset['year'] = $data_asset['year']+1;
                    }
                    if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                        $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
                    }

                    $assets_model->createAssets($data_asset);*/


                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sec->getLastCosts()->sec_id."|sec|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }
    public function addsec(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $sec = $this->model->get('seccostModel');
            $data = array(
                        'sec_cost_date' => strtotime(trim($_POST['sec_cost_date'])),
                        'sec_cost_comment' => trim($_POST['sec_cost_comment']),
                        'sec_cost_money' => trim(str_replace(',','',$_POST['sec_cost_money'])),
                        'sec_cost_bank' => trim($_POST['sec_cost_bank']),
                        'sec_cost_type' => trim($_POST['sec_cost_type']),
                        'sec_code' => trim($_POST['sec_code']),
                        'sec' => trim($_POST['sec']),
                        );
            
            if ($_POST['yes'] != "") {
                
                    $costs_data = $sec->getCosts($_POST['yes']);

                    $sec->updateCosts($data,array('sec_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|sec_cost|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                    $sec->createCosts($data);

                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$sec->getLastCosts()->sec_cost_id."|sec_cost|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sec = $this->model->get('secModel');
            $sec_cost = $this->model->get('seccostModel');
            $assets_model = $this->model->get('assetsModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $costs_data = $sec->getCosts($data);

                       $sec->deleteCosts($data);
                       //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$data);
                       $sec_cost->queryCosts('DELETE FROM sec_cost WHERE sec = '.$data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|sec|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $costs_data = $sec->getCosts($_POST['data']);

                        $sec->deleteCosts($_POST['data']);
                        //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$_POST['data']);
                        $sec_cost->queryCosts('DELETE FROM sec_cost WHERE sec = '.$_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|sec|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }
    public function deletecost(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sec = $this->model->get('seccostModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $costs_data = $sec->getCosts($data);

                       $sec->deleteCosts($data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|sec_cost|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $costs_data = $sec->getCosts($_POST['data']);

                        $sec->deleteCosts($_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|sec_cost|"."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $sec = $this->model->get('secModel');
            $bank = $this->model->get('bankModel');
            $assets_model = $this->model->get('assetsModel');
            $sec_cost = $this->model->get('seccostModel');

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
            $sec_id = 0;
                for ($row = 8; $row <= $highestRow; ++ $row) {
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
                    if ($val[0] != null) {

                            

                            $sec_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[0]));                                      
                            $sec_date = $sec_date-3600;

                            if ($val[1] != null && $val[3] != null) {
                                $id_bank_out = $bank->getBankByWhere(array('bank_name'=>trim($val[3])))->bank_id;

                                if(!$sec->getCostsByWhere(array('bank_out'=>$id_bank_out,'sec_money'=>trim($val[1]),'sec_date'=>$sec_date))) {
                                    $sec_data = array(
                                    'sec_money' => trim($val[1]),
                                    'sec_date' => $sec_date,
                                    'bank_out' => $id_bank_out,
                                    'sec_comment' => trim($val[5]),
                                    );


                                    $sec->createCosts($sec_data);

                                    $sec_id = $sec->getLastCosts()->sec_id;

                                }
                                else if($sec->getCostsByWhere(array('bank_out'=>$id_bank_out,'sec_money'=>trim($val[1]),'sec_date'=>$sec_date))) {
                                    $id_sec = $sec->getCostsByWhere(array('bank_out'=>$id_bank_out,'sec_money'=>trim($val[1]),'sec_date'=>$sec_date))->sec_id;
                                    $sec_data = array(
                                    'sec_money' => trim($val[1]),
                                    'sec_date' => $sec_date,
                                    'bank_out' => $id_bank_out,
                                    'sec_comment' => trim($val[5]),
                                    );
                                    $sec->updateCosts($sec_data,array('sec_id' => $id_sec));

                                    $sec_id = $id_sec;
                                }
                            }

                            if ($val[2] != null && $val[3] == null && $val[6] != null) {
                                $id_bank_in = $bank->getBankByWhere(array('bank_name'=>trim($val[2])))->bank_id;

                                if (trim($val[4]) == "Hành chính" || trim($val[4]) == "HC") {
                                    $mang = 1;
                                }
                                else if (trim($val[4]) == "Sale") {
                                    $mang = 2;
                                }
                                else if (trim($val[4]) == "Trading") {
                                    $mang = 3;
                                }
                                else if (trim($val[4]) == "Agent") {
                                    $mang = 4;
                                }
                                else if (trim($val[4]) == "TCMT") {
                                    $mang = 5;
                                }

                                if (!$sec_cost->getCostsByWhere(array('sec'=>$sec_id,'sec_cost_bank'=>$id_bank_in,'sec_cost_date'=>$sec_date,'sec_cost_money'=>$val[6]))) {
                                    $sec_data = array(
                                        'sec_cost_money' => trim($val[6]),
                                        'sec_cost_date' => $sec_date,
                                        'sec_cost_bank' => $id_bank_in,
                                        'sec_cost_comment' => trim($val[5]),
                                        'sec_cost_type' => $mang,
                                        'sec' => $sec_id,
                                        );

                                        $sec_cost->createCosts($sec_data);
                                }
                                else if ($sec_cost->getCostsByWhere(array('sec'=>$sec_id,'sec_cost_bank'=>$id_bank_in,'sec_cost_date'=>$sec_date,'sec_cost_money'=>$val[6]))) {
                                    $id_sec_cost = $sec_cost->getCostsByWhere(array('sec'=>$sec_id,'sec_cost_bank'=>$id_bank_in,'sec_cost_date'=>$sec_date,'sec_cost_money'=>$val[6]))->sec_cost_id;
                                    $sec_data = array(
                                        'sec_cost_money' => trim($val[6]),
                                        'sec_cost_date' => $sec_date,
                                        'sec_cost_bank' => $id_bank_in,
                                        'sec_cost_comment' => trim($val[5]),
                                        'sec_cost_type' => $mang,
                                        'sec' => $sec_id,
                                        );

                                        $sec_cost->updateCosts($sec_data,array('sec_cost_id'=>$id_sec_cost));
                                }

                                
                            }
                            


                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('sec');
        }
        $this->view->show('sec/import');

    }

    

}
?>