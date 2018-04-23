<?php
Class vatController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Xuất hóa đơn';

        $batdau = '01-'.date('m-Y');
        $ketthuc = date('t-m-Y');
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));
        $this->view->data['customers'] = $customers;

        $invoice_model = $this->model->get('invoicetireModel');

        $last_num = '0000000';
        $last_date = strtotime(date('d-m-Y'));

        $invoices = $invoice_model->getAllInvoice(array('order_by'=>'invoice_tire_number DESC','limit'=>1));
        foreach ($invoices as $invoice) {
            $last_num = $invoice->invoice_tire_number;
            $last_date = $invoice->invoice_tire_date+86400;
        }
        $this->view->data['last_num'] = $last_num;
        $this->view->data['last_date'] = $last_date;

        $this->view->show('vat/index');
    }
    public function view() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Xuất hóa đơn';


        $this->view->show('vat/view');
    }
    public function getItemAdd() {
        if (isset($_GET['sohoadon'])) {
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

            $invoices = $invoice_tire_detail_model->getAllInvoice(array('where'=>'invoice_tire_detail_number LIKE "%'.$_GET['sohoadon'].'%"'),array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'invoice_tire_detail_brand=tire_brand_id AND invoice_tire_detail_size=tire_size_id AND invoice_tire_detail_pattern=tire_pattern_id'));
            
            $ngay = "";
            $thang = "";
            $nam = "";
            $sohd = "";
            $tennguoimua = "";
            $tendv = "";
            $mst = "";
            $diachi = "";
            $ten = array();
            $dvt = array();
            $sl = array();
            $dg = array();
            $tt = array();
            $price_hide = array();
            $total_hide = array();
            $brand = array();
            $size = array();
            $pattern = array();
            $order = array();
            $orderlist = array();

            $i=1;

            foreach ($invoices as $invoice) {
                $ngay = date('d',$invoice->invoice_tire_detail_date);
                $thang = date('m',$invoice->invoice_tire_detail_date);
                $nam = substr(date('Y',$invoice->invoice_tire_detail_date),2);
                $sohd = $invoice->invoice_tire_detail_number;
                $tennguoimua = $invoice->person_hide;
                $tendv = $invoice->company_hide;
                $mst = $invoice->mst_hide;
                $diachi = $invoice->address_hide;
                $ten[] = $invoice->tire_brand_name.' '.$invoice->tire_size_number.' '.$invoice->tire_pattern_name;
                $dvt[] = substr($invoice->tire_size_number, -2)=='.5'?'Cái':'Bộ';
                $sl[] = $invoice->invoice_tire_detail_volume;
                $dg[] = $invoice->invoice_tire_detail_price;
                $tt[] = round($invoice->total_hide);
                $price_hide[] = $invoice->price_hide;
                $total_hide[] = $invoice->total_hide;
                $brand[] = $invoice->invoice_tire_detail_brand;
                $size[] = $invoice->invoice_tire_detail_size;
                $pattern[] = $invoice->invoice_tire_detail_pattern;
                $order[] = $invoice->order_tire;
                $orderlist[] = $invoice->order_tire_list;


                $i++;
            }

            $result = array(
                'ngay'=>$ngay,
                'thang'=>$thang,
                'nam'=>$nam,
                'sohd'=>$sohd,
                'tennguoimua'=>$tennguoimua,
                'tendv'=>$tendv,
                'mst'=>$mst,
                'diachi'=>$diachi,
                'ten'=>$ten,
                'dvt'=>$dvt,
                'sl'=>$sl,
                'dg'=>$dg,
                'tt'=>$tt,
                'price_hide'=>$price_hide,
                'total_hide'=>$total_hide,
                'brand'=>$brand,
                'size'=>$size,
                'pattern'=>$pattern,
                'order'=>$order,
                'orderlist'=>$orderlist,
                'total'=>$i,
            );

            echo json_encode($result);
        }
    }
    public function printpage() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $invoice_tire_model = $this->model->get('invoicetireModel');
        $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();

        foreach( $query as $param )
        {
          list($name, $value) = explode('=', $param, 2);
          $params[urldecode($name)][] = urldecode($value);
        }
        
        $items = $params;

        
        foreach ($items['order'] as $value) {
            if($value>0){
                $data_invoice = array(
                    'order_tire'=>$value,
                    'invoice_tire_number'=>$items['sohd'][0],
                    'invoice_tire_date'=> strtotime($items['ngay'][0].'-'.$items['thang'][0].'-20'.$items['nam'][0]),
                    'invoice_tire_create_user'=>$_SESSION['userid_logined'],
                );
                $invoices = $invoice_tire_model->getInvoiceByWhere(array('order_tire'=>$data_invoice['order_tire'],'invoice_tire_number'=>$data_invoice['invoice_tire_number']));
                if (!$invoices) {
                    $invoice_tire_model->createInvoice($data_invoice);
                }
                else{
                    $invoice_tire_model->updateInvoice($data_invoice,array('invoice_tire_id'=>$invoices->invoice_tire_id));
                }
            }
        }

        
        $j=0;
        foreach ($items['orderlist'] as $value) {
            if($value>0){

                if(!isset($conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]])){
                    $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['sl'][$j];
                    $dongia[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = str_replace(',', '', $items['dg'][$j]);
                    $giavat[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = round(str_replace(',', '', $items['dg'][$j])*0.1);

                    $dongia_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['price_hide'][$j];
                    $tt_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['total_hide'][$j];
                }
                $sl = $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]];

                $o_list = $order_tire_list_model->getTire($value);
                if ($sl>$o_list->tire_number) {
                    $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] -= $o_list->tire_number;
                    $sl = $o_list->tire_number;
                }

                $data_invoice = array(
                    'order_tire_list'=>$value,
                    'invoice_tire_detail_number'=>$items['sohd'][0],
                    'invoice_tire_detail_date'=> strtotime($items['ngay'][0].'-'.$items['thang'][0].'-20'.$items['nam'][0]),
                    'invoice_tire_detail_brand'=>$items['brand'][$j],
                    'invoice_tire_detail_size'=>$items['size'][$j],
                    'invoice_tire_detail_pattern'=>$items['pattern'][$j],
                    'invoice_tire_detail_volume'=>$sl,
                    'invoice_tire_detail_price'=>$dongia[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'invoice_tire_detail_create_user'=>$_SESSION['userid_logined'],
                    'invoice_tire_detail_vat'=>$giavat[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'order_tire'=>$items['order'][$j],
                    'person_hide'=>$items['tennguoimua'][0],
                    'company_hide'=>$items['tendv'][0],
                    'mst_hide'=>$items['mst'][0],
                    'address_hide'=>$items['diachi'][0],
                    'price_hide'=>$dongia_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'total_hide'=>$tt_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                );

                $invoices = $invoice_tire_detail_model->getInvoiceByWhere(array('order_tire'=>$data_invoice['order_tire'],'order_tire_list'=>$data_invoice['order_tire_list'],'invoice_tire_detail_number'=>$data_invoice['invoice_tire_detail_number']));
                if (!$invoices) {
                    $invoice_tire_detail_model->createInvoice($data_invoice);
                }
                else{
                    $invoice_tire_detail_model->updateInvoice($data_invoice,array('invoice_tire_detail_id'=>$invoices->invoice_tire_detail_id));
                }

                
            }

            $j++;
        }
        

        $this->view->data['items'] = $items;
        $this->view->data['nguoimh'] = $params['tennguoimua'][0];
        $this->view->data['company_name'] = $params['tendv'][0];
        $this->view->data['mst'] = $params['mst'][0];
        $this->view->data['customer_address'] = $params['diachi'][0];
        $this->view->data['thanhtoan'] = $params['thanhtoan'][0];
        $this->view->data['ngay'] = $params['ngay'][0];
        $this->view->data['thang'] = $params['thang'][0];
        $this->view->data['nam'] = $params['nam'][0];
        $this->view->data['sohd'] = $params['sohd'][0];
        $this->view->data['congtien'] = str_replace(',', '', $params['congtien'][0]);
        $this->view->data['tienthue'] = str_replace(',', '', $params['tienthue'][0]);
        $this->view->data['tongcong'] = str_replace(',', '', $params['tongcong'][0]);

        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
        $filename = "action_logs.txt";
        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$params['sohd'][0]."|invoice_tire|".$params['sohd'][0]."\n"."\r\n";
        
        $fh = fopen($filename, "a") or die("Could not open log file.");
        fwrite($fh, $text) or die("Could not write file!");
        fclose($fh);

        $this->view->show('vat/printpage');
    }
    public function printview() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();

        foreach( $query as $param )
        {
          list($name, $value) = explode('=', $param, 2);
          $params[urldecode($name)][] = urldecode($value);
        }
        
        $items = $params;

        

        $this->view->data['items'] = $items;
        $this->view->data['nguoimh'] = $params['tennguoimua'][0];
        $this->view->data['company_name'] = $params['tendv'][0];
        $this->view->data['mst'] = $params['mst'][0];
        $this->view->data['customer_address'] = $params['diachi'][0];
        $this->view->data['thanhtoan'] = $params['thanhtoan'][0];
        $this->view->data['ngay'] = $params['ngay'][0];
        $this->view->data['thang'] = $params['thang'][0];
        $this->view->data['nam'] = $params['nam'][0];
        $this->view->data['sohd'] = $params['sohd'][0];
        $this->view->data['congtien'] = str_replace(',', '', $params['congtien'][0]);
        $this->view->data['tienthue'] = str_replace(',', '', $params['tienthue'][0]);
        $this->view->data['tongcong'] = str_replace(',', '', $params['tongcong'][0]);

        $this->view->show('vat/printview');
    }

   public function getItem(){
        $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
        $items_model = $this->model->get('ordertireModel');
        $item_list_model = $this->model->get('ordertirelistModel');
        $batdau = $_GET['batdau'];
        $ketthuc = $_GET['ketthuc'];
        $customer = $_GET['customer'];

        if ($customer>0) {
            $items = $items_model->getAllTire(array('where'=>'customer = '.$customer.' AND ((delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') OR (order_tire_status IS NULL OR order_tire_status=0)) AND vat>0','order_by'=>'order_number ASC'),array('table'=>'customer','where'=>'customer=customer_id'));
        }
        else{
            $items = $items_model->getAllTire(array('where'=>'((delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') OR (order_tire_status IS NULL OR order_tire_status=0)) AND vat>0','order_by'=>'order_number ASC'),array('table'=>'customer','where'=>'customer=customer_id'));
        }
        
        
        $str = '<table class="table_data" id="tblExport2">';
        $str .= '<thead><tr><th class="fix"><input type="checkbox" onclick="checkall(\'checkbox2\', this)" name="checkall"/></th><th class="fix">Ngày</th><th class="fix">Số ĐH</th><th class="fix">KH</th><th class="fix">SL</th><th class="fix">Thu</th><th class="fix">Thuế</th><th class="fix">Tổng cộng</th></tr></thead>';
        $str .= '<tbody>';

        foreach ($items as $item) {
            $lists = $item_list_model->getAllTire(array('where'=>'order_tire='.$item->order_tire_id),array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id'));

            $str .= '<tr style="font-weight:bold" class="tr" data="'.$item->order_tire_id.'"><td><input name="check[]" type="checkbox" class="checkbox" value="'.$item->order_tire_id.'" data="'.$item->order_tire_id.'"></td><td class="fix">'.$this->lib->hien_thi_ngay_thang($item->delivery_date).'</td><td class="fix">'.$item->order_number.'</td><td class="fix">'.$item->customer_name.'</td><td class="fix">'.$item->order_tire_number.'</td><td class="fix">'.$this->lib->formatMoney($item->total-$item->vat+$item->discount+$item->reduce).'</td><td class="fix">'.$this->lib->formatMoney($item->vat).'</td><td class="fix">'.$this->lib->formatMoney($item->total).'</td></tr>';

            $customer = $item->customer_name;
            $company = $item->company_name;
            $mst = $item->mst;
            $dc = $item->customer_address;

            foreach ($lists as $order) {
                

                $details = $invoice_tire_detail_model->getAllInvoice(array('where'=>'order_tire_list='.$order->order_tire_list_id));
                $sum_vat = 0;
                foreach ($details as $detail) {
                    $sum_vat += $detail->invoice_tire_detail_volume;
                }

                if ($order->tire_number>$sum_vat) {

                    $ten = $order->tire_brand_name.' '.$order->tire_size_number.' '.$order->tire_pattern_name;
                    $dvt = substr($order->tire_size_number, -2)=='.5'?'Cái':'Bộ';
                    $sl = $order->tire_number-$sum_vat;
                    $dg = $item->check_price_vat==1?$order->tire_price_vat*$item->vat_percent*0.1/1.1:$order->tire_price*$item->vat_percent*0.1;
                    $tt = round($dg*$sl);
                    $congtien = $dg*$sl;
                    $dg1 = $dg;
                    $dg = round($dg);

                    $str .= '<tr style="font-style:italic" class="tr" data="'.$item->order_tire_id.'"><td><input name="check_i[]" type="checkbox" class="checkbox2" value="'.$order->order_tire_list_id.'" data="'.$item->order_tire_id.'" data-cus="'.$company.'" data-add="'.$dc.'" data-mst="'.$mst.'" data-ten="'.$ten.'" data-dvt="'.$dvt.'" data-sl="'.$sl.'" data-dg="'.$dg.'" data-dg1="'.$dg1.'" data-tt="'.$tt.'" data-cong="'.$congtien.'" data-brand="'.$order->tire_brand.'" data-size="'.$order->tire_size.'" data-pattern="'.$order->tire_pattern.'"></td><td class="fix"></td><td class="fix">'.$item->order_number.'</td><td class="fix">'.$ten.'</td><td class="fix">'.$sl.'</td><td class="fix">'.$this->lib->formatMoney($dg).'</td><td class="fix"></td><td class="fix">'.$customer.'</td></tr>';
                }
            }
            
        }
        
        $str .= '</tbody></table>';
        echo $str;
   }

   public function getMoney(){
        echo $this->lib->convert_number_to_words($_GET['data']).' đồng';
   }

   public function delete(){
        if (isset($_POST['data'])) {
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

            $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE invoice_tire_number = "'.$_POST['data'].'"');
            $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE invoice_tire_detail_number = "'.$_POST['data'].'"');

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|invoice_tire|".$_POST['data']."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
   }


}
?>