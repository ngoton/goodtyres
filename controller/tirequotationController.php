<?php
Class tirequotationController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $thuonghieu = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $size = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $magai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_quotation_brand_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_quotation_pattern ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $thuonghieu = 0;
            $size = 0;
            $magai = 0;
        }


        $tire_brand_model = $this->model->get('tirequotationbrandModel');
        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_quotation_brand_name','order'=>'ASC'));
        $tire_size_model = $this->model->get('tirequotationsizeModel');
        $tire_sizes = $tire_size_model->getAllTire();
        $tire_pattern_model = $this->model->get('tirequotationpatternModel');
        $tire_patterns = $tire_pattern_model->getAllTire();
        $this->view->data['tire_quotation_brands'] = $tire_brands;
        $this->view->data['tire_quotation_sizes'] = $tire_sizes;
        $this->view->data['tire_quotation_patterns'] = $tire_patterns;


        $join = array('table'=>'tire_quotation_size, tire_quotation_pattern, tire_quotation_brand','where'=>'tire_quotation_size=tire_quotation_size_id AND tire_quotation_pattern=tire_quotation_pattern_id AND tire_quotation_brand=tire_quotation_brand_id');

        $tire_product_model = $this->model->get('tirequotationModel');
        
        $data_p = array(
            'order_by'=>'tire_quotation_brand_name',
            'order'=>'ASC',
            'where'=>'1=1',
        );

        if ($thuonghieu>0) {
            $data_p['where'] .= ' AND tire_quotation_brand_id = '.$thuonghieu;
        }

        $tire_producers = $tire_brand_model->getAllTire($data_p);
        $this->view->data['tire_data_producers'] = $tire_producers;

        $data_products = array();

        foreach ($tire_producers as $tire_producer) {
            $sonews = $limit;
            $x = ($page-1) * $sonews;
            $pagination_stages = 2;
            
            $data = array(
                'where'=>'( end_date IS NULL OR end_date >= '.strtotime(date('d-m-Y')).') AND tire_quotation_brand = '.$tire_producer->tire_quotation_brand_id,
            );
            
            
            if ($size>0) {
                $data['where'] .= ' AND tire_quotation_size = '.$size;
            }
            if ($magai>0) {
                $data['where'] .= ' AND tire_quotation_pattern = '.$magai;
            }
            
            $tongsodong = count($tire_product_model->getAllTire($data,$join));
            $tongsotrang = ceil($tongsodong / $sonews);


            $data = array(
                'order_by'=>$order_by,
                'order'=>$order,
                'limit'=>$x.','.$sonews,
                'where'=>'( end_date IS NULL OR end_date >= '.strtotime(date('d-m-Y')).') AND tire_quotation_brand = '.$tire_producer->tire_quotation_brand_id,
                );
            
            
            if ($size>0) {
                $data['where'] .= ' AND tire_quotation_size = '.$size;
            }
            if ($magai>0) {
                $data['where'] .= ' AND tire_quotation_pattern = '.$magai;
            }
          
            if ($keyword != '') {
                $search = '( tire_quotation_brand_name LIKE "%'.$keyword.'%" 
                    OR tire_quotation_size_number LIKE "%'.$keyword.'%" 
                    OR tire_quotation_pattern_name LIKE "%'.$keyword.'%" )';
                
                    $data['where'] = $data['where'].' AND '.$search;
            }

            

            
            $data_products[$tire_producer->tire_quotation_brand_id] = $tire_product_model->getAllTire($data,$join);
        }

        $this->view->data['lastID'] = isset($tire_product_model->getLastTire()->tire_quotation_id)?$tire_product_model->getLastTire()->tire_quotation_id:0;

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['thuonghieu'] = $thuonghieu;
        $this->view->data['size'] = $size;
        $this->view->data['magai'] = $magai;
        
        $this->view->data['data_products'] = $data_products;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tirequotation/index');
    }

    public function quotation(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $thuonghieu = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $size = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
            $magai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_quotation_brand_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_quotation_pattern ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $thuonghieu = 0;
            $size = 0;
            $magai = 0;
        }

        $tire_producer_model = $this->model->get('tireproducerModel');
        $tire_producers = $tire_producer_model->getAllTire(array('order_by'=>'tire_producer_name','order'=>'ASC'));
        $this->view->data['tire_producers'] = $tire_producers;

        $tire_pattern_model = $this->model->get('tirequotationpatternModel');
        $tire_size_model = $this->model->get('tireproductsizeModel');
        $tire_size_lists = $tire_size_model->getAllTire();
        $this->view->data['tire_size_lists'] = $tire_size_lists;

        $tire_patterns = $tire_pattern_model->getAllTire();
        $this->view->data['tire_patterns'] = $tire_patterns;

        $rowspan = array();
        $tire_sizes = array();

        $row_size = array();

        foreach ($tire_patterns as $tire_pattern) {
            $rowspan[$tire_pattern->tire_quotation_pattern_id] = 1;
            $sizes = $tire_size_model->queryTire('SELECT * FROM tire_product_size WHERE tire_product_size_id IN (SELECT tire_size FROM tire_buy_price,tire_product_pattern WHERE tire_pattern = tire_product_pattern_id AND (tire_product_pattern_type LIKE "'.$tire_pattern->tire_quotation_pattern_name.'" OR tire_product_pattern_type LIKE "'.$tire_pattern->tire_quotation_pattern_name.',%" OR tire_product_pattern_type LIKE "%,'.$tire_pattern->tire_quotation_pattern_name.'" OR tire_product_pattern_type LIKE "%,'.$tire_pattern->tire_quotation_pattern_name.',%")) ORDER BY priority ASC');
            foreach ($sizes as $size) {
                $rowspan[$tire_pattern->tire_quotation_pattern_id]++;
                $tire_sizes['size_number'][$tire_pattern->tire_quotation_pattern_id][] = $size->tire_product_size_number;
                $row_size[$tire_pattern->tire_quotation_pattern_id][] = $size->tire_product_size_id;
            }
        }


        $tire_price_model = $this->model->get('tirebuypriceModel');
        $tire_cost_logs_model = $this->model->get('tirecostlogsModel');
        $tire_stuff_model = $this->model->get('tirestuffModel');
        $tire_tax_model = $this->model->get('tiretaxModel');

        $tire_profit_model = $this->model->get('tireprofitModel');
        $tire_profits = $tire_profit_model->getAllTire();
        $profit = array();
        foreach ($tire_profits as $tire_profit) {
            $profit[$tire_profit->tire_brand] = $tire_profit->percent;
        }

        $logistics = $tire_cost_logs_model->getTire(1);
        $logistics = ($logistics->custom+$logistics->lift+$logistics->shipping+$logistics->trucking+$logistics->stevedore+$logistics->transfer+$logistics->deviation);
        $rate = 22530;

        $tire_prices = array();
        $join = array('table'=>'tire_product_pattern,tire_product_size','where'=>'tire_size=tire_product_size_id AND tire_pattern=tire_product_pattern_id');
        $prices = $tire_price_model->getAllTire(null,$join);
        foreach ($prices as $price) {
            $pt = explode(',', $price->tire_product_pattern_type);
            for ($i=0; $i < count($pt); $i++) { 
                $tax = $tire_tax_model->getTireByWhere(array('tire_size'=>$price->tire_size));
                $taxs = ($tax)?((($tax->tax*0.25)+((($tax->tax*0.25)+$tax->tax)*0.1))*$rate):0;
                $stuff = $tire_stuff_model->getTireByWhere(array('tire_size'=>$price->tire_size));
                $logs = ($stuff)?$logistics/$stuff->stuff:0;
                $gia = $price->tire_buy_price*$rate;

                $tire_prices[$price->tire_brand][$price->tire_product_size_number][$pt[$i]] = round($gia+$taxs+$logs,-3);
                $tire_prices[$price->tire_brand][$price->tire_product_size_number][$pt[$i]] = isset($profit[$price->tire_brand])?$tire_prices[$price->tire_brand][$price->tire_product_size_number][$pt[$i]]+($tire_prices[$price->tire_brand][$price->tire_product_size_number][$pt[$i]]*($profit[$price->tire_brand]/100)):$tire_prices[$price->tire_brand][$price->tire_product_size_number][$pt[$i]];
            }
        }

        $this->view->data['rowspan'] = $rowspan;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_prices'] = $tire_prices;
        $this->view->data['row_size'] = $row_size;

        $this->view->show('tirequotation/quotation');
    }

    public function xuat() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        
        $tire_brand_model = $this->model->get('tirequotationbrandModel');
        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_quotation_brand_name','order'=>'ASC'));
        $tire_size_model = $this->model->get('tirequotationsizeModel');
        $tire_sizes = $tire_size_model->getAllTire();
        $tire_pattern_model = $this->model->get('tirequotationpatternModel');
        $tire_patterns = $tire_pattern_model->getAllTire();
        $this->view->data['tire_quotation_brands'] = $tire_brands;
        $this->view->data['tire_quotation_sizes'] = $tire_sizes;
        $this->view->data['tire_quotation_patterns'] = $tire_patterns;



        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tirequotation/xuat');
    }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_product_model = $this->model->get('tireproductModel');
            $data = array(
                        
                        'tire_producer' => trim($_POST['tire_producer']),
                        'tire_product_name' => trim($_POST['tire_product_name']),
                        'tire_type' => trim($_POST['tire_type']),
                        'tire_size' => trim($_POST['tire_size']),
                        'tire_pattern' => trim($_POST['tire_pattern']),
                        'tire_pr' => trim($_POST['tire_pr']),
                        'tire_weight' => trim($_POST['tire_weight']),
                        'tire_depth' => trim($_POST['tire_depth']),
                        'tire_qty' => trim($_POST['tire_qty']),
                        'tire_price' => trim(str_replace(',','',$_POST['tire_price'])),
                        'tire_agent' => trim(str_replace(',','',$_POST['tire_agent'])),
                        'tire_retail' => trim(str_replace(',','',$_POST['tire_retail'])),
                        'tire_product_desc' => trim($_POST['tire_product_desc']),
                        'tire_product_content' => trim($_POST['tire_product_content']),
                        'tire_product_plies' => trim($_POST['tire_product_plies']),
                        'tire_product_tube' => trim($_POST['tire_product_tube']),
                        'tire_product_vehicle' => trim($_POST['tire_product_vehicle']),
                        'tire_product_feature' => trim($_POST['tire_product_feature']),
                        'tire_product_link' => trim($_POST['tire_product_link']),
                        );
        
            if ($_FILES['tire_product_thumb']['name'] != '') {
                $this->lib->upload_image('tire_product_thumb');
                $data['tire_product_thumb'] = $_FILES['tire_product_thumb']['name'];
            }

            if ($_POST['yes'] != "") {
                

                    $tire_product_model->updateTire($data,array('tire_product_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_product|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
            

                    $tire_product_model->createTire($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_product_model->getLastTire()->tire_product_id."|tire_product|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }

       return $this->view->redirect('tireproduct'); 
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_product_model = $this->model->get('tireproductModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_product_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_product|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_product_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_product|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function exportexcel(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tirequotation = $this->model->get('tirequotationModel');
            $tirepattern = $this->model->get('tirequotationpatternModel');
            $tiresize = $this->model->get('tirequotationsizeModel');
            $tirebrand = $this->model->get('tirequotationbrandModel');

            $tire_price = $_POST['tire_price_2'];
            $pattern = $_POST['pattern_arr_2'];
            $size = $_POST['size_arr_2'];
            $brand = $_POST['brand_arr_2'];

            $pattern_data = array();
            $sizes_data = array();
            $brands_data = array();

            $data = array();
            $str = "";

            foreach ($pattern as $key) {
                $pattern_data[] = $tirepattern->getTire($key)->tire_quotation_pattern_name;
            }

            foreach ($size as $key) {
                $size_data[] = $tiresize->getTire($key)->tire_quotation_size_number;
            }

            foreach ($brand as $key) {
                $v = $tirebrand->getTire($key)->tire_quotation_brand_name;
                $brand_data[] = $v;
                $str .= '<td style="font-weight:bold">'.$v.'</td>';
            }

            $html = '<style>.pdf table{width:100%;table-layout:fixed;}.pdf table td{text-align: center;vertical-align: middle;}.tb{height:100%}.tb td {display: table-cell;border: 1px solid black;min-width: 100px;text-align: center;padding:5px;vertical-align: middle;}</style>';
            $html .= '<div><table class="pdf" border="1" style="border: 1px solid black;text-align: center;margin-top:20px">';
            $html .= '<tr><td style="min-width: 100px;font-weight:bold">Mã gai</td><td style="min-width: 200px;font-weight:bold">Hình ảnh</td><td><table class="tb"><tr><td style="font-weight:bold">Kích cỡ</td>'.$str.'</tr></table></td></tr>';
            
            for ($i=0; $i < count($tire_price); $i++) { 
                $html .= '<tr>';
                $html .= '<td style="min-width:100px">'.$pattern_data[$i].'</td>';
                $html .= '<td style="min-width:200px"><img width="200" src="'.BASE_URL.'/public/images/upload/'.$pattern_data[$i].'.jpg" /></td>';
                $html .= '<td><table class="tb">';
                for ($j=0; $j < count($tire_price[$i]); $j++) { 
                    $html .= '<tr>';
                    $html .= '<td>'.$size_data[$j].'</td>';
                    for ($z=0; $z < count($tire_price[$i][$j]); $z++) { 
                        $html .= '<td>'.$tire_price[$i][$j][$z].'</td>';
                        $data[$pattern_data[$i]][$size_data[$j]][$brand_data[$z]] = $tire_price[$i][$j][$z];
                    }
                    $html .= '</tr>';
                }
                $html .= '</table></td>';
                $html .= '</tr>';
            }

            $html .= '</table></div>';

            $file="baogia.xls";
            
            header("Content-type: application/vnd.ms-excel;charset=utf-8;");
            header("Content-Disposition: attachment; filename=".$file);

            echo $html;
        }
    }

    public function sendmail(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require "lib/class.phpmailer.php";
            require("lib/Classes/tcpdf/tcpdf.php");

            $tirequotation = $this->model->get('tirequotationModel');
            $tirepattern = $this->model->get('tirequotationpatternModel');
            $tiresize = $this->model->get('tirequotationsizeModel');
            $tirebrand = $this->model->get('tirequotationbrandModel');

            $tire_price = $_POST['tire_price'];
            $pattern = $_POST['pattern_arr'];
            $size = $_POST['size_arr'];
            $brand = $_POST['brand_arr'];

            $pattern_data = array();
            $sizes_data = array();
            $brands_data = array();

            $data = array();
            $str = "";

            foreach ($pattern as $key) {
                $pattern_data[] = $tirepattern->getTire($key)->tire_quotation_pattern_name;
            }

            foreach ($size as $key) {
                $size_data[] = $tiresize->getTire($key)->tire_quotation_size_number;
            }

            foreach ($brand as $key) {
                $v = $tirebrand->getTire($key)->tire_quotation_brand_name;
                $brand_data[] = $v;
                $str .= '<td style="font-weight:bold">'.$v.'</td>';
            }

            $html = '<style>.pdf table{width:100%;table-layout:fixed;}.pdf table td{text-align: center;vertical-align: middle;}.tb{height:100%}.tb td {display: table-cell;border: 1px solid black;min-width: 100px;text-align: center;padding:5px;vertical-align: middle;}</style>';
            $html .= '<div><table class="pdf" border="1" style="border: 1px solid black;text-align: center;margin-top:20px">';
            $html .= '<tr><td style="min-width: 100px;font-weight:bold">Mã gai</td><td style="min-width: 200px;font-weight:bold">Hình ảnh</td><td><table class="tb"><tr><td style="font-weight:bold">Kích cỡ</td>'.$str.'</tr></table></td></tr>';
            
            for ($i=0; $i < count($tire_price); $i++) { 
                $html .= '<tr>';
                $html .= '<td style="min-width:100px">'.$pattern_data[$i].'</td>';
                $html .= '<td style="min-width:200px"><img width="200" src="'.BASE_URL.'/public/images/upload/'.$pattern_data[$i].'.jpg" /></td>';
                $html .= '<td><table class="tb">';
                for ($j=0; $j < count($tire_price[$i]); $j++) { 
                    $html .= '<tr>';
                    $html .= '<td>'.$size_data[$j].'</td>';
                    for ($z=0; $z < count($tire_price[$i][$j]); $z++) { 
                        $html .= '<td>'.$tire_price[$i][$j][$z].'</td>';
                        $data[$pattern_data[$i]][$size_data[$j]][$brand_data[$z]] = $tire_price[$i][$j][$z];
                    }
                    $html .= '</tr>';
                }
                $html .= '</table></td>';
                $html .= '</tr>';
            }

            $html .= '</table></div>';

            // create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Ngô Tôn');
            $pdf->SetTitle('Bảng báo giá');
            $pdf->SetSubject('BÁO GIÁ');
            $pdf->SetKeywords('Viet Trade, tire');

            // remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            //set margins
            $pdf->SetMargins(11, PDF_MARGIN_TOP, 11);

            //set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            //set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

            $pdf->SetFont('freeserif', '', 9);
            $pdf->AddPage();

            $left_cell_width = 60;
            $row_height = 6;

            $pdf->Image(BASE_URL . '/public/img/banggia.png', 0, 12, null, 36, null, null, 'N', false, null,'L');
            $pdf->Ln();
            $pdf->Ln('13');

            $pdf->writeHTML($html, true, false, true, false, '');

            $filename = "baogia.pdf";

            $pdf->Output($filename, 'F'); // save the pdf under filename


            $hostname = trim($_POST['hostname']);
            $user = trim($_POST['tendangnhap']);
            $pass = trim($_POST['matkhau']);
            $to = trim($_POST['nguoinhan']);
            $subject = trim($_POST['subject']);
            $noidung = stripslashes(trim($_POST['noidung']));
            $chuky = stripslashes(trim($_POST['chuky']));

            if ($noidung == "") {
                $noidung = '<div>Kính gửi Quý khách hàng,</div>
                            <div>Cảm ơn quý khách đã quan tâm đến sản phẩm của công ty GoodTyres - Nhà nhập khẩu & phân phối lốp xe tải bố kẽm chính hãng.</div>
                            <div>Để thuận tiện trong việc chọn lựa sản phẩm phù hợp với nhu cầu, công ty GoodTyres xin gửi bảng báo giá lốp xe bố kẽm.</div>';
            }

            $end = '<div>Rất mong được hợp tác cùng quý khách hàng.</div>';

            $noidung = $noidung.$html.$end.$chuky;

            // Khai báo tạo PHPMailer
            $mail = new PHPMailer();
            //Khai báo gửi mail bằng SMTP
            $mail->IsSMTP();
            //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
            // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
            // 1 = Thông báo lỗi ở client
            // 2 = Thông báo lỗi cả client và lỗi ở server
            $mail->SMTPDebug  = 0;
             
            $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
            $mail->Host       = $hostname; //host smtp để gửi mail
            $mail->Port       = 587; // cổng để gửi mail
            $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
            $mail->SMTPAuth   = true; //Xác thực SMTP
            $mail->CharSet = 'UTF-8';
            $mail->Username   = $user; // Tên đăng nhập tài khoản Gmail
            $mail->Password   = $pass; //Mật khẩu của gmail
            $mail->SetFrom($user, "GoodTyres"); // Thông tin người gửi
            $mail->AddReplyTo("cskh@goodtyres.vn","GoodTyres");// Ấn định email sẽ nhận khi người dùng reply lại.

            $pdf_content = file_get_contents($filename);

            $mail->AddAddress($to, $to);//Email của người nhận
            $mail->Subject = $subject; //Tiêu đề của thư
            $mail->IsHTML(true); // send as HTML   
            $mail->AddStringAttachment($pdf_content, "baogia.pdf", "base64", "application/pdf");  // note second item is name of emailed pdf
            //$mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
            $mail->MsgHTML($noidung); //Nội dung của bức thư.
            // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
            // Gửi thư với tập tin html

            $mail->AltBody = "BANG BAO GIA - GOODTYRES";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
            //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
            // For most clients expecting the Priority header:
            // 1 = High, 2 = Medium, 3 = Low
            $mail->Priority = 1;
            // MS Outlook custom header
            // May set to "Urgent" or "Highest" rather than "High"
            $mail->AddCustomHeader("X-MSMail-Priority: High");
            // Not sure if Priority will also set the Importance header:
            $mail->AddCustomHeader("Importance: High"); 
            $mail->Send();
            //Tiến hành gửi email và kiểm tra lỗi

            unlink($filename); // this will delete the file off of server

            echo $html;

        }
    }

    public function checkprice(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tirequotation = $this->model->get('tirequotationModel');
            $data = array(
                'tire_quotation_brand' => trim($_POST['brand']),
                'tire_quotation_size' => trim($_POST['size']),
                'tire_quotation_pattern' => trim($_POST['pattern']),
            );
            $price = $tirequotation->getTireByWhere($data);
            echo isset($price->tire_quotation_price)?$price->tire_quotation_price:null;
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $tirebrand = $this->model->get('tirequotationbrandModel');
            $tirepattern = $this->model->get('tirequotationpatternModel');
            $tiresize = $this->model->get('tirequotationsizeModel');
            $tirequotation = $this->model->get('tirequotationModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();

            $cell_ngay = $objWorksheet->getCellByColumnAndRow(0, 5);
            $ngay = $cell_ngay->getCalculatedValue();
            $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);                                      
            $ngaythang = $ngaythang-3600;

            $ngaytruoc = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$ngaythang).' -1 day')));


            
            for($col1 = 4; $col1 < $highestColumnIndex; ++ $col1) {
                $cell1 = $objWorksheet->getCellByColumnAndRow($col1, 7);
                // Check if cell is merged
                foreach ($objWorksheet->getMergeCells() as $cells1) {
                    if ($cell1->isInRange($cells1)) {
                        $currMergedCellsArray1 = PHPExcel_Cell::splitRange($cells1);
                        $cell1 = $objWorksheet->getCell($currMergedCellsArray1[0][0]);
                        break;
                        
                    }
                }
                //$val[] = $cell->getValue();
                //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                $val1[$col1] = $cell1->getCalculatedValue();
                foreach ($val1 as $value) {
                    if ($value != "") {
                        if ($tirebrand->getTireByWhere(array('tire_quotation_brand_name'=>trim($value)))) {
                            $id_brand = $tirebrand->getTireByWhere(array('tire_quotation_brand_name'=>trim($value)))->tire_quotation_brand_id;
                        }
                        else if (!$tirebrand->getTireByWhere(array('tire_quotation_brand_name'=>trim($value)))) {
                            $data_brand = array(
                                'tire_quotation_brand_name'=>trim($value),
                            );
                            $tirebrand->createTire($data_brand);
                            $id_brand = $tirebrand->getLastTire()->tire_quotation_brand_id;
                        }
                    }
                }

                    for ($row = 8; $row <= $highestRow; ++ $row) {
                        $val = array();
                        for ($col = 0; $col <= 3; ++ $col) {
                            $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                            // Check if cell is merged
                            foreach ($objWorksheet->getMergeCells() as $cells) {
                                if ($cell->isInRange($cells)) {
                                    $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                    $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                    break;
                                    
                                }
                            }
                            //$val[] = $cell->getValue();
                            //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                            $val[] = $cell->getCalculatedValue();
                            //here's my prob..
                            //echo $val;
                        }
                        
                            $cell2 = $objWorksheet->getCellByColumnAndRow($col1, $row);
                            // Check if cell is merged
                            foreach ($objWorksheet->getMergeCells() as $cells) {
                                if ($cell2->isInRange($cells)) {
                                    $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                    $cell2 = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                    break;
                                    
                                }
                            }
                            //$val[] = $cell->getValue();
                            //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                            $val[] = $cell2->getCalculatedValue();
                            //here's my prob..
                            //echo $val;
                        

                        if ($val[0] != null && $val[2] != null && $val[4] != "") {


                                if($tiresize->getTireByWhere(array('tire_quotation_size_number'=>trim($val[2])))) {
                                    $id_size = $tiresize->getTireByWhere(array('tire_quotation_size_number'=>trim($val[2])))->tire_quotation_size_id;
                                }
                                else if(!$tiresize->getTireByWhere(array('tire_quotation_size_number'=>trim($val[2])))){
                                    $tireproductsize_data = array(
                                        'tire_quotation_size_number' => trim($val[2]),
                                        );
                                    $tiresize->createTire($tireproductsize_data);

                                    $id_size = $tiresize->getLastTire()->tire_quotation_size_id;
                                }

                                if($tirepattern->getTireByWhere(array('tire_quotation_pattern_name'=>trim($val[0])))) {
                                    $id_pattern = $tirepattern->getTireByWhere(array('tire_quotation_pattern_name'=>trim($val[0])))->tire_quotation_pattern_id;
                                }
                                else if(!$tirepattern->getTireByWhere(array('tire_quotation_pattern_name'=>trim($val[0])))){
                                    $tirepattern_data = array(
                                        'tire_quotation_pattern_name' => trim($val[0]),
                                        );
                                    $tirepattern->createTire($tirepattern_data);

                                    $id_pattern = $tirepattern->getLastTire()->tire_quotation_pattern_id;
                                }

                                $pattern = $tirepattern->getTire($id_pattern)->tire_quotation_pattern_name;

                                if (!$tirequotation->getTireByWhere(array('tire_quotation_brand'=>$id_brand,'tire_quotation_size'=>$id_size,'tire_quotation_pattern'=>$id_pattern,'start_date'=>$ngaythang))) {
                                    $tirequotation->queryTire('UPDATE tire_quotation SET end_date = '.$ngaytruoc.' WHERE (end_date IS NULL OR end_date = 0) AND tire_quotation_brand='.$id_brand.' AND tire_quotation_size='.$id_size.' AND tire_quotation_pattern='.$id_pattern);

                                    $data = array(
                                        'tire_quotation_brand'=>$id_brand,
                                        'tire_quotation_size'=>$id_size,
                                        'tire_quotation_pattern'=>$id_pattern,
                                        'tire_quotation_price' => trim($val[4]),
                                        'tire_quotation_picture' => $pattern.'.jpg',
                                        'start_date' => $ngaythang,
                                    );
                                    $tirequotation->createTire($data);
                                }
                                else if ($tirequotation->getTireByWhere(array('tire_quotation_brand'=>$id_brand,'tire_quotation_size'=>$id_size,'tire_quotation_pattern'=>$id_pattern,'start_date'=>$ngaythang))) {
                                    $id_quotation = $tirequotation->getTireByWhere(array('tire_quotation_brand'=>$id_brand,'tire_quotation_size'=>$id_size,'tire_quotation_pattern'=>$id_pattern,'start_date'=>$ngaythang))->tire_quotation_id;
                                    $data = array(
                                        'tire_quotation_brand'=>$id_brand,
                                        'tire_quotation_size'=>$id_size,
                                        'tire_quotation_pattern'=>$id_pattern,
                                        'tire_quotation_price' => trim($val[4]),
                                        'tire_quotation_picture' => $pattern.'.jpg',
                                    );
                                    $tirequotation->updateTire($data,array('tire_quotation_id'=>$id_quotation));
                                }
                            
                        }
                        
                        //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                        // insert


                    }
                
            }


                
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('tirequotation');
        }
        $this->view->show('tirequotation/import');

    }

}
?>