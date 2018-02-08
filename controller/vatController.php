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
    public function printpage() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

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
                $data_invoice = array(
                    'order_tire_list'=>$value,
                    'invoice_tire_detail_number'=>$items['sohd'][0],
                    'invoice_tire_detail_date'=> strtotime($items['ngay'][0].'-'.$items['thang'][0].'-20'.$items['nam'][0]),
                    'invoice_tire_detail_brand'=>$items['brand'][$j],
                    'invoice_tire_detail_size'=>$items['size'][$j],
                    'invoice_tire_detail_pattern'=>$items['pattern'][$j],
                    'invoice_tire_detail_volume'=>$items['sl'][$j],
                    'invoice_tire_detail_price'=>str_replace(',', '', $items['dg'][$j]),
                    'invoice_tire_detail_create_user'=>$_SESSION['userid_logined'],
                    'invoice_tire_detail_vat'=>round(str_replace(',', '', $items['dg'][$j])*0.1),
                    'order_tire'=>$items['order'][$j],
                );
                $invoice_tire_detail_model->createInvoice($data_invoice);
                
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

        $this->view->show('vat/printpage');
    }

   public function getItem(){
        $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
        $items_model = $this->model->get('ordertireModel');
        $item_list_model = $this->model->get('ordertirelistModel');
        $batdau = $_GET['batdau'];
        $ketthuc = $_GET['ketthuc'];

        $items = $items_model->getAllTire(array('where'=>'delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).' AND vat>0','order_by'=>'order_number ASC'),array('table'=>'customer','where'=>'customer=customer_id'));
        
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
            $congtien = 0;

            foreach ($lists as $order) {
                

                $details = $invoice_tire_detail_model->getAllInvoice(array('where'=>'order_tire_list='.$item->order_tire_list_id));
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
                    $congtien += $dg*$sl;
                    $dg = round($dg);

                    $str .= '<tr style="font-style:italic" class="tr" data="'.$item->order_tire_id.'"><td><input name="check_i[]" type="checkbox" class="checkbox2" value="'.$order->order_tire_list_id.'" data="'.$item->order_tire_id.'" data-cus="'.$company.'" data-add="'.$dc.'" data-mst="'.$mst.'" data-ten="'.$ten.'" data-dvt="'.$dvt.'" data-sl="'.$sl.'" data-dg="'.$dg.'" data-tt="'.$tt.'" data-cong="'.$congtien.'" data-brand="'.$order->tire_brand.'" data-size="'.$order->tire_size.'" data-pattern="'.$order->tire_pattern.'"></td><td class="fix"></td><td class="fix">'.$item->order_number.'</td><td class="fix">'.$ten.'</td><td class="fix">'.$sl.'</td><td class="fix">'.$this->lib->formatMoney($dg).'</td><td class="fix"></td><td class="fix">'.$customer.'</td></tr>';
                }
            }
            
        }
        
        $str .= '</tbody></table>';
        echo $str;
   }

   public function getMoney(){
        echo $this->lib->convert_number_to_words($_GET['data']).' đồng';
   }


}
?>