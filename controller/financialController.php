<?php
Class financialController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $batdau = date('d-m-Y', strtotime('last Sunday',strtotime(date('d-m-Y'))));
            $ketthuc = date('d-m-Y', strtotime('next Sunday',strtotime(date('d-m-Y'))));
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('d-m-Y',$this->registry->router->param_id);
            $ketthuc = date('d-m-Y',$this->registry->router->page);
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));


        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0)','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account_model->getAllAccount(array('where'=>'account_parent='.$account->account_id,'order_by'=>'account_number','order'=>'ASC'));
        }

        $this->view->data['accounts'] = $accounts;
        $this->view->data['account_parents'] = $account_parents;

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $account_before[$account->account] = isset($account_before[$account->account])?$account_before[$account->account]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_before[$account->account_parent] = isset($account_parent_before[$account->account_parent])?$account_parent_before[$account->account_parent]+$account->money:$account->money;
            }
            else{
                $account_parent_before[$account->account] = isset($account_parent_before[$account->account])?$account_parent_before[$account->account]+$account->money:$account->money;
            }
        }

        $data = array(
            'where'=>'account_balance_date >= '.strtotime($batdau).' AND account_balance_date <= '.strtotime($ketthuc),
        );
        $account_balances = $account_balance_model->getAllAccount($data,$join);
        $account_add = array();
        $account_parent_add = array();

        foreach ($account_balances as $account) {
            if ($account->money > 0) {
                $account_add[$account->account]['no'] = isset($account_add[$account->account]['no'])?$account_add[$account->account]['no']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account->account_parent]['no'] = isset($account_parent_add[$account->account_parent]['no'])?$account_parent_add[$account->account_parent]['no']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account]['no'] = isset($account_parent_add[$account->account]['no'])?$account_parent_add[$account->account]['no']+$account->money:$account->money;
                }
            }
            else{
                $account_add[$account->account]['co'] = isset($account_add[$account->account]['co'])?$account_add[$account->account]['co']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account->account_parent]['co'] = isset($account_parent_add[$account->account_parent]['co'])?$account_parent_add[$account->account_parent]['co']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account]['co'] = isset($account_parent_add[$account->account]['co'])?$account_parent_add[$account->account]['co']+$account->money:$account->money;
                }
            }
            
        }

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['account_parent_before'] = $account_parent_before;
        $this->view->data['account_parent_add'] = $account_parent_add;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('financial/index');
    }

    public function week() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $batdau = date('d-m-Y', strtotime('last Sunday',strtotime(date('d-m-Y'))));
            $ketthuc = date('d-m-Y', strtotime('next Sunday',strtotime(date('d-m-Y'))));
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('d-m-Y',$this->registry->router->param_id);
            $ketthuc = date('d-m-Y',$this->registry->router->page);
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0)','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account_model->getAllAccount(array('where'=>'account_parent='.$account->account_id,'order_by'=>'account_number','order'=>'ASC'));
        }

        $this->view->data['accounts'] = $accounts;
        $this->view->data['account_parents'] = $account_parents;

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $account_before[$account->account] = isset($account_before[$account->account])?$account_before[$account->account]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_before[$account->account_parent] = isset($account_parent_before[$account->account_parent])?$account_parent_before[$account->account_parent]+$account->money:$account->money;
            }
            else{
                $account_parent_before[$account->account] = isset($account_parent_before[$account->account])?$account_parent_before[$account->account]+$account->money:$account->money;
            }
        }

        $account_add = array();
        $account_parent_add = array();

        $number = $this->datediffInWeeks($batdau, $ketthuc);
        $tuan = date('W',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        for ($i=0; $i <= $number; $i++) { 
            $t = ($tuan+$i)>52?($tuan+$i-52):$tuan+$i;
            $n = ($tuan+$i)>52?($nam+1):$nam;

            $data = array(
                'where' => 'account_balance_date >= '.strtotime($batdau).' AND account_balance_date <= '.strtotime($ketthuc).' AND week = '.$t.' AND year = '.$n,
            );
            $account_balances = $account_balance_model->getAllAccount($data,$join);
            
            foreach ($account_balances as $account) {
                if ($account->money > 0) {
                    $account_add[$t][$n][$account->account]['no'] = isset($account_add[$t][$n][$account->account]['no'])?$account_add[$t][$n][$account->account]['no']+$account->money:$account->money;
                    if ($account->account_parent > 0) {
                        $account_parent_add[$t][$n][$account->account_parent]['no'] = isset($account_parent_add[$t][$n][$account->account_parent]['no'])?$account_parent_add[$t][$n][$account->account_parent]['no']+$account->money:$account->money;
                    }
                    else{
                        $account_parent_add[$t][$n][$account->account]['no'] = isset($account_parent_add[$t][$n][$account->account]['no'])?$account_parent_add[$t][$n][$account->account]['no']+$account->money:$account->money;
                    }
                }
                else{
                    $account_add[$t][$n][$account->account]['co'] = isset($account_add[$t][$n][$account->account]['co'])?$account_add[$t][$n][$account->account]['co']+$account->money:$account->money;
                    if ($account->account_parent > 0) {
                        $account_parent_add[$t][$n][$account->account_parent]['co'] = isset($account_parent_add[$t][$n][$account->account_parent]['co'])?$account_parent_add[$t][$n][$account->account_parent]['co']+$account->money:$account->money;
                    }
                    else{
                        $account_parent_add[$t][$n][$account->account]['co'] = isset($account_parent_add[$t][$n][$account->account]['co'])?$account_parent_add[$t][$n][$account->account]['co']+$account->money:$account->money;
                    }
                }
                
            }
        }

        

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['account_parent_before'] = $account_parent_before;
        $this->view->data['account_parent_add'] = $account_parent_add;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['number'] = $number;
        $this->view->data['tuan'] = $tuan;
        $this->view->data['nam'] = $nam;
        
        $this->view->show('financial/week');
    }

    public function month() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo tài chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $batdau = date('m-Y', strtotime('last month',strtotime(date('d-m-Y'))));
            $ketthuc = date('m-Y');
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('m-Y',$this->registry->router->param_id);
            $ketthuc = date('m-Y',$this->registry->router->page);
            if ($batdau == $ketthuc) {
                $ketthuc = date('m-Y', strtotime("+1 month", $this->registry->router->page));
            }
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0)','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account_model->getAllAccount(array('where'=>'account_parent='.$account->account_id,'order_by'=>'account_number','order'=>'ASC'));
        }

        $this->view->data['accounts'] = $accounts;
        $this->view->data['account_parents'] = $account_parents;

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $account_before[$account->account] = isset($account_before[$account->account])?$account_before[$account->account]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_before[$account->account_parent] = isset($account_parent_before[$account->account_parent])?$account_parent_before[$account->account_parent]+$account->money:$account->money;
            }
            else{
                $account_parent_before[$account->account] = isset($account_parent_before[$account->account])?$account_parent_before[$account->account]+$account->money:$account->money;
            }
        }

        $account_add = array();
        $account_parent_add = array();

        $thang = date('m',strtotime('01-'.$batdau));
        $nam = date('Y',strtotime('01-'.$batdau));

        $thang2 = date('m',strtotime('01-'.$ketthuc));
        $nam2 = date('Y',strtotime('01-'.$ketthuc));

        if ($nam == $nam2) {
            $number = $thang2 - $thang;
        }
        else{
            $sonam = $nam2-$nam;
            if ($sonam > 1) {
                $l = $thang2+(12*$sonam);
            }
            else{
                $l = $thang2;
            }
            $f = 12 - $thang;
            $number = $f+$l;
        }

        for ($i=0; $i <= $number; $i++) { 
            $t = ($thang+$i)>12?($thang+$i-12):$thang+$i;
            $n = ($thang+$i)>12?($nam+1):$nam;

            $bd = '01-'.$t.'-'.$n;
            $kt = date('t-m-Y',strtotime($bd));

            $data = array(
                'where' => 'account_balance_date >= '.strtotime($bd).' AND account_balance_date <= '.strtotime($kt),
            );
            $account_balances = $account_balance_model->getAllAccount($data,$join);
            
            foreach ($account_balances as $account) {
                if ($account->money > 0) {
                    $account_add[$t][$n][$account->account]['no'] = isset($account_add[$t][$n][$account->account]['no'])?$account_add[$t][$n][$account->account]['no']+$account->money:$account->money;
                    if ($account->account_parent > 0) {
                        $account_parent_add[$t][$n][$account->account_parent]['no'] = isset($account_parent_add[$t][$n][$account->account_parent]['no'])?$account_parent_add[$t][$n][$account->account_parent]['no']+$account->money:$account->money;
                    }
                    else{
                        $account_parent_add[$t][$n][$account->account]['no'] = isset($account_parent_add[$t][$n][$account->account]['no'])?$account_parent_add[$t][$n][$account->account]['no']+$account->money:$account->money;
                    }
                }
                else{
                    $account_add[$t][$n][$account->account]['co'] = isset($account_add[$t][$n][$account->account]['co'])?$account_add[$t][$n][$account->account]['co']+$account->money:$account->money;
                    if ($account->account_parent > 0) {
                        $account_parent_add[$t][$n][$account->account_parent]['co'] = isset($account_parent_add[$t][$n][$account->account_parent]['co'])?$account_parent_add[$t][$n][$account->account_parent]['co']+$account->money:$account->money;
                    }
                    else{
                        $account_parent_add[$t][$n][$account->account]['co'] = isset($account_parent_add[$t][$n][$account->account]['co'])?$account_parent_add[$t][$n][$account->account]['co']+$account->money:$account->money;
                    }
                }
                
            }
        }

        

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['account_parent_before'] = $account_parent_before;
        $this->view->data['account_parent_add'] = $account_parent_add;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['number'] = $number;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        
        $this->view->show('financial/month');
    }

    public function result() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kết quả hoạt động kinh doanh';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $tha = (int)date('m');
            $na = date('Y');
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('d-m-Y',$this->registry->router->param_id);
            $ketthuc = date('d-m-Y',$this->registry->router->page);
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $tha = (int)date('m',strtotime($batdau));
        $na = date('Y',strtotime($batdau));

        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $tire_sale_model = $this->model->get('tiresaleModel');
        $sales = $tire_sale_model->queryTire('SELECT SUM(volume) AS soluong FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc));
        $total_sale = 0;
        foreach ($sales as $sale) {
            $total_sale = $sale->soluong;
        }
        $this->view->data['total_sale'] = $total_sale;

        $account_model = $this->model->get('accountModel');

        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0)','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account->account_number;
        }

        $account_balance_model = $this->model->get('accountbalanceModel');

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'(account_balance_type IS NULL) AND account_balance_date < '.strtotime($batdau),
        );
        $account_balance_befores = $account_balance_model->getAllAccount($data,$join);
        $account_before = array();
        $account_parent_before = array();

        foreach ($account_balance_befores as $account) {
            $account_before[$account->account_number] = isset($account_before[$account->account_number])?$account_before[$account->account_number]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_before[$account_parents[$account->account_parent]] = isset($account_parent_before[$account_parents[$account->account_parent]])?$account_parent_before[$account_parents[$account->account_parent]]+$account->money:$account->money;
            }
            else{
                $account_parent_before[$account->account_number] = isset($account_parent_before[$account->account_number])?$account_parent_before[$account->account_number]+$account->money:$account->money;
            }
        }

        $data = array(
            'where'=>'(account_balance_type IS NULL) AND account_balance_date >= '.strtotime($batdau).' AND account_balance_date <= '.strtotime($ketthuc),
        );
        $account_balances = $account_balance_model->getAllAccount($data,$join);
        $account_add = array();
        $account_parent_add = array();

        foreach ($account_balances as $account) {
            if ($account->money > 0) {
                $account_add[$account->account_number]['no'] = isset($account_add[$account->account_number]['no'])?$account_add[$account->account_number]['no']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account_parents[$account->account_parent]]['no'] = isset($account_parent_add[$account_parents[$account->account_parent]]['no'])?$account_parent_add[$account_parents[$account->account_parent]]['no']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account_number]['no'] = isset($account_parent_add[$account->account_number]['no'])?$account_parent_add[$account->account_number]['no']+$account->money:$account->money;
                }
            }
            else{
                $account_add[$account->account_number]['co'] = isset($account_add[$account->account_number]['co'])?$account_add[$account->account_number]['co']+$account->money:$account->money;
                if ($account->account_parent > 0) {
                    $account_parent_add[$account_parents[$account->account_parent]]['co'] = isset($account_parent_add[$account_parents[$account->account_parent]]['co'])?$account_parent_add[$account_parents[$account->account_parent]]['co']+$account->money:$account->money;
                }
                else{
                    $account_parent_add[$account->account_number]['co'] = isset($account_parent_add[$account->account_number]['co'])?$account_parent_add[$account->account_number]['co']+$account->money:$account->money;
                }
            }
            
        }

        $accounts = $account_model->getAllAccount();
        $account_num = array();
        foreach ($accounts as $acc) {
            $account_num[$acc->account_number] = $acc->account_id;
        }
        $this->view->data['account_num'] = $account_num;

        $this->view->data['account_before'] = $account_before;
        $this->view->data['account_add'] = $account_add;
        $this->view->data['account_parent_before'] = $account_parent_before;
        $this->view->data['account_parent_add'] = $account_parent_add;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('financial/result');
    }

    public function balance() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng cân đối tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $tha = (int)date('m');
            $na = date('Y');
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('d-m-Y',$this->registry->router->param_id);
            $ketthuc = date('d-m-Y',$this->registry->router->page);
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $tha = (int)date('m',strtotime($batdau));
        $na = date('Y',strtotime($batdau));

        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0) AND account_number < 511','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account_model->getAllAccount(array('where'=>'account_parent='.$account->account_id,'order_by'=>'account_number','order'=>'ASC'));
        }

        $this->view->data['accounts'] = $accounts;
        $this->view->data['account_parents'] = $account_parents;

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date <= '.strtotime($ketthuc),
        );
        $account_balance_afters = $account_balance_model->getAllAccount($data,$join);
        $account_after = array();
        $account_parent_after = array();

        foreach ($account_balance_afters as $account) {
            $account_after[$account->account] = isset($account_after[$account->account])?$account_after[$account->account]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_after[$account->account_parent] = isset($account_parent_after[$account->account_parent])?$account_parent_after[$account->account_parent]+$account->money:$account->money;
            }
            else{
                $account_parent_after[$account->account] = isset($account_parent_after[$account->account])?$account_parent_after[$account->account]+$account->money:$account->money;
            }
        }


        $this->view->data['account_after'] = $account_after;
        $this->view->data['account_parent_after'] = $account_parent_after;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('financial/balance');
    }

    public function asset() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng cân đối tài sản';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $tha = (int)date('m');
            $na = date('Y');
        }

        if ($this->registry->router->param_id != "" && $this->registry->router->page != "") {
            $batdau = date('d-m-Y',$this->registry->router->param_id);
            $ketthuc = date('d-m-Y',$this->registry->router->page);
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $tha = (int)date('m',strtotime($batdau));
        $na = date('Y',strtotime($batdau));

        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $account_model = $this->model->get('accountModel');
        $account_balance_model = $this->model->get('accountbalanceModel');
        $accounts = $account_model->getAllAccount(array('where'=>'(account_parent IS NULL OR account_parent = 0) AND account_number < 352','order_by'=>'account_number','order'=>'ASC'));
        $account_parents = array();
        foreach ($accounts as $account) {
            $account_parents[$account->account_id] = $account_model->getAllAccount(array('where'=>'account_parent='.$account->account_id,'order_by'=>'account_number','order'=>'ASC'));
        }

        $this->view->data['accounts'] = $accounts;
        $this->view->data['account_parents'] = $account_parents;

        $join = array('table'=>'account','where'=>'account=account_id');
        $data = array(
            'where'=>'account_balance_date <= '.strtotime($ketthuc),
        );
        $account_balance_afters = $account_balance_model->getAllAccount($data,$join);
        $account_after = array();
        $account_parent_after = array();

        foreach ($account_balance_afters as $account) {
            $account_after[$account->account] = isset($account_after[$account->account])?$account_after[$account->account]+$account->money:$account->money;
            if ($account->account_parent > 0) {
                $account_parent_after[$account->account_parent] = isset($account_parent_after[$account->account_parent])?$account_parent_after[$account->account_parent]+$account->money:$account->money;
            }
            else{
                $account_parent_after[$account->account] = isset($account_parent_after[$account->account])?$account_parent_after[$account->account]+$account->money:$account->money;
            }
        }


        $this->view->data['account_after'] = $account_after;
        $this->view->data['account_parent_after'] = $account_parent_after;

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('financial/asset');
    }

    public function office() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'additional_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $tha = (int)date('m');
            $na = date('Y');
        }

        $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

        $tha = (int)date('m',strtotime($batdau));
        $na = date('Y',strtotime($batdau));

        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $id = $this->registry->router->param_id;

        $additional_model = $this->model->get('additionalModel');

        $account_model = $this->model->get('accountModel');

        $account_parents = $account_model->getAllAccount(array('order_by'=>'account_number ASC'));
        $account_data = array();
        $id_office = "";
        foreach ($account_parents as $account_parent) {
            $account_data[$account_parent->account_id] = $account_parent->account_number;
            if ($account_parent->account_number == 642) {
                $id_office = $account_parent->account_id;
            }
        }
        $this->view->data['account_data'] = $account_data;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'debit IN (SELECT account_id FROM account WHERE account_parent = '.$id_office.')  AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
        );

        if ($id>0) {
            $data['where'] = 'additional_id = '.$id;
        }
        
        
        $tongsodong = count($additional_model->getAllAdditional($data));
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
            'where' => 'debit IN (SELECT account_id FROM account WHERE account_parent = '.$id_office.')  AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
            );
        
        if ($id>0) {
            $data['where'] = 'additional_id = '.$id;
        }
      
        if ($keyword != '') {
            $search = '(
                    OR additional_comment LIKE "%'.$keyword.'%" 
                    OR code LIKE "%'.$keyword.'%" 
                    OR money LIKE "%'.$keyword.'%" 
                )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['additionals'] = $additional_model->getAllAdditional($data);
        $this->view->data['lastID'] = isset($additional_model->getLastAdditional()->additional_id)?$additional_model->getLastAdditional()->additional_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('financial/office');
    }

    public function getAdditional(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $account = isset($_POST['account']) ? $_POST['account'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;

            $kt = date('d-m-Y',strtotime('+1 day', strtotime($ketthuc)));

            $additional_model = $this->model->get('additionalModel');

            $data = array(
                'where' => 'debit NOT IN (SELECT account_id FROM account WHERE account_number="911") AND credit NOT IN (SELECT account_id FROM account WHERE account_number="911") AND (debit = '.$account.' OR credit = '.$account.') AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
                'order_by' => 'additional_date',
                'order' => 'ASC',
            );
            $additionals = $additional_model->getAllAdditional($data);

            $tr = "";
            $tong = 0;
            if ($additionals) {
                $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th><th>Code</th></tr></thead><tbody>';
                foreach ($additionals as $v) {
                    $total = $v->debit==$account?$v->money:0-$v->money;
                    $tr.= '<tr>';
                    $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$this->lib->hien_thi_ngay_thang($v->additional_date).'</a></td>';
                    $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$v->additional_comment.'</a></td>';
                    $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$this->lib->formatMoney($total).'</a></td>';
                    $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$v->code.'</a></td>';
                    $tr.= '</tr>';
                    $tong += $total;
                }
                $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td><td></td></tr></tfoot>';
                $tr.= "</tbody></table>";
            }
            else{
                $data = array(
                    'where' => '( debit IN (SELECT account_id FROM account WHERE account_parent = '.$account.') OR credit IN (SELECT account_id FROM account WHERE account_parent = '.$account.') ) AND additional_date >= '.strtotime($batdau).' AND additional_date <= '.strtotime($ketthuc),
                    'order_by' => 'additional_date',
                    'order' => 'ASC',
                );
                $additionals = $additional_model->getAllAdditional($data);

                if ($additionals) {
                    $tr .= '<table class="table_data"><thead><tr><th>Ngày</th><th>ND</th><th>Số tiền</th><th>Code</th></tr></thead><tbody>';
                    foreach ($additionals as $v) {
                        $total = $v->debit==$account?$v->money:0-$v->money;
                        $tr.= '<tr>';
                        $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$this->lib->hien_thi_ngay_thang($v->additional_date).'</a></td>';
                        $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$v->additional_comment.'</a></td>';
                        $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$this->lib->formatMoney($total).'</a></td>';
                        $tr.= '<td><a target="_blank" href="'.BASE_URL.'/additional/index/'.$v->additional_id.'">'.$v->code.'</a></td>';
                        $tr.= '</tr>';
                        $tong += $total;
                    }
                    $tr.= '<tfoot><tr style="color:red"><td colspan="2">Tổng cộng</td><td>'.$this->lib->formatMoney($tong).'</td><td></td></tr></tfoot>';
                    $tr.= "</tbody></table>";
                }
            }
            echo $tr;
        }
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
    function datediffInWeeks($from, $to)
    {
        $w1 = date('W',strtotime($from));
        $w2 = date('W',strtotime($to));
        if ($w2 >= $w1) {
            $diff = $w2 - $w1;
        }
        else{
            $f = 52-$w1;
            $l = $w2;
            $diff = $f+$l;
        }
        return $diff;
    }



}
?>