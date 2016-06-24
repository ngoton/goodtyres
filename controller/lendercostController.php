<?php
Class lendercostController Extends baseController {
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'lender_cost_date';
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

        
        $join = array('table'=>'bank, lender','where'=>'bank.bank_id = lender_cost.source AND lender.lender_id = lender_cost.lender');

        $costs_model = $this->model->get('lendercostModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1 = 1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND lender_cost_id = '.$id;
        }

                
        $tongsodong = count($costs_model->getAllLender($data,$join));
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
            $data['where'] .= ' AND lender_cost_id = '.$id;
        }


      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR bank_name LIKE "%'.$keyword.'%" 
                OR money LIKE "%'.$keyword.'%" 
                OR lender_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

                
        $this->view->data['lender_costs'] = $costs_model->getAllLender($data,$join);
        $this->view->data['lastID'] = isset($costs_model->getLastLender()->lender_cost_id)?$costs_model->getLastLender()->lender_cost_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('lendercost/index');
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
            $lender_costs = $this->model->get('lendercostModel');
            $lender_owes = $this->model->get('lenderoweModel');
            $lender_pays = $this->model->get('lenderpayModel');
            $data = array(
                        'comment' => trim($_POST['comment']),
                        'money' => trim(str_replace(',','',$_POST['money'])),
                        'lender_cost_date' => strtotime(trim($_POST['lender_cost_date'])),
                        'lender_cost_expect' => strtotime(trim($_POST['lender_cost_expect'])),
                        'week' => (int)date('W', strtotime(trim($_POST['lender_cost_date']))),
                        'source' => trim($_POST['source']),
                        'year' => (int)date('Y', strtotime(trim($_POST['lender_cost_date']))),
                        'rate' => trim($_POST['rate']),
                        
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
                
                $lender_data = $lender_costs->getLender($_POST['yes']);

                $data_asset = array(
                            'bank' => $data['source'],
                            'total' => $data['money'],
                            'assets_date' => $data['lender_cost_date'],
                            'lender_cost' => $lender_data->lender_cost_id,
                            'week' => (int)date('W',$data['lender_cost_date']),
                            'year' => (int)date('Y',$data['lender_cost_date']),
                        );
                if($data_asset['week'] == 53){
                    $data_asset['week'] = 1;
                    $data_asset['year'] = $data_asset['year']+1;
                }
                if (((int)date('W',$data['lender_cost_date']) == 1) && ((int)date('m',$data['lender_cost_date']) == 12) ) {
                    $data_asset['year'] = (int)date('Y',$data['lender_cost_date'])+1;
                }

                $assets_model->updateAssets($data_asset,array('lender_cost'=>$lender_data->lender_cost_id,'total'=>$lender_data->money));

                $data_owe = array(
                            'lender' => $data['lender'],
                            'money' => $data['money'],
                            'lender_owe_date' => $data['lender_cost_date'],
                            'lender_cost' => $lender_data->lender_cost_id,
                            'week' => (int)date('W',$data['lender_cost_date']),
                            'year' => (int)date('Y',$data['lender_cost_date']),
                        );
                if($data_owe['week'] == 53){
                    $data_owe['week'] = 1;
                    $data_owe['year'] = $data_owe['year']+1;
                }
                if (((int)date('W',$data['lender_cost_date']) == 1) && ((int)date('m',$data['lender_cost_date']) == 12) ) {
                    $data_owe['year'] = (int)date('Y',$data['lender_cost_date'])+1;
                }

                $lender_owes->updateLender($data_owe,array('lender_cost'=>$lender_data->lender_cost_id,'money'=>$lender_data->money));

                $first_month = date('m',$data['lender_cost_date']);
                $last_month = date('m',$data['lender_cost_expect']);
                $first_year = date('Y',$data['lender_cost_date']);
                $last_year = date('Y',$data['lender_cost_expect']);

                if ($last_year-$first_year==0) {
                    $number_month = $last_month-$first_month;
                }
                else if ($last_year-$first_year>0) {
                    $f = 12-$first_month;
                    $number_month = $f + ($last_month+(12*($last_year-$first_year-1)));
                }

                $data_pay = array(
                        'lender' => $data['lender'],
                        'lender_money' => $data['money'],
                        'lender_pay_date' => $data['lender_cost_date'],
                        'lender_cost' => $lender_data->lender_cost_id,
                        'week' => (int)date('W',strtotime( "+".$number_month." month", $data['lender_cost_date'])),
                        'year' => (int)date('Y',strtotime( "+".$number_month." month", $data['lender_cost_date'])),
                        'lender_pay_expect' => strtotime( "+".$number_month." month", $data['lender_cost_date']),
                        'comment' => 'Vay vốn',
                        'source' => $data['source'],
                    );
                if($data_pay['week'] == 53){
                    $data_pay['week'] = 1;
                    $data_pay['year'] = $data_pay['year']+1;
                }
                if (((int)date('W',strtotime( "+".$number_month." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$number_month." month", $data['lender_cost_date'])) == 12) ) {
                    $data_pay['year'] = (int)date('Y',strtotime( "+".$number_month." month", $data['lender_cost_date']))+1;
                }

                $lender_pays->updateLender($data_pay,array('lender_cost' => $lender_data->lender_cost_id,'lender_money'=>$lender_data->money));

                if ($lender_data->rate>0 && ($data['rate'] == 0 || $data['rate'] == "" )) {
                    $lender_owes->queryLender('DELETE FROM lender_owe WHERE lender_cost='.$lender_data->lender_cost_id.' AND money='.($lender_data->money*($lender_data->rate/100)));
                    $lender_pays->queryLender('DELETE FROM lender_pay WHERE lender_cost='.$lender_data->lender_cost_id.' AND lender_money='.($lender_data->money*($lender_data->rate/100)));
                }

                if ($data['rate']>0) {
                    if ($data['lender_cost_expect'] == $lender_data->lender_cost_expect) {
                        for ($i=1; $i <= $number_month; $i++) {

                            $data_owe = array(
                                    'lender' => $data['lender'],
                                    'money' => $data['money']*($data['rate']/100),
                                    'lender_owe_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'lender_cost' => $lender_data->lender_cost_id,
                                    'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                );
                            if($data_owe['week'] == 53){
                                $data_owe['week'] = 1;
                                $data_owe['year'] = $data_owe['year']+1;
                            }
                            if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                $data_owe['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                            }


                            $lender_owes->updateLender($data_owe,array('lender_cost' => $lender_data->lender_cost_id,'money'=>($lender_data->money*($lender_data->rate/100)),'lender_owe_date' => strtotime( "+".$i." month", $data['lender_cost_date'])));

                            $data_pay = array(
                                    'lender' => $data['lender'],
                                    'lender_money' => $data['money']*($data['rate']/100),
                                    'lender_pay_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'lender_cost' => $lender_data->lender_cost_id,
                                    'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'lender_pay_expect' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'comment' => 'Thanh toán lãi suất vay vốn tháng '.date('m/Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'source' => $data['source'],
                                );
                            if($data_pay['week'] == 53){
                                $data_pay['week'] = 1;
                                $data_pay['year'] = $data_pay['year']+1;
                            }
                            if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                $data_pay['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                            }

                            $lender_pays->updateLender($data_pay,array('lender_cost' => $lender_data->lender_cost_id,'lender_money'=>($lender_data->money*($lender_data->rate/100)),'lender_pay_date' => strtotime( "+".$i." month", $data['lender_cost_date'])));
                        }
                    }
                    elseif ($data['lender_cost_expect'] != $lender_data->lender_cost_expect) {
                        for ($i=1; $i <= $number_month; $i++) {
                            if ($lender_owes->getLenderByWhere(array('lender_cost' => $lender_data->lender_cost_id,'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))))) {
                                $data_owe = array(
                                        'lender' => $data['lender'],
                                        'money' => $data['money']*($data['rate']/100),
                                        'lender_owe_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'lender_cost' => $lender_data->lender_cost_id,
                                        'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                                }


                                $lender_owes->updateLender($data_owe,array('lender_cost' => $lender_data->lender_cost_id,'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))));

                                $data_pay = array(
                                        'lender' => $data['lender'],
                                        'lender_money' => $data['money']*($data['rate']/100),
                                        'lender_pay_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'lender_cost' => $lender_data->lender_cost_id,
                                        'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'lender_pay_expect' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'comment' => 'Thanh toán lãi suất vay vốn tháng '.date('m/Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'source' => $data['source'],
                                    );
                                if($data_pay['week'] == 53){
                                    $data_pay['week'] = 1;
                                    $data_pay['year'] = $data_pay['year']+1;
                                }
                                if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                    $data_pay['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                                }

                                $lender_pays->updateLender($data_pay,array('lender_cost' => $lender_data->lender_cost_id,'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))));
                            }
                            else{
                                $data_owe = array(
                                        'lender' => $data['lender'],
                                        'money' => $data['money']*($data['rate']/100),
                                        'lender_owe_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'lender_cost' => $lender_data->lender_cost_id,
                                        'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    );
                                if($data_owe['week'] == 53){
                                    $data_owe['week'] = 1;
                                    $data_owe['year'] = $data_owe['year']+1;
                                }
                                if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                    $data_owe['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                                }


                                $lender_owes->createLender($data_owe);

                                $data_pay = array(
                                        'lender' => $data['lender'],
                                        'lender_money' => $data['money']*($data['rate']/100),
                                        'lender_pay_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'lender_cost' => $lender_data->lender_cost_id,
                                        'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'lender_pay_expect' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                        'comment' => 'Thanh toán lãi suất vay vốn tháng '.date('m/Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                        'source' => $data['source'],
                                    );
                                if($data_pay['week'] == 53){
                                    $data_pay['week'] = 1;
                                    $data_pay['year'] = $data_pay['year']+1;
                                }
                                if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                    $data_pay['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                                }

                                $lender_pays->createLender($data_pay);
                            }
                        }
                    }
                }
                

                
                                      

                    $lender_costs->updateLender($data,array('lender_cost_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|lender_cost|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $lender_costs->createLender($data);
                    echo "Thêm thành công";

                    $id_lender = $lender_costs->getLastLender()->lender_cost_id;

                    $data_asset = array(
                                'bank' => $data['source'],
                                'total' => $data['money'],
                                'assets_date' => $data['lender_cost_date'],
                                'lender_cost' => $id_lender,
                                'week' => (int)date('W',$data['lender_cost_date']),
                                'year' => (int)date('Y',$data['lender_cost_date']),
                            );
                    if($data_asset['week'] == 53){
                        $data_asset['week'] = 1;
                        $data_asset['year'] = $data_asset['year']+1;
                    }
                    if (((int)date('W',$data['lender_cost_date']) == 1) && ((int)date('m',$data['lender_cost_date']) == 12) ) {
                        $data_asset['year'] = (int)date('Y',$data['lender_cost_date'])+1;
                    }

                    $assets_model->createAssets($data_asset);

                    $data_owe = array(
                                'lender' => $data['lender'],
                                'money' => $data['money'],
                                'lender_owe_date' => $data['lender_cost_date'],
                                'lender_cost' => $id_lender,
                                'week' => (int)date('W',$data['lender_cost_date']),
                                'year' => (int)date('Y',$data['lender_cost_date']),
                            );
                    if($data_owe['week'] == 53){
                        $data_owe['week'] = 1;
                        $data_owe['year'] = $data_owe['year']+1;
                    }
                    if (((int)date('W',$data['lender_cost_date']) == 1) && ((int)date('m',$data['lender_cost_date']) == 12) ) {
                        $data_owe['year'] = (int)date('Y',$data['lender_cost_date'])+1;
                    }

                    $lender_owes->createLender($data_owe);

                    $first_month = date('m',$data['lender_cost_date']);
                    $last_month = date('m',$data['lender_cost_expect']);
                    $first_year = date('Y',$data['lender_cost_date']);
                    $last_year = date('Y',$data['lender_cost_expect']);

                    if ($last_year-$first_year==0) {
                        $number_month = $last_month-$first_month;
                    }
                    else if ($last_year-$first_year>0) {
                        $f = 12-$first_month;
                        $number_month = $f + ($last_month+(12*($last_year-$first_year-1)));
                    }

                    $data_pay = array(
                            'lender' => $data['lender'],
                            'lender_money' => $data['money'],
                            'lender_pay_date' => $data['lender_cost_date'],
                            'lender_cost' => $id_lender,
                            'week' => (int)date('W',strtotime( "+".$number_month." month", $data['lender_cost_date'])),
                            'year' => (int)date('Y',strtotime( "+".$number_month." month", $data['lender_cost_date'])),
                            'lender_pay_expect' => strtotime( "+".$number_month." month", $data['lender_cost_date']),
                            'comment' => 'Vay vốn',
                            'source' => $data['source'],
                        );
                    if($data_pay['week'] == 53){
                        $data_pay['week'] = 1;
                        $data_pay['year'] = $data_pay['year']+1;
                    }
                    if (((int)date('W',strtotime( "+".$number_month." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$number_month." month", $data['lender_cost_date'])) == 12) ) {
                        $data_pay['year'] = (int)date('Y',strtotime( "+".$number_month." month", $data['lender_cost_date']))+1;
                    }

                    $lender_pays->createLender($data_pay);

                    if ($data['rate']>0) {
                        for ($i=1; $i <= $number_month; $i++) {

                            $data_owe = array(
                                    'lender' => $data['lender'],
                                    'money' => $data['money']*($data['rate']/100),
                                    'lender_owe_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'lender_cost' => $id_lender,
                                    'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                );
                            if($data_owe['week'] == 53){
                                $data_owe['week'] = 1;
                                $data_owe['year'] = $data_owe['year']+1;
                            }
                            if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                $data_owe['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                            }

                            $lender_owes->createLender($data_owe);

                            $data_pay = array(
                                    'lender' => $data['lender'],
                                    'lender_money' => $data['money']*($data['rate']/100),
                                    'lender_pay_date' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'lender_cost' => $id_lender,
                                    'week' => (int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'year' => (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'lender_pay_expect' => strtotime( "+".$i." month", $data['lender_cost_date']),
                                    'comment' => 'Thanh toán lãi suất vay vốn tháng '.date('m/Y',strtotime( "+".$i." month", $data['lender_cost_date'])),
                                    'source' => $data['source'],
                                );
                            if($data_pay['week'] == 53){
                                $data_pay['week'] = 1;
                                $data_pay['year'] = $data_pay['year']+1;
                            }
                            if (((int)date('W',strtotime( "+".$i." month", $data['lender_cost_date'])) == 1) && ((int)date('m',strtotime( "+".$i." month", $data['lender_cost_date'])) == 12) ) {
                                $data_pay['year'] = (int)date('Y',strtotime( "+".$i." month", $data['lender_cost_date']))+1;
                            }

                            $lender_pays->createLender($data_pay);
                        }
                    }
              

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$lender_costs->getLastLender()->lender_cost_id."|costs|".implode("-",$data)."\n"."\r\n";
                        
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
            $assets_model = $this->model->get('assetsModel');
            $lender_costs = $this->model->get('lendercostModel');
            $lender_owes = $this->model->get('lenderoweModel');
            $lender_pays = $this->model->get('lenderpayModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $lender_data = $lender_costs->getLender($data);

                       $lender_costs->deleteLender($data);
                       $assets_model->queryAssets('DELETE FROM assets WHERE lender_cost = '.$data);
                       $lender_owes->queryLender('DELETE FROM lender_owe WHERE lender_cost = '.$data);
                       $lender_pays->queryLender('DELETE FROM lender_pay WHERE lender_cost = '.$data);

                       

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|lender_costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $lender_data = $lender_costs->getLender($_POST['data']);

                        $lender_costs->deleteLender($_POST['data']);
                        $assets_model->queryAssets('DELETE FROM assets WHERE lender_cost = '.$_POST['data']);
                       $lender_owes->queryLender('DELETE FROM lender_owe WHERE lender_cost = '.$_POST['data']);
                       $lender_pays->queryLender('DELETE FROM lender_pay WHERE lender_cost = '.$_POST['data']);

                       

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|lender_costs|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>