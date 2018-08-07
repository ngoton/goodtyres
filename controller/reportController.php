<?php
Class reportController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;
        }
        else{
            $dat = date('d-m-Y');
            $tuan = (int)date('W', strtotime($dat))-1;
            $tuan_truoc = $tuan-1;
            $nam_truoc = (int)date('Y');
            $nam_report = (int)date('Y');

            if (date('N', strtotime(date('d-m-Y'))) >= 6) {
                $tuan ++;
                $tuan_truoc ++;
            }
        }

        

        $number = $tuan-$tuan_truoc;
        $new_batdau_truoc = array();
        $new_ketthuc_truoc = array();

        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');
        
        $assets_model = $this->model->get('assetsModel');

        $costs_model = $this->model->get('costsModel');
        $advance_model = $this->model->get('advanceModel');
        $payable_model = $this->model->get('payableModel');

        $min_data = array();
        for ($i=1; $i <= $number; $i++) { 
            $new_mang_truoc = $this->getStartAndEndDate(($tuan_truoc+$i),$nam_truoc);
            $new_batdau_truoc[$tuan_truoc+$i] = $new_mang_truoc[0];
            $new_ketthuc_truoc[$tuan_truoc+$i] = $new_mang_truoc[1];

            

        }

        
        $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
        $batdau_truoc = $mang_truoc[0];
        $ketthuc_truoc = $mang_truoc[1];

        $mang = $this->getStartAndEndDate($tuan,$nam_report);
        $batdau = $mang[0];
        $ketthuc = $mang[1];

        // $sale_model = $this->model->get('salereportModel');
        // $agent_model = $this->model->get('agentModel');
        // $agentmanifest_model = $this->model->get('agentmanifestModel');
        // $invoice_model = $this->model->get('invoiceModel');
        // $trading_model = $this->model->get('tradingModel');
        

        



        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        

        $asset_data = array();
        $asset_data_truoc = array();
        $new_asset_data_truoc = array();

        foreach ($banks as $bank) {
           
            // $where = array(
            //     'where' => 'assets_date <= '.strtotime($ketthuc).' AND bank = '.$bank->bank_id,
            // );
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') )  AND bank = '.$bank->bank_id,
            );
            $assets = $assets_model->getAllAssets($where);
            
            
            foreach ($assets as $asset) {
                $asset_data[$asset->bank] = isset($asset_data[$asset->bank])?($asset_data[$asset->bank]+$asset->total):0+$asset->total;
            }

            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND bank = '.$bank->bank_id,
            );
            $assets_truoc = $assets_model->getAllAssets($where_truoc);
            
            
            foreach ($assets_truoc as $asset) {
                $asset_data_truoc[$asset->bank] = isset($asset_data_truoc[$asset->bank])?($asset_data_truoc[$asset->bank]+$asset->total):0+$asset->total;
            }

            for ($i=1; $i <= $number; $i++) { 
                $new_where_truoc = array(
                    'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND bank = '.$bank->bank_id,
                );
                $new_assets_truoc = $assets_model->getAllAssets($new_where_truoc);
                
                
                foreach ($new_assets_truoc as $asset) {
                    $new_asset_data_truoc[$tuan_truoc+$i][$asset->bank] = isset($new_asset_data_truoc[$tuan_truoc+$i][$asset->bank])?($new_asset_data_truoc[$tuan_truoc+$i][$asset->bank]+$asset->total):0+$asset->total;
                }
            }


        }

        $customer_model = $this->model->get('customerModel');
        $obtain_model = $this->model->get('obtainModel');

        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $customers_data = $customer_model->getAllCustomer($where);
        
        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $customers = $customer_model->getAllCustomer($where);


        $obtains_data = array();
        foreach ($customers as $customer) {
         
                $where = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND customer = '.$customer->customer_id,
                );
                $obtains = $obtain_model->getAllObtain($where);


                foreach ($obtains as $obtain) {
                    $obtains_data['money'][$obtain->customer] = isset($obtains_data['money'][$obtain->customer])?($obtains_data['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                   
                }
        }

        $where_truoc = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $customers_truoc = $customer_model->getAllCustomer($where_truoc);


        $obtains_data_truoc = array();
        foreach ($customers_truoc as $customer) {
         
                $where_truoc = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND customer = '.$customer->customer_id,
                );
                $obtains_truoc = $obtain_model->getAllObtain($where_truoc);


                foreach ($obtains_truoc as $obtain) {
                    $obtains_data_truoc['money'][$obtain->customer] = isset($obtains_data_truoc['money'][$obtain->customer])?($obtains_data_truoc['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                }
        }

        $new_obtains_data_truoc = array();
        $new_customers_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'customer_id in (SELECT customer FROM obtain WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_customers_truoc = $customer_model->getAllCustomer($new_where_truoc);

            foreach ($new_customers_truoc as $customer) {
             
                    $new_where_truoc = array(
                        'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND customer = '.$customer->customer_id,
                    );
                    $new_obtains_truoc = $obtain_model->getAllObtain($new_where_truoc);


                    foreach ($new_obtains_truoc as $obtain) {
                        $new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer] = isset($new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer])?($new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                    }
            }
        }
        
        
        $owe_model = $this->model->get('oweModel');
        $vendor_model = $this->model->get('shipmentvendorModel');

        $where = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $shipvendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_ops_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors_ops = $vendor_model->getAllVendor($where);
        
         $owes_data = array();
        foreach ($shipvendors as $vendor) {
           
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes = $owe_model->getAllOwe($where);
           
            

            foreach ($owes as $owe) {
                $owes_data['money'][$owe->vendor] = isset($owes_data['money'][$owe->vendor])?($owes_data['money'][$owe->vendor]+$owe->money):0+$owe->money;

               
            }
        }

        $where_truoc = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $shipvendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_ops_truoc = $vendor_model->getAllVendor($where_truoc);
        
         $owes_data_truoc = array();

        foreach ($shipvendors_truoc as $vendor) {
           
            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes_truoc = $owe_model->getAllOwe($where_truoc);
           

            foreach ($owes_truoc as $owe) {
                $owes_data_truoc['money'][$owe->vendor] = isset($owes_data_truoc['money'][$owe->vendor])?($owes_data_truoc['money'][$owe->vendor]+$owe->money):0+$owe->money;

                
            }
        }


        $new_owes_data_truoc = array();
        $new_vendors_truoc = null;
        $new_shipvendors_truoc = null;
        $new_vendors_ops_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_shipvendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_vendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_vendors_ops_truoc = $vendor_model->getAllVendor($new_where_truoc);
            
             

            foreach ($new_shipvendors_truoc as $vendor) {
               
                $new_where_truoc = array(
                    'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND vendor = '.$vendor->shipment_vendor_id,
                );
                $new_owes_truoc = $owe_model->getAllOwe($new_where_truoc);
               
                foreach ($new_owes_truoc as $owe) {
                    $new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor] = isset($new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor])?($new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor]+$owe->money):0+$owe->money;

                }
            }
        }

        
        

        $staff_model = $this->model->get('staffModel');
        
        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $staffs_data = $staff_model->getAllStaff($where);

        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $staffs = $staff_model->getAllStaff($where);

        $staff_debt_model = $this->model->get('staffdebtModel');

        $staff_debt_data = array();
        foreach ($staffs as $staff) {
           
        
            $join = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['co'][$staff_debt->staff] = isset($staff_debt_data['co'][$staff_debt->staff])?($staff_debt_data['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['no'][$staff_debt->staff] = isset($staff_debt_data['no'][$staff_debt->staff])?($staff_debt_data['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }

        $where_truoc = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $staffs_truoc = $staff_model->getAllStaff($where_truoc);


        $staff_debt_data_truoc = array();
        foreach ($staffs_truoc as $staff) {
           
        
            $join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where_truoc = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['co'][$staff_debt->staff] = isset($staff_debt_data_truoc['co'][$staff_debt->staff])?($staff_debt_data_truoc['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where_truoc = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['no'][$staff_debt->staff] = isset($staff_debt_data_truoc['no'][$staff_debt->staff])?($staff_debt_data_truoc['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }


        $new_staff_debt_data_truoc = array();
        $new_staffs_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_staffs_truoc = $staff_model->getAllStaff($new_where_truoc);


            
            foreach ($new_staffs_truoc as $staff) {
               
            
                $new_join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
                $new_where_truoc = array(
                    'where' => 'staff_debt.status=1 AND week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff])?($new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }

                $new_where_truoc = array(
                    'where' => 'staff_debt.status=2 AND week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff])?($new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }
            }
        }

        $luong = null;

        $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan).' AND assets.year = '.$nam_report.')';
        $luongs = $assets_model->queryAssets($q_luong);
        $luong_datra = 0;

        foreach ($luongs as $luongthang) {
            $luong += str_replace('-', "", $luongthang->total);
        }

        


        $salary_model = $this->model->get('newsalaryModel');
/*
        $thang = date('m',strtotime($ketthuc));
        $nam = date('Y',strtotime($ketthuc));
        $ngay = '08-'.$thang.'-'.$nam;
        $ngay2 = '14-'.$thang.'-'.$nam;

        $luong = null;

        if(strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)){
            $lthang = $thang-1;
            $lnam = $nam;
            if ($thang == 1) {
                $lthang = 12;
                $lnam = $nam-1;
            }
            
            $where = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang.'-'.$lnam),
            );
            $salarys = $salary_model->getAllSalary($where);
            $tongluong = 0;
            foreach ($salarys as $salary) {
                $tongluong += $salary->total;
            }


            $luong = $tongluong;

            
        }

        $ngay = '14-'.$thang.'-'.$nam;
        $ngay2 = '17-'.$thang.'-'.$nam;

        if(strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)){
            $lthang = $thang-1;
            $lnam = $nam;
            if ($thang == 1) {
                $lthang = 12;
                $lnam = $nam-1;
            }
            
            $where = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang.'-'.$lnam),
            );
            $salarys = $salary_model->getAllSalary($where);
            $tongluong = 0;
            foreach ($salarys as $salary) {
                $tongluong += $salary->total;
            }


            $luong = $tongluong;

            $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets.assets_date >= '.strtotime($ngay).' AND assets.assets_date <= '.strtotime($ngay2);
            $luongs = $assets_model->queryAssets($q_luong);
            $luong_datra = 0;

            foreach ($luongs as $luongthang) {
                $luong_datra += $luongthang->total;
            }

            $luong = $luong+$luong_datra;
        }*/

        /*$ngay = '13-'.$thang.'-'.$nam;
        $ngay2 = '17-'.$thang.'-'.$nam;

        if((strtotime($ngay) <= strtotime($batdau) && strtotime($ngay2) >= strtotime($batdau)) || (strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)) ){

            $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc);
            $luongs = $assets_model->queryAssets($q_luong);
            $luong_datra = 0;

            foreach ($luongs as $luongthang) {
                $luong_datra += $luongthang->total;
            }

            $luong = $luong-$luong_datra;
        }

        if (strtotime($ngay2) <= strtotime($batdau)) {
            $luong = null;
        }*/

        $luong_truoc = null;
        
        $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc).' AND assets.year = '.$nam_truoc.')';
        $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
        $luong_datra_truoc = 0;

        foreach ($luongs_truoc as $luongthang_truoc) {
            $luong_truoc += str_replace('-', "", $luongthang_truoc->total);
        }

        /*$thang_truoc = date('m',strtotime($ketthuc_truoc));
        $nam_truoc = date('Y',strtotime($ketthuc_truoc));
        $ngay_truoc = '08-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '14-'.$thang_truoc.'-'.$nam_truoc;

        $luong_truoc = null;

        if(strtotime($ngay_truoc) <= strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) >= strtotime($ketthuc_truoc) ){
            $lthang_truoc = $thang_truoc-1;
            $lnam_truoc = $nam_truoc;
            if ($thang_truoc == 1) {
                $lthang_truoc = 12;
                $lnam_truoc = $nam_truoc-1;
            }
            
            $where_truoc = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang_truoc.'-'.$lnam_truoc),
            );
            $salarys_truoc = $salary_model->getAllSalary($where_truoc);
            $tongluong_truoc = 0;
            foreach ($salarys_truoc as $salary) {
                $tongluong_truoc += $salary->total;
            }


            $luong_truoc = $tongluong_truoc;
        }

        $ngay_truoc = '14-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '17-'.$thang_truoc.'-'.$nam_truoc;

        if(strtotime($ngay_truoc) <= strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) >= strtotime($ketthuc_truoc) ){
            $lthang_truoc = $thang_truoc-1;
            $lnam_truoc = $nam_truoc;
            if ($thang_truoc == 1) {
                $lthang_truoc = 12;
                $lnam_truoc = $nam_truoc-1;
            }
            
            $where_truoc = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang_truoc.'-'.$lnam_truoc),
            );
            $salarys_truoc = $salary_model->getAllSalary($where_truoc);
            $tongluong_truoc = 0;
            foreach ($salarys_truoc as $salary) {
                $tongluong_truoc += $salary->total;
            }


            $luong_truoc = $tongluong_truoc;

            $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets.assets_date >= '.strtotime($ngay_truoc).' AND assets.assets_date <= '.strtotime($ngay2_truoc);
            $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
            $luong_datra_truoc = 0;

            foreach ($luongs_truoc as $luongthang_truoc) {
                $luong_datra_truoc += $luongthang_truoc->total;
            }

            $luong_truoc = $luong_truoc+$luong_datra_truoc;
        }*/

        //  $ngay_truoc = '15-'.$thang_truoc.'-'.$nam_truoc;

        // if(strtotime($ngay_truoc)<=strtotime($ketthuc_truoc) && strtotime($ngay_truoc)>=strtotime($batdau_truoc)){
        //     $luong_truoc = null;
        // }

        /*$ngay_truoc = '13-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '17-'.$thang_truoc.'-'.$nam_truoc;

        if((strtotime($ngay_truoc) < strtotime($batdau_truoc) && strtotime($ngay2_truoc) > strtotime($batdau_truoc)) || (strtotime($ngay_truoc) < strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) > strtotime($ketthuc_truoc)) ){

            $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets_date >= '.strtotime($batdau_truoc).' AND assets_date <= '.strtotime($ketthuc_truoc);
            $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
            $luong_datra_truoc = 0;

            foreach ($luongs_truoc as $luongthang_truoc) {
                $luong_datra_truoc += $luongthang_truoc->total;
            }

            $luong_truoc = $luong_truoc-$luong_datra_truoc;
        }

        if (strtotime($ngay2_truoc) <= strtotime($batdau_truoc)) {
            $luong_truoc = null;
        }*/

       

        $new_luong_truoc = null;
        $new_luong_truoc_tra = null;

        for ($i=1; $i <= $number; $i++) { 

            $new_nam_truoc[$tuan_truoc+$i] = date('Y',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));

            $new_q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc+$i).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc = $assets_model->queryAssets($new_q_luong_truoc);
            $new_luong_datra_truoc = 0;

            $new_luong_truoc[$tuan_truoc+$i] = 0;

            foreach ($new_luongs_truoc as $new_luongthang_truoc) {
                $new_luong_truoc[$tuan_truoc+$i] += str_replace('-', "", $new_luongthang_truoc->total);
            }



            $new_q_luong_truoc_tra = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc+$i).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc_tra = $assets_model->queryAssets($new_q_luong_truoc_tra);
            $new_luong_datra_truoc_tra = 0;

            $new_luong_truoc_tra[$tuan_truoc+$i] = 0;

            foreach ($new_luongs_truoc_tra as $new_luongthang_truoc_tra) {
                $new_luong_truoc_tra[$tuan_truoc+$i] += str_replace('-', "", $new_luongthang_truoc_tra->total);
            }

            /*$new_thang_truoc[$tuan_truoc+$i] = date('m',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));
            $new_nam_truoc[$tuan_truoc+$i] = date('Y',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));
            $new_ngay_truoc[$tuan_truoc+$i] = '05-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];
            $new_ngay2_truoc[$tuan_truoc+$i] = '08-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];

            

            if((strtotime($new_ngay_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i])) || (strtotime($new_ngay2_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay2_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i]))){
                $new_lthang_truoc[$tuan_truoc+$i] = $new_thang_truoc[$tuan_truoc+$i]-1;
                $new_lnam_truoc[$tuan_truoc+$i] = $new_nam_truoc[$tuan_truoc+$i];
                if ($new_thang_truoc[$tuan_truoc+$i] == 1) {
                    $new_lthang_truoc[$tuan_truoc+$i] = 12;
                    $new_lnam_truoc[$tuan_truoc+$i] = $new_nam_truoc[$tuan_truoc+$i]-1;
                }
                
                $new_where_truoc = array(
                    'where'=> 'create_time = '.strtotime('01-'.$new_lthang_truoc[$tuan_truoc+$i].'-'.$new_lnam_truoc[$tuan_truoc+$i]),
                );
                $new_salarys_truoc = $salary_model->getAllSalary($new_where_truoc);
                $new_tongluong_truoc[$tuan_truoc+$i] = 0;
                foreach ($new_salarys_truoc as $salary) {
                    $new_tongluong_truoc[$tuan_truoc+$i] += $salary->total;
                }


                $new_luong_truoc[$tuan_truoc+$i] = $new_tongluong_truoc[$tuan_truoc+$i];
            }



             $new_ngay_truoc[$tuan_truoc+$i] = '15-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];

            if(strtotime($new_ngay_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i])){
                $new_luong_truoc[$tuan_truoc+$i] = null;


            }*/
        }

        $this->view->data['banks'] = $banks;

        $this->view->data['asset'] = $asset_data;
        $this->view->data['obtains'] = $obtains_data;
        $this->view->data['customers'] = $customers;
        $this->view->data['owe'] = $owes_data;
        $this->view->data['vendors'] = $vendors;
        $this->view->data['vendors_ops'] = $vendors_ops;
        $this->view->data['staffs'] = $staffs;
        $this->view->data['debt'] = $staff_debt_data;
        $this->view->data['salary'] = $luong;
        $this->view->data['tuan'] = $tuan;

        $this->view->data['asset_truoc'] = $asset_data_truoc;
        $this->view->data['obtains_truoc'] = $obtains_data_truoc;
        $this->view->data['customers_truoc'] = $customers_truoc;
        $this->view->data['owe_truoc'] = $owes_data_truoc;
        $this->view->data['vendors_truoc'] = $vendors_truoc;
        $this->view->data['vendors_ops_truoc'] = $vendors_ops_truoc;
        $this->view->data['staffs_truoc'] = $staffs_truoc;
        $this->view->data['debt_truoc'] = $staff_debt_data_truoc;
        $this->view->data['salary_truoc'] = $luong_truoc;
        $this->view->data['tuantruoc'] = $tuan_truoc;

        $this->view->data['new_asset_truoc'] = $new_asset_data_truoc;
        $this->view->data['new_obtains_truoc'] = $new_obtains_data_truoc;
        $this->view->data['new_customers_truoc'] = $new_customers_truoc;
        $this->view->data['new_owe_truoc'] = $new_owes_data_truoc;
        $this->view->data['new_vendors_truoc'] = $new_vendors_truoc;
        $this->view->data['new_vendors_ops_truoc'] = $new_vendors_ops_truoc;
        $this->view->data['new_staffs_truoc'] = $new_staffs_truoc;
        $this->view->data['new_debt_truoc'] = $new_staff_debt_data_truoc;
        $this->view->data['new_salary_truoc'] = $new_luong_truoc;
        $this->view->data['number'] = $number;

        $this->view->data['nam'] = $nam_report;
        $this->view->data['namtruoc'] = $nam_truoc;

        $this->view->data['customers_data'] = $customers_data;
        $this->view->data['vendors_data'] = $vendors_data;
        $this->view->data['vendors_ops_data'] = $vendors_ops_data;
        $this->view->data['staffs_data'] = $staffs_data;

        $this->view->data['new_salary_truoc_tra'] = $new_luong_truoc_tra;
        
        $this->view->show('report/index');
    }

    public function report() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;
        }
        else{
            $dat = date('d-m-Y');
            $tuan = (int)date('W', strtotime($dat))-1;
            $tuan_truoc = $tuan-1;
            $nam_truoc = (int)date('Y');
            $nam_report = (int)date('Y');

            if (date('N', strtotime(date('d-m-Y'))) >= 6) {
                $tuan ++;
                $tuan_truoc ++;
            }

            $nam_truoc = $tuan_truoc==-1?$nam_truoc-1:$nam_truoc;
            $tuan_truoc = $tuan_truoc==-1?52:$tuan_truoc;
            $tuan = $tuan==0?1:$tuan;
        }

        
        if ($nam_truoc==$nam_report) {
            $number = $tuan-$tuan_truoc;
        }
        else if ($nam_report>$nam_truoc) {
            $sonam = $nam_report-$nam_truoc;
            $tuancuoi = date('W',strtotime('31-12-'.$nam_truoc));
            $number = ($tuancuoi-$tuan_truoc)+($tuan-1)*$sonam;
        }
        
        $new_batdau_truoc = array();
        $new_ketthuc_truoc = array();

        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_import_model = $this->model->get('tireimportModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        
        $assets_model = $this->model->get('assetsModel');

        $costs_model = $this->model->get('costsModel');
        $advance_model = $this->model->get('advanceModel');
        $payable_model = $this->model->get('payableModel');

        $min_data = array();
        for ($i=1; $i <= $number; $i++) { 
            $new_mang_truoc = $this->getStartAndEndDate(($tuan_truoc+$i),$nam_truoc);
            $new_batdau_truoc[$tuan_truoc+$i] = $new_mang_truoc[0];
            $new_ketthuc_truoc[$tuan_truoc+$i] = $new_mang_truoc[1];

            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $max_data = array();
            
            $m = 0;
            $assets_data = $assets_model->queryAssets('select * from assets where assets_date >= '.strtotime($new_mang_truoc[0]).' AND assets_date <= '.strtotime($new_mang_truoc[1]).' AND (sec is null or sec <= 0) AND  ( costs > 0 AND costs in (SELECT costs_id FROM costs WHERE (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff IS NULL OR staff <= 0) AND (check_salary IS NULL OR check_salary <= 0) AND (money_in IS NULL OR money_in <= 0) ) )  order by total asc limit 3');

            if($assets_data){
                foreach ($assets_data as $as) {
                    if ($as->costs > 0) {
                        $costs_data = $costs_model->getCosts($as->costs);
                        $min_data[$t][$n][$m]['date'] = $as->assets_date;
                        $min_data[$t][$n][$m]['money_in'] = 0;
                        $min_data[$t][$n][$m]['money'] = $as->total;
                        $min_data[$t][$n][$m]['comment'] = substr($costs_data->comment,0,30).'...';
                    }
                     $m++;                  
                }
            }
            
                $min_array = array();

                    $sdata = array(
                        'where' => '(revenue+revenue_vat+other_revenue+other_revenue_vat-cost-cost_vat) <= 0 AND sale_date >= '.strtotime($new_mang_truoc[0]).' AND sale_date <= '.strtotime($new_mang_truoc[1]),
                    );

                    $sales = $sale_model->getAllSale($sdata);

                    foreach ($sales as $sale) {
                        $min_array[$sale->code]['date'] = $sale->sale_date;
                        $min_array[$sale->code]['money_in'] = $sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat;
                        $min_array[$sale->code]['money'] = $sale->cost_vat+$sale->cost;
                        $min_array[$sale->code]['comment'] = $sale->code;
                        $min_array[$sale->code]['loinhuan'] = ($sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat)-($sale->cost_vat+$sale->cost);
                        
                    }

                    $adata = array(
                        'where' => '(total_offer-(total_cost+bill_cost-(cost_17*160000+cost_18*40000+document_cost+pay_cost))) <= 0 AND agent_date >= '.strtotime($new_mang_truoc[0]).' AND agent_date <= '.strtotime($new_mang_truoc[1]),
                    );

                    $agents = $agent_model->getAllAgent($adata);

                    foreach ($agents as $agent) {
                        $min_array[$agent->code]['date'] = $agent->agent_date;
                        $min_array[$agent->code]['money_in'] = $agent->total_offer;
                        $min_array[$agent->code]['money'] = $agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost);
                        $min_array[$agent->code]['comment'] = $agent->code;
                        $min_array[$agent->code]['loinhuan'] = $agent->total_offer-($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
                    }

                    $data_manifest = array(
                        'where' => '(revenue_vat-cost_sg-cost_cm-driver_cost-commission_cost-other_cost-other_vendor_cost) <= 0 AND agent_manifest_date >= '.strtotime($new_mang_truoc[0]).' AND agent_manifest_date <= '.strtotime($new_mang_truoc[1]),
                    );

                    $manifests = $manifest_model->getAllAgent($data_manifest);

                    foreach ($manifests as $agent) {
                        $min_array[$agent->code]['date'] = $agent->agent_manifest_date;
                        $min_array[$agent->code]['money_in'] = $agent->revenue_vat+$agent->revenue;
                        $min_array[$agent->code]['money'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
                        $min_array[$agent->code]['comment'] = $agent->code;
                        $min_array[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
                    }

                    $idata = array(
                        'where' => '(receive-pay1-pay2-pay3) <= 0 AND  day_invoice >= '.strtotime($new_mang_truoc[0]).' AND day_invoice <= '.strtotime($new_mang_truoc[1]),
                    );

                    $invoices = $invoice_model->getAllInvoice($idata);

                    foreach ($invoices as $invoice) {
                        $min_array[$invoice->invoice_number]['date'] = $invoice->day_invoice;
                        $min_array[$invoice->invoice_number]['money_in'] = $invoice->receive;
                        $min_array[$invoice->invoice_number]['money'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
                        $min_array[$invoice->invoice_number]['comment'] = $invoice->invoice_number;
                        $min_array[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);
                    }

                    usort($min_array, function ($item1, $item2) {
                        if ($item1['loinhuan'] == $item2['loinhuan']) return 0;
                        return $item1['loinhuan'] > $item2['loinhuan'] ? -1 : 1;
                    });

                    $min_datas[$t][$n] = array_slice($min_array, -3, 3);
            
            $financial_data = array();


            $sdata = array(
                'where' => 'sale_date >= '.strtotime($new_mang_truoc[0]).' AND sale_date <= '.strtotime($new_mang_truoc[1]),
            );

            $sales = $sale_model->getAllSale($sdata);

            foreach ($sales as $sale) {
                $max_data[$sale->code]['code'] = $sale->code;
                $max_data[$sale->code]['doanhthu'] = $sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat;
                $max_data[$sale->code]['chiphi'] = $sale->cost_vat+($sale->cost*1.1);
                $max_data[$sale->code]['loinhuan'] = ($sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat)-($sale->cost_vat+($sale->cost*1.1));
                $max_data[$sale->code]['loinhuansau'] = ($sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat)-($sale->cost_vat+($sale->cost*1.1))-round((($sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat)-($sale->cost_vat+($sale->cost*1.1)))*0.1);


                $financial_data[$sale->code]['code'] = $sale->code;
                $financial_data[$sale->code]['doanhthu'] = round(($sale->cost*0.1)+((($sale->revenue_vat+$sale->revenue+$sale->other_revenue+$sale->other_revenue_vat)-($sale->cost_vat+($sale->cost*1.1)))*0.1));
            }

            $adata = array(
                'where' => 'agent_date >= '.strtotime($new_mang_truoc[0]).' AND agent_date <= '.strtotime($new_mang_truoc[1]),
            );

            $agents = $agent_model->getAllAgent($adata);

            foreach ($agents as $agent) {
                $max_data[$agent->code]['code'] = $agent->code;
                $max_data[$agent->code]['doanhthu'] = $agent->total_offer;
                $max_data[$agent->code]['chiphi'] = ($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost))*1.1;
                $max_data[$agent->code]['loinhuan'] = $agent->total_offer-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1;
                $max_data[$agent->code]['loinhuansau'] = ($agent->total_offer-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)-round(($agent->total_offer-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)*0.1);

                $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
                foreach ($phi_agent as $phi) {
                    $phidaily = $phi->money;
                }
                $phidaily = 0;

                $financial_data[$agent->code]['code'] = $agent->code;
                $financial_data[$agent->code]['doanhthu'] = round((($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost))*0.1)+(($agent->total_offer+$phidaily-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)*0.1));
            }

            $data_manifest = array(
                'where' => 'agent_manifest_date >= '.strtotime($new_mang_truoc[0]).' AND agent_manifest_date <= '.strtotime($new_mang_truoc[1]),
            );

            $manifests = $manifest_model->getAllAgent($data_manifest);

            foreach ($manifests as $agent) {
                $max_data[$agent->code]['code'] = $agent->code;
                $max_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
                $max_data[$agent->code]['chiphi'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1;
                $max_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1;
                $max_data[$agent->code]['loinhuansau'] = ($agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1)-round((($agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1))*0.1);
                
                $financial_data[$agent->code]['code'] = $agent->code;
                $financial_data[$agent->code]['doanhthu'] = round((($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*0.1)+(($agent->revenue_vat+$agent->revenue-(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1))*0.1));
            }

            $idata = array(
                'where' => 'day_invoice >= '.strtotime($new_mang_truoc[0]).' AND day_invoice <= '.strtotime($new_mang_truoc[1]),
            );

            $invoices = $invoice_model->getAllInvoice($idata);

            foreach ($invoices as $invoice) {
                $max_data[$invoice->invoice_number]['code'] = $invoice->invoice_number;
                $max_data[$invoice->invoice_number]['doanhthu'] = $invoice->receive;
                $max_data[$invoice->invoice_number]['chiphi'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
                $max_data[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);
                $max_data[$invoice->invoice_number]['loinhuansau'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3)-round(($invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3))*0.1);

            }

            usort($max_data, function ($item1, $item2) {
                if ($item1['loinhuan'] == $item2['loinhuan']) return 0;
                return $item1['loinhuan'] > $item2['loinhuan'] ? -1 : 1;
            });

            $max_array[$t][$n] = array_slice($max_data, 0, 3);

            usort($financial_data, function ($item1, $item2) {
                if ($item1['doanhthu'] == $item2['doanhthu']) return 0;
                return $item1['doanhthu'] > $item2['doanhthu'] ? -1 : 1;
            });

            $max_financial_data[$t][$n] = array_slice($financial_data, 0, 3);

        }

        $this->view->data['min_array'] = $min_data;
        $this->view->data['min_arrays'] = $min_datas;
        $this->view->data['max_array'] = $max_array;
        $this->view->data['max_financial'] = $max_financial_data;
        
        
        $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
        $batdau_truoc = $mang_truoc[0];
        $ketthuc_truoc = $mang_truoc[1];

        $mang = $this->getStartAndEndDate($tuan,$nam_report);
        $batdau = $mang[0];
        $ketthuc = $mang[1];

        // $sale_model = $this->model->get('salereportModel');
        // $agent_model = $this->model->get('agentModel');
        // $agentmanifest_model = $this->model->get('agentmanifestModel');
        // $invoice_model = $this->model->get('invoiceModel');
        // $trading_model = $this->model->get('tradingModel');

        $ajoin = array('table'=>'fixed_asset_buy','where'=>'assets.fixed_asset_buy = fixed_asset_buy.fixed_asset_buy_id');
        $adata = array(
            'where' => 'total != 0 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )',
        );

        $asset_buys = $assets_model->getAllAssets($adata,$ajoin);

        $mua_ts = 0;
        foreach ($asset_buys as $asset_buy) {
            $mua_ts += $asset_buy->fixed_asset_buy_money;
        }

        $mjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
        $mdata = array(
            'where' => 'costs.check_office = 1 AND money=0 AND money_in>0 AND (invoice_balance <= 0 OR invoice_balance IS NULL)  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )',
        );

        $costs = $assets_model->getAllAssets($mdata,$mjoin);

        $khoanthu = 0;

        foreach ($costs as $cost) {
            $khoanthu += $cost->total;
            
        }
        

        $cjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
        $cdata = array(
            'where' => 'costs.check_office = 1 AND (money!=money_in OR money_in IS NULL) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )',
        );

        $costs = $assets_model->getAllAssets($cdata,$cjoin);

        $hanhchinh = 0;

        foreach ($costs as $cost) {
            $hanhchinh += $cost->total;
            if ($cost->staff_cost > 0) {
                $hanhchinh = $hanhchinh + $cost->staff_cost;
            }
        }

        $max_data = array();

        $sdata = array(
            'where' => 'sale_type = 1 AND sale_date > '.strtotime($ketthuc_truoc).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($sdata);

        $sale_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $sale_data['doanhthu'] = isset($sale_data['doanhthu'])?($sale_data['doanhthu']+$sale->revenue+$sale->revenue_vat):($sale->revenue+$sale->revenue_vat);
            $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $sale_data['kvat'] = isset($sale_data['kvat'])?($sale_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND sale_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )');
        foreach ($other_cost as $cost) {
            if ($cost->check_invoice == 1) {
                $sale_data['invoice'] = isset($sale_data['invoice'])?($sale_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $tdata = array(
            'where' => 'sale_type = 2 AND sale_date > '.strtotime($ketthuc_truoc).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($tdata);

        $trading_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $trading_data['doanhthu'] = isset($trading_data['doanhthu'])?($trading_data['doanhthu']+$sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat):($sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat);
            $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $trading_data['kvat'] = isset($trading_data['kvat'])?($trading_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND trading_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $trading_data['invoice'] = isset($trading_data['invoice'])?($trading_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $adata = array(
            'where' => 'agent_date > '.strtotime($ketthuc_truoc).' AND agent_date <= '.strtotime($ketthuc),
        );

        $agents = $agent_model->getAllAgent($adata);

        $agent_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );

        $phidaily = 0;

        foreach ($agents as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->total_offer):(0+$agent->total_offer);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));

            $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
            foreach ($phi_agent as $phi) {
                $phidaily = $phi->money;
            }
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$phidaily):(0+$phidaily);
            
            $phidaily = 0;

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->total_offer;
            $max_data[$agent->code]['chiphi'] = $agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->total_offer-($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
        }

        $data_manifest = array(
            'where' => 'agent_manifest_date > '.strtotime($ketthuc_truoc).' AND agent_manifest_date <= '.strtotime($ketthuc),
        );

        $manifests = $manifest_model->getAllAgent($data_manifest);

        foreach ($manifests as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->revenue_vat+$agent->revenue):(0+$agent->revenue_vat+$agent->revenue);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
            $max_data[$agent->code]['chiphi'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
    
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND agent_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $agent_data['invoice'] = isset($agent_data['invoice'])?($agent_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $idata = array(
            'where' => 'day_invoice > '.strtotime($ketthuc_truoc).' AND day_invoice <= '.strtotime($ketthuc),
        );

        $invoices = $invoice_model->getAllInvoice($idata);

        $invoice_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($invoices as $invoice) {
            $invoice_data['doanhthu'] = isset($invoice_data['doanhthu'])?($invoice_data['doanhthu']+$invoice->receive):(0+$invoice->receive);
            $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+($invoice->pay1+$invoice->pay2+$invoice->pay3)):(0+($invoice->pay1+$invoice->pay2+$invoice->pay3));
            $invoice_data['kvat'] = isset($invoice_data['kvat'])?($invoice_data['kvat']+$invoice->pay1+$invoice->pay2):(0+$invoice->pay1+$invoice->pay2);

            $max_data[$invoice->invoice_number]['code'] = $invoice->invoice_number;
            $max_data[$invoice->invoice_number]['doanhthu'] = $invoice->receive;
            $max_data[$invoice->invoice_number]['chiphi'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
            $max_data[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);

        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND tcmt_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $invoice_data['invoice'] = isset($invoice_data['invoice'])?($invoice_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }

        $tire_imports = $tire_import_model->getAllTire();
        $tire_prices = array();
        $count = array();
        foreach ($tire_imports as $tire) {
            if (isset($tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern])) {
                $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+1;
                $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+$tire->tire_price;
            }
            else{
                $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = 1;
                $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire->tire_price;
            }
        }

        $odata = array(
            'where' => 'order_tire_status=1 AND delivery_date > '.strtotime($ketthuc_truoc).' AND delivery_date <= '.strtotime($ketthuc),
        );

        $orders = $order_tire_model->getAllTire($odata);

        $order_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
        );
        foreach ($orders as $order) {
            $order_data['doanhthu'] = isset($order_data['doanhthu'])?($order_data['doanhthu']+$order->total):(0+$order->total);
            $order_data['chiphi'] = isset($order_data['chiphi'])?($order_data['chiphi']+$order->order_cost):(0+$order->order_cost);
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $gia = isset($tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern]:0;
                $sl = isset($count[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$count[$l->tire_brand][$l->tire_size][$l->tire_pattern]:1;
                $order_data['chiphi'] = isset($order_data['chiphi'])?($order_data['chiphi']+$l->tire_number*($gia/$sl)):$l->tire_number*($gia/$sl);
            }
        }


        $this->view->data['lopxe_data'] = $order_data;
        $this->view->data['invoice_data'] = $invoice_data;
        $this->view->data['agent_data'] = $agent_data;
        $this->view->data['sale_data'] = $sale_data;
        $this->view->data['trading_data'] = $trading_data;
        $this->view->data['hanhchinh'] = $hanhchinh;
        $this->view->data['mua_ts'] = $mua_ts;
        $this->view->data['khoanthu'] = $khoanthu;



        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        
        $bankings = $bank_model->getAllBank(array('where'=>'bank_id > 2'));
        $this->view->data['bankings'] = $bankings;

        $asset_data = array();
        $asset_data_truoc = array();
        $new_asset_data_truoc = array();

        foreach ($banks as $bank) {
           
            // $where = array(
            //     'where' => 'assets_date <= '.strtotime($ketthuc).' AND bank = '.$bank->bank_id,
            // );
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') )  AND bank = '.$bank->bank_id,
            );
            $assets = $assets_model->getAllAssets($where);
            
            
            foreach ($assets as $asset) {
                $asset_data[$asset->bank] = isset($asset_data[$asset->bank])?($asset_data[$asset->bank]+$asset->total):0+$asset->total;
            }

            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND bank = '.$bank->bank_id,
            );
            $assets_truoc = $assets_model->getAllAssets($where_truoc);
            
            
            foreach ($assets_truoc as $asset) {
                $asset_data_truoc[$asset->bank] = isset($asset_data_truoc[$asset->bank])?($asset_data_truoc[$asset->bank]+$asset->total):0+$asset->total;
            }

            for ($i=1; $i <= $number; $i++) { 
                $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
                $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

                $new_where_truoc = array(
                    'where' => 'week = '.($t).' AND year = '.$n.' AND bank = '.$bank->bank_id,
                );
                $new_assets_truoc = $assets_model->getAllAssets($new_where_truoc);
                
                
                foreach ($new_assets_truoc as $asset) {
                    $new_asset_data_truoc[$t][$n][$asset->bank] = isset($new_asset_data_truoc[$t][$n][$asset->bank])?($new_asset_data_truoc[$t][$n][$asset->bank]+$asset->total):0+$asset->total;
                }
            }


        }

        $customer_model = $this->model->get('customerModel');
        $obtain_model = $this->model->get('obtainModel');

        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $customers_data = $customer_model->getAllCustomer($where);
        
        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $customers = $customer_model->getAllCustomer($where);


        $obtains_data = array();
        foreach ($customers as $customer) {
         
                $where = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND customer = '.$customer->customer_id,
                );
                $obtains = $obtain_model->getAllObtain($where);


                foreach ($obtains as $obtain) {
                    $obtains_data['money'][$obtain->customer] = isset($obtains_data['money'][$obtain->customer])?($obtains_data['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                   
                }
        }

        $where_truoc = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $customers_truoc = $customer_model->getAllCustomer($where_truoc);


        $obtains_data_truoc = array();
        foreach ($customers_truoc as $customer) {
         
                $where_truoc = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND customer = '.$customer->customer_id,
                );
                $obtains_truoc = $obtain_model->getAllObtain($where_truoc);


                foreach ($obtains_truoc as $obtain) {
                    $obtains_data_truoc['money'][$obtain->customer] = isset($obtains_data_truoc['money'][$obtain->customer])?($obtains_data_truoc['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                }
        }

        $new_obtains_data_truoc = array();
        $new_customers_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_where_truoc = array(
                'where' => 'customer_id in (SELECT customer FROM obtain WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_customers_truoc = $customer_model->getAllCustomer($new_where_truoc);

            foreach ($new_customers_truoc as $customer) {
             
                    $new_where_truoc = array(
                        'where' => 'week = '.($t).' AND year = '.$n.' AND customer = '.$customer->customer_id,
                    );
                    $new_obtains_truoc = $obtain_model->getAllObtain($new_where_truoc);


                    foreach ($new_obtains_truoc as $obtain) {
                        $new_obtains_data_truoc[$t][$n]['money'][$obtain->customer] = isset($new_obtains_data_truoc[$t][$n]['money'][$obtain->customer])?($new_obtains_data_truoc[$t][$n]['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                    }
            }
        }
        
        
        $owe_model = $this->model->get('oweModel');
        $vendor_model = $this->model->get('shipmentvendorModel');

        $lender_owe_model = $this->model->get('lenderoweModel');
        $lender_model = $this->model->get('lenderModel');

        $where = array(
            'where' => 'lender_id in (SELECT lender FROM lender_owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $lenders = $lender_model->getAllLender($where);

        $where = array(
            'where' => 'lender_id in (SELECT lender FROM lender_owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $lenders_data = $lender_model->getAllLender($where);

        $where = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $shipvendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_ops_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors_ops = $vendor_model->getAllVendor($where);
        
         $owes_data = array();
        foreach ($shipvendors as $vendor) {
           
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes = $owe_model->getAllOwe($where);
           
            

            foreach ($owes as $owe) {
                $owes_data['money'][$owe->vendor] = isset($owes_data['money'][$owe->vendor])?($owes_data['money'][$owe->vendor]+$owe->money):0+$owe->money;

               
            }
        }

        $lender_owes_data = array();

        foreach ($lenders as $lender) {
           
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND lender = '.$lender->lender_id,
            );
            $owes = $lender_owe_model->getAllLender($where);
           
            

            foreach ($owes as $owe) {
                $lender_owes_data['money'][$owe->lender] = isset($lender_owes_data['money'][$owe->lender])?($lender_owes_data['money'][$owe->lender]+$owe->money):0+$owe->money;

               
            }
        }

        $where_truoc = array(
            'where' => 'lender_id in (SELECT lender FROM lender_owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $lenders_truoc = $lender_model->getAllLender($where_truoc);

        $where_truoc = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $shipvendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_ops_truoc = $vendor_model->getAllVendor($where_truoc);
        
         $owes_data_truoc = array();

        foreach ($shipvendors_truoc as $vendor) {
           
            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes_truoc = $owe_model->getAllOwe($where_truoc);
           

            foreach ($owes_truoc as $owe) {
                $owes_data_truoc['money'][$owe->vendor] = isset($owes_data_truoc['money'][$owe->vendor])?($owes_data_truoc['money'][$owe->vendor]+$owe->money):0+$owe->money;

                
            }
        }

        $lender_owes_data_truoc = array();

        foreach ($lenders_truoc as $lender) {
           
            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND lender = '.$lender->lender_id,
            );
            $owes_truoc = $lender_owe_model->getAllLender($where_truoc);
           

            foreach ($owes_truoc as $owe) {
                $lender_owes_data_truoc['money'][$owe->lender] = isset($lender_owes_data_truoc['money'][$owe->lender])?($lender_owes_data_truoc['money'][$owe->lender]+$owe->money):0+$owe->money;

                
            }
        }

        $new_lender_owes_data_truoc = array();
        $new_lenders_truoc = null;

        $new_owes_data_truoc = array();
        $new_vendors_truoc = null;
        $new_shipvendors_truoc = null;
        $new_vendors_ops_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_where_truoc = array(
                'where' => 'lender_id in (SELECT lender FROM lender_owe WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_lenders_truoc = $lender_model->getAllLender($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_shipvendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_vendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_vendors_ops_truoc = $vendor_model->getAllVendor($new_where_truoc);
            
             

            foreach ($new_shipvendors_truoc as $vendor) {
               
                $new_where_truoc = array(
                    'where' => 'week = '.($t).' AND year = '.$n.' AND vendor = '.$vendor->shipment_vendor_id,
                );
                $new_owes_truoc = $owe_model->getAllOwe($new_where_truoc);
               
                foreach ($new_owes_truoc as $owe) {
                    $new_owes_data_truoc[$t][$n]['money'][$owe->vendor] = isset($new_owes_data_truoc[$t][$n]['money'][$owe->vendor])?($new_owes_data_truoc[$t][$n]['money'][$owe->vendor]+$owe->money):0+$owe->money;

                }
            }

            foreach ($new_lenders_truoc as $lender) {
               
                $new_where_truoc = array(
                    'where' => 'week = '.($t).' AND year = '.$n.' AND lender = '.$lender->lender_id,
                );
                $new_owes_truoc = $lender_owe_model->getAllLender($new_where_truoc);
               
                foreach ($new_owes_truoc as $owe) {
                    $new_lender_owes_data_truoc[$t][$n]['money'][$owe->lender] = isset($new_lender_owes_data_truoc[$t][$n]['money'][$owe->lender])?($new_lender_owes_data_truoc[$t][$n]['money'][$owe->lender]+$owe->money):0+$owe->money;

                }
            }

        }

        
        

        $staff_model = $this->model->get('staffModel');
        
        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $staffs_data = $staff_model->getAllStaff($where);

        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $staffs = $staff_model->getAllStaff($where);

        $staff_debt_model = $this->model->get('staffdebtModel');

        $staff_debt_data = array();
        foreach ($staffs as $staff) {
           
        
            $join = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['co'][$staff_debt->staff] = isset($staff_debt_data['co'][$staff_debt->staff])?($staff_debt_data['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['no'][$staff_debt->staff] = isset($staff_debt_data['no'][$staff_debt->staff])?($staff_debt_data['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }

        $where_truoc = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $staffs_truoc = $staff_model->getAllStaff($where_truoc);


        $staff_debt_data_truoc = array();
        foreach ($staffs_truoc as $staff) {
           
        
            $join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where_truoc = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['co'][$staff_debt->staff] = isset($staff_debt_data_truoc['co'][$staff_debt->staff])?($staff_debt_data_truoc['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where_truoc = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['no'][$staff_debt->staff] = isset($staff_debt_data_truoc['no'][$staff_debt->staff])?($staff_debt_data_truoc['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }


        $new_staff_debt_data_truoc = array();
        $new_staffs_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_where_truoc = array(
                'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE week = '.($t).' AND year = '.$n.')',
            );
            $new_staffs_truoc = $staff_model->getAllStaff($new_where_truoc);


            
            foreach ($new_staffs_truoc as $staff) {
               
            
                $new_join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
                $new_where_truoc = array(
                    'where' => 'staff_debt.status=1 AND week = '.($t).' AND year = '.$n.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$t][$n]['co'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$t][$n]['co'][$staff_debt->staff])?($new_staff_debt_data_truoc[$t][$n]['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }

                $new_where_truoc = array(
                    'where' => 'staff_debt.status=2 AND week = '.($t).' AND year = '.$n.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$t][$n]['no'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$t][$n]['no'][$staff_debt->staff])?($new_staff_debt_data_truoc[$t][$n]['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }
            }
        }


        $import_tire_model = $this->model->get('importtireModel');

        
        $buy_data = array();
        $imports_data = array();

        $tire_buy_tires = $tire_buy_model->queryTire('SELECT * FROM tire_buy WHERE tire_buy_date <= '.strtotime($ketthuc).' GROUP BY code');
        foreach ($tire_buy_tires as $tire) {
            $buy_data[$tire->code][date('W',$tire->tire_buy_date)][date('Y',$tire->tire_buy_date)] = 1;

            $ims = $import_tire_model->getAllSale(array('where'=>'code = '.$tire->code));
            foreach ($ims as $im) {
                $imports_data['money'] = isset($imports_data['money'])?$imports_data['money']-$im->cost-$im->cost_vat:0-$im->cost-$im->cost_vat;
            }
        }


        $where = array(
            'where' => 'import_tire_date <= '.strtotime($ketthuc),
        );
        $imports = $import_tire_model->getAllSale($where);
        
        
        foreach ($imports as $import) {
           
            $imports_data['money'] = isset($imports_data['money'])?$imports_data['money']+$import->cost+$import->cost_vat:$import->cost+$import->cost_vat;
            
        }



        $imports_data_truoc = array();
        
        $tire_buy_tires = $tire_buy_model->queryTire('SELECT * FROM tire_buy WHERE tire_buy_date <= '.strtotime($ketthuc_truoc).' GROUP BY code');
        foreach ($tire_buy_tires as $tire) {
            $buy_data[$tire->code][date('W',$tire->tire_buy_date)][date('Y',$tire->tire_buy_date)] = 1;
            $im_truocs = $import_tire_model->getAllSale(array('where'=>'code = '.$tire->code));
            foreach ($im_truocs as $im_truoc) {
                $imports_data_truoc['money'] = isset($imports_data_truoc['money'])?$imports_data_truoc['money']-$im_truoc->cost-$im_truoc->cost_vat:0-$im_truoc->cost-$im_truoc->cost_vat;
            }
        }

        $where_truoc = array(
            'where' => 'import_tire_date <= '.strtotime($ketthuc_truoc),
        );
        $imports_truoc = $import_tire_model->getAllSale($where_truoc);
        
        

        foreach ($imports_truoc as $import) {
           
            $imports_data_truoc['money'] = isset($imports_data_truoc['money'])?$imports_data_truoc['money']+$import->cost+$import->cost_vat:$import->cost+$import->cost_vat;
            
        }

        

        $new_imports_data_truoc = array();

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_mang_truoc = $this->getStartAndEndDate(($tuan_truoc+$i),$nam_truoc);
            $new_batdau_truoc[$tuan_truoc+$i] = $new_mang_truoc[0];
            $new_ketthuc_truoc[$tuan_truoc+$i] = $new_mang_truoc[1];

            $tire_buy_tires = $tire_buy_model->queryTire('SELECT * FROM tire_buy WHERE tire_buy_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND tire_buy_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).' GROUP BY code');
            foreach ($tire_buy_tires as $tire) {
                $buy_data[$tire->code][date('W',$tire->tire_buy_date)][date('Y',$tire->tire_buy_date)] = 1;
                $new_im_truocs = $import_tire_model->getAllSale(array('where'=>'code = '.$tire->code));
                foreach ($new_im_truocs as $new_im_truoc) {
                    $new_imports_data_truoc['money'][$t][$n] = isset($new_imports_data_truoc['money'][$t][$n])?$new_imports_data_truoc['money'][$t][$n]-$new_im_truoc->cost-$new_im_truoc->cost_vat:0-$new_im_truoc->cost-$new_im_truoc->cost_vat;
                }
            }

            $new_where_truoc = array(
                'where' => "import_tire_date >= ".strtotime($new_batdau_truoc[$tuan_truoc+$i])." AND import_tire_date <= ".strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );
            $new_imports_truoc = $import_tire_model->getAllSale($new_where_truoc);

            foreach ($new_imports_truoc as $import) {
               
                $new_imports_data_truoc['money'][$t][$n] = isset($new_imports_data_truoc['money'][$t][$n])?$new_imports_data_truoc['money'][$t][$n]+$import->cost+$import->cost_vat:$import->cost+$import->cost_vat;
                
            }

            

        }

        $query = "SELECT * FROM tire_buy WHERE tire_buy_date <= ".strtotime($ketthuc_truoc);
        $tire_buys = $tire_buy_model->queryTire($query);

        $ton_tamung = array();

        foreach ($tire_buys as $tire_buy) {
            $im_truocs = $import_tire_model->getAllSale(array('where'=>'code = '.$tire_buy->code));
            if (!$im_truocs) {
                $ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern] = isset($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]+$tire_buy->tire_buy_volume:$tire_buy->tire_buy_volume;
            }
 
        }
        $data_sale = array(
            'where'=>'tire_sale_date <= '.strtotime($ketthuc_truoc),
        );
        $tire_sales = $tire_sale_model->getAllTire($data_sale);

        foreach ($tire_sales as $tire_sale) {
            
                if ($tire_sale->order_tire == "" || $tire_sale->order_tire == 0) {
                    $ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern] = isset($ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern])?$ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern]-$tire_sale->volume:0-$tire_sale->volume;
                }
             
        }

        

        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_buy_date <= ".strtotime($ketthuc_truoc)." AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);

        $sell_truoc = array();
        $thanhtien_truoc = 0;
        foreach ($tire_buys as $tire_buy) {

            $data_sale = array(
                'where'=>'tire_sale_date <= '.strtotime($ketthuc_truoc).' AND tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                //if ($tire_sale->customer != 119) {
                    $sell_truoc[$tire_buy->tire_buy_id]['number'] = isset($sell_truoc[$tire_buy->tire_buy_id]['number'])?$sell_truoc[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;

                //}
                
            }

            $ton = $tire_buy->soluong - (isset($sell_truoc[$tire_buy->tire_buy_id]['number'])?$sell_truoc[$tire_buy->tire_buy_id]['number']:0);

            $gia = isset($tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:0;
            $sl = isset($count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:1;
            $giatri = $gia/$sl;

            if($ton > 0){
                
                $thanhtien_truoc += $ton*$giatri;
                
            }

            if (isset($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]) && $ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern] > 0) {
                $imports_data_truoc['money'] = isset($imports_data_truoc['money'])?$imports_data_truoc['money']-($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]*$giatri):0-($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]*$giatri);
            }

 
        }
        $this->view->data['tonkho_truoc'] = $thanhtien_truoc;


        $query = "SELECT * FROM tire_buy WHERE tire_buy_date <= ".strtotime($ketthuc);
        $tire_buys = $tire_buy_model->queryTire($query);

        $ton_tamung = array();

        foreach ($tire_buys as $tire_buy) {
            $im_truocs = $import_tire_model->getAllSale(array('where'=>'code = '.$tire_buy->code));
            if (!$im_truocs) {
                $ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern] = isset($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]+$tire_buy->tire_buy_volume:$tire_buy->tire_buy_volume;
            }
 
        }
        $data_sale = array(
            'where'=>'tire_sale_date <= '.strtotime($ketthuc),
        );
        $tire_sales = $tire_sale_model->getAllTire($data_sale);

        foreach ($tire_sales as $tire_sale) {
            
                if ($tire_sale->order_tire == "" || $tire_sale->order_tire == 0) {
                    $ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern] = isset($ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern])?$ton_tamung[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern]-$tire_sale->volume:0-$tire_sale->volume;
                }
             
        }

        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_buy_date <= ".strtotime($ketthuc)." AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);

        $sell = array();
        $thanhtien = 0;
        foreach ($tire_buys as $tire_buy) {

            $data_sale = array(
                'where'=>'tire_sale_date <= '.strtotime($ketthuc).' AND tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                //if ($tire_sale->customer != 119) {
                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;
                //}
                
            }

            $ton = $tire_buy->soluong - (isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']:0);

            $gia = isset($tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:0;
            $sl = isset($count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:1;
            $giatri = $gia/$sl;

            if($ton > 0){
                
                $thanhtien += $ton*$giatri;
            }

            if (isset($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]) && $ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern] > 0) {
                $imports_data['money'] = isset($imports_data['money'])?$imports_data['money']-($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]*$giatri):0-($ton_tamung[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]*$giatri);
            }

            
        }
        $this->view->data['tonkho'] = $thanhtien;

        $new_tonkho_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_mang_truoc = $this->getStartAndEndDate(($tuan_truoc+$i),$nam_truoc);
            $new_batdau_truoc[$tuan_truoc+$i] = $new_mang_truoc[0];
            $new_ketthuc_truoc[$tuan_truoc+$i] = $new_mang_truoc[1];

            $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_buy_date >= ".strtotime($new_batdau_truoc[$tuan_truoc+$i])." AND tire_buy_date <= ".strtotime($new_ketthuc_truoc[$tuan_truoc+$i])." AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
            $tire_buys = $tire_buy_model->queryTire($query);

            $new_sell = array();
            $new_thanhtien = 0;
            foreach ($tire_buys as $tire_buy) {
                $gia = isset($tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$tire_prices[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:0;
                $sl = isset($count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern])?$count[$tire_buy->tire_buy_brand][$tire_buy->tire_buy_size][$tire_buy->tire_buy_pattern]:1;
                $giatri = $gia/$sl;
                $new_thanhtien += $tire_buy->soluong*$giatri;

                /*$new_im_truocs = $import_tire_model->getAllSale(array('where'=>'code = '.$tire_buy->code));
                if (!$new_im_truocs) {
                    $new_imports_data_truoc['money'][$t][$n] = isset($new_imports_data_truoc['money'][$t][$n])?$new_imports_data_truoc['money'][$t][$n]-($tire_buy->soluong*$giatri):0-($tire_buy->soluong*$giatri);
                }*/
            }

            $query = "SELECT *,SUM(volume) AS soluong FROM tire_sale, tire_brand, tire_size, tire_pattern WHERE tire_sale_date >= ".strtotime($new_batdau_truoc[$tuan_truoc+$i])." AND tire_sale_date <= ".strtotime($new_ketthuc_truoc[$tuan_truoc+$i])." AND tire_brand.tire_brand_id = tire_sale.tire_brand AND tire_size.tire_size_id = tire_sale.tire_size AND tire_pattern.tire_pattern_id = tire_sale.tire_pattern GROUP BY tire_brand,tire_size,tire_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
            $tire_sales = $tire_sale_model->queryTire($query);
            foreach ($tire_sales as $tire_sale) {
                
                //if ($tire_sale->customer != 119) {
                    $gia = isset($tire_prices[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern])?$tire_prices[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern]:0;
                    $sl = isset($count[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern])?$count[$tire_sale->tire_brand][$tire_sale->tire_size][$tire_sale->tire_pattern]:1;
                    $giatri = $gia/$sl;
                    $new_thanhtien -= $tire_sale->soluong*$giatri;

                    $lopxe_data['doanhthu'] = isset($lopxe_data['doanhthu'])?$lopxe_data['doanhthu']+($tire_sale->soluong*$giatri):$tire_sale->soluong*$giatri;
                //}
                
            }

            $new_tonkho_truoc[$t][$n] = $new_thanhtien;


        }

        $this->view->data['imports_data'] = $imports_data;
        $this->view->data['imports_data_truoc'] = $imports_data_truoc;
        $this->view->data['new_imports_data_truoc'] = $new_imports_data_truoc;

        $this->view->data['new_tonkho_truoc'] = $new_tonkho_truoc;


        $luong = null;

        $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan).' AND assets.year = '.$nam_report.')';
        $luongs = $assets_model->queryAssets($q_luong);
        $luong_datra = 0;

        foreach ($luongs as $luongthang) {
            $luong += str_replace('-', "", $luongthang->total);
        }

        


        $salary_model = $this->model->get('newsalaryModel');
/*
        $thang = date('m',strtotime($ketthuc));
        $nam = date('Y',strtotime($ketthuc));
        $ngay = '08-'.$thang.'-'.$nam;
        $ngay2 = '14-'.$thang.'-'.$nam;

        $luong = null;

        if(strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)){
            $lthang = $thang-1;
            $lnam = $nam;
            if ($thang == 1) {
                $lthang = 12;
                $lnam = $nam-1;
            }
            
            $where = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang.'-'.$lnam),
            );
            $salarys = $salary_model->getAllSalary($where);
            $tongluong = 0;
            foreach ($salarys as $salary) {
                $tongluong += $salary->total;
            }


            $luong = $tongluong;

            
        }

        $ngay = '14-'.$thang.'-'.$nam;
        $ngay2 = '17-'.$thang.'-'.$nam;

        if(strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)){
            $lthang = $thang-1;
            $lnam = $nam;
            if ($thang == 1) {
                $lthang = 12;
                $lnam = $nam-1;
            }
            
            $where = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang.'-'.$lnam),
            );
            $salarys = $salary_model->getAllSalary($where);
            $tongluong = 0;
            foreach ($salarys as $salary) {
                $tongluong += $salary->total;
            }


            $luong = $tongluong;

            $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets.assets_date >= '.strtotime($ngay).' AND assets.assets_date <= '.strtotime($ngay2);
            $luongs = $assets_model->queryAssets($q_luong);
            $luong_datra = 0;

            foreach ($luongs as $luongthang) {
                $luong_datra += $luongthang->total;
            }

            $luong = $luong+$luong_datra;
        }*/

        /*$ngay = '13-'.$thang.'-'.$nam;
        $ngay2 = '17-'.$thang.'-'.$nam;

        if((strtotime($ngay) <= strtotime($batdau) && strtotime($ngay2) >= strtotime($batdau)) || (strtotime($ngay) <= strtotime($ketthuc) && strtotime($ngay2) >= strtotime($ketthuc)) ){

            $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets_date >= '.strtotime($batdau).' AND assets_date <= '.strtotime($ketthuc);
            $luongs = $assets_model->queryAssets($q_luong);
            $luong_datra = 0;

            foreach ($luongs as $luongthang) {
                $luong_datra += $luongthang->total;
            }

            $luong = $luong-$luong_datra;
        }

        if (strtotime($ngay2) <= strtotime($batdau)) {
            $luong = null;
        }*/

        $luong_truoc = null;
        
        $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc).' AND assets.year = '.$nam_truoc.')';
        $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
        $luong_datra_truoc = 0;

        foreach ($luongs_truoc as $luongthang_truoc) {
            $luong_truoc += str_replace('-', "", $luongthang_truoc->total);
        }

        /*$thang_truoc = date('m',strtotime($ketthuc_truoc));
        $nam_truoc = date('Y',strtotime($ketthuc_truoc));
        $ngay_truoc = '08-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '14-'.$thang_truoc.'-'.$nam_truoc;

        $luong_truoc = null;

        if(strtotime($ngay_truoc) <= strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) >= strtotime($ketthuc_truoc) ){
            $lthang_truoc = $thang_truoc-1;
            $lnam_truoc = $nam_truoc;
            if ($thang_truoc == 1) {
                $lthang_truoc = 12;
                $lnam_truoc = $nam_truoc-1;
            }
            
            $where_truoc = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang_truoc.'-'.$lnam_truoc),
            );
            $salarys_truoc = $salary_model->getAllSalary($where_truoc);
            $tongluong_truoc = 0;
            foreach ($salarys_truoc as $salary) {
                $tongluong_truoc += $salary->total;
            }


            $luong_truoc = $tongluong_truoc;
        }

        $ngay_truoc = '14-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '17-'.$thang_truoc.'-'.$nam_truoc;

        if(strtotime($ngay_truoc) <= strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) >= strtotime($ketthuc_truoc) ){
            $lthang_truoc = $thang_truoc-1;
            $lnam_truoc = $nam_truoc;
            if ($thang_truoc == 1) {
                $lthang_truoc = 12;
                $lnam_truoc = $nam_truoc-1;
            }
            
            $where_truoc = array(
                'where'=> 'create_time = '.strtotime('01-'.$lthang_truoc.'-'.$lnam_truoc),
            );
            $salarys_truoc = $salary_model->getAllSalary($where_truoc);
            $tongluong_truoc = 0;
            foreach ($salarys_truoc as $salary) {
                $tongluong_truoc += $salary->total;
            }


            $luong_truoc = $tongluong_truoc;

            $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets.assets_date >= '.strtotime($ngay_truoc).' AND assets.assets_date <= '.strtotime($ngay2_truoc);
            $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
            $luong_datra_truoc = 0;

            foreach ($luongs_truoc as $luongthang_truoc) {
                $luong_datra_truoc += $luongthang_truoc->total;
            }

            $luong_truoc = $luong_truoc+$luong_datra_truoc;
        }*/

        //  $ngay_truoc = '15-'.$thang_truoc.'-'.$nam_truoc;

        // if(strtotime($ngay_truoc)<=strtotime($ketthuc_truoc) && strtotime($ngay_truoc)>=strtotime($batdau_truoc)){
        //     $luong_truoc = null;
        // }

        /*$ngay_truoc = '13-'.$thang_truoc.'-'.$nam_truoc;
        $ngay2_truoc = '17-'.$thang_truoc.'-'.$nam_truoc;

        if((strtotime($ngay_truoc) < strtotime($batdau_truoc) && strtotime($ngay2_truoc) > strtotime($batdau_truoc)) || (strtotime($ngay_truoc) < strtotime($ketthuc_truoc) && strtotime($ngay2_truoc) > strtotime($ketthuc_truoc)) ){

            $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND assets_date >= '.strtotime($batdau_truoc).' AND assets_date <= '.strtotime($ketthuc_truoc);
            $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
            $luong_datra_truoc = 0;

            foreach ($luongs_truoc as $luongthang_truoc) {
                $luong_datra_truoc += $luongthang_truoc->total;
            }

            $luong_truoc = $luong_truoc-$luong_datra_truoc;
        }

        if (strtotime($ngay2_truoc) <= strtotime($batdau_truoc)) {
            $luong_truoc = null;
        }*/

       

        $new_luong_truoc = null;
        $new_luong_truoc_tra = null;

        for ($i=1; $i <= $number; $i++) { 
            $t = ($tuan_truoc+$i)>52?($tuan_truoc+$i-52):$tuan_truoc+$i;
            $n = ($tuan_truoc+$i)>52?$nam_report:$nam_truoc;

            $new_nam_truoc[$tuan_truoc+$i] = date('Y',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));

            $new_q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($t).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc = $assets_model->queryAssets($new_q_luong_truoc);
            $new_luong_datra_truoc = 0;

            $new_luong_truoc[$t][$n] = 0;

            foreach ($new_luongs_truoc as $new_luongthang_truoc) {
                $new_luong_truoc[$t][$n] += str_replace('-', "", $new_luongthang_truoc->total);
            }



            $new_q_luong_truoc_tra = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($t).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc_tra = $assets_model->queryAssets($new_q_luong_truoc_tra);
            $new_luong_datra_truoc_tra = 0;

            $new_luong_truoc_tra[$t][$n] = 0;

            foreach ($new_luongs_truoc_tra as $new_luongthang_truoc_tra) {
                $new_luong_truoc_tra[$t][$n] += str_replace('-', "", $new_luongthang_truoc_tra->total);
            }

            /*$new_thang_truoc[$t][$n] = date('m',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));
            $new_nam_truoc[$tuan_truoc+$i] = date('Y',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));
            $new_ngay_truoc[$tuan_truoc+$i] = '05-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];
            $new_ngay2_truoc[$tuan_truoc+$i] = '08-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];

            

            if((strtotime($new_ngay_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i])) || (strtotime($new_ngay2_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay2_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i]))){
                $new_lthang_truoc[$tuan_truoc+$i] = $new_thang_truoc[$tuan_truoc+$i]-1;
                $new_lnam_truoc[$tuan_truoc+$i] = $new_nam_truoc[$tuan_truoc+$i];
                if ($new_thang_truoc[$tuan_truoc+$i] == 1) {
                    $new_lthang_truoc[$tuan_truoc+$i] = 12;
                    $new_lnam_truoc[$tuan_truoc+$i] = $new_nam_truoc[$tuan_truoc+$i]-1;
                }
                
                $new_where_truoc = array(
                    'where'=> 'create_time = '.strtotime('01-'.$new_lthang_truoc[$tuan_truoc+$i].'-'.$new_lnam_truoc[$tuan_truoc+$i]),
                );
                $new_salarys_truoc = $salary_model->getAllSalary($new_where_truoc);
                $new_tongluong_truoc[$tuan_truoc+$i] = 0;
                foreach ($new_salarys_truoc as $salary) {
                    $new_tongluong_truoc[$tuan_truoc+$i] += $salary->total;
                }


                $new_luong_truoc[$tuan_truoc+$i] = $new_tongluong_truoc[$tuan_truoc+$i];
            }



             $new_ngay_truoc[$tuan_truoc+$i] = '15-'.$new_thang_truoc[$tuan_truoc+$i].'-'.$new_nam_truoc[$tuan_truoc+$i];

            if(strtotime($new_ngay_truoc[$tuan_truoc+$i])<=strtotime($new_ketthuc_truoc[$tuan_truoc+$i]) && strtotime($new_ngay_truoc[$tuan_truoc+$i])>=strtotime($new_batdau_truoc[$tuan_truoc+$i])){
                $new_luong_truoc[$tuan_truoc+$i] = null;


            }*/
        }

        $this->view->data['banks'] = $banks;

        $this->view->data['asset'] = $asset_data;
        $this->view->data['obtains'] = $obtains_data;
        $this->view->data['customers'] = $customers;
        $this->view->data['owe'] = $owes_data;
        $this->view->data['vendors'] = $vendors;
        $this->view->data['vendors_ops'] = $vendors_ops;
        $this->view->data['staffs'] = $staffs;
        $this->view->data['debt'] = $staff_debt_data;
        $this->view->data['salary'] = $luong;
        $this->view->data['tuan'] = $tuan;
        $this->view->data['lender_owe'] = $lender_owes_data;
        $this->view->data['lenders'] = $lenders;

        $this->view->data['asset_truoc'] = $asset_data_truoc;
        $this->view->data['obtains_truoc'] = $obtains_data_truoc;
        $this->view->data['customers_truoc'] = $customers_truoc;
        $this->view->data['owe_truoc'] = $owes_data_truoc;
        $this->view->data['vendors_truoc'] = $vendors_truoc;
        $this->view->data['vendors_ops_truoc'] = $vendors_ops_truoc;
        $this->view->data['staffs_truoc'] = $staffs_truoc;
        $this->view->data['debt_truoc'] = $staff_debt_data_truoc;
        $this->view->data['salary_truoc'] = $luong_truoc;
        $this->view->data['tuantruoc'] = $tuan_truoc;
        $this->view->data['lender_owe_truoc'] = $lender_owes_data_truoc;
        $this->view->data['lenders_truoc'] = $lenders_truoc;

        $this->view->data['new_asset_truoc'] = $new_asset_data_truoc;
        $this->view->data['new_obtains_truoc'] = $new_obtains_data_truoc;
        $this->view->data['new_customers_truoc'] = $new_customers_truoc;
        $this->view->data['new_owe_truoc'] = $new_owes_data_truoc;
        $this->view->data['new_vendors_truoc'] = $new_vendors_truoc;
        $this->view->data['new_vendors_ops_truoc'] = $new_vendors_ops_truoc;
        $this->view->data['new_staffs_truoc'] = $new_staffs_truoc;
        $this->view->data['new_debt_truoc'] = $new_staff_debt_data_truoc;
        $this->view->data['new_salary_truoc'] = $new_luong_truoc;
        $this->view->data['number'] = $number;
        $this->view->data['new_lender_owe_truoc'] = $new_lender_owes_data_truoc;
        $this->view->data['new_lenders_truoc'] = $new_lenders_truoc;

        $this->view->data['nam'] = $nam_report;
        $this->view->data['namtruoc'] = $nam_truoc;

        $this->view->data['customers_data'] = $customers_data;
        $this->view->data['vendors_data'] = $vendors_data;
        $this->view->data['vendors_ops_data'] = $vendors_ops_data;
        $this->view->data['staffs_data'] = $staffs_data;
        $this->view->data['lenders_data'] = $lenders_data;

        $this->view->data['new_salary_truoc_tra'] = $new_luong_truoc_tra;
        
        $this->view->show('report/report');
    }

   
    function getStartAndEndDate($week, $year)
    {
        $week_start = new DateTime();
        $week_start->setISODate($year,$week);
        $return[0] = $week_start->format('d-m-Y');
        $time = strtotime($return[0], time());
        $time += 6*24*3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }

    public function getVendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor = isset($_POST['vendor']) ? $_POST['vendor'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $owe_model = $this->model->get('oweModel');
            

            /*$join = array('table'=>'sale_report, owe','where'=>'(owe.sale_report = sale_vendor.sale_report OR owe.trading = sale_vendor.trading) AND (sale_report.sale_report_id = sale_vendor.sale_report OR sale_report.sale_report_id = sale_vendor.trading)');
            $data = array(
                'where'=> ' sale_vendor.vendor='.$vendor.' AND owe.week >= '.$tuantruoc.' AND owe.week <= '.$tuan.' AND owe.year='.date('Y'),
            );

            $vendors = $vendor_model->getAllVendor($data,$join);*/

                $vendors = $owe_model->query('SELECT owe.owe_date, owe.money FROM owe WHERE ( (owe.owe_date > '.strtotime($ketthuc_truoc).' AND owe.owe_date <= '.strtotime($ketthuc).')  ) AND owe.vendor='.$vendor);

                $tong = 0;
                $tr = "";
                if($vendors){
                    $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Số tiền</th></tr></thead><tbody>';
                    foreach ($vendors as $v) {
                        $tr.= '<tr>';
                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->owe_date).'</td>';
                        $tr.= '<td>'.$this->lib->formatMoney($v->money).'</td>';
                        $tr.= '</tr>';
                        $tong +=$v->money;
                    }

                    

                    $tr.= '<tfoot><tr style="color:red"><td>Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                    $tr.= "</tbody></table>";
                }
                
            

            echo $tr;

        }
    }

    


    public function getCustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = isset($_POST['customer']) ? $_POST['customer'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $obtain_model = $this->model->get('obtainModel');
            $customer_model = $this->model->get('customerModel');


            $customers = $obtain_model->query('SELECT obtain.obtain_date, obtain.money FROM obtain WHERE obtain.customer='.$customer.' AND ( (obtain.obtain_date > '.strtotime($ketthuc_truoc).' AND obtain.obtain_date <= '.strtotime($ketthuc).')  )  GROUP BY obtain.obtain_id');
            
            $tong = 0;
            $tr = "";
            if($customers){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Số tiền</th></tr></thead><tbody>';
                foreach ($customers as $v) {
                    $tr.= '<tr>';
                    $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->obtain_date).'</td>';
                    $tr.= '<td>'.$this->lib->formatMoney($v->money).'</td>';
                    $tr.= '</tr>';
                    $tong +=$v->money;
                }

                $tr.= '<tfoot><tr style="color:red"><td >Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";
            }
            

            echo $tr;

        }
    }

    public function getStaff(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staff = isset($_POST['staff']) ? $_POST['staff'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $staff_debt_model = $this->model->get('staffdebtModel');

            $tr = "";
            $tong = 0;

            $banks = $staff_debt_model->query('SELECT staff_debt_date, staff_debt.money, comment FROM staff_debt WHERE  staff='.$staff.' AND ( (staff_debt_date > '.strtotime($ketthuc_truoc).' AND staff_debt_date <= '.strtotime($ketthuc).')  ) ORDER BY staff_debt_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        $tr.= '<tr>';
                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->staff_debt_date).'</td>';
                        $tr.= '<td>'.$v->comment.'</td>';
                        $tr.= '<td>'.$this->lib->formatMoney($v->money).'</td>';
                        $tr.= '</tr>';
                        $tong +=$v->money;
                    }
                $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";
            }

            echo $tr;
        }
    }

    public function getBank(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $bank = isset($_POST['bank']) ? $_POST['bank'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tong = 0;

            $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets.assets_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        $tr.= '<tr>';
                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                        $tr.= '<td>'.$v->comment.'</td>';
                        $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                        $tr.= '</tr>';
                        $tong +=$v->total;
                    }

                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, advance WHERE assets.advance=advance.advance_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                }
                $banks = $assets_model->query('SELECT assets_date, total, receive_comment FROM assets, receive WHERE assets.receivable=receive.receivable AND assets.total = receive.money AND assets.assets_date = receive.receive_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->receive_comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                    
                }
                $banks = $assets_model->query('SELECT assets_date, total, pay_comment FROM assets, pay WHERE assets.payable=pay.payable AND assets.total = (0 - pay.money) AND assets.assets_date = pay.pay_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->pay_comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                    
                }
                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_cost WHERE assets.lender_cost=lender_cost.lender_cost_id AND assets.assets_date = lender_cost.lender_cost_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                    
                }
                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                    
                }

                $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";
            }
            else{
                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, advance WHERE assets.advance=advance.advance_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                if($banks){
                    $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                    foreach ($banks as $v) {
                            $tr.= '<tr>';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                            $tong +=$v->total;
                        }
                    $banks = $assets_model->query('SELECT assets_date, total, receive_comment FROM assets, receive WHERE assets.receivable=receive.receivable AND assets.total = receive.money AND assets.assets_date = receive.receive_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                    if($banks){
                        
                        foreach ($banks as $v) {
                                $tr.= '<tr>';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->receive_comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                                $tong +=$v->total;
                            }
                        
                    }
                    $banks = $assets_model->query('SELECT assets_date, total, pay_comment FROM assets, pay WHERE assets.payable=pay.payable AND assets.total = (0 - pay.money) AND assets.assets_date = pay.pay_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                    if($banks){
                        
                        foreach ($banks as $v) {
                                $tr.= '<tr>';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->pay_comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                                $tong +=$v->total;
                            }
                        
                    }
                    $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_cost WHERE assets.lender_cost=lender_cost.lender_cost_id AND assets.assets_date = lender_cost.lender_cost_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                    if($banks){
                        
                        foreach ($banks as $v) {
                                $tr.= '<tr>';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                                $tong +=$v->total;
                            }
                        
                    }
                    $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                    if($banks){
                        
                        foreach ($banks as $v) {
                                $tr.= '<tr>';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                                $tong +=$v->total;
                            }
                        
                    }
                    $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                    $tr.= "</tbody></table>";
                }
                else{
                    $banks = $assets_model->query('SELECT assets_date, total, receive_comment FROM assets, receive WHERE assets.receivable=receive.receivable AND assets.total = receive.money AND assets.assets_date = receive.receive_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                    if($banks){
                        $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                        foreach ($banks as $v) {
                                $tr.= '<tr>';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->receive_comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                                $tong +=$v->total;
                            }
                        $banks = $assets_model->query('SELECT assets_date, total, pay_comment FROM assets, pay WHERE assets.payable=pay.payable AND assets.total = (0 - pay.money) AND assets.assets_date = pay.pay_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                        if($banks){
                            
                            foreach ($banks as $v) {
                                    $tr.= '<tr>';
                                    $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                    $tr.= '<td>'.$v->pay_comment.'</td>';
                                    $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                    $tr.= '</tr>';
                                    $tong +=$v->total;
                                }
                            
                        }
                        $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_cost WHERE assets.lender_cost=lender_cost.lender_cost_id AND assets.assets_date = lender_cost.lender_cost_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                        if($banks){
                            
                            foreach ($banks as $v) {
                                    $tr.= '<tr>';
                                    $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                    $tr.= '<td>'.$v->comment.'</td>';
                                    $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                    $tr.= '</tr>';
                                    $tong +=$v->total;
                                }
                            
                        }
                        $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                        if($banks){
                            
                            foreach ($banks as $v) {
                                    $tr.= '<tr>';
                                    $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                    $tr.= '<td>'.$v->comment.'</td>';
                                    $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                    $tr.= '</tr>';
                                    $tong +=$v->total;
                                }
                            
                        }
                        $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                        $tr.= "</tbody></table>";
                    }
                    else{
                        $banks = $assets_model->query('SELECT assets_date, total, pay_comment FROM assets, pay WHERE assets.payable=pay.payable AND assets.total = (0 - pay.money) AND assets.assets_date = pay.pay_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                        if($banks){
                            $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                            foreach ($banks as $v) {
                                    $tr.= '<tr>';
                                    $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                    $tr.= '<td>'.$v->pay_comment.'</td>';
                                    $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                    $tr.= '</tr>';
                                    $tong +=$v->total;
                                }

                            $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_cost WHERE assets.lender_cost=lender_cost.lender_cost_id AND assets.assets_date = lender_cost.lender_cost_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                            if($banks){
                                
                                foreach ($banks as $v) {
                                        $tr.= '<tr>';
                                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                        $tr.= '<td>'.$v->comment.'</td>';
                                        $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                        $tr.= '</tr>';
                                        $tong +=$v->total;
                                    }
                                
                            }
                            $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                            if($banks){
                                
                                foreach ($banks as $v) {
                                        $tr.= '<tr>';
                                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                        $tr.= '<td>'.$v->comment.'</td>';
                                        $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                        $tr.= '</tr>';
                                        $tong +=$v->total;
                                    }
                                
                            }
                            $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                            $tr.= "</tbody></table>";
                        }
                        else{
                            $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_cost WHERE assets.lender_cost=lender_cost.lender_cost_id AND assets.assets_date = lender_cost.lender_cost_date AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                            if($banks){
                                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                                foreach ($banks as $v) {
                                        $tr.= '<tr>';
                                        $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                        $tr.= '<td>'.$v->comment.'</td>';
                                        $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                        $tr.= '</tr>';
                                        $tong +=$v->total;
                                    }
                                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                                if($banks){
                                    
                                    foreach ($banks as $v) {
                                            $tr.= '<tr>';
                                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                            $tr.= '<td>'.$v->comment.'</td>';
                                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                            $tr.= '</tr>';
                                            $tong +=$v->total;
                                        }
                                    
                                }
                                $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                                $tr.= "</tbody></table>";
                            }
                            else{
                                $banks = $assets_model->query('SELECT assets_date, total, comment FROM assets, lender_pay WHERE assets.lender_pay=lender_pay.lender_pay_id AND assets.total != 0 AND assets.bank='.$bank.' AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  )  ORDER BY assets.assets_date ASC');
                                if($banks){
                                    $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                                    foreach ($banks as $v) {
                                            $tr.= '<tr>';
                                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                            $tr.= '<td>'.$v->comment.'</td>';
                                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                            $tr.= '</tr>';
                                            $tong +=$v->total;
                                        }
                                    $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                                    $tr.= "</tbody></table>";
                                }
                            }
                        }
                    }
                }
            }

            echo $tr;
        }
    }

    public function getOffice(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tong = 0;
            $w = 0;
            $tongtuan = 0;

            $banks = $assets_model->query('SELECT assets.week, assets_date, total, comment FROM assets, costs WHERE assets.costs=costs.costs_id AND costs.check_office = 1 AND (money!=money_in OR money_in IS NULL) AND (check_salary IS NULL OR check_salary <=0) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets.assets_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        
                        if($v->week != $w && $w >= ($tuan_truoc+1)){
                            $tr.= '<tr class="show_week" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td>Chi phí hành chính</td>';
                            $tr.= '<td>'.($tongtuan>0?"+":null).$this->lib->formatMoney($tongtuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan'.$v->week.'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';

                            $tongtuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan'.$v->week.'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tong +=$v->total;
                        $tongtuan +=$v->total;
                        $w = $v->week;
                    }
                $tr.= '<tr class="show_week" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td>Chi phí hành chính</td>';
                $tr.= '<td>'.($tongtuan>0?"+":null).$this->lib->formatMoney($tongtuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getKhoanthu(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tong = 0;
            $w = 0;
            $tongtuan = 0;

            $banks = $assets_model->query('SELECT assets.week, assets_date, total, comment FROM assets, costs WHERE assets.costs=costs.costs_id AND costs.check_office = 1 AND money!=money_in AND money = 0 AND money_in > 0 AND (invoice_balance <= 0 OR invoice_balance IS NULL) AND (check_salary IS NULL OR check_salary <=0) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets.assets_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        
                        if($v->week != $w && $w >= ($tuan_truoc+1)){
                            $tr.= '<tr class="show_week" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td>Các khoản thu vào</td>';
                            $tr.= '<td>'.($tongtuan>0?"+":null).$this->lib->formatMoney($tongtuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan'.$v->week.'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';

                            $tongtuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan'.$v->week.'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                            $tr.= '<td>'.$v->comment.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tong +=$v->total;
                        $tongtuan +=$v->total;
                        $w = $v->week;
                    }
                $tr.= '<tr class="show_week" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td>Thu vào</td>';
                $tr.= '<td>'.($tongtuan>0?"+":null).$this->lib->formatMoney($tongtuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }
    public function getSale(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $banks = $assets_model->query('SELECT * FROM sale_report WHERE sale_type = 1 AND sale_date > '.strtotime($ketthuc_truoc).' AND sale_date <= '.strtotime($ketthuc).' ORDER BY sale_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Code</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        
                        if((int)date('W',$v->sale_date) != $w && $w >= ($tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->sale_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->sale_date).'</td>';
                            $tr.= '<td>'.$v->code.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->revenue_vat+$v->revenue).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney(($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                            $tongchiphituan = 0;
                            $tongloinhuantuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->sale_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->sale_date).'</td>';
                            $tr.= '<td>'.$v->code.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->revenue_vat+$v->revenue).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney(($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += ($v->revenue_vat+$v->revenue);
                        $tongchiphi += ($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongloinhuan += (($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongdoanhthutuan += ($v->revenue_vat+$v->revenue);
                        $tongchiphituan += ($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongloinhuantuan += (($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $w = (int)date('W',$v->sale_date);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td><td>'.$this->lib->formatMoney($tongchiphi).'</td><td>'.$this->lib->formatMoney($tongloinhuan).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND sale_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets_date ASC');
            
            $tongphikhac = 0;
            $tongphikhactuan = 0;
            $we = 0;

            if ($other_cost) {
                $tr .= '<table class="table_data"><thead><tr><th style="color: red; text-align: center" colspan="3">Chi phí khác</th></tr><tr><th>Ngày</th><th>Nội dung</th><th>Chi phí</th></tr></thead><tbody>';
                foreach ($other_cost as $v) {
                        
                        if ($v->check_invoice != 1) {
                            if($v->week != $we && $we >= ($tuan_truoc+1)){
                                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                                $tr.= '<td>Tuần '.$we.'</td>';
                                $tr.= '<td></td>';
                                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                                $tr.= '</tr>'; 

                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';

                                $tongphikhactuan = 0;
                            }
                            else{
                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                            }

                            $we = $v->week;

                            $tongphikhactuan += $v->total;
                            $tongphikhac += $v->total;
                        }
                        
                    }
                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                $tr.= '<td>Tuần '.$we.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                $tr.= '</tr>'; 


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongphikhac-round($tongphikhac*0.1)).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale_other").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getTrading(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $banks = $assets_model->query('SELECT * FROM sale_report WHERE sale_type = 2 AND sale_date > '.strtotime($ketthuc_truoc).' AND sale_date <= '.strtotime($ketthuc).' ORDER BY sale_date ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Code</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        
                        if((int)date('W',$v->sale_date) != $w && ($w >= $tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->sale_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->sale_date).'</td>';
                            $tr.= '<td>'.$v->code.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney(($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                            $tongchiphituan = 0;
                            $tongloinhuantuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->sale_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->sale_date).'</td>';
                            $tr.= '<td>'.$v->code.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney(($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1)).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += ($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat);
                        $tongchiphi += ($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongloinhuan += (($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongdoanhthutuan += ($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat);
                        $tongchiphituan += ($v->cost_vat+$v->cost+round($v->cost*0.1)+round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $tongloinhuantuan += (($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1)-round((($v->revenue_vat+$v->revenue+$v->other_revenue+$v->other_revenue_vat)-($v->cost_vat+$v->cost)-round($v->cost*0.1))*0.1));
                        $w = (int)date('W',$v->sale_date);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td><td>'.$this->lib->formatMoney($tongchiphi).'</td><td>'.$this->lib->formatMoney($tongloinhuan).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND trading_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets_date ASC');
            
            $tongphikhac = 0;
            $tongphikhactuan = 0;
            $we = 0;

            if ($other_cost) {
                $tr .= '<table class="table_data"><thead><tr><th style="color: red; text-align: center" colspan="3">Chi phí khác</th></tr><tr><th>Ngày</th><th>Nội dung</th><th>Chi phí</th></tr></thead><tbody>';
                foreach ($other_cost as $v) {
                        
                        if ($v->check_invoice != 1) {
                            if($v->week != $we && $we >= ($tuan_truoc+1)){
                                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                                $tr.= '<td>Tuần '.$we.'</td>';
                                $tr.= '<td></td>';
                                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                                $tr.= '</tr>'; 

                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';

                                $tongphikhactuan = 0;
                            }
                            else{
                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                            }

                            $we = $v->week;

                            $tongphikhactuan += $v->total;
                            $tongphikhac += $v->total;
                        }
                        
                    }
                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                $tr.= '<td>Tuần '.$we.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                $tr.= '</tr>'; 


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongphikhac-round($tongphikhac*0.1)).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale_other").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getAgent(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $agent_data = array();

            $agents = $assets_model->query('SELECT * FROM agent WHERE agent_date > '.strtotime($ketthuc_truoc).' AND agent_date <= '.strtotime($ketthuc).' ORDER BY agent_date ASC');

            $phidaily = 0;

            foreach ($agents as $agent) {
                $agent_data[$agent->code]['ngay'] = $agent->agent_date;
                $agent_data[$agent->code]['code'] = $agent->code;
                $agent_data[$agent->code]['doanhthu'] = $agent->total_offer;

                $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
                foreach ($phi_agent as $phi) {
                    $phidaily = $phi->money;
                }
                $agent_data[$agent->code]['doanhthu'] = isset($agent_data[$agent->code]['doanhthu'])?($agent_data[$agent->code]['doanhthu']+$phidaily):(0+$phidaily);
                
                $agent_data[$agent->code]['chiphi'] = round(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost))*1.1)+round(($agent->total_offer+$phidaily-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)*0.1);
                $agent_data[$agent->code]['loinhuan'] = $agent->total_offer+$phidaily-round(round(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost))*1.1)+round(($agent->total_offer+$phidaily-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)*0.1));

                $phidaily = 0;
            }

            $manifests = $assets_model->query('SELECT * FROM agent_manifest WHERE agent_manifest_date > '.strtotime($ketthuc_truoc).' AND agent_manifest_date <= '.strtotime($ketthuc).' ORDER BY agent_manifest_date ASC');

            foreach ($manifests as $agent) {
                $agent_data[$agent->code]['ngay'] = $agent->agent_manifest_date;
                $agent_data[$agent->code]['code'] = $agent->code;
                $agent_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
                $agent_data[$agent->code]['chiphi'] = round(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1)+round(($agent->revenue_vat+$agent->revenue-(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1))*0.1);
                $agent_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-round(round(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1)+round(($agent->revenue_vat+$agent->revenue-(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1))*0.1));
            }

            usort($agent_data, function ($item1, $item2) {
                if ($item1['ngay'] == $item2['ngay']) return 0;
                return $item1['ngay'] < $item2['ngay'] ? -1 : 1;
            });


            if($agent_data){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Code</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead><tbody>';
                foreach ($agent_data as $v) {
                        
                        if((int)date('W',$v['ngay']) != $w && $w >= ($tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v['ngay']).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v['ngay']).'</td>';
                            $tr.= '<td>'.$v['code'].'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['doanhthu']).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['chiphi']).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['loinhuan']).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                            $tongchiphituan = 0;
                            $tongloinhuantuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v['ngay']).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v['ngay']).'</td>';
                            $tr.= '<td>'.$v['code'].'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['doanhthu']).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['chiphi']).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['loinhuan']).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += $v['doanhthu'];
                        $tongchiphi += $v['chiphi'];
                        $tongloinhuan += $v['loinhuan'];
                        $tongdoanhthutuan += $v['doanhthu'];
                        $tongchiphituan += $v['chiphi'];
                        $tongloinhuantuan += $v['loinhuan'];
                        $w = (int)date('W',$v['ngay']);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td><td>'.$this->lib->formatMoney($tongchiphi).'</td><td>'.$this->lib->formatMoney($tongloinhuan).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND agent_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets_date ASC');
            
            $tongphikhac = 0;
            $tongphikhactuan = 0;
            $we = 0;

            if ($other_cost) {
                $tr .= '<table class="table_data"><thead><tr><th style="color: red; text-align: center" colspan="3">Chi phí khác</th></tr><tr><th>Ngày</th><th>Nội dung</th><th>Chi phí</th></tr></thead><tbody>';
                foreach ($other_cost as $v) {
                        
                        if ($v->check_invoice != 1) {
                            if($v->week != $we && $we >= ($tuan_truoc+1)){
                                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                                $tr.= '<td>Tuần '.$we.'</td>';
                                $tr.= '<td></td>';
                                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                                $tr.= '</tr>'; 

                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';

                                $tongphikhactuan = 0;
                            }
                            else{
                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                            }

                            $we = $v->week;

                            $tongphikhactuan += $v->total;
                            $tongphikhac += $v->total;
                        }
                        
                    }
                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                $tr.= '<td>Tuần '.$we.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                $tr.= '</tr>'; 


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongphikhac-round($tongphikhac*0.1)).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale_other").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getTCMT(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $banks = $assets_model->query('SELECT * FROM invoice WHERE day_invoice > '.strtotime($ketthuc_truoc).' AND day_invoice <= '.strtotime($ketthuc).' ORDER BY day_invoice ASC');
            if($banks){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Số HD</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead><tbody>';
                foreach ($banks as $v) {
                        
                        if((int)date('W',$v->day_invoice) != $w && ($w >= $tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->day_invoice).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->day_invoice).'</td>';
                            $tr.= '<td>'.$v->invoice_number.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->receive).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->pay1+$v->pay2+$v->pay3).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->receive-($v->pay1+$v->pay2+$v->pay3)).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                            $tongchiphituan = 0;
                            $tongloinhuantuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->day_invoice).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->day_invoice).'</td>';
                            $tr.= '<td>'.$v->invoice_number.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->receive).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->pay1+$v->pay2+$v->pay3).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->receive-($v->pay1+$v->pay2+$v->pay3)).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += $v->receive;
                        $tongchiphi += ($v->pay1+$v->pay2+$v->pay3);
                        $tongloinhuan += ($v->receive-($v->pay1+$v->pay2+$v->pay3));
                        $tongdoanhthutuan += $v->receive;
                        $tongchiphituan += ($v->pay1+$v->pay2+$v->pay3);
                        $tongloinhuantuan += ($v->receive-($v->pay1+$v->pay2+$v->pay3));
                        $w = (int)date('W',$v->day_invoice);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td><td>'.$this->lib->formatMoney($tongchiphi).'</td><td>'.$this->lib->formatMoney($tongloinhuan).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND tcmt_estimate=1 AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets_date ASC');
            
            $tongphikhac = 0;
            $tongphikhactuan = 0;
            $we = 0;

            if ($other_cost) {
                $tr .= '<table class="table_data"><thead><tr><th style="color: red; text-align: center" colspan="3">Chi phí khác</th></tr><tr><th>Ngày</th><th>Nội dung</th><th>Chi phí</th></tr></thead><tbody>';
                foreach ($other_cost as $v) {
                        
                            if($v->week != $we && $we >= ($tuan_truoc+1)){
                                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                                $tr.= '<td>Tuần '.$we.'</td>';
                                $tr.= '<td></td>';
                                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                                $tr.= '</tr>'; 

                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';

                                $tongphikhactuan = 0;
                            }
                            else{
                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                            }

                            $we = $v->week;

                            $tongphikhactuan += $v->total;
                            $tongphikhac += $v->total;
                        
                        
                    }
                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                $tr.= '<td>Tuần '.$we.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                $tr.= '</tr>'; 


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongphikhac).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale_other").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getFinancial(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $assets_model = $this->model->get('assetsModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $financial_data = array();

            $sales = $assets_model->query('SELECT * FROM sale_report WHERE sale_date > '.strtotime($ketthuc_truoc).' AND sale_date <= '.strtotime($ketthuc).' ORDER BY sale_date ASC');
            $agents = $assets_model->query('SELECT * FROM agent WHERE agent_date > '.strtotime($ketthuc_truoc).' AND agent_date <= '.strtotime($ketthuc).' ORDER BY agent_date ASC');
            $manifests = $assets_model->query('SELECT * FROM agent_manifest WHERE agent_manifest_date > '.strtotime($ketthuc_truoc).' AND agent_manifest_date <= '.strtotime($ketthuc).' ORDER BY agent_manifest_date ASC');

            foreach ($sales as $sale) {
                $financial_data[$sale->code]['ngay'] = $sale->sale_date;
                $financial_data[$sale->code]['code'] = $sale->code;
                $financial_data[$sale->code]['doanhthu'] = round(($sale->cost*0.1)+((($sale->revenue_vat+$sale->revenue)-($sale->cost_vat+($sale->cost*1.1)))*0.1));
            }

            $phidaily = 0;

            foreach ($agents as $agent) {
                $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
                foreach ($phi_agent as $phi) {
                    $phidaily = $phi->money;
                }
                $phidaily = 0;

                $financial_data[$agent->code]['ngay'] = $agent->agent_date;
                $financial_data[$agent->code]['code'] = $agent->code;
                $financial_data[$agent->code]['doanhthu'] = round((($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost))*0.1)+(($agent->total_offer+$phidaily-(($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)))*1.1)*0.1));
            }


            foreach ($manifests as $agent) {
                $financial_data[$agent->code]['ngay'] = $agent->agent_manifest_date;
                $financial_data[$agent->code]['code'] = $agent->code;
                $financial_data[$agent->code]['doanhthu'] = round((($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*0.1)+(($agent->revenue_vat+$agent->revenue-(($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)*1.1))*0.1));
            }

            usort($financial_data, function ($item1, $item2) {
                if ($item1['ngay'] == $item2['ngay']) return 0;
                return $item1['ngay'] < $item2['ngay'] ? -1 : 1;
            });

            
            if($financial_data){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Code</th><th>Thu</th></tr></thead><tbody>';
                foreach ($financial_data as $v) {
                        
                        if((int)date('W',$v['ngay']) != $w && ($w >= $tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v['ngay']).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v['ngay']).'</td>';
                            $tr.= '<td>'.$v['code'].'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['doanhthu']).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v['ngay']).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v['ngay']).'</td>';
                            $tr.= '<td>'.$v['code'].'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v['doanhthu']).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += $v['doanhthu'];
                        $tongdoanhthutuan += $v['doanhthu'];
                        $w = (int)date('W',$v['ngay']);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND assets.total != 0 AND (sale_estimate=1 OR trading_estimate=1 OR agent_estimate=1) AND ( (assets.assets_date > '.strtotime($ketthuc_truoc).' AND assets.assets_date <= '.strtotime($ketthuc).')  ) ORDER BY assets_date ASC');
            
            $tongphikhac = 0;
            $tongphikhactuan = 0;
            $we = 0;

            if ($other_cost) {
                $tr .= '<table class="table_data"><thead><tr><th style="color: red; text-align: center" colspan="3">Chi phí khác</th></tr><tr><th>Ngày</th><th>Nội dung</th><th>Chi phí</th></tr></thead><tbody>';
                foreach ($other_cost as $v) {
                        
                            if($v->week != $we && $we >= ($tuan_truoc+1)){
                                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                                $tr.= '<td>Tuần '.$we.'</td>';
                                $tr.= '<td></td>';
                                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                                $tr.= '</tr>'; 

                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';

                                $tongphikhactuan = 0;
                            }
                            else{
                                $tr.= '<tr style="display:none" class="tuan_sale_other'.$v->week.'">';
                                $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->assets_date).'</td>';
                                $tr.= '<td>'.$v->comment.'</td>';
                                $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                                $tr.= '</tr>';
                            }

                            $we = $v->week;

                            $tongphikhactuan += $v->total;
                            $tongphikhac += $v->total;
                            if ($v->check_invoice != 1) {
                                $tongphikhac -= round($v->total*0.1);
                            }
                        
                    }
                $tr.= '<tr class="show_week_sale_other" data="'.$we.'" style="color:red">';
                $tr.= '<td>Tuần '.$we.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongphikhactuan).'</td>';
                $tr.= '</tr>'; 


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongphikhac).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale_other").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale_other"+tuan).hide();
                            });
                        </script>';
            }
            

            echo $tr;
        }
    }

    public function getLopxe(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $tuan_truoc = isset($_POST['tuantruoc']) ? $_POST['tuantruoc'] : null;
            $nam_truoc = isset($_POST['namtruoc']) ? $_POST['namtruoc'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;

            $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
            $batdau_truoc = $mang_truoc[0];
            $ketthuc_truoc = $mang_truoc[1];

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $order_tire_model = $this->model->get('ordertireModel');
            $tire_import_model = $this->model->get('tireimportModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tr = "";
            $tongdoanhthu = 0;
            $tongchiphi = 0;
            $tongloinhuan = 0;
            $w = 0;
            $tongdoanhthutuan = 0;
            $tongchiphituan = 0;
            $tongloinhuantuan = 0;

            $tire_imports = $tire_import_model->getAllTire();
            $tire_prices = array();
            $count = array();
            foreach ($tire_imports as $tire) {
                if (isset($tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern])) {
                    $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+1;
                    $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern]+$tire->tire_price;
                }
                else{
                    $count[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = 1;
                    $tire_prices[$tire->tire_brand][$tire->tire_size][$tire->tire_pattern] = $tire->tire_price;
                }
            }

            $odata = array(
                'where' => 'order_tire_status=1 AND delivery_date > '.strtotime($ketthuc_truoc).' AND delivery_date <= '.strtotime($ketthuc),
            );

            $orders = $order_tire_model->getAllTire($odata);


            if($orders){
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>Số ĐH</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead><tbody>';
                foreach ($orders as $v) {
                    $chiphi = $v->order_cost;
                    $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$v->order_tire_id));
                    foreach ($order_tire_lists as $l) {
                        $gia = isset($tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$tire_prices[$l->tire_brand][$l->tire_size][$l->tire_pattern]:0;
                        $sl = isset($count[$l->tire_brand][$l->tire_size][$l->tire_pattern])?$count[$l->tire_brand][$l->tire_size][$l->tire_pattern]:1;
                        $chiphi += $l->tire_number*($gia/$sl);
                    }    
                        if((int)date('W',$v->delivery_date) != $w && ($w >= $tuan_truoc+1)){
                            $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                            $tr.= '<td>Tuần '.$w.'</td>';
                            $tr.= '<td></td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                            $tr.= '</tr>'; 

                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->delivery_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->delivery_date).'</td>';
                            $tr.= '<td>'.$v->order_number.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($chiphi).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total-$chiphi).'</td>';
                            $tr.= '</tr>';

                            $tongdoanhthutuan = 0;
                            $tongchiphituan = 0;
                            $tongloinhuantuan = 0;
                        }
                        else{
                            $tr.= '<tr style="display:none" class="tuan_sale'.(int)date('W',$v->delivery_date).'">';
                            $tr.= '<td>'.$this->lib->hien_thi_ngay_thang($v->delivery_date).'</td>';
                            $tr.= '<td>'.$v->order_number.'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($chiphi).'</td>';
                            $tr.= '<td>'.$this->lib->formatMoney($v->total-$chiphi).'</td>';
                            $tr.= '</tr>';
                        }
                        
                        $tongdoanhthu += $v->total;
                        $tongchiphi += $chiphi;
                        $tongloinhuan += $v->total-$chiphi;
                        $tongdoanhthutuan += $v->total;
                        $tongchiphituan += $chiphi;
                        $tongloinhuantuan += $v->total-$chiphi;
                        $w = (int)date('W',$v->delivery_date);
                    }
                $tr.= '<tr class="show_week_sale" data="'.$w.'" style="color:red">';
                $tr.= '<td>Tuần '.$w.'</td>';
                $tr.= '<td></td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongdoanhthutuan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongchiphituan).'</td>';
                $tr.= '<td>'.$this->lib->formatMoney($tongloinhuantuan).'</td>';
                $tr.= '</tr>';


                $tr.= '<tfoot><tr style="color:rgb(0, 90, 255)"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tongdoanhthu).'</td><td>'.$this->lib->formatMoney($tongchiphi).'</td><td>'.$this->lib->formatMoney($tongloinhuan).'</td></tr></tfoot>';
                $tr.= "</tbody></table>";

                $tr.= '<script type="text/javascript">
                            $(".show_week_sale").toggle(function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).show();
                            },function(){
                                var tuan = $(this).attr("data");
                                $(".tuan_sale"+tuan).hide();
                            });
                        </script>';
            }

            

            echo $tr;
        }
    }

    public function getAnalytics() {

        $tuan_truoc = $this->registry->router->param_id;
        $tuan = $this->registry->router->page;
        $nam_truoc = $this->registry->router->order_by;
        $nam_report = $this->registry->router->order;
        
        $number = $tuan-$tuan_truoc;
        $new_batdau_truoc = array();
        $new_ketthuc_truoc = array();

        $sale_model = $this->model->get('salereportModel');
        $agent_model = $this->model->get('agentModel');
        $manifest_model = $this->model->get('agentmanifestModel');
        $invoice_model = $this->model->get('invoiceModel');
        
        $assets_model = $this->model->get('assetsModel');

        $costs_model = $this->model->get('costsModel');
        $advance_model = $this->model->get('advanceModel');
        $payable_model = $this->model->get('payableModel');

        $min_data = array();
        for ($i=1; $i <= $number; $i++) { 
            $new_mang_truoc = $this->getStartAndEndDate(($tuan_truoc+$i),$nam_truoc);
            $new_batdau_truoc[$tuan_truoc+$i] = $new_mang_truoc[0];
            $new_ketthuc_truoc[$tuan_truoc+$i] = $new_mang_truoc[1];

            $ajoin = array('table'=>'fixed_asset_buy','where'=>'assets.fixed_asset_buy = fixed_asset_buy.fixed_asset_buy_id');
            $adata = array(
                'where' => 'total != 0 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )',
            );

            $asset_buys = $assets_model->getAllAssets($adata,$ajoin);

            $mua_ts = array();
            foreach ($asset_buys as $asset_buy) {
                $mua_ts[$i] = isset($mua_ts[$i])?$mua_ts[$i]+$asset_buy->fixed_asset_buy_money:$asset_buy->fixed_asset_buy_money;
            }
            

            $cjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
            $cdata = array(
                'where' => 'costs.check_office = 1 AND (money!=money_in OR money_in IS NULL) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )',
            );

            $costs = $assets_model->getAllAssets($cdata,$cjoin);

            $hanhchinh = array();

            foreach ($costs as $cost) {
                $hanhchinh[$i] = isset($hanhchinh[$i])?$hanhchinh[$i]+$cost->total:$cost->total;
                if ($cost->staff_cost > 0) {
                    $hanhchinh[$i] = isset($hanhchinh[$i])?$hanhchinh[$i] + $cost->staff_cost:$cost->staff_cost;
                }
            }

            $max_data = array();

            $sdata = array(
                'where' => 'sale_type = 1 AND sale_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND sale_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );

            $sales = $sale_model->getAllSale($sdata);

            $sale_data = array(
                'doanhthu' => array(),
                'chiphi' => array(),
                'kvat' => array(),
                'invoice' => array(),
            );
            foreach ($sales as $sale) {
                $sale_data['doanhthu'][$i] = isset($sale_data['doanhthu'][$i])?($sale_data['doanhthu'][$i]+$sale->revenue+$sale->revenue_vat):($sale->revenue+$sale->revenue_vat);
                $sale_data['chiphi'][$i] = isset($sale_data['chiphi'][$i])?($sale_data['chiphi'][$i]+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
                $sale_data['kvat'][$i] = isset($sale_data['kvat'][$i])?($sale_data['kvat'][$i]+$sale->cost):(0+$sale->cost);
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND sale_estimate=1 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )');
            foreach ($other_cost as $cost) {
                if ($cost->check_invoice == 1) {
                    $sale_data['invoice'][$i] = isset($sale_data['invoice'][$i])?($sale_data['invoice'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
                elseif ($cost->check_invoice != 1) {
                    $sale_data['chiphi'][$i] = isset($sale_data['chiphi'][$i])?($sale_data['chiphi'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
            }


            $tdata = array(
                'where' => 'sale_type = 2 AND sale_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND sale_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );

            $sales = $sale_model->getAllSale($tdata);

            $trading_data = array(
                'doanhthu' => array(),
                'chiphi' => array(),
                'kvat' => array(),
                'invoice' => array(),
            );
            foreach ($sales as $sale) {
                $trading_data['doanhthu'][$i] = isset($trading_data['doanhthu'][$i])?($trading_data['doanhthu'][$i]+$sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat):($sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat);
                $trading_data['chiphi'][$i] = isset($trading_data['chiphi'][$i])?($trading_data['chiphi'][$i]+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
                $trading_data['kvat'][$i] = isset($trading_data['kvat'][$i])?($trading_data['kvat'][$i]+$sale->cost):(0+$sale->cost);
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND trading_estimate=1 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )');
            foreach ($other_cost as $cost) {
                
                if ($cost->check_invoice == 1) {
                    $trading_data['invoice'][$i] = isset($trading_data['invoice'][$i])?($trading_data['invoice'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
                elseif ($cost->check_invoice != 1) {
                    $trading_data['chiphi'][$i] = isset($trading_data['chiphi'][$i])?($trading_data['chiphi'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
            }


            $adata = array(
                'where' => 'agent_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND agent_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );

            $agents = $agent_model->getAllAgent($adata);

            $agent_data = array(
                'doanhthu' => array(),
                'chiphi' => array(),
                'kvat' => array(),
                'invoice' => array(),
            );

            $phidaily = 0;

            foreach ($agents as $agent) {
                $agent_data['doanhthu'][$i] = isset($agent_data['doanhthu'][$i])?($agent_data['doanhthu'][$i]+$agent->total_offer):(0+$agent->total_offer);
                $agent_data['chiphi'][$i] = isset($agent_data['chiphi'][$i])?($agent_data['chiphi'][$i]+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
                $agent_data['kvat'][$i] = isset($agent_data['kvat'][$i])?($agent_data['kvat'][$i]+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));

                $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
                foreach ($phi_agent as $phi) {
                    $phidaily = $phi->money;
                }
                $agent_data['doanhthu'][$i] = isset($agent_data['doanhthu'][$i])?($agent_data['doanhthu'][$i]+$phidaily):(0+$phidaily);
                
                $phidaily = 0;

                $max_data[$agent->code]['code'] = $agent->code;
                $max_data[$agent->code]['doanhthu'] = $agent->total_offer;
                $max_data[$agent->code]['chiphi'] = $agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost);
                $max_data[$agent->code]['loinhuan'] = $agent->total_offer-($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
            }

            $data_manifest = array(
                'where' => 'agent_manifest_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND agent_manifest_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );

            $manifests = $manifest_model->getAllAgent($data_manifest);

            foreach ($manifests as $agent) {
                $agent_data['doanhthu'][$i] = isset($agent_data['doanhthu'][$i])?($agent_data['doanhthu'][$i]+$agent->revenue_vat+$agent->revenue):(0+$agent->revenue_vat+$agent->revenue);
                $agent_data['chiphi'][$i] = isset($agent_data['chiphi'][$i])?($agent_data['chiphi'][$i]+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));
                $agent_data['kvat'][$i] = isset($agent_data['kvat'][$i])?($agent_data['kvat'][$i]+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));

                $max_data[$agent->code]['code'] = $agent->code;
                $max_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
                $max_data[$agent->code]['chiphi'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
                $max_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
        
            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND agent_estimate=1 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )');
            foreach ($other_cost as $cost) {
                
                if ($cost->check_invoice == 1) {
                    $agent_data['invoice'][$i] = isset($agent_data['invoice'][$i])?($agent_data['invoice'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
                elseif ($cost->check_invoice != 1) {
                    $agent_data['chiphi'][$i] = isset($agent_data['chiphi'][$i])?($agent_data['chiphi'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
            }


            $idata = array(
                'where' => 'day_invoice >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND day_invoice <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]),
            );

            $invoices = $invoice_model->getAllInvoice($idata);

            $invoice_data = array(
                'doanhthu' => array(),
                'chiphi' => array(),
                'kvat' => array(),
                'invoice' => array(),
            );
            foreach ($invoices as $invoice) {
                $invoice_data['doanhthu'][$i] = isset($invoice_data['doanhthu'][$i])?($invoice_data['doanhthu'][$i]+$invoice->receive):(0+$invoice->receive);
                $invoice_data['chiphi'][$i] = isset($invoice_data['chiphi'][$i])?($invoice_data['chiphi'][$i]+($invoice->pay1+$invoice->pay2+$invoice->pay3)):(0+($invoice->pay1+$invoice->pay2+$invoice->pay3));
                $invoice_data['kvat'][$i] = isset($invoice_data['kvat'][$i])?($invoice_data['kvat'][$i]+$invoice->pay1+$invoice->pay2):(0+$invoice->pay1+$invoice->pay2);

                $max_data[$invoice->invoice_number]['code'] = $invoice->invoice_number;
                $max_data[$invoice->invoice_number]['doanhthu'] = $invoice->receive;
                $max_data[$invoice->invoice_number]['chiphi'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
                $max_data[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);

            }

            $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND tcmt_estimate=1 AND ( (assets.assets_date >= '.strtotime($new_batdau_truoc[$tuan_truoc+$i]).' AND assets.assets_date <= '.strtotime($new_ketthuc_truoc[$tuan_truoc+$i]).') )');
            foreach ($other_cost as $cost) {
                
                if ($cost->check_invoice == 1) {
                    $invoice_data['invoice'][$i] = isset($invoice_data['invoice'][$i])?($invoice_data['invoice'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
                elseif ($cost->check_invoice != 1) {
                    $invoice_data['chiphi'][$i] = isset($invoice_data['chiphi'][$i])?($invoice_data['chiphi'][$i]+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
                }
            }

        }

        
        $mang_truoc = $this->getStartAndEndDate($tuan_truoc,$nam_truoc);
        $batdau_truoc = $mang_truoc[0];
        $ketthuc_truoc = $mang_truoc[1];

        $mang = $this->getStartAndEndDate($tuan,$nam_report);
        $batdau = $mang[0];
        $ketthuc = $mang[1];

        


        $invoice_data = $invoice_data;
        $agent_data = $agent_data;
        $sale_data = $sale_data;
        $trading_data = $trading_data;
        $hanhchinh = $hanhchinh;
        $mua_ts = $mua_ts;


        $ajoin = array('table'=>'fixed_asset_buy','where'=>'assets.fixed_asset_buy = fixed_asset_buy.fixed_asset_buy_id');
        $adata = array(
            'where' => 'total != 0 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.') )',
        );

        $asset_buys = $assets_model->getAllAssets($adata,$ajoin);

        $mua_ts = 0;
        foreach ($asset_buys as $asset_buy) {
            $mua_ts += $asset_buy->fixed_asset_buy_money;
        }
        

        $cjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
        $cdata = array(
            'where' => 'costs.check_office = 1 AND (money!=money_in OR money_in IS NULL) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.'))',
        );

        $costs = $assets_model->getAllAssets($cdata,$cjoin);

        $hanhchinh = 0;

        foreach ($costs as $cost) {
            $hanhchinh += $cost->total;
            if ($cost->staff_cost > 0) {
                $hanhchinh = $hanhchinh + $cost->staff_cost;
            }
        }

        $max_data = array();

        $sdata = array(
            'where' => 'sale_type = 1 AND sale_date > '.strtotime($batdau_truoc).' AND sale_date <= '.strtotime($ketthuc_truoc),
        );

        $sales = $sale_model->getAllSale($sdata);

        $sale_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $sale_data['doanhthu'] = isset($sale_data['doanhthu'])?($sale_data['doanhthu']+$sale->revenue+$sale->revenue_vat):($sale->revenue+$sale->revenue_vat);
            $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $sale_data['kvat'] = isset($sale_data['kvat'])?($sale_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND sale_estimate=1 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.') )');
        foreach ($other_cost as $cost) {
            if ($cost->check_invoice == 1) {
                $sale_data['invoice'] = isset($sale_data['invoice'])?($sale_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $tdata = array(
            'where' => 'sale_type = 2 AND sale_date > '.strtotime($batdau_truoc).' AND sale_date <= '.strtotime($ketthuc_truoc),
        );

        $sales = $sale_model->getAllSale($tdata);

        $trading_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $trading_data['doanhthu'] = isset($trading_data['doanhthu'])?($trading_data['doanhthu']+$sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat):($sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat);
            $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $trading_data['kvat'] = isset($trading_data['kvat'])?($trading_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND trading_estimate=1 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.') )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $trading_data['invoice'] = isset($trading_data['invoice'])?($trading_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $adata = array(
            'where' => 'agent_date > '.strtotime($batdau_truoc).' AND agent_date <= '.strtotime($ketthuc_truoc),
        );

        $agents = $agent_model->getAllAgent($adata);

        $agent_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );

        $phidaily = 0;

        foreach ($agents as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->total_offer):(0+$agent->total_offer);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));

            $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
            foreach ($phi_agent as $phi) {
                $phidaily = $phi->money;
            }
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$phidaily):(0+$phidaily);
            
            $phidaily = 0;

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->total_offer;
            $max_data[$agent->code]['chiphi'] = $agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->total_offer-($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
        }

        $data_manifest = array(
            'where' => 'agent_manifest_date > '.strtotime($batdau_truoc).' AND agent_manifest_date <= '.strtotime($ketthuc_truoc),
        );

        $manifests = $manifest_model->getAllAgent($data_manifest);

        foreach ($manifests as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->revenue_vat+$agent->revenue):(0+$agent->revenue_vat+$agent->revenue);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
            $max_data[$agent->code]['chiphi'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
    
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND agent_estimate=1 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.')  )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $agent_data['invoice'] = isset($agent_data['invoice'])?($agent_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $idata = array(
            'where' => 'day_invoice > '.strtotime($batdau_truoc).' AND day_invoice <= '.strtotime($ketthuc_truoc),
        );

        $invoices = $invoice_model->getAllInvoice($idata);

        $invoice_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($invoices as $invoice) {
            $invoice_data['doanhthu'] = isset($invoice_data['doanhthu'])?($invoice_data['doanhthu']+$invoice->receive):(0+$invoice->receive);
            $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+($invoice->pay1+$invoice->pay2+$invoice->pay3)):(0+($invoice->pay1+$invoice->pay2+$invoice->pay3));
            $invoice_data['kvat'] = isset($invoice_data['kvat'])?($invoice_data['kvat']+$invoice->pay1+$invoice->pay2):(0+$invoice->pay1+$invoice->pay2);

            $max_data[$invoice->invoice_number]['code'] = $invoice->invoice_number;
            $max_data[$invoice->invoice_number]['doanhthu'] = $invoice->receive;
            $max_data[$invoice->invoice_number]['chiphi'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
            $max_data[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);

        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND tcmt_estimate=1 AND ( (assets.week = '.$tuan_truoc.' AND assets.year = '.$nam_truoc.') )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $invoice_data['invoice'] = isset($invoice_data['invoice'])?($invoice_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }

        $invoice_data_truoc = $invoice_data;
        $agent_data_truoc = $agent_data;
        $sale_data_truoc = $sale_data;
        $trading_data_truoc = $trading_data;
        $hanhchinh_truoc = $hanhchinh;
        $mua_ts_truoc = $mua_ts;

        $ajoin = array('table'=>'fixed_asset_buy','where'=>'assets.fixed_asset_buy = fixed_asset_buy.fixed_asset_buy_id');
        $adata = array(
            'where' => 'total != 0 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.') )',
        );

        $asset_buys = $assets_model->getAllAssets($adata,$ajoin);

        $mua_ts = 0;
        foreach ($asset_buys as $asset_buy) {
            $mua_ts += $asset_buy->fixed_asset_buy_money;
        }
        

        $cjoin = array('table'=>'costs','where'=>'assets.costs = costs.costs_id');
        $cdata = array(
            'where' => 'costs.check_office = 1 AND (money!=money_in OR money_in IS NULL) AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND (staff_cost <= 0 OR staff_cost IS NULL)  AND total != 0 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.'))',
        );

        $costs = $assets_model->getAllAssets($cdata,$cjoin);

        $hanhchinh = 0;

        foreach ($costs as $cost) {
            $hanhchinh += $cost->total;
            if ($cost->staff_cost > 0) {
                $hanhchinh = $hanhchinh + $cost->staff_cost;
            }
        }

        $max_data = array();

        $sdata = array(
            'where' => 'sale_type = 1 AND sale_date > '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($sdata);

        $sale_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $sale_data['doanhthu'] = isset($sale_data['doanhthu'])?($sale_data['doanhthu']+$sale->revenue+$sale->revenue_vat):($sale->revenue+$sale->revenue_vat);
            $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $sale_data['kvat'] = isset($sale_data['kvat'])?($sale_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND sale_estimate=1 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.') )');
        foreach ($other_cost as $cost) {
            if ($cost->check_invoice == 1) {
                $sale_data['invoice'] = isset($sale_data['invoice'])?($sale_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $sale_data['chiphi'] = isset($sale_data['chiphi'])?($sale_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $tdata = array(
            'where' => 'sale_type = 2 AND sale_date > '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc),
        );

        $sales = $sale_model->getAllSale($tdata);

        $trading_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($sales as $sale) {
            $trading_data['doanhthu'] = isset($trading_data['doanhthu'])?($trading_data['doanhthu']+$sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat):($sale->revenue+$sale->revenue_vat+$sale->other_revenue+$sale->other_revenue_vat);
            $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+$sale->cost+$sale->cost_vat):($sale->cost+$sale->cost_vat);
            $trading_data['kvat'] = isset($trading_data['kvat'])?($trading_data['kvat']+$sale->cost):(0+$sale->cost);
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND trading_estimate=1 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.') )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $trading_data['invoice'] = isset($trading_data['invoice'])?($trading_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $trading_data['chiphi'] = isset($trading_data['chiphi'])?($trading_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $adata = array(
            'where' => 'agent_date > '.strtotime($batdau).' AND agent_date <= '.strtotime($ketthuc),
        );

        $agents = $agent_model->getAllAgent($adata);

        $agent_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );

        $phidaily = 0;

        foreach ($agents as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->total_offer):(0+$agent->total_offer);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost)):(0+$agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));

            $phi_agent = $assets_model->queryAssets('SELECT * FROM obtain WHERE money=1100000 AND customer='.$agent->customer.' AND agent='.$agent->agent_id);
            foreach ($phi_agent as $phi) {
                $phidaily = $phi->money;
            }
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$phidaily):(0+$phidaily);
            
            $phidaily = 0;

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->total_offer;
            $max_data[$agent->code]['chiphi'] = $agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->total_offer-($agent->total_cost+$agent->bill_cost-(($agent->cost_17*160000)+($agent->cost_18*40000)+$agent->document_cost+$agent->pay_cost));
        }

        $data_manifest = array(
            'where' => 'agent_manifest_date > '.strtotime($batdau).' AND agent_manifest_date <= '.strtotime($ketthuc),
        );

        $manifests = $manifest_model->getAllAgent($data_manifest);

        foreach ($manifests as $agent) {
            $agent_data['doanhthu'] = isset($agent_data['doanhthu'])?($agent_data['doanhthu']+$agent->revenue_vat+$agent->revenue):(0+$agent->revenue_vat+$agent->revenue);
            $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));
            $agent_data['kvat'] = isset($agent_data['kvat'])?($agent_data['kvat']+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost)):(0+($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost));

            $max_data[$agent->code]['code'] = $agent->code;
            $max_data[$agent->code]['doanhthu'] = $agent->revenue_vat+$agent->revenue;
            $max_data[$agent->code]['chiphi'] = ($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
            $max_data[$agent->code]['loinhuan'] = $agent->revenue_vat+$agent->revenue-($agent->cost_sg+$agent->cost_cm+$agent->driver_cost+$agent->commission_cost+$agent->other_cost+$agent->other_vendor_cost);
    
        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND agent_estimate=1 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.')  )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $agent_data['invoice'] = isset($agent_data['invoice'])?($agent_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $agent_data['chiphi'] = isset($agent_data['chiphi'])?($agent_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }


        $idata = array(
            'where' => 'day_invoice > '.strtotime($batdau).' AND day_invoice <= '.strtotime($ketthuc),
        );

        $invoices = $invoice_model->getAllInvoice($idata);

        $invoice_data = array(
            'doanhthu' => 0,
            'chiphi' => 0,
            'kvat' => 0,
            'invoice' => 0,
        );
        foreach ($invoices as $invoice) {
            $invoice_data['doanhthu'] = isset($invoice_data['doanhthu'])?($invoice_data['doanhthu']+$invoice->receive):(0+$invoice->receive);
            $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+($invoice->pay1+$invoice->pay2+$invoice->pay3)):(0+($invoice->pay1+$invoice->pay2+$invoice->pay3));
            $invoice_data['kvat'] = isset($invoice_data['kvat'])?($invoice_data['kvat']+$invoice->pay1+$invoice->pay2):(0+$invoice->pay1+$invoice->pay2);

            $max_data[$invoice->invoice_number]['code'] = $invoice->invoice_number;
            $max_data[$invoice->invoice_number]['doanhthu'] = $invoice->receive;
            $max_data[$invoice->invoice_number]['chiphi'] = ($invoice->pay1+$invoice->pay2+$invoice->pay3);
            $max_data[$invoice->invoice_number]['loinhuan'] = $invoice->receive-($invoice->pay1+$invoice->pay2+$invoice->pay3);

        }

        $other_cost = $assets_model->queryAssets('SELECT * FROM assets, costs WHERE assets.costs=costs.costs_id AND tcmt_estimate=1 AND ( (assets.week = '.$tuan.' AND assets.year = '.$nam_report.') )');
        foreach ($other_cost as $cost) {
            
            if ($cost->check_invoice == 1) {
                $invoice_data['invoice'] = isset($invoice_data['invoice'])?($invoice_data['invoice']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
            elseif ($cost->check_invoice != 1) {
                $invoice_data['chiphi'] = isset($invoice_data['chiphi'])?($invoice_data['chiphi']+str_replace('-', "", $cost->total)):(0+str_replace('-', "", $cost->total));
            }
        }

        $invoice_data_sau = $invoice_data;
        $agent_data_sau = $agent_data;
        $sale_data_sau = $sale_data;
        $trading_data_sau = $trading_data;
        $hanhchinh_sau = $hanhchinh;
        $mua_ts_sau = $mua_ts;


        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        

        $asset_data = array();
        $asset_data_truoc = array();
        $new_asset_data_truoc = array();

        foreach ($banks as $bank) {
           
            // $where = array(
            //     'where' => 'assets_date <= '.strtotime($ketthuc).' AND bank = '.$bank->bank_id,
            // );
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') )  AND bank = '.$bank->bank_id,
            );
            $assets = $assets_model->getAllAssets($where);
            
            
            foreach ($assets as $asset) {
                $asset_data[$asset->bank] = isset($asset_data[$asset->bank])?($asset_data[$asset->bank]+$asset->total):0+$asset->total;
            }

            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND bank = '.$bank->bank_id,
            );
            $assets_truoc = $assets_model->getAllAssets($where_truoc);
            
            
            foreach ($assets_truoc as $asset) {
                $asset_data_truoc[$asset->bank] = isset($asset_data_truoc[$asset->bank])?($asset_data_truoc[$asset->bank]+$asset->total):0+$asset->total;
            }

            for ($i=1; $i <= $number; $i++) { 
                $new_where_truoc = array(
                    'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND bank = '.$bank->bank_id,
                );
                $new_assets_truoc = $assets_model->getAllAssets($new_where_truoc);
                
                
                foreach ($new_assets_truoc as $asset) {
                    $new_asset_data_truoc[$tuan_truoc+$i][$asset->bank] = isset($new_asset_data_truoc[$tuan_truoc+$i][$asset->bank])?($new_asset_data_truoc[$tuan_truoc+$i][$asset->bank]+$asset->total):0+$asset->total;
                }
            }


        }

        $customer_model = $this->model->get('customerModel');
        $obtain_model = $this->model->get('obtainModel');

        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $customers_data = $customer_model->getAllCustomer($where);
        
        $where = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $customers = $customer_model->getAllCustomer($where);


        $obtains_data = array();
        foreach ($customers as $customer) {
         
                $where = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND customer = '.$customer->customer_id,
                );
                $obtains = $obtain_model->getAllObtain($where);


                foreach ($obtains as $obtain) {
                    $obtains_data['money'][$obtain->customer] = isset($obtains_data['money'][$obtain->customer])?($obtains_data['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                   
                }
        }

        $where_truoc = array(
            'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $customers_truoc = $customer_model->getAllCustomer($where_truoc);


        $obtains_data_truoc = array();
        foreach ($customers_truoc as $customer) {
         
                $where_truoc = array(
                    'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND customer = '.$customer->customer_id,
                );
                $obtains_truoc = $obtain_model->getAllObtain($where_truoc);


                foreach ($obtains_truoc as $obtain) {
                    $obtains_data_truoc['money'][$obtain->customer] = isset($obtains_data_truoc['money'][$obtain->customer])?($obtains_data_truoc['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                }
        }

        $new_obtains_data_truoc = array();
        $new_customers_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'customer_id in (SELECT customer FROM obtain WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_customers_truoc = $customer_model->getAllCustomer($new_where_truoc);

            foreach ($new_customers_truoc as $customer) {
             
                    $new_where_truoc = array(
                        'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND customer = '.$customer->customer_id,
                    );
                    $new_obtains_truoc = $obtain_model->getAllObtain($new_where_truoc);


                    foreach ($new_obtains_truoc as $obtain) {
                        $new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer] = isset($new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer])?($new_obtains_data_truoc[$tuan_truoc+$i]['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                    }
            }
        }
        
        
        $owe_model = $this->model->get('oweModel');
        $vendor_model = $this->model->get('shipmentvendorModel');

        $where = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $shipvendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $vendors_ops_data = $vendor_model->getAllVendor($where);

        $where = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $vendors_ops = $vendor_model->getAllVendor($where);
        
         $owes_data = array();
        foreach ($shipvendors as $vendor) {
           
            $where = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes = $owe_model->getAllOwe($where);
           
            

            foreach ($owes as $owe) {
                $owes_data['money'][$owe->vendor] = isset($owes_data['money'][$owe->vendor])?($owes_data['money'][$owe->vendor]+$owe->money):0+$owe->money;

               
            }
        }

        $where_truoc = array(
            'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $shipvendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_truoc = $vendor_model->getAllVendor($where_truoc);

        $where_truoc = array(
            'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $vendors_ops_truoc = $vendor_model->getAllVendor($where_truoc);
        
         $owes_data_truoc = array();

        foreach ($shipvendors_truoc as $vendor) {
           
            $where_truoc = array(
                'where' => '( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND vendor = '.$vendor->shipment_vendor_id,
            );
            $owes_truoc = $owe_model->getAllOwe($where_truoc);
           

            foreach ($owes_truoc as $owe) {
                $owes_data_truoc['money'][$owe->vendor] = isset($owes_data_truoc['money'][$owe->vendor])?($owes_data_truoc['money'][$owe->vendor]+$owe->money):0+$owe->money;

                
            }
        }


        $new_owes_data_truoc = array();
        $new_vendors_truoc = null;
        $new_shipvendors_truoc = null;
        $new_vendors_ops_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_shipvendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_vendors_truoc = $vendor_model->getAllVendor($new_where_truoc);

            $new_where_truoc = array(
                'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_vendors_ops_truoc = $vendor_model->getAllVendor($new_where_truoc);
            
             

            foreach ($new_shipvendors_truoc as $vendor) {
               
                $new_where_truoc = array(
                    'where' => 'week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND vendor = '.$vendor->shipment_vendor_id,
                );
                $new_owes_truoc = $owe_model->getAllOwe($new_where_truoc);
               
                foreach ($new_owes_truoc as $owe) {
                    $new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor] = isset($new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor])?($new_owes_data_truoc[$tuan_truoc+$i]['money'][$owe->vendor]+$owe->money):0+$owe->money;

                }
            }
        }

        
        

        $staff_model = $this->model->get('staffModel');
        
        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE (week >= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.' ) OR (week <= 53 AND year < '.$nam_truoc.') )',
        );
        $staffs_data = $staff_model->getAllStaff($where);

        $where = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) )',
        );
        $staffs = $staff_model->getAllStaff($where);

        $staff_debt_model = $this->model->get('staffdebtModel');

        $staff_debt_data = array();
        foreach ($staffs as $staff) {
           
        
            $join = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['co'][$staff_debt->staff] = isset($staff_debt_data['co'][$staff_debt->staff])?($staff_debt_data['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') OR (week <= '.$tuan.' AND year = '.$nam_report.') ) AND staff = '.$staff->staff_id,
            );
            $staff_debts = $staff_debt_model->getAllCost($where,$join);
            foreach ($staff_debts as $staff_debt) {
                $staff_debt_data['no'][$staff_debt->staff] = isset($staff_debt_data['no'][$staff_debt->staff])?($staff_debt_data['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }

        $where_truoc = array(
            'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') ) )',
        );
        $staffs_truoc = $staff_model->getAllStaff($where_truoc);


        $staff_debt_data_truoc = array();
        foreach ($staffs_truoc as $staff) {
           
        
            $join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
            $where_truoc = array(
                'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['co'][$staff_debt->staff] = isset($staff_debt_data_truoc['co'][$staff_debt->staff])?($staff_debt_data_truoc['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }

            $where_truoc = array(
                'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan_truoc.' AND year = '.$nam_truoc.') OR (week <= 53 AND year < '.$nam_truoc.') )  AND staff = '.$staff->staff_id,
            );
            $staff_debts_truoc = $staff_debt_model->getAllCost($where_truoc,$join_truoc);
            foreach ($staff_debts_truoc as $staff_debt) {
                $staff_debt_data_truoc['no'][$staff_debt->staff] = isset($staff_debt_data_truoc['no'][$staff_debt->staff])?($staff_debt_data_truoc['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
            }
        }


        $new_staff_debt_data_truoc = array();
        $new_staffs_truoc = null;

        for ($i=1; $i <= $number; $i++) { 
            $new_where_truoc = array(
                'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.')',
            );
            $new_staffs_truoc = $staff_model->getAllStaff($new_where_truoc);


            
            foreach ($new_staffs_truoc as $staff) {
               
            
                $new_join_truoc = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
                $new_where_truoc = array(
                    'where' => 'staff_debt.status=1 AND week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff])?($new_staff_debt_data_truoc[$tuan_truoc+$i]['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }

                $new_where_truoc = array(
                    'where' => 'staff_debt.status=2 AND week = '.($tuan_truoc+$i).' AND year = '.$nam_truoc.' AND staff = '.$staff->staff_id,
                );
                $new_staff_debts_truoc = $staff_debt_model->getAllCost($new_where_truoc,$new_join_truoc);
                foreach ($new_staff_debts_truoc as $staff_debt) {
                    $new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff] = isset($new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff])?($new_staff_debt_data_truoc[$tuan_truoc+$i]['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }
            }
        }

        $luong = null;

        $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan).' AND assets.year = '.$nam_report.')';
        $luongs = $assets_model->queryAssets($q_luong);
        $luong_datra = 0;

        foreach ($luongs as $luongthang) {
            $luong += str_replace('-', "", $luongthang->total);
        }

        


        $salary_model = $this->model->get('newsalaryModel');

        $luong_truoc = null;
        
        $q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc).' AND assets.year = '.$nam_truoc.')';
        $luongs_truoc = $assets_model->queryAssets($q_luong_truoc);
        $luong_datra_truoc = 0;

        foreach ($luongs_truoc as $luongthang_truoc) {
            $luong_truoc += str_replace('-', "", $luongthang_truoc->total);
        }


        $new_luong_truoc = null;
        $new_luong_truoc_tra = null;

        for ($i=1; $i <= $number; $i++) { 

            $new_nam_truoc[$tuan_truoc+$i] = date('Y',strtotime($new_ketthuc_truoc[$tuan_truoc+$i]));

            $new_q_luong_truoc = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc+$i).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc = $assets_model->queryAssets($new_q_luong_truoc);
            $new_luong_datra_truoc = 0;

            $new_luong_truoc[$tuan_truoc+$i] = 0;

            foreach ($new_luongs_truoc as $new_luongthang_truoc) {
                $new_luong_truoc[$tuan_truoc+$i] += str_replace('-', "", $new_luongthang_truoc->total);
            }



            $new_q_luong_truoc_tra = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan_truoc+$i).' AND assets.year = '.$new_nam_truoc[$tuan_truoc+$i].')';
            $new_luongs_truoc_tra = $assets_model->queryAssets($new_q_luong_truoc_tra);
            $new_luong_datra_truoc_tra = 0;

            $new_luong_truoc_tra[$tuan_truoc+$i] = 0;

            foreach ($new_luongs_truoc_tra as $new_luongthang_truoc_tra) {
                $new_luong_truoc_tra[$tuan_truoc+$i] += str_replace('-', "", $new_luongthang_truoc_tra->total);
            }

        }


        $asset = $asset_data;
        $obtains = $obtains_data;
        $owe = $owes_data;
        $debt = $staff_debt_data;
        $salary = $luong;

        $asset_truoc = $asset_data_truoc;
        $obtains_truoc = $obtains_data_truoc;
        $owe_truoc = $owes_data_truoc;
        $debt_truoc = $staff_debt_data_truoc;
        $salary_truoc = $luong_truoc;
        $tuantruoc = $tuan_truoc;

        $new_asset_truoc = $new_asset_data_truoc;
        $new_obtains_truoc = $new_obtains_data_truoc;
        $new_owe_truoc = $new_owes_data_truoc;
        $new_debt_truoc = $new_staff_debt_data_truoc;
        $new_salary_truoc = $new_luong_truoc;

        $nam = $nam_report;
        $namtruoc = $nam_truoc;

        $new_salary_truoc_tra = $new_luong_truoc_tra;

        $tongnh_truoc = (isset($asset_truoc[3])?$asset_truoc[3]:0)+(isset($asset_truoc[4])?$asset_truoc[4]:0)+(isset($asset_truoc[5])?$asset_truoc[5]:0)+(isset($asset_truoc[6])?$asset_truoc[6]:0)+(isset($asset_truoc[7])?$asset_truoc[7]:0)+(isset($asset_truoc[8])?$asset_truoc[8]:0)+(isset($asset_truoc[9])?$asset_truoc[9]:0)+(isset($asset_truoc[10])?$asset_truoc[10]:0)+(isset($asset_truoc[11])?$asset_truoc[11]:0)+(isset($asset_truoc[12])?$asset_truoc[12]:0)+(isset($asset_truoc[13])?$asset_truoc[13]:0);
        $tongnh = (isset($asset[3])?$asset[3]:0)+(isset($asset[4])?$asset[4]:0)+(isset($asset[5])?$asset[5]:0)+(isset($asset[6])?$asset[6]:0)+(isset($asset[7])?$asset[7]:0)+(isset($asset[8])?$asset[8]:0)+(isset($asset[9])?$asset[9]:0)+(isset($asset[10])?$asset[10]:0)+(isset($asset[11])?$asset[11]:0)+(isset($asset[12])?$asset[12]:0)+(isset($asset[13])?$asset[13]:0);

        $tongthuco_truoc = null;
        $tongthuno_truoc = null;
        $tongthuco = null;
        $tongthuno = null;
        $new_tongthuco_truoc = array();
        $new_tongthuno_truoc = array();
                foreach ($customers_data as $customer) {  
                    $tongthuco += (isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]>0)?$obtains['money'][$customer->customer_id]:null;
                    $tongthuno += (isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]<0)?str_replace('-', '', $obtains['money'][$customer->customer_id]):null;

                    $tongthuco_truoc += (isset($obtains_truoc['money'][$customer->customer_id]) && $obtains_truoc['money'][$customer->customer_id]>0)?$obtains_truoc['money'][$customer->customer_id]:null;
                    $tongthuno_truoc += (isset($obtains_truoc['money'][$customer->customer_id]) && $obtains_truoc['money'][$customer->customer_id]<0)?str_replace('-', "", $obtains_truoc['money'][$customer->customer_id]):null;

                    for ($i=1; $i < $number; $i++) { 
                        $new_tongthuco_truoc[$tuantruoc+$i] = isset($new_tongthuco_truoc[$tuantruoc+$i])?($new_tongthuco_truoc[$tuantruoc+$i]+((isset($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]) && $new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]>0)?($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]):null)):(0+((isset($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]) && $new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]>0)?($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]):null));

                        $new_tongthuno_truoc[$tuantruoc+$i] = isset($new_tongthuno_truoc[$tuantruoc+$i])?($new_tongthuno_truoc[$tuantruoc+$i]+((isset($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]) && $new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]<0)?($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]):null)):(0+((isset($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]) && $new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]<0)?($new_obtains_truoc[$tuantruoc+$i]['money'][$customer->customer_id]):null));

                        
                    }
                    
                }

        $tongtraco_truoc = null;
        $tongtrano_truoc = null;
        $tongtraco = null;
        $tongtrano = null;
        $new_tongtraco_truoc = array();
        $new_tongtrano_truoc = array();
                foreach ($vendors_data as $vendor) {  
                    $tongtraco_truoc += (isset($owe_truoc['money'][$vendor->shipment_vendor_id]) && $owe_truoc['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $owe_truoc['money'][$vendor->shipment_vendor_id]):null;
                    $tongtrano_truoc += (isset($owe_truoc['money'][$vendor->shipment_vendor_id]) && $owe_truoc['money'][$vendor->shipment_vendor_id]>0)?$owe_truoc['money'][$vendor->shipment_vendor_id]:null;
                    $tongtraco += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $owe['money'][$vendor->shipment_vendor_id]):null;
                    $tongtrano += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)?$owe['money'][$vendor->shipment_vendor_id]:null;

                    for ($i=1; $i < $number; $i++) { 
                        $new_tongtraco_truoc[$tuantruoc+$i] = isset($new_tongtraco_truoc[$tuantruoc+$i])?($new_tongtraco_truoc[$tuantruoc+$i]+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]<0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null)):(0+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]<0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null));

                        $new_tongtrano_truoc[$tuantruoc+$i] = isset($new_tongtrano_truoc[$tuantruoc+$i])?($new_tongtrano_truoc[$tuantruoc+$i]+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]>0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null)):(0+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]>0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null));

                        
                    }
                    
                }

        $tongopsco_truoc = null;
        $tongopsno_truoc = null;
        $tongopsco = null;
        $tongopsno = null;
        $new_tongopsco_truoc = array();
        $new_tongopsno_truoc = array();

        $co1 = array(); $no1 = array(); $co_truoc1 = array(); $no_truoc1 = array();

                foreach ($staffs_data as $staff) {  
                    if (isset($debt_truoc['co'][$staff->staff_id]) && $debt_truoc['co'][$staff->staff_id] > 0) {
                        $co_truoc1[$staff->staff_id] = isset($co_truoc1[$staff->staff_id])?($co_truoc1[$staff->staff_id] + $debt_truoc['co'][$staff->staff_id]):($debt_truoc['co'][$staff->staff_id]);
                    }
                    if (isset($debt_truoc['no'][$staff->staff_id]) && $debt_truoc['no'][$staff->staff_id] < 0) {
                        $co_truoc1[$staff->staff_id] = isset($co_truoc1[$staff->staff_id])?($co_truoc1[$staff->staff_id] + str_replace('-', "", $debt_truoc['no'][$staff->staff_id])):(str_replace('-', "", $debt_truoc['no'][$staff->staff_id]));
                    }

                    if (isset($debt_truoc['co'][$staff->staff_id]) && $debt_truoc['co'][$staff->staff_id] < 0) {
                        $no_truoc1[$staff->staff_id] = isset($no_truoc1[$staff->staff_id])?($no_truoc1[$staff->staff_id] + str_replace('-', "", $debt_truoc['co'][$staff->staff_id])):(str_replace('-', "", $debt_truoc['co'][$staff->staff_id]));
                    }
                    if (isset($debt_truoc['no'][$staff->staff_id]) && $debt_truoc['no'][$staff->staff_id] > 0) {
                        $no_truoc1[$staff->staff_id] = isset($no_truoc1[$staff->staff_id])?($no_truoc1[$staff->staff_id] + $debt_truoc['no'][$staff->staff_id]):($debt_truoc['no'][$staff->staff_id]);
                    }

                    if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] > 0) {
                        $co1[$staff->staff_id] = isset($co1[$staff->staff_id])?($co1[$staff->staff_id] + $debt['co'][$staff->staff_id]):($debt['co'][$staff->staff_id]);
                    }
                    if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] < 0) {
                        $co1[$staff->staff_id] = isset($co1[$staff->staff_id])?($co1[$staff->staff_id] + str_replace('-', "", $debt['no'][$staff->staff_id]) ):(str_replace('-', "", $debt['no'][$staff->staff_id]));
                    }

                    if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] < 0) {
                        $no1[$staff->staff_id] = isset($no1[$staff->staff_id])?($no1[$staff->staff_id] + str_replace('-', "", $debt['co'][$staff->staff_id]) ):(str_replace('-', "", $debt['co'][$staff->staff_id]));
                    }
                    if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] > 0) {
                        $no1[$staff->staff_id] = isset($no1[$staff->staff_id])?($no1[$staff->staff_id] + $debt['no'][$staff->staff_id]):($debt['no'][$staff->staff_id]);
                    }

                    $tongopsco_truoc += isset($co_truoc1[$staff->staff_id]) ?$co_truoc1[$staff->staff_id]:null;
                    $tongopsno_truoc += isset($no_truoc1[$staff->staff_id]) ?$no_truoc1[$staff->staff_id]:null;
                    $tongopsco += isset($co1[$staff->staff_id]) ?$co1[$staff->staff_id]:null;
                    $tongopsno += isset($no1[$staff->staff_id]) ?$no1[$staff->staff_id]:null;

                    for ($i=1; $i < $number; $i++) { 
                        $new_tongopsco_truoc[$tuantruoc+$i] = isset($new_tongopsco_truoc[$tuantruoc+$i])?($new_tongopsco_truoc[$tuantruoc+$i]+(((isset($new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]>0) ? str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]):null )+((isset($new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]<0)?str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]):null ))):(0+(((isset($new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]>0) ? str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]):null )+((isset($new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]<0)?str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]):null )));
                        $new_tongopsno_truoc[$tuantruoc+$i] = isset($new_tongopsno_truoc[$tuantruoc+$i])?($new_tongopsno_truoc[$tuantruoc+$i]+(((isset($new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]>0)?str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]):null )+((isset($new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]<0) ? str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]):null ))):(0+(((isset($new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]>0)?str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['no'][$staff->staff_id]):null )+((isset($new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]) && $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]<0) ? str_replace('-', "", $new_debt_truoc[$tuantruoc+$i]['co'][$staff->staff_id]):null )));
                    }
                }

                foreach ($vendors_ops_data as $vendor) {
                    $tongopsco += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $owe['money'][$vendor->shipment_vendor_id]):null;
                    $tongopsno += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)?$owe['money'][$vendor->shipment_vendor_id]:null;

                    $tongopsco_truoc += (isset($owe_truoc['money'][$vendor->shipment_vendor_id]) && $owe_truoc['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $owe_truoc['money'][$vendor->shipment_vendor_id]):null;
                    $tongopsno_truoc += (isset($owe_truoc['money'][$vendor->shipment_vendor_id]) && $owe_truoc['money'][$vendor->shipment_vendor_id]>0)?$owe_truoc['money'][$vendor->shipment_vendor_id]:null;

                    for ($i=1; $i < $number; $i++) { 
                        $new_tongopsco_truoc[$tuantruoc+$i] = isset($new_tongopsco_truoc[$tuantruoc+$i])?($new_tongopsco_truoc[$tuantruoc+$i]+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]):null)):(0+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', "", $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]):null));
                        $new_tongopsno_truoc[$tuantruoc+$i] = isset($new_tongopsno_truoc[$tuantruoc+$i])?($new_tongopsno_truoc[$tuantruoc+$i]+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]>0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null)):(0+((isset($new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]) && $new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]>0)?$new_owe_truoc[$tuantruoc+$i]['money'][$vendor->shipment_vendor_id]:null));
                    }

                }

        $tongluong = $salary; $tongluong_truoc = $salary_truoc; 

        $luongthang = 0;

        $new_tongluong_truoc = array();


        $table = array();
        $table['cols'] = array(
            /* define your DataTable columns here
             * each column gets its own array
             * syntax of the arrays is:
             * label => column label
             * type => data type of column (string, number, date, datetime, boolean)
             */
            // I assumed your first column is a "string" type
            // and your second column is a "number" type
            // but you can change them if they are not
            array('label' => 'Tuần', 'type' => 'string'),
            array('label' => 'Tổng tài sản', 'type' => 'number'),
            array('label' => 'Tăng giảm', 'type' => 'number'),
            array('label' => 'LN Sale', 'type' => 'number'),
            array('label' => 'LN Agent', 'type' => 'number'),
            array('label' => 'LN Trading', 'type' => 'number'),
            array('label' => 'LN TCMT', 'type' => 'number'),
            array('label' => 'LN Tài chính', 'type' => 'number')
        );
        $rows = array();

        $loinhuansale = $sale_data_truoc['doanhthu']-$sale_data_truoc['chiphi']-round($sale_data_truoc['kvat']*0.1);
        $loinhuanagent = $agent_data_truoc['doanhthu']-$agent_data_truoc['chiphi']-round($agent_data_truoc['kvat']*0.1);
        $loinhuantrading = $trading_data_truoc['doanhthu']-$trading_data_truoc['chiphi']-round($trading_data_truoc['kvat']*0.1);
        $loinhuantcmt = $invoice_data_truoc['doanhthu']-$invoice_data_truoc['chiphi']-$invoice_data_truoc['invoice'];
        $loinhuantc = round(($sale_data_truoc['kvat']+$agent_data_truoc['kvat']+$trading_data_truoc['kvat'])*0.1)-($sale_data_truoc['invoice']+$agent_data_truoc['invoice']+$trading_data_truoc['invoice']);

        $tts[0] = (((isset($asset_truoc[1])?$asset_truoc[1]:0)+(isset($asset_truoc[2])?$asset_truoc[2]:0)+$tongnh_truoc+str_replace('-', "", $tongthuco_truoc)+str_replace('-', "", $tongtraco_truoc)+str_replace('-', "", $tongopsco_truoc))-(str_replace('-', "", $tongthuno_truoc)+str_replace('-', "", $tongtrano_truoc)+str_replace('-', "", $tongopsno_truoc)/*+str_replace('-', "", $tongluong_truoc)*/));

        $tts_cuoi = $tts[0];

        $temp = array();
        $temp[] = array('v' => 'Tuần '. $tuan_truoc);
        $temp[] = array('v' => (float) $tts[0], 'f'=>(string)$this->lib->formatMoney($tts[0])); 
        $temp[] = array('v' => (float) 0); 
        $temp[] = array('v' => (float) ($loinhuansale-round($loinhuansale*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuansale-round($loinhuansale*0.1)));
        $temp[] = array('v' => (float) ($loinhuanagent-round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuanagent-round($loinhuanagent*0.1)));
        $temp[] = array('v' => (float) ($loinhuantrading-round($loinhuantrading*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantrading-round($loinhuantrading*0.1)));
        $temp[] = array('v' => (float) $loinhuantcmt, 'f'=>(string)$this->lib->formatMoney($loinhuantcmt));
        $temp[] = array('v' => (float) ($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1))); 
        // insert the temp array into $rows
        $rows[] = array('c' => $temp);

        $truoc_co_tm = 0;
        $truoc_co_usd = 0;
        $truoc_co_nh = 0;
        $truoc_co_thu = 0;
        $truoc_co_tra = 0;
        $truoc_no_thu = 0;
        $truoc_no_tra = 0;
        $truoc_co_ops = 0;
        $truoc_no_ops = 0;
        for ($i=1; $i < $number; $i++) {
            $new_tongnh_truoc[$tuantruoc+$i] = (isset($new_asset_truoc[$tuantruoc+$i][3])?$new_asset_truoc[$tuantruoc+$i][3]:0)+(isset($new_asset_truoc[$tuantruoc+$i][4])?$new_asset_truoc[$tuantruoc+$i][4]:0)+(isset($new_asset_truoc[$tuantruoc+$i][5])?$new_asset_truoc[$tuantruoc+$i][5]:0)+(isset($new_asset_truoc[$tuantruoc+$i][6])?$new_asset_truoc[$tuantruoc+$i][6]:0)+(isset($new_asset_truoc[$tuantruoc+$i][7])?$new_asset_truoc[$tuantruoc+$i][7]:0)+(isset($new_asset_truoc[$tuantruoc+$i][8])?$new_asset_truoc[$tuantruoc+$i][8]:0)+(isset($new_asset_truoc[$tuantruoc+$i][9])?$new_asset_truoc[$tuantruoc+$i][9]:0)+(isset($new_asset_truoc[$tuantruoc+$i][10])?$new_asset_truoc[$tuantruoc+$i][10]:0)+(isset($new_asset_truoc[$tuantruoc+$i][11])?$new_asset_truoc[$tuantruoc+$i][11]:0)+(isset($new_asset_truoc[$tuantruoc+$i][12])?$new_asset_truoc[$tuantruoc+$i][12]:0)+(isset($new_asset_truoc[$tuantruoc+$i][13])?$new_asset_truoc[$tuantruoc+$i][13]:0);
            $new_tongluong_truoc[$tuantruoc+$i] = isset($new_salary_truoc[$tuantruoc+$i])?$new_salary_truoc[$tuantruoc+$i]:null; 
            $luongthang += $new_tongluong_truoc[$tuantruoc+$i];

            $truoc_co_tm += isset($new_asset_truoc[$tuantruoc+$i-1][1])?$new_asset_truoc[$tuantruoc+$i-1][1]:0;
            $truoc_co_usd += isset($new_asset_truoc[$tuantruoc+$i-1][2])?$new_asset_truoc[$tuantruoc+$i-1][2]:0;
            $truoc_co_nh += isset($new_tongnh_truoc[$tuantruoc+$i-1])?$new_tongnh_truoc[$tuantruoc+$i-1]:0;
            $truoc_co_thu += isset($new_tongthuco_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongthuco_truoc[$tuantruoc+$i-1]):0;
            $truoc_no_thu += isset($new_tongthuno_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongthuno_truoc[$tuantruoc+$i-1]):0;
            $truoc_co_tra += isset($new_tongtraco_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongtraco_truoc[$tuantruoc+$i-1]):0;
            $truoc_no_tra += isset($new_tongtrano_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongtrano_truoc[$tuantruoc+$i-1]):0;
            $truoc_co_ops += isset($new_tongopsco_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongopsco_truoc[$tuantruoc+$i-1]):0;
            $truoc_no_ops += isset($new_tongopsno_truoc[$tuantruoc+$i-1])?str_replace('-', "", $new_tongopsno_truoc[$tuantruoc+$i-1]):0;

            $tts[$i] = ((((isset($new_asset_truoc[$tuantruoc+$i][1]) && $new_asset_truoc[$tuantruoc+$i][1]>0)?$new_asset_truoc[$tuantruoc+$i][1]:null)+((isset($new_asset_truoc[$tuantruoc+$i][2]) && $new_asset_truoc[$tuantruoc+$i][2]>0)?$new_asset_truoc[$tuantruoc+$i][1]:null)+($new_tongnh_truoc[$tuantruoc+$i]>0?str_replace('-', "", $new_tongnh_truoc[$tuantruoc+$i]):null)+str_replace('-', "", $new_tongthuco_truoc[$tuantruoc+$i])+str_replace('-', "", $new_tongtraco_truoc[$tuantruoc+$i])+str_replace('-', "", $new_tongopsco_truoc[$tuantruoc+$i])+($asset_truoc[1]+$truoc_co_tm)+($asset_truoc[2]+$truoc_co_usd)+($tongnh_truoc+$truoc_co_nh)+($tongthuco_truoc+$truoc_co_thu)+($tongtraco_truoc+$truoc_co_tra)+($tongopsco_truoc+$truoc_co_ops))-(((isset($new_asset_truoc[$tuantruoc+$i][1]) && $new_asset_truoc[$tuantruoc+$i][1]<0)?str_replace('-', "", $new_asset_truoc[$tuantruoc+$i][1]):null)+((isset($new_asset_truoc[$tuantruoc+$i][2]) && $new_asset_truoc[$tuantruoc+$i][2]<0)?str_replace('-', "", $new_asset_truoc[$tuantruoc+$i][2]):null)+($new_tongnh_truoc[$tuantruoc+$i]<0?str_replace('-', "", $new_tongnh_truoc[$tuantruoc+$i]):null)+str_replace('-', "", $new_tongthuno_truoc[$tuantruoc+$i])+str_replace('-', "", $new_tongtrano_truoc[$tuantruoc+$i])+str_replace('-', "", $new_tongopsno_truoc[$tuantruoc+$i])+($tongthuno_truoc+$truoc_no_thu)+($tongtrano_truoc+$truoc_no_tra)+($tongopsno_truoc+$truoc_no_ops)/*+str_replace('-', "", $new_tongluong_truoc[$tuantruoc+$i])*/));

            $loinhuansale = $sale_data['doanhthu'][$i]-$sale_data['chiphi'][$i]-round($sale_data['kvat'][$i]*0.1);
            $loinhuanagent = $agent_data['doanhthu'][$i]-$agent_data['chiphi'][$i]-round($agent_data['kvat'][$i]*0.1);
            $loinhuantrading = $trading_data['doanhthu'][$i]-$trading_data['chiphi'][$i]-round($trading_data['kvat'][$i]*0.1);
            $loinhuantcmt = $invoice_data['doanhthu'][$i]-$invoice_data['chiphi'][$i]-$invoice_data['invoice'][$i];
            $loinhuantc = round(($sale_data['kvat'][$i]+$agent_data['kvat'][$i]+$trading_data['kvat'][$i])*0.1)-($sale_data['invoice'][$i]+$agent_data['invoice'][$i]+$trading_data['invoice'][$i]);


            $temp = array();
            // each column needs to have data inserted via the $temp array
            $temp[] = array('v' => 'Tuần '. ($tuantruoc+$i));
            $temp[] = array('v' => (float) $tts[$i], 'f'=>(string)$this->lib->formatMoney($tts[$i])); 
            $temp[] = array('v' => (float) ($tts[$i]-$tts[$i-1]), 'f'=>(string)$this->lib->formatMoney($tts[$i]-$tts[$i-1])); 
            $temp[] = array('v' => (float) ($loinhuansale-round($loinhuansale*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuansale-round($loinhuansale*0.1)));
            $temp[] = array('v' => (float) ($loinhuanagent-round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuanagent-round($loinhuanagent*0.1)));
            $temp[] = array('v' => (float) ($loinhuantrading-round($loinhuantrading*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantrading-round($loinhuantrading*0.1)));
            $temp[] = array('v' => (float) $loinhuantcmt, 'f'=>(string)$this->lib->formatMoney($loinhuantcmt));
            $temp[] = array('v' => (float) ($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1))); 
            // insert the temp array into $rows
            $rows[] = array('c' => $temp);

            $tts_cuoi = $tts[$i];
        }

        $loinhuansale = $sale_data_sau['doanhthu']-$sale_data_sau['chiphi']-round($sale_data_sau['kvat']*0.1);
        $loinhuanagent = $agent_data_sau['doanhthu']-$agent_data_sau['chiphi']-round($agent_data_sau['kvat']*0.1);
        $loinhuantrading = $trading_data_sau['doanhthu']-$trading_data_sau['chiphi']-round($trading_data_sau['kvat']*0.1);
        $loinhuantcmt = $invoice_data_sau['doanhthu']-$invoice_data_sau['chiphi']-$invoice_data_sau['invoice'];
        $loinhuantc = round(($sale_data_sau['kvat']+$agent_data_sau['kvat']+$trading_data_sau['kvat'])*0.1)-($sale_data_sau['invoice']+$agent_data_sau['invoice']+$trading_data_sau['invoice']);


        $tts = (((isset($asset[1])?$asset[1]:0)+(isset($asset[2])?$asset[2]:0)+$tongnh+str_replace('-', "", $tongthuco)+str_replace('-', "", $tongtraco)+str_replace('-', "", $tongopsco))-(str_replace('-', "", $tongthuno)+str_replace('-', "", $tongtrano)+str_replace('-', "", $tongopsno)/*+$tongluong*/));
        $temp = array();
        $temp[] = array('v' => 'Tuần '. $tuan);
        $temp[] = array('v' => (float) $tts, 'f'=>(string)$this->lib->formatMoney($tts)); 
        $temp[] = array('v' => (float) ($tts-$tts_cuoi), 'f'=>(string)$this->lib->formatMoney($tts-$tts_cuoi)); 
        $temp[] = array('v' => (float) ($loinhuansale-round($loinhuansale*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuansale-round($loinhuansale*0.1)));
        $temp[] = array('v' => (float) ($loinhuanagent-round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuanagent-round($loinhuanagent*0.1)));
        $temp[] = array('v' => (float) ($loinhuantrading-round($loinhuantrading*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantrading-round($loinhuantrading*0.1)));
        $temp[] = array('v' => (float) $loinhuantcmt, 'f'=>(string)$this->lib->formatMoney($loinhuantcmt));
        $temp[] = array('v' => (float) ($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1)), 'f'=>(string)$this->lib->formatMoney($loinhuantc+round($loinhuansale*0.1)+round($loinhuantrading*0.1)+round($loinhuanagent*0.1))); 
        // insert the temp array into $rows
        $rows[] = array('c' => $temp);

        $table['rows'] = $rows;
        
        echo json_encode($table);
    }

    function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($this->registry->router->param_id != null && $this->registry->router->page != null) {
            $tuan = $this->registry->router->param_id;
            $nam_report = $this->registry->router->page;

            $mang = $this->getStartAndEndDate($tuan,$nam_report);
            $batdau = $mang[0];
            $ketthuc = $mang[1];

            $bank_model = $this->model->get('bankModel');
            $banks = $bank_model->getAllBank();
            $assets_model = $this->model->get('assetsModel');

            $asset_data = array();
            foreach ($banks as $bank) {
               
                $where = array(
                    'where' => '( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) AND bank = '.$bank->bank_id,
                );
                $assets = $assets_model->getAllAssets($where);
                
                
                foreach ($assets as $asset) {
                    $asset_data[$asset->bank] = isset($asset_data[$asset->bank])?($asset_data[$asset->bank]+$asset->total):0+$asset->total;
                }
            }

            $customer_model = $this->model->get('customerModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $where = array(
                'where' => 'customer_id in (SELECT customer FROM obtain WHERE ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) )',
            );
            $customers = $customer_model->getAllCustomer($where);


            $obtains_data = array();
            foreach ($customers as $customer) {
             
                    $where = array(
                        'where' => '( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') )  AND customer = '.$customer->customer_id,
                    );
                    $obtains = $obtain_model->getAllObtain($where);

                    foreach ($obtains as $obtain) {
                        $obtains_data['money'][$obtain->customer] = isset($obtains_data['money'][$obtain->customer])?($obtains_data['money'][$obtain->customer]+$obtain->money):0+$obtain->money;

                    }
            }
            
            
            $owe_model = $this->model->get('oweModel');
            $vendor_model = $this->model->get('shipmentvendorModel');

            $where = array(
                'where' => 'shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) )',
            );
            $shipvendors = $vendor_model->getAllVendor($where);

            $where = array(
                'where' => 'vendor_type != 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) )',
            );
            $vendors = $vendor_model->getAllVendor($where);

            $where = array(
                'where' => 'vendor_type = 1 AND shipment_vendor_id in (SELECT vendor FROM owe WHERE ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) )',
            );
            $vendors_ops = $vendor_model->getAllVendor($where);
            
             $owes_data = array();
            foreach ($shipvendors as $vendor) {
               
                $where = array(
                    'where' => '( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') )  AND vendor = '.$vendor->shipment_vendor_id,
                );
                $owes = $owe_model->getAllOwe($where);
               

                foreach ($owes as $owe) {
                    $owes_data['money'][$owe->vendor] = isset($owes_data['money'][$owe->vendor])?($owes_data['money'][$owe->vendor]+$owe->money):0+$owe->money;

                }
            }



            
            

            $staff_model = $this->model->get('staffModel');
            
            $where = array(
                'where' => 'staff_id in (SELECT staff FROM staff_debt WHERE ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') ) )',
            );
            $staffs = $staff_model->getAllStaff($where);

            $staff_debt_model = $this->model->get('staffdebtModel');

            $staff_debt_data = array();
            foreach ($staffs as $staff) {
               
            
                $join = array('table'=>'staff','where'=>'staff.staff_id = staff_debt.staff ');
                $where = array(
                    'where' => 'staff_debt.status=1 AND ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') )  AND staff = '.$staff->staff_id,
                );
                $staff_debts = $staff_debt_model->getAllCost($where,$join);
                
                foreach ($staff_debts as $staff_debt) {
                    $staff_debt_data['co'][$staff_debt->staff] = isset($staff_debt_data['co'][$staff_debt->staff])?($staff_debt_data['co'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }

                $where = array(
                    'where' => 'staff_debt.status=2 AND ( (week <= '.$tuan.' AND year = '.$nam_report.') OR (week <= 53 AND year < '.$nam_report.') )  AND staff = '.$staff->staff_id,
                );
                $staff_debts = $staff_debt_model->getAllCost($where,$join);
                foreach ($staff_debts as $staff_debt) {
                    $staff_debt_data['no'][$staff_debt->staff] = isset($staff_debt_data['no'][$staff_debt->staff])?($staff_debt_data['no'][$staff_debt->staff]+$staff_debt->money):0+$staff_debt->money;
                }
            }

            $luong = null;

            $q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND (staff_cost <= 0 OR staff_cost IS NULL) AND check_salary=1 AND (assets.week = '.($tuan).' AND assets.year = '.$nam_report.')';
            $luongs = $assets_model->queryAssets($q_luong);
            $luong_datra = 0;

            foreach ($luongs as $luongthang) {
                $luong += str_replace('-', "", $luongthang->total);
            }

            $asset = $asset_data;
            $obtains = $obtains_data;
            $customers = $customers;
            $owe = $owes_data;
            $vendors = $vendors;
            $vendors_ops = $vendors_ops;
            $staffs = $staffs;
            $debt = $staff_debt_data;
            $salary = $luong;


            $tongthuco = null;
            $tongthuno = null;
            foreach ($customers as $customer) {  
                $tongthuco += (isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]>0)?$obtains['money'][$customer->customer_id]:null;
                $tongthuno += (isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]<0)?str_replace('-', '', $obtains['money'][$customer->customer_id]):null;
            }

            $tongtraco = null;
            $tongtrano = null;
            foreach ($vendors as $vendor) {  
                $tongtraco += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', '', $owe['money'][$vendor->shipment_vendor_id]):null;
                $tongtrano += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)?$owe['money'][$vendor->shipment_vendor_id]:null;
            }

            $tongopsco = null;
            $tongopsno = null;
            $co1 = array(); $no1 = array();
            foreach ($staffs as $staff) {  
                if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] > 0) {
                    $co1[$staff->staff_id] = isset($co1[$staff->staff_id])?($co1[$staff->staff_id] + $debt['co'][$staff->staff_id]):($debt['co'][$staff->staff_id]);
                }
                if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] < 0) {
                    $co1[$staff->staff_id] = isset($co1[$staff->staff_id])?($co1[$staff->staff_id] + str_replace('-', "", $debt['no'][$staff->staff_id]) ):(str_replace('-', "", $debt['no'][$staff->staff_id]));
                }

                if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] < 0) {
                    $no1[$staff->staff_id] = isset($no1[$staff->staff_id])?($no1[$staff->staff_id] + str_replace('-', "", $debt['co'][$staff->staff_id]) ):(str_replace('-', "", $debt['co'][$staff->staff_id]));
                }
                if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] > 0) {
                    $no1[$staff->staff_id] = isset($no1[$staff->staff_id])?($no1[$staff->staff_id] + $debt['no'][$staff->staff_id]):($debt['no'][$staff->staff_id]);
                }

                $tongopsco += isset($co1[$staff->staff_id]) ?$co1[$staff->staff_id]:null;
                $tongopsno += isset($no1[$staff->staff_id]) ?$no1[$staff->staff_id]:null;
            }

            foreach ($vendors_ops as $vendor) {
                $tongopsco += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0)?str_replace('-', '', $owe['money'][$vendor->shipment_vendor_id]):null;
                $tongopsno += (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)?$owe['money'][$vendor->shipment_vendor_id]:null;
            }

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'BÁO CÁO TÀI SẢN TUẦN '.$tuan)
                ->setCellValue('A3', 'STT')
               ->setCellValue('B3', 'Nội dung')
               ->setCellValue('C3', 'Có')
               ->setCellValue('D3', 'Nợ')
               ->setCellValue('E3', 'Ghi chú')
               ->setCellValue('A4', '1')
               ->setCellValue('B4', 'Tiền mặt')
               ->setCellValue('C4', isset($asset[1])?$asset[1]:0)
               ->setCellValue('A5', '2')
               ->setCellValue('B5', 'USD (200)')
               ->setCellValue('C5', isset($asset[2])?$asset[2]:0)
               ->setCellValue('A6', '3')
               ->setCellValue('B6', 'Ngân hàng')
               ->setCellValue('C6', (isset($asset[3])?$asset[3]:0)+(isset($asset[4])?$asset[4]:0)+(isset($asset[5])?$asset[5]:0)+(isset($asset[6])?$asset[6]:0)+(isset($asset[7])?$asset[7]:0)+(isset($asset[8])?$asset[8]:0)+(isset($asset[9])?$asset[9]:0)+(isset($asset[10])?$asset[10]:0)+(isset($asset[11])?$asset[11]:0)+(isset($asset[12])?$asset[12]:0)+(isset($asset[13])?$asset[13]:0))
               
               ->setCellValue('B7', 'ACB (CMT)')
               ->setCellValue('C7', isset($asset[3])?$asset[3]:0)
               ->setCellValue('B8', 'ACB (CMG)')
               ->setCellValue('C8', isset($asset[4])?$asset[4]:0)
               ->setCellValue('B9', 'ACB_Oanh')
               ->setCellValue('C9', isset($asset[5])?$asset[5]:0)
               ->setCellValue('B10', 'ACB (TVCM)')
               ->setCellValue('C10', isset($asset[6])?$asset[6]:0)
               ->setCellValue('B11', 'Standard (USD)')
               ->setCellValue('C11', isset($asset[7])?$asset[7]:0)
               ->setCellValue('B12', 'Standard (VND)')
               ->setCellValue('C12', isset($asset[8])?$asset[8]:0)
               ->setCellValue('B13', 'BIDV')
               ->setCellValue('C13', isset($asset[9])?$asset[9]:0)
               ->setCellValue('B14', 'VCB')
               ->setCellValue('C14', isset($asset[10])?$asset[10]:0)
               ->setCellValue('B15', 'CMG (USD)')
               ->setCellValue('C15', isset($asset[11])?$asset[11]:0)
               ->setCellValue('B16', 'ACB_VietTrade (VND)')
               ->setCellValue('C16', isset($asset[12])?$asset[12]:0)
               ->setCellValue('B17', 'ACB_VietTrade (USD)')
               ->setCellValue('C17', isset($asset[13])?$asset[13]:0)
               ->setCellValue('A18', '4')
               ->setCellValue('B18', 'Phải thu')
               ->setCellValue('C18', $tongthuco)
               ->setCellValue('D18', $tongthuno);
               
            $objPHPExcel->getActiveSheet()->getStyle('A4:D6')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A18:D18')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );

               $hang = 19;
                $i=1;

            if ($customers) {

                

                foreach ($customers as $customer) {
                    if((isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]>0) || (isset($obtains['money'][$customer->customer_id]) && $obtains['money'][$customer->customer_id]<0)) {
                        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                         $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('B' . $hang, $customer->customer_name)
                            ->setCellValue('C' . $hang, $obtains['money'][$customer->customer_id]>0?$obtains['money'][$customer->customer_id]:null)
                            ->setCellValue('D' . $hang, $obtains['money'][$customer->customer_id]<0?str_replace('-', '', $obtains['money'][$customer->customer_id]):null);
                         $hang++;
                     }

                  }

            }
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $hang, 5)
                    ->setCellValue('B' . $hang, 'Phải trả')
                    ->setCellValue('C' . $hang, $tongtraco)
                    ->setCellValue('D' . $hang, $tongtrano);
                 $hang++;

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang-1).':D'.($hang-1))->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );

            $hangthunhat = $hang-1;

            if ($vendors) {

                

                foreach ($vendors as $vendor) {
                    if((isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0) || (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)){
                        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                         $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('B' . $hang, $vendor->shipment_vendor_name)
                            ->setCellValue('C' . $hang, $owe['money'][$vendor->shipment_vendor_id]<0?str_replace('-', '', $owe['money'][$vendor->shipment_vendor_id]):null)
                            ->setCellValue('D' . $hang, $owe['money'][$vendor->shipment_vendor_id]>0?$owe['money'][$vendor->shipment_vendor_id]:null);
                         $hang++;
                     }

                  }

            }
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $hang, 6)
                    ->setCellValue('B' . $hang, 'OPS')
                    ->setCellValue('C' . $hang, $tongopsco)
                    ->setCellValue('D' . $hang, $tongopsno);
                 $hang++;

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang-1).':D'.($hang-1))->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );

            $hangthuhai = $hang-1;

            if ($staffs) {

                

                foreach ($staffs as $staff) {
                    if((isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] != 0) || (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] != 0)){
                        if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] > 0) {
                            $co[$staff->staff_id] = isset($co[$staff->staff_id])?($co[$staff->staff_id] + $debt['co'][$staff->staff_id]):($debt['co'][$staff->staff_id]);
                        }
                        if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] < 0) {
                            $co[$staff->staff_id] = isset($co[$staff->staff_id])?($co[$staff->staff_id] + str_replace('-', "", $debt['no'][$staff->staff_id]) ):(str_replace('-', "", $debt['no'][$staff->staff_id]));
                        }

                        if (isset($debt['co'][$staff->staff_id]) && $debt['co'][$staff->staff_id] < 0) {
                            $no[$staff->staff_id] = isset($no[$staff->staff_id])?($no[$staff->staff_id] + str_replace('-', "", $debt['co'][$staff->staff_id]) ):(str_replace('-', "", $debt['co'][$staff->staff_id]));
                        }
                        if (isset($debt['no'][$staff->staff_id]) && $debt['no'][$staff->staff_id] > 0) {
                            $no[$staff->staff_id] = isset($no[$staff->staff_id])?($no[$staff->staff_id] + $debt['no'][$staff->staff_id]):($debt['no'][$staff->staff_id]);
                        }
                        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                         $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('B' . $hang, $staff->staff_name)
                            ->setCellValue('C' . $hang, isset($co[$staff->staff_id])?$co[$staff->staff_id]:null)
                            ->setCellValue('D' . $hang, isset($no[$staff->staff_id]) ?$no[$staff->staff_id]:null);
                         $hang++;
                     }

                  }

            }
            if ($vendors_ops) {

                

                foreach ($vendors_ops as $vendor) {
                    if((isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]<0) || (isset($owe['money'][$vendor->shipment_vendor_id]) && $owe['money'][$vendor->shipment_vendor_id]>0)){
                        
                        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                         $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('B' . $hang, $vendor->shipment_vendor_name)
                            ->setCellValue('C' . $hang, $owe['money'][$vendor->shipment_vendor_id]<0?str_replace('-', '', $owe['money'][$vendor->shipment_vendor_id]):null)
                            ->setCellValue('D' . $hang, $owe['money'][$vendor->shipment_vendor_id]>0?$owe['money'][$vendor->shipment_vendor_id]:null);
                         $hang++;
                     }

                  }

            }
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $hang, 7)
                    ->setCellValue('B' . $hang, 'Lương')
                    ->setCellValue('D' . $hang, $salary);
                 $hang++;

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang-1).':D'.($hang-1))->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );

            $hangthuba = $hang-1;

            if($salary != 0){
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $hang, 'Lương tháng')
                    ->setCellValue('D' . $hang, $salary);
                 $hang++;
             }

            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $hang, 'Tổng')
                    ->setCellValue('C' . $hang, '=C4+C5+C6+C16+C'.$hangthunhat.'+C'.$hangthuhai.'+C'.$hangthuba)
                    ->setCellValue('D' . $hang, '=D4+D5+D6+D16+D'.$hangthunhat.'+D'.$hangthuhai.'+D'.$hangthuba);
                 $hang++;

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang-1).':D'.($hang-1))->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                    )
                )
            );

            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $hang, 'Tổng tài sản')
                    ->setCellValue('C' . $hang, '=C'.($hang-1).'-D'.($hang-1));
                 $hang++;

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang-1).':D'.($hang-1))->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );


            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');

            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(18);

            $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('C4:C'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('D4:D'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('A3:E3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A3:E3')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A3:E3')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(28);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(6.5);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(51);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("CMG")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Report")
                            ->setSubject("Report")
                            ->setDescription("Report.")
                            ->setKeywords("Report")
                            ->setCategory("Report");
            $objPHPExcel->getActiveSheet()->setTitle("Tuan".$tuan);

            $objPHPExcel->getActiveSheet()->freezePane('A4');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BAO CAO TAI SAN TUAN ".$tuan.".xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        }
        
    }

}
?>