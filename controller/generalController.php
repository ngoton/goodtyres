<?php
Class generalController Extends baseController {
    

    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tuan = isset($_POST['tuan']) ? $_POST['tuan'] : null;
            $nam_report = isset($_POST['nam']) ? $_POST['nam'] : null;
        }
        else{
            $dat = date('d-m-Y');
            $tuan = (int)date('W', strtotime($dat));
            $nam_report = (int)date('Y');
        }

        $mang = $this->getStartAndEndDate($tuan,$nam_report);
        $batdau = $mang[0];
        $ketthuc = $mang[1];
        
        $assets_model = $this->model->get('assetsModel');

            /***************/
            
            /***************************/
            $query = 'SELECT sale_report.code, obtain.customer, customer.customer_name, obtain.money AS customer_money, obtain.obtain_id, owe.vendor, shipment_vendor.shipment_vendor_name, owe.money AS vendor_money, owe.owe_id FROM obtain, sale_report, owe, customer, shipment_vendor WHERE obtain.sale_report=sale_report.sale_report_id AND owe.sale_report=sale_report.sale_report_id AND obtain.customer=customer.customer_id AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money >= 0 AND obtain.money >= 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY sale_report.code ASC';
            $sales = $assets_model->queryAssets($query);

            $query = 'SELECT sale_report.code, obtain.customer, customer.customer_name, obtain.money AS customer_money, obtain.obtain_id, owe.vendor, shipment_vendor.shipment_vendor_name, owe.money AS vendor_money, owe.owe_id FROM obtain, sale_report, owe, customer, shipment_vendor WHERE obtain.trading=sale_report.sale_report_id AND owe.trading=sale_report.sale_report_id AND obtain.customer=customer.customer_id AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money > 0 AND obtain.money > 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY sale_report.code ASC';
            $tradings = $assets_model->queryAssets($query);

            $query = 'SELECT agent.code, obtain.customer, customer.customer_name, obtain.money AS customer_money, obtain.obtain_id, owe.vendor, shipment_vendor.shipment_vendor_name, owe.money AS vendor_money, owe.owe_id FROM obtain, agent, owe, customer, shipment_vendor WHERE obtain.agent=agent.agent_id AND owe.agent=agent.agent_id AND obtain.customer=customer.customer_id AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money > 0 AND obtain.money > 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY agent.code ASC';
            $agents = $assets_model->queryAssets($query);

            $query = 'SELECT agent_manifest.code, obtain.customer, customer.customer_name, obtain.money AS customer_money, obtain.obtain_id, owe.vendor, shipment_vendor.shipment_vendor_name, owe.money AS vendor_money, owe.owe_id FROM obtain, agent_manifest, owe, customer, shipment_vendor WHERE obtain.agent_manifest=agent_manifest.agent_manifest_id AND owe.agent_manifest=agent_manifest.agent_manifest_id AND obtain.customer=customer.customer_id AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money > 0 AND obtain.money > 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY agent_manifest.code ASC';
            $agent_manifests = $assets_model->queryAssets($query);

            $query = 'SELECT invoice.invoice_number, obtain.customer, customer.customer_name, obtain.money AS customer_money, obtain.obtain_id, owe.vendor, shipment_vendor.shipment_vendor_name, owe.money AS vendor_money, owe.owe_id FROM obtain, invoice, owe, customer, shipment_vendor WHERE obtain.invoice=invoice.invoice_id AND owe.invoice=invoice.invoice_id AND obtain.customer=customer.customer_id AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money > 0 AND obtain.money > 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY invoice.invoice_number ASC';
            $invoices = $assets_model->queryAssets($query);

            $query = 'SELECT receivable.code, receivable.customer, customer.customer_name, assets.total, assets.bank, bank.bank_name FROM receivable, assets, customer, bank WHERE assets.receivable=receivable.receivable_id AND receivable.customer=customer.customer_id AND assets.bank=bank.bank_id AND assets.total > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY receivable.code ASC';
            $receivables = $assets_model->queryAssets($query);

            $query = 'SELECT obtain.customer, customer.customer_name, obtain.money FROM obtain, customer WHERE (obtain.sale_report IS NULL OR obtain.sale_report <= 0) AND (obtain.trading IS NULL OR obtain.trading <= 0) AND (obtain.agent IS NULL OR obtain.agent <= 0) AND (obtain.agent_manifest IS NULL OR obtain.agent_manifest <= 0) AND (obtain.invoice IS NULL OR obtain.invoice <= 0) AND obtain.customer=customer.customer_id AND obtain.money > 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' ORDER BY obtain.obtain_date ASC';
            $receivables_in = $assets_model->queryAssets($query);

            $query = 'SELECT receivable.code, receivable.staff, staff.staff_name, assets.total, assets.bank, bank.bank_name FROM receivable, assets, staff, bank WHERE assets.receivable=receivable.receivable_id AND receivable.staff=staff.staff_id AND assets.bank=bank.bank_id AND assets.total > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY receivable.code ASC';
            $receivables_staff = $assets_model->queryAssets($query);

            $query = 'SELECT receivable.code, receivable.vendor, shipment_vendor.shipment_vendor_name, assets.total, assets.bank, bank.bank_name FROM receivable, assets, shipment_vendor, bank WHERE assets.receivable=receivable.receivable_id AND receivable.vendor=shipment_vendor.shipment_vendor_id AND assets.bank=bank.bank_id AND assets.total > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY receivable.code ASC';
            $receivables_vendor = $assets_model->queryAssets($query);

            // $query = 'SELECT obtain.customer, customer.customer_name, obtain.money FROM obtain, customer WHERE (obtain.sale_report IS NULL OR obtain.sale_report <= 0) AND (obtain.trading IS NULL OR obtain.trading <= 0) AND (obtain.agent IS NULL OR obtain.agent <= 0) AND (obtain.agent_manifest IS NULL OR obtain.agent_manifest <= 0) AND (obtain.invoice IS NULL OR obtain.invoice <= 0) AND obtain.customer=customer.customer_id AND obtain.money < 0 AND obtain.week = '.($tuan).' AND obtain.year = '.$nam_report.' ORDER BY obtain.obtain_date ASC';
            // $receivables_out = $assets_model->queryAssets($query);

            $query = 'SELECT payable.code, payable.vendor, shipment_vendor.shipment_vendor_name, assets.total, assets.bank, bank.bank_name FROM payable, assets, shipment_vendor, bank WHERE assets.payable=payable.payable_id AND payable.vendor=shipment_vendor.shipment_vendor_id AND assets.bank=bank.bank_id AND assets.total < 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY payable.code ASC';
            $payables = $assets_model->queryAssets($query);

            $query = 'SELECT owe.vendor, shipment_vendor.shipment_vendor_name, owe.money FROM owe, shipment_vendor WHERE (owe.sale_report IS NULL OR owe.sale_report <= 0) AND (owe.trading IS NULL OR owe.trading <= 0) AND (owe.agent IS NULL OR owe.agent <= 0) AND (owe.agent_manifest IS NULL OR owe.agent_manifest <= 0) AND (owe.invoice IS NULL OR owe.invoice <= 0) AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money > 0 AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY owe.owe_date ASC';
            $payables_in = $assets_model->queryAssets($query);

            $query = 'SELECT payable.code, payable.customer, customer.customer_name, assets.total, assets.bank, bank.bank_name FROM payable, assets, customer, bank WHERE assets.payable=payable.payable_id AND payable.customer=customer.customer_id AND assets.bank=bank.bank_id AND assets.total < 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY payable.code ASC';
            $payables_customer = $assets_model->queryAssets($query);

            // $query = 'SELECT owe.vendor, shipment_vendor.shipment_vendor_name, owe.money FROM owe, shipment_vendor WHERE (owe.sale_report IS NULL OR owe.sale_report <= 0) AND (owe.trading IS NULL OR owe.trading <= 0) AND (owe.agent IS NULL OR owe.agent <= 0) AND (owe.agent_manifest IS NULL OR owe.agent_manifest <= 0) AND (owe.invoice IS NULL OR owe.invoice <= 0) AND owe.vendor=shipment_vendor.shipment_vendor_id AND owe.money < 0 AND owe.week = '.($tuan).' AND owe.year = '.$nam_report.' ORDER BY owe.owe_date ASC';
            // $payables_out = $assets_model->queryAssets($query);
            
            $query = 'SELECT advance.staff, staff.staff_name, assets.total, assets.bank, bank.bank_name FROM advance, staff, assets, bank WHERE assets.advance=advance.advance_id AND advance.staff=staff.staff_id AND assets.bank=bank.bank_id AND assets.total < 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY advance.advance_id ASC';
            $advances = $assets_model->queryAssets($query);

            $query = 'SELECT costs.code, costs.comment, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND costs.source=assets.bank AND assets.bank=bank.bank_id AND (costs.money_in = 0 OR costs.money_in IS NULL) AND (source_in IS NULL OR source_in <= 0) AND (staff IS NULL OR staff <= 0) AND assets.total < 0 AND assets.bank > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY assets.assets_date ASC';
            $costs = $assets_model->queryAssets($query);

            $query = 'SELECT costs.code, costs.comment, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND costs.source_in=assets.bank AND assets.bank=bank.bank_id AND costs.money = 0 AND costs.money_in > 0 AND source_in > 0 AND assets.total > 0 AND assets.bank > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY assets.assets_date ASC';
            $costs_in = $assets_model->queryAssets($query);

            $query = 'SELECT costs.staff, staff.staff_name, assets.total, assets.bank, bank.bank_name FROM costs, staff, assets, bank WHERE assets.costs=costs.costs_id AND costs.staff=staff.staff_id AND assets.bank=bank.bank_id AND costs.staff_cost > 0 AND staff > 0 AND assets.total < 0 AND assets.bank > 0 AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY assets.assets_date ASC';
            $costs_staff = $assets_model->queryAssets($query);

            // $query = 'SELECT costs.code, costs.comment, assets.total, costs.source, costs.source_in, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND costs.source_in=assets.bank AND assets.bank=bank.bank_id AND source > 0 AND  source_in > 0 AND assets.total > 0  AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY costs.costs_id ASC';
            // $transfers_in = $assets_model->queryAssets($query);

            // $query = 'SELECT costs.code, costs.comment, assets.total, costs.source, costs.source_in, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND costs.source=assets.bank AND assets.bank=bank.bank_id AND source > 0 AND  source_in > 0 AND assets.total < 0  AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY costs.costs_id ASC';
            // $transfers_out = $assets_model->queryAssets($query);

            $query = 'SELECT costs.costs_id, costs.code, costs.comment, assets.total, costs.source, costs.source_in, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id AND source > 0 AND  source_in > 0 AND costs.money > 0 AND costs.money_in > 0 AND  assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY costs.costs_id ASC';
            $transfers = $assets_model->queryAssets($query);

            $query = 'SELECT assets.total, assets.bank, bank.bank_name FROM bank, assets WHERE assets.bank=bank.bank_id AND  assets.total > 0 AND (assets.receipts IS NULL OR assets.receipts <= 0) AND (assets.office IS NULL OR assets.office <= 0) AND (assets.spent IS NULL OR assets.spent <= 0) AND (assets.advance IS NULL OR assets.advance <= 0) AND (assets.costs IS NULL OR assets.costs <= 0) AND (assets.payable IS NULL OR assets.payable <= 0) AND (assets.receivable IS NULL OR assets.receivable <= 0)  AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY assets.assets_date ASC';
            $assets_in = $assets_model->queryAssets($query);

            $query = 'SELECT assets.total, assets.bank, bank.bank_name FROM bank, assets WHERE assets.bank=bank.bank_id AND  assets.total < 0 AND (assets.receipts IS NULL OR assets.receipts <= 0) AND (assets.office IS NULL OR assets.office <= 0) AND (assets.spent IS NULL OR assets.spent <= 0) AND (assets.advance IS NULL OR assets.advance <= 0) AND (assets.costs IS NULL OR assets.costs <= 0) AND (assets.payable IS NULL OR assets.payable <= 0) AND (assets.receivable IS NULL OR assets.receivable <= 0)  AND assets.week = '.($tuan).' AND assets.year = '.$nam_report.' ORDER BY assets.assets_date ASC';
            $assets_out = $assets_model->queryAssets($query);

            $luong = null;

        /*$q_luong = 'SELECT * FROM costs, assets WHERE costs.costs_id = assets.costs AND check_salary=1 AND (assets.week = '.($tuan+1).' AND assets.year = '.$nam_report.')';
        $luongs = $assets_model->queryAssets($q_luong);
        $luong_datra = 0;

        foreach ($luongs as $luongthang) {
            $luong += str_replace('-', "", $luongthang->total);
        }*/

        /*$thang = date('m',strtotime($ketthuc));
        $nam = date('Y',strtotime($ketthuc));
        $ngay = '05-'.$thang.'-'.$nam;
        $ngay2 = '08-'.$thang.'-'.$nam;

        $luong = null;

        if((strtotime($ngay)<=strtotime($ketthuc) && strtotime($ngay)>=strtotime($batdau)) || (strtotime($ngay2)<=strtotime($ketthuc) && strtotime($ngay2)>=strtotime($batdau))){
            $lthang = $thang-1;
            $lnam = $nam;
            if ($thang == 1) {
                $lthang = 12;
                $lnam = $nam-1;
            }
            $salary_model = $this->model->get('newsalaryModel');
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



         $ngay = '15-'.$thang.'-'.$nam;

        if(strtotime($ngay)<=strtotime($ketthuc) && strtotime($ngay)>=strtotime($batdau)){
            $luong = null;
        }*/


        $this->view->data['sales'] = $sales;
        $this->view->data['tradings'] = $tradings;
        $this->view->data['agents'] = $agents;
        $this->view->data['agent_manifests'] = $agent_manifests;
        $this->view->data['invoices'] = $invoices;
        $this->view->data['receivables'] = $receivables;
        $this->view->data['payables'] = $payables;
        $this->view->data['costs'] = $costs;
        $this->view->data['costs_in'] = $costs_in;
        $this->view->data['luong'] = $luong;
        $this->view->data['receivables_in'] = $receivables_in;
        $this->view->data['payables_in'] = $payables_in;
        $this->view->data['assets_out'] = $assets_out;
        $this->view->data['assets_in'] = $assets_in;
        $this->view->data['receivables_staff'] = $receivables_staff;
        $this->view->data['advances'] = $advances;
        $this->view->data['costs_staff'] = $costs_staff;
        $this->view->data['transfers'] = $transfers;
        $this->view->data['receivables_vendor'] = $receivables_vendor;
        $this->view->data['payables_customer'] = $payables_customer;

        $this->view->data['tuan'] = $tuan;
        $this->view->data['nam'] = $nam_report;
        
        $this->view->show('general/index');
    }

   
    function getStartAndEndDate($week, $year)
    {
        $week = $week-1;
        $time = strtotime('01-01-'.$year, time());
        $day = date('w', $time);
        $time += ((7*$week)+1-$day)*24*3600;
        $return[0] = date('d-m-Y', $time);
        $time += 6*24*3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }

    

}
?>