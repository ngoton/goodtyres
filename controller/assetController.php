<?php
Class assetController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý tài sản cố định';


        $asset_model = $this->model->get('fixedassetModel');
        
        
        $this->view->data['assets'] = $asset_model->getAllAsset();

        $this->view->data['lastID'] = isset($asset_model->getLastAsset()->fixed_asset_id)?$asset_model->getLastAsset()->fixed_asset_id:0;
        
        $this->view->show('asset/index');
    }

    

    


}
?>