<?php
Class lenderpayController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Vay vốn';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'lender_pay_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $ngay = date('d-m-Y');
            $batdau = (int)date('W',strtotime($ngay));
            $trangthai = 0;
        }

        $id = $this->registry->router->param_id;
        
        $nam = date('Y');


        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        
        $join = array('table'=>'bank, lender','where'=>'bank.bank_id = lender_pay.source AND lender.lender_id = lender_pay.lender');

        $pays_model = $this->model->get('lenderpayModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_pay_id = '.$id;
        }

                
        $tongsodong = count($pays_model->getAllLender($data,$join));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1 = 1',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_pay_id = '.$id;
        }


      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR lender_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

                
        $this->view->data['lender_pays'] = $pays_model->getAllLender($data,$join);
        $this->view->data['lastID'] = isset($pays_model->getLastLender()->lender_pay_id)?$pays_model->getLastLender()->lender_pay_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('lenderpay/index');
    }

    public function getlender(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $lender_model = $this->model->get('lenderModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $lender_model->getAllLender();
            }
            else{
                $data = array(
                'where'=>'( lender_name LIKE "%'.$_POST['keyword'].'%")',
                );
                $list = $lender_model->getAllLender($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $lender_name = $rs->lender_name;
                if ($_POST['keyword'] != "*") {
                    $lender_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->lender_name);
                }
                
                // add new option
                echo '<li onclick="set_item(\''.$rs->lender_name.'\',\''.$rs->lender_id.'\')">'.$lender_name.'</li>';
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
            $lender_pays = $this->model->get('lenderpayModel');
            $lender_owes = $this->model->get('lenderoweModel');
            $pay_lenders = $this->model->get('paylenderModel');
            $data = array(
                        'comment' => trim($_POST['comment']),
                        'lender_money' => trim(str_replace(',','',$_POST['lender_money'])),
                        'lender_pay_date' => strtotime(trim($_POST['lender_pay_date'])),
                        'lender_pay_expect' => strtotime(trim($_POST['lender_pay_expect'])),
                        'week' => (int)date('W', strtotime(trim($_POST['lender_pay_date']))),
                        'source' => trim($_POST['source']),
                        'year' => (int)date('Y', strtotime(trim($_POST['lender_pay_date']))),
                        'lender_pay_money' => trim(str_replace(',','',$_POST['lender_pay_money'])),
                        
                        );

            
            if (trim($_POST['lender_name']) != "") {
                if (trim($_POST['lender']) != "") {
                    $data['lender'] = trim($_POST['lender']);
                }
                else{
                    $lenders = $this->model->get('lenderModel');
                    $data_lender = array(
                        'lender_name' => trim($_POST['lender_name']),
                    );

                    $lenders->createLender($data_lender);
                    $data['lender'] = $lenders->getLastLender()->lender_id;
                }
            }

            if ($_POST['yes'] != "") {
                
                $lender_data = $lender_pays->getLender($_POST['yes']);

                $data_owe = array(
                            'lender' => $data['lender'],
                            'money' => $data['lender_money'],
                            'lender_owe_date' => $data['lender_pay_date'],
                            'lender_pay' => $lender_data->lender_pay_id,
                            'week' => (int)date('W',$data['lender_pay_date']),
                            'year' => (int)date('Y',$data['lender_pay_date']),
                        );
                if($data_owe['week'] == 53){
                    $data_owe['week'] = 1;
                    $data_owe['year'] = $data_owe['year']+1;
                }
                if (((int)date('W',$data['lender_pay_date']) == 1) && ((int)date('m',$data['lender_pay_date']) == 12) ) {
                    $data_owe['year'] = (int)date('Y',$data['lender_pay_date'])+1;
                }

                $lender_owes->updateLender($data_owe,array('lender_pay'=>$lender_data->lender_pay_id,'money'=>$lender_data->money));

                
                                      

                    $lender_pays->updateLender($data,array('lender_pay_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|lender_pay|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $lender_pays->createLender($data);
                    echo "Thêm thành công";

                    $id_lender = $lender_pays->getLastLender()->lender_pay_id;

                    
                    $data_owe = array(
                                'lender' => $data['lender'],
                                'money' => $data['lender_money'],
                                'lender_owe_date' => $data['lender_pay_date'],
                                'lender_pay' => $id_lender,
                                'week' => (int)date('W',$data['lender_pay_date']),
                                'year' => (int)date('Y',$data['lender_pay_date']),
                            );
                    if($data_owe['week'] == 53){
                        $data_owe['week'] = 1;
                        $data_owe['year'] = $data_owe['year']+1;
                    }
                    if (((int)date('W',$data['lender_pay_date']) == 1) && ((int)date('m',$data['lender_pay_date']) == 12) ) {
                        $data_owe['year'] = (int)date('Y',$data['lender_pay_date'])+1;
                    }

                    $lender_owes->createLender($data_owe);

                    
              

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$lender_pays->getLastLender()->lender_pay_id."|pays|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function pay(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $lender_pay = $this->model->get('lenderpayModel');
            $lender_pay_data = $lender_pay->getLender($_POST['data']);

            $data = array(
                        
                        'lender_pay_money' => $lender_pay_data->lender_pay_money + trim(str_replace(',','',$_POST['money'])),
                        );
          
            $lender_pay->updateLender($data,array('lender_pay_id' => $_POST['data']));

            $data = array(
                'source' => trim($_POST['source']),
                'pay_money' => trim(str_replace(',','',$_POST['money'])),
                'pay_date' => strtotime($_POST['pay_date']),
            );

            $assets_model = $this->model->get('assetsModel');

            if($data['pay_money'] != 0){
                $data_asset = array(
                            'bank' => $data['source'],
                            'total' => 0 - $data['pay_money'],
                            'assets_date' => $data['pay_date'],
                            'lender_pay' => $_POST['data'],
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
            }


            $pay_model = $this->model->get('paylenderModel');
            $data_pay = array(
                        'source' => $data['source'],
                        'money' => $data['pay_money'],
                        'pay_lender_date' => $data['pay_date'],
                        'lender_pay' => $_POST['data'],
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                    );
            if($data_pay['week'] == 53){
                $data_pay['week'] = 1;
                $data_pay['year'] = $data_pay['year']+1;
            }
            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $pay_model->createLender($data_pay);

            $lender_owe_model = $this->model->get('lenderoweModel');
            $data_owe = array(
                        'money' => 0-$data['pay_money'],
                        'lender_owe_date' => $data['pay_date'],
                        'lender_pay' => $_POST['data'],
                        'lender' => $lender_pay_data->lender,
                        'lender_cost' => $lender_pay_data->lender_cost,
                        'week' => (int)date('W',$data['pay_date']),
                        'year' => (int)date('Y',$data['pay_date']),
                    );
            if($data_owe['week'] == 53){
                $data_owe['week'] = 1;
                $data_owe['year'] = $data_owe['year']+1;
            }
            if (((int)date('W',$data['pay_date']) == 1) && ((int)date('m',$data['pay_date']) == 12) ) {
                $data_asset['year'] = (int)date('Y',$data['pay_date'])+1;
            }

            $lender_owe_model->createLender($data_owe);

            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."pay"."|".$_POST['data']."|lender_pay|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            return true;
                    
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
            $assets_model = $this->model->get('assetsModel');
            $lender_pays = $this->model->get('lenderpayModel');
            $lender_owes = $this->model->get('lenderoweModel');
            $pay_lenders = $this->model->get('paylenderModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $lender_data = $lender_pays->getLender($data);

                       $lender_pays->deleteLender($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE lender_pay = '.$data);
                       $lender_owes->queryLender('DELETE FROM lender_owe WHERE lender_pay = '.$data);
                       $pay_lenders->queryLender('DELETE FROM pay_lender WHERE lender_pay = '.$data);

                       

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|lender_pays|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $lender_data = $lender_pays->getLender($_POST['data']);

                        $lender_pays->deleteLender($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE lender_pay = '.$_POST['data']);
                       $lender_owes->queryLender('DELETE FROM lender_owe WHERE lender_pay = '.$_POST['data']);
                       $pay_lenders->queryLender('DELETE FROM pay_lender WHERE lender_pay = '.$_POST['data']);

                       

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|lender_pays|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>