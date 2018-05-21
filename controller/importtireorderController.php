<?php
Class importtireorderController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nhập khẩu';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'import_tire_order_code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-01-'.date('Y');
            $ketthuc = date('t-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $this->view->data['stevedore_small'] = 8000;
        $this->view->data['stevedore_large'] = 8000;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor();
        $data_vendor = array();
        foreach ($vendors as $vendor) {
            $data_vendor['name'][$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
        }
        $this->view->data['data_vendor'] = $data_vendor;

        $import_tire_custom_model = $this->model->get('importtirecustomModel');
        $prices = $import_tire_custom_model->getAllImport(array('order_by'=>'start_time ASC'));
        $gia_custom=array();
        foreach ($prices as $price) {
            $gia_custom[$price->import_tire_custom_service]['1cont'] = $price->import_tire_1_cont;
            $gia_custom[$price->import_tire_custom_service]['2cont'] = $price->import_tire_2_cont;
            $gia_custom[$price->import_tire_custom_service]['plus'] = $price->import_tire_plus;
            $gia_custom[$price->import_tire_custom_service]['vat'] = $price->check_vat;
        }
        $this->view->data['custom_price'] = $gia_custom;

        $import_tire_port_model = $this->model->get('importtireportModel');
        $ports = $import_tire_port_model->getAllImport(array('order_by'=>'import_tire_port_name ASC'),array('table'=>'import_tire_country','where'=>'import_tire_country=import_tire_country_id AND import_tire_country_name != "Việt Nam"'));
        $this->view->data['port_froms'] = $ports;

        $ports = $import_tire_port_model->getAllImport(array('order_by'=>'import_tire_port_name ASC'),array('table'=>'import_tire_country','where'=>'import_tire_country=import_tire_country_id AND import_tire_country_name = "Việt Nam"'));
        $this->view->data['port_tos'] = $ports;

        $import_tire_order_model = $this->model->get('importtireorderModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'import_tire_order_date >= '.strtotime($batdau).' AND import_tire_order_date < '.strtotime($ngayketthuc),
        );
        
        $tongsodong = count($import_tire_order_model->getAllImport($data,null));
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
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'import_tire_order_date >= '.strtotime($batdau).' AND import_tire_order_date < '.strtotime($ngayketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( import_tire_order_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $import_tire_orders = $import_tire_order_model->getAllImport($data,null);

        
        $this->view->data['import_tire_orders'] = $import_tire_orders;
       
        $this->view->data['lastID'] = isset($import_tire_order_model->getLastImport()->import_tire_order_code)?$import_tire_order_model->getLastImport()->import_tire_order_code:0;

        $last_bank_rate = 22800;
        $last_tax_rate = 22800;
        $last_bank_rates = $import_tire_order_model->queryImport('SELECT max(import_tire_order_bank_rate) AS bank_rate,max(import_tire_order_tax_rate) AS tax_rate FROM import_tire_order GROUP BY import_tire_order_bank_rate,import_tire_order_tax_rate');
        foreach ($last_bank_rates as $last) {
            $last_bank_rate = $last->bank_rate;
            $last_tax_rate = $last->tax_rate;
        }
        $this->view->data['last_bank_rate'] = $last_bank_rate;
        $this->view->data['last_tax_rate'] = $last_tax_rate;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('importtireorder/index');
    }
    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            

            foreach ($list as $rs) {
                // put in bold the written text
                $shipment_vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $shipment_vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                // add new option
                echo '<li onclick="set_item_vendor(\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_id.'\',\''.$_POST['offset'].'\')">'.$shipment_vendor_name.'</li>';
            }
        }
    }
    public function getseller(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            

            foreach ($list as $rs) {
                // put in bold the written text
                $shipment_vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $shipment_vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                // add new option
                echo '<li onclick="set_item_seller(\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_id.'\',\''.$_POST['offset'].'\')">'.$shipment_vendor_name.'</li>';
            }
        }
    }

    public function lock(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {
            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_order_model->updateImport(array('import_tire_order_lock'=>$_POST['lock']),array('import_tire_order_id'=>$_POST['data']));

            echo "Thành công";
        }
    }
    
    public function updatestatus(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {
            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $tire_going_model = $this->model->get('tiregoingModel');
            $tirebuy = $this->model->get('tirebuyModel');
            $tireimportdetail = $this->model->get('tireimportdetailModel');
            $tireimport = $this->model->get('tireimportModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $account_model = $this->model->get('accountModel');
            $importtire_model = $this->model->get('importtireModel');
            $sale_vendor = $this->model->get('importtirecostModel');
            $vendor_model = $this->model->get('shipmentvendorModel');

            $tk_331 = $account_model->getAccountByWhere(array('account_number'=>'331'))->account_id;
            $tk_156 = $account_model->getAccountByWhere(array('account_number'=>'156'))->account_id;

            $import_tire_order_model->updateImport(array('import_tire_order_status'=>$_POST['status']),array('import_tire_order_id'=>$_POST['data']));
            $import_orders = $import_tire_order_model->getImport($_POST['data']);

            if ($import_orders->import_tire_order_status==4) {
                $import_tire_order_model->updateImport(array('import_tire_order_port_date'=>strtotime($_POST['date'])),array('import_tire_order_id'=>$_POST['data']));
            }
            else{
                $import_tire_order_model->updateImport(array('import_tire_order_expect_date'=>strtotime($_POST['date'])),array('import_tire_order_id'=>$_POST['data']));
            }

            $importtire_model->updateSale(array('expect_date'=>strtotime($_POST['date'])),array('import_tire_order'=>$_POST['data']));

            
            $list_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$_POST['data']));
            foreach ($list_orders as $order) {
                
                if ($import_orders->import_tire_order_status==3) {
                    $tire_going = array(
                    'tire_going_date' => strtotime($_POST['date']),
                    'code' => $import_orders->import_tire_order_code,
                    'tire_size' => $order->tire_size,
                    'tire_pattern' => $order->tire_pattern,
                    'tire_brand' => $order->tire_brand,
                    'tire_number' => $order->tire_number,
                    'import_tire_list' => $order->import_tire_list_id,
                    'status' => 1,
                    );
                    if (!$tire_going_model->getTireByWhere(array('import_tire_list' => $order->import_tire_list_id))) {
                        $tire_going_model->createTire($tire_going);
                    }
                    else{
                        $tire_going_model->updateTire($tire_going,array('import_tire_list' => $order->import_tire_list_id));
                    }

                    $id_brand = $order->tire_brand;
                    $id_size = $order->tire_size;
                    $id_pattern = $order->tire_pattern;
                    $code = $import_orders->import_tire_order_code;

                    $dauthang = $tire_going['tire_going_date'];

                    if($tireimportdetail->getTireByWhere(array('import_tire_list' => $order->import_tire_list_id))) {
                        $tirebuy->queryTire('DELETE FROM tire_buy WHERE import_tire_list='.$order->import_tire_list_id);
                        $tireimport->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$order->import_tire_list_id);
                        $tireimportdetail->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$order->import_tire_list_id);
                    }

                    if($tireimportdetail->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern))) {
                        $ton = 0;

                        $tire_buys = $tirebuy->getAllTire(array('where'=>'code != '.$code.' AND tire_buy_date <= '.$dauthang.' AND tire_buy_brand = '.$id_brand.' AND tire_buy_size = '.$id_size.' AND tire_buy_pattern = '.$id_pattern));
                        foreach ($tire_buys as $tire) {
                            $ton += $tire->tire_buy_volume;
                        }

                        $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_sale_date < '.$dauthang.' AND tire_brand = '.$id_brand.' AND tire_size = '.$id_size.' AND tire_pattern = '.$id_pattern));
                        foreach ($tire_sales as $tire) {
                            $ton -= $tire->volume;
                        }

                        $data_old = array(
                            'where' => 'tire_brand = '.$id_brand.' AND tire_size = '.$id_size.' AND tire_pattern = '.$id_pattern.' AND start_date <= '.$dauthang,
                            'order_by' => 'start_date',
                            'order' => 'DESC, tire_import_id DESC',
                            'limit' => 1,
                        );
                        $tire_imports = $tireimport->getAllTire($data_old);
                        $soluong = 0; $gia = 0;
                        foreach ($tire_imports as $tire) {
                            $soluong = $ton;
                            $gia = $ton*$tire->tire_price;
                        }
                        $soluong += $order->tire_number;
                        $gia += $order->tire_price_origin*$order->tire_number;

                        $tireimportdetail->updateTire(array('status'=>0),array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern,'status'=>1));

                        $tire_import_detail_data = array(
                        'tire_brand' => $id_brand,
                        'tire_size' => $id_size,
                        'tire_pattern' => $id_pattern,
                        'tire_price' => $order->tire_price_origin,
                        'tire_price_vat' => $order->tire_tax_vat,
                        'tire_number' => $order->tire_number,
                        'code' => $code,
                        'status' => 1,
                        'import_tire_list' => $order->import_tire_list_id,
                        );
                        $tireimportdetail->createTire($tire_import_detail_data);

                        $tire_import_data = array(
                        'tire_brand' => $id_brand,
                        'tire_size' => $id_size,
                        'tire_pattern' => $id_pattern,
                        'tire_price' => $gia/$soluong,
                        'tire_price_vat' => $order->tire_tax_vat,
                        'code' => $code,
                        'start_date' => $dauthang,
                        'import_tire_list' => $order->import_tire_list_id,
                        );
                        $tireimport->createTire($tire_import_data);
                    }
                    else{
                        $tire_import_detail_data = array(
                        'tire_brand' => $id_brand,
                        'tire_size' => $id_size,
                        'tire_pattern' => $id_pattern,
                        'tire_price' => $order->tire_price_origin,
                        'tire_price_vat' => $order->tire_tax_vat,
                        'tire_number' => $order->tire_number,
                        'code' => $code,
                        'status' => 1,
                        'import_tire_list' => $order->import_tire_list_id,
                        );
                        $tireimportdetail->createTire($tire_import_detail_data);

                        $tire_import_data = array(
                        'tire_brand' => $id_brand,
                        'tire_size' => $id_size,
                        'tire_pattern' => $id_pattern,
                        'tire_price' => $order->tire_price_origin,
                        'tire_price_vat' => $order->tire_tax_vat,
                        'code' => $code,
                        'start_date' => $dauthang,
                        'import_tire_list' => $order->import_tire_list_id,
                        );
                        $tireimport->createTire($tire_import_data);
                    }

                    $data_buy = array(  
                    'code' => $import_orders->import_tire_order_code,
                    'tire_buy_volume' => $order->tire_number,
                    'tire_buy_brand' => $order->tire_brand,
                    'tire_buy_size' => $order->tire_size,
                    'tire_buy_pattern' => $order->tire_pattern,
                    'rate' => $import_orders->import_tire_order_bank_rate,
                    'rate_shipper' => $import_orders->import_tire_order_bank_rate,
                    'date_solow' => $tire_going['tire_going_date'],
                    'date_shipper' => $tire_going['tire_going_date'],
                    'tire_buy_date' => $tire_going['tire_going_date'],
                    'date_manufacture' => $tire_going['tire_going_date'],
                    'import_tire_list' => $order->import_tire_list_id,
                    );

                    $tirebuy->createTire($data_buy);

                    $import_tire_order_model->updateImport(array('import_tire_order_lock'=>1),array('import_tire_order_id'=>$_POST['data']));
                    
                }
                else if ($import_orders->import_tire_order_status==2 || $import_orders->import_tire_order_status==4) {
                    $tire_going = array(
                    'tire_going_date' => strtotime($_POST['date']),
                    'code' => $import_orders->import_tire_order_code,
                    'tire_size' => $order->tire_size,
                    'tire_pattern' => $order->tire_pattern,
                    'tire_brand' => $order->tire_brand,
                    'tire_number' => $order->tire_number,
                    'import_tire_list' => $order->import_tire_list_id,
                    'status' => 0,
                    );
                    if (!$tire_going_model->getTireByWhere(array('import_tire_list' => $order->import_tire_list_id))) {
                        $tire_going_model->createTire($tire_going);
                    }
                    else{
                        $tire_going_model->updateTire($tire_going,array('import_tire_list' => $order->import_tire_list_id));
                    }
                    
                    $tirebuy->queryTire('DELETE FROM tire_buy WHERE import_tire_list='.$order->import_tire_list_id);
                    $tireimport->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$order->import_tire_list_id);
                    $tireimportdetail->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$order->import_tire_list_id);
                    $import_tire_order_model->updateImport(array('import_tire_order_lock'=>0),array('import_tire_order_id'=>$_POST['data']));
                }
                else if ($import_orders->import_tire_order_status==1){
                    $tire_going_model->queryTire('DELETE FROM tire_going WHERE import_tire_list='.$order->import_tire_list_id);
                    $tirebuy->queryTire('DELETE FROM tire_buy WHERE import_tire_list='.$order->import_tire_list_id);
                    $tireimport->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$order->import_tire_list_id);
                    $tireimportdetail->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$order->import_tire_list_id);
                    $import_tire_order_model->updateImport(array('import_tire_order_lock'=>0),array('import_tire_order_id'=>$_POST['data']));
                }
            }

            $giatrinhap = $import_orders->import_tire_order_sum+$import_orders->import_tire_order_tax+$import_orders->import_tire_order_logistics+$import_orders->import_tire_order_stevedore+$import_orders->import_tire_order_bank_cost+$import_orders->import_tire_order_other_cost;

            if ($import_orders->import_tire_order_status==3) {
                $id_order = $_POST['data'];


                
                $sale_data = $importtire_model->getSaleByWhere(array('import_tire_order' => $id_order));
                $id_trading = $sale_data->import_tire_id;

                $tongtienphaitra = $import_orders->import_tire_order_sum-round($import_orders->import_tire_order_claim*$import_orders->import_tire_order_bank_rate)+$import_orders->import_tire_order_rate_diff;
                $tongtiendagiam = round($import_orders->import_tire_order_sum_usd_down*$import_orders->import_tire_order_bank_rate);

                // /*Mua lốp*/
                // if($import_orders->import_tire_order_sum_usd_down > 0){

                //     $solow = $vendor_model->getVendorByWhere(array('shipment_vendor_name'=>"Solow"))->shipment_vendor_id;

                //     $data_solow = $sale_vendor->getVendorByWhere(array('vendor' => $solow,'import_tire_order' => $id_order));
                //     ///
                //     $id_order_cost = $data_solow->import_tire_cost_id;
                //     $credits = $account_model->getAccountByWhere(array('account_number'=>'332_solow'));
                //     if (!$credits) {
                //         $account_model->createAccount(array('account_number'=>'332_solow'));
                //         $credit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $credit = $credits->account_id;
                //     }
                //     $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                //     if (!$debits) {
                //         $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //         $debit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $debit = $debits->account_id;
                //     }

                //     $data_additional = array(
                //         'document_date' => $dauthang,
                //         'additional_date' => $dauthang,
                //         'additional_comment' => $import_orders->import_tire_order_comment,
                //         'debit' => $debit,
                //         'credit' => $credit,
                //         'money' => $data_solow->cost+$data_solow->cost_vat,
                //         'code' => $import_orders->import_tire_order_code,
                //         'import_tire_cost' => $id_order_cost,
                //     );
                //     $additional_model->createAdditional($data_additional);
                //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                //     $data_debit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['debit'],
                //         'money' => $data_additional['money'],
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $data_credit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['credit'],
                //         'money' => (0-$data_additional['money']),
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $account_balance_model->createAccount($data_debit);
                //     $account_balance_model->createAccount($data_credit);
                //     ////

                //     $data_mualop = $sale_vendor->getVendorByWhere(array('vendor' => $import_orders->import_tire_order_seller,'import_tire_order' => $id_order));
                //     ///
                //     $id_order_cost = $data_mualop->import_tire_cost_id;
                //     $sellers = $vendor_model->getVendor($import_orders->import_tire_order_seller);
                //     $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                //     $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                //     if (!$credits) {
                //         $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                //         $credit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $credit = $credits->account_id;
                //     }
                //     $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                //     if (!$debits) {
                //         $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //         $debit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $debit = $debits->account_id;
                //     }

                //     $data_additional = array(
                //         'document_date' => $dauthang,
                //         'additional_date' => $dauthang,
                //         'additional_comment' => $import_orders->import_tire_order_comment,
                //         'debit' => $debit,
                //         'credit' => $credit,
                //         'money' => $data_mualop->cost+$data_mualop->cost_vat,
                //         'code' => $import_orders->import_tire_order_code,
                //         'import_tire_cost' => $id_order_cost,
                //     );
                //     $additional_model->createAdditional($data_additional);
                //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                //     $data_debit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['debit'],
                //         'money' => $data_additional['money'],
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $data_credit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['credit'],
                //         'money' => (0-$data_additional['money']),
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $account_balance_model->createAccount($data_debit);
                //     $account_balance_model->createAccount($data_credit);
                //     ////

                    
                            
                // }
                // else{
                    
                //     $data_mualop = $sale_vendor->getVendorByWhere(array('vendor' => $import_orders->import_tire_order_seller,'import_tire_order' => $id_order));
                //     ///
                //     $id_order_cost = $data_mualop->import_tire_cost_id;
                //     $sellers = $vendor_model->getVendor($import_orders->import_tire_order_seller);
                //     $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                //     $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                //     if (!$credits) {
                //         $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                //         $credit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $credit = $credits->account_id;
                //     }
                //     $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                //     if (!$debits) {
                //         $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //         $debit = $account_model->getLastAccount()->account_id;
                //     }
                //     else{
                //         $debit = $debits->account_id;
                //     }

                //     $data_additional = array(
                //         'document_date' => $dauthang,
                //         'additional_date' => $dauthang,
                //         'additional_comment' => $import_orders->import_tire_order_comment,
                //         'debit' => $debit,
                //         'credit' => $credit,
                //         'money' => $data_mualop->cost+$data_mualop->cost_vat,
                //         'code' => $import_orders->import_tire_order_code,
                //         'import_tire_cost' => $id_order_cost,
                //     );
                //     $additional_model->createAdditional($data_additional);
                //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                //     $data_debit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['debit'],
                //         'money' => $data_additional['money'],
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $data_credit = array(
                //         'account_balance_date' => $data_additional['additional_date'],
                //         'account' => $data_additional['credit'],
                //         'money' => (0-$data_additional['money']),
                //         'week' => (int)date('W', $data_additional['additional_date']),
                //         'year' => (int)date('Y', $data_additional['additional_date']),
                //         'additional' => $additional_id,
                //     );
                //     $account_balance_model->createAccount($data_debit);
                //     $account_balance_model->createAccount($data_credit);
                //     ////
                    
                // }
                // /*Mua lốp*/
                // ///
                $chenhlech = round($import_orders->import_tire_order_claim*$import_orders->import_tire_order_bank_rate)-$import_orders->import_tire_order_rate_diff;
                if ($chenhlech>=0) {
                    $credits = $account_model->getAccountByWhere(array('account_number'=>'711'));
                    if (!$credits) {
                        $account_model->createAccount(array('account_number'=>'711'));
                        $credit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $credit = $credits->account_id;
                    }
                    $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                    if (!$debits) {
                        $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                        $debit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $debit = $debits->account_id;
                    }

                    $data_additional = array(
                        'document_date' => $dauthang,
                        'additional_date' => $dauthang,
                        'additional_comment' => $import_orders->import_tire_order_comment,
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $chenhlech,
                        'code' => $import_orders->import_tire_order_code,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);
                }
                else{
                    $debits = $account_model->getAccountByWhere(array('account_number'=>'811'));
                    if (!$debits) {
                        $account_model->createAccount(array('account_number'=>'811'));
                        $debit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $debit = $debits->account_id;
                    }
                    $credits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                    if (!$credits) {
                        $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                        $credit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $credit = $credits->account_id;
                    }

                    $data_additional = array(
                        'document_date' => $dauthang,
                        'additional_date' => $dauthang,
                        'additional_comment' => $import_orders->import_tire_order_comment,
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => (0-$chenhlech),
                        'code' => $import_orders->import_tire_order_code,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);
                }
                
                // ////

                // /*THuế*/
                // $thue = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"THUE"))->shipment_vendor_id;
                
                // $data_thue = $sale_vendor->getVendorByWhere(array('vendor' => $thue,'import_tire_order' => $id_order));
                // ///
                // $id_order_cost = $data_thue->import_tire_cost_id;
                // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_thue'));
                // if (!$credits) {
                //     $account_model->createAccount(array('account_number'=>'331_thue','account_parent'=>$tk_331));
                //     $credit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $credit = $credits->account_id;
                // }
                // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                // if (!$debits) {
                //     $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //     $debit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $debit = $debits->account_id;
                // }

                // $data_additional = array(
                //     'document_date' => $dauthang,
                //     'additional_date' => $dauthang,
                //     'additional_comment' => $import_orders->import_tire_order_comment,
                //     'debit' => $debit,
                //     'credit' => $credit,
                //     'money' => $data_thue->cost+$data_thue->cost_vat,
                //     'code' => $import_orders->import_tire_order_code,
                //     'import_tire_cost' => $id_order_cost,
                // );
                // $additional_model->createAdditional($data_additional);
                // $additional_id = $additional_model->getLastAdditional()->additional_id;

                // $data_debit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['debit'],
                //     'money' => $data_additional['money'],
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $data_credit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['credit'],
                //     'money' => (0-$data_additional['money']),
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $account_balance_model->createAccount($data_debit);
                // $account_balance_model->createAccount($data_credit);
                // ////
                
                // /*Thuế*/

                /*Logistics*/
                $philogs = $import_orders->import_tire_order_logistics;
                $phibx = $import_orders->import_tire_order_stevedore;
                
                
                $data_logs = $sale_vendor->getVendorByWhere(array('vendor' => $import_orders->import_tire_order_supplier,'import_tire_order' => $id_order));
                ///
                $id_order_cost = $data_logs->import_tire_cost_id;
                $sellers = $vendor_model->getVendor($import_orders->import_tire_order_supplier);
                $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                if ($seller_name=="LandOcean" || $seller_name=="landocean" || $seller_name=="Land Ocean" || $seller_name=="land ocean") {
                    $seller_name = 'ocean';
                }
                $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                if (!$credits) {
                    $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                    $credit = $account_model->getLastAccount()->account_id;
                }
                else{
                    $credit = $credits->account_id;
                }
                $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                if (!$debits) {
                    $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                    $debit = $account_model->getLastAccount()->account_id;
                }
                else{
                    $debit = $debits->account_id;
                }

                $data_additional = array(
                    'document_date' => $dauthang,
                    'additional_date' => $dauthang,
                    'additional_comment' => $import_orders->import_tire_order_comment,
                    'debit' => $debit,
                    'credit' => $credit,
                    'money' => $data_logs->cost+$data_logs->cost_vat,
                    'code' => $import_orders->import_tire_order_code,
                    'import_tire_cost' => $id_order_cost,
                );
                $additional_model->createAdditional($data_additional);
                $additional_id = $additional_model->getLastAdditional()->additional_id;

                $data_debit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['debit'],
                    'money' => $data_additional['money'],
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );
                $data_credit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['credit'],
                    'money' => (0-$data_additional['money']),
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                ////
                
                // /*Logistics*/
                // /*Phí bốc xếp*/
                // $phibocxep = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"BOCXEP"))->shipment_vendor_id;
                
                // $data_bx = $sale_vendor->getVendorByWhere(array('vendor' => $phibocxep,'import_tire_order' => $id_order));
                // ///
                // $id_order_cost = $data_bx->import_tire_cost_id;
                
                // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_VC'));
                // if (!$credits) {
                //     $account_model->createAccount(array('account_number'=>'331_VC'));
                //     $credit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $credit = $credits->account_id;
                // }
                // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                // if (!$debits) {
                //     $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //     $debit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $debit = $debits->account_id;
                // }

                // $data_additional = array(
                //     'document_date' => $dauthang,
                //     'additional_date' => $dauthang,
                //     'additional_comment' => $import_orders->import_tire_order_comment,
                //     'debit' => $debit,
                //     'credit' => $credit,
                //     'money' => $data_bx->cost+$data_bx->cost_vat,
                //     'code' => $import_orders->import_tire_order_code,
                //     'import_tire_cost' => $id_order_cost,
                // );
                // $additional_model->createAdditional($data_additional);
                // $additional_id = $additional_model->getLastAdditional()->additional_id;

                // $data_debit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['debit'],
                //     'money' => $data_additional['money'],
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $data_credit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['credit'],
                //     'money' => (0-$data_additional['money']),
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $account_balance_model->createAccount($data_debit);
                // $account_balance_model->createAccount($data_credit);
                // ////
                
                // /*Phí bốc xếp*/

                // /*Phí chuyển tiền*/
                // $phick = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHICK"))->shipment_vendor_id;
                
                // $data_phick = $sale_vendor->getVendorByWhere(array('vendor' => $phick,'import_tire_order' => $id_order));
                // ///
                // $id_order_cost = $data_phick->import_tire_cost_id;
                
                // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_phick'));
                // if (!$credits) {
                //     $account_model->createAccount(array('account_number'=>'331_phick','account_parent'=>$tk_331));
                //     $credit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $credit = $credits->account_id;
                // }
                // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                // if (!$debits) {
                //     $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //     $debit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $debit = $debits->account_id;
                // }

                // $data_additional = array(
                //     'document_date' => $dauthang,
                //     'additional_date' => $dauthang,
                //     'additional_comment' => $import_orders->import_tire_order_comment,
                //     'debit' => $debit,
                //     'credit' => $credit,
                //     'money' => $data_phick->cost+$data_phick->cost_vat,
                //     'code' => $import_orders->import_tire_order_code,
                //     'import_tire_cost' => $id_order_cost,
                // );
                // $additional_model->createAdditional($data_additional);
                // $additional_id = $additional_model->getLastAdditional()->additional_id;

                // $data_debit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['debit'],
                //     'money' => $data_additional['money'],
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $data_credit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['credit'],
                //     'money' => (0-$data_additional['money']),
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $account_balance_model->createAccount($data_debit);
                // $account_balance_model->createAccount($data_credit);
                // ////
                
                // /*Phí chuyển tiền*/

                // /*Phí khác*/
                // $phikhac = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHIKHAC"))->shipment_vendor_id;
                
                // $data_phikhac = $sale_vendor->getVendorByWhere(array('vendor' => $phikhac,'import_tire_order' => $id_order));
                // ///
                // $id_order_cost = $data_phikhac->import_tire_cost_id;
                // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_khac'));
                // if (!$credits) {
                //     $account_model->createAccount(array('account_number'=>'331_khac','account_parent'=>$tk_331));
                //     $credit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $credit = $credits->account_id;
                // }
                // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$import_orders->import_tire_order_code));
                // if (!$debits) {
                //     $account_model->createAccount(array('account_number'=>'156_'.$import_orders->import_tire_order_code,'account_name'=>'Lô '.$import_orders->import_tire_order_code,'account_parent'=>$tk_156));
                //     $debit = $account_model->getLastAccount()->account_id;
                // }
                // else{
                //     $debit = $debits->account_id;
                // }

                // $data_additional = array(
                //     'document_date' => $dauthang,
                //     'additional_date' => $dauthang,
                //     'additional_comment' => $import_orders->import_tire_order_comment,
                //     'debit' => $debit,
                //     'credit' => $credit,
                //     'money' => $data_phikhac->cost+$data_phikhac->cost_vat,
                //     'code' => $import_orders->import_tire_order_code,
                //     'import_tire_cost' => $id_order_cost,
                // );
                // $additional_model->createAdditional($data_additional);
                // $additional_id = $additional_model->getLastAdditional()->additional_id;

                // $data_debit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['debit'],
                //     'money' => $data_additional['money'],
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $data_credit = array(
                //     'account_balance_date' => $data_additional['additional_date'],
                //     'account' => $data_additional['credit'],
                //     'money' => (0-$data_additional['money']),
                //     'week' => (int)date('W', $data_additional['additional_date']),
                //     'year' => (int)date('Y', $data_additional['additional_date']),
                //     'additional' => $additional_id,
                // );
                // $account_balance_model->createAccount($data_debit);
                // $account_balance_model->createAccount($data_credit);
                ////
                
                /*Phí chuyển tiền*/

                ////////////////////

                $tk_156 = $account_model->getAccountByWhere(array('account_number'=>'156'))->account_id;
                
                $additionals = $additional_model->getAdditionalByWhere(array('import_tire_order'=>$_POST['data']));
                if ($additionals) {
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);
                }

                $credit_156 = $account_model->getAccountByWhere(array('account_number'=>'156_'.$code));
                if (!$credit_156) {
                    $account_model->createAccount(array('account_number'=>'156_'.$code,'account_name'=>'Lô '.$code,'account_parent'=>$tk_156));
                    $credit_156 = $account_model->getLastAccount();
                }

                $debit_156 = $account_model->getAccountByWhere(array('account_number'=>'1561'));

                $data_additional = array(
                    'document_date' => $dauthang,
                    'additional_date' => $dauthang,
                    'additional_comment' => 'Nhập hàng vào kho',
                    'debit' => $debit_156->account_id,
                    'credit' => $credit_156->account_id,
                    'money' => $giatrinhap,
                    'code' => $code,
                    'import_tire_order' => $_POST['data'],
                );
                $additional_model->createAdditional($data_additional);
                $additional_id = $additional_model->getLastAdditional()->additional_id;

                $data_debit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['debit'],
                    'money' => $data_additional['money'],
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );
                $data_credit = array(
                    'account_balance_date' => $data_additional['additional_date'],
                    'account' => $data_additional['credit'],
                    'money' => (0-$data_additional['money']),
                    'week' => (int)date('W', $data_additional['additional_date']),
                    'year' => (int)date('Y', $data_additional['additional_date']),
                    'additional' => $additional_id,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
            }
            else{
                $additionals = $additional_model->getAdditionalByWhere(array('import_tire_order'=>$_POST['data']));
                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);

                $costs = $sale_vendor->getAllVendor(array('where' => 'import_tire_order='.$_POST['data']));
                foreach ($costs as $cost) {
                    $additionals = $additional_model->getAdditionalByWhere(array('import_tire_cost'=>$cost->import_tire_cost_id));
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);
                }

                $credits = $account_model->getAccountByWhere(array('account_number'=>'711'));
                if (!$credits) {
                    $account_model->createAccount(array('account_number'=>'711'));
                    $credit = $account_model->getLastAccount()->account_id;
                }
                else{
                    $credit = $credits->account_id;
                }

                $debits = $account_model->getAccountByWhere(array('account_number'=>'811'));
                if (!$debits) {
                    $account_model->createAccount(array('account_number'=>'811'));
                    $debit = $account_model->getLastAccount()->account_id;
                }
                else{
                    $debit = $debits->account_id;
                }

                $additionals = $additional_model->getAdditionalByWhere(array('credit'=>$credit,'code'=>$import_orders->import_tire_order_code));
                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);

                $additionals = $additional_model->getAdditionalByWhere(array('debit'=>$debit,'code'=>$import_orders->import_tire_order_code));
                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);

            }

            echo "Cập nhật thành công";
        }
    }
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $vendor_model = $this->model->get('shipmentvendorModel');
            $import_tire_price_model = $this->model->get('importtirepriceModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $sale = $this->model->get('importtireModel');
            $sale_vendor = $this->model->get('importtirecostModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $tire_going_model = $this->model->get('tiregoingModel');
            $tire_going_order_model = $this->model->get('tiregoingorderModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $account_model = $this->model->get('accountModel');
            $tirebuy = $this->model->get('tirebuyModel');
            $tireimportdetail = $this->model->get('tireimportdetailModel');
            $tireimport = $this->model->get('tireimportModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $tk_331 = $account_model->getAccountByWhere(array('account_number'=>'331'))->account_id;
            $tk_156 = $account_model->getAccountByWhere(array('account_number'=>'156'))->account_id;

            $data = array(
                        
                'import_tire_order_bill_number' => trim($_POST['import_tire_order_bill_number']),
                'import_tire_order_contract_number' => trim($_POST['import_tire_order_contract_number']),
                'import_tire_order_code' => trim($_POST['import_tire_order_code']),
                'import_tire_order_comment' => trim($_POST['import_tire_order_comment']),
                'import_tire_order_seller' => trim($_POST['import_tire_order_seller']),
                'import_tire_order_port_from' => trim($_POST['import_tire_order_port_from']),
                'import_tire_order_port_to' => trim($_POST['import_tire_order_port_to']),
                'import_tire_order_supplier' => trim($_POST['import_tire_order_supplier']),
                'import_tire_order_type' => trim($_POST['import_tire_order_type']),
                'import_tire_order_date' => strtotime(str_replace('/', '-', $_POST['import_tire_order_date'])),
                'import_tire_order_expect_date' => strtotime(str_replace('/', '-', $_POST['import_tire_order_expect_date'])),
                'import_tire_order_claim' => str_replace(',', '', $_POST['import_tire_order_claim']),
                'import_tire_order_rate_diff' => str_replace(',', '', $_POST['import_tire_order_rate_diff']),
                'import_tire_order_bank_rate' => str_replace(',', '', $_POST['import_tire_order_bank_rate']),
                'import_tire_order_tax_rate' => str_replace(',', '', $_POST['import_tire_order_tax_rate']),
                'import_tire_order_bank_cost' => str_replace(',', '', $_POST['import_tire_order_bank_cost']),
                'import_tire_order_other_cost' => str_replace(',', '', $_POST['import_tire_order_other_cost']),
                'import_tire_order_other_cost_comment' => trim($_POST['import_tire_order_other_cost_comment']),
                'import_tire_order_oceanfreight' => str_replace(',', '', $_POST['import_tire_order_oceanfreight']),
                'import_tire_order_lift' => str_replace(',', '', $_POST['import_tire_order_lift']),
                'import_tire_order_sum' => str_replace(',', '', $_POST['import_tire_order_sum']),
                'import_tire_order_tax' => str_replace(',', '', $_POST['import_tire_order_tax']),
                'import_tire_order_logistics' => str_replace(',', '', $_POST['import_tire_order_logistics']),
                'import_tire_order_sum_usd' => str_replace(',', '', $_POST['import_tire_order_sum_usd']),
                'import_tire_order_sum_usd_down' => str_replace(',', '', $_POST['import_tire_order_sum_usd_down']),
                'import_tire_order_total' => str_replace(',', '', $_POST['import_tire_order_total']),
                'import_tire_order_stevedore' => str_replace(',', '', $_POST['import_tire_order_stevedore']),
                'import_tire_order_lift_1cont' => str_replace(',', '', $_POST['import_tire_order_lift_1cont']),
                'import_tire_order_lift_2cont' => str_replace(',', '', $_POST['import_tire_order_lift_2cont']),
                'import_tire_order_lift_plus' => str_replace(',', '', $_POST['import_tire_order_lift_plus']),
                'import_tire_order_truck_1cont' => str_replace(',', '', $_POST['import_tire_order_truck_1cont']),
                'import_tire_order_truck_2cont' => str_replace(',', '', $_POST['import_tire_order_truck_2cont']),
                'import_tire_order_truck_plus' => str_replace(',', '', $_POST['import_tire_order_truck_plus']),
                'import_tire_order_bill_1cont' => str_replace(',', '', $_POST['import_tire_order_bill_1cont']),
                'import_tire_order_bill_2cont' => str_replace(',', '', $_POST['import_tire_order_bill_2cont']),
                'import_tire_order_bill_plus' => str_replace(',', '', $_POST['import_tire_order_bill_plus']),
                'import_tire_order_tthq_1cont' => str_replace(',', '', $_POST['import_tire_order_tthq_1cont']),
                'import_tire_order_tthq_2cont' => str_replace(',', '', $_POST['import_tire_order_tthq_2cont']),
                'import_tire_order_tthq_plus' => str_replace(',', '', $_POST['import_tire_order_tthq_plus']),
                'import_tire_order_stevedore_small' => str_replace(',', '', $_POST['import_tire_order_stevedore_small']),
                'import_tire_order_stevedore_large' => str_replace(',', '', $_POST['import_tire_order_stevedore_large']),
                'import_tire_order_cont_total' => trim($_POST['import_tire_order_cont_total']),
            );
            
            if ($data['import_tire_order_seller'] == "" || $data['import_tire_order_seller'] == 0) {
                $seller = $vendor_model->getVendorByWhere(array('shipment_vendor_name'=>trim($_POST['import_tire_order_seller_name'])));
                if ($seller) {
                    $data['import_tire_order_seller'] = $seller->shipment_vendor_id;
                }
                else{
                    $data_vendor = array(
                        
                        'shipment_vendor_name' => trim($_POST['import_tire_order_seller_name']),
                        'company_name' => trim($_POST['import_tire_order_seller_name']),
                        'vendor_type' => 1,
                    );
                    $vendor_model->createVendor($data_vendor);
                    $data['import_tire_order_seller'] = $vendor_model->getLastVendor()->shipment_vendor_id;
                }
            }
            if ($data['import_tire_order_supplier'] == "" || $data['import_tire_order_supplier'] == 0) {
                $supplier = $vendor_model->getVendorByWhere(array('shipment_vendor_name'=>trim($_POST['import_tire_order_supplier_name'])));
                if ($supplier) {
                    $data['import_tire_order_supplier'] = $supplier->shipment_vendor_id;
                }
                else{
                    $data_vendor = array(
                        
                        'shipment_vendor_name' => trim($_POST['import_tire_order_supplier_name']),
                        'company_name' => trim($_POST['import_tire_order_supplier_name']),
                        'vendor_type' => 1,
                    );
                    $vendor_model->createVendor($data_vendor);
                    $data['import_tire_order_supplier'] = $vendor_model->getLastVendor()->shipment_vendor_id;
                }
            }

            if ($_POST['yes'] != "") {
                    $import_orders = $import_tire_order_model->getImport($_POST['yes']);

                    $import_tire_order_model->updateImport($data,array('import_tire_order_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $id_order = $_POST['yes'];

                    

                    $data_sale = array(
                        'import_tire_date' => $data['import_tire_order_date'],
                        'code' => $data['import_tire_order_code'],
                        'comment' => $data['import_tire_order_comment'],
                        'expect_date' => $data['import_tire_order_expect_date'],
                        'import_tire_lock' => 1,
                        'import_type' => 1,
                        'sale' => $_SESSION['userid_logined'],
                        'cost' => 0,
                        'cost_vat' => 0,
                        'import_tire_order' => $id_order,
                    );

                    $sale->updateSale($data_sale,array('import_tire_order' => $id_order));
                    /*********************/
                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;


                    $id_trading = $sale->getSaleByWhere(array('import_tire_order' => $id_order))->import_tire_id;
                    $sale_data = $sale->getSale($id_trading);

                    $tongtienphaitra = $data['import_tire_order_sum']-round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate'])+$data['import_tire_order_rate_diff'];
                    $tongtiendagiam = round($data['import_tire_order_sum_usd_down']*$data['import_tire_order_bank_rate']);

                    /*Mua lốp*/
                    if($data['import_tire_order_sum_usd_down'] > 0){

                        $solow = $vendor_model->getVendorByWhere(array('shipment_vendor_name'=>"Solow"))->shipment_vendor_id;

                        $data_solow = array(
                            'trading' => $id_trading,
                            'vendor' => $solow,
                            'type' => 1,
                            'cost' => $tongtienphaitra-$tongtiendagiam,
                            'cost_vat' => null,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Chuyển Solow',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $data_mualop = array(
                            'trading' => $id_trading,
                            'vendor' => $data['import_tire_order_seller'],
                            'type' => 1,
                            'cost' => null,
                            'cost_vat' => $tongtiendagiam,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Tiền mua lốp',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $kvat += $data_solow['cost']+$data_mualop['cost'];
                        $vat += $data_solow['cost_vat']+$data_mualop['cost_vat'];

                        $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$solow))->import_tire_cost_id;
                        $sale_vendor->updateVendor($data_solow,array('import_tire_order' => $id_order,'vendor'=>$solow));
                        ///
                        
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'332_solow'));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'332_solow'));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }
                        // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                        // if (!$add) {
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_date'],
                        //         'additional_date' => $data['import_tire_order_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_solow['cost']+$data_solow['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->createAdditional($data_additional);
                        //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->createAccount($data_debit);
                        //     $account_balance_model->createAccount($data_credit);
                        // }
                        // else{
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_expect_date'],
                        //         'additional_date' => $data['import_tire_order_expect_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_solow['cost']+$data_solow['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                        //     $additional_id = $add->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                        //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                        // }
                        
                        ////
                        $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller))->import_tire_cost_id;
                        $sale_vendor->updateVendor($data_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));
                        ///
                        
                        // $sellers = $vendor_model->getVendor($data['import_tire_order_seller']);
                        // $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        // if (!$debits) {
                        //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                        //     $debit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $debit = $debits->account_id;
                        // }
                        // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                        // if (!$add) {
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_date'],
                        //         'additional_date' => $data['import_tire_order_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->createAdditional($data_additional);
                        //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->createAccount($data_debit);
                        //     $account_balance_model->createAccount($data_credit);
                        // }
                        // else{
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_expect_date'],
                        //         'additional_date' => $data['import_tire_order_expect_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                        //     $additional_id = $add->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                        //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                        // }
                        
                        ////
                        ///////////
                        $owe_solow = array(
                            'owe_date' => $data_solow['expect_date'],
                            'vendor' => $data_solow['vendor'],
                            'money' => $data_solow['cost'],
                            'week' => (int)date('W',$data_solow['expect_date']),
                            'year' => (int)date('Y',$data_solow['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->updateOwe($owe_solow,array('import_tire_order' => $id_order,'vendor'=>$solow));

                        $payable_solow = array(
                            'vendor' => $data_solow['vendor'],
                            'money' => $data_solow['cost'],
                            'payable_date' => $data_solow['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_solow['expect_date'],
                            'week' => (int)date('W',$data_solow['expect_date']),
                            'year' => (int)date('Y',$data_solow['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Solow',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>0,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->updateCosts($payable_solow,array('import_tire_order' => $id_order,'vendor'=>$solow));
                        //////////////
                        $owe_mualop = array(
                            'owe_date' => $data_mualop['expect_date'],
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->updateOwe($owe_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));

                        $payable_mualop = array(
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'payable_date' => $data_mualop['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_mualop['expect_date'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Tiền mua lốp',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>1,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->updateCosts($payable_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));
                                
                    }
                    else{
                        $data_mualop = array(
                            'trading' => $id_trading,
                            'vendor' => $data['import_tire_order_seller'],
                            'type' => 1,
                            'cost' => null,
                            'cost_vat' => $tongtienphaitra,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Tiền mua lốp',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $kvat += $data_mualop['cost'];
                        $vat += $data_mualop['cost_vat'];

                        
                        $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller))->import_tire_cost_id;
                        $sale_vendor->updateVendor($data_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));
                        ///
                        
                        // $sellers = $vendor_model->getVendor($data['import_tire_order_seller']);
                        // $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }
                        // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                        // if (!$add) {
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_date'],
                        //         'additional_date' => $data['import_tire_order_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->createAdditional($data_additional);
                        //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->createAccount($data_debit);
                        //     $account_balance_model->createAccount($data_credit);
                        // }
                        // else{
                        //     $data_additional = array(
                        //         'document_date' => $data['import_tire_order_expect_date'],
                        //         'additional_date' => $data['import_tire_order_expect_date'],
                        //         'additional_comment' => $data['import_tire_order_comment'],
                        //         'debit' => $debit,
                        //         'credit' => $credit,
                        //         'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                        //         'code' => $data['import_tire_order_code'],
                        //         'import_tire_cost' => $id_order_cost,
                        //     );
                        //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                        //     $additional_id = $add->additional_id;

                        //     $data_debit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['debit'],
                        //         'money' => $data_additional['money'],
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $data_credit = array(
                        //         'account_balance_date' => $data_additional['additional_date'],
                        //         'account' => $data_additional['credit'],
                        //         'money' => (0-$data_additional['money']),
                        //         'week' => (int)date('W', $data_additional['additional_date']),
                        //         'year' => (int)date('Y', $data_additional['additional_date']),
                        //         'additional' => $additional_id,
                        //     );
                        //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                        //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                        // }
                        
                        ////
                        /////////
                        $owe_mualop = array(
                            'owe_date' => $data_mualop['expect_date'],
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->updateOwe($owe_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));

                        $payable_mualop = array(
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'payable_date' => $data_mualop['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_mualop['expect_date'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Tiền mua lốp',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>1,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->updateCosts($payable_mualop,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_seller));
                    }
                    /*Mua lốp*/

                    $chenhlech = round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate'])-$data['import_tire_order_rate_diff'];

                    if ($chenhlech>=0) {
                        $credits = $account_model->getAccountByWhere(array('account_number'=>'711'));
                        if (!$credits) {
                            $account_model->createAccount(array('account_number'=>'711'));
                            $credit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $credit = $credits->account_id;
                        }
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }

                        $add = $additional_model->getAdditionalByWhere(array('credit' => $credit,'code' => $import_orders->import_tire_order_code));
                        if (!$add) {
                            // $data_additional = array(
                            //     'document_date' => $data['import_tire_order_date'],
                            //     'additional_date' => $data['import_tire_order_date'],
                            //     'additional_comment' => $data['import_tire_order_comment'],
                            //     'debit' => $debit,
                            //     'credit' => $credit,
                            //     'money' => round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate']),
                            //     'code' => $data['import_tire_order_code'],
                            // );
                            // $additional_model->createAdditional($data_additional);
                            // $additional_id = $additional_model->getLastAdditional()->additional_id;

                            // $data_debit = array(
                            //     'account_balance_date' => $data_additional['additional_date'],
                            //     'account' => $data_additional['debit'],
                            //     'money' => $data_additional['money'],
                            //     'week' => (int)date('W', $data_additional['additional_date']),
                            //     'year' => (int)date('Y', $data_additional['additional_date']),
                            //     'additional' => $additional_id,
                            // );
                            // $data_credit = array(
                            //     'account_balance_date' => $data_additional['additional_date'],
                            //     'account' => $data_additional['credit'],
                            //     'money' => (0-$data_additional['money']),
                            //     'week' => (int)date('W', $data_additional['additional_date']),
                            //     'year' => (int)date('Y', $data_additional['additional_date']),
                            //     'additional' => $additional_id,
                            // );
                            // $account_balance_model->createAccount($data_debit);
                            // $account_balance_model->createAccount($data_credit);
                        }
                        else{
                            $data_additional = array(
                                'document_date' => $data['import_tire_order_expect_date'],
                                'additional_date' => $data['import_tire_order_expect_date'],
                                'additional_comment' => $data['import_tire_order_comment'],
                                'debit' => $debit,
                                'credit' => $credit,
                                'money' => $chenhlech,
                                'code' => $data['import_tire_order_code'],
                            );
                            $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                            $additional_id = $add->additional_id;

                            $data_debit = array(
                                'account_balance_date' => $data_additional['additional_date'],
                                'account' => $data_additional['debit'],
                                'money' => $data_additional['money'],
                                'week' => (int)date('W', $data_additional['additional_date']),
                                'year' => (int)date('Y', $data_additional['additional_date']),
                                'additional' => $additional_id,
                            );
                            $data_credit = array(
                                'account_balance_date' => $data_additional['additional_date'],
                                'account' => $data_additional['credit'],
                                'money' => (0-$data_additional['money']),
                                'week' => (int)date('W', $data_additional['additional_date']),
                                'year' => (int)date('Y', $data_additional['additional_date']),
                                'additional' => $additional_id,
                            );
                            $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                            $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                        }
                    }
                    else{
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'811'));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'811'));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }
                        $credits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$credits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $credit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $credit = $credits->account_id;
                        }

                        $add = $additional_model->getAdditionalByWhere(array('debit' => $debit,'code' => $import_orders->import_tire_order_code));
                        if (!$add) {
                            // $data_additional = array(
                            //     'document_date' => $data['import_tire_order_date'],
                            //     'additional_date' => $data['import_tire_order_date'],
                            //     'additional_comment' => $data['import_tire_order_comment'],
                            //     'debit' => $debit,
                            //     'credit' => $credit,
                            //     'money' => round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate']),
                            //     'code' => $data['import_tire_order_code'],
                            // );
                            // $additional_model->createAdditional($data_additional);
                            // $additional_id = $additional_model->getLastAdditional()->additional_id;

                            // $data_debit = array(
                            //     'account_balance_date' => $data_additional['additional_date'],
                            //     'account' => $data_additional['debit'],
                            //     'money' => $data_additional['money'],
                            //     'week' => (int)date('W', $data_additional['additional_date']),
                            //     'year' => (int)date('Y', $data_additional['additional_date']),
                            //     'additional' => $additional_id,
                            // );
                            // $data_credit = array(
                            //     'account_balance_date' => $data_additional['additional_date'],
                            //     'account' => $data_additional['credit'],
                            //     'money' => (0-$data_additional['money']),
                            //     'week' => (int)date('W', $data_additional['additional_date']),
                            //     'year' => (int)date('Y', $data_additional['additional_date']),
                            //     'additional' => $additional_id,
                            // );
                            // $account_balance_model->createAccount($data_debit);
                            // $account_balance_model->createAccount($data_credit);
                        }
                        else{
                            $data_additional = array(
                                'document_date' => $data['import_tire_order_expect_date'],
                                'additional_date' => $data['import_tire_order_expect_date'],
                                'additional_comment' => $data['import_tire_order_comment'],
                                'debit' => $debit,
                                'credit' => $credit,
                                'money' => $chenhlech,
                                'code' => $data['import_tire_order_code'],
                            );
                            $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                            $additional_id = $add->additional_id;

                            $data_debit = array(
                                'account_balance_date' => $data_additional['additional_date'],
                                'account' => $data_additional['debit'],
                                'money' => $data_additional['money'],
                                'week' => (int)date('W', $data_additional['additional_date']),
                                'year' => (int)date('Y', $data_additional['additional_date']),
                                'additional' => $additional_id,
                            );
                            $data_credit = array(
                                'account_balance_date' => $data_additional['additional_date'],
                                'account' => $data_additional['credit'],
                                'money' => (0-$data_additional['money']),
                                'week' => (int)date('W', $data_additional['additional_date']),
                                'year' => (int)date('Y', $data_additional['additional_date']),
                                'additional' => $additional_id,
                            );
                            $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                            $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                        }
                    }

                     


                    
                    ////

                    /*THuế*/
                    $thue = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"THUE"))->shipment_vendor_id;
                    $data_thue = array(
                        'trading' => $id_trading,
                        'vendor' => $thue,
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $data['import_tire_order_tax'],
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Tiền thuế',
                        'check_cost'=>2,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_thue['cost'];
                    $vat += $data_thue['cost_vat'];

                    
                    $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$thue))->import_tire_cost_id;
                    $sale_vendor->updateVendor($data_thue,array('import_tire_order' => $id_order,'vendor'=>$thue));
                    ///
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_thue'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_thue','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }
                    // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                    // if (!$add) {
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_date'],
                    //         'additional_date' => $data['import_tire_order_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_thue['cost']+$data_thue['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->createAdditional($data_additional);
                    //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->createAccount($data_debit);
                    //     $account_balance_model->createAccount($data_credit);
                    // }
                    // else{
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_expect_date'],
                    //         'additional_date' => $data['import_tire_order_expect_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_thue['cost']+$data_thue['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                    //     $additional_id = $add->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                    //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    // }
                    
                    ////
                    /////////
                    $owe_thue = array(
                        'owe_date' => $data_thue['expect_date'],
                        'vendor' => $data_thue['vendor'],
                        'money' => $data_thue['cost_vat'],
                        'week' => (int)date('W',$data_thue['expect_date']),
                        'year' => (int)date('Y',$data_thue['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->updateOwe($owe_thue,array('import_tire_order' => $id_order,'vendor'=>$thue));

                    $payable_thue = array(
                        'vendor' => $data_thue['vendor'],
                        'money' => $data_thue['cost_vat'],
                        'payable_date' => $data_thue['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_thue['expect_date'],
                        'week' => (int)date('W',$data_thue['expect_date']),
                        'year' => (int)date('Y',$data_thue['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Nộp thuế',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>2,
                        'import_tire_order' => $id_order,
                    );
                    $payable->updateCosts($payable_thue,array('import_tire_order' => $id_order,'vendor'=>$thue));
                    /*Thuế*/

                    /*Logistics*/
                    $philogs = $data['import_tire_order_logistics'];
                    $phibx = $data['import_tire_order_stevedore'];
                    
                    $data_logs = array(
                        'trading' => $id_trading,
                        'vendor' => $data['import_tire_order_supplier'],
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $philogs,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Chi phí logistics',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_logs['cost'];
                    $vat += $data_logs['cost_vat'];

                    
                    $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_supplier))->import_tire_cost_id;
                    $sale_vendor->updateVendor($data_logs,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_supplier));
                    ///
                    
                    $sellers = $vendor_model->getVendor($data['import_tire_order_supplier']);
                    $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                    if ($seller_name=="LandOcean" || $seller_name=="landocean" || $seller_name=="Land Ocean" || $seller_name=="land ocean") {
                        $seller_name = "ocean";
                    }
                    $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                    if (!$credits) {
                        $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                        $credit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $credit = $credits->account_id;
                    }
                    $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    if (!$debits) {
                        $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                        $debit = $account_model->getLastAccount()->account_id;
                    }
                    else{
                        $debit = $debits->account_id;
                    }
                    $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                    if (!$add) {
                        // $data_additional = array(
                        //     'document_date' => $data['import_tire_order_date'],
                        //     'additional_date' => $data['import_tire_order_date'],
                        //     'additional_comment' => $data['import_tire_order_comment'],
                        //     'debit' => $debit,
                        //     'credit' => $credit,
                        //     'money' => $data_logs['cost']+$data_logs['cost_vat'],
                        //     'code' => $data['import_tire_order_code'],
                        //     'import_tire_cost' => $id_order_cost,
                        // );
                        // $additional_model->createAdditional($data_additional);
                        // $additional_id = $additional_model->getLastAdditional()->additional_id;

                        // $data_debit = array(
                        //     'account_balance_date' => $data_additional['additional_date'],
                        //     'account' => $data_additional['debit'],
                        //     'money' => $data_additional['money'],
                        //     'week' => (int)date('W', $data_additional['additional_date']),
                        //     'year' => (int)date('Y', $data_additional['additional_date']),
                        //     'additional' => $additional_id,
                        // );
                        // $data_credit = array(
                        //     'account_balance_date' => $data_additional['additional_date'],
                        //     'account' => $data_additional['credit'],
                        //     'money' => (0-$data_additional['money']),
                        //     'week' => (int)date('W', $data_additional['additional_date']),
                        //     'year' => (int)date('Y', $data_additional['additional_date']),
                        //     'additional' => $additional_id,
                        // );
                        // $account_balance_model->createAccount($data_debit);
                        // $account_balance_model->createAccount($data_credit);
                    }
                    else{
                        $data_additional = array(
                            'document_date' => $data['import_tire_order_expect_date'],
                            'additional_date' => $data['import_tire_order_expect_date'],
                            'additional_comment' => $data['import_tire_order_comment'],
                            'debit' => $debit,
                            'credit' => $credit,
                            'money' => $data_logs['cost']+$data_logs['cost_vat'],
                            'code' => $data['import_tire_order_code'],
                            'import_tire_cost' => $id_order_cost,
                        );
                        $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                        $additional_id = $add->additional_id;

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                        $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    }
                    
                    ////
                    /////////
                    $owe_logs = array(
                        'owe_date' => $data_logs['expect_date'],
                        'vendor' => $data_logs['vendor'],
                        'money' => $data_logs['cost_vat'],
                        'week' => (int)date('W',$data_logs['expect_date']),
                        'year' => (int)date('Y',$data_logs['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->updateOwe($owe_logs,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_supplier));

                    $payable_logs = array(
                        'vendor' => $data_logs['vendor'],
                        'money' => $data_logs['cost_vat'],
                        'payable_date' => $data_logs['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_logs['expect_date'],
                        'week' => (int)date('W',$data_logs['expect_date']),
                        'year' => (int)date('Y',$data_logs['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - TTHQ, Vc, Nâng hạ..',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->updateCosts($payable_logs,array('import_tire_order' => $id_order,'vendor'=>$import_orders->import_tire_order_supplier));
                    /*Logistics*/
                    /*Phí bốc xếp*/
                    $phibocxep = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"BOCXEP"))->shipment_vendor_id;
                    $data_bx = array(
                        'trading' => $id_trading,
                        'vendor' => $phibocxep,
                        'type' => 1,
                        'cost' => $phibx,
                        'cost_vat' => null,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Tiền bốc xếp',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_bx['cost'];
                    $vat += $data_bx['cost_vat'];

                    
                    $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$phibocxep))->import_tire_cost_id;
                    $sale_vendor->updateVendor($data_bx,array('import_tire_order' => $id_order,'vendor'=>$phibocxep));
                    ///
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_VC'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_VC'));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }
                    // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                    // if (!$add) {
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_date'],
                    //         'additional_date' => $data['import_tire_order_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_bx['cost']+$data_bx['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->createAdditional($data_additional);
                    //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->createAccount($data_debit);
                    //     $account_balance_model->createAccount($data_credit);
                    // }
                    // else{
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_expect_date'],
                    //         'additional_date' => $data['import_tire_order_expect_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_bx['cost']+$data_bx['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                    //     $additional_id = $add->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                    //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    // }
                    
                    ////
                    /////////
                    $owe_bx = array(
                        'owe_date' => $data_bx['expect_date'],
                        'vendor' => $data_bx['vendor'],
                        'money' => $data_bx['cost'],
                        'week' => (int)date('W',$data_bx['expect_date']),
                        'year' => (int)date('Y',$data_bx['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->updateOwe($owe_bx,array('import_tire_order' => $id_order,'vendor'=>$phibocxep));

                    $payable_bx = array(
                        'vendor' => $data_bx['vendor'],
                        'money' => $data_bx['cost'],
                        'payable_date' => $data_bx['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_bx['expect_date'],
                        'week' => (int)date('W',$data_bx['expect_date']),
                        'year' => (int)date('Y',$data_bx['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Bốc xếp',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>0,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->updateCosts($payable_bx,array('import_tire_order' => $id_order,'vendor'=>$phibocxep));
                    /*Phí bốc xếp*/

                    /*Phí chuyển tiền*/
                    $phick = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHICK"))->shipment_vendor_id;
                    $data_phick = array(
                        'trading' => $id_trading,
                        'vendor' => $phick,
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $data['import_tire_order_bank_cost'],
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Phí chuyển tiền',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_phick['cost'];
                    $vat += $data_phick['cost_vat'];

                    
                    $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$phick))->import_tire_cost_id;
                    $sale_vendor->updateVendor($data_phick,array('import_tire_order' => $id_order,'vendor'=>$phick));
                    ///
                    
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_phick'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_phick','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }
                    // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                    // if (!$add) {
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_date'],
                    //         'additional_date' => $data['import_tire_order_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_phick['cost']+$data_phick['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->createAdditional($data_additional);
                    //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->createAccount($data_debit);
                    //     $account_balance_model->createAccount($data_credit);
                    // }
                    // else{
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_expect_date'],
                    //         'additional_date' => $data['import_tire_order_expect_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_phick['cost']+$data_phick['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                    //     $additional_id = $add->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                    //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    // }
                    
                    ////
                    /////////
                    $owe_phick = array(
                        'owe_date' => $data_phick['expect_date'],
                        'vendor' => $data_phick['vendor'],
                        'money' => $data_phick['cost_vat'],
                        'week' => (int)date('W',$data_phick['expect_date']),
                        'year' => (int)date('Y',$data_phick['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->updateOwe($owe_phick,array('import_tire_order' => $id_order,'vendor'=>$phick));

                    $payable_phick = array(
                        'vendor' => $data_phick['vendor'],
                        'money' => $data_phick['cost_vat'],
                        'payable_date' => $data_phick['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_phick['expect_date'],
                        'week' => (int)date('W',$data_phick['expect_date']),
                        'year' => (int)date('Y',$data_phick['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Phí ck',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->updateCosts($payable_phick,array('import_tire_order' => $id_order,'vendor'=>$phick));
                    /*Phí chuyển tiền*/

                    /*Phí khác*/
                    $phikhac = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHIKHAC"))->shipment_vendor_id;
                    $data_phikhac = array(
                        'trading' => $id_trading,
                        'vendor' => $phikhac,
                        'type' => 1,
                        'cost' => $data['import_tire_order_other_cost'],
                        'cost_vat' => null,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Phí khác '.$data['import_tire_order_other_cost_comment'],
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_phikhac['cost'];
                    $vat += $data_phikhac['cost_vat'];

                    
                    $id_order_cost = $sale_vendor->getVendorByWhere(array('import_tire_order' => $id_order,'vendor'=>$phikhac))->import_tire_cost_id;
                    $sale_vendor->updateVendor($data_phikhac,array('import_tire_order' => $id_order,'vendor'=>$phikhac));
                    ///
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_khac'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_khac','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }
                    // $add = $additional_model->getAdditionalByWhere(array('import_tire_cost' => $id_order_cost));
                    // if (!$add) {
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_date'],
                    //         'additional_date' => $data['import_tire_order_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_phikhac['cost']+$data_phikhac['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->createAdditional($data_additional);
                    //     $additional_id = $additional_model->getLastAdditional()->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->createAccount($data_debit);
                    //     $account_balance_model->createAccount($data_credit);
                    // }
                    // else{
                    //     $data_additional = array(
                    //         'document_date' => $data['import_tire_order_expect_date'],
                    //         'additional_date' => $data['import_tire_order_expect_date'],
                    //         'additional_comment' => $data['import_tire_order_comment'],
                    //         'debit' => $debit,
                    //         'credit' => $credit,
                    //         'money' => $data_phikhac['cost']+$data_phikhac['cost_vat'],
                    //         'code' => $data['import_tire_order_code'],
                    //         'import_tire_cost' => $id_order_cost,
                    //     );
                    //     $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                    //     $additional_id = $add->additional_id;

                    //     $data_debit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['debit'],
                    //         'money' => $data_additional['money'],
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $data_credit = array(
                    //         'account_balance_date' => $data_additional['additional_date'],
                    //         'account' => $data_additional['credit'],
                    //         'money' => (0-$data_additional['money']),
                    //         'week' => (int)date('W', $data_additional['additional_date']),
                    //         'year' => (int)date('Y', $data_additional['additional_date']),
                    //         'additional' => $additional_id,
                    //     );
                    //     $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                    //     $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    // }
                    
                    ////
                    /////////
                    $owe_phikhac = array(
                        'owe_date' => $data_phikhac['expect_date'],
                        'vendor' => $data_phikhac['vendor'],
                        'money' => $data_phikhac['cost_vat'],
                        'week' => (int)date('W',$data_phikhac['expect_date']),
                        'year' => (int)date('Y',$data_phikhac['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->updateOwe($owe_phikhac,array('import_tire_order' => $id_order,'vendor'=>$phikhac));

                    $payable_phikhac = array(
                        'vendor' => $data_phikhac['vendor'],
                        'money' => $data_phikhac['cost'],
                        'payable_date' => $data_phikhac['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_phikhac['expect_date'],
                        'week' => (int)date('W',$data_phikhac['expect_date']),
                        'year' => (int)date('Y',$data_phikhac['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Chi phí khác',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>0,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->updateCosts($payable_phikhac,array('import_tire_order' => $id_order,'vendor'=>$phikhac));
                    /*Phí chuyển tiền*/

                    $import_orders = $import_tire_order_model->getImport($_POST['yes']);

                    $dauthang = $data['import_tire_order_expect_date'];


                    $giatrinhap = $import_orders->import_tire_order_sum+$import_orders->import_tire_order_tax+$import_orders->import_tire_order_logistics+$import_orders->import_tire_order_stevedore+$import_orders->import_tire_order_bank_cost+$import_orders->import_tire_order_other_cost;

                    $add = $additional_model->getAdditionalByWhere(array('import_tire_order'=>$_POST['yes']));
                    if ($add) {
                        $code = $data['import_tire_order_code'];

                        $credit_156 = $account_model->getAccountByWhere(array('account_number'=>'156_'.$code));
                        if (!$credit_156) {
                            $account_model->createAccount(array('account_number'=>'156_'.$code,'account_name'=>'Lô '.$code,'account_parent'=>$tk_156));
                            $credit_156 = $account_model->getLastAccount();
                        }

                        $debit_156 = $account_model->getAccountByWhere(array('account_number'=>'1561'));

                        $data_additional = array(
                            'document_date' => $dauthang,
                            'additional_date' => $dauthang,
                            'additional_comment' => 'Nhập hàng vào kho',
                            'debit' => $debit_156->account_id,
                            'credit' => $credit_156->account_id,
                            'money' => $giatrinhap,
                            'code' => $code,
                            'import_tire_order' => $_POST['yes'],
                        );
                        $additional_model->updateAdditional($data_additional,array('additional_id'=>$add->additional_id));
                        $additional_id = $add->additional_id;

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $account_balance_model->updateAccount($data_debit,array('additional' => $additional_id,'account'=>$add->debit));
                        $account_balance_model->updateAccount($data_credit,array('additional' => $additional_id,'account'=>$add->credit));
                    }

                    

                    ////////////////////
                    $data_update = array(
                        'cost' => $kvat,
                        'cost_vat' => $vat,

                    );
                    
                    $sale->updateSale($data_update,array('import_tire_id' => $id_trading));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $import_tire_order_model->createImport($data);

                    
                    echo "Thêm thành công";

                    $id_order = $import_tire_order_model->getLastImport()->import_tire_order_id;

                    $import_orders = $import_tire_order_model->getImport($id_order);

                    $data_sale = array(
                        'import_tire_date' => $data['import_tire_order_date'],
                        'code' => $data['import_tire_order_code'],
                        'comment' => $data['import_tire_order_comment'],
                        'expect_date' => $data['import_tire_order_expect_date'],
                        'import_tire_lock' => 1,
                        'import_type' => 1,
                        'sale' => $_SESSION['userid_logined'],
                        'cost' => 0,
                        'cost_vat' => 0,
                        'import_tire_order' => $id_order,
                    );

                    $sale->createSale($data_sale);
                    /*********************/
                    $kvat = 0;
                    $vat = 0;
                    $estimate = 0;


                    $id_trading = $sale->getLastSale()->import_tire_id;
                    $sale_data = $sale->getSale($id_trading);

                    $tongtienphaitra = $data['import_tire_order_sum']-round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate'])+$data['import_tire_order_rate_diff'];
                    $tongtiendagiam = round($data['import_tire_order_sum_usd_down']*$data['import_tire_order_bank_rate']);

                    /*Mua lốp*/
                    if($data['import_tire_order_sum_usd_down'] > 0){

                        $solow = $vendor_model->getVendorByWhere(array('shipment_vendor_name'=>"Solow"))->shipment_vendor_id;

                        $data_solow = array(
                            'trading' => $id_trading,
                            'vendor' => $solow,
                            'type' => 1,
                            'cost' => $tongtienphaitra-$tongtiendagiam,
                            'cost_vat' => null,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Chuyển Solow',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $data_mualop = array(
                            'trading' => $id_trading,
                            'vendor' => $data['import_tire_order_seller'],
                            'type' => 1,
                            'cost' => null,
                            'cost_vat' => $tongtiendagiam,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Tiền mua lốp',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $kvat += $data_solow['cost']+$data_mualop['cost'];
                        $vat += $data_solow['cost_vat']+$data_mualop['cost_vat'];

                        $sale_vendor->createVendor($data_solow);
                        ///
                        $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'332_solow'));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'332_solow'));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }

                        /*$data_additional = array(
                            'document_date' => $data['import_tire_order_date'],
                            'additional_date' => $data['import_tire_order_date'],
                            'additional_comment' => $data['import_tire_order_comment'],
                            'debit' => $debit,
                            'credit' => $credit,
                            'money' => $data_solow['cost']+$data_solow['cost_vat'],
                            'code' => $data['import_tire_order_code'],
                            'import_tire_cost' => $id_order_cost,
                        );
                        $additional_model->createAdditional($data_additional);
                        $additional_id = $additional_model->getLastAdditional()->additional_id;

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);*/
                        ////

                        $sale_vendor->createVendor($data_mualop);
                        ///
                        $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                        // $sellers = $vendor_model->getVendor($data['import_tire_order_seller']);
                        // $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        // if (!$debits) {
                        //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                        //     $debit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $debit = $debits->account_id;
                        // }

                        /*$data_additional = array(
                            'document_date' => $data['import_tire_order_date'],
                            'additional_date' => $data['import_tire_order_date'],
                            'additional_comment' => $data['import_tire_order_comment'],
                            'debit' => $debit,
                            'credit' => $credit,
                            'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                            'code' => $data['import_tire_order_code'],
                            'import_tire_cost' => $id_order_cost,
                        );
                        $additional_model->createAdditional($data_additional);
                        $additional_id = $additional_model->getLastAdditional()->additional_id;

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);*/
                        ////

                        ///////////
                        $owe_solow = array(
                            'owe_date' => $data_solow['expect_date'],
                            'vendor' => $data_solow['vendor'],
                            'money' => $data_solow['cost'],
                            'week' => (int)date('W',$data_solow['expect_date']),
                            'year' => (int)date('Y',$data_solow['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->createOwe($owe_solow);

                        $payable_solow = array(
                            'vendor' => $data_solow['vendor'],
                            'money' => $data_solow['cost'],
                            'payable_date' => $data_solow['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_solow['expect_date'],
                            'week' => (int)date('W',$data_solow['expect_date']),
                            'year' => (int)date('Y',$data_solow['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Solow',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>0,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->createCosts($payable_solow);
                        //////////////
                        $owe_mualop = array(
                            'owe_date' => $data_mualop['expect_date'],
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->createOwe($owe_mualop);

                        $payable_mualop = array(
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'payable_date' => $data_mualop['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_mualop['expect_date'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Tiền mua lốp',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>1,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->createCosts($payable_mualop);
                                
                    }
                    else{
                        $data_mualop = array(
                            'trading' => $id_trading,
                            'vendor' => $data['import_tire_order_seller'],
                            'type' => 1,
                            'cost' => null,
                            'cost_vat' => $tongtienphaitra,
                            'expect_date' => $data['import_tire_order_date'],
                            'source' => 1,
                            'invoice_cost' => null,
                            'pay_cost' => null,
                            'document_cost' => null,
                            'comment' => 'Tiền mua lốp',
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );

                        $kvat += $data_mualop['cost'];
                        $vat += $data_mualop['cost_vat'];

                        $sale_vendor->createVendor($data_mualop);
                        ///
                        $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                        // $sellers = $vendor_model->getVendor($data['import_tire_order_seller']);
                        // $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                        // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                        // if (!$credits) {
                        //     $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                        //     $credit = $account_model->getLastAccount()->account_id;
                        // }
                        // else{
                        //     $credit = $credits->account_id;
                        // }
                        $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                        if (!$debits) {
                            $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                            $debit = $account_model->getLastAccount()->account_id;
                        }
                        else{
                            $debit = $debits->account_id;
                        }

                        /*$data_additional = array(
                            'document_date' => $data['import_tire_order_date'],
                            'additional_date' => $data['import_tire_order_date'],
                            'additional_comment' => $data['import_tire_order_comment'],
                            'debit' => $debit,
                            'credit' => $credit,
                            'money' => $data_mualop['cost']+$data_mualop['cost_vat'],
                            'code' => $data['import_tire_order_code'],
                            'import_tire_cost' => $id_order_cost,
                        );
                        $additional_model->createAdditional($data_additional);
                        $additional_id = $additional_model->getLastAdditional()->additional_id;

                        $data_debit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['debit'],
                            'money' => $data_additional['money'],
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $data_credit = array(
                            'account_balance_date' => $data_additional['additional_date'],
                            'account' => $data_additional['credit'],
                            'money' => (0-$data_additional['money']),
                            'week' => (int)date('W', $data_additional['additional_date']),
                            'year' => (int)date('Y', $data_additional['additional_date']),
                            'additional' => $additional_id,
                        );
                        $account_balance_model->createAccount($data_debit);
                        $account_balance_model->createAccount($data_credit);*/
                        ////
                        /////////
                        $owe_mualop = array(
                            'owe_date' => $data_mualop['expect_date'],
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'import_tire' => $id_trading,
                            'import_tire_order' => $id_order,
                        );
                        $owe->createOwe($owe_mualop);

                        $payable_mualop = array(
                            'vendor' => $data_mualop['vendor'],
                            'money' => $data_mualop['cost_vat'],
                            'payable_date' => $data_mualop['expect_date'],
                            'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                            'expect_date' => $data_mualop['expect_date'],
                            'week' => (int)date('W',$data_mualop['expect_date']),
                            'year' => (int)date('Y',$data_mualop['expect_date']),
                            'code' => $data['import_tire_order_code'],
                            'source' => 1,
                            'comment' => $data['import_tire_order_comment'].' - Tiền mua lốp',
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'import_tire' => $id_trading,
                            'cost_type' => 1,
                            'check_vat'=>1,
                            'check_cost'=>1,
                            'import_tire_order' => $id_order,
                        );
                        $payable->createCosts($payable_mualop);
                    }
                    /*Mua lốp*/
                    ///
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'711'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'711'));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => round($data['import_tire_order_claim']*$data['import_tire_order_bank_rate']),
                        'code' => $data['import_tire_order_code'],
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////

                    /*THuế*/
                    $thue = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"THUE"))->shipment_vendor_id;
                    $data_thue = array(
                        'trading' => $id_trading,
                        'vendor' => $thue,
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $data['import_tire_order_tax'],
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Tiền thuế',
                        'check_cost'=>2,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_thue['cost'];
                    $vat += $data_thue['cost_vat'];

                    $sale_vendor->createVendor($data_thue);
                    ///
                    $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_thue'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_thue','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $data_thue['cost']+$data_thue['cost_vat'],
                        'code' => $data['import_tire_order_code'],
                        'import_tire_cost' => $id_order_cost,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////
                    /////////
                    $owe_thue = array(
                        'owe_date' => $data_thue['expect_date'],
                        'vendor' => $data_thue['vendor'],
                        'money' => $data_thue['cost_vat'],
                        'week' => (int)date('W',$data_thue['expect_date']),
                        'year' => (int)date('Y',$data_thue['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->createOwe($owe_thue);

                    $payable_thue = array(
                        'vendor' => $data_thue['vendor'],
                        'money' => $data_thue['cost_vat'],
                        'payable_date' => $data_thue['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_thue['expect_date'],
                        'week' => (int)date('W',$data_thue['expect_date']),
                        'year' => (int)date('Y',$data_thue['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Nộp thuế',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>2,
                        'import_tire_order' => $id_order,
                    );
                    $payable->createCosts($payable_thue);
                    /*Thuế*/

                    /*Logistics*/
                    $philogs = $data['import_tire_order_logistics'];
                    $phibx = $data['import_tire_order_stevedore'];
                    
                    $data_logs = array(
                        'trading' => $id_trading,
                        'vendor' => $data['import_tire_order_supplier'],
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $philogs,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Chi phí logistics',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_logs['cost'];
                    $vat += $data_logs['cost_vat'];

                    $sale_vendor->createVendor($data_logs);
                    ///
                    $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                    // $sellers = $vendor_model->getVendor($data['import_tire_order_supplier']);
                    // $seller_name = $this->lib->stripUnicode(str_replace(' ', '', $sellers->shipment_vendor_name));
                    // if ($seller_name=="LandOcean") {
                    //     $seller_name = 'ocean';
                    // }
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_'.$seller_name));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_'.$seller_name,'account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $data_logs['cost']+$data_logs['cost_vat'],
                        'code' => $data['import_tire_order_code'],
                        'import_tire_cost' => $id_order_cost,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////
                    /////////
                    $owe_logs = array(
                        'owe_date' => $data_logs['expect_date'],
                        'vendor' => $data_logs['vendor'],
                        'money' => $data_logs['cost_vat'],
                        'week' => (int)date('W',$data_logs['expect_date']),
                        'year' => (int)date('Y',$data_logs['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->createOwe($owe_logs);

                    $payable_logs = array(
                        'vendor' => $data_logs['vendor'],
                        'money' => $data_logs['cost_vat'],
                        'payable_date' => $data_logs['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_logs['expect_date'],
                        'week' => (int)date('W',$data_logs['expect_date']),
                        'year' => (int)date('Y',$data_logs['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - TTHQ, Vc, Nâng hạ...',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->createCosts($payable_logs);
                    /*Logistics*/
                    /*Phí bốc xếp*/
                    $phibocxep = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"BOCXEP"))->shipment_vendor_id;
                    $data_bx = array(
                        'trading' => $id_trading,
                        'vendor' => $phibocxep,
                        'type' => 1,
                        'cost' => $phibx,
                        'cost_vat' => null,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Tiền bốc xếp',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_bx['cost'];
                    $vat += $data_bx['cost_vat'];

                    $sale_vendor->createVendor($data_bx);
                    ///
                    $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_VC'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_VC'));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $data_bx['cost']+$data_bx['cost_vat'],
                        'code' => $data['import_tire_order_code'],
                        'import_tire_cost' => $id_order_cost,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////
                    /////////
                    $owe_bx = array(
                        'owe_date' => $data_bx['expect_date'],
                        'vendor' => $data_bx['vendor'],
                        'money' => $data_bx['cost'],
                        'week' => (int)date('W',$data_bx['expect_date']),
                        'year' => (int)date('Y',$data_bx['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->createOwe($owe_bx);

                    $payable_bx = array(
                        'vendor' => $data_bx['vendor'],
                        'money' => $data_bx['cost'],
                        'payable_date' => $data_bx['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_bx['expect_date'],
                        'week' => (int)date('W',$data_bx['expect_date']),
                        'year' => (int)date('Y',$data_bx['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Bốc xếp',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>0,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->createCosts($payable_bx);
                    /*Phí bốc xếp*/

                    /*Phí chuyển tiền*/
                    $phick = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHICK"))->shipment_vendor_id;
                    $data_phick = array(
                        'trading' => $id_trading,
                        'vendor' => $phick,
                        'type' => 1,
                        'cost' => null,
                        'cost_vat' => $data['import_tire_order_bank_cost'],
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Phí chuyển tiền',
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_phick['cost'];
                    $vat += $data_phick['cost_vat'];

                    $sale_vendor->createVendor($data_phick);
                    ///
                    $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                    
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_phick'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_phick','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $data_phick['cost']+$data_phick['cost_vat'],
                        'code' => $data['import_tire_order_code'],
                        'import_tire_cost' => $id_order_cost,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////
                    /////////
                    $owe_phick = array(
                        'owe_date' => $data_phick['expect_date'],
                        'vendor' => $data_phick['vendor'],
                        'money' => $data_phick['cost_vat'],
                        'week' => (int)date('W',$data_phick['expect_date']),
                        'year' => (int)date('Y',$data_phick['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->createOwe($owe_phick);

                    $payable_phick = array(
                        'vendor' => $data_phick['vendor'],
                        'money' => $data_phick['cost_vat'],
                        'payable_date' => $data_phick['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_phick['expect_date'],
                        'week' => (int)date('W',$data_phick['expect_date']),
                        'year' => (int)date('Y',$data_phick['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Phí ck',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>1,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->createCosts($payable_phick);
                    /*Phí chuyển tiền*/

                    /*Phí khác*/
                    $phikhac = $vendor_model->getVendorByWhere(array('shipment_vendor_code'=>"PHIKHAC"))->shipment_vendor_id;
                    $data_phikhac = array(
                        'trading' => $id_trading,
                        'vendor' => $phikhac,
                        'type' => 1,
                        'cost' => $data['import_tire_order_other_cost'],
                        'cost_vat' => null,
                        'expect_date' => $data['import_tire_order_date'],
                        'source' => 1,
                        'invoice_cost' => null,
                        'pay_cost' => null,
                        'document_cost' => null,
                        'comment' => 'Phí khác '.$data['import_tire_order_other_cost_comment'],
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );

                    $kvat += $data_phikhac['cost'];
                    $vat += $data_phikhac['cost_vat'];

                    $sale_vendor->createVendor($data_phikhac);
                    ///
                    $id_order_cost = $sale_vendor->getLastVendor()->import_tire_cost_id;
                    // $credits = $account_model->getAccountByWhere(array('account_number'=>'331_khac'));
                    // if (!$credits) {
                    //     $account_model->createAccount(array('account_number'=>'331_khac','account_parent'=>$tk_331));
                    //     $credit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $credit = $credits->account_id;
                    // }
                    // $debits = $account_model->getAccountByWhere(array('account_number'=>'156_'.$data['import_tire_order_code']));
                    // if (!$debits) {
                    //     $account_model->createAccount(array('account_number'=>'156_'.$data['import_tire_order_code'],'account_name'=>'Lô '.$data['import_tire_order_code'],'account_parent'=>$tk_156));
                    //     $debit = $account_model->getLastAccount()->account_id;
                    // }
                    // else{
                    //     $debit = $debits->account_id;
                    // }

                    /*$data_additional = array(
                        'document_date' => $data['import_tire_order_date'],
                        'additional_date' => $data['import_tire_order_date'],
                        'additional_comment' => $data['import_tire_order_comment'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'money' => $data_phikhac['cost']+$data_phikhac['cost_vat'],
                        'code' => $data['import_tire_order_code'],
                        'import_tire_cost' => $id_order_cost,
                    );
                    $additional_model->createAdditional($data_additional);
                    $additional_id = $additional_model->getLastAdditional()->additional_id;

                    $data_debit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['debit'],
                        'money' => $data_additional['money'],
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data_additional['additional_date'],
                        'account' => $data_additional['credit'],
                        'money' => (0-$data_additional['money']),
                        'week' => (int)date('W', $data_additional['additional_date']),
                        'year' => (int)date('Y', $data_additional['additional_date']),
                        'additional' => $additional_id,
                    );
                    $account_balance_model->createAccount($data_debit);
                    $account_balance_model->createAccount($data_credit);*/
                    ////
                    /////////
                    $owe_phikhac = array(
                        'owe_date' => $data_phikhac['expect_date'],
                        'vendor' => $data_phikhac['vendor'],
                        'money' => $data_phikhac['cost_vat'],
                        'week' => (int)date('W',$data_phikhac['expect_date']),
                        'year' => (int)date('Y',$data_phikhac['expect_date']),
                        'import_tire' => $id_trading,
                        'import_tire_order' => $id_order,
                    );
                    $owe->createOwe($owe_phikhac);

                    $payable_phikhac = array(
                        'vendor' => $data_phikhac['vendor'],
                        'money' => $data_phikhac['cost'],
                        'payable_date' => $data_phikhac['expect_date'],
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $data_phikhac['expect_date'],
                        'week' => (int)date('W',$data_phikhac['expect_date']),
                        'year' => (int)date('Y',$data_phikhac['expect_date']),
                        'code' => $data['import_tire_order_code'],
                        'source' => 1,
                        'comment' => $data['import_tire_order_comment'].' - Chi phí khác',
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'import_tire' => $id_trading,
                        'cost_type' => 1,
                        'check_vat'=>0,
                        'check_cost'=>3,
                        'import_tire_order' => $id_order,
                    );
                    $payable->createCosts($payable_phikhac);
                    /*Phí chuyển tiền*/

                    ////////////////////
                    $data_update = array(
                        'cost' => $kvat,
                        'cost_vat' => $vat,

                    );
                    
                    $sale->updateSale($data_update,array('import_tire_id' => $id_trading));


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_order."|import_tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }


            $import_list = $_POST['import_list'];
            $arr_item = "";
            foreach ($import_list as $v) {
                $id_list = $v['tire_list_id'];
                $data_list = array(
                    'tire_number'=> $v['tire_number'],
                    'tire_price'=> $v['tire_price'],
                    'tire_price_down'=> $v['tire_price_down'],
                    'tire_stuff'=> $v['tire_stuff'],
                    'tire_ocean_freight'=> $v['tire_ocean_freight'],
                    'tire_price_sum'=> str_replace(',', '', $v['tire_price_sum']),
                    'tire_tax_import'=> str_replace(',', '', $v['tire_tax_import']),
                    'tire_tax_vat'=> str_replace(',', '', $v['tire_tax_vat']),
                    'tire_bill'=> str_replace(',', '', $v['tire_bill']),
                    'tire_tthq'=> str_replace(',', '', $v['tire_tthq']),
                    'tire_lift'=> str_replace(',', '', $v['tire_lift']),
                    'tire_trucking'=> str_replace(',', '', $v['tire_trucking']),
                    'tire_stevedore'=> str_replace(',', '', $v['tire_stevedore']),
                    'tire_bank_cost'=> str_replace(',', '', $v['tire_bank_cost']),
                    'tire_other_cost'=> str_replace(',', '', $v['tire_other_cost']),
                    'tire_price_total'=> str_replace(',', '', $v['tire_price_total']),
                    'tire_price_origin'=> str_replace(',', '', $v['tire_price_origin']),
                    'import_tire_order'=> $id_order,
                );

                $brand = trim($v['tire_brand']);
                $size = trim($v['tire_size']);
                $pattern = trim($v['tire_pattern']);

                if ($brand != "") {
                    if ($tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))) {
                        $data_list['tire_brand'] = $tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))->tire_brand_id;
                    }
                    else{
                        $tire_brand_model->createTire(array('tire_brand_name' => $brand));
                        $tire_brand_id = $tire_brand_model->getLastTire()->tire_brand_id;
                        $data_list['tire_brand'] = $tire_brand_id;
                    }
                    
                }
                if ($size != "") {
                    if ($tire_size_model->getTireByWhere(array('tire_size_number' => $size))) {
                        $data_list['tire_size'] = $tire_size_model->getTireByWhere(array('tire_size_number' => $size))->tire_size_id;
                    }
                    else{
                        $tire_size_model->createTire(array('tire_size_number' => $size));
                        $tire_size_id = $tire_size_model->getLastTire()->tire_size_id;
                        $data_list['tire_size'] = $tire_size_id;
                    }
                    
                }
                if ($pattern != "") {
                    if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))) {
                        $data_list['tire_pattern'] = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))->tire_pattern_id;
                    }
                    else{
                        $tire_pattern_model->createTire(array('tire_pattern_name' => $pattern));
                        $tire_pattern_id = $tire_pattern_model->getLastTire()->tire_pattern_id;
                        $data_list['tire_pattern'] = $tire_pattern_id;
                    }
                    
                }

                if ($data_list['tire_brand']>0 && $data_list['tire_size']>0 && $data_list['tire_pattern']>0 && $data_list['tire_number']>0) {
                    if ($id_list>0) {
                        $import_tire_list_model->updateImport($data_list,array('import_tire_list_id'=>$id_list));
                        
                        $tire_going_data = array(
                        'tire_going_order_date' => $data['import_tire_order_expect_date'],
                        'code' => $data['import_tire_order_code'],
                        'tire_size' => $data_list['tire_size'],
                        'tire_pattern' => $data_list['tire_pattern'],
                        'tire_brand' => $data_list['tire_brand'],
                        'tire_number' => $data_list['tire_number'],
                        'tire_price' => $data_list['tire_price'],
                        'import_tire_list' => $id_list,
                        );

                        $tire_going = array(
                        'tire_going_date' => $data['import_tire_order_expect_date'],
                        'code' => $data['import_tire_order_code'],
                        'tire_size' => $data_list['tire_size'],
                        'tire_pattern' => $data_list['tire_pattern'],
                        'tire_brand' => $data_list['tire_brand'],
                        'tire_number' => $data_list['tire_number'],
                        'import_tire_list' => $id_list,
                        );

                        $going = $tire_going_order_model->getTireByWhere(array('import_tire_list' => $id_list));
                        if (!$going) {
                            $tire_going_order_model->createTire($tire_going_data);
                            if ($import_orders->import_tire_order_status>1) {
                                $tire_going_model->createTire($tire_going);
                            }
                            
                        }
                        else{
                            $tire_going_order_model->updateTire($tire_going_data,array('import_tire_list' => $id_list));
                            $tire_going_model->updateTire($tire_going,array('import_tire_list' => $id_list));
                        }
                        
                        
                    }
                    else{
                        $import_tire_list_model->createImport($data_list);
                        $id_list = $import_tire_list_model->getLastImport()->import_tire_list_id;

                        $tire_going_data = array(
                        'tire_going_order_date' => $data['import_tire_order_expect_date'],
                        'code' => $data['import_tire_order_code'],
                        'tire_size' => $data_list['tire_size'],
                        'tire_pattern' => $data_list['tire_pattern'],
                        'tire_brand' => $data_list['tire_brand'],
                        'tire_number' => $data_list['tire_number'],
                        'tire_price' => $data_list['tire_price'],
                        'import_tire_list' => $id_list,
                        );
                        $tire_going_order_model->createTire($tire_going_data);

                        if ($import_orders->import_tire_order_status>1) {
                            $tire_going = array(
                            'tire_going_date' => $data['import_tire_order_expect_date'],
                            'code' => $data['import_tire_order_code'],
                            'tire_size' => $data_list['tire_size'],
                            'tire_pattern' => $data_list['tire_pattern'],
                            'tire_brand' => $data_list['tire_brand'],
                            'tire_number' => $data_list['tire_number'],
                            'import_tire_list' => $id_list,
                            );
                            $tire_going_model->createTire($tire_going);
                        }
                    }

                    if ($arr_item=="") {
                        $arr_item .= $id_list;
                    }
                    else{
                        $arr_item .= ','.$id_list;
                    }

                    $item_olds = $import_tire_list_model->queryImport('SELECT * FROM import_tire_list WHERE import_tire_order='.$id_order.' AND import_tire_list_id NOT IN ('.$arr_item.')');
                    foreach ($item_olds as $item_old) {
                        $import_tire_list_model->updateImport(array('import_tire_order'=>null),array('import_tire_list_id'=>$item_old->import_tire_list_id));
                        $tire_going_order_model->queryTire('DELETE FROM tire_going_order WHERE import_tire_list='.$item_old->import_tire_list_id);
                        $tire_going_model->queryTire('DELETE FROM tire_going WHERE import_tire_list='.$item_old->import_tire_list_id);
                    }

                    $prices = $import_tire_price_model->getAllImport(array('where'=>'tire_brand="'.$data_list['tire_brand'].'" AND tire_size="'.$data_list['tire_size'].'" AND tire_pattern="'.$data_list['tire_pattern'].'" AND start_time <= '.$data['import_tire_order_date'],'order_by'=>'start_time DESC','limit'=>1));

                    if (!$prices) {
                        $data_price = array(
                        'tire_stuff' => $data_list['tire_stuff'],
                        'tire_price' => $data_list['tire_price'],
                        'tire_price_down' => $data_list['tire_price_down'],
                        'start_time' => $data['import_tire_order_date'],
                        'tire_brand'=>$data_list['tire_brand'],
                        'tire_size'=>$data_list['tire_size'],
                        'tire_pattern'=>$data_list['tire_pattern'],
                        );
                        $import_tire_price_model->createImport($data_price);
                    }
                    else{
                        foreach ($prices as $price) {
                            if ($price->tire_price != $data_list['tire_price'] || $price->tire_price_down != $data_list['tire_price_down']) {
                                $data_price = array(
                                'tire_stuff' => $data_list['tire_stuff'],
                                'tire_price' => $data_list['tire_price'],
                                'tire_price_down' => $data_list['tire_price_down'],
                                'start_time' => $data['import_tire_order_date'],
                                'tire_brand'=>$data_list['tire_brand'],
                                'tire_size'=>$data_list['tire_size'],
                                'tire_pattern'=>$data_list['tire_pattern'],
                                );
                                $import_tire_price_model->createImport($data_price);
                            }
                        }
                    }
                }

                
            }

            if ($_POST['yes'] != "") {
                $import_orders = $import_tire_order_model->getImport($_POST['yes']);

                $dauthang = $data['import_tire_order_expect_date'];

                $list_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$_POST['yes']));
                foreach ($list_orders as $order) {
                    
                    if ($import_orders->import_tire_order_status==3) {
                        

                        $id_brand = $order->tire_brand;
                        $id_size = $order->tire_size;
                        $id_pattern = $order->tire_pattern;
                        $code = $import_orders->import_tire_order_code;

                        if($tireimportdetail->getTireByWhere(array('import_tire_list' => $order->import_tire_list_id))) {
                            $tirebuy->queryTire('DELETE FROM tire_buy WHERE import_tire_list='.$order->import_tire_list_id);
                            $tireimport->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$order->import_tire_list_id);
                            $tireimportdetail->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$order->import_tire_list_id);
                        }

                        if($tireimportdetail->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern))) {
                            $ton = 0;

                            $tire_buys = $tirebuy->getAllTire(array('where'=>'code != '.$code.' AND tire_buy_date <= '.$dauthang.' AND tire_buy_brand = '.$id_brand.' AND tire_buy_size = '.$id_size.' AND tire_buy_pattern = '.$id_pattern));
                            foreach ($tire_buys as $tire) {
                                $ton += $tire->tire_buy_volume;
                            }

                            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_sale_date < '.$dauthang.' AND tire_brand = '.$id_brand.' AND tire_size = '.$id_size.' AND tire_pattern = '.$id_pattern));
                            foreach ($tire_sales as $tire) {
                                $ton -= $tire->volume;
                            }

                            $data_old = array(
                                'where' => 'tire_brand = '.$id_brand.' AND tire_size = '.$id_size.' AND tire_pattern = '.$id_pattern.' AND start_date <= '.$dauthang,
                                'order_by' => 'start_date',
                                'order' => 'DESC, tire_import_id DESC',
                                'limit' => 1,
                            );
                            $tire_imports = $tireimport->getAllTire($data_old);
                            $soluong = 0; $gia = 0;
                            foreach ($tire_imports as $tire) {
                                $soluong = $ton;
                                $gia = $ton*$tire->tire_price;
                            }
                            $soluong += $order->tire_number;
                            $gia += $order->tire_price_origin*$order->tire_number;

                            $tireimportdetail->updateTire(array('status'=>0),array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern,'status'=>1));

                            $tire_import_detail_data = array(
                            'tire_brand' => $id_brand,
                            'tire_size' => $id_size,
                            'tire_pattern' => $id_pattern,
                            'tire_price' => $order->tire_price_origin,
                            'tire_price_vat' => $order->tire_tax_vat,
                            'tire_number' => $order->tire_number,
                            'code' => $code,
                            'status' => 1,
                            'import_tire_list' => $order->import_tire_list_id,
                            );
                            $tireimportdetail->createTire($tire_import_detail_data);

                            $tire_import_data = array(
                            'tire_brand' => $id_brand,
                            'tire_size' => $id_size,
                            'tire_pattern' => $id_pattern,
                            'tire_price' => $gia/$soluong,
                            'tire_price_vat' => $order->tire_tax_vat,
                            'code' => $code,
                            'start_date' => $dauthang,
                            'import_tire_list' => $order->import_tire_list_id,
                            );
                            $tireimport->createTire($tire_import_data);
                        }
                        else{
                            $tire_import_detail_data = array(
                            'tire_brand' => $id_brand,
                            'tire_size' => $id_size,
                            'tire_pattern' => $id_pattern,
                            'tire_price' => $order->tire_price_origin,
                            'tire_price_vat' => $order->tire_tax_vat,
                            'tire_number' => $order->tire_number,
                            'code' => $code,
                            'status' => 1,
                            'import_tire_list' => $order->import_tire_list_id,
                            );
                            $tireimportdetail->createTire($tire_import_detail_data);

                            $tire_import_data = array(
                            'tire_brand' => $id_brand,
                            'tire_size' => $id_size,
                            'tire_pattern' => $id_pattern,
                            'tire_price' => $order->tire_price_origin,
                            'tire_price_vat' => $order->tire_tax_vat,
                            'code' => $code,
                            'start_date' => $dauthang,
                            'import_tire_list' => $order->import_tire_list_id,
                            );
                            $tireimport->createTire($tire_import_data);
                        }

                        $data_buy = array(  
                        'code' => $import_orders->import_tire_order_code,
                        'tire_buy_volume' => $order->tire_number,
                        'tire_buy_brand' => $order->tire_brand,
                        'tire_buy_size' => $order->tire_size,
                        'tire_buy_pattern' => $order->tire_pattern,
                        'rate' => $import_orders->import_tire_order_bank_rate,
                        'rate_shipper' => $import_orders->import_tire_order_bank_rate,
                        'date_solow' => $dauthang,
                        'date_shipper' => $dauthang,
                        'tire_buy_date' => $dauthang,
                        'date_manufacture' => $dauthang,
                        'import_tire_list' => $order->import_tire_list_id,
                        );

                        $tirebuy->createTire($data_buy);

                        $import_tire_order_model->updateImport(array('import_tire_order_lock'=>1),array('import_tire_order_id'=>$_POST['yes']));
                        
                    }
                    
                }
            }

                    
        }
    }

    public function delall(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $import_tire_list_model = $this->model->get('importtirelistModel');
        $import_tire_list_model->queryImport('DELETE FROM import_tire_list WHERE import_tire_order IS NULL OR import_tire_order=0');
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $sale = $this->model->get('importtireModel');
            $sale_vendor = $this->model->get('importtirecostModel');
            $owe = $this->model->get('oweModel');
            $payable = $this->model->get('payableModel');
            $tire_going_model = $this->model->get('tiregoingModel');
            $tire_going_order_model = $this->model->get('tiregoingorderModel');
            $tire_import_model = $this->model->get('tireimportModel');
            $tire_import_detail_model = $this->model->get('tireimportdetailModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $orders = $import_tire_order_model->getImport($data);
                    $sale->querySale('DELETE FROM import_tire WHERE import_tire_order='.$data);
                    $import_costs = $sale_vendor->getAllVendor(array('where'=>'import_tire_order='.$data));
                    foreach ($import_costs as $cost) {
                        $adds = $additional_model->getAllAdditional(array('where'=>'import_tire_cost='.$cost->import_tire_cost_id));
                        foreach ($adds as $add) {
                            $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$add->additional_id);
                        }
                        $additional_model->queryAdditional('DELETE FROM additional WHERE import_tire_cost='.$cost->import_tire_cost_id);
                    }
                    $adds = $additional_model->getAllAdditional(array('where'=>'money='.(round($orders->import_tire_order_claim*$orders->import_tire_order_bank_rate)-$orders->import_tire_order_rate_diff).' AND code='.$orders->import_tire_order_code));
                    foreach ($adds as $add) {
                        $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$add->additional_id);
                        $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$add->additional_id);
                    }
                    
                    $sale_vendor->queryVendor('DELETE FROM import_tire_cost WHERE import_tire_order='.$data);
                    $owe->queryOwe('DELETE FROM owe WHERE import_tire_order='.$data);
                    $payable->queryCosts('DELETE FROM payable WHERE import_tire_order='.$data);
                    $import_lists = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$data));
                    foreach ($import_list as $import_list) {
                        $tire_going_model->queryTire('DELETE FROM tire_going WHERE import_tire_list='.$import_list->import_tire_list_id);
                        $tire_going_order_model->queryTire('DELETE FROM tire_going_order WHERE import_tire_list='.$import_list->import_tire_list_id);
                        $tire_import_model->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$import_list->import_tire_list_id);
                        $tire_import_detail_model->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$import_list->import_tire_list_id);
                    }
                    $import_tire_list_model->queryImport('DELETE FROM import_tire_list WHERE import_tire_order='.$data);
                    $additionals = $additional_model->getAdditionalByWhere(array('import_tire_order'=>$data));
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);
                       $import_tire_order_model->deleteImport($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_tire_order|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                $orders = $import_tire_order_model->getImport($_POST['data']);

                $sale->querySale('DELETE FROM import_tire WHERE import_tire_order='.$_POST['data']);
                $import_costs = $sale_vendor->getAllVendor(array('where'=>'import_tire_order='.$_POST['data']));
                foreach ($import_costs as $cost) {
                    $adds = $additional_model->getAllAdditional(array('where'=>'import_tire_cost='.$cost->import_tire_cost_id));
                    foreach ($adds as $add) {
                        $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$add->additional_id);
                    }
                    $additional_model->queryAdditional('DELETE FROM additional WHERE import_tire_cost='.$cost->import_tire_cost_id);
                }
                $adds = $additional_model->getAllAdditional(array('where'=>'money='.(round($orders->import_tire_order_claim*$orders->import_tire_order_bank_rate)-$orders->import_tire_order_rate_diff).' AND code='.$orders->import_tire_order_code));
                foreach ($adds as $add) {
                    $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$add->additional_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$add->additional_id);
                }

                $sale_vendor->queryVendor('DELETE FROM import_tire_cost WHERE import_tire_order='.$_POST['data']);
                $owe->queryOwe('DELETE FROM owe WHERE import_tire_order='.$_POST['data']);
                $payable->queryCosts('DELETE FROM payable WHERE import_tire_order='.$_POST['data']);
                $import_lists = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$_POST['data']));
                foreach ($import_list as $import_list) {
                    $tire_going_model->queryTire('DELETE FROM tire_going WHERE import_tire_list='.$import_list->import_tire_list_id);
                    $tire_going_order_model->queryTire('DELETE FROM tire_going_order WHERE import_tire_list='.$import_list->import_tire_list_id);
                    $tire_import_model->queryTire('DELETE FROM tire_import WHERE import_tire_list='.$import_list->import_tire_list_id);
                    $tire_import_detail_model->queryTire('DELETE FROM tire_import_detail WHERE import_tire_list='.$import_list->import_tire_list_id);
                }
                $import_tire_list_model->queryImport('DELETE FROM import_tire_list WHERE import_tire_order='.$_POST['data']);
                $additionals = $additional_model->getAdditionalByWhere(array('import_tire_order'=>$_POST['data']));
                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$additionals->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$additionals->additional_id);
                        $import_tire_order_model->deleteImport($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_tire_order|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $import_tire_price_model = $this->model->get('importtirepriceModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');

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

            /*$nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = (strlen($day[0]) < 2 ? "0".$day[0] : $day[0] )."-".$day[1] ;
            $ngaythang = '01-'.$ngaythang;*/

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            $tongchiphi = 0;

            $y = 0;

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

                /*$ngay = $val[1];
                $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);                                      
                $ngaythang = $ngaythang+86400;

                $ngaythang = strtotime(date('d-m-Y',$ngaythang));*/


                if (trim($val[0]) != null && trim($val[1]) != null && trim($val[2]) != null && trim($val[3]) != null) {
                    $brand = trim($val[0]);
                    $size = trim($val[1]);
                    $pattern = trim($val[2]);
                    
                    if ($tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))) {
                        $brand_id = $tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))->tire_brand_id;
                    }
                    else{
                        $tire_brand_model->createTire(array('tire_brand_name' => $brand));
                        $tire_brand_id = $tire_brand_model->getLastTire()->tire_brand_id;
                        $brand_id = $tire_brand_id;
                    }

                    if ($tire_size_model->getTireByWhere(array('tire_size_number' => $size))) {
                        $size_id = $tire_size_model->getTireByWhere(array('tire_size_number' => $size))->tire_size_id;
                    }
                    else{
                        $tire_size_model->createTire(array('tire_size_number' => $size));
                        $tire_size_id = $tire_size_model->getLastTire()->tire_size_id;
                        $size_id = $tire_size_id;
                    }

                    if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))) {
                        $pattern_id = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))->tire_pattern_id;
                    }
                    else{
                        $tire_pattern_model->createTire(array('tire_pattern_name' => $pattern));
                        $tire_pattern_id = $tire_pattern_model->getLastTire()->tire_pattern_id;
                        $pattern_id = $tire_pattern_id;
                    }

                    if (trim($val[4]) > 0) {
                        $price = $import_tire_price_model->getAllImport(array('where'=>'tire_brand='.$brand_id.' AND tire_size='.$size_id.' AND tire_pattern='.$pattern_id,'order_by'=>'start_time DESC','limit'=>1));
                        if (!$price) {
                            $data_price = array(
                            'tire_brand'=>$brand_id,
                            'tire_size'=>$size_id,
                            'tire_pattern'=>$pattern_id,
                            'tire_stuff' => trim($val[6]),
                            'tire_price' => trim($val[4]),
                            'tire_price_down' => trim($val[5]),
                            'start_time' => strtotime(date('d-m-Y')),
                            );
                            $import_tire_price_model->createImport($data_price);
                        }
                        else{
                            foreach ($price as $pr) {
                                $data_price = array(
                                'tire_brand'=>$brand_id,
                                'tire_size'=>$size_id,
                                'tire_pattern'=>$pattern_id,
                                'tire_stuff' => trim($val[6]),
                                'tire_price' => trim($val[4]),
                                'tire_price_down' => trim($val[5]),
                                'start_time' => strtotime(date('d-m-Y')),
                                );
                                if (trim($val[4])>0) {
                                    if ($pr->tire_price != trim($val[4])) {
                                        $import_tire_price_model->createImport($data_price);
                                    }
                                }
                            }
                        }
                    }
                    

                    $data = array(
                        'tire_brand'=>$brand_id,
                        'tire_size'=>$size_id,
                        'tire_pattern'=>$pattern_id,
                        'tire_number'=>$val[3],
                    );
                    $import_tire_list_model->createImport($data);
                    
                }
                


            }
            //return $this->view->redirect('importtireorder');
            echo "Thêm thành công!";
        }
        //$this->view->show('importtireorder/import');

    }
    public function getoceanfreight(){
        $import_tire_ocean_freight_model = $this->model->get('importtireoceanfreightModel');
        $prices = $import_tire_ocean_freight_model->getAllImport(array('where'=>'import_tire_port='.$_GET['port'],'order_by'=>'start_time DESC','limit'=>1));
        $gia=0;
        foreach ($prices as $price) {
            $gia = $price->import_tire_ocean_freight;
        }
        echo $gia;
    }
    public function getlift(){
        $import_tire_lift_model = $this->model->get('importtireliftModel');
        $prices = $import_tire_lift_model->getAllImport(array('where'=>'import_tire_port='.$_GET['port'],'order_by'=>'start_time DESC','limit'=>1));
        $gia=0;
        foreach ($prices as $price) {
            $gia = $price->import_tire_lift_on+$price->import_tire_lift_off;
        }
        echo $gia;
    }
    public function getprice(){
        $import_tire_price_model = $this->model->get('importtirepriceModel');
        $result = array(
            'gia'=>0,
            'giagiam'=>0,
            'stuff'=>0,
        );
        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_brand AND tire_size.tire_size_id = tire_size AND tire_pattern.tire_pattern_id = tire_pattern');
        $prices = $import_tire_price_model->getAllImport(array('where'=>'tire_brand_name="'.$_GET['tire_brand'].'" AND tire_size_number="'.$_GET['tire_size'].'" AND tire_pattern_name="'.$_GET['tire_pattern'].'"','order_by'=>'start_time DESC','limit'=>1),$join);
        foreach ($prices as $price) {
            $result = array(
                'gia'=>$price->tire_price,
                'giagiam'=>$price->tire_price_down,
                'stuff'=>$price->tire_stuff,
            );
        }
        echo json_encode($result);
    }
    public function getgoing(){
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $import_tire_price_model = $this->model->get('importtirepriceModel');
            
            $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_brand AND tire_size.tire_size_id = tire_size AND tire_pattern.tire_pattern_id = tire_pattern');
            $lists = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order IS NULL'),$join);

            $str = "";
            $str2 = "";
            $i = 1;
            $sl = 0;
            $tonggia = 0;
            $tonggiagiam = 0;
            $tongphantram = 0;

            if ($lists) {
                foreach ($lists as $tire) {
                    $prices = $import_tire_price_model->getAllImport(array('where'=>'tire_brand='.$tire->tire_brand.' AND tire_size='.$tire->tire_size.' AND tire_pattern='.$tire->tire_pattern,'order_by'=>'start_time DESC','limit'=>1));
                    foreach ($prices as $price) {
                        $gia = $price->tire_price;
                        $gia_giam = $price->tire_price_down;
                        $stuff = $price->tire_stuff;
                    }
                    $sl += $tire->tire_number;
                    $tonggia += $gia*$tire->tire_number;
                    $tonggiagiam += $gia_giam*$tire->tire_number;
                    $tongphantram += $tire->tire_number/$stuff;
                    
                    $bocxep = 8000;
                    /*if ($tire->tire_size_number=="12.00R20") {
                        $bocxep = 7000;
                    }*/

                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off" value="'.$tire->tire_brand_name.'" data="'.$tire->tire_brand.'">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off" value="'.$tire->tire_size_number.'" data="'.$tire->tire_size.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off" value="'.$tire->tire_pattern_name.'" data="'.$tire->tire_pattern.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off" value="'.$tire->tire_number.'" data="'.$tire->import_tire_list_id.'" ></td>
                        <td class="width-5"><input type="text" name="tire_price[]" class="tire_price numbers text-right" required="required" autocomplete="off" value="'.$gia.'"></td>
                        <td class="width-5"><input type="text" name="tire_price_down[]" class="tire_price_down numbers text-right" required="required" autocomplete="off" value="'.$gia_giam.'"></td>
                        <td class="width-7"><input type="text" name="tire_stuff[]" class="tire_stuff numbers text-right" required="required" autocomplete="off" value="'.$stuff.'"></td>
                        <td class="width-5"><input type="text" name="tire_ocean_freight[]" class="tire_ocean_freight text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_sum[]" class="tire_price_sum numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_import[]" class="tire_tax_import numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_vat[]" class="tire_tax_vat numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax[]" class="tire_tax numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bill[]" class="tire_bill numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_tthq[]" class="tire_tthq numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_lift[]" class="tire_lift numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_trucking[]" class="tire_trucking numbers text-right" autocomplete="off">
                          
                          <input type="text" name="tire_logistics[]" class="tire_logistics numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-5">
                        <input type="text" name="tire_stevedore[]" class="tire_stevedore numbers text-right" autocomplete="off" value="'.$this->lib->formatMoney($bocxep*$tire->tire_number).'">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bank_cost[]" class="tire_bank_cost numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_other_cost[]" class="tire_other_cost numbers text-right" autocomplete="off">
                          <input type="text" name="tire_cost[]" class="tire_cost numbers text-right" autocomplete="off">
                        </td>
                        <td class="width-10"><input type="text" name="tire_price_total[]" class="tire_price_total numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_origin[]" class="tire_price_origin numbers text-right" required="required" autocomplete="off"></td>';
                    
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_price[]" class="tire_price numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_price_down[]" class="tire_price_down numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_stuff[]" class="tire_stuff numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_ocean_freight[]" class="tire_ocean_freight text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_sum[]" class="tire_price_sum numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_import[]" class="tire_tax_import numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_vat[]" class="tire_tax_vat numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax[]" class="tire_tax numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bill[]" class="tire_bill numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_tthq[]" class="tire_tthq numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_lift[]" class="tire_lift numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_trucking[]" class="tire_trucking numbers text-right" autocomplete="off">
                          <input type="text" name="tire_logistics[]" class="tire_logistics numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-5">
                        <input type="text" name="tire_stevedore[]" class="tire_stevedore numbers text-right" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bank_cost[]" class="tire_bank_cost numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_other_cost[]" class="tire_other_cost numbers text-right" autocomplete="off">
                          <input type="text" name="tire_cost[]" class="tire_cost numbers text-right" autocomplete="off">
                        </td>
                        <td class="width-10"><input type="text" name="tire_price_total[]" class="tire_price_total numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_origin[]" class="tire_price_origin numbers text-right" required="required" autocomplete="off"></td>';
                
                $str .= '</tr>';

            }

            $str2 .= '<tr>';
            $str2 .= '<th class="width-3"></th>
                    <th class="width-10">Tổng</th>
                    <th class="width-10"></th>
                    <th class="width-7"></th>
                    <th class="width-5 numbers" id="tongsl">'.$sl.'</th>
                    <th class="width-5 numbers" id="tonggia">'.$tonggia.'</th>
                    <th class="width-5 numbers" id="tonggiagiam">'.$tonggiagiam.'</th>
                    <th class="width-7" id="tongphantram">'.round($tongphantram*100,7).'</th>
                    <th class="width-5 numbers" id="tongcuoctau"></th>
                    <th class="width-7 numbers" id="tongtienhang">Tổng tiền</th>
                    <th class="width-7 numbers" id="tongthuenk">Thuế NK</th>
                    <th class="width-7 numbers" id="tongthuevat">Thuế VAT</th>
                    <th class="width-7 numbers" id="tongthue">Thuế</th>
                    <th class="width-7 numbers" id="tonglogs">Logistics</th>
                    <th class="width-5 numbers" id="tongbocxep">Bốc xếp</th>
                    <th class="width-7 numbers" id="tongphikhac">Phí khác</th>
                    <th class="width-10 numbers" id="tongcong">Tổng cộng</th>
                    <th class="width-7"></th>
                    <th style="width:5px"></th>';
            
            $str2 .= '</tr>';
            

            $arr = array(
                'hang'=>$str,
                'tong'=>$str2,
            );
            echo json_encode($arr);
    }
    public function getitemadd(){
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $import_tire_order_model = $this->model->get('importtireorderModel');
            
            $orders = $import_tire_order_model->getImport($_POST['order']);
            $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_brand AND tire_size.tire_size_id = tire_size AND tire_pattern.tire_pattern_id = tire_pattern');
            $lists = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order = '.$_POST['order']),$join);

            $str = "";
            $str2 = "";
            $i = 1;
            $sl = 0;
            $tonggia = 0;
            $tonggiagiam = 0;
            $tongphantram = 0;
            $tongthuenk = 0;
            $tongthuevat = 0;

            if ($lists) {
                foreach ($lists as $tire) {
                    
                    $gia = $tire->tire_price;
                    $gia_giam = $tire->tire_price_down;
                    $stuff = $tire->tire_stuff;
                    
                    $sl += $tire->tire_number;
                    $tonggia += $gia*$tire->tire_number;
                    $tonggiagiam += $gia_giam*$tire->tire_number;
                    $tongphantram += $tire->tire_number/$stuff;
                    
                    $bocxep = $tire->tire_stevedore;

                    $tongthuenk += $tire->tire_tax_import;
                    $tongthuevat += $tire->tire_tax_vat;

                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off" value="'.$tire->tire_brand_name.'" data="'.$tire->tire_brand.'">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off" value="'.$tire->tire_size_number.'" data="'.$tire->tire_size.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off" value="'.$tire->tire_pattern_name.'" data="'.$tire->tire_pattern.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off" value="'.$tire->tire_number.'" data="'.$tire->import_tire_list_id.'" ></td>
                        <td class="width-5"><input type="text" name="tire_price[]" class="tire_price numbers text-right" required="required" autocomplete="off" value="'.$gia.'"></td>
                        <td class="width-5"><input type="text" name="tire_price_down[]" class="tire_price_down numbers text-right" required="required" autocomplete="off" value="'.$gia_giam.'"></td>
                        <td class="width-7"><input type="text" name="tire_stuff[]" class="tire_stuff numbers text-right" required="required" autocomplete="off" value="'.$stuff.'"></td>
                        <td class="width-5"><input type="text" name="tire_ocean_freight[]" class="tire_ocean_freight text-right" required="required" autocomplete="off" value="'.$tire->tire_ocean_freight.'"></td>
                        <td class="width-7"><input type="text" name="tire_price_sum[]" class="tire_price_sum numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_price_sum).'"></td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_import[]" class="tire_tax_import numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_tax_import).'">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_vat[]" class="tire_tax_vat numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_tax_vat).'">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax[]" class="tire_tax numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_tax_vat+$tire->tire_tax_import).'">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bill[]" class="tire_bill numbers text-right" autocomplete="off" value="'.$tire->tire_bill.'">
                          <input type="hidden" name="tire_tthq[]" class="tire_tthq numbers text-right" autocomplete="off" value="'.$tire->tire_tthq.'">
                          <input type="hidden" name="tire_lift[]" class="tire_lift numbers text-right" autocomplete="off" value="'.$tire->tire_lift.'">
                          <input type="hidden" name="tire_trucking[]" class="tire_trucking numbers text-right" autocomplete="off" value="'.$tire->tire_trucking.'">
                          
                          <input type="text" name="tire_logistics[]" class="tire_logistics numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney(($tire->tire_bill+$tire->tire_tthq+$tire->tire_lift+$tire->tire_trucking)*$tire->tire_number).'">
                        </td>
                        <td class="width-5">
                        <input type="text" name="tire_stevedore[]" class="tire_stevedore numbers text-right" autocomplete="off" value="'.$this->lib->formatMoney($bocxep).'">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bank_cost[]" class="tire_bank_cost numbers text-right" autocomplete="off" value="'.$tire->tire_bank_cost.'">
                          <input type="hidden" name="tire_other_cost[]" class="tire_other_cost numbers text-right" autocomplete="off" value="'.$tire->tire_other_cost.'">
                          <input type="text" name="tire_cost[]" class="tire_cost numbers text-right" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_bank_cost+$tire->tire_other_cost).'">
                        </td>
                        <td class="width-10"><input type="text" name="tire_price_total[]" class="tire_price_total numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_price_total).'"></td>
                        <td class="width-7"><input type="text" name="tire_price_origin[]" class="tire_price_origin numbers text-right" required="required" autocomplete="off" value="'.$this->lib->formatMoney($tire->tire_price_origin,2).'"></td>';
                    
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_price[]" class="tire_price numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_price_down[]" class="tire_price_down numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_stuff[]" class="tire_stuff numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-5"><input type="text" name="tire_ocean_freight[]" class="tire_ocean_freight text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_sum[]" class="tire_price_sum numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_import[]" class="tire_tax_import numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax_vat[]" class="tire_tax_vat numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_tax[]" class="tire_tax numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bill[]" class="tire_bill numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_tthq[]" class="tire_tthq numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_lift[]" class="tire_lift numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_trucking[]" class="tire_trucking numbers text-right" autocomplete="off">
                          
                          <input type="text" name="tire_logistics[]" class="tire_logistics numbers text-right" required="required" autocomplete="off">
                        </td>
                        <td class="width-5">
                        <input type="text" name="tire_stevedore[]" class="tire_stevedore numbers text-right" autocomplete="off">
                        </td>
                        <td class="width-7">
                          <input type="hidden" name="tire_bank_cost[]" class="tire_bank_cost numbers text-right" autocomplete="off">
                          <input type="hidden" name="tire_other_cost[]" class="tire_other_cost numbers text-right" autocomplete="off">
                          <input type="text" name="tire_cost[]" class="tire_cost numbers text-right" autocomplete="off">
                        </td>
                        <td class="width-10"><input type="text" name="tire_price_total[]" class="tire_price_total numbers text-right" required="required" autocomplete="off"></td>
                        <td class="width-7"><input type="text" name="tire_price_origin[]" class="tire_price_origin numbers text-right" required="required" autocomplete="off"></td>';
                
                $str .= '</tr>';

            }

            $str2 .= '<tr>';
            $str2 .= '<th class="width-3"></th>
                    <th class="width-10">Tổng</th>
                    <th class="width-10"></th>
                    <th class="width-7"></th>
                    <th class="width-5 numbers" id="tongsl">'.$sl.'</th>
                    <th class="width-5 numbers" id="tonggia">'.$tonggia.'</th>
                    <th class="width-5 numbers" id="tonggiagiam">'.$tonggiagiam.'</th>
                    <th class="width-7" id="tongphantram">'.round($tongphantram*100,7).'</th>
                    <th class="width-5 numbers" id="tongcuoctau">'.$this->lib->formatMoney($orders->import_tire_order_oceanfreight).'</th>
                    <th class="width-7 numbers editable" id="tongtienhang">'.$this->lib->formatMoney($orders->import_tire_order_sum).'</th>
                    <th class="width-7 numbers editable" id="tongthuenk">'.$this->lib->formatMoney($tongthuenk).'</th>
                    <th class="width-7 numbers editable" id="tongthuevat">'.$this->lib->formatMoney($tongthuevat).'</th>
                    <th class="width-7 numbers editable" id="tongthue">'.$this->lib->formatMoney($orders->import_tire_order_tax).'</th>
                    <th class="width-7 numbers editable" id="tonglogs">'.$this->lib->formatMoney($orders->import_tire_order_logistics).'</th>
                    <th class="width-5 numbers editable" id="tongbocxep">'.$this->lib->formatMoney($orders->import_tire_order_stevedore).'</th>
                    <th class="width-7 numbers editable" id="tongphikhac">'.$this->lib->formatMoney($orders->import_tire_order_bank_cost+$orders->import_tire_order_other_cost).'</th>
                    <th class="width-10 numbers editable" id="tongcong">'.$this->lib->formatMoney($orders->import_tire_order_sum+$orders->import_tire_order_tax+$orders->import_tire_order_logistics+$orders->import_tire_order_stevedore+$orders->import_tire_order_bank_cost+$orders->import_tire_order_other_cost).'</th>
                    <th class="width-7"></th>
                    <th style="width:5px"></th>';
            
            $str2 .= '</tr>';
            

            $arr = array(
                'hang'=>$str,
                'tong'=>$str2,
            );
            echo json_encode($arr);
    }
    

}
?>