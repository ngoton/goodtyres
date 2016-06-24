<?php
Class officecostController Extends baseController {
    public function index() {
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý chi phí hành chính';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'office_cost_id';
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

        $cost_model = $this->model->get('officecostModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'office_cost_date >= '.strtotime($batdau).' AND office_cost_date <= '.$ketthuc,
        );
        $join = array('table'=>'bank','where'=>'office_cost.source = bank.bank_id ');
        
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
            'where' => 'office_cost_date >= '.strtotime($batdau).' AND office_cost_date <= '.strtotime($ketthuc),
            );

      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR recipient LIKE "%'.$keyword.'%"
                OR approver LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['costs'] = $cost_model->getAllCost($data);
        $this->view->data['lastID'] = isset($cost_model->getLastCost()->office_cost_id)?$cost_model->getLastCost()->office_cost_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('officecost/index');
    }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 ) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {

            $cost = $this->model->get('officecostModel');
            $data = array(
                        'office_cost_date' => strtotime(trim($_POST['office_cost_date'])),
                        'comment' => trim($_POST['comment']),
                        'recipient' => trim($_POST['recipient']),
                        'approver' => trim($_POST['approver']),
                        'source' => trim($_POST['source']),
                        'person' => trim($_POST['person']),
                        'office_cost' => trim(str_replace(',','',$_POST['office_cost'])),
                        
                        );
            $assets_model = $this->model->get('assetsModel');
            


            if ($_POST['yes'] != "") {
                
                //var_dump($data);
                /*$total = $assets_model->getAssetsByWhere(array('assets_date' => $data['office_cost_date'],'bank' => $data['source']))->total;

                $cptruoc = $cost->getCost($_POST['yes'])->office_cost;
                $cp = $data['office_cost'] - $cptruoc;
                $soton = $total - $cp;*/

                $data_asset = array(
                    'total' => 0 - $data['office_cost'],
                    'bank' => $data['source'],
                    'assets_date' => $data['office_cost_date'],
                );

                $assets_model->updateAssets($data_asset,array('office' => $_POST['yes']));
               // $assets_model->queryAssets('UPDATE assets SET total = total-'.$cp.' WHERE bank = '.$data['source'].' AND assets_date > '.$data['office_cost_date']);
                
                    if ($data['person'] == 1) {
                        $staff_debt_model = $this->model->get('staffdebtModel');
                        $id_debt = $staff_debt_model->getCostByWhere(array('staff'=>$data['recipient'],'staff_debt_date'=>$data['office_cost_date']))->staff_debt_id;
                        $staff_debt_model->deleteCost($id_debt);
                        $debt = array(
                            'staff'=> $data['recipient'],
                            'money' => $data['office_cost'],
                            'comment' => $data['comment'],
                            'staff_debt_date' => $data['office_cost_date'],
                            'source' => $data['source'],
                            'status' => 1,
                        );
                        $staff_debt_model->createCost($debt);
                    }

                    $cost->updateCost($data,array('office_cost_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|office_cost|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{


                    if ($data['person'] == 1) {
                        $staff_debt_model = $this->model->get('staffdebtModel');
                        $debt = array(
                            'staff'=> $data['recipient'],
                            'money' => $data['office_cost'],
                            'comment' => $data['comment'],
                            'staff_debt_date' => $data['office_cost_date'],
                            'source' => $data['source'],
                            'status' => 1,
                        );
                        $staff_debt_model->createCost($debt);
                    }

                    $cost->createCost($data);
                    echo "Thêm thành công";

                    /*$assets = $assets_model->getAllAssets(array('where'=>'bank = '.$data['source'],'order_by'=>'assets_date','order'=>'DESC','limit'=>'0,1'));
                foreach ($assets as $asset) {
                    $total = $asset->total;
                }*/
                    $soton = 0 - $data['office_cost'];

                    $data_asset = array(
                        'bank' => $data['source'],
                        'total' => $soton,
                        'assets_date' => $data['office_cost_date'],
                        'office' => $cost->getLastCost()->office_cost_id,
                    );

                    $assets_model->createAssets($data_asset);


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$cost->getLastCost()->office_cost_id."|office_cost|".implode("-",$data)."\n"."\r\n";
                        
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
            $cost = $this->model->get('officecostModel');
            $assets = $this->model->get('assetsModel');
            $staff_debt_model = $this->model->get('staffdebtModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $costs = $cost->getCostByWhere(array('office_cost_id'=>$data));
                    $assets->queryAssets('DELETE assets WHERE office='.$data);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->office_cost_date.' AND staff="'.$costs->recipient.'" AND money='.$costs->office_cost);
                        
                        $cost->deleteCost($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|office_cost|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    $costs = $cost->getCostByWhere(array('office_cost_id'=>$_POST['data']));
                    $assets->queryAssets('DELETE assets WHERE office='.$_POST['data']);
                    $staff_debt_model->queryCost('DELETE staff_debt WHERE source='.$costs->source.' AND staff_debt_date='.$costs->office_cost_date.' AND staff="'.$costs->recipient.'" AND money='.$costs->office_cost);
                        $cost->deleteCost($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|office_cost|"."\n"."\r\n";
                        
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