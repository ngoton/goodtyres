<?php
Class receiptsController Extends baseController {
    public function index() {
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'receipts_id';
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
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
        }
        $this->view->data['nh'] = $bank_data;

        $receipts_model = $this->model->get('receiptsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'receipts_date >= '.strtotime($batdau).' AND receipts_date <= '.$ketthuc,
        );
        
        
        $tongsodong = count($receipts_model->getAllReceipts($data));
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
            'where' => 'receipts_date >= '.strtotime($batdau).' AND receipts_date <= '.strtotime($ketthuc),
            );

      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"
                OR code LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['receipts'] = $receipts_model->getAllReceipts($data);
        $this->view->data['lastID'] = isset($receipts_model->getLastReceipts()->office_receipts_id)?$receipts_model->getLastReceipts()->office_receipts_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('receipts/index');
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
    public function getstaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] > 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff_model = $this->model->get('staffdebtModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $staff_model->getAllCost();
            }
            else{
                $data = array(
                'where'=>'( staff LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $staff_model->getAllCost($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $staff = $rs->staff;
                if ($_POST['keyword'] != "*") {
                    $code = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->staff);
                }
                
                // add new option
                echo '<li onclick="set_item_staff(\''.$rs->staff.'\')">'.$staff.'</li>';
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

            $receipts = $this->model->get('receiptsModel');
            $data = array(
                        'receipts_date' => strtotime(trim($_POST['receipts_date'])),
                        'comment' => trim($_POST['comment']),
                        'code' => trim($_POST['code']),
                        'source' => trim($_POST['source']),
                        'out' => trim($_POST['out']),
                        'proceeds' => trim(str_replace(',','',$_POST['proceeds'])),
                        'staff' => trim($_POST['staff']),
                        
                        );
            $assets_model = $this->model->get('assetsModel');
            $obtain_model = $this->model->get('obtainModel');
            $salereport = $this->model->get('salereportModel');

            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                /*$total = $assets_model->getAssetsByWhere(array('assets_date' => $data['receipts_date'],'bank' => $data['source']))->total;

                $cptruoc = $receipts->getReceipts($_POST['yes'])->proceeds;
                $cp = $data['proceeds'] - $cptruoc;
                $soton = $total + $cp;*/

                $data_asset = array(
                    'total' => $data['proceeds'],
                    'bank' => $data['source'],
                    'assets_date' => $data['receipts_date'],
                );

                $assets_model->updateAssets($data_asset,array('receipts' => $_POST['yes'],'bank' => $data['source']));
                //$assets_model->queryAssets('UPDATE assets SET total = total+'.$cp.' WHERE bank = '.$data['source'].' AND assets_date > '.$data['receipts_date']);
                
                if($data['out'] >= 1){
                    /*$total = $assets_model->getAssetsByWhere(array('assets_date' => $data['receipts_date'],'bank' => $data['out']))->total;

                    $cptruoc = $receipts->getReceipts($_POST['yes'])->proceeds;
                    $cp = $data['proceeds'] - $cptruoc;
                    $soton = $total - $cp;*/

                    $data_asset = array(
                        'total' => 0 - $data['proceeds'],
                        'bank' => $data['out'],
                        'assets_date' => $data['receipts_date'],
                    );

                    $assets_model->updateAssets($data_asset,array('receipts' => $_POST['yes'],'bank' => $data['out']));
                    //$assets_model->queryAssets('UPDATE assets SET total = total-'.$cp.' WHERE bank = '.$data['out'].' AND assets_date > '.$data['receipts_date']);
                }

                if($data['code'] != ""){
                    $customer_id = $salereport->getSaleByWhere(array('code'=>$data['code']))->customer;
                    $id_obtain = $obtain_model->getObtainByWhere(array('obtain_date'=>$data['receipts_date'],'customer'=>$customer_id));
                    $obtain_model->deleteObtain($id_obtain);
                    $data_obtain = array(
                        'obtain_date' => $data['receipts_date'],
                        'customer' => $customer_id,
                        'money' => 0-$data['proceeds'],
                    );
                    $obtain_model->createObtain($data_obtain);
                }

                    if ($data['staff'] != "") {
                        $staff_debt_model = $this->model->get('staffdebtModel');
                        $id_debt = $staff_debt_model->getCostByWhere(array('staff'=>$data['staff'],'staff_debt_date'=>$data['receipts_date']))->staff_debt_id;
                        $staff_debt_model->deleteCost($id_debt);
                        $debt = array(
                            'staff'=> $data['staff'],
                            'proceeds' => 0-$data['proceeds'],
                            'comment' => $data['comment'],
                            'staff_debt_date' => $data['receipts_date'],
                            'source' => $data['source'],
                            'status' => 1,
                        );
                        $staff_debt_model->createCost($debt);
                    }

                    $receipts->updateReceipts($data,array('receipts_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|receipts|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                


                

                if($data['code'] != ""){
                    $customer_id = $salereport->getSaleByWhere(array('code'=>$data['code']))->customer;
                    $data_obtain = array(
                        'obtain_date' => $data['receipts_date'],
                        'customer' => $customer_id,
                        'money' => 0-$data['proceeds'],
                    );
                    $obtain_model->createObtain($data_obtain);
                }
                    if ($data['staff'] != "") {
                        $staff_debt_model = $this->model->get('staffdebtModel');
                        $debt = array(
                            'staff'=> $data['staff'],
                            'proceeds' => 0-$data['proceeds'],
                            'comment' => $data['comment'],
                            'staff_debt_date' => $data['receipts_date'],
                            'source' => $data['source'],
                            'status' => 1,
                        );
                        $staff_debt_model->createCost($debt);
                    }
                
                    $receipts->createReceipts($data);
                    echo "Thêm thành công";

                    /*$assets = $assets_model->getAllAssets(array('where'=>'bank = '.$data['source'],'order_by'=>'assets_date','order'=>'DESC','limit'=>'0,1'));
                foreach ($assets as $asset) {
                    $total = $asset->total;
                }*/

                $soton = 0 + $data['proceeds'];

                    $data_asset = array(
                        'bank' => $data['source'],
                        'total' => $soton,
                        'assets_date' => $data['receipts_date'],
                        'receipts' => $receipts->getLastReceipts()->receipts_id,
                    );

                    $assets_model->createAssets($data_asset);

                if($data['out'] >= 1){
                    /*$assets = $assets_model->getAllAssets(array('where'=>'bank = '.$data['out'],'order_by'=>'assets_date','order'=>'DESC','limit'=>'0,1'));
                    foreach ($assets as $asset) {
                        $total = $asset->total;
                    }*/

                    $soton = 0 - $data['proceeds'];

                    $data_asset = array(
                        'bank' => $data['out'],
                        'total' => $soton,
                        'assets_date' => $data['receipts_date'],
                        'receipts' => $receipts->getLastReceipts()->receipts_id,
                    );

                    $assets_model->createAssets($data_asset);
                }

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$receipts->getLastReceipts()->receipts_id."|receipts|".implode("-",$data)."\n"."\r\n";
                        
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
            $receipts = $this->model->get('receiptsModel');
            $obtain_model = $this->model->get('obtainModel');
            $salereport = $this->model->get('salereportModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            $assets = $this->model->get('assetsModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $costs = $receipts->getReceiptsByWhere(array('receipts_id'=>$data));
                    $sale = $salereport->getSaleByWhere(array('code'=>$costs->code));
                    $assets->queryAssets('DELETE assets WHERE receipts='.$data);
                    $obtain_model->queryObtain('DELETE obtain WHERE obtain_date='.$costs->receipts_date.' AND customer='.$sale->customer.' AND money='.$costs->proceeds);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->receipts_date.' AND staff="'.$costs->staff.'" AND money='.$costs->proceeds);
                        $receipts->deleteReceipts($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|receipts|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                $costs = $receipts->getReceiptsByWhere(array('receipts_id'=>$_POST['data']));
                    $sale = $salereport->getSaleByWhere(array('code'=>$costs->code));
                    $assets->queryAssets('DELETE assets WHERE receipts='.$_POST['data']);
                    $obtain_model->queryObtain('DELETE obtain WHERE obtain_date='.$costs->receipts_date.' AND customer='.$sale->customer.' AND money='.$costs->proceeds);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->receipts_date.' AND staff="'.$costs->staff.'" AND money='.$costs->proceeds);
                    
                        $receipts->deleteReceipts($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|receipts|"."\n"."\r\n";
                        
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