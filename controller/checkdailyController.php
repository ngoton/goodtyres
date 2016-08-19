<?php
Class checkdailyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo Thu chi ';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m-Y');
            $ngaytaobatdau = date('m-Y');
        }

        
        $daily_bank_model = $this->model->get('dailybankModel');
        $bank_model = $this->model->get('bankModel');

        $banks = $bank_model->getAllBank(array('where'=>'symbol IS NOT NULL'));
        $this->view->data['banks'] = $banks;

        $join = array('table'=>'bank','where'=>'bank=bank_id');

        $data_bank = array(
            'where' => 'daily_bank_date < '.strtotime($batdau),
        );
        $bank_dau = $daily_bank_model->getAllDaily($data_bank,$join);
        $tondau = array();
        foreach ($bank_dau as $ba) {
            $tondau[$ba->symbol] = isset($tondau[$ba->symbol])?$tondau[$ba->symbol]+$ba->money:$ba->money;
        }

        $data_bank = array(
            'where' => 'daily_bank_date >= '.strtotime($batdau).' AND daily_bank_date <= '.strtotime($ketthuc),
        );
        $bank_ps = $daily_bank_model->getAllDaily($data_bank,$join);
        $thu = array(); $chi = array();
        foreach ($bank_ps as $ba) {
            if ($ba->money > 0) {
                $thu[$ba->symbol] = isset($thu[$ba->symbol])?$thu[$ba->symbol]+$ba->money:$ba->money;
            }
            else{
                $chi[$ba->symbol] = isset($chi[$ba->symbol])?$chi[$ba->symbol]+$ba->money:$ba->money;
            }
        }

        $this->view->data['tondau'] = $tondau;
        $this->view->data['thu'] = $thu;
        $this->view->data['chi'] = $chi;

        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $account = $account_model->getAllAccount(array('where'=>'(account_parent = 1 OR account_parent = 4)'));
        
        $accounts = array();
        foreach ($account as $acc) {
            $tk = ($acc->account_number==1111 || $acc->account_number==1112)?'tm':strtolower(substr($acc->account_number, strpos($acc->account_number, '_')+1));
            $accounts[$tk] = $acc->account_number;
        }

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'(account_parent = 1 OR account_parent = 4) AND account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $tk = ($account->account_number==1111 || $account->account_number==1112)?'tm':strtolower(substr($account->account_number, strpos($account->account_number, '_')+1));
            $account_before[$tk] = isset($account_before[$tk])?$account_before[$tk]+$account->money:$account->money;
        }

        $data = array(
            'where'=>'(account_parent = 1 OR account_parent = 4) AND account_balance_date >= '.strtotime($batdau).' AND account_balance_date <= '.strtotime(date('d-m-Y',strtotime('+1 day',strtotime($ketthuc)))),
        );
        $account_balances = $account_balance_model->getAllAccount($data,$join);
        $account_add = array();
        $account_parent_add = array();

        foreach ($account_balances as $account) {
            $tk = ($account->account_number==1111 || $account->account_number==1112)?'tm':strtolower(substr($account->account_number, strpos($account->account_number, '_')+1));
            if ($account->money > 0) {
                $account_add[$tk]['no'] = isset($account_add[$tk]['no'])?$account_add[$tk]['no']+$account->money:$account->money;
            }
            else{
                $account_add[$tk]['co'] = isset($account_add[$tk]['co'])?$account_add[$tk]['co']+$account->money:$account->money;
            }
            
        }

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['accounts'] = $accounts;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('checkdaily/index');
    }

    

}
?>