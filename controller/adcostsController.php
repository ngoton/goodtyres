<?php
Class adcostsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $hanhchinh = isset($_POST['hanhchinh']) ? $_POST['hanhchinh'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
            $hanhchinh = 1;
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $lender_pays = array();
        $lender_costs = array();

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id AND (staff IS NULL OR staff <= 0)  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        

        if ($hanhchinh == 1) {
            $query .= ' AND  (money_in != money) ';

            $query1 = 'SELECT assets.assets_date, lender_cost.comment, lender_cost.lender_cost_id, assets.total, assets.bank, bank.bank_name FROM lender_cost, assets, bank WHERE lender_cost.lender_cost_id=assets.lender_cost  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
            $lender_costs = $assets_model->queryAssets($query1);

            $query2 = 'SELECT assets.assets_date, lender_pay.comment, lender_pay.lender_pay_id, assets.total, assets.bank, bank.bank_name FROM lender_pay, assets, bank WHERE lender_pay.lender_pay_id=assets.lender_pay  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
            $lender_pays = $assets_model->queryAssets($query2);
        }
        else if ($hanhchinh == 2) {
            $query .= ' AND check_salary = 1';
        }
        else if ($hanhchinh == 3) {
            $query .= ' AND check_phone = 1';
        }
        else if ($hanhchinh == 4) {
            $query .= ' AND check_equipment = 1';
        }
        else if ($hanhchinh == 5) {
            $query .= ' AND check_bonus = 1';
        }
        else if ($hanhchinh == 6) {
            $query .= ' AND check_entertainment = 1';
        }
        else if ($hanhchinh == 7) {
            $query .= ' AND check_energy = 1';
        }
        else if ($hanhchinh == 10) {
            $query .= ' AND check_other = 1';

            $query1 = 'SELECT assets.assets_date, lender_cost.comment, lender_cost.lender_cost_id, assets.total, assets.bank, bank.bank_name FROM lender_cost, assets, bank WHERE lender_cost.lender_cost_id=assets.lender_cost  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
            $lender_costs = $assets_model->queryAssets($query1);

            $query2 = 'SELECT assets.assets_date, lender_pay.comment, lender_pay.lender_pay_id, assets.total, assets.bank, bank.bank_name FROM lender_pay, assets, bank WHERE lender_pay.lender_pay_id=assets.lender_pay  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
            $lender_pays = $assets_model->queryAssets($query2);
        }
        else if ($hanhchinh == 8) {
            $query .= ' AND  (money_in = money) ';
        }
        else if ($hanhchinh == 9) {
            $query .= ' AND staff > 0 ';
        }

        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

                
        $this->view->data['costs'] = $costs;
        $this->view->data['lender_costs'] = $lender_costs;
        $this->view->data['lender_pays'] = $lender_pays;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['hanhchinh'] = $hanhchinh;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/index');
    }

    public function salary() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        $query .= ' AND (staff IS NULL OR staff <= 0) AND check_salary = 1';

        
        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

        
        $this->view->data['costs'] = $costs;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/salary');
    }

    public function allowance() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        $query .= ' AND (check_phone = 1 OR check_eating = 1 OR check_insurance = 1)';

        
        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

        
        $this->view->data['costs'] = $costs;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/allowance');
    }

    public function office() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        $query .= ' AND (check_equipment = 1 OR check_energy = 1)';

        
        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

        
        $this->view->data['costs'] = $costs;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/office');
    }
    public function team() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        $query .= ' AND (check_bonus = 1 OR check_entertainment = 1)';

        
        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

        
        $this->view->data['costs'] = $costs;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/team');
    }
    public function other() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thu chi hành chính';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $assets_model = $this->model->get('assetsModel');

        $query = 'SELECT assets.assets_date, costs.comment, costs.costs_id, assets.total, assets.bank, bank.bank_name FROM costs, assets, bank WHERE assets.costs=costs.costs_id AND assets.bank=bank.bank_id  AND (sale_estimate IS NULL OR sale_estimate <= 0) AND (agent_estimate IS NULL OR agent_estimate <= 0) AND (trading_estimate IS NULL OR trading_estimate <= 0) AND (tcmt_estimate IS NULL OR tcmt_estimate <= 0) AND costs.check_office = 1 AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc);
        $query .= ' AND check_other = 1 AND (staff IS NULL OR staff <= 0) AND money_in != money';

        
        $query .= ' ORDER BY assets.assets_date ASC';
        $costs = $assets_model->queryAssets($query);

        $query = 'SELECT assets.assets_date, lender_cost.comment, lender_cost.lender_cost_id, assets.total, assets.bank, bank.bank_name FROM lender_cost, assets, bank WHERE lender_cost.lender_cost_id=assets.lender_cost  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
        $lender_costs = $assets_model->queryAssets($query);

        $query = 'SELECT assets.assets_date, lender_pay.comment, lender_pay.lender_pay_id, assets.total, assets.bank, bank.bank_name FROM lender_pay, assets, bank WHERE lender_pay.lender_pay_id=assets.lender_pay  AND assets.bank=bank.bank_id AND assets.total != 0 AND assets.bank > 0 AND assets.assets_date >= '.strtotime($batdau).' AND assets.assets_date <= '.strtotime($ketthuc).' ORDER BY assets.assets_date ASC';
        $lender_pays = $assets_model->queryAssets($query);

        $this->view->data['costs'] = $costs;
        $this->view->data['lender_costs'] = $lender_costs;
        $this->view->data['lender_pays'] = $lender_pays;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        
        $this->view->show('adcosts/other');
    }

   
    

}
?>