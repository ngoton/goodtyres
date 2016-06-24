<?php
Class fixedassetbuyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $asset = $this->registry->router->param_id;
        if (!isset($asset)) {
            return $this->view->redirect('fixedasset');
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'fixed_asset_buy_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;

        $join = array('table'=>'fixed_asset, bank','where'=>'fixed_asset_buy.fixed_asset = fixed_asset.fixed_asset_id AND fixed_asset_buy.fixed_asset_buy_source=bank.bank_id');
        $data = array(
            'where'=>'fixed_asset='.$asset,
        );

        $asset_model = $this->model->get('fixedassetbuyModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $tongsodong = count($asset_model->getAllAsset($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        
        $this->view->data['fixed_asset'] = $asset;

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
            'where'=>'fixed_asset='.$asset,
            );
        
        if ($keyword != '') {
            $search = '( fixed_asset_name LIKE "%'.$keyword.'%" )';
            $data['where'] = $search;
        }
        
        
        
        $this->view->data['assets'] = $asset_model->getAllAsset($data,$join);

        $this->view->data['lastID'] = isset($asset_model->getLastAsset()->fixed_asset_buy_id)?$asset_model->getLastAsset()->fixed_asset_buy_id:0;
        
        $this->view->show('fixedassetbuy/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $asset = $this->model->get('fixedassetbuyModel');
            $asset_model = $this->model->get('assetsModel');
            $data = array(
                        
                        'fixed_asset' => trim($_POST['fixed_asset']),
                        'fixed_asset_buy_money' => trim(str_replace(',','',$_POST['fixed_asset_buy_money'])),
                        'fixed_asset_buy_date' => strtotime($_POST['fixed_asset_buy_date']),
                        'fixed_asset_buy_source' => trim($_POST['fixed_asset_buy_source']),
                        
                        );
            if ($_POST['yes'] != "") {
                //var_dump($data);
                
                    $asset_data = $asset->getAsset($_POST['yes']);
                        $asset->updateAsset($data,array('fixed_asset_buy_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        if (!$asset_model->getAssetsByWhere(array('fixed_asset_buy'=>$_POST['yes']))) {
                            if ($data['fixed_asset_buy_money'] > 0) {
                                $data_asset = array(
                                    'assets_date' => $data['fixed_asset_buy_date'],
                                    'total' => 0 - $data['fixed_asset_buy_money'],
                                    'bank' => $data['fixed_asset_buy_source'],
                                    'fixed_asset_buy' => $_POST['yes'],
                                    'week' => (int)date('W',$data['fixed_asset_buy_date']),
                                    'year' => (int)date('Y',$data['fixed_asset_buy_date'])
                                );
                                if($data_asset['week'] == 53){
                                    $data_asset['week'] = 1;
                                    $data_asset['year'] = $data_asset['year']+1;
                                }
                                if (((int)date('W',$data['fixed_asset_buy_date']) == 1) && ((int)date('m',$data['fixed_asset_buy_date']) == 12) ) {
                                    $data_asset['year'] = (int)date('Y',$data['fixed_asset_buy_date'])+1;
                                }

                                $asset_model->createAssets($data_asset);
                            }

                        }
                        else if ($asset_model->getAssetsByWhere(array('fixed_asset_buy'=>$_POST['yes']))) {
                            if ($asset_model->getAssetsByWhere(array('fixed_asset_buy'=>$_POST['yes'],'total'=>(0-$asset_data->fixed_asset_buy_money)))) {
                                $assets_data = $asset_model->getAssetsByWhere(array('fixed_asset_buy'=>$_POST['yes'],'total'=>(0-$asset_data->fixed_asset_buy_money)));
                                if ($data['fixed_asset_buy_money'] > 0) {
                                    $data_asset = array(
                                        'assets_date' => $data['fixed_asset_buy_date'],
                                        'total' => 0 - $data['fixed_asset_buy_money'],
                                        'bank' => $data['fixed_asset_buy_source'],
                                        'week' => (int)date('W',$data['fixed_asset_buy_date']),
                                        'year' => (int)date('Y',$data['fixed_asset_buy_date'])
                                    );
                                    if($data_asset['week'] == 53){
                                        $data_asset['week'] = 1;
                                        $data_asset['year'] = $data_asset['year']+1;
                                    }
                                    if (((int)date('W',$data['fixed_asset_buy_date']) == 1) && ((int)date('m',$data['fixed_asset_buy_date']) == 12) ) {
                                        $data_asset['year'] = (int)date('Y',$data['fixed_asset_buy_date'])+1;
                                    }

                                    $asset_model->updateAssets($data_asset,array('assets_id'=>$assets_data->assets_id));
                                }
                                else if ($data['fixed_asset_buy_money'] == 0 || $data['fixed_asset_buy_money'] == "") {
                                    $asset_model->queryAssets('DELETE FROM assets WHERE fixed_asset_buy = '.$_POST['yes'].' AND total = '.(0-$asset_data->fixed_asset_buy_money));
                                }
                            }
                            
                        }

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|fixed_asset_buy|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                
                
            }
            else{
                //$data['customer'] = $_POST['customer'];
                //var_dump($data);
                
                    $asset->createAsset($data);
                    echo "Thêm thành công";

                    if ($data['fixed_asset_buy_money'] > 0) {
                        $data_asset = array(
                            'assets_date' => $data['fixed_asset_buy_date'],
                            'total' => 0 - $data['fixed_asset_buy_money'],
                            'bank' => $data['fixed_asset_buy_source'],
                            'fixed_asset_buy' => $asset->getLastAsset()->fixed_asset_buy_id,
                            'week' => (int)date('W',$data['fixed_asset_buy_date']),
                            'year' => (int)date('Y',$data['fixed_asset_buy_date'])
                        );
                        if($data_asset['week'] == 53){
                            $data_asset['week'] = 1;
                            $data_asset['year'] = $data_asset['year']+1;
                        }
                        if (((int)date('W',$data['fixed_asset_buy_date']) == 1) && ((int)date('m',$data['fixed_asset_buy_date']) == 12) ) {
                            $data_asset['year'] = (int)date('Y',$data['fixed_asset_buy_date'])+1;
                        }

                        $asset_model->createAssets($data_asset);
                    }

                    
                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$asset->getLastAsset()->fixed_asset_buy_id."|fixed_asset_buy|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $asset = $this->model->get('fixedassetbuyModel');
            $asset_model = $this->model->get('assetsModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                        $asset->deleteAsset($data);
                        echo "Xóa thành công";

                        $asset_model->queryAssets('DELETE FROM assets WHERE fixed_asset_buy = '.$data);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|fixed_asset_buy|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    
                        $asset->deleteAsset($_POST['data']);
                        echo "Xóa thành công";

                        $asset_model->queryAssets('DELETE FROM assets WHERE fixed_asset_buy = '.$_POST['data']);

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|fixed_asset_buy|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    


}
?>