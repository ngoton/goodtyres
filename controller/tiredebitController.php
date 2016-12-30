<?php
Class tiredebitController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ đơn hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 0;
            $tha = 0;
        }

        $customer_model = $this->model->get('customerModel');
        $customer = $customer_model->getCustomer($trangthai);
        $this->view->data['customer'] = $customer;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $tongsodong = count($customer_model->getAllCustomer());
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'1=1',
            );

        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $customers = $customer_model->getAllCustomer($data);
        $this->view->data['customers'] = $customers;
        $this->view->data['lastID'] = isset($customer_model->getLastCustomer()->customer_id)?$customer_model->getLastCustomer()->customer_id:0;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel'); 

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'1=1',
            );


        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $this->view->data['order_tires'] = $orders;

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$order->order_tire_number:$order->order_tire_number;
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$order->pay_money:$order->pay_money;
            $data_customer['sale'][$order->customer] = $order->username;
        }

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $data = array(
            'where'=>'1=1',
            );


        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        

        if ($keyword != '') {
            $search = '( code LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $receivables = $receivable_model->getAllCosts($data,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');

        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            );
            if ($nv > 0) {
                $data['where'] .= ' AND sale = '.$nv;
            }

            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                $data_customer['sale'][$order->customer] = $sale->username;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                );
                if ($nv > 0) {
                    $data['where'] .= ' AND sale = '.$nv;
                }

                $sales = $tire_sale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                    $data_customer['sale'][$order->customer] = $sale->username;
                }
            }
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$order->pay_money:$order->pay_money;
            }
            
        }


        $this->view->data['data_customer'] = $data_customer;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/index');
    }
    public function view() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ đơn hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = 0;
            $tha = 0;
        }

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getCustomer($trangthai);

        

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array(
            'order_by'=> 'staff_name',
            'order'=> 'ASC',
            ));

        $this->view->data['staffs'] = $staffs;

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        

        if ($tha == 0) {
            $data = array(
                'where'=>'(pay_money IS NULL OR pay_money < money)',
            );
        }
        else{
            $data = array(
                'where'=>'pay_money = money',
            );
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;

            $this->view->data['customers'] = $customers;
        }

        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where'=>'(pay_money IS NULL OR pay_money < money)',
            );

        if ($tha == 0) {
            $data['where'] = '(pay_money IS NULL OR pay_money < money)';
        }
        else{
            $data['where'] = 'pay_money = money';
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer_id = '.$trangthai;
        } 
        if ($nv > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$nv.') ';
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $orders = $order_tire_model->getAllTire($data,$join);

        $this->view->data['order_tires'] = $orders;
        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/view');
    }
    public function pay() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

        $id = $this->registry->router->param_id;

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'ASC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE order_tire = '.$id.')',
            );

        $receives = $receive_model->getAllCosts($data,$join);
        if (!$receives) {
            $data = array(
                'order_by'=>'receive_date',
                'order'=>'ASC',
                'where'=>'receivable  = '.$id,
                );

            $receives = $receive_model->getAllCosts($data,$join);
        }
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/pay');
    }
    public function customer() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        $id = $this->registry->router->param_id;
        $this->view->data['cus'] = $id;

        $order_tire_model = $this->model->get('ordertireModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'order_tire.customer = '.$id,
            );

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 
        $data = array(
            'order_by'=>'receivable.expect_date',
            'order'=>'DESC',
            'where'=>'customer_id = '.$id,
            );

        $receivables = $receivable_model->getAllCosts($data,$join);
        $this->view->data['receivables'] = $receivables;

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');
        
        $receivable_data = array();
        foreach ($receivables as $re) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$re->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$re->customer,
            );
            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $receivable_data[$re->receivable_id]['number'] = isset($receivable_data[$re->receivable_id]['number'])?$receivable_data[$re->receivable_id]['number']+$sale->volume:$sale->volume;
                $receivable_data[$re->receivable_id]['sale'] = $sale->username;
            }
        }
        $this->view->data['receivable_data'] = $receivable_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/customer');
    }
    public function cuspay() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đã thu';

        $id = $this->registry->router->param_id;

        $receive_model = $this->model->get('receiveModel');

        $join = array('table'=>'bank','where'=>'source = bank_id');
        $data = array(
            'order_by'=>'receive_date',
            'order'=>'ASC',
            'where'=>'receivable IN (SELECT receivable_id FROM receivable WHERE customer = '.$id.')',
            );

        $receives = $receive_model->getAllCosts($data,$join);
        $this->view->data['receives'] = $receives;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/cuspay');
    }
    public function cus() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn hàng';

        $id = $this->registry->router->param_id;

        $order_tire_model = $this->model->get('ordertireModel');

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'order_by'=>'delivery_date',
            'order'=>'DESC',
            'where'=>'(pay_money IS NULL OR pay_money < money) AND order_tire.customer = '.$id,
            );

        $orders = $order_tire_model->getAllTire($data,$join);
        $this->view->data['orders'] = $orders;

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 
        $data = array(
            'order_by'=>'receivable.expect_date',
            'order'=>'DESC',
            'where'=>'(pay_money IS NULL OR pay_money < money) AND customer_id = '.$id,
            );

        $receivables = $receivable_model->getAllCosts($data,$join);
        $this->view->data['receivables'] = $receivables;

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');
        
        $receivable_data = array();
        foreach ($receivables as $re) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$re->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$re->customer,
            );
            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $receivable_data[$re->receivable_id]['number'] = isset($receivable_data[$re->receivable_id]['number'])?$receivable_data[$re->receivable_id]['number']+$sale->volume:$sale->volume;
                $receivable_data[$re->receivable_id]['sale'] = $sale->username;
            }
        }
        $this->view->data['receivable_data'] = $receivable_data;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tiredebit/cus');
    }

    function export(){

        $this->view->disableLayout();

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }



        $kh = $this->registry->router->param_id;

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $receivable_model = $this->model->get('receivableModel');

        $join = array('table'=>'customer','where'=>'customer_id = customer');


        if($kh > 0){

            $data['where'] = 'customer = '.$kh;

        }

        

        /*if ($_SESSION['role_logined'] == 3) {

            $data['where'] = $data['where'].' AND shipment_create_user = '.$_SESSION['userid_logined'];

            

        }*/


        $data['order_by'] = 'order_number';

        $data['order'] = 'ASC';



        $orders = $order_tire_model->getAllTire($data,$join);

        

            require("lib/Classes/PHPExcel/IOFactory.php");

            require("lib/Classes/PHPExcel.php");



            $objPHPExcel = new PHPExcel();



            



            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A1', 'CÔNG TY TNHH VIỆT TRA DE')

                ->setCellValue('A2', 'PHÒNG KINH DOANH')

                ->setCellValue('I1', 'CỘNG HÒA XÃ CHỦ NGHĨA VIỆT NAM')

                ->setCellValue('I2', 'Độc lập - Tự do - Hạnh phúc')

                ->setCellValue('A4', 'BẢNG KÊ MUA HÀNG LỐP XE')

                ->setCellValue('A6', 'STT')

               ->setCellValue('B6', 'Ngày')

               ->setCellValue('C6', 'Số ĐH')

               ->setCellValue('D6', 'Tên hàng')

               ->setCellValue('E6', 'Loại hàng')

               ->setCellValue('F6', 'Số lượng')

               ->setCellValue('G6', 'Đơn giá')

               ->setCellValue('H6', 'Thành tiền')

               ->setCellValue('I6', 'Trừ giảm')

               ->setCellValue('J6', 'Đã TT')

               ->setCellValue('K6', 'KH Phải trải')

               ->setCellValue('L6', 'Ghi chú');

               


            if ($orders) {



                $hang = 7;

                $i=1;


                $k=0;
                foreach ($orders as $row) {

                    $receivable = $receivable_model->getCostsByWhere(array('order_tire'=>$row->order_tire_id));

                    $tencongty = $row->company_name;

                    $sohang = $hang;

                    $join_order = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id');
                    $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$row->order_tire_id), $join_order);
                    if ($order_lists) {

                        
                        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

                         $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $this->lib->hien_thi_ngay_thang($row->order_tire_date))

                            ->setCellValue('C' . $hang, $row->order_number);


                        foreach ($order_lists as $order_list) {

                            $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('D' . $hang, $order_list->tire_brand_name)

                            ->setCellValue('E' . $hang, $order_list->tire_size_number.' '.$order_list->tire_pattern_name)

                            ->setCellValue('F' . $hang, $order_list->tire_number)

                            ->setCellValue('G' . $hang, ($row->check_price_vat==1?$order_list->tire_price_vat:$order_list->tire_price)+($row->vat/$row->order_tire_number))

                            ->setCellValue('H' . $hang, '=F'.$hang.'*G'.$hang)

                            ->setCellValue('I' . $hang, $row->discount+$row->reduce)

                            ->setCellValue('J' . $hang, $receivable->pay_money);

                            $hang++;

                        }

                        $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('K' . $sohang, '=SUM(H'.$sohang.':H'.($hang-1).')-J'.$sohang.'-I'.$sohang);

                        $objPHPExcel->getActiveSheet()->mergeCells('A'.$sohang.':A'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('B'.$sohang.':B'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('C'.$sohang.':C'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('I'.$sohang.':I'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('J'.$sohang.':J'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('K'.$sohang.':K'.($hang-1));
                        $objPHPExcel->getActiveSheet()->mergeCells('L'.$sohang.':L'.($hang-1));


                      }

                }

            }

            $objPHPExcel->getActiveSheet()->getStyle('I7:L'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.$hang, 'TỔNG CỘNG')

                ->setCellValue('F'.$hang, '=SUM(F7:F'.($hang-1).')')

               ->setCellValue('K'.$hang, '=SUM(K7:K'.($hang-1).')');



            $objPHPExcel->getActiveSheet()->getStyle('A6:L'.$hang)->applyFromArray(

                array(

                    

                    'borders' => array(

                        'allborders' => array(

                          'style' => PHPExcel_Style_Border::BORDER_THIN

                        )

                    )

                )

            );



            $cell = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(10, $hang)->getCalculatedValue();

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.($hang+2), 'Bằng chữ: ');

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

            ->setCellValue('B'.($hang+2), $this->lib->convert_number_to_words(round($cell)).' đồng');



            $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':E'.$hang);

            $objPHPExcel->getActiveSheet()->mergeCells('B'.($hang+2).':L'.($hang+2));


            $objPHPExcel->getActiveSheet()->getRowDimension($hang+1)->setRowHeight(8);


            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);





            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A'.($hang+4), 'NGƯỜI LẬP BIỂU')

                ->setCellValue('E'.($hang+4), 'CÔNG TY TNHH VIỆT TRA DE')

               ->setCellValue('I'.($hang+4), mb_strtoupper($tencongty, "UTF-8"));



            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+4).':D'.($hang+4));

            $objPHPExcel->getActiveSheet()->mergeCells('E'.($hang+4).':H'.($hang+4));

            $objPHPExcel->getActiveSheet()->mergeCells('I'.($hang+4).':L'.($hang+4));


            $objPHPExcel->getActiveSheet()->getStyle('A6:E'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A6:E'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':L'.($hang+4))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':L'.($hang+4))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);



            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':L'.($hang+4))->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->getStyle('B'.($hang+2))->getFont()->setBold(false);
            $objPHPExcel->getActiveSheet()->getStyle('B'.($hang+2))->getFont()->setItalic(true);



            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



            $highestRow ++;



            $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');

            $objPHPExcel->getActiveSheet()->mergeCells('I1:L1');

            $objPHPExcel->getActiveSheet()->mergeCells('A2:D2');

            $objPHPExcel->getActiveSheet()->mergeCells('I2:L2');



            $objPHPExcel->getActiveSheet()->mergeCells('A4:L4');



            $objPHPExcel->getActiveSheet()->getStyle('A1:L4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:L4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('I2')->getFont()->setItalic(true);




            $objPHPExcel->getActiveSheet()->getStyle('A1:L4')->applyFromArray(

                array(

                    

                    'font' => array(

                        'bold'  => true,

                        'color' => array('rgb' => '000000')

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->applyFromArray(

                array(

                    

                    'font' => array(

                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,

                    )

                )

            );



            $objPHPExcel->getActiveSheet()->getStyle('G7:L'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A6:L6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A6:L6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A6:L6')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);

            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(18);

            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);

            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:L'.$highestRow)->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A1:L'.$highestRow)->getFont()->setSize(12);

            $objPHPExcel->getActiveSheet()->getStyle("A4")->getFont()->setSize(18);



            // Set properties

            $objPHPExcel->getProperties()->setCreator("VT")

                            ->setLastModifiedBy($_SESSION['user_logined'])

                            ->setTitle("Sale Report")

                            ->setSubject("Sale Report")

                            ->setDescription("Sale Report.")

                            ->setKeywords("Sale Report")

                            ->setCategory("Sale Report");

            $objPHPExcel->getActiveSheet()->setTitle("Bang ke san luong");



            $objPHPExcel->getActiveSheet()->freezePane('A7');

            $objPHPExcel->setActiveSheetIndex(0);







            



            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            header("Content-Disposition: attachment; filename= BẢNG KÊ MUA HÀNG.xlsx");

            header("Cache-Control: max-age=0");

            ob_clean();

            $objWriter->save("php://output");

        

    }


}
?>