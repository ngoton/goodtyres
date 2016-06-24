<?php
Class spentController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý chi phí làm hàng';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'spent_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = date('d-m-Y', strtotime("last monday"));
            $ketthuc = date('d-m-Y', time()+86400); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor();
        $data_vendor = array();
        foreach ($vendors as $vendor) {
            $data_vendor['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
        }
        $this->view->data['vendors'] = $data_vendor;

        $cost_model = $this->model->get('spentModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'spent_date >= '.strtotime($batdau).' AND spent_date <= '.$ketthuc,
        );
        $join = array('table'=>'bank, sale_report','where'=>'spent.source = bank.bank_id AND spent.code = sale_report.code');
        
        $tongsodong = count($cost_model->getAllCost($data));
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
            'where' => 'spent_date >= '.strtotime($batdau).' AND spent_date <= '.strtotime($ketthuc),
            );

      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR vendor in (SELECT shipment_vendor_id FROM shipment_vendor WHERE shipment_vendor_name LIKE "%'.$keyword.'%") 
                OR code LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['spents'] = $cost_model->getAllCost($data);
        $this->view->data['lastID'] = isset($cost_model->getLastCost()->spent_id)?$cost_model->getLastCost()->spent_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('spent/index');
    }

   public function getcode(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $sale_report_model->getAllSale();
            }
            else{
                $data = array(
                'where'=>'( code LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $sale_report_model->getAllSale($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $code = $rs->code;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->code);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->code.'\')">'.$code.'</li>';
            }
        }
    }
    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $shipment_vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $shipment_vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $shipment_vendor_model->getAllVendor($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $vendor = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                
                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->shipment_vendor_id.'\',\''.$rs->shipment_vendor_name.'\')">'.$vendor.'</li>';
            }
        }
    }
    public function getcommission(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $sale_report_model->getAllSale();
            }
            else{
                $data = array(
                'where'=>'( commission_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $sale_report_model->getAllSale($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $vendor = $rs->commission_name;
                if ($_POST['keyword'] != "*") {
                    $vendor = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->commission_name);
                }
                
                // add new option
                echo '<li onclick="set_item_commission(\''.$rs->commission_name.'\')">'.$vendor.'</li>';
            }
        }
    }
    public function getport(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sale_report_model = $this->model->get('salereportModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $sale_report_model->getAllSale();
            }
            else{
                $data = array(
                'where'=>'( handling_port LIKE "%'.$_POST['keyword'].'%" OR handling_port2 LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $sale_report_model->getAllSale($data);
            }
            $port = array();
            foreach ($list as $rs) {
                // put in bold the written text
                if(!isset($port[$rs->handling_port]) || !isset($port[$rs->handling_port2])){
                        $vendor = $rs->handling_port;
                        if ($vendor != "") {
                            $port[$vendor] = $vendor;
                        }
                        $vendor2 = $rs->handling_port2;
                        if ($vendor2 != "") {
                            $port[$vendor2] = $vendor2;
                        }
                        if ($_POST['keyword'] != "*") {
                            if($vendor != "")
                            $vendor = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $port[$rs->handling_port]);
                            if($vendor2 != "")
                            $vendor2 = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $port[$rs->handling_port2]);
                        }
                        
                        // add new option
                        if($vendor != "")
                        echo '<li onclick="set_item_port(\''.$port[$rs->handling_port].'\')">'.$port[$rs->handling_port].'</li>';
                        if($vendor2 != "")
                        echo '<li onclick="set_item_port(\''.$port[$rs->handling_port2].'\')">'.$port[$rs->handling_port2].'</li>';
                }
            }
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 ) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $cost = $this->model->get('spentModel');
            $data = array(
                        'spent_date' => strtotime(trim($_POST['spent_date'])),
                        'comment' => trim($_POST['comment']),
                        'code' => trim($_POST['code']),
                        'vendor' => trim($_POST['vendor']),
                        'source' => trim($_POST['source']),
                        'proceeds' => trim(str_replace(',','',$_POST['proceeds'])),
                        'type' => trim($_POST['type']),
                        'name' => trim($_POST['name']),
                        
                        );
            $assets_model = $this->model->get('assetsModel');
            $owe_model = $this->model->get('oweModel');


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                /*$total = $assets_model->getAssetsByWhere(array('assets_date' => $data['spent_date'],'bank' => $data['source']))->total;

                $cptruoc = $cost->getCost($_POST['yes'])->spent;
                $cp = $data['spent'] - $cptruoc;
                $soton = $total - $cp;*/

                $data_asset = array(
                    'total' => 0 - $data['proceeds'],
                    'bank' => $data['source'],
                    'assets_date' => $data['spent_date'],
                );

                $assets_model->updateAssets($data_asset,array('spent' => $_POST['yes']));
                //$assets_model->queryAssets('UPDATE assets SET total = total-'.$cp.' WHERE bank = '.$data['source'].' AND assets_date > '.$data['spent_date']);
                
                if($data['type'] == 1){
                $data_owe = array(
                        'owe_date' => $data['spent_date'],
                        'vendor' => $data['vendor'],
                        'money' => 0-$data['proceeds'],
                    );
                    $owe_model->updateOwe($data_owe,array('owe_date'=>$data['spent_date'],'vendor'=>$data['vendor']));
                }

                    $cost->updateCost($data,array('spent_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|spent|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                

                    if($data['type'] == 1){
                        $data_owe = array(
                            'owe_date' => $data['spent_date'],
                            'vendor' => $data['vendor'],
                            'money' => 0-$data['proceeds'],
                        );
                        $owe_model->createOwe($data_owe);
                    }

                    $cost->createCost($data);
                    echo "Thêm thành công";

                    /*$assets = $assets_model->getAllAssets(array('where'=>'bank = '.$data['source'],'order_by'=>'assets_date','order'=>'DESC','limit'=>'0,1'));
                foreach ($assets as $asset) {
                    $total = $asset->total;
                }*/
                    $soton = 0 - $data['proceeds'];

                    $data_asset = array(
                        'bank' => $data['source'],
                        'total' => $soton,
                        'assets_date' => $data['spent_date'],
                        'spent' => $cost->getLastCost()->spent_id,
                    );

                    $assets_model->createAssets($data_asset);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$cost->getLastCost()->spent_id."|spent|".implode("-",$data)."\n"."\r\n";
                        
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
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 ) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cost = $this->model->get('spentModel');
            $owe_model = $this->model->get('oweModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $assets = $this->model->get('assetsModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $costs = $cost->getCostsByWhere(array('spent_id'=>$data));
                    $assets->queryAssets('DELETE assets WHERE spent='.$data);
                    $owe_model->queryOwe('DELETE owe WHERE owe_date='.$costs->spent_date.' AND vendor='.$costs->vendor.' AND money='.$costs->proceeds);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->spent_date.' AND staff="'.$costs->name.'"');

                        $cost->deleteCost($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|spent|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    $costs = $cost->getCostsByWhere(array('spent_id'=>$_POST['data']));
                    $assets->queryAssets('DELETE assets WHERE spent='.$_POST['data']);
                    $owe_model->queryOwe('DELETE owe WHERE owe_date='.$costs->spent_date.' AND vendor='.$costs->vendor.' AND money='.$costs->proceeds);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->spent_date.' AND staff="'.$costs->name.'"');

                        $cost->deleteCost($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|spent|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

    public function view() {
        
        $this->view->show('accounting/view');
    }

}
?>