<?php
Class inventoryController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tồn kho lốp xe';

        $ngay = date('d-m-Y');
        if (isset($_POST['date'])) {
            $ngay = $_POST['date'];
        }
        $this->view->data['ngay'] = $ngay;

        $tire_order_model = $this->model->get('tireorderModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');

        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";
        $tire_buys = $tire_buy_model->queryTire($query);
        $this->view->data['tire_buys'] = $tire_buys;

        $link_picture = array();

        $sell = array();
        foreach ($tire_buys as $tire_buy) {
            $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_buy->tire_pattern_name.'.jpg';

            $data_sale = array(
                'where'=>'tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,
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
        
        $buy = $tire_buy_model->queryTire('SELECT max(tire_buy_date) AS max FROM tire_buy');  
        $sale = $tire_sale_model->queryTire('SELECT max(tire_sale_date) AS max FROM tire_sale'); 
        $order = $tire_order_model->queryTire('SELECT max(tire_receive_date) AS max FROM tire_order');

        $max = 0;

        foreach ($buy as $b) {
             $max = $b->max;
        }

        foreach ($sale as $s) {
            if($s->max > $max)
                $max = $s->max;
        }

        foreach ($order as $o) {
            if($o->max > $max)
                $max = $o->max;
        }

        $today = strtotime($ngay);

        $max = $max > $today ? $max : $today;

        $this->view->data['max'] = $max;


        $total = 0;

        $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_date < '.$today);  
        $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE customer != 119 AND tire_sale_date < '.$today); 
        $orders = $tire_order_model->queryTire('SELECT sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date < '.$today);

        foreach ($buys as $buy) {
            $total += $buy->total_buy;
        }

        foreach ($sales as $sale) {
            $total -= $sale->total_sale;
        }

        foreach ($orders as $order) {
            $total -= $order->total_order;
        }

        $this->view->data['total'] = $total;

        $buys = $tire_buy_model->queryTire('SELECT * FROM tire_buy WHERE tire_buy_date >= '.$today);  
        $sales = $tire_sale_model->queryTire('SELECT * FROM tire_sale WHERE customer != 119 AND tire_sale_date >= '.$today); 
        $orders = $tire_order_model->queryTire('SELECT * FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date >= '.$today);

        $tire = array();

        foreach ($buys as $buy) {
            $tire[date('d-m-Y',$buy->tire_buy_date)]['buy'] = isset($tire[date('d-m-Y',$buy->tire_buy_date)]['buy'])?$tire[date('d-m-Y',$buy->tire_buy_date)]['buy']+$buy->tire_buy_volume:$buy->tire_buy_volume;
        }

        foreach ($sales as $sale) {
            $tire[date('d-m-Y',$sale->tire_sale_date)]['sale'] = isset($tire[date('d-m-Y',$sale->tire_sale_date)]['sale'])?$tire[date('d-m-Y',$sale->tire_sale_date)]['sale']+$sale->volume:$sale->volume;
        }

        foreach ($orders as $order) {
            $tire[date('d-m-Y',$order->tire_receive_date)]['order'] = isset($tire[date('d-m-Y',$order->tire_receive_date)]['order'])?$tire[date('d-m-Y',$order->tire_receive_date)]['order']+$order->tire_number:$order->tire_number;
        }

        $this->view->data['tire'] = $tire;
        
        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('inventory/index');
    }

    public function brand(){
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_brands = $tire_brand_model->getAllTire();

        $today = strtotime(date('d-m-Y'));
        $ngay = $this->registry->router->order_by;
        if ($ngay != "") {
            $today = strtotime($ngay);
        }

        $table = array();
        $table['cols'] = array(
            array('label' => 'Thương hiệu', 'type' => 'string'),
            array('label' => 'Tồn kho', 'type' => 'number'),
        );

        foreach ($tire_brands as $b) {
            
            $total = 0;

            $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_brand = '.$b->tire_brand_id.' AND tire_buy_date <= '.$today);  
            $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE tire_brand = '.$b->tire_brand_id.' AND customer != 119 AND tire_sale_date <= '.$today); 
            $orders = $tire_order_model->queryTire('SELECT tire_order_id, sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date <= '.$today);

            foreach ($buys as $buy) {
                $total += $buy->total_buy;
            }

            foreach ($sales as $sale) {
                $total -= $sale->total_sale;
            }

            foreach ($orders as $order) {
                $order_types = $tire_order_type_model->queryTire('SELECT sum(tire_order_type.tire_number) AS total_order FROM tire_order_type WHERE tire_order = '.$order->tire_order_id.' AND tire_order_type.tire_brand = '.$b->tire_brand_id);
                foreach ($order_types as $order_type) {
                    $total -= $order_type->total_order;
                }
                
            }

            
            $temp = array();
            $temp[] = array('v' => $b->tire_brand_name);
            $temp[] = array('v' => $total);
            $rows = array();
            $rows = array('c' => $temp);

            $table['rows'][] = $rows;
        }
       
        echo json_encode($table);
        
    }

    public function size(){
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_sizes = $tire_size_model->getAllTire();

        $today = strtotime(date('d-m-Y'));
        $ngay = $this->registry->router->order_by;
        if ($ngay != "") {
            $today = strtotime($ngay);
        }

        $table = array();
        $table['cols'] = array(
            array('label' => 'Kích cỡ', 'type' => 'string'),
        );

        $tire_brands = $tire_brand_model->getAllTire();
        foreach ($tire_brands as $b) {
            array_push($table['cols'], array('label' => $b->tire_brand_name, 'type' => 'number'));
        }

        array_push($table['cols'], array('type'=> 'string', 'role'=> 'annotation'));

        foreach ($tire_sizes as $s) {
            
            $temp = array();
            $temp[] = array('v' => $s->tire_size_number);

            foreach ($tire_brands as $b) {
                $total = 0;

                $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_size = '.$s->tire_size_id.' AND tire_buy_brand = '.$b->tire_brand_id.' AND tire_buy_date <= '.$today);  
                $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE tire_size = '.$s->tire_size_id.' AND tire_brand = '.$b->tire_brand_id.' AND customer != 119 AND tire_sale_date <= '.$today); 
                $orders = $tire_order_model->queryTire('SELECT tire_order_id, sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date <= '.$today);

                foreach ($buys as $buy) {
                    $total += $buy->total_buy;
                }

                foreach ($sales as $sale) {
                    $total -= $sale->total_sale;
                }

                foreach ($orders as $order) {
                    $order_types = $tire_order_type_model->queryTire('SELECT sum(tire_order_type.tire_number) AS total_order FROM tire_order_type WHERE tire_order = '.$order->tire_order_id.' AND tire_order_type.tire_size = '.$s->tire_size_id.' AND tire_order_type.tire_brand = '.$b->tire_brand_id);
                    foreach ($order_types as $order_type) {
                        $total -= $order_type->total_order;
                    }
                    
                }

                $temp[] = array('v' => $total);
            }

            $total = 0;

            $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_size = '.$s->tire_size_id.' AND tire_buy_date <= '.$today);  
            $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE tire_size = '.$s->tire_size_id.' AND customer != 119 AND tire_sale_date <= '.$today); 
            $orders = $tire_order_model->queryTire('SELECT tire_order_id, sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date <= '.$today);

            foreach ($buys as $buy) {
                $total += $buy->total_buy;
            }

            foreach ($sales as $sale) {
                $total -= $sale->total_sale;
            }

            foreach ($orders as $order) {
                $order_types = $tire_order_type_model->queryTire('SELECT sum(tire_order_type.tire_number) AS total_order FROM tire_order_type WHERE tire_order = '.$order->tire_order_id.' AND tire_order_type.tire_size = '.$s->tire_size_id);
                foreach ($order_types as $order_type) {
                    $total -= $order_type->total_order;
                }
                
            }

            $temp[] = array('v' => $total);


            $rows = array();
            $rows = array('c' => $temp);

            $table['rows'][] = $rows;
        }

        echo json_encode($table);
    }

    public function pattern(){
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');
        $tire_patterns = $tire_pattern_model->getAllTire();

        $today = strtotime(date('d-m-Y'));
        $ngay = $this->registry->router->order_by;
        if ($ngay != "") {
            $today = strtotime($ngay);
        }

        $table = array();
        $table['cols'] = array(
            array('label' => 'Mã gai', 'type' => 'string'),
        );

        $tire_sizes = $tire_size_model->getAllTire();
        foreach ($tire_sizes as $b) {
            array_push($table['cols'], array('label' => $b->tire_size_number, 'type' => 'number'));
        }

        array_push($table['cols'], array('type'=> 'string', 'role'=> 'annotation'));

        foreach ($tire_patterns as $s) {
            
            $temp = array();
            $temp[] = array('v' => $s->tire_pattern_name);

            foreach ($tire_sizes as $b) {
                $total = 0;

                $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_pattern = '.$s->tire_pattern_id.' AND tire_buy_size = '.$b->tire_size_id.' AND tire_buy_date <= '.$today);  
                $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE tire_pattern = '.$s->tire_pattern_id.' AND tire_size = '.$b->tire_size_id.' AND customer != 119 AND tire_sale_date <= '.$today); 
                $orders = $tire_order_model->queryTire('SELECT tire_order_id, sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date <= '.$today);

                foreach ($buys as $buy) {
                    $total += $buy->total_buy;
                }

                foreach ($sales as $sale) {
                    $total -= $sale->total_sale;
                }

                foreach ($orders as $order) {
                    $order_types = $tire_order_type_model->queryTire('SELECT sum(tire_order_type.tire_number) AS total_order FROM tire_order_type WHERE tire_order = '.$order->tire_order_id.' AND tire_order_type.tire_pattern = '.$s->tire_pattern_id.' AND tire_order_type.tire_size = '.$b->tire_size_id);
                    foreach ($order_types as $order_type) {
                        $total -= $order_type->total_order;
                    }
                    
                }

                $temp[] = array('v' => $total);
            }

            $total = 0;

            $buys = $tire_buy_model->queryTire('SELECT sum(tire_buy_volume) AS total_buy FROM tire_buy WHERE tire_buy_pattern = '.$s->tire_pattern_id.' AND tire_buy_date <= '.$today);  
            $sales = $tire_sale_model->queryTire('SELECT sum(volume) AS total_sale FROM tire_sale WHERE tire_pattern = '.$s->tire_pattern_id.' AND customer != 119 AND tire_sale_date <= '.$today); 
            $orders = $tire_order_model->queryTire('SELECT tire_order_id, sum(tire_number) AS total_order FROM tire_order WHERE (status IS NULL OR status != 1) AND tire_receive_date > 0 AND tire_receive_date <= '.$today);

            foreach ($buys as $buy) {
                $total += $buy->total_buy;
            }

            foreach ($sales as $sale) {
                $total -= $sale->total_sale;
            }

            foreach ($orders as $order) {
                $order_types = $tire_order_type_model->queryTire('SELECT sum(tire_order_type.tire_number) AS total_order FROM tire_order_type WHERE tire_order = '.$order->tire_order_id.' AND tire_order_type.tire_pattern = '.$s->tire_pattern_id);
                foreach ($order_types as $order_type) {
                    $total -= $order_type->total_order;
                }
                
            }

            $temp[] = array('v' => $total);


            $rows = array();
            $rows = array('c' => $temp);

            $table['rows'][] = $rows;
        }

        echo json_encode($table);
    }

       
    
}
?>