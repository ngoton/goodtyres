<?php
Class moneyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=10) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Báo cáo dòng tiền';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $ngay = date('d-m-Y');
            $batdau = (int)date('W', strtotime($ngay));
            $ketthuc = date('Y');
        }
        
        if(isset($this->registry->router->param_id) && isset($this->registry->router->page)){
            $batdau = $this->registry->router->param_id;
            $ketthuc = $this->registry->router->page;
        }

        $mang = $this->getStartAndEndDate($batdau,$ketthuc);
        $ngaybatdau = $mang[0];
        $ngayketthuc = $mang[1];


        $assets_model = $this->model->get('assetsModel');
        $payable = $this->model->get('payableModel');
        $receivable = $this->model->get('receivableModel');
        $pay = $this->model->get('payModel');
        $receive = $this->model->get('receiveModel');
        $cost = $this->model->get('costsModel');

        $where = array(
            'where' => '( (week <= '.$batdau.' AND year = '.$ketthuc.') OR (week <= 53 AND year < '.$ketthuc.') )',
        );
        $assets = $assets_model->getAllAssets($where);
        $dongtien = array();

        foreach ($assets as $bank) {
            $dongtien[$batdau] = isset($dongtien[$batdau])?($dongtien[$batdau]+$bank->total):(0+$bank->total);
        }
        
        $first_day = date('d-m-Y', strtotime(date('d-m-Y'). ' - 90 days'));

        $payable_data = array();
        $receivable_data = array();
        $pay_data = array();
        $receive_data = array();
        $costs_data = array();

        $payables = $payable->getAllCosts(array('where'=>'week >= '.$batdau.' AND year = '.$ketthuc));
        $receivables = $receivable->getAllCosts(array('where'=>'( ( (week >= '.$batdau.' AND year = '.$ketthuc.') AND (new_expect_date <= 0 OR new_expect_date IS NULL) ) OR (new_week >= '.$batdau.' AND new_year = '.$ketthuc.') )'));
        $costs = $cost->getAllCosts(array('where'=>'(money_in IS NULL OR money_in = 0) AND week >= '.$batdau.' AND year = '.$ketthuc));

        $payables_old = $payable->getAllCosts(array('where'=>'(money != pay_money OR pay_money IS NULL) AND ( (week < '.$batdau.' AND year = '.$ketthuc.') OR (week <= 53 AND year < '.$ketthuc.') )'));
        $receivables_old = $receivable->getAllCosts(array('where'=>'(money != pay_money OR pay_money IS NULL) AND ( ( ( (week < '.$batdau.' AND year = '.$ketthuc.') OR (week <= 53 AND year < '.$ketthuc.') ) AND expect_date > '.strtotime($first_day).' AND (new_expect_date <= 0 OR new_expect_date IS NULL) ) OR ( ( (new_week < '.$batdau.' AND new_year = '.$ketthuc.') OR (new_week <= 53 AND new_year < '.$ketthuc.') ) AND new_expect_date > '.strtotime($first_day).' ) )'));
        $costs_old = $cost->getAllCosts(array('where'=>'(money != pay_money OR pay_money IS NULL) AND (money_in IS NULL OR money_in = 0) AND  ( (week < '.$batdau.' AND year = '.$ketthuc.') OR (week <= 53 AND year < '.$ketthuc.') )'));

        $pays_payable = $pay->queryCosts('SELECT type, assets.week, assets.total FROM payable, assets WHERE assets.payable = payable.payable_id  AND assets.week >= '.$batdau.' AND assets.year = '.$ketthuc);
        $pays_advance = $pay->queryCosts('SELECT assets.week, assets.total FROM advance, assets WHERE assets.advance = advance.advance_id  AND assets.week >= '.$batdau.' AND assets.year = '.$ketthuc);
        $pays_costs = $pay->queryCosts('SELECT * FROM costs, assets WHERE assets.costs = costs.costs_id AND (money_in IS NULL OR money_in = 0)  AND assets.week >= '.$batdau.' AND assets.year = '.$ketthuc);

        $receives = $receive->queryCosts('SELECT type, assets.week, assets.total, assets.receivable FROM receivable, assets WHERE assets.receivable = receivable.receivable_id AND assets.week >= '.$batdau.' AND assets.year = '.$ketthuc);

        $duthu = array();
        $dathu = array();

        $chiconlai = 0;
        $thuconlai = 0;
        $costconlai = 0;
        // Du chi
        foreach ($payables as $p) {
            $payable_data[$p->week][$p->type] = isset($payable_data[$p->week][$p->type])?($payable_data[$p->week][$p->type]+($p->money-$p->pay_money)):($p->money-$p->pay_money);
        }
        foreach ($payables_old as $po) {
            $payable_data[$batdau][$po->type] = isset($payable_data[$batdau][$po->type])?($payable_data[$batdau][$po->type]+($po->money-$po->pay_money)):($po->money-$po->pay_money);
        }

        foreach ($costs as $c) {
            if ($c->sale_estimate==1) {
                $payable_data[$c->week][1] = isset($payable_data[$c->week][1])?($payable_data[$c->week][1]+($c->money-$c->pay_money)):($c->money-$c->pay_money);
            }
            else if ($c->agent_estimate==1) {
                $payable_data[$c->week][2] = isset($payable_data[$c->week][2])?($payable_data[$c->week][2]+($c->money-$c->pay_money)):($c->money-$c->pay_money);
            }
            else if ($c->tcmt_estimate==1) {
                $payable_data[$c->week][3] = isset($payable_data[$c->week][3])?($payable_data[$c->week][3]+($c->money-$c->pay_money)):($c->money-$c->pay_money);
            }
            else if ($c->trading_estimate==1) {
                $payable_data[$c->week][4] = isset($payable_data[$c->week][4])?($payable_data[$c->week][4]+($c->money-$c->pay_money)):($c->money-$c->pay_money);
            }
            else{
                $payable_data[$c->week][5] = isset($payable_data[$c->week][5])?($payable_data[$c->week][5]+($c->money-$c->pay_money)):($c->money-$c->pay_money);
            }
            
            
        }
        foreach ($costs_old as $co) {
            if ($co->sale_estimate==1) {
                $payable_data[$batdau][1] = isset($payable_data[$batdau][1])?($payable_data[$batdau][1]+($co->money-$co->pay_money)):($co->money-$co->pay_money);
            }
            else if ($co->agent_estimate==1) {
                $payable_data[$batdau][2] = isset($payable_data[$batdau][2])?($payable_data[$batdau][2]+($co->money-$co->pay_money)):($co->money-$co->pay_money);
            }
            else if ($co->tcmt_estimate==1) {
                $payable_data[$batdau][3] = isset($payable_data[$batdau][3])?($payable_data[$batdau][3]+($co->money-$co->pay_money)):($co->money-$co->pay_money);
            }
            else if ($co->trading_estimate==1) {
                $payable_data[$batdau][4] = isset($payable_data[$batdau][4])?($payable_data[$batdau][4]+($co->money-$co->pay_money)):($co->money-$co->pay_money);
            }
            else{
                $payable_data[$batdau][5] = isset($payable_data[$batdau][5])?($payable_data[$batdau][5]+($co->money-$co->pay_money)):($co->money-$co->pay_money);
            }
            
        }

        // Du thu
        foreach ($receivables as $r) {
                if ($r->new_week > 0) {
                    $receivable_data[$r->new_week][$r->type] = isset($receivable_data[$r->new_week][$r->type])?($receivable_data[$r->new_week][$r->type]+($r->money-$r->pay_money)):($r->money-$r->pay_money);
                    $duthu[$r->receivable_id][$r->new_week][$r->type] = isset($duthu[$r->receivable_id][$r->new_week][$r->type])?($duthu[$r->receivable_id][$r->new_week][$r->type]+($r->money-$r->pay_money)):($r->money-$r->pay_money);
                }
                else{
                    $receivable_data[$r->week][$r->type] = isset($receivable_data[$r->week][$r->type])?($receivable_data[$r->week][$r->type]+($r->money-$r->pay_money)):($r->money-$r->pay_money);
                    $duthu[$r->receivable_id][$r->week][$r->type] = isset($duthu[$r->receivable_id][$r->week][$r->type])?($duthu[$r->receivable_id][$r->week][$r->type]+($r->money-$r->pay_money)):($r->money-$r->pay_money);
                }
                
        }
        foreach ($receivables_old as $ro) {
            $receivable_data[$batdau][$ro->type] = isset($receivable_data[$batdau][$ro->type])?($receivable_data[$batdau][$ro->type]+($ro->money-$ro->pay_money)):($ro->money-$ro->pay_money);
        }
        
        // Da chi
        foreach ($pays_payable as $pp) {
            $pay_data[$pp->week][$pp->type] = isset($pay_data[$pp->week][$pp->type])?($pay_data[$pp->week][$pp->type]+str_replace('-', "", $pp->total)):(0+str_replace('-', "", $pp->total));
        }
        foreach ($pays_advance as $pa) {
            $pay_data[$pa->week][5] = isset($pay_data[$pa->week][5])?($pay_data[$pa->week][5]+str_replace('-', "", $pa->total)):(0+str_replace('-', "", $pa->total));
        }
        foreach ($pays_costs as $pc) {
            if ($pc->sale_estimate==1) {
                $pay_data[$pc->week][1] = isset($pay_data[$pc->week][1])?($pay_data[$pc->week][1]+str_replace('-', "", $pc->total)):(0+str_replace('-', "", $pc->total));
            }
            else if ($pc->agent_estimate==1) {
                $pay_data[$pc->week][2] = isset($pay_data[$pc->week][2])?($pay_data[$pc->week][2]+str_replace('-', "", $pc->total)):(0+str_replace('-', "", $pc->total));
            }
            else if ($pc->tcmt_estimate==1) {
                $pay_data[$pc->week][3] = isset($pay_data[$pc->week][3])?($pay_data[$pc->week][3]+str_replace('-', "", $pc->total)):(0+str_replace('-', "", $pc->total));
            }
            else if ($pc->trading_estimate==1) {
                $pay_data[$pc->week][4] = isset($pay_data[$pc->week][4])?($pay_data[$pc->week][4]+str_replace('-', "", $pc->total)):(0+str_replace('-', "", $pc->total));
            }
            else{
                $pay_data[$pc->week][5] = isset($pay_data[$pc->week][5])?($pay_data[$pc->week][5]+str_replace('-', "", $pc->total)):(0+str_replace('-', "", $pc->total));
            }
        }
        // Da thu
        foreach ($receives as $re) {
            $receive_data[$re->week][$re->type] = isset($receive_data[$re->week][$re->type])?($receive_data[$re->week][$re->type]+$re->total):0+$re->total;
            $dathu[$re->receivable][$re->week][$re->type] = isset($dathu[$re->receivable][$re->week][$re->type])?($dathu[$re->receivable][$re->week][$re->type]+$re->total):$re->total;
        }

        $sodu = array();

        $day = date('d-m-Y');
        $week = (int)date('W',strtotime($day));

        $receivables_max = $receivable->queryCosts('SELECT max(week) as week FROM receivable WHERE year = '.$ketthuc.' GROUP BY week');
        $receivables_max_new = $receivable->queryCosts('SELECT max(new_week) as new_week FROM receivable WHERE year = '.$ketthuc.' GROUP BY new_week');
        foreach ($receivables_max as $max) {
            $max_week = $max->week;
        }
        
        $max_week_new = 0;
        foreach ($receivables_max_new as $max) {
            $max_week_new = $max->new_week;
        }

        $max_week = $max_week>$max_week_new?$max_week:$max_week_new;
    
        if ($max_week > $week) {
            $week = $max_week;
        }

        /*for ($i=($batdau-1); $i <= $week; $i++) { 
            for ($j=1; $j <= 5; $j++) { 
                $receive_data[$i][$j] = isset($receive_data[$i][$j])?$receive_data[$i][$j]:0;
                $receivable_data[$i][$j] = isset($receivable_data[$i][$j])?$receivable_data[$i][$j]:0;
                $receivable_data[$i+1][$j] = isset($receivable_data[$i+1][$j])?$receivable_data[$i+1][$j]:0;

                $sodu[$i][$j] = isset($sodu[$i][$j])?($sodu[$i][$j] + $receivable_data[$i][$j] - $receive_data[$i][$j]):($receivable_data[$i][$j] - $receive_data[$i][$j]);

                if($sodu[$i][$j] > 0){
                    $receivable_data[$i+1][$j] += $sodu[$i][$j];
                }
            }
           
        }*/


        $this->view->data['payable_data'] = $payable_data;
        $this->view->data['receivable_data'] = $receivable_data;
        $this->view->data['pay_data'] = $pay_data;
        $this->view->data['receive_data'] = $receive_data;
        $this->view->data['week'] = $week;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['dongtien_data'] = $dongtien;
        
        $this->view->show('money/index');
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