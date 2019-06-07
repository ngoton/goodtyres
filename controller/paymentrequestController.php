<?php
Class paymentrequestController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đề nghị thanh toán';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'payment_request_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC, payment_request_number DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-01-'.date('Y');
            $ketthuc = date('t-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data[$user->user_id] = $user->username;
        }
        $this->view->data['user_data'] = $user_data;

        $payment_request_model = $this->model->get('paymentrequestModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'user','where'=>'payment_request_user=user_id');
        $data = array(
            'where' => 'payment_request_date >= '.strtotime($batdau).' AND payment_request_date < '.strtotime($ngayketthuc),
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND payment_request_user='.$_SESSION['userid_logined'];
        }
        
        $tongsodong = count($payment_request_model->getAllPayment($data,$join));
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
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'payment_request_date >= '.strtotime($batdau).' AND payment_request_date < '.strtotime($ngayketthuc),
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND payment_request_user='.$_SESSION['userid_logined'];
        }
        
      
        if ($keyword != '') {
            $search = '( payment_request_number LIKE "%'.$keyword.'%" 
                    OR payment_request_comment LIKE "%'.$keyword.'%" 
                    OR payment_request_money LIKE "%'.$keyword.'%" 
             )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $payment_requests = $payment_request_model->getAllPayment($data,$join);

        
        $this->view->data['payment_requests'] = $payment_requests;
       
        $this->view->data['lastID'] = isset($payment_request_model->getLastPayment()->payment_request_number)?$payment_request_model->getLastPayment()->payment_request_number:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('paymentrequest/index');
    }
    public function approve(){
        $payment_request_model = $this->model->get('paymentrequestModel');

        $payment_request_model->updatePayment(array('payment_request_user_approve'=>$_SESSION['userid_logined']),array('payment_request_id'=>$_POST['payment']));
        echo "Thành công";
    }

    public function getItem(){
        $payable_model = $this->model->get('payableModel');
        $shipment_vendor_model = $this->model->get('shipmentvendorModel');
        $customer_model = $this->model->get('customerModel');
        $order_model = $this->model->get('ordertireModel');
        $vendor_data = array();
        $vendors = $shipment_vendor_model->getAllVendor();
        foreach ($vendors as $vendor) {
            $vendor_data[$vendor->shipment_vendor_id] = $vendor->shipment_vendor_name;
        }

        $items = $payable_model->getAllCosts(array('where'=>'money>0 AND vendor>0 AND ( pay_money IS NULL OR pay_money=0 OR pay_money<money )','order_by'=>'payable_date DESC'));
        
        $str = '<table class="table_data" id="tblExport2">';
        $str .= '<thead><tr><th class="fix"><input type="checkbox" onclick="checkall(\'checkbox2\', this)" name="checkall"/></th><th class="fix">Ngày</th><th class="fix">Code</th><th class="fix">Phải trả</th><th class="fix">Nội dung</th><th class="fix">USD</th><th class="fix">Số tiền</th><th class="fix">Đã trả</th><th class="fix">Còn lại</th></tr></thead>';
        $str .= '<tbody>';

        foreach ($items as $item) {
            if ($item->order_tire>0) {
                $cus = $customer_model->getCustomer($order_model->getTire($item->order_tire)->customer);

                $str .= '<tr style="font-style:italic" class="tr"><td><input name="check_i[]" type="checkbox" class="checkbox2" value="'.$item->payable_id.'" data="'.$item->payable_id.'" data-code="'.$item->code.'" data-comment="'.str_replace($item->code.'-', '', $item->comment).' '.$cus->customer_name.'" data-money="'.$this->lib->formatMoney($item->money-$item->pay_money).'" data-money-usd="'.$item->money_usd.'" ></td><td class="fix">'.$this->lib->hien_thi_ngay_thang($item->payable_date).'</td><td class="fix">'.$item->code.'</td><td class="fix">'.$vendor_data[$item->vendor].'</td><td class="fix">'.$item->comment.' '.$cus->customer_name.'</td><td class="fix">'.$item->money_usd.'</td><td class="fix">'.$this->lib->formatMoney($item->money).'</td><td class="fix">'.$this->lib->formatMoney($item->pay_money).'</td><td class="fix">'.$this->lib->formatMoney($item->money-$item->pay_money).'</td></tr>';
            }
            else{
                $str .= '<tr style="font-style:italic" class="tr"><td><input name="check_i[]" type="checkbox" class="checkbox2" value="'.$item->payable_id.'" data="'.$item->payable_id.'" data-code="'.$item->code.'" data-comment="'.str_replace($item->code.'-', '', $item->comment).'" data-money="'.$this->lib->formatMoney($item->money-$item->pay_money).'" data-money-usd="'.$item->money_usd.'" ></td><td class="fix">'.$this->lib->hien_thi_ngay_thang($item->payable_date).'</td><td class="fix">'.$item->code.'</td><td class="fix">'.$vendor_data[$item->vendor].'</td><td class="fix">'.$item->comment.'</td><td class="fix">'.$item->money_usd.'</td><td class="fix">'.$this->lib->formatMoney($item->money).'</td><td class="fix">'.$this->lib->formatMoney($item->pay_money).'</td><td class="fix">'.$this->lib->formatMoney($item->money-$item->pay_money).'</td></tr>';
            }
            
            
        }
        
        $str .= '</tbody></table>';
        echo $str;
   }
   public function getitemadd(){
        $payment_request_detail_model = $this->model->get('paymentrequestdetailModel');
            
        $details = $payment_request_detail_model->getAllPayment(array('where'=>'payment_request='.$_POST['payment']));
        $str = "";
        $i = 1;
        if ($details) {
            foreach ($details as $detail) {
                $str .= '<tr>
                            <td class="width-3">'.$i++.'</td>
                            <td class="width-10"><input type="text" name="code[]" class="code" autocomplete="off" data="'.$detail->payable.'" alt="'.$detail->payment_request_detail_id.'" value="'.$detail->payment_request_detail_code.'"></td>
                            <td><input type="text" name="comment[]" class="comment" autocomplete="off" value="'.$detail->payment_request_detail_comment.'"></td>
                            <td class="width-10"><input type="text" name="money[]" class="money numbers text-right" autocomplete="off" value="'.$this->lib->formatMoney($detail->payment_request_detail_money).'"></td>
                            <td class="width-10"><input type="text" name="money_usd[]" class="money_usd text-right" autocomplete="off" value="'.$detail->payment_request_detail_money_usd.'"></td>
                          </tr>';
            }
        }
        else{
            $str .= '<tr>
                        <td class="width-3">1</td>
                        <td class="width-10"><input type="text" name="code[]" class="code" autocomplete="off"></td>
                        <td><input type="text" name="comment[]" class="comment" autocomplete="off"></td>
                        <td class="width-10"><input type="text" name="money[]" class="money numbers text-right" autocomplete="off"></td>
                        <td class="width-10"><input type="text" name="money_usd[]" class="money_usd text-right" autocomplete="off"></td>
                      </tr>';
        }

        $arr = array(
            'hang'=>$str,
        );
        echo json_encode($arr);
   }
    public function printpay() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $payment = $this->registry->router->param_id;

        include('lib/phpqrcode/qrlib.php'); 

        $tempDir = "public/images/qr/"; 
        array_map('unlink', glob($tempDir."*"));
        $codeContents = 'https://www.viet-trade.org/paymentrequestdetail/index/'.$payment;
        $fileName = 'qr_'.md5($codeContents).'.png'; 
        $pngAbsoluteFilePath = $tempDir.$fileName; 
        QRcode::png($codeContents, $pngAbsoluteFilePath); 
        $this->view->data['img'] = $pngAbsoluteFilePath;

        $payment_request_model = $this->model->get('paymentrequestModel');
        $payments = $payment_request_model->getPayment($payment);

        $this->view->data['payment_requests'] = $payments;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data[$staff->account] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $info_model = $this->model->get('infoModel');
        $this->view->data['infos'] = $info_model->getLastInfo();

        $this->view->show('paymentrequest/printpay');
    }
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $payment_request_model = $this->model->get('paymentrequestModel');
            $payment_request_detail_model = $this->model->get('paymentrequestdetailModel');
            
            $data = array(
                        
                'payment_request_number' => trim($_POST['payment_request_number']),
                'payment_request_comment' => addslashes(trim($_POST['payment_request_comment'])),
                'payment_request_date' => strtotime(str_replace('/', '-', $_POST['payment_request_date'])),
                'payment_request_receive' => trim($_POST['payment_request_receive']),
                'payment_request_destination' => trim($_POST['payment_request_destination']),
                'payment_request_money' => str_replace(',', '', $_POST['payment_request_money']),
                'payment_request_money_usd' => str_replace(',', '', $_POST['payment_request_money_usd']),
                'payment_request_origin' => trim($_POST['payment_request_origin']),
                'payment_request_type' => trim($_POST['payment_request_type']),
            );
            
            if ($_POST['yes'] != "") {        
                    $payment_request_model->updatePayment($data,array('payment_request_id' => $_POST['yes']));
                    $id_payment = $_POST['yes'];

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|payment_request|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                $data['payment_request_user'] = $_SESSION['userid_logined'];
                
                    $payment_request_model->createPayment($data);
                    $id_payment = $payment_request_model->getLastPayment()->payment_request_id;
                    
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_payment."|payment_request|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }

            $data_payable = "";

            $payables = $_POST['payable'];

            $arr_item = "";
            foreach ($payables as $v) {
                $data_detail = array(
                    'payment_request_detail_code'=>trim($v['code']),
                    'payment_request_detail_comment'=>addslashes(trim($v['comment'])),
                    'payment_request_detail_money'=>str_replace(',', '', $v['money']),
                    'payment_request_detail_money_usd'=>str_replace(',', '', $v['money_usd']),
                    'payable'=>$v['payable_id'],
                    'payment_request'=>$id_payment,
                );
                $id_list = $v['detail_id'];

                if ($data_detail['payment_request_detail_money']>0) {
                    

                    if ($id_list>0) {
                        $payment_request_detail_model->updatePayment($data_detail,array('payment_request_detail_id'=>$id_list));
                    }
                    else{
                        $payment_request_detail_model->createPayment($data_detail);
                        $id_list = $payment_request_detail_model->getLastPayment()->payment_request_detail_id;
                    }

                    if ($arr_item=="") {
                        $arr_item .= $id_list;
                    }
                    else{
                        $arr_item .= ','.$id_list;
                    }

                    

                    if ($data_payable=="") {
                        $data_payable .= $data_detail['payable'];
                    }
                    else{
                        $data_payable .= ','.$data_detail['payable'];
                    }
                    
                }

                
            }

            $item_olds = $payment_request_detail_model->queryPayment('SELECT * FROM payment_request_detail WHERE payment_request='.$id_payment.' AND payment_request_detail_id NOT IN ('.$arr_item.')');
                    foreach ($item_olds as $item_old) {
                        $payment_request_detail_model->queryPayment('DELETE FROM payment_request_detail WHERE payment_request_detail_id='.$item_old->payment_request_detail_id);
                    }
            $payment_request_model->updatePayment(array('payable'=>$data_payable),array('payment_request_id'=>$id_payment));
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payment_request_model = $this->model->get('paymentrequestModel');
            $payment_request_detail_model = $this->model->get('paymentrequestdetailModel');
            
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        $payment_request_detail_model->queryPayment('DELETE FROM payment_request_detail WHERE payment_request='.$data);
                       $payment_request_model->deletePayment($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|payment_request|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    $payment_request_detail_model->queryPayment('DELETE FROM payment_request_detail WHERE payment_request='.$_POST['data']);
                        $payment_request_model->deletePayment($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|payment_request|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }


}
?>