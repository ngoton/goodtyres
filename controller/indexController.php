<?php

Class indexController Extends baseController {

    public function index() {

        /*** set a template variable ***/

            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';

        /*** load the index template ***/

            $this->view->data['title'] = null;



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



            $join = array('table'=>'tire_producer, tire_product_size','where'=>'tire_producer=tire_producer_id AND tire_size=tire_product_size_id');



            $data = array(

                'order_by' => 'RAND()',

                'limit' => '10',

                'where' => 'tire_product_feature = 1',

            );



            $tire_product_features = $tire_product_model->getAllTire($data,$join);


            $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_position ASC, tire_producer_name ASC'));



            $this->view->data['tire_product_features'] = $tire_product_features;

            $this->view->data['tire_producers'] = $tire_producers;

            $this->view->data['list_tire_producers'] = $tire_producers;

            $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name','order'=>'ASC'));
            $tire_producer_data = array();
            foreach ($tire_producers as $tire) {
                $tire_producer_data[strtoupper(substr($tire->tire_producer_name, 0, 1))][] = $tire->tire_producer_name;
            }
            $this->view->data['tire_producer_data'] = $tire_producer_data;



            $this->view->show('index/index');

    }
    public function view() {

        /*** set a template variable ***/

            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';

        /*** load the index template ***/

            $this->view->data['title'] = 'Việt Trade | ';



            $this->view->data['lib'] = $this->lib;



            $tire_product_model = $this->model->get('tireproductModel');

            $tire_producer_model = $this->model->get('tireproducerModel');



            $join = array('table'=>'tire_producer, tire_product_size','where'=>'tire_producer=tire_producer_id AND tire_size=tire_product_size_id');



            $data = array(

                'order_by' => 'RAND()',

                'limit' => '10',

                'where' => 'tire_product_feature = 1',

            );



            $tire_product_features = $tire_product_model->getAllTire($data,$join);



            $data = array(

                'order_by' => 'RAND()',

                'limit' => '6',

                'where' => 'tire_product_plies = 1',

            );



            $tire_product_tbrs = $tire_product_model->getAllTire($data,$join);



            $data = array(

                'order_by' => 'RAND()',

                'limit' => '6',

                'where' => 'tire_product_plies = 2',

            );



            $tire_product_nylons = $tire_product_model->getAllTire($data,$join);



            $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_position ASC, tire_producer_name ASC'));



            $tire_products = array();

            foreach ($tire_producers as $tire_producer) {

                $data = array(

                    'order_by' => 'RAND()',

                    'limit' => '3',

                    'where' => 'tire_producer = '.$tire_producer->tire_producer_id,

                );



                $tire_products[$tire_producer->tire_producer_id] = $tire_product_model->getAllTire($data,$join);

            }





            $this->view->data['tire_product_features'] = $tire_product_features;

            $this->view->data['tire_product_tbrs'] = $tire_product_tbrs;

            $this->view->data['tire_product_nylons'] = $tire_product_nylons;

            $this->view->data['tire_products'] = $tire_products;

            $this->view->data['tire_producers'] = $tire_producers;



            $this->view->show('index/index');

    }




}

?>