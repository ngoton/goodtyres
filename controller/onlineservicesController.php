<?php



Class onlineservicesController Extends baseController {


    public function index() {

        /*** set a template variable ***/

            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';

        /*** load the index template ***/

            $this->view->data['title'] = 'Việt Trade';



            $this->view->data['lib'] = $this->lib;


            $post_model = $this->model->get('postModel');

            $data = array(

                'order_by' => 'post_date',

                'order' => 'DESC',

                'limit' => '10',

                'where' => '1=1',

            );

            $new_posts = $post_model->getAllPost($data);

            $this->view->data['new_posts'] = $new_posts;



            $tire_product_model = $this->model->get('tireproductModel');

            $tire_producer_model = $this->model->get('tireproducerModel');



            $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern','where'=>'tire_pattern=tire_product_pattern_id AND tire_producer=tire_producer_id AND tire_size=tire_product_size_id');



            $data = array(

                'order_by' => 'RAND()',

                'limit' => '10',

                'where' => 'tire_product_feature = 1',

            );

            $this->view->data['limit'] = 10;

            $tire_product_features = $tire_product_model->getAllTire($data,$join);


            $tire_producers = $tire_producer_model->getAllTire();



            $this->view->data['tire_product_features'] = $tire_product_features;

            $this->view->data['tire_producers'] = $tire_producers;

            $this->view->data['list_tire_producers'] = $tire_producers;

            $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name','order'=>'ASC'));
            $tire_producer_data = array();
            foreach ($tire_producers as $tire) {
                $tire_producer_data[strtoupper(substr($tire->tire_producer_name, 0, 1))][] = $tire->tire_producer_name;
            }
            $this->view->data['tire_producer_data'] = $tire_producer_data;

            $this->view->show('onlineservices/index');

    }

    public function tireorder() {

        $this->view->setLayout('detail');

        $this->view->data['title'] = 'Tra cứu đơn hàng';

        $this->view->data['lib'] = $this->lib;

        $post_model = $this->model->get('postModel');

            $data = array(

                'order_by' => 'post_date',

                'order' => 'DESC',

                'limit' => '10',

                'where' => '1=1',

            );

            $new_posts = $post_model->getAllPost($data);

            $this->view->data['new_posts'] = $new_posts;

        $tire_product_model = $this->model->get('tireproductModel');



            $join = array('table'=>'tire_producer, tire_product_size, tire_product_pattern','where'=>'tire_pattern=tire_product_pattern_id AND tire_producer=tire_producer_id AND tire_size=tire_product_size_id');

            $data = array(

                'order_by' => 'RAND()',

                'limit' => '10',

                'where' => 'tire_product_feature = 1',

            );



            $tire_product_features = $tire_product_model->getAllTire($data,$join);

            $link_breadcrumb = '

                <span class="arrow">›</span>        

                  <div itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">         

                    <a title="Dịch vụ trực tuyến" href="'.BASE_URL.'/onlineservices" itemprop="url">            

                      <span itemprop="title">Dịch vụ trực tuyến</span>

                    </a>

                  </div>
                <span class="arrow">›</span>        

                  <div itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">         

                    <a title="Tra cứu đơn hàng" href="'.BASE_URL.'/onlineservices/tireorder" itemprop="url">            

                      <span itemprop="title">Tra cứu đơn hàng</span>

                    </a>

                  </div>

                ';



            

            $this->view->data['tire_product_features'] = $tire_product_features;

            $this->view->data['link_breadcrumb'] = $link_breadcrumb;

            $order = $this->registry->router->param_id;
            
            if (substr($order, 0, 2) == "00" && substr($order, -1) == "1" ) {
                
                $order = substr(substr($order, 2),0,-1);

                $tire_order_model = $this->model->get('ordertireModel');
                $tire_order_list_model = $this->model->get('ordertirelistModel');
                $customer_model = $this->model->get('customerModel');
                $staff_model = $this->model->get('staffModel');
                $receive_model = $this->model->get('receiveModel');

                $orders = $tire_order_model->getTire($order);
                if ($orders) {

                    $staffs = $staff_model->getStaffByWhere(array('account'=>$orders->sale));
                    $customers = $customer_model->getCustomer($orders->customer);

                    $data = array('where'=>'order_tire = '.$order);
                    $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
                    $order_types = $tire_order_list_model->getAllTire($data,$join);


                    $join = array('table'=>'bank','where'=>'source = bank_id');
                    $data = array(
                        'order_by'=>'receive_date',
                        'order'=>'ASC',
                        'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE order_tire = '.$order.') AND receive_date <= '.strtotime(date('d-m-Y')),
                        );

                    $receives = $receive_model->getAllCosts($data,$join);

                    $this->view->data['receives'] = $receives;
                    $this->view->data['orders'] = $orders;
                    $this->view->data['staffs'] = $staffs;
                    $this->view->data['customers'] = $customers;
                    $this->view->data['order_types'] = $order_types;
                }
            
            }
            

        $this->view->show('onlineservices/tireorder');
    }

}



?>