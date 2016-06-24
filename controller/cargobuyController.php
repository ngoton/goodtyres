<?php
Class cargobuyController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nhập hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'cargo_buy_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        
        $cargo_type_model = $this->model->get('cargotypeModel');
        $cargo_brand_model = $this->model->get('cargobrandModel');
        $cargo_category_model = $this->model->get('cargocategoryModel');
        $vendor_model = $this->model->get('shipmentvendorModel');

        $cargo_types = $cargo_type_model->getAllCargo();
        $cargo_brands = $cargo_brand_model->getAllCargo();
        $cargo_categorys = $cargo_category_model->getAllCargo();
        $vendors = $vendor_model->getAllVendor();

        $this->view->data['cargo_types'] = $cargo_types;
        $this->view->data['cargo_brands'] = $cargo_brands;
        $this->view->data['cargo_categorys'] = $cargo_categorys;
        $this->view->data['cargo_vendors'] = $vendors;

        $join = array('table'=>'shipment_vendor, cargo_type, cargo_brand, cargo_category','where'=>'cargo_category.cargo_category_id = cargo_buy.cargo_category AND cargo_type.cargo_type_id = cargo_brand.cargo_type AND cargo_brand.cargo_brand_id = cargo_category.cargo_brand AND cargo_buy.cargo_buy_vendor = shipment_vendor.shipment_vendor_id');

        $cargo_buy_model = $this->model->get('cargobuyModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($cargo_buy_model->getAllCargo($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );
        
      
        if ($keyword != '') {
            $search = '( cargo_buy_code LIKE "%'.$keyword.'%" 
                OR cargo_brand_name LIKE "%'.$keyword.'%" 
                OR cargo_type_name LIKE "%'.$keyword.'%" 
                OR cargo_category_name LIKE "%'.$keyword.'%" 
                OR shipment_vendor_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['cargo_buys'] = $cargo_buy_model->getAllCargo($data,$join);
        $this->view->data['lastID'] = isset($cargo_buy_model->getLastCargo()->cargo_buy_id)?$cargo_buy_model->getLastCargo()->cargo_buy_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('cargobuy/index');
    }

   
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $cargo_buy_model = $this->model->get('cargobuyModel');
            $data = array(
                        
                        'cargo_buy_code' => trim($_POST['cargo_buy_code']),
                        'cargo_buy_number' => trim($_POST['cargo_buy_number']),
                        'cargo_category' => trim($_POST['cargo_category']),
                        'cargo_buy_vendor' => trim($_POST['cargo_buy_vendor']),
                        'cargo_buy_charge' => trim(str_replace(',','',$_POST['cargo_buy_charge'])),
                        'cargo_buy_cost' => trim(str_replace(',','',$_POST['cargo_buy_cost'])),
                        'cargo_buy_date' => strtotime($_POST['cargo_buy_date']),
                        );
            

            if ($_POST['yes'] != "") {
                


                    $cargo_buy_model->updateCargo($data,array('cargo_buy_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|cargo_buy|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $cargo_buy_model->createCargo($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$cargo_buy_model->getLastCargo()->cargo_buy_id."|cargo_buy|".implode("-",$data)."\n"."\r\n";
                        
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
            $cargo_buy_model = $this->model->get('cargobuyModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $cargo_buy_model->deleteCargo($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|cargo_buy|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $cargo_buy_model->deleteCargo($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|cargo_buy|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function getBrand(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cargo_brand_model = $this->model->get('cargobrandModel');

            $data = array(
                'where' => 'cargo_type = '.$_POST['data'],
            );

            $cargo_brands = $cargo_brand_model->getAllCargo($data);

            $str = "";

            foreach ($cargo_brands as $cargo_brand) {
                $str .= '<option value="'.$cargo_brand->cargo_brand_id.'">'.$cargo_brand->cargo_brand_name.'</option>';
            }

            echo $str;
        }
    }

    public function getCategory(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cargo_category_model = $this->model->get('cargocategoryModel');

            $data = array(
                'where' => 'cargo_brand = '.$_POST['data'],
            );

            $cargo_categorys = $cargo_category_model->getAllCargo($data);

            $str = "";

            foreach ($cargo_categorys as $cargo_category) {
                $str .= '<option value="'.$cargo_category->cargo_category_id.'">'.$cargo_category->cargo_category_name.'</option>';
            }

            echo $str;
        }
    }

}
?>