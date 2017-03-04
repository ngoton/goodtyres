<?php
Class expiredController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Hàng tồn quá hạn';

        $thuonghieu = isset($_POST['thuonghieu'])?$_POST['thuonghieu']:0;
        $kichco = isset($_POST['kichco'])?$_POST['kichco']:0;
        $magai = isset($_POST['magai'])?$_POST['magai']:0;

        $this->view->data['thuonghieu'] = $thuonghieu;
        $this->view->data['kichco'] = $kichco;
        $this->view->data['magai'] = $magai;

        $ngay = date('d-m-Y');
        if (isset($_POST['date'])) {
            $ngay = $_POST['date'];
        }
        $this->view->data['ngay'] = $ngay;

        $tire_order_model = $this->model->get('tireorderModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $query = "SELECT t2.soluong, t1.*, t2.tire_brand_name, t2.tire_size_number, t2.tire_pattern_name FROM tire_buy t1 JOIN (SELECT sum(tire_buy_volume) as soluong, tire_buy_brand, tire_buy_size, tire_buy_pattern, MAX(tire_buy_id) as lonnhat, tire_brand_name, tire_size_number, tire_pattern_name, tire_buy_id FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_buy_date <= ".strtotime($ngay)." AND date_manufacture > 0 AND tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand, tire_buy_size, tire_buy_pattern, date_manufacture ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC) t2 ON t1.tire_buy_id = t2.lonnhat AND t1.tire_buy_brand = t2.tire_buy_brand AND t1.tire_buy_size = t2.tire_buy_size AND t1.tire_buy_pattern = t2.tire_buy_pattern";
        if ($thuonghieu > 0) {
            $query .= " AND t1.tire_buy_brand = ".$thuonghieu;
        }
        if ($kichco > 0) {
            $query .= " AND t1.tire_buy_size = ".$kichco;
        }
        if ($magai > 0) {
            $query .= " AND t1.tire_buy_pattern = ".$magai;
        }
        
        $tire_buys = $tire_buy_model->queryTire($query);
        $this->view->data['tire_buys'] = $tire_buys;

        $link_picture = array();

        $sell = array();
        foreach ($tire_buys as $tire_buy) {
            $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_buy->tire_pattern_name.'.jpg';

            $data_sale = array(
                'where'=>'tire_sale_date <= '.strtotime($ngay).' AND tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern.' AND date_manufacture_sale='.$tire_buy->date_manufacture,
            );
            $tire_sales = $tire_sale_model->getAllTire($data_sale);

            foreach ($tire_sales as $tire_sale) {
                
                if ($tire_sale->customer != 119) {
                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;
                }
                
            }
        }

        $this->view->data['link_picture'] = $link_picture;

        $this->view->data['tire_buys'] = $tire_buys;
        $this->view->data['sell'] = $sell;
        $this->view->data['page'] = NULL;
        $this->view->data['order_by'] = NULL;
        $this->view->data['order'] = NULL;
        $this->view->data['keyword'] = NULL;
        $this->view->data['pagination_stages'] = NULL;
        $this->view->data['tongsotrang'] = NULL;
        $this->view->data['limit'] = NULL;
        $this->view->data['sonews'] = NULL;

        
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('expired/index');
    }

    
}
?>