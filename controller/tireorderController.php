<?php
Class tireorderController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }


        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');

        $tire_brands = $tire_brand_model->getAllTire();
        $tire_sizes = $tire_size_model->getAllTire();

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;

        $join = array('table'=>'tire_brand, tire_size, customer, tire_pattern','where'=>'tire_order.customer=customer.customer_id AND tire_brand.tire_brand_id = tire_order.tire_brand AND tire_size.tire_size_id = tire_order.tire_size AND tire_pattern.tire_pattern_id = tire_order.tire_pattern');

        $tire_order_model = $this->model->get('tireorderModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        if ($_SESSION['role_logined'] == 4) {
            $data['where'] .= ' AND tire_order_sale = '.$_SESSION['userid_logined'];
        }
        
        $tongsodong = count($tire_order_model->getAllTire($data,$join));
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
            
            'where' => '1=1',
            );

        if ($_SESSION['role_logined'] == 4) {
            $data['where'] .= ' AND tire_order_sale = '.$_SESSION['userid_logined'];
        }
      
        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" 
                OR tire_brand_name LIKE "%'.$keyword.'%" 
                OR tire_size_number LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_orders'] = $tire_order_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_order_model->getLastTire()->tire_order_id)?$tire_order_model->getLastTire()->tire_order_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireorder/index');
    }
    public function view() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngay = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $kh = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $ngay = "";
            $kh = 0;
        }

        $chonngay = $this->registry->router->param_id;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'customer_name',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $tire_order_model = $this->model->get('tireorderModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );

        if ($chonngay != "") {
            $ngay = date('d-m-Y',$chonngay);
            $data['where'] .= ' AND (status IS NULL OR status != 1)';
        }

        if ($ngay != "") {
            $data['where'] .= ' AND tire_receive_date > '.strtotime("-1 day",$chonngay).' AND tire_receive_date < '.strtotime("+1 day",$chonngay);
        }

        if ($kh > 0) {
            $data['where'] .= ' AND customer = '.$kh;
        }
        
        $join = array('table'=>'customer','where'=>'tire_order.customer=customer.customer_id ');

        $tongsodong = count($tire_order_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['ngay'] = $ngay;
        $this->view->data['kh'] = $kh;

        $data = array(
            
            'where' => '1=1',
            );
        if ($chonngay != "") {
            $ngay = date('d-m-Y',$chonngay);
            $data['where'] .= ' AND (status IS NULL OR status != 1)';
        }

        if ($ngay != "") {
            $data['where'] .= ' AND tire_receive_date > '.strtotime("-1 day",$chonngay).' AND tire_receive_date < '.strtotime("+1 day",$chonngay);
        }

        if ($kh > 0) {
            $data['where'] .= ' AND customer = '.$kh;
        }

      
        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_orders'] = $tire_order_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_order_model->getLastTire()->tire_order_id)?$tire_order_model->getLastTire()->tire_order_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireorder/view');
    }

    public function contract() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getCustomer($this->registry->router->param_id);


        $info = $this->registry->router->addition;
        
        $arr = explode('@', $info);

        $this->view->data['company'] = strtoupper($customers->company_name);
        $this->view->data['mst'] = $customers->mst;
        $this->view->data['address'] = $customers->customer_address;
        $this->view->data['phone'] = $customers->customer_phone;
        $this->view->data['fax'] = $customers->customer_fax;
        $this->view->data['bank_number'] = $customers->account_number;
        $this->view->data['bank'] = $customers->customer_bank_name;
        $this->view->data['name'] = $customers->director;

        $this->view->data['contract_date'] = explode('-', $arr[0]);
        $this->view->data['contract_number'] = $arr[1];
        $this->view->data['contract_pay'] = $arr[2];
        $this->view->data['contract_pay2'] = $arr[3];
        $this->view->data['contract_valid'] = str_replace('-', '/', $arr[4]);
                
        $this->view->show('tireorder/contract');
    }

    public function contractcm() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getCustomer($this->registry->router->param_id);


        $info = $this->registry->router->addition;
        
        $arr = explode('@', $info);

        $this->view->data['company'] = strtoupper($customers->company_name);
        $this->view->data['mst'] = $customers->mst;
        $this->view->data['address'] = $customers->customer_address;
        $this->view->data['phone'] = $customers->customer_phone;
        $this->view->data['fax'] = $customers->customer_fax;
        $this->view->data['bank_number'] = $customers->account_number;
        $this->view->data['bank'] = $customers->customer_bank_name;
        $this->view->data['name'] = $customers->director;

        $this->view->data['contract_date'] = explode('-', $arr[0]);
        $this->view->data['contract_number'] = $arr[1];
        $this->view->data['contract_pay'] = $arr[2];
        $this->view->data['contract_pay2'] = $arr[3];
        $this->view->data['contract_valid'] = str_replace('-', '/', $arr[4]);
                
        $this->view->show('tireorder/contractcm');
    }

    function bangke(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'tire_order = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand.tire_brand_id=tire_order_type.tire_brand AND tire_size.tire_size_id=tire_order_type.tire_size AND tire_pattern.tire_pattern_id=tire_order_type.tire_pattern');
        $order_types = $tire_order_type_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'Đơn vị bán hàng: CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A2', 'Địa chỉ: Số 545, Tổ 10, Ấp Hương Phước, Phước Tân, Biên Hòa, Đồng Nai')
                ->setCellValue('A3', 'MST: 3603295302')
                ->setCellValue('A4', 'Điện thoại: 0613 937 677')
                ->setCellValue('A6', 'BẢNG KÊ')
                ->setCellValue('G7', 'TP Biên Hòa, Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
               ->setCellValue('A9', 'Kính gửi: '.$customers->company_name)
               ->setCellValue('A10', 'Địa chỉ: '.$customers->customer_address)
               ->setCellValue('A11', 'MST: '.$customers->mst)
               ->setCellValue('A12', 'Đề nghị thanh toán: Tiền lốp xe')
               ->setCellValue('A13', 'STT')
               ->setCellValue('B13', 'TÊN HÀNG')
               ->setCellValue('C13', 'LOẠI HÀNG')
               ->setCellValue('D13', 'ĐƠN VỊ')
               ->setCellValue('E13', 'SỐ LƯỢNG')
               ->setCellValue('F13', 'ĐƠN GIÁ')
               ->setCellValue('G13', 'THÀNH TIỀN')
               ->setCellValue('H13', 'GHI CHÚ');
               

            

            
            
            

            if ($order_types) {

                $hang = 14;
                $i=1;

                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe')
                        ->setCellValue('C' . $hang, $row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('D' . $hang, 'Cái')
                        ->setCellValue('E' . $hang, $row->tire_number)
                        ->setCellValue('F' . $hang, $row->tire_price)
                        ->setCellValue('G' . $hang, '=E'.$hang.'*F'.$hang)
                        ->setCellValue('H' . $hang, 'Giao hàng tại kho khách hàng');
                     $hang++;


                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=SUM(G7:G'.($hang-1).')')
                       ->setCellValue('H'.$hang, 'Đã bao gồm 10% VAT');

                    $objPHPExcel->getActiveSheet()->getStyle('A6:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+3), 'XÁC NHẬN KHÁCH HÀNG')
                        ->setCellValue('G'.($hang+3), 'NGƯỜI LẬP');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+3).':D'.($hang+3));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+3).':H'.($hang+3));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':H'.($hang+3))->applyFromArray(
                        array(
                            
                            'font' => array(
                                'bold'  => true,
                                'color' => array('rgb' => '000000')
                            )
                        )
                    );

          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('A4:H4');
            $objPHPExcel->getActiveSheet()->mergeCells('A6:H6');
            $objPHPExcel->getActiveSheet()->mergeCells('G7:H7');

            $objPHPExcel->getActiveSheet()->mergeCells('A9:H9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:H11');
            $objPHPExcel->getActiveSheet()->mergeCells('A12:H12');

            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A1:H13')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            

            $objPHPExcel->getActiveSheet()->getStyle('F14:G'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("List")
                            ->setSubject("List")
                            ->setDescription("List.")
                            ->setKeywords("List")
                            ->setCategory("List");
            $objPHPExcel->getActiveSheet()->setTitle("Bang ke");

            $objPHPExcel->getActiveSheet()->freezePane('A13');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG KÊ.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    function bangkecm(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'tire_order = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand.tire_brand_id=tire_order_type.tire_brand AND tire_size.tire_size_id=tire_order_type.tire_size AND tire_pattern.tire_pattern_id=tire_order_type.tire_pattern');
        $order_types = $tire_order_type_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'Đơn vị bán hàng: CÔNG TY TNHH MTV Tiếp Vận Cái Mép')
                ->setCellValue('A2', 'Địa chỉ: Số 29, Tổ 1, Ấp Đồng, Phước Tân, Biên Hòa, Đồng Nai')
                ->setCellValue('A3', 'MST: 3603205852')
                ->setCellValue('A4', 'Điện thoại: 0613 937 677')
                ->setCellValue('A6', 'BẢNG KÊ')
                ->setCellValue('G7', 'TP Biên Hòa, Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
               ->setCellValue('A9', 'Kính gửi: '.$customers->company_name)
               ->setCellValue('A10', 'Địa chỉ: '.$customers->customer_address)
               ->setCellValue('A11', 'MST: '.$customers->mst)
               ->setCellValue('A12', 'Đề nghị thanh toán: Tiền lốp xe')
               ->setCellValue('A13', 'STT')
               ->setCellValue('B13', 'TÊN HÀNG')
               ->setCellValue('C13', 'LOẠI HÀNG')
               ->setCellValue('D13', 'ĐƠN VỊ')
               ->setCellValue('E13', 'SỐ LƯỢNG')
               ->setCellValue('F13', 'ĐƠN GIÁ')
               ->setCellValue('G13', 'THÀNH TIỀN')
               ->setCellValue('H13', 'GHI CHÚ');
               

            

            
            
            

            if ($order_types) {

                $hang = 14;
                $i=1;

                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe')
                        ->setCellValue('C' . $hang, $row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('D' . $hang, 'Cái')
                        ->setCellValue('E' . $hang, $row->tire_number)
                        ->setCellValue('F' . $hang, $row->tire_price)
                        ->setCellValue('G' . $hang, '=E'.$hang.'*F'.$hang)
                        ->setCellValue('H' . $hang, 'Giao hàng tại kho khách hàng');
                     $hang++;


                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=SUM(G7:G'.($hang-1).')')
                       ->setCellValue('H'.$hang, 'Đã bao gồm 10% VAT');

                    $objPHPExcel->getActiveSheet()->getStyle('A6:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+3), 'XÁC NHẬN KHÁCH HÀNG')
                        ->setCellValue('G'.($hang+3), 'NGƯỜI LẬP');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+3).':D'.($hang+3));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+3).':H'.($hang+3));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':H'.($hang+3))->applyFromArray(
                        array(
                            
                            'font' => array(
                                'bold'  => true,
                                'color' => array('rgb' => '000000')
                            )
                        )
                    );

          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('A4:H4');
            $objPHPExcel->getActiveSheet()->mergeCells('A6:H6');
            $objPHPExcel->getActiveSheet()->mergeCells('G7:H7');

            $objPHPExcel->getActiveSheet()->mergeCells('A9:H9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:H11');
            $objPHPExcel->getActiveSheet()->mergeCells('A12:H12');

            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A1:H13')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            

            $objPHPExcel->getActiveSheet()->getStyle('F14:G'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("List")
                            ->setSubject("List")
                            ->setDescription("List.")
                            ->setKeywords("List")
                            ->setCategory("List");
            $objPHPExcel->getActiveSheet()->setTitle("Bang ke");

            $objPHPExcel->getActiveSheet()->freezePane('A13');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG KÊ.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    function invoice(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'tire_order = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand.tire_brand_id=tire_order_type.tire_brand AND tire_size.tire_size_id=tire_order_type.tire_size AND tire_pattern.tire_pattern_id=tire_order_type.tire_pattern');
        $order_types = $tire_order_type_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'HÓA ĐƠN GTGT')
                ->setCellValue('A2', 'Liên 2: Giao cho người mua')
                ->setCellValue('G3', 'Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
                ->setCellValue('A5', 'Đơn vị bán hàng: ')
                ->setCellValue('B5', 'CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A6', 'MST: ')
                ->setCellValue('B6', "'3603295302")
                ->setCellValue('A7', 'Địa chỉ: ')
                ->setCellValue('B7', 'Số 545, Tổ 10, Ấp Hương Phước, xã Phước Tân, TP.Biên Hòa, Đồng Nai')
                ->setCellValue('A8', 'Điện thoại: ')
                ->setCellValue('B8', '0613 937 677')
                ->setCellValue('C8', 'STK: ')
                ->setCellValue('D8', '200970509 ')
                ->setCellValue('E8', 'ACB Biên Hòa')
                ->setCellValue('A9', 'Họ tên người mua hàng: ')
                ->setCellValue('C9', $customers->customer_name)
                ->setCellValue('A10', 'Tên đơn vị: ')
               ->setCellValue('B10', $customers->company_name)
               ->setCellValue('A11', 'Mã số thuế: ')
               ->setCellValue('B11', "'".$customers->mst)
               ->setCellValue('A12', 'Địa chỉ: ')
               ->setCellValue('B12', $customers->customer_address)
               ->setCellValue('A13', 'STK: ')
               ->setCellValue('A14', 'STT')
               ->setCellValue('B14', 'Tên Hàng Hóa Dịch Vụ')
               ->setCellValue('F14', 'Đơn Vị Tính')
               ->setCellValue('G14', 'Số Lượng')
               ->setCellValue('H14', 'Đơn Giá')
               ->setCellValue('I14', 'Thành Tiền')
               ->setCellValue('A16', '1')
               ->setCellValue('B16', '2')
               ->setCellValue('F16', '3')
               ->setCellValue('G16', '4')
               ->setCellValue('H16', '5')
               ->setCellValue('I16', '6 = 4x5');
               
            
            

            if ($order_types) {

                $hang = 17;
                $i=1;
                $tong = 0;
                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe '.$row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('F' . $hang, 'Cái')
                        ->setCellValue('G' . $hang, $row->tire_number)
                        ->setCellValue('H' . $hang, round($row->tire_price/1.1))
                        ->setCellValue('I' . $hang, '=G'.$hang.'*H'.$hang);
                     $hang++;

                     $tong += $row->tire_number*round($row->tire_price/1.1);

                     $objPHPExcel->getActiveSheet()->getStyle('B'.$hang.':I'.$hang)->applyFromArray(
                            array(
                                'font' => array(
                                    'color' => array('rgb' => '5bc0de')
                                ),
                            )
                        );

                  }

                  $tong = round($tong+($tong*0.1));

                  for ($j=0; $j < 5; $j++) { 
                      $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' .$hang, null)
                        ->setCellValue('F' . $hang, null)
                        ->setCellValue('G' . $hang, null)
                        ->setCellValue('H' . $hang, null)
                        ->setCellValue('I' . $hang, null);
                     $hang++;
                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('D'.($hang+1), 'Cộng tiền hàng:')
                       ->setCellValue('I'.($hang+1), '=SUM(I17:I'.($hang-2).')')
                       ->setCellValue('D'.($hang+2), 'Tiền thuế GTGT:')
                       ->setCellValue('I'.($hang+2), '=I'.($hang+1).'*10%')
                       ->setCellValue('D'.($hang+3), 'Tổng cộng tiền thanh toán:')
                       ->setCellValue('I'.($hang+3), '=I'.($hang+2).'+I'.($hang+1))
                       ->setCellValue('A'.($hang+4), 'Viết bằng chữ:')
                       ->setCellValue('C'.($hang+4), $this->lib->convert_number_to_words($tong));

                    $objRichText = new PHPExcel_RichText();
                    $textBold = $objRichText->createTextRun("Thuế suất GTGT: ");

                    $under = $objRichText->createTextRun('  10%');
                    $under->getFont()->setBold(true);
                    $under->getFont()->setItalic(true);

                    $objPHPExcel->getActiveSheet()->getCell('A'.($hang+2))->setValue($objRichText);


                    $objPHPExcel->getActiveSheet()->getStyle('A14:I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  

                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+7), '(Kí ghi rõ họ tên)')
                        ->setCellValue('G'.($hang+7), '(Kí ghi rõ họ tên, đóng dấu)');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+7).':B'.($hang+7));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+7).':H'.($hang+7));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:I2');
            $objPHPExcel->getActiveSheet()->mergeCells('G3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('B4:I4');
            $objPHPExcel->getActiveSheet()->mergeCells('B5:I5');
            $objPHPExcel->getActiveSheet()->mergeCells('B6:I6');

            $objPHPExcel->getActiveSheet()->mergeCells('B7:I7');
            $objPHPExcel->getActiveSheet()->mergeCells('E8:I8');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:B9');
            $objPHPExcel->getActiveSheet()->mergeCells('C9:I9');
            $objPHPExcel->getActiveSheet()->mergeCells('B10:I10');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:I11');
            $objPHPExcel->getActiveSheet()->mergeCells('B12:I12');
            $objPHPExcel->getActiveSheet()->mergeCells('B13:I13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:E15');
            $objPHPExcel->getActiveSheet()->mergeCells('A14:A15');
            $objPHPExcel->getActiveSheet()->mergeCells('F14:F15');
            $objPHPExcel->getActiveSheet()->mergeCells('G14:G15');
            $objPHPExcel->getActiveSheet()->mergeCells('H14:H15');
            $objPHPExcel->getActiveSheet()->mergeCells('I14:I15');


            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+1).':I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            $objPHPExcel->getActiveSheet()->getStyle('B5:B13')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('D8:E8')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('I17:I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );



            

            $objPHPExcel->getActiveSheet()->getStyle('H17:I'.$hang)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('I'.$hang.':I'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(10);

            $objPHPExcel->getActiveSheet()->getStyle("A1:I".($highestRow+1))->getFont()->setName('Times New Roman');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Invoice")
                            ->setSubject("Invoice")
                            ->setDescription("Invoice.")
                            ->setKeywords("Invoice")
                            ->setCategory("Invoice");
            $objPHPExcel->getActiveSheet()->setTitle("Hoa don");

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= HÓA ĐƠN NHÁP.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    function invoicecm(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('tireorderModel');
        $tire_order_type_model = $this->model->get('tireordertypeModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'tire_order = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand.tire_brand_id=tire_order_type.tire_brand AND tire_size.tire_size_id=tire_order_type.tire_size AND tire_pattern.tire_pattern_id=tire_order_type.tire_pattern');
        $order_types = $tire_order_type_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'HÓA ĐƠN GTGT')
                ->setCellValue('A2', 'Liên 2: Giao cho người mua')
                ->setCellValue('G3', 'Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
                ->setCellValue('A5', 'Đơn vị bán hàng: ')
                ->setCellValue('B5', 'CÔNG TY TNHH MTV Tiếp Vận Cái Mép')
                ->setCellValue('A6', 'MST: ')
                ->setCellValue('B6', "'3603205852")
                ->setCellValue('A7', 'Địa chỉ: ')
                ->setCellValue('B7', 'Số 29, Tổ 1, Ấp Đồng, xã Phước Tân, TP.Biên Hòa, Đồng Nai')
                ->setCellValue('A8', 'Điện thoại: ')
                ->setCellValue('B8', '0613 937 677')
                ->setCellValue('C8', 'STK: ')
                ->setCellValue('D8', '186663099 ')
                ->setCellValue('E8', 'ACB Biên Hòa')
                ->setCellValue('A9', 'Họ tên người mua hàng: ')
                ->setCellValue('C9', $customers->customer_name)
                ->setCellValue('A10', 'Tên đơn vị: ')
               ->setCellValue('B10', $customers->company_name)
               ->setCellValue('A11', 'Mã số thuế: ')
               ->setCellValue('B11', "'".$customers->mst)
               ->setCellValue('A12', 'Địa chỉ: ')
               ->setCellValue('B12', $customers->customer_address)
               ->setCellValue('A13', 'STK: ')
               ->setCellValue('A14', 'STT')
               ->setCellValue('B14', 'Tên Hàng Hóa Dịch Vụ')
               ->setCellValue('F14', 'Đơn Vị Tính')
               ->setCellValue('G14', 'Số Lượng')
               ->setCellValue('H14', 'Đơn Giá')
               ->setCellValue('I14', 'Thành Tiền')
               ->setCellValue('A16', '1')
               ->setCellValue('B16', '2')
               ->setCellValue('F16', '3')
               ->setCellValue('G16', '4')
               ->setCellValue('H16', '5')
               ->setCellValue('I16', '6 = 4x5');
               
            
            

            if ($order_types) {

                $hang = 17;
                $i=1;
                $tong = 0;
                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe '.$row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('F' . $hang, 'Cái')
                        ->setCellValue('G' . $hang, $row->tire_number)
                        ->setCellValue('H' . $hang, round($row->tire_price/1.1))
                        ->setCellValue('I' . $hang, '=G'.$hang.'*H'.$hang);
                     $hang++;

                     $tong += $row->tire_number*round($row->tire_price/1.1);

                     $objPHPExcel->getActiveSheet()->getStyle('B'.$hang.':I'.$hang)->applyFromArray(
                            array(
                                'font' => array(
                                    'color' => array('rgb' => '5bc0de')
                                ),
                            )
                        );
                  }

                  $tong = round($tong+($tong*0.1));

                  for ($j=0; $j < 5; $j++) { 
                      $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' .$hang, null)
                        ->setCellValue('F' . $hang, null)
                        ->setCellValue('G' . $hang, null)
                        ->setCellValue('H' . $hang, null)
                        ->setCellValue('I' . $hang, null);
                     $hang++;
                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('D'.($hang+1), 'Cộng tiền hàng:')
                       ->setCellValue('I'.($hang+1), '=SUM(I17:I'.($hang-2).')')
                       ->setCellValue('D'.($hang+2), 'Tiền thuế GTGT:')
                       ->setCellValue('I'.($hang+2), '=I'.($hang+1).'*10%')
                       ->setCellValue('D'.($hang+3), 'Tổng cộng tiền thanh toán:')
                       ->setCellValue('I'.($hang+3), '=I'.($hang+2).'+I'.($hang+1))
                       ->setCellValue('A'.($hang+4), 'Viết bằng chữ:')
                       ->setCellValue('C'.($hang+4), $this->lib->convert_number_to_words($tong));

                    $objRichText = new PHPExcel_RichText();
                    $textBold = $objRichText->createTextRun("Thuế suất GTGT: ");

                    $under = $objRichText->createTextRun('  10%');
                    $under->getFont()->setBold(true);
                    $under->getFont()->setItalic(true);

                    $objPHPExcel->getActiveSheet()->getCell('A'.($hang+2))->setValue($objRichText);


                    $objPHPExcel->getActiveSheet()->getStyle('A14:I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  

                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+7), '(Kí ghi rõ họ tên)')
                        ->setCellValue('G'.($hang+7), '(Kí ghi rõ họ tên, đóng dấu)');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+7).':B'.($hang+7));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+7).':H'.($hang+7));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


          }

          $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+1).':I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:I2');
            $objPHPExcel->getActiveSheet()->mergeCells('G3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('B4:I4');
            $objPHPExcel->getActiveSheet()->mergeCells('B5:I5');
            $objPHPExcel->getActiveSheet()->mergeCells('B6:I6');

            $objPHPExcel->getActiveSheet()->mergeCells('B7:I7');
            $objPHPExcel->getActiveSheet()->mergeCells('E8:I8');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:B9');
            $objPHPExcel->getActiveSheet()->mergeCells('C9:I9');
            $objPHPExcel->getActiveSheet()->mergeCells('B10:I10');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:I11');
            $objPHPExcel->getActiveSheet()->mergeCells('B12:I12');
            $objPHPExcel->getActiveSheet()->mergeCells('B13:I13');

            $objPHPExcel->getActiveSheet()->mergeCells('B14:E15');
            $objPHPExcel->getActiveSheet()->mergeCells('A14:A15');
            $objPHPExcel->getActiveSheet()->mergeCells('F14:F15');
            $objPHPExcel->getActiveSheet()->mergeCells('G14:G15');
            $objPHPExcel->getActiveSheet()->mergeCells('H14:H15');
            $objPHPExcel->getActiveSheet()->mergeCells('I14:I15');

            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('B5:B13')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('D8:E8')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('I17:I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+4).':I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );
            

            $objPHPExcel->getActiveSheet()->getStyle('H17:I'.$hang)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('I'.$hang.':I'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(10);

            $objPHPExcel->getActiveSheet()->getStyle("A1:I".($highestRow+1))->getFont()->setName('Times New Roman');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Invoice")
                            ->setSubject("Invoice")
                            ->setDescription("Invoice.")
                            ->setKeywords("Invoice")
                            ->setCategory("Invoice");
            $objPHPExcel->getActiveSheet()->setTitle("Hoa don");

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= HÓA ĐƠN NHÁP.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    public function receive(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {

            $tire = $this->model->get('tireorderModel');

            $data = array(
                        
                        'status' => 1,
                        );
          
            $tire->updateTire($data,array('tire_order_id' => $_POST['data']));
                    
        }
    }

       
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_order_model = $this->model->get('tireorderModel');
            $data = array(
                        
                        'tire_receive_date' => strtotime($_POST['tire_receive_date']),
                        'tire_number' => trim($_POST['tire_number']),
                        'tire_brand' => trim($_POST['tire_brand']),
                        'tire_size' => trim($_POST['tire_size']),
                        'tire_price' => trim(str_replace(',','',$_POST['tire_price'])),
                        'customer' => trim($_POST['customer']),
                        'tire_order_date' => strtotime($_POST['tire_order_date']),
                        
                        );
            if (trim($_POST['tire_pattern']) == "" && trim($_POST['tire_pattern_name']) != "") {
                $tire_pattern_model = $this->model->get('tirepatternModel');
                $data_pattern = array(
                    'tire_pattern_name' => trim($_POST['tire_pattern_name']),
                );
                $tire_pattern_model->createTire($data_pattern);
                $data['tire_pattern'] = $tire_pattern_model->getLastTire()->tire_pattern_id;
            }
            elseif (trim($_POST['tire_pattern']) != "") {
                $data['tire_pattern'] = trim($_POST['tire_pattern']);
            }

            if ($_POST['yes'] != "") {
                


                    $tire_order_model->updateTire($data,array('tire_order_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $tire_order_model->createTire($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_order_model->getLastTire()->tire_order_id."|tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function addorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $tires = $this->model->get('tireorderModel');
            /**************/
            $tire_type = $_POST['tire_type'];
            /**************/
            $tire_order_type = $this->model->get('tireordertypeModel');

            $data = array(
                        
                        'tire_receive_date' => strtotime($_POST['tire_receive_date']),
                        'customer' => trim($_POST['customer']),
                        'tire_order_date' => strtotime($_POST['tire_order_date']),
                        
                        
                        );



            if ($_POST['yes'] != "") {
                $tires->updateTire($data,array('tire_order_id' => $_POST['yes']));

                $id_tire_order = $_POST['yes'];
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
            }
            else{

                $data['tire_order_sale'] = $_SESSION['userid_logined'];
                    $tires->createTire($data);
                    echo "Thêm thành công";

                $id_tire_order = $tires->getLastTire()->tire_order_id;

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tires->getLastTire()->tire_order_id."|tire_order|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
            }

            $total_number = 0;
            $total_price = 0;

            $tire_brand = $this->model->get('tirebrandModel');
            $tire_size = $this->model->get('tiresizeModel');
            $tire_pattern = $this->model->get('tirepatternModel');

            foreach ($tire_type as $v) {
                $data_tire_type = array(
                    'tire_brand' => $v['tire_brand'],
                    'tire_size' => $v['tire_size'],
                    'tire_pattern' => $v['tire_pattern'],
                    'tire_number' => str_replace(',','',$v['tire_number']),
                    'tire_price' => str_replace(',','',$v['tire_price']),
                    'tire_order' => $id_tire_order,
                );

                $total_number += $data_tire_type['tire_number'];
                $total_price += ($data_tire_type['tire_price']*$data_tire_type['tire_number']);

                if ($data_tire_type['tire_brand'] != "") {
                    $data_tire_type['tire_brand'] = $data_tire_type['tire_brand'];
                }
                else{
                    if (trim($v['tire_brand_name']) != "") {
                        if ($tire_brand->getTireByWhere(array('tire_brand_name' => trim($v['tire_brand_name'])))) {
                            $data_tire_type['tire_brand'] = $tire_brand->getTireByWhere(array('tire_brand_name' => trim($v['tire_brand_name'])))->tire_brand_id;
                        }
                        else{
                           $tire_brand->createTire(array('tire_brand_name' => trim($v['tire_brand_name'])));
                            $tire_brand_id = $tire_brand->getLastTire()->tire_brand_id;
                            $data_tire_type['tire_brand'] = $tire_brand_id; 
                        }
                        
                    }
                }

                if ($data_tire_type['tire_size'] != "") {
                    $data_tire_type['tire_size'] = $data_tire_type['tire_size'];
                }
                else{
                    if (trim($v['tire_size_number']) != "") {
                        if ($tire_size->getTireByWhere(array('tire_size_number' => trim($v['tire_size_number'])))) {
                            $data_tire_type['tire_size'] = $tire_size->getTireByWhere(array('tire_size_number' => trim($v['tire_size_number'])))->tire_size_id;
                        }
                        else{
                           $tire_size->createTire(array('tire_size_number' => trim($v['tire_size_number'])));
                            $tire_size_id = $tire_size->getLastTire()->tire_size_id;
                            $data_tire_type['tire_size'] = $tire_size_id; 
                        }
                        
                    }
                }

                if ($data_tire_type['tire_pattern'] != "") {
                    $data_tire_type['tire_pattern'] = $data_tire_type['tire_pattern'];
                }
                else{
                    if (trim($v['tire_pattern_name']) != "") {
                        if($tire_pattern->getTireByWhere(array('tire_pattern_name' => trim($v['tire_pattern_name'])))){
                            $data_tire_type['tire_pattern'] = $tire_pattern->getTireByWhere(array('tire_pattern_name' => trim($v['tire_pattern_name'])))->tire_pattern_id;
                        }
                        else{
                           $tire_pattern->createTire(array('tire_pattern_name' => trim($v['tire_pattern_name'])));
                            $tire_pattern_id = $tire_pattern->getLastTire()->tire_pattern_id;
                            $data_tire_type['tire_pattern'] = $tire_pattern_id; 
                        }
                        
                    }
                }

                if ($tire_order_type->getTireByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'tire_order'=>$id_tire_order))) {
                    $id_tire_order_type = $tire_order_type->getTireByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'tire_order'=>$id_tire_order))->tire_order_type_id;
                    $tire_order_type->updateTire($data_tire_type,array('tire_order_type_id'=>$id_tire_order_type));
                }
                else if (!$tire_order_type->getTireByWhere(array('tire_size'=>$data_tire_type['tire_size'],'tire_brand'=>$data_tire_type['tire_brand'],'tire_pattern'=>$data_tire_type['tire_pattern'],'tire_order'=>$id_tire_order))) {
                    $tire_order_type->createTire($data_tire_type);
                }
            }

            $data['tire_number'] = $total_number;
            $data['tire_price'] = $total_price;

            $tires->updateTire($data,array('tire_order_id' => $id_tire_order));
                    
        }
    }

    public function gettire(){
        if(isset($_POST['tire_order'])){
            $tire_order_type = $this->model->get('tireordertypeModel');
            $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_order_type.tire_brand = tire_brand.tire_brand_id AND tire_order_type.tire_size = tire_size.tire_size_id AND tire_order_type.tire_pattern = tire_pattern.tire_pattern_id');
            $customer_types = $tire_order_type->getAllTire(array('where'=>'tire_order='.$_POST['tire_order']),$join);
            

            $str = "";

            if(!$customer_types){

                $str .= '<tr class="'.$_POST['tire_order'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['tire_order'] .'">';
                    $str .= '<td>Thương hiệu</td>';
                    $str .= '<td><input required type="text" class="tire_brand" name="tire_brand[]" autocomplete="false" tabindex="8" >';
                    $str .= '<ul class="brand_list_id"></ul></td>';
                    $str .= '<td>Size</td>';
                    $str .= '<td><input required type="text" class="tire_size" name="tire_size[]" autocomplete="false" tabindex="9" >';
                    $str .= '<ul class="size_list_id"></ul></td>';
                    $str .= '<td>Mã gai</td>';
                    $str .= '<td><input required type="text" class="numbers tire_pattern" name="tire_pattern[]" autocomplete="false" tabindex="10" >';
                    $str .= '<ul class="tire_list_id"></ul></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['tire_order'] .'">';
                    $str .= '<td>Số lượng</td>';
                    $str .= '<td><input  type="text" class="numbers tire_number"  name="tire_number[]" tabindex="11" ></td>';
                    $str .= '<td>Giá chào</td>';
                    $str .= '<td><input  type="text"  class="number tire_price"  name="tire_price[]" tabindex="12" ></td></tr>';
                                        
                    $str .= '</table></td></tr>';
            }
            else{

                foreach ($customer_types as $v) {
                    $str .= '<tr class="'.$v->tire_order.'">';
                    $str .= '<td><input type="checkbox"  name="chk" class="'.$v->tire_pattern.'" data="'.$v->tire_brand.'" tabindex="'.$v->tire_size.'" title="'.$v->tire_order.'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->tire_order.'">';
                    $str .= '<td>Thương hiệu</td>';
                    $str .= '<td><input required type="text" disabled class="tire_brand" tabindex="8" name="tire_brand[]" data="'.$v->tire_brand.'" value="'.$v->tire_brand_name.'" autocomplete="false" >';
                    $str .= '<ul class="brand_list_id"></ul></td>';
                    $str .= '<td>Size</td>';
                    $str .= '<td><input required type="text" disabled class="tire_size" tabindex="9" name="tire_size[]" data="'.$v->tire_size.'" value="'.$v->tire_size_number.'" autocomplete="false" >';
                    $str .= '<ul class="size_list_id"></ul></td>';
                    $str .= '<td>Mã gai</td>';
                    $str .= '<td><input required type="text" disabled class="numbers tire_pattern" tabindex="10" name="tire_pattern[]" data="'.$v->tire_pattern.'" value="'.$v->tire_pattern_name.'" autocomplete="false" >';
                    $str .= '<ul class="size_list_id"></ul></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['tire_order'] .'">';
                    $str .= '<td>Số lượng</td>';
                    $str .= '<td><input  type="text" class="numbers tire_number" tabindex="11" value="'.$v->tire_number.'" name="tire_number[]"  ></td>';
                    $str .= '<td>Giá chào</td>';
                    $str .= '<td><input  type="text"  class="number tire_price" tabindex="12" value="'.$this->lib->formatMoney($v->tire_price).'" name="tire_price[]"  > </td></tr>';
                    
                    $str .= '</table></td></tr>';
                }
            }

            echo $str;
        }
    }

    public function deletetiretype(){
        if (isset($_POST['data'])) {
            $tire_type = $this->model->get('tireordertypeModel');

            $tire_type->queryTire('DELETE FROM tire_order_type WHERE tire_brand='.$_POST['data'].' AND tire_size='.$_POST['type'].' AND tire_pattern='.$_POST['pattern'].' AND tire_order='.$_POST['tire_order']);
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_order_model = $this->model->get('tireorderModel');
            $tire_type = $this->model->get('tireordertypeModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_order_model->deleteTire($data);
                       $tire_type->queryTire('DELETE FROM tire_order_type WHERE tire_order='.$data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_order|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_order_model->deleteTire($_POST['data']);
                        $tire_type->queryTire('DELETE FROM tire_order_type WHERE tire_order='.$_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_order|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>