<?php
Class statementController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng kê thực thu - thực chi';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $day = date('d-m-Y');
            $batdau = (int)date('W', strtotime($day));
            $ketthuc = date('Y');
        }

        

        $assets_model = $this->model->get('assetsModel');

        $bank_model = $this->model->get('bankModel');

        $data = array(
            'where' => 'bank_id IN (SELECT bank FROM assets  WHERE assets.week = '.$batdau.' AND assets.year = '.$ketthuc.')',
        );
        $banks = $bank_model->getAllBank($data);

        $asset_costs = array();
        $asset_advance = array();
        $asset_payable = array();
        $asset_receivable = array();

        foreach ($banks as $bank) {
            $join = array('table'=>'costs, bank','where'=>'assets.costs = costs.costs_id AND bank.bank_id = assets.bank AND assets.bank='.$bank->bank_id);
            $where = array(
                'where' => 'assets.total != 0 AND assets.week = '.$batdau.' AND assets.year = '.$ketthuc,
                'order_by'=> 'assets.assets_date',
                'order'=> 'ASC',
            );
            $asset_costs[$bank->bank_id] = $assets_model->getAllAssets($where,$join);

            
            $join = array('table'=>'advance, bank','where'=>'assets.advance = advance.advance_id AND bank.bank_id = assets.bank AND assets.bank='.$bank->bank_id);
            $where = array(
                'where' => 'assets.total != 0 AND assets.week = '.$batdau.' AND assets.year = '.$ketthuc,
                'order_by'=> 'assets.assets_date',
                'order'=> 'ASC',
            );
            $asset_advance[$bank->bank_id] = $assets_model->getAllAssets($where,$join);
            

            $join = array('table'=>'payable, bank','where'=>'assets.payable = payable.payable_id AND bank.bank_id = assets.bank AND assets.bank='.$bank->bank_id);
            $where = array(
                'where' => 'assets.total != 0 AND assets.week = '.$batdau.' AND assets.year = '.$ketthuc,
                'order_by'=> 'assets.assets_date',
                'order'=> 'ASC',
            );
            $asset_payable[$bank->bank_id] = $assets_model->getAllAssets($where,$join);
            

            $join = array('table'=>'receivable, bank','where'=>'assets.receivable = receivable.receivable_id AND bank.bank_id = assets.bank AND assets.bank='.$bank->bank_id);
            $where = array(
                'where' => 'assets.total != 0 AND assets.week = '.$batdau.' AND assets.year = '.$ketthuc,
                'order_by'=> 'assets.assets_date',
                'order'=> 'ASC',
            );
            $asset_receivable[$bank->bank_id] = $assets_model->getAllAssets($where,$join);
        }

        
        
        

        $this->view->data['banks'] = $banks;
        $this->view->data['asset_costs'] = $asset_costs;
        $this->view->data['asset_advance'] = $asset_advance;
        $this->view->data['asset_payable'] = $asset_payable;
        $this->view->data['asset_receivable'] = $asset_receivable;
        
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('statement/index');
    }

   
    

}
?>