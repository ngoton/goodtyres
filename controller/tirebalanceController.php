<?php
Class tirebalanceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nhập hàng lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'import_tire_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 100;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y'); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');
        }
        $limit = 100;

        $sale_model = $this->model->get('importtireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'import_tire_date >= '.strtotime($batdau).' AND import_tire_date <= '.strtotime($ketthuc),
        );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }
        
        
        $tongsodong = count($sale_model->getAllSale($data));
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
            'where' => 'import_tire_date >= '.strtotime($batdau).' AND import_tire_date <= '.strtotime($ketthuc),
            );

        if (isset($id) && $id > 0) {
            $data['where'] = 'code = '.$id;
        }

        /*if ($_SESSION['role_logined'] == 4) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR comment LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $sales = $sale_model->getAllSale($data);

        $sale_cost_model = $this->model->get('importtirecostModel');
        $tire_going_model = $this->model->get('tiregoingModel');

        $tire_tax_model = $this->model->get('tiretaxModel');
        $tire_stuff_model = $this->model->get('tirestuffModel');
        $tire_cost_logs_model = $this->model->get('tirecostlogsModel');
        $tire_buy_price_model = $this->model->get('tirebuypriceModel');

        $codes = array();
        $costs = array();
        $costs_vat = array();

        $numbers = array();
        $taxs = array();
        $prices = array();

        foreach ($sales as $sale) {
            $codes[$sale->code]['code'] = $sale->code;

            $import_costs = $sale_cost_model->getAllVendor(array('where'=>'trading = '.$sale->import_tire_id));
            foreach ($import_costs as $import_cost) {
                if (($import_cost->check_deposit == "" || $import_cost->check_deposit == 0) && $import_cost->vendor != 200) {
                    if ($import_cost->cost>0) {
                        $costs[$sale->code][$import_cost->check_cost] = isset($costs[$sale->code][$import_cost->check_cost])?$costs[$sale->code][$import_cost->check_cost]+$import_cost->cost:$import_cost->cost;
                    }
                    if ($import_cost->cost_vat>0) {
                        $costs_vat[$sale->code][$import_cost->check_cost] = isset($costs_vat[$sale->code][$import_cost->check_cost])?$costs_vat[$sale->code][$import_cost->check_cost]+$import_cost->cost_vat:$import_cost->cost_vat;
                    }
                    
                }
                if ($import_cost->vendor == 200) {
                    if ($import_cost->cost>0) {
                        $costs[$sale->code][3] = isset($costs[$sale->code][3])?$costs[$sale->code][3]+$import_cost->cost:$import_cost->cost;
                    }
                    if ($import_cost->cost_vat>0) {
                        $costs_vat[$sale->code][3] = isset($costs_vat[$sale->code][3])?$costs_vat[$sale->code][3]+$import_cost->cost_vat:$import_cost->cost_vat;
                    }
                }
                
            }

            $join = array('table'=>'tire_size,tire_brand,tire_pattern','where'=>'tire_size=tire_size_id AND tire_pattern=tire_pattern_id AND tire_brand=tire_brand_id');

            $goings = $tire_going_model->getAllTire(array('where'=>'code='.$sale->code),$join);
            foreach ($goings as $going) {
                $numbers[$sale->code] = isset($numbers[$sale->code])?$numbers[$sale->code]+$going->tire_number:$going->tire_number;

                $ta = $tire_tax_model->getAllTire(array('where'=>'tire_product_size_number="'.$going->tire_size_number.'"'),array('table'=>'tire_product_size','where'=>'tire_size=tire_product_size_id'));
                foreach ($ta as $tax) {
                    $taxs[$sale->code] = isset($taxs[$sale->code])?$taxs[$sale->code]+$tax->tax:$tax->tax;
                }
                
                $join_p = array('table'=>'tire_product_size,tire_product_pattern,tire_producer','where'=>'tire_size=tire_product_size_id AND tire_brand=tire_producer_id AND tire_pattern=tire_product_pattern_id');
                $pr = $tire_buy_price_model->getAllTire(array('where'=>'tire_product_size_number="'.$going->tire_size_number.'" AND tire_product_pattern_name="'.$going->tire_pattern_name.'" AND tire_producer_name="'.$going->tire_brand_name.'"'),$join_p);
                foreach ($pr as $price) {
                    $prices[$sale->code] = isset($prices[$sale->code])?$prices[$sale->code]+$price->tire_buy_price:$price->tire_buy_price;
                }
                
            }
        }

        $logistics = $tire_cost_logs_model->getTire(1);
        
        $this->view->data['codes'] = $codes;
        $this->view->data['costs'] = $costs;
        $this->view->data['costs_vat'] = $costs_vat;
        $this->view->data['logistics'] = $logistics;
        $this->view->data['taxs'] = $taxs;
        $this->view->data['prices'] = $prices;
        

        $this->view->show('tirebalance/index');
    }

    public function going(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $code = $this->registry->router->param_id;

        $tire_going_model = $this->model->get('tiregoingModel');
        $join = array('table'=>'tire_pattern,tire_brand,tire_size','where'=>'tire_pattern = tire_pattern_id AND tire_brand = tire_brand_id AND tire_size = tire_size_id');

        $data = array(
            'where' => 'code='.$code,
        );

        $goings = $tire_going_model->getAllTire($data,$join);
        $this->view->data['tire_goings'] = $goings;

        $this->view->data['code'] = $code;
        $this->view->data['tire_going_date'] = $this->registry->router->order_by;

        $this->view->show('tirebalance/going');
    }

}
?>