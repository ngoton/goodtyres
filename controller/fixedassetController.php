<?php
Class fixedassetController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý tài sản cố định';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'fixed_asset_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $fixed_asset_buy_model = $this->model->get('fixedassetbuyModel');
        $fixed_asset_buys = $fixed_asset_buy_model->getAllAsset();
        $buy_data = array();
        foreach ($fixed_asset_buys as $fixed_asset_buy) {
            $buy_data[$fixed_asset_buy->fixed_asset] = isset($buy_data[$fixed_asset_buy->fixed_asset])?$buy_data[$fixed_asset_buy->fixed_asset]+$fixed_asset_buy->fixed_asset_buy_money:$fixed_asset_buy->fixed_asset_buy_money;
        }
        $this->view->data['buy_data'] = $buy_data;

        $asset_model = $this->model->get('fixedassetModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($asset_model->getAllAsset());
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( fixed_asset_name LIKE "%'.$keyword.'%" )';
            $data['where'] = $search;
        }
        
        
        
        $this->view->data['assets'] = $asset_model->getAllAsset($data);

        $this->view->data['lastID'] = isset($asset_model->getLastAsset()->fixed_asset_id)?$asset_model->getLastAsset()->fixed_asset_id:0;
        
        $this->view->show('fixedasset/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $asset = $this->model->get('fixedassetModel');
            $asset_model = $this->model->get('assetsModel');
            $data = array(
                        
                        'fixed_asset_name' => trim($_POST['fixed_asset_name']),
                        'fixed_asset_number' => trim($_POST['fixed_asset_number']),
                        
                        
                        );
            if ($_POST['yes'] != "") {
                //var_dump($data);
                
                if ($asset->getAllAssetByWhere($_POST['yes'].' AND fixed_asset_name = '.trim($_POST['fixed_asset_name']))) {
                    echo "Thông tin đã tồn tại";
                    return false;
                }
                else{
                    $asset_data = $asset->getAsset($_POST['yes']);
                        $asset->updateAsset($data,array('fixed_asset_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|fixed_asset|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                
            }
            else{
                //$data['customer'] = $_POST['customer'];
                //var_dump($data);
                if ($asset->getAssetByWhere(array('fixed_asset_name'=>trim($_POST['fixed_asset_name'])))) {
                    echo "Thông tin đã tồn tại";
                    return false;
                }
                else{
                    $asset->createAsset($data);
                    echo "Thêm thành công";

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$asset->getLastAsset()->fixed_asset_id."|fixed_asset|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $asset = $this->model->get('fixedassetModel');
            $asset_model = $this->model->get('fixedassetbuyModel');
            $asset_sale_model = $this->model->get('fixedassetsaleModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $asset->deleteAsset($data);
                        echo "Xóa thành công";

                        $asset_model->queryAssets('DELETE FROM fixed_asset_buy WHERE fixed_asset = '.$data);
                        $asset_sale_model->queryAssets('DELETE FROM fixed_asset_sale WHERE fixed_asset = '.$data);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|fixed_asset|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    
                        $asset->deleteAsset($_POST['data']);
                        echo "Xóa thành công";

                        $asset_model->queryAssets('DELETE FROM fixed_asset_buy WHERE fixed_asset = '.$_POST['data']);
                        $asset_sale_model->queryAssets('DELETE FROM fixed_asset_sale WHERE fixed_asset = '.$_POST['data']);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|fixed_asset|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    


}
?>