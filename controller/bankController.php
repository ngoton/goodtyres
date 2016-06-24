<?php
Class bankController Extends baseController {
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
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $day = date('d-m-Y');
            $batdau = (int)date('W', strtotime($day));
            $ketthuc = date('Y');
        }

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();

        $assets_model = $this->model->get('assetsModel');
        $where = array(
            'where' => '(( week <= '.$batdau.' AND year = '.$ketthuc.') OR (week <= 53 AND year < '.$ketthuc.') )',
        );
        $assets = $assets_model->getAllAssets($where);

        $asset_data = array();
        
        foreach ($assets as $asset) {
            $asset_data[$asset->bank] = isset($asset_data[$asset->bank])?($asset_data[$asset->bank]+$asset->total):(0+$asset->total);
        }

        // $where1 = array(
        //     'where' => 'week = '.($batdau-1).' AND year = '.$ketthuc,
        // );
        // $assets_last = $assets_model->getAllAssets($where1);

        // $asset_data_last = array();
        
        // foreach ($assets_last as $asset) {
        //     $asset_data_last[$asset->bank] = isset($asset_data_last[$asset->bank])?($asset_data_last[$asset->bank]+$asset->total):0+$asset->total;
        // }

        $where2 = array(
            'where' => 'total < 0 AND week = '.$batdau.' AND year = '.$ketthuc,
        );
        $assets_giam = $assets_model->getAllAssets($where2);

        $asset_data_giam = array();
        
        foreach ($assets_giam as $asset) {
            $asset_data_giam[$asset->bank] = isset($asset_data_giam[$asset->bank])?($asset_data_giam[$asset->bank]+str_replace('-', '', $asset->total)):str_replace('-', '', $asset->total);
        }

        $where3 = array(
            'where' => 'total > 0 AND week = '.$batdau.' AND year = '.$ketthuc,
        );
        $assets_tang = $assets_model->getAllAssets($where3);

        $asset_data_tang = array();
        
        foreach ($assets_tang as $asset) {
            $asset_data_tang[$asset->bank] = isset($asset_data_tang[$asset->bank])?($asset_data_tang[$asset->bank]+$asset->total):$asset->total;
        }

        

        $this->view->data['banks'] = $banks;
        $this->view->data['assets'] = $asset_data;
        //$this->view->data['assets_last'] = $asset_data_last;
        $this->view->data['assets_tang'] = $asset_data_tang;
        $this->view->data['assets_giam'] = $asset_data_giam;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        
        $this->view->show('bank/index');
    }

   
    

}
?>